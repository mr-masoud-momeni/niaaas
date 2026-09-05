<?php

if (!defined('ABSPATH')) {
    exit;
}

class Nias_Login_Fastsell_Plugin
{

    private static $instance = null;

    /** شورت‌کد [nias_fastsell] در این صفحه رندر شده است؟ (مودال فوتر دیگر چاپ نمی‌شود) */
    private $inline_rendered = false;

    /** دارایی‌ها یک بار بیشتر enqueue نشوند */
    private $assets_enqueued = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        if (!get_option('nias_quick_purchase')) {
            // شورت‌کد حتی وقتی خرید سریع خاموش است ثبت می‌شود تا متن خام
            // [nias_fastsell] وسط صفحه چاپ نشود
            add_shortcode('nias_fastsell', '__return_empty_string');
            add_shortcode('nias_fastsell_add_to_cart', '__return_empty_string');
            return;
        }
        add_action('init', [$this, 'init']);

        // نسخه درون‌صفحه‌ای مودال — برای قرار دادن خرید سریع در صفحه سبد خرید/تسویه حساب
        add_shortcode('nias_fastsell', [$this, 'render_inline_shortcode']);

        // دکمه «افزودن به سبد» مودال خرید سریع به‌صورت شورت‌کد
        add_shortcode('nias_fastsell_add_to_cart', [$this, 'render_add_to_cart_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_nias_login_fastsell_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nias_login_fastsell_update_cart', [$this, 'ajax_update_cart']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_update_cart', [$this, 'ajax_update_cart']);
        add_action('wp_ajax_nias_login_fastsell_remove_cart_item', [$this, 'ajax_remove_cart_item']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_remove_cart_item', [$this, 'ajax_remove_cart_item']);
        add_action('wp_ajax_nias_login_fastsell_get_cart', [$this, 'ajax_get_cart']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_get_cart', [$this, 'ajax_get_cart']);
        add_action('wp_ajax_nias_login_fastsell_apply_coupon', [$this, 'ajax_apply_coupon']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_apply_coupon', [$this, 'ajax_apply_coupon']);
        add_action('wp_ajax_nias_login_fastsell_get_checkout_form', [$this, 'ajax_get_checkout_form']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_get_checkout_form', [$this, 'ajax_get_checkout_form']);
        add_action('wp_ajax_nias_login_fastsell_process_checkout', [$this, 'ajax_process_checkout']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_process_checkout', [$this, 'ajax_process_checkout']);
        add_action('wp_ajax_nias_login_fastsell_set_shipping', [$this, 'ajax_set_shipping']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_set_shipping', [$this, 'ajax_set_shipping']);
        add_action('wp_ajax_nias_login_fastsell_set_address', [$this, 'ajax_set_address']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_set_address', [$this, 'ajax_set_address']);
        add_action('wp_footer', [$this, 'modal_template']);
        add_filter('woocommerce_add_to_cart_form_action', [$this, 'modify_add_to_cart_action']);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'prevent_redirect'], 99);
        add_filter('woocommerce_cart_redirect_after_error', [$this, 'prevent_redirect'], 99);
        add_action('wp_ajax_nias_check_stock', [$this, 'ajax_check_stock']);
        add_action('wp_ajax_nopriv_nias_check_stock', [$this, 'ajax_check_stock']);

        // احراز هویت قبل از تسویه حساب — خودِ ورود/ثبت‌نام از همان endpointهای
        // /nias-login و /nias-verify انجام می‌شود (منطق مشترک با مودال ورود)؛
        // اینجا فقط nonce تازه‌ی خرید سریع پس از ورود صادر می‌شود
        add_action('wp_ajax_nias_login_fastsell_refresh_nonce', [$this, 'ajax_refresh_nonce']);
        add_action('wp_ajax_nopriv_nias_login_fastsell_refresh_nonce', [$this, 'ajax_refresh_nonce']);

        // ترتیب فیلدهای checkout: شهر بعد از استان
        add_filter('woocommerce_checkout_fields', [$this, 'reorder_checkout_fields']);

        // خرید سریع تکی: محدود کردن درگاه‌های پرداخت و روش‌های حمل و نقل به موارد انتخاب‌شده در ادمین
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_buy_now_gateways'], 100);
        add_filter('woocommerce_package_rates', [$this, 'filter_buy_now_shipping_rates'], 100);

        // حفظ سبد تا پرداخت: سبد هنگام ثبت سفارش خالی نمی‌شود، پس باید بعد از
        // قطعی شدن سفارش پاک شود — چه در همان درخواستِ بازگشت از درگاه
        // (order_status_changed) و چه در اولین بازدید صفحه تشکر (thankyou)
        add_action('woocommerce_order_status_changed', [$this, 'maybe_empty_cart_for_order'], 20);
        add_action('woocommerce_thankyou', [$this, 'maybe_empty_cart_for_order'], 20);

        // ...و در سمت مقابل، جلوی پاک‌سازی خودکار ووکامرس گرفته می‌شود:
        // wc_clear_cart_after_payment() به‌محض نشستن روی endpoint «سفارش دریافت شد»
        // سبد را بدون توجه به وضعیت سفارش خالی می‌کند و درگاه‌ها کاربرِ منصرف/ناموفق
        // را هم به همان صفحه برمی‌گردانند — دقیقاً همان حالتی که این گزینه باید بگیرد
        add_filter('woocommerce_should_clear_cart_after_payment', [$this, 'filter_should_clear_cart_after_payment']);
        add_action('template_redirect', [$this, 'legacy_prevent_cart_clear'], 5);

        // بارگذاری اسکریپت‌های صفحه پرداخت در تمام صفحات جلو
        add_action('wp', [$this, 'force_checkout_assets_context']);
    }

    /**
     * ووکامرس واقعاً بارگذاری شده است؟
     *
     * این را نمی‌شود در سازنده چک کرد: وردپرس افزونه‌ها را به ترتیب الفبا لود
     * می‌کند و «nias-login-signup» قبل از «woocommerce» می‌آید، پس آنجا همیشه
     * false برمی‌گردد. بنابراین بررسی داخل خودِ هندلرها انجام می‌شود که همه
     * بعد از plugins_loaded اجرا می‌شوند.
     */
    private function wc_available(): bool
    {
        return class_exists('WooCommerce') && function_exists('WC');
    }

    /**
     * بررسی می‌کند که آیا صفحه جاری باید از خرید سریع exclude شود:
     * سبد خرید، تسویه حساب، حساب کاربری، و صفحه تشکر (order-received)
     * فقط از آی‌دی صفحات و توابع WooCommerce استفاده می‌کند
     * (از is_cart/is_checkout استفاده نمی‌شود چون ممکن است توسط فیلترها تغییر کرده باشند)
     */
    private function is_woo_cart_or_checkout_page(): bool
    {
        $current_id = (int) get_queried_object_id();

        // سبد خرید
        $cart_page_id = (int) get_option('woocommerce_cart_page_id');
        if ($current_id > 0 && $current_id === $cart_page_id) {
            return true;
        }

        // تسویه حساب (شامل order-received که زیرصفحه checkout است)
        $checkout_page_id = (int) get_option('woocommerce_checkout_page_id');
        if ($current_id > 0 && $current_id === $checkout_page_id) {
            return true;
        }

        // صفحه order-received — endpoint زیر checkout است، آی‌دی صفحه همان checkout است
        // ولی برای اطمینان با is_wc_endpoint_url هم چک می‌کنیم
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            return true;
        }

        // حساب کاربری (my-account و تمام endpoint های زیرمجموعه‌اش)
        $myaccount_page_id = (int) get_option('woocommerce_myaccount_page_id');
        if ($current_id > 0 && $current_id === $myaccount_page_id) {
            return true;
        }
        // endpoint های my-account مثل orders، edit-account، lost-password و...
        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        return false;
    }

    /**
     * آیا محتوای صفحه جاری شورت‌کد [nias_fastsell] را دارد؟
     *
     * این بررسی باید پیش از رندر محتوا (روی hookهای wp و wp_enqueue_scripts)
     * جواب بدهد، چون دارایی‌های چک‌اوت ووکامرس همان‌جا لود می‌شوند.
     * علاوه بر post_content، دادهٔ المنتور هم دیده می‌شود چون آن‌ها محتوا را در
     * متای _elementor_data نگه می‌دارند نه در post_content.
     *
     * فیلتر nias_fastsell_has_inline_shortcode برای صفحه‌سازهای دیگر.
     */
    private function page_has_inline_shortcode(): bool
    {
        static $cached = null;
        if (null !== $cached) {
            return $cached;
        }

        $found = false;

        if (!is_admin() && is_singular()) {
            $post = get_post();
            if ($post instanceof WP_Post) {
                $content = (string) $post->post_content;

                $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
                if (is_string($elementor_data) && '' !== $elementor_data) {
                    $content .= ' ' . $elementor_data;
                }

                $found = has_shortcode($content, 'nias_fastsell');
            }
        }

        $cached = (bool) apply_filters('nias_fastsell_has_inline_shortcode', $found);

        return $cached;
    }

    /**
     * آیا درخواست جاری یکی از AJAX های مودال خرید سریع است؟
     */
    private function is_fastsell_ajax_request(): bool
    {
        if (!function_exists('wp_doing_ajax') || !wp_doing_ajax() || !isset($_REQUEST['action'])) {
            return false;
        }

        $action = (string) wp_unslash($_REQUEST['action']);

        // nias_check_stock پیشوند مشترک را ندارد ولی همان‌قدر به ووکامرس وابسته است
        return strpos($action, 'nias_login_fastsell_') === 0 || $action === 'nias_check_stock';
    }

    /**
     * خرید سریع تکی: فقط درگاه‌های انتخاب‌شده در ادمین در مودال فراخوانی شوند
     * لیست خالی = بدون محدودیت (همه درگاه‌های فعال)
     */
    public function filter_buy_now_gateways($gateways)
    {
        if (!get_option('nias_fastsell_buy_now') || !$this->is_fastsell_ajax_request()) {
            return $gateways;
        }
        $allowed = (array) get_option('nias_fastsell_buy_now_gateways', []);
        if (empty($allowed)) {
            return $gateways;
        }
        return array_intersect_key($gateways, array_flip($allowed));
    }

    /**
     * خرید سریع تکی: فقط روش‌های حمل و نقل انتخاب‌شده در ادمین در مودال فراخوانی شوند
     * لیست خالی = بدون محدودیت (همه روش‌های فعال)
     */
    public function filter_buy_now_shipping_rates($rates)
    {
        if (!get_option('nias_fastsell_buy_now') || !$this->is_fastsell_ajax_request()) {
            return $rates;
        }
        $allowed = (array) get_option('nias_fastsell_buy_now_shipping', []);
        if (empty($allowed)) {
            return $rates;
        }
        return array_intersect_key($rates, array_flip($allowed));
    }

    /**
     * فراخوانی اسکریپت‌های صفحه checkout در تمام صفحات (برای مودال)
     * در صفحات سبد خرید و تسویه حساب اجرا نمی‌شود
     *
     * فیلتر woocommerce_is_checkout فقط در بازه‌ی wp_enqueue_scripts فعال می‌شود؛
     * اگر در کل چرخه رندر فعال بماند، پلاگین‌های سئو (مثل Rank Math) همه صفحات را
     * صفحه تسویه‌حساب تشخیص داده و متای robots را noindex می‌کنند.
     */
    public function force_checkout_assets_context()
    {
        // is_checkout() تابع ووکامرس است؛ بدون گارد، روی سایتی که ووکامرس ندارد
        // (یا غیرفعال شده) این متد روی هوک wp اجرا می‌شود و کل فرانت را می‌خواباند
        if (!$this->wc_available()) {
            return;
        }
        if (is_admin() || is_checkout()) {
            return;
        }
        // صفحه سبد خرید/حساب کاربری فقط وقتی استثنا می‌شود که شورت‌کد درون‌صفحه‌ای
        // آنجا نباشد؛ با شورت‌کد، فرم تسویه داخل همان صفحه رندر می‌شود و به
        // اسکریپت‌های چک‌اوت ووکامرس نیاز دارد
        if ($this->is_woo_cart_or_checkout_page() && !$this->page_has_inline_shortcode()) {
            return;
        }
        add_action('wp_enqueue_scripts', function () {
            add_filter('woocommerce_is_checkout', '__return_true', 5);
        }, 0);
        add_action('wp_enqueue_scripts', function () {
            remove_filter('woocommerce_is_checkout', '__return_true', 5);
        }, PHP_INT_MAX);
    }

    /**
     * شهر (billing_city) را بعد از استان (billing_state) نمایش بده
     * priority پیش‌فرض: state=80, city=70 — city را به 85 می‌بریم
     * همچنین تنظیمات ترتیب، نمایش و اجباری بودن فیلدها را از آپشن‌های ادمین اعمال می‌کند
     */
    public function reorder_checkout_fields($fields)
    {
        $saved = get_option('nias_fastsell_checkout_fields', []);

        if (empty($saved)) {
            // رفتار پیش‌فرض: شهر بعد از استان
            if (isset($fields['billing']['billing_city'])) {
                $fields['billing']['billing_city']['priority'] = 85;
            }
            return $fields;
        }

        foreach ($saved as $field_key => $cfg) {
            // تشخیص گروه فیلد (billing / shipping / order)
            if (strpos($field_key, 'billing_') === 0) {
                $group = 'billing';
            } elseif (strpos($field_key, 'shipping_') === 0) {
                $group = 'shipping';
            } else {
                $group = 'order';
            }

            if (!isset($fields[$group][$field_key])) {
                continue;
            }

            // نمایش / پنهان
            if (empty($cfg['visible'])) {
                unset($fields[$group][$field_key]);
                continue;
            }

            // اجباری بودن
            $fields[$group][$field_key]['required'] = !empty($cfg['required']);

            // ترتیب — priority در WooCommerce به صورت عددی کار می‌کند (کمتر = اول)
            $fields[$group][$field_key]['priority'] = (int) ($cfg['order'] ?? 99) * 10;
        }

        return $fields;
    }

    public function init()
    {
        // load_plugin_textdomain is likely handled by the main plugin

        // تک‌نقطه‌ی مهار AJAX: بدنه‌ی همه‌ی هندلرها مستقیم سراغ WC() می‌رود و
        // بدون ووکامرس فتال می‌دهد. هوک init در admin-ajax.php هم اجرا می‌شود،
        // پس اینجا قبل از رسیدن به هندلر جلویش گرفته می‌شود.
        if (!$this->wc_available() && $this->is_fastsell_ajax_request()) {
            wp_send_json_error(
                ['message' => __('فروشگاه در دسترس نیست', 'nias-login-signup')],
                503
            );
        }
    }

    public function prevent_redirect($url)
    {
        // در صفحات سبد خرید و تسویه حساب، ریدایرکت‌های WooCommerce دست نخورد
        if ($this->is_woo_cart_or_checkout_page()) {
            return $url;
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }
        return $url;
    }

    public function enqueue_scripts()
    {
        // بدون ووکامرس، مودال خرید سریع هیچ کاری نمی‌تواند بکند — دارایی‌هایش هم لود نشود
        if (is_admin() || !$this->wc_available()) {
            return;
        }
        // در صفحات سبد خرید و تسویه حساب WooCommerce، اسکریپت‌های خرید سریع لود نمی‌شوند
        // — مگر اینکه کاربر شورت‌کد [nias_fastsell] را همان‌جا گذاشته باشد
        if ($this->is_woo_cart_or_checkout_page() && !$this->page_has_inline_shortcode()) {
            return;
        }
        $this->enqueue_assets();
    }

    /**
     * دارایی‌های فرانت خرید سریع.
     * علاوه بر wp_enqueue_scripts، از خودِ شورت‌کد هم صدا زده می‌شود (صفحه‌سازهایی
     * که محتوا را در post_content نگه نمی‌دارند)، پس باید idempotent بماند.
     */
    public function enqueue_assets()
    {
        if ($this->assets_enqueued) {
            return;
        }
        $this->assets_enqueued = true;

        [$fs_css_url, $fs_css_ver] = nias_login_asset('fastsell/assets/easysale.css');
        wp_enqueue_style('nias-login-fastsell-style', $fs_css_url, [], $fs_css_ver);

        [$fs_js_url, $fs_js_ver] = nias_login_asset('fastsell/assets/easysale.js');
        wp_enqueue_script('nias-login-fastsell-script', $fs_js_url, ['jquery'], $fs_js_ver, true);

        wp_localize_script('nias-login-fastsell-script', 'niasLoginFastsellData', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('nias_login_fastsell_nonce'),
            'debug_log' => (bool) get_option('nias_fastsell_debug_log', false),
            'buy_now'   => (bool) get_option('nias_fastsell_buy_now', false),
            // خاموش که باشد، کلیک روی «افزودن به سبد» فقط محصول را اضافه می‌کند و
            // مودال باز نمی‌شود (باز کردن از روی آیکون سبد همچنان کار می‌کند)
            'open_modal_after_add'     => (bool) get_option('nias_fastsell_open_modal_after_add', 1),
            'custom_add_to_cart_class' => (string) get_option('nias_fastsell_custom_add_to_cart_class', ''),
            'open_modal_selector'      => (string) get_option('nias_fastsell_open_modal_selector', ''),

            // گیت احراز هویت به همان endpointهای مودال ورود پست می‌کند تا منطق
            // شرطی (ایمیل/موبایل، رمز، رمز+کد، رمز دستی، بلاک IP) یکجا بماند
            'login_url'  => esc_url(home_url('/nias-login')),
            'verify_url' => esc_url(home_url('/nias-verify')),
            'forgot_url' => esc_url(home_url('/nias-forgot-password')),
            // «افزونه حمل و نقل ووکامرس» فیلد شهر را با یک select از ترم‌های
            // تاکسونومی state_city جایگزین می‌کند (مقدار = شناسه ترم) و فیلد محله
            // اضافه می‌کند. اسکریپت خودش فقط در is_checkout لود می‌شود و هندلرهایش
            // را مستقیم روی DOM اولیه می‌بندد، پس در فرم AJAXی ما کار نمی‌کند و
            // باید خودمان لیست شهر/محله را delegated بارگذاری کنیم.
            'pws_active' => defined('PWS_VERSION'),
            'auth'       => [
                'email_activate'        => (bool) get_option('nias_email_activate'),
                'email_phone_activate'  => (bool) get_option('nias_email_phone_activate'),
                'password_activate'     => (bool) get_option('nias_password_activate'),
                'password_otp_activate' => (bool) get_option('nias_password_otp_activate'),
                'manual_password'       => (bool) (
                    get_option('nias_manual_password_activate')
                    && (get_option('nias_password_activate') || get_option('nias_password_otp_activate'))
                ),
            ],
        ]);

        // اسکریپت‌های WooCommerce مربوط به صفحه پرداخت
        wp_enqueue_script('wc-city-select');
        wp_enqueue_script('wc-country-select');
        wp_enqueue_script('wc-checkout');
        wp_enqueue_script('selectWoo');
        wp_enqueue_style('selectWoo');
        // ووکامرس استایل select2 را با هندل «select2» ثبت می‌کند نه «selectWoo».
        // بدون آن، سلکت‌های شهر/محله در مودال بدون استایل رندر می‌شوند — افزونه‌ی
        // حمل و نقل خودش این استایل را فقط در صفحه پرداخت لود می‌کند.
        wp_enqueue_style('select2');

        // لیست شهرهای ایران — فایل محلی پلاگین
        wp_enqueue_script(
            'iran-cities',
            plugin_dir_url(__FILE__) . 'assets/iran_cities.min.js',
            [],
            NIAS_LOGIN_VERSION,
            true
        );
    }

    public function modify_add_to_cart_action($url)
    {
        // در صفحات سبد خرید و تسویه حساب، فرم اصلی WooCommerce دست نخورد
        if ($this->is_woo_cart_or_checkout_page()) {
            return $url;
        }
        return '';
    }

    /**
     * بررسی nonce بدون کشتن درخواست با خروجی خام «-1».
     *
     * check_ajax_referer پیش‌فرض با wp_die(-1, 403) پاسخ می‌دهد؛ آن خروجی نه
     * JSON است و نه قابل تشخیص از بلاک‌شدن توسط فایروال/افزونه امنیتی، پس
     * سمت جاوااسکریپت فقط «خطای ناشناخته» دیده می‌شد. اینجا پاسخ JSON با
     * پرچم nonce_expired برمی‌گردد تا کلاینت nonce تازه بگیرد و یک‌بار
     * درخواست را تکرار کند.
     *
     * علت رایج منقضی‌شدن: افزونه‌های کش (لایت‌اسپید/WP Rocket) که خروجی
     * wp_localize_script را داخل فایل جاوااسکریپت ادغام و کش می‌کنند و nonce
     * کهنه را به همه بازدیدکننده‌ها می‌دهند.
     */
    private function verify_nonce()
    {
        if (check_ajax_referer('nias_login_fastsell_nonce', 'nonce', false)) {
            return;
        }

        while (ob_get_level() > 0) { ob_end_clean(); }

        wp_send_json_error([
            'message'       => __('نشست شما منقضی شده است؛ لطفاً دوباره تلاش کنید', 'nias-login-signup'),
            'nonce_expired' => true,
        ]);
    }

    public function ajax_check_stock()
    {
        $this->verify_nonce();

        $product_id    = absint($_POST['product_id']);
        $variation_id  = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $requested_qty = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        $stock_check_id = $variation_id ?: $product_id;
        $product = wc_get_product($stock_check_id);

        if (!$product) {
            wp_send_json_error(['message' => __('محصول یافت نشد', 'nias-login-signup')]);
            return;
        }

        if ($product->managing_stock()) {
            $stock_qty = (int) $product->get_stock_quantity();

            if ($requested_qty > $stock_qty) {
                wp_send_json_error([
                    'message'   => sprintf(
                        __('فقط %d عدد در انبار موجود است', 'nias-login-signup'),
                        $stock_qty
                    ),
                    'max_qty'   => $stock_qty,
                    'available' => $stock_qty,
                ]);
                return;
            }
        }

        wp_send_json_success([
            'available' => true,
            'max_qty'   => $product->managing_stock() ? (int) $product->get_stock_quantity() : null,
        ]);
    }

    private function clean_variation_attribute($value)
    {
        $value = is_scalar($value) ? wp_unslash($value) : '';
        return function_exists('wc_clean') ? wc_clean($value) : sanitize_text_field($value);
    }

    private function normalize_variation_attributes($variation)
    {
        $normalized = [];

        foreach ((array) $variation as $key => $value) {
            $key = $this->clean_variation_attribute($key);
            if ($key === '') {
                continue;
            }
            if (strpos($key, 'attribute_') !== 0) {
                $key = 'attribute_' . $key;
            }

            $value = $this->clean_variation_attribute($value);
            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function get_variation_attributes_for_cart($variation_id, $posted_variation = [])
    {
        $variation = $this->normalize_variation_attributes($posted_variation);
        $variation_product = $variation_id ? wc_get_product($variation_id) : false;

        if (!$variation_product || !$variation_product->is_type('variation')) {
            return $variation;
        }

        foreach ($variation_product->get_variation_attributes() as $key => $value) {
            if ($value === '') {
                continue;
            }
            $variation[$key] = $value;
        }

        return $variation;
    }

    public function ajax_add_to_cart()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $product_id   = absint($_POST['product_id']);
        $quantity     = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

        // دریافت آرایه variation برای محصولات متغیر
        $variation = [];
        if (!empty($_POST['variation']) && is_array($_POST['variation'])) {
            $variation = $this->normalize_variation_attributes($_POST['variation']);
        }

        // اگر variation_id نداریم ولی variation attributes داریم، variation_id را پیدا کن
        if (!$variation_id && !empty($variation)) {
            $product = wc_get_product($product_id);
            if ($product && $product->is_type('variable')) {
                $data_store = WC_Data_Store::load('product');
                $variation_id = $data_store->find_matching_product_variation($product, $variation);
            }
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => __('محصول یافت نشد', 'nias-login-signup')]);
            return;
        }

        // بررسی اینکه آیا محصول متغیر نیاز به انتخاب گزینه دارد
        if ($variation_id) {
            $variation_product = wc_get_product($variation_id);
            if (!$variation_product || !$variation_product->is_type('variation') || (int) $variation_product->get_parent_id() !== (int) $product_id) {
                wp_send_json_error(['message' => __('متغیر انتخاب‌شده برای این محصول معتبر نیست', 'nias-login-signup')]);
                return;
            }
            $variation = $this->get_variation_attributes_for_cart($variation_id, $variation);
        }

        if ($product->is_type('variable') && !$variation_id) {
            wp_send_json_error(['message' => __('لطفاً ویژگی‌های محصول را انتخاب کنید', 'nias-login-signup')]);
            return;
        }

        // حالت خرید سریع تکی — قبل از افزودن، سبد خرید خالی می‌شود تا فقط همین محصول در آن باشد
        if (get_option('nias_fastsell_buy_now')) {
            WC()->cart->empty_cart();
        }

        // بررسی محصول تک‌فروش
        if ($product->is_sold_individually()) {
            foreach (WC()->cart->get_cart() as $item) {
                $item_product_id = isset($item['product_id']) ? $item['product_id'] : 0;
                $parent_id = is_object($item['data']) && method_exists($item['data'], 'get_parent_id')
                    ? $item['data']->get_parent_id() : 0;
                if (
                    $item_product_id === $product_id ||
                    $parent_id === $product_id ||
                    ($variation_id && $item_product_id === $variation_id)
                ) {
                    wp_send_json_success([
                        'cart_html' => $this->get_cart_html(),
                        'message'   => __('این محصول تک‌فروش است و قبلاً در سبد شما وجود دارد', 'nias-login-signup'),
                    ]);
                    return;
                }
            }
        }

        // بررسی موجودی قبل از افزودن
        $check_id      = $variation_id ? $variation_id : $product_id;
        $check_product = wc_get_product($check_id);

        if ($check_product && $check_product->managing_stock()) {
            $stock_qty  = $check_product->get_stock_quantity();
            $in_cart_qty = 0;
            foreach (WC()->cart->get_cart() as $item) {
                $cart_pid = $variation_id ? $item['variation_id'] : $item['product_id'];
                if ((int) $cart_pid === (int) $check_id) {
                    $in_cart_qty += $item['quantity'];
                }
            }

            $available = $stock_qty - $in_cart_qty;

            if ($available <= 0) {
                wp_send_json_success([
                    'cart_html' => $this->get_cart_html(),
                    'message'   => sprintf(
                        __('موجودی کافی نیست — تنها %d عدد در انبار داریم و همان مقدار در سبد شما است', 'nias-login-signup'),
                        $stock_qty
                    ),
                ]);
                return;
            }

            if ($quantity > $available) {
                $quantity = $available;
            }
        }

        wc_clear_notices();

        if ($variation_id) {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
        } else {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
        }

        if ($cart_item_key) {
            wp_send_json_success([
                'cart_html' => $this->get_cart_html(),
                'message'   => __('محصول به سبد خرید اضافه شد', 'nias-login-signup'),
            ]);
        } else {
            $notices      = wc_get_notices('error');
            $error_message = __('خطا در افزودن به سبد خرید', 'nias-login-signup');

            if (!empty($notices)) {
                $first = reset($notices);
                if (is_array($first) && isset($first['notice'])) {
                    $error_message = wp_strip_all_tags(html_entity_decode($first['notice'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                } elseif (is_string($first)) {
                    $error_message = wp_strip_all_tags($first);
                }
            }

            wc_clear_notices();

            wp_send_json_success([
                'cart_html' => $this->get_cart_html(),
                'message'   => $error_message,
            ]);
        }
    }

    public function ajax_update_cart()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        $quantity      = absint($_POST['quantity']);

        if ($quantity < 1) {
            wp_send_json_error(['message' => __('تعداد نامعتبر است', 'nias-login-signup')]);
            return;
        }

        $cart_item = WC()->cart->get_cart_item($cart_item_key);

        if (!$cart_item || empty($cart_item['data'])) {
            wp_send_json_error(['message' => __('این محصول در سبد خرید یافت نشد', 'nias-login-signup')]);
            return;
        }

        /** @var WC_Product $product */
        $product = $cart_item['data'];

        // اعتبارسنجی موجودی روی سرور — دکمه‌های + و − موجودی را چک می‌کنند اما کاربر
        // می‌تواند عدد را مستقیماً در اینپوت تایپ کند یا درخواست AJAX را دستکاری کند
        if (!$product->is_in_stock()) {
            wp_send_json_error([
                'message'   => __('این محصول در حال حاضر ناموجود است', 'nias-login-signup'),
                'max_qty'   => 0,
                'cart_html' => $this->get_cart_html(),
            ]);
            return;
        }

        // سقف مجاز خرید: ۱ برای محصول تک‌فروش، موجودی انبار برای محصول دارای مدیریت
        // انبار، و -1 یعنی نامحدود (بدون مدیریت انبار یا با پیش‌خرید مجاز)
        $max_qty = $product->get_max_purchase_quantity();

        if ($max_qty >= 0 && $quantity > $max_qty) {
            $message = $product->is_sold_individually()
                ? __('این محصول فقط به تعداد ۱ عدد قابل سفارش است', 'nias-login-signup')
                : sprintf(__('فقط %d عدد در انبار موجود است', 'nias-login-signup'), $max_qty);

            wp_send_json_error([
                'message'   => $message,
                'max_qty'   => $max_qty,
                'cart_html' => $this->get_cart_html(),
            ]);
            return;
        }

        WC()->cart->set_quantity($cart_item_key, $quantity, true);

        wp_send_json_success([
            'cart_html'            => $this->get_cart_html(),
            'cart_total'           => (float) WC()->cart->get_total(''),
            'payment_section_html' => $this->get_payment_section_html(),
        ]);
    }

    public function ajax_remove_cart_item()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);

        if (WC()->cart->remove_cart_item($cart_item_key)) {
            wp_send_json_success([
                'cart_html'            => $this->get_cart_html(),
                'cart_total'           => (float) WC()->cart->get_total(''),
                'payment_section_html' => $this->get_payment_section_html(),
            ]);
        } else {
            wp_send_json_error(['message' => __('خطا در حذف محصول', 'nias-login-signup')]);
        }
    }

    /**
     * بازگشت HTML سبد خرید فعلی — برای باز کردن مودال از روی آیکون سبد/مینی‌کارت
     * (بدون افزودن محصول، فقط نمایش محتوای فعلی سبد)
     */
    public function ajax_get_cart()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        WC()->cart->calculate_totals();

        wp_send_json_success([
            'cart_html' => $this->get_cart_html(),
        ]);
    }

    public function ajax_apply_coupon()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        wc_clear_notices();

        if (WC()->cart->apply_coupon($coupon_code)) {
            wp_send_json_success([
                'cart_html' => $this->get_cart_html(),
                'message'   => __('کد تخفیف اعمال شد', 'nias-login-signup'),
            ]);
        } else {
            $errors   = wc_get_notices('error');
            $messages = [];
            if (is_array($errors)) {
                foreach ($errors as $err) {
                    if (is_array($err) && isset($err['notice'])) {
                        $message    = wp_strip_all_tags($err['notice']);
                        $message    = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $messages[] = $message;
                    } elseif (is_string($err)) {
                        $message    = wp_strip_all_tags($err);
                        $message    = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $messages[] = $message;
                    }
                }
            }
            $msg = !empty($messages) ? implode('؛ ', $messages) : __('کد تخفیف نامعتبر است', 'nias-login-signup');
            wp_send_json_error(['message' => $msg]);
        }
    }

    /**
     * تغییر روش حمل و نقل در مرحله سبد خرید و بازگشت جمع به‌روز
     */
    public function ajax_set_shipping()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $shipping_method = sanitize_text_field($_POST['shipping_method'] ?? '');
        // تشخیص اینکه درخواست از مرحله ۱ (سبد خرید) است یا مرحله ۲ (تسویه حساب)
        $is_step2 = isset($_POST['is_step2']) && (bool) $_POST['is_step2'];

        if ($shipping_method && WC()->session) {
            WC()->session->set('chosen_shipping_methods', [$shipping_method]);
        }

        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        // خلاصه سفارش مرحله ۲ ممکن است در تنظیمات ادمین غیرفعال شده باشد
        $show_step2_summary = !$is_step2 || get_option('nias_fastsell_show_order_summary', 1);

        // مرحله ۱ باید انتخابگر روش ارسال را نگه دارد. قبلاً اینجا هم false
        // فرستاده می‌شد و چون جاوااسکریپت کل #nias-fastsell-order-summary را با
        // این خروجی جایگزین می‌کند، بلافاصله بعد از انتخاب روش ارسال کل آن ناحیه
        // از صفحه حذف می‌شد. در مرحله ۲ انتخابگر جای دیگری در فرم است، پس
        // خلاصه فقط هزینه را نشان می‌دهد.
        wp_send_json_success([
            'summary_html' => $show_step2_summary
                ? $this->get_order_summary_html(!$is_step2, true)
                : '',
        ]);
    }

