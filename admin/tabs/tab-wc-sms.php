<?php
defined('ABSPATH') || exit;

if (!class_exists('Nias_WC_Status_SMS')) {
    require_once NIAS_LOGIN_INC . 'class-wc-status-sms.php';
}

$nias_wc_active   = Nias_WC_Status_SMS::is_wc_active();
$nias_wc_settings = Nias_WC_Status_SMS::get_settings();
$nias_wc_statuses = $nias_wc_active ? Nias_WC_Status_SMS::get_statuses() : [];
$nias_wc_vars     = Nias_WC_Status_SMS::get_variables();

// اسکریپت انتهای فایل بیرون از شرطِ «ووکامرس فعال است؟» چاپ می‌شود (چون بخش
// پیامک‌های رویدادی هم به آن نیاز دارد) و این مقدار را در JSON می‌گذارد. پس باید
// همیشه تعریف شود، وگرنه با غیرفعال بودن ووکامرس صفحه‌ی تنظیمات فتال می‌دهد.
// cast به array هم برای آپشن‌های خراب/قدیمی که آرایه نیستند.
$nias_wc_custom_vars = (array) ($nias_wc_settings['custom_vars'] ?? []);

// اولین بار (تنظیماتی ذخیره نشده) → متن‌های پیش‌فرض پیشنهاد می‌شوند
$nias_wc_is_fresh = !is_array(get_option(Nias_WC_Status_SMS::OPTION, false));

