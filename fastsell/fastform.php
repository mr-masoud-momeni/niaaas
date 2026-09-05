<?php
defined('ABSPATH') || exit;

/**
 * فرم سفارش سریع (شورت‌کد) — نیاس لاگین
 *
 * کاربر فقط شماره موبایل را وارد می‌کند؛ حساب کاربری ساخته/پیدا می‌شود،
 * سفارش با محصول موردنظر ثبت شده و مستقیماً به درگاه پرداخت هدایت می‌شود.
 *
 * شورت‌کدها:
 *   [nias_fast_order product_id="123"]  فرم ثبت سفارش سریع
 *   [nias_order_status]                 کارت وضعیت سفارش (پرداخت‌شده / ناموفق / در انتظار)
 */

if (!function_exists('nias_fastorder_log')) {
    function nias_fastorder_log($message, $data = null)
    {
        if (!(defined('WP_DEBUG') && WP_DEBUG)) {
            return;
        }
        $line = '[NIAS FASTORDER] ' . $message;
        if (null !== $data) {
            $line .= ' | ' . print_r($data, true);
        }
        error_log($line);
    }
}

/* ------------------------------------------------------------------ *
 *  انتخاب درگاه پرداخت
 * ------------------------------------------------------------------ */

/**
 * انتخاب اولین درگاه فعال (در صورت نبود تنظیم دستی)
 */
function nias_fastorder_get_default_gateway()
{
    nias_fastorder_log('nias_fastorder_get_default_gateway() اجرا شد');

    $gateways = WC()->payment_gateways()->get_available_payment_gateways();

    nias_fastorder_log('تعداد درگاه‌های فعال', count($gateways));
    nias_fastorder_log('لیست شناسه درگاه‌های فعال', array_keys($gateways));

    if (empty($gateways)) {
        nias_fastorder_log('هیچ درگاه فعالی پیدا نشد - خروج با رشته خالی');
        return '';
    }

    $first = reset($gateways);
    nias_fastorder_log('درگاه انتخاب شده', $first->id);
    return $first->id;
}

function nias_fastorder_get_nonce()
{
    nias_fastorder_log('درخواست دریافت nonce رسید', array(
        'is_user_logged_in' => is_user_logged_in(),
        'ip'                => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown',
    ));

    wp_send_json_success(array('nonce' => wp_create_nonce('nias_fastorder_nonce')));
}
add_action('wp_ajax_nias_fastorder_get_nonce', 'nias_fastorder_get_nonce');
add_action('wp_ajax_nopriv_nias_fastorder_get_nonce', 'nias_fastorder_get_nonce');

/* ------------------------------------------------------------------ *
 *  شورت‌کد فرم
 * ------------------------------------------------------------------ */

