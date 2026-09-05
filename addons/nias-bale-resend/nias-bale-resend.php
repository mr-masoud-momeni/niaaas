<?php
/**
 * Plugin Name: Nias Login - Bale OTP Resend
 * Description: افزودن امکان ارسال مجدد کد تایید از طریق پیام‌رسان بله، بدون تغییر در فایل‌های Nias Login.
 * Version: 1.0.0
 * Author: Masoud Momeni
 */

defined('ABSPATH') || exit;

define('NIAS_BALE_RESEND_VERSION', '1.0.0');
define('NIAS_BALE_RESEND_URL', plugin_dir_url(__FILE__));

action_add_action();

function action_add_action() {
    add_action('wp_enqueue_scripts', 'nias_bale_resend_enqueue_assets', 20);
    add_action('wp_ajax_nias_bale_resend', 'nias_bale_resend_ajax');
    add_action('wp_ajax_nopriv_nias_bale_resend', 'nias_bale_resend_ajax');
}

function nias_bale_resend_enqueue_assets() {
    if (is_admin()) {
        return;
    }

    // این افزونه هیچ تنظیماتی ندارد؛ فقط وقتی بله در Nias تنظیم شده، اسکریپت را لود می‌کنیم.
    if (!get_option('nias_bale_activate', 0)) {
        return;
    }

    if (!get_option('nias_bale_api', '') || !get_option('nias_bale_botid', '')) {
        return;
    }

    wp_enqueue_script(
        'nias-bale-resend',
        NIAS_BALE_RESEND_URL . 'assets/js/bale-resend.js',
        [],
        NIAS_BALE_RESEND_VERSION,
        true
    );

    wp_localize_script('nias-bale-resend', 'niasBaleResend', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('nias_bale_resend'),
        'label'   => 'ارسال مجدد کد با پیام‌رسان بله',
        'sending' => 'در حال ارسال کد با بله...',
    ]);
}

function nias_bale_resend_normalize_digits($value) {
    $value = (string) $value;
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function nias_bale_resend_generate_code($length) {
    $length = max(1, min(10, absint($length)));
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= (string) wp_rand(0, 9);
    }

    return $code;
}

function nias_bale_resend_ajax() {
    if (!check_ajax_referer('nias_bale_resend', 'nonce', false)) {
        wp_send_json_error(['message' => 'درخواست نامعتبر است.'], 403);
    }

    if (!get_option('nias_bale_activate', 0)) {
        wp_send_json_error(['message' => 'ارسال کد با بله فعال نیست.'], 400);
    }

    if (!get_option('nias_bale_api', '') || !get_option('nias_bale_botid', '')) {
        wp_send_json_error(['message' => 'تنظیمات بله در Nias کامل نیست.'], 400);
    }

    $identifier = isset($_POST['identifier'])
        ? sanitize_text_field(wp_unslash($_POST['identifier']))
        : '';

    $identifier = nias_bale_resend_normalize_digits(trim($identifier));

    if ($identifier === '') {
        wp_send_json_error(['message' => 'شماره موبایل پیدا نشد.'], 400);
    }

    // بله فقط برای OTP شماره موبایل قابل استفاده است.
    if (!preg_match('/^09\d{9}$/', $identifier)) {
        wp_send_json_error(['message' => 'ارسال کد با بله فقط برای شماره موبایل امکان‌پذیر است.'], 400);
    }

    // یک درخواست در هر 60 ثانیه برای هر شماره/IP.
    $rate_key = 'nias_bale_resend_' . md5($identifier . '|' . nias_bale_resend_client_ip());
    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'لطفاً کمی بعد دوباره تلاش کنید.'], 429);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'nias_sms_login';

    $verify = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE phone = %s ORDER BY created_at DESC, ID DESC LIMIT 1",
            $identifier
        )
    );

    if (!$verify) {
        wp_send_json_error(['message' => 'اطلاعات ورود پیدا نشد. لطفاً دوباره شماره موبایل را وارد کنید.'], 404);
    }

    $now = current_time('timestamp');
    $expired_at_timestamp = strtotime((string) $verify->expired_at);

    // فقط وقتی تایمر قبلی تمام شده اجازه ارسال مجدد بده.
    if ($expired_at_timestamp && $expired_at_timestamp > $now) {
        $remaining = max(1, $expired_at_timestamp - $now);
        wp_send_json_error([
            'message'  => 'کد قبلی هنوز معتبر است.',
            'duration' => $remaining,
        ], 400);
    }

    if (!class_exists('Nias_SMS_Gateway')) {
        wp_send_json_error(['message' => 'Nias Login هنوز بارگذاری نشده است.'], 500);
    }

    $digits  = absint(get_option('nsdigitsquantity', 4));
    $expire  = max(1, absint(get_option('nias_login_expire', 120)));
    $code    = nias_bale_resend_generate_code($digits);
    $new_exp = date('Y-m-d H:i:s', $now + $expire);

    // از خود کلاس Nias استفاده می‌کنیم تا API و تنظیمات بله دوباره پیاده‌سازی نشود.
    try {
        $gateway = new Nias_SMS_Gateway();
        $response = $gateway->send_bale($identifier, $code);
    } catch (Throwable $e) {
        wp_send_json_error(['message' => 'خطا در ارسال کد با بله.'], 500);
    }

    if ($response !== true) {
        $message = is_string($response) && $response !== ''
            ? $response
            : 'ارسال کد با بله ناموفق بود.';

        wp_send_json_error(['message' => wp_strip_all_tags($message)], 400);
    }

    $updated = $wpdb->update(
        $table,
        [
            'code'       => $code,
            'expired_at' => $new_exp,
            'attempts'   => 0,
            'verified'   => 0,
        ],
        ['ID' => absint($verify->ID)],
        ['%s', '%s', '%d', '%d'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error(['message' => 'کد ارسال شد اما ذخیره کد جدید انجام نشد. لطفاً دوباره تلاش کنید.'], 500);
    }

    set_transient('nias_otp_sent_' . md5($identifier), 1, $expire);
    set_transient($rate_key, 1, 60);

    wp_send_json_success([
        'message'  => 'کد جدید با پیام‌رسان بله ارسال شد.',
        'duration' => $expire,
    ]);
}

function nias_bale_resend_client_ip() {
    // عمداً فقط REMOTE_ADDR استفاده می‌شود؛ هدرهای HTTP_* قابل جعل هستند.
    return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
}
