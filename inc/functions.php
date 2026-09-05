<?php
defined('ABSPATH') || exit;

/**
 * ووکامرس نصب و فعال است؟
 *
 * تنظیماتی که فقط با ووکامرس معنا دارند (خرید سریع، نظرسنجی محصولات، همگام‌سازی
 * billing_phone و...) با همین تابع پنهان می‌شوند تا کاربرِ سایت غیرفروشگاهی
 * گزینه‌هایی نبیند که هیچ اثری ندارند.
 *
 * نکته: در سازنده‌ی افزونه‌ها قابل اتکا نیست — وردپرس افزونه‌ها را الفبایی لود
 * می‌کند و «nias-login-signup» قبل از «woocommerce» می‌آید. اینجا مشکلی نیست
 * چون فقط هنگام رندر صفحه‌ی تنظیمات (خیلی بعدتر) صدا زده می‌شود.
 */
function nias_login_wc_active(): bool
{
    return class_exists('WooCommerce') && function_exists('WC');
}


/**
 * مقادیر پیش‌فرض تنظیمات پلاگین.
 *
 * این مقادیر باید با defaultهای register_setting در admin/manage-users.php یکسان باشند.
 * register_setting فقط در پیشخوان (admin_init) اجرا می‌شود؛ بنابراین فیلتر default_option
 * که مقدار پیش‌فرض را برمی‌گرداند فقط در ادمین فعال است. در نتیجه قبل از اولین ذخیره‌سازی،
 * فراخوانی get_option در فرانت‌اند (عملکرد واقعی پلاگین مثل تعداد ارقام کد تایید) مقدار خالی
 * برمی‌گرداند. این تابع همان defaultها را در همه‌جا (فرانت‌اند و ادمین) فعال می‌کند.
 */
function nias_login_option_defaults()
{
    return array(
        // هویت کاربر / کد تایید
        'nsdigitsquantity'                     => 4,
        'displaynamenias'                      => 'کاربر {userid}',
        'nias_login_username'                  => '{niasrandom}',
        'nias_default_user_role'               => 'subscriber',
        'nias_login_expire'                    => 60,

        // ریدایرکت و قفل
        'nias_login_locked_pages'             => 'my-account',
        'nias_login_click_links'              => 'my-account',

        // تغییر شماره موبایل در پنل کاربری (با کد تایید)
        'nias_account_phone_change'           => 0,

        // همگام‌سازی billing_phone ووکامرس با متای phone
        'nias_billing_phone_sync'             => 0,

        // ساختار پسورد خودکار
        'nias_password_length'                => 12,
        'nias_password_include_uppercase'     => 1,
        'nias_password_include_lowercase'     => 1,
        'nias_password_include_numbers'       => 1,
        'nias_password_include_special'       => 1,

        // استایل
        'nias_login_style_radius'             => 15,
        'nias_login_style_title_font_size'    => 20,
        'nias_login_style_subtitle_font_size' => 13,
        'nias_login_style_font_size'          => 16,

        // ترنسفورم لیبل شناور فیلدها — حالت عادی و حالت فوکوس/پرشده
        // (مقادیر پیش‌فرض دقیقاً همان چیزی است که تا امروز در style.css ثابت بود)
        'nias_login_style_label_ty'           => 6,
        'nias_login_style_label_tx'           => 0,
        'nias_login_style_label_scale'        => 1,
        'nias_login_style_label_ty_focus'     => -14,
        'nias_login_style_label_tx_focus'     => -5,
        'nias_login_style_label_scale_focus'  => 0.7,

        // شمارش معکوس / تلاش‌ها
        'nias_countdown_duration'             => 120,
        'nias_max_attempts'                   => 3,
        'nias_block_duration'                 => 60,

        // ایمیل
        'nias_email_gateway'                  => 'wp_mail',
        'nias_email_message_template'         => '<p>کد ورود شما: <strong>{code}</strong></p>',
        'nias_email_password_message_template' => '<p>پسورد جدید شما: <strong>{password}</strong></p>',

        // امنیت
        'nias_security_mode'                  => 'block',
        'nias_block_time_window'              => 60,
        'nias_block_min_attempts'             => 3,
        'nias_cooldown_level1_attempts'       => 2,
        'nias_cooldown_level1_duration'       => 30,
        'nias_cooldown_level2_attempts'       => 4,
        'nias_cooldown_level2_duration'       => 120,
        'nias_cooldown_level3_attempts'       => 6,
        'nias_cooldown_level3_duration'       => 600,
        'nias_cooldown_level4_attempts'       => 8,
        'nias_cooldown_level4_duration'       => 1800,
        'nias_cooldown_max_attempts'          => 10,
        'nias_security_logging'               => 1,
        'nias_security_cleanup_days'          => 30,
        'nias_login_log_cleanup_days'         => 30,

        // نظرسنجی محصولات
        'nias_review_survey_scope'            => 'all',
        'nias_review_survey_product_id'       => 0,
        'nias_review_survey_closable'         => 1,
        'nias_review_survey_max_shows'        => 1,
        'nias_review_survey_delay_days'       => 0,

        // فروش سریع
        'nias_fastsell_show_order_summary'    => 1,
        'nias_fastsell_show_price_breakdown'  => 1,
        'nias_fastsell_open_modal_after_add'  => 1,
        'nias_fastsell_keep_cart_until_paid'  => 0,
        'nias_fastform_activate'              => 0,
    );
}

/**
 * ثبت فیلترهای default_option برای همه تنظیمات دارای مقدار پیش‌فرض.
 *
 * با این کار get_option حتی پیش از اولین ذخیره‌سازی (و در فرانت‌اند) مقدار پیش‌فرض درست
 * را برمی‌گرداند. اگر فراخوانی‌کننده خودش default صریح بدهد (get_option('x', $y))، همان
 * مقدار ارسالی محترم شمرده می‌شود.
 */
