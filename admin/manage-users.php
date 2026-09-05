<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/license-check.php';

// نمایش فیلدهای اضافی در فرم ویرایش و ایجاد کاربر
add_action('edit_user_profile', 'nias_user_inputs');
add_action('show_user_profile', 'nias_user_inputs');
add_action('user_new_form', 'nias_user_inputs');

function nias_user_inputs($user)
{
    $phone = '';

    // بررسی و گرفتن مقدار متای کاربر
    if (is_a($user, 'WP_User')) {
        $phone = get_user_meta($user->ID, 'phone', true);
    }

    // درج فرم از فایل
    include NIAS_LOGIN_VIEW . 'user-inputs.php';
}

// بروزرسانی اطلاعات کاربر هنگام ویرایش پروفایل
add_action('edit_user_profile_update', 'nias_update_user');
add_action('personal_options_update', 'nias_update_user');

// ذخیره اطلاعات کاربر هنگام ایجاد کاربر جدید
add_action('user_register', 'nias_update_user');

function nias_update_user($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['nias_phone'])) {
        $raw   = sanitize_text_field($_POST['nias_phone']);
        $phone = nias_normalize_phone_number($raw);

        // اگر شماره به فرمت استاندارد نرسید ولی خالی هم نیست،
        // دست‌کم الزام «شروع با ۰» را تضمین کن.
        if ($phone === '' && $raw !== '') {
            $digits = preg_replace('/\D+/', '', $raw);
            if ($digits !== '') {
                $phone = ($digits[0] === '0') ? $digits : '0' . $digits;
            }
        }

        update_user_meta($user_id, 'phone', $phone);
    }
}


add_filter('manage_users_columns', 'nias_users_columns');
function nias_users_columns($cols)
{
    $cols['phone'] = 'تلفن';
    return $cols;
}

add_filter('manage_users_custom_column', 'nias_users_columns_data', 10, 3);
function nias_users_columns_data($output, $column_name, $user_id)
{

    if ($column_name == 'phone') {
        $output = get_user_meta($user_id, 'phone', true);
        if (!$output) {
            $output = '-';
        }
    }
    return $output;
}


//ایجاد ترتیب برای شماره و سرچ بر اساس شماره در قسمت کاربران

// Enable sorting for the custom column
add_filter('manage_users_sortable_columns', 'nias_users_sortable_columns');
function nias_users_sortable_columns($columns)
{
    $columns['phone'] = 'phone';
    return $columns;
}




//Searching Meta Data in Admin
add_action('pre_user_query', 'nias_pre_user_search');
function nias_pre_user_search($user_search)
{
    global $wpdb;
    if (!isset($_GET['s']))
        return;

    //Enter Your Meta Fields To Query
    $search_array = array("customer_id", "postal_code", "churchorganization_name", "first_name", "last_name", "phone");

    $user_search->query_from .= " INNER JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID={$wpdb->usermeta}.user_id AND (";
    for ($i = 0; $i < count($search_array); $i++) {
        if ($i > 0)
            $user_search->query_from .= " OR ";
        $user_search->query_from .= "{$wpdb->usermeta}.meta_key='" . $search_array[$i] . "'";
    }
    $user_search->query_from .= ")";
    $custom_where = $wpdb->prepare("{$wpdb->usermeta}.meta_value LIKE '%s'", "%" . $_GET['s'] . "%");
    $user_search->query_where = str_replace('WHERE 1=1 AND (', "WHERE 1=1 AND ({$custom_where} OR ", $user_search->query_where);
}




//ساخت منو
add_action('admin_menu', 'niasloginsignup');
function niasloginsignup()
{
    add_menu_page(
        "تنظیمات ورود و ثبت نام نیاس",
        "نیاس لاگین",
        "manage_options",
        "niasloginsignup", //slug
        "niasloginsignup_callback",
        NIAS_LOGIN_IMAGES . "/nias.svg"
    );
}


//Display admin notices 
function nias_login_admin_notice()
{
    //get the current screen
    $screen = get_current_screen();

    //return if not plugin settings page 
    //To get the exact your screen ID just do var_dump($screen)
    if ($screen->id !== 'toplevel_page_niasloginsignup')
        return;

    //Checks if settings updated 
    if (isset($_GET['settings-updated'])) {
        //if settings updated successfully 
        if ('true' === $_GET['settings-updated']): ?>

            <div class="notice notice-success is-dismissible nias-plugin-notice">
                <p><?php _e('تنظیمات پلاگین  نیاس با موفقیت ذخیره شد️', 'nias-login-signup') ?></p>
            </div>

        <?php else: ?>

            <div class="notice notice-warning is-dismissible nias-plugin-notice">
                <p><?php _e('متاسفانه مشکلی پیش اومده به نیاس اطلاع بدید', 'nias-login-signup') ?></p>
            </div>

        <?php endif;
    }
}
add_action('admin_notices', 'nias_login_admin_notice');

// نمایش نوتیس برای فیلدهای خالی ضروری
add_action('admin_notices', 'nias_check_required_fields');
function nias_check_required_fields()
{
    // بررسی فیلدهای ضروری
    $required_fields = [
        'displaynamenias' => 'نام نمایشی کاربر',
        'nias_login_username' => 'نام کاربری',
        'nias_login_expire' => 'مدت زمان انقضای کد',
        'nsdigitsquantity' => 'تعداد ارقام کد تایید'
    ];

    $empty_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty(get_option($field))) {
            $empty_fields[] = $label;
        }
    }

    if (!empty($empty_fields)) {
        echo '<div class="notice notice-error is-dismissible nias-plugin-notice">
            <p>لطفاً فیلد های ضروری زیر را از تب عملکرد پلاگین نیاس پر کنید:</p>
            <ul style="list-style: disc inside;">
                <li>' . implode('</li><li>', $empty_fields) . '</li>
            </ul>
        </div>';
    }
}

//استایل تنظیمات
function nias_enqueue_admin_css()
{
    // دارایی‌های پنل مدیریت عمداً از تنظیمات «بهینه‌سازی» پیروی نمی‌کنند —
    // آن تنظیمات فقط برای فرانت‌اند است
    wp_enqueue_style('niaslogin-admin', NIAS_LOGIN_URL . 'admin/admin-niaslogin.css', array(), NIAS_LOGIN_VERSION);
    wp_enqueue_script('nias-login-admin-script', NIAS_LOGIN_URL . 'admin/admin-niaslogin.js', array('jquery'), NIAS_LOGIN_VERSION, true);
    wp_enqueue_script('jquery-ui-sortable');

    // Add custom font-face with dynamic URL
    $font_url = NIAS_LOGIN_URL . 'assets/font/vazirmatn.woff2';
    $custom_css = "
    @font-face {
        font-family: 'Vazirmatn';
        font-style: normal;
        font-weight: 400;
        src: url('{$font_url}') format('woff2');
        unicode-range: U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FE74, U+FE76-FEFC, U+102E0-102FB, U+10E60-10E7E, U+10EC2-10EC4, U+10EFC-10EFF, U+1EE00-1EE03, U+1EE05-1EE1F, U+1EE21-1EE22, U+1EE24, U+1EE27, U+1EE29-1EE32, U+1EE34-1EE37, U+1EE39, U+1EE3B, U+1EE42, U+1EE47, U+1EE49, U+1EE4B, U+1EE4D-1EE4F, U+1EE51-1EE52, U+1EE54, U+1EE57, U+1EE59, U+1EE5B, U+1EE5D, U+1EE5F, U+1EE61-1EE62, U+1EE64, U+1EE67-1EE6A, U+1EE6C-1EE72, U+1EE74-1EE77, U+1EE79-1EE7C, U+1EE7E, U+1EE80-1EE89, U+1EE8B-1EE9B, U+1EEA1-1EEA3, U+1EEA5-1EEA9, U+1EEAB-1EEBB, U+1EEF0-1EEF1;
    }
    #adminmenu #toplevel_page_niasloginsignup .wp-menu-image {
        display: flex; align-items: center; justify-content: center;
    }
    #adminmenu #toplevel_page_niasloginsignup .wp-menu-image img {
        width: 22px !important; height: 22px !important;
        padding: 0 !important; margin: 0 !important;
        opacity: 1 !important;
    }
    #adminmenu li#toplevel_page_niasloginsignup a .wp-menu-image { opacity: 1 !important; }
    body.toplevel_page_niasloginsignup .notice:not(.nias-plugin-notice) {
        display: none !important;
    }
    ";
    wp_add_inline_style('niaslogin-admin', $custom_css);

    // Localize script for AJAX
    wp_localize_script('nias-login-admin-script', 'niasAdminData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nias_db_actions'),
        // دکمه‌ی «ساخت خودکار قالب» در تب‌های ظاهر و متادیتا
        'templateNonce' => wp_create_nonce('nias_elementor_create_template')
    ));
}
add_action('admin_enqueue_scripts', 'nias_enqueue_admin_css');

