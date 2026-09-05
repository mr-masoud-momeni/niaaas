<?php
defined('ABSPATH') || exit;

/* -------------------------------------------------------------------------- */
/*              اکسپورت / ایمپورت تنظیمات افزونه (تب دیتابیس)                  */
/* -------------------------------------------------------------------------- */

/**
 * اطمینان از اجرای register_setting‌های افزونه.
 *
 * این‌ها معمولاً روی هوک admin_init ثبت می‌شوند که در بافت admin-ajax اجرا
 * نمی‌شود. اگر رجیستری گروه خالی باشد، توابع/متدهای ثبت را مستقیم صدا می‌زنیم
 * (register_setting ایدمپوتنت است و مقدار قبلی را بازنویسی می‌کند).
 */
function nias_settings_ensure_registered()
{
    global $wp_registered_settings;

    if (is_array($wp_registered_settings)) {
        foreach ($wp_registered_settings as $args) {
            if (isset($args['group']) && $args['group'] === 'nias_login_settings') {
                return; // از قبل ثبت شده‌اند
            }
        }
    }

    if (function_exists('nias_login_register_settings')) {
        nias_login_register_settings();
    }
    if (class_exists('Nias_WC_Status_SMS') && method_exists('Nias_WC_Status_SMS', 'get_instance')) {
        Nias_WC_Status_SMS::get_instance()->register_settings();
    }
    if (class_exists('Nias_Event_SMS') && method_exists('Nias_Event_SMS', 'get_instance')) {
        Nias_Event_SMS::get_instance()->register_settings();
    }
}

/**
 * نام همه آپشن‌هایی که با register_setting در گروه nias_login_settings ثبت شده‌اند.
 * این لیست منبع واحدِ حقیقت برای اکسپورت/ایمپورت است تا با افزوده شدن هر تنظیم
 * جدید، به‌صورت خودکار پوشش داده شود.
 *
 * @return array<string,array> نام آپشن => آرگومان‌های ثبت‌شده (شامل sanitize_callback)
 */
function nias_settings_registered_options()
{
    global $wp_registered_settings;

    // در بافت admin-ajax هوک admin_init اجرا نمی‌شود، پس register_setting‌ها هنوز
    // اجرا نشده‌اند و رجیستری خالی است. در این حالت ثبت تنظیمات را دستی اجرا می‌کنیم.
    nias_settings_ensure_registered();

    $out = [];
    if (is_array($wp_registered_settings)) {
        foreach ($wp_registered_settings as $name => $args) {
            if (isset($args['group']) && $args['group'] === 'nias_login_settings') {
                $out[$name] = $args;
            }
        }
    }

    /**
     * امکان افزودن/حذف آپشن‌ها از فهرست اکسپورت/ایمپورت تنظیمات.
     *
     * @param array<string,array> $out
     */
    return apply_filters('nias_settings_ie_options', $out);
}

