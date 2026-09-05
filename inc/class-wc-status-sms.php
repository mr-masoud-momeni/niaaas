<?php
defined('ABSPATH') || exit;

/**
 * پیامک وضعیت‌های سفارش ووکامرس
 *
 * با تغییر وضعیت هر سفارش، از طریق همان درگاه پیامکی تنظیم‌شده در تب «تنظیم درگاه»،
 * پیامک متنی به مشتری و/یا مدیر ارسال می‌کند. متن هر پیامک به‌ازای هر وضعیت قابل
 * تنظیم است و از متغیرهای ووکامرس مثل {order_id} و {billing_full_name} پشتیبانی می‌کند.
 */
class Nias_WC_Status_SMS
{
    const OPTION = 'nias_wc_sms_settings';

    /** متای هر سفارش: آیا هنگام ذخیرهٔ دستی، پیامک مشتری ارسال شود؟ (۱=بله، ۰=خیر) */
    const ORDER_META_SEND_CUSTOMER = '_nias_wc_send_customer_sms';

    /** متای هر سفارش: کد رهگیری مرسوله */
    const ORDER_META_TRACKING_CODE = '_nias_wc_tracking_code';

    /** @var Nias_WC_Status_SMS */
    private static $instance = null;

    /** @var array<int,bool> سفارش‌هایی که در این درخواست، پیامک مشتری‌شان باید سرکوب شود */
    private static $suppress_customer = [];

    /**
     * @var array<int,bool> سفارش‌هایی که مدیر در همین ذخیره، تیک «ارسال پیامک به مشتری»
     * را زده است. این تیک قفلِ «یک‌بار برای هر وضعیت» را برای پیامک مشتری باز می‌کند.
     */
    private static $force_customer = [];