function niasloginsignup_callback()
{
    if (!nias_license_is_valid()) {
        nias_render_license_notice();
        return;
    }

    ?>


        <div class="nias-login-admin" data-dir="a">
                <form method="post" action="options.php" autocomplete="off">
                    <?php
                    settings_fields('nias_login_settings');
                    do_settings_sections('nias_login_settings');
                    ?>

                    <!-- ============ TOPBAR ============ -->
                    <header class="nias-login-topbar">
                        <div class="nias-login-brand">
                            <div class="nias-login-brand__mark">
                                <img src="<?php echo esc_url(NIAS_LOGIN_IMAGES . 'nias.svg'); ?>" alt="نیاس" />
                            </div>
                            <div class="nias-login-brand__text">
                                <span class="nias-login-brand__title">نیاس لاگین</span>
                                <span class="nias-login-brand__sub">ورود و ثبت‌نام · v<?php echo esc_html(NIAS_LOGIN_VERSION); ?></span>
                            </div>
                        </div>

                        <div class="nias-login-topbar__spacer"></div>

                        <div class="nias-login-dirswitch" role="group" aria-label="جهت طراحی">
                            <button type="button" class="nias-login-dirswitch__btn" data-dir="a" aria-pressed="true"><span class="nias-login-dirswitch__dot"></span>آرورا</button>
                            <button type="button" class="nias-login-dirswitch__btn" data-dir="b" aria-pressed="false"><span class="nias-login-dirswitch__dot"></span>روشن</button>
                            <button type="button" class="nias-login-dirswitch__btn" data-dir="c" aria-pressed="false"><span class="nias-login-dirswitch__dot"></span>گرافیت</button>
                        </div>

                        <a href="https://www.aparat.com/playlist/24182337/" target="_blank" rel="noopener noreferrer" class="nias-login-btn nias-login-btn--ghost nias-login-help-link" title="آموزش تنظیم">
                            <svg viewBox="0 0 24 24" fill="none"><path d="m10 8 6 4-6 4V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><rect x="3" y="4" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/></svg>
                            <span>آموزش تنظیم</span>
                        </a>

                        <button type="button" class="nias-login-iconbtn" id="nsnotif-trigger" title="اعلان‌ها">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.7 21a2 2 0 0 1-3.4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <span class="nias-login-iconbtn__dot"></span>
                        </button>

                        <button type="submit" class="nias-login-btn-save"><svg viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>ذخیره تغییرات</span></button>
                    </header>

                    <div class="nias-login-body">
                        <main class="nias-login-main">
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-dashboard.php'; ?>
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-operator.php'; ?>
                            <!-- ----------------------------------------------------------------------- -->
                            <!--                              تنظیم عملکرد                               -->
                            <!-- ----------------------------------------------------------------------- -->
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-main-options.php'; ?>
                            <!-- ----------------------------------------------------------------------- -->
                            <!--                              تنظیمات امنیتی                             -->
                            <!-- ----------------------------------------------------------------------- -->
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-security.php'; ?>
                            <!-- ----------------------------------------------------------------------- -->
                            <!--                              تنظیمات دیتابیس                             -->
                            <!-- ----------------------------------------------------------------------- -->
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-database.php'; ?>
                            <!-- ----------------------------------------------------------------------- -->
                            <!--                              تنظیمات ظاهری                              -->
                            <!-- ----------------------------------------------------------------------- -->
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-design.php'; ?>

                            <!-- ----------------------------------------------------------------------- -->
                            <!--                               تنظیم ایمیل                               -->
                            <!-- ----------------------------------------------------------------------- -->
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-email.php'; ?>
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-google.php'; ?>
                        <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-user-meta.php'; ?>

                            <?php // تب خرید سریع کاملاً به ووکامرس وابسته است ?>
                            <?php if (nias_login_wc_active()) : ?>
                                <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-fastsell.php'; ?>
                            <?php endif; ?>
                            <!-- ----------------------------------------------------------------------- -->
                            <!--                         پیامک وضعیت‌های ووکامرس                          -->
                            <!-- ----------------------------------------------------------------------- -->
                            <?php include NIAS_LOGIN_ADMIN . 'tabs/tab-wc-sms.php'; ?>




                        </main>

                        <!-- ============ ICON RAIL ============ -->
                        <nav class="nias-login-rail">
                            <div class="nias-login-rail__top">
                                <button type="button" class="nias-login-rail__pin" title="سنجاق منو"><svg viewBox="0 0 24 24" fill="none"><path d="M13 5l7 7-7 7M20 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                            </div>
                            <div class="nias-login-rail__nav">
                                <span class="nias-login-rail__group-label">داشبورد</span>
                                <button type="button" class="nias-login-navitem" data-tab="nsdashboard" onclick="niasopentab(event, 'nsdashboard')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg></span><span class="nias-login-navitem__label">پیشخوان</span></button>

                                <span class="nias-login-rail__group-label">پیکربندی ورود</span>
                                <button type="button" class="nias-login-navitem" data-tab="nsoperator" onclick="niasopentab(event, 'nsoperator')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M2 9h20M6 14h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="nias-login-navitem__label">تنظیم درگاه</span></button>
                                <button type="button" class="nias-login-navitem" data-tab="nsemail" onclick="niasopentab(event, 'nsemail')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="m3 6 9 6 9-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="nias-login-navitem__label">تنظیم ایمیل</span></button>
                                <button type="button" class="nias-login-navitem" data-tab="nsgoogle" onclick="niasopentab(event, 'nsgoogle')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="nias-login-navitem__label">ورود با گوگل</span></button>
                                <button type="button" class="nias-login-navitem" data-tab="nsmainoption" onclick="niasopentab(event, 'nsmainoption')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8M4.6 9a1.6 1.6 0 0 0-.3-1.8M12 2v3M12 19v3M22 12h-3M5 12H2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="nias-login-navitem__label">تنظیم عملکرد</span></button>
                                <button type="button" class="nias-login-navitem" data-tab="nsdesignoption" onclick="niasopentab(event, 'nsdesignoption')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2 2 7l10 5 10-5-10-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m2 17 10 5 10-5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span><span class="nias-login-navitem__label">تنظیمات ظاهری</span></button>

                                <span class="nias-login-rail__group-label">کاربران و فروش</span>
                                <button type="button" class="nias-login-navitem" data-tab="nsusermeta" onclick="niasopentab(event, 'nsusermeta')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a8 8 0 0 1 12-7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="nias-login-navitem__label">مودال اطلاعات</span></button>
                                <?php if (nias_login_wc_active()) : ?><button type="button" class="nias-login-navitem" data-tab="nsfastsell" onclick="niasopentab(event, 'nsfastsell')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4ZM3 6h18M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="nias-login-navitem__label">خرید سریع</span></button><?php endif; ?>
                                <button type="button" class="nias-login-navitem" data-tab="nswcsms" onclick="niasopentab(event, 'nswcsms')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.3A8.4 8.4 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 11.5h7M8.5 8.5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span><span class="nias-login-navitem__label">پیامک ووکامرس</span></button>

                                <span class="nias-login-rail__group-label">امنیت و داده</span>
                                <button type="button" class="nias-login-navitem" data-tab="nssecurity" onclick="niasopentab(event, 'nssecurity')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span><span class="nias-login-navitem__label">تنظیمات امنیتی</span></button>
                                <button type="button" class="nias-login-navitem" data-tab="nsdatabase" onclick="niasopentab(event, 'nsdatabase')"><span class="nias-login-navitem__icon"><svg viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="8" ry="3" stroke="currentColor" stroke-width="1.7"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5" stroke="currentColor" stroke-width="1.7"/></svg></span><span class="nias-login-navitem__label">دیتابیس</span></button>
                            </div>
                        </nav>
                    </div><!-- /.nias-login-body -->

                    <?php submit_button(); ?>
                </form>
            </div><!-- /.nias-login-admin -->

            <!-- notification drawer -->
            <div class="nias-login-drawer-overlay" id="nsnotif-overlay"></div>
            <aside class="nias-login-drawer" id="nsnotif-panel">
                <div class="nias-login-drawer__head"><h3>اعلان‌ها و نکات</h3><button type="button" class="nias-login-drawer__close" id="nsnotif-close">&times;</button></div>
                <div class="nias-login-drawer__body">
                    <!--
                    <div class="nias-login-notif nias-login-notif--alert">توجه: درگاه‌های «ملی پیامک» و «پیامیتو» یکسان هستند.</div>
                    <div class="nias-login-notif nias-login-notif--alert">توجه: درگاه فراز برای تمام درگاه‌های آیپی پنل قابل استفاده است.</div>
        -->
                    <div class="nias-login-notif">حتماً فیلدهای بخش «تنظیم درگاه» را به‌طور کامل پر کنید.</div>
                    <div class="nias-login-notif">اگر از افزونه بهینه‌سازی استفاده می‌کنید، در بخش exclude فایل‌های JS/CSS عبارت <code>nias-login-signup</code> را وارد کنید.</div>
                </div>
            </aside>


        <?php
}
// ثبت تنظیمات در دیتابیس
function nias_login_register_settings()
{
    register_setting('nias_login_settings', 'nias_username');
    register_setting('nias_login_settings', 'nias_password');
    register_setting('nias_login_settings', 'nias_localnumber');
    register_setting('nias_login_settings', 'nias_api');
    register_setting('nias_login_settings', 'nias_operator'); // ثبت فیلد nias-login-select

    // SMS.ir gateway settings
    register_setting('nias_login_settings', 'nias_smsir_api_key', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smsir_template_id', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smsir_param1', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smsir_param2', 'sanitize_text_field');

    // Plain-text SMS template for non-pattern gateways
    register_setting('nias_login_settings', 'nias_sms_text', 'sanitize_text_field');

    // Password sending settings
    register_setting('nias_login_settings', 'nias_password_pattern', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_password_var', 'sanitize_text_field');
    // ثبت فیلدهای جدید
    register_setting('nias_login_settings', 'nias_pattern');
    register_setting('nias_login_settings', 'nias_var1');
    register_setting('nias_login_settings', 'nias_var2');
    register_setting('nias_login_settings', 'nias_desginform'); // ثبت فیلد nias-login-select
    register_setting('nias_login_settings', 'nias_elementor_shortcode'); // ثبت فیلد nias-login-input

    // تنظیمات استایل پیش‌فرض پاپ‌آپ ورود
    register_setting('nias_login_settings', 'nias_login_close_outside');
    register_setting('nias_login_settings', 'nias_login_style_primary_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#043ccc',
    ));
    register_setting('nias_login_settings', 'nias_login_style_title_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#205fff',
    ));
    register_setting('nias_login_settings', 'nias_login_style_bg_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff',
    ));
    register_setting('nias_login_settings', 'nias_login_style_close_btn_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ff0000',
    ));
    register_setting('nias_login_settings', 'nias_login_style_radius', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 15,
    ));
    register_setting('nias_login_settings', 'nias_login_style_title_font_size', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 20,
    ));
    register_setting('nias_login_settings', 'nias_login_style_subtitle_font_size', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 13,
    ));
    register_setting('nias_login_settings', 'nias_login_style_font_size', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 16,
    ));

    // ترنسفورم لیبل شناور فیلدها — حالت عادی
    register_setting('nias_login_settings', 'nias_login_style_label_ty', array(
        'type' => 'integer',
        'sanitize_callback' => 'nias_login_sanitize_label_offset',
        'default' => 6,
    ));
    register_setting('nias_login_settings', 'nias_login_style_label_tx', array(
        'type' => 'integer',
        'sanitize_callback' => 'nias_login_sanitize_label_offset',
        'default' => 0,
    ));
    register_setting('nias_login_settings', 'nias_login_style_label_scale', array(
        'type' => 'number',
        'sanitize_callback' => 'nias_login_sanitize_label_scale',
        'default' => 1,
    ));

    // ترنسفورم لیبل شناور فیلدها — حالت فوکوس/پرشده
    register_setting('nias_login_settings', 'nias_login_style_label_ty_focus', array(
        'type' => 'integer',
        'sanitize_callback' => 'nias_login_sanitize_label_offset',
        'default' => -14,
    ));
    register_setting('nias_login_settings', 'nias_login_style_label_tx_focus', array(
        'type' => 'integer',
        'sanitize_callback' => 'nias_login_sanitize_label_offset',
        'default' => -5,
    ));
    register_setting('nias_login_settings', 'nias_login_style_label_scale_focus', array(
        'type' => 'number',
        'sanitize_callback' => 'nias_login_sanitize_label_scale',
        'default' => 0.7,
    ));

    // متن‌های قابل‌تنظیم مودال پیش‌فرض (خالی = استفاده از متن پیش‌فرض)
    foreach (array_keys(nias_login_get_modal_texts()) as $nias_modal_text_key) {
        register_setting('nias_login_settings', $nias_modal_text_key, array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
    }
    register_setting('nias_login_settings', 'nias_login_username', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '{niasrandom}',
    ));
    register_setting('nias_login_settings', 'nias_download_problem');
    register_setting('nias_login_settings', 'nias_account_phone_change');
    register_setting('nias_login_settings', 'nias_billing_phone_sync');
    register_setting('nias_login_settings', 'nias_fastform_activate');

    register_setting('nias_login_settings', 'nsdigitsquantity', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4,
    ));
    register_setting('nias_login_settings', 'displaynamenias', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'کاربر {userid}',
    ));
    register_setting('nias_login_settings', 'nias_login_locked_pages', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'my-account',
    ));
    register_setting('nias_login_settings', 'nias_login_click_links', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'my-account',
    ));
    register_setting('nias_login_settings', 'nias_login_click_classes', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));
    register_setting('nias_login_settings', 'nias_login_click_ids', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));
    register_setting('nias_login_settings', 'nias_login_redirect');
    register_setting('nias_login_settings', 'nias_logout_links');
    register_setting('nias_login_settings', 'nias_login_expire', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 60,
    ));
    register_setting('nias_login_settings', 'nias_countdown_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 120,
    ));
    register_setting('nias_login_settings', 'nias_max_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 3,
    ));
    register_setting('nias_login_settings', 'nias_block_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 60,
    ));
    register_setting('nias_login_settings', 'nias_password_activate');
    register_setting('nias_login_settings', 'nias_password_otp_activate');
    register_setting('nias_login_settings', 'nias_manual_password_activate');

    register_setting('nias_login_settings', 'nias_bale_activate');
    register_setting('nias_login_settings', 'nias_bale_botid');
    register_setting('nias_login_settings', 'nias_bale_api');


    // تنظیمات ساختار پسورد
    register_setting('nias_login_settings', 'nias_password_length', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 12,
    ));
    register_setting('nias_login_settings', 'nias_password_include_uppercase', array(
        'type' => 'boolean',
        'default' => 1,
    ));
    register_setting('nias_login_settings', 'nias_password_include_lowercase', array(
        'type' => 'boolean',
        'default' => 1,
    ));
    register_setting('nias_login_settings', 'nias_password_include_numbers', array(
        'type' => 'boolean',
        'default' => 1,
    ));
    register_setting('nias_login_settings', 'nias_password_include_special', array(
        'type' => 'boolean',
        'default' => 1,
    ));
    register_setting('nias_login_settings', 'nias_password_prefix', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('nias_login_settings', 'nias_quick_purchase');
    register_setting('nias_login_settings', 'nias_fastsell_buy_now');
    register_setting('nias_login_settings', 'nias_fastsell_buy_now_gateways', [
        'type'              => 'array',
        'sanitize_callback' => 'nias_sanitize_fastsell_method_ids',
        'default'           => [],
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_buy_now_shipping', [
        'type'              => 'array',
        'sanitize_callback' => 'nias_sanitize_fastsell_method_ids',
        'default'           => [],
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_show_shipping_address');
    register_setting('nias_login_settings', 'nias_fastsell_keep_cart_until_paid', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_open_modal_after_add', [
        'type'    => 'boolean',
        'default' => 1,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_custom_add_to_cart_class', [
        'type'              => 'string',
        'sanitize_callback' => 'nias_sanitize_fastsell_css_class_list',
        'default'           => '',
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_open_modal_selector', [
        'type'              => 'string',
        'sanitize_callback' => 'nias_sanitize_fastsell_css_class_list',
        'default'           => '',
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_show_order_summary', [
        'type'    => 'boolean',
        'default' => 1,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_show_price_breakdown', [
        'type'    => 'boolean',
        'default' => 1,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_otp_before_checkout', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_debug_log', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
    register_setting('nias_login_settings', 'nias_fastsell_free_shipping_show_method', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
    register_setting('nias_login_settings', 'nias-fastsell-shortcode');
    register_setting('nias_login_settings', 'nias-force-user-meta');
    register_setting('nias_login_settings', 'nias_fastsell_checkout_fields', [
        'type'              => 'array',
        'sanitize_callback' => 'nias_sanitize_fastsell_fields',
        'default'           => [],
    ]);
    register_setting('nias_login_settings', 'nias-use-elementor-meta-widget');
    register_setting('nias_login_settings', 'nias-elementor-meta-shortcode');
    register_setting('nias_login_settings', 'nias-login-meta_roles');
    register_setting('nias_login_settings', 'nias-login-meta_fields');

    // نظرسنجی محصولات (مودال اجباری مجزا)
    register_setting('nias_login_settings', 'nias_review_survey_enabled');
    register_setting('nias_login_settings', 'nias_review_survey_scope', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'all',
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_product_id', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_closable', [
        'type'    => 'boolean',
        'default' => 1,
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_max_shows', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1,
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_delay_days', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_title', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
    register_setting('nias_login_settings', 'nias_review_survey_subtitle', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    // ورود / ثبت‌نام با حساب گوگل
    register_setting('nias_login_settings', 'nias_google_login_activate');
    register_setting('nias_login_settings', 'nias_google_client_id', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
    register_setting('nias_login_settings', 'nias_google_client_secret', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
    register_setting('nias_login_settings', 'nias_google_button_text', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    /* -------------------------------------------------------------------------- */
    /*                                تنظیمات ایمیل                               */
    /* -------------------------------------------------------------------------- */
    register_setting('nias_login_settings', 'nias_email_activate');
    register_setting('nias_login_settings', 'nias_email_phone_activate');
    register_setting('nias_login_settings', 'nias_email_gateway', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'wp_mail',
    ));
    register_setting('nias_login_settings', 'nias_smtp_host', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smtp_user', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smtp_pass', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smtp_port', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_smtp_secure', 'sanitize_text_field');
    register_setting('nias_login_settings', 'nias_email_message_template', array(
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'default' => '<p>کد ورود شما: <strong>{code}</strong></p>', // Default template
    ));
    register_setting('nias_login_settings', 'nias_email_password_message_template', array(
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'default' => '<p>پسورد جدید شما: <strong>{password}</strong></p>', // Default template
    ));

    /* -------------------------------------------------------------------------- */
    /*                              تنظیمات امنیتی                                */
    /* -------------------------------------------------------------------------- */
    register_setting('nias_login_settings', 'nias_security_mode', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'block',
    ));

    // Block mode settings
    register_setting('nias_login_settings', 'nias_block_time_window', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 60,
    ));
    register_setting('nias_login_settings', 'nias_block_min_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 3,
    ));

    // Cooldown mode settings - Level 1
    register_setting('nias_login_settings', 'nias_cooldown_level1_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 2,
    ));
    register_setting('nias_login_settings', 'nias_cooldown_level1_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 30,
    ));

    // Cooldown mode settings - Level 2
    register_setting('nias_login_settings', 'nias_cooldown_level2_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4,
    ));
    register_setting('nias_login_settings', 'nias_cooldown_level2_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 120,
    ));

    // Cooldown mode settings - Level 3
    register_setting('nias_login_settings', 'nias_cooldown_level3_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 6,
    ));
    register_setting('nias_login_settings', 'nias_cooldown_level3_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 600,
    ));

    // Cooldown mode settings - Level 4
    register_setting('nias_login_settings', 'nias_cooldown_level4_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 8,
    ));
    register_setting('nias_login_settings', 'nias_cooldown_level4_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1800,
    ));

    // Cooldown max attempts
    register_setting('nias_login_settings', 'nias_cooldown_max_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 10,
    ));

    // Security logging
    register_setting('nias_login_settings', 'nias_security_logging', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1,
    ));

    // Security cleanup days
    register_setting('nias_login_settings', 'nias_security_cleanup_days', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 30,
    ));

    // Login log cleanup days
    register_setting('nias_login_settings', 'nias_login_log_cleanup_days', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 30,
    ));

    register_setting('nias_login_settings', 'nias_replace_wp_login');
    register_setting('nias_login_settings', 'nias_block_admin_phone_login');
    register_setting('nias_login_settings', 'nias_default_user_role', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_key',
        'default'           => 'subscriber',
    ));
    register_setting('nias_login_settings', 'nias_block_dashboard_access');

    // بهینه‌سازی — هر دو پیش‌فرض خاموش‌اند؛ روی بعضی سایت‌ها (کش سمت سرور، CDN یا
    // افزونه‌های بهینه‌سازی) باعث می‌شوند فایل قدیمی سرو شود و افزونه درست کار نکند
    register_setting('nias_login_settings', 'nias_optimize_minify', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
    register_setting('nias_login_settings', 'nias_optimize_cache', [
        'type'    => 'boolean',
        'default' => 0,
    ]);
}
add_action('admin_init', 'nias_login_register_settings');