function nias_login_apply_option_defaults()
{
    foreach (nias_login_option_defaults() as $option_name => $default_value) {
        add_filter(
            "default_option_{$option_name}",
            function ($default, $option, $passed_default) use ($default_value) {
                // اگر فراخوانی‌کننده default صریح داده باشد، آن را تغییر نده.
                return $passed_default ? $default : $default_value;
            },
            10,
            3
        );
    }
}
nias_login_apply_option_defaults();


/**
 * مقدار مؤثر یک تریگر باز‌کننده مودال یا صفحه قفل را برمی‌گرداند.
 *
 * قانون: اگر فیلد خالی باشد، مقدار پیش‌فرض 'my-account' اعمال می‌شود؛ اگر کاربر چیزی
 * وارد کرده باشد، دقیقاً از همان مقدار واردشده پیروی می‌شود (نه پیش‌فرض).
 */
function nias_login_trigger_value($option_name)
{
    $value = trim((string) get_option($option_name));
    return $value === '' ? 'my-account' : $value;
}


/**
 * ضریب بزرگ‌نمایی (scale) لیبل شناور را بین ۰٫۱ تا ۳ محدود می‌کند.
 * مقدار نامعتبر یا صفر باعث ناپدید شدن کامل لیبل می‌شود، پس به ۱ برمی‌گردد.
 */
function nias_login_sanitize_label_scale($value)
{
    $value = (float) nias_normalize_digits((string) $value);

    if ($value <= 0) {
        return 1.0;
    }

    return (float) min(3, max(0.1, $value));
}

/**
 * جابه‌جایی (translate) لیبل شناور بر حسب پیکسل؛ مقدار منفی مجاز است.
 */
function nias_login_sanitize_label_offset($value)
{
    return (int) nias_normalize_digits((string) $value);
}


/**
 * تبدیل اعداد فارسی و عربی به انگلیسی
 * برای استفاده در تمام جاهای پردازش اعداد
 */
function nias_normalize_digits($input)
{
    $western = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');

    $normalized = str_replace($persian, $western, $input);
    $normalized = str_replace($arabic, $western, $normalized);

    return $normalized;
}

/**
 * ارزیابی کد تایید (OTP) با پشتیبانی اعداد چند‌زبانی
 */
function nias_validate_otp_code($code)
{
    if (empty($code)) {
        return false;
    }

    // نرمال‌سازی اعداد
    $normalized_code = nias_normalize_digits($code);

    // حذف فاصله و کاراکترهای غیرضروری
    $normalized_code = preg_replace('/[^\d]/', '', $normalized_code);

    // بررسی اینکه فقط اعداد باشد
    if (!ctype_digit($normalized_code)) {
        return false;
    }

    // بررسی طول کد (معمولاً 4-6 رقم)
    $allowed_lengths = array(4, 5, 6);
    if (!in_array(strlen($normalized_code), $allowed_lengths)) {
        return false;
    }

    return $normalized_code;
}

/**
 * ارزیابی شماره تلفن با پشتیبانی اعداد چند‌زبانی
 */
function nias_validate_phone_number($phone)
{
    if (empty($phone)) {
        return false;
    }

    // نرمال‌سازی اعداد ابتدا
    $normalized = nias_normalize_digits($phone);

    // تنظیف: حذف فاصله، خط تیره و کاراکترهای غیرضروری
    $normalized = preg_replace('/[\s\-\(\)]/u', '', $normalized);

    // الگوهای معتبر شماره موبایل ایرانی
    $patterns = array(
        '/^09\d{9}$/',              // 09123456789
        '/^\+989\d{9}$/',           // +989123456789
        '/^00989\d{9}$/',           // 00989123456789
        '/^989\d{9}$/',             // 989123456789
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $normalized)) {
            // تبدیل به فرمت استاندارد 09xxxxxxxxx
            if (substr($normalized, 0, 4) === '+989') {
                return '0' . substr($normalized, 4);
            } elseif (substr($normalized, 0, 5) === '00989') {
                return '0' . substr($normalized, 5);
            } elseif (substr($normalized, 0, 3) === '989') {
                return '0' . substr($normalized, 3);
            }
            return $normalized;
        }
    }

    return false;
}

/**
 * ارزیابی ایمیل با پشتیبانی اعداد چند‌زبانی
 */
function nias_validate_email_normalized($email)
{
    if (empty($email)) {
        return false;
    }

    // نرمال‌سازی اعداد در بخش ایمیل
    $normalized = nias_normalize_digits($email);

    // حذف فاصله‌های اضافی
    $normalized = trim($normalized);

    // استفاده از تابع built-in WordPress
    if (!is_email($normalized)) {
        return false;
    }

    return $normalized;
}

/**
 * ارزیابی identifier (شماره یا ایمیل) با پشتیبانی اعداد چند‌زبانی
 */
function nias_validate_identifier($identifier)
{
    if (empty($identifier)) {
        return array('valid' => false, 'type' => null, 'value' => null);
    }

    // نرمال‌سازی اعداد
    $normalized = nias_normalize_digits($identifier);
    $normalized = trim($normalized);

    // بررسی ایمیل
    if (is_email($normalized)) {
        return array(
            'valid' => true,
            'type' => 'email',
            'value' => $normalized
        );
    }

    // بررسی شماره تلفن
    $validated_phone = nias_validate_phone_number($normalized);
    if ($validated_phone) {
        return array(
            'valid' => true,
            'type' => 'phone',
            'value' => $validated_phone
        );
    }

    return array('valid' => false, 'type' => null, 'value' => null);
}

/**
 * تابع sanitize_phone اصلاح‌شده با پشتیبانی اعداد چند‌زبانی
 */
