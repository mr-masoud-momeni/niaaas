<?php
defined('ABSPATH') || exit;

if (!class_exists('Nias_Gateway_Registry')) {
    require_once NIAS_LOGIN_INC . 'gateways/abstract-gateway.php';
    require_once NIAS_LOGIN_INC . 'gateways/class-gateway-new-adapter.php';
    require_once NIAS_LOGIN_INC . 'gateways/class-gateway-registry.php';
}

$selected_operator = get_option('nias_operator', 'national_sms');
$total_gw          = count(Nias_Gateway_Registry::get_all());
?>
<style>
/* ── Gateway picker ───────────────────────────────────────────── */
.nias-gw-picker { position: relative; }

.nias-gw-trigger {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    width: 100%; padding: 9px 12px;
    background: var(--surface-2); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm, 8px); cursor: pointer;
    color: var(--text); font-size: 14px; font-family: inherit;
    text-align: right; transition: border-color .15s, box-shadow .15s;
}
.nias-gw-trigger:hover { border-color: var(--accent); }
.nias-gw-trigger[aria-expanded="true"] {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.nias-gw-trigger__text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: right; }
.nias-gw-trigger__count {
    font-size: 11px; color: var(--text-faint);
    background: var(--surface-3); padding: 2px 7px;
    border-radius: 99px; white-space: nowrap; flex-shrink: 0;
}
.nias-gw-chevron { width: 16px; height: 16px; flex-shrink: 0; color: var(--text-faint); transition: transform .2s; }
.nias-gw-trigger[aria-expanded="true"] .nias-gw-chevron { transform: rotate(180deg); }

.nias-gw-dropdown {
    position: fixed; z-index: 99999;
    background: var(--surface-2); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm, 8px); box-shadow: 0 10px 32px rgba(0,0,0,.35);
    display: none; overflow: hidden;
    animation: gwSlideIn .14s ease;
}
.nias-gw-dropdown.open { display: block; }
@keyframes gwSlideIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

.nias-gw-search-wrap {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-bottom: 1px solid var(--border);
}
.nias-gw-search-icon { width: 15px; height: 15px; color: var(--text-faint); flex-shrink: 0; }
.nias-gw-search-wrap input {
    flex: 1; background: transparent; border: none; outline: none;
    color: var(--text); font-size: 13px; font-family: inherit;
    padding: 0; direction: rtl;
}
.nias-gw-search-wrap input::placeholder { color: var(--text-faint); }

.nias-gw-list {
    list-style: none; margin: 0; padding: 4px 0;
    max-height: 290px; overflow-y: auto;
}
.nias-gw-list::-webkit-scrollbar { width: 5px; }
.nias-gw-list::-webkit-scrollbar-track { background: transparent; }
.nias-gw-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