/* -------------------------------------------------------------------------- */
/*   Replace wp-login.php with Nias form (inline, no redirect)                */
/* -------------------------------------------------------------------------- */

/**
 * The auth blocker (class-nias-auth-blocker.php) now handles all login page
 * replacement via early PHP redirects at init priority 1. The inline form
 * injection on wp-login.php is no longer used and always returns false.
 */
function nias_should_replace_login()
{
    return false;
}

// Enqueue Nias CSS + JS on the WP login page
add_action('login_enqueue_scripts', 'nias_wp_login_enqueue');
function nias_wp_login_enqueue()
{
    if (!nias_should_replace_login()) return;
    [$login_css_url, $login_css_ver] = nias_login_asset('assets/css/style.css');
    wp_enqueue_style('nias-login-style', $login_css_url, [], $login_css_ver);

    wp_enqueue_script('jquery');

    [$toast_url, $toast_ver] = nias_login_asset('assets/js/nias-toast.js');
    wp_enqueue_script('nias-toast', $toast_url, [], $toast_ver, true);

    [$login_js_url, $login_js_ver] = nias_login_asset('assets/js/script.js');
    wp_enqueue_script('nias-login-script', $login_js_url, ['jquery', 'nias-toast'], $login_js_ver, true);
    $cfg = wp_json_encode([
        'lockedPages'          => [],
        'clickLinks'           => ['.nias-wp-login-wrap'],
        'countdown_duration'   => (int) get_option('nias_countdown_duration', 120),
        'password_only_mode'   => (bool) (get_option('nias_password_activate') && !get_option('nias_password_otp_activate')),
        'password_otp_activate'=> (bool) get_option('nias_password_otp_activate'),
        'manual_password_activate' => (bool) get_option('nias_manual_password_activate'),
        'ajax_url'             => esc_url(home_url('/nias-login')),
        'site_url'             => esc_url(home_url()),
    ]);
    wp_add_inline_script('nias-login-script', 'window.nias=' . $cfg . ';', 'before');
}