/* -------------------------------------------------------------------------- */
/*                    AJAX: دانلود فایل اکسپورت تنظیمات                        */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_settings_export', 'nias_ajax_settings_export');
function nias_ajax_settings_export()
{
    check_ajax_referer('nias_db_actions', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }

    $options = [];
    foreach (array_keys(nias_settings_registered_options()) as $name) {
        // فقط آپشن‌هایی که واقعاً ذخیره شده‌اند اکسپورت می‌شوند تا مقادیر
        // پیش‌فرضِ ذخیره‌نشده در فایل خروجی نیایند.
        $value = get_option($name, null);
        if ($value !== null) {
            $options[$name] = $value;
        }
    }

    $payload = [
        '_meta' => [
            'plugin'      => 'nias-login-signup',
            'version'     => defined('NIAS_LOGIN_VERSION') ? NIAS_LOGIN_VERSION : '',
            'site'        => home_url(),
            'exported_at' => current_time('mysql'),
            'count'       => count($options),
        ],
        'options' => $options,
    ];

    $filename = 'nias-settings-' . date('Y-m-d-His') . '.json';

    nocache_headers();
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/* -------------------------------------------------------------------------- */
/*                    AJAX: ایمپورت (بازیابی) تنظیمات                         */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_settings_import', 'nias_ajax_settings_import');
function nias_ajax_settings_import()
{
    check_ajax_referer('nias_db_actions', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز'], 403);
    }

    // خواندن محتوای JSON: یا از فایل آپلودشده یا از فیلد متنی
    $raw = '';
    if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        if (!empty($_FILES['file']['error'])) {
            wp_send_json_error(['message' => 'خطا در آپلود فایل'], 400);
        }
        $raw = file_get_contents($_FILES['file']['tmp_name']);
    } elseif (isset($_POST['payload'])) {
        $raw = wp_unslash($_POST['payload']);
    }

    $raw = trim((string) $raw);
    if ($raw === '') {
        wp_send_json_error(['message' => 'فایلی انتخاب نشده یا محتوای آن خالی است'], 400);
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        wp_send_json_error(['message' => 'فایل معتبر نیست (JSON نامعتبر)'], 400);
    }

    // پشتیبانی از هر دو حالت: {options:{...}} یا مستقیم {name:value}
    $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : $data;

    // اطمینان از اینکه فایل مربوط به همین افزونه است (اگر متادیتا دارد)
    if (isset($data['_meta']['plugin']) && $data['_meta']['plugin'] !== 'nias-login-signup') {
        wp_send_json_error(['message' => 'این فایل متعلق به افزونه دیگری است'], 400);
    }

    $registered = nias_settings_registered_options();

    $applied = 0;
    $skipped = [];

    foreach ($options as $name => $value) {
        // فقط آپشن‌های شناخته‌شده (whitelist) پذیرفته می‌شوند تا آپشن دلخواهی
        // بازنویسی نشود.
        if (!isset($registered[$name])) {
            $skipped[] = $name;
            continue;
        }

        // اعمال همان sanitize_callback ثبت‌شده‌ی هر تنظیم (در صورت وجود) تا
        // ورودی دقیقاً مثل ذخیره از صفحه تنظیمات پاکسازی شود.
        $callback = isset($registered[$name]['sanitize_callback']) ? $registered[$name]['sanitize_callback'] : null;
        if ($callback && is_callable($callback)) {
            $value = call_user_func($callback, $value);
        }

        update_option($name, $value);
        $applied++;
    }

    if ($applied === 0) {
        wp_send_json_error(['message' => 'هیچ تنظیم معتبری در فایل یافت نشد'], 400);
    }

    wp_send_json_success([
        'message' => sprintf('%d تنظیم با موفقیت بازیابی شد.', $applied),
        'applied' => $applied,
        'skipped' => count($skipped),
    ]);
}