.nias-gw-group {
    padding: 8px 12px 3px; font-size: 10px; color: var(--text-faint);
    font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    pointer-events: none;
}
.nias-gw-item {
    padding: 7px 12px; cursor: pointer; font-size: 13px; color: var(--text);
    transition: background .1s; display: flex; align-items: center; gap: 6px;
}
.nias-gw-item:hover, .nias-gw-item.active { background: var(--surface-3, rgba(255,255,255,.07)); }
.nias-gw-item.selected { color: var(--accent, #6366f1); font-weight: 600; }
.nias-gw-item.selected::before {
    content: ''; display: inline-block; width: 6px; height: 6px;
    border-radius: 50%; background: var(--accent); flex-shrink: 0;
}
.nias-gw-no-results {
    padding: 18px 12px; text-align: center;
    color: var(--text-faint); font-size: 13px; display: none;
}
.nias-gw-no-results.visible { display: block; }
</style>

<section id="nsoperator" class="nias-login-panel nias-login-tabcontent" data-tab="nsoperator">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M2 9h20M6 14h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('تنظیم درگاه پیامک', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('اپراتور پیامکی خود را انتخاب و اطلاعات اتصال را وارد کنید. فیلدها بر اساس درگاه انتخابی نمایش داده می‌شوند.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">

        <!-- ── Operator Picker Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php esc_html_e('انتخاب اپراتور', 'nias-login-signup'); ?></span>
                    <span class="nias-login-card__sub"><?php printf(esc_html__('از %d درگاه پیامکی پشتیبانی‌شده', 'nias-login-signup'), (int) $total_gw); ?></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias-gw-search"><?php esc_html_e('نوع اپراتور', 'nias-login-signup'); ?></label>

                    <!-- Custom searchable picker -->
                    <div class="nias-gw-picker" id="nias-gw-picker">

                        <!-- Trigger -->
                        <button type="button" class="nias-gw-trigger" id="nias-gw-trigger"
                                aria-haspopup="listbox" aria-expanded="false" aria-controls="nias-gw-dropdown">
                            <span class="nias-gw-trigger__text" id="nias-gw-label"><?php esc_html_e('در حال بارگذاری…', 'nias-login-signup'); ?></span>
                            <span class="nias-gw-trigger__count"><?php printf(esc_html__('%d درگاه', 'nias-login-signup'), (int) $total_gw); ?></span>
                            <svg class="nias-gw-chevron" viewBox="0 0 20 20" fill="none">
                                <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div class="nias-gw-dropdown" id="nias-gw-dropdown" role="listbox">
                            <div class="nias-gw-search-wrap">
                                <svg class="nias-gw-search-icon" viewBox="0 0 20 20" fill="none">
                                    <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M13 13l3.5 3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                                <input type="text" id="nias-gw-search"
                                       placeholder="<?php esc_attr_e('جستجو در نام یا آدرس درگاه…', 'nias-login-signup'); ?>"
                                       autocomplete="off" spellcheck="false">
                            </div>
                            <ul class="nias-gw-list" id="nias-gw-list" role="listbox"></ul>
                            <div class="nias-gw-no-results" id="nias-gw-no-results"><?php esc_html_e('نتیجه‌ای یافت نشد', 'nias-login-signup'); ?></div>
                        </div>

                        <!-- Hidden select – used for form POST and JS change events -->
                        <select name="nias_operator" id="nias_operator"
                                style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;overflow:hidden;"
                                aria-hidden="true" tabindex="-1">
                            <?php echo Nias_Gateway_Registry::get_options_html($selected_operator); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Connection Details Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 0-6-6m1.3 9.7a16 16 0 0 1-7-7l1.7-1.7L9 5 5 4 4 8l1.7.7a16 16 0 0 0 9.6 9.6L16 20l4-1-1-4-1.7 1.7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('اطلاعات اتصال درگاه', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('اعتبارنامه‌های سرویس پیامک', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-grid" style="gap:14px 20px">
                    <div class="nias-login-field-stack" data-gw="username"><label class="nias-login-field-stack__label" for="nias_username"><?php esc_html_e('نام کاربری', 'nias-login-signup'); ?></label><input class="nias-login-input" id="nias_username" type="text" name="nias_username" value="<?php echo esc_attr(get_option('nias_username')); ?>"></div>
                    <div class="nias-login-field-stack" data-gw="password"><label class="nias-login-field-stack__label" for="nias_password_input"><?php esc_html_e('رمز عبور درگاه', 'nias-login-signup'); ?></label><div class="nias-login-input-wrap"><input class="nias-login-input" type="password" id="nias_password_input" name="nias_password" value="<?php echo esc_attr(get_option('nias_password')); ?>"><button type="button" class="nias-login-input-eye" data-target="nias_password_input"><svg class="nias-login-eye-open" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><svg class="nias-login-eye-closed" viewBox="0 0 24 24" fill="none" hidden><path d="M17.9 17.9A10 10 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5-5.9M9.9 4.2A9 9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2m-6.7-1.1a3 3 0 1 1-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></div></div>
                    <div class="nias-login-field-stack" data-gw="localnumber"><label class="nias-login-field-stack__label" for="nias_localnumber"><?php esc_html_e('شماره ارسال‌کننده', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_localnumber" type="text" name="nias_localnumber" value="<?php echo esc_attr(get_option('nias_localnumber')); ?>"></div>
                    <div class="nias-login-field-stack" data-gw="api"><label class="nias-login-field-stack__label" for="nias_api_input"><?php esc_html_e('کلید API', 'nias-login-signup'); ?></label><div class="nias-login-input-wrap"><input class="nias-login-input nias-login-input--mono" type="password" id="nias_api_input" name="nias_api" value="<?php echo esc_attr(get_option('nias_api')); ?>"><button type="button" class="nias-login-input-eye" data-target="nias_api_input"><svg class="nias-login-eye-open" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><svg class="nias-login-eye-closed" viewBox="0 0 24 24" fill="none" hidden><path d="M17.9 17.9A10 10 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5-5.9M9.9 4.2A9 9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2m-6.7-1.1a3 3 0 1 1-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></div></div>
                    <div class="nias-login-field-stack" data-gw="pattern"><label class="nias-login-field-stack__label" id="pattern-label" for="nias_pattern"><?php esc_html_e('کد پترن', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_pattern" type="text" name="nias_pattern" value="<?php echo esc_attr(get_option('nias_pattern')); ?>"></div>
                    <div class="nias-login-field-stack" data-gw="var1"><label class="nias-login-field-stack__label" for="nias_var1"><?php esc_html_e('متغیر ۱', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_var1" type="text" name="nias_var1" value="<?php echo esc_attr(get_option('nias_var1')); ?>"></div>
                    <div class="nias-login-field-stack" data-gw="var2"><label class="nias-login-field-stack__label" for="nias_var2"><?php esc_html_e('متغیر ۲', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_var2" type="text" name="nias_var2" value="<?php echo esc_attr(get_option('nias_var2')); ?>"></div>
                    <div class="nias-login-field-stack nias-login-span-2" data-gw="smstext">
                        <label class="nias-login-field-stack__label" for="nias_sms_text"><?php printf(esc_html__('قالب پیامک متنی %s', 'nias-login-signup'), '<span style="font-weight:400;font-size:12px;color:var(--text-faint);">' . esc_html__('(برای درگاه‌های بدون پترن)', 'nias-login-signup') . '</span>'); ?></label>
                        <input class="nias-login-input" id="nias_sms_text" type="text" name="nias_sms_text" value="<?php echo esc_attr(get_option('nias_sms_text', __('کد تایید: {code}', 'nias-login-signup'))); ?>" placeholder="<?php esc_attr_e('کد تایید: {code}', 'nias-login-signup'); ?>">
                        <span style="font-size:11px;color:var(--text-faint);margin-top:4px;display:block;"><?php printf(esc_html__('از %s برای جایگذاری کد OTP استفاده کنید.', 'nias-login-signup'), '<code style="font-family:var(--mono);background:var(--surface-3);padding:1px 4px;border-radius:3px;">{code}</code>'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Password Send Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('ارسال پسورد', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('برای بازیابی رمز کاربر', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_password_pattern"><?php esc_html_e('کد پترن ارسال پسورد', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_password_pattern" type="text" name="nias_password_pattern" placeholder="<?php esc_attr_e('مثال: 100322', 'nias-login-signup'); ?>" value="<?php echo esc_attr(get_option('nias_password_pattern')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_password_var"><?php esc_html_e('متغیر پسورد', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_password_var" type="text" name="nias_password_var" placeholder="PASSWORD" value="<?php echo esc_attr(get_option('nias_password_var')); ?>"></div>
            </div>
        </div>

        <!-- ── Bale Card ── -->
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.3A8.4 8.4 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('پیام‌رسان بله', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('ارسال هم‌زمان کد در بله', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی ارسال در بله', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('کد تایید علاوه بر پیامک، در بله نیز ارسال می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_bale_activate" name="nias_bale_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_bale_activate'), 1); ?>><label for="nias_bale_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_bale_botid"><?php esc_html_e('بات آیدی بله', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" id="nias_bale_botid" type="text" name="nias_bale_botid" placeholder="@my_bot" value="<?php echo esc_attr(get_option('nias_bale_botid')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_bale_api"><?php esc_html_e('API بله', 'nias-login-signup'); ?></label><div class="nias-login-input-wrap"><input class="nias-login-input nias-login-input--mono" id="nias_bale_api" type="password" name="nias_bale_api" value="<?php echo esc_attr(get_option('nias_bale_api')); ?>"><button type="button" class="nias-login-input-eye" data-target="nias_bale_api"><svg class="nias-login-eye-open" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><svg class="nias-login-eye-closed" viewBox="0 0 24 24" fill="none" hidden><path d="M17.9 17.9A10 10 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5-5.9M9.9 4.2A9 9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2m-6.7-1.1a3 3 0 1 1-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></div></div>
            </div>
        </div>

        <!-- ── Test Card ── -->
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head">
                <div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z" stroke="currentColor" stroke-width="1.8"/></svg></div>
                <div class="nias-login-card__titles">
                    <span class="nias-login-card__title"><?php esc_html_e('تست ارسال', 'nias-login-signup'); ?></span>
                    <span class="nias-login-card__sub"><?php printf(esc_html__('ابتدا تغییرات را ذخیره کنید، سپس صحت تنظیمات درگاه را بررسی کنید — کد تست: %s', 'nias-login-signup'), '<code style="font-family:var(--mono);font-size:11px;background:var(--surface-3);padding:1px 6px;border-radius:4px;">12345</code>'); ?></span>
                </div>
            </div>
            <div class="nias-login-card__body">
                <div class="nias-login-grid" style="gap:14px 20px; margin-bottom:14px;">
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_test_phone"><?php printf(esc_html__('شماره موبایل %s', 'nias-login-signup'), '<span style="font-weight:400;font-size:12px;color:var(--text-faint);">' . esc_html__('(پیامک · بله)', 'nias-login-signup') . '</span>'); ?></label>
                        <input type="text" id="nias_test_phone" class="nias-login-input nias-login-input--mono" placeholder="09123456789" dir="ltr" />
                    </div>
                    <div class="nias-login-field-stack">
                        <label class="nias-login-field-stack__label" for="nias_test_email"><?php printf(esc_html__('ایمیل %s', 'nias-login-signup'), '<span style="font-weight:400;font-size:12px;color:var(--text-faint);">' . esc_html__('(تست ایمیل)', 'nias-login-signup') . '</span>'); ?></label>
                        <input type="email" id="nias_test_email" class="nias-login-input nias-login-input--mono" placeholder="test@example.com" dir="ltr" />
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="nias-login-btn" data-test="otp_sms"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8l1.3 3.8-3.8-1.3A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><?php esc_html_e('OTP پیامک', 'nias-login-signup'); ?></button>
                    <button type="button" class="nias-login-btn nias-login-btn--ghost" data-test="pass_sms"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e('پسورد پیامک', 'nias-login-signup'); ?></button>
                    <button type="button" class="nias-login-btn nias-login-btn--ghost" data-test="email"><svg viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="m3 6 9 6 9-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e('ایمیل', 'nias-login-signup'); ?></button>
                    <button type="button" class="nias-login-btn nias-login-btn--ghost" data-test="bale"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.3A8.4 8.4 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg><?php esc_html_e('بله', 'nias-login-signup'); ?></button>
                    <span id="nias-test-spinner" style="display:none;font-size:13px;color:var(--text-faint);"><?php esc_html_e('در حال ارسال...', 'nias-login-signup'); ?></span>
                </div>
                <div id="nias-test-result" style="display:none;margin-top:12px;"></div>
            </div>
        </div>

        <!-- ── Help Note ── -->
        <div class="nias-login-card nias-login-span-2"><div class="nias-login-card__body">
            <div class="nias-login-note"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16v-4m0-4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <div class="nias-login-note__body"><strong><?php esc_html_e('راهنمای پیکربندی درگاه‌ها:', 'nias-login-signup'); ?></strong>
                <ul>
                    <li><strong><?php esc_html_e('SMS.ir / کاوه‌نگار / فراز جدید / قاصدک و سایر درگاه‌های API‌محور:', 'nias-login-signup'); ?></strong> <?php printf(esc_html__('کلید API را در فیلد «کلید API» وارد کنید، شناسه قالب را در «کد پترن»، و نام پارامتر کد (مثل %s) را در متغیر ۱.', 'nias-login-signup'), '<code>CODE</code>'); ?></li>
                    <li><strong><?php esc_html_e('ملی پیامک / نگار پیام / پیامیتو:', 'nias-login-signup'); ?></strong> <?php esc_html_e('نام کاربری + کلید API + شناسه قالب در «کد پترن».', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('فرا پیامک:', 'nias-login-signup'); ?></strong> <?php esc_html_e('نام کاربری + رمز عبور وب‌سرویس (یا کلید API) + شناسه الگوی تاییدشده در «کد پترن». «شماره ارسال‌کننده» فقط برای پیامک‌های متن آزاد مثل وضعیت سفارش لازم است؛ ارسال کد تایید روی خط خدماتی خودِ سامانه انجام می‌شود.', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('آیپی پنل:', 'nias-login-signup'); ?></strong> <?php esc_html_e('نام کاربری + رمز + شماره ارسال‌کننده + کد پترن.', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('درگاه‌های متنی (SOAP/ساده):', 'nias-login-signup'); ?></strong> <?php esc_html_e('نام کاربری + رمز + شماره ارسال‌کننده + قالب متنی پیامک.', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('سفیرک / ایده پیام / علاءالدین مارکتینگ:', 'nias-login-signup'); ?></strong> <?php esc_html_e('هر سه روی یک سرویس کار می‌کنند: کلید API را در «کلید API» بگذارید (یا اگر کلید ندارید، نام کاربری و رمز پنل را وارد کنید)، شماره خط را در «شماره ارسال‌کننده»، شناسه الگو را در «کد پترن» و نام متغیر الگو را در «متغیر ۱» وارد کنید.', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('نکته:', 'nias-login-signup'); ?></strong> <?php esc_html_e('چنانچه فقط یک متغیر دارید، کافی است همان را در یکی از دو فیلد وارد کنید؛ مقدار آن به‌صورت خودکار برای فیلد دیگر نیز استفاده می‌شود.', 'nias-login-signup'); ?></li>
                    <li><strong><?php esc_html_e('پترن چندمتغیره (مثل فراز جدید):', 'nias-login-signup'); ?></strong> <?php printf(esc_html__('اگر قالب شما چند متغیر برای کد تایید دارد (مثلاً %1$s و %2$s)، می‌توانید همهٔ نام‌ها را در یک فیلد متغیر با کاما جدا کنید (%3$s)؛ کد تایید در همهٔ آن‌ها قرار می‌گیرد.', 'nias-login-signup'), '<code>code</code>', '<code>otp</code>', '<code>code, otp</code>'); ?></li>
                </ul></div>
            </div>
        </div></div>

    </div><!-- /.nias-login-grid -->
</section>

<?php
// Emit gateway-field map for admin-niaslogin.js field-visibility logic.
$fields_map = [];
foreach (Nias_Gateway_Registry::get_all() as $id => $gw) {
    $fields_map[$id] = $gw['fields'];
}
?>
<script>
var niasGatewayFields = <?php echo wp_json_encode($fields_map); ?>;

/* ── Searchable gateway picker ──────────────────────────────── */
(function () {
    var trigger   = document.getElementById('nias-gw-trigger');
    var dropdown  = document.getElementById('nias-gw-dropdown');
    var searchEl  = document.getElementById('nias-gw-search');
    var listEl    = document.getElementById('nias-gw-list');
    var labelEl   = document.getElementById('nias-gw-label');
    var noResults = document.getElementById('nias-gw-no-results');
    var select    = document.getElementById('nias_operator');
    var picker    = document.getElementById('nias-gw-picker');

    if (!trigger || !select) return;

    /* Build a flat array of {value, text, group} from the hidden <select>. */
    var items = [];
    Array.prototype.forEach.call(select.children, function (child) {
        if (child.tagName === 'OPTGROUP') {
            var grp = child.label;
            Array.prototype.forEach.call(child.children, function (opt) {
                items.push({ value: opt.value, text: opt.text, group: grp });
            });
        } else if (child.tagName === 'OPTION') {
            items.push({ value: child.value, text: child.text, group: '' });
        }
    });

    /* ---- Render ------------------------------------------------ */
    function render(q) {
        q = (q || '').toLowerCase();
        listEl.innerHTML = '';
        var lastGrp = null, count = 0;

        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            if (q && it.text.toLowerCase().indexOf(q) === -1 &&
                     it.value.toLowerCase().indexOf(q) === -1) continue;

            if (it.group !== lastGrp) {
                var g = document.createElement('li');
                g.className = 'nias-gw-group';
                g.textContent = it.group || '─';
                listEl.appendChild(g);
                lastGrp = it.group;
            }

            var li = document.createElement('li');
            li.className = 'nias-gw-item' + (it.value === select.value ? ' selected' : '');
            li.setAttribute('role', 'option');
            li.setAttribute('data-value', it.value);
            li.textContent = it.text;
            li.addEventListener('mousedown', function (e) {
                // mousedown fires before blur; prevent the input from losing focus
                e.preventDefault();
            });
            li.addEventListener('click', (function (item) {
                return function () {
                    select.value = item.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncLabel();
                    close();
                };
            })(it));
            listEl.appendChild(li);
            count++;
        }

        noResults.classList.toggle('visible', count === 0);
    }

    /* ---- Helpers ----------------------------------------------- */
    function syncLabel() {
        var opt = select.options[select.selectedIndex];
        if (labelEl && opt) labelEl.textContent = opt.text;
    }

    function positionDropdown() {
        var rect = trigger.getBoundingClientRect();
        dropdown.style.left  = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
        var spaceBelow = window.innerHeight - rect.bottom - 8;
        if (spaceBelow >= 180) {
            dropdown.style.top    = (rect.bottom + 5) + 'px';
            dropdown.style.bottom = 'auto';
        } else {
            dropdown.style.top    = 'auto';
            dropdown.style.bottom = (window.innerHeight - rect.top + 5) + 'px';
        }
    }

    function open() {
        positionDropdown();
        dropdown.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        searchEl.value = '';
        render('');
        searchEl.focus();
        var sel = listEl.querySelector('.selected');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    }

    function close() {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    }

    function moveHighlight(dir) {
        var els   = listEl.querySelectorAll('.nias-gw-item');
        var cur   = listEl.querySelector('.active');
        var idx   = -1;
        for (var i = 0; i < els.length; i++) { if (els[i] === cur) { idx = i; break; } }
        if (cur) cur.classList.remove('active');
        idx = dir === 1 ? Math.min(idx + 1, els.length - 1)
                        : Math.max(idx - 1, 0);
        if (els[idx]) {
            els[idx].classList.add('active');
            els[idx].scrollIntoView({ block: 'nearest' });
        }
    }

    /* ---- Events ------------------------------------------------ */
    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.contains('open') ? close() : open();
    });

    searchEl.addEventListener('input', function () {
        render(this.value);
    });

    searchEl.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown')  { e.preventDefault(); moveHighlight(1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); moveHighlight(-1); }
        else if (e.key === 'Enter') {
            var active = listEl.querySelector('.active');
            if (active) active.click();
        } else if (e.key === 'Escape') {
            close(); trigger.focus();
        }
    });

    document.addEventListener('click', function (e) {
        if (picker && !picker.contains(e.target) && !dropdown.contains(e.target)) close();
    });

    window.addEventListener('scroll', function (e) { if (dropdown.classList.contains('open') && !dropdown.contains(e.target)) close(); }, true);
    window.addEventListener('resize', function () { if (dropdown.classList.contains('open')) positionDropdown(); });

    /* ---- Init -------------------------------------------------- */
    syncLabel();
})();
</script>
