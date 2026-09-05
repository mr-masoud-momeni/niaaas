<?php
defined('ABSPATH') || exit;
$nias_email_gateway = get_option('nias_email_gateway', 'wp_mail');

$nias_modern_code_template     = function_exists('nias_default_email_code_template') ? nias_default_email_code_template() : '<p>' . esc_html__('کد ورود شما:', 'nias-login-signup') . ' <strong>{code}</strong></p>';
$nias_modern_password_template = function_exists('nias_default_email_password_template') ? nias_default_email_password_template() : '<p>' . esc_html__('پسورد شما:', 'nias-login-signup') . ' <strong>{password}</strong></p>';

$nias_email_message_template          = get_option('nias_email_message_template', $nias_modern_code_template);
$nias_email_password_message_template = get_option('nias_email_password_message_template', $nias_modern_password_template);

// مقادیر نمونه برای پیش‌نمایش + قالب‌های پیش‌فرض مدرن برای دکمه «درج قالب»
$nias_email_preview_data = [
    'samples' => [
        '{code}'         => '4 8 2 9',
        '{password}'     => 'Xk7$mP2q',
        '{username}'     => 'user_482',
        '{display_name}' => __('کاربر نمونه', 'nias-login-signup'),
        '{email}'        => 'user@example.com',
        '{email_phone}'  => 'user@example.com',
        '{site_name}'    => get_bloginfo('name'),
        '{site_url}'     => home_url('/'),
        '{login_url}'    => wp_login_url(),
    ],
    'defaults' => [
        'code'     => $nias_modern_code_template,
        'password' => $nias_modern_password_template,
    ],
];
?>
<section id="nsemail" class="nias-login-panel nias-login-tabcontent" data-tab="nsemail">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="m3 6 9 6 9-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('تنظیم ایمیل', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('روش ورود مبتنی بر ایمیل و درگاه ارسال ایمیل را پیکربندی کنید.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('روش ورود', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی فقط ایمیل', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('ورود تنها از طریق ایمیل انجام می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_email_activate" name="nias_email_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_email_activate'), 1); ?>><label for="nias_email_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('ایمیل به‌همراه موبایل', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('کاربر می‌تواند با ایمیل یا موبایل وارد شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_email_phone_activate" name="nias_email_phone_activate" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_email_phone_activate'), 1); ?>><label for="nias_email_phone_activate" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('درگاه ارسال ایمیل', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <!-- hidden radios keep form submission + existing JS working -->
                <input type="radio" name="nias_email_gateway" value="wp_mail" style="display:none" <?php checked($nias_email_gateway, 'wp_mail'); ?>>
                <input type="radio" name="nias_email_gateway" value="smtp" style="display:none" <?php checked($nias_email_gateway, 'smtp'); ?>>

                <div class="nias-login-field">
                    <div class="nias-login-field__main">
                        <span class="nias-login-field__label"><?php esc_html_e('درگاه ارسال', 'nias-login-signup'); ?></span>
                        <span class="nias-login-field__hint"><?php esc_html_e('روشن: سرور SMTP اختصاصی · خاموش: wp_mail پیش‌فرض وردپرس', 'nias-login-signup'); ?></span>
                    </div>
                    <div class="nias-login-field__control">
                        <div class="nias-login-two-way">
                            <span class="nias-login-two-way__a"><?php esc_html_e('وردپرس', 'nias-login-signup'); ?></span>
                            <input type="checkbox" id="nias_email_gw_toggle" class="nias-login-toggle-input"
                                   data-sync="nias_email_gateway" data-off="wp_mail" data-on="smtp"
                                   <?php echo $nias_email_gateway === 'smtp' ? 'checked' : ''; ?>>
                            <label for="nias_email_gw_toggle" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                            <span class="nias-login-two-way__b">SMTP</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="nias-login-card nias-login-span-2 nias-login-reveal" id="smtp-settings" <?php echo $nias_email_gateway === 'smtp' ? '' : 'hidden'; ?>>
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="6" rx="2" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="14" width="18" height="6" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 7h.01M7 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('تنظیمات SMTP', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body"><div class="nias-login-grid" style="gap:14px 20px">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_smtp_host">SMTP Host</label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_smtp_host" name="nias_smtp_host" placeholder="smtp.example.com" value="<?php echo esc_attr(get_option('nias_smtp_host')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_smtp_user">SMTP User</label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_smtp_user" name="nias_smtp_user" value="<?php echo esc_attr(get_option('nias_smtp_user')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_smtp_pass">SMTP Password</label><div class="nias-login-input-wrap"><input class="nias-login-input nias-login-input--mono" type="password" id="nias_smtp_pass" name="nias_smtp_pass" value="<?php echo esc_attr(get_option('nias_smtp_pass')); ?>"><button type="button" class="nias-login-input-eye" data-target="nias_smtp_pass"><svg class="nias-login-eye-open" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><svg class="nias-login-eye-closed" viewBox="0 0 24 24" fill="none" hidden><path d="M17.9 17.9A10 10 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5-5.9M9.9 4.2A9 9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2m-6.7-1.1a3 3 0 1 1-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></div></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_smtp_port">SMTP Port</label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_smtp_port" name="nias_smtp_port" placeholder="465" value="<?php echo esc_attr(get_option('nias_smtp_port')); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_smtp_secure">Secure (ssl/tls)</label><input class="nias-login-input nias-login-input--mono" type="text" id="nias_smtp_secure" name="nias_smtp_secure" placeholder="ssl" value="<?php echo esc_attr(get_option('nias_smtp_secure')); ?>"></div>
            </div></div>
        </div>

        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4zM8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('قالب‌های ایمیل', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('از {code}، {password}، {site_url}، {site_name} استفاده کنید', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <style>
                    .nias-email-tpl { margin-bottom: 26px; }
                    .nias-email-tpl:last-child { margin-bottom: 0; }
                    .nias-email-tabbar { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:8px 0 12px; flex-wrap:wrap; }
                    .nias-email-tabs { display:inline-flex; background:var(--surface-3); border-radius:9px; padding:3px; gap:3px; }
                    .nias-email-tab { border:none; background:transparent; color:var(--text-faint); font:inherit; font-size:13px; padding:6px 18px; border-radius:7px; cursor:pointer; transition:background .15s,color .15s; }
                    .nias-email-tab.is-active { background:var(--surface-1,#fff); color:var(--accent,#043ccc); box-shadow:0 1px 3px rgba(0,0,0,.08); font-weight:600; }
                    .nias-email-pane[hidden] { display:none; }
                    .nias-email-preview { width:100%; min-height:430px; border:1px solid var(--border); border-radius:10px; background:#f4f5f7; display:block; }
                    .nias-email-reset svg { width:15px; height:15px; }
                </style>

                <?php
                $nias_email_templates = [
                    [
                        'key'   => 'code',
                        'id'    => 'nias_email_message_template',
                        'label' => __('ساختار ایمیل کد تایید', 'nias-login-signup'),
                        'value' => $nias_email_message_template,
                        'hint'  => __('متغیرها: {code}، {site_name}، {site_url}، {email}', 'nias-login-signup'),
                    ],
                    [
                        'key'   => 'password',
                        'id'    => 'nias_email_password_message_template',
                        'label' => __('ساختار ایمیل پسورد جدید', 'nias-login-signup'),
                        'value' => $nias_email_password_message_template,
                        'hint'  => __('متغیرها: {password}، {username}، {display_name}، {login_url}، {site_name}، {site_url}', 'nias-login-signup'),
                    ],
                ];
                foreach ($nias_email_templates as $tpl) :
                ?>
                    <div class="nias-email-tpl" data-tpl="<?php echo esc_attr($tpl['key']); ?>">
                        <label class="nias-login-field-stack__label" for="<?php echo esc_attr($tpl['id']); ?>"><?php echo esc_html($tpl['label']); ?></label>
                        <div class="nias-email-tabbar">
                            <div class="nias-email-tabs" role="tablist">
                                <button type="button" class="nias-email-tab is-active" data-mode="edit"><?php esc_html_e('ویرایش', 'nias-login-signup'); ?></button>
                                <button type="button" class="nias-email-tab" data-mode="preview"><?php esc_html_e('پیش‌نمایش', 'nias-login-signup'); ?></button>
                            </div>
                            <button type="button" class="nias-login-btn nias-login-btn--ghost nias-email-reset" data-tpl="<?php echo esc_attr($tpl['key']); ?>"><svg viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('درج قالب پیش‌فرض مدرن', 'nias-login-signup'); ?></button>
                        </div>
                        <div class="nias-email-pane" data-pane="edit">
                            <textarea class="nias-login-textarea nias-login-input--mono" id="<?php echo esc_attr($tpl['id']); ?>" name="<?php echo esc_attr($tpl['id']); ?>" rows="12"><?php echo esc_textarea($tpl['value']); ?></textarea>
                            <span class="nias-login-field-stack__hint"><?php echo esc_html($tpl['hint']); ?></span>
                        </div>
                        <div class="nias-email-pane" data-pane="preview" hidden>
                            <iframe class="nias-email-preview" title="<?php esc_attr_e('پیش‌نمایش ایمیل', 'nias-login-signup'); ?>"></iframe>
                        </div>
                    </div>
                <?php endforeach; ?>

                <script>
                    window.niasEmailPreview = <?php echo wp_json_encode($nias_email_preview_data); ?>;
                    (function () {
                        var cfg      = window.niasEmailPreview || {};
                        var samples  = cfg.samples  || {};
                        var defaults = cfg.defaults || {};

                        function render(tplEl) {
                            var ta = tplEl.querySelector('textarea');
                            var iframe = tplEl.querySelector('.nias-email-preview');
                            if (!ta || !iframe) return;
                            var html = ta.value;
                            Object.keys(samples).forEach(function (k) {
                                html = html.split(k).join(samples[k]);
                            });
                            iframe.srcdoc = '<!doctype html><html dir="rtl" lang="fa"><head><meta charset="utf-8"></head><body style="margin:0;">' + html + '</body></html>';
                        }

                        function previewVisible(tplEl) {
                            var pv = tplEl.querySelector('.nias-email-pane[data-pane="preview"]');
                            return pv && !pv.hidden;
                        }

                        document.querySelectorAll('.nias-email-tpl').forEach(function (tplEl) {
                            tplEl.querySelectorAll('.nias-email-tab').forEach(function (tab) {
                                tab.addEventListener('click', function () {
                                    var mode = tab.getAttribute('data-mode');
                                    tplEl.querySelectorAll('.nias-email-tab').forEach(function (t) {
                                        t.classList.toggle('is-active', t === tab);
                                    });
                                    tplEl.querySelectorAll('.nias-email-pane').forEach(function (p) {
                                        p.hidden = p.getAttribute('data-pane') !== mode;
                                    });
                                    if (mode === 'preview') render(tplEl);
                                });
                            });

                            var ta = tplEl.querySelector('textarea');
                            if (ta) {
                                ta.addEventListener('input', function () {
                                    if (previewVisible(tplEl)) render(tplEl);
                                });
                            }
                        });

                        document.querySelectorAll('.nias-email-reset').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var key = btn.getAttribute('data-tpl');
                                var tplEl = btn.closest('.nias-email-tpl');
                                var ta = tplEl.querySelector('textarea');
                                if (!ta || defaults[key] == null) return;
                                if (ta.value.trim() && !window.confirm('قالب فعلی با قالب پیش‌فرض مدرن جایگزین شود؟')) return;
                                ta.value = defaults[key];
                                if (previewVisible(tplEl)) render(tplEl);
                            });
                        });
                    })();
                </script>
            </div>
        </div>
    </div>
</section>
