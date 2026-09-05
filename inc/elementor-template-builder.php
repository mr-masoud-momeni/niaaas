<?php

/**
 * ساخت خودکار قالب المنتور.
 *
 * فرایند دستی (ساختن قالب، برداشتن شناسه/شورت‌کد، Paste در تنظیمات) دست‌نخورده
 * باقی می‌ماند؛ این فقط یک میان‌بر است: یک قالب ذخیره‌شده می‌سازد که ویجت مربوطه
 * از قبل داخلش گذاشته شده، شناسه‌اش را در همان تنظیم قبلی ذخیره می‌کند و کاربر را
 * مستقیم به ویرایشگر المنتور می‌فرستد.
 *
 * ساخت با API خود المنتور انجام می‌شود (Source_Local::save_item) نه با دست‌کاری
 * مستقیم متا، تا تاکسونومی نوع قالب، نسخه و ساختار داده همان چیزی باشد که
 * المنتور انتظار دارد.
 */

defined('ABSPATH') || exit;

/**
 * نگاشت نوع → ویجت و تنظیمی که شناسه در آن ذخیره می‌شود.
 *
 * @return array<string,array{widget:string,option:string,title:string}>
 */
function nias_elementor_template_types(): array
{
    return [
        'login' => [
            'widget' => 'nias_login_widget',
            'option' => 'nias_elementor_shortcode',
            'title'  => __('قالب پاپ‌آپ ورود نیاس', 'nias-login-signup'),
        ],
        'fastsell' => [
            'widget' => 'nias_fastsell_widget',
            'option' => 'nias-fastsell-shortcode',
            'title'  => __('قالب مودال خرید سریع نیاس', 'nias-login-signup'),
        ],
        'meta' => [
            'widget' => 'nias_metadata_widget',
            'option' => 'nias-elementor-meta-shortcode',
            'title'  => __('قالب متادیتای کاربر نیاس', 'nias-login-signup'),
        ],
    ];
}

/**
 * نوع سند قالب: در نسخه‌هایی که «کانتینر» فعال است container، وگرنه section.
 * ماژول کتابخانه‌ی المنتور container را مشروط ثبت می‌کند، پس نمی‌شود ثابت فرض کرد.
 */
function nias_elementor_document_type(): string
{
    if (
        isset(\Elementor\Plugin::$instance->documents)
        && method_exists(\Elementor\Plugin::$instance->documents, 'get_document_type')
        && \Elementor\Plugin::$instance->documents->get_document_type('container', false)
    ) {
        return 'container';
    }

    return 'section';
}

/** درخت المان‌ها با ویجت داده‌شده، متناسب با نوع سند */
function nias_elementor_template_content(string $widget_type, string $document_type): array
{
    $widget = [
        'id'         => substr(md5($widget_type . '-widget'), 0, 7),
        'elType'     => 'widget',
        'widgetType' => $widget_type,
        'settings'   => [],
        'elements'   => [],
    ];

    if ($document_type === 'container') {
        return [[
            'id'       => substr(md5($widget_type . '-container'), 0, 7),
            'elType'   => 'container',
            'settings' => [],
            'elements' => [$widget],
            'isInner'  => false,
        ]];
    }

    return [[
        'id'       => substr(md5($widget_type . '-section'), 0, 7),
        'elType'   => 'section',
        'settings' => [],
        'isInner'  => false,
        'elements' => [[
            'id'       => substr(md5($widget_type . '-column'), 0, 7),
            'elType'   => 'column',
            'settings' => ['_column_size' => 100, '_inline_size' => null],
            'isInner'  => false,
            'elements' => [$widget],
        ]],
    ]];
}

/**
 * قالب را می‌سازد و شناسه‌اش را در تنظیمات ذخیره می‌کند.
 *
 * @return array{id:int,edit_url:string,title:string}|WP_Error
 */
function nias_elementor_create_template(string $type)
{
    $types = nias_elementor_template_types();
    if (!isset($types[$type])) {
        return new WP_Error('nias_bad_type', __('نوع قالب نامعتبر است.', 'nias-login-signup'));
    }

    if (!nias_elementor_available() || !isset(\Elementor\Plugin::$instance->templates_manager)) {
        return new WP_Error('nias_no_elementor', __('المنتور فعال نیست.', 'nias-login-signup'));
    }

    $source = \Elementor\Plugin::$instance->templates_manager->get_source('local');
    if (!$source || !method_exists($source, 'save_item')) {
        return new WP_Error('nias_no_source', __('کتابخانه‌ی قالب‌های المنتور در دسترس نیست.', 'nias-login-signup'));
    }

    $config        = $types[$type];
    $document_type = nias_elementor_document_type();

    $template_id = $source->save_item([
        'title'   => $config['title'],
        'type'    => $document_type,
        'content' => nias_elementor_template_content($config['widget'], $document_type),
    ]);

    if (is_wp_error($template_id)) {
        return $template_id;
    }

    update_option($config['option'], (string) (int) $template_id);

    $edit_url = '';
    $document = \Elementor\Plugin::$instance->documents->get((int) $template_id);
    if ($document && method_exists($document, 'get_edit_url')) {
        $edit_url = $document->get_edit_url();
    }

    return [
        'id'       => (int) $template_id,
        'edit_url' => $edit_url,
        'title'    => $config['title'],
    ];
}