/* -------------------------------------------------------------------------- */
/*                         AJAX: جستجوی محصولات                               */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_search_products', 'nias_ajax_search_products');
function nias_ajax_search_products()
{
    check_ajax_referer('nias_export_users', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    if (!function_exists('wc_get_products')) {
        wp_send_json(['results' => [], 'pagination' => ['more' => false]]);
    }

    $search   = isset($_GET['q'])    ? sanitize_text_field($_GET['q'])  : '';
    $page     = isset($_GET['page']) ? max(1, absint($_GET['page']))     : 1;
    $per_page = 20;

    $args = [
        'limit'   => $per_page,
        'offset'  => ($page - 1) * $per_page,
        'status'  => 'publish',
        'return'  => 'objects',
        'orderby' => 'title',
        'order'   => 'ASC',
    ];

    if ($search !== '') {
        $args['s'] = $search;
    }

    $products = wc_get_products($args);

    $results = [];
    foreach ($products as $product) {
        $results[] = [
            'id'   => $product->get_id(),
            'text' => $product->get_name() . ' (#' . $product->get_id() . ')',
        ];
    }

    // بررسی وجود صفحه بعدی
    $args_more           = $args;
    $args_more['offset'] = $page * $per_page;
    $args_more['limit']  = 1;
    $more                = !empty(wc_get_products($args_more));

    wp_send_json([
        'results'    => $results,
        'pagination' => ['more' => $more],
    ]);
}

/* -------------------------------------------------------------------------- */
/*                         AJAX: شمارش کاربران                                 */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_export_count_users', 'nias_ajax_export_count_users');
function nias_ajax_export_count_users()
{
    check_ajax_referer('nias_export_users', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $count = nias_export_get_user_count();
    wp_send_json_success(['count' => $count]);
}

/* -------------------------------------------------------------------------- */
/*                         AJAX: دانلود فایل خروجی                             */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_export_users_download', 'nias_ajax_export_users_download');
function nias_ajax_export_users_download()
{
    check_ajax_referer('nias_export_users', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }

    $fields = isset($_POST['fields']) ? (array) $_POST['fields'] : [];
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';

    if (empty($fields)) {
        wp_die('هیچ فیلدی انتخاب نشده است');
    }

    $users = nias_export_get_users();
    $rows  = nias_export_build_rows($users, $fields);

    if ($format === 'excel') {
        nias_export_as_excel($rows);
    } else {
        nias_export_as_csv($rows);
    }
    exit;
}

/* -------------------------------------------------------------------------- */
/*          پیدا کردن خریداران یک محصول با SQL مستقیم (بدون loop)             */
/* -------------------------------------------------------------------------- */
function nias_export_get_buyer_ids($product_id, $order_from = '', $order_to = '')
{
    global $wpdb;

    $product_id = absint($product_id);
    if (!$product_id) return [];

    // آرایه شرط‌های تاریخ
    $date_condition = '';
    $date_params    = [];

    // ─── HPOS (High-Performance Order Storage) ───
    $hpos = (
        class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
        method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
        \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
    );

    if ($hpos) {
        if ($order_from) {
            $date_condition .= ' AND o.date_created_gmt >= %s';
            $date_params[]   = $order_from . ' 00:00:00';
        }
        if ($order_to) {
            $date_condition .= ' AND o.date_created_gmt <= %s';
            $date_params[]   = $order_to . ' 23:59:59';
        }

        $sql = "
            SELECT DISTINCT o.customer_id
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
                ON oi.order_id = o.id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
                ON oim.order_item_id = oi.order_item_id
                AND oim.meta_key = '_product_id'
                AND oim.meta_value = %d
            WHERE o.type = 'shop_order'
              AND o.status IN ('wc-completed','wc-processing')
              AND o.customer_id > 0
              {$date_condition}
        ";

        $params   = array_merge([$product_id], $date_params);
        $buyer_ids = $wpdb->get_col($wpdb->prepare($sql, $params));

    } else {
        // ─── روش سنتی (posts + postmeta) ───
        if ($order_from) {
            $date_condition .= ' AND p.post_date >= %s';
            $date_params[]   = $order_from . ' 00:00:00';
        }
        if ($order_to) {
            $date_condition .= ' AND p.post_date <= %s';
            $date_params[]   = $order_to . ' 23:59:59';
        }

        $sql = "
            SELECT DISTINCT pm.meta_value AS customer_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '_customer_user'
                AND pm.meta_value > 0
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
                ON oi.order_id = p.ID
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
                ON oim.order_item_id = oi.order_item_id
                AND oim.meta_key = '_product_id'
                AND oim.meta_value = %d
            WHERE p.post_type   = 'shop_order'
              AND p.post_status IN ('wc-completed','wc-processing')
              {$date_condition}
        ";

        $params   = array_merge([$product_id], $date_params);
        $buyer_ids = $wpdb->get_col($wpdb->prepare($sql, $params));
    }

    return array_map('absint', $buyer_ids);
}

/* -------------------------------------------------------------------------- */
/*                   شمارش کاربران (بدون واکشی کامل داده)                     */
/* -------------------------------------------------------------------------- */
function nias_export_get_user_count()
{
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $order_from = isset($_POST['order_from']) ? sanitize_text_field($_POST['order_from']) : '';
    $order_to   = isset($_POST['order_to'])   ? sanitize_text_field($_POST['order_to'])   : '';

    if ($product_id && function_exists('wc_get_orders')) {
        $buyer_ids = nias_export_get_buyer_ids($product_id, $order_from, $order_to);
        if (empty($buyer_ids)) return 0;

        // اعمال فیلتر نقش و تاریخ ثبت‌نام روی خریداران
        $args = nias_export_build_user_args();
        $args['include'] = $buyer_ids;
        $args['fields']  = 'ID';
        $args['number']  = -1;
        return count(get_users($args));
    }

    $args           = nias_export_build_user_args();
    $args['fields'] = 'ID';
    $args['number'] = -1;
    return count(get_users($args));
}

/* -------------------------------------------------------------------------- */
/*                         دریافت لیست کاربران با فیلتر                        */
/* -------------------------------------------------------------------------- */
function nias_export_get_users()
{
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $order_from = isset($_POST['order_from']) ? sanitize_text_field($_POST['order_from']) : '';
    $order_to   = isset($_POST['order_to'])   ? sanitize_text_field($_POST['order_to'])   : '';

    $args = nias_export_build_user_args();
    $args['number'] = -1;
    $args['fields'] = 'all';

    if ($product_id && function_exists('wc_get_orders')) {
        $buyer_ids = nias_export_get_buyer_ids($product_id, $order_from, $order_to);
        if (empty($buyer_ids)) return [];
        $args['include'] = $buyer_ids;
    }

    return get_users($args);
}

/* -------------------------------------------------------------------------- */
/*                  ساخت آرگومان‌های پایه WP_User_Query                       */
/* -------------------------------------------------------------------------- */
function nias_export_build_user_args()
{
    $role     = isset($_POST['role'])     ? sanitize_text_field($_POST['role'])     : '';
    $reg_from = isset($_POST['reg_from']) ? sanitize_text_field($_POST['reg_from']) : '';
    $reg_to   = isset($_POST['reg_to'])   ? sanitize_text_field($_POST['reg_to'])   : '';

    $args = [];

    if ($role) {
        $args['role'] = $role;
    }

    if ($reg_from || $reg_to) {
        $args['date_query'] = ['inclusive' => true];
        if ($reg_from) {
            $args['date_query']['after'] = $reg_from . ' 00:00:00';
        }
        if ($reg_to) {
            $args['date_query']['before'] = $reg_to . ' 23:59:59';
        }
    }

    return $args;
}

/* -------------------------------------------------------------------------- */
/*                         ساخت ردیف‌های داده                                  */
/* -------------------------------------------------------------------------- */
function nias_export_build_rows($users, $fields)
{
    $standard_fields = [];
    $custom_metas    = [];
    foreach ($fields as $f) {
        if (strpos($f, 'custom:') === 0) {
            $custom_metas[] = substr($f, 7);
        } else {
            $standard_fields[] = $f;
        }
    }

    $all_fields = array_merge($standard_fields, $custom_metas);
    $labels = [
        'first_name'          => 'نام',
        'last_name'           => 'نام خانوادگی',
        'phone'               => 'شماره موبایل',
        'user_email'          => 'ایمیل',
        'user_registered'     => 'تاریخ ثبت‌نام',
        'billing_first_name'  => 'نام (صورتحساب)',
        'billing_last_name'   => 'نام خانوادگی (صورتحساب)',
        'billing_phone'       => 'تلفن (صورتحساب)',
        'billing_email'       => 'ایمیل (صورتحساب)',
        'billing_address_1'   => 'آدرس (صورتحساب)',
        'billing_city'        => 'شهر (صورتحساب)',
        'billing_state'       => 'استان (صورتحساب)',
        'billing_postcode'    => 'کد پستی (صورتحساب)',
        'billing_country'     => 'کشور (صورتحساب)',
        'user_login'          => 'نام کاربری',
        'display_name'        => 'نام نمایشی',
        'roles'               => 'نقش کاربر',
    ];

    $header = [];
    foreach ($all_fields as $f) {
        $header[] = isset($labels[$f]) ? $labels[$f] : $f;
    }

    $rows = [$header];

    $meta_fields = [
        'first_name', 'last_name', 'phone',
        'billing_first_name', 'billing_last_name', 'billing_phone',
        'billing_email', 'billing_address_1', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
    ];

    foreach ($users as $user) {
        $row = [];
        foreach ($standard_fields as $f) {
            if ($f === 'user_email') {
                $row[] = $user->user_email;
            } elseif ($f === 'user_login') {
                $row[] = $user->user_login;
            } elseif ($f === 'display_name') {
                $row[] = $user->display_name;
            } elseif ($f === 'user_registered') {
                $row[] = $user->user_registered;
            } elseif ($f === 'roles') {
                $row[] = implode(', ', (array) $user->roles);
            } else {
                $row[] = get_user_meta($user->ID, $f, true);
            }
        }
        foreach ($custom_metas as $m) {
            $row[] = get_user_meta($user->ID, $m, true);
        }
        $rows[] = $row;
    }

    return $rows;
}

/* -------------------------------------------------------------------------- */
/*                              خروجی CSV                                      */
/* -------------------------------------------------------------------------- */
function nias_export_as_csv($rows)
{
    $filename = 'nias-users-' . date('Y-m-d-His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

/* -------------------------------------------------------------------------- */
/*                         خروجی Excel (XLSX ساده)                             */
/* -------------------------------------------------------------------------- */
function nias_export_as_excel($rows)
{
    $filename = 'nias-users-' . date('Y-m-d-His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '
<html dir="rtl">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Tahoma, sans-serif; font-size: 13px; background: #f4f5fc; margin: 0; padding: 20px; direction: rtl; }
  table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(40,44,90,.10); }
  th {
    background: #5b5ef0; color: #fff;
    font-weight: 700; font-size: 12px;
    padding: 11px 14px; text-align: right;
    border-bottom: 2px solid #4549d6;
    white-space: nowrap;
  }
  td {
    padding: 10px 14px; text-align: right;
    border-bottom: 1px solid #eceefa;
    color: #1b2038; font-size: 12px;
  }
  tr:last-child td { border-bottom: 0; }
  tr:nth-child(even) td { background: #f8f9ff; }
</style>
</head>
<body>';

    echo '<table>';

    foreach ($rows as $i => $row) {
        echo '<tr>';
        $tag = ($i === 0) ? 'th' : 'td';
        foreach ($row as $cell) {
            $val = htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8');
            echo "<{$tag}>{$val}</{$tag}>";
        }
        echo '</tr>';
    }

    echo '</table></body></html>';
}
