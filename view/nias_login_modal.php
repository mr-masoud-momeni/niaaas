<?php

if (!function_exists('nias_show_modal')) {
    function nias_show_modal()
    {
        $digits = get_option('nsdigitsquantity');
        // ظاهر مؤثر: اگر «طراحی با المنتور» انتخاب شده ولی المنتور نصب نباشد،
        // به‌جای یک باکس خالی، ظاهر پیش‌فرض افزونه رندر می‌شود
        $selected_design_nias = nias_login_active_design();
        $elementor_design_nias = get_option('nias_elementor_shortcode');
        $email_activate = get_option('nias_email_activate');
        $email_phone_activate = get_option('nias_email_phone_activate');
        $password_activate = get_option('nias_password_activate');
        $password_otp_activate = get_option('nias_password_otp_activate');
        $manual_password_activate = get_option('nias_manual_password_activate');

        // بلوک ساخت رمز عبور دستی برای کاربران جدید (با جاوااسکریپت فقط برای کاربر جدید نمایش داده می‌شود)
        $nias_manual_password_block = '';
        if ($manual_password_activate && ($password_activate || $password_otp_activate)) {
            $nias_manual_password_block = '
                <div class="nias-manual-password" style="display:none;">
                    ' . nias_login_modal_text_html('nias_login_text_manualpass_title', '<span class="nias-modal-title">', '</span>') . '
                    <div class="nsinput-field">
                        <input autocomplete="new-password" placeholder=" " type="password" name="nias_manual_new_password" id="nias_manual_new_password" />
                        <label class="ns-radio-label" for="nias_manual_new_password">' . esc_html(nias_login_modal_text('nias_login_text_manualpass_new')) . '</label>
                    </div>
                    <div class="nias-pass-strength" aria-hidden="true">
                        <div class="nias-pass-strength__track"><span class="nias-pass-strength__bar"></span></div>
                        ' . nias_login_modal_text_html('nias_login_text_manualpass_strength', '<span class="nias-pass-strength__text">', '</span>') . '
                    </div>
                    <div class="nsinput-field">
                        <input autocomplete="new-password" placeholder=" " type="password" name="nias_manual_confirm_password" id="nias_manual_confirm_password" />
                        <label class="ns-radio-label" for="nias_manual_confirm_password">' . esc_html(nias_login_modal_text('nias_login_text_manualpass_confirm')) . '</label>
                    </div>
                    ' . nias_login_modal_text_html('nias_login_text_manualpass_hint', '<span class="ns-text-small">', '</span>') . '
                </div>';
        }


        if ($selected_design_nias == 'default_design') {
            $style_primary_color    = get_option('nias_login_style_primary_color', '#043ccc');
            $style_title_color      = get_option('nias_login_style_title_color', '#205fff');
            $style_bg_color         = get_option('nias_login_style_bg_color', '#ffffff');
            $style_close_btn_color  = get_option('nias_login_style_close_btn_color', '#ff0000');
            $style_radius           = (int) get_option('nias_login_style_radius', 15);
            $style_title_font_size  = (int) get_option('nias_login_style_title_font_size', 20);
            $style_subtitle_font_size = (int) get_option('nias_login_style_subtitle_font_size', 13);
            $style_font_size        = (int) get_option('nias_login_style_font_size', 16);

            // ترنسفورم لیبل شناور فیلدها (حالت عادی و حالت فوکوس/پرشده)
            $style_label_ty          = (int) get_option('nias_login_style_label_ty', 6);
            $style_label_tx          = (int) get_option('nias_login_style_label_tx', 0);
            $style_label_scale       = (float) get_option('nias_login_style_label_scale', 1);
            $style_label_ty_focus    = (int) get_option('nias_login_style_label_ty_focus', -14);
            $style_label_tx_focus    = (int) get_option('nias_login_style_label_tx_focus', -5);
            $style_label_scale_focus = (float) get_option('nias_login_style_label_scale_focus', 0.7);
?>
            <style>
            .nias-modal-box {
                --nias-primary: <?php echo esc_attr($style_primary_color); ?>;
                --nias-title-color: <?php echo esc_attr($style_title_color); ?>;
                --nias-modal-bg: <?php echo esc_attr($style_bg_color); ?>;
                --nias-close-btn-bg: <?php echo esc_attr($style_close_btn_color); ?>;
                --nias-radius: <?php echo esc_attr($style_radius); ?>px;
                --nias-title-font-size: <?php echo esc_attr($style_title_font_size); ?>px;
                --nias-subtitle-font-size: <?php echo esc_attr($style_subtitle_font_size); ?>px;
                --nias-font-size: <?php echo esc_attr($style_font_size); ?>px;
                --nias-label-ty: <?php echo esc_attr($style_label_ty); ?>px;
                --nias-label-tx: <?php echo esc_attr($style_label_tx); ?>px;
                --nias-label-sc: <?php echo esc_attr($style_label_scale); ?>;
                --nias-label-ty-f: <?php echo esc_attr($style_label_ty_focus); ?>px;
                --nias-label-tx-f: <?php echo esc_attr($style_label_tx_focus); ?>px;
                --nias-label-sc-f: <?php echo esc_attr($style_label_scale_focus); ?>;
            }
            /* ترنسفورم لیبل شناور — فقط برای ظاهر پیش‌فرض.
               ویجت المنتور (.nias-main-modal.nias-elementor) عمداً استثنا شده تا
               کنترل‌های خودِ ویجت دست‌نخورده و در اولویت بمانند. */
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field label.ns-radio-label {
                transform: translateY(var(--nias-label-ty)) translateX(var(--nias-label-tx)) scale(var(--nias-label-sc));
            }
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="text"]:focus ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="password"]:focus ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="tel"]:focus ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="email"]:focus ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="text"]:valid ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="password"]:valid ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="tel"]:valid ~ label,
            .nias-modal-box .nias-main-modal:not(.nias-elementor) .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label {
                transform: translateY(var(--nias-label-ty-f)) translateX(var(--nias-label-tx-f)) scale(var(--nias-label-sc-f)) !important;
            }
            #nias-toast-wrap {
                position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                z-index: 2147483647; display: flex; flex-direction: column; align-items: center;
                gap: 10px; pointer-events: none;
                width: max-content; max-width: min(400px, calc(100vw - 32px));
            }
            .nias-toast {
                display: flex; align-items: center; gap: 10px;
                padding: 12px 16px; border-radius: 10px; font-size: 14px;
                line-height: 1.5; direction: rtl;
                font-family: inherit;
                pointer-events: auto; box-shadow: 0 4px 24px rgba(0,0,0,.35);
                opacity: 0; transform: translateY(-10px) scale(.96);
                transition: opacity .25s ease, transform .25s ease;
                min-width: 220px;
            }
            .nias-toast--show    { opacity: 1; transform: none; }
            .nias-toast--info    { background:#1e3a5f; color:#93c5fd; border:1px solid rgba(147,197,253,.25); }
            .nias-toast--success { background:#14532d; color:#86efac; border:1px solid rgba(134,239,172,.25); }
            .nias-toast--error   { background:#450a0a; color:#fca5a5; border:1px solid rgba(252,165,165,.25); }
            .nias-toast--warning { background:#431407; color:#fdba74; border:1px solid rgba(253,186,116,.25); }
            .nias-toast__icon { flex-shrink:0; font-size:15px; }
            .nias-toast__msg  { flex:1; }
            .nias-toast__close {
                flex-shrink:0; background:none; border:none; cursor:pointer;
                color:inherit; opacity:.55; font-size:18px; line-height:1;
                font-family:inherit; padding:0; margin-right:auto;
            }
            .nias-toast__close:hover { opacity:1; }
            /* پیام‌ها با توست نمایش داده می‌شوند؛ ناحیه پیام درون‌خطی فقط کانال انتقال متن است و نباید دیده شود */
            .nias-modal-box .nias-login-message { display: none !important; }
            </style>
            <div id="nias-toast-wrap"></div>
            <div class="nias-success" style="display: none;">
                <?php echo nias_login_modal_text_html('nias_login_text_success_title', '<p class="niaslogin-success-title">', ' </p>'); ?>
                <p class="niaslogin-success-subtitle"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <style>
                            .spinner_0XTQ {
                                transform-origin: center;
                                animation: spinner_y6GP .75s linear infinite
                            }

                            @keyframes spinner_y6GP {
                                100% {
                                    transform: rotate(360deg)
                                }
                            }
                        </style>
                        <path class="spinner_0XTQ" d="M12,23a9.63,9.63,0,0,1-8-9.5,9.51,9.51,0,0,1,6.79-9.1A1.66,1.66,0,0,0,12,2.81h0a1.67,1.67,0,0,0-1.94-1.64A11,11,0,0,0,12,23Z" />
                    </svg>
                    <?php echo esc_html(nias_login_modal_text('nias_login_text_success_subtitle')); ?>
                </p>
            </div>
            <div class="nias-modal-box">
                <div class="nias-main-modal">
                    <button class="nias-close-modal" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9.16998 14.83L14.83 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M14.83 14.83L9.16998 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <?php echo esc_html(nias_login_modal_text('nias_login_text_close')); ?>
                    </button>
                    <div>
                        <form class="nias-login-form" id="nias-login">
                            <?php echo nias_login_modal_text_html('nias_login_text_title', '<span class="nias-modal-title">', '</span>'); ?>
                            <?php echo nias_login_modal_text_html('nias_login_text_subtitle', '<p class="nias-subtitle">', '</p>'); ?>
                            <div class="nias-login-field">
                                <?php
                                if ($email_activate) {
                                ?>

                                    <div class="nias-email-box nsinput-field">
                                        <input id="nias_email_input" placeholder=" " type="email" name="ns_email" required>
                                        <label class="ns-radio-label" for="nias_email_input"><?php echo esc_html(nias_login_modal_text('nias_login_text_field_label')); ?></label>
                                    </div>

                                <?php
                                } elseif ($email_phone_activate) {
                                ?>

                                    <div class="nsinput-field">
                                        <input
                                            required=""
                                            placeholder=" "
                                            autocomplete="on"
                                            type="text"
                                            name="ns_email_phone"
                                            id="nias_email_phone_input" />
                                        <label class="ns-radio-label" for="nias_email_phone_input"><?php echo esc_html(nias_login_modal_text('nias_login_text_field_label')); ?></label>
                                    </div>

                                <?php


                                } else {

                                ?>

                                    <?php echo nias_login_modal_text_html('nias_login_text_field_label', '<label for="niasphoneinput">', '</label>'); ?>
                                    <div class="nias-phone-box">
                                        <svg class="nias-checkicon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path opacity="0.4" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#00C72C" />
                                            <path d="M10.5795 15.5801C10.3795 15.5801 10.1895 15.5001 10.0495 15.3601L7.21945 12.5301C6.92945 12.2401 6.92945 11.7601 7.21945 11.4701C7.50945 11.1801 7.98945 11.1801 8.27945 11.4701L10.5795 13.7701L15.7195 8.6301C16.0095 8.3401 16.4895 8.3401 16.7795 8.6301C17.0695 8.9201 17.0695 9.4001 16.7795 9.6901L11.1095 15.3601C10.9695 15.5001 10.7795 15.5801 10.5795 15.5801Z" fill="#00C72C" />
                                        </svg>
                                        <input id="niasphoneinput" maxlength="11" type="text" inputmode="tel" placeholder="<?php echo esc_attr(nias_login_modal_text('nias_login_text_phone_placeholder')); ?>" name="phone" required>
                                        <?php echo nias_login_modal_text_html('nias_login_text_country_code', '<span>', '</span>'); ?>
                                        <img src="<?php echo NIAS_LOGIN_IMAGES; ?>iran-nias.png" alt="iran" width="30" height="30">
                                    </div>

                                <?php


                                }

                                ?>



                                <span class="nias-login-message" style="display:none"></span>
                                <input type="hidden" name="action" value="nias_login">
                                <?php wp_nonce_field('nias-login'); ?>
                                <button>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                        <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z" />
                                        <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7" />
                                        <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9" />
                                    </svg>
                                    <?php echo esc_html(nias_login_modal_text('nias_login_text_send_button')); ?>
                                </button>
                            </div>
                        </form>

                        <form class="nias-login-code" id="nias-code-form" autocomplete="one-time-code">

                            <?php
                            // منطق جدید برای فرم تاییدیه بر اساس password_activate و password_otp_activate
                            if ($password_otp_activate) {
                                // اگر password_otp_activate فعال باشد، هردو تب (کد تایید و پسورد) نمایش داده می‌شوند
                            ?>
                                <section class="nias-tabs" role="tablist" aria-label="<?php esc_attr_e('تب‌های محتوایی', 'nias-login-signup'); ?>">
                                    <!-- رادیوهای کنترل تب‌ها: تب اول = پسورد، تب دوم = کد تایید -->
                                    <input checked type="radio" name="nias-tabs" id="nias-tab-1" aria-controls="nias-panel-1" />
                                    <label id="nias-label-1" for="nias-tab-1" role="tab" aria-selected="true" tabindex="0"><?php echo esc_html(nias_login_modal_text('nias_login_text_tab_password')); ?></label>

                                    <input type="radio" name="nias-tabs" id="nias-tab-2" aria-controls="nias-panel-2" />
                                    <label id="nias-label-2" for="nias-tab-2" role="tab" aria-selected="false" tabindex="-1"><?php echo esc_html(nias_login_modal_text('nias_login_text_tab_otp')); ?></label>

                                    <!-- پنل‌های محتوا -->
                                    <div class="nias-tabs__panels">

                                        <!-- پنل اول: ورود با پسورد -->
                                        <div id="nias-panel-1" class="nias-panel" role="tabpanel" aria-labelledby="nias-label-1">
                                            <?php echo nias_login_modal_text_html('nias_login_text_verify_title_password', '<span class="nias-modal-title">', '</span>'); ?>
                                            <div class="nsinput-field">
                                                <input
                                                    required=""
                                                    autocomplete="off"
                                                    placeholder=" "
                                                    type="password"
                                                    name="ns_password"
                                                    id="ns_password" />
                                                <label class="ns-radio-label" for="ns_password"><?php echo esc_html(nias_login_modal_text('nias_login_text_password_label')); ?></label>
                                            </div>

                                            <?php echo $nias_manual_password_block; ?>

                                            <button type="button" class="nias-forgot-password" id="nias-forgot-password">
                                                <?php echo esc_html(nias_login_modal_text('nias_login_text_forgot')); ?>
                                            </button>
                                            <?php echo nias_login_modal_text_html('nias_login_text_forgot_hint', '<span class="ns-text-small">', '</span>'); ?>

                                            <!-- فیلد پنهان برای انتقال ایمیل/شماره -->
                                            <?php if ($email_activate) { ?>
                                                <input type="hidden" id="nias_forgot_email" name="nias_forgot_email" value="">
                                                <script>
                                                    document.addEventListener("DOMContentLoaded", function() {
                                                        const emailInput = document.getElementById("nias_email_input");
                                                        const hiddenField = document.getElementById("nias_forgot_email");
                                                        if (emailInput && hiddenField) {
                                                            emailInput.addEventListener("input", function() {
                                                                hiddenField.value = emailInput.value;
                                                            });
                                                        }
                                                    });
                                                </script>
                                            <?php } elseif ($email_phone_activate) { ?>
                                                <input type="hidden" id="nias_forgot_email_phone" name="nias_forgot_email_phone" value="">
                                                <script>
                                                    document.addEventListener("DOMContentLoaded", function() {
                                                        const input = document.getElementById("nias_email_phone_input");
                                                        const hiddenField = document.getElementById("nias_forgot_email_phone");
                                                        if (input && hiddenField) {
                                                            input.addEventListener("input", function() {
                                                                hiddenField.value = input.value;
                                                            });
                                                        }
                                                    });
                                                </script>
                                            <?php } ?>
                                        </div>

                                        <!-- پنل دوم: ورود با کد تایید -->
                                        <div id="nias-panel-2" class="nias-panel" role="tabpanel" aria-labelledby="nias-label-2">
                                            <?php echo nias_login_modal_text_html('nias_login_text_verify_title', '<span class="nias-modal-title">', '</span>'); ?>
                                            <div class="nias-under-code">
                                                <div class="nias-resendbox">
                                                    <a class="nias-resend" href="#"><?php echo esc_html(nias_login_modal_text('nias_login_text_resend')); ?></a>
                                                    <svg width="120" height="120" viewBox="0 0 120 120" class="nias-circle-counter">
                                                        <circle class="fill-neutral-light" cx="60" cy="60" r="40" stroke-width="10px" style="fill: #e5f4ff;"></circle>
                                                        <circle class="stroke-neutral-light fill-[none]" cx="60" cy="60" r="55" stroke-width="10px" style="stroke-linecap: round;stroke:#e5f4ff;fill: none;"></circle>
                                                        <circle class="stroke-neutral-dark fill-[none] transition-all duration-300" cx="60" cy="60" r="55" stroke-width="10px" transform="rotate(-90 60 60)" style="stroke-dasharray: 345.575;stroke-dashoffset: 0;stroke-linecap: round;transition-duration: .3s;transition-property: all;stroke:#043ccc;fill: #e0000000;"></circle>
                                                    </svg>
                                                    <svg class="nias-spinner-timer" fill="#043ccc" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                                        <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"></path>
                                                        <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"></rect>
                                                        <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"></rect>
                                                    </svg>
                                                    <span class="nias-countdown">00:00</span>
                                                </div>
                                            </div>
                                            <p class="nias-login-result"><?php echo esc_html(nias_login_modal_text('nias_login_text_code_hint')); ?></p>
                                            <div class="nias-login-field">
                                                <div class="nias-code-box">
                                                    <?php foreach (range(1, $digits) as $index) : ?>
                                                        <input type="tel" inputmode="tel" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit <?php echo $index; ?>">
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </section>

                            <?php
                            } elseif ($password_activate) {
                                // اگر فقط password_activate فعال باشد، فقط بخش وارد کردن پسورد نمایش داده می‌شود
                            ?>
                                <?php echo nias_login_modal_text_html('nias_login_text_verify_title_password', '<span class="nias-modal-title">', '</span>'); ?>
                                <div class="nsinput-field">
                                    <input
                                        required=""
                                        autocomplete="off"
                                        placeholder=" "
                                        type="password"
                                        name="ns_password"
                                        id="ns_password" />
                                    <label class="ns-radio-label" for="ns_password"><?php echo esc_html(nias_login_modal_text('nias_login_text_password_label')); ?></label>
                                </div>

                                <?php echo $nias_manual_password_block; ?>

                                <button type="button" class="nias-forgot-password" id="nias-forgot-password">
                                    <?php echo esc_html(nias_login_modal_text('nias_login_text_forgot')); ?>
                                </button>
                                <?php echo nias_login_modal_text_html('nias_login_text_forgot_hint', '<span class="ns-text-small">', '</span>'); ?>

                                <!-- فیلد پنهان برای انتقال ایمیل/شماره -->
                                <?php if ($email_activate) { ?>
                                    <input type="hidden" id="nias_forgot_email" name="nias_forgot_email" value="">
                                    <script>
                                        document.addEventListener("DOMContentLoaded", function() {
                                            const emailInput = document.getElementById("nias_email_input");
                                            const hiddenField = document.getElementById("nias_forgot_email");
                                            if (emailInput && hiddenField) {
                                                emailInput.addEventListener("input", function() {
                                                    hiddenField.value = emailInput.value;
                                                });
                                            }
                                        });
                                    </script>
                                <?php } elseif ($email_phone_activate) { ?>
                                    <input type="hidden" id="nias_forgot_email_phone" name="nias_forgot_email_phone" value="">
                                    <script>
                                        document.addEventListener("DOMContentLoaded", function() {
                                            const input = document.getElementById("nias_email_phone_input");
                                            const hiddenField = document.getElementById("nias_forgot_email_phone");
                                            if (input && hiddenField) {
                                                input.addEventListener("input", function() {
                                                    hiddenField.value = input.value;
                                                });
                                            }
                                        });
                                    </script>
                                <?php } ?>

                            <?php
                            } else {
                                // اگر هیچکدام فعال نباشند، فقط بخش کد تایید نمایش داده می‌شود
                            ?>
                                <?php echo nias_login_modal_text_html('nias_login_text_verify_title', '<span class="nias-modal-title">', '</span>'); ?>
                                <div class="nias-under-code">
                                    <div class="nias-resendbox">
                                        <a class="nias-resend" href="#"><?php echo esc_html(nias_login_modal_text('nias_login_text_resend')); ?></a>
                                        <svg width="120" height="120" viewBox="0 0 120 120" class="nias-circle-counter">
                                            <circle class="fill-neutral-light" cx="60" cy="60" r="40" stroke-width="10px" style="fill: #e5f4ff;"></circle>
                                            <circle class="stroke-neutral-light fill-[none]" cx="60" cy="60" r="55" stroke-width="10px" style="stroke-linecap: round;stroke:#e5f4ff;fill: none;"></circle>
                                            <circle class="stroke-neutral-dark fill-[none] transition-all duration-300" cx="60" cy="60" r="55" stroke-width="10px" transform="rotate(-90 60 60)" style="stroke-dasharray: 345.575;stroke-dashoffset: 0;stroke-linecap: round;transition-duration: .3s;transition-property: all;stroke:#043ccc;fill: #e0000000;"></circle>
                                        </svg>
                                        <svg class="nias-spinner-timer" fill="#043ccc" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"></path>
                                            <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"></rect>
                                            <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"></rect>
                                        </svg>
                                        <span class="nias-countdown">00:00</span>
                                    </div>
                                </div>
                                <p class="nias-login-result"><?php echo esc_html(nias_login_modal_text('nias_login_text_code_hint')); ?></p>
                                <div class="nias-login-field">
                                    <div class="nias-code-box">
                                        <?php foreach (range(1, $digits) as $index) : ?>
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="1"
                                                name="one-time-code"
                                                autocomplete="one-time-code"
                                                aria-label="Digit <?php echo $index; ?>">
                                        <?php endforeach; ?>

                                    </div>
                                </div>

                            <?php
                            }
                            ?>


                            <span class="nias-login-message" style="display:none"></span>
                            <div class="nias-confirm-box">
                                <button class="nias-confirm-code">
                                    <input type="hidden" name="code" id="nias-verify-code" value="">
                                    <input type="hidden" name="identifier" value="">
                                    <input type="hidden" name="phone" value="">
                                    <input type="hidden" name="_wpnonce" value="">
                                    <input type="hidden" name="action" value="nias_verify">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                        <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z" />
                                        <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7" />
                                        <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9" />
                                    </svg>
                                    <?php echo esc_html(nias_login_modal_text('nias_login_text_confirm_button')); ?>
                                </button>
                                <button type="button" class="nias-change-phone" id="nias-change-number">
                                    <?php echo esc_html(nias_login_modal_text('nias_login_text_change_button')); ?>
                                </button>
                            </div>

                            <?php
                            // دکمه تمام‌عرض ورود با گوگل (در صورت فعال بودن) زیر دکمه‌های تایید کد و اصلاح
                            if (function_exists('nias_google_login_button')) {
                                echo nias_google_login_button();
                            }
                            ?>
                        </form>
                    </div>
                </div>

            </div>


        <?php
        } elseif ($selected_design_nias == 'elementory_design') {
        ?>
            <div class="nias-modal-box">
                <?php // شناسه‌ی قالب یا شورت‌کد — بدون نیاز به المنتور پرو
                echo nias_elementor_render_template($elementor_design_nias); ?>
            </div>
            <script>
                /* ناحیه ورود موفق و توست‌ها باید خارج از باکس مودال باشند تا استایل‌های باکس
                   (backdrop-filter آن position:fixed را نسبت به باکس محاسبه می‌کند) روی آن‌ها اثر نگذارد */
                (function() {
                    var box = document.querySelector('.nias-modal-box');
                    if (!box) return;
                    box.querySelectorAll('.nias-success, #nias-toast-wrap').forEach(function(el) {
                        box.parentNode.insertBefore(el, box);
                    });
                })();
            </script>
<?php
        }
    }
}

nias_show_modal();
