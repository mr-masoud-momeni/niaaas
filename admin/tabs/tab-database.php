<?php defined('ABSPATH') || exit; ?>
<section id="nsdatabase" class="nias-login-panel nias-login-tabcontent" data-tab="nsdatabase">
    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="8" ry="3" stroke="currentColor" stroke-width="1.8"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" stroke="currentColor" stroke-width="1.8"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('مدیریت دیتابیس', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('مشاهده آیپی‌های مسدود، لاگ ارسال‌ها و انتقال داده‌ها بین افزونه‌ها.', 'nias-login-signup'); ?></p></div>
    </div>

    <div class="nias-login-grid--1">
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('آیپی‌های مسدود شده', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="nias-login-btn" id="load-blocked-ips"><svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><?php esc_html_e('نمایش آیپی‌ها', 'nias-login-signup'); ?></button>
                    <button type="button" class="nias-login-btn nias-login-btn--danger" id="clear-blocked-ips"><svg viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('پاکسازی همه آیپی‌ها', 'nias-login-signup'); ?></button>
                </div>
                <div id="blocked-ips-container" style="margin-top: 16px; display: none;">
                    <div id="blocked-ips-table"></div>
                    <div id="blocked-ips-pagination" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4zM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('لاگ ارسال‌های اخیر', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('۲۰ ارسال اخیر (پیامک/ایمیل)', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <button type="button" class="nias-login-btn" id="load-recent-codes" style="align-self:flex-start"><svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="1.8"/><path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e('نمایش لاگ ارسال‌ها', 'nias-login-signup'); ?></button>
                <div id="recent-codes-container" style="margin-top: 16px; display: none;">
                    <div id="recent-codes-table"></div>
                </div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('بررسی و ترمیم جداول', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('رفع خطای «خطا در ثبت کد در دیتابیس» با افزودن جدول/ستون‌های ناقص', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <span class="nias-login-field__hint" style="margin-bottom:8px"><?php esc_html_e('جداول افزونه را بررسی می‌کند و ستون‌ها یا جداول ناقص را می‌سازد.', 'nias-login-signup'); ?> <strong><?php esc_html_e('هیچ داده‌ای حذف نمی‌شود.', 'nias-login-signup'); ?></strong></span>
                <button type="button" class="nias-login-btn" id="nias-repair-tables" style="align-self:flex-start"><svg viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a2 2 0 1 0 3 3l6-6a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2 2.6-2.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('بررسی و ترمیم جداول', 'nias-login-signup'); ?></button>
                <div id="nias-repair-result" style="margin-top: 15px;"></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M17 1l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('انتقال اطلاعات کاربران', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('از افزونه‌های دیگر به این افزونه', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="old_meta_key"><?php esc_html_e('فیلد متای قدیمی', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php printf(esc_html__('نام فیلدی که می‌خواهید به %s منتقل شود.', 'nias-login-signup'), '<code class="nias-login-mono">phone</code>'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="old_meta_key" placeholder="digits_phone_no"></div>
                <button type="button" class="nias-login-btn" id="migrate-meta-data" style="align-self:flex-start;margin-top:6px"><?php esc_html_e('انتقال داده‌ها', 'nias-login-signup'); ?></button>
                <div id="migration-result" style="margin-top: 15px;"></div>
            </div>
        </div>

        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('بررسی و اصلاح شماره‌های نامعتبر', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('پیش‌نمایش شماره‌های نامعتبر و اصلاح گروهی (افزودن ۰ ابتدایی یا تغییر ارقام اول)', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="invalid_meta_key"><?php esc_html_e('فیلد متا', 'nias-login-signup'); ?></label><span class="nias-login-field-stack__hint"><?php printf(esc_html__('پیش‌فرض %s است. اگر می‌خواهید فیلد دیگری را بررسی کنید نامش را وارد کنید.', 'nias-login-signup'), '<code class="nias-login-mono">phone</code>'); ?></span><input class="nias-login-input nias-login-input--mono" type="text" id="invalid_meta_key" value="phone"></div>
                <button type="button" class="nias-login-btn" id="scan-invalid-phones" style="align-self:flex-start;margin-top:6px"><svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><?php esc_html_e('بررسی شماره‌های نامعتبر', 'nias-login-signup'); ?></button>
                <div id="invalid-phones-summary" style="margin-top: 15px;"></div>
                <div id="invalid-phones-samples" style="margin-top: 12px;"></div>

                <div id="invalid-fix-panel" style="display:none;margin-top:18px;padding-top:16px;border-top:1px solid var(--border-soft)">
                    <span class="nias-login-field__hint" style="margin-bottom:10px;display:block"><?php printf(
                        esc_html__('تبدیل روی هر شمارهٔ نامعتبر اعمال می‌شود: ابتدا %1$s و سپس %2$s می‌گردد؛ در پایان شماره اعتبارسنجی (%3$s) و در فیلد %4$s ذخیره می‌شود.', 'nias-login-signup'),
                        '<strong>' . esc_html__('ارقام ابتدایی حذف', 'nias-login-signup') . '</strong>',
                        '<strong>' . esc_html__('پیش‌شماره افزوده', 'nias-login-signup') . '</strong>',
                        '<code class="nias-login-mono">09XXXXXXXXX</code>',
                        '<code class="nias-login-mono">phone</code>'
                    ); ?></span>
                    <div style="display:flex;gap:14px;flex-wrap:wrap">
                        <div class="nias-login-field-stack" style="flex:1;min-width:160px"><label class="nias-login-field-stack__label" for="fix_strip_count"><?php esc_html_e('حذف چند رقم از ابتدا', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="number" min="0" step="1" id="fix_strip_count" value="0"></div>
                        <div class="nias-login-field-stack" style="flex:1;min-width:160px"><label class="nias-login-field-stack__label" for="fix_add_prefix"><?php esc_html_e('افزودن پیش‌شماره', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="fix_add_prefix" placeholder="0" value="0"></div>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
                        <button type="button" class="nias-login-btn" id="preview-fix-invalid"><svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><?php esc_html_e('پیش‌نمایش تغییرات', 'nias-login-signup'); ?></button>
                        <button type="button" class="nias-login-btn nias-login-btn--danger" id="apply-fix-invalid"><svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('اعمال و ذخیره', 'nias-login-signup'); ?></button>
                    </div>
                    <div id="invalid-fix-result" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <div class="nias-login-card">
            <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('اکسپورت و ایمپورت تنظیمات', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('پشتیبان‌گیری از همه تنظیمات افزونه و بازیابی آن روی همین سایت یا سایت دیگر', 'nias-login-signup'); ?></span></div></div>
            <div class="nias-login-card__body">
                <span class="nias-login-field__hint" style="margin-bottom:10px"><?php esc_html_e('یک فایل JSON شامل تمام تنظیمات افزونه (به‌جز کلید لایسنس) دانلود می‌کند. این فایل را می‌توانید روی همین سایت یا سایت دیگری ایمپورت کنید.', 'nias-login-signup'); ?></span>

                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="nias-login-btn" id="nias-export-settings"><svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('دانلود فایل تنظیمات', 'nias-login-signup'); ?></button>
                </div>

                <hr class="nias-login-divider" style="margin:18px 0;">

                <span class="nias-login-field__hint" style="margin-bottom:10px"><?php esc_html_e('برای بازیابی، فایل JSON که قبلاً دانلود کرده‌اید را انتخاب و روی «بازیابی تنظیمات» بزنید.', 'nias-login-signup'); ?> <strong><?php esc_html_e('توجه: تنظیمات فعلی با مقادیر فایل جایگزین می‌شوند.', 'nias-login-signup'); ?></strong></span>

                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <input type="file" id="nias-import-settings-file" accept="application/json,.json" class="nias-login-input" style="flex:1;min-width:220px;padding:8px;">
                    <button type="button" class="nias-login-btn nias-login-btn--danger" id="nias-import-settings"><svg viewBox="0 0 24 24" fill="none"><path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4M7 14l5-5 5 5M12 9v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('بازیابی تنظیمات', 'nias-login-signup'); ?></button>
                </div>
                <div id="nias-settings-ie-result" style="margin-top:15px;"></div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof niasAdminData === 'undefined') return;

    var exportBtn = document.getElementById('nias-export-settings');
    var importBtn = document.getElementById('nias-import-settings');
    var fileInput = document.getElementById('nias-import-settings-file');
    var resultEl  = document.getElementById('nias-settings-ie-result');

    function showResult(ok, msg) {
        var bg = ok ? 'rgba(52,211,153,.12)' : 'rgba(248,113,113,.12)';
        var cl = ok ? 'var(--success, #12855b)' : 'var(--danger, #c0392b)';
        resultEl.innerHTML = '<div style="padding:12px 16px;border-radius:var(--radius-sm);background:' + bg + ';color:' + cl + ';font-size:13px;">' + msg + '</div>';
    }

    /* اکسپورت: ارسال فرم پنهان تا مرورگر فایل را دانلود کند */
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = niasAdminData.ajaxurl;
            form.style.display = 'none';

            form.innerHTML =
                '<input type="hidden" name="action" value="nias_settings_export">' +
                '<input type="hidden" name="nonce" value="' + niasAdminData.nonce + '">';

            document.body.appendChild(form);
            form.submit();
            setTimeout(function () { form.remove(); }, 1000);
        });
    }

    /* ایمپورت: آپلود فایل با FormData */
    if (importBtn && fileInput) {
        importBtn.addEventListener('click', function () {
            if (!fileInput.files || !fileInput.files.length) {
                showResult(false, 'ابتدا یک فایل JSON انتخاب کنید.');
                return;
            }

            if (!window.confirm('تنظیمات فعلی افزونه با مقادیر این فایل جایگزین می‌شوند. ادامه می‌دهید؟')) {
                return;
            }

            var fd = new FormData();
            fd.append('action', 'nias_settings_import');
            fd.append('nonce', niasAdminData.nonce);
            fd.append('file', fileInput.files[0]);

            importBtn.disabled = true;
            var original = importBtn.innerHTML;
            importBtn.textContent = 'در حال بازیابی...';

            fetch(niasAdminData.ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success) {
                        var m = (res.data && res.data.message) ? res.data.message : 'بازیابی انجام شد.';
                        if (res.data && res.data.skipped) {
                            m += ' (' + res.data.skipped + ' مورد ناشناخته نادیده گرفته شد)';
                        }
                        showResult(true, m + ' صفحه تا لحظاتی دیگر تازه‌سازی می‌شود...');
                        setTimeout(function () { window.location.reload(); }, 1800);
                    } else {
                        var em = (res && res.data && res.data.message) ? res.data.message : 'خطا در بازیابی تنظیمات';
                        showResult(false, em);
                    }
                })
                .catch(function () { showResult(false, 'خطا در ارتباط با سرور'); })
                .finally(function () {
                    importBtn.disabled = false;
                    importBtn.innerHTML = original;
                });
        });
    }
});
</script>

<style>
.nias-login-admin .nias-db-table {
    width: 100%;
    background: var(--surface-2);
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 1px solid var(--border);
    margin-top: 10px;
}
.nias-login-admin .nias-db-table table { width: 100%; border-collapse: collapse; }
.nias-login-admin .nias-db-table th {
    background: var(--surface-3);
    color: var(--text);
    padding: 12px;
    text-align: right;
    font-weight: 700;
    border-bottom: 1px solid var(--border);
}
.nias-login-admin .nias-db-table td {
    padding: 10px 12px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-soft);
}
.nias-login-admin .nias-db-table tr:last-child td { border-bottom: none; }
.nias-login-admin .nias-db-table tr:hover { background: var(--surface-3); }
.nias-login-admin .nias-pagination { display: flex; gap: 5px; justify-content: center; align-items: center; }
.nias-login-admin .nias-pagination button {
    padding: 8px 12px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: var(--radius-xs);
    cursor: pointer;
    transition: all 0.2s;
}
.nias-login-admin .nias-pagination button:hover:not(:disabled) { background: var(--surface-3); }
.nias-login-admin .nias-pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
.nias-login-admin .nias-pagination button.active { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }
.nias-login-admin .nias-pagination span { color: var(--text-muted); padding: 0 10px; }
.nias-login-admin .nias-loading { text-align: center; padding: 20px; color: var(--text-muted); }
.nias-login-admin .nias-empty-state { text-align: center; padding: 40px 20px; color: var(--text-faint); }
.nias-login-admin .nias-empty-state svg { width: 64px; height: 64px; margin-bottom: 15px; opacity: 0.3; }
</style>
