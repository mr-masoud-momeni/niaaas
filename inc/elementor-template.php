<?php

/**
 * پل بین تنظیمات افزونه و قالب‌های المنتور.
 *
 * چرا لازم است: شورت‌کد [elementor-template] فقط در المنتور پرو ثبت می‌شود
 * (elementor-pro/modules/library/classes/shortcode.php). کاربر نسخه‌ی رایگان
 * می‌تواند قالب بسازد ولی هیچ شورت‌کدی برای جاسازی‌اش ندارد، پس فیلدهای
 * «شورت‌کد ویجت المنتوری» برایش بی‌استفاده بود.
 *
 * راه‌حل: تابعی که قالب را خودمان رندر می‌کنیم با
 * Plugin::$instance->frontend->get_builder_content_for_display() که در هسته‌ی
 * رایگان المنتور وجود دارد. بنابراین همان فیلد قبلی حالا هم شناسه‌ی عددی را
 * قبول می‌کند و هم شورت‌کد را — و در هر دو حالت بدون پرو کار می‌کند.
 */

defined('ABSPATH') || exit;

/** شناسه‌ی قالب را از مقدار ذخیره‌شده بیرون می‌کشد (عدد خام یا داخل شورت‌کد) */
function nias_elementor_template_id($value): int
{
    if (is_numeric($value)) {
        return (int) $value;
    }

    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }

    // [elementor-template id="123"] — با گیومه‌ی تکی، جفتی یا بدون گیومه
    if (preg_match('/\bid\s*=\s*["\']?(\d+)/i', $value, $m)) {
        return (int) $m[1];
    }

    return 0;
}

/** قالب وجود دارد و قابل نمایش است؟ */
function nias_elementor_template_exists($template_id): bool
{
    $template_id = (int) $template_id;
    if ($template_id <= 0) {
        return false;
    }

    $post = get_post($template_id);

    return $post instanceof WP_Post && !in_array($post->post_status, ['trash', 'auto-draft'], true);
}

/** المنتور فعال است و API رندر در دسترس است؟ */
function nias_elementor_available(): bool
{
    return did_action('elementor/loaded')
        && class_exists('\Elementor\Plugin')
        && isset(\Elementor\Plugin::$instance->frontend)
        && method_exists(\Elementor\Plugin::$instance->frontend, 'get_builder_content_for_display');
}

/**
 * المنتور روی سایت نصب و فعال است؟
 *
 * سبک‌تر از nias_elementor_available() است و برای تصمیم‌های رابط کاربری به کار
 * می‌رود: بخش‌های وابسته به المنتور وقتی نصب نیست اصلاً نمایش داده نمی‌شوند.
 * (class_exists به‌عنوان پشتیبان است تا اگر جایی پیش از هوک elementor/loaded
 * صدا زده شد هم جواب درست بدهد.)
 */
function nias_elementor_active(): bool
{
    return did_action('elementor/loaded') > 0 || class_exists('\Elementor\Plugin');
}

/**
 * آیا باید از قالب ذخیره‌شده در این تنظیم برای رندر استفاده شود؟
 *
 *   خالی                                → نه؛ ظاهر پیش‌فرض افزونه
 *   شناسه قالب / [elementor-template]   → فقط اگر المنتور فعال باشد
 *   شورت‌کد دیگر (بی‌ربط به المنتور)     → بله؛ به المنتور نیازی ندارد
 *
 * با این کار، غیرفعال شدن المنتور به‌جای یک ناحیه‌ی خالی، ظاهر پیش‌فرض را
 * برمی‌گرداند و مقدار ذخیره‌شده هم دست‌نخورده می‌ماند تا با نصب دوباره‌ی
 * المنتور همان طراحی قبلی برگردد.
 *
 * @param string|int $value مقدار ذخیره‌شده در تنظیمات
 */
function nias_elementor_design_active($value): bool
{
    $value = is_scalar($value) ? trim((string) $value) : '';
    if ($value === '') {
        return false;
    }

    if (nias_elementor_template_id($value) > 0) {
        return nias_elementor_active();
    }

    return true;
}

/**
 * ظاهر مؤثر مودال ورود — با درنظر گرفتن نبودِ المنتور.
 * اگر «طراحی با المنتور» انتخاب شده ولی المنتور نصب نباشد، ظاهر پیش‌فرض برمی‌گردد.
 */
function nias_login_active_design(): string
{
    $design = get_option('nias_desginform', 'default_design');

    if ($design === 'elementory_design' && !nias_elementor_design_active(get_option('nias_elementor_shortcode'))) {
        return 'default_design';
    }

    return $design;
}

/**
 * خروجی قالب المنتوری تنظیم‌شده را برمی‌گرداند.
 *
 * ترتیب تلاش:
 *   ۱) شناسه (چه عدد خام باشد چه از داخل شورت‌کد استخراج شده) → رندر مستقیم
 *      با API هسته‌ی رایگان؛
 *   ۲) اگر شناسه‌ای پیدا نشد یا قالب حذف شده بود → do_shortcode روی همان مقدار
 *      تا شورت‌کدهای غیرالمنتوری و رفتار قدیمی نشکند.
 *
 * @param string|int $value مقدار ذخیره‌شده در تنظیمات
 */
function nias_elementor_render_template($value): string
{
    $value = is_scalar($value) ? trim((string) $value) : '';
    if ($value === '') {
        return '';
    }

    $template_id = nias_elementor_template_id($value);

    if ($template_id > 0 && !nias_elementor_active()) {
        // شناسه‌ی قالب المنتور بدون المنتور قابل رندر نیست؛ رفتن سراغ do_shortcode
        // باعث می‌شد همان عدد خام وسط صفحه چاپ شود
        return '';
    }

    if ($template_id > 0 && nias_elementor_template_exists($template_id) && nias_elementor_available()) {
        $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id, true);
    } else {
        // مقدارهایی که شناسه ندارند (شورت‌کد افزونه‌ی دیگر) یا قالب پاک‌شده
        $html = do_shortcode($value);
    }

    return (string) $html;
}

/**
 * قالب‌های ذخیره‌شده‌ی المنتور برای نمایش در تنظیمات.
 *
 * @return array<int,string> شناسه => عنوان
 */
function nias_elementor_saved_templates(): array
{
    if (!post_type_exists('elementor_library')) {
        return [];
    }

    $posts = get_posts([
        'post_type'        => 'elementor_library',
        'post_status'      => ['publish', 'draft', 'private', 'pending'],
        'numberposts'      => 200,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => false,
    ]);

    $out = [];
    foreach ($posts as $post) {
        $out[(int) $post->ID] = $post->post_title !== '' ? $post->post_title : ('#' . $post->ID);
    }

    return $out;
}
