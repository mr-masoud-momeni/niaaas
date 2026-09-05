<?php
defined('ABSPATH') || exit;

/**
 * Nias Login Handler Class
 * Handles SMS/Email login with OTP, password, and password+OTP modes
 */
class Nias_Login_Handler
{
    /** @var Nias_Login_Handler */
    private static $instance = null;

    /** @var Nias_SMS_Gateway */
    private $gateway;

    /** @var Nias_Email */
    private $email_gateway;

    private $email_activate;
    private $email_phone_activate;

    /** @var array Default response */
    private $result = ['message' => 'در حال بررسی اطلاعات...'];

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->email_activate       = get_option('nias_email_activate');
        $this->email_phone_activate = get_option('nias_email_phone_activate');

        require_once plugin_dir_path(__FILE__) . 'class-sms-gateway.php';
        require_once plugin_dir_path(__FILE__) . 'class-email-gateway.php';

        $this->gateway       = new Nias_SMS_Gateway();
        $this->email_gateway = new Nias_Email();

        $this->init_hooks();
    }

    private function init_hooks()
    {
        // /nias-login  → ارسال OTP یا شروع فرآیند ورود
        add_action('init', [$this, 'add_custom_login_endpoint']);
        add_filter('query_vars', [$this, 'add_login_query_var']);
        add_action('template_redirect', [$this, 'handle_login_request']);

        // /nias-verify → تایید کد یا ورود با رمز
        add_action('init', [$this, 'add_custom_verify_endpoint']);
        add_filter('query_vars', [$this, 'add_verify_query_var']);
        add_action('template_redirect', [$this, 'handle_verify_request']);

        // /nias-forgot-password → بازیابی / ارسال رمز جدید
        add_action('init', [$this, 'add_custom_forgot_password_endpoint']);
        add_filter('query_vars', [$this, 'add_forgot_password_query_var']);
        add_action('template_redirect', [$this, 'handle_forgot_password_request']);
    }


    // ─── Endpoints ───────────────────────────────────────────────────────────────

    public function add_custom_login_endpoint()
    {
        add_rewrite_rule('^nias-login/?$', 'index.php?nias_login=1', 'top');
    }

    public function add_login_query_var($vars)
    {
        $vars[] = 'nias_login';
        return $vars;
    }

    /**
     * /nias-login
     * nias_action=send_otp → ارسال OTP در حالت password_otp
     * (بدون nias_action)   → شروع فرآیند ورود
     */
    public function handle_login_request()
    {
        if (get_query_var('nias_login') == 1) {
            $action = isset($_REQUEST['nias_action']) ? sanitize_text_field($_REQUEST['nias_action']) : '';
            if ($action === 'send_otp') {
                $this->process_send_otp();
            } else {
                $this->process_sms_login();
            }
            exit;
        }
    }

    public function add_custom_verify_endpoint()
    {
        add_rewrite_rule('^nias-verify/?$', 'index.php?nias_verify=1', 'top');
    }

    public function add_verify_query_var($vars)
    {
        $vars[] = 'nias_verify';
        return $vars;
    }

    public function handle_verify_request()
    {
        if (get_query_var('nias_verify') == 1) {
            $this->process_sms_verify();
            exit;
        }
    }

    public function add_custom_forgot_password_endpoint()
    {
        add_rewrite_rule('^nias-forgot-password/?$', 'index.php?nias_forgot_password=1', 'top');
    }

    public function add_forgot_password_query_var($vars)
    {
        $vars[] = 'nias_forgot_password';
        return $vars;
    }

    public function handle_forgot_password_request()
    {
        if (get_query_var('nias_forgot_password') == 1) {
            $this->process_forgot_password();
            exit;
        }
    }

    // ─── OTP Send (password_otp mode) ────────────────────────────────────────────

    /**
     * ارسال OTP هنگام باز کردن تب کد تایید در حالت password_otp_activate
     * فراخوانی از: POST /nias-login?nias_action=send_otp
     */
    private function process_send_otp()
    {
        $identifier = isset($_REQUEST['identifier']) ? sanitize_text_field($_REQUEST['identifier']) : null;

        if (!$identifier) {
            wp_send_json_error(['message' => 'شناسه ارسال نشده'], 400);
            return;
        }

        $identifier = nias_normalize_digits($identifier);
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if ($this->is_ip_blocked($ip_address)) {
            wp_send_json_error(['message' => 'شما قبلاً محدود شدید و مجاز به ورود نیستید! به پشتیبان سایت اطلاع دهید'], 401);
            return;
        }

        $security_mode = get_option('nias_security_mode', 'block');
        if ($security_mode === 'cooldown') {
            $cooldown = $this->get_cooldown_status($ip_address);
            if ($cooldown['blocked']) {
                wp_send_json_error(['message' => $cooldown['message']], 429);
                return;
            }
        }

        if ($this->should_block_ip($ip_address)) {
            $this->block_ip($ip_address);
            wp_send_json_error(['message' => 'بیش از حد تلاش کردید برای رفع محدودیت به پشتیبان سایت اطلاع دهید'], 401);
            return;
        }

        $verify = $this->get_verification_code($identifier);

        if (!$verify) {
            wp_send_json_error(['message' => 'کدی یافت نشد، لطفاً شماره خود را مجدداً ارسال کنید'], 404);
            return;
        }

        $digit      = get_option('nsdigitsquantity');
        $expire     = get_option('nias_login_expire');
        $is_expired = current_time('timestamp') >= strtotime($verify->expired_at);

        // اگر کد منقضی شده → کد جدید بساز و در همان رکورد آپدیت کن
        if ($is_expired) {
            $new_code   = nias_generate_code($digit);
            $expired_at = date('Y-m-d H:i:s', current_time('timestamp') + $expire);

            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'nias_sms_login',
                [
                    'code'       => $new_code,
                    'expired_at' => $expired_at,
                    'attempts'   => 0,
                    'verified'   => 0,
                ],
                ['ID' => $verify->ID]
            );

            // transient قبلی را پاک کن تا ارسال جدید مجاز باشد
            delete_transient('nias_otp_sent_' . md5($identifier));

            $verify->code       = $new_code;
            $verify->expired_at = $expired_at;
        }

        // جلوگیری از ارسال مجدد تا زمانی که کد هنوز معتبر است
        $sent_flag_key = 'nias_otp_sent_' . md5($identifier);
        if (get_transient($sent_flag_key)) {
            $remaining = max((int) (strtotime($verify->expired_at) - current_time('timestamp')), 30);
            wp_send_json_error([
                'message'    => 'لطفاً کمی صبر کنید کد قبلی ارسال شده هنوز مهلت دارد',
                'error_code' => 123,
                'identifier' => $identifier,
                'duration'   => $remaining,
                '_wpnonce'   => wp_create_nonce('verify' . $identifier),
            ], 401);
            return;
        }

        $phone        = $verify->phone ?? null;
        $email        = $verify->email ?? null;
        $code         = $verify->code;
        $bale_active  = get_option('nias_bale_activate');
        $send_results = [];

        if ($phone) {
            $sms_response = $this->gateway->send_code($phone, $code);
            if ($bale_active) {
                $this->gateway->send_bale($phone, $code);
            }
            $send_results['sms'] = $this->parse_sms_result($sms_response);
        }

        if ($email) {
            $email_response = $this->email_gateway->send_email_gateway($identifier, $email, $code);
            $send_results['email'] = $this->parse_email_result($email_response);
        }

        $has_success = !empty(array_filter($send_results, fn($r) => $r['success']));

        if (!$has_success) {
            wp_send_json_error(['message' => 'خطا در ارسال کد تایید'], 503);
            return;
        }

        $remaining = max(strtotime($verify->expired_at) - current_time('timestamp'), 60);
        set_transient($sent_flag_key, 1, $remaining);

        $parts = [];
        if ($phone) $parts[] = 'شماره ' . $phone;
        if ($email) $parts[] = 'ایمیل ' . $email;

        wp_send_json_success([
            'message'    => 'کد ' . $digit . ' رقمی ارسال شده به ' . implode(' و ', $parts) . ' را وارد کنید',
            'duration'   => $remaining,
            'identifier' => $identifier,
        ], 200);
    }



    // ─── Login (Step 1) ──────────────────────────────────────────────────────────

    /**
     * پردازش مرحله اول ورود: دریافت شماره/ایمیل و ارسال OTP یا شروع حالت رمز
     */
    private function process_sms_login()
    {
        $password_activate     = get_option('nias_password_activate');
        $password_otp_activate = get_option('nias_password_otp_activate');

        // حالت فقط رمز عبور
        if ($password_activate && !$password_otp_activate) {
            $response = [
                'message'    => 'در این حالت فقط امکان ورود با رمز عبور وجود دارد',
                'login_mode' => 'password_only',
            ];

            // ثبت پسورد دستی: تحلیل کاربر تا فقط برای کاربر جدید فیلد ساخت رمز نمایش داده شود
            if (get_option('nias_manual_password_activate')) {
                [$phone, $email, $identifier] = $this->extract_identifier();
                if (!is_wp_error($phone)) {
                    $user_analysis = $this->analyze_user_data($phone, $email);
                    $response['identifier']      = $identifier;
                    $response['user_status']     = $user_analysis['user_status'];
                    $response['is_new_user']     = $user_analysis['is_new_user'];
                    $response['manual_password'] = (bool) $user_analysis['is_new_user'];
                    $response['_wpnonce']        = wp_create_nonce('verify' . $identifier);
                }
            }

            wp_send_json_success($response, 200);
            return;
        }

        // دریافت و اعتبارسنجی ورودی
        [$phone, $email, $identifier] = $this->extract_identifier();
        if (is_wp_error($phone)) {
            $this->result['message'] = $phone->get_error_message();
            wp_send_json_error($this->result, 401);
            return;
        }

        $ip_address = $_SERVER['REMOTE_ADDR'];

        // بررسی‌های امنیتی IP
        if ($this->is_ip_blocked($ip_address)) {
            $this->result['message'] = 'شما قبلاً محدود شدید و مجاز به ورود نیستید! به پشتیبان سایت اطلاع دهید';
            wp_send_json_error($this->result, 401);
            return;
        }

        $security_mode = get_option('nias_security_mode', 'block');
        if ($security_mode === 'cooldown') {
            $cooldown = $this->get_cooldown_status($ip_address);
            if ($cooldown['blocked']) {
                $this->result['message'] = $cooldown['message'];
                wp_send_json_error($this->result, 429);
                return;
            }
        }

        if ($this->should_block_ip($ip_address)) {
            $this->block_ip($ip_address);
            $this->result['message'] = 'بیش از حد تلاش کردید برای رفع محدودیت به پشتیبان سایت اطلاع دهید';
            wp_send_json_error($this->result, 401);
            return;
        }

        // بررسی کد منقضی نشده
        $unexpired = $this->get_unexpired_code($identifier, $ip_address);
        if ($unexpired) {
            $remaining = max((int) (strtotime($unexpired->expired_at) - current_time('timestamp')), 30);
            wp_send_json_error([
                'message'    => 'لطفاً کمی صبر کنید کد قبلی ارسال شده هنوز مهلت دارد',
                'error_code' => 123,
                'identifier' => $identifier,
                'duration'   => $remaining,
                '_wpnonce'   => wp_create_nonce('verify' . $identifier),
            ], 401);
            return;
        }

        // تحلیل کاربر و ثبت کد در دیتابیس
        $user_analysis = $this->analyze_user_data($phone, $email);
        $digit         = get_option('nsdigitsquantity');
        $expire        = get_option('nias_login_expire');
        $code          = nias_generate_code($digit);
        $expired_at    = date('Y-m-d H:i:s', current_time('timestamp') + $expire);

        $inserted = $this->register_verification_code($phone, $email, $code, $expired_at, $ip_address, $user_analysis);
        if (is_wp_error($inserted)) {
            $this->result['message'] = $inserted->get_error_message();
            wp_send_json_error($this->result, 503);
            return;
        }

        // حالت password_otp: کد ثبت شد، ارسال OTP فقط با باز کردن تب کد تایید
        if ($password_otp_activate) {
            wp_send_json_success([
                'message'           => 'لطفاً رمز عبور خود را وارد کنید یا تب کد تایید را انتخاب کنید',
                'duration'          => $expire,
                'identifier'        => $identifier,
                'password_otp_mode' => true,
                'user_status'       => $user_analysis['user_status'],
                'is_new_user'       => $user_analysis['is_new_user'],
                'manual_password'   => (bool) (get_option('nias_manual_password_activate') && $user_analysis['is_new_user']),
                '_wpnonce'          => wp_create_nonce('verify' . $identifier),
            ], 200);
            return;
        }

        // ارسال کد / رمز
        $this->send_verification_code($user_analysis, $code, $digit, $expire, $identifier);
    }

    /**
     * استخراج و اعتبارسنجی identifier از درخواست
     * @return array [phone|WP_Error, email|null, identifier|null]
     */
    private function extract_identifier()
    {
        $phone      = null;
        $email      = null;
        $identifier = null;

        if ($this->email_phone_activate) {
            if (!isset($_REQUEST['ns_email_phone'])) {
                return [new WP_Error('missing', 'ایمیل یا شماره موبایل ارسال نشده است'), null, null];
            }
            $input = nias_normalize_digits(sanitize_text_field($_REQUEST['ns_email_phone']));

            if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
                $email = sanitize_email($input);
                if (!$email) {
                    return [new WP_Error('invalid', 'ایمیل وارد شده صحیح نیست'), null, null];
                }
                $identifier = $email;
            } else {
                $phone = nias_sanitize_phone_enhanced($input);
                if (!$phone) {
                    return [new WP_Error('invalid', 'ایمیل یا شماره موبایل صحیح نیست'), null, null];
                }
                $identifier = $phone;
            }
        } elseif ($this->email_activate) {
            if (!isset($_REQUEST['ns_email'])) {
                return [new WP_Error('missing', 'ایمیل ارسال نشده است'), null, null];
            }
            $input = nias_normalize_digits(sanitize_text_field($_REQUEST['ns_email']));
            $email = sanitize_email($input);
            if (!$email) {
                return [new WP_Error('invalid', 'ایمیل صحیح نیست'), null, null];
            }
            $identifier = $email;
        } else {
            if (!isset($_REQUEST['phone'])) {
                return [new WP_Error('missing', 'شماره تلفن ارسال نشده است'), null, null];
            }
            $input = nias_normalize_digits(sanitize_text_field($_REQUEST['phone']));
            $phone = nias_sanitize_phone_enhanced($input);
            if (!$phone) {
                return [new WP_Error('invalid', 'شماره تلفن صحیح نیست'), null, null];
            }
            $identifier = $phone;
        }

        return [$phone, $email, $identifier];
    }


    // ─── User Analysis ───────────────────────────────────────────────────────────

    /**
     * تحلیل وضعیت کاربر بر اساس شماره/ایمیل
     */
    private function analyze_user_data($phone, $email)
    {
        $analysis = [
            'phone_user'    => null,
            'email_user'    => null,
            'is_new_user'   => false,
            'send_to_phone' => false,
            'send_to_email' => false,
            'final_phone'   => null,
            'final_email'   => null,
            'user_status'   => 'unknown',
        ];

        if ($phone) {
            $analysis['phone_user'] = nias_get_user_by_phone($phone);
        }
        if ($email) {
            $analysis['email_user'] = get_user_by('email', $email);
        }

        if ($this->email_phone_activate) {
            if ($phone) {
                $analysis['send_to_phone'] = true;
                $analysis['final_phone']   = $phone;

                if ($analysis['phone_user']) {
                    $analysis['user_status'] = 'existing';
                    $user_email = $analysis['phone_user']->user_email;
                    if ($user_email && strpos($user_email, '@') !== false) {
                        $analysis['send_to_email'] = true;
                        $analysis['final_email']   = $user_email;
                    }
                } else {
                    $analysis['is_new_user'] = true;
                    $analysis['user_status'] = 'new';
                }
            } elseif ($email) {
                $analysis['send_to_email'] = true;
                $analysis['final_email']   = $email;

                if ($analysis['email_user']) {
                    $analysis['user_status'] = 'existing';
                    // تلاش برای بازیابی شماره از متا
                    $user_phone = get_user_meta($analysis['email_user']->ID, 'phone', true)
                        ?: get_user_meta($analysis['email_user']->ID, 'billing_phone', true);

                    if ($user_phone) {
                        $normalized = nias_sanitize_phone_enhanced($user_phone);
                        if ($normalized) {
                            $analysis['send_to_phone'] = true;
                            $analysis['final_phone']   = $normalized;
                        }
                    }
                } else {
                    $analysis['is_new_user'] = true;
                    $analysis['user_status'] = 'new';
                }
            }
        } elseif ($this->email_activate) {
            $analysis['send_to_email'] = true;
            $analysis['final_email']   = $email;
            $analysis['is_new_user']   = !$analysis['email_user'];
            $analysis['user_status']   = $analysis['email_user'] ? 'existing' : 'new';
        } else {
            $analysis['send_to_phone'] = true;
            $analysis['final_phone']   = $phone;
            $analysis['is_new_user']   = !$analysis['phone_user'];
            $analysis['user_status']   = $analysis['phone_user'] ? 'existing' : 'new';
        }

        return $analysis;
    }

    // ─── DB Helpers ──────────────────────────────────────────────────────────────

    /**
     * ثبت کد تایید در دیتابیس
     */
    private function register_verification_code($phone, $email, $code, $expired_at, $ip_address, $user_analysis)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'nias_sms_login';

        $data = [
            'code'          => $code,
            'expired_at'    => $expired_at,
            'ip'            => $ip_address,
            'created_at'    => current_time('mysql'),
            'attempts'      => 0,
            'user_status'   => $user_analysis['user_status'],
            'send_to_phone' => $user_analysis['send_to_phone'] ? 1 : 0,
            'send_to_email' => $user_analysis['send_to_email'] ? 1 : 0,
        ];

        if ($user_analysis['final_phone']) {
            $data['phone'] = $user_analysis['final_phone'];
        }
        if ($user_analysis['final_email']) {
            $data['email'] = $user_analysis['final_email'];
        }

        if ($wpdb->insert($table, $data) === false) {
            // ثبت علت دقیق خطای MySQL در لاگ برای عیب‌یابی
            // (مثلاً نبودن ستون، نوع/طول نامناسب، یا نبودن خود جدول)
            error_log('Nias SMS login DB insert failed on ' . $table . ': ' . $wpdb->last_error);
            return new WP_Error('db_insert_error', 'خطا در ثبت کد در دیتابیس');
        }

        return true;
    }

    /** دریافت کد منقضی نشده برای identifier و IP */
    private function get_unexpired_code($identifier, $ip)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'nias_sms_login';
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE $field = %s AND expired_at > %s AND ip = %s ORDER BY created_at DESC",
            $identifier, current_time('mysql'), $ip
        ));
    }

    /** دریافت آخرین کد تایید برای identifier */
    private function get_verification_code($identifier)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'nias_sms_login';
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE $field = %s ORDER BY created_at DESC",
            $identifier
        ));
    }

    private function increment_attempts($verify)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'nias_sms_login',
            [
                'attempts'   => $verify->attempts + 1,
                'updated_at' => current_time('mysql'),
            ],
            ['ID' => $verify->ID]
        );
    }

    private function reset_attempts($verify)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'nias_sms_login',
            ['attempts' => 0],
            ['ID' => $verify->ID]
        );
    }

    private function update_verification_status($verify, $user, $is_new)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'nias_sms_login',
            [
                'user_id'     => $user->ID,
                'status'      => $is_new ? 'register' : 'login',
                'verified'    => 1,
                'verified_at' => current_time('mysql'),
                'updated_at'  => current_time('mysql'),
            ],
            ['ID' => $verify->ID]
        );
    }

    // ─── Send Verification Code ───────────────────────────────────────────────────

    /**
     * ارسال کد تایید یا رمز عبور بر اساس تحلیل کاربر
     */
    private function send_verification_code($user_analysis, $code, $digit, $expire, $identifier)
    {
        $phone        = $user_analysis['final_phone'];
        $email        = $user_analysis['final_email'];
        $send_results = [];

        $password_activate     = get_option('nias_password_activate');
        $password_otp_activate = get_option('nias_password_otp_activate');
        $bale_active           = get_option('nias_bale_activate');

        // کاربر جدید + حالت پسورد → ارسال رمز به جای OTP
        $send_password = $user_analysis['is_new_user'] && ($password_activate || $password_otp_activate);
        $new_password  = $send_password ? $this->generate_random_password() : null;

        // ارسال به موبایل
        if ($user_analysis['send_to_phone'] && $phone) {
            if ($send_password) {
                $pattern   = get_option('nias_password_pattern');
                $var       = get_option('nias_password_var');
                $response  = ($pattern && $var)
                    ? $this->gateway->send_password($phone, $new_password)
                    : $this->gateway->send_code($phone, $new_password);
            } else {
                $response = $this->gateway->send_code($phone, $code);
                if ($bale_active) {
                    $this->gateway->send_bale($phone, $code);
                }
            }
            $send_results['sms'] = $this->parse_sms_result($response);
        }

        // ارسال به ایمیل
        if ($user_analysis['send_to_email'] && $email) {
            if ($send_password) {
                $subject = 'رمز عبور حساب کاربری شما';
                $message = '<p>حساب کاربری شما با موفقیت ایجاد شد.</p>'
                    . '<p>رمز عبور شما: <strong>' . esc_html($new_password) . '</strong></p>'
                    . '<p>لطفاً این رمز عبور را در مکانی امن نگهداری کنید.</p>';
                $response = $this->email_gateway->send_email_gateway($identifier, $email, $new_password, $subject, $message);
            } else {
                $response = $this->email_gateway->send_email_gateway($identifier, $email, $code);
            }
            $send_results['email'] = $this->parse_email_result($response);
        }

        $has_success   = !empty(array_filter($send_results, fn($r) => $r['success']));
        $error_details = array_map(
            fn($m, $r) => $m . ': ' . $r['message'],
            array_keys($send_results),
            $send_results
        );

        if (!$has_success) {
            $this->result['message'] = 'خطا در ارسال ' . ($send_password ? 'رمز عبور' : 'کد تایید')
                . ' - ' . implode(', ', $error_details);
            wp_send_json_error($this->result, 503);
            return;
        }

        // ایجاد کاربر جدید اگر رمز ارسال شد
        if ($send_password && $new_password) {
            $this->create_new_user($phone, $email, $identifier, $new_password);
        }

        $this->send_success_response($user_analysis, $digit, $expire, $identifier, $send_results, $send_password);
    }

    /**
     * ایجاد کاربر جدید پس از ارسال رمز
     */
    private function create_new_user($phone, $email, $identifier, $password)
    {
        if ($phone) {
            nias_get_or_make_user($phone);
        } elseif ($email) {
            $username = explode('@', $email)[0];
            $user_data = [
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'display_name' => $username,
                'role'         => get_option('nias_default_user_role', get_option('default_role')),
            ];
            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id) && strpos($user_id->get_error_message(), 'exists') !== false) {
                $user_data['user_login'] = $username . '_' . wp_rand(100, 999);
                wp_insert_user($user_data);
            }
        }
    }

    /**
     * ارسال پاسخ موفقیت‌آمیز
     */
    private function send_success_response($user_analysis, $digit, $expire, $identifier, $send_results, $password_sent = false)
    {
        $parts = [];
        if ($user_analysis['send_to_phone'] && $user_analysis['final_phone']) {
            $parts[] = 'شماره ' . $user_analysis['final_phone'];
        }
        if ($user_analysis['send_to_email'] && $user_analysis['final_email']) {
            $parts[] = 'ایمیل ' . $user_analysis['final_email'];
        }

        $dest = implode(' و ', $parts);

        if ($password_sent) {
            $message = 'حساب کاربری شما ایجاد شد و رمز عبور به ' . $dest . ' ارسال شد. لطفاً با رمز عبور دریافتی وارد شوید';
        } elseif (get_option('nias_bale_activate')) {
            $message = 'کد ' . $digit . ' رقمی که از طریق پیامک و پیامرسان بله به ' . $dest . ' ارسال شده است را وارد کنید';
        } else {
            $message = 'کد ' . $digit . ' رقمی ارسال شده به ' . $dest . ' را وارد کنید';
        }

        wp_send_json_success([
            'message'      => $message,
            'duration'     => $expire,
            'identifier'   => $identifier,
            'user_status'  => $user_analysis['user_status'],
            'is_new_user'  => $user_analysis['is_new_user'],
            'password_sent'=> $password_sent,
            'send_results' => $send_results,
            'phone_sent'   => $user_analysis['send_to_phone'],
            'email_sent'   => $user_analysis['send_to_email'],
            '_wpnonce'     => wp_create_nonce('verify' . $identifier),
        ], 200);
    }

    // ─── Verify (Step 2) ─────────────────────────────────────────────────────────

    /**
     * پردازش مرحله دوم: تایید کد OTP یا ورود با رمز عبور
     */
    private function process_sms_verify()
    {
        $this->result['message'] = 'خطایی رخ داده است';

        $identifier             = isset($_REQUEST['identifier'])             ? sanitize_text_field($_REQUEST['identifier']) : null;
        $code                   = isset($_REQUEST['code'])                   ? sanitize_text_field($_REQUEST['code'])       : null;
        $password               = isset($_REQUEST['password'])               ? sanitize_text_field($_REQUEST['password'])   : null;
        $login_with_password    = isset($_REQUEST['login_with_password'])    && $_REQUEST['login_with_password'] == 1;
        $register_with_password = isset($_REQUEST['register_with_password']) && $_REQUEST['register_with_password'] == 1;

        // ─── حالت ثبت رمز عبور دستی توسط کاربر جدید ───────────────────────────────
        if ($register_with_password) {
            $this->process_manual_password_registration($identifier, $password);
            return;
        }

        // ─── حالت ورود با رمز عبور ───────────────────────────────────────────────
        if ($login_with_password) {
            if (!$identifier || !$password) {
                $this->result['message'] = 'اطلاعات ناقص ارسال شده یا jquery لود نشده به مدیر سایت اطلاع دهید';
                wp_send_json_error($this->result, 401);
                return;
            }

            $user = $this->find_user_by_identifier($identifier);

            if (!$user) {
                $this->result['message'] = 'کاربری با این مشخصات یافت نشد';
                wp_send_json_error($this->result, 401);
                return;
            }

            if (!wp_check_password($password, $user->user_pass, $user->ID)) {
                $this->result['message'] = 'رمز عبور اشتباه است';
                wp_send_json_error($this->result, 401);
                return;
            }

            // Skip admin block when WP login is fully replaced (no fallback WP form)
            if (get_option('nias_block_admin_phone_login', 0)
                && !get_option('nias_replace_wp_login', 0)
                && in_array('administrator', (array) $user->roles)) {
                $this->result['message'] = 'مدیران سیستم مجاز به ورود از این صفحه نیستند.';
                wp_send_json_error($this->result, 403);
                return;
            }

            $this->login_user($user);

            $referer = wp_get_referer();
            if ( $referer && false !== strpos( $referer, 'wp-login.php' ) ) {
                $referer = '';
            }
            $redirect_url = get_option('nias_login_redirect') ?: $referer ?: home_url();

            wp_send_json_success([
                'message'      => 'ورود با موفقیت انجام شد',
                'user_status'  => 'login',
                'user_id'      => $user->ID,
                'redirect_url' => $redirect_url,
            ], 200);
            return;
        }

        // ─── حالت ورود با کد OTP ─────────────────────────────────────────────────
        if (!$identifier || !$code) {
            $this->result['message'] = 'اطلاعات ناقص ارسال شده';
            wp_send_json_error($this->result, 401);
            return;
        }

        $verify = $this->get_verification_code($identifier);

        if (!$verify) {
            $this->result['message'] = 'درخواست احراز شما یافت نشد';
            wp_send_json_error($this->result, 401);
            return;
        }

        if (current_time('timestamp') >= strtotime($verify->expired_at)) {
            $this->result['message'] = 'کد وارد شده منقضی شده است مجدداً تلاش کنید';
            wp_send_json_error($this->result, 401);
            return;
        }

        if ($verify->attempts >= 3) {
            $this->result['message'] = 'تعداد تلاش‌های شما بیش از حد مجاز است 1 دقیقه صبر کنید';
            wp_send_json_error($this->result, 401);
            return;
        }

        if (!nias_compare_otp_codes($verify->code, $code)) {
            $this->increment_attempts($verify);
            $this->result['message'] = 'کد وارد شده اشتباه است مجدداً تلاش کنید';
            wp_send_json_error($this->result, 401);
            return;
        }

        $this->reset_attempts($verify);

        $user_result = $this->handle_user_verification($verify, $password);

        if (is_wp_error($user_result)) {
            $this->result['message'] = $user_result->get_error_message();
            wp_send_json_error($this->result, 401);
            return;
        }

        $user   = $user_result['user'];
        $is_new = $user_result['is_new'];

        // گارد: مطمئن شو کاربر معتبر با ID>0 داریم تا کوکی احراز برای user_id=0 ست نشود
        if (!$user instanceof WP_User || empty($user->ID)) {
            error_log('Nias verify: resolved user is invalid before login. user=' . var_export($user, true));
            $this->result['message'] = 'خطا در شناسایی حساب کاربری، لطفاً مجدداً تلاش کنید';
            wp_send_json_error($this->result, 500);
            return;
        }

        // Skip admin block when WP login is fully replaced (no fallback WP form)
        if (get_option('nias_block_admin_phone_login', 0)
            && !get_option('nias_replace_wp_login', 0)
            && in_array('administrator', (array) $user->roles)) {
            $this->result['message'] = 'مدیران سیستم مجاز به ورود از این صفحه نیستند.';
            wp_send_json_error($this->result, 403);
            return;
        }

        $this->login_user($user);
        do_action('wp_login', $user->user_login, $user);

        $this->update_verification_status($verify, $user, $is_new);

        $redirect_option = get_option('nias_login_redirect');
        $referer         = wp_get_referer();
        if ( $referer && false !== strpos( $referer, 'wp-login.php' ) ) {
            $referer = '';
        }
        $redirect_url = empty($redirect_option) ? ( $referer ?: home_url() ) : esc_url($redirect_option);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        wp_send_json_success([
            'message'      => $is_new ? 'ثبت‌نام و ورود با موفقیت انجام شد' : 'ورود با موفقیت انجام شد',
            'user_status'  => $is_new ? 'registered' : 'login',
            'user_id'      => $user->ID,
            'redirect_url' => $redirect_url,
        ], 200);
    }

    /**
     * ثبت رمز عبور دستی توسط کاربر جدید (تب پسورد در مرحله دوم)
     * این مسیر فقط برای کاربران جدید است؛ هرگز رمز کاربر موجود را تغییر نمی‌دهد.
     */
    private function process_manual_password_registration($identifier, $password)
    {
        // قابلیت باید فعال بوده و یکی از حالت‌های پسورد روشن باشد
        $enabled = get_option('nias_manual_password_activate')
            && (get_option('nias_password_activate') || get_option('nias_password_otp_activate'));

        if (!$enabled) {
            $this->result['message'] = 'این قابلیت غیرفعال است';
            wp_send_json_error($this->result, 403);
            return;
        }

        if (!$identifier || !$password) {
            $this->result['message'] = 'اطلاعات ناقص ارسال شده';
            wp_send_json_error($this->result, 401);
            return;
        }

        $identifier = nias_normalize_digits($identifier);

        // سنجش حداقلی قدرت رمز عبور در سمت سرور
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->result['message'] = 'رمز عبور باید حداقل ۸ کاراکتر و شامل حروف و اعداد باشد';
            wp_send_json_error($this->result, 401);
            return;
        }

        // امنیت: اگر کاربری با این مشخصات از قبل وجود دارد، اجازه‌ی تغییر رمز نده
        if ($this->find_user_by_identifier($identifier)) {
            $this->result['message'] = 'این حساب از قبل وجود دارد؛ لطفاً با رمز عبور خود وارد شوید';
            wp_send_json_error($this->result, 409);
            return;
        }

        $user = $this->create_manual_password_user($identifier, $password);
        if (is_wp_error($user)) {
            $this->result['message'] = 'خطا در ایجاد حساب کاربری: ' . $user->get_error_message();
            wp_send_json_error($this->result, 500);
            return;
        }

        // مسدودسازی مدیر در صورت فعال بودن (مشابه سایر مسیرهای ورود)
        if (get_option('nias_block_admin_phone_login', 0)
            && !get_option('nias_replace_wp_login', 0)
            && in_array('administrator', (array) $user->roles)) {
            $this->result['message'] = 'مدیران سیستم مجاز به ورود از این صفحه نیستند.';
            wp_send_json_error($this->result, 403);
            return;
        }

        $this->login_user($user);
        do_action('wp_login', $user->user_login, $user);

        $referer = wp_get_referer();
        if ($referer && false !== strpos($referer, 'wp-login.php')) {
            $referer = '';
        }
        $redirect_url = get_option('nias_login_redirect') ?: $referer ?: home_url();

        wp_send_json_success([
            'message'      => 'حساب کاربری شما ایجاد شد و وارد شدید',
            'user_status'  => 'registered',
            'user_id'      => $user->ID,
            'redirect_url' => $redirect_url,
        ], 200);
    }

    /**
     * ایجاد کاربر جدید با رمز عبور انتخابی کاربر
     * @return WP_User|WP_Error
     */
    private function create_manual_password_user($identifier, $password)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $email    = sanitize_email($identifier);
            $username = explode('@', $email)[0];
            $user_data = [
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'display_name' => $username,
                'role'         => get_option('nias_default_user_role', get_option('default_role')),
            ];

            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id) && strpos($user_id->get_error_message(), 'exists') !== false) {
                $user_data['user_login'] = $username . '_' . wp_rand(100, 999);
                $user_id = wp_insert_user($user_data);
            }
            if (is_wp_error($user_id)) {
                return $user_id;
            }

            $user = get_user_by('ID', $user_id);
            update_user_meta($user->ID, 'nickname', $username);
            return $user;
        }

        // شماره موبایل
        $phone = nias_sanitize_phone_enhanced($identifier);
        if (!$phone) {
            return new WP_Error('invalid_phone', 'شماره موبایل نامعتبر است');
        }

        $user = nias_get_or_make_user($phone);
        if (is_wp_error($user)) {
            return $user;
        }

        wp_update_user(['ID' => $user->ID, 'user_pass' => $password]);
        return get_user_by('ID', $user->ID);
    }

    /**
     * جستجوی کاربر بر اساس ایمیل، شماره موبایل یا نام کاربری
     */
    private function find_user_by_identifier($identifier)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return get_user_by('email', $identifier);
        }

        $validated_phone = nias_validate_phone_number($identifier);
        if ($validated_phone) {
            $user = nias_get_user_by_phone($validated_phone);
            if ($user) return $user;
        }

        // fallback به username
        return get_user_by('login', $identifier);
    }

    /**
     * ست کردن کوکی احراز هویت با مدت یک سال
     */
    private function login_user($user)
    {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);

        add_filter('auth_cookie_expiration', function ($exp, $uid, $remember) {
            return $remember ? 365 * DAY_IN_SECONDS : $exp;
        }, 10, 3);

        wp_set_auth_cookie($user->ID, true, is_ssl());
        remove_all_filters('auth_cookie_expiration');

        wp_cache_delete($user->ID, 'users');
        wp_cache_delete($user->user_login, 'userlogins');

        if (wp_using_ext_object_cache()) {
            wp_cache_flush();
        }
    }


    // ─── User Registration / Lookup ──────────────────────────────────────────────

    /**
     * مدیریت تایید کاربر: جستجو یا ایجاد حساب
     */
    private function handle_user_verification($verify, $password = null)
    {
        $is_new_user = $verify->user_status === 'new';

        if ($is_new_user) {
            return $this->register_user($verify, $password);
        }

        // کاربر موجود → جستجو
        $user = null;
        if ($verify->email) {
            $user = get_user_by('email', $verify->email);
        } elseif ($verify->phone) {
            $user = nias_get_user_by_phone($verify->phone);
        }

        // اگر پیدا نشد → ایجاد
        if (!$user) {
            return $this->register_user($verify, $password);
        }

        return ['user' => $user, 'is_new' => false];
    }

    /**
     * ایجاد حساب کاربری جدید بر اساس اطلاعات verify
     */
    private function register_user($verify, $password = null)
    {
        if ($verify->email && !$verify->phone) {
            $display_name = explode('@', $verify->email)[0];
            $user_data    = [
                'user_email'   => $verify->email,
                'user_login'   => $verify->email,
                'display_name' => $display_name,
                'role'         => get_option('nias_default_user_role', get_option('default_role')),
            ];
            if ($password) {
                $user_data['user_pass'] = $password;
            }

            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id)) {
                // ثبت علت واقعی خطای ساخت کاربر برای عیب‌یابی (مثلاً ایمیل تکراری)
                error_log('Nias register_user (email) failed: ' . $user_id->get_error_code() . ' - ' . $user_id->get_error_message());
                return $user_id;
            }
            $user = get_user_by('ID', $user_id);
            if (!$user instanceof WP_User || empty($user->ID)) {
                error_log('Nias register_user (email) could not load user after insert. user_id=' . var_export($user_id, true));
                return new WP_Error('user_lookup_failed', 'خطا در بازیابی حساب کاربری ساخته‌شده');
            }
            update_user_meta($user->ID, 'nickname', $display_name);

        } elseif ($verify->phone && !$verify->email) {
            $user = nias_get_or_make_user($verify->phone);
            if (is_wp_error($user)) {
                error_log('Nias register_user (phone) failed: ' . $user->get_error_code() . ' - ' . $user->get_error_message());
                return $user;
            }
            if (!$user instanceof WP_User || empty($user->ID)) {
                error_log('Nias register_user (phone) returned invalid user for phone=' . $verify->phone);
                return new WP_Error('user_lookup_failed', 'خطا در ساخت حساب کاربری با شماره موبایل');
            }
            $display_name = $user->display_name ?: $verify->phone;
            wp_update_user(['ID' => $user->ID, 'display_name' => $display_name]);
            update_user_meta($user->ID, 'nickname', $display_name);

        } else {
            return new WP_Error('invalid_registration', 'اطلاعات ثبت‌نام نامعتبر است');
        }

        return ['user' => $user, 'is_new' => true];
    }

    // ─── Security / IP ───────────────────────────────────────────────────────────

    private function is_ip_blocked($ip)
    {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT ip FROM {$wpdb->prefix}nias_blockedip_sms WHERE ip = %s",
            $ip
        ));
    }

    private function should_block_ip($ip)
    {
        $mode = get_option('nias_security_mode', 'block');
        return $mode === 'cooldown'
            ? $this->check_progressive_cooldown($ip)
            : $this->check_block_mode($ip);
    }

    private function check_block_mode($ip)
    {
        global $wpdb;
        $table       = $wpdb->prefix . 'nias_sms_login';
        $time_window = get_option('nias_block_time_window', 60);
        $min_attempts = get_option('nias_block_min_attempts', 3);

        $dates = $wpdb->get_col($wpdb->prepare(
            "SELECT expired_at FROM $table WHERE ip = %s ORDER BY expired_at DESC LIMIT %d",
            $ip, $min_attempts
        ));

        if (count($dates) < $min_attempts) return false;

        $current_time        = current_time('timestamp');
        $oldest_expired_time = strtotime($dates[$min_attempts - 1]);

        if ($current_time - $oldest_expired_time > $time_window) return false;

        $nias_login_expire = get_option('nias_login_expire', 60);
        return strtotime($dates[0]) - $oldest_expired_time < $nias_login_expire;
    }

    /**
     * آمار شکست‌های واقعی یک IP در ۲۴ ساعت اخیر
     *
     * شکست واقعی: رکورد تاییدنشده‌ای که یا کد اشتباه برایش وارد شده (attempts > 0)
     * یا کدش بدون تایید منقضی شده. رکورد در انتظار (کد معتبر و بدون تلاش اشتباه)
     * شکست محسوب نمی‌شود تا خودِ درخواست کد باعث مسدودیت نشود.
     * شکست‌های قبل از آخرین ورود موفق همین IP نیز بخشیده می‌شوند.
     *
     * @return array{count:int, last_failure:?string}
     */
    private function get_failure_stats($ip)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'nias_sms_login';
        $now   = current_time('mysql');

        $since = date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS);

        $last_success = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(verified_at) FROM $table WHERE ip = %s AND verified = 1",
            $ip
        ));
        if ($last_success && $last_success > $since) {
            $since = $last_success;
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) AS cnt,
                    MAX(CASE WHEN attempts > 0 THEN COALESCE(updated_at, created_at) ELSE expired_at END) AS last_failure
             FROM $table
             WHERE ip = %s
               AND verified = 0
               AND created_at > %s
               AND (attempts > 0 OR expired_at <= %s)",
            $ip, $since, $now
        ));

        return [
            'count'        => (int) ($row->cnt ?? 0),
            'last_failure' => $row->last_failure ?? null,
        ];
    }

    private function check_progressive_cooldown($ip)
    {
        $stats = $this->get_failure_stats($ip);
        return $stats['count'] >= (int) get_option('nias_cooldown_max_attempts', 10);
    }

    private function get_cooldown_status($ip)
    {
        $stats  = $this->get_failure_stats($ip);
        $failed = $stats['count'];

        if (!$failed || !$stats['last_failure']) return ['blocked' => false, 'message' => ''];

        $time_passed = current_time('timestamp') - strtotime($stats['last_failure']);

        $levels = [
            [(int) get_option('nias_cooldown_level4_attempts', 8), (int) get_option('nias_cooldown_level4_duration', 1800), 'سطح 4'],
            [(int) get_option('nias_cooldown_level3_attempts', 6), (int) get_option('nias_cooldown_level3_duration', 600),  'سطح 3'],
            [(int) get_option('nias_cooldown_level2_attempts', 4), (int) get_option('nias_cooldown_level2_duration', 120),  'سطح 2'],
            [(int) get_option('nias_cooldown_level1_attempts', 2), (int) get_option('nias_cooldown_level1_duration', 30),   'سطح 1'],
        ];

        foreach ($levels as [$min, $duration, $label]) {
            if ($failed >= $min && $time_passed < $duration) {
                $remaining = $duration - $time_passed;
                return [
                    'blocked'           => true,
                    'message'           => sprintf('تعداد تلاش‌های شما زیاد است (%s). لطفاً %d دقیقه صبر کنید', $label, ceil($remaining / 60)),
                    'remaining_seconds' => $remaining,
                    'level'             => $label,
                ];
            }
        }

        return ['blocked' => false, 'message' => ''];
    }

    private function block_ip($ip)
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'nias_blockedip_sms',
            ['ip' => $ip, 'block' => 'به دلیل تلاش بیش از حد بلاک شد', 'time' => current_time('mysql')]
        );
    }



    // ─── Forgot Password ─────────────────────────────────────────────────────────

    /**
     * بازیابی رمز عبور: ارسال رمز جدید به کاربر موجود یا ثبت‌نام کاربر جدید
     */
    private function process_forgot_password()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'درخواست نامعتبر'], 405);
            return;
        }

        // دریافت identifier از فیلدهای مختلف
        $input = '';
        foreach (['identifier', 'email', 'ns_email_phone'] as $field) {
            if (!empty($_REQUEST[$field])) {
                $input = sanitize_text_field($_REQUEST[$field]);
                break;
            }
        }

        if (!$input) {
            wp_send_json_error(['message' => 'ایمیل یا شماره تلفن الزامی است'], 400);
            return;
        }

        $ip_address = $_SERVER['REMOTE_ADDR'];

        if ($this->is_ip_blocked($ip_address)) {
            wp_send_json_error(['message' => 'شما قبلاً محدود شدید و مجاز به استفاده از این سرویس نیستید'], 403);
            return;
        }

        $user            = null;
        $email           = null;
        $validated_phone = null;
        $is_new_user     = false;

        if (is_email($input)) {
            $email = sanitize_email($input);
            $user  = get_user_by('email', $email);
        } else {
            $validated_phone = nias_validate_phone_number($input);
            if (!$validated_phone) {
                wp_send_json_error(['message' => 'فرمت شماره موبایل یا ایمیل نامعتبر است'], 400);
                return;
            }
            $user = nias_get_user_by_phone($validated_phone);
            if ($user) {
                $user_email = $user->user_email;
                $email = ($user_email && strpos($user_email, '@') !== false) ? $user_email : null;
            }
        }

        $new_password = $this->generate_random_password();

        if (!$user) {
            // کاربر جدید → ثبت‌نام
            $is_new_user = true;
            [$user, $email] = $this->create_user_for_forgot_password($input, $email, $validated_phone, $new_password);

            if (is_wp_error($user)) {
                wp_send_json_error(['message' => 'خطا در ایجاد حساب کاربری: ' . $user->get_error_message()], 500);
                return;
            }
        } else {
            // کاربر موجود → به‌روزرسانی رمز
            $update = wp_update_user(['ID' => $user->ID, 'user_pass' => $new_password]);
            if (is_wp_error($update)) {
                wp_send_json_error(['message' => 'خطا در به‌روزرسانی رمز عبور'], 500);
                return;
            }
        }

        $email_sent = false;
        $sms_sent   = false;

        if ($email && strpos($email, '@') !== false) {
            $email_sent = $this->send_new_password_email($user, $new_password, $is_new_user);
        }

        if ($validated_phone) {
            $sms_sent = $this->send_password_sms($validated_phone, $new_password);
        }

        if (!$email_sent && !$sms_sent) {
            wp_send_json_error([
                'message' => $is_new_user ? 'خطا در ارسال اطلاعات حساب جدید' : 'خطا در ارسال رمز عبور جدید',
            ], 500);
            return;
        }

        $methods = [];
        if ($email_sent)  $methods[] = 'ایمیل';
        if ($sms_sent)    $methods[] = 'پیامک';

        $prefix  = $is_new_user ? 'حساب کاربری جدید برای شما ایجاد شد و رمز عبور ' : 'رمز عبور جدید برای شما ';
        $message = $prefix . 'از طریق ' . implode(' و ', $methods) . ' ارسال شد';

        wp_send_json_success([
            'message'     => $message,
            'email_sent'  => $email_sent,
            'sms_sent'    => $sms_sent,
            'is_new_user' => $is_new_user,
            'user_id'     => $user->ID,
        ], 200);
    }

    /**
     * ایجاد کاربر جدید در فرآیند فراموشی رمز
     * @return array [WP_User|WP_Error, string|null $email]
     */
    private function create_user_for_forgot_password($input, $email, $validated_phone, $new_password)
    {
        if (is_email($input)) {
            $username  = explode('@', $input)[0];
            $user_data = [
                'user_login'   => $username,
                'user_email'   => $input,
                'user_pass'    => $new_password,
                'display_name' => $username,
                'role'         => get_option('nias_default_user_role', get_option('default_role')),
            ];
            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id) && strpos($user_id->get_error_message(), 'exists') !== false) {
                $user_data['user_login'] = $username . '_' . wp_rand(100, 999);
                $user_id = wp_insert_user($user_data);
            }
            if (is_wp_error($user_id)) return [$user_id, null];
            return [get_user_by('ID', $user_id), $input];
        }

        // شماره موبایل
        $user = nias_get_or_make_user($validated_phone);
        if (is_wp_error($user)) return [$user, null];

        // به‌روزرسانی رمز
        wp_update_user(['ID' => $user->ID, 'user_pass' => $new_password]);

        $user_email = $user->user_email;
        $email = ($user_email && strpos($user_email, '@') !== false) ? $user_email : null;

        return [$user, $email];
    }

    /**
     * ارسال رمز عبور از طریق SMS با fallback
     */
    private function send_password_sms($phone, $password)
    {
        $response = $this->gateway->send_password($phone, $password);
        if ($response === true) return true;

        // fallback به send_code
        $fallback = $this->gateway->send_code($phone, $password);
        return $this->parse_sms_result($fallback)['success'];
    }

    // ─── Password Generation & Email ─────────────────────────────────────────────

    /**
     * تولید رمز عبور تصادفی امن بر اساس تنظیمات
     */
    private function generate_random_password($length = null)
    {
        $length          = max(6, (int) ($length ?: get_option('nias_password_length', 12)));
        $use_upper       = get_option('nias_password_include_uppercase', 1);
        $use_lower       = get_option('nias_password_include_lowercase', 1);
        $use_numbers     = get_option('nias_password_include_numbers', 1);
        $use_special     = get_option('nias_password_include_special', 1);
        $prefix          = (string) get_option('nias_password_prefix', '');

        $sets = [
            $use_upper   ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '',
            $use_lower   ? 'abcdefghijklmnopqrstuvwxyz' : '',
            $use_numbers ? '0123456789'                  : '',
            $use_special ? '!@#$%^&*'                    : '',
        ];
        $sets = array_filter($sets);

        // fallback اگر هیچ نوعی انتخاب نشده
        if (empty($sets)) {
            $sets = ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz', '0123456789', '!@#$%^&*'];
        }

        $all_chars = implode('', $sets);
        $password  = '';

        // حداقل یک کاراکتر از هر مجموعه
        foreach ($sets as $set) {
            $password .= $set[random_int(0, strlen($set) - 1)];
        }

        // تکمیل تا طول مورد نظر
        $remaining = $length - strlen($prefix) - strlen($password);
        for ($i = 0; $i < max(0, $remaining); $i++) {
            $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
        }

        return $prefix . str_shuffle($password);
    }

    /**
     * ارسال ایمیل حاوی رمز عبور جدید از طریق Nias_Email
     */
    private function send_new_password_email($user, $new_password, $is_new_user = false)
    {
        $site_name = get_bloginfo('name');
        $site_url  = home_url();

        $default_password_template = function_exists('nias_default_email_password_template')
            ? nias_default_email_password_template()
            : '<p>نام کاربری: {username}</p><p>رمز عبور: {password}</p><p><a href="{login_url}">ورود</a></p>';

        if ($is_new_user) {
            $subject  = 'خوش آمدید - حساب کاربری شما ایجاد شد - ' . $site_name;
            $template = get_option('nias_email_password_message_template', $default_password_template);
            $vars = [
                '{username}'     => $user->user_login,
                '{password}'     => $new_password,
                '{display_name}' => $user->display_name,
                '{site_name}'    => $site_name,
                '{site_url}'     => $site_url,
                '{login_url}'    => wp_login_url(),
            ];
        } else {
            $subject  = 'بازیابی رمز عبور - ' . $site_name;
            $template = get_option('nias_email_password_message_template', $default_password_template);
            $vars = [
                '{display_name}' => $user->display_name,
                '{password}'     => $new_password,
                '{site_name}'    => $site_name,
                '{site_url}'     => $site_url,
                '{login_url}'    => wp_login_url(),
            ];
        }

        $rendered = strtr($template, $vars);

        // قالب‌های کامل HTML (جدول‌محور) نباید با wpautop/the_content دستکاری شوند؛
        // فقط قالب‌های ساده متنی به‌صورت پاراگراف‌بندی پردازش می‌شوند.
        $is_full_html = (stripos($rendered, '<table') !== false)
            || (stripos($rendered, '<html') !== false)
            || (stripos($rendered, '<div') !== false);

        $message = $is_full_html ? $rendered : wpautop(apply_filters('the_content', $rendered));

        $result = $this->email_gateway->send_email_gateway(
            $user->user_email,
            $user->user_email,
            $new_password,
            $subject,
            $message
        );

        return $this->parse_email_result($result)['success'];
    }

    // ─── Response Parsers ────────────────────────────────────────────────────────

    /** پردازش نتیجه ارسال SMS */
    private function parse_sms_result($response)
    {
        return [
            'success' => $response === true,
            'message' => $response === true ? 'موفق' : (is_string($response) ? $response : 'ناموفق'),
        ];
    }

    /** پردازش نتیجه ارسال ایمیل */
    private function parse_email_result($response)
    {
        if ($response === true) {
            return ['success' => true, 'message' => 'موفق'];
        }

        if (is_string($response)) {
            // ابتدا کلیدواژه‌های شکست بررسی می‌شوند چون «ناموفق» شامل «موفق» است
            // و در غیر این صورت پیام خطا به‌اشتباه موفق تشخیص داده می‌شود.
            $failure_keywords = ['ناموفق', 'انجام نشد', 'ارسال نشد', 'خطا', 'failed', 'error', 'not supported'];
            foreach ($failure_keywords as $kw) {
                if (mb_strpos($response, $kw) !== false) {
                    return ['success' => false, 'message' => $response];
                }
            }

            $success_keywords = ['موفقیت', 'موفق', 'ارسال شد', 'sent successfully', 'success'];
            foreach ($success_keywords as $kw) {
                if (mb_strpos($response, $kw) !== false) {
                    return ['success' => true, 'message' => 'موفق'];
                }
            }
            return ['success' => false, 'message' => $response];
        }

        return ['success' => false, 'message' => 'ناموفق'];
    }
}