    /** @var array<string,bool> نگهبان درون‌درخواستی: "order_id|status|recipient" */
    private static $sent_in_request = [];

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);

        // تغییر وضعیت سفارش (شامل pending→processing و ...)
        add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 20, 4);
        // ثبت سفارش جدید — وضعیت اولیه (مثلاً pending) رویداد تغییر وضعیت ندارد
        add_action('woocommerce_new_order', [$this, 'on_new_order'], 20, 2);

        // تاگل «ارسال پیامک به مشتری» در صفحهٔ ویرایش سفارش (ثبت/ویرایش دستی توسط مدیر)
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'render_order_toggle']);
        // باکس «کد رهگیری مرسوله» در صفحهٔ ویرایش سفارش (در صورت فعال بودن این قابلیت)
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'render_tracking_box']);
        // مقدار تاگل را پیش از اجرای ذخیرهٔ ووکامرس (که تغییر وضعیت را تریگر می‌کند) بخوان
        add_action('woocommerce_process_shop_order_meta', [$this, 'capture_order_toggle'], 1);
    }

    public static function is_wc_active(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_order_statuses');
    }

    // ── تنظیمات ─────────────────────────────────────────────────────────────

    public function register_settings()
    {
        register_setting('nias_login_settings', self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
            'default'           => [],
        ]);
    }

    public static function sanitize_settings($input): array
    {
        if (!is_array($input)) {
            // فرم بدون فیلدهای این تب ارسال شده (مثلاً ووکامرس غیرفعال است)؛
            // تنظیمات قبلی را دست‌نخورده نگه دار.
            $existing = get_option(self::OPTION, []);
            return is_array($existing) ? $existing : [];
        }

        $clean = [
            'enabled'           => empty($input['enabled']) ? 0 : 1,
            'send_on_new_order' => empty($input['send_on_new_order']) ? 0 : 1,
            'tracking_enabled'  => empty($input['tracking_enabled']) ? 0 : 1,
            'admin_phones'      => sanitize_text_field($input['admin_phones'] ?? ''),
            'statuses'          => [],
            'custom_vars'       => [],
        ];

        // متغیرهای متای سفارشی: نام متغیر + کلید متای سفارش (+ توضیح اختیاری)
        if (!empty($input['custom_vars']) && is_array($input['custom_vars'])) {
            $seen = [];
            foreach ($input['custom_vars'] as $cv) {
                if (!is_array($cv)) {
                    continue;
                }
                // نام متغیر: فقط حروف/عدد/زیرخط؛ آکولادها و فاصله حذف می‌شوند.
                $placeholder = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($cv['placeholder'] ?? ''));
                // کلید متا: می‌تواند با زیرخط شروع شود (متای خصوصی)؛ فقط فاصله حذف می‌شود.
                $meta_key = trim((string) ($cv['meta_key'] ?? ''));
                $label    = sanitize_text_field($cv['label'] ?? '');

                if ($placeholder === '' || $meta_key === '' || isset($seen[$placeholder])) {
                    continue;
                }
                $seen[$placeholder] = true;

                $clean['custom_vars'][] = [
                    'placeholder' => $placeholder,
                    'meta_key'    => $meta_key,
                    'label'       => $label,
                ];
            }
        }

        if (!empty($input['statuses']) && is_array($input['statuses'])) {
            foreach ($input['statuses'] as $status => $cfg) {
                $key = sanitize_key($status);
                if ($key === '' || !is_array($cfg)) {
                    continue;
                }
                $clean['statuses'][$key] = [
                    'customer_enabled'      => empty($cfg['customer_enabled']) ? 0 : 1,
                    'customer_text'         => sanitize_textarea_field($cfg['customer_text'] ?? ''),
                    'customer_pattern'      => sanitize_text_field($cfg['customer_pattern'] ?? ''),
                    'customer_pattern_vars' => sanitize_text_field($cfg['customer_pattern_vars'] ?? ''),
                    'admin_enabled'         => empty($cfg['admin_enabled']) ? 0 : 1,
                    'admin_text'            => sanitize_textarea_field($cfg['admin_text'] ?? ''),
                    'admin_pattern'         => sanitize_text_field($cfg['admin_pattern'] ?? ''),
                    'admin_pattern_vars'    => sanitize_text_field($cfg['admin_pattern_vars'] ?? ''),
                ];
            }
        }

        return $clean;
    }

    public static function get_settings(): array
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return wp_parse_args($saved, [
            'enabled'           => 0,
            'send_on_new_order' => 1,
            'tracking_enabled'  => 0,
            'admin_phones'      => '',
            'statuses'          => [],
            'custom_vars'       => [],
        ]);
    }

    /** وضعیت‌های سفارش (بدون پیشوند wc-) — شامل وضعیت‌های سفارشی ثبت‌شده */
    public static function get_statuses(): array
    {
        if (!function_exists('wc_get_order_statuses')) {
            return [];
        }
        $out = [];
        foreach (wc_get_order_statuses() as $key => $label) {
            $slug = strpos($key, 'wc-') === 0 ? substr($key, 3) : $key;
            $out[$slug] = $label;
        }
        return $out;
    }

    /** متغیرهای قابل استفاده در متن پیامک: placeholder => توضیح فارسی */
    public static function get_variables(): array
    {
        $vars = [
            '{order_id}'           => 'شناسه سفارش',
            '{order_number}'       => 'شماره سفارش',
            '{order_date}'         => 'تاریخ ثبت سفارش',
            '{order_time}'         => 'ساعت ثبت سفارش',
            '{order_status}'       => 'نام وضعیت فعلی سفارش',
            '{order_total}'        => 'مبلغ کل سفارش',
            '{order_subtotal}'     => 'جمع جزء (بدون هزینه ارسال)',
            '{order_shipping}'     => 'هزینه ارسال',
            '{order_discount}'     => 'مبلغ تخفیف',
            '{payment_method}'     => 'روش پرداخت',
            '{shipping_method}'    => 'روش ارسال',
            '{transaction_id}'     => 'شناسه تراکنش',
            '{items}'              => 'لیست اقلام سفارش (نام × تعداد)',
            '{items_count}'        => 'تعداد اقلام سفارش',
            '{billing_first_name}' => 'نام مشتری',
            '{billing_last_name}'  => 'نام خانوادگی مشتری',
            '{billing_full_name}'  => 'نام کامل مشتری',
            '{billing_phone}'      => 'موبایل مشتری',
            '{billing_email}'      => 'ایمیل مشتری',
            '{billing_state}'      => 'استان (صورتحساب)',
            '{billing_city}'       => 'شهر (صورتحساب)',
            '{billing_address}'    => 'آدرس صورتحساب',
            '{billing_postcode}'   => 'کد پستی صورتحساب',
            '{shipping_city}'      => 'شهر (حمل و نقل)',
            '{shipping_address}'   => 'آدرس حمل و نقل',
            '{customer_note}'      => 'یادداشت مشتری',
            '{order_link}'         => 'لینک مشاهده سفارش (مشتری)',
            '{admin_order_link}'   => 'لینک سفارش در پیشخوان (مدیر)',
            '{site_name}'          => 'نام سایت',
            '{site_url}'           => 'آدرس سایت',
        ];

        // متغیر کد رهگیری فقط وقتی این قابلیت فعال باشد پیشنهاد داده می‌شود
        $settings = self::get_settings();
        if (!empty($settings['tracking_enabled'])) {
            $vars['{tracking_code}'] = 'کد رهگیری مرسوله';
        }

        // متغیرهای لایسنس اسپات پلیر فقط وقتی یکی از افزونه‌های مرتبط
        // (دوره ساز نیاس یا افزونه اسپات پلیر) فعال باشد پیشنهاد می‌شوند.
        if (self::spotplayer_available()) {
            $vars['{spot_license_key}'] = 'کلید لایسنس اسپات پلیر';
            $vars['{spot_license_id}']  = 'شناسه لایسنس اسپات پلیر';
        }

        // متغیرهای متای سفارشی تعریف‌شده توسط مدیر (متای هر سفارش)
        foreach ($settings['custom_vars'] ?? [] as $cv) {
            if (empty($cv['placeholder'])) {
                continue;
            }
            $token = '{' . $cv['placeholder'] . '}';
            // متغیرهای هسته بازنویسی نمی‌شوند تا رفتار پیش‌فرض حفظ شود.
            if (isset($vars[$token])) {
                continue;
            }
            $vars[$token] = $cv['label'] !== '' ? $cv['label'] : ('متای سفارش: ' . $cv['meta_key']);
        }

        return $vars;
    }

    /** آیا افزونه‌ای که لایسنس اسپات پلیر می‌سازد روی سایت فعال است؟ */
    public static function spotplayer_available(): bool
    {
        return function_exists('nias_spot_enabled') || function_exists('spot_license_code');
    }

    /**
     * دادهٔ لایسنس اسپات پلیر ذخیره‌شده روی سفارش (دوره ساز نیاس یا افزونه
     * اصلی اسپات پلیر). اگر لایسنسی نباشد یا افزونه‌ای نصب نباشد، آرایه خالی
     * برمی‌گردد — فقط متای سفارش خوانده می‌شود و به هیچ افزونه‌ای وابسته نیست.
     */
    private static function order_spot_license(WC_Order $order): array
    {
        foreach (['_nias_spot_data', '_spotplayer_data'] as $meta_key) {
            $data = $order->get_meta($meta_key);
            if (is_array($data) && !empty($data['_id'])) {
                return $data;
            }
        }
        return [];
    }

    /** متن پیش‌فرض پیامک مشتری برای هر وضعیت */
    public static function default_customer_text(string $status): string
    {
        $defaults = [
            'pending'    => '{billing_full_name} عزیز، سفارش شما به شماره {order_number} ثبت شد و در انتظار پرداخت است. {site_name}',
            'processing' => '{billing_full_name} عزیز، پرداخت سفارش {order_number} با موفقیت انجام شد و سفارش شما در حال پردازش است. {site_name}',
            'on-hold'    => '{billing_full_name} عزیز، سفارش {order_number} شما در انتظار بررسی پرداخت است. {site_name}',
            'completed'  => '{billing_full_name} عزیز، سفارش {order_number} شما تکمیل و ارسال شد. از خرید شما متشکریم. {site_name}',
            'cancelled'  => '{billing_full_name} عزیز، سفارش {order_number} شما لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید. {site_name}',
            'refunded'   => '{billing_full_name} عزیز، مبلغ سفارش {order_number} به شما مسترد شد. {site_name}',
            'failed'     => '{billing_full_name} عزیز، پرداخت سفارش {order_number} ناموفق بود. لطفاً مجدداً اقدام کنید. {site_name}',
        ];
        return $defaults[$status] ?? 'سفارش {order_number} شما به وضعیت «{order_status}» تغییر کرد. {site_name}';
    }

    /** متن پیش‌فرض پیامک مدیر برای هر وضعیت */
    public static function default_admin_text(string $status): string
    {
        if ($status === 'processing' || $status === 'pending') {
            return 'سفارش جدید {order_number} | {billing_full_name} | {billing_phone} | مبلغ: {order_total} | وضعیت: {order_status}';
        }
        return 'سفارش {order_number} به وضعیت «{order_status}» تغییر کرد | {billing_full_name} | {billing_phone} | مبلغ: {order_total}';
    }

    // ── رویدادهای ووکامرس ───────────────────────────────────────────────────

    public function on_new_order($order_id, $order = null)
    {
        $settings = self::get_settings();
        if (empty($settings['send_on_new_order'])) {
            return;
        }
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }
        $this->dispatch($order, $order->get_status());
    }

    public function on_status_changed($order_id, $old_status, $new_status, $order)
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }
        $this->dispatch($order, $new_status);
    }

    // ── تاگل هر سفارش (ارسال پیامک به مشتری) ─────────────────────────────────

    /**
     * تاگل «ارسال پیامک وضعیت به مشتری» را در باکس مشخصات سفارش نمایش می‌دهد.
     * چون داخل فرم ویرایش سفارش رندر می‌شود، مقدارش هنگام ذخیره POST می‌شود.
     */
    public function render_order_toggle($order)
    {
        if (!$order instanceof WC_Order) {
            return;
        }
        // اگر پیامک وضعیت سفارش به‌صورت کلی غیرفعال باشد، تاگل نمایش داده نمی‌شود.
        $settings = self::get_settings();
        if (empty($settings['enabled'])) {
            return;
        }
        // پیش‌فرض روشن است؛ مقدار قبلی همان سفارش به‌خاطر سپرده می‌شود.
        $val     = $order->get_meta(self::ORDER_META_SEND_CUSTOMER);
        $checked = ($val === '' || $val === null) ? true : ((string) $val === '1');
        ?>
        <p class="form-field form-field-wide nias-wc-sms-order-toggle" style="margin-top:10px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                <input type="checkbox" name="nias_wc_send_customer_sms" value="1" <?php checked($checked, true); ?> style="width:16px;height:16px;">
                ارسال پیامک وضعیت به مشتری هنگام ذخیرهٔ این سفارش
            </label>
            <span class="description" style="display:block;margin-top:4px;">
                تا وقتی این گزینه روشن باشد، با هر تغییر وضعیت این سفارش پیامک برای مشتری ارسال می‌شود؛
                حتی اگر قبلاً برای همان وضعیت پیامکی رفته باشد. اگر خاموشش کنید، پیامکی برای مشتری
                ارسال نمی‌شود (پیامک مدیر تحت تأثیر نیست و برای هر وضعیت فقط یک‌بار ارسال می‌شود).
            </span>
        </p>
        <?php
    }

    /**
     * باکس «کد رهگیری مرسوله» را در بخش آدرس ارسال صفحهٔ ویرایش سفارش نمایش می‌دهد.
     * فقط وقتی این قابلیت در تنظیمات فعال باشد رندر می‌شود. چون داخل فرم ویرایش
     * سفارش است، مقدارش هنگام ذخیره POST شده و در capture_order_toggle ذخیره می‌شود.
     */
    public function render_tracking_box($order)
    {
        if (!$order instanceof WC_Order) {
            return;
        }
        $settings = self::get_settings();
        if (empty($settings['tracking_enabled'])) {
            return;
        }
        $code = (string) $order->get_meta(self::ORDER_META_TRACKING_CODE);
        ?>
        <p class="form-field form-field-wide nias-wc-tracking-box" style="margin-top:12px;">
            <label for="nias_wc_tracking_code" style="font-weight:600;">کد رهگیری مرسوله</label>
            <input type="text" id="nias_wc_tracking_code" name="nias_wc_tracking_code" class="short" dir="ltr" style="width:100%;" value="<?php echo esc_attr($code); ?>" placeholder="مثلاً کد رهگیری پستی">
            <span class="description" style="display:block;margin-top:4px;">
                این کد با متغیر <code>{tracking_code}</code> در متن پیامک‌های وضعیت قابل استفاده است.
            </span>
        </p>
        <?php
    }

    /**
     * مقدار تاگل را پیش از ذخیرهٔ ووکامرس می‌خواند و ذخیره می‌کند.
     * با اولویت ۱ اجرا می‌شود تا قبل از تریگر تغییر وضعیت، تصمیم مشخص باشد.
     */
    public function capture_order_toggle($order_id)
    {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            return;
        }

        // تاگل فقط وقتی فرم ویرایش سفارش ارسال شده معنا دارد (فیلد همیشه در فرم هست،
        // checkbox تیک‌نخورده در POST نمی‌آید). برای تشخیص ارسالِ این فرم به یک
        // فیلد همیشگیِ همان فرم تکیه می‌کنیم.
        if (!isset($_POST['order_status']) && !isset($_POST['_wp_http_referer'])) {
            return;
        }

        $send = !empty($_POST['nias_wc_send_customer_sms']);
        self::$suppress_customer[(int) $order_id] = !$send;

        // تیک‌خورده = مدیر صراحتاً ارسال را خواسته؛ حتی اگر برای این وضعیت قبلاً
        // پیامکی رفته باشد، دوباره ارسال می‌شود.
        self::$force_customer[(int) $order_id] = $send;

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $order->update_meta_data(self::ORDER_META_SEND_CUSTOMER, $send ? '1' : '0');

        // کد رهگیری مرسوله — فقط وقتی این قابلیت فعال باشد.
        // باید قبل از تریگر تغییر وضعیت ذخیره شود تا {tracking_code} در همان
        // پیامکِ همین ذخیره (مثلاً وضعیت «تکمیل شده») در دسترس باشد.
        $settings = self::get_settings();
        if (!empty($settings['tracking_enabled']) && isset($_POST['nias_wc_tracking_code'])) {
            $code = sanitize_text_field(wp_unslash($_POST['nias_wc_tracking_code']));
            if (function_exists('nias_normalize_digits')) {
                $code = nias_normalize_digits($code);
            }
            $order->update_meta_data(self::ORDER_META_TRACKING_CODE, $code);
        }

        $order->save();
    }

    /** آیا ارسال پیامک مشتری برای این سفارش در این درخواست مجاز است؟ */
    private function customer_sms_allowed(WC_Order $order): bool
    {
        $id = $order->get_id();
        if (array_key_exists($id, self::$suppress_customer)) {
            return !self::$suppress_customer[$id];
        }
        // اگر در همین درخواست تصمیمی گرفته نشده، به متای ذخیره‌شدهٔ سفارش رجوع کن.
        $val = $order->get_meta(self::ORDER_META_SEND_CUSTOMER);
        if ($val === '' || $val === null) {
            return true; // پیش‌فرض: ارسال شود
        }
        return (string) $val === '1';
    }

    // ── تست ──────────────────────────────────────────────────────────────────

    /**
     * ارسال آزمایشی پیامک یک وضعیت با استفاده از همان تنظیمات ذخیره‌شده و
     * داده‌های یک سفارش واقعی (برای جایگذاری متغیرها).
     *
     * @param string $status    اسلاگ وضعیت (بدون wc-)
     * @param string $phone     شمارهٔ مقصد تست
     * @param string $recipient 'customer' یا 'admin'
     * @param int    $order_id  شناسهٔ سفارش نمونه (۰ = آخرین سفارش)
     * @return true|string      true در صورت موفقیت، یا پیام خطا
     */
    public function send_test(string $status, string $phone, string $recipient, int $order_id = 0)
    {
        if (!self::is_wc_active()) {
            return 'ووکامرس فعال نیست.';
        }
        $recipient = $recipient === 'admin' ? 'admin' : 'customer';

        $settings = self::get_settings();
        $cfg = $settings['statuses'][$status] ?? [];

        // اگر تنظیمات ذخیره نشده باشد، از متن پیش‌فرض استفاده می‌کنیم
        if (empty($cfg) || !$this->has_content($cfg, $recipient)) {
            if ($recipient === 'admin') {
                $cfg[$recipient . '_text'] = self::default_admin_text($status);
            } else {
                $cfg[$recipient . '_text'] = self::default_customer_text($status);
            }
            $cfg[$recipient . '_pattern'] = '';
            $cfg[$recipient . '_pattern_vars'] = '';
        }

        $order = $order_id ? wc_get_order($order_id) : $this->get_latest_order();
        if (!$order) {
            return $order_id
                ? 'سفارشی با این شناسه یافت نشد.'
                : 'هیچ سفارشی برای نمونه‌گیری متغیرها یافت نشد؛ یک شناسهٔ سفارش وارد کنید.';
        }

        return $this->send_for_recipient($order, $phone, $cfg, $recipient);
    }

    /** آخرین سفارش ثبت‌شده (برای نمونه‌گیری متغیرها در تست). */
    private function get_latest_order()
    {
        if (!function_exists('wc_get_orders')) {
            return null;
        }
        $orders = wc_get_orders(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC']);
        return !empty($orders) ? $orders[0] : null;
    }

    // ── ارسال ────────────────────────────────────────────────────────────────

    private function dispatch(WC_Order $order, string $status)
    {
        $settings = self::get_settings();
        if (empty($settings['enabled'])) {
            return;
        }

        $cfg = $settings['statuses'][$status] ?? null;
        if (!$cfg) {
            return;
        }

        $order_id = $order->get_id();

        // اگر مدیر در همین ذخیرهٔ سفارش تیک «ارسال پیامک به مشتری» را زده باشد،
        // قفلِ «یک‌بار برای هر وضعیت» برای پیامک مشتری نادیده گرفته می‌شود.
        $force_customer = !empty(self::$force_customer[$order_id]);

        $sent = [];

        // پیامک مشتری
        if (!empty($cfg['customer_enabled'])
            && $this->has_content($cfg, 'customer')
            && $this->customer_sms_allowed($order)
            && !$this->sent_in_request($order_id, $status, 'customer')
            && ($force_customer || !$this->already_sent($order, $status, 'customer'))
        ) {
            $phone = $this->get_customer_phone($order);
            if ($phone) {
                $result = $this->send_for_recipient($order, $phone, $cfg, 'customer');
                $this->add_result_note($order, 'مشتری', $phone, $result);
                $this->mark_sent_in_request($order_id, $status, 'customer');
                $sent[] = 'customer';
            } else {
                $order->add_order_note('پیامک نیاس: شماره موبایل مشتری برای ارسال پیامک وضعیت یافت نشد.');
            }
        }

        // پیامک مدیر — همچنان برای هر وضعیت فقط یک‌بار (تا مدیر با هر ذخیره اسپم نشود)
        if (!empty($cfg['admin_enabled'])
            && $this->has_content($cfg, 'admin')
            && !$this->sent_in_request($order_id, $status, 'admin')
            && !$this->already_sent($order, $status, 'admin')
        ) {
            $phones = $this->get_admin_phones($settings);
            if ($phones) {
                foreach ($phones as $phone) {
                    $result = $this->send_for_recipient($order, $phone, $cfg, 'admin');
                    $this->add_result_note($order, 'مدیر', $phone, $result);
                }
                $this->mark_sent_in_request($order_id, $status, 'admin');
                $sent[] = 'admin';
            } else {
                $order->add_order_note('پیامک نیاس: شماره موبایل مدیر در تنظیمات «پیامک ووکامرس» وارد نشده است.');
            }
        }

        if ($sent) {
            foreach ($sent as $recipient) {
                $order->update_meta_data(self::sent_meta_key($status, $recipient), current_time('mysql'));
            }
            $order->save();
        }
    }

    /** کلید متای «قبلاً ارسال شده» برای هر وضعیت و گیرنده */
    private static function sent_meta_key(string $status, string $recipient): string
    {
        return '_nias_wc_sms_sent_' . $status . '_' . $recipient;
    }

    /**
     * آیا پیش‌تر (در درخواستی دیگر) برای این وضعیت و گیرنده پیامکی ارسال شده است؟
     * کلید مشترک نسخه‌های قبلی نیز بررسی می‌شود تا سفارش‌های قدیمی پس از به‌روزرسانی
     * دوباره پیامک نگیرند.
     */
    private function already_sent(WC_Order $order, string $status, string $recipient): bool
    {
        if ($order->get_meta(self::sent_meta_key($status, $recipient))) {
            return true;
        }
        return (bool) $order->get_meta('_nias_wc_sms_sent_' . $status);
    }

    /** نگهبان درون‌درخواستی تا new_order + status_changed پیامک دوتایی نفرستند */
    private function sent_in_request(int $order_id, string $status, string $recipient): bool
    {
        return !empty(self::$sent_in_request[$order_id . '|' . $status . '|' . $recipient]);
    }

    private function mark_sent_in_request(int $order_id, string $status, string $recipient)
    {
        self::$sent_in_request[$order_id . '|' . $status . '|' . $recipient] = true;
    }

    /** آیا برای این گیرنده محتوایی (پترن یا متن آزاد) تنظیم شده است؟ */
    private function has_content(array $cfg, string $recipient): bool
    {
        return trim((string) ($cfg[$recipient . '_pattern'] ?? '')) !== ''
            || trim((string) ($cfg[$recipient . '_text'] ?? '')) !== '';
    }

    /**
     * ارسال پیامک یک وضعیت برای یک گیرنده.
     * اگر «کد پترن» تنظیم شده باشد از پترن همان درگاه استفاده می‌شود؛
     * در غیر این صورت متن آزاد ارسال می‌شود.
     */
    private function send_for_recipient(WC_Order $order, string $phone, array $cfg, string $recipient)
    {
        $pattern = trim((string) ($cfg[$recipient . '_pattern'] ?? ''));

        if ($pattern !== '') {
            $params = $this->build_pattern_params((string) ($cfg[$recipient . '_pattern_vars'] ?? ''), $order);
            return $this->send_pattern($phone, $pattern, $params);
        }

        $text = $this->replace_variables((string) ($cfg[$recipient . '_text'] ?? ''), $order);
        return $this->send_sms($phone, $text);
    }

    /**
     * ساخت پارامترهای پترن از رشته‌ی «متغیرهای پترن».
     * هر توکن با کاما جدا می‌شود؛ توکن «نام:{متغیر}» پارامتر نام‌دار (برای SMS.ir و
     * درگاه‌های مشابه) و توکن «{متغیر}» پارامتر ترتیبی (برای ملی‌پیامک/فراز/...) می‌سازد.
     */
    private function build_pattern_params(string $spec, WC_Order $order): array
    {
        $params = [];
        $tokens = preg_split('/[,،]+/u', $spec, -1, PREG_SPLIT_NO_EMPTY);

        foreach ((array) $tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            if (strpos($token, ':') !== false) {
                // پارامتر نام‌دار: name:{variable}
                [$name, $value_tpl] = array_map('trim', explode(':', $token, 2));
                if ($name === '') {
                    continue;
                }
                $params[$name] = $this->replace_variables($value_tpl, $order);
            } else {
                // پارامتر ترتیبی
                $params[] = $this->replace_variables($token, $order);
            }
        }

        return $params;
    }

    private function send_sms(string $phone, string $text)
    {
        if (!class_exists('Nias_SMS_Gateway')) {
            require_once NIAS_LOGIN_INC . 'class-sms-gateway.php';
        }
        $gateway = new Nias_SMS_Gateway();
        return $gateway->send_text($phone, $text);
    }

    /** ارسال از طریق پترن درگاه تنظیم‌شده */
    private function send_pattern(string $phone, string $pattern, array $params)
    {
        if (!class_exists('Nias_SMS_Gateway')) {
            require_once NIAS_LOGIN_INC . 'class-sms-gateway.php';
        }
        $gateway = new Nias_SMS_Gateway();
        return $gateway->send($phone, $pattern, $params);
    }

    private function add_result_note(WC_Order $order, string $who, string $phone, $result)
    {
        if ($result === true) {
            $order->add_order_note(sprintf('پیامک نیاس: پیامک وضعیت به %s (%s) با موفقیت ارسال شد.', $who, $phone));
        } else {
            $order->add_order_note(sprintf(
                'پیامک نیاس: خطا در ارسال پیامک وضعیت به %s (%s): %s',
                $who,
                $phone,
                is_string($result) ? $result : 'ناموفق'
            ));
        }
    }

    // ── کمکی‌ها ──────────────────────────────────────────────────────────────

    private function get_customer_phone(WC_Order $order): string
    {
        $phone = (string) $order->get_billing_phone();

        if (!$phone && $order->get_customer_id()) {
            $phone = (string) (get_user_meta($order->get_customer_id(), 'phone', true)
                ?: get_user_meta($order->get_customer_id(), 'billing_phone', true));
        }

        if ($phone && function_exists('nias_sanitize_phone_enhanced')) {
            $normalized = nias_sanitize_phone_enhanced(nias_normalize_digits($phone));
            if ($normalized) {
                return $normalized;
            }
        }

        return trim($phone);
    }

    /** @return string[] شماره‌های مدیر (جداشده با , یا فاصله یا خط جدید) */
    private function get_admin_phones(array $settings): array
    {
        $raw    = (string) ($settings['admin_phones'] ?? '');
        $parts  = preg_split('/[\s,،;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $phones = [];

        foreach ((array) $parts as $part) {
            if (function_exists('nias_sanitize_phone_enhanced')) {
                $normalized = nias_sanitize_phone_enhanced(nias_normalize_digits($part));
                if ($normalized) {
                    $phones[] = $normalized;
                    continue;
                }
            }
            $phones[] = trim($part);
        }

        return array_values(array_unique(array_filter($phones)));
    }

    private function replace_variables(string $template, WC_Order $order): string
    {
        $created   = $order->get_date_created();
        $timestamp = $created ? $created->getOffsetTimestamp() : current_time('timestamp');

        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' × ' . $item->get_quantity();
        }

        $billing_full_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

        $spot_license = self::order_spot_license($order);

        $vars = [
            '{order_id}'           => (string) $order->get_id(),
            '{order_number}'       => (string) $order->get_order_number(),
            '{order_date}'         => date_i18n('Y/m/d', $timestamp),
            '{order_time}'         => date_i18n('H:i', $timestamp),
            '{order_status}'       => wc_get_order_status_name($order->get_status()),
            '{order_total}'        => $this->format_price($order->get_total(), $order),
            '{order_subtotal}'     => $this->format_price($order->get_subtotal(), $order),
            '{order_shipping}'     => $this->format_price($order->get_shipping_total(), $order),
            '{order_discount}'     => $this->format_price($order->get_total_discount(), $order),
            '{payment_method}'     => (string) $order->get_payment_method_title(),
            '{shipping_method}'    => (string) $order->get_shipping_method(),
            '{transaction_id}'     => (string) $order->get_transaction_id(),
            '{items}'              => implode('، ', $items),
            '{items_count}'        => (string) $order->get_item_count(),
            '{billing_first_name}' => (string) $order->get_billing_first_name(),
            '{billing_last_name}'  => (string) $order->get_billing_last_name(),
            '{billing_full_name}'  => $billing_full_name,
            '{billing_phone}'      => (string) $order->get_billing_phone(),
            '{billing_email}'      => (string) $order->get_billing_email(),
            '{billing_state}'      => $this->state_name($order->get_billing_country(), $order->get_billing_state()),
            '{billing_city}'       => (string) $order->get_billing_city(),
            '{billing_address}'    => trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
            '{billing_postcode}'   => (string) $order->get_billing_postcode(),
            '{shipping_city}'      => (string) $order->get_shipping_city(),
            '{shipping_address}'   => trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2()),
            '{customer_note}'      => (string) $order->get_customer_note(),
            '{tracking_code}'      => (string) $order->get_meta(self::ORDER_META_TRACKING_CODE),
            '{spot_license_key}'   => (string) ($spot_license['key'] ?? ''),
            '{spot_license_id}'    => (string) ($spot_license['_id'] ?? ''),
            '{order_link}'         => $order->get_view_order_url(),
            '{admin_order_link}'   => $order->get_edit_order_url(),
            '{site_name}'          => get_bloginfo('name'),
            '{site_url}'           => home_url(),
        ];

        // متغیرهای متای سفارشی تعریف‌شده توسط مدیر: مقدار از متای همین سفارش خوانده
        // می‌شود. متغیرهای هسته اولویت دارند و بازنویسی نمی‌شوند.
        $settings = self::get_settings();
        foreach ($settings['custom_vars'] ?? [] as $cv) {
            if (empty($cv['placeholder']) || empty($cv['meta_key'])) {
                continue;
            }
            $token = '{' . $cv['placeholder'] . '}';
            if (isset($vars[$token])) {
                continue;
            }
            $value = $order->get_meta($cv['meta_key']);
            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $vars[$token] = (string) $value;
        }

        /**
         * امکان افزودن/تغییر متغیرهای پیامک وضعیت سفارش
         *
         * @param array    $vars  placeholder => value
         * @param WC_Order $order
         */
        $vars = apply_filters('nias_wc_sms_variables', $vars, $order);

        return strtr($template, $vars);
    }

    private function format_price($amount, WC_Order $order): string
    {
        $formatted = wc_price((float) $amount, ['currency' => $order->get_currency()]);
        return trim(html_entity_decode(wp_strip_all_tags($formatted), ENT_QUOTES, 'UTF-8'));
    }

    private function state_name($country, $state): string
    {
        if ($state && function_exists('WC')) {
            $states = WC()->countries->get_states($country);
            if (is_array($states) && isset($states[$state])) {
                return (string) $states[$state];
            }
        }
        return (string) $state;
    }
}