/**
 * راهنما + دکمه‌ی میان‌بر، زیر فیلد شورت‌کد/شناسه در تنظیمات.
 *
 * فیلد و فرایند دستی سر جای خودش می‌ماند؛ این فقط کنارش اضافه می‌شود.
 *
 * @param string $type       کلید nias_elementor_template_types()
 * @param string $input_id   شناسه‌ی فیلد ورودی که باید پس از ساخت پر شود
 */
function nias_elementor_template_field_extras(string $type, string $input_id): void
{
    $types = nias_elementor_template_types();
    if (!isset($types[$type])) {
        return;
    }

    // بدون المنتور، راهنمای قالب هم بی‌معنی است
    if (!nias_elementor_active()) {
        return;
    }

    $current    = (string) get_option($types[$type]['option'], '');
    $current_id = nias_elementor_template_id($current);
    $has_valid  = $current_id > 0 && nias_elementor_template_exists($current_id);
    ?>
    <span class="nias-login-field-stack__hint" style="display:block;margin-top:6px">
        <?php
        printf(
            /* translators: 1: numeric id example, 2: shortcode example */
            esc_html__('هم شناسه‌ی عددی قالب را می‌پذیرد و هم شورت‌کد را — مثلاً %1$s یا %2$s. شورت‌کد %3$s فقط در المنتور پرو وجود دارد؛ اگر پرو ندارید کافی است شناسه‌ی قالب را وارد کنید یا از دکمه‌ی زیر استفاده کنید.', 'nias-login-signup'),
            '<code dir="ltr">123</code>',
            '<code dir="ltr">[elementor-template id="123"]</code>',
            '<code dir="ltr">[elementor-template]</code>'
        );
        ?>
    </span>

    <?php if ($current !== '' && !$has_valid && nias_elementor_template_id($current) > 0): ?>
        <span class="nias-login-field-stack__hint" style="display:block;margin-top:6px;color:#b45309">
            <?php esc_html_e('قالبی با این شناسه پیدا نشد — ممکن است حذف شده باشد.', 'nias-login-signup'); ?>
        </span>
    <?php endif; ?>

    <?php if (nias_elementor_available()): ?>
        <div style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <button type="button"
                    class="button nias-elementor-create-template"
                    data-type="<?php echo esc_attr($type); ?>"
                    data-target="<?php echo esc_attr($input_id); ?>">
                <?php esc_html_e('ساخت خودکار قالب', 'nias-login-signup'); ?>
            </button>
            <span class="nias-login-field-stack__hint nias-elementor-create-template-status" style="margin:0"></span>
        </div>
        <span class="nias-login-field-stack__hint" style="display:block;margin-top:6px">
            <?php esc_html_e('یک قالب ذخیره‌شده با همین ویجت می‌سازد، شناسه‌اش را در فیلد بالا می‌گذارد و ویرایشگر المنتور را باز می‌کند. یادتان باشد بعد از آن، تنظیمات این صفحه را ذخیره کنید.', 'nias-login-signup'); ?>
        </span>
    <?php endif; ?>
    <?php
}

/** هندلر AJAX دکمه‌ی «ساخت خودکار قالب» */
function nias_elementor_ajax_create_template()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('دسترسی ندارید.', 'nias-login-signup')], 403);
    }

    check_ajax_referer('nias_elementor_create_template', 'nonce');

    $type   = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
    $result = nias_elementor_create_template($type);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success($result);
}
// روی init ثبت می‌شود چون هنگام بارگذاری فایل، المنتور هنوز بالا نیامده و
// nias_elementor_active() جواب درست نمی‌دهد. هوک wp_ajax_* بعد از init اجرا می‌شود.
add_action('init', function () {
    if (nias_elementor_active()) {
        add_action('wp_ajax_nias_elementor_create_template', 'nias_elementor_ajax_create_template');
    }
});
