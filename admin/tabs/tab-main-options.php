<?php
defined('ABSPATH') || exit;
$show_password_structure = get_option('nias_password_activate') || get_option('nias_password_otp_activate');
$preview_prefix = get_option('nias_password_prefix', '');
?>
<section id="nsmainoption" class="nias-login-panel nias-login-tabcontent" data-tab="nsmainoption">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8M4.6 9a1.6 1.6 0 0 0-.3-1.8M12 2v3M12 19v3M22 12h-3M5 12H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('تنظیم عملکرد', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('رفتار اصلی ورود، ساختار پسورد، نام کاربری و ریدایرکت‌ها.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('پسورد', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی پسورد', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_password_activate" name="nias_password_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_password_activate'), 1); ?>><label for="nias_password_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('پسورد به‌همراه کد تایید', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_password_otp_activate" name="nias_password_otp_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_password_otp_activate'), 1); ?>><label for="nias_password_otp_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('ثبت پسورد دستی توسط کاربر', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی، فقط برای کاربران جدید در مرحله دوم (تب پسورد) فیلدهای انتخاب رمز عبور به‌همراه سنجش قدرت رمز نمایش داده می‌شود و رمز انتخابی به‌عنوان رمز حساب ثبت می‌گردد. این بخش هرگز برای کاربرانی که از قبل حساب دارند نمایش داده نمی‌شود. نیازمند فعال بودن «پسورد» یا «پسورد به‌همراه کد تایید».', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_manual_password_activate" name="nias_manual_password_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_manual_password_activate'), 1); ?>><label for="nias_manual_password_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>

                <div class="nias-login-subpanel nias-login-reveal" id="nias-password-structure-section" <?php echo $show_password_structure ? '' : 'hidden'; ?>>
                    <h4 class="nias-login-subpanel__title"><?php esc_html_e('ساختار پسورد خودکار', 'nias-login-signup'); ?></h4>
                    <span class="nias-login-field__hint" style="margin-bottom:8px"><?php esc_html_e('برای پسوردهایی که سیستم برای کاربران جدید تولید می‌کند.', 'nias-login-signup'); ?></span>
                    <div class="nias-login-field-stack" style="border:0;padding-top:0"><label class="nias-login-field-stack__label" for="nias_password_length"><?php esc_html_e('طول پسورد', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" id="nias_password_length" name="nias_password_length" min="6" max="32" value="<?php echo esc_attr(get_option('nias_password_length', 12)); ?>"></div>
                    <span class="nias-login-section-label"><?php esc_html_e('ترکیب شامل', 'nias-login-signup'); ?></span>
                    <div class="nias-login-checkgrid">
                        <label class="nias-login-checkrow"><input type="checkbox" name="nias_password_include_uppercase" value="1" <?php checked(get_option('nias_password_include_uppercase', 1), 1); ?>> <?php esc_html_e('حروف بزرگ (A-Z)', 'nias-login-signup'); ?></label>
                        <label class="nias-login-checkrow"><input type="checkbox" name="nias_password_include_lowercase" value="1" <?php checked(get_option('nias_password_include_lowercase', 1), 1); ?>> <?php esc_html_e('حروف کوچک (a-z)', 'nias-login-signup'); ?></label>
                        <label class="nias-login-checkrow"><input type="checkbox" name="nias_password_include_numbers" value="1" <?php checked(get_option('nias_password_include_numbers', 1), 1); ?>> <?php esc_html_e('اعداد (0-9)', 'nias-login-signup'); ?></label>
                        <label class="nias-login-checkrow"><input type="checkbox" name="nias_password_include_special" value="1" <?php checked(get_option('nias_password_include_special', 1), 1); ?>> <?php esc_html_e('کاراکتر خاص (!@#)', 'nias-login-signup'); ?></label>
                    </div>
                    <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_password_prefix"><?php esc_html_e('پیشوند پسورد (اختیاری)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono nias-login-input--sm" type="text" id="nias_password_prefix" name="nias_password_prefix" placeholder="Site-" maxlength="10" style="width:180px" value="<?php echo esc_attr($preview_prefix); ?>"></div>
                    <span class="nias-login-section-label"><?php esc_html_e('نمونه تولیدی', 'nias-login-signup'); ?></span>
                    <div class="nias-login-codechip" id="nias-password-preview"><?php echo esc_html($preview_prefix . 'Abc123!@#xyz'); ?></div>
                    <button type="button" class="nias-login-btn nias-login-btn--ghost" id="nias-generate-preview" style="align-self:flex-start;margin-top:10px"><svg viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('تولید نمونه جدید', 'nias-login-signup'); ?></button>
                </div>

                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('رفع مشکل لینک دانلود', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('مشکل لینک دانلود فایل‌ها پس از خرید را حل می‌کند؛ اگر فقط از شماره موبایل استفاده می‌کنید فعال کنید.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_download_problem" name="nias_download_problem" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_download_problem'), 1); ?>><label for="nias_download_problem" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('هویت کاربر', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="digitsquantity"><?php esc_html_e('تعداد ارقام کد تایید', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" id="digitsquantity" name="nsdigitsquantity" value="<?php echo esc_attr(get_option('nsdigitsquantity', 4)); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="displaynamenias"><?php printf(esc_html__('نام نمایشی کاربر %s', 'nias-login-signup'), '<span class="nias-login-tag-var">{userid}</span>'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('متغیرها: {userid}, {sitename}, {phone}, {email}, {username}', 'nias-login-signup'); ?></span><input class="nias-login-input" type="text" id="displaynamenias" name="displaynamenias" placeholder="<?php esc_attr_e('کاربر {userid} از {sitename}', 'nias-login-signup'); ?>" value="<?php echo esc_attr(get_option('displaynamenias', __('کاربر {userid}', 'nias-login-signup'))); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_username"><?php printf(esc_html__('نام کاربری %s', 'nias-login-signup'), '<span class="nias-login-tag-var">{niasrandom}</span>'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('از نام فارسی و فاصله استفاده نکنید؛ {niasrandom} پیشنهاد می‌شود.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_username" name="nias_login_username" placeholder="{niasrandom}" value="<?php echo esc_attr(get_option('nias_login_username', '{niasrandom}')); ?>"></div>
                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_default_user_role"><?php esc_html_e('نقش کاربری پیش‌فرض ثبت‌نام', 'nias-login-signup'); ?></label>
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('نقشی که به کاربرانی که از طریق فرم نیاس ثبت‌نام می‌کنند اختصاص داده می‌شود.', 'nias-login-signup'); ?></span>
                    <select class="nias-login-select" id="nias_default_user_role" name="nias_default_user_role">
                        <?php
                        $nias_default_role  = get_option('nias_default_user_role', get_option('default_role', 'subscriber'));
                        $nias_editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : [];
                        foreach ($nias_editable_roles as $role_key => $role_data):
                        ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($nias_default_role, $role_key); ?>><?php echo esc_html($role_data['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php // هر دو گزینه به صفحه/فیلدهای ووکامرس وابسته‌اند ?>
                <?php if (nias_login_wc_active()) : ?>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('تغییر شماره موبایل در پنل کاربری', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی، کاربر می‌تواند در صفحه «جزئیات حساب» ووکامرس شماره موبایل خود را تغییر دهد. تغییر فقط پس از تایید کد پیامکی ارسال‌شده به شماره جدید اعمال می‌شود و ثبت شماره تکراری (متعلق به کاربر دیگر) ممکن نیست. در حالت غیرفعال، شماره فقط نمایش داده می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_account_phone_change" value="0" />
                    <input type="checkbox" id="nias_account_phone_change" name="nias_account_phone_change" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_account_phone_change'), 1); ?>><label for="nias_account_phone_change" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('همگام‌سازی فیلد billing phone ووکامرس با فیلد phone نیاس لاگین', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی، هر بار که شماره موبایل کاربر (متای phone) ثبت یا تغییر کند، فیلد شماره تلفن صورتحساب ووکامرس (billing_phone) هم به‌صورت خودکار با آن یکسان می‌شود. برای کاربران قدیمی، این همگام‌سازی هنگام ورود بعدی آن‌ها انجام می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_billing_phone_sync" value="0" />
                    <input type="checkbox" id="nias_billing_phone_sync" name="nias_billing_phone_sync" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_billing_phone_sync'), 1); ?>><label for="nias_billing_phone_sync" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('ریدایرکت و قفل', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_click_links"><?php esc_html_e('لینک‌های باز‌کننده مودال', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php printf(esc_html__('بخشی از آدرس لینک (href). با کاما جدا کنید. خالی بگذارید تا مقدار پیش‌فرض %s اعمال شود.', 'nias-login-signup'), '<code style="font-family:var(--mono)">my-account</code>'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_click_links" name="nias_login_click_links" value="<?php echo esc_attr(get_option('nias_login_click_links', 'my-account')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_click_classes"><?php esc_html_e('کلاس‌های باز‌کننده مودال', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('نام کلاس CSS (بدون نقطه)؛ با کلیک روی هر عنصر دارای این کلاس، مودال باز می‌شود. با کاما جدا کنید.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_click_classes" name="nias_login_click_classes" placeholder="open-login, my-login-btn" value="<?php echo esc_attr(get_option('nias_login_click_classes', '')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_click_ids"><?php esc_html_e('شناسه‌های (ID) باز‌کننده مودال', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('مقدار ID عنصر (بدون #)؛ با کلیک روی عنصر دارای این ID، مودال باز می‌شود. با کاما جدا کنید.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_click_ids" name="nias_login_click_ids" placeholder="login-button, header-login" value="<?php echo esc_attr(get_option('nias_login_click_ids', '')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_locked_pages"><?php esc_html_e('صفحات قفل‌شده', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php printf(esc_html__('هر صفحه را می‌توانید با «لینک کامل (https)»، «شناسه عددی (ID) برگه» یا «نامک/شناسایی برگه (slug)» قفل کنید. موارد را با کاما جدا کنید. خالی بگذارید تا پیش‌فرض %s اعمال شود.', 'nias-login-signup'), '<code style="font-family:var(--mono)">my-account</code>'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_locked_pages" name="nias_login_locked_pages" value="<?php echo esc_attr(get_option('nias_login_locked_pages', 'my-account')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_redirect"><?php esc_html_e('ریدایرکت بعد از ورود', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('برای ریدایرکت خودکار خالی بگذارید.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="nias_login_redirect" name="nias_login_redirect" value="<?php echo esc_attr(get_option('nias_login_redirect')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_logout_links"><?php esc_html_e('ریدایرکت بعد از خروج', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_logout_links" name="nias_logout_links" value="<?php echo esc_attr(get_option('nias_logout_links')); ?>"></div>

                <div class="nias-login-field-stack">
                    <label class="nias-login-field-stack__label" for="nias_direct_logout_url"><?php esc_html_e('لینک خروج مستقیم از سایت', 'nias-login-signup'); ?></label>
                    <span class="nias-login-field-stack__hint"><?php esc_html_e('این آدرس را کپی کنید و روی هر دکمه، منو یا لینکی در سایت قرار دهید تا کاربر با یک کلیک از حساب خود خارج شود. برخلاف لینک پیش‌فرض وردپرس نیازی به کد امنیتی ندارد، پس برای همه کاربران یکسان است و منقضی نمی‌شود. مقصد بعد از خروج، همان «ریدایرکت بعد از خروج» بالا است.', 'nias-login-signup'); ?></span>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input class="nias-login-input nias-login-input--mono" type="text" id="nias_direct_logout_url" dir="ltr" readonly value="<?php echo esc_attr(nias_login_direct_logout_url()); ?>" style="flex:1;min-width:220px;" onfocus="this.select();">
                        <button type="button" class="nias-login-btn nias-login-btn--ghost" id="nias-copy-logout-url"><svg viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e('کپی لینک', 'nias-login-signup'); ?></button>
                    </div>
                </div>
                <script>
                (function () {
                    var btn   = document.getElementById('nias-copy-logout-url');
                    var input = document.getElementById('nias_direct_logout_url');
                    if (!btn || !input) return;

                    var original = btn.innerHTML;
                    var timer    = null;

                    function flash(text) {
                        clearTimeout(timer);
                        btn.textContent = text;
                        timer = setTimeout(function () { btn.innerHTML = original; }, 1600);
                    }

                    btn.addEventListener('click', function () {
                        input.select();
                        input.setSelectionRange(0, 99999);

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(input.value)
                                .then(function () { flash('کپی شد ✓'); })
                                .catch(function () { flash('کپی نشد — دستی کپی کنید'); });
                            return;
                        }

                        try {
                            flash(document.execCommand('copy') ? 'کپی شد ✓' : 'کپی نشد — دستی کپی کنید');
                        } catch (e) {
                            flash('کپی نشد — دستی کپی کنید');
                        }
                    });
                })();
                </script>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_expire"><?php esc_html_e('انقضای کد تایید (ثانیه)', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" id="nias_login_expire" name="nias_login_expire" value="<?php echo esc_attr(get_option('nias_login_expire', 60)); ?>"></div>
                <hr class="nias-login-divider" />
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی ورود نیاس در wp-login.php', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php printf(esc_html__('با فعال‌سازی، فرم ورود نیاس مستقیماً در آدرس %s نمایش داده می‌شود (بدون ریدایرکت به صفحه اصلی). درخواست‌های ورود، ثبت‌نام و بازیابی رمز همه از طریق همین صفحه با فرم نیاس پردازش می‌شوند.', 'nias-login-signup'), '<code style="font-family:var(--mono);font-size:11px;background:var(--surface-3);padding:1px 5px;border-radius:4px;">wp-login.php</code>'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_replace_wp_login" name="nias_replace_wp_login" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_replace_wp_login'), 1); ?>><label for="nias_replace_wp_login" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <hr class="nias-login-divider" />
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('مسدودسازی دسترسی پیشخوان برای کاربران سطح پایین', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('کاربرانی که فقط نقش «مشترک» یا «مشتری» دارند نمی‌توانند به پیشخوان وردپرس (wp-admin) دسترسی داشته باشند و در صورت تلاش، به صفحه اصلی سایت هدایت می‌شوند.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_block_dashboard_access" value="0" />
                    <input type="checkbox" id="nias_block_dashboard_access" name="nias_block_dashboard_access" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_block_dashboard_access'), 1); ?>><label for="nias_block_dashboard_access" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M13 2L4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('بهینه‌سازی', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('فعال‌سازی این گزینه‌ها در برخی سایت‌ها باعث عدم عملکرد صحیح می‌شود، بنابراین موارد زیر به‌صورت پیش‌فرض غیرفعال است.', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('استفاده از فایل مینیفای‌شده', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('در صورت وجود نسخه فشرده هر فایل (مثلاً script.min.js) همان لود می‌شود. اگر نسخه فشرده ساخته نشده یا از فایل اصلی قدیمی‌تر باشد، به‌صورت خودکار فایل اصلی لود می‌شود تا کد قدیمی اجرا نشود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_optimize_minify" value="0" />
                    <input type="checkbox" id="nias_optimize_minify" name="nias_optimize_minify" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_optimize_minify'), 1); ?>><label for="nias_optimize_minify" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('کش کردن فایل‌های افزونه', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('در حالت غیرفعال، فایل‌های افزونه هرگز در مرورگر کش نمی‌شوند و همیشه تازه دریافت می‌شوند. با فعال‌سازی، نسخه فایل به زمان آخرین تغییر آن گره می‌خورد؛ یعنی تا وقتی فایل عوض نشده کش می‌ماند و به‌محض تغییر، همه کاربران نسخه جدید را می‌گیرند.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="hidden" name="nias_optimize_cache" value="0" />
                    <input type="checkbox" id="nias_optimize_cache" name="nias_optimize_cache" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_optimize_cache'), 1); ?>><label for="nias_optimize_cache" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>
    </div>
</section>