function nias_sanitize_phone_enhanced($phone)
{
    if (empty($phone)) {
        return '';
    }

    // نرمال‌سازی اعداد
    $phone = nias_normalize_digits($phone);

    // حذف کاراکترهای غیرضروری
    $phone = preg_replace('/[^\d+]/', '', $phone);

    if (strpos($phone, '.') === 0) {
        $phone = '0' . substr($phone, 1);
    }

    if (strpos($phone, '0098') === 0) {
        $phone = substr($phone, 4);
    }

    if (strlen($phone) === 13 && strpos($phone, '098') === 0) {
        $phone = substr($phone, 3);
    }

    if (strlen($phone) === 13 && strpos($phone, '+98') === 0) {
        $phone = substr($phone, 3);
    }

    if (strlen($phone) === 14 && strpos($phone, '+98 ') === 0) {
        $phone = substr($phone, 4);
    }

    if (strlen($phone) === 12 && strpos($phone, '98') === 0) {
        $phone = substr($phone, 2);
    }

    if (strpos($phone, '0') !== 0) {
        $phone = '0' . $phone;
    }

    if (!ctype_digit($phone)) {
        return '';
    }

    if (strlen($phone) !== 11) {
        return '';
    }

    return $phone;
}

/**
 * ارزیابی کد تایید برای مقایسه
 * برای استفاده در verify request
 */
function nias_compare_otp_codes($stored_code, $user_input)
{
    if (empty($stored_code) || empty($user_input)) {
        return false;
    }

    // نرمال‌سازی هر دو کد
    $normalized_stored = nias_normalize_digits($stored_code);
    $normalized_input = nias_normalize_digits($user_input);

    // حذف اعداد غیرضروری
    $normalized_stored = preg_replace('/[^\d]/', '', $normalized_stored);
    $normalized_input = preg_replace('/[^\d]/', '', $normalized_input);

    // مقایسه حساس به بزرگی و کوچکی
    return $normalized_stored === $normalized_input;
}

$digits = get_option('nsdigitsquantity');

function nias_generate_code($digits)
{
    $code = '';
    for ($i = 0; $i < $digits; $i++) {
        $code .= rand(! $i ? 1 : 0, 9);
    }
    return $code;
}

function nias_register_code($phone, $code, $expired_at, $ip)
{
    global $wpdb;

    $data = [
        'ip'              => $ip,
        'phone'         => $phone,
        'code'          => $code,
        'expired_at'    => $expired_at,
        'created_at'    => current_time('mysql'),
        'updated_at'    => current_time('mysql'),
    ];

    $inserted = $wpdb->insert(
        $wpdb->nias_sms_login,
        $data,
        '%s'

    );
    if (! $inserted) {
        //  notificator_send_message('insert error for' .$wpdb->nias_sms_login . PHP_EOL . print_r($data, true));
        new WP_Error('error_insertion', 'خطا در ثبت داده');
    }
    return $wpdb->insert_id;
}

/**
 * بررسی اینکه آیا نام نمایشی از قبل بر اساس نام و نام خانوادگی ساخته شده
 */
function nias_is_display_name_from_real_name($user_id)
{
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return false;
    }

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $display_name = $user->display_name;

    // اگر نام یا نام خانوادگی خالی باشد
    if (empty($first_name) && empty($last_name)) {
        return false;
    }

    // ترکیب‌های ممکن برای نام نمایشی
    $possible_combinations = array();

    if (!empty($first_name) && !empty($last_name)) {
        $possible_combinations[] = $first_name . ' ' . $last_name;
        $possible_combinations[] = $last_name . ' ' . $first_name;
        $possible_combinations[] = trim($first_name . ' ' . $last_name);
        $possible_combinations[] = trim($last_name . ' ' . $first_name);
    } elseif (!empty($first_name)) {
        $possible_combinations[] = $first_name;
        $possible_combinations[] = trim($first_name);
    } elseif (!empty($last_name)) {
        $possible_combinations[] = $last_name;
        $possible_combinations[] = trim($last_name);
    }

    // بررسی اینکه آیا نام نمایشی فعلی با یکی از ترکیب‌ها مطابقت دارد
    return in_array(trim($display_name), array_map('trim', $possible_combinations));
}

/**
 * بروزرسانی نام نمایشی بر اساس نام و نام خانوادگی
 */
function nias_update_display_name_from_real_name($user_id)
{
    // جلوگیری از recursion
    static $processing = false;
    if ($processing) {
        return;
    }
    $processing = true;

    $user = get_user_by('ID', $user_id);
    if (!$user) {
        $processing = false;
        return;
    }

    // اگر نام نمایشی از قبل بر اساس نام واقعی ساخته شده، کاری نکن
    if (nias_is_display_name_from_real_name($user_id)) {
        $processing = false;
        return;
    }

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);

    // اگر هر دو خالی باشند، کاری نکن
    if (empty($first_name) && empty($last_name)) {
        $processing = false;
        return;
    }

    // ساخت نام نمایشی جدید
    $new_display_name = '';
    if (!empty($first_name) && !empty($last_name)) {
        $new_display_name = $first_name . ' ' . $last_name;
    } elseif (!empty($first_name)) {
        $new_display_name = $first_name;
    } else {
        $new_display_name = $last_name;
    }

    // بروزرسانی نام نمایشی
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => trim($new_display_name)
    ));

    $processing = false;
}
/**
 * بروزرسانی نام نمایشی بعد از لاگین
 */
add_action('wp_login', 'nias_update_display_name_on_login', 10, 2);
function nias_update_display_name_on_login($user_login, $user)
{
    if ($user && isset($user->ID)) {
        nias_update_display_name_from_real_name($user->ID);
    }
}



/**
 * بروزرسانی نام نمایشی بعد از تکمیل خرید در ووکامرس
 * فقط در صورتی که سایت ووکامرسی باشد
 */
