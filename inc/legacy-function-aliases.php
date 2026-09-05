<?php

/**
 * پوسته‌های سازگاری برای نام‌های قدیمیِ توابع عمومی.
 *
 * چرا: این توابع قبلاً بدون پیشوند تعریف می‌شدند (normalize_digits،
 * validate_phone_number، block_ip و ...). چون نام‌هایی عمومی‌اند، روی سایتی که
 * افزونه یا قالب دیگری همان نام را گرفته باشد، تعریف دوباره‌شان خطای
 * «Cannot redeclare» می‌داد و افزونه اصلاً فعال نمی‌شد. حالا همه‌ی توابع با
 * پیشوند nias_ تعریف می‌شوند و اینجا فقط برای کدهای سفارشیِ قدیمی (اسنیپت داخل
 * functions.php قالب و مشابهش) نام قبلی به نسخه‌ی جدید وصل می‌شود.
 *
 * دو نکته‌ی مهم در طراحی این فایل:
 *
 *   ۱) هر تعریف با function_exists گارد شده است؛ اگر نام قبلاً توسط کد دیگری
 *      گرفته شده باشد ما دست نمی‌زنیم و خطای Cannot redeclare رخ نمی‌دهد.
 *
 *   ۲) ثبت روی after_setup_theme با اولویت خیلی زیاد انجام می‌شود، نه در لحظه‌ی
 *      بارگذاری افزونه. در آن نقطه همه‌ی افزونه‌ها و functions.php قالب لود
 *      شده‌اند، پس هم گاردِ بالا نتیجه‌ی درست می‌دهد و هم ما زودتر از دیگران نام
 *      را نمی‌گیریم که باعث خطای آن‌ها شویم.
 *
 * منطق واقعی همیشه در نسخه‌ی nias_* است؛ این‌ها فقط پوسته‌اند.
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', 'nias_register_legacy_function_aliases', 9999);

function nias_register_legacy_function_aliases()
{
    if (!function_exists('normalize_digits')) {
        function normalize_digits($input)
        {
            return nias_normalize_digits($input);
        }
    }

    if (!function_exists('validate_otp_code')) {
        function validate_otp_code($code)
        {
            return nias_validate_otp_code($code);
        }
    }

    if (!function_exists('validate_phone_number')) {
        function validate_phone_number($phone)
        {
            return nias_validate_phone_number($phone);
        }
    }

    if (!function_exists('validate_email_normalized')) {
        function validate_email_normalized($email)
        {
            return nias_validate_email_normalized($email);
        }
    }

    if (!function_exists('validate_identifier')) {
        function validate_identifier($identifier)
        {
            return nias_validate_identifier($identifier);
        }
    }

    if (!function_exists('sanitize_phone_enhanced')) {
        function sanitize_phone_enhanced($phone)
        {
            return nias_sanitize_phone_enhanced($phone);
        }
    }

    if (!function_exists('compare_otp_codes')) {
        function compare_otp_codes($stored_code, $user_input)
        {
            return nias_compare_otp_codes($stored_code, $user_input);
        }
    }

    if (!function_exists('change_display_name')) {
        function change_display_name($user_id)
        {
            return nias_change_display_name($user_id);
        }
    }

    if (!function_exists('is_ip_blocked')) {
        function is_ip_blocked($ip)
        {
            return nias_is_ip_blocked($ip);
        }
    }

    if (!function_exists('should_block_ip')) {
        function should_block_ip($ip)
        {
            return nias_should_block_ip($ip);
        }
    }

    if (!function_exists('block_ip')) {
        function block_ip($ip)
        {
            return nias_block_ip($ip);
        }
    }

    if (!function_exists('kavenegar_lookUp')) {
        function kavenegar_lookUp($username, $code, $to, $templateName, $tokensParam)
        {
            return nias_kavenegar_lookUp($username, $code, $to, $templateName, $tokensParam);
        }
    }

    if (!function_exists('melipayamakpattern')) {
        function melipayamakpattern($username, $phone, $melipassword, $code, $bodyId)
        {
            return nias_melipayamakpattern($username, $phone, $melipassword, $code, $bodyId);
        }
    }

    if (!function_exists('farazsendsmsm')) {
        function farazsendsmsm($from, $to, $user, $pass, $pattern_code, $input_data)
        {
            return nias_farazsendsmsm($from, $to, $user, $pass, $pattern_code, $input_data);
        }
    }

    if (!function_exists('aladdin')) {
        function aladdin($fromNum, $toNum, $Content, $patternID, $Type, $token)
        {
            return nias_aladdin($fromNum, $toNum, $Content, $patternID, $Type, $token);
        }
    }
}