// Inject CSS overrides: hide WP form, make Nias modal static
add_action('login_head', 'nias_wp_login_head');
function nias_wp_login_head()
{
    if (!nias_should_replace_login()) return;
    ?>
    <style>
    #loginform, #backtoblog, #nav, .language-switcher, .login-action-login p.submit { display: none !important; }
    .login #login { width: auto !important; max-width: 460px; }
    .nias-wp-login-wrap .nias-modal-box    { position: static !important; background: transparent !important; }
    .nias-wp-login-wrap .nias-main-modal   { position: static !important; transform: none !important;
                                              box-shadow: 0 4px 24px rgba(0,0,0,.12) !important;
                                              border-radius: 16px !important; width: 100% !important; }
    .nias-wp-login-wrap .nias-close-modal  { display: none !important; }
    </style>
    <?php
}

// Inject the Nias form above the (hidden) WP form via login_message filter
add_filter('login_message', 'nias_wp_login_inject_form');
function nias_wp_login_inject_form($msg)
{
    if (!nias_should_replace_login()) return $msg;
    ob_start();
    echo '<div class="nias-wp-login-wrap">';
    if (function_exists('nias_show_modal')) {
        nias_show_modal();
    }
    echo '</div>';
    return ob_get_clean() . $msg;
}

/**
 * پاکسازی و اعتبارسنجی تنظیمات فیلدهای خرید سریع
 */
