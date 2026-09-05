<?php
/**
 * Nias login signup
 *
 * @package           PluginPackage
 * @author            Alireza aliniya
 * @copyright         2026 nias
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Nias login signup | پلاگین ورود و ثبت نام نیاس
 * Plugin URI:        https://nias.ir
 * Description:       نیاس لاگین سبک ترین و راحت ترین پلاگین ورود و ثبت نام پیامکی / ایمیلی با قابلیت های بینظیر(جهت استفاده کامل از امکانات حتماً <a href="https://www.aparat.com/playlist/24182337/" target="_blank">آموزش های تنظیم و استفاده از پلاگین را از آپارات مشاهده کنید</a>) 
 * Version:           1.3.1
 * Requires at least: 5.2
 * Requires PHP:      8.1
 * Author:            Alireza aliniya
 * Author URI:        https://nias.ir
 * Text Domain:       nias-login-signup
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
defined('ABSPATH') || exit;
define('NIAS_LOGIN_VERSION', '1.3.1');


$critical_file = plugin_dir_path(__FILE__) . 'login-nlmw.php';
if (!file_exists($critical_file)) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>خطای حیاتی:</strong> فایل‌های اصلی پلاگین نیاس لاگین یافت نشد. لطفاً پلاگین را مجدداً نصب کنید.</p></div>';
    });
    return; // Stop plugin execution
}
require_once $critical_file;

// Verify critical constants are loaded
if (!defined('NIAS_LOGIN_VERSION') || !defined('NIAS_LOGIN_INC')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>خطای حیاتی:</strong> فایل‌های اصلی پلاگین نیاس لاگین به درستی بارگذاری نشد. لطفاً پلاگین را مجدداً نصب کنید.</p></div>';
    });
    return; // Stop plugin execution
}
require_once NIAS_LOGIN_INC . 'class-nias-login-handler.php';
require_once NIAS_LOGIN_INC . 'class-nias-auth-blocker.php';
Nias_Auth_Blocker::get_instance();
require_once NIAS_LOGIN_INC . 'class-email-gateway.php';
require(NIAS_LOGIN_INC . 'elementor-template.php');
require(NIAS_LOGIN_INC . 'elementor-template-builder.php');

// ویجت‌های المنتور فقط وقتی خودِ المنتور بالا آمده باشد بارگذاری می‌شوند؛
// بدون المنتور اصلاً فراخوانی نمی‌شوند (کلاس‌های ویجت از Widget_Base ارث می‌برند)
if (did_action('elementor/loaded')) {
    require_once NIAS_ELEMENTOR_WIDGET . 'modal-widget.php';
} else {
    add_action('elementor/loaded', function () {
        require_once NIAS_ELEMENTOR_WIDGET . 'modal-widget.php';
    });
}
require(NIAS_LOGIN_TEMPLATE . 'simple.php');
require(NIAS_LOGIN_INC . 'enqueue.php');
require(NIAS_LOGIN_INC . 'functions.php');
require(NIAS_LOGIN_INC . 'modal-texts.php');
require(NIAS_LOGIN_INC . 'activation.php');
require(NIAS_LOGIN_INC . 'ajax.php');

// نام‌های قدیمیِ بدون پیشوند توابع عمومی — فقط برای کدهای سفارشی قدیمی
require(NIAS_LOGIN_INC . 'legacy-function-aliases.php');

// Cron cleanup function - moved from main file
function nias_login_plugin_function()
{
    global $wpdb;
    $table_name  = $wpdb->prefix . 'nias_sms_login';
    $cleanup_days = (int) get_option('nias_login_log_cleanup_days', 30);

    if ($cleanup_days > 0) {
        // حذف رکوردهای قدیمی‌تر از تعداد روز تنظیم‌شده
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $cleanup_days
            )
        );
    } else {
        // 0 = پاکسازی کامل (رفتار قدیمی)
        $wpdb->query("DELETE FROM {$table_name}");
    }
}
add_action('nias_login_plugin_event', 'nias_login_plugin_function');

// Include EasySale module
require_once plugin_dir_path(__FILE__) . 'fastsell/easysale.php';
Nias_Login_Fastsell_Plugin::get_instance();

// فرم سفارش سریع (شورت‌کد) — فقط در صورت فعال بودن تنظیمات
if (get_option('nias_fastform_activate')) {
    require_once plugin_dir_path(__FILE__) . 'fastsell/fastform.php';
}

// پیامک وضعیت‌های سفارش ووکامرس
require_once NIAS_LOGIN_INC . 'class-wc-status-sms.php';
Nias_WC_Status_SMS::get_instance();

// پیامک‌های رویدادی (خوش‌آمدگویی، اطلاع ورود، پس از نظر)
require_once NIAS_LOGIN_INC . 'class-event-sms.php';

// ورود / ثبت‌نام با حساب گوگل
require_once NIAS_LOGIN_INC . 'class-google-login.php';


//require(NIAS_LOGIN_INC . 'gateways.php');
require(NIAS_LOGIN_PUBLIC . 'modal.php');
if (is_admin()) {
    require(NIAS_LOGIN_ADMIN . 'manage-users.php');
    require(NIAS_LOGIN_ADMIN . 'export-users.php');
    require(NIAS_LOGIN_ADMIN . 'export-ajax.php');
}
require_once plugin_dir_path(__FILE__) . 'user-meta/user-meta.php';

// نظرسنجی محصولات (مودال اجباری مجزا)
require_once plugin_dir_path(__FILE__) . 'review-survey/review-survey.php';

// تابع فلش
function nias_flush_rewrite_rules()
{
    flush_rewrite_rules();
}

// اجرا هنگام فعال‌سازی پلاگین
register_activation_hook(__FILE__, function () {
    update_option('nias_needs_flush', true);
});

// اجرا هنگام بروزرسانی پلاگین
add_action('upgrader_process_complete', function ($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        $plugin_basename = plugin_basename(__FILE__);
        if (isset($options['plugins']) && in_array($plugin_basename, $options['plugins'])) {
            update_option('nias_needs_flush', true);
        }
    }
}, 10, 2);

// اجرا هنگام غیرفعال‌سازی پلاگین
register_deactivation_hook(__FILE__, 'nias_flush_rewrite_rules');

// پاک‌سازی هنگام حذف افزونه در uninstall.php انجام می‌شود.
// register_uninstall_hook اینجا بی‌اثر بود (وردپرس وقتی uninstall.php وجود دارد
// آن را اجرا می‌کند و این هوک را نادیده می‌گیرد) ولی در هر بار لود صفحه یک
// update_option روی آپشن uninstall_plugins می‌زد؛ حذف شد.

// اجرای فلش در اولین بار بعد از فعال‌سازی/بروزرسانی
add_action('init', function () {
    if (get_option('nias_needs_flush')) {
        nias_flush_rewrite_rules();
        delete_option('nias_needs_flush');
    }
});

// ───────────────────────────────
// دکمه فلش در صفحه پلاگین‌ها
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nias_add_settings_link');
function nias_add_settings_link($links)
{
    $flush_link = '<a href="' . wp_nonce_url(admin_url('admin-post.php?action=nias_flush_rewrite'), 'nias_flush') . '">فلش لینک‌ها</a>';
    $settings_link = '<a href="' . admin_url('options-general.php?page=nias-login-plugin-license') . '">تنظیمات</a>';
    array_unshift($links, $flush_link, $settings_link);
    return $links;
}

// اجرای فلش از دکمه
add_action('admin_post_nias_flush_rewrite', 'nias_handle_flush_rewrite_request');
function nias_handle_flush_rewrite_request()
{
    if (!current_user_can('manage_options') || !check_admin_referer('nias_flush')) {
        wp_die('دسترسی غیرمجاز');
    }
    nias_flush_rewrite_rules();
    wp_safe_redirect(admin_url('plugins.php?flushed=true'));
    exit;
}

// پیام موفقیت
add_action('admin_notices', function () {
    if (isset($_GET['flushed'])) {
        echo '<div class="notice notice-success is-dismissible nias-plugin-notice"><p>فلش با موفقیت انجام شد و لینک‌ها بازسازی شد.</p></div>';
    }
});



//Thanks to https://github.com/hamedmoody for providing the tutorial on creating the main source of the plugin