    /**
     * ثبت آدرس مقصد روی مشتری و محاسبه مجدد حمل و نقل.
     *
     * افزونه‌های حمل و نقل ایرانی نرخ را بر اساس استان/شهر/محله مقصد حساب می‌کنند و
     * تا وقتی این مقادیر روی WC()->customer ننشسته باشند، یا نرخی برنمی‌گردانند یا
     * نرخ آدرس پیش‌فرض فروشگاه را می‌دهند. مودال خرید سریع فرم چک‌اوت ووکامرس را
     * ندارد، پس رویداد update_order_review هم اجرا نمی‌شود و باید خودمان این کار را
     * انجام دهیم.
     */
    public function ajax_set_address()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        $ship_different = !empty($_POST['ship_to_different_address']);

        $country  = sanitize_text_field($_POST['country']  ?? '');
        $state    = sanitize_text_field($_POST['state']    ?? '');
        $city     = sanitize_text_field($_POST['city']     ?? '');
        $district = sanitize_text_field($_POST['district'] ?? '');
        $postcode = sanitize_text_field($_POST['postcode'] ?? '');

        $customer = WC()->customer;

        if ($country) {
            $customer->set_billing_country($country);
            $customer->set_shipping_country($country);
        }

        $customer->set_billing_state($state);
        $customer->set_shipping_state($state);
        $customer->set_billing_city($city);
        $customer->set_shipping_city($city);