function nias_sanitize_fastsell_fields($input)
{
    if (!is_array($input)) {
        return [];
    }
    $clean = [];
    foreach ($input as $key => $cfg) {
        $safe_key = sanitize_key($key);
        if (empty($safe_key)) continue;
        $clean[$safe_key] = [
            'order'    => isset($cfg['order'])    ? absint($cfg['order'])  : 99,
            'visible'  => isset($cfg['visible'])  ? (intval($cfg['visible'])  === 1) : false,
            'required' => isset($cfg['required']) ? (intval($cfg['required']) === 1) : false,
        ];
    }
    return $clean;
}

/**
 * پاکسازی لیست شناسه‌های درگاه پرداخت / روش حمل و نقل خرید سریع تکی
 * شناسه روش حمل شامل کاراکتر دونقطه است (مثل flat_rate:1) پس از sanitize_key استفاده نمی‌شود
 */
function nias_sanitize_fastsell_method_ids($input)
{
    if (!is_array($input)) {
        return [];
    }
    $clean = [];
    foreach ($input as $id) {
        $id = sanitize_text_field((string) $id);
        if ($id !== '') {
            $clean[] = $id;
        }
    }
    return array_values(array_unique($clean));
}

/**
 * نرمال‌سازی فهرست سلکتورهای CSS برای دکمه «افزودن به سبد» سفارشی.
 * المان سفارشی می‌تواند هر تگی باشد (a، button، div ...) و با کلاس یا آیدی هدف‌گذاری شود.
 * ورودی: سلکتورها جداشده با کاما (مثل: .my-btn, #buy-now, button.add-cart).
 * خروجی: همان فهرست پس از پاک‌سازی کاراکترهای خطرناک، جدا شده با کاما.
 */
function nias_sanitize_fastsell_css_class_list($input)
{
    if (is_array($input)) {
        $input = implode(',', $input);
    }
    $tokens = explode(',', (string) $input);
    $clean  = [];
    foreach ($tokens as $token) {
        $token = trim($token);
        // فقط کاراکترهای مجاز سلکتور CSS را نگه می‌داریم
        // (حروف، اعداد، _ - . # [ ] = : ( ) > + ~ * فاصله و کوتیشن)
        $token = preg_replace('/[^A-Za-z0-9_\-\.\#\[\]\=\:\(\)\>\+\~\*\s"\']/', '', $token);
        $token = trim($token);
        if ($token !== '') {
            $clean[] = $token;
        }
    }
    return implode(', ', array_values(array_unique($clean)));
}

/* -------------------------------------------------------------------------- */
/*                        AJAX Handler: Gateway Test Send                     */
/* -------------------------------------------------------------------------- */
/**
 * یادآوری ذخیره‌سازی را به پیام‌های «... وارد نشده است» نتیجه تست اضافه می‌کند.
 *
 * این پیام‌ها را خودِ کلاس درگاه می‌سازد و مقادیر را از دیتابیس می‌خواند، پس
 * مدیری که فیلد را پر کرده ولی هنوز ذخیره نکرده دقیقاً همین خطا را می‌گیرد و
 * بدون این یادآوری فکر می‌کند مقدارش را وارد نکرده است. یادآوری فقط اینجا
 * (مسیر تست در پیشخوان) اضافه می‌شود تا در خطاهای سمت کاربر ظاهر نشود.
 */
function nias_test_result_save_hint($message)
{
    $message = (string) $message;

    $config_errors = ['وارد نشده', 'تنظیم نشده', 'انتخاب نشده', 'پیکربندی نشده', 'خالی است'];

    foreach ($config_errors as $needle) {
        if (mb_strpos($message, $needle) !== false) {
            return preg_replace('/[\s\.،]+$/u', '', $message) . ' — ابتدا تنظیمات را ذخیره کنید.';
        }
    }

    return $message;
}

/**
 * تست ایمیل را با «کاوشگر» اجرا می‌کند تا معلوم شود wp_mail واقعاً چه کرد.
 *
 * چرا لازم است: wp_mail وقتی true برمی‌گرداند فقط یعنی پیام بدون خطا به لایه‌ی
 * ارسال سپرده شد — نه اینکه به صندوق گیرنده رسیده باشد. خودِ هسته وردپرس هم در
 * wp-includes/pluggable.php همین را تصریح کرده است. دو حالت رایج «موفق ولی
 * نرسید» را اینجا تشخیص می‌دهیم:
 *
 *   ۱) افزونه‌ی دیگری با فیلتر pre_wp_mail کل wp_mail را رهگیری کرده و بدون
 *      ارسال، true برگردانده (در این حالت هوک phpmailer_init اصلاً اجرا نمی‌شود).
 *   ۲) پیام با mail() خود PHP به MTA محلی سپرده شده؛ سرور گیرنده به‌خاطر
 *      نبود SPF/DKIM یا دامنه‌ی نامعتبرِ فرستنده آن را دور می‌اندازد.
 *
 * @param callable $send تابعی که ارسال را انجام می‌دهد
 * @return array{result:mixed,probe:array}
 */
function nias_email_test_probe(callable $send): array
{
    $probe = [
        'phpmailer_ran' => false,
        'mailer'        => '',
        'host'          => '',
        'from'          => '',
        'error'         => '',
    ];

    $on_init = function ($phpmailer) use (&$probe) {
        $probe['phpmailer_ran'] = true;
        $probe['mailer'] = isset($phpmailer->Mailer) ? (string) $phpmailer->Mailer : '';
        $probe['host']   = isset($phpmailer->Host)   ? (string) $phpmailer->Host   : '';
        $probe['from']   = isset($phpmailer->From)   ? (string) $phpmailer->From   : '';
    };

    $on_fail = function ($wp_error) use (&$probe) {
        if (is_wp_error($wp_error)) {
            $probe['error'] = $wp_error->get_error_message();
        }
    };

    add_action('phpmailer_init', $on_init, 9999);
    add_action('wp_mail_failed', $on_fail);

    $result = $send();

    remove_action('phpmailer_init', $on_init, 9999);
    remove_action('wp_mail_failed', $on_fail);

    return ['result' => $result, 'probe' => $probe];
}

/**
 * توضیح خوانا از نتیجه‌ی کاوشگر — به پیام نتیجه‌ی تست اضافه می‌شود.
 */