function nias_fastorder_form_shortcode($atts)
{
    nias_fastorder_log('شورت‌کد nias_fast_order رندر شد', $atts);

    if (!function_exists('WC')) {
        nias_fastorder_log('ووکامرس فعال نیست - شورت‌کد چیزی برنمی‌گرداند');
        return '';
    }

    $atts = shortcode_atts(array(
        'product_id' => 0,
    ), $atts, 'nias_fast_order');

    $product_id = absint($atts['product_id']);
    if (!$product_id) {
        $product_id = get_the_ID(); // داخل صفحه محصول
    }

    nias_fastorder_log('product_id تعیین شده برای فرم', $product_id);

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable()) {
        nias_fastorder_log('محصول پیدا نشد یا قابل خرید نیست', $product_id);
        return '';
    }

    $ajax_url = admin_url('admin-ajax.php');
    $uid      = 'nias-fastorder-' . wp_rand(1000, 9999);

    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="nias-fastorder-wrap" dir="rtl">
        <style>
            #<?php echo esc_attr($uid); ?>.nias-fastorder-wrap{
                max-width:560px;margin:0 auto;font-family:inherit;text-align:right;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-label{
                font-size:15px;font-weight:700;color:#222;margin:0 0 12px;display:block;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-input-box{
                display:flex;align-items:center;border:1px solid #d9d9d9;border-radius:14px;
                padding:0 14px;background:#fff;transition:border-color .2s;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-input-box:focus-within{border-color:#1ec07a;}
            #<?php echo esc_attr($uid); ?> .nias-fo-icon{
                width:22px;height:22px;color:#9aa0a6;flex:0 0 auto;margin-left:6px;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-input{
                flex:1;border:0;outline:none;background:transparent;
                font-size:16px;padding:16px 8px;text-align:left;direction:ltr;color:#222;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-input::placeholder{color:#b5b5b5;}
            #<?php echo esc_attr($uid); ?> .nias-fo-note{
                font-size:12px;color:#9aa0a6;margin:10px 2px 18px;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-btn{
                width:100%;border:0;border-radius:14px;background:#1ec07a;color:#fff;
                font-size:17px;font-weight:700;padding:18px;cursor:pointer;
                transition:background .2s;font-family:inherit;
            }
            #<?php echo esc_attr($uid); ?> .nias-fo-btn:hover{background:#17a868;}
            #<?php echo esc_attr($uid); ?> .nias-fo-btn:disabled{opacity:.6;cursor:not-allowed;}
            #<?php echo esc_attr($uid); ?> .nias-fo-error{
                color:#e23b3b;font-size:13px;margin:0 2px 12px;display:none;
            }
        </style>

        <label class="nias-fo-label"><?php esc_html_e('شماره تماس خود را وارد کنید :', 'nias-login-signup'); ?></label>

        <div class="nias-fo-input-box">
            <svg class="nias-fo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <input type="tel" class="nias-fo-input" inputmode="numeric" autocomplete="tel"
                   placeholder="<?php esc_attr_e('مثال: ۰۹۱۲۳۴۵۶۷۸۹', 'nias-login-signup'); ?>" maxlength="14" />
        </div>

        <p class="nias-fo-note"><?php esc_html_e('شماره شما فقط برای هماهنگی سفارش استفاده می‌شود.', 'nias-login-signup'); ?></p>
        <p class="nias-fo-error"></p>

        <button type="button" class="nias-fo-btn"><?php esc_html_e('ثبت سفارش و پرداخت', 'nias-login-signup'); ?></button>

        <script>
        (function(){
            var root  = document.getElementById('<?php echo esc_js($uid); ?>');
            var input = root.querySelector('.nias-fo-input');
            var btn   = root.querySelector('.nias-fo-btn');
            var err   = root.querySelector('.nias-fo-error');
            var btnLabel = btn.textContent;

            function log(){
                if (window.console && console.log) {
                    var args = Array.prototype.slice.call(arguments);
                    args.unshift('[NIAS FASTORDER JS]');
                    console.log.apply(console, args);
                }
            }

            log('فرم سفارش سریع آماده شد', root.id);

            // تبدیل ارقام فارسی/عربی به انگلیسی
            function toEnDigits(s){
                var fa='۰۱۲۳۴۵۶۷۸۹', ar='٠١٢٣٤٥٦٧٨٩';
                return (s||'').replace(/[۰-۹]/g,function(d){return fa.indexOf(d);})
                              .replace(/[٠-٩]/g,function(d){return ar.indexOf(d);});
            }

            input.addEventListener('input', function(){
                this.value = toEnDigits(this.value).replace(/[^0-9]/g,'');
            });

            function showErr(msg){
                log('نمایش خطا به کاربر:', msg);
                err.textContent = msg; err.style.display='block';
            }

            function resetBtn(){
                btn.disabled = false;
                btn.textContent = btnLabel;
            }

            btn.addEventListener('click', function(){
                err.style.display='none';
                var phone = toEnDigits(input.value).replace(/[^0-9]/g,'');

                log('دکمه ثبت سفارش کلیک شد. شماره وارد شده:', phone);

                if(!/^09\d{9}$/.test(phone)){
                    log('شماره موبایل نامعتبر - عملیات متوقف شد');
                    showErr('لطفاً یک شماره موبایل معتبر وارد کنید (مثال ۰۹۱۲۳۴۵۶۷۸۹).');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'در حال انتقال به درگاه...';

                var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';

                // ۱) nonce تازه بگیر (کش نمی‌شود چون AJAX زنده است)
                fetch(ajaxUrl + '?action=nias_fastorder_get_nonce', { credentials:'same-origin' })
                .then(function(r){
                    log('پاسخ HTTP درخواست nonce. status:', r.status);
                    return r.json();
                })
                .then(function(nres){
                    if(!nres || !nres.success || !nres.data || !nres.data.nonce){
                        log('خطا: nonce معتبر در پاسخ پیدا نشد');
                        throw new Error('nonce');
                    }

                    log('مرحله ۲: nonce دریافت شد، درخواست ساخت سفارش ارسال می‌شود...');

                    var data = new FormData();
                    data.append('action','nias_fastorder_create');
                    data.append('nonce', nres.data.nonce);
                    data.append('product_id','<?php echo esc_js($product_id); ?>');
                    data.append('phone', phone);

                    return fetch(ajaxUrl, {
                        method:'POST', body:data, credentials:'same-origin'
                    });
                })
                .then(function(r){
                    log('پاسخ HTTP درخواست ساخت سفارش. status:', r.status);
                    return r.json();
                })
                .then(function(res){
                    log('پاسخ JSON ساخت سفارش:', res);
                    if(res && res.success && res.data && res.data.redirect){
                        log('موفق! در حال انتقال به آدرس:', res.data.redirect);
                        window.location.href = res.data.redirect;
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'خطایی رخ داد، دوباره تلاش کنید.';
                        log('سرور موفقیت را false برگرداند یا redirect وجود نداشت. پیام:', msg);
                        showErr(msg);
                        resetBtn();
                    }
                })
                .catch(function(e){
                    log('خطای catch:', e && e.message ? e.message : e);
                    showErr('ارتباط با سرور برقرار نشد.');
                    resetBtn();
                });
            });
        })();
        </script>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('nias_fast_order', 'nias_fastorder_form_shortcode');

/* ------------------------------------------------------------------ *
 *  هندلر ایجکس: ساخت کاربر، ساخت سفارش، لاگین، انتقال به صفحه پرداخت
 * ------------------------------------------------------------------ */

function nias_fastorder_create_handler()
{
    nias_fastorder_log('=========== شروع nias_fastorder_create_handler ===========');

    check_ajax_referer('nias_fastorder_nonce', 'nonce');

    if (!function_exists('WC')) {
        nias_fastorder_log('خطا: تابع WC() وجود ندارد - ووکامرس فعال نیست');
        wp_send_json_error(array('message' => 'فروشگاه فعال نیست.'));
    }

    // اطمینان از وجود session ووکامرس (لازم برای بعضی درگاه‌ها)
    if (null === WC()->session) {
        nias_fastorder_log('WC()->session نال بود - initialize_session صدا زده شد');
        WC()->initialize_session();
    }
    if (WC()->session && !WC()->session->has_session()) {
        nias_fastorder_log('سشن ووکامرس وجود نداشت - کوکی سشن مشتری ست شد');
        WC()->session->set_customer_session_cookie(true);
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $phone      = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';

    // نرمال‌سازی شماره با توابع خود پلاگین (پشتیبانی از ارقام فارسی/عربی و فرمت‌های مختلف)
    $phone = nias_sanitize_phone_enhanced($phone);

    nias_fastorder_log('phone بعد از نرمال‌سازی', $phone);

    if (!$phone || !preg_match('/^09\d{9}$/', $phone)) {
        nias_fastorder_log('خطا: شماره موبایل نامعتبر - توقف اجرا');
        wp_send_json_error(array('message' => 'شماره موبایل نامعتبر است.'));
    }

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable()) {
        nias_fastorder_log('خطا: محصول پیدا نشد یا قابل خرید نیست', $product_id);
        wp_send_json_error(array('message' => 'محصول قابل خرید نیست.'));
    }
    nias_fastorder_log('محصول معتبر است', array('id' => $product_id, 'name' => $product->get_name()));

    /* -------------------------------------------------------------- *
     *  تعیین کاربر صاحب سفارش
     * -------------------------------------------------------------- */
    $user_id = 0;

    if (is_user_logged_in()) {
        // کاربر لاگین: سفارش همیشه برای خودش ثبت می‌شود
        $user_id = get_current_user_id();
        nias_fastorder_log('کاربر از قبل لاگین بود', $user_id);

        if (!get_user_meta($user_id, 'phone', true)) {
            update_user_meta($user_id, 'phone', $phone);
            nias_fastorder_log('متای phone برای کاربر لاگین‌شده ذخیره شد', $phone);
        }
    } else {
        nias_fastorder_log('کاربر مهمان است - جستجو بر اساس شماره تلفن');

        // از تابع خود پلاگین استفاده می‌شود تا شماره‌های با فرمت قدیمی (بدون صفر) هم پیدا شوند
        $found = nias_get_user_by_phone($phone);

        if ($found) {
            $user_id = (int) $found->ID;
            nias_fastorder_log('کاربر موجود با این شماره پیدا شد', $user_id);
        } else {
            nias_fastorder_log('کاربری با این شماره پیدا نشد - در حال ساخت کاربر جدید');

            // ساخت کاربر با همان قواعد پلاگین (نام کاربری، نقش پیش‌فرض و متای phone)
            $user = nias_get_or_make_user($phone);

            if (is_wp_error($user)) {
                nias_fastorder_log('خطای ساخت کاربر - توقف اجرا', $user->get_error_message());
                wp_send_json_error(array('message' => 'ساخت حساب کاربری ممکن نشد.'));
            }

            $user_id = (int) $user->ID;
            nias_fastorder_log('کاربر جدید با موفقیت ساخته شد', $user_id);

            update_user_meta($user_id, 'billing_phone', $phone);
        }
    }

    if (!$user_id) {
        nias_fastorder_log('خطای بحرانی: user_id صفر ماند - توقف اجرا');
        wp_send_json_error(array('message' => 'تعیین کاربر ممکن نشد.'));
    }

    nias_fastorder_log('user_id نهایی برای سفارش', $user_id);

    /* -------------------------------------------------------------- *
     *  ساخت سفارش و اتصال به کاربر (هیچ‌وقت مهمان نیست)
     * -------------------------------------------------------------- */
    $order = wc_create_order(array('customer_id' => $user_id));
    if (is_wp_error($order)) {
        nias_fastorder_log('خطای wc_create_order - توقف اجرا', $order->get_error_message());
        wp_send_json_error(array('message' => 'ساخت سفارش ممکن نشد.'));
    }
    nias_fastorder_log('سفارش ساخته شد', $order->get_id());

    $order->add_product($product, 1);
    $order->set_billing_phone($phone);

    // علامت‌گذاری به‌عنوان سفارش سریع (برای سابمیت خودکار فرم پرداخت)
    $order->update_meta_data('_nias_fastorder', 'yes');

    /* -------------------------------------------------------------- *
     *  تعیین درگاه پرداخت
     * -------------------------------------------------------------- */
    $gateway_id = nias_fastorder_get_default_gateway();
    if (!$gateway_id) {
        nias_fastorder_log('خطای بحرانی: هیچ gateway_id ای برنگشت', $order->get_id());
        wp_send_json_error(array('message' => 'هیچ درگاه پرداختی فعال نیست.'));
    }

    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (!isset($gateways[$gateway_id])) {
        nias_fastorder_log('خطای بحرانی: gateway_id در لیست درگاه‌های فعال نیست', $gateway_id);
        wp_send_json_error(array('message' => 'درگاه انتخابی فعال نیست.'));
    }

    $order->set_payment_method($gateways[$gateway_id]);
    $order->calculate_totals();
    nias_fastorder_log('مجموع سفارش محاسبه شد', $order->get_total());

    $order->update_status('pending', 'سفارش سریع از فرم سفارش سریع نیاس.');
    $order->save();

    /* -------------------------------------------------------------- *
     *  لاگین کاربر — فقط وقتی لاگین نیست و نقش مدیریتی ندارد
     * -------------------------------------------------------------- */
    $u           = new WP_User($user_id);
    $admin_roles = array('administrator', 'shop_manager', 'editor');
    $is_admin    = (bool) array_intersect($admin_roles, (array) $u->roles);

    if (!$is_admin && !is_user_logged_in()) {
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        nias_fastorder_log('کاربر به صورت خودکار لاگین شد', $user_id);
    } else {
        nias_fastorder_log('لاگین خودکار انجام نشد (از قبل لاگین بود یا نقش مدیریتی داشت)');
    }

    /* -------------------------------------------------------------- *
     *  انتقال به صفحه پرداخت سفارش (order-pay)
     *  در آن صفحه فرم پرداخت خودکار سابمیت می‌شود.
     * -------------------------------------------------------------- */
    $redirect = $order->get_checkout_payment_url(true);

    nias_fastorder_log('آدرس نهایی redirect ساخته شد', $redirect);
    nias_fastorder_log('=========== پایان موفق nias_fastorder_create_handler ===========');

    wp_send_json_success(array('redirect' => $redirect));
}
add_action('wp_ajax_nias_fastorder_create', 'nias_fastorder_create_handler');
add_action('wp_ajax_nopriv_nias_fastorder_create', 'nias_fastorder_create_handler');

/* ------------------------------------------------------------------ *
 *  سابمیت خودکار فرم پرداخت در صفحه order-pay
 *  فقط برای سفارش‌هایی که با فرم سفارش سریع ساخته شده‌اند (متای _nias_fastorder).
 * ------------------------------------------------------------------ */

function nias_fastorder_autosubmit_pay_form()
{
    global $wp;

    if (!isset($wp->query_vars['order-pay'])) {
        return;
    }

    $order_id = absint($wp->query_vars['order-pay']);
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order || !$order->needs_payment()) {
        nias_fastorder_log('سفارش پیدا نشد یا نیاز به پرداخت ندارد - سابمیت خودکار اجرا نمی‌شود', $order_id);
        return;
    }

    // فقط سفارش‌های فرم سفارش سریع
    if ('yes' !== $order->get_meta('_nias_fastorder')) {
        return;
    }

    nias_fastorder_log('اسکریپت سابمیت خودکار فرم پرداخت رندر می‌شود', $order_id);
    ?>
    <div id="nias-fastorder-redirecting" dir="rtl" style="max-width:480px;margin:40px auto;text-align:center;font-family:inherit;">
        <div style="width:54px;height:54px;margin:0 auto 18px;border:4px solid #e8f7ef;border-top-color:#1ec07a;border-radius:50%;animation:niasFoSpin .8s linear infinite;"></div>
        <p style="font-size:16px;font-weight:700;color:#222;margin:0;"><?php esc_html_e('در حال انتقال به درگاه پرداخت...', 'nias-login-signup'); ?></p>
        <p style="font-size:13px;color:#9aa0a6;margin:8px 0 0;"><?php esc_html_e('لطفاً صفحه را نبندید.', 'nias-login-signup'); ?></p>
        <style>@keyframes niasFoSpin{to{transform:rotate(360deg);}}</style>
    </div>
    <script>
    (function(){
        function log(){
            if (window.console && console.log) {
                var args = Array.prototype.slice.call(arguments);
                args.unshift('[NIAS FASTORDER JS - autosubmit]');
                console.log.apply(console, args);
            }
        }

        log('اسکریپت سابمیت خودکار بارگذاری شد');

        function go(){
            var form = document.getElementById('order_review')
                     || document.querySelector('form#order_review, form.woocommerce-checkout, form[name="checkout"]');
            if(!form){
                log('فرم پرداخت هنوز در DOM پیدا نشد');
                return false;
            }
            log('فرم پرداخت پیدا شد:', form);

            var radio = form.querySelector('input[name="payment_method"]');
            if(radio){
                radio.checked = true;
                log('روش پرداخت انتخاب شد:', radio.value);
            }

            var btn = form.querySelector('#place_order, button[type="submit"], input[type="submit"]');
            if(btn){
                log('دکمه ثبت سفارش پیدا شد - کلیک خودکار انجام می‌شود');
                btn.click();
                return true;
            }
            log('دکمه ثبت سفارش پیدا نشد - فرم مستقیم submit می‌شود');
            form.submit();
            return true;
        }

        function tryGo(){
            if(!go()){
                var tries = 0;
                var t = setInterval(function(){
                    tries++;
                    if(go() || tries > 20){
                        clearInterval(t);
                        if (tries > 20) {
                            log('هشدار: بعد از ۲۰ تلاش فرم پیدا/سابمیت نشد - احتمالاً مشکل از قالب چک‌اوت است');
                        }
                    }
                }, 300);
            }
        }

        if(document.readyState === 'complete' || document.readyState === 'interactive'){
            setTimeout(tryGo, 300);
        } else {
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(tryGo, 300); });
        }
    })();
    </script>
    <?php
}
add_action('before_woocommerce_pay', 'nias_fastorder_autosubmit_pay_form');

