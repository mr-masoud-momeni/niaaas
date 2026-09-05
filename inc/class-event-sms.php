<?php
defined('ABSPATH') || exit;

/**
 * پیامک‌های رویدادی نیاس
 *
 * سه نوع پیامک مستقل از وضعیت سفارش ووکامرس:
 *   1) خوش‌آمدگویی  → هنگام ثبت‌نام کاربر جدید، به شماره خودش
 *   2) اطلاع ورود   → هنگام ورود کاربر، به شماره(های) مدیر
 *   3) پس از نظر    → پس از ثبت نظر توسط کاربر، به شماره خودش
 *
 * هر پیامک می‌تواند با متن آزاد یا پترن همان درگاه تنظیم‌شده ارسال شود
 * (مشابه پیامک وضعیت‌های ووکامرس).
 */
class Nias_Event_SMS
{
    const OPTION = 'nias_event_sms_settings';

    /** @var Nias_Event_SMS */
    private static $instance = null;

    /** @var Nias_SMS_Gateway|null */
    private $gateway = null;

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

        // خوش‌آمدگویی — هنگام افزوده‌شدن متای phone (که دقیقاً پس از ساخت کاربر رخ می‌دهد)
        add_action('added_user_meta', [$this, 'on_phone_meta_added'], 20, 4);
        // اطلاع ورود به مدیر
        add_action('wp_login', [$this, 'on_user_login'], 20, 2);
        // پس از ثبت نظر کاربر
        add_action('comment_post', [$this, 'on_comment_post'], 20, 3);
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
            $existing = get_option(self::OPTION, []);
            return is_array($existing) ? $existing : [];
        }

        $clean = [];
        foreach (['welcome', 'admin_login', 'comment'] as $event) {
            $cfg = isset($input[$event]) && is_array($input[$event]) ? $input[$event] : [];
            $clean[$event] = [
                'enabled'      => empty($cfg['enabled']) ? 0 : 1,
                'text'         => sanitize_textarea_field($cfg['text'] ?? ''),
                'pattern'      => sanitize_text_field($cfg['pattern'] ?? ''),
                'pattern_vars' => sanitize_text_field($cfg['pattern_vars'] ?? ''),
            ];
            if ($event === 'admin_login') {
                $clean[$event]['admin_phones'] = sanitize_text_field($cfg['admin_phones'] ?? '');
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

        $defaults = [
            'welcome' => [
                'enabled'      => 0,
                'text'         => self::default_text('welcome'),
                'pattern'      => '',
                'pattern_vars' => '',
            ],
            'admin_login' => [
                'enabled'      => 0,
                'admin_phones' => '',
                'text'         => self::default_text('admin_login'),
                'pattern'      => '',
                'pattern_vars' => '',
            ],
            'comment' => [
                'enabled'      => 0,
                'text'         => self::default_text('comment'),
                'pattern'      => '',
                'pattern_vars' => '',
            ],
        ];

        $out = [];
        foreach ($defaults as $event => $def) {
            $out[$event] = wp_parse_args(is_array($saved[$event] ?? null) ? $saved[$event] : [], $def);
        }
        return $out;
    }

    public static function default_text(string $event): string
    {
        switch ($event) {
            case 'welcome':
                return '{display_name} عزیز، به {site_name} خوش آمدید! حساب کاربری شما با موفقیت ساخته شد.';
            case 'admin_login':
                return 'ورود کاربر به {site_name}: {display_name} ({user_phone}) در ساعت {login_time} مورخ {login_date}';
            case 'comment':
                return '{display_name} عزیز، نظر شما برای «{post_title}» ثبت شد و پس از تایید نمایش داده می‌شود. {site_name}';
        }
        return '';
    }

    /** متغیرهای قابل استفاده در هر رویداد: placeholder => توضیح */
    public static function variables(string $event): array
    {
        $common = [
            '{site_name}' => 'نام سایت',
            '{site_url}'  => 'آدرس سایت',
        ];
        switch ($event) {
            case 'welcome':
                return ['{display_name}' => 'نام کاربر', '{username}' => 'نام کاربری', '{user_phone}' => 'موبایل کاربر', '{user_email}' => 'ایمیل کاربر'] + $common;
            case 'admin_login':
                return ['{display_name}' => 'نام کاربر', '{username}' => 'نام کاربری', '{user_phone}' => 'موبایل کاربر', '{user_email}' => 'ایمیل کاربر', '{login_time}' => 'ساعت ورود', '{login_date}' => 'تاریخ ورود'] + $common;
            case 'comment':
                return ['{display_name}' => 'نام کاربر', '{username}' => 'نام کاربری', '{user_phone}' => 'موبایل کاربر', '{post_title}' => 'عنوان نوشته/محصول', '{post_url}' => 'لینک نوشته', '{comment_excerpt}' => 'خلاصه نظر'] + $common;
        }
        return $common;
    }

    // ── رویدادها ────────────────────────────────────────────────────────────

    /** خوش‌آمدگویی: هنگام افزوده‌شدن متای phone برای یک کاربر جدید */
    public function on_phone_meta_added($meta_id, $user_id, $meta_key, $meta_value)
    {
        if ($meta_key !== 'phone') {
            return;
        }

        $settings = self::get_settings();
        if (empty($settings['welcome']['enabled'])) {
            return;
        }

        // فقط یک‌بار برای هر کاربر
        if (get_user_meta($user_id, '_nias_welcome_sms_sent', true)) {
            return;
        }

        $phone = $this->normalize_phone((string) $meta_value);
        if (!$phone) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        update_user_meta($user_id, '_nias_welcome_sms_sent', current_time('mysql'));

        $vars = [
            '{display_name}' => $user->display_name ?: $phone,
            '{username}'     => $user->user_login,
            '{user_phone}'   => $phone,
            '{user_email}'   => $user->user_email,
            '{site_name}'    => get_bloginfo('name'),
            '{site_url}'     => home_url('/'),
        ];

        $this->dispatch($phone, $settings['welcome'], $vars);
    }

    /** اطلاع ورود به مدیر */
    public function on_user_login($user_login, $user = null)
    {
        $settings = self::get_settings();
        if (empty($settings['admin_login']['enabled'])) {
            return;
        }

        if (!$user instanceof WP_User) {
            $user = get_user_by('login', $user_login);
        }
        if (!$user) {
            return;
        }

        $phones = $this->parse_phones($settings['admin_login']['admin_phones'] ?? '');
        if (empty($phones)) {
            return;
        }

        $timestamp = current_time('timestamp');
        $vars = [
            '{display_name}' => $user->display_name ?: $user->user_login,
            '{username}'     => $user->user_login,
            '{user_phone}'   => $this->get_user_phone($user->ID) ?: '—',
            '{user_email}'   => $user->user_email,
            '{login_time}'   => date_i18n('H:i', $timestamp),
            '{login_date}'   => date_i18n('Y/m/d', $timestamp),
            '{site_name}'    => get_bloginfo('name'),
            '{site_url}'     => home_url('/'),
        ];

        foreach ($phones as $phone) {
            $this->dispatch($phone, $settings['admin_login'], $vars);
        }
    }

    /** پس از ثبت نظر توسط کاربر */
    public function on_comment_post($comment_id, $comment_approved, $commentdata = [])
    {
        $settings = self::get_settings();
        if (empty($settings['comment']['enabled'])) {
            return;
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }

        // فقط نظرهای واقعی (نه pingback/trackback)
        $type = (string) $comment->comment_type;
        if (!in_array($type, ['', 'comment', 'review'], true)) {
            return;
        }

        $user_id = (int) $comment->user_id;
        if ($user_id <= 0) {
            return; // فقط کاربران ثبت‌نام‌شده شماره دارند
        }

        $phone = $this->get_user_phone($user_id);
        if (!$phone) {
            return;
        }

        $user      = get_userdata($user_id);
        $post_id   = (int) $comment->comment_post_ID;
        $excerpt   = wp_trim_words((string) $comment->comment_content, 12, '...');

        $vars = [
            '{display_name}'    => $user ? ($user->display_name ?: $user->user_login) : $comment->comment_author,
            '{username}'        => $user ? $user->user_login : '',
            '{user_phone}'      => $phone,
            '{post_title}'      => get_the_title($post_id),
            '{post_url}'        => get_permalink($post_id),
            '{comment_excerpt}' => $excerpt,
            '{site_name}'       => get_bloginfo('name'),
            '{site_url}'        => home_url('/'),
        ];

        $this->dispatch($phone, $settings['comment'], $vars);
    }

    // ── ارسال ────────────────────────────────────────────────────────────────

    /**
     * ارسال پیامک یک رویداد؛ اگر «کد پترن» تنظیم شده باشد از پترن استفاده می‌شود،
     * در غیر این صورت متن آزاد.
     */
    private function dispatch($phone, array $cfg, array $vars)
    {
        $phone = $this->normalize_phone((string) $phone);
        if (!$phone) {
            return;
        }

        $gateway = $this->get_gateway();
        $pattern = trim((string) ($cfg['pattern'] ?? ''));

        if ($pattern !== '') {
            $params = $this->build_pattern_params((string) ($cfg['pattern_vars'] ?? ''), $vars);
            $gateway->send($phone, $pattern, $params);
        } else {
            $text = trim(strtr((string) ($cfg['text'] ?? ''), $vars));
            if ($text === '') {
                return;
            }
            $gateway->send_text($phone, $text);
        }
    }

    /**
     * ساخت پارامترهای پترن از رشته‌ی «متغیرهای پترن».
     * توکن «نام:{متغیر}» پارامتر نام‌دار (SMS.ir) و «{متغیر}» پارامتر ترتیبی می‌سازد.
     */
    private function build_pattern_params(string $spec, array $vars): array
    {
        $params = [];
        $tokens = preg_split('/[,،]+/u', $spec, -1, PREG_SPLIT_NO_EMPTY);

        foreach ((array) $tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            if (strpos($token, ':') !== false) {
                [$name, $value_tpl] = array_map('trim', explode(':', $token, 2));
                if ($name === '') {
                    continue;
                }
                $params[$name] = strtr($value_tpl, $vars);
            } else {
                $params[] = strtr($token, $vars);
            }
        }

        return $params;
    }

    private function get_gateway(): Nias_SMS_Gateway
    {
        if ($this->gateway === null) {
            if (!class_exists('Nias_SMS_Gateway')) {
                require_once NIAS_LOGIN_INC . 'class-sms-gateway.php';
            }
            $this->gateway = new Nias_SMS_Gateway();
        }
        return $this->gateway;
    }

    // ── کمکی‌ها ──────────────────────────────────────────────────────────────

    private function normalize_phone(string $phone): string
    {
        $phone = function_exists('nias_normalize_digits') ? nias_normalize_digits($phone) : $phone;
        if (function_exists('nias_sanitize_phone_enhanced')) {
            $normalized = nias_sanitize_phone_enhanced($phone);
            if ($normalized) {
                return $normalized;
            }
        }
        return trim($phone);
    }

    private function get_user_phone(int $user_id): string
    {
        $phone = (string) (get_user_meta($user_id, 'phone', true) ?: get_user_meta($user_id, 'billing_phone', true));
        return $phone ? $this->normalize_phone($phone) : '';
    }

    /** @return string[] */
    private function parse_phones(string $raw): array
    {
        $parts  = preg_split('/[\s,،;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $phones = [];
        foreach ((array) $parts as $part) {
            $normalized = $this->normalize_phone($part);
            if ($normalized) {
                $phones[] = $normalized;
            }
        }
        return array_values(array_unique($phones));
    }
}

Nias_Event_SMS::get_instance();
