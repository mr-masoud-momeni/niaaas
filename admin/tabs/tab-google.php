<?php
defined('ABSPATH') || exit;

$nias_google_redirect_uri = class_exists('Nias_Google_Login') ? Nias_Google_Login::redirect_uri() : admin_url('admin-ajax.php?action=nias_google_callback');
?>
<section id="nsgoogle" class="nias-login-panel nias-login-tabcontent" data-tab="nsgoogle">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('ورود با گوگل', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('ورود و ثبت‌نام کاربران با حساب گوگل (OAuth 2.0). با حساب‌های موجودی که ایمیل دارند سازگار است.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('فعال‌سازی', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی ورود با گوگل', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی، دکمه «ورود با گوگل» به‌صورت تمام‌عرض زیر دکمه‌های تایید کد و اصلاح نمایش داده می‌شود. (نیازمند تکمیل Client ID و Client Secret)', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_google_login_activate" name="nias_google_login_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_google_login_activate'), 1); ?>><label for="nias_google_login_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('اعتبارنامه‌های گوگل', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('از Google Cloud Console → APIs & Services → Credentials', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_google_client_id">Client ID</label><input class="nias-login-input nias-login-input--mono" type="text" dir="ltr" id="nias_google_client_id" name="nias_google_client_id" placeholder="xxxxxx.apps.googleusercontent.com" value="<?php echo esc_attr(get_option('nias_google_client_id')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_google_client_secret">Client Secret</label><div class="nias-login-input-wrap"><input class="nias-login-input nias-login-input--mono" type="password" dir="ltr" id="nias_google_client_secret" name="nias_google_client_secret" value="<?php echo esc_attr(get_option('nias_google_client_secret')); ?>"><button type="button" class="nias-login-input-eye" data-target="nias_google_client_secret"><svg class="nias-login-eye-open" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><svg class="nias-login-eye-closed" viewBox="0 0 24 24" fill="none" hidden><path d="M17.9 17.9A10 10 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5-5.9M9.9 4.2A9 9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2m-6.7-1.1a3 3 0 1 1-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></div></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_google_button_text"><?php esc_html_e('متن دکمه', 'nias-login-signup'); ?></label><input class="nias-login-input" type="text" id="nias_google_button_text" name="nias_google_button_text" placeholder="<?php esc_attr_e('ورود / ثبت‌نام با گوگل', 'nias-login-signup'); ?>" value="<?php echo esc_attr(get_option('nias_google_button_text')); ?>"></div>

                <hr class="nias-login-divider" />
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label">Authorized redirect URI</label>
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('این آدرس را در Google Cloud Console در بخش «Authorized redirect URIs» دقیقاً وارد کنید:', 'nias-login-signup'); ?></span>
                    <input class="nias-login-input nias-login-input--mono" type="text" dir="ltr" readonly onclick="this.select()" value="<?php echo esc_attr($nias_google_redirect_uri); ?>">
                </div>
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label">Authorized JavaScript origin</label>
                    <input class="nias-login-input nias-login-input--mono" type="text" dir="ltr" readonly onclick="this.select()" value="<?php echo esc_attr(home_url('/')); ?>">
                </div>
            </div>
        </div>
    </div>
</section>
