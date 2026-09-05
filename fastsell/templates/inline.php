<?php

/**
 * قالب درون‌صفحه‌ای خرید سریع — خروجی شورت‌کد [nias_fastsell]
 *
 * همان ساختار templates/modal.php است، منهای پوسته‌ی مودال:
 * دکمه بستن و overlay اینجا معنایی ندارند. ظرف بیرونی (#nias-login-fastsell-modal
 * با کلاس nias-login-fastsell-inline) را render_inline_shortcode() چاپ می‌کند،
 * پس تمام class/id/data-attribute ها دست‌نخورده می‌مانند و easysale.js و
 * رندرکننده‌های AJAX بدون تغییر کار می‌کنند.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="nias-login-fastsell-modal-content">
    <div class="nias-login-fastsell-modal-card">

        <!-- TABS -->
        <div class="nias-login-fastsell-steps">
            <div class="nias-login-fastsell-step active" data-step="1">
                <span class="nias-login-fastsell-step-number">۰</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h15l-1.4 8.4a2 2 0 0 1-2 1.6H8.4a2 2 0 0 1-2-1.6L5 5H3" /><circle cx="9" cy="20" r="1.2" /><circle cx="17" cy="20" r="1.2" /></svg>
                <span class="nias-login-fastsell-step-title" data-default="سبد خرید">سبد خرید</span>
            </div>
            <div class="nias-login-fastsell-step" data-step="2">
                <span class="nias-login-fastsell-step-number">۲</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2.4" /><path d="M3 10h18" /><path d="M16.5 15.5h1.5" /></svg>
                <span class="nias-login-fastsell-step-title" data-default="تسویه حساب">تسویه حساب</span>
            </div>
        </div>

        <!-- BODY -->
        <div class="nias-login-fastsell-body">

            <!-- STEP 1: CART -->
            <div class="nias-login-fastsell-step-content" id="nias-login-fastsell-step-1">
                <div class="nias-login-fastsell-cart-container"></div>

                <div class="nias-login-fastsell-footer">
                    <div class="nias-login-fastsell-coupon">
                        <input type="text" id="nias-login-fastsell-coupon-code" data-default-placeholder="کد تخفیف دارید؟" placeholder="کد تخفیف دارید؟">
                        <button type="button" id="nias-login-fastsell-apply-coupon" data-default-text="اعمال">اعمال</button>
                    </div>
                    <button type="button" class="nias-login-fastsell-btn-next" data-default-text="ادامه و تسویه حساب">
                        <span>ادامه و تسویه حساب</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
                    </button>
                </div>
            </div>

            <!-- STEP 2: CHECKOUT -->
            <div class="nias-login-fastsell-step-content" id="nias-login-fastsell-step-2" style="display: none;">
                <div id="nias-login-fastsell-checkout-container"></div>

                <div class="nias-login-fastsell-footer">
                    <?php if (get_option('nias_fastsell_show_order_summary', 1)): ?>
                        <div id="nias-fastsell-checkout-order-summary"></div>
                    <?php endif; ?>

                    <div class="nias-login-fastsell-actions">
                        <button type="button" class="nias-login-fastsell-btn-back" data-default-text="بازگشت">بازگشت</button>
                        <button type="button" class="nias-login-fastsell-btn-submit" data-default-text="پرداخت">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2.2" /><path d="M3 10h18" /></svg>
                            <span>پرداخت</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div class="nias-login-fastsell-loading" style="display: none;">
            <div class="nias-login-fastsell-spinner"></div>
        </div>

    </div>
</div>