function nias_email_test_note(array $probe, bool $sent): string
{
    if (!$sent) {
        return $probe['error'] ? '<br><small>' . esc_html($probe['error']) . '</small>' : '';
    }

    if (!$probe['phpmailer_ran']) {
        return '<br><small>⚠️ افزونه‌ی دیگری wp_mail را رهگیری کرده (فیلتر pre_wp_mail) و بدون آنکه خودش ارسال را انجام دهد پاسخ موفق داده است — معمولاً افزونه‌های SMTP. وضعیت واقعی ارسال را در لاگ همان افزونه ببینید.</small>';
    }

    if ($probe['mailer'] === 'smtp') {
        return '<br><small>پیام از طریق SMTP به «' . esc_html($probe['host']) . '» تحویل داده شد'
            . ($probe['from'] ? ' (فرستنده: ' . esc_html($probe['from']) . ')' : '')
            . '. تحویل به سرور به معنی رسیدن به صندوق گیرنده نیست؛ اگر نرسید پوشه اسپم را ببینید.</small>';
    }

    return '<br><small>پیام با تابع mail() خود PHP به سرور ایمیل هاست سپرده شد'
        . ($probe['from'] ? ' (فرستنده: ' . esc_html($probe['from']) . ')' : '')
        . '. این یعنی PHP آن را پذیرفت، نه اینکه به گیرنده رسیده باشد — اگر ایمیل نمی‌رسد، پوشه اسپم و رکوردهای SPF/DKIM دامنه فرستنده را بررسی کنید یا درگاه را روی SMTP بگذارید.</small>';
}

add_action('wp_ajax_nias_test_gateway', 'nias_ajax_test_gateway');
function nias_ajax_test_gateway()
{
    check_ajax_referer('nias_db_actions', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $type  = isset($_POST['type'])  ? sanitize_text_field($_POST['type'])  : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email'])      : '';

    $test_code     = '12345';
    $test_password = 'Test@9876';
    $extra_note    = '';

    switch ($type) {
        case 'otp_sms':
            if (empty($phone)) { wp_send_json_error('شماره موبایل وارد نشده است'); }
            $gw     = new Nias_SMS_Gateway();
            $result = $gw->send_code($phone, $test_code);
            break;

        case 'pass_sms':
            if (empty($phone)) { wp_send_json_error('شماره موبایل وارد نشده است'); }
            $gw     = new Nias_SMS_Gateway();
            $result = $gw->send_password($phone, $test_password);
            break;

        case 'email':
            if (empty($email)) { wp_send_json_error('آدرس ایمیل وارد نشده است'); }
            $gw = new Nias_Email();

            $probed = nias_email_test_probe(function () use ($gw, $email, $test_code) {
                return $gw->send_email_gateway('', $email, $test_code, 'تست درگاه ایمیل — نیاس', null, []);
            });
            $result = $probed['result'];

            // کاوشگر فقط برای مسیر wp_mail معنا دارد؛ حالت SMTP اختصاصی افزونه
            // با PHPMailer خودش ارسال می‌کند و از wp_mail عبور نمی‌کند
            if ('smtp' !== get_option('nias_email_gateway', 'wp_mail')) {
                $extra_note = nias_email_test_note($probed['probe'], $result === true);
            }
            break;

        case 'bale':
            if (empty($phone)) { wp_send_json_error('شماره موبایل وارد نشده است'); }
            $gw     = new Nias_SMS_Gateway();
            $result = $gw->send_bale($phone, $test_code);
            break;

        default:
            wp_send_json_error('نوع تست نامعتبر است');
    }

    // true = gateway returned non-string success; string may be success or error message
    if ($result === true || (is_string($result) && mb_strpos($result, 'موفقیت') !== false)) {
        wp_send_json_success([
            'msg'  => ($result === true ? 'ارسال با موفقیت انجام شد ✓' : $result) . $extra_note,
            'ok'   => true,
        ]);
    } else {
        wp_send_json_success([
            'msg' => nias_test_result_save_hint(is_string($result) ? $result : 'ارسال ناموفق بود') . $extra_note,
            'ok'  => false,
        ]);
    }
}

add_action('wp_ajax_nias_test_wc_sms', 'nias_ajax_test_wc_sms');
function nias_ajax_test_wc_sms()
{
    check_ajax_referer('nias_db_actions', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    if (!class_exists('Nias_WC_Status_SMS')) {
        require_once NIAS_LOGIN_INC . 'class-wc-status-sms.php';
    }
    if (!Nias_WC_Status_SMS::is_wc_active()) {
        wp_send_json_error('ووکامرس فعال نیست.');
    }

    $status    = isset($_POST['status'])    ? sanitize_key($_POST['status'])             : '';
    $phone     = isset($_POST['phone'])     ? sanitize_text_field($_POST['phone'])       : '';
    $recipient = isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient'])   : 'customer';
    $order_id  = isset($_POST['order_id'])  ? absint($_POST['order_id'])                 : 0;

    if (empty($status)) { wp_send_json_error('وضعیت انتخاب نشده است'); }
    if (empty($phone))  { wp_send_json_error('شماره موبایل وارد نشده است'); }

    $result = Nias_WC_Status_SMS::get_instance()->send_test($status, $phone, $recipient, $order_id);

    if ($result === true || (is_string($result) && mb_strpos($result, 'موفقیت') !== false)) {
        wp_send_json_success([
            'ok'  => true,
            'msg' => $result === true ? 'پیامک تست با موفقیت ارسال شد ✓' : $result,
        ]);
    } else {
        wp_send_json_success([
            'ok'  => false,
            'msg' => nias_test_result_save_hint(is_string($result) ? $result : 'ارسال ناموفق بود'),
        ]);
    }
}

/* -------------------------------------------------------------------------- */
/*                         AJAX Handlers for Database Tab                     */
/* -------------------------------------------------------------------------- */

// Get blocked IPs with pagination
add_action('wp_ajax_nias_get_blocked_ips', 'nias_ajax_get_blocked_ips');
function nias_ajax_get_blocked_ips()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'nias_blockedip_sms';

    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
    $offset = ($page - 1) * $per_page;

    // Get total count
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total / $per_page);

    // Get paginated results
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY time DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));

    $items = array();
    foreach ($results as $row) {
        $items[] = array(
            'ip' => $row->ip,
            'time' => date_i18n('Y/m/d H:i:s', strtotime($row->time)),
            'block' => $row->block
        );
    }

    wp_send_json_success(array(
        'items' => $items,
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page
    ));
}

// Clear all blocked IPs
add_action('wp_ajax_nias_clear_blocked_ips', 'nias_ajax_clear_blocked_ips');
function nias_ajax_clear_blocked_ips()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'nias_blockedip_sms';

    $result = $wpdb->query("DELETE FROM $table_name");

    if ($result !== false) {
        wp_send_json_success('آیپی ها با موفقیت پاک شدند');
    } else {
        wp_send_json_error('خطا در پاکسازی آیپی ها');
    }
}

// Get recent verification codes
add_action('wp_ajax_nias_get_recent_codes', 'nias_ajax_get_recent_codes');
function nias_ajax_get_recent_codes()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'nias_sms_login';

    $results = $wpdb->get_results(
        "SELECT ID, user_id, phone, email, code, ip, status, user_status, 
                send_to_phone, send_to_email, verified, attempts,
                created_at, verified_at, expired_at 
        FROM $table_name 
        ORDER BY created_at DESC 
        LIMIT 20"
    );

    $items = array();
    foreach ($results as $row) {
        // تعیین نوع ارسال
        $send_method = array();
        if ($row->send_to_phone == 1)
            $send_method[] = 'پیامک';
        if ($row->send_to_email == 1)
            $send_method[] = 'ایمیل';
        $send_method_str = !empty($send_method) ? implode(' + ', $send_method) : 'نامشخص';

        // تعیین وضعیت کاربر
        $user_status_label = '';
        switch ($row->user_status) {
            case 'new':
                $user_status_label = 'کاربر جدید';
                break;
            case 'existing':
                $user_status_label = 'کاربر موجود';
                break;
            default:
                $user_status_label = 'نامشخص';
        }

        // تعیین وضعیت تایید
        $status_label = '';
        $status_color = '';
        if ($row->verified == 1) {
            $status_label = '✓ تایید شده';
            $status_color = '#00c72c';
        } else {
            // بررسی انقضا
            $now = current_time('mysql');
            if ($row->expired_at < $now) {
                $status_label = '✗ منقضی شده';
                $status_color = '#dc3545';
            } else {
                $status_label = '⏳ در انتظار';
                $status_color = '#ffc107';
            }
        }

        $items[] = array(
            'id' => $row->ID,
            'user_id' => $row->user_id > 0 ? $row->user_id : '-',
            'phone' => $row->phone ?: '-',
            'email' => $row->email ?: '-',
            'code' => $row->code,
            'ip' => $row->ip,
            'send_method' => $send_method_str,
            'user_status' => $user_status_label,
            'status' => $status_label,
            'status_color' => $status_color,
            'attempts' => $row->attempts,
            'created_at' => date_i18n('Y/m/d H:i:s', strtotime($row->created_at)),
            'verified_at' => $row->verified_at ? date_i18n('Y/m/d H:i:s', strtotime($row->verified_at)) : '-',
            'expired_at' => date_i18n('Y/m/d H:i:s', strtotime($row->expired_at))
        );
    }

    wp_send_json_success($items);
}