/* ------------------------------------------------------------------ *
 *  سازندهٔ HTML کارت وضعیت سفارش — مشترک بین هوک صفحهٔ پرداخت و شورت‌کد
 *  وضعیت‌ها:
 *    paid    → پرداخت‌شده (processing/completed)  → پیام موفقیت
 *    failed  → ناموفق یا لغوشده (failed/cancelled) → دکمهٔ تلاش دوباره
 *    pending → در انتظار پرداخت (بقیه وضعیت‌ها)   → دکمهٔ پرداخت
 *
 *  @param WC_Order $order سفارش هدف.
 *  @param array    $args  گزینه‌ها: show_pay_button، show_view_button.
 *  @return string HTML آمادهٔ چاپ (در صورت نامعتبر بودن سفارش، رشتهٔ خالی).
 * ------------------------------------------------------------------ */
function nias_fastorder_status_card_html($order, $args = array())
{
    if (!($order instanceof WC_Order)) {
        return '';
    }

    $args = wp_parse_args($args, array(
        'show_pay_button'  => false,
        'show_view_button' => false,
    ));

    // تعیین وضعیت پرداخت از خود ووکامرس
    $status = $order->get_status();
    if ($order->is_paid()) {
        $state = 'paid';
    } elseif (in_array($status, array('failed', 'cancelled'), true)) {
        $state = 'failed';
    } else {
        $state = 'pending';
    }

    switch ($state) {
        case 'paid':
            $title     = 'پرداخت شما با موفقیت انجام شد';
            $subtitle  = 'سفارش شما ثبت و پرداخت آن تأیید شد. از خرید شما سپاسگزاریم.';
            $total_lbl = 'مبلغ پرداخت‌شده:';
            break;
        case 'failed':
            $title     = 'پرداخت شما ناموفق بود';
            $subtitle  = 'پرداخت انجام نشد یا لغو شد. برای تکمیل سفارش دوباره تلاش کنید.';
            $total_lbl = 'مبلغ قابل پرداخت:';
            break;
        default: // pending
            $title     = 'سفارش شما پرداخت نشده است';
            $subtitle  = 'پرداخت شما هنوز تکمیل نشده است. برای نهایی کردن سفارش وارد درگاه شوید.';
            $total_lbl = 'مبلغ قابل پرداخت:';
            break;
    }

    // فهرست محصولات
    $products_html = '';
    foreach ($order->get_items() as $item) {
        $name = $item->get_name();
        $qty  = $item->get_quantity();
        $line = $qty > 1 ? ($name . ' × ' . $qty) : $name;
        $products_html .= '<li>' . esc_html($line) . '</li>';
    }

    $total = $order->get_formatted_order_total();

    // دکمه‌ها
    $button_html = '';
    if ('paid' === $state) {
        if ($args['show_view_button']) {
            $button_html = '<a href="' . esc_url($order->get_view_order_url()) . '" class="nias-order-status-btn nias-order-status-btn-view">مشاهده سفارش</a>';
        }
    } elseif ($args['show_pay_button']) {
        $label       = 'failed' === $state ? 'تلاش دوباره برای پرداخت' : 'پرداخت سفارش';
        $button_html = '<a href="' . esc_url($order->get_checkout_payment_url(true)) . '" class="nias-order-status-btn">' . esc_html($label) . '</a>';
    }

    // آیکن بر اساس وضعیت
    if ('paid' === $state) {
        $icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
    } else {
        $icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    }

    ob_start();
    ?>
    <div class="nias-order-status-wrap nias-order-status-state-<?php echo esc_attr($state); ?>" dir="rtl">
        <style>
            .nias-order-status-wrap{max-width:480px;margin:24px auto;font-family:inherit;text-align:right;}
            .nias-order-status-card{background:#fff;border:1px solid #eee;border-radius:18px;box-shadow:0 8px 30px rgba(0,0,0,.06);padding:28px 24px;}
            .nias-order-status-icon{width:64px;height:64px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f5f5f5;}
            .nias-order-status-icon svg{width:34px;height:34px;color:#9aa0a6;}
            .nias-order-status-state-paid .nias-order-status-icon{background:#eefaf3;}
            .nias-order-status-state-paid .nias-order-status-icon svg{color:#1ec07a;}
            .nias-order-status-state-failed .nias-order-status-icon{background:#fff3f3;}
            .nias-order-status-state-failed .nias-order-status-icon svg{color:#e23b3b;}
            .nias-order-status-state-pending .nias-order-status-icon{background:#fff8ec;}
            .nias-order-status-state-pending .nias-order-status-icon svg{color:#e0a106;}
            .nias-order-status-title{font-size:18px;font-weight:800;color:#222;text-align:center;margin:0 0 6px;}
            .nias-order-status-sub{font-size:13px;color:#9aa0a6;text-align:center;margin:0 0 22px;line-height:1.8;}
            .nias-order-status-box{background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:16px 18px;margin:0;}
            .nias-order-status-box-label{font-size:12px;color:#9aa0a6;margin:0 0 8px;}
            .nias-order-status-products{list-style:none;margin:0;padding:0;}
            .nias-order-status-products li{font-size:15px;color:#222;font-weight:600;padding:6px 0;border-bottom:1px dashed #ececec;}
            .nias-order-status-products li:last-child{border-bottom:0;}
            .nias-order-status-total{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #eee;}
            .nias-order-status-total span:first-child{font-size:13px;color:#9aa0a6;}
            .nias-order-status-total span:last-child{font-size:16px;font-weight:800;color:#222;}
            .nias-order-status-btn{display:block;width:100%;box-sizing:border-box;text-align:center;background:#1ec07a;color:#fff !important;text-decoration:none;font-size:17px;font-weight:700;padding:16px;border-radius:14px;margin-top:22px;transition:background .2s;}
            .nias-order-status-btn:hover{background:#17a868;}
            .nias-order-status-btn-view{background:#2b6ef2;}
            .nias-order-status-btn-view:hover{background:#1f57c8;}
            .nias-order-status-empty{text-align:center;color:#9aa0a6;font-size:15px;margin:0;padding:12px;}
        </style>

        <div class="nias-order-status-card">
            <div class="nias-order-status-icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

            <h3 class="nias-order-status-title"><?php echo esc_html($title); ?></h3>
            <p class="nias-order-status-sub"><?php echo esc_html($subtitle); ?></p>

            <div class="nias-order-status-box">
                <p class="nias-order-status-box-label"><?php esc_html_e('محصولات سفارش:', 'nias-login-signup'); ?></p>
                <ul class="nias-order-status-products"><?php echo $products_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
                <div class="nias-order-status-total">
                    <span><?php echo esc_html($total_lbl); ?></span>
                    <span><?php echo wp_kses_post($total); ?></span>
                </div>
            </div>

            <?php echo $button_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------ *
 *  نمایش خودکار کارت وضعیت سفارش روی صفحه پرداخت سفارش (order-pay)
 *  توجه: سفارش‌های «سفارش سریعِ پرداخت‌نشده» کارت نمی‌گیرند (خودکار سابمیت می‌شوند)
 * ------------------------------------------------------------------ */

function nias_fastorder_render_status_card()
{
    global $wp;

    if (!isset($wp->query_vars['order-pay'])) {
        return;
    }

    $order_id = absint($wp->query_vars['order-pay']);
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $is_paid = $order->is_paid();

    // سفارش سریعِ پرداخت‌نشده خودکار سابمیت می‌شود، کارت لازم نیست.
    if (!$is_paid && 'yes' === $order->get_meta('_nias_fastorder') && $order->needs_payment()) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput
    echo nias_fastorder_status_card_html($order, array('show_view_button' => true));
}
add_action('before_woocommerce_pay', 'nias_fastorder_render_status_card');

/* ------------------------------------------------------------------ *
 *  شورت‌کد کارت وضعیت سفارش: [nias_order_status]
 * ------------------------------------------------------------------ */

function nias_fastorder_order_status_shortcode()
{
    nias_fastorder_log('شورت‌کد nias_order_status رندر شد');

    if (!function_exists('WC')) {
        return '';
    }

    // ۱) از endpointهای ووکامرس (شناسه در «مسیر» است نه query string)
    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) {
        $order_id = absint(get_query_var('order-pay'));
    }

    // ۲) سپس از پارامترهای query string (بعضی درگاه‌ها با ?order_id=... برمی‌گردند)
    if (!$order_id) {
        foreach (array('order-pay', 'order-received', 'order_id', 'order', 'pay_for_order', 'id') as $key) {
            if (isset($_GET[$key]) && absint($_GET[$key])) { // phpcs:ignore WordPress.Security.NonceVerification
                $order_id = absint($_GET[$key]); // phpcs:ignore WordPress.Security.NonceVerification
                break;
            }
        }
    }

    // اگر در URL نبود و کاربر لاگین است، آخرین سفارش در انتظار/ناموفقش را نشان بده.
    if (!$order_id && is_user_logged_in()) {
        $orders = wc_get_orders(array(
            'customer_id' => get_current_user_id(),
            'status'      => array('pending', 'failed'),
            'limit'       => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'return'      => 'ids',
        ));
        if (!empty($orders)) {
            $order_id = (int) $orders[0];
        }
    }

    if (!$order_id) {
        return '<div class="nias-order-status-wrap" dir="rtl"><div class="nias-order-status-card"><p class="nias-order-status-empty">سفارشی برای نمایش یافت نشد.</p></div></div>';
    }

    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) {
        return '<div class="nias-order-status-wrap" dir="rtl"><div class="nias-order-status-card"><p class="nias-order-status-empty">سفارش یافت نشد.</p></div></div>';
    }

    // کنترل دسترسی: کاربر لاگین باید صاحب سفارش (یا مدیر) باشد، مهمان باید کلید معتبر بدهد.
    if (is_user_logged_in()) {
        $cust = (int) $order->get_customer_id();
        if ($cust && $cust !== get_current_user_id() && !current_user_can('manage_woocommerce')) {
            nias_fastorder_log('دسترسی رد شد: سفارش مال کاربر لاگین‌شده فعلی نیست');
            return '<div class="nias-order-status-wrap" dir="rtl"><div class="nias-order-status-card"><p class="nias-order-status-empty">دسترسی به این سفارش امکان‌پذیر نیست.</p></div></div>';
        }
    } else {
        // مهمان: با کلید سفارش بررسی کن (لینک بازگشت از درگاه شامل key هست)
        $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if (!$key || !hash_equals($order->get_order_key(), $key)) {
            nias_fastorder_log('دسترسی رد شد: کلید سفارش مهمان نامعتبر یا خالی است');
            return '<div class="nias-order-status-wrap" dir="rtl"><div class="nias-order-status-card"><p class="nias-order-status-empty">دسترسی به این سفارش امکان‌پذیر نیست.</p></div></div>';
        }
    }

    return nias_fastorder_status_card_html($order, array(
        'show_pay_button'  => true,
        'show_view_button' => true,
    ));
}
add_shortcode('nias_order_status', 'nias_fastorder_order_status_shortcode');