if (class_exists('WooCommerce')) {
    add_action('woocommerce_thankyou', 'nias_update_display_name_after_purchase', 10, 1);
    function nias_update_display_name_after_purchase($order_id)
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        // دریافت نام و نام خانوادگی از صورت‌حساب
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();

        // اگر نام یا نام خانوادگی در صورت‌حساب پر شده باشد
        if (!empty($billing_first_name) || !empty($billing_last_name)) {

            // بروزرسانی متاهای کاربر اگر خالی باشند
            $current_first_name = get_user_meta($user_id, 'first_name', true);
            $current_last_name = get_user_meta($user_id, 'last_name', true);

            if (empty($current_first_name) && !empty($billing_first_name)) {
                update_user_meta($user_id, 'first_name', sanitize_text_field($billing_first_name));
            }

            if (empty($current_last_name) && !empty($billing_last_name)) {
                update_user_meta($user_id, 'last_name', sanitize_text_field($billing_last_name));
            }

            // بروزرسانی نام نمایشی
            nias_update_display_name_from_real_name($user_id);
        }
    }
}

function nias_get_user_by_phone($phone)
{
    $phone = (string) $phone;
    if ($phone === '') {
        return false;
    }

    // هر دو فرمت (با ۰ و بدون ۰) را می‌سنجیم تا کاربران قدیمی که شماره‌شان
    // بدون صفر ذخیره شده هم پیدا شوند و صرف‌نظر از فرمت ورودی تطبیق برقرار شود.
    $variants = array($phone);
    if (substr($phone, 0, 1) === '0') {
        $variants[] = substr($phone, 1);
    } else {
        $variants[] = '0' . $phone;
    }

    $users = get_users([
        'meta_query' => array(array(
            'key'     => 'phone',
            'value'   => $variants,
            'compare' => 'IN',
        )),
        'number'     => 1,
    ]);
    return empty($users) ? false : $users[0];
}


function nias_get_or_make_user($phone)
{

    $user = nias_get_user_by_phone($phone);

    // اگر از طریق متا پیدا نشد، بررسی کنیم آیا user_login = phone وجود دارد
    if (! $user) {
        $user = get_user_by('login', $phone);
        if ($user) {
            // کاربری با user_login = phone وجود دارد، متا phone را ست کن و برگردان
            update_user_meta($user->ID, 'phone', $phone);
            return $user;
        }
    }

    if (! $user) {
        $phone_last = substr($phone, -4);
        $password   = wp_generate_password(15);

        // ایمیل جایگزین یکتا بر اساس شماره موبایل.
        // برخی سایت‌ها روی ستون user_email ایندکس UNIQUE دارند (مثلاً user_email_pinova_unique)
        // و اگر کاربر را با ایمیل خالی بسازیم، دومین کاربرِ بدون ایمیل با خطای
        // «Duplicate entry '' for key ...» رد می‌شود و کاربر با ID=0 برمی‌گردد.
        $email_domain = parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
        $fake_email   = $phone . '@' . $email_domain;

        $user_id    = wp_create_user($phone, $password, $fake_email);  // ابتدا کاربر ایجاد می‌شود با user_login موقت (که همان phone است)

        // اگر user_login = phone از قبل وجود داشت، کاربر موجود را برگردان
        if (is_wp_error($user_id) && $user_id->get_error_code() === 'existing_user_login') {
            $existing = get_user_by('login', $phone);
            if ($existing) {
                update_user_meta($existing->ID, 'phone', $phone);
                return $existing;
            }
            return $user_id;
        }

        if (! is_wp_error($user_id)) {

            // ذخیره متا phone بلافاصله پس از ایجاد کاربر
            update_user_meta($user_id, 'phone', $phone);

            // حالا تنظیم user_login بر اساس تنظیمات nias_login_username
            $username_template = get_option('nias_login_username', '{niasrandom}');
            $random_number = rand(1000, 9999);  // یک عدد رندوم 4 رقمی
            $niasrandom = 'user' . $random_number . '-' . $phone_last;  // {niasrandom} = user + randomnumber + چهار رقم آخر phone

            // جایگزینی متغیرها در template
            $replacements = array(
                '{niasrandom}' => $niasrandom,
                '{sitename}' => get_bloginfo('name'),
                '{phone}' => $phone,
                '{email}' => '',  // اگر ایمیل موجود نیست، خالی بگذار (برای سازگاری با ایمیل)
                '{username}' => 'user' . $user_id,  // username پیشفرض
            );

            $user_login = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $username_template
            );

            // اگر user_login جدید همان phone است و user_login فعلی هم phone است، نیازی به آپدیت نیست
            // (این حالت وقتی اتفاق می‌افتد که template = {phone})
            $current_user = get_user_by('ID', $user_id);
            if ($current_user && $current_user->user_login !== $user_login) {
                // بروزرسانی user_login
                global $wpdb;
                $wpdb->update(
                    $wpdb->users,
                    ['user_login' => $user_login],
                    ['ID' => $user_id]
                );
            }

            // بروزرسانی nickname
            update_user_meta($user_id, 'nickname', $user_login);

            // حالا کاربر را دوباره بگیریم
            $user = new WP_User($user_id);

            // اعمال نقش کاربری پیش‌فرض تنظیم‌شده در افزونه
            $user->set_role(get_option('nias_default_user_role', get_option('default_role', 'subscriber')));

            // بروزرسانی display_name بعد از ذخیره تمام متاها
            nias_update_user_display_name($user_id);
        } else {
            $user = $user_id;
        }
    }
    return $user;
}



