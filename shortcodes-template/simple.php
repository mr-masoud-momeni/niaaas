<?php

function nias_main_modal_shortcode($atts) {
    $digits = get_option('nsdigitsquantity');
    ob_start();
    ?>
<div class="nias-modal-box">
    <div class="nias-main-modal">
        <button class="nias-close-modal" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.16998 14.83L14.83 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14.83 14.83L9.16998 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                بستن
        </button>
        <div>
    <form class="nias-login-form" id="nias-login">
        <span class="nias-modal-title" >ورود/ثبت نام</span>
        <p class="nias-subtitle">در صورت روشن بودن vpn خاموش کنید تا به مشکلی نخورید</p>
        <div class="nias-login-field">
        <label for="niasphoneinput">شماره همراه خود را وارد کنید</label>
        <div class="nias-phone-box">
            <svg class="nias-checkicon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path opacity="0.4" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#00C72C"/>
                <path d="M10.5795 15.5801C10.3795 15.5801 10.1895 15.5001 10.0495 15.3601L7.21945 12.5301C6.92945 12.2401 6.92945 11.7601 7.21945 11.4701C7.50945 11.1801 7.98945 11.1801 8.27945 11.4701L10.5795 13.7701L15.7195 8.6301C16.0095 8.3401 16.4895 8.3401 16.7795 8.6301C17.0695 8.9201 17.0695 9.4001 16.7795 9.6901L11.1095 15.3601C10.9695 15.5001 10.7795 15.5801 10.5795 15.5801Z" fill="#00C72C"/>
                </svg>
            <input id="niasphoneinput" maxlength="11" type="text" inputmode="tel" placeholder="0 9 - - - - - - - - -" name="phone" required>
            <span>98+</span>
            <img src="<?php echo NIAS_LOGIN_IMAGES;?>iran-nias.png" alt="iran" width="30" height="30">
        </div>
        <span class="nias-login-message">
            شماره همراه صحیح نیست
    </span>
        <input type="hidden" name="action" value="nias_login">
        <?php wp_nonce_field('nias-login');?>
        <button>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><style>.spinner_d9Sa{transform-origin:center}.spinner_qQQY{animation:spinner_ZpfF 9s linear infinite}.spinner_pote{animation:spinner_ZpfF .75s linear infinite}@keyframes spinner_ZpfF{100%{transform:rotate(360deg)}}</style><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"/><rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"/><rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"/></svg>
        ارسال کد تایید
        </button>
			</div>
    </form>


    <form class="nias-login-code" id="nias-code-form">
        <span class="nias-modal-title" >تایید شماره همراه</span>
        <p class="nias-login-result">کد 5 رقمی ارسال شده به شماره 09999316323 را وارد نمایید</p>
        <div class="nias-login-field">
        <div class="nias-code-box">

        <?php foreach( range( 1, $digits) as $index): ?>
            <input type="tel" inputmode="tel" pattern="[0-9]*" maxlength="1" autocomplete="off" >
        <?php endforeach;?>

        </div>
    <div class="nias-under-code">
        <div class="nias-resendbox">
            <a class="nias-resend" href="#">ارسال مجدد</a>
            <span class="nias-countdown">01:48</span>
        </div>
    </div>
    <span class="nias-login-message">
</span>
<p class="nias-success" style="display: none;"> ورود با موفقیت انجام شد </p>
    <div class="nias-confirm-box">
        <button class="nias-confirm-code">
            <input type="hidden" name="code" id="nias-verify-code" value="">
            <input type="hidden" name="phone" value="">
            <input type="hidden" name="_wpnonce" value="">
            <input type="hidden" name="action" value="nias_verify">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><style>.spinner_d9Sa{transform-origin:center}.spinner_qQQY{animation:spinner_ZpfF 9s linear infinite}.spinner_pote{animation:spinner_ZpfF .75s linear infinite}@keyframes spinner_ZpfF{100%{transform:rotate(360deg)}}</style><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"/><rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"/><rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"/></svg>
        تایید کد
        </button>
        <button type="button" class="nias-change-phone" id="nias-change-number">
            اصلاح شماره
        </button>
    </div>
    </div>
    </form>
    </div>
    </div>
</div>


    <?php
    return ob_get_clean();
}

add_shortcode('nias_main_modal', 'nias_main_modal_shortcode');
?>
