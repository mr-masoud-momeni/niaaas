<?php
defined('ABSPATH') || exit;

/**
 * فهرست متن‌های قابل‌تنظیم مودال پیش‌فرض ورود.
 *
 * کلید هر آیتم نام گزینه (option) ذخیره‌شده است و مقدار آن شامل:
 * - section: گروه نمایش در تب تنظیمات ظاهری
 * - label:   عنوان فیلد در پنل ادمین
 * - default: متن پیش‌فرض (بر اساس حالت‌های فعال ایمیل/موبایل/پسورد محاسبه می‌شود)
 *
 * هم پنل ادمین (tab-design) و هم خروجی مودال (nias_login_modal) از همین
 * فهرست استفاده می‌کنند تا پیش‌فرض‌ها همیشه یکسان بمانند.
 */
function nias_login_get_modal_texts()
{
    static $texts = null;
    if ($texts !== null) {
        return $texts;
    }

    $email_activate        = get_option('nias_email_activate');
    $email_phone_activate  = get_option('nias_email_phone_activate');
    $password_activate     = get_option('nias_password_activate');
    $password_otp_activate = get_option('nias_password_otp_activate');

    if ($email_activate) {
        $field_label  = __('ایمیل خود را وارد کنید', 'nias-login-signup');
        $verify_title = __('تایید ایمیل', 'nias-login-signup');
        $change_btn   = __('اصلاح ایمیل', 'nias-login-signup');
    } elseif ($email_phone_activate) {
        $field_label  = __('ایمیل یا شماره موبایل', 'nias-login-signup');
        $verify_title = __('تایید شماره همراه/ایمیل', 'nias-login-signup');
        $change_btn   = __('اصلاح ایمیل و شماره', 'nias-login-signup');
    } else {
        $field_label  = __('شماره همراه خود را وارد کنید', 'nias-login-signup');
        $verify_title = __('تایید شماره همراه', 'nias-login-signup');
        $change_btn   = __('اصلاح شماره', 'nias-login-signup');
    }

    if ($password_otp_activate) {
        $send_btn    = __('ورود با کد تایید/رمز', 'nias-login-signup');
        $confirm_btn = __('تایید کد/رمز', 'nias-login-signup');
        $code_hint   = __('برای دریافت کد تایید روی تب باز کنید', 'nias-login-signup');
    } elseif ($password_activate) {
        $send_btn    = __('ورود با رمز', 'nias-login-signup');
        $confirm_btn = __('تایید رمز', 'nias-login-signup');
        $code_hint   = __('کد 5 رقمی ارسال شده را وارد نمایید', 'nias-login-signup');
    } else {
        $send_btn    = __('ارسال کد تایید', 'nias-login-signup');
        $confirm_btn = __('تایید کد', 'nias-login-signup');
        $code_hint   = __('کد 5 رقمی ارسال شده را وارد نمایید', 'nias-login-signup');
    }

    $texts = [
        // ---------- فرم اول ----------
        'nias_login_text_title' => [
            'section' => 'first',
            'label'   => __('عنوان مودال', 'nias-login-signup'),
            'default' => __('ورود/ثبت نام', 'nias-login-signup'),
        ],
        'nias_login_text_subtitle' => [
            'section' => 'first',
            'label'   => __('زیرعنوان مودال', 'nias-login-signup'),
            'default' => __('در صورت روشن بودن vpn خاموش کنید تا به مشکلی نخورید', 'nias-login-signup'),
        ],
        'nias_login_text_field_label' => [
            'section' => 'first',
            'label'   => __('لیبل فیلد شماره/ایمیل', 'nias-login-signup'),
            'default' => $field_label,
        ],
        'nias_login_text_phone_placeholder' => [
            'section' => 'first',
            'label'   => __('متن داخل فیلد شماره', 'nias-login-signup'),
            'default' => '0 9 - - - - - - - - -',
        ],
        'nias_login_text_country_code' => [
            'section' => 'first',
            'label'   => __('کد کشور', 'nias-login-signup'),
            'default' => '98+',
        ],
        'nias_login_text_send_button' => [
            'section' => 'first',
            'label'   => __('متن دکمه فرم اول', 'nias-login-signup'),
            'default' => $send_btn,
        ],
        'nias_login_text_close' => [
            'section' => 'first',
            'label'   => __('متن دکمه بستن', 'nias-login-signup'),
            'default' => __('بستن', 'nias-login-signup'),
        ],

        // ---------- فرم تایید ----------
        'nias_login_text_verify_title' => [
            'section' => 'verify',
            'label'   => __('عنوان فرم تایید (کد تایید)', 'nias-login-signup'),
            'default' => $verify_title,
        ],
        'nias_login_text_verify_title_password' => [
            'section' => 'verify',
            'label'   => __('عنوان فرم تایید (پسورد)', 'nias-login-signup'),
            'default' => $verify_title,
        ],
        'nias_login_text_code_hint' => [
            'section' => 'verify',
            'label'   => __('متن راهنمای کد تایید', 'nias-login-signup'),
            'default' => $code_hint,
        ],
        'nias_login_text_resend' => [
            'section' => 'verify',
            'label'   => __('متن ارسال مجدد', 'nias-login-signup'),
            'default' => __('ارسال مجدد', 'nias-login-signup'),
        ],
        'nias_login_text_confirm_button' => [
            'section' => 'verify',
            'label'   => __('متن دکمه تایید', 'nias-login-signup'),
            'default' => $confirm_btn,
        ],
        'nias_login_text_change_button' => [
            'section' => 'verify',
            'label'   => __('متن دکمه اصلاح شماره/ایمیل', 'nias-login-signup'),
            'default' => $change_btn,
        ],
        'nias_login_text_tab_password' => [
            'section' => 'verify',
            'label'   => __('عنوان تب ورود با پسورد', 'nias-login-signup'),
            'default' => __('ورود پسورد', 'nias-login-signup'),
        ],
        'nias_login_text_tab_otp' => [
            'section' => 'verify',
            'label'   => __('عنوان تب ورود با کد تایید', 'nias-login-signup'),
            'default' => __('ورود با کد تایید', 'nias-login-signup'),
        ],
        'nias_login_text_password_label' => [
            'section' => 'verify',
            'label'   => __('لیبل فیلد رمز عبور', 'nias-login-signup'),
            'default' => __('رمز عبور', 'nias-login-signup'),
        ],
        'nias_login_text_forgot' => [
            'section' => 'verify',
            'label'   => __('متن دکمه فراموشی رمز', 'nias-login-signup'),
            'default' => __('فراموشی رمز عبور', 'nias-login-signup'),
        ],
        'nias_login_text_forgot_hint' => [
            'section' => 'verify',
            'label'   => __('متن زیر دکمه فراموشی', 'nias-login-signup'),
            'default' => __('رمز عبور جدید برای شما ارسال خواهد شد', 'nias-login-signup'),
        ],

        // ---------- ساخت رمز عبور دستی ----------
        'nias_login_text_manualpass_title' => [
            'section' => 'manualpass',
            'label'   => __('عنوان ساخت رمز عبور', 'nias-login-signup'),
            'default' => __('یک رمز عبور برای حساب خود انتخاب کنید', 'nias-login-signup'),
        ],
        'nias_login_text_manualpass_new' => [
            'section' => 'manualpass',
            'label'   => __('لیبل رمز عبور جدید', 'nias-login-signup'),
            'default' => __('رمز عبور جدید', 'nias-login-signup'),
        ],
        'nias_login_text_manualpass_confirm' => [
            'section' => 'manualpass',
            'label'   => __('لیبل تکرار رمز عبور', 'nias-login-signup'),
            'default' => __('تکرار رمز عبور', 'nias-login-signup'),
        ],
        'nias_login_text_manualpass_strength' => [
            'section' => 'manualpass',
            'label'   => __('متن قدرت رمز عبور', 'nias-login-signup'),
            'default' => __('قدرت رمز عبور', 'nias-login-signup'),
        ],
        'nias_login_text_manualpass_hint' => [
            'section' => 'manualpass',
            'label'   => __('متن راهنمای رمز عبور', 'nias-login-signup'),
            'default' => __('حداقل ۸ کاراکتر، شامل حروف و اعداد', 'nias-login-signup'),
        ],

        // ---------- ورود موفق ----------
        'nias_login_text_success_title' => [
            'section' => 'success',
            'label'   => __('عنوان ورود موفق', 'nias-login-signup'),
            'default' => __('ورود با موفقیت انجام شد', 'nias-login-signup'),
        ],
        'nias_login_text_success_subtitle' => [
            'section' => 'success',
            'label'   => __('متن زیر لودر ورود موفق', 'nias-login-signup'),
            'default' => __('صفحه مجدداً بارگذاری میشود، ممنون از صبوری شما', 'nias-login-signup'),
        ],
    ];

    return $texts;
}

/**
 * متن نهایی یک بخش مودال.
 *
 * - گزینه هرگز ذخیره نشده (false) → متن پیش‌فرض
 * - گزینه عمداً خالی ذخیره شده ('') → رشته خالی؛ یعنی مدیر خواسته آن متن نمایش داده نشود
 */
function nias_login_modal_text($key)
{
    $value = get_option($key, false);
    if ($value !== false) {
        return $value;
    }

    $texts = nias_login_get_modal_texts();

    return isset($texts[$key]) ? $texts[$key]['default'] : '';
}

/**
 * خروجی HTML یک متن مودال: اگر متن خالی باشد رشته خالی برمی‌گرداند
 * تا عنصر دربرگیرنده (before/after) اصلاً رندر نشود.
 */
function nias_login_modal_text_html($key, $before = '', $after = '')
{
    $text = nias_login_modal_text($key);
    if ($text === '') {
        return '';
    }

    return $before . esc_html($text) . $after;
}