add_action('woocommerce_edit_account_form', 'nias_add_phone_to_edit_account_form');
function nias_add_phone_to_edit_account_form()
{
    $user          = wp_get_current_user();
    $change_active = (bool) get_option('nias_account_phone_change');
?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="padding:10px; background-color:#2666cf;color:white; border-radius:10px">
        <label for="nias-account-phone" style="color:white;"><?php _e('شماره موبایل', 'woocommerce'); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" id="nias-account-phone" value="<?php echo esc_attr($user->phone); ?>" readonly style="background:#ffffffd9;color:#333;" />
        <?php if (!$change_active) : ?>
            <span style="display:block;margin-top:6px;font-size:12px;">امکان تغییر شماره موبایل غیرفعال است. برای تغییر با پشتیبانی تماس بگیرید.</span>
        <?php endif; ?>
    </p>

    <?php if ($change_active) : ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="nias-phone-change-wrap">
            <button type="button" class="woocommerce-Button button" id="nias-phone-change-toggle">تغییر شماره موبایل</button>
        </p>
        <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="nias-phone-change-box" hidden style="padding:12px;border:1px solid #2666cf;border-radius:10px;">
            <p class="form-row" style="margin-bottom:10px;">
                <label for="nias-new-phone">شماره موبایل جدید</label>
                <input type="text" inputmode="tel" maxlength="11" class="woocommerce-Input woocommerce-Input--text input-text" id="nias-new-phone" placeholder="09xxxxxxxxx" autocomplete="off" />
            </p>
            <p class="form-row" style="margin-bottom:10px;">
                <button type="button" class="woocommerce-Button button" id="nias-phone-send-code">ارسال کد تایید</button>
            </p>
            <p class="form-row" id="nias-phone-code-row" hidden style="margin-bottom:10px;">
                <label for="nias-phone-code">کد تایید ارسال‌شده به شماره جدید</label>
                <input type="text" inputmode="numeric" maxlength="6" class="woocommerce-Input woocommerce-Input--text input-text" id="nias-phone-code" autocomplete="one-time-code" />
                <button type="button" class="woocommerce-Button button" id="nias-phone-verify-code" style="margin-top:8px;">تایید و ثبت شماره</button>
            </p>
            <span id="nias-phone-change-msg" style="display:none;padding:6px 10px;border-radius:8px;font-size:13px;"></span>
        </div>
        <script>
        (function () {
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce   = <?php echo wp_json_encode(wp_create_nonce('nias_account_phone')); ?>;

            var toggleBtn = document.getElementById('nias-phone-change-toggle');
            var box       = document.getElementById('nias-phone-change-box');
            var sendBtn   = document.getElementById('nias-phone-send-code');
            var verifyBtn = document.getElementById('nias-phone-verify-code');
            var codeRow   = document.getElementById('nias-phone-code-row');
            var msgEl     = document.getElementById('nias-phone-change-msg');
            var countdown = null;

            function showMsg(text, ok) {
                msgEl.textContent = text;
                msgEl.style.display = 'inline-block';
                msgEl.style.background = ok ? '#e6f7ec' : '#fdecec';
                msgEl.style.color = ok ? '#0f7b3d' : '#c22525';
            }

            function startCountdown(seconds) {
                var remain = parseInt(seconds, 10) || 60;
                sendBtn.disabled = true;
                var base = 'ارسال مجدد کد';
                clearInterval(countdown);
                countdown = setInterval(function () {
                    remain--;
                    sendBtn.textContent = base + ' (' + remain + ')';
                    if (remain <= 0) {
                        clearInterval(countdown);
                        sendBtn.disabled = false;
                        sendBtn.textContent = base;
                    }
                }, 1000);
            }

            function post(action, data, onDone) {
                var body = new URLSearchParams(data);
                body.append('action', action);
                body.append('nonce', nonce);
                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(function (r) { return r.json(); })
                .then(onDone)
                .catch(function () { showMsg('خطا در برقراری ارتباط، لطفاً مجدداً تلاش کنید', false); });
            }

            toggleBtn.addEventListener('click', function () {
                box.hidden = !box.hidden;
            });

            sendBtn.addEventListener('click', function () {
                var phone = document.getElementById('nias-new-phone').value.trim();
                if (!phone) { showMsg('شماره موبایل جدید را وارد کنید', false); return; }
                sendBtn.disabled = true;
                post('nias_account_phone_send_code', { phone: phone }, function (res) {
                    if (res && res.success) {
                        showMsg(res.data.message, true);
                        codeRow.hidden = false;
                        document.getElementById('nias-phone-code').focus();
                        startCountdown(res.data.duration);
                    } else {
                        sendBtn.disabled = false;
                        showMsg((res && res.data && res.data.message) || 'خطایی رخ داده است', false);
                    }
                });
            });

            verifyBtn.addEventListener('click', function () {
                var code = document.getElementById('nias-phone-code').value.trim();
                if (!code) { showMsg('کد تایید را وارد کنید', false); return; }
                verifyBtn.disabled = true;
                post('nias_account_phone_verify_code', { code: code }, function (res) {
                    verifyBtn.disabled = false;
                    if (res && res.success) {
                        showMsg(res.data.message, true);
                        document.getElementById('nias-account-phone').value = res.data.phone;
                        codeRow.hidden = true;
                        box.hidden = true;
                        clearInterval(countdown);
                    } else {
                        showMsg((res && res.data && res.data.message) || 'خطایی رخ داده است', false);
                    }
                });
            });
        })();
        </script>
    <?php endif;
}

/**
 * ارسال کد تایید برای تغییر شماره موبایل در پنل کاربری.
 *
 * ذخیره کد در transient (نه جدول nias_sms_login) تا در شمارش امنیتی
 * تلاش‌های ورود اختلالی ایجاد نشود.
 */