// Migrate user meta data
add_action('wp_ajax_nias_migrate_meta', 'nias_ajax_migrate_meta');
function nias_ajax_migrate_meta()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $old_meta_key = isset($_POST['old_meta_key']) ? sanitize_text_field($_POST['old_meta_key']) : '';

    if (empty($old_meta_key)) {
        wp_send_json_error('لطفاً نام فیلد متای قدیمی را وارد کنید');
    }

    global $wpdb;

    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $old_meta_key)
    );

    if ($rows === null) {
        wp_send_json_error('خطا در انتقال داده‌ها');
    }

    if (empty($rows)) {
        wp_send_json_error('هیچ داده‌ای با این کلید پیدا نشد.');
    }

    $migrated = 0;
    $skipped  = 0;

    foreach ($rows as $row) {
        $normalized_phone = nias_normalize_phone_number($row->meta_value);

        if ($normalized_phone !== '') {
            update_user_meta($row->user_id, 'phone', $normalized_phone);
            $migrated++;
        } else {
            $skipped++;
        }
    }

    // پاکسازی فیلد قدیمی پس از انتقال (در صورتی که همان فیلد phone نباشد)
    if ($old_meta_key !== 'phone') {
        $wpdb->delete($wpdb->usermeta, array('meta_key' => $old_meta_key));
    }

    wp_send_json_success(array(
        'message'  => sprintf('انتقال انجام شد. %d شماره استاندارد (با ۰ ابتدایی) شد و %d شماره نامعتبر رد شد.', $migrated, $skipped),
        'count'    => $migrated,
        'migrated' => $migrated,
        'skipped'  => $skipped
    ));
}

// خواندن ارقام خام یک شماره برای نمایش/پردازش
function nias_phone_raw_digits($value)
{
    return preg_replace('/\D+/', '', (string) $value);
}

// پیش‌نمایش شماره‌های نامعتبر یک فیلد متا
add_action('wp_ajax_nias_preview_invalid_phones', 'nias_ajax_preview_invalid_phones');
function nias_ajax_preview_invalid_phones()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : 'phone';
    if (empty($meta_key)) {
        $meta_key = 'phone';
    }

    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key)
    );

    if ($rows === null) {
        wp_send_json_error('خطا در خواندن داده‌ها');
    }

    $total      = count($rows);
    $invalid    = array();
    $prefix_map = array();

    foreach ($rows as $row) {
        if (nias_normalize_phone_number($row->meta_value) !== '') {
            continue;
        }

        $digits    = nias_phone_raw_digits($row->meta_value);
        $invalid[] = array(
            'user_id' => (int) $row->user_id,
            'raw'     => (string) $row->meta_value,
            'digits'  => $digits,
            'length'  => strlen($digits),
        );

        // توزیع بر اساس چهار رقم ابتدایی برای کمک به تصمیم‌گیری
        if ($digits !== '') {
            $p = substr($digits, 0, 4);
            $prefix_map[$p] = isset($prefix_map[$p]) ? $prefix_map[$p] + 1 : 1;
        }
    }

    arsort($prefix_map);
    $prefixes = array();
    foreach (array_slice($prefix_map, 0, 8, true) as $p => $c) {
        $prefixes[] = array('prefix' => $p, 'count' => $c);
    }

    wp_send_json_success(array(
        'meta_key'      => $meta_key,
        'total'         => $total,
        'invalid_count' => count($invalid),
        'samples'       => array_slice($invalid, 0, 20),
        'prefixes'      => $prefixes,
    ));
}

// اصلاح گروهی شماره‌های نامعتبر (با امکان پیش‌نمایش بدون ذخیره)
add_action('wp_ajax_nias_fix_invalid_phones', 'nias_ajax_fix_invalid_phones');
function nias_ajax_fix_invalid_phones()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : 'phone';
    if (empty($meta_key)) {
        $meta_key = 'phone';
    }

    $strip      = isset($_POST['strip_count']) ? absint($_POST['strip_count']) : 0;
    $add_prefix = isset($_POST['add_prefix']) ? preg_replace('/\D+/', '', (string) $_POST['add_prefix']) : '';
    $dry_run    = !empty($_POST['dry_run']);

    if ($strip === 0 && $add_prefix === '') {
        wp_send_json_error('حداقل یک تغییر را مشخص کنید: افزودن پیش‌شماره یا حذف ارقام ابتدایی.');
    }

    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key)
    );

    if ($rows === null) {
        wp_send_json_error('خطا در خواندن داده‌ها');
    }

    $fixed         = 0;
    $still_invalid = 0;
    $samples       = array();

    foreach ($rows as $row) {
        // فقط شماره‌های نامعتبر پردازش می‌شوند
        if (nias_normalize_phone_number($row->meta_value) !== '') {
            continue;
        }

        $digits     = nias_phone_raw_digits($row->meta_value);
        $candidate  = $add_prefix . substr($digits, $strip);
        $normalized = nias_normalize_phone_number($candidate);
        $ok         = ($normalized !== '');

        if ($ok) {
            $fixed++;
            if (!$dry_run) {
                update_user_meta($row->user_id, 'phone', $normalized);
            }
        } else {
            $still_invalid++;
        }

        if (count($samples) < 20) {
            $samples[] = array(
                'user_id' => (int) $row->user_id,
                'before'  => $digits,
                'after'   => $ok ? $normalized : '—',
                'ok'      => $ok,
            );
        }
    }

    $message = $dry_run
        ? sprintf('پیش‌نمایش: با این تنظیمات %d شماره معتبر می‌شود و %d شماره همچنان نامعتبر می‌ماند. (هنوز ذخیره نشده)', $fixed, $still_invalid)
        : sprintf('%d شماره اصلاح و در فیلد phone ذخیره شد. %d شماره همچنان نامعتبر ماند.', $fixed, $still_invalid);

    wp_send_json_success(array(
        'dry_run'       => $dry_run,
        'fixed'         => $fixed,
        'still_invalid' => $still_invalid,
        'samples'       => $samples,
        'message'       => $message,
    ));
}

// اصلاح دستی شمارهٔ یک کاربر
add_action('wp_ajax_nias_save_single_phone', 'nias_ajax_save_single_phone');
function nias_ajax_save_single_phone()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $raw     = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

    if ($user_id <= 0) {
        wp_send_json_error('شناسه کاربر نامعتبر است.');
    }
    if (!get_userdata($user_id)) {
        wp_send_json_error('کاربر یافت نشد.');
    }
    if ($raw === '') {
        wp_send_json_error('لطفاً شماره را وارد کنید.');
    }

    $phone = nias_normalize_phone_number($raw);
    $valid = ($phone !== '');

    // اگر به فرمت استاندارد نرسید، دست‌کم الزام «شروع با ۰» را تضمین کن
    if (!$valid) {
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            wp_send_json_error('شمارهٔ واردشده هیچ رقمی ندارد.');
        }
        $phone = ($digits[0] === '0') ? $digits : '0' . $digits;
    }

    update_user_meta($user_id, 'phone', $phone);

    wp_send_json_success(array(
        'user_id' => $user_id,
        'phone'   => $phone,
        'valid'   => $valid,
        'message' => $valid
            ? 'شماره استاندارد شد و ذخیره گردید.'
            : 'شماره ذخیره شد (با صفر ابتدایی) اما فرمت موبایل استاندارد ۰۹XXXXXXXXX را ندارد.',
    ));
}