// پیامک‌های رویدادی (مستقل از ووکامرس)
$nias_event_settings = class_exists('Nias_Event_SMS') ? Nias_Event_SMS::get_settings() : [];
$nias_event_base     = class_exists('Nias_Event_SMS') ? Nias_Event_SMS::OPTION : 'nias_event_sms_settings';
$nias_events_meta = [
    'welcome' => [
        'title'     => __('پیامک خوش‌آمدگویی', 'nias-login-signup'),
        'sub'       => __('هنگام ثبت‌نام کاربر جدید به شماره خودش ارسال می‌شود', 'nias-login-signup'),
        'has_admin' => false,
        'vars'      => '{display_name}، {username}، {user_phone}، {user_email}، {site_name}، {site_url}',
    ],
    'admin_login' => [
        'title'     => __('اطلاع ورود کاربر به مدیر', 'nias-login-signup'),
        'sub'       => __('هر بار که کاربری وارد می‌شود به شماره(های) مدیر ارسال می‌شود', 'nias-login-signup'),
        'has_admin' => true,
        'vars'      => '{display_name}، {username}، {user_phone}، {user_email}، {login_time}، {login_date}، {site_name}',
    ],
    'comment' => [
        'title'     => __('پیامک پس از ثبت نظر', 'nias-login-signup'),
        'sub'       => __('پس از ثبت نظر توسط کاربر به شماره خودش ارسال می‌شود', 'nias-login-signup'),
        'has_admin' => false,
        'vars'      => '{display_name}، {post_title}، {comment_excerpt}، {post_url}، {site_name}',
    ],
];
?>
<style>
.nias-wcsms-vars { display: flex; flex-wrap: wrap; gap: 6px; }
.nias-wcsms-var {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--surface-3); border: 1px solid var(--border);
    border-radius: 7px; padding: 4px 9px; cursor: pointer;
    font-size: 12px; color: var(--text); transition: border-color .15s, background .15s;
}
.nias-wcsms-var:hover { border-color: var(--accent); }
.nias-wcsms-var code { font-family: var(--mono); font-size: 11px; color: var(--accent, #6366f1); background: transparent; padding: 0; }
.nias-wcsms-var.copied { background: rgba(34,197,94,.15); border-color: #22c55e; }
.nias-wcsms-status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
@media (max-width: 1100px) { .nias-wcsms-status-grid { grid-template-columns: 1fr; } }
.nias-wcsms-block {
    border: 1px solid var(--border); border-radius: var(--radius-sm, 8px);
    padding: 12px; background: var(--surface-2);
}
.nias-wcsms-block textarea { margin-top: 10px; }
.nias-wcsms-pattern { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.nias-wcsms-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: #22c55e; color: #fff; font-size: 13px; font-weight: 600;
    padding: 10px 18px; border-radius: 9px; box-shadow: 0 6px 20px rgba(0,0,0,.18);
    display: inline-flex; align-items: center; gap: 8px; z-index: 99999;
    opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s;
}
.nias-wcsms-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.nias-wcsms-toast svg { width: 16px; height: 16px; }

/* ── ابزار سازندهٔ پترن ── */
.nias-wcsms-tool-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px 18px; }
@media (max-width: 1100px) { .nias-wcsms-tool-grid { grid-template-columns: 1fr; } }
.nias-wcsms-map { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
.nias-wcsms-map-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.nias-wcsms-map-row .nias-wcsms-token {
    font-family: var(--mono); font-size: 12px; background: var(--surface-3);
    border: 1px solid var(--border); border-radius: 6px; padding: 6px 9px;
    color: var(--accent, #6366f1); min-width: 96px; text-align: center; direction: ltr;
}
.nias-wcsms-map-row select, .nias-wcsms-map-row input { flex: 1; min-width: 150px; }
.nias-wcsms-map-sep { color: var(--text-faint); font-size: 12px; }
.nias-wcsms-preview {
    font-family: var(--mono); font-size: 12px; direction: ltr; text-align: left;
    background: var(--surface-3); border: 1px solid var(--border); border-radius: 8px;
    padding: 10px 12px; word-break: break-all; min-height: 20px; margin-top: 6px;
}
.nias-wcsms-tool-msg { font-size: 13px; margin-top: 12px; display: none; padding: 8px 12px; border-radius: 8px; }
.nias-wcsms-flash { animation: niasWcFlash 1.3s ease; }
@keyframes niasWcFlash { 0%, 100% { box-shadow: none; } 20%, 65% { box-shadow: 0 0 0 3px rgba(34,197,94,.55); } }
</style>

<section id="nswcsms" class="nias-login-panel nias-login-tabcontent" data-tab="nswcsms">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4ZM3 6h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8" stroke="currentColor" stroke-width="0" /><path d="M9 11h6M9 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('پیامک وضعیت‌های ووکامرس', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('با تغییر وضعیت هر سفارش، پیامک متنی از طریق همان درگاه تنظیم‌شده در تب «تنظیم درگاه» برای مشتری و مدیر ارسال می‌شود.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">

    <?php if (!$nias_wc_active) : ?>
        <div class="nias-login-card nias-login-span-2"><div class="nias-login-card__body">
            <div class="nias-login-note"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16v-4m0-4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <div class="nias-login-note__body"><strong><?php esc_html_e('ووکامرس فعال نیست.', 'nias-login-signup'); ?></strong> <?php esc_html_e('برای استفاده از پیامک وضعیت سفارش، ابتدا افزونه ووکامرس را نصب و فعال کنید.', 'nias-login-signup'); ?></div>
            </div>
        </div></div>
    <?php else : ?>

        <!-- ── General Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8M4.6 9a1.6 1.6 0 0 0-.3-1.8M12 2v3M12 19v3M22 12h-3M5 12H2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('تنظیمات کلی', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('فعال‌سازی و شماره‌های مدیر', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی پیامک وضعیت سفارش', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('بدون فعال بودن این گزینه هیچ پیامکی ارسال نمی‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_wc_sms_enabled" name="<?php echo Nias_WC_Status_SMS::OPTION; ?>[enabled]" value="1" class="nias-login-toggle-input" <?php checked($nias_wc_settings['enabled'], 1); ?>><label for="nias_wc_sms_enabled" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('ارسال هنگام ثبت سفارش جدید', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('برای وضعیت اولیه سفارش (مثلاً «در انتظار پرداخت») نیز پیامک ارسال شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_wc_sms_new_order" name="<?php echo Nias_WC_Status_SMS::OPTION; ?>[send_on_new_order]" value="1" class="nias-login-toggle-input" <?php checked($nias_wc_settings['send_on_new_order'], 1); ?>><label for="nias_wc_sms_new_order" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('کد رهگیری مرسوله', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php printf(esc_html__('با فعال‌سازی، در صفحهٔ ویرایش هر سفارش یک باکس برای وارد کردن کد رهگیری مرسوله نمایش داده می‌شود و متغیر %s در متن پیامک‌های وضعیت قابل استفاده می‌شود.', 'nias-login-signup'), '<code>{tracking_code}</code>'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_wc_sms_tracking" name="<?php echo Nias_WC_Status_SMS::OPTION; ?>[tracking_enabled]" value="1" class="nias-login-toggle-input" <?php checked(!empty($nias_wc_settings['tracking_enabled']), true); ?>><label for="nias_wc_sms_tracking" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_wc_sms_admin_phones"><?php esc_html_e('شماره‌(های) موبایل مدیر', 'nias-login-signup'); ?></label>
                    <input class="nias-login-input nias-login-input--mono" id="nias_wc_sms_admin_phones" type="text" dir="ltr" name="<?php echo Nias_WC_Status_SMS::OPTION; ?>[admin_phones]" placeholder="09121234567, 09351234567" value="<?php echo esc_attr($nias_wc_settings['admin_phones']); ?>">
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('چند شماره را با کاما (,) جدا کنید. پیامک‌های مدیر به همه این شماره‌ها ارسال می‌شود.', 'nias-login-signup'); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Variables Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 3H7a2 2 0 0 0-2 2v4c0 1-1 2-2 2 1 0 2 1 2 2v4a2 2 0 0 0 2 2h1M16 3h1a2 2 0 0 1 2 2v4c0 1 1 2 2 2-1 0-2 1-2 2v4a2 2 0 0 1-2 2h-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('متغیرهای قابل استفاده', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('برای کپی، روی هر متغیر کلیک کنید', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-wcsms-vars">
                    <?php foreach ($nias_wc_vars as $placeholder => $label) : ?>
                        <span class="nias-wcsms-var" data-var="<?php echo esc_attr($placeholder); ?>" title="<?php esc_attr_e('کلیک = کپی', 'nias-login-signup'); ?>"><code><?php echo esc_html($placeholder); ?></code><?php echo esc_html($label); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Custom Meta Variables Card ── -->
        <?php // $nias_wc_custom_vars بالای فایل تعریف شده ?>
        <div class="nias-login-card nias-login-span-2" id="nias-wcsms-customvars">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php esc_html_e('متغیرهای متای سفارشی', 'nias-login-signup'); ?></span>
                    <span class="nias-login-card__sub"><?php esc_html_e('برای هر «متای سفارش» یک متغیر بسازید تا در متن و پترن همه وضعیت‌ها قابل استفاده باشد', 'nias-login-signup'); ?></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <span class="nias-login-field__hint" style="margin-bottom:10px;display:block;"><?php printf(
                    esc_html__('نام متغیر (فقط حروف انگلیسی، عدد و زیرخط) و کلید متای سفارش را وارد کنید. مثلاً نام %1$s و کلید متای %2$s؛ سپس می‌توانید %3$s را در متن یا «متغیرهای پترن» هر وضعیت به کار ببرید و مقدارش از همان متای سفارش پر می‌شود. کلیدهای متای خصوصی که با زیرخط شروع می‌شوند (مثل %4$s) هم پشتیبانی می‌شوند.', 'nias-login-signup'),
                    '<code class="nias-login-mono">gift</code>',
                    '<code class="nias-login-mono">_gift_message</code>',
                    '<code class="nias-login-mono">{gift}</code>',
                    '<code class="nias-login-mono">_billing_custom_field</code>'
                ); ?></span>

                <div id="nias-wcsms-cv-list" style="display:flex;flex-direction:column;gap:10px;"></div>

                <button type="button" class="nias-login-btn nias-login-btn--ghost" id="nias-wcsms-cv-add" style="align-self:flex-start;margin-top:12px;"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><?php esc_html_e('افزودن متغیر', 'nias-login-signup'); ?></button>

                <template id="nias-wcsms-cv-template">
                    <div class="nias-wcsms-cv-row" style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;">
                        <div style="flex:1;min-width:150px;">
                            <input type="text" class="nias-login-input nias-login-input--mono nias-wcsms-cv-ph" dir="ltr" placeholder="<?php esc_attr_e('نام متغیر: gift', 'nias-login-signup'); ?>">
                        </div>
                        <div style="flex:1.4;min-width:170px;">
                            <input type="text" class="nias-login-input nias-login-input--mono nias-wcsms-cv-mk" dir="ltr" placeholder="<?php esc_attr_e('کلید متا: _gift_message', 'nias-login-signup'); ?>">
                        </div>
                        <div style="flex:1.4;min-width:170px;">
                            <input type="text" class="nias-login-input nias-wcsms-cv-lb" placeholder="<?php esc_attr_e('توضیح (اختیاری)', 'nias-login-signup'); ?>">
                        </div>
                        <button type="button" class="nias-login-btn nias-login-btn--danger nias-login-btn--ghost nias-wcsms-cv-del" style="padding:8px 12px;" title="<?php esc_attr_e('حذف', 'nias-login-signup'); ?>"><svg viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                </template>

                <div id="nias-wcsms-cv-hidden"></div>
            </div>
        </div>

        <!-- ── Test Card ── -->
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z" stroke="currentColor" stroke-width="1.8"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php esc_html_e('تست ارسال پیامک وضعیت', 'nias-login-signup'); ?></span>
                    <span class="nias-login-card__sub"><?php esc_html_e('پیامک هر وضعیت را با داده‌های یک سفارش واقعی آزمایش کنید. ابتدا تنظیمات را ذخیره کنید.', 'nias-login-signup'); ?></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-login-grid" style="gap:14px 20px; margin-bottom:14px;">
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctest_phone"><?php esc_html_e('شماره موبایل مقصد', 'nias-login-signup'); ?></label>
                        <input type="text" id="nias_wctest_phone" class="nias-login-input nias-login-input--mono" placeholder="09123456789" dir="ltr">
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctest_status"><?php esc_html_e('وضعیت سفارش', 'nias-login-signup'); ?></label>
                        <select id="nias_wctest_status" class="nias-login-input">
                            <?php foreach ($nias_wc_statuses as $slug => $label) : ?>
                                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label . ' (' . $slug . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctest_recipient"><?php esc_html_e('گیرنده', 'nias-login-signup'); ?></label>
                        <select id="nias_wctest_recipient" class="nias-login-input">
                            <option value="customer"><?php esc_html_e('پیامک مشتری', 'nias-login-signup'); ?></option>
                            <option value="admin"><?php esc_html_e('پیامک مدیر', 'nias-login-signup'); ?></option>
                        </select>
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctest_order"><?php printf(esc_html__('شناسه سفارش نمونه %s', 'nias-login-signup'), '<span style="font-weight:400;font-size:12px;color:var(--text-faint);">' . esc_html__('(اختیاری)', 'nias-login-signup') . '</span>'); ?></label>
                        <input type="text" id="nias_wctest_order" class="nias-login-input nias-login-input--mono" placeholder="<?php esc_attr_e('خالی = آخرین سفارش', 'nias-login-signup'); ?>" dir="ltr">
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="nias-login-btn" id="nias_wctest_btn"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8l1.3 3.8-3.8-1.3A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><?php esc_html_e('ارسال پیامک تست', 'nias-login-signup'); ?></button>
                    <span id="nias-wctest-spinner" style="display:none;font-size:13px;color:var(--text-faint);"><?php esc_html_e('در حال ارسال...', 'nias-login-signup'); ?></span>
                </div>
                <div id="nias-wctest-result" style="display:none;margin-top:12px;"></div>
                <span class="nias-login-field-stack__hint" style="display:block;margin-top:10px;"><?php esc_html_e('متغیرها از سفارش نمونه پر می‌شوند. این پیامک واقعی است و به شمارهٔ مقصد ارسال می‌شود.', 'nias-login-signup'); ?></span>
            </div>
        </div>

        <!-- ── Pattern Builder Tool ── -->
        <?php
        $nias_wc_var_json = [];
        foreach ($nias_wc_vars as $ph => $lbl) {
            $nias_wc_var_json[] = ['v' => $ph, 'l' => $lbl];
        }
        ?>
        <div class="nias-login-card nias-login-span-2" id="nias-wcsms-tool">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a4 4 0 0 1 5 5l-8.4 8.4-4.6 1 1-4.6 7-9.8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M12 8l4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php esc_html_e('سازندهٔ پترن (پر کردن خودکار متغیرها)', 'nias-login-signup'); ?></span>
                    <span class="nias-login-card__sub"><?php esc_html_e('متن پترن پنل پیامکی را جای‌گذاری کنید، برای هر متغیر مقدار ووکامرس را انتخاب کنید تا به‌صورت خودکار در وضعیت انتخابی درج شود.', 'nias-login-signup'); ?></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_wctool_pattern_text"><?php esc_html_e('۱) متن کامل پترن (کپی از پنل پیامکی شما)', 'nias-login-signup'); ?></label>
                    <textarea id="nias_wctool_pattern_text" class="nias-login-textarea nias-login-input--mono" rows="3" dir="ltr" placeholder="&#8235;سلام #name# سفارش شماره #order# به وضعیت #status# تغییر کرد"></textarea>
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('متغیرهای داخل # #، % % یا { } به‌صورت خودکار شناسایی می‌شوند.', 'nias-login-signup'); ?></span>
                </div>

                <div class="nias-wcsms-tool-grid" style="margin-top:12px;">
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctool_pattern_code"><?php esc_html_e('۲) کد پترن (کد قالب)', 'nias-login-signup'); ?></label>
                        <input type="text" id="nias_wctool_pattern_code" class="nias-login-input nias-login-input--mono" dir="ltr" placeholder="<?php esc_attr_e('مثال: 123456', 'nias-login-signup'); ?>">
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctool_status"><?php esc_html_e('۳) وضعیت سفارش', 'nias-login-signup'); ?></label>
                        <select id="nias_wctool_status" class="nias-login-input">
                            <?php foreach ($nias_wc_statuses as $slug => $label) : ?>
                                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label . ' (' . $slug . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctool_recipient"><?php esc_html_e('گیرنده', 'nias-login-signup'); ?></label>
                        <select id="nias_wctool_recipient" class="nias-login-input">
                            <option value="customer"><?php esc_html_e('پیامک مشتری', 'nias-login-signup'); ?></option>
                            <option value="admin"><?php esc_html_e('پیامک مدیر', 'nias-login-signup'); ?></option>
                        </select>
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_wctool_mode"><?php esc_html_e('نوع پارامترهای درگاه', 'nias-login-signup'); ?></label>
                        <select id="nias_wctool_mode" class="nias-login-input">
                            <option value="named"><?php esc_html_e('نام‌دار (SMS.ir و مشابه)', 'nias-login-signup'); ?></option>
                            <option value="sequential"><?php esc_html_e('ترتیبی (ملی‌پیامک، فراز، کاوه‌نگار)', 'nias-login-signup'); ?></option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <button type="button" class="nias-login-btn nias-login-btn--ghost" id="nias_wctool_detect"><svg viewBox="0 0 24 24" fill="none"><path d="M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14ZM21 21l-4.3-4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e('شناسایی متغیرها', 'nias-login-signup'); ?></button>
                </div>

                <div class="nias-wcsms-map" id="nias_wctool_map"></div>

                <div class="nias-login-field-stack" id="nias_wctool_preview_wrap" style="display:none;margin-top:14px;">
                    <label class="nias-login-field-stack__label"><?php esc_html_e('خروجی «متغیرهای پترن» (پیش‌نمایش)', 'nias-login-signup'); ?></label>
                    <div class="nias-wcsms-preview" id="nias_wctool_preview"></div>
                </div>

                <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="nias-login-btn" id="nias_wctool_apply"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('درج در وضعیت انتخابی', 'nias-login-signup'); ?></button>
                </div>

                <div class="nias-wcsms-tool-msg" id="nias_wctool_msg"></div>

                <div class="nias-login-note" style="margin-top:14px;"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16v-4m0-4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <div class="nias-login-note__body"><?php esc_html_e('این ابزار فقط فیلدهای «کد پترن» و «متغیرهای پترن» همان وضعیت و گیرنده را پر می‌کند و کلید ارسال آن را روشن می‌کند. برای اعمال نهایی، پس از درج حتماً روی دکمه «ذخیره تغییرات» فرم بزنید. در حالت نام‌دار، نام هر متغیر همان کلمهٔ داخل # # یا { } در نظر گرفته می‌شود.', 'nias-login-signup'); ?></div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            var WC_VARS   = <?php echo wp_json_encode($nias_wc_var_json); ?>;
            var WC_OPTION = <?php echo wp_json_encode(Nias_WC_Status_SMS::OPTION); ?>;

            var textEl   = document.getElementById('nias_wctool_pattern_text');
            var codeEl   = document.getElementById('nias_wctool_pattern_code');
            var statusEl = document.getElementById('nias_wctool_status');
            var recipEl  = document.getElementById('nias_wctool_recipient');
            var modeEl   = document.getElementById('nias_wctool_mode');
            var detectBtn= document.getElementById('nias_wctool_detect');
            var applyBtn = document.getElementById('nias_wctool_apply');
            var mapEl    = document.getElementById('nias_wctool_map');
            var prevWrap = document.getElementById('nias_wctool_preview_wrap');
            var prevEl   = document.getElementById('nias_wctool_preview');
            var msgEl    = document.getElementById('nias_wctool_msg');

            if (!textEl || !applyBtn) return;

            function showMsg(text, ok) {
                msgEl.textContent = text;
                msgEl.style.display = 'block';
                msgEl.style.background = ok ? 'rgba(52,211,153,.12)' : 'rgba(248,113,113,.12)';
                msgEl.style.color = ok ? 'var(--success, #12855b)' : 'var(--danger, #c0392b)';
            }

            /* شناسایی توکن‌های #var# ، %var% ، {var} به ترتیب ظهور و بدون تکرار */
            function detectTokens(text) {
                var re = /#\s*([^#\s]{1,40})\s*#|%\s*([^%\s]{1,40})\s*%|\{\{?\s*([^}\s]{1,40})\s*\}?\}/g;
                var m, seen = {}, out = [];
                while ((m = re.exec(text))) {
                    var name = m[1] || m[2] || m[3];
                    if (!name) continue;
                    if (seen[name]) continue;
                    seen[name] = true;
                    out.push(name);
                }
                return out;
            }

            function buildSelect(selected) {
                var s = '<select class="nias-login-input nias-wctool-map-wc">';
                s += '<option value="">— متغیر ووکامرس —</option>';
                WC_VARS.forEach(function (o) {
                    var sel = (o.v === selected) ? ' selected' : '';
                    s += '<option value="' + o.v + '"' + sel + '>' + o.v + ' — ' + o.l + '</option>';
                });
                s += '</select>';
                return s;
            }

            function renderRows(tokens) {
                mapEl.innerHTML = '';
                tokens.forEach(function (name) {
                    var row = document.createElement('div');
                    row.className = 'nias-wcsms-map-row';
                    row.setAttribute('data-name', name);
                    row.innerHTML =
                        '<span class="nias-wcsms-token" title="متغیر پترن">#' + name + '#</span>' +
                        buildSelect('') +
                        '<span class="nias-wcsms-map-sep">یا</span>' +
                        '<input type="text" class="nias-login-input nias-login-input--mono nias-wctool-map-custom" dir="ltr" placeholder="مقدار دلخواه/متن ثابت">';
                    mapEl.appendChild(row);
                });
                updatePreview();
            }

            function buildVars() {
                var mode = modeEl.value;
                var parts = [];
                mapEl.querySelectorAll('.nias-wcsms-map-row').forEach(function (row) {
                    var wc     = row.querySelector('.nias-wctool-map-wc').value;
                    var custom = row.querySelector('.nias-wctool-map-custom').value.trim();
                    var value  = custom || wc;
                    if (!value) return;
                    if (mode === 'named') {
                        parts.push(row.getAttribute('data-name') + ':' + value);
                    } else {
                        parts.push(value);
                    }
                });
                return parts.join(', ');
            }

            function updatePreview() {
                var out = buildVars();
                prevEl.textContent = out || '—';
                prevWrap.style.display = mapEl.children.length ? 'flex' : 'none';
            }

            detectBtn.addEventListener('click', function () {
                var tokens = detectTokens(textEl.value || '');
                if (!tokens.length) {
                    mapEl.innerHTML = '';
                    prevWrap.style.display = 'none';
                    showMsg('متغیری در متن پترن پیدا نشد. اگر پترن شما بدون متغیر است، فقط کد پترن را وارد و درج کنید.', false);
                    return;
                }
                renderRows(tokens);
                showMsg(tokens.length + ' متغیر شناسایی شد. برای هر کدام مقدار ووکامرس را انتخاب کنید.', true);
            });

            mapEl.addEventListener('input', updatePreview);
            mapEl.addEventListener('change', updatePreview);
            modeEl.addEventListener('change', updatePreview);

            applyBtn.addEventListener('click', function () {
                var slug      = statusEl.value;
                var recipient = recipEl.value;
                var code      = (codeEl.value || '').trim();
                var vars      = buildVars();

                if (!code) { showMsg('کد پترن را وارد کنید.', false); return; }

                var base        = WC_OPTION + '[statuses][' + slug + ']';
                var patternField= document.querySelector('[name="' + base + '[' + recipient + '_pattern]"]');
                var varsField   = document.querySelector('[name="' + base + '[' + recipient + '_pattern_vars]"]');

                if (!patternField || !varsField) {
                    showMsg('کارت این وضعیت پیدا نشد. صفحه را تازه‌سازی کنید.', false);
                    return;
                }

                patternField.value = code;
                varsField.value    = vars;

                /* روشن کردن کلید ارسال این گیرنده */
                var toggleId = 'nias_wc_' + (recipient === 'customer' ? 'c' : 'a') + '_' + slug;
                var toggle   = document.getElementById(toggleId);
                if (toggle) toggle.checked = true;

                var card = patternField.closest('.nias-login-card');
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.remove('nias-wcsms-flash');
                    void card.offsetWidth;
                    card.classList.add('nias-wcsms-flash');
                }

                showMsg('در کارت وضعیت درج شد و کلید ارسال روشن شد. برای اعمال، دکمه «ذخیره تغییرات» را بزنید.', true);
            });
        })();
        </script>

        <!-- ── Per-status Cards ── -->
        <?php foreach ($nias_wc_statuses as $slug => $label) :
            $cfg = $nias_wc_settings['statuses'][$slug] ?? [];
            $customer_text = $cfg['customer_text'] ?? ($nias_wc_is_fresh ? Nias_WC_Status_SMS::default_customer_text($slug) : '');
            $admin_text    = $cfg['admin_text']    ?? ($nias_wc_is_fresh ? Nias_WC_Status_SMS::default_admin_text($slug) : '');
            $base_name     = Nias_WC_Status_SMS::OPTION . '[statuses][' . esc_attr($slug) . ']';
        ?>
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.3A8.4 8.4 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php printf(esc_html__('وضعیت: %s', 'nias-login-signup'), esc_html($label)); ?></span>
                    <span class="nias-login-card__sub"><code style="font-family:var(--mono);font-size:11px;background:var(--surface-3);padding:1px 6px;border-radius:4px;"><?php echo esc_html($slug); ?></code></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-wcsms-status-grid">

                    <!-- Customer -->
                    <div class="nias-wcsms-block">
                        <div class="nias-login-field" style="padding:0;border:0;">
                            <div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('پیامک مشتری', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('به شماره موبایل ثبت‌شده در سفارش ارسال می‌شود.', 'nias-login-signup'); ?></span></div>
                            <span class="nias-login-field__control">
                                <input type="checkbox" id="nias_wc_c_<?php echo esc_attr($slug); ?>" name="<?php echo $base_name; ?>[customer_enabled]" value="1" class="nias-login-toggle-input" <?php checked(!empty($cfg['customer_enabled']), true); ?>><label for="nias_wc_c_<?php echo esc_attr($slug); ?>" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                            </span>
                        </div>
                        <textarea class="nias-login-textarea nias-login-input--mono nias-wcsms-textarea" rows="3" name="<?php echo $base_name; ?>[customer_text]" placeholder="<?php echo esc_attr(Nias_WC_Status_SMS::default_customer_text($slug)); ?>"><?php echo esc_textarea($customer_text); ?></textarea>
                        <div class="nias-wcsms-pattern">
                            <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo $base_name; ?>[customer_pattern]" placeholder="<?php esc_attr_e('کد پترن', 'nias-login-signup'); ?>" value="<?php echo esc_attr($cfg['customer_pattern'] ?? ''); ?>">
                            <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo $base_name; ?>[customer_pattern_vars]" placeholder="name:{billing_full_name}, order:{order_number}" value="<?php echo esc_attr($cfg['customer_pattern_vars'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Admin -->
                    <div class="nias-wcsms-block">
                        <div class="nias-login-field" style="padding:0;border:0;">
                            <div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('پیامک مدیر', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('به شماره‌های مدیر در «تنظیمات کلی» ارسال می‌شود.', 'nias-login-signup'); ?></span></div>
                            <span class="nias-login-field__control">
                                <input type="checkbox" id="nias_wc_a_<?php echo esc_attr($slug); ?>" name="<?php echo $base_name; ?>[admin_enabled]" value="1" class="nias-login-toggle-input" <?php checked(!empty($cfg['admin_enabled']), true); ?>><label for="nias_wc_a_<?php echo esc_attr($slug); ?>" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                            </span>
                        </div>
                        <textarea class="nias-login-textarea nias-login-input--mono nias-wcsms-textarea" rows="3" name="<?php echo $base_name; ?>[admin_text]" placeholder="<?php echo esc_attr(Nias_WC_Status_SMS::default_admin_text($slug)); ?>"><?php echo esc_textarea($admin_text); ?></textarea>
                        <div class="nias-wcsms-pattern">
                            <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo $base_name; ?>[admin_pattern]" placeholder="<?php esc_attr_e('کد پترن', 'nias-login-signup'); ?>" value="<?php echo esc_attr($cfg['admin_pattern'] ?? ''); ?>">
                            <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo $base_name; ?>[admin_pattern_vars]" placeholder="order:{order_number}, name:{billing_full_name}" value="<?php echo esc_attr($cfg['admin_pattern_vars'] ?? ''); ?>">
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

        <!-- ── پیامک‌های رویدادی (مستقل از ووکامرس) ── -->
        <?php foreach ($nias_events_meta as $ev_key => $ev) :
            $ev_cfg  = $nias_event_settings[$ev_key] ?? [];
            $ev_name = $nias_event_base . '[' . $ev_key . ']';
        ?>
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.3A8.4 8.4 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div>
                <div class="nias-login-card__titles"><span class="nias-login-card__title"><?php echo esc_html($ev['title']); ?></span><span class="nias-login-card__sub"><?php echo esc_html($ev['sub']); ?></span></div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-login-field" style="padding-top:0;border-top:0;"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('بدون فعال بودن این گزینه، این پیامک ارسال نمی‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_ev_<?php echo esc_attr($ev_key); ?>" name="<?php echo esc_attr($ev_name); ?>[enabled]" value="1" class="nias-login-toggle-input" <?php checked(!empty($ev_cfg['enabled']), true); ?>><label for="nias_ev_<?php echo esc_attr($ev_key); ?>" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>

                <?php if ($ev['has_admin']) : ?>
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_ev_<?php echo esc_attr($ev_key); ?>_phones"><?php esc_html_e('شماره‌(های) موبایل مدیر', 'nias-login-signup'); ?></label>
                    <input class="nias-login-input nias-login-input--mono" id="nias_ev_<?php echo esc_attr($ev_key); ?>_phones" type="text" dir="ltr" name="<?php echo esc_attr($ev_name); ?>[admin_phones]" placeholder="09121234567, 09351234567" value="<?php echo esc_attr($ev_cfg['admin_phones'] ?? ''); ?>">
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('چند شماره را با کاما (,) جدا کنید.', 'nias-login-signup'); ?></span>
                </div>
                <?php endif; ?>

                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_ev_<?php echo esc_attr($ev_key); ?>_text"><?php esc_html_e('متن پیامک', 'nias-login-signup'); ?></label>
                    <textarea class="nias-login-textarea nias-login-input--mono nias-wcsms-textarea" id="nias_ev_<?php echo esc_attr($ev_key); ?>_text" name="<?php echo esc_attr($ev_name); ?>[text]" rows="3" placeholder="<?php echo esc_attr(class_exists('Nias_Event_SMS') ? Nias_Event_SMS::default_text($ev_key) : ''); ?>"><?php echo esc_textarea($ev_cfg['text'] ?? ''); ?></textarea>
                    <?php preg_match_all('/\{[^}]+\}/', $ev['vars'], $ev_var_tokens); ?>
                    <?php if (!empty($ev_var_tokens[0])) : ?>
                    <div class="nias-wcsms-vars" style="margin-top:8px;">
                        <?php foreach ($ev_var_tokens[0] as $ev_tok) : ?>
                            <span class="nias-wcsms-var" data-var="<?php echo esc_attr($ev_tok); ?>" title="<?php esc_attr_e('کلیک = کپی', 'nias-login-signup'); ?>"><code><?php echo esc_html($ev_tok); ?></code></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="nias-wcsms-pattern">
                    <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo esc_attr($ev_name); ?>[pattern]" placeholder="<?php esc_attr_e('کد پترن', 'nias-login-signup'); ?>" value="<?php echo esc_attr($ev_cfg['pattern'] ?? ''); ?>">
                    <input class="nias-login-input nias-login-input--sm nias-login-input--mono" type="text" dir="ltr" name="<?php echo esc_attr($ev_name); ?>[pattern_vars]" placeholder="name:{display_name}, ..." value="<?php echo esc_attr($ev_cfg['pattern_vars'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div><!-- /.nias-login-grid -->
</section>

<script>
/* کپی متغیر با کلیک + درج در آخرین textarea فوکوس‌شده */
(function () {
    var lastArea = null;
    document.addEventListener('focusin', function (e) {
        if (e.target.classList && e.target.classList.contains('nias-wcsms-textarea')) lastArea = e.target;
    });

    // نوتیف «کپی شد»
    var toast = null, toastTimer = null;
    function showToast(v) {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'nias-wcsms-toast';
            document.body.appendChild(toast);
        }
        toast.innerHTML = '<svg viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg><span>«' + v + '» کپی شد</span>';
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 1500);
    }

    document.querySelectorAll('.nias-wcsms-var').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var v = chip.getAttribute('data-var');

            // درج در محل کرسر آخرین textarea فعال
            if (lastArea) {
                var s = lastArea.selectionStart || 0, e = lastArea.selectionEnd || 0;
                lastArea.value = lastArea.value.slice(0, s) + v + lastArea.value.slice(e);
                lastArea.selectionStart = lastArea.selectionEnd = s + v.length;
            }

            // کپی در کلیپ‌بورد
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(v);
            }

            chip.classList.add('copied');
            setTimeout(function () { chip.classList.remove('copied'); }, 700);

            showToast(v);
        });
    });
})();

/* مدیریت ردیف‌های «متغیرهای متای سفارشی» */
document.addEventListener('DOMContentLoaded', function () {
    var list   = document.getElementById('nias-wcsms-cv-list');
    var addBtn = document.getElementById('nias-wcsms-cv-add');
    var tpl    = document.getElementById('nias-wcsms-cv-template');
    var hidden = document.getElementById('nias-wcsms-cv-hidden');
    if (!list || !addBtn || !tpl || !hidden) return;

    var OPTION  = <?php echo wp_json_encode(Nias_WC_Status_SMS::OPTION); ?>;
    var SAVED   = <?php echo wp_json_encode(array_values($nias_wc_custom_vars)); ?>;

    function addRow(data) {
        data = data || {};
        var frag = tpl.content.cloneNode(true);
        var row  = frag.querySelector('.nias-wcsms-cv-row');
        row.querySelector('.nias-wcsms-cv-ph').value = data.placeholder || '';
        row.querySelector('.nias-wcsms-cv-mk').value = data.meta_key || '';
        row.querySelector('.nias-wcsms-cv-lb').value = data.label || '';
        row.querySelector('.nias-wcsms-cv-del').addEventListener('click', function () {
            row.remove();
            syncHidden();
        });
        list.appendChild(row);
    }

    /* فیلدهای مخفی با نام آرایه‌ای تا با ذخیرهٔ فرم تنظیمات submit شوند */
    function syncHidden() {
        hidden.innerHTML = '';
        var i = 0;
        list.querySelectorAll('.nias-wcsms-cv-row').forEach(function (row) {
            var ph = row.querySelector('.nias-wcsms-cv-ph').value.trim();
            var mk = row.querySelector('.nias-wcsms-cv-mk').value.trim();
            var lb = row.querySelector('.nias-wcsms-cv-lb').value.trim();
            if (!ph || !mk) return; // ردیف ناقص ذخیره نمی‌شود
            var base = OPTION + '[custom_vars][' + i + ']';
            hidden.appendChild(mkInput(base + '[placeholder]', ph));
            hidden.appendChild(mkInput(base + '[meta_key]', mk));
            hidden.appendChild(mkInput(base + '[label]', lb));
            i++;
        });
    }

    function mkInput(name, value) {
        var el = document.createElement('input');
        el.type = 'hidden';
        el.name = name;
        el.value = value;
        return el;
    }

    list.addEventListener('input', syncHidden);
    addBtn.addEventListener('click', function () { addRow({}); syncHidden(); });

    // پیش‌فرض: مقادیر ذخیره‌شده؛ اگر خالی بود یک ردیف خالی
    if (SAVED && SAVED.length) {
        SAVED.forEach(function (cv) { addRow(cv); });
    } else {
        addRow({});
    }
    syncHidden();

    // قبل از ارسال فرم، فیلدهای مخفی به‌روز باشند
    var settingsForm = document.querySelector('form[action="options.php"]');
    if (settingsForm) settingsForm.addEventListener('submit', syncHidden);
});

/* تست ارسال پیامک وضعیت ووکامرس */
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('nias_wctest_btn');
    if (!btn || typeof niasAdminData === 'undefined') return;

    var spin   = document.getElementById('nias-wctest-spinner');
    var result = document.getElementById('nias-wctest-result');

    function show(ok, msg) {
        var bg = ok ? 'rgba(52,211,153,.12)' : 'rgba(248,113,113,.12)';
        var cl = ok ? 'var(--success)' : 'var(--danger)';
        result.innerHTML = '<div style="padding:12px 16px;border-radius:var(--radius-sm);background:' + bg + ';color:' + cl + ';font-size:13px;">' + msg + '</div>';
        result.style.display = 'block';
    }

    btn.addEventListener('click', function () {
        var phone = (document.getElementById('nias_wctest_phone').value || '').trim();
        var status = document.getElementById('nias_wctest_status').value;
        var recipient = document.getElementById('nias_wctest_recipient').value;
        var orderId = (document.getElementById('nias_wctest_order').value || '').trim();

        if (!phone) { show(false, 'شماره موبایل را وارد کنید.'); return; }

        // پیامک تست با قوانین و پترن‌های ذخیره‌شده ساخته می‌شود، نه با آنچه در فرم
        // تایپ شده؛ پس با تغییرات ذخیره‌نشده نتیجه‌اش گمراه‌کننده است
        if (typeof window.niasSettingsDirty === 'function' && window.niasSettingsDirty('#nswcsms')) {
            show(false, 'ابتدا تغییرات را ذخیره کنید — پیامک تست با تنظیمات ذخیره‌شده ارسال می‌شود، نه مقادیری که هنوز ذخیره نکرده‌اید.');
            return;
        }

        result.style.display = 'none';
        spin.style.display = 'inline';
        btn.disabled = true;

        var body = new URLSearchParams();
        body.append('action', 'nias_test_wc_sms');
        body.append('nonce', niasAdminData.nonce);
        body.append('phone', phone);
        body.append('status', status);
        body.append('recipient', recipient);
        body.append('order_id', orderId);

        fetch(niasAdminData.ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
            .then(function (r) { return r.text(); })
            .then(function (raw) {
                var r = null;
                try { var s = raw.substring(raw.indexOf('{')); r = JSON.parse(s.substring(0, s.lastIndexOf('}') + 1)); } catch (e) {}
                if (r && r.success && r.data) {
                    show(!!r.data.ok, r.data.msg || (r.data.ok ? 'ارسال موفق ✓' : 'ارسال ناموفق'));
                } else if (r && !r.success) {
                    show(false, (r.data && r.data.msg) ? r.data.msg : (typeof r.data === 'string' ? r.data : 'خطا'));
                } else {
                    show(false, 'پاسخ نامعتبر از سرور');
                }
            })
            .catch(function () { show(false, 'خطا در ارتباط با سرور'); })
            .finally(function () { spin.style.display = 'none'; btn.disabled = false; });
    });
});
</script>