add_action('wp_ajax_nias_account_phone_send_code', 'nias_account_phone_send_code');
function nias_account_phone_send_code()
{
    check_ajax_referer('nias_account_phone', 'nonce');

    if (!get_option('nias_account_phone_change')) {
        wp_send_json_error(['message' => 'امکان تغییر شماره موبایل غیرفعال است'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'ابتدا وارد حساب کاربری شوید'], 401);
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    if (function_exists('nias_is_ip_blocked') && nias_is_ip_blocked($ip)) {
        wp_send_json_error(['message' => 'شما محدود شده‌اید! به پشتیبان سایت اطلاع دهید'], 401);
    }

    $phone = nias_sanitize_phone_enhanced(wp_unslash($_POST['phone'] ?? ''));
    if (!$phone) {
        wp_send_json_error(['message' => 'شماره موبایل وارد شده صحیح نیست'], 400);
    }

    if ($phone === (string) get_user_meta($user_id, 'phone', true)) {
        wp_send_json_error(['message' => 'این شماره هم‌اکنون شماره فعلی حساب شماست'], 400);
    }

    // جلوگیری از ثبت شماره تکراری
    $existing = nias_get_user_by_phone($phone);
    if ($existing && (int) $existing->ID !== $user_id) {
        wp_send_json_error(['message' => 'این شماره قبلاً در سایت ثبت شده است و امکان استفاده از آن وجود ندارد'], 409);
    }

    $key    = 'nias_phone_change_' . $user_id;
    $exists = get_transient($key);
    if ($exists && !empty($exists['expires_at']) && $exists['expires_at'] > current_time('timestamp')) {
        wp_send_json_error(['message' => 'کد قبلی هنوز معتبر است، لطفاً کمی صبر کنید'], 429);
    }

    $digits = (int) get_option('nsdigitsquantity', 4);
    $expire = (int) get_option('nias_login_expire', 60);
    $code   = nias_generate_code($digits);

    $gateway = new Nias_SMS_Gateway();
    $result  = $gateway->send_code($phone, $code);
    if ($result !== true) {
        wp_send_json_error(['message' => is_string($result) ? $result : 'خطا در ارسال پیامک'], 503);
    }
    if (get_option('nias_bale_activate')) {
        $gateway->send_bale($phone, $code);
    }

    set_transient($key, [
        'phone'      => $phone,
        'code'       => $code,
        'attempts'   => 0,
        'expires_at' => current_time('timestamp') + $expire,
    ], $expire);

    wp_send_json_success([
        'message'  => 'کد ' . $digits . ' رقمی به شماره ' . $phone . ' ارسال شد',
        'duration' => $expire,
    ]);
}

/**
 * تایید کد و اعمال شماره موبایل جدید در پنل کاربری
 */
add_action('wp_ajax_nias_account_phone_verify_code', 'nias_account_phone_verify_code');
function nias_account_phone_verify_code()
{
    check_ajax_referer('nias_account_phone', 'nonce');

    if (!get_option('nias_account_phone_change')) {
        wp_send_json_error(['message' => 'امکان تغییر شماره موبایل غیرفعال است'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'ابتدا وارد حساب کاربری شوید'], 401);
    }

    $key  = 'nias_phone_change_' . $user_id;
    $data = get_transient($key);
    if (!$data || empty($data['expires_at']) || $data['expires_at'] <= current_time('timestamp')) {
        delete_transient($key);
        wp_send_json_error(['message' => 'کد منقضی شده است، لطفاً مجدداً درخواست کد دهید'], 410);
    }

    if ((int) $data['attempts'] >= 3) {
        delete_transient($key);
        wp_send_json_error(['message' => 'تعداد تلاش‌های شما بیش از حد مجاز است، مجدداً درخواست کد دهید'], 429);
    }

    $code = wp_unslash($_POST['code'] ?? '');
    if (!nias_compare_otp_codes($data['code'], $code)) {
        $data['attempts']++;
        $remaining = max($data['expires_at'] - current_time('timestamp'), 1);
        set_transient($key, $data, $remaining);
        wp_send_json_error(['message' => 'کد وارد شده اشتباه است'], 400);
    }

    // بررسی مجدد تکراری نبودن شماره (در فاصله ارسال تا تایید ممکن است ثبت شده باشد)
    $existing = nias_get_user_by_phone($data['phone']);
    if ($existing && (int) $existing->ID !== $user_id) {
        delete_transient($key);
        wp_send_json_error(['message' => 'این شماره قبلاً در سایت ثبت شده است و امکان استفاده از آن وجود ندارد'], 409);
    }

    update_user_meta($user_id, 'phone', $data['phone']);
    delete_transient($key);

    wp_send_json_success([
        'message' => 'شماره موبایل شما با موفقیت تغییر کرد',
        'phone'   => $data['phone'],
    ]);
}


// ─── همگام‌سازی billing_phone ووکامرس با متای phone نیاس ─────────────────────

/**
 * هر بار که متای phone کاربر ثبت یا تغییر کند (ثبت‌نام، تغییر شماره با کد تایید،
 * ویرایش توسط ادمین و ...)، مقدار billing_phone ووکامرس هم با آن یکسان می‌شود.
 */
add_action('added_user_meta', 'nias_sync_billing_phone_on_meta_change', 10, 4);
add_action('updated_user_meta', 'nias_sync_billing_phone_on_meta_change', 10, 4);
function nias_sync_billing_phone_on_meta_change($meta_id, $user_id, $meta_key, $meta_value)
{
    if ($meta_key !== 'phone' || !get_option('nias_billing_phone_sync')) {
        return;
    }

    if ((string) get_user_meta($user_id, 'billing_phone', true) !== (string) $meta_value) {
        update_user_meta($user_id, 'billing_phone', $meta_value);
    }
}

/**
 * همگام‌سازی تدریجی کاربران قدیمی: هنگام ورود، اگر billing_phone با phone
 * یکسان نبود، به‌روزرسانی می‌شود.
 */
add_action('wp_login', 'nias_sync_billing_phone_on_login', 10, 2);
function nias_sync_billing_phone_on_login($user_login, $user)
{
    if (!get_option('nias_billing_phone_sync') || !$user || empty($user->ID)) {
        return;
    }

    $phone = get_user_meta($user->ID, 'phone', true);
    if ($phone && (string) get_user_meta($user->ID, 'billing_phone', true) !== (string) $phone) {
        update_user_meta($user->ID, 'billing_phone', $phone);
    }
}


function nias_change_display_name($user_id)
{
    // این تابع قدیمی است، اما برای سازگاری نگه می‌داریم. حالا از nias_update_user_display_name استفاده می‌شود.
    nias_update_user_display_name($user_id);
}




// این تابع خطای عدم ورود ایمیل را هنگام آپدیت پروفایل کاربر حذف می‌کند
add_action('user_profile_update_errors', 'nias_user_profile_update_errors', 10, 3);
function nias_user_profile_update_errors($errors, $update, $user)
{
    $errors->remove('empty_email');
}

// این تابع اعتبارسنجی اجباری بودن ایمیل را در فرم‌های جاوااسکریپت حذف می‌کند
// و متن '(required)' را از برچسب حذف می‌کند
// برای فرم‌های کاربر جدید، پروفایل کاربر و ویرایش کاربر عمل می‌کند
add_action('user_new_form', 'nias_user_form_js', 10, 1);
add_action('show_user_profile', 'nias_user_form_js', 10, 1);
add_action('edit_user_profile', 'nias_user_form_js', 10, 1);
function nias_user_form_js($form_type)
{
?>
    <script type="text/javascript">
        jQuery('#email').closest('tr').removeClass('form-required').find('.description').remove();
        // به صورت پیش‌فرض گزینه ارسال ایمیل کاربر جدید را غیرفعال می‌کند
        <?php if (isset($form_type) && $form_type === 'add-new-user') : ?>
            jQuery('#send_user_notification').prop('checked', false);
        <?php endif; ?>
    </script>
<?php
}

$nias_download_problem = get_option('nias_download_problem');
if ($nias_download_problem) {

    add_action('woocommerce_thankyou', 'update_downloadable_product_permissions', 10, 1);

    function update_downloadable_product_permissions($order_id)
    {
        // دریافت جزئیات سفارش
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id(); // دریافت ID کاربر
        $site_url = parse_url(get_site_url(), PHP_URL_HOST); // دریافت فقط نام دامنه

        // بررسی اینکه آیا کاربر ایمیل ندارد
        global $wpdb;
        $table_name = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';

        // پیدا کردن رکورد کاربر بر اساس سفارش
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id)
        );

        // دریافت شماره تلفن کاربر از متا
        $user_phone = get_user_meta($user_id, 'phone', true);

        foreach ($results as $row) {
            if (empty($row->user_email)) {
                // به‌روزرسانی user_email
                $new_email = $user_phone . '@' . $site_url;
                $wpdb->update(
                    $table_name,
                    array('user_email' => $new_email), // داده‌های جدید
                    array('order_id' => $order_id) // شرط
                );
            }
        }
    }
}
function nias_process_display_name($user_id)
{
    $display_name_template = get_option('displaynamenias', 'کاربر {userid}');
    $user = get_user_by('ID', $user_id);

    $replacements = array(
        '{userid}' => $user_id,
        '{sitename}' => get_bloginfo('name'),
        '{phone}' => get_user_meta($user_id, 'phone', true),  // حالا متا ذخیره شده است
        '{email}' => $user->user_email,
        '{username}' => $user->user_login
    );

    $display_name = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $display_name_template
    );

    return $display_name;
}

