<?php
defined('ABSPATH') || exit;

/**
 * لیست پیش‌فرض فیلدهای تسویه حساب خرید سریع
 */
$nias_fastsell_default_fields = [
    'billing_first_name' => ['label' => __('نام', 'nias-login-signup'),            'required' => true,  'visible' => true],
    'billing_last_name'  => ['label' => __('نام خانوادگی', 'nias-login-signup'),   'required' => true,  'visible' => true],
    'billing_phone'      => ['label' => __('شماره موبایل', 'nias-login-signup'),   'required' => true,  'visible' => true],
    'billing_email'      => ['label' => __('ایمیل', 'nias-login-signup'),          'required' => false, 'visible' => true],
    'billing_country'    => ['label' => __('کشور', 'nias-login-signup'),           'required' => true,  'visible' => true],
    'billing_state'      => ['label' => __('استان', 'nias-login-signup'),          'required' => false, 'visible' => true],
    'billing_city'       => ['label' => __('شهر', 'nias-login-signup'),            'required' => false, 'visible' => true],
    'billing_address_1'  => ['label' => __('آدرس', 'nias-login-signup'),           'required' => false, 'visible' => true],
    'billing_address_2'  => ['label' => __('آدرس تکمیلی (واحد، طبقه و...)', 'nias-login-signup'), 'required' => false, 'visible' => false],
    'billing_postcode'   => ['label' => __('کد پستی', 'nias-login-signup'),        'required' => false, 'visible' => false],
    'billing_company'    => ['label' => __('شرکت', 'nias-login-signup'),           'required' => false, 'visible' => false],
    'order_comments'     => ['label' => __('توضیحات سفارش', 'nias-login-signup'), 'required' => false, 'visible' => true],
];

$saved_fields = get_option('nias_fastsell_checkout_fields', []);

foreach ($nias_fastsell_default_fields as $key => $defaults) {
    if (!isset($saved_fields[$key])) {
        $saved_fields[$key] = [
            'order'    => array_search($key, array_keys($nias_fastsell_default_fields)),
            'required' => $defaults['required'],
            'visible'  => $defaults['visible'],
        ];
    }
}

