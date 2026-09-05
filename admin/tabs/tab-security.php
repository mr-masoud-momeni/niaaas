<?php
defined('ABSPATH') || exit;
$mode    = get_option('nias_security_mode', 'block');
$isBlock = ($mode !== 'cooldown');
?>
<section id="nssecurity" class="nias-login-panel nias-login-tabcontent" data-tab="nssecurity">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('تنظیمات امنیتی', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('محافظت در برابر حملات Brute Force و مدیریت لاگ‌های امنیتی.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid">
        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('حالت محافظت', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('واکنش به تلاش‌های مشکوک', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <!-- hidden radios keep form submission + existing JS working -->
                <input type="radio" name="nias_security_mode" value="block" style="display:none" <?php checked($isBlock, true); ?>>
                <input type="radio" name="nias_security_mode" value="cooldown" style="display:none" <?php checked($isBlock, false); ?>>

                <div class="nias-login-field">
                    <div class="nias-login-field__main">
                        <span class="nias-login-field__label"><?php esc_html_e('حالت محافظت', 'nias-login-signup'); ?></span>
                        <span class="nias-login-field__hint"><?php esc_html_e('واکنش به تلاش‌های مشکوک ورود', 'nias-login-signup'); ?></span>
                    </div>
                    <div class="nias-login-field__control">
                        <div class="nias-login-two-way">
                            <span class="nias-login-two-way__a"><?php esc_html_e('مسدودسازی', 'nias-login-signup'); ?></span>
                            <input type="checkbox" id="nias_security_mode_toggle" class="nias-login-toggle-input"
                                   data-sync="nias_security_mode" data-off="block" data-on="cooldown"
                                   <?php echo !$isBlock ? 'checked' : ''; ?>>
                            <label for="nias_security_mode_toggle" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                            <span class="nias-login-two-way__b"><?php esc_html_e('مکث پلکانی', 'nias-login-signup'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="nias-login-note nias-login-reveal" id="block-mode-info" style="margin-top:16px" <?php echo $isBlock ? '' : 'hidden'; ?>>
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/></svg>
                    <div class="nias-login-note__body"><strong><?php esc_html_e('مسدودسازی کامل:', 'nias-login-signup'); ?></strong> <?php esc_html_e('اگر یک IP در بازه کوتاه تلاش‌های زیادی انجام دهد، کاملاً مسدود می‌شود و نیاز به رفع دستی دارد. مناسب سایت‌هایی با نیاز امنیتی بالا.', 'nias-login-signup'); ?></div>
                </div>
                <div class="nias-login-note nias-login-reveal" id="cooldown-mode-info" style="margin-top:16px" <?php echo $isBlock ? 'hidden' : ''; ?>>
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <div class="nias-login-note__body"><strong><?php esc_html_e('مکث پلکانی:', 'nias-login-signup'); ?></strong> <?php esc_html_e('با افزایش تلاش‌های ناموفق، زمان انتظار پلکانی بیشتر می‌شود (۳۰ثانیه → ۲دقیقه → ۱۰دقیقه → مسدودسازی). تعادل بین امنیت و تجربه کاربری.', 'nias-login-signup'); ?></div>
                </div>
            </div>
        </div>

        <div class="nias-login-card nias-login-span-2 nias-login-reveal" id="block-mode-settings" <?php echo $isBlock ? '' : 'hidden'; ?>>
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('تنظیمات مسدودسازی', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_block_time_window"><?php esc_html_e('بازه زمانی بررسی (ثانیه)', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('فاصله بین اولین و آخرین تلاش.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--sm" type="number" id="nias_block_time_window" name="nias_block_time_window" min="10" max="300" value="<?php echo esc_attr(get_option('nias_block_time_window', 60)); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_block_min_attempts"><?php esc_html_e('حداقل تلاش برای مسدودسازی', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" id="nias_block_min_attempts" name="nias_block_min_attempts" min="2" max="10" value="<?php echo esc_attr(get_option('nias_block_min_attempts', 3)); ?>"></div>
            </div>
        </div>

        <div class="nias-login-card nias-login-span-2 nias-login-reveal" id="cooldown-mode-settings" <?php echo $isBlock ? 'hidden' : ''; ?>>
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('سطوح مکث پلکانی', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-grid" style="gap:12px">
                    <div class="nias-login-field-stack" style="border:0;padding:6px 0"><label class="nias-login-field-stack__label"><?php esc_html_e('سطح ۱ — تلاش / مکث(ثانیه)', 'nias-login-signup'); ?></label><div style="display:flex;gap:8px"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level1_attempts" value="<?php echo esc_attr(get_option('nias_cooldown_level1_attempts', 2)); ?>"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level1_duration" value="<?php echo esc_attr(get_option('nias_cooldown_level1_duration', 30)); ?>"></div></div>
                    <div class="nias-login-field-stack" style="border:0;padding:6px 0"><label class="nias-login-field-stack__label"><?php esc_html_e('سطح ۲ — تلاش / مکث(ثانیه)', 'nias-login-signup'); ?></label><div style="display:flex;gap:8px"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level2_attempts" value="<?php echo esc_attr(get_option('nias_cooldown_level2_attempts', 4)); ?>"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level2_duration" value="<?php echo esc_attr(get_option('nias_cooldown_level2_duration', 120)); ?>"></div></div>
                    <div class="nias-login-field-stack" style="border:0;padding:6px 0"><label class="nias-login-field-stack__label"><?php esc_html_e('سطح ۳ — تلاش / مکث(ثانیه)', 'nias-login-signup'); ?></label><div style="display:flex;gap:8px"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level3_attempts" value="<?php echo esc_attr(get_option('nias_cooldown_level3_attempts', 6)); ?>"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level3_duration" value="<?php echo esc_attr(get_option('nias_cooldown_level3_duration', 600)); ?>"></div></div>
                    <div class="nias-login-field-stack" style="border:0;padding:6px 0"><label class="nias-login-field-stack__label"><?php esc_html_e('سطح ۴ — تلاش / مکث(ثانیه)', 'nias-login-signup'); ?></label><div style="display:flex;gap:8px"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level4_attempts" value="<?php echo esc_attr(get_option('nias_cooldown_level4_attempts', 8)); ?>"><input class="nias-login-input nias-login-input--sm" type="number" name="nias_cooldown_level4_duration" value="<?php echo esc_attr(get_option('nias_cooldown_level4_duration', 1800)); ?>"></div></div>
                    <div class="nias-login-field-stack" style="border:0;padding:6px 0"><label class="nias-login-field-stack__label" for="nias_cooldown_max_attempts"><?php esc_html_e('حداکثر تلاش قبل از مسدودسازی', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" id="nias_cooldown_max_attempts" name="nias_cooldown_max_attempts" value="<?php echo esc_attr(get_option('nias_cooldown_max_attempts', 10)); ?>"></div>
                </div>
            </div>
        </div>

        <div class="nias-login-card nias-login-span-2">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6M9 13h6M9 17h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('تنظیمات اضافی', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی لاگ امنیتی', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('تمام تلاش‌های ناموفق در جدول لاگ ذخیره می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_security_logging" name="nias_security_logging" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_security_logging', 1), 1); ?>><label for="nias_security_logging" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_security_cleanup_days"><?php esc_html_e('پاکسازی لاگ امنیتی (روز)', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('۰ = غیرفعال.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--sm" type="number" id="nias_security_cleanup_days" name="nias_security_cleanup_days" min="0" max="365" value="<?php echo esc_attr(get_option('nias_security_cleanup_days', 30)); ?>"></div>
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_login_log_cleanup_days"><?php esc_html_e('پاکسازی لاگ ورودها (روز)', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php esc_html_e('۰ = پاکسازی کامل در هر اجرا.', 'nias-login-signup'); ?></span><input class="nias-login-input nias-login-input--sm" type="number" id="nias_login_log_cleanup_days" name="nias_login_log_cleanup_days" min="0" max="365" value="<?php echo esc_attr(get_option('nias_login_log_cleanup_days', 30)); ?>"></div>
                <hr class="nias-login-divider" />
                <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('جلوگیری از ورود مدیران با موبایل', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('با فعال‌سازی، کاربران با نقش مدیر کل (administrator) نمی‌توانند از مودال نیاس (پیامک یا ایمیل) وارد شوند و باید از صفحه ورود پیش‌فرض وردپرس استفاده کنند.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
                    <input type="checkbox" id="nias_block_admin_phone_login" name="nias_block_admin_phone_login" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias_block_admin_phone_login'), 1); ?>><label for="nias_block_admin_phone_login" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                </span></div>
            </div>
        </div>
    </div>
</section>
