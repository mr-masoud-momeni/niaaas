<?php
defined('ABSPATH') || exit;

/**
 * نظرسنجی محصولات (مودال اجباری مجزا)
 *
 * به کاربرانی که از فروشگاه خرید کرده‌اند یک مودال نشان می‌دهد و از آن‌ها می‌خواهد
 * برای محصولات خریداری‌شده نظر و امتیاز ثبت کنند. تا زمانی که کاربر برای محصول(ها)
 * نظری ثبت نکرده باشد، مودال در هر بارگذاری صفحه نمایش داده می‌شود.
 */
class Nias_Product_Review_Survey
{
    private static $instance = null;

    /** @var array|null کش محصولات در انتظار نظر برای همین درخواست */
    private $pending_cache = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets'], 5);
        add_action('wp_footer', [$this, 'render_modal']);
        add_action('wp_ajax_nias_review_survey_save', [$this, 'handle_save']);
    }

    // ── تنظیمات ─────────────────────────────────────────────────────────────

    private function is_enabled(): bool
    {
        return (bool) get_option('nias_review_survey_enabled');
    }

    private function get_scope(): string
    {
        $scope = (string) get_option('nias_review_survey_scope', 'all');
        return $scope === 'specific' ? 'specific' : 'all';
    }

    private function get_product_id(): int
    {
        return (int) get_option('nias_review_survey_product_id', 0);
    }

    private function is_closable(): bool
    {
        return (bool) get_option('nias_review_survey_closable', 1);
    }

    private function get_max_shows(): int
    {
        return max(1, (int) get_option('nias_review_survey_max_shows', 1));
    }

    private function get_delay_days(): int
    {
        return max(0, (int) get_option('nias_review_survey_delay_days', 0));
    }

    private function get_show_count(int $user_id): int
    {
        return max(0, (int) get_user_meta($user_id, 'nias_review_survey_show_count', true));
    }

    private function increment_show_count(int $user_id): void
    {
        update_user_meta($user_id, 'nias_review_survey_show_count', $this->get_show_count($user_id) + 1);
    }

    private function get_title(): string
    {
        $t = (string) get_option('nias_review_survey_title', '');
        return $t !== '' ? $t : 'محصولات زیر را خریداری کردید';
    }

    private function get_subtitle(): string
    {
        $t = (string) get_option('nias_review_survey_subtitle', '');
        return $t !== '' ? $t : 'ممنون می‌شویم نظرتان را برای هر محصول ثبت کنید';
    }

    public static function is_wc_active(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_orders') && function_exists('wc_get_product');
    }

    // ── منطق نمایش ──────────────────────────────────────────────────────────

    /**
     * نگاشت محصولات خریداری‌شده به تاریخ خرید (timestamp).
     * برای محصولی که چند بار خریده شده، قدیمی‌ترین تاریخ خرید نگه داشته می‌شود
     * (چون کاربر بیشترین زمان را برای استفاده از آن داشته است).
     *
     * @return array<int,int> [product_id => purchase_timestamp]
     */
    private function get_purchased_product_map(int $user_id): array
    {
        if (!self::is_wc_active()) {
            return [];
        }

        $statuses = apply_filters('nias_review_survey_order_statuses', ['wc-completed']);

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status'      => $statuses,
            'limit'       => -1,
            'return'      => 'objects',
        ]);

        $map = [];
        foreach ($orders as $order) {
            if (!$order instanceof WC_Order) {
                continue;
            }
            $date = $order->get_date_completed() ?: $order->get_date_created();
            $ts   = $date ? $date->getTimestamp() : 0;
            foreach ($order->get_items() as $item) {
                $pid = (int) $item->get_product_id();
                if ($pid <= 0) {
                    continue;
                }
                if (!isset($map[$pid]) || ($ts > 0 && $ts < $map[$pid])) {
                    $map[$pid] = $ts; // قدیمی‌ترین تاریخ خرید
                }
            }
        }

        return $map;
    }

    /** شناسه محصولاتی که کاربر خریده (یکتا، وضعیت‌های تکمیل/در حال انجام) */
    private function get_purchased_product_ids(int $user_id): array
    {
        return array_keys($this->get_purchased_product_map($user_id));
    }

    /**
     * آیا کاربر برای این محصول قبلاً نظری ثبت کرده است؟
     * هر کامنتی در هر وضعیتی (تاییدشده، در انتظار بررسی، اسپم یا حذف‌شده) به‌معنای
     * ثبت نظر در نظر گرفته می‌شود تا مودال دوباره نمایش داده نشود.
     */
    private function has_reviewed(int $user_id, int $product_id): bool
    {
        foreach (['approve', 'hold', 'spam', 'trash'] as $status) {
            $count = get_comments([
                'post_id' => $product_id,
                'user_id' => $user_id,
                'status'  => $status,
                'count'   => true,
            ]);
            if ((int) $count > 0) {
                return true;
            }
        }
        return false;
    }

    /** فهرست محصولاتی که کاربر خریده ولی هنوز نظری ثبت نکرده */
    public function get_pending_products(int $user_id): array
    {
        if ($this->pending_cache !== null) {
            return $this->pending_cache;
        }

        $map = $this->get_purchased_product_map($user_id);

        // فقط محصولاتی که حداقل N روز از خریدشان گذشته باشد
        $delay_days = $this->get_delay_days();
        if ($delay_days > 0) {
            $threshold = time() - ($delay_days * DAY_IN_SECONDS);
            $map = array_filter($map, function ($ts) use ($threshold) {
                return $ts > 0 && $ts <= $threshold;
            });
        }

        $ids = array_keys($map);

        if ($this->get_scope() === 'specific') {
            $target = $this->get_product_id();
            $ids = ($target && in_array($target, $ids, true)) ? [$target] : [];
        }

        $pending = [];
        foreach ($ids as $pid) {
            if ($this->has_reviewed($user_id, $pid)) {
                continue;
            }
            $product = wc_get_product($pid);
            if (!$product || $product->get_status() !== 'publish') {
                continue;
            }
            $image = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
            if (!$image && function_exists('wc_placeholder_img_src')) {
                $image = wc_placeholder_img_src('thumbnail');
            }
            $pending[] = [
                'id'        => $pid,
                'name'      => $product->get_name(),
                'image'     => $image ?: '',
                'permalink' => get_permalink($pid),
            ];
        }

        $this->pending_cache = $pending;
        return $pending;
    }

    public function should_show_modal(): bool
    {
        if (!is_user_logged_in() || !$this->is_enabled() || !self::is_wc_active()) {
            return false;
        }

        $user_id = get_current_user_id();
        if ($this->get_show_count($user_id) >= $this->get_max_shows()) {
            return false;
        }

        return !empty($this->get_pending_products($user_id));
    }

    // ── انکیو و رندر ────────────────────────────────────────────────────────

    public function maybe_enqueue_assets()
    {
        if (!$this->should_show_modal()) {
            return;
        }

        wp_enqueue_script('jquery');
        [$rs_css_url, $rs_css_ver] = nias_login_asset('review-survey/assets/review-survey.css');
        wp_enqueue_style('nias-review-survey-style', $rs_css_url, [], $rs_css_ver);

        [$rs_js_url, $rs_js_ver] = nias_login_asset('review-survey/assets/review-survey.js');
        wp_enqueue_script('nias-review-survey-script', $rs_js_url, ['jquery'], $rs_js_ver, true);

        $data = [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('nias_review_survey'),
            'closable' => $this->is_closable() ? 1 : 0,
            'maxShows' => $this->get_max_shows(),
        ];
        wp_add_inline_script('nias-review-survey-script', 'window.niasReviewSurvey=' . wp_json_encode($data) . ';', 'before');
    }

    public function render_modal()
    {
        $user_id = get_current_user_id();
        if (!$this->should_show_modal()) {
            return;
        }

        $this->increment_show_count($user_id);
        $pending  = $this->get_pending_products($user_id);
        $closable = $this->is_closable();
        ?>
        <div class="nias-rs-overlay" id="nias-rs-overlay" data-closable="<?php echo $closable ? '1' : '0'; ?>">
            <div class="nias-rs-modal" role="dialog" aria-modal="true" aria-labelledby="nias-rs-title">
                <?php if ($closable) : ?>
                    <button type="button" class="nias-rs-close" id="nias-rs-close" aria-label="بستن">&times;</button>
                <?php endif; ?>

                <div class="nias-rs-head">
                    <h3 class="nias-rs-title" id="nias-rs-title"><?php echo esc_html($this->get_title()); ?></h3>
                    <p class="nias-rs-subtitle"><?php echo esc_html($this->get_subtitle()); ?></p>
                </div>

                <div class="nias-rs-list">
                    <?php foreach ($pending as $p) : ?>
                        <div class="nias-rs-card" data-product="<?php echo esc_attr($p['id']); ?>">
                            <div class="nias-rs-card__row">
                                <?php if (!empty($p['image'])) : ?>
                                    <img class="nias-rs-card__img" src="<?php echo esc_url($p['image']); ?>" alt="<?php echo esc_attr($p['name']); ?>" width="56" height="56" loading="lazy">
                                <?php endif; ?>
                                <span class="nias-rs-card__name"><?php echo esc_html($p['name']); ?></span>
                                <svg class="nias-rs-card__chevron" viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>

                            <div class="nias-rs-card__body">
                                <div class="nias-rs-stars" role="radiogroup" aria-label="امتیاز">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <button type="button" class="nias-rs-star" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> ستاره">
                                            <svg viewBox="0 0 24 24"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z"/></svg>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <textarea class="nias-rs-text" rows="3" placeholder="نظر خود را درباره این محصول بنویسید..."></textarea>
                                <div class="nias-rs-card__actions">
                                    <span class="nias-rs-msg"></span>
                                    <button type="button" class="nias-rs-submit">ثبت نظر</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    // ── ذخیره نظر ───────────────────────────────────────────────────────────

    public function handle_save()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'عدم دسترسی'], 403);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nias_review_survey')) {
            wp_send_json_error(['message' => 'درخواست نامعتبر'], 403);
        }
        if (!$this->is_enabled() || !self::is_wc_active()) {
            wp_send_json_error(['message' => 'غیرفعال'], 400);
        }

        $user_id    = get_current_user_id();
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $rating     = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        $text       = isset($_POST['text']) ? trim(sanitize_textarea_field(wp_unslash($_POST['text']))) : '';

        if ($product_id <= 0) {
            wp_send_json_error(['message' => 'محصول نامعتبر است'], 400);
        }
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'لطفاً امتیاز ستاره را انتخاب کنید'], 400);
        }
        if ($text === '') {
            wp_send_json_error(['message' => 'لطفاً متن نظر را بنویسید'], 400);
        }

        // امنیت: فقط محصولی که کاربر خریده و هنوز نظری برایش ثبت نکرده
        if (!in_array($product_id, $this->get_purchased_product_ids($user_id), true)) {
            wp_send_json_error(['message' => 'این محصول در سفارش‌های شما یافت نشد'], 403);
        }
        if ($this->has_reviewed($user_id, $product_id)) {
            wp_send_json_error(['message' => 'قبلاً برای این محصول نظر ثبت کرده‌اید'], 409);
        }

        $user      = wp_get_current_user();
        // نظر همیشه در وضعیت «در انتظار بررسی» ثبت می‌شود و به‌صورت خودکار تایید نمی‌شود.
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_content'      => $text,
            'user_id'              => $user_id,
            'comment_type'         => 'review',
            'comment_approved'     => 0,
            'comment_author_IP'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'comment_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        if (!$comment_id) {
            wp_send_json_error(['message' => 'خطا در ثبت نظر'], 500);
        }

        add_comment_meta($comment_id, 'rating', $rating);
        add_comment_meta($comment_id, 'verified', 1);

        if (class_exists('WC_Comments')) {
            WC_Comments::clear_transients($product_id);
        }

        // محصولات باقی‌مانده پس از ثبت این نظر
        $this->pending_cache = null;
        $remaining = count($this->get_pending_products($user_id));

        wp_send_json_success([
            'message'   => 'نظر شما ثبت شد و پس از تایید نمایش داده می‌شود. متشکریم!',
            'remaining' => $remaining,
        ]);
    }
}

Nias_Product_Review_Survey::get_instance();
