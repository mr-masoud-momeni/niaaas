<?php
defined('ABSPATH') || exit;

/**
 * آدرس و نسخه‌ی یک فایل دارایی افزونه را برمی‌گرداند.
 *
 * رفتارش با دو تاگل بخش «بهینه‌سازی» در تنظیم عملکرد کنترل می‌شود. هر دو پیش‌فرض
 * خاموش‌اند، چون روی بعضی سایت‌ها (کش سمت سرور، CDN یا افزونه‌های بهینه‌سازی)
 * باعث می‌شوند فایل قدیمی سرو شود و افزونه درست کار نکند.
 *
 *   nias_optimize_minify → اگر نسخه‌ی X.min.js/X.min.css موجود و تازه باشد، همان لود شود
 *   nias_optimize_cache  → نسخه به filemtime گره بخورد تا مرورگر واقعاً کش کند
 *
 * @param string $relative مسیر نسبت به ریشه افزونه، مثل 'assets/js/script.js'
 * @return array [url, version]
 */
function nias_login_asset($relative)
{
    $relative = ltrim($relative, '/');
    $path     = NIAS_LOGIN_PATH . $relative;
    $url      = NIAS_LOGIN_URL . $relative;

    $use_min = get_option('nias_optimize_minify')
        && !(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)
        && strpos($relative, '.min.') === false;

    if ($use_min) {
        $min_relative = preg_replace('/\.(js|css)$/i', '.min.$1', $relative);
        $min_path     = NIAS_LOGIN_PATH . $min_relative;

        // اگر نسخه‌ی فشرده از فایل اصلی قدیمی‌تر باشد یعنی بعد از آخرین ویرایش
        // دوباره ساخته نشده؛ سرو کردنش یعنی اجرای کد کهنه، پس نادیده گرفته می‌شود
        // و فایل اصلی لود می‌شود. این گارد، فراموش‌کردنِ ساخت مجدد را بی‌خطر می‌کند.
        if (
            $min_relative !== $relative
            && file_exists($min_path)
            && (!file_exists($path) || filemtime($min_path) >= filemtime($path))
        ) {
            $path = $min_path;
            $url  = NIAS_LOGIN_URL . $min_relative;
        }
    }

    // کش خاموش: نسخه در هر بار لود عوض می‌شود، پس مرورگر هرگز فایل قدیمی نمی‌دهد.
    // کش روشن: نسخه به زمان تغییر فایل گره می‌خورد — تا فایل عوض نشده کش می‌ماند و
    // لحظه‌ای که عوض شد آدرس عوض می‌شود و همه‌ی کاربران نسخه‌ی تازه را می‌گیرند.
    if (!get_option('nias_optimize_cache')) {
        return [$url, (string) time()];
    }

    return [$url, file_exists($path) ? (string) filemtime($path) : NIAS_LOGIN_VERSION];
}

add_action('wp_enqueue_scripts', 'nias_sms_login_public_scripts');
function nias_sms_login_public_scripts()
{
    if (is_user_logged_in()) {
        return;
    }

    // خالی → پیش‌فرض my-account؛ مقدار واردشده → همان مقدار (nias_login_trigger_value)
    $click_links   = nias_login_trigger_value('nias_login_click_links');
    $locked_pages  = nias_login_trigger_value('nias_login_locked_pages');
    $click_classes = get_option('nias_login_click_classes', '');
    $click_ids     = get_option('nias_login_click_ids', '');
    $ns_triggered  = !empty($_GET['ns-login']); // Redirected here by the auth blocker

    // Always enqueue when the auth blocker sent the user here
    if (!$ns_triggered && empty($click_links) && empty($locked_pages)) {
        return;
    }

    // Prepare data for localization
    $locked_pages_array    = $locked_pages ? array_map('trim', explode(',', $locked_pages)) : [];
    $click_links_array     = $click_links  ? array_map('trim', explode(',', $click_links))  : [];
    $click_classes_array   = $click_classes ? array_values(array_filter(array_map('trim', explode(',', $click_classes)))) : [];
    $click_ids_array       = $click_ids     ? array_values(array_filter(array_map('trim', explode(',', $click_ids))))     : [];
    $password_activate     = get_option('nias_password_activate', false);
    $password_otp_activate = get_option('nias_password_otp_activate', false);

    // Determine whether to enqueue
    $should_enqueue = $ns_triggered; // Always true when redirected by auth blocker

    if (!$should_enqueue && !empty($locked_pages)) {
        $current_url    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]" . strtok($_SERVER['REQUEST_URI'], '?');
        $is_locked_page = in_array($current_url, $locked_pages_array);

        if (empty($click_links)) {
            if ($is_locked_page) {
                $should_enqueue = true;
            }
        } else {
            $should_enqueue = true;
        }
    } elseif (!$should_enqueue && !empty($click_links)) {
        $should_enqueue = true;
    }

    if ($should_enqueue) {
        // Enqueue jQuery first
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-api-fetch');

        // Enqueue CSS
        [$style_url, $style_ver] = nias_login_asset('assets/css/style.css');
        wp_enqueue_style('nias-login-style', $style_url, [], $style_ver);

        // سیستم توست — باید پیش از script.js لود شود تا window.niasToast در دسترس باشد
        [$toast_url, $toast_ver] = nias_login_asset('assets/js/nias-toast.js');
        wp_enqueue_script('nias-toast', $toast_url, [], $toast_ver, true);

        // Enqueue JS
        [$script_url, $script_ver] = nias_login_asset('assets/js/script.js');
        wp_enqueue_script(
            'nias-login-script',
            $script_url,
            ['jquery', 'wp-api-fetch', 'nias-toast'],
            $script_ver,
            true
        );


        // Add inline script AFTER nias-login-script
        $inline_js = sprintf(
            'window.nias = %s;',
            wp_json_encode([
                'lockedPages' => $locked_pages_array,
                'clickLinks' => $click_links_array,
                'clickClasses' => $click_classes_array,
                'clickIds' => $click_ids_array,
                'countdown_duration' => (int) get_option('nias_countdown_duration', 120),
                'password_only_mode' => (bool) ($password_activate && !$password_otp_activate),
                'password_otp_activate' => (bool) $password_otp_activate,
                'manual_password_activate' => (bool) get_option('nias_manual_password_activate'),
                // ظاهر مؤثر خوانده می‌شود نه خودِ تنظیم؛ بدون المنتور، مودال با
                // ظاهر پیش‌فرض رندر می‌شود و بستن با کلیک بیرون باید کار کند
                'closeOnOutsideClick' => (bool) get_option('nias_login_close_outside') && nias_login_active_design() !== 'elementory_design',
                'ajax_url' => esc_url(home_url('/nias-login')),
                'site_url' => esc_url(home_url()),
            ])
        );

        // IMPORTANT: Add inline script to nias-login-script, with 'before' position
        wp_add_inline_script('nias-login-script', $inline_js, 'before');
    }
}