<?php
defined('ABSPATH') || exit;

/* -------------------------------------------------------------------------- */
/* زیرمنو و صفحه خروجی کاربران */
/* -------------------------------------------------------------------------- */

add_action('admin_menu', 'nias_export_submenu', 20);

function nias_export_submenu()
{
    add_submenu_page(
        'niasloginsignup',
        'خروجی کاربران',
        'خروجی',
        'manage_options',
        'nias-export-users',
        'nias_export_users_page'
    );
}

/* -------------------------------------------------------------------------- */
/* رندر صفحه خروجی */
/* -------------------------------------------------------------------------- */
function nias_export_users_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $wp_roles = wp_roles()->get_names();
    ?>
    <div class="nias-login-admin nias-login-export-app" data-dir="a">

        <!-- ============ TOPBAR ============ -->
        <header class="nias-login-topbar">
            <div class="nias-login-brand">
                <div class="nias-login-brand__mark">
                    <img src="<?php echo esc_url(NIAS_LOGIN_IMAGES . 'nias.svg'); ?>" alt="نیاس" />
                </div>
                <div class="nias-login-brand__text">
                    <span class="nias-login-brand__title">نیاس لاگین</span>
                    <span class="nias-login-brand__sub">خروجی کاربران · v<?php echo esc_html(NIAS_LOGIN_VERSION); ?></span>
                </div>
            </div>

            <div class="nias-login-topbar__spacer"></div>

            <div class="nias-login-dirswitch" role="group" aria-label="جهت طراحی">
                <button type="button" class="nias-login-dirswitch__btn" data-dir="a" aria-pressed="true"><span class="nias-login-dirswitch__dot"></span>آرورا</button>
                <button type="button" class="nias-login-dirswitch__btn" data-dir="b" aria-pressed="false"><span class="nias-login-dirswitch__dot"></span>روشن</button>
                <button type="button" class="nias-login-dirswitch__btn" data-dir="c" aria-pressed="false"><span class="nias-login-dirswitch__dot"></span>گرافیت</button>
            </div>

            <button type="button" id="nias-do-export" class="nias-login-btn-save">
                <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>دریافت خروجی</span>
            </button>
        </header>

        <!-- ============ BODY ============ -->
        <div class="nias-login-body">
            <div class="nias-login-main" style="margin-right:0;">

                <!-- Notice area -->
                <div id="nias-export-notice" style="display:none; margin-bottom:18px;"></div>

                <!-- Page head -->
                <div class="nias-login-page-head">
                    <div class="nias-login-page-head__icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <h1 class="nias-login-page-head__title">خروجی کاربران</h1>
                        <p class="nias-login-page-head__desc">فیلدها و شرط‌های فیلتر را انتخاب کنید، سپس فایل خروجی را دریافت کنید.</p>
                    </div>
                </div>

                <!-- Two-column nias-login-grid -->
                <div class="nias-export-grid">

                    <!-- ── Left column: fields + filters ── -->
                    <div class="nias-export-col-main">

                        <!-- Card: nias-login-field selection -->
                        <div class="nias-login-card" style="margin-bottom:18px;">
                            <div class="nias-login-card__head">
                                <div class="nias-login-card__head-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="nias-login-card__titles">
                                    <span class="nias-login-card__title">اطلاعات کاربران</span>
                                    <span class="nias-login-card__sub">فیلدهایی که می‌خواهید در فایل خروجی باشند</span>
                                </div>
                            </div>
                            <div class="nias-login-card__body">

                                <div class="nias-ef-grid">
                                    <?php
                                    $fields = [
                                        'first_name'          => 'نام',
                                        'last_name'           => 'نام خانوادگی',
                                        'phone'               => 'شماره موبایل',
                                        'user_email'          => 'ایمیل',
                                        'user_registered'     => 'تاریخ ثبت‌نام',
                                        'billing_first_name'  => 'نام (صورتحساب)',
                                        'billing_last_name'   => 'نام خانوادگی (صورتحساب)',
                                        'billing_phone'       => 'تلفن (صورتحساب)',
                                        'billing_email'       => 'ایمیل (صورتحساب)',
                                        'billing_address_1'   => 'آدرس (صورتحساب)',
                                        'billing_city'        => 'شهر (صورتحساب)',
                                        'billing_state'       => 'استان (صورتحساب)',
                                        'billing_postcode'    => 'کد پستی (صورتحساب)',
                                        'billing_country'     => 'کشور (صورتحساب)',
                                        'user_login'          => 'نام کاربری',
                                        'display_name'        => 'نام نمایشی',
                                        'roles'               => 'نقش کاربر',
                                    ];
                                    $default_on = ['first_name', 'last_name', 'phone', 'user_email'];
                                    foreach ($fields as $key => $label):
                                        $checked = in_array($key, $default_on) ? 'checked' : '';
                                    ?>
                                    <div class="nias-ef-item">
                                        <input type="checkbox"
                                               id="field_<?php echo esc_attr($key); ?>"
                                               name="export_fields[]"
                                               value="<?php echo esc_attr($key); ?>"
                                               class="nias-login-toggle-input nias-export-field-cb"
                                               <?php echo $checked; ?> />
                                        <label class="nias-login-toggle" for="field_<?php echo esc_attr($key); ?>">
                                            <span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span>
                                        </label>
                                        <label for="field_<?php echo esc_attr($key); ?>" class="nias-ef-name"><?php echo esc_html($label); ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr class="nias-login-divider" />

                                <div class="nias-login-field-stack">
                                    <label class="nias-login-field-stack__label" for="nias_custom_meta">
                                        متای سفارشی
                                        <span style="font-weight:400;font-size:12px;color:var(--text-faint);">(با کاما جدا کنید)</span>
                                    </label>
                                    <span class="nias-login-field-stack__hint">نام دقیق <code style="font-family:var(--mono);font-size:11.5px;background:var(--surface-3);padding:1px 6px;border-radius:4px;direction:ltr;">meta_key</code> را وارد کنید</span>
                                    <input type="text" id="nias_custom_meta" class="nias-login-input" placeholder="مثال: national_code, company" />
                                </div>

                            </div>
                        </div>

                        <!-- Card: filters -->
                        <div class="nias-login-card">
                            <div class="nias-login-card__head">
                                <div class="nias-login-card__head-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="nias-login-card__titles">
                                    <span class="nias-login-card__title">شرط‌های فیلتر</span>
                                    <span class="nias-login-card__sub">نقش، بازه زمانی و خریداران محصول</span>
                                </div>
                            </div>
                            <div class="nias-login-card__body">

                                <div class="nias-login-field-stack">
                                    <label class="nias-login-field-stack__label" for="filter_role">نقش کاربر</label>
                                    <select id="filter_role" class="nias-login-select">
                                        <option value="">همه نقش‌ها</option>
                                        <?php foreach ($wp_roles as $role_key => $role_name): ?>
                                            <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="nias-login-field-stack">
                                    <label class="nias-login-field-stack__label">بازه زمانی ثبت‌نام</label>
                                    <div class="nias-ef-date-row">
                                        <div class="nias-ef-date-col">
                                            <span class="nias-login-field-stack__hint">از تاریخ</span>
                                            <input type="date" id="filter_reg_from" class="nias-login-input" />
                                        </div>
                                        <div class="nias-ef-date-col">
                                            <span class="nias-login-field-stack__hint">تا تاریخ</span>
                                            <input type="date" id="filter_reg_to" class="nias-login-input" />
                                        </div>
                                    </div>
                                </div>

                                <?php if (function_exists('wc_get_products')): ?>
                                <div class="nias-login-field-stack">
                                    <label class="nias-login-field-stack__label">فقط خریداران محصول خاص</label>

                                    <div class="nias-product-search-wrap" style="position:relative;">
                                        <div style="position:relative;">
                                            <input type="text" id="filter_product_search"
                                                   placeholder="نام یا شماره محصول را تایپ کنید..."
                                                   autocomplete="off"
                                                   class="nias-login-input"
                                                   style="padding-left:38px;" />
                                            <button type="button" id="nias-prod-clear" title="پاک کردن"
                                                    style="display:none;position:absolute;left:8px;top:50%;transform:translateY(-50%);
                                                           background:none;border:none;cursor:pointer;color:var(--text-faint);padding:4px;line-height:1;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                            </button>
                                            <span id="nias-prod-spinner"
                                                  style="display:none;position:absolute;left:10px;top:50%;transform:translateY(-50%);
                                                         color:var(--text-faint);font-size:11px;pointer-events:none;">⏳</span>
                                        </div>
                                        <input type="hidden" id="filter_product" value="" />

                                        <div id="nias-product-dropdown"
                                             style="display:none;position:absolute;z-index:9999;top:calc(100% + 4px);right:0;left:0;
                                                    background:var(--surface-2);border:1px solid var(--border);
                                                    border-radius:var(--radius-sm);max-height:240px;overflow-y:auto;
                                                    box-shadow:var(--shadow);"></div>

                                        <div id="nias-product-selected-badge" class="nias-login-note" style="display:none;margin-top:8px;">
                                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <span id="nias-product-selected-text"></span>
                                        </div>
                                    </div>

                                    <div class="nias-ef-date-row" style="margin-top:10px;">
                                        <div class="nias-ef-date-col">
                                            <span class="nias-login-field-stack__hint">از تاریخ خرید</span>
                                            <input type="date" id="filter_order_from" class="nias-login-input" />
                                        </div>
                                        <div class="nias-ef-date-col">
                                            <span class="nias-login-field-stack__hint">تا تاریخ خرید</span>
                                            <input type="date" id="filter_order_to" class="nias-login-input" />
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>

                    </div>

                    <!-- ── Right column: format + action ── -->
                    <div class="nias-export-col-side">

                        <div class="nias-login-card">
                            <div class="nias-login-card__head">
                                <div class="nias-login-card__head-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="nias-login-card__titles">
                                    <span class="nias-login-card__title">نوع خروجی</span>
                                </div>
                            </div>
                            <div class="nias-login-card__body">

                                <!-- hidden radios for JS getParams + form -->
                                <input type="radio" name="export_format" value="csv" checked style="display:none" />
                                <input type="radio" name="export_format" value="excel" style="display:none" />

                                <div class="nias-login-field" style="margin-bottom:18px;">
                                    <div class="nias-login-field__main">
                                        <span class="nias-login-field__label">فرمت فایل</span>
                                        <span class="nias-login-field__hint">خاموش: CSV · روشن: Excel (XLSX)</span>
                                    </div>
                                    <div class="nias-login-field__control">
                                        <div class="nias-login-two-way">
                                            <span class="nias-login-two-way__a">CSV</span>
                                            <input type="checkbox" id="export_format_toggle" class="nias-login-toggle-input"
                                                   data-sync="export_format" data-off="csv" data-on="excel" />
                                            <label for="export_format_toggle" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
                                            <span class="nias-login-two-way__b">Excel</span>
                                        </div>
                                    </div>
                                </div>

                                <hr class="nias-login-divider" />

                                <div class="nias-login-note" style="margin-bottom:16px;flex-wrap:wrap;gap:8px;align-items:center;">
                                    <svg viewBox="0 0 24 24" fill="none" style="flex:0 0 auto;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    <span id="nias-export-count-text" style="flex:1;min-width:0;">برای مشاهده تعداد، فیلترها را اعمال کنید</span>
                                    <button type="button" id="nias-preview-count" class="nias-login-btn nias-login-btn--ghost" style="padding:6px 12px;font-size:12px;flex:0 0 auto;">بررسی تعداد</button>
                                </div>

                                <button type="button" id="nias-do-export-card" class="nias-login-btn-save" style="width:100%;justify-content:center;font-size:14px;padding:13px;">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span>دریافت خروجی</span>
                                </button>

                                <div id="nias-export-loading" style="display:none;text-align:center;padding:16px 0;">
                                    <div style="margin:0 auto;width:26px;height:26px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:nias-export-spin .7s linear infinite;"></div>
                                    <p style="margin-top:8px;color:var(--text-faint);font-size:13px;">در حال آماده‌سازی فایل...</p>
                                </div>

                            </div>
                        </div>

                    </div>
                </div><!-- /.nias-export-grid -->

            </div><!-- /.nias-login-main -->
        </div><!-- /.nias-login-body -->

    </div><!-- /.nias-login-admin -->

    <style>
    /* ── export page: no nias-login-rail ── */
    .nias-login-export-app .nias-login-body { overflow-y: auto; }

    /* two-column layout */
    .nias-export-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 960px) { .nias-export-grid { grid-template-columns: 1fr; } }

    /* nias-login-field toggle nias-login-grid */
    .nias-ef-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
        gap: 8px;
    }
    @media (max-width: 540px) { .nias-ef-grid { grid-template-columns: 1fr 1fr; } }

    .nias-login-admin .nias-ef-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        transition: border-color .16s, background .16s;
        overflow: hidden;
    }
    .nias-login-admin .nias-ef-item:has(.nias-login-toggle-input:checked) {
        border-color: var(--accent-line);
        background: var(--accent-soft);
    }
    .nias-login-admin .nias-ef-name {
        font-size: 13px;
        cursor: pointer;
        flex: 1;
        line-height: 1.4;
        color: var(--text);
    }

    /* date row */
    .nias-ef-date-row { display: flex; gap: 12px; }
    .nias-ef-date-col { flex: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    @media (max-width: 480px) { .nias-ef-date-row { flex-direction: column; } }

    /* product dropdown items */
    .nias-prod-item {
        padding: 9px 14px;
        cursor: pointer;
        font-size: 13px;
        border-bottom: 1px solid var(--border-soft);
        transition: background .15s;
        color: var(--text-muted);
    }
    .nias-prod-item:hover { background: var(--surface-3); color: var(--text); }
    .nias-prod-item:last-child { border-bottom: 0; }
    .nias-prod-empty { color: var(--text-faint); cursor: default; }
    .nias-prod-empty:hover { background: none; }
    #nias-prod-load-more {
        padding: 9px 14px; text-align: center; font-size: 12px;
        color: var(--accent-2); cursor: pointer;
        border-top: 1px solid var(--border);
    }
    #nias-prod-load-more:hover { background: var(--surface-3); }

    /* notice boxes */
    #nias-export-notice .nias-notice-ok {
        background: rgba(52,211,153,.10); border: 1px solid rgba(52,211,153,.30);
        border-radius: var(--radius-sm); padding: 12px 16px;
        color: var(--success); font-size: 13px;
    }
    #nias-export-notice .nias-notice-err {
        background: rgba(248,113,113,.10); border: 1px solid rgba(248,113,113,.30);
        border-radius: var(--radius-sm); padding: 12px 16px;
        color: var(--danger); font-size: 13px;
    }

    /* spinner */
    @keyframes nias-export-spin { to { transform: rotate(360deg); } }

    /* date picker icon tint for dark themes */
    .nias-login-admin[data-dir="a"] input[type="date"]::-webkit-calendar-picker-indicator,
    .nias-login-admin[data-dir="c"] input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(.8); cursor: pointer; }
    </style>

    <script>
    jQuery(document).ready(function($) {

        var nonce = '<?php echo wp_create_nonce('nias_export_users'); ?>';

        /* two-way toggle → hidden radio sync */
        $('.nias-login-toggle-input[data-sync]').on('change', function() {
            var name = $(this).data('sync');
            var val  = $(this).is(':checked') ? $(this).data('on') : $(this).data('off');
            $('input[name="' + name + '"][value="' + val + '"]').prop('checked', true);
        });

        function getParams(action) {
            var fields = [];
            $('input.nias-export-field-cb:checked').each(function() { fields.push($(this).val()); });
            var customMeta = $('#nias_custom_meta').val().trim();
            if (customMeta) {
                customMeta.split(',').forEach(function(m) {
                    var t = m.trim();
                    if (t) fields.push('custom:' + t);
                });
            }
            return {
                action   : action,
                nonce    : nonce,
                fields   : fields,
                role     : $('#filter_role').val(),
                reg_from : $('#filter_reg_from').val(),
                reg_to   : $('#filter_reg_to').val(),
                product_id: $('#filter_product').val() || '',
                order_from: $('#filter_order_from').val(),
                order_to : $('#filter_order_to').val(),
                format   : $('input[name="export_format"]:checked').val()
            };
        }

        function showNotice(msg, type) {
            var cls = type === 'ok' ? 'nias-notice-ok' : 'nias-notice-err';
            $('#nias-export-notice').html('<div class="' + cls + '">' + msg + '</div>').show();
        }

        function doExport() {
            var fields = [];
            $('input.nias-export-field-cb:checked').each(function() { fields.push($(this).val()); });
            if (!fields.length) { showNotice('حداقل یک فیلد را انتخاب کنید', 'err'); return; }

            var $btns = $('#nias-do-export, #nias-do-export-card');
            $btns.prop('disabled', true);
            $('#nias-export-loading').show();
            $('#nias-export-notice').hide();

            var params = getParams('nias_export_users_download');
            var $form = $('<form method="POST" style="display:none">').attr('action', ajaxurl);
            $.each(params, function(k, v) {
                if (Array.isArray(v)) {
                    $.each(v, function(i, item) { $form.append($('<input type="hidden">').attr('name', k + '[]').val(item)); });
                } else {
                    $form.append($('<input type="hidden">').attr('name', k).val(v));
                }
            });
            $('body').append($form);
            $form.submit();
            $form.remove();

            setTimeout(function() {
                $btns.prop('disabled', false);
                $('#nias-export-loading').hide();
                showNotice('فایل در حال دانلود است', 'ok');
            }, 2000);
        }

        /* count preview */
        $('#nias-preview-count').on('click', function() {
            var $btn = $(this).prop('disabled', true).text('در حال بررسی...');
            $.post(ajaxurl, getParams('nias_export_count_users'), function(res) {
                $('#nias-export-count-text').text(res.success ? 'تعداد کاربران: ' + res.data.count + ' نفر' : 'خطا: ' + res.data);
            }).always(function() { $btn.prop('disabled', false).text('بررسی تعداد'); });
        });

        /* export buttons */
        $('#nias-do-export, #nias-do-export-card').on('click', doExport);

        /* ── product search ── */
        var prodTimer = null, prodPage = 1, prodQuery = '', prodHasMore = false, prodBusy = false;

        function searchProducts(q, page, append) {
            if (prodBusy) return;
            prodBusy = true;
            $('#nias-prod-spinner').show();
            $('#nias-prod-clear').hide();
            $.get(ajaxurl, { action: 'nias_search_products', nonce: nonce, q: q, page: page }, function(data) {
                var $dd = $('#nias-product-dropdown');
                if (!append) $dd.empty();
                if (!data.results || !data.results.length) {
                    if (!append) $dd.html('<div class="nias-prod-item nias-prod-empty">محصولی یافت نشد</div>');
                    prodHasMore = false;
                } else {
                    $.each(data.results, function(i, item) {
                        $('<div class="nias-prod-item" data-id="' + item.id + '">' + $('<span>').text(item.text).html() + '</div>').appendTo($dd);
                    });
                    prodHasMore = data.pagination && data.pagination.more;
                    if (prodHasMore) $dd.append('<div id="nias-prod-load-more">بارگذاری بیشتر...</div>');
                }
                $dd.show();
            }).always(function() {
                prodBusy = false;
                $('#nias-prod-spinner').hide();
                if ($('#filter_product_search').val().trim() || $('#filter_product').val()) $('#nias-prod-clear').show();
            });
        }

        $('#filter_product_search')
            .on('input', function() {
                prodQuery = $(this).val().trim(); prodPage = 1;
                clearTimeout(prodTimer);
                prodTimer = setTimeout(function() { searchProducts(prodQuery, 1, false); }, 300);
            })
            .on('focus', function() {
                if (!$('#nias-product-dropdown').is(':visible')) searchProducts($(this).val().trim(), 1, false);
                else $('#nias-product-dropdown').show();
            });

        $(document).on('click', '.nias-prod-item', function() {
            var id = $(this).data('id'), text = $(this).text();
            if (!id) return;
            $('#filter_product').val(id);
            $('#filter_product_search').val(text);
            $('#nias-product-selected-text').text(text);
            $('#nias-product-selected-badge').show();
            $('#nias-product-dropdown').hide().empty();
            $('#nias-prod-clear').show();
        });

        $(document).on('click', '#nias-prod-load-more', function() {
            $(this).remove(); prodPage++;
            searchProducts(prodQuery, prodPage, true);
        });

        $('#nias-prod-clear').on('click', function() {
            $('#filter_product, #filter_product_search').val('');
            $('#nias-product-selected-badge').hide();
            $('#nias-product-dropdown').hide().empty();
            $(this).hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nias-product-search-wrap').length) $('#nias-product-dropdown').hide();
        });

    });
    </script>
    <?php
}
