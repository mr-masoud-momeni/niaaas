jQuery(document).ready(function ($) {
  /**
   * Handle post-login redirect.
   * If the URL contains redirect_to (set by the auth blocker when intercepting
   * wp-login.php or wp-admin access), use that as the destination — but only
   * when it is same-origin to prevent open-redirect attacks.
   * Falls back to the server-provided redirect_url, then a page reload.
   */
  function niasHandleLoginRedirect(redirectUrl) {
    try {
      var urlRedirectTo = new URLSearchParams(window.location.search).get('redirect_to');
      if (urlRedirectTo) {
        var dest = new URL(urlRedirectTo, window.location.origin);
        if (dest.origin === window.location.origin) {
          window.location.href = dest.href;
          return;
        }
      }
    } catch (e) { /* ignore invalid URLs */ }

    if (redirectUrl) {
      window.location.href = redirectUrl;
    } else {
      location.reload();
    }
  }

  var defaultCountdown =
    typeof nias !== "undefined" && nias.countdown_duration
      ? parseInt(nias.countdown_duration)
      : 120;

  var countdown = defaultCountdown;
  var countdownInterval = null;

  var isPasswordOnlyMode = false;
  var isPasswordOtpMode = false;
  var isManualPasswordMode =
    typeof nias !== "undefined" && nias.manual_password_activate ? true : false;

  if (typeof nias !== "undefined" && nias.password_only_mode) {
    isPasswordOnlyMode = true;
  }

  /**
   * نمایش پیام‌ها به کاربر.
   *
   * توست صریحاً از همین‌جا صدا زده می‌شود. پیش‌تر لایه‌ی توست با MutationObserver
   * متن ناحیه‌های پیام را رصد می‌کرد و هر پلاگین ثالثی که DOM را بازنویسی می‌کرد
   * (فارسی‌سازِ اعداد، نیم‌فاصله‌گذار و…) همان لحظه‌ی لود صفحه توست جعلی می‌ساخت.
   */
  function niasNotify(text, type) {
    if (typeof window.niasToast === "function") {
      window.niasToast(text, type);
    }
  }

  /** نوشتن پیام در ناحیه‌ی درون‌خطی + نمایش توست */
  function niasShowMessage($el, text, type) {
    $el.addClass("active").text(text);
    niasNotify(text, type);
    return $el;
  }

  /** به‌روزرسانی متن راهنمای فرم کد + نمایش توست */
  function niasShowResult(text, type) {
    $(".nias-login-result").text(text);
    niasNotify(text, type);
  }

  /** توست ورود موفق، هم‌زمان با نمایش ناحیه‌ی .nias-success */
  function niasShowSuccess() {
    var title = $(".niaslogin-success-title").first().text().trim();
    niasNotify(title || "ورود با موفقیت انجام شد", "success");
  }

  /**
   * نمایش فیلدهای ساخت رمز عبور دستی برای کاربر جدید در تب پسورد.
   * فیلد ورود رمز موجود و دکمه فراموشی رمز که مخصوص کاربران قدیمی است مخفی می‌شود.
   */
  function showManualPasswordFields() {
    $(".nias-manual-password").show();
    // فیلد رمز موجود را مخفی و از حالت required خارج کن تا اعتبارسنجی مرورگر مانع ارسال نشود
    $("#ns_password").prop("required", false).closest(".nsinput-field").hide();
    $("#nias-forgot-password").hide().next(".ns-text-small").hide();
    setTimeout(function () {
      $("#nias_manual_new_password").focus();
    }, 50);
  }

  function hideManualPasswordFields() {
    $(".nias-manual-password").hide();
    $("#ns_password").prop("required", true).closest(".nsinput-field").show();
    $("#nias-forgot-password").show().next(".ns-text-small").show();
  }

  /**
   * اعتبارسنجی و دریافت رمز عبور دستی واردشده.
   * در صورت بروز خطا پیام را نمایش داده و null برمی‌گرداند.
   */
  function collectManualPassword(_message) {
    var np = $("#nias_manual_new_password").val();
    var cp = $("#nias_manual_confirm_password").val();

    if (!np || !cp) {
      niasShowMessage(_message, "لطفاً رمز عبور و تکرار آن را وارد کنید");
      return null;
    }
    if (np !== cp) {
      niasShowMessage(_message, "رمز عبور و تکرار آن یکسان نیستند");
      return null;
    }
    if (!validateInput("password", np)) {
      niasShowMessage(
        _message,
        "رمز عبور باید حداقل ۸ کاراکتر و شامل حروف و اعداد باشد"
      );
      return null;
    }
    return np;
  }

  /** محاسبه قدرت رمز عبور در بازه ۰ تا ۴ */
  function niasPasswordStrength(pw) {
    if (!pw) return 0;
    var score = 0;
    if (pw.length >= 8) score++;
    if (pw.length >= 12) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 4);
  }

  $(document).on("input", "#nias_manual_new_password", function () {
    var pw = $(this).val();
    var s = niasPasswordStrength(pw);
    var pct = (s / 4) * 100;
    var labels = ["خیلی ضعیف", "ضعیف", "متوسط", "خوب", "قوی"];
    var colors = ["#e53935", "#fb8c00", "#fdd835", "#43a047", "#2e7d32"];
    var $block = $(this).closest(".nias-manual-password");
    $block
      .find(".nias-pass-strength__bar")
      .css({ width: pct + "%", background: colors[s] });
    $block
      .find(".nias-pass-strength__text")
      .text(pw ? labels[s] : "قدرت رمز عبور");
  });

  /* ── آیکون چشم نمایش/مخفی‌سازی رمز عبور ───────────────────────────────── */
  var NIAS_EYE_ICON =
    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg>';
  var NIAS_EYE_OFF_ICON =
    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.9 5.2A9.9 9.9 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.6M6.1 6.1A17 17 0 0 0 2 12s3.5 7 10 7a9.9 9.9 0 0 0 4.1-.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  function niasInitPasswordToggles() {
    document
      .querySelectorAll(".nsinput-field input[type='password']")
      .forEach(function (input) {
        var field = input.closest(".nsinput-field");
        if (!field || field.querySelector(".nias-pass-toggle")) return;
        field.classList.add("nias-has-toggle");
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "nias-pass-toggle";
        btn.setAttribute("aria-label", "نمایش رمز عبور");
        btn.innerHTML = NIAS_EYE_ICON;
        field.appendChild(btn);
      });
  }

  $(document).on("click", ".nias-pass-toggle", function () {
    var input = $(this).closest(".nsinput-field").find("input")[0];
    if (!input) return;
    if (input.type === "password") {
      input.type = "text";
      this.innerHTML = NIAS_EYE_OFF_ICON;
      this.setAttribute("aria-label", "مخفی کردن رمز عبور");
    } else {
      input.type = "password";
      this.innerHTML = NIAS_EYE_ICON;
      this.setAttribute("aria-label", "نمایش رمز عبور");
    }
  });

  niasInitPasswordToggles();

  function countdown_handle() {
    if (countdown <= 0) {
      $(".nias-resendbox").addClass("active");
      $(".nias-login-message")
        .removeClass("nias-countdown")
        .text("مدت زمان کد قبلی به پایان رسید لطفاً مجدداً تلاش کنید");
      niasNotify("مدت زمان کد قبلی به پایان رسید لطفاً مجدداً تلاش کنید", "warning");
      if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
      }
      return;
    }

    countdown--;
    set_time(countdown);
  }

  function start_countdown(duration) {
    if (isPasswordOnlyMode) return;

    countdown = duration || 120;
    initialCountdownValue = null;
    if (countdownInterval) {
      clearInterval(countdownInterval);
    }
    countdownInterval = setInterval(countdown_handle, 1000);
  }

  var initialCountdownValue = null;

  function set_time(countdown) {
    if (isPasswordOnlyMode) return;

    if (initialCountdownValue === null) {
      initialCountdownValue = countdown;
    }

    let remain = countdown >= 0 ? countdown : 0;
    let minute = Math.floor(remain / 60);
    minute = minute < 10 ? "0" + minute : minute;

    let second = remain % 60;
    second = second < 10 ? "0" + second : second;

    let time = `${minute}:${second}`;
    $(".nias-countdown").text(time);

    const totalDasharray = 345.575;
    const step = totalDasharray / initialCountdownValue;
    const elapsedSeconds = initialCountdownValue - remain;
    const newDashoffset = step * elapsedSeconds;

    $(".stroke-neutral-dark").css("stroke-dashoffset", newDashoffset);
  }

  $(document).on("focus", ".nias-code-box input", function (e) {
    $(this).select();
  });

  $(document).on("input", ".nias-code-box input", function (e) {
    let code = $(this).val().trim();
    if (code.length) {
      if ($(this).next().length) {
        $(this).next().focus();
      }
      if ($(this).index() >= $(".nias-code-box input").length - 1) {
        $("#nias-code-form").submit();
      }
    }
  });

  $(document).on("keydown", ".nias-code-box input", function (e) {
    if (e.key === "Backspace" && $(this).val().trim() === "") {
      let prevInput = $(this).prev();
      if (prevInput.length) {
        prevInput.val("").focus();
      }
    }
  });

  $("#nias-change-number").click(toggle_form);

  function toggle_form() {
    $(".nias-main-modal").toggleClass("verify");
    $(".nias-code-box input").val("");
  }

  function close_modal() {
    $(".nias-modal-box").removeClass("open");
    isPasswordOtpMode = false;
    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
  }

  function open_modal(e) {
    e.preventDefault();
    $(".nias-modal-box").addClass("open");
  }

  $(".nias-close-modal").click(close_modal);

  // Close the modal when clicking outside the box (overlay area)
  $(document).on("click", ".nias-modal-box", function (e) {
    if (e.target !== this) return;
    if (typeof nias === "undefined" || !nias.closeOnOutsideClick) return;
    if ($("body").hasClass("nias-locked")) return;
    close_modal();
  });

  // Converted to vanilla JS fetch
  $("#nias-login").submit(function (e) {
    e.preventDefault();

    let data = $(this).serialize();
    let _this = $(this);
    let _message = $(this).find(".nias-login-message");
    let _btn = $(this).find("button");
    let _resend = $(".nias-resend");

    // Convert serialized data to URLSearchParams
    const formData = new URLSearchParams(data);

    fetch(nias.site_url + "/nias-login", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: formData.toString(),
    })
      .then((response) => {
        if (!response.ok && response.status !== 400) {
          throw response;
        }
        return response.json();
      })
      .then((result) => {
        if (result.success) {
          if (result.data.login_mode === "password_only") {
            isPasswordOnlyMode = true;
            niasShowResult(result.data.message);
            $(".nias-main-modal").addClass("verify");

            $(".nias-code-box").hide();
            $(".nias-resendbox").hide();
            $(".nias-countdown").hide();

            $("#nias-panel-2, .nias-panel").show();

            // ذخیره identifier و nonce در صورت ارسال (حالت ثبت رمز دستی)
            if (result.data.identifier) {
              $("#nias-code-form input[name='identifier']").val(
                result.data.identifier
              );
            }
            if (result.data._wpnonce) {
              $("#nias-code-form input[name='_wpnonce']").val(
                result.data._wpnonce
              );
            }

            // فقط برای کاربر جدید: نمایش فیلدهای ساخت رمز عبور
            if (isManualPasswordMode && result.data.is_new_user) {
              showManualPasswordFields();
            } else {
              hideManualPasswordFields();
              $("#ns_password").focus();
            }

            return;
          }

          // اگر پسورد ارسال شده (کاربر جدید در حالت پسورد)
          if (result.data.password_sent) {
            niasShowResult(result.data.message);
            $(".nias-main-modal").addClass("verify");

            // مخفی کردن بخش کد تایید
            $(".nias-code-box").hide();
            $(".nias-resendbox").hide();
            $(".nias-countdown").hide();

            // نمایش فرم ورود با پسورد (تب-1 در ساختار جدید)
            $("#nias-panel-1, .nias-panel").show();
            $("#ns_password").focus();

            // ذخیره identifier برای ورود
            $("#nias-code-form input[name='identifier']").val(
              result.data.identifier
            );
            $("#nias-code-form input[name='_wpnonce']").val(result.data._wpnonce);

            return;
          }

          // حالت password_otp_activate: هیچ چیز ارسال نمی‌شود، فقط تب‌ها نمایش داده می‌شوند
          // پسورد از طریق دکمه فراموشی رمز ارسال می‌شود
          // OTP فقط با باز کردن تب کد تایید ارسال می‌شود
          if (result.data.password_otp_mode) {
            isPasswordOtpMode = true;

            niasShowResult(result.data.message);
            $(".nias-main-modal").addClass("verify");

            // ذخیره identifier و nonce
            $("#nias-code-form input[name='identifier']").val(result.data.identifier);
            $("#nias-code-form input[name='_wpnonce']").val(result.data._wpnonce);

            // تب پسورد (تب-1) به صورت پیش‌فرض نمایش داده می‌شود
            $("#nias-tab-1").prop("checked", true);

            // فقط برای کاربر جدید: نمایش فیلدهای ساخت رمز عبور در تب پسورد
            if (isManualPasswordMode && result.data.is_new_user) {
              showManualPasswordFields();
            } else {
              hideManualPasswordFields();
              $("#ns_password").focus();
            }

            return;
          }

          start_countdown(result.data.duration);

          niasShowResult(result.data.message);
          $(".nias-main-modal").addClass("verify");

          $("#nias-code-form input[name='identifier']").val(
            result.data.identifier
          );
          $("#nias-code-form input[name='_wpnonce']").val(result.data._wpnonce);

          if (
            result.data.is_new_user &&
            (result.data.email_sent || isEmailInput(result.data.identifier))
          ) {
            $(".nias-config-password").show();
            $(".nias-save-password").show();
            $(".nias-code-box input").eq(0).focus();
          } else {
            $(".nias-config-password").hide();
            $(".nias-save-password").hide();
            $(".nias-code-box input").eq(0).focus();
          }

          display_send_results(result.data);
        }
      })
      .catch((error) => {
        if (error instanceof Response) {
          error.json().then((result) => {
            if (
              result &&
              result.data &&
              result.data.error_code === 123 &&
              result.data.identifier
            ) {
              niasShowResult(result.data.message);
              $(".nias-main-modal").addClass("verify");
              $("#nias-code-form input[name='identifier']").val(result.data.identifier);
              if (result.data._wpnonce) {
                $("#nias-code-form input[name='_wpnonce']").val(result.data._wpnonce);
              }
              if (result.data.duration) {
                start_countdown(result.data.duration);
              }
              $(".nias-config-password").hide();
              $(".nias-save-password").hide();
              $(".nias-code-box input").eq(0).focus();
              return;
            }
            handle_error({ status: error.status, responseJSON: result }, _message);
          });
        } else {
          handle_error({ responseJSON: null }, _message);
        }
      })
      .finally(() => {
        _this.removeClass("loading");
        _btn.attr("disabled", false);
        $(".nias-resendbox").removeClass("loading");
        if (!isPasswordOnlyMode) {
          _resend.text("ارسال مجدد");
        }
      });

    // beforeSend equivalent
    _this.addClass("loading");
    _message.removeClass("active");
    _btn.attr("disabled", true);
    if (!isPasswordOnlyMode) {
      _resend.text("درحال ارسال...");
      $(".nias-resendbox").removeClass("active");
      $(".nias-resendbox").addClass("loading");
    }
  });

  $(document).on("click", ".nias-resendbox.active .nias-resend", function (e) {
    e.preventDefault();

    // فیلدهای کد قبلی پیش از ارسال مجدد پاک شوند
    $(".nias-code-box input").val("");
    $(".nias-code-box input").eq(0).focus();

    if (countdown <= 0 && !isPasswordOnlyMode) {
      if (isPasswordOtpMode) {
        // در حالت password_otp: مطمئن شو تب کد فعاله، بعد OTP جدید بفرست
        // data-from-resend را ست کن تا handler تب دوباره triggerSendOtp صدا نزند
        $("#nias-tab-2").data("from-resend", true).prop("checked", true).trigger("change");
        triggerSendOtp();
      } else {
        $("#nias-login").submit();
      }
    }
  });

  // هنگام کلیک روی تب کد تایید (تب-2) در حالت password_otp_activate
  $(document).on("change", "#nias-tab-2", function () {
    if (!isPasswordOtpMode) return;

    // اگر تایمر هنوز در حال اجراست، کد قبلی هنوز معتبر است
    // فقط فیلدها را فعال کن و تایمر را ادامه بده، بدون درخواست جدید
    if (countdownInterval !== null && countdown > 0) {
      $(".nias-code-box input").prop("disabled", false).eq(0).focus();
      // تایمر را دوباره رندر کن تا مقدار فعلی نمایش داده شود
      set_time(countdown);
      return;
    }

    // فقط اگر از طریق کلیک مستقیم روی تب آمده‌ایم (نه از resend که خودش triggerSendOtp صدا می‌زند)
    if (!$(this).data("from-resend")) {
      triggerSendOtp();
    }
    $(this).removeData("from-resend");
  });

  function triggerSendOtp() {
    var identifier = $("#nias-code-form input[name='identifier']").val();
    if (!identifier) return;

    // نمایش لودینگ
    $("#nias-code-form").addClass("nias-otp-sending");
    $(".nias-code-box input").val("").prop("disabled", true);
    $(".nias-login-message").removeClass("active");
    $(".nias-resendbox").removeClass("active").addClass("loading");

    fetch(nias.site_url + "/nias-login", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        identifier: identifier,
        nias_action: "send_otp",
      }).toString(),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (result) {
        if (result && result.success) {
          // موفق: شروع شمارش معکوس و فعال کردن فیلدهای کد
          start_countdown(result.data.duration);
          niasShowResult(result.data.message);
          $(".nias-code-box input").prop("disabled", false).eq(0).focus();
        } else {
          var errMsg =
            result && result.data && result.data.message
              ? result.data.message
              : "خطا در ارسال کد تایید، لطفاً مجدداً تلاش کنید";

          // اگر کد قبلی هنوز معتبر است (error_code 123)، فیلدها را فعال کن
          if (result && result.data && result.data.error_code === 123) {
            $(".nias-code-box input").prop("disabled", false).eq(0).focus();
            if (result.data.duration) {
              start_countdown(result.data.duration);
            }
          } else {
            niasShowMessage($(".nias-login-message"), errMsg);
            $(".nias-code-box input").prop("disabled", false);
            if (isPasswordOtpMode) {
              // در حالت password_otp: روی تب کد بمان، resend را دوباره فعال کن تا retry ممکن باشد
              $(".nias-resendbox").addClass("active");
            } else {
              niasShowResult("برای دریافت کد تایید روی این تب کلیک کنید");
              $("#nias-tab-1").prop("checked", true);
            }
          }
        }
      })
      .catch(function () {
        niasShowMessage(
          $(".nias-login-message"),
          "خطا در ارسال کد تایید، لطفاً مجدداً تلاش کنید"
        );
        $(".nias-code-box input").prop("disabled", false);
        if (isPasswordOtpMode) {
          $(".nias-resendbox").addClass("active");
        } else {
          niasShowResult("برای دریافت کد تایید روی این تب کلیک کنید");
          $("#nias-tab-1").prop("checked", true);
        }
      })
      .finally(function () {
        $("#nias-code-form").removeClass("nias-otp-sending");
        $(".nias-resendbox").removeClass("loading");
      });
  }

  // Converted to vanilla JS fetch
  $(document).on("submit", "#nias-code-form", function (e) {
    e.preventDefault();

    var _this = $(this);
    var _message = $(this).find(".nias-login-message");
    var _message_s = $(".nias-success");
    var _btn = $(this).find(".nias-confirm-code");

    if (isPasswordOnlyMode) {
      var identifier =
        getIdentifierFromLoginForm() ||
        $("#nias-code-form input[name='identifier']").val();

      if (!identifier) {
        niasShowMessage(
          _message,
          "لطفاً ابتدا ایمیل یا شماره موبایل خود را وارد کنید"
        );
        return;
      }

      var postData;

      // کاربر جدید با ثبت رمز دستی → ساخت حساب با رمز انتخابی
      if (isManualPasswordMode && $(".nias-manual-password").is(":visible")) {
        var manualPw = collectManualPassword(_message);
        if (!manualPw) return;

        postData = {
          identifier: identifier,
          password: manualPw,
          action: "nias_verify",
          register_with_password: 1,
          _wpnonce: $("#nias-code-form input[name='_wpnonce']").val(),
        };
      } else {
        let password = $("#ns_password").val();

        if (!password) {
          niasShowMessage(_message, "لطفاً رمز عبور را وارد کنید");
          return;
        }

        postData = {
          identifier: identifier,
          password: password,
          action: "nias_verify",
          login_with_password: 1,
        };
      }

      const formData = new URLSearchParams(postData);

      // beforeSend
      _this.addClass("loading");
      _btn.attr("disabled", true);
      _message.removeClass("active");

      fetch(nias.site_url + "/nias-verify", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: formData.toString(),
      })
        .then((response) => {
          if (!response.ok) {
            throw response;
          }
          return response.json();
        })
        .then((result) => {
          if (result.success) {
            _message_s.show();
            niasShowSuccess();
            setTimeout(function () {
              niasHandleLoginRedirect(result.data.redirect_url);
            }, 2000);
          }
        })
        .catch((error) => {
          navigator.vibrate && navigator.vibrate(200);
          if (error instanceof Response) {
            error.json().then((result) => {
              handle_error({ status: error.status, responseJSON: result }, _message);
            });
          } else {
            handle_error({ responseJSON: null }, _message);
          }
        })
        .finally(() => {
          _this.removeClass("loading");
          _btn.attr("disabled", false);
        });

      return;
    }

    var identifier = $("#nias-code-form input[name='identifier']").val();
    var nonce = $("#nias-code-form input[name='_wpnonce']").val();

    if (!identifier) {
      niasShowMessage(_message, "خطا در شناسایی کاربر، لطفاً مجدداً تلاش کنید");
      return;
    }

    // در ساختار جدید: تب-1 = پسورد، تب-2 = کد تایید
    var isPasswordTab =
      $('input[name="nias-tabs"]:checked').attr("id") === "nias-tab-1";
    var isCodeTab =
      $('input[name="nias-tabs"]:checked').attr("id") === "nias-tab-2";

    var postData = {
      identifier: identifier,
      _wpnonce: nonce,
      action: "nias_verify",
    };

    if (isCodeTab || !$('input[name="nias-tabs"]').length) {
      let code = "";
      $(this)
        .find(".nias-code-box input")
        .each(function () {
          code += $(this).val();
        });

      if (code.length < $(".nias-code-box input").length) {
        niasShowMessage(_message, "لطفاً همه فیلدهای کد را تکمیل کنید");
        return;
      }

      postData.code = code;

      if ($(".nias-config-password").is(":visible")) {
        let newPassword = $("#new_password").val();
        let confirmPassword = $("#confirm_password").val();

        if (newPassword && newPassword !== confirmPassword) {
          niasShowMessage(_message, "رمز عبور و تایید آن یکسان نیستند");
          return;
        }

        if (newPassword && !validateInput("password", newPassword)) {
          niasShowMessage(
            _message,
            "رمز عبور باید حداقل 8 کاراکتر و شامل حروف و اعداد باشد"
          );
          return;
        }

        if (newPassword) {
          postData.password = newPassword;
        }
      }
    } else if (isPasswordTab) {
      // کاربر جدید با ثبت رمز دستی → ساخت حساب با رمز انتخابی
      if (isManualPasswordMode && $(".nias-manual-password").is(":visible")) {
        var manualPw = collectManualPassword(_message);
        if (!manualPw) return;

        postData = {
          identifier: identifier,
          _wpnonce: nonce,
          action: "nias_verify",
          password: manualPw,
          register_with_password: 1,
        };
      } else {
        let password = $("#ns_password").val();

        if (!password) {
          niasShowMessage(_message, "لطفاً رمز عبور را وارد کنید");
          return;
        }

        postData = {
          identifier: identifier,
          _wpnonce: nonce,
          action: "nias_verify",
          password: password,
          login_with_password: 1,
        };
      }
    }

    const formData = new URLSearchParams(postData);

    // beforeSend
    _this.addClass("loading");
    _btn.attr("disabled", true);
    _message.removeClass("active");

    fetch(nias.site_url + "/nias-verify", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: formData.toString(),
    })
      .then((response) => {
        if (!response.ok) {
          throw response;
        }
        return response.json();
      })
      .then((result) => {
        if (result.success) {
          _message_s.show();
          niasShowSuccess();
          setTimeout(function () {
            niasHandleLoginRedirect(result.data.redirect_url);
          }, 2000);
        }
      })
      .catch((error) => {
        navigator.vibrate && navigator.vibrate(200);
        if (error instanceof Response) {
          error.json().then((result) => {
            handle_error({ status: error.status, responseJSON: result }, _message);
          });
        } else {
          handle_error({ responseJSON: null }, _message);
        }
      })
      .finally(() => {
        _this.removeClass("loading");
        _btn.attr("disabled", false);
      });
  });

  function getIdentifierFromLoginForm() {
    var identifier = null;

    if ($("#nias_email_phone_input").length) {
      identifier = $("#nias_email_phone_input").val().trim();
    } else if ($("#nias_email_input").length) {
      identifier = $("#nias_email_input").val().trim();
    } else if ($("#niasphoneinput").length) {
      identifier = $("#niasphoneinput").val().trim();
    }

    return identifier;
  }

  function handle_error(xhr, messageElement) {
    let result = xhr.responseJSON;

    if (xhr.status === 400) {
      niasShowMessage(messageElement, "شما قبلاً وارد شده‌اید، صفحه رفرش می‌شود...");
    } else if (xhr.responseText && xhr.responseText.includes("Denied")) {
      niasShowMessage(messageElement, "لطفاً فیلترشکن را خاموش کنید");
    } else if (result && result.data && result.data.message) {
      niasShowMessage(messageElement, result.data.message);

      if (result.data.error_code === 123 && !isPasswordOnlyMode) {
        $(".nias-resendbox").removeClass("active");
      }
    } else {
      niasShowMessage(messageElement, "خطایی رخ داده است، لطفاً مجدداً تلاش کنید");
    }
  }

  function display_send_results(data) {
    if (isPasswordOnlyMode) return;

    if (data.send_results) {
      let sendInfo = [];

      if (data.phone_sent && data.send_results.sms) {
        sendInfo.push(
          "پیامک: " + (data.send_results.sms.success ? "موفق" : "ناموفق")
        );
      }

      if (data.email_sent && data.send_results.email) {
        sendInfo.push(
          "ایمیل: " + (data.send_results.email.success ? "موفق" : "ناموفق")
        );
      }

      if (sendInfo.length > 0) {
        //console.log("وضعیت ارسال:", sendInfo.join(" | "));
      }
    }
  }

  function process_otp(otp) {
    if (isPasswordOnlyMode) return;

    let code = otp.code;
    let code_array = code.split("");

    for (
      let i = 0;
      i < code_array.length && i < $(".nias-code-box input").length;
      i++
    ) {
      $(".nias-code-box input").eq(i).val(code_array[i]);
    }

    $("#nias-verify-code").val(code);
    setTimeout(function () {
      $("#nias-code-form").submit();
    }, 500);
  }

  if ("OTPCredential" in window) {
    let otpController = null;

    $(document).on("DOMNodeInserted", function (e) {
      if (
        $(e.target).hasClass("nias-code-box") ||
        $(e.target).find(".nias-code-box").length
      ) {
        startOTPListener();
      }
    });

    function startOTPListener() {
      if (isPasswordOnlyMode) return;

      if (otpController) {
        otpController.abort();
      }

      otpController = new AbortController();

      navigator.credentials
        .get({
          otp: { transport: ["sms"] },
          signal: otpController.signal,
        })
        .then(function (otp) {
          if (otp && otp.code) {
            process_otp(otp);
          }
        })
        .catch(function (err) {
          if (err.name !== "AbortError") {
            //console.log('OTP detection error:', err);
          }
        });
    }

    $(".nias-close-modal").on("click", function () {
      if (otpController) {
        otpController.abort();
      }
    });

    $("#nias-change-number").on("click", function () {
      if (otpController) {
        otpController.abort();
      }
    });
  }
  setTimeout(function () {
    if ("OTPCredential" in window && !isPasswordOnlyMode) {
      startOTPListener();
    }
  }, 300);

  $(document).ready(function () {
    $("#niasphoneinput").on("input", function () {
      var inputValue = $(this).val();

      if (inputValue.length >= 3) {
        if (inputValue.startsWith("09")) {
          // شماره صحیح است
        } else if (inputValue.startsWith("9")) {
          $(this).val("0" + inputValue);
        } else {
          var cleanValue = inputValue.replace(/[^\d]/g, "");
          $(this).val("09" + cleanValue);
        }
      }

      if (inputValue.length === 11 && inputValue.startsWith("09")) {
        $(".nias-checkicon").addClass("correct");
      } else {
        $(".nias-checkicon").removeClass("correct");
      }
    });
  });

  $(document).ready(function () {
    if (typeof nias !== "undefined" && nias.lockedPages) {
      var lockedPages = nias.lockedPages;
      lockedPages.forEach(function (page) {
        if (window.location.href.indexOf(page) > -1) {
          $(".nias-modal-box").addClass("open");
          $("body > *")
            .not(".nias-modal-box, .nias-success, #nias-toast-wrap, script, link, style")
            .remove();
          $(".nias-close-modal").remove();
        }
      });
    }

    if (typeof nias !== "undefined" && nias.clickLinks) {
      var clickLinks = nias.clickLinks;
      clickLinks.forEach(function (link) {
        $(document).on("click", "a[href*='" + link + "']", open_modal);
      });
    }

    // باز کردن مودال با کلیک روی کلاس‌های تعیین‌شده
    if (typeof nias !== "undefined" && nias.clickClasses) {
      nias.clickClasses.forEach(function (cls) {
        cls = (cls || "").replace(/^\./, "").trim();
        if (cls) {
          $(document).on("click", "." + cls, open_modal);
        }
      });
    }

    // باز کردن مودال با کلیک روی ID های تعیین‌شده
    if (typeof nias !== "undefined" && nias.clickIds) {
      nias.clickIds.forEach(function (id) {
        id = (id || "").replace(/^#/, "").trim();
        if (id) {
          $(document).on("click", "#" + id, open_modal);
        }
      });
    }
  });

  function isEmailInput(identifier) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);
  }

  $(document).on("change", 'input[name="nias-tabs"]', function () {
    var selectedTab = $(this).attr("id");

    $('label[role="tab"]')
      .attr("aria-selected", "false")
      .attr("tabindex", "-1");
    $('label[for="' + selectedTab + '"]')
      .attr("aria-selected", "true")
      .attr("tabindex", "0");

    // در حالت password_otp: وقتی به تب پسورد می‌رویم، فیلدهای کد را disable کن
    // تا وقتی دوباره به تب کد برگشتیم، منطق بررسی تایمر درست کار کند
    if (isPasswordOtpMode && selectedTab === "nias-tab-1") {
      $(".nias-code-box input").prop("disabled", true);
    }
  });

  $(".nias-save-password").on("click", function (e) {
    e.preventDefault();

    var currentPassword = $("#current_password").val();
    var newPassword = $("#new_password").val();
    var confirmPassword = $("#confirm_password").val();

    if (!currentPassword || !newPassword || !confirmPassword) {
      niasShowMessage($(".nias-login-message"), "لطفاً همه فیلدها را تکمیل کنید");
      return;
    }

    if (newPassword !== confirmPassword) {
      niasShowMessage(
        $(".nias-login-message"),
        "رمز عبور جدید و تایید آن یکسان نیستند"
      );
      return;
    }

    if (newPassword.length < 8) {
      niasShowMessage($(".nias-login-message"), "رمز عبور باید حداقل 8 کاراکتر باشد");
      return;
    }

    $(".nias-login-message")
      .removeClass("active")
      .text("رمز عبور با موفقیت ذخیره شد");
    niasNotify("رمز عبور با موفقیت ذخیره شد", "success");
  });

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof wp !== "undefined" && wp.apiFetch) {
      var rootURLMiddleware = wp.apiFetch.createRootURLMiddleware(
        nias.site_url + "/wp-json/"
      );
      wp.apiFetch.use(rootURLMiddleware);
    }
  });

  function validateInput(type, value) {
    switch (type) {
      case "email":
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      case "phone":
        return /^09[0-9]{9}$/.test(value);
      case "password":
        return (
          value.length >= 8 && /[A-Za-z]/.test(value) && /[0-9]/.test(value)
        );
      default:
        return true;
    }
  }

  $(document).on("blur", 'input[type="email"]', function () {
    var email = $(this).val();
    if (email && !validateInput("email", email)) {
      $(this).addClass("error");
      niasShowMessage($(".nias-login-message"), "فرمت ایمیل صحیح نیست");
    } else {
      $(this).removeClass("error");
    }
  });

  $(document).on("blur", 'input[name="phone"], #niasphoneinput', function () {
    var phone = $(this).val();
    if (phone && !validateInput("phone", phone)) {
      $(this).addClass("error");
      niasShowMessage(
        $(".nias-login-message"),
        "شماره موبایل باید 11 رقم و با 09 شروع شود"
      );
    } else {
      $(this).removeClass("error");
    }
  });

  // Converted to vanilla JS fetch
  $(document).on("click", "#nias-forgot-password", function (e) {
    e.preventDefault();

    var identifier = null;

    if ($("#nias_forgot_email_phone").length) {
      identifier = $("#nias_forgot_email_phone").val().trim();
    } else if ($("#nias_forgot_email").length) {
      identifier = $("#nias_forgot_email").val().trim();
    }

    if (!identifier) {
      identifier = getIdentifierFromLoginForm();
    }

    if (!identifier) {
      niasShowMessage(
        $(".nias-login-message"),
        "لطفاً ایمیل یا شماره موبایل خود را وارد کنید"
      );
      return;
    }

    var _btn = $(this);
    var _message = $(".nias-login-message");

    const postData = new URLSearchParams({
      identifier: identifier,
      action: "nias_forgot_password",
    });

    // beforeSend
    _btn.text("در حال ارسال...").attr("disabled", true);
    _message.removeClass("active");

    fetch(nias.site_url + "/nias-forgot-password", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: postData.toString(),
    })
      .then((response) => {
        if (!response.ok) {
          throw response;
        }
        return response.json();
      })
      .then((result) => {
        if (result.success) {
          _message.removeClass("active").text(result.data.message).show();
          niasNotify(result.data.message, "success");
          setTimeout(function () {
            _message.hide();
          }, 5000);
        }
      })
      .catch((error) => {
        if (error instanceof Response) {
          error.json().then((result) => {
            handle_error({ status: error.status, responseJSON: result }, _message);
          });
        } else {
          handle_error({ responseJSON: null }, _message);
        }
      })
      .finally(() => {
        _btn.text("فراموشی رمز عبور").attr("disabled", false);
      });
  });
});