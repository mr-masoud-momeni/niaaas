<?php
defined('ABSPATH') || exit;
// بخش‌های «ظاهر المنتوری» فقط وقتی المنتور نصب باشد نمایش داده می‌شوند؛ در غیر
// این صورت مقادیر ذخیره‌شده با فیلد مخفی حفظ می‌شوند تا با نصب دوباره‌ی المنتور
// همان طراحی قبلی برگردد (وگرنه options.php آن‌ها را با هر ذخیره خالی می‌کند)
$nias_elementor_ok = nias_elementor_active();

$is_elementory = $nias_elementor_ok && get_option('nias_desginform') === 'elementory_design';
$is_fastsell_elementory = $nias_elementor_ok && !empty(get_option('nias-fastsell-shortcode'));
// ظاهر مودال خرید سریع فقط با ووکامرس معنا دارد
$nias_wc_ok = nias_login_wc_active();
?>
<section id="nsdesignoption" class="nias-login-panel nias-login-tabcontent" data-tab="nsdesignoption">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2 2 7l10 5 10-5-10-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('تنظیمات ظاهری', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php echo $nias_elementor_ok
            ? esc_html__('ظاهر پاپ‌اپ ورود و مودال خرید سریع را با شورت‌کد المنتور سفارشی کنید.', 'nias-login-signup')
            : esc_html__('ظاهر پاپ‌اپ ورود و متن‌های آن را سفارشی کنید.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid--1">
        <?php if (!$nias_elementor_ok) : ?>
            <?php // المنتور نصب نیست: تنظیمات ظاهر المنتوری نمایش داده نمی‌شود ولی پاک هم نمی‌شود ?>
            <input type="hidden" name="nias_desginform" value="<?php echo esc_attr(get_option('nias_desginform', 'default_design')); ?>" />
            <input type="hidden" name="nias_elementor_shortcode" value="<?php echo esc_attr(get_option('nias_elementor_shortcode')); ?>" />
        <?php else : ?>
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M3 9h18" stroke="currentColor" stroke-width="1.8"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('ظاهر پاپ‌اپ ورود', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <input type="hidden" name="nias_desginform" value="default_design" />
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('استفاده از ظاهر المنتوری', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('در صورت فعال بودن، شورت‌کد المنتوری به‌جای ظاهر پیش‌فرض نمایش داده می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_desginform_toggle" name="nias_desginform" value="elementory_design" class="nias-login-toggle-input" <?php checked($is_elementory, true); ?>><label for="nias_desginform_toggle" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field-stack nias-login-reveal" id="nias-shortcode-part" <?php echo $is_elementory ? '' : 'hidden'; ?>><label class="nias-login-field-stack__label" for="nias_elementor_shortcode"><?php esc_html_e('شناسه یا شورت‌کد قالب المنتوری (قالب حتماً از نوع کانتینر یا بخش باشد)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_elementor_shortcode" name="nias_elementor_shortcode" placeholder="123" value="<?php echo esc_attr(get_option('nias_elementor_shortcode')); ?>"><?php nias_elementor_template_field_extras('login', 'nias_elementor_shortcode'); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="nias-login-card nias-login-reveal" id="nias-default-style-card" <?php echo $is_elementory ? 'hidden' : ''; ?>>
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="13.5" cy="6.5" r="1.5" fill="currentColor"/><circle cx="17.5" cy="10.5" r="1.5" fill="currentColor"/><circle cx="8.5" cy="7.5" r="1.5" fill="currentColor"/><circle cx="6.5" cy="12.5" r="1.5" fill="currentColor"/><path d="M12 22a10 10 0 1 1 10-10c0 1.66-1.34 3-3 3h-1.5a2 2 0 0 0-1 3.73A2 2 0 0 1 15 22a9 9 0 0 1-3 0Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('تنظیمات استایل پیش‌فرض پاپ‌اپ', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('این تنظیمات فقط زمانی اعمال می‌شود که ظاهر پیش‌فرض (غیرالمنتوری) فعال باشد.', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('بستن با کلیک خارج از باکس', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با کلیک روی فضای اطراف پاپ‌اپ، آن بسته می‌شود. در صفحات قفل‌شده این گزینه غیرفعال است.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_login_close_outside" name="nias_login_close_outside" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_login_close_outside'), 1); ?>><label for="nias_login_close_outside" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>

                <span class="nias-login-section-label"><?php esc_html_e('رنگ‌بندی', 'nias-login-signup'); ?></span>
                <div class="nias-login-color-grid">
                    <label class="nias-login-color-field"><span class="nias-login-color-field__label"><?php esc_html_e('رنگ اصلی (دکمه‌ها و تاکیدها)', 'nias-login-signup'); ?></span><input type="color" name="nias_login_style_primary_color" class="nias-login-color" value="<?php echo esc_attr(get_option('nias_login_style_primary_color', '#043ccc')); ?>"></label>
                    <label class="nias-login-color-field"><span class="nias-login-color-field__label"><?php esc_html_e('رنگ عنوان', 'nias-login-signup'); ?></span><input type="color" name="nias_login_style_title_color" class="nias-login-color" value="<?php echo esc_attr(get_option('nias_login_style_title_color', '#205fff')); ?>"></label>
                    <label class="nias-login-color-field"><span class="nias-login-color-field__label"><?php esc_html_e('رنگ پس‌زمینه باکس', 'nias-login-signup'); ?></span><input type="color" name="nias_login_style_bg_color" class="nias-login-color" value="<?php echo esc_attr(get_option('nias_login_style_bg_color', '#ffffff')); ?>"></label>
                    <label class="nias-login-color-field"><span class="nias-login-color-field__label"><?php esc_html_e('رنگ دکمه بستن', 'nias-login-signup'); ?></span><input type="color" name="nias_login_style_close_btn_color" class="nias-login-color" value="<?php echo esc_attr(get_option('nias_login_style_close_btn_color', '#ff0000')); ?>"></label>
                </div>

                <span class="nias-login-section-label"><?php esc_html_e('اندازه‌ها', 'nias-login-signup'); ?></span>
                <div class="nias-login-size-grid">
                    <div class="nias-login-size-field"><label for="nias_login_style_title_font_size"><?php esc_html_e('سایز فونت عنوان (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="10" max="60" id="nias_login_style_title_font_size" name="nias_login_style_title_font_size" value="<?php echo esc_attr(get_option('nias_login_style_title_font_size', 20)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_subtitle_font_size"><?php esc_html_e('سایز فونت زیرعنوان (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="8" max="40" id="nias_login_style_subtitle_font_size" name="nias_login_style_subtitle_font_size" value="<?php echo esc_attr(get_option('nias_login_style_subtitle_font_size', 13)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_font_size"><?php esc_html_e('سایز فونت فیلدها و دکمه‌ها (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="10" max="30" id="nias_login_style_font_size" name="nias_login_style_font_size" value="<?php echo esc_attr(get_option('nias_login_style_font_size', 16)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_radius"><?php esc_html_e('گردی گوشه‌های باکس (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="0" max="60" id="nias_login_style_radius" name="nias_login_style_radius" value="<?php echo esc_attr(get_option('nias_login_style_radius', 15)); ?>"></div>
                </div>

                <span class="nias-login-section-label"><?php esc_html_e('ترنسفورم لیبل فیلدها', 'nias-login-signup'); ?></span>
                <span class="nias-login-field-stack__hint"><?php esc_html_e('لیبل فیلدهای ورودی شناور است: در حالت عادی داخل فیلد قرار می‌گیرد و با کلیک یا پر شدن فیلد به بالای آن منتقل می‌شود. اگر لیبل در قالب شما بد جا افتاده، با این مقادیر جای دقیق آن را در هر دو حالت تنظیم کنید. مقدار منفی مجاز است.', 'nias-login-signup'); ?></span>

                <span class="nias-login-section-label" style="font-weight:400;opacity:.75;"><?php esc_html_e('حالت عادی (فیلد خالی)', 'nias-login-signup'); ?></span>
                <div class="nias-login-size-grid">
                    <div class="nias-login-size-field"><label for="nias_login_style_label_ty"><?php esc_html_e('جابه‌جایی عمودی Y (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="-100" max="100" step="1" id="nias_login_style_label_ty" name="nias_login_style_label_ty" value="<?php echo esc_attr(get_option('nias_login_style_label_ty', 6)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_label_tx"><?php esc_html_e('جابه‌جایی افقی X (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="-100" max="100" step="1" id="nias_login_style_label_tx" name="nias_login_style_label_tx" value="<?php echo esc_attr(get_option('nias_login_style_label_tx', 0)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_label_scale"><?php esc_html_e('بزرگ‌نمایی (scale)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="0.1" max="3" step="0.01" id="nias_login_style_label_scale" name="nias_login_style_label_scale" value="<?php echo esc_attr(get_option('nias_login_style_label_scale', 1)); ?>"></div>
                </div>

                <span class="nias-login-section-label" style="font-weight:400;opacity:.75;"><?php esc_html_e('حالت فوکوس یا پرشده (لیبل بالای فیلد)', 'nias-login-signup'); ?></span>
                <div class="nias-login-size-grid">
                    <div class="nias-login-size-field"><label for="nias_login_style_label_ty_focus"><?php esc_html_e('جابه‌جایی عمودی Y (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="-100" max="100" step="1" id="nias_login_style_label_ty_focus" name="nias_login_style_label_ty_focus" value="<?php echo esc_attr(get_option('nias_login_style_label_ty_focus', -14)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_label_tx_focus"><?php esc_html_e('جابه‌جایی افقی X (px)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="-100" max="100" step="1" id="nias_login_style_label_tx_focus" name="nias_login_style_label_tx_focus" value="<?php echo esc_attr(get_option('nias_login_style_label_tx_focus', -5)); ?>"></div>
                    <div class="nias-login-size-field"><label for="nias_login_style_label_scale_focus"><?php esc_html_e('بزرگ‌نمایی (scale)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="0.1" max="3" step="0.01" id="nias_login_style_label_scale_focus" name="nias_login_style_label_scale_focus" value="<?php echo esc_attr(get_option('nias_login_style_label_scale_focus', 0.7)); ?>"></div>
                </div>

                <?php
                // متن‌های قابل‌تنظیم مودال — منبع فهرست و پیش‌فرض‌ها: inc/modal-texts.php
                $nias_modal_texts = function_exists('nias_login_get_modal_texts') ? nias_login_get_modal_texts() : [];
                $nias_text_sections = [
                    'first'      => __('متن‌های فرم اول', 'nias-login-signup'),
                    'verify'     => __('متن‌های فرم تایید', 'nias-login-signup'),
                    'manualpass' => __('متن‌های ساخت رمز عبور', 'nias-login-signup'),
                    'success'    => __('متن‌های ورود موفق', 'nias-login-signup'),
                ];
                if (!empty($nias_modal_texts)) {
                ?>
                    <span class="nias-login-section-label"><?php esc_html_e('متن‌های مودال', 'nias-login-signup'); ?></span>
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('فیلدها با متن فعلی مودال پر شده‌اند و می‌توانید هرکدام را ویرایش کنید. اگر فیلدی را خالی کنید و ذخیره نمایید، آن متن در مودال نمایش داده نمی‌شود. برای بازگشت به پیش‌فرض، متن کم‌رنگ داخل فیلد را دوباره تایپ کنید.', 'nias-login-signup'); ?></span>
                    <?php foreach ($nias_text_sections as $nias_section_key => $nias_section_label) : ?>
                        <span class="nias-login-section-label nias-login-section-label--sub"><?php echo esc_html($nias_section_label); ?></span>
                        <div class="nias-login-text-grid">
                            <?php foreach ($nias_modal_texts as $nias_text_key => $nias_text_item) : ?>
                                <?php if ($nias_text_item['section'] !== $nias_section_key) continue; ?>
                                <div class="nias-login-text-field">
                                    <label for="<?php echo esc_attr($nias_text_key); ?>"><?php echo esc_html($nias_text_item['label']); ?></label>
                                    <input class="nias-login-input" type="text" id="<?php echo esc_attr($nias_text_key); ?>" name="<?php echo esc_attr($nias_text_key); ?>" value="<?php echo esc_attr(nias_login_modal_text($nias_text_key)); ?>" placeholder="<?php echo esc_attr($nias_text_item['default']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php } ?>
            </div>
        </div>

        <?php if (!$nias_elementor_ok || !$nias_wc_ok) : ?>
            <?php // کارت نمایش داده نمی‌شود؛ مقدار ذخیره‌شده حفظ می‌شود ?>
            <input type="hidden" name="nias-fastsell-shortcode" value="<?php echo esc_attr(get_option('nias-fastsell-shortcode', '')); ?>" />
        <?php else : ?>
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4ZM3 6h18M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('ظاهر خرید سریع (EasySale)', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('استفاده از ظاهر المنتوری', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('شورت‌کد المنتوری به‌جای مودال پیش‌فرض خرید سریع نمایش داده می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_fastsell_elementory_toggle" data-target="nias-fastsell-shortcode-design-wrapper" class="nias-login-toggle-input" <?php checked($is_fastsell_elementory, true); ?>><label for="nias_fastsell_elementory_toggle" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field-stack nias-login-reveal" id="nias-fastsell-shortcode-design-wrapper" <?php echo $is_fastsell_elementory ? '' : 'hidden'; ?>><label class="nias-login-field-stack__label" for="nias-fastsell-shortcode-design"><?php esc_html_e('شناسه یا شورت‌کد قالب المنتوری خرید سریع', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="nias-fastsell-shortcode-design" name="nias-fastsell-shortcode" placeholder="123" value="<?php echo esc_attr(get_option('nias-fastsell-shortcode', '')); ?>"><span class="nias-login-field-stack__hint"><?php esc_html_e('در صورت خالی بودن، از ظاهر پیش‌فرض استفاده می‌شود.', 'nias-login-signup'); ?></span><?php nias_elementor_template_field_extras('fastsell', 'nias-fastsell-shortcode-design'); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