uasort($saved_fields, function ($a, $b) {
    return (int)($a['order'] ?? 99) - (int)($b['order'] ?? 99);
});
?>
<section id="nsfastsell" class="nias-login-panel nias-login-tabcontent" data-tab="nsfastsell">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4ZM3 6h18M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('خرید سریع (EasySale)', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('فرایند خرید را در یک مودال انجام دهید و فیلدهای تسویه حساب را مدیریت کنید.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid--1">
        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی حالت خرید سریع', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('خرید در مودال بلافاصله پس از کلیک روی «افزودن به سبد» انجام می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_quick_purchase" name="nias_quick_purchase" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_quick_purchase'), 1); ?>><label for="nias_quick_purchase" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('باز شدن مودال پس از افزودن به سبد', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('به‌صورت پیش‌فرض با کلیک روی «افزودن به سبد» مودال خرید سریع باز می‌شود. در صورت غیرفعال کردن، محصول بدون باز شدن مودال به سبد اضافه می‌شود و فقط پیام تأیید نمایش داده می‌شود؛ کاربر می‌تواند بعداً از روی آیکون سبد خرید (سلکتور پایین همین صفحه) مودال را باز کند. توجه: اگر «خرید سریع تکی» فعال است، این گزینه را روشن بگذارید وگرنه کاربر هرگز به مرحله تسویه‌حساب نمی‌رسد.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_fastsell_open_modal_after_add" value="0" />
                    <input type="checkbox" id="nias_fastsell_open_modal_after_add" name="nias_fastsell_open_modal_after_add" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_open_modal_after_add', 1), 1); ?>><label for="nias_fastsell_open_modal_after_add" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('حفظ سبد خرید تا پرداخت موفق', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('به‌صورت پیش‌فرض سبد خرید در لحظه ثبت سفارش خالی می‌شود؛ بنابراین کاربری که به درگاه می‌رود ولی پرداخت نمی‌کند، با سبد خالی برمی‌گردد. با فعال‌سازی این گزینه، محصولات تا زمانی که پرداخت سفارش انجام نشده در سبد باقی می‌مانند و به‌محض پرداخت موفق (یا وضعیت «در انتظار بررسی» برای روش‌هایی مثل پرداخت در محل و کارت به کارت) سبد به‌صورت خودکار خالی می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_fastsell_keep_cart_until_paid" value="0" />
                    <input type="checkbox" id="nias_fastsell_keep_cart_until_paid" name="nias_fastsell_keep_cart_until_paid" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_keep_cart_until_paid'), 1); ?>><label for="nias_fastsell_keep_cart_until_paid" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3M7 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('شورت‌کد خرید سریع درون صفحه', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('همان مودال، ولی به‌صورت یک بلوک داخل خود صفحه', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-codechip" dir="ltr">[nias_fastsell]</div>
                <span class="nias-login-field__hint" style="display:block;margin-top:10px"><?php esc_html_e('مودال خرید سریع در صفحه‌های سبد خرید، تسویه حساب و حساب کاربری ووکامرس نمایش داده نمی‌شود. اگر می‌خواهید فرایند خرید سریع در همان صفحه‌ها هم در دسترس باشد، این شورت‌کد را داخل محتوای صفحه قرار دهید؛ همان مراحل سبد خرید و تسویه حساب به‌صورت یک بلوک درون صفحه (بدون پوسته مودال و بدون دکمه بستن) رندر می‌شود.', 'nias-login-signup'); ?></span>
                <span class="nias-login-field__hint" style="display:block;margin-top:8px"><?php esc_html_e('نکته: در هر صفحه فقط یک بار قابل استفاده است و در صفحه‌ای که این شورت‌کد را داشته باشد، مودال شناور دیگر نمایش داده نمی‌شود. اگر آن را در صفحه تسویه حساب می‌گذارید، بهتر است فرم پیش‌فرض ووکامرس همان صفحه را حذف کنید تا دو فرم تسویه هم‌زمان روی صفحه نباشد.', 'nias-login-signup'); ?></span>
                <?php if (nias_elementor_active()) : ?>
                    <span class="nias-login-field__hint" style="display:block;margin-top:8px"><?php esc_html_e('اگر قالب اختصاصی المنتور برای مودال انتخاب کرده باشید، همان طراحی در این بلوک هم استفاده می‌شود.', 'nias-login-signup'); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.55L21 8H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('شورت‌کد دکمه افزودن به سبد', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('دکمه‌ای که مثل دکمه پیش‌فرض، محصول را به سبد اضافه و مودال خرید سریع را باز می‌کند', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-codechip" dir="ltr">[nias_fastsell_add_to_cart]</div>
                <span class="nias-login-field__hint" style="display:block;margin-top:10px"><?php printf(
                    esc_html__('در صفحه محصول بدون پارامتر استفاده کنید تا خودِ همان محصول به سبد اضافه شود. برای محصول مشخص در هر صفحه دیگری، شناسه محصول را بدهید: %s', 'nias-login-signup'),
                    '<code dir="ltr">[nias_fastsell_add_to_cart product_id="123"]</code>'
                ); ?></span>
                <span class="nias-login-field__hint" style="display:block;margin-top:8px"><?php printf(
                    esc_html__('پارامترهای اختیاری: %1$s تعداد محصول، %2$s متن دکمه و %3$s کلاس CSS دلخواه برای استایل‌دهی. نمونه کامل: %4$s', 'nias-login-signup'),
                    '<code dir="ltr">quantity</code>',
                    '<code dir="ltr">text</code>',
                    '<code dir="ltr">class</code>',
                    '<code dir="ltr">[nias_fastsell_add_to_cart product_id="123" quantity="2" text="خرید سریع" class="my-btn"]</code>'
                ); ?></span>
                <span class="nias-login-field__hint" style="display:block;margin-top:8px"><?php esc_html_e('برای محصول متغیر، اگر شورت‌کد در صفحه خود آن محصول باشد ویژگی‌های انتخاب‌شده کاربر خوانده می‌شود؛ در صفحات دیگر به‌جای دکمه، لینک به صفحه محصول نمایش داده می‌شود (چون انتخاب ویژگی‌ها ممکن نیست). محصول ناموجود هم به‌صورت دکمه غیرفعال «ناموجود» رندر می‌شود.', 'nias-login-signup'); ?></span>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('خرید سریع تکی (تک‌مرحله‌ای)', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی این گزینه، مرحله سبد خرید حذف می‌شود؛ با کلیک روی «افزودن به سبد» ابتدا سبد خرید خالی شده و فقط همین محصول به آن اضافه می‌شود، سپس مودال مستقیماً مرحله تسویه‌حساب را بدون نمایش تب مراحل نشان می‌دهد تا فرایند سریع‌تر انجام شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_fastsell_buy_now" name="nias_fastsell_buy_now" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_buy_now'), 1); ?>><label for="nias_fastsell_buy_now" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>

                <?php
                $nias_buy_now_enabled  = (int) get_option('nias_fastsell_buy_now') === 1;
                $nias_allowed_gateways = (array) get_option('nias_fastsell_buy_now_gateways', []);
                $nias_allowed_shipping = (array) get_option('nias_fastsell_buy_now_shipping', []);
                $nias_knob_svg = '<span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span>';
                ?>
                <div id="nias-fastsell-buy-now-box" class="nias-login-buynow-box" <?php echo $nias_buy_now_enabled ? '' : 'style="display:none"'; ?>>
                    <?php if (class_exists('WooCommerce') && function_exists('WC')): ?>
                        <div class="nias-login-buynow-group">
                            <span class="nias-login-field__label"><?php esc_html_e('درگاه‌های پرداخت مجاز', 'nias-login-signup'); ?></span>
                            <span class="nias-login-field__hint"><?php esc_html_e('فقط درگاه‌های فعال‌شده در مودال خرید سریع نمایش داده می‌شوند. اگر هیچ‌کدام فعال نشود، همه درگاه‌های فعال نمایش داده می‌شوند.', 'nias-login-signup'); ?></span>
                            <?php
                            $nias_wc_gateways = WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : [];
                            $nias_has_gateway = false;
                            foreach ($nias_wc_gateways as $nias_gw):
                                if ('yes' !== $nias_gw->enabled) continue;
                                $nias_has_gateway = true;
                                $nias_gw_uid = 'nias_fsbn_gw_' . preg_replace('/[^a-z0-9_]/i', '_', $nias_gw->id);
                            ?>
                                <div class="nias-login-buynow-row">
                                    <span class="nias-login-fs-name"><b><?php echo esc_html($nias_gw->get_title()); ?></b><small><?php echo esc_html($nias_gw->id); ?></small></span>
                                    <span class="nias-login-field__control">
                                        <input type="checkbox" id="<?php echo esc_attr($nias_gw_uid); ?>" name="nias_fastsell_buy_now_gateways[]" value="<?php echo esc_attr($nias_gw->id); ?>" class="nias-login-toggle-input" <?php checked(in_array($nias_gw->id, $nias_allowed_gateways, true)); ?> /><label for="<?php echo esc_attr($nias_gw_uid); ?>" class="nias-login-toggle"><?php echo $nias_knob_svg; ?></label>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$nias_has_gateway): ?>
                                <span class="nias-login-field__hint"><?php esc_html_e('هیچ درگاه پرداخت فعالی در ووکامرس یافت نشد.', 'nias-login-signup'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="nias-login-buynow-group">
                            <span class="nias-login-field__label"><?php esc_html_e('روش‌های حمل و نقل مجاز', 'nias-login-signup'); ?></span>
                            <span class="nias-login-field__hint"><?php esc_html_e('فقط روش‌های فعال‌شده در مودال خرید سریع نمایش داده می‌شوند. اگر هیچ‌کدام فعال نشود، همه روش‌های فعال نمایش داده می‌شوند.', 'nias-login-signup'); ?></span>
                            <?php
                            $nias_zones = WC_Shipping_Zones::get_zones();
                            $nias_rest_zone = new WC_Shipping_Zone(0);
                            $nias_zones[] = [
                                'zone_name'        => $nias_rest_zone->get_zone_name(),
                                'shipping_methods' => $nias_rest_zone->get_shipping_methods(),
                            ];
                            $nias_has_shipping = false;
                            foreach ($nias_zones as $nias_zone):
                                $nias_zone_methods = array_filter($nias_zone['shipping_methods'], function ($m) { return $m->is_enabled(); });
                                if (empty($nias_zone_methods)) continue;
                                $nias_has_shipping = true;
                            ?>
                                <span class="nias-login-buynow-zone"><?php echo esc_html($nias_zone['zone_name']); ?></span>
                                <?php foreach ($nias_zone_methods as $nias_method):
                                    $nias_rate_id  = $nias_method->id . ':' . $nias_method->get_instance_id();
                                    $nias_rate_uid = 'nias_fsbn_ship_' . preg_replace('/[^a-z0-9_]/i', '_', $nias_rate_id);
                                ?>
                                    <div class="nias-login-buynow-row">
                                        <span class="nias-login-fs-name"><b><?php echo esc_html($nias_method->get_title()); ?></b><small><?php echo esc_html($nias_rate_id); ?></small></span>
                                        <span class="nias-login-field__control">
                                            <input type="checkbox" id="<?php echo esc_attr($nias_rate_uid); ?>" name="nias_fastsell_buy_now_shipping[]" value="<?php echo esc_attr($nias_rate_id); ?>" class="nias-login-toggle-input" <?php checked(in_array($nias_rate_id, $nias_allowed_shipping, true)); ?> /><label for="<?php echo esc_attr($nias_rate_uid); ?>" class="nias-login-toggle"><?php echo $nias_knob_svg; ?></label>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php if (!$nias_has_shipping): ?>
                                <span class="nias-login-field__hint"><?php esc_html_e('هیچ روش حمل و نقل فعالی در ووکامرس یافت نشد.', 'nias-login-signup'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="nias-login-field__hint"><?php esc_html_e('برای انتخاب درگاه پرداخت و روش حمل و نقل، ووکامرس باید فعال باشد.', 'nias-login-signup'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('احراز قبل از تسویه حساب', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی این گزینه، کاربری که وارد حساب نشده باشد در مرحله تسویه‌حساب ابتدا فیلد شماره موبایل می‌بیند؛ کد تأیید با همان تنظیمات پیش‌فرض ارسال کد پلاگین (درگاه پیامک، تعداد ارقام و مهلت کد) برایش پیامک می‌شود و فقط پس از وارد کردن کد صحیح، فیلدهای تسویه‌حساب ووکامرس، انتخاب درگاه و دکمه پرداخت نمایش داده می‌شوند. با تأیید کد، کاربر با همان شماره وارد حساب می‌شود (اگر حساب نداشته باشد ساخته می‌شود).', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_fastsell_otp_before_checkout" value="0" />
                    <input type="checkbox" id="nias_fastsell_otp_before_checkout" name="nias_fastsell_otp_before_checkout" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_otp_before_checkout'), 1); ?>><label for="nias_fastsell_otp_before_checkout" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('نمایش گزینه «ارسال به آدرس متفاوت»', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('در صورت غیرفعال کردن، چک‌باکس و فیلدهای آدرس متفاوت برای ارسال در فرم تسویه‌حساب نمایش داده نمی‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_fastsell_show_shipping_address" value="0" />
                    <input type="checkbox" id="nias_fastsell_show_shipping_address" name="nias_fastsell_show_shipping_address" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_show_shipping_address', 1), 1); ?>><label for="nias_fastsell_show_shipping_address" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field__main" style="display:block"><span class="nias-login-field__label"><?php esc_html_e('سلکتور CSS دکمه «افزودن به سبد» سفارشی', 'nias-login-signup'); ?></span><br><span class="nias-login-field__hint"><?php printf(
                    esc_html__('به‌صورت پیش‌فرض مودال خرید سریع روی دکمه‌های پیش‌فرض ووکامرس و المنتور کار می‌کند. اگر قالب شما دکمه «افزودن به سبد» را با المان دلخواه پیاده کرده است (تگ %1$s، %2$s، %3$s و ...)، سلکتور CSS آن را اینجا وارد کنید تا افزونه روی آن هم عمل کند. برای کلاس از %4$s و برای آیدی از %5$s استفاده کنید؛ نام تنها (بدون نقطه) به‌عنوان کلاس در نظر گرفته می‌شود. چند سلکتور را با کاما جدا کنید. مثال: %6$s', 'nias-login-signup'),
                    '<code>&lt;a&gt;</code>',
                    '<code>&lt;button&gt;</code>',
                    '<code>&lt;div&gt;</code>',
                    '<code>.</code>',
                    '<code>#</code>',
                    '<code>.my-add-to-cart, #buy-now, button.theme-buy-btn</code>'
                ); ?></span></div>
                <input type="text" id="nias_fastsell_custom_add_to_cart_class" name="nias_fastsell_custom_add_to_cart_class" class="nias-login-input nias-login-input--mono" dir="ltr" placeholder=".my-add-to-cart, #buy-now" value="<?php echo esc_attr(get_option('nias_fastsell_custom_add_to_cart_class', '')); ?>" style="width:100%;margin-top:8px" />
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field__main" style="display:block"><span class="nias-login-field__label"><?php esc_html_e('سلکتور CSS باز کردن مودال (نمایش سبد خرید)', 'nias-login-signup'); ?></span><br><span class="nias-login-field__hint"><?php printf(
                    esc_html__('سلکتور CSS المان‌هایی مثل آیکون سبد خرید یا مینی‌کارت را اینجا وارد کنید. با کلیک روی آن‌ها عملیات پیش‌فرض (باز شدن مینی‌کارت یا رفتن به صفحه سبد) متوقف شده و به‌جای آن مودال خرید سریع روی مرحله سبد خرید باز می‌شود. برای کلاس از %1$s و برای آیدی از %2$s استفاده کنید؛ نام تنها (بدون نقطه) به‌عنوان کلاس در نظر گرفته می‌شود. چند سلکتور را با کاما جدا کنید. اگر این فیلد خالی باشد این قابلیت غیرفعال است. مثال: %3$s', 'nias-login-signup'),
                    '<code>.</code>',
                    '<code>#</code>',
                    '<code>.cart-icon, #mini-cart, a.header-cart</code>'
                ); ?></span></div>
                <input type="text" id="nias_fastsell_open_modal_selector" name="nias_fastsell_open_modal_selector" class="nias-login-input nias-login-input--mono" dir="ltr" placeholder=".cart-icon, #mini-cart" value="<?php echo esc_attr(get_option('nias_fastsell_open_modal_selector', '')); ?>" style="width:100%;margin-top:8px" />
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی شورت‌کد فرم سفارش سریع', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('فرمی که فقط شماره موبایل می‌گیرد؛ حساب کاربری ساخته/پیدا می‌شود، سفارش محصول ثبت شده و کاربر مستقیماً به درگاه پرداخت منتقل می‌شود (بدون صفحه تسویه‌حساب).', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_fastform_activate" value="0" />
                    <input type="checkbox" id="nias_fastform_activate" name="nias_fastform_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastform_activate'), 1); ?>><label for="nias_fastform_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>

                <div id="nias-fastform-shortcodes" class="nias-login-reveal" <?php echo get_option('nias_fastform_activate') ? '' : 'hidden'; ?> style="margin-top:12px;border-top:1px solid #eef0f5;padding-top:12px">
                    <div style="margin-bottom:14px">
                        <span class="nias-login-field__label"><?php esc_html_e('شورت‌کد فرم سفارش سریع', 'nias-login-signup'); ?></span>
                        <div class="nias-login-codechip" dir="ltr" style="margin-top:6px">[nias_fast_order]</div>
                        <span class="nias-login-field__hint" style="display:block;margin-top:6px"><?php printf(
                            esc_html__('در صفحه محصول بدون پارامتر استفاده کنید تا خودِ همان محصول سفارش شود. برای محصول مشخص در هر صفحه‌ای، شناسه را بدهید: %s', 'nias-login-signup'),
                            '<code dir="ltr">[nias_fast_order product_id="123"]</code>'
                        ); ?></span>
                    </div>
                    <div>
                        <span class="nias-login-field__label"><?php esc_html_e('شورت‌کد کارت وضعیت سفارش', 'nias-login-signup'); ?></span>
                        <div class="nias-login-codechip" dir="ltr" style="margin-top:6px">[nias_order_status]</div>
                        <span class="nias-login-field__hint" style="display:block;margin-top:6px"><?php esc_html_e('در صفحه بازگشت از درگاه قرار دهید؛ وضعیت پرداخت (موفق / ناموفق / در انتظار) را با محصولات، مبلغ و دکمه پرداخت مجدد نمایش می‌دهد.', 'nias-login-signup'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__body" style="padding-bottom:4px">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('لاگ دیباگ کنسول', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('وقتی فعال است، تمام رویدادهای AJAX در کنسول مرورگر نمایش داده می‌شوند. فقط برای عیب‌یابی فعال کنید.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_fastsell_debug_log" name="nias_fastsell_debug_log" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_debug_log'), 1); ?>><label for="nias_fastsell_debug_log" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('فیلدهای تسویه حساب', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('ترتیب را بکشید · نمایش و اجباری بودن را تنظیم کنید', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-fs-head"><span></span><span><?php esc_html_e('فیلد', 'nias-login-signup'); ?></span><span><?php esc_html_e('نمایش', 'nias-login-signup'); ?></span><span><?php esc_html_e('اجباری', 'nias-login-signup'); ?></span></div>
                <ul class="nias-login-fs-list" id="nias-fastsell-fields-sortable">
                    <?php
                    $order_index = 0;
                    foreach ($saved_fields as $field_key => $field_cfg):
                        $label    = $nias_fastsell_default_fields[$field_key]['label'] ?? $field_key;
                        $visible  = !empty($field_cfg['visible']);
                        $required = !empty($field_cfg['required']);
                        $uid      = esc_attr($field_key);
                    ?>
                    <li class="nias-login-fs-row" data-field="<?php echo $uid; ?>">
                        <span class="nias-login-fs-grip nias-fastsell-drag-handle">⠿</span>
                        <span class="nias-login-fs-name"><b><?php echo esc_html($label); ?></b><small><?php echo esc_html($field_key); ?></small></span>
                        <span class="nias-login-field__control">
                            <input type="hidden" name="nias_fastsell_checkout_fields[<?php echo $uid; ?>][visible]" value="0" />
                            <input type="checkbox" id="nias_fastsell_visible_<?php echo $uid; ?>" name="nias_fastsell_checkout_fields[<?php echo $uid; ?>][visible]" value="1" class="nias-login-toggle-input nias-fastsell-visible-cb" data-field="<?php echo $uid; ?>" <?php checked($visible, true); ?> />
                            <label for="nias_fastsell_visible_<?php echo $uid; ?>" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                        </span>
                        <span class="nias-login-field__control">
                            <input type="hidden" name="nias_fastsell_checkout_fields[<?php echo $uid; ?>][required]" value="0" />
                            <input type="checkbox" id="nias_fastsell_required_<?php echo $uid; ?>" name="nias_fastsell_checkout_fields[<?php echo $uid; ?>][required]" value="1" class="nias-login-toggle-input nias-fastsell-required-cb" data-field="<?php echo $uid; ?>" <?php checked($required, true); ?> />
                            <label for="nias_fastsell_required_<?php echo $uid; ?>" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                        </span>
                        <input type="hidden" name="nias_fastsell_checkout_fields[<?php echo $uid; ?>][order]" class="nias-fastsell-order-input" value="<?php echo esc_attr($order_index); ?>" />
                    </li>
                    <?php
                        $order_index++;
                    endforeach;
                    ?>
                </ul>
                <span class="nias-login-field__hint" style="margin-top:12px"><?php esc_html_e('* فیلدهای مخفی در فرم تسویه نمایش داده نمی‌شوند. فیلدهای اجباری باید پر شوند.', 'nias-login-signup'); ?></span>

                <div style="margin-top:16px;border-top:1px solid #eef0f5;padding-top:12px">
                    <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('نمایش خلاصه سفارش مرحله تسویه', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('ناحیه خلاصه سفارش (روش ارسال و جمع کل) بالای دکمه پرداخت در مرحله تسویه‌حساب. در صورت غیرفعال‌سازی نمایش داده نمی‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                        <input type="hidden" name="nias_fastsell_show_order_summary" value="0" />
                        <input type="checkbox" id="nias_fastsell_show_order_summary" name="nias_fastsell_show_order_summary" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_show_order_summary', 1), 1); ?>><label for="nias_fastsell_show_order_summary" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                    </span></div>

                    <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('نمایش ریز قیمت (تخفیف / جمع محصولات)', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('ناحیه ریز قیمت شامل قیمت اصلی، تخفیف محصول و تخفیف کوپن. در صورت غیرفعال‌سازی در سبد خرید و فرم تسویه نمایش داده نمی‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                        <input type="hidden" name="nias_fastsell_show_price_breakdown" value="0" />
                        <input type="checkbox" id="nias_fastsell_show_price_breakdown" name="nias_fastsell_show_price_breakdown" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_show_price_breakdown', 1), 1); ?>><label for="nias_fastsell_show_price_breakdown" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                    </span></div>

                    <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('نمایش نام روش ارسال به‌جای «رایگان»', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('وقتی هزینه روش ارسال صفر است، به‌جای کلمه «رایگان» نام همان روش در ردیف «هزینه ارسال» نوشته می‌شود؛ مناسب روش‌هایی مثل «پس‌کرایه» یا «تحویل حضوری» که هزینه‌شان هنگام تحویل دریافت می‌شود و رایگان نیستند.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                        <input type="hidden" name="nias_fastsell_free_shipping_show_method" value="0" />
                        <input type="checkbox" id="nias_fastsell_free_shipping_show_method" name="nias_fastsell_free_shipping_show_method" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_fastsell_free_shipping_show_method'), 1); ?>><label for="nias_fastsell_free_shipping_show_method" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                    </span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // نمایش شورت‌کدها فقط وقتی فرم سفارش سریع فعال است
        var toggle = document.getElementById('nias_fastform_activate');
        var box    = document.getElementById('nias-fastform-shortcodes');
        if (toggle && box) {
            toggle.addEventListener('change', function () {
                box.hidden = !toggle.checked;
            });
        }
    });
</script>