        if ($postcode !== '') {
            $customer->set_billing_postcode($postcode);
            $customer->set_shipping_postcode($postcode);
        }

        $customer->save();

        // «محله» فیلد استاندارد ووکامرس نیست؛ افزونه حمل و نقل آن را از session
        // (برای مقداردهی مجدد فرم) و از post_data (برای ساخت package) می‌خواند
        if (WC()->session) {
            WC()->session->set('billing_district', $district);
            WC()->session->set('shipping_district', $district);
        }

        // شبیه‌سازی همان چیزی که update_order_review ووکامرس می‌فرستد تا فیلتر
        // woocommerce_cart_shipping_packages افزونه‌های حمل و نقل مقصد کامل را ببیند
        $post_data = [
            'billing_state'     => $state,
            'billing_city'      => $city,
            'billing_district'  => $district,
            'billing_postcode'  => $postcode,
            'shipping_state'    => $state,
            'shipping_city'     => $city,
            'shipping_district' => $district,
            'shipping_postcode' => $postcode,
        ];
        if ($ship_different) {
            $post_data['ship_to_different_address'] = 1;
        }
        $_POST['post_data'] = http_build_query($post_data);

        // ووکامرس نرخ‌ها را با هشِ package کش می‌کند و مقصد بخشی از آن هش است،
        // پس با تغییر آدرس خودبه‌خود دوباره محاسبه می‌شود
        $chosen = WC()->session ? (array) WC()->session->get('chosen_shipping_methods') : [];
        WC()->cart->calculate_shipping();

        // اگر روشی که کاربر انتخاب کرده بود هنوز برای مقصد جدید در دسترس است،
        // همان را نگه دار تا انتخابش با هر تغییر آدرس از بین نرود
        if (!empty($chosen[0])) {
            foreach (WC()->shipping()->get_packages() as $package) {
                if (isset($package['rates'][$chosen[0]])) {
                    WC()->session->set('chosen_shipping_methods', [$chosen[0]]);
                    break;
                }
            }
        }

        WC()->cart->calculate_totals();