/**
 *  ابتدا بررسی می‌کند آیا نام/نام خانوادگی موجود است
 */
function nias_update_user_display_name($user_id)
{
    // جلوگیری از recursion
    static $processing = false;
    if ($processing) {
        return;
    }
    $processing = true;

    $user = get_user_by('ID', $user_id);
    if (!$user) {
        $processing = false;
        return;
    }

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);

    // اگر نام یا نام خانوادگی وجود دارد، از آن استفاده کن
    if (!empty($first_name) || !empty($last_name)) {
        $display_name = '';
        if (!empty($first_name) && !empty($last_name)) {
            $display_name = $first_name . ' ' . $last_name;
        } elseif (!empty($first_name)) {
            $display_name = $first_name;
        } else {
            $display_name = $last_name;
        }

        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => trim($display_name)
        ));
    } else {
        // در غیر این صورت از تمپلیت nias استفاده کن
        $display_name = nias_process_display_name($user_id);
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));
    }

    $processing = false;
}

add_action('user_register', 'nias_update_user_display_name');
//add_action('profile_update', 'nias_update_user_display_name');



//logout redirect

/**
 * Redirect user to a custom page after logout in WordPress.
 */
function nias_login_redirect_after_logout() {
 $logout_url = get_option('nias_logout_links');

    if ( ! empty( $logout_url ) ) {
        wp_redirect( $logout_url );
        exit;
    }else{
        return;
    }
}
add_action( 'wp_logout', 'nias_login_redirect_after_logout' );


// ─── لینک خروج مستقیم ────────────────────────────────────────────────────────

/**
 * آدرس «خروج مستقیم» از سایت.
 *
 * لینک استاندارد وردپرس (wp_logout_url) یک nonce دارد که برای هر کاربر متفاوت
 * است و منقضی می‌شود؛ بنابراین نمی‌توان آن را به‌صورت ثابت روی دکمه یا منو گذاشت.
 * این آدرس برای همه کاربران یکسان است و قابل کپی کردن در هر دکمه‌ای است.
 */
function nias_login_direct_logout_url()
{
    return apply_filters('nias_login_direct_logout_url', home_url('/?nias_logout=1'));
}

/**
 * پردازش لینک خروج مستقیم.
 *
 * پس از خروج، اکشن wp_logout ریدایرکت تنظیم‌شده در «ریدایرکت بعد از خروج» را
 * اعمال می‌کند؛ اگر چیزی تنظیم نشده باشد، کاربر به صفحه اصلی هدایت می‌شود.
 */
function nias_login_handle_direct_logout()
{
    if (empty($_GET['nias_logout'])) {
        return;
    }

    if (is_user_logged_in()) {
        wp_logout();
    }

    wp_safe_redirect(home_url('/'));
    exit;
}
add_action('template_redirect', 'nias_login_handle_direct_logout', 1);


// ─── مسدودسازی دسترسی پیشخوان برای کاربران سطح پایین ─────────────────────────