/* -------------------------------------------------------------------------- */
/*                     بررسی و ترمیم جداول دیتابیس افزونه                     */
/* -------------------------------------------------------------------------- */
add_action('wp_ajax_nias_repair_tables', 'nias_ajax_repair_tables');
function nias_ajax_repair_tables()
{
    check_ajax_referer('nias_db_actions', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    global $wpdb;

    // ستون‌های مورد انتظار هر جدول (مطابق اسکیمای فعال‌سازی)
    $expected = [
        'nias_sms_login'    => ['ID', 'user_id', 'phone', 'email', 'code', 'attempts', 'ip', 'status', 'user_status', 'send_to_phone', 'send_to_email', 'verified', 'created_at', 'updated_at', 'verified_at', 'expired_at'],
        'nias_blockedip_sms' => ['ID', 'ip', 'block_reason', 'block', 'blocked_by', 'unblock_at', 'is_permanent', 'time', 'updated_at'],
        'nias_security_log' => ['ID', 'event_type', 'identifier', 'user_id', 'ip_address', 'user_agent', 'details', 'severity', 'created_at'],
        'nias_statistics'   => ['ID', 'stat_date', 'stat_type', 'stat_key', 'stat_value', 'additional_data', 'created_at', 'updated_at'],
    ];

    // وضعیت جدول‌ها قبل از ترمیم
    $before = [];
    foreach ($expected as $short => $cols) {
        $table = $wpdb->prefix . $short;
        $exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        $before[$short] = [
            'exists'  => $exists,
            'columns' => $exists ? (array) $wpdb->get_col("SHOW COLUMNS FROM `$table`", 0) : [],
        ];
    }

    // اجرای ترمیم (dbDelta — افزودن جدول/ستون‌های ناقص بدون حذف داده)
    if (!function_exists('nias_create_all_tables')) {
        require_once NIAS_LOGIN_INC . 'activation.php';
    }
    if (!function_exists('nias_create_all_tables')) {
        wp_send_json_error('تابع ساخت جداول یافت نشد');
    }
    nias_create_all_tables();

    // وضعیت پس از ترمیم + ساخت گزارش
    $rows = '';
    $had_problem = false;

    foreach ($expected as $short => $cols) {
        $table = $wpdb->prefix . $short;
        $exists_after  = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        $columns_after = $exists_after ? (array) $wpdb->get_col("SHOW COLUMNS FROM `$table`", 0) : [];

        $created   = !$before[$short]['exists'] && $exists_after;
        $added     = array_values(array_diff($columns_after, $before[$short]['columns']));
        $missing   = array_values(array_diff($cols, $columns_after));

        if ($created || !empty($added) || !empty($missing) || !$exists_after) {
            $had_problem = true;
        }

        if (!$exists_after) {
            $status = '<span style="color:#c0392b;font-weight:700;">✗ ساخته نشد</span>';
            $detail = 'ترمیم ناموفق بود؛ دسترسی دیتابیس را بررسی کنید.';
        } elseif ($created) {
            $status = '<span style="color:#16a34a;font-weight:700;">✓ ساخته شد</span>';
            $detail = 'جدول وجود نداشت و ایجاد شد.';
        } elseif (!empty($added)) {
            $status = '<span style="color:#16a34a;font-weight:700;">✓ ترمیم شد</span>';
            $detail = 'ستون‌های افزوده‌شده: ' . esc_html(implode('، ', $added));
        } else {
            $status = '<span style="color:#16a34a;font-weight:700;">✓ سالم</span>';
            $detail = 'بدون تغییر — ساختار کامل بود.';
        }

        if (!empty($missing) && $exists_after) {
            $detail .= ' | ستون‌های هنوز ناقص: ' . esc_html(implode('، ', $missing));
        }

        $rows .= '<tr><td dir="ltr" style="font-family:monospace">' . esc_html($table) . '</td><td>' . $status . '</td><td>' . $detail . '</td></tr>';
    }

    $html  = '<div class="nias-db-table" style="margin-top:10px"><table><thead><tr><th>جدول</th><th>وضعیت</th><th>توضیح</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
    $html .= '<div class="nias-login-note nias-login-note--' . ($had_problem ? 'success' : 'success') . '" style="margin-top:10px"><span>✓ بررسی و ترمیم انجام شد. هیچ داده‌ای حذف نشده است.</span></div>';

    wp_send_json_success(['message' => $html]);
}




/* -------------------------------------------------------------------------- */
/*                    فعالسازی کد ادیتور برای درج متن ایمیل                   */
/* -------------------------------------------------------------------------- */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_niasloginsignup') {
        return;
    }

    $settings = wp_enqueue_code_editor(['type' => 'text/html']);

    if ($settings !== false) {
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        // لود تم دارک (مثلا material)
        wp_enqueue_style(
            'codemirror-material',
            plugin_dir_url(__FILE__) . 'material.min.css',
            [],
            '5.65.16'
        );

        // اضافه کردن تم به تنظیمات CodeMirror
        $settings['codemirror']['theme'] = 'material';

        wp_add_inline_script(
            'wp-theme-plugin-editor',
            'jQuery(function($){
                wp.codeEditor.initialize($("#nias_email_message_template"), ' . wp_json_encode($settings) . ');
                wp.codeEditor.initialize($("#nias_email_password_message_template"), ' . wp_json_encode($settings) . ');
            });'
        );
    }
});

/* -------------------------------------------------------------------------- */

// اجرای عملیات انتقال متا بدون تداخل
add_action('admin_init', 'nias_process_meta_migration');

function nias_normalize_phone_number($phone)
{
    if (!is_string($phone)) {
        $phone = (string) $phone;
    }

    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }

    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }

    if (preg_match('/^98(\d{10})$/', $digits, $matches)) {
        $digits = '0' . $matches[1];
    } elseif (preg_match('/^0?9(\d{9})$/', $digits, $matches)) {
        $digits = '09' . $matches[1];
    } elseif (preg_match('/^0(\d{10})$/', $digits, $matches)) {
        $digits = '0' . $matches[1];
    } elseif (preg_match('/^(\d{10})$/', $digits, $matches)) {
        $digits = '0' . $matches[1];
    } elseif (preg_match('/^(\d{9})$/', $digits, $matches)) {
        $digits = '09' . $matches[1];
    }

    if (strlen($digits) > 11) {
        $digits = substr($digits, -11);
    }

    return preg_match('/^09\d{9}$/', $digits) ? $digits : '';
}

function nias_process_meta_migration()
{
    if (
        isset($_POST['nias_action'])
        && $_POST['nias_action'] === 'meta_migration'
        && check_admin_referer('nias_meta_migration')
    ) {
        global $wpdb;

        $old_meta_key = sanitize_text_field($_POST['old_meta_key']);
        if (empty($old_meta_key)) {
            add_settings_error('nias_meta_migration', 'empty_key', 'لطفاً نام فیلد متای قدیمی را وارد کنید.', 'error');
            return;
        }

        $users = $wpdb->get_results(
            $wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $old_meta_key)
        );

        if ($users) {
            $migrated = 0;
            $skipped = 0;

            foreach ($users as $user) {
                $normalized_phone = nias_normalize_phone_number($user->meta_value);

                if ($normalized_phone !== '') {
                    update_user_meta($user->user_id, 'phone', $normalized_phone);
                    $migrated++;
                } else {
                    $skipped++;
                }
            }

            add_settings_error(
                'nias_meta_migration',
                'success',
                sprintf('انتقال داده‌ها با موفقیت انجام شد. %d شماره استاندارد شد و %d شماره نامعتبر رد شد.', $migrated, $skipped),
                'updated'
            );
        } else {
            add_settings_error('nias_meta_migration', 'not_found', 'هیچ داده‌ای با این کلید پیدا نشد.', 'error');
        }
    }
}