        wp_send_json_success([
            'shipping_html' => $this->get_shipping_methods_html(),
            'summary_html'  => get_option('nias_fastsell_show_order_summary', 1)
                ? $this->get_order_summary_html(false, true)
                : '',
        ]);
    }

    public function ajax_get_checkout_form()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        // احراز قبل از تسویه حساب: کاربر مهمان ابتدا باید هویتش را تأیید کند.
        // روش تأیید از تنظیمات «عملکرد» می‌آید. فرم تسویه (فیلدها، درگاه‌ها و
        // دکمه پرداخت) فقط پس از ورود موفق رندر می‌شود.
        if ($this->auth_required()) {
            wp_send_json_success([
                'requires_auth' => true,
                'auth_html'     => $this->get_auth_form_html(),
            ]);
            return;
        }

        // سبد خالی → فرم تسویه‌حساب اصلاً رندر نمی‌شود.
        // سبد ممکن است بین باز شدن مودال و رسیدن به مرحله دو خالی شده باشد
        // (تب دیگر، بازگشت با دکمه Back پس از یک سفارش موفق، انقضای نشست).
        if (WC()->cart->is_empty()) {
            wp_send_json_error([
                'message'    => __('سبد خرید شما خالی است؛ ابتدا محصولی به سبد اضافه کنید', 'nias-login-signup'),
                'cart_empty' => true,
                'cart_html'  => $this->get_cart_html(),
            ]);
            return;
        }

        WC()->cart->calculate_totals();

        if (!is_user_logged_in()) {
            $default_country = get_option('woocommerce_default_country');
            if ($default_country) {
                $country_parts = explode(':', $default_country);
                $country = $country_parts[0];
                $state   = isset($country_parts[1]) ? $country_parts[1] : '';

                WC()->customer->set_billing_country($country);
                WC()->customer->set_shipping_country($country);

                // افزونه‌های حمل و نقل ایرانی لیست استان‌ها را با فیلتر woocommerce_states
                // جایگزین می‌کنند و مقادیرشان شناسه‌ی ترم است، نه کد استان ووکامرس.
                // اگر استانِ ذخیره‌شده در تنظیمات دیگر در آن لیست نباشد، ست کردنش باعث
                // می‌شود روش‌های ارسال به‌خاطر مقصد نامعتبر اصلاً نمایش داده نشوند.
                $valid_states = WC()->countries->get_states($country);
                if ($state && (empty($valid_states) || isset($valid_states[$state]))) {
                    WC()->customer->set_billing_state($state);
                    WC()->customer->set_shipping_state($state);
                }
            }
        }

        WC()->cart->calculate_shipping();

        ob_start();
        $this->render_checkout_form();
        $form_html = ob_get_clean();

        wp_send_json_success([
            'form_html'    => $form_html,
            // در صورت غیرفعال بودن، ناحیه nias-fastsell-checkout-order-summary خالی می‌ماند
            'summary_html' => get_option('nias_fastsell_show_order_summary', 1) ? $this->get_order_summary_html(false, true) : '',
        ]);
    }

    public function ajax_process_checkout()
    {
        $this->verify_nonce();
        while (ob_get_level() > 0) { ob_end_clean(); }

        // احراز قبل از تسویه حساب: بدون ورود، ثبت سفارش مجاز نیست
        if ($this->auth_required()) {
            wp_send_json_error(['message' => __('برای ثبت سفارش ابتدا وارد حساب خود شوید', 'nias-login-signup')]);
            return;
        }

        // سبد خالی = هیچ سفارشی نباید ثبت شود.
        //
        // بدون این گارد، سفارشِ بدون محصول ساخته می‌شد: جمع کل صفر می‌شد،
        // پس هم انتخاب درگاه پرداخت لازم نبود و هم مسیر «سفارش رایگان»
        // اجرا و سفارش خودکار «تکمیل‌شده» می‌شد. نتیجه‌اش همان سفارش‌های
        // خالی با مبلغ ۰ و وضعیت تکمیل‌شده بود.
        if (WC()->cart->is_empty()) {
            wp_send_json_error([
                'message'    => __('سبد خرید شما خالی است؛ سفارشی ثبت نشد', 'nias-login-signup'),
                'cart_empty' => true,
                'cart_html'  => $this->get_cart_html(),
            ]);
            return;
        }

        $data          = $_POST['checkout_data'];
        $billing_email = isset($data['billing_email']) ? sanitize_email($data['billing_email']) : '';
        $billing_phone = isset($data['billing_phone']) ? sanitize_text_field($data['billing_phone']) : '';

        $checkout = WC()->checkout();
        $missing  = false;
        foreach (['billing', 'order'] as $section) {
            $fields = $checkout->get_checkout_fields($section);
            foreach ($fields as $key => $field) {
                if (!empty($field['required'])) {
                    if (empty($data[$key])) {
                        $missing = true;
                        break 2;
                    }
                }
            }
        }
        if (WC()->cart->needs_shipping() && !empty($data['ship_to_different_address'])) {
            $fields = $checkout->get_checkout_fields('shipping');
            foreach ($fields as $key => $field) {
                if (!empty($field['required'])) {
                    if (empty($data[$key])) {
                        $missing = true;
                        break;
                    }
                }
            }
        }
        if ($missing) {
            wp_send_json_error(['message' => __('لطفا تمام فیلدهای ضروری را پر کنید', 'nias-login-signup')]);
            return;
        }

        // آخرین سد موجودی پیش از ساخت سفارش. سبد ممکن است از زمان افزودن تغییر کرده باشد
        // (موجودی تمام شده، محصول غیرقابل خرید شده) یا تعداد از سمت کلاینت دستکاری شده باشد.
        wc_clear_notices();
        WC()->cart->check_cart_items();
        $cart_errors = wc_get_notices('error');
        wc_clear_notices();

        if (!empty($cart_errors)) {
            $first   = reset($cart_errors);
            $notice  = is_array($first) && isset($first['notice']) ? $first['notice'] : (is_string($first) ? $first : '');
            $message = $notice
                ? wp_strip_all_tags(html_entity_decode($notice, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                : __('موجودی برخی از محصولات سبد خرید کافی نیست', 'nias-login-signup');

            wp_send_json_error([
                'message'   => $message,
                'cart_html' => $this->get_cart_html(),
            ]);
            return;
        }

        WC()->cart->calculate_totals();
        $is_free_order = (float) WC()->cart->get_total('') <= 0;

        if (!$is_free_order && empty($data['payment_method'])) {
            wp_send_json_error(['message' => __('لطفاً روش پرداخت را انتخاب کنید', 'nias-login-signup')]);
            return;
        }
        if (WC()->cart->needs_shipping() && empty($data['shipping_method'])) {
            wp_send_json_error(['message' => __('لطفاً روش حمل و نقل را انتخاب کنید', 'nias-login-signup')]);
            return;
        }

        if (is_user_logged_in()) {
            // کاربر لاگین‌شده — سفارش همیشه به‌نام خودش ثبت می‌شود؛
            // ایمیل/شماره واردشده در فرم فقط به‌عنوان آدرس صورتحساب/ارسال استفاده می‌شوند.
            $user = wp_get_current_user();
        } else {
            $user_result = $this->handle_user_authentication($billing_email, $billing_phone, $data);

            if (is_wp_error($user_result)) {
                wp_send_json_error(['message' => $user_result->get_error_message()]);
                return;
            }

            $user         = $user_result['user'];
            $should_login = $user_result['should_login'];

            if ($should_login && $user) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
            }
        }

        $order = $this->create_order($data, $user);

        if (is_wp_error($order)) {
            wp_send_json_error(['message' => $order->get_error_message()]);
            return;
        }

        // محاسبه مجدد جمع کل — shipping item داخل create_order اضافه شده
        $order->calculate_totals();
        $order->save();

        // منبع ورود سفارش (گوگل، ارجاع، مستقیم، کمپین utm و ...)
        $this->save_order_attribution($order, $data);

        // سفارش رایگان — نیاز به درگاه پرداخت نیست
        // بررسی مجدد بعد از اضافه شدن هزینه ارسال
        $order_total = (float) $order->get_total();
        $is_free_order = $order_total <= 0;

        if ($is_free_order) {
            $order->payment_complete();
            $order->update_status('completed', __('سفارش رایگان — تکمیل خودکار', 'nias-login-signup'));
            wp_send_json_success([
                'redirect_url' => $order->get_checkout_order_received_url(),
                'order_id'     => $order->get_id(),
                'message'      => __('سفارش شما با موفقیت ثبت شد', 'nias-login-signup'),
            ]);
            return;
        }

        if (!empty($data['payment_method'])) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

            if (isset($available_gateways[$data['payment_method']])) {
                $gateway = $available_gateways[$data['payment_method']];
                $order->set_payment_method($gateway->id);
                $order->set_payment_method_title($gateway->get_title());
                $order->save();

                // اطمینان از اینکه WC session مبلغ درست را دارد
                if (WC()->session) {
                    WC()->session->set('order_awaiting_payment', $order->get_id());
                }

                $result = $gateway->process_payment($order->get_id());

                if (isset($result['result']) && $result['result'] === 'success') {
                    wp_send_json_success([
                        'redirect_url' => $result['redirect'],
                        'order_id'     => $order->get_id(),
                        'message'      => __('در حال انتقال به درگاه پرداخت...', 'nias-login-signup'),
                    ]);
                    return;
                }
            }
        }

        $order->save();
        $payment_url = $order->get_checkout_payment_url(true);

        wp_send_json_success([
            'redirect_url' => $payment_url,
            'order_id'     => $order->get_id(),
            'message'      => __('سفارش با موفقیت ثبت شد', 'nias-login-signup'),
        ]);
    }

    /**
     * آیا برای درخواست جاری احراز هویت لازم است؟
     * فقط وقتی گزینه ادمین فعال باشد و کاربر لاگین نکرده باشد.
     * روشِ احراز (کد تایید / رمز عبور / رمز+کد) از تنظیمات «عملکرد» می‌آید.
     */
    private function auth_required(): bool
    {
        return (bool) get_option('nias_fastsell_otp_before_checkout') && !is_user_logged_in();
    }

    /**
     * برچسب‌ها و مشخصات فیلد شناسه بر اساس تنظیمات ایمیل/موبایل —
     * همان سه شاخه‌ای که مودال ورود (view/nias_login_modal.php) دارد
     */
    private function get_auth_identifier_config(): array
    {
        if (get_option('nias_email_activate')) {
            return [
                'name'        => 'ns_email',
                'type'        => 'email',
                'inputmode'   => 'email',
                'autocomplete'=> 'email',
                'label'       => __('ایمیل', 'nias-login-signup'),
                'placeholder' => __('name@example.com', 'nias-login-signup'),
                'title'       => __('تأیید ایمیل', 'nias-login-signup'),
                'desc'        => __('برای ادامه خرید، ایمیل خود را وارد کنید', 'nias-login-signup'),
            ];
        }

        if (get_option('nias_email_phone_activate')) {
            return [
                'name'        => 'ns_email_phone',
                'type'        => 'text',
                'inputmode'   => 'text',
                'autocomplete'=> 'on',
                'label'       => __('ایمیل یا شماره موبایل', 'nias-login-signup'),
                'placeholder' => __('ایمیل یا شماره موبایل', 'nias-login-signup'),
                'title'       => __('تأیید ایمیل یا شماره موبایل', 'nias-login-signup'),
                'desc'        => __('برای ادامه خرید، ایمیل یا شماره موبایل خود را وارد کنید', 'nias-login-signup'),
            ];
        }

        return [
            'name'        => 'phone',
            'type'        => 'tel',
            'inputmode'   => 'tel',
            'autocomplete'=> 'tel',
            'label'       => __('شماره موبایل', 'nias-login-signup'),
            'placeholder' => __('۰۹۱۲۳۴۵۶۷۸۹', 'nias-login-signup'),
            'title'       => __('تأیید شماره موبایل', 'nias-login-signup'),
            'desc'        => __('برای ادامه خرید، شماره موبایل خود را وارد کنید', 'nias-login-signup'),
        ];
    }

    /**
     * پنل ورود با رمز عبور — به‌همراه بلوک ساخت رمز دستی که فقط برای کاربر جدید
     * (بر اساس پاسخ سرور) با جاوااسکریپت نمایش داده می‌شود
     */
    private function get_auth_password_panel_html(bool $hidden): string
    {
        $manual_password = get_option('nias_manual_password_activate')
            && (get_option('nias_password_activate') || get_option('nias_password_otp_activate'));

        ob_start();
        ?>
        <div class="nias-fastsell-otp-panel" data-panel="password"<?php echo $hidden ? ' style="display:none"' : ''; ?>>
            <div class="nias-fastsell-otp-step nias-fastsell-otp-existing-password">
                <input type="password" id="nias-fastsell-auth-password" autocomplete="current-password"
                       placeholder="<?php esc_attr_e('رمز عبور', 'nias-login-signup'); ?>" />
            </div>

            <?php if ($manual_password) : ?>
                <div class="nias-fastsell-otp-manual" style="display:none">
                    <p class="nias-fastsell-otp-desc"><?php _e('برای شما حسابی وجود ندارد؛ یک رمز عبور برای حساب جدید انتخاب کنید', 'nias-login-signup'); ?></p>
                    <div class="nias-fastsell-otp-step">
                        <input type="password" id="nias-fastsell-auth-new-password" autocomplete="new-password"
                               placeholder="<?php esc_attr_e('رمز عبور دلخواه', 'nias-login-signup'); ?>" />
                    </div>
                    <div class="nias-fastsell-otp-step">
                        <input type="password" id="nias-fastsell-auth-confirm-password" autocomplete="new-password"
                               placeholder="<?php esc_attr_e('تکرار رمز عبور', 'nias-login-signup'); ?>" />
                    </div>
                    <p class="nias-fastsell-otp-desc"><?php _e('حداقل ۸ کاراکتر، شامل حروف و اعداد', 'nias-login-signup'); ?></p>
                </div>
            <?php endif; ?>

            <div class="nias-fastsell-otp-meta nias-fastsell-otp-forgot-row">
                <button type="button" id="nias-fastsell-auth-forgot"><?php _e('فراموشی رمز عبور', 'nias-login-signup'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** پنل ورود با کد تایید */
    private function get_auth_code_panel_html(bool $hidden): string
    {
        $digits = max(4, (int) get_option('nsdigitsquantity', 4));

        ob_start();
        ?>
        <div class="nias-fastsell-otp-panel" data-panel="code"<?php echo $hidden ? ' style="display:none"' : ''; ?>>
            <div class="nias-fastsell-otp-step">
                <input type="tel" id="nias-fastsell-auth-code" dir="ltr" inputmode="numeric"
                       autocomplete="one-time-code" maxlength="<?php echo esc_attr($digits); ?>"
                       placeholder="<?php echo esc_attr(sprintf(__('کد %s رقمی', 'nias-login-signup'), $digits)); ?>" />
            </div>
            <div class="nias-fastsell-otp-meta">
                <button type="button" id="nias-fastsell-otp-resend" disabled><?php _e('ارسال مجدد کد', 'nias-login-signup'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * HTML گیت احراز هویت — به جای فرم تسویه حساب برای مهمان‌ها رندر می‌شود.
     * ساختار شرطی دقیقاً از مودال ورود الگو گرفته است:
     *   password_otp_activate → تب رمز عبور + تب کد تایید
     *   password_activate     → فقط رمز عبور
     *   هیچ‌کدام             → فقط کد تایید
     */
    private function get_auth_form_html()
    {
        $password_activate     = get_option('nias_password_activate');
        $password_otp_activate = get_option('nias_password_otp_activate');
        $field                 = $this->get_auth_identifier_config();

        ob_start();
        ?>
        <div class="nias-fastsell-otp" id="nias-fastsell-otp">
            <div class="nias-fastsell-otp-icon">
                <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg>
            </div>
            <span class="nias-fastsell-otp-title"><?php echo esc_html($field['title']); ?></span>
            <p class="nias-fastsell-otp-desc"><?php echo esc_html($field['desc']); ?></p>

            <div class="nias-fastsell-otp-step nias-fastsell-otp-step-identifier">
                <input type="<?php echo esc_attr($field['type']); ?>"
                       id="nias-fastsell-auth-identifier"
                       name="<?php echo esc_attr($field['name']); ?>"
                       data-label="<?php echo esc_attr($field['label']); ?>"
                       dir="<?php echo $field['name'] === 'ns_email_phone' ? 'auto' : 'ltr'; ?>"
                       inputmode="<?php echo esc_attr($field['inputmode']); ?>"
                       autocomplete="<?php echo esc_attr($field['autocomplete']); ?>"
                       placeholder="<?php echo esc_attr($field['placeholder']); ?>" />
                <button type="button" id="nias-fastsell-auth-continue"><?php _e('ادامه', 'nias-login-signup'); ?></button>
            </div>

            <div class="nias-fastsell-otp-step2" style="display:none">
                <?php
                if ($password_otp_activate) {
                    // هر دو روش در دسترس — تب پسورد پیش‌فرض، مثل مودال ورود
                    ?>
                    <div class="nias-fastsell-otp-tabs">
                        <button type="button" class="nias-fastsell-otp-tab is-active" data-panel="password"><?php _e('رمز عبور', 'nias-login-signup'); ?></button>
                        <button type="button" class="nias-fastsell-otp-tab" data-panel="code"><?php _e('کد تایید', 'nias-login-signup'); ?></button>
                    </div>
                    <?php
                    echo $this->get_auth_password_panel_html(false);
                    echo $this->get_auth_code_panel_html(true);
                } elseif ($password_activate) {
                    echo $this->get_auth_password_panel_html(false);
                } else {
                    echo $this->get_auth_code_panel_html(false);
                }
                ?>

                <div class="nias-fastsell-otp-step nias-fastsell-otp-submit-row">
                    <button type="button" id="nias-fastsell-auth-submit"><?php _e('تأیید و ادامه', 'nias-login-signup'); ?></button>
                </div>
                <div class="nias-fastsell-otp-meta">
                    <button type="button" id="nias-fastsell-otp-edit"><?php printf(esc_html__('ویرایش %s', 'nias-login-signup'), esc_html($field['label'])); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * nonce تازه‌ی خرید سریع پس از ورود کاربر — nonce مهمانِ قبلی دیگر معتبر نیست.
     * خودِ nonce یک راز نیست و به session جاری گره خورده، پس بررسی nonce ورودی لازم نیست.
     */
    public function ajax_refresh_nonce()
    {
        while (ob_get_level() > 0) { ob_end_clean(); }

        wp_send_json_success(['nonce' => wp_create_nonce('nias_login_fastsell_nonce')]);
    }


    private function handle_user_authentication($email, $phone, $data)
    {
        $phonecheck = preg_replace('/[\s\-()]/', '', $phone);

        if (substr($phonecheck, 0, 3) == '+98') {
            $phonecheck = '0' . substr($phonecheck, 3);
        } elseif (substr($phonecheck, 0, 2) == '98') {
            $phonecheck = '0' . substr($phonecheck, 2);
        }

        // الزام: شماره باید با ۰ شروع شود (مثلاً ۹۱۲... → ۰۹۱۲...)
        if ($phonecheck !== '' && substr($phonecheck, 0, 1) !== '0') {
            $phonecheck = '0' . $phonecheck;
        }

        // برای جستجو هر دو فرمت (با ۰ و بدون ۰) را می‌سنجیم تا کاربران قدیمی که
        // شماره‌شان بدون صفر ذخیره شده پیدا شوند و حساب تکراری ساخته نشود.
        // ذخیره‌سازی همچنان با فرمت استاندارد $phonecheck انجام می‌شود.
        $phone_variants = array();
        if ($phonecheck !== '') {
            $phone_variants[] = $phonecheck;
            if (substr($phonecheck, 0, 1) === '0') {
                $phone_variants[] = substr($phonecheck, 1);
            }
        }

        $user            = null;
        $should_login    = false;
        $is_existing_user = false;

        if (!empty($email)) {
            $user = get_user_by('email', $email);
            if ($user) {
                $is_existing_user = true;
            }
        }

        if (!$user && !empty($phone_variants)) {
            foreach (array('billing_phone', 'phone') as $meta_key) {
                $users = get_users([
                    'meta_query' => [[
                        'key'     => $meta_key,
                        'value'   => $phone_variants,
                        'compare' => 'IN',
                    ]],
                    'number'     => 1,
                ]);

                if (!empty($users)) {
                    $user             = $users[0];
                    $is_existing_user = true;
                    break;
                }
            }
        }

        if ($is_existing_user && $user) {
            return [
                'user'         => $user,
                'should_login' => false,
            ];
        }

        if (!$user) {
            $username = $email ? sanitize_user($email) : 'user_' . time();
            $password = wp_generate_password(12, true, true);

            // اگر ایمیل واقعی نداریم، ایمیل جایگزینِ یکتا بساز.
            // برخی سایت‌ها روی ستون user_email ایندکس UNIQUE دارند (مثل user_email_pinova_unique)
            // و ساختِ کاربر با ایمیل خالی باعث خطای «Duplicate entry '' for key ...» روی دومین کاربر می‌شود.
            $user_email = $email;
            if (empty($user_email)) {
                $email_domain = parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
                $user_email   = ($phonecheck ?: $username) . '@' . $email_domain;
            }

            $user_id = wp_create_user($username, $password, $user_email);

            if (is_wp_error($user_id)) {
                return $user_id;
            }

            $user = get_user_by('id', $user_id);

            if (!empty($phone)) {
                update_user_meta($user_id, 'phone', $phonecheck);
                update_user_meta($user_id, 'billing_phone', $phonecheck);
            }

            foreach ($data as $key => $value) {
                if (strpos($key, 'billing_') === 0 || strpos($key, 'shipping_') === 0) {
                    update_user_meta($user_id, $key, sanitize_text_field($value));
                }
            }

            $should_login = true;
        }

        return [
            'user'         => $user,
            'should_login' => $should_login,
        ];
    }

    private function create_order($data, $user)
    {
        try {
            // آخرین سد: سفارشِ بدون محصول هرگز نباید ساخته شود. اگر سبد در فاصله‌ی
            // بین بررسی اولیه و اینجا خالی شده باشد (یا این متد از مسیر دیگری صدا
            // زده شود)، به‌جای ساختن سفارشِ خالیِ صفر تومانی خطا برمی‌گردد.
            if (WC()->cart->is_empty()) {
                return new WP_Error(
                    'nias_empty_cart',
                    __('سبد خرید شما خالی است؛ سفارشی ثبت نشد', 'nias-login-signup')
                );
            }

            $order = wc_create_order(['customer_id' => $user ? $user->ID : 0]);

            $added_items = 0;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (empty($cart_item['data']) || empty($cart_item['quantity'])) {
                    continue;
                }
                $item_id = $order->add_product($cart_item['data'], $cart_item['quantity']);
                if ($item_id) {
                    $added_items++;
                }
            }

            // سبد آیتم داشت ولی هیچ‌کدام به سفارش اضافه نشد (محصول حذف‌شده،
            // ناموجود یا رد شده توسط فیلترها) — سفارشِ نیم‌بند ساخته نماند
            if ($added_items === 0) {
                $order->delete(true);

                return new WP_Error(
                    'nias_no_order_items',
                    __('محصولات سبد خرید قابل ثبت نیستند؛ لطفاً سبد خرید را بررسی کنید', 'nias-login-signup')
                );
            }

            $billing_address = [];
            foreach ($data as $key => $value) {
                if (strpos($key, 'billing_') === 0) {
                    $field_key                  = str_replace('billing_', '', $key);
                    $billing_address[$field_key] = sanitize_text_field($value);
                }
            }
            if (!empty($billing_address)) {
                $order->set_address($billing_address, 'billing');
            }

            if (isset($data['ship_to_different_address']) && $data['ship_to_different_address']) {
                $shipping_address = [];
                foreach ($data as $key => $value) {
                    if (strpos($key, 'shipping_') === 0) {
                        $field_key                   = str_replace('shipping_', '', $key);
                        $shipping_address[$field_key] = sanitize_text_field($value);
                    }
                }
                if (!empty($shipping_address)) {
                    $order->set_address($shipping_address, 'shipping');
                }
            }

            foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
                $order->apply_coupon($coupon_code);
            }

            // اضافه کردن آیتم حمل و نقل — باید قبل از empty_cart باشد
            // چون بعد از empty_cart دیگر packages در دسترس نیستند
            if (!empty($data['shipping_method'])) {
                $shipping_method_id = sanitize_text_field($data['shipping_method']);

                // ست کردن session قبل از get_packages
                if (WC()->session) {
                    WC()->session->set('chosen_shipping_methods', [$shipping_method_id]);
                }

                // محاسبه مجدد shipping با سبد فعلی (هنوز خالی نشده)
                WC()->cart->calculate_shipping();

                $packages = WC()->shipping()->get_packages();
                $shipping_added = false;

                foreach ($packages as $package) {
                    if (isset($package['rates'][$shipping_method_id])) {
                        $rate = $package['rates'][$shipping_method_id];
                        $item = new WC_Order_Item_Shipping();
                        $item->set_props([
                            'method_title' => $rate->get_label(),
                            'method_id'    => $rate->get_method_id(),
                            'instance_id'  => $rate->get_instance_id(),
                            'total'        => wc_format_decimal($rate->get_cost()),
                            'taxes'        => $rate->get_taxes(),
                        ]);
                        foreach ($rate->get_meta_data() as $meta_key => $meta_value) {
                            $item->add_meta_data($meta_key, $meta_value, true);
                        }
                        $order->add_item($item);
                        $shipping_added = true;
                        break;
                    }
                }

                // اگر rate در packages پیدا نشد، مستقیم از WC_Shipping_Rate بساز
                if (!$shipping_added) {
                    $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods', []) : [];
                    // fallback: فقط method_id را ست کن بدون هزینه
                    $item = new WC_Order_Item_Shipping();
                    $item->set_props([
                        'method_title' => $shipping_method_id,
                        'method_id'    => explode(':', $shipping_method_id)[0],
                        'instance_id'  => explode(':', $shipping_method_id)[1] ?? '',
                        'total'        => 0,
                        'taxes'        => [],
                    ]);
                    $order->add_item($item);
                }
            }

            // فیلدهای آدرسی که ووکامرس به‌صورت استاندارد نمی‌شناسد (مثل «محله» که
            // افزونه‌های حمل و نقل اضافه می‌کنند) در set_address نادیده گرفته می‌شوند،
            // پس مستقیم به‌صورت متا ذخیره‌شان می‌کنیم
            foreach (['billing_district', 'shipping_district'] as $extra_key) {
                if (!empty($data[$extra_key])) {
                    $order->update_meta_data('_' . $extra_key, sanitize_text_field($data[$extra_key]));
                }
            }

            // خالی کردن سبد بعد از اضافه شدن shipping item.
            // در حالت «حفظ سبد تا پرداخت» سبد دست‌نخورده می‌ماند تا کاربری که وارد
            // درگاه شده ولی پرداخت نکرده و برگشته، محصولاتش را از دست ندهد؛
            // پاک‌سازی به maybe_empty_cart_for_order() سپرده می‌شود.
            if ($this->keep_cart_until_paid()) {
                $order->update_meta_data('_nias_fastsell_keep_cart', 'yes');
            } else {
                WC()->cart->empty_cart();
            }

            $order->save();

            // هوک استاندارد ذخیره متای سفارش در چک‌اوت. افزونه‌های حمل و نقل با همین
            // هوک شناسه‌های استان/شهر/محله را به نام تبدیل و ذخیره می‌کنند؛ بدون آن
            // در سفارش به‌جای نام شهر، شناسه‌ی عددی ترم ثبت می‌شود.
            do_action('woocommerce_checkout_update_order_meta', $order->get_id(), $data);

            return $order;

        } catch (Exception $e) {
            return new WP_Error('order_error', $e->getMessage());
        }
    }

    /**
     * «حفظ سبد خرید تا پرداخت موفق» فعال است؟
     */
    private function keep_cart_until_paid(): bool
    {
        return (bool) get_option('nias_fastsell_keep_cart_until_paid');
    }

    /**
     * سفارشِ در جریانِ این بازدید که سبدش باید نگه داشته شود، یا null.
     *
     * یا از endpoint «سفارش دریافت شد» خوانده می‌شود (بازگشت از درگاه) یا از
     * order_awaiting_payment نشست؛ و فقط وقتی برگردانده می‌شود که سفارش با گزینه
     * «حفظ سبد تا پرداخت» ثبت شده و هنوز پرداخت نشده باشد.
     */
    private function get_unpaid_kept_cart_order()
    {
        if (!$this->wc_available() || !function_exists('wc_get_order')) {
            return null;
        }

        global $wp;

        $order_id = isset($wp->query_vars['order-received'])
            ? absint($wp->query_vars['order-received'])
            : 0;

        if (!$order_id && WC()->session) {
            $order_id = absint(WC()->session->get('order_awaiting_payment'));
        }

        if (!$order_id) {
            return null;
        }

        $order = wc_get_order($order_id);
        if (!$order || 'yes' !== $order->get_meta('_nias_fastsell_keep_cart')) {
            return null;
        }

        // پرداخت‌نشده / ناموفق / لغوشده → محصولات باید در سبد بمانند.
        // هر وضعیت دیگری یعنی سفارش قطعی شده و ووکامرس آزاد است سبد را خالی کند.
        return $order->has_status(['pending', 'failed', 'cancelled']) ? $order : null;
    }

    /**
     * فیلتر رسمی ووکامرس (از نسخه ۹.۳) برای جلوگیری از خالی شدن سبد در بازگشت از درگاه
     */
    public function filter_should_clear_cart_after_payment($should_clear)
    {
        if (!$should_clear || !$this->keep_cart_until_paid()) {
            return $should_clear;
        }

        return $this->get_unpaid_kept_cart_order() ? false : $should_clear;
    }

    /**
     * همان کار برای ووکامرس قدیمی‌تر از ۹.۳ که فیلتر بالا را ندارد:
     * تابع پاک‌کننده پیش از اجرا (اولویت ۲۰) از هوک برداشته می‌شود.
     */
    public function legacy_prevent_cart_clear()
    {
        if (!$this->keep_cart_until_paid()) {
            return;
        }
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '9.3', '>=')) {
            return;
        }
        if ($this->get_unpaid_kept_cart_order()) {
            remove_action('template_redirect', 'wc_clear_cart_after_payment', 20);
        }
    }

    /**
     * پاک‌سازی سبد خرید پس از قطعی شدن سفارش — فقط برای سفارش‌هایی که با گزینه
     * «حفظ سبد تا پرداخت» ثبت شده‌اند و سبدشان هنگام ثبت خالی نشده است.
     *
     * سفارشِ در انتظار پرداخت (pending) یا ناموفق (failed) سبد را نگه می‌دارد تا
     * کاربر بتواند دوباره تلاش کند؛ به‌محض اینکه سفارش پرداخت شد یا فروشنده آن را
     * پذیرفت (on-hold مثل پرداخت در محل / کارت به کارت) سبد خالی می‌شود.
     */
    public function maybe_empty_cart_for_order($order_id)
    {
        if (!$this->wc_available() || !function_exists('wc_get_order')) {
            return;
        }

        // تغییر وضعیت از پیشخوان مدیریت نباید سبدِ خودِ مدیر را خالی کند
        if (is_admin() && !(function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order || 'yes' !== $order->get_meta('_nias_fastsell_keep_cart')) {
            return;
        }

        $finished = array_unique(array_merge((array) wc_get_is_paid_statuses(), ['on-hold']));
        if (!$order->has_status($finished)) {
            return;
        }

        // فقط سبدِ خودِ خریدار؛ سفارش مهمان با شناسه‌ی نشستِ در انتظار پرداخت تطبیق داده می‌شود
        $customer_id = (int) $order->get_customer_id();
        if ($customer_id) {
            if ($customer_id !== get_current_user_id()) {
                return;
            }
        } else {
            $awaiting = WC()->session ? (int) WC()->session->get('order_awaiting_payment') : 0;
            if ($awaiting !== (int) $order->get_id()) {
                return;
            }
        }

        // درخواست‌های سمت‌سروری درگاه (IPN) نشست خریدار را ندارند و WC()->cart در
        // آن‌ها ساخته نمی‌شود؛ پاک‌سازی به اولین بازدید خود کاربر موکول می‌شود
        if (!WC()->cart) {
            return;
        }

        if (!WC()->cart->is_empty()) {
            WC()->cart->empty_cart();
        }

        $order->update_meta_data('_nias_fastsell_keep_cart', 'done');
        $order->save();
    }

    /**
     * کنترلر «منبع سفارش» ووکامرس (Order Attribution) — یا null.
     *
     * از نسخه ۸.۵ ووکامرس، منبع ورود سفارش (گوگل، ارجاع، مستقیم، کمپین utm و ...)
     * ثبت می‌شود. مکانیزمش دو تکه است و مودال خرید سریع هیچ‌کدام را نداشت:
     *
     *   ۱) یک المان <wc-order-attribution-inputs> داخل فرم تسویه که ووکامرس با
     *      هوک‌هایی مثل woocommerce_after_checkout_billing_form چاپ می‌کند —
     *      این فرم را خودمان رندر می‌کنیم و آن هوک‌ها اجرا نمی‌شوند.
     *   ۲) خواندن آن فیلدها هنگام ساخت سفارش، که ووکامرس روی هوک
     *      woocommerce_checkout_order_created انجام می‌دهد — ما سفارش را مستقیم
     *      با wc_create_order() می‌سازیم و آن هوک اجرا نمی‌شود.
     *
     * نتیجه‌اش این بود که همه‌ی سفارش‌های خرید سریع «نامشخص» ثبت می‌شدند.
     */
    private function get_order_attribution_controller()
    {
        static $controller = false;

        if (false !== $controller) {
            return $controller;
        }

        $controller = null;

        $class = '\Automattic\WooCommerce\Internal\Orders\OrderAttributionController';

        if (!function_exists('wc_get_container') || !class_exists($class)) {
            return $controller;
        }

        try {
            $instance = wc_get_container()->get($class);
        } catch (Exception $e) {
            return $controller;
        }

        if (is_object($instance) && method_exists($instance, 'get_prefix') && method_exists($instance, 'get_field_names')) {
            $controller = $instance;
        }

        return $controller;
    }

    /**
     * چاپ المان فیلدهای منبع سفارش داخل فرم تسویه‌حساب.
     * اسکریپت خود ووکامرس (order-attribution.js) این المان را با اینپوت‌های
     * مخفی پر می‌کند و چون یک Custom Element است، روی محتوای AJAXی هم کار می‌کند.
     */
    private function render_order_attribution_inputs(): void
    {
        $controller = $this->get_order_attribution_controller();

        if ($controller && method_exists($controller, 'stamp_html_element')) {
            $controller->stamp_html_element();
        }
    }

    /**
     * ثبت منبع سفارش روی سفارشِ تازه‌ساخته‌شده.
     *
     * همان کاری که ووکامرس روی هوک woocommerce_checkout_order_created می‌کند:
     * مقادیر ارسالی فرم را بدون پیشوند به اکشن رسمی
     * woocommerce_order_save_attribution_data می‌دهد تا خود ووکامرس متاها را
     * بسازد (و رویداد Tracks را هم بفرستد).
     *
     * @param WC_Order $order سفارش تازه‌ساخته‌شده
     * @param array    $data  داده‌های ارسالی فرم تسویه‌حساب
     */
    private function save_order_attribution($order, $data): void
    {
        $controller = $this->get_order_attribution_controller();

        if (!$controller || !is_array($data)) {
            return;
        }

        $prefix = (string) $controller->get_prefix();
        $fields = (array) $controller->get_field_names();

        if ($prefix === '' || empty($fields)) {
            return;
        }

        $params = [];
        foreach ($fields as $field) {
            $key = $prefix . $field;
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $params[$field] = wc_clean(wp_unslash((string) $data[$key]));
            }
        }

        if (empty($params)) {
            return;
        }

        do_action('woocommerce_order_save_attribution_data', $order, $params);
    }

    /**
     * بازگشت HTML سبد خرید
     */
    private function get_cart_html()
    {
        // سبد خالی
        if (WC()->cart->is_empty()) {
            ob_start();
            ?>
            <div class="nias-fastsell-empty-cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24">
                    <path d="M8.5 14.25c0 1.92 1.58 3.5 3.5 3.5s3.5-1.58 3.5-3.5M8.81 2L5.19 5.63M15.19 2l3.62 3.63" stroke="#ccc" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 7.85c0-1.85.99-2 2.22-2h15.56c1.23 0 2.22.15 2.22 2 0 2.15-.99 2-2.22 2H4.22C2.99 9.85 2 10 2 7.85z" stroke="#ccc" stroke-width="1.5"/>
                    <path d="M3.5 10l1.41 8.64C5.23 20.58 6 22 8.86 22h6.03c3.11 0 3.57-1.36 3.93-3.24L20.5 10" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <p><?php _e('سبد خرید شما خالی است', 'nias-login-signup'); ?></p>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="nias-login-fastsell-cart-items">
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item):
                $_product   = $cart_item['data'];
                $product_name = $_product->get_name();
                $thumbnail  = $_product->get_image();

                // قیمت اصلی و تخفیف خورده
                $regular_price = (float) $_product->get_regular_price();
                $sale_price    = (float) $_product->get_sale_price();
                $has_sale      = $_product->is_on_sale() && $sale_price > 0 && $sale_price < $regular_price;

                $price    = WC()->cart->get_product_price($_product);
                $subtotal = WC()->cart->get_product_subtotal($_product, $cart_item['quantity']);

                $product_id   = $cart_item['product_id'];
                $variation_id = $cart_item['variation_id'] ?? 0;
                $quantity     = $cart_item['quantity'];
                ?>
                <div class="nias-login-fastsell-cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
                    <div class="nias-login-fastsell-item-image"><?php echo $thumbnail; ?></div>
                    <div class="nias-login-fastsell-item-details">
                        <h4><?php echo esc_html($product_name); ?></h4>

                        <?php
                        // نمایش variation attributes
                        if (!empty($cart_item['variation'])) {
                            echo '<div class="nias-fastsell-variation-attrs">';
                            foreach ($cart_item['variation'] as $attr_key => $attr_val) {
                                // decode کردن مقادیر URL-encoded (مثل %da%a9%d8%af)
                                $attr_val_decoded = urldecode($attr_val);
                                $label = wc_attribute_label(str_replace('attribute_', '', $attr_key));
                                // اگر label هم URL-encoded بود، decode شود
                                $label_decoded = urldecode($label);
                                echo '<span class="nias-fastsell-attr"><strong>' . esc_html($label_decoded) . ':</strong> ' . esc_html($attr_val_decoded) . '</span>';
                            }
                            echo '</div>';
                        }
                        ?>

                        <div class="nias-fastsell-item-row">
                            <?php if ($_product->is_sold_individually()): ?>
                                <div class="nias-login-fastsell-item-quantity">
                                    <span class="nias-login-fastsell-qty-static"><?php printf(esc_html__('تعداد: %s', 'nias-login-signup'), esc_html($quantity)); ?></span>
                                </div>
                            <?php else: ?>
                                <?php
                                // سقف مجاز خرید؛ -1 یعنی نامحدود. روی اینپوت ست می‌شود تا
                                // تایپ مستقیمِ عددِ بزرگ‌تر از موجودی در همان لحظه اصلاح شود
                                $max_purchase = (int) $_product->get_max_purchase_quantity();
                                ?>
                                <div class="nias-login-fastsell-item-quantity">
                                    <div class="nias-login-fastsell-qty-wrapper">
                                        <button class="nias-login-fastsell-qty-plus">+</button>
                                        <input type="number" class="nias-login-fastsell-qty-input"
                                            value="<?php echo esc_attr($quantity); ?>"
                                            data-product-id="<?php echo esc_attr($product_id); ?>"
                                            data-variation-id="<?php echo esc_attr($variation_id); ?>" min="1"
                                            <?php if ($max_purchase > 0) : ?>max="<?php echo esc_attr($max_purchase); ?>"<?php endif; ?> />
                                        <button class="nias-login-fastsell-qty-minus">−</button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($has_sale): ?>
                                <div class="nias-login-fastsell-item-price">
                                    <span class="nias-fastsell-sale-price"><?php echo wc_price($sale_price); ?></span>
                                    <span class="nias-fastsell-regular-price"><?php echo wc_price($regular_price); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="nias-login-fastsell-item-price"><?php echo $price; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="nias-login-fastsell-remove-item">×</button>
                </div>
            <?php endforeach; ?>
            <?php echo $this->get_price_breakdown_html(); ?>
        </div>

        <?php // مرحله سبد خرید: نمایش انتخاب روش ارسال و هزینه آن (مطابق طرح) ?>
        <?php echo $this->get_order_summary_html(true); ?>
        <?php
        return ob_get_clean();
    }

    /**
     * ردیف‌های تخفیف / جمع محصولات — درون سبد خرید و فرم تسویه نمایش داده می‌شود
     */
    private function get_price_breakdown_html()
    {
        // ناحیه nias-fastsell-price-breakdown در تنظیمات ادمین قابل غیرفعال‌سازی است
        if (!get_option('nias_fastsell_show_price_breakdown', 1)) {
            return '';
        }

        $cart           = WC()->cart;
        $subtotal       = $cart->get_subtotal();
        $discount_total = $cart->get_discount_total();

        $original_subtotal = 0;
        foreach ($cart->get_cart() as $cart_item) {
            $_product = $cart_item['data'];
            $regular  = (float) $_product->get_regular_price();
            $original_subtotal += ($regular > 0 ? $regular : (float) $_product->get_price()) * $cart_item['quantity'];
        }

        $product_discount = $original_subtotal - $subtotal;
        $coupon_discount  = $discount_total;
        $has_discount     = ($product_discount + $coupon_discount) > 0;

        ob_start();
        ?>
        <div class="nias-fastsell-price-breakdown">
            <?php if ($has_discount): ?>
                <div class="nias-fastsell-total-line nias-fastsell-original-price">
                    <span><?php _e('قیمت اصلی:', 'nias-login-signup'); ?></span>
                    <span class="nias-fastsell-strikethrough"><?php echo wc_price($original_subtotal); ?></span>
                </div>
                <?php if ($product_discount > 0): ?>
                    <div class="nias-fastsell-total-line nias-fastsell-product-discount">
                        <span><?php _e('تخفیف محصول:', 'nias-login-signup'); ?></span>
                        <span class="nias-fastsell-discount-val">-<?php echo wc_price($product_discount); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($coupon_discount > 0): ?>
                    <div class="nias-fastsell-total-line nias-fastsell-coupon-discount">
                        <span><?php _e('تخفیف کوپن:', 'nias-login-signup'); ?></span>
                        <span class="nias-fastsell-discount-val">-<?php echo wc_price($coupon_discount); ?></span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="nias-fastsell-total-line">
                    <span><?php _e('جمع محصولات:', 'nias-login-signup'); ?></span>
                    <span><?php echo wc_price($subtotal); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * خلاصه سفارش — فقط روش ارسال (اختیاری) و جمع کل
     */
    /**
     * «فعال کردن محاسبه‌گر هزینه ارسال در برگه سبد خرید» — تنظیم خود ووکامرس
     * (ووکامرس ← تنظیمات ← حمل و نقل ← گزینه‌های حمل و نقل).
     *
     * تب سبد خرید مودال معادل همان برگه‌ی سبد خرید است، پس باید از همین تنظیم
     * پیروی کند. مرحله تسویه‌حساب مشمول نیست؛ آن‌جا انتخاب روش ارسال بخشی از
     * فرم چک‌اوت است نه محاسبه‌گر سبد.
     */
    private function cart_shipping_calc_enabled(): bool
    {
        if (function_exists('wc_shipping_enabled') && !wc_shipping_enabled()) {
            return false;
        }

        return 'yes' === get_option('woocommerce_enable_shipping_calc', 'yes');
    }

    /**
     * برچسب روش ارسال انتخاب‌شده — فقط نام روش، بدون هزینه.
     *
     * wc_cart_totals_shipping_method_label() نام و هزینه را با هم برمی‌گرداند و
     * برای ردیف «هزینه ارسال» مناسب نیست؛ اینجا خودِ rate را از میان بسته‌ها
     * پیدا می‌کنیم و فقط get_label() آن را برمی‌داریم.
     */
    private function get_chosen_shipping_label($chosen_method_id): string
    {
        $chosen_method_id = (string) $chosen_method_id;
        if ($chosen_method_id === '') {
            return '';
        }

        foreach (WC()->shipping()->get_packages() as $package) {
            if (empty($package['rates'])) {
                continue;
            }
            foreach ($package['rates'] as $rate) {
                if ($rate->id === $chosen_method_id) {
                    return (string) $rate->get_label();
                }
            }
        }

        return '';
    }

    private function get_order_summary_html($show_shipping_selector = true, $show_shipping_cost = null)
    {
        if ($show_shipping_cost === null) {
            $show_shipping_cost = $show_shipping_selector;
        }

        // بعد از تعیین شدن $show_shipping_cost اعمال می‌شود: خاموش بودن محاسبه‌گر
        // فقط انتخابگر را حذف می‌کند، ردیف «هزینه ارسال» سر جایش می‌ماند
        if ($show_shipping_selector && !$this->cart_shipping_calc_enabled()) {
            $show_shipping_selector = false;
        }

        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        $cart             = WC()->cart;
        $needs_shipping   = $cart->needs_shipping();
        $shipping_total   = $cart->get_shipping_total();
        $total            = $cart->get_total('');

        if (!$show_shipping_cost && $needs_shipping && $shipping_total > 0) {
            $total = $total - $shipping_total;
        }

        $chosen_methods   = WC()->session ? WC()->session->get('chosen_shipping_methods', []) : [];
        $chosen_method_id = !empty($chosen_methods) ? $chosen_methods[0] : '';

        ob_start();
        ?>
        <div class="nias-fastsell-order-summary" id="nias-fastsell-order-summary">

            <?php if ($needs_shipping && $show_shipping_selector): ?>
                <div class="nias-fastsell-shipping-section">
                    <span class="nias-fastsell-section-title">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="#2563EB" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13V6a1 1 0 0 1 1-1h10v8M14 8h4l3 3v2M3 16h11M21 16h-2"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/></svg>
                        <?php _e('روش ارسال', 'nias-login-signup'); ?>
                    </span>
                    <?php
                    $packages = WC()->shipping()->get_packages();
                    $all_methods = [];
                    foreach ($packages as $package) {
                        if (!empty($package['rates'])) {
                            foreach ($package['rates'] as $rate) {
                                $all_methods[] = $rate;
                            }
                        }
                    }
                    if (!empty($all_methods)):
                        foreach ($all_methods as $method):
                            $is_checked = ($chosen_method_id === $method->id) ? 'checked' : '';
                            if (empty($chosen_method_id) && $method === $all_methods[0]) {
                                $is_checked = 'checked';
                            }
                            ?>
                            <label class="nias-fastsell-shipping-option">
                                <input type="radio" name="nias_cart_shipping_method"
                                       value="<?php echo esc_attr($method->id); ?>"
                                       class="nias-fastsell-cart-shipping-radio"
                                       <?php echo $is_checked; ?> />
                                <span class="nias-fastsell-shipping-label"><?php echo esc_html($method->get_label()); ?></span>
                                <span class="nias-fastsell-shipping-cost">
                                    <?php echo wc_cart_totals_shipping_method_label($method); ?>
                                </span>
                            </label>
                        <?php endforeach;
                    else: ?>
                        <p class="nias-fastsell-no-shipping"><?php _e('روش ارسالی موجود نیست', 'nias-login-signup'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="nias-fastsell-totals-section">
                <?php if ($show_shipping_cost && $needs_shipping && $shipping_total > 0): ?>
                    <div class="nias-fastsell-total-line nias-fastsell-shipping-cost-line">
                        <span><?php _e('هزینه ارسال:', 'nias-login-signup'); ?></span>
                        <span><?php echo wc_price($shipping_total); ?></span>
                    </div>
                <?php elseif ($show_shipping_cost && $needs_shipping && $shipping_total == 0 && !empty($chosen_method_id)):
                    // هزینه صفر لزوماً یعنی «رایگان» نیست — ممکن است روش انتخابی
                    // پس‌کرایه یا تحویل حضوری باشد که هزینه‌اش سر تحویل گرفته
                    // می‌شود. با این تنظیم، به‌جای «رایگان» نام خود روش می‌نشیند.
                    $free_label = __('رایگان', 'nias-login-signup');
                    if (get_option('nias_fastsell_free_shipping_show_method')) {
                        $method_label = $this->get_chosen_shipping_label($chosen_method_id);
                        if ($method_label !== '') {
                            $free_label = $method_label;
                        }
                    }
                    ?>
                    <div class="nias-fastsell-total-line nias-fastsell-shipping-cost-line">
                        <span><?php _e('هزینه ارسال:', 'nias-login-signup'); ?></span>
                        <span><?php echo esc_html($free_label); ?></span>
                    </div>
                <?php endif; ?>

                <div class="nias-fastsell-total-line nias-fastsell-grand-total">
                    <span><?php _e('جمع کل:', 'nias-login-signup'); ?></span>
                    <?php if ((float) $total <= 0): ?>
                        <span class="nias-fastsell-grand-total-val nias-fastsell-free-label"><?php _e('رایگان', 'nias-login-signup'); ?></span>
                    <?php else: ?>
                        <span class="nias-fastsell-grand-total-val"><?php echo wc_price((float) $total); ?></span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    private function render_checkout_form()
    {
        $checkout = WC()->checkout();

        $billing_title  = 'اطلاعات صورتحساب';
        $shipping_title = 'روش حمل و نقل';
        $payment_title  = 'روش پرداخت';

        if (nias_elementor_design_active(get_option('nias-fastsell-shortcode', ''))) {
            ?>
            <script>
                if (typeof window.niasFastsellElementorSettings !== 'undefined') {
                    var settings = window.niasFastsellElementorSettings;
                    if (settings.billing_title) {
                        jQuery('.nias-login-fastsell-billing-fields-title').text(settings.billing_title);
                    }
                    if (settings.shipping_title) {
                        jQuery('.nias-login-fastsell-shipping-methods-title').text(settings.shipping_title);
                    }
                    if (settings.payment_title) {
                        jQuery('.nias-login-fastsell-payment-methods-title').text(settings.payment_title);
                    }
                }
            </script>
            <?php
        }

        // بارگذاری اسکریپت‌های چک‌اوت (برای پلاگین‌های شهر، پرداخت و...)
        ?>
        <script>
        (function($) {
            // راه‌اندازی مجدد selectWoo/select2 روی المان‌های جدید
            $(document).trigger('nias_fastsell_checkout_loaded');
            if (typeof $.fn.selectWoo !== 'undefined') {
                $('#nias-login-fastsell-checkout-container select').selectWoo();
            } else if (typeof $.fn.select2 !== 'undefined') {
                $('#nias-login-fastsell-checkout-container select').select2();
            }
            // فایر کردن event های WC
            $(document.body).trigger('country_to_state_changed');
            $(document.body).trigger('update_checkout');
            if (typeof wc_country_select_params !== 'undefined') {
                $(document.body).trigger('wc-credit-card-form-init');
            }
        })(jQuery);
        </script>
        <?php

        ?>
        <div class="nias-login-fastsell-checkout-fields">
            <?php do_action('woocommerce_before_checkout_form', $checkout); ?>

            <?php // فیلدهای «منبع سفارش» ووکامرس (Order Attribution) ?>
            <?php $this->render_order_attribution_inputs(); ?>

            <div class="woocommerce-billing-fields">
                <span class="nias-login-fastsell-billing-fields-title"><?php echo esc_html($billing_title); ?></span>
                <?php
                $fields = $checkout->get_checkout_fields('billing');
                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, $checkout->get_value($key));
                }
                ?>
            </div>

            <?php if (get_option('nias_fastsell_show_shipping_address', 1)): ?>
            <div class="woocommerce-shipping-fields">
                <span class="nias-login-fastsell-ship-to-different-address-title"><?php _e('ارسال به آدرس متفاوت؟', 'nias-login-signup'); ?></span>
                <label class="nias-login-fastsell-ship-to-different-address">
                    <input type="checkbox" name="ship_to_different_address" value="1">
                    <span><?php _e('ارسال به آدرس متفاوت؟', 'nias-login-signup'); ?></span>
                </label>
                <div class="nias-login-fastsell-shipping-fields" style="display:none;">
                    <?php
                    $fields = $checkout->get_checkout_fields('shipping');
                    foreach ($fields as $key => $field) {
                        woocommerce_form_field($key, $field, $checkout->get_value($key));
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="woocommerce-additional-fields">
                <?php
                $fields = $checkout->get_checkout_fields('order');
                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, $checkout->get_value($key));
                }
                ?>
            </div>

            <?php if (WC()->cart->needs_shipping()): ?>
                <div class="nias-login-fastsell-shipping-methods">
                    <span class="nias-login-fastsell-shipping-methods-title"><?php echo esc_html($shipping_title); ?></span>
                    <?php // فقط این ناحیه با تغییر آدرس دوباره رندر می‌شود تا عنوان سفارشی حفظ بماند ?>
                    <div class="nias-login-fastsell-shipping-options">
                        <?php echo $this->get_shipping_methods_html(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="nias-login-fastsell-payment-methods">
                <?php echo $this->get_payment_section_html($payment_title); ?>
            </div>

            <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
        </div>
        <?php echo $this->get_price_breakdown_html(); ?>
        <?php
    }

    /**
     * فهرست روش‌های حمل و نقل مرحله تسویه.
     * جدا شده تا پس از تغییر استان/شهر/محله بتوان همین بخش را دوباره رندر کرد —
     * افزونه‌های حمل و نقل (مثل «افزونه حمل و نقل ووکامرس») نرخ را بر اساس مقصد
     * محاسبه می‌کنند، پس با هر تغییر آدرس باید دوباره ساخته شود.
     */
    private function get_shipping_methods_html(): string
    {
        if (!WC()->cart->needs_shipping()) {
            return '';
        }

        ob_start();
        $packages      = WC()->shipping()->get_packages();
        $chosen_method = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
        $has_method    = false;

        foreach ($packages as $package) {
            $available_methods = isset($package['rates']) ? $package['rates'] : [];

            foreach ($available_methods as $method) {
                $has_method = true;
                $is_checked = (!empty($chosen_method) && $chosen_method[0] === $method->id) ? 'checked' : '';
                ?>
                <label class="nias-login-fastsell-shipping-method-option">
                    <input type="radio" name="shipping_method" value="<?php echo esc_attr($method->id); ?>" <?php echo $is_checked; ?>>
                    <span><?php echo esc_html($method->get_label()); ?></span>
                    <span class="nias-login-fastsell-method-cost"><?php echo wc_cart_totals_shipping_method_label($method); ?></span>
                </label>
                <?php
            }
        }

        // بدون مقصد معتبر، افزونه‌های حمل و نقل هیچ نرخی برنمی‌گردانند؛ به‌جای
        // ناحیه‌ی خالی به کاربر بگو چه چیزی لازم است
        if (!$has_method) {
            ?>
            <p class="nias-fastsell-no-shipping"><?php _e('برای نمایش روش‌ها و هزینه ارسال، ابتدا استان و شهر خود را انتخاب کنید', 'nias-login-signup'); ?></p>
            <?php
        }

        return ob_get_clean();
    }

    private function get_payment_section_html($payment_title = 'روش پرداخت')
    {
        if (WC()->session) {
            WC()->session->set('refresh_totals', true);
        }
        WC()->cart->calculate_totals();

        $render_cart_total = (float) WC()->cart->get_total('');
        $is_free_render    = $render_cart_total <= 0;

        ob_start();
        ?>
        <span class="nias-login-fastsell-payment-methods-title"><?php echo esc_html($payment_title); ?></span>
        <input type="hidden" id="nias-fastsell-cart-total" value="<?php echo esc_attr($render_cart_total); ?>">
        <?php if ($is_free_render): ?>
            <div class="nias-fastsell-free-order-notice">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#28a745"/></svg>
                <?php _e('این سفارش رایگان است و نیاز به پرداخت ندارد', 'nias-login-signup'); ?>
            </div>
        <?php else:
            WC()->payment_gateways()->init();
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            $chosen_gateway     = WC()->session ? WC()->session->get('chosen_payment_method') : '';

            // اگر درگاه انتخاب‌شده قبلی با محدودیت خرید تکی حذف شده، اولین درگاه مجاز انتخاب شود
            if ($chosen_gateway && !isset($available_gateways[$chosen_gateway])) {
                $chosen_gateway = '';
            }

            if (!empty($available_gateways)) {
                $first_gateway = true;
                foreach ($available_gateways as $gateway) {
                    if ($first_gateway && empty($chosen_gateway)) {
                        $is_checked     = 'checked';
                        $chosen_gateway = $gateway->id;
                        $first_gateway  = false;
                    } else {
                        $is_checked = ($chosen_gateway === $gateway->id) ? 'checked' : '';
                    }
                    ?>
                    <label class="nias-login-fastsell-payment-method-option">
                        <input type="radio" name="payment_method" value="<?php echo esc_attr($gateway->id); ?>" <?php echo $is_checked; ?>>
                        <span><?php echo esc_html($gateway->get_title()); ?></span>
                        <?php if (method_exists($gateway, 'get_icon')) { echo $gateway->get_icon(); } ?>
                        <?php if ($gateway->has_fields() || $gateway->get_description()): ?>
                            <div class="nias-login-fastsell-payment-box" style="<?php echo $is_checked ? '' : 'display:none;'; ?>">
                                <?php $gateway->payment_fields(); ?>
                            </div>
                        <?php endif; ?>
                    </label>
                    <?php
                }
            } else { ?>
                <p class="nias-login-fastsell-no-payment-methods">
                    <?php _e('متأسفانه هیچ روش پرداختی در دسترس نیست. لطفاً با مدیر سایت تماس بگیرید.', 'nias-login-signup'); ?>
                </p>
            <?php } ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * شورت‌کد [nias_fastsell] — همان محتوای مودال، ولی درون خودِ صفحه.
     *
     * برای صفحه‌های سبد خرید و تسویه حساب ووکامرس که مودال در آن‌ها رندر نمی‌شود.
     * مارک‌آپ عمداً همان id مودال (#nias-login-fastsell-modal) را دارد تا تمام
     * استایل‌ها و هندلرهای موجود بدون تغییر روی آن کار کنند؛ در عوض هر جا این
     * شورت‌کد رندر شود، مودالِ فوتر چاپ نمی‌شود تا id تکراری نشود.
     */
    public function render_inline_shortcode($atts = [])
    {
        // فقط یک نمونه در هر صفحه (id باید یکتا بماند)
        if ($this->inline_rendered || is_admin() || is_feed()) {
            return '';
        }
        if (!function_exists('WC')) {
            return '';
        }

        $this->inline_rendered = true;

        // صفحه‌سازهایی که محتوا را در post_content نگه نمی‌دارند از دید
        // page_has_inline_shortcode() پنهان‌اند؛ اینجا جبران می‌شود.
        // استایل/اسکریپتی که بعد از wp_head اضافه شود در فوتر چاپ می‌گردد.
        $this->enqueue_assets();

        $elementor_shortcode = (string) get_option('nias-fastsell-shortcode', '');

        ob_start();

        echo '<div id="nias-login-fastsell-modal" class="nias-login-fastsell-modal nias-login-fastsell-inline" dir="rtl">';

        // بدون المنتور، قالب اختصاصی رندر نمی‌شود و قالب پیش‌فرض جایش را می‌گیرد
        if (nias_elementor_design_active($elementor_shortcode)) {
            // همان ساختاری که modal_template() برای قالب المنتور می‌سازد، بدون overlay
            echo '<div class="nias-login-fastsell-modal-content elementor-template">';
            echo nias_elementor_render_template($elementor_shortcode);
            echo '</div>';
        } else {
            $template_path = plugin_dir_path(__FILE__) . 'templates/inline.php';
            if (file_exists($template_path)) {
                include $template_path;
            }
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * شورت‌کد [nias_fastsell_add_to_cart] — دکمه «افزودن به سبد» خرید سریع.
     *
     * در صفحه محصول بدون پارامتر (شناسه از خودِ صفحه خوانده می‌شود) و در هر صفحه
     * دیگری با product_id کار می‌کند. کلیک روی آن دقیقاً همان مسیر دکمه‌های
     * پیش‌فرض را طی می‌کند: افزودن AJAXی به سبد و باز شدن مودال خرید سریع
     * (اگر گزینه «باز شدن مودال پس از افزودن» فعال باشد).
     *
     * پارامترها: product_id (یا id)، quantity، text، class
     */
    public function render_add_to_cart_shortcode($atts = [])
    {
        if (is_admin() || is_feed() || !$this->wc_available()) {
            return '';
        }

        $atts = shortcode_atts([
            'product_id' => 0,
            'id'         => 0,
            'quantity'   => 1,
            'text'       => '',
            'class'      => '',
        ], $atts, 'nias_fastsell_add_to_cart');

        $product_id = absint($atts['product_id']) ?: absint($atts['id']);
        if (!$product_id && function_exists('is_product') && is_product()) {
            $product_id = (int) get_queried_object_id();
        }
        if (!$product_id) {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        $quantity = max(1, absint($atts['quantity']));
        $label    = trim((string) $atts['text']);
        if ('' === $label) {
            $label = $product->single_add_to_cart_text();
        }

        $classes = ['nias-fastsell-atc'];
        foreach (preg_split('/\s+/', (string) $atts['class']) as $extra_class) {
            $extra_class = sanitize_html_class($extra_class);
            if ('' !== $extra_class) {
                $classes[] = $extra_class;
            }
        }

        // صفحه‌سازهایی که محتوا را در post_content نگه نمی‌دارند از دید
        // page_has_inline_shortcode() پنهان‌اند؛ اینجا جبران می‌شود
        $this->enqueue_assets();

        if (!$product->is_in_stock()) {
            $classes[] = 'nias-fastsell-atc--disabled';
            return sprintf(
                '<button type="button" class="%s" disabled>%s</button>',
                esc_attr(implode(' ', $classes)),
                esc_html__('ناموجود', 'nias-login-signup')
            );
        }

        // محصول متغیر بدون فرم ویژگی‌ها قابل افزودن نیست؛ بیرون از صفحه خودِ محصول
        // به‌جای دکمه‌ی همیشه‌خطا، لینک به صفحه محصول داده می‌شود
        $on_product_page = function_exists('is_product') && is_product()
            && (int) get_queried_object_id() === $product_id;

        if ($product->is_type('variable') && !$on_product_page) {
            $classes[] = 'nias-fastsell-atc--link';
            return sprintf(
                '<a class="%s" href="%s">%s</a>',
                esc_attr(implode(' ', $classes)),
                esc_url(get_permalink($product_id)),
                esc_html($label)
            );
        }

        $classes[] = 'nias-fastsell-atc-shortcode';

        return sprintf(
            '<button type="button" class="%s" data-product-id="%d"%s>%s</button>',
            esc_attr(implode(' ', $classes)),
            $product_id,
            $quantity > 1 ? ' data-quantity="' . esc_attr($quantity) . '"' : '',
            esc_html($label)
        );
    }

    public function modal_template()
    {
        // اگر شورت‌کد درون‌صفحه‌ای رندر شده، مودال فوتر تکرار نمی‌شود
        if ($this->inline_rendered || !$this->wc_available()) {
            return;
        }
        // در صفحات سبد خرید و تسویه حساب WooCommerce، مودال خرید سریع رندر نمی‌شود
        if (!is_admin() && !$this->is_woo_cart_or_checkout_page()) {
            $elementor_shortcode = get_option('nias-fastsell-shortcode', '');

            if (nias_elementor_design_active($elementor_shortcode)) {
                // data-lenis-prevent — توضیح در templates/modal.php
                echo '<div id="nias-login-fastsell-modal" class="nias-login-fastsell-modal" data-lenis-prevent>';
                echo '<div class="nias-login-fastsell-modal-overlay"></div>';
                echo '<div class="nias-login-fastsell-modal-content elementor-template">';
                echo nias_elementor_render_template($elementor_shortcode);
                echo '</div>';
                echo '</div>';
            } else {
                $template_path = plugin_dir_path(__FILE__) . 'templates/modal.php';
                if (file_exists($template_path)) {
                    include $template_path;
                }
            }
        }
    }
}
