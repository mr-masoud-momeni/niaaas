<?php
defined('ABSPATH') || exit;

/**
 * ورود / ثبت‌نام با حساب گوگل (OAuth 2.0)
 *
 * از admin-ajax برای شروع و callback استفاده می‌کند تا نیازی به flush قوانین rewrite نباشد.
 * با حساب‌های موجودی که ایمیل برایشان ثبت شده سازگار است: اگر کاربری با همان ایمیل گوگل
 * وجود داشته باشد، به همان حساب وارد می‌شود؛ در غیر این صورت کاربر جدید ساخته می‌شود.
 */
class Nias_Google_Login
{
    const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /** @var Nias_Google_Login */
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_nopriv_nias_google_start', [$this, 'handle_start']);
        add_action('wp_ajax_nias_google_start', [$this, 'handle_start']);
        add_action('wp_ajax_nopriv_nias_google_callback', [$this, 'handle_callback']);
        add_action('wp_ajax_nias_google_callback', [$this, 'handle_callback']);
    }

    // ── تنظیمات ─────────────────────────────────────────────────────────────

    public static function is_enabled(): bool
    {
        return (bool) get_option('nias_google_login_activate')
            && get_option('nias_google_client_id')
            && get_option('nias_google_client_secret');
    }

    public static function button_text(): string
    {
        $t = (string) get_option('nias_google_button_text', '');
        return $t !== '' ? $t : 'ورود / ثبت‌نام با گوگل';
    }

    /** آدرس callback که باید در Google Cloud Console ثبت شود */
    public static function redirect_uri(): string
    {
        return admin_url('admin-ajax.php?action=nias_google_callback');
    }

    /** آدرس شروع فرآیند ورود با گوگل (برای دکمه) */
    public static function start_url(): string
    {
        return admin_url('admin-ajax.php?action=nias_google_start');
    }

    // ── شروع OAuth ──────────────────────────────────────────────────────────

    public function handle_start()
    {
        if (!self::is_enabled()) {
            wp_die('ورود با گوگل فعال نیست.');
        }

        // state تصادفی برای جلوگیری از CSRF + ذخیره مقصد بازگشت
        $state = wp_generate_password(24, false);

        $return_to = wp_get_referer();
        if (!$return_to || strpos($return_to, 'wp-login.php') !== false) {
            $return_to = home_url('/');
        }

        set_transient('nias_google_state_' . $state, $return_to, 10 * MINUTE_IN_SECONDS);

        $params = [
            'client_id'     => get_option('nias_google_client_id'),
            'redirect_uri'  => self::redirect_uri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];

        wp_redirect(self::AUTH_URL . '?' . http_build_query($params));
        exit;
    }

    // ── Callback ────────────────────────────────────────────────────────────

    public function handle_callback()
    {
        if (!self::is_enabled()) {
            wp_die('ورود با گوگل فعال نیست.');
        }

        // خطای بازگشتی از گوگل (مثلاً انصراف کاربر)
        if (!empty($_GET['error'])) {
            $this->redirect_with_error(home_url('/'), 'ورود با گوگل لغو شد.');
        }

        $code  = isset($_GET['code'])  ? sanitize_text_field(wp_unslash($_GET['code']))  : '';
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';

        if (!$code || !$state) {
            $this->redirect_with_error(home_url('/'), 'پاسخ نامعتبر از گوگل.');
        }

        $return_to = get_transient('nias_google_state_' . $state);
        if (!$return_to) {
            $this->redirect_with_error(home_url('/'), 'نشست ورود با گوگل منقضی شده است. دوباره تلاش کنید.');
        }
        delete_transient('nias_google_state_' . $state);

        // تبدیل code به توکن
        $token = $this->exchange_code($code);
        if (is_wp_error($token)) {
            $this->redirect_with_error($return_to, 'خطا در ارتباط با گوگل: ' . $token->get_error_message());
        }

        // دریافت اطلاعات کاربر
        $profile = $this->fetch_userinfo($token);
        if (is_wp_error($profile)) {
            $this->redirect_with_error($return_to, 'خطا در دریافت اطلاعات حساب گوگل.');
        }

        $email = isset($profile['email']) ? sanitize_email($profile['email']) : '';
        $verified = !empty($profile['email_verified']) || (isset($profile['email_verified']) && $profile['email_verified'] === 'true');

        if (!$email || !is_email($email)) {
            $this->redirect_with_error($return_to, 'ایمیل معتبری از گوگل دریافت نشد.');
        }
        if (!$verified) {
            $this->redirect_with_error($return_to, 'ایمیل حساب گوگل شما تایید نشده است.');
        }

        // سازگاری با حساب‌های موجود: ابتدا بر اساس ایمیل جستجو می‌شود
        $user = get_user_by('email', $email);

        if (!$user) {
            $user = $this->create_user_from_google($email, $profile);
            if (is_wp_error($user)) {
                $this->redirect_with_error($return_to, 'خطا در ایجاد حساب کاربری: ' . $user->get_error_message());
            }
        }

        // ذخیره شناسه گوگل برای مراجعات بعدی
        if (!empty($profile['sub'])) {
            update_user_meta($user->ID, 'nias_google_id', sanitize_text_field($profile['sub']));
        }

        // مسدودسازی مدیر در صورت فعال بودن (هماهنگ با سایر مسیرهای ورود)
        if (get_option('nias_block_admin_phone_login', 0)
            && !get_option('nias_replace_wp_login', 0)
            && in_array('administrator', (array) $user->roles, true)) {
            $this->redirect_with_error($return_to, 'مدیران سیستم مجاز به ورود از این روش نیستند.');
        }

        $this->login_user($user);
        do_action('wp_login', $user->user_login, $user);

        $redirect = get_option('nias_login_redirect') ?: $return_to ?: home_url('/');
        wp_safe_redirect($redirect);
        exit;
    }

    // ── کمکی‌ها ──────────────────────────────────────────────────────────────

    private function exchange_code(string $code)
    {
        $response = wp_remote_post(self::TOKEN_URL, [
            'timeout' => 20,
            'body'    => [
                'code'          => $code,
                'client_id'     => get_option('nias_google_client_id'),
                'client_secret' => get_option('nias_google_client_secret'),
                'redirect_uri'  => self::redirect_uri(),
                'grant_type'    => 'authorization_code',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['access_token'])) {
            $msg = is_array($data) && isset($data['error_description']) ? $data['error_description'] : 'توکن دریافت نشد';
            return new WP_Error('nias_google_token', $msg);
        }

        return (string) $data['access_token'];
    }

    private function fetch_userinfo(string $access_token)
    {
        $response = wp_remote_get(self::USERINFO_URL, [
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['email'])) {
            return new WP_Error('nias_google_userinfo', 'اطلاعات کاربر دریافت نشد');
        }

        return $data;
    }

    /** @return WP_User|WP_Error */
    private function create_user_from_google(string $email, array $profile)
    {
        $username = sanitize_user(current(explode('@', $email)), true);
        if ($username === '' || username_exists($username)) {
            $username = $username . '_' . wp_rand(100, 999);
        }

        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password(20, true, true),
            'first_name'   => isset($profile['given_name']) ? sanitize_text_field($profile['given_name']) : '',
            'last_name'    => isset($profile['family_name']) ? sanitize_text_field($profile['family_name']) : '',
            'display_name' => isset($profile['name']) ? sanitize_text_field($profile['name']) : $username,
            'role'         => get_option('nias_default_user_role', get_option('default_role', 'subscriber')),
        ]);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return get_user_by('ID', $user_id);
    }

    /** ست کردن کوکی احراز هویت با مدت یک سال (هماهنگ با سایر مسیرهای ورود نیاس) */
    private function login_user($user)
    {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);

        add_filter('auth_cookie_expiration', function ($exp, $uid, $remember) {
            return $remember ? 365 * DAY_IN_SECONDS : $exp;
        }, 10, 3);

        wp_set_auth_cookie($user->ID, true, is_ssl());
        remove_all_filters('auth_cookie_expiration');
    }

    private function redirect_with_error(string $url, string $message)
    {
        $url = add_query_arg('nias_google_error', rawurlencode($message), $url);
        wp_safe_redirect($url);
        exit;
    }
}

Nias_Google_Login::get_instance();

/* -------------------------------------------------------------------------- */
/*                    توابع کمکی برای استفاده در قالب‌ها                       */
/* -------------------------------------------------------------------------- */

if (!function_exists('nias_google_login_enabled')) {
    function nias_google_login_enabled(): bool
    {
        return Nias_Google_Login::is_enabled();
    }
}

if (!function_exists('nias_google_login_button')) {
    /**
     * دکمه‌ی تمام‌عرض «ورود با گوگل» (در صورت فعال بودن)
     */
    function nias_google_login_button(): string
    {
        if (!Nias_Google_Login::is_enabled()) {
            return '';
        }

        $url   = esc_url(Nias_Google_Login::start_url());
        $label = esc_html(Nias_Google_Login::button_text());
        $logo  = '<svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.5 0 10.4-2.1 14.1-5.5l-6.5-5.5c-2 1.5-4.7 2.5-7.6 2.5-5.2 0-9.6-3.3-11.2-8l-6.5 5C9.6 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.5l6.5 5.5C41.4 36.6 44 30.8 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>';

        return '<a class="nias-google-btn" href="' . $url . '">' . $logo . '<span>' . $label . '</span></a>';
    }
}