/**
 * در صورت فعال بودن گزینه nias_block_dashboard_access، کاربرانی که فقط نقش
 * «مشترک» (subscriber) یا «مشتری» (customer) دارند را از دسترسی به wp-admin
 * منع کرده و به صفحه اصلی سایت هدایت می‌کند.
 */
function nias_block_dashboard_access_for_low_roles() {
    if ( ! get_option( 'nias_block_dashboard_access' ) ) {
        return;
    }

    if ( ! is_admin() ) {
        return;
    }

    if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        return;
    }

    if ( ! nias_is_low_role_blocked_user() ) {
        return;
    }

    wp_safe_redirect( home_url( '/' ) );
    exit;
}
add_action( 'admin_init', 'nias_block_dashboard_access_for_low_roles' );

/**
 * بررسی می‌کند که آیا کاربر فعلی فقط نقش «مشترک» یا «مشتری» دارد و مشمول
 * مسدودسازی است یا خیر. اگر کاربر نقش دیگری (مثل مدیر) هم داشته باشد، مشمول نیست.
 */
function nias_is_low_role_blocked_user() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user          = wp_get_current_user();
    $blocked_roles = array( 'subscriber', 'customer' );
    $extra_roles   = array_diff( (array) $user->roles, $blocked_roles );

    if ( empty( $user->roles ) || ! empty( $extra_roles ) ) {
        return false;
    }

    return true;
}

/**
 * در صورت فعال بودن گزینه nias_block_dashboard_access، علاوه بر مسدودسازی پیشخوان،
 * نوار مدیریت (Admin Bar) را برای کاربران سطح پایین در بخش پیشخوان (فرانت‌اند) پنهان می‌کند.
 */
function nias_hide_admin_bar_for_low_roles( $show ) {
    if ( ! get_option( 'nias_block_dashboard_access' ) ) {
        return $show;
    }

    if ( nias_is_low_role_blocked_user() ) {
        return false;
    }

    return $show;
}
add_filter( 'show_admin_bar', 'nias_hide_admin_bar_for_low_roles' );

/* -------------------------------------------------------------------------- */
/*                          قالب‌های پیش‌فرض مدرن ایمیل                         */
/* -------------------------------------------------------------------------- */

/**
 * قالب پیش‌فرض مدرن ایمیل کد تایید
 * متغیرها: {code} {site_name} {site_url} {email}
 */
function nias_default_email_code_template()
{
    return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;font-family:Tahoma,Arial,sans-serif;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
      <tr><td style="background:#043ccc;padding:26px 32px;text-align:center;">
        <span style="color:#ffffff;font-size:20px;font-weight:bold;">{site_name}</span>
      </td></tr>
      <tr><td style="padding:32px;text-align:center;direction:rtl;">
        <p style="margin:0 0 6px;color:#1a1a2e;font-size:17px;font-weight:bold;">کد ورود شما</p>
        <p style="margin:0 0 22px;color:#888888;font-size:13px;line-height:1.9;">برای ورود به حساب کاربری، کد زیر را وارد کنید.</p>
        <div style="display:inline-block;background:#f0f4ff;border:1px dashed #043ccc;border-radius:12px;padding:14px 30px;font-size:30px;font-weight:bold;letter-spacing:8px;color:#043ccc;direction:ltr;">{code}</div>
        <p style="margin:22px 0 0;color:#aaaaaa;font-size:12px;line-height:1.9;">این کد فقط چند دقیقه معتبر است. اگر شما این درخواست را نداده‌اید، این ایمیل را نادیده بگیرید.</p>
      </td></tr>
      <tr><td style="background:#fafafa;padding:16px 32px;text-align:center;border-top:1px solid #eeeeee;">
        <a href="{site_url}" style="color:#043ccc;font-size:12px;text-decoration:none;">{site_name}</a>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
}

/**
 * قالب پیش‌فرض مدرن ایمیل رمز عبور
 * متغیرها: {password} {username} {display_name} {login_url} {site_name} {site_url}
 */
function nias_default_email_password_template()
{
    return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;font-family:Tahoma,Arial,sans-serif;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
      <tr><td style="background:#043ccc;padding:26px 32px;text-align:center;">
        <span style="color:#ffffff;font-size:20px;font-weight:bold;">{site_name}</span>
      </td></tr>
      <tr><td style="padding:32px;text-align:center;direction:rtl;">
        <p style="margin:0 0 6px;color:#1a1a2e;font-size:17px;font-weight:bold;">{display_name} عزیز، خوش آمدید</p>
        <p style="margin:0 0 20px;color:#888888;font-size:13px;line-height:1.9;">اطلاعات ورود به حساب کاربری شما به شرح زیر است.</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;border-radius:12px;">
          <tr><td style="padding:18px 22px;text-align:right;direction:rtl;">
            <p style="margin:0 0 10px;color:#555555;font-size:13px;">نام کاربری: <strong style="color:#1a1a2e;direction:ltr;display:inline-block;">{username}</strong></p>
            <p style="margin:0;color:#555555;font-size:13px;">رمز عبور: <strong style="color:#043ccc;font-size:16px;direction:ltr;display:inline-block;letter-spacing:1px;">{password}</strong></p>
          </td></tr>
        </table>
        <a href="{login_url}" style="display:inline-block;margin:24px 0 0;background:#043ccc;color:#ffffff;text-decoration:none;padding:12px 36px;border-radius:10px;font-size:14px;font-weight:bold;">ورود به حساب کاربری</a>
        <p style="margin:22px 0 0;color:#aaaaaa;font-size:12px;line-height:1.9;">لطفاً این رمز عبور را در جایی امن نگهداری کنید و پس از ورود آن را تغییر دهید.</p>
      </td></tr>
      <tr><td style="background:#fafafa;padding:16px 32px;text-align:center;border-top:1px solid #eeeeee;">
        <a href="{site_url}" style="color:#043ccc;font-size:12px;text-decoration:none;">{site_name}</a>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
}


