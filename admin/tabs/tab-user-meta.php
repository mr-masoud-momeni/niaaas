<?php
defined('ABSPATH') || exit;
$force = get_option('nias-force-user-meta');
$roles_opt = get_option('nias-login-meta_roles');
$fields_opt = get_option('nias-login-meta_fields');
$roles_arr = [];
if (is_string($roles_opt)) {
  $tmp = json_decode($roles_opt, true);
  if (is_array($tmp)) {
    $roles_arr = $tmp;
  }
}
$fields_arr = [];
if (is_string($fields_opt)) {
  $tmp = json_decode($fields_opt, true);
  if (is_array($tmp)) {
    $fields_arr = $tmp;
  }
}
$editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : [];
?>
<section id="nsusermeta" class="nias-login-panel nias-login-tabcontent" data-tab="nsusermeta">
  <div class="nias-login-page-head">
    <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 21a8 8 0 0 1 12-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m16 19 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    <div><h1 class="nias-login-page-head__title"><?php esc_html_e('متادیتای کاربر', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('فیلدهای تکمیلی پروفایل را برای کاربران اجباری کنید و ترتیب آن‌ها را تعیین کنید.', 'nias-login-signup'); ?></p></div>
  </div>

  <div class="nias-login-grid--1">
    <div class="nias-login-card">
      <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M9 11l3 3 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('اجبار تکمیل متادیتا', 'nias-login-signup'); ?></span></div></div>
      <div class="nias-login-card__body">
        <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی مودال اجباری', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
          <input type="checkbox" id="nias-force-user-meta" name="nias-force-user-meta" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias-force-user-meta'), 1); ?>><label for="nias-force-user-meta" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
        </span></div>
        <?php if (!nias_elementor_active()) : ?>
          <?php // المنتور نصب نیست: گزینه‌های ویجت المنتوری نمایش داده نمی‌شوند
                // ولی مقدارشان حفظ می‌شود تا با نصب دوباره‌ی المنتور برگردد ?>
          <input type="hidden" name="nias-use-elementor-meta-widget" value="<?php echo esc_attr(get_option('nias-use-elementor-meta-widget')); ?>" />
          <input type="hidden" name="nias-elementor-meta-shortcode" value="<?php echo esc_attr(get_option('nias-elementor-meta-shortcode', '')); ?>" />
        <?php else : ?>
        <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('استفاده از ویجت المنتوری متادیتا', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('تنظیمات متادیتا از ویجت المنتور خوانده می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
          <input type="checkbox" id="nias-use-elementor-meta-widget" name="nias-use-elementor-meta-widget" value="1" class="nias-login-toggle-input" <?php checked(get_option('nias-use-elementor-meta-widget'), 1); ?>><label for="nias-use-elementor-meta-widget" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
        </span></div>
        <div class="nias-login-field-stack nias-login-reveal" id="nias-elementor-meta-shortcode-wrapper" style="display:none;"><label class="nias-login-field-stack__label" for="nias-elementor-meta-shortcode"><?php esc_html_e('شناسه یا شورت‌کد قالب متادیتا', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="nias-elementor-meta-shortcode" name="nias-elementor-meta-shortcode" placeholder="123" value="<?php echo esc_attr(get_option('nias-elementor-meta-shortcode', '')); ?>"><?php nias_elementor_template_field_extras('meta', 'nias-elementor-meta-shortcode'); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="nias-login-card" id="nias-meta-settings-wrapper">
      <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('نقش‌ها و فیلدها', 'nias-login-signup'); ?></span></div></div>
      <div class="nias-login-card__body">
        <span class="nias-login-section-label"><?php esc_html_e('نقش‌های مشمول', 'nias-login-signup'); ?></span>
        <span class="nias-login-field__hint" style="margin-bottom:8px"><?php esc_html_e('در صورت خالی بودن، برای همه نقش‌ها اعمال می‌شود.', 'nias-login-signup'); ?></span>
        <div class="nias-login-chips" id="nias-login-meta-roles-ui">
          <?php foreach ($editable_roles as $role_key => $role_data): ?>
            <label class="nias-login-chip"><input type="checkbox" class="nias-login-meta-role" value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, $roles_arr, true) ? 'checked' : ''; ?>> <?php echo esc_html($role_data['name']); ?></label>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="nias-login-meta_roles" id="nias-login-meta-roles" value="<?php echo esc_attr(is_string($roles_opt) ? $roles_opt : '[]'); ?>" />

        <div class="nias-login-divider"></div>
        <span class="nias-login-section-label"><?php esc_html_e('افزودن فیلد', 'nias-login-signup'); ?></span>
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div class="nias-login-field-stack" style="border:0;padding:0;flex:1;min-width:160px"><label class="nias-login-field-stack__label" for="nias-login-meta-type"><?php esc_html_e('نوع', 'nias-login-signup'); ?></label><select class="nias-login-select" id="nias-login-meta-type"><option value="ready"><?php esc_html_e('فیلد آماده', 'nias-login-signup'); ?></option><option value="custom"><?php esc_html_e('فیلد دستی', 'nias-login-signup'); ?></option></select></div>
          <div class="nias-login-field-stack" style="border:0;padding:0;flex:1;min-width:200px" id="nias-login-meta-ready-wrapper"><label class="nias-login-field-stack__label" for="nias-login-meta-ready"><?php esc_html_e('انتخاب فیلد آماده', 'nias-login-signup'); ?></label><select class="nias-login-select" id="nias-login-meta-ready">
            <option value="first_name" data-label="<?php esc_attr_e('نام', 'nias-login-signup'); ?>" data-type="text"><?php esc_html_e('نام (first_name)', 'nias-login-signup'); ?></option>
            <option value="last_name" data-label="<?php esc_attr_e('نام خانوادگی', 'nias-login-signup'); ?>" data-type="text"><?php esc_html_e('نام خانوادگی (last_name)', 'nias-login-signup'); ?></option>
            <option value="billing_first_name" data-label="<?php esc_attr_e('نام صورتحساب', 'nias-login-signup'); ?>" data-type="text"><?php esc_html_e('نام صورتحساب (Woo)', 'nias-login-signup'); ?></option>
            <option value="billing_last_name" data-label="<?php esc_attr_e('نام‌خانوادگی صورتحساب', 'nias-login-signup'); ?>" data-type="text"><?php esc_html_e('نام‌خانوادگی صورتحساب (Woo)', 'nias-login-signup'); ?></option>
          </select></div>
          <div id="nias-login-meta-custom-wrapper" style="display:none;flex:1;min-width:200px;flex-direction:column;gap:8px">
            <div class="nias-login-field-stack" style="border:0;padding:0"><label class="nias-login-field-stack__label" for="nias-login-meta-key"><?php esc_html_e('کلید متا', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--mono" type="text" id="nias-login-meta-key" placeholder="meta_key"></div>
            <div class="nias-login-field-stack" style="border:0;padding:0"><label class="nias-login-field-stack__label" for="nias-login-meta-label"><?php esc_html_e('لیبل', 'nias-login-signup'); ?></label><input class="nias-login-input" type="text" id="nias-login-meta-label" placeholder="<?php esc_attr_e('لیبل', 'nias-login-signup'); ?>"></div>
            <div class="nias-login-field-stack" style="border:0;padding:0"><label class="nias-login-field-stack__label" for="nias-login-meta-field-type"><?php esc_html_e('نوع فیلد', 'nias-login-signup'); ?></label><select class="nias-login-select" id="nias-login-meta-field-type"><option value="text">text</option><option value="number">number</option><option value="nias-login-select">select</option></select></div>
            <div class="nias-login-field-stack" style="border:0;padding:0"><label class="nias-login-field-stack__label" for="nias-login-meta-options"><?php esc_html_e('گزینه‌ها (برای nias-login-select با | جدا کنید)', 'nias-login-signup'); ?></label><input class="nias-login-input" type="text" id="nias-login-meta-options" placeholder="<?php esc_attr_e('مثال: گزینه۱|گزینه۲|گزینه۳', 'nias-login-signup'); ?>"></div>
          </div>
          <button type="button" class="nias-login-btn" id="nias-login-meta-add"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><?php esc_html_e('افزودن', 'nias-login-signup'); ?></button>
        </div>

        <span class="nias-login-section-label" style="margin-top:14px"><?php printf(esc_html__('فیلدهای فعلی %s', 'nias-login-signup'), '<span class="nias-login-field__hint">' . esc_html__('(برای تغییر ترتیب بکشید)', 'nias-login-signup') . '</span>'); ?></span>
        <ul class="nias-login-meta-list" id="nias-login-meta-list">
          <?php foreach ($fields_arr as $f): ?>
            <li class="nias-login-meta-item nias-login-meta-item" data-key="<?php echo esc_attr($f['key'] ?? ''); ?>">
              <span class="nias-login-fs-grip">⠿</span>
              <span class="nias-login-meta-item__txt nias-login-meta-text"><?php echo esc_html(($f['label'] ?? '') . ' [' . ($f['key'] ?? '') . '] (' . ($f['type'] ?? 'text') . ')'); ?></span>
              <span class="nias-login-badge"><?php esc_html_e('ضروری', 'nias-login-signup'); ?></span>
              <button type="button" class="nias-login-btn nias-login-btn--ghost nias-login-meta-remove" style="padding:6px 12px"><?php esc_html_e('حذف', 'nias-login-signup'); ?></button>
            </li>
          <?php endforeach; ?>
        </ul>
        <input type="hidden" name="nias-login-meta_fields" id="nias-login-meta-fields" value="<?php echo esc_attr(is_string($fields_opt) ? $fields_opt : '[]'); ?>" />
        <span class="nias-login-field__hint"><?php esc_html_e('ترتیب نمایش در مودال مطابق ترتیب این فهرست است.', 'nias-login-signup'); ?></span>
      </div>
    </div>

    <?php
    $rs_enabled  = get_option('nias_review_survey_enabled');
    $rs_scope    = get_option('nias_review_survey_scope', 'all');
    $rs_product  = (int) get_option('nias_review_survey_product_id', 0);
    $rs_closable = get_option('nias_review_survey_closable', 1);
    $rs_max      = (int) get_option('nias_review_survey_max_shows', 1);
    $rs_delay    = (int) get_option('nias_review_survey_delay_days', 0);
    $rs_title    = get_option('nias_review_survey_title', '');
    $rs_subtitle = get_option('nias_review_survey_subtitle', '');
    $rs_wc       = class_exists('WooCommerce');
    ?>
    <?php // نظرسنجی محصولات کاملاً به ووکامرس وابسته است ?>
    <?php if ($rs_wc) : ?>
    <div class="nias-login-card">
      <div class="nias-login-card__head"><div class="nias-login-card__head-icon"><svg viewBox="0 0 24 24" fill="none"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></div><div class="nias-login-card__titles"><span class="nias-login-card__title"><?php esc_html_e('نظرسنجی محصولات', 'nias-login-signup'); ?></span><span class="nias-login-card__sub"><?php esc_html_e('مودال اجباری ثبت نظر و امتیاز برای محصولات خریداری‌شده', 'nias-login-signup'); ?></span></div></div>
      <div class="nias-login-card__body">


        <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('فعال‌سازی مودال نظرسنجی', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('تا زمانی که کاربر برای محصول خریداری‌شده نظری ثبت نکند، مودال در هر بارگذاری صفحه نمایش داده می‌شود.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
          <input type="checkbox" id="nias_review_survey_enabled" name="nias_review_survey_enabled" value="1" class="nias-login-toggle-input" <?php checked($rs_enabled, 1); ?>><label for="nias_review_survey_enabled" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
        </span></div>

        <div class="nias-login-field-stack">
          <label class="nias-login-field-stack__label" for="nias_review_survey_scope"><?php esc_html_e('دامنه نظرسنجی', 'nias-login-signup'); ?></label>
          <select class="nias-login-select" id="nias_review_survey_scope" name="nias_review_survey_scope">
            <option value="all" <?php selected($rs_scope, 'all'); ?>><?php esc_html_e('همه محصولاتی که کاربر خریده', 'nias-login-signup'); ?></option>
            <option value="specific" <?php selected($rs_scope, 'specific'); ?>><?php esc_html_e('یک محصول خاص', 'nias-login-signup'); ?></option>
          </select>
        </div>

        <div class="nias-login-field-stack nias-login-reveal" id="nias_review_survey_product_wrap" style="<?php echo $rs_scope === 'specific' ? '' : 'display:none;'; ?>">
          <label class="nias-login-field-stack__label" for="nias_review_survey_product_id"><?php esc_html_e('شناسه (ID) محصول خاص', 'nias-login-signup'); ?></label>
          <input class="nias-login-input nias-login-input--mono" type="number" min="0" id="nias_review_survey_product_id" name="nias_review_survey_product_id" value="<?php echo esc_attr($rs_product); ?>">
          <span class="nias-login-field-stack__hint"><?php esc_html_e('شناسه عددی محصول موردنظر را وارد کنید (در صفحه ویرایش محصول قابل مشاهده است).', 'nias-login-signup'); ?></span>
        </div>

        <div class="nias-login-field"><div class="nias-login-field__main"><span class="nias-login-field__label"><?php esc_html_e('امکان بستن مودال', 'nias-login-signup'); ?></span><span class="nias-login-field__hint"><?php esc_html_e('اگر خاموش باشد، کاربر تا ثبت نظر نمی‌تواند مودال را ببندد.', 'nias-login-signup'); ?></span></div><span class="nias-login-field__control">
          <input type="hidden" name="nias_review_survey_closable" value="0" />
          <input type="checkbox" id="nias_review_survey_closable" name="nias_review_survey_closable" value="1" class="nias-login-toggle-input" <?php checked($rs_closable, 1); ?>><label for="nias_review_survey_closable" class="nias-login-toggle"><span class="nias-login-knob"><svg viewBox="0 0 10 10"><path d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z"></path></svg></span></label>
        </span></div>

        <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_review_survey_max_shows"><?php esc_html_e('تعداد دفعات نمایش برای کاربر', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="1" id="nias_review_survey_max_shows" name="nias_review_survey_max_shows" value="<?php echo esc_attr($rs_max ?: 1); ?>"><span class="nias-login-field-stack__hint"><?php esc_html_e('اگر مقدار ۱ باشد، فقط یک بار برای کاربر نمایش داده می‌شود. اگر ۲ باشد، در بارگذاری بعدی دوباره یک بار نمایش می‌یابد و پس از آن دیگر نشان داده نمی‌شود.', 'nias-login-signup'); ?></span></div>

        <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_review_survey_delay_days"><?php esc_html_e('روزهای گذشته از خرید', 'nias-login-signup'); ?></label><input class="nias-login-input nias-login-input--sm" type="number" min="0" id="nias_review_survey_delay_days" name="nias_review_survey_delay_days" value="<?php echo esc_attr($rs_delay); ?>"><span class="nias-login-field-stack__hint"><?php esc_html_e('نظرسنجی فقط برای محصولاتی نمایش داده می‌شود که حداقل این تعداد روز از خریدشان گذشته باشد (تا کاربر فرصت استفاده از محصول را داشته باشد). مقدار ۰ یعنی بدون تأخیر.', 'nias-login-signup'); ?></span></div>

        <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_review_survey_title"><?php esc_html_e('عنوان مودال', 'nias-login-signup'); ?></label><input class="nias-login-input" type="text" id="nias_review_survey_title" name="nias_review_survey_title" placeholder="<?php esc_attr_e('محصولات زیر را خریداری کردید', 'nias-login-signup'); ?>" value="<?php echo esc_attr($rs_title); ?>"></div>

        <div class="nias-login-field-stack"><label class="nias-login-field-stack__label" for="nias_review_survey_subtitle"><?php esc_html_e('زیرعنوان مودال', 'nias-login-signup'); ?></label><input class="nias-login-input" type="text" id="nias_review_survey_subtitle" name="nias_review_survey_subtitle" placeholder="<?php esc_attr_e('ممنون می‌شویم نظرتان را برای هر محصول ثبت کنید', 'nias-login-signup'); ?>" value="<?php echo esc_attr($rs_subtitle); ?>"></div>

      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var rsScope = document.getElementById('nias_review_survey_scope');
    var rsProductWrap = document.getElementById('nias_review_survey_product_wrap');
    if (rsScope && rsProductWrap) {
      rsScope.addEventListener('change', function () {
        rsProductWrap.style.display = rsScope.value === 'specific' ? '' : 'none';
      });
    }
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // نمایش/مخفی کردن تنظیمات بر اساس انتخاب ویجت المنتوری
    var $useElementorWidget = document.getElementById('nias-use-elementor-meta-widget');
    var $shortcodeWrapper = document.getElementById('nias-elementor-meta-shortcode-wrapper');
    var $metaSettingsWrapper = document.getElementById('nias-meta-settings-wrapper');

    function toggleMetaSettings() {
      if ($useElementorWidget && $useElementorWidget.checked) {
        if ($shortcodeWrapper) $shortcodeWrapper.style.display = 'flex';
        if ($metaSettingsWrapper) $metaSettingsWrapper.style.display = 'none';
      } else {
        if ($shortcodeWrapper) $shortcodeWrapper.style.display = 'none';
        if ($metaSettingsWrapper) $metaSettingsWrapper.style.display = '';
      }
    }

    if ($useElementorWidget) {
      $useElementorWidget.addEventListener('change', toggleMetaSettings);
      toggleMetaSettings();
    }

    var $rolesHidden = document.getElementById('nias-login-meta-roles');
    var $fieldsHidden = document.getElementById('nias-login-meta-fields');
    var $rolesChecks = document.querySelectorAll('.nias-login-meta-role');

    function updateRoles() {
      var arr = [];
      $rolesChecks.forEach(function(c) {
        if (c.checked) arr.push(c.value);
      });
      $rolesHidden.value = JSON.stringify(arr);
    }
    $rolesChecks.forEach(function(c) {
      c.addEventListener('change', updateRoles);
    });
    updateRoles();

    var $type = document.getElementById('nias-login-meta-type');
    var $readyWrap = document.getElementById('nias-login-meta-ready-wrapper');
    var $customWrap = document.getElementById('nias-login-meta-custom-wrapper');
    $type.addEventListener('change', function() {
      var v = $type.value;
      if (v === 'custom') {
        $readyWrap.style.display = 'none';
        $customWrap.style.display = 'flex';
      } else {
        $readyWrap.style.display = '';
        $customWrap.style.display = 'none';
      }
    });

    if (window.jQuery) {
      var $list = jQuery('#nias-login-meta-list');
      if ($list.length && jQuery.fn.sortable) {
        $list.sortable({
          handle: '.nias-login-fs-grip',
          update: function() { persistFields(); }
        });
      }
    }

    function persistFields() {
      var items = [];
      document.querySelectorAll('#nias-login-meta-list .nias-login-meta-item').forEach(function(li) {
        var textEl = li.querySelector('.nias-login-meta-text');
        var text = textEl ? textEl.textContent : '';
        var key = li.getAttribute('data-key');
        var parts = /\[(.*?)\]\s\((.*?)\)/.exec(text);
        var label = text.split(' [')[0];
        var type = parts ? parts[2] : 'text';
        items.push({ key: key, label: label, type: type, required: true });
      });
      $fieldsHidden.value = JSON.stringify(items);
    }

    document.getElementById('nias-login-meta-add').addEventListener('click', function() {
      if ($type.value === 'ready') {
        var sel = document.getElementById('nias-login-meta-ready');
        var key = sel.value;
        var label = sel.selectedOptions[0].getAttribute('data-label') || key;
        var ftype = sel.selectedOptions[0].getAttribute('data-type') || 'text';
        addItem({ key: key, label: label, type: ftype, required: true });
      } else {
        var key = document.getElementById('nias-login-meta-key').value.trim();
        var label = document.getElementById('nias-login-meta-label').value.trim();
        var ftype = document.getElementById('nias-login-meta-field-type').value;
        var options = document.getElementById('nias-login-meta-options').value.trim();
        if (!key) return;
        addItem({ key: key, label: label || key, type: ftype, required: true, options: options ? options.split('|') : [] });
      }
    });

    function addItem(f) {
      var li = document.createElement('li');
      li.className = 'nias-login-meta-item';
      li.setAttribute('data-key', f.key);

      var grip = document.createElement('span');
      grip.className = 'nias-login-fs-grip';
      grip.textContent = '⠿';

      var span = document.createElement('span');
      span.className = 'nias-login-meta-item__txt nias-login-meta-text';
      span.textContent = f.label + ' [' + f.key + '] (' + (f.type || 'text') + ')';

      var badgeEl = document.createElement('span');
      badgeEl.className = 'nias-login-badge';
      badgeEl.textContent = 'ضروری';

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'nias-login-btn nias-login-btn--ghost nias-login-meta-remove';
      removeBtn.style.padding = '6px 12px';
      removeBtn.textContent = 'حذف';
      removeBtn.addEventListener('click', function() {
        li.remove();
        persistFields();
      });

      li.appendChild(grip);
      li.appendChild(span);
      li.appendChild(badgeEl);
      li.appendChild(removeBtn);
      document.getElementById('nias-login-meta-list').appendChild(li);
      persistFields();
    }

    document.getElementById('nias-login-meta-list').addEventListener('click', function(e) {
      var removeBtn = e.target.closest('.nias-login-meta-remove');
      if (removeBtn) {
        e.preventDefault();
        var li = removeBtn.closest('.nias-login-meta-item');
        if (li) {
          li.remove();
          persistFields();
        }
      }
    });

    var metaForm = document.querySelector('form[action="options.php"]');
    if (metaForm) {
      metaForm.addEventListener('submit', function() {
        persistFields();
        updateRoles();
      });
    }
  });
</script>
