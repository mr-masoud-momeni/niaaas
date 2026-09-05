jQuery(document).ready(function ($) {
  var _debug = niasLoginFastsellData && niasLoginFastsellData.debug_log;
  function _log() { if (_debug) console.log.apply(console, arguments); }
  function _warn() { if (_debug) console.warn.apply(console, arguments); }
  function _err() { if (_debug) console.error.apply(console, arguments); }

  // تبدیل ارقام لاتین به فارسی برای نمایش در بَج‌ها
  function toPersianDigits(n) {
    return String(n).replace(/[0-9]/g, function (d) {
      return "۰۱۲۳۴۵۶۷۸۹"[d];
    });
  }

  // به‌روزرسانی بَج تعداد آیتم‌ها روی تب «سبد خرید»
  function updateCartBadge() {
    var count = $(
      ".nias-login-fastsell-cart-container .nias-login-fastsell-cart-item",
    ).length;
    $(
      '.nias-login-fastsell-step[data-step="1"] .nias-login-fastsell-step-number',
    ).text(toPersianDigits(count));
  }

  // حالت درون‌صفحه‌ای — خروجی شورت‌کد [nias_fastsell] که به‌جای مودال، داخل خود
  // صفحه (سبد خرید / تسویه حساب) رندر می‌شود. مارک‌آپ و id یکی است، فقط پوسته‌ی
  // مودال ندارد: نه باز/بسته می‌شود، نه اسکرول صفحه را قفل می‌کند.
  var _modalEl = document.getElementById("nias-login-fastsell-modal");
  var _inline =
    !!_modalEl && _modalEl.classList.contains("nias-login-fastsell-inline");

  // پیامی که وقتی درخواست اصلاً به سرور نمی‌رسد (۴۰۴/۴۰۳/۵xx یا پاسخ غیر JSON)
  // به‌جای تب‌های خالی مودال نمایش داده می‌شود
  var NIAS_FATAL_MESSAGE =
    "ثبت محصول ناموفق بود لطفاً فیلترشکن خود را در صورت فعال بودن خاموش کنید و صفحه را دوباره باز کنید";

  // ── مقاومت در برابر nonce منقضی/کش‌شده ──────────────────────────────────
  // افزونه‌های کش (لایت‌اسپید، WP Rocket و ...) خروجی wp_localize_script را
  // داخل فایل جاوااسکریپت ادغام و کش می‌کنند؛ nonce برای همه بازدیدکننده‌ها
  // ثابت و بعد از چند ساعت منقضی می‌شود. آن‌وقت سرور درخواست را رد می‌کند و
  // کاربر فقط یک خطای مبهم می‌بیند که شبیه بلاک‌شدن توسط افزونه امنیتی است.
  // اینجا یک بار nonce تازه گرفته و همان درخواست دوباره فرستاده می‌شود.
  function _isNonceFailure(response, jqXHR) {
    if (response && typeof response === "object") {
      return !!(response.data && response.data.nonce_expired);
    }
    var body =
      jqXHR && typeof jqXHR.responseText === "string"
        ? $.trim(jqXHR.responseText)
        : "";
    // نسخه‌های قدیمی سرور با wp_die(-1, 403) پاسخ می‌دادند
    return response === -1 || response === "-1" || body === "-1";
  }

  function _ajax(options) {
    var retried = false;
    var pendingRetry = false;
    var canRetry = !!(
      options.data &&
      typeof options.data === "object" &&
      typeof options.data.nonce !== "undefined"
    );
    var userSuccess = options.success;
    var userError = options.error;
    var userComplete = options.complete;

    function scheduleRetry() {
      retried = true;
      pendingRetry = true;
      _warn("[NIAS fastsell] nonce رد شد؛ nonce تازه گرفته و درخواست تکرار می‌شود");
      refreshFastsellNonce(function () {
        options.data.nonce = niasLoginFastsellData.nonce;
        send();
      });
    }

    function send() {
      return $.ajax(
        $.extend({}, options, {
          success: function (response, textStatus, jqXHR) {
            if (!retried && canRetry && _isNonceFailure(response, jqXHR)) {
              scheduleRetry();
              return;
            }
            if (userSuccess) {
              userSuccess.apply(this, arguments);
            }
          },
          error: function (jqXHR) {
            if (
              !retried &&
              canRetry &&
              jqXHR &&
              jqXHR.status === 403 &&
              _isNonceFailure(null, jqXHR)
            ) {
              scheduleRetry();
              return;
            }
            if (userError) {
              userError.apply(this, arguments);
            }
          },
          complete: function () {
            // تلاش ناموفقِ اول نباید لودینگ را ببندد یا دکمه را آزاد کند
            if (pendingRetry) {
              pendingRetry = false;
              return;
            }
            if (userComplete) {
              userComplete.apply(this, arguments);
            }
          },
        }),
      );
    }

    return send();
  }

  // باز شدن مودال پس از «افزودن به سبد» — در تنظیمات ادمین قابل خاموش کردن است.
  // خاموش که باشد محصول بی‌سروصدا به سبد اضافه می‌شود و مودال باز نمی‌شود.
  // wp_localize_script همه مقادیر را رشته می‌کند (false → "")، پس مقایسه با
  // false کار نمی‌کند و باید truthy بررسی شود. نبودِ کلید = رفتار پیش‌فرض (باز شود).
  var _openAfterAdd =
    !niasLoginFastsellData ||
    typeof niasLoginFastsellData.open_modal_after_add === "undefined" ||
    !!niasLoginFastsellData.open_modal_after_add;

  // حالت خرید سریع تکی — حذف مرحله سبد خرید و تب مراحل
  var _buyNow = !!(niasLoginFastsellData && niasLoginFastsellData.buy_now);
  // این حالت مربوط به فلوی «افزودن به سبد» در صفحه محصول است؛ در بلوک
  // درون‌صفحه‌ای نباید تب سبد خرید و کوپن حذف شوند
  if (_buyNow && !_inline) {
    $("body").addClass("nias-fastsell-buynow-mode");
  }

  // بررسی وجود تنظیمات المنتوری و اعمال آن‌ها
  function applyElementorSettings() {
    if (typeof window.niasFastsellElementorSettings !== "undefined") {
      const settings = window.niasFastsellElementorSettings;

      if (settings.cart_title) {
        $('.nias-login-fastsell-step-title[data-default="سبد خرید"]').text(
          settings.cart_title,
        );
      }
      if (settings.checkout_title) {
        $('.nias-login-fastsell-step-title[data-default="تسویه حساب"]').text(
          settings.checkout_title,
        );
      }
      if (settings.coupon_placeholder) {
        $("#nias-login-fastsell-coupon-code").attr(
          "placeholder",
          settings.coupon_placeholder,
        );
      }
      if (settings.coupon_button) {
        $("#nias-login-fastsell-apply-coupon").text(settings.coupon_button);
      }
      if (settings.next_button) {
        $(".nias-login-fastsell-btn-next").text(settings.next_button);
      }
      if (settings.back_button) {
        $(".nias-login-fastsell-btn-back").text(settings.back_button);
      }
      if (settings.submit_button) {
        $(".nias-login-fastsell-btn-submit").text(settings.submit_button);
      }
      if (settings.close_outside === false) {
        $(".nias-login-fastsell-modal-overlay").off("click");
      }
    }
  }

  // اگر فقط یک درگاه پرداخت فعال باشد، بخش روش پرداخت پنهان می‌شود
  // (همان تک درگاه به‌صورت خودکار انتخاب شده باقی می‌ماند)
  function togglePaymentSectionVisibility() {
    var $section = $(".nias-login-fastsell-payment-methods");
    if (!$section.length) return;
    var $radios = $section.find('input[name="payment_method"]');
    if ($radios.length === 1) {
      $radios.prop("checked", true);
      $section.hide();
    } else {
      $section.show();
    }
  }

  const modal = {
    el: $("#nias-login-fastsell-modal"),
    currentStep: 1,
    loadingCount: 0,

    open: function () {
      // در حالت درون‌صفحه‌ای چیزی باز نمی‌شود و اسکرول صفحه نباید قفل شود
      if (!_inline) {
        // کلاس روی html هم لازم است؛ در بیشتر قالب‌ها اسکرول صفحه روی html است و
        // overflow:hidden فقط روی body جلوی اسکرول پشت مودال را نمی‌گیرد
        $("html, body").addClass("nias-login-fastsell-modal-open");
        // قالب‌هایی که اسکرول نرم دارند اصلاً از overflow صفحه پیروی نمی‌کنند
        niasPauseSmoothScroll(true);
      }
      applyElementorSettings();
    },

    close: function () {
      if (_inline) return;
      $("html, body").removeClass("nias-login-fastsell-modal-open");
      niasPauseSmoothScroll(false);
      this.currentStep = 1;
    },

    goToStep: function (step) {
      this.currentStep = step;
      $(".nias-login-fastsell-step-content").hide();
      $("#nias-login-fastsell-step-" + step).show();
      $(".nias-login-fastsell-steps .nias-login-fastsell-step").removeClass(
        "active",
      );
      $(
        '.nias-login-fastsell-steps .nias-login-fastsell-step[data-step="' +
          step +
          '"]',
      ).addClass("active");

      if (
        step === 2 &&
        $("#nias-login-fastsell-checkout-container").children().length === 0
      ) {
        this.loadCheckoutForm();
      }
    },

    loadCheckoutForm: function () {
      this.showLoading();

      _ajax({
        url: niasLoginFastsellData.ajax_url,
        type: "POST",
        dataType: "json",
        data: {
          action: "nias_login_fastsell_get_checkout_form",
          nonce: niasLoginFastsellData.nonce,
        },
        success: function (response) {
          _log("[NIAS fastsell] get_checkout_form response:", response);
          if (response && response.success) {
            // احراز شماره موبایل لازم است — به جای فرم تسویه، فرم OTP نمایش داده می‌شود
            // و دکمه پرداخت تا تأیید کد پنهان می‌ماند
            if (response.data && response.data.requires_auth) {
              $("#nias-login-fastsell-checkout-container").html(
                response.data.auth_html,
              );
              $(".nias-login-fastsell-btn-submit").hide();
              resetFastsellAuth();
              $("#nias-fastsell-auth-identifier").trigger("focus");
              return;
            }
            $(".nias-login-fastsell-btn-submit").show();

            var $container = $("#nias-login-fastsell-checkout-container");
            $container.html(response.data.form_html);

            // خلاصه سفارش بالای دکمه‌های پرداخت/بازگشت
            if (response.data.summary_html) {
              $("#nias-fastsell-checkout-order-summary").html(
                response.data.summary_html,
              );
            }

            togglePaymentSectionVisibility();

            // منبع سفارش (گوگل / ارجاع / مستقیم / کمپین) روی فرم تازه‌آمده
            applyOrderAttribution($container);

            // اجرای اسکریپت‌های inline موجود در HTML برگشتی
            // (WooCommerce خودش selectWoo استان/کشور را اینجا init می‌کند)
            $container.find("script").each(function () {
              try {
                eval($(this).html());
              } catch (e) {}
            });

            // اگر پلاگین WooCommerce فارسی نصب است، فیلد شهر را به select2 تبدیل کن
            initCitySelect($container);

            // هنگام تغییر استان، لیست شهرها به‌روز شود
            $container.on(
              "change",
              'select[name="billing_state"], input[name="billing_state"]',
              function () {
                setTimeout(function () {
                  initCitySelect($container);
                }, 200);
              },
            );

            initShippingAddressSync($container);

            // نمایش/مخفی کردن فیلدهای حمل و نقل
            $('input[name="ship_to_different_address"]').on(
              "change",
              function () {
                if ($(this).is(":checked")) {
                  $(".nias-login-fastsell-shipping-fields").slideDown();
                } else {
                  $(".nias-login-fastsell-shipping-fields").slideUp();
                }
                updateSubmitState();
              },
            );

            // نمایش توضیحات درگاه پرداخت
            $('input[name="payment_method"]').on("change", function () {
              $(".nias-login-fastsell-payment-box").slideUp();
              $(this)
                .closest(".nias-login-fastsell-payment-method-option")
                .find(".nias-login-fastsell-payment-box")
                .slideDown();
              updateSubmitState();
            });

            $('input[name="payment_method"]:checked')
              .closest(".nias-login-fastsell-payment-method-option")
              .find(".nias-login-fastsell-payment-box")
              .show();

            $container.on(
              "input change",
              "input, select, textarea",
              function () {
                // پاک کردن خطا وقتی کاربر فیلد را پر کرد
                const $f = $(this);
                if ($f.val()) {
                  markFieldError($f, false);
                }
                updateSubmitState();
              },
            );

            updateSubmitState();
          } else {
            _warn("[NIAS fastsell] get_checkout_form: success=false", response);
            // سبد خالی است — فرم تسویه‌حساب رندر نمی‌شود
            if (response && response.data && response.data.cart_empty) {
              handleEmptyCartResponse(response.data);
            }
          }
        },
        error: function (jqXHR, status, err) {
          _err("[NIAS fastsell] get_checkout_form AJAX error:", status, err, jqXHR.responseText);
        },
        complete: function () {
          modal.hideLoading();
        },
      });
    },

    /* =====================================================================
       خطای انتقالی — وقتی اصلاً پاسخی از سرور نگرفتیم
       ---------------------------------------------------------------------
       مودال قبل از رسیدن پاسخ باز می‌شود تا کاربر منتظر بماند. اگر درخواست با
       ۴۰۴ / ۴۰۳ / ۵xx برگردد، یا پاسخ اصلاً JSON نباشد (صفحه‌ی بلاک فایروال یا
       فیلترشکن)، هیچ‌وقت محتوایی برای ریختن داخل تب‌ها نمی‌آید و کاربر یک مودالِ
       خالی با تب‌های بی‌محتوا می‌بیند. در این حالت به‌جای تب‌ها یک پیام
       نمایش می‌دهیم.

       پنهان‌سازی با کلاس روی ریشه انجام می‌شود نه با hide/show، چون بعد از هر
       AJAX بخش‌هایی از مودال دوباره رندر می‌شوند و استایل درون‌خطی جا می‌ماند.
       ===================================================================== */
    fatalRoot: function () {
      return _modalEl ? $(_modalEl) : $("#nias-login-fastsell-modal");
    },

    showFatalError: function (message) {
      var $root = this.fatalRoot();
      if (!$root.length) {
        showNiasMessage(message, true);
        return;
      }

      var $box = $root.find(".nias-fastsell-fatal").first();
      if (!$box.length) {
        $box = $(
          '<div class="nias-fastsell-fatal">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
            '<circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 16.5v.01" />' +
            "</svg>" +
            '<p class="nias-fastsell-fatal-text"></p>' +
            '<button type="button" class="nias-fastsell-fatal-retry">بارگذاری دوباره صفحه</button>' +
            "</div>",
        );

        // اولین ظرفی که در هر سه قالب (پیش‌فرض، درون‌صفحه‌ای، المنتور) وجود دارد
        var $host = $root.find(".nias-login-fastsell-body").first();
        if (!$host.length) $host = $root.find(".nias-fastsell-scroll-area").first();
        if (!$host.length) $host = $root.find(".nias-login-fastsell-modal-card").first();
        if (!$host.length) $host = $root.find(".nias-login-fastsell-modal-content").first();
        if (!$host.length) $host = $root;

        $host.append($box);
      }

      $box.find(".nias-fastsell-fatal-text").text(message);
      $root.addClass("nias-fastsell-has-fatal");
    },

    clearFatalError: function () {
      this.fatalRoot().removeClass("nias-fastsell-has-fatal");
    },

    showLoading: function () {
      this.loadingCount++;
      $(".nias-login-fastsell-loading").show();
    },

    hideLoading: function () {
      this.loadingCount = Math.max(0, this.loadingCount - 1);
      if (this.loadingCount === 0) {
        $(".nias-login-fastsell-loading").hide();
      }
    },

    // ===== لودر خطی مرحله پرداخت =====
    // عملیات پرداخت یک درخواست واحد است؛ پیشروی نوار شبیه‌سازی می‌شود و
    // تا رسیدن پاسخ سرور هرگز به ۱۰۰٪ نمی‌رسد
    progressTimer: null,
    progressValue: 0,
    progressStages: [],

    showCheckoutProgress: function (stages) {
      var self = this;
      this.loadingCount++;
      this.progressStages = stages;
      this.progressValue = 0;

      var $loading = $(".nias-login-fastsell-loading");
      if (!$loading.find(".nias-fastsell-progress").length) {
        $loading.append(
          '<div class="nias-fastsell-progress">' +
            '<span class="nias-fastsell-progress-text"></span>' +
            '<div class="nias-fastsell-progress-track">' +
            '<div class="nias-fastsell-progress-fill"></div>' +
            "</div>" +
            "</div>",
        );
      }

      $loading.addClass("nias-fastsell-loading--progress").show();
      this.updateCheckoutProgress();

      clearInterval(this.progressTimer);
      this.progressTimer = setInterval(function () {
        self.progressValue += (92 - self.progressValue) * 0.035;
        self.updateCheckoutProgress();
      }, 150);
    },

    updateCheckoutProgress: function () {
      var $loading = $(".nias-login-fastsell-loading");
      var text = "";
      for (var i = 0; i < this.progressStages.length; i++) {
        if (this.progressValue < this.progressStages[i].until) {
          text = this.progressStages[i].text;
          break;
        }
      }
      $loading.find(".nias-fastsell-progress-text").text(text);
      $loading
        .find(".nias-fastsell-progress-fill")
        .css("width", Math.min(this.progressValue, 100) + "%");
    },

    finishCheckoutProgress: function (text, callback) {
      clearInterval(this.progressTimer);
      this.progressTimer = null;
      var $loading = $(".nias-login-fastsell-loading");
      $loading.find(".nias-fastsell-progress-text").text(text);
      $loading.find(".nias-fastsell-progress-fill").css("width", "100%");
      // مکث کوتاه تا کاربر پر شدن نوار را ببیند
      setTimeout(callback, 450);
    },

    hideCheckoutProgress: function () {
      clearInterval(this.progressTimer);
      this.progressTimer = null;
      $(".nias-login-fastsell-loading").removeClass(
        "nias-fastsell-loading--progress",
      );
      this.hideLoading();
    },
  };

  /* =======================================================================
     سازگاری با کتابخانه‌های اسکرول نرم (Lenis، Locomotive، ScrollSmoother)
     -----------------------------------------------------------------------
     Lenis روی window یک لیسنر wheel با passive:false می‌گذارد، جلوی اسکرول
     بومی را می‌گیرد و خودش صفحه را با requestAnimationFrame حرکت می‌دهد.

     چون آن لیسنر روی window است، رویداد wheel داخل مودال اول به هندلر ما
     می‌رسد و بعد بابل می‌شود تا window؛ preventDefault ما جلوی Lenis را
     نمی‌گیرد چون Lenis اصلاً defaultPrevented را نگاه نمی‌کند. نتیجه: هر
     چرخش موس داخل مودال، صفحه‌ی پشت را حرکت می‌دهد — مستقل از اینکه ظرف
     اسکرول مودال سالم باشد یا نه، و مستقل از اینکه کدام تب باز است.

     راه رسمی خود Lenis صفت data-lenis-prevent است: اگر روی هر عنصری در
     مسیر رویداد (composedPath) باشد، Lenis آن رویداد را کامل نادیده
     می‌گیرد و اسکرول بومی انجام می‌شود. این صفت در قالب‌ها هم روی مودال
     گذاشته شده؛ اینجا برای صفحه‌های کش‌شده و قالب المنتور تکرار می‌شود.
     ======================================================================= */
  var NIAS_LENIS_ATTR = "data-lenis-prevent";

  // بلوک درون‌صفحه‌ای عمداً مستثنا است: آن‌جا اسکرول باید مال خود صفحه بماند
  if (_modalEl && !_inline) {
    _modalEl.setAttribute(NIAS_LENIS_ATTR, "");
  }

  /**
   * select2 لیست بازشونده را بیرون از مودال و مستقیم زیر <body> می‌سازد، پس
   * صفتی که روی مودال گذاشتیم آن را پوشش نمی‌دهد و باید جداگانه علامت بخورد.
   * بدون این، اسکرول بین آیتم‌های استان/شهر هم به Lenis می‌رسد.
   */
  function niasMarkSelect2Dropdown() {
    $(".select2-dropdown").attr(NIAS_LENIS_ATTR, "");
  }

  /** نمونه‌ی فعال اسکرول نرم، اگر قالب آن را سراسری کرده باشد */
  function niasSmoothScroller() {
    var candidates = [
      window.lenis,
      window.Lenis && window.Lenis.instance,
      window.locoScroll,
      window.locomotiveScroll,
      window.smoother,
    ];
    for (var i = 0; i < candidates.length; i++) {
      var c = candidates[i];
      if (c && typeof c.stop === "function" && typeof c.start === "function") {
        return c;
      }
    }
    return null;
  }

  /**
   * توقف/ادامه‌ی اسکرول نرم هم‌زمان با باز و بسته شدن مودال.
   * صفت data-lenis-prevent به‌تنهایی کافی است، ولی این کار دو چیز اضافه را هم
   * حل می‌کند: انیمیشن نیمه‌تمامی که هنگام باز شدن مودال در جریان بوده، و
   * کتابخانه‌هایی که رویداد را جای دیگری می‌گیرند. اگر نمونه پیدا نشود
   * بی‌سروصدا رد می‌شویم — این مسیر هیچ‌وقت نباید باز شدن مودال را بشکند.
   */
  function niasPauseSmoothScroll(pause) {
    var scroller = niasSmoothScroller();
    if (!scroller) return;
    try {
      if (pause) scroller.stop();
      else scroller.start();
    } catch (e) {
      _warn("[NIAS fastsell] smooth-scroll toggle failed:", e);
    }
  }

  /* =======================================================================
     رفع قفل شدن اسکرول مودال توسط selectWoo
     -----------------------------------------------------------------------
     فیلدهای استان/شهر افزونه‌های «ووکامرس فارسی» و «حمل و نقل ووکامرس» با
     selectWoo (همان select2 ووکامرس) ساخته می‌شوند. select2 هنگام باز شدن هر
     dropdown، در AttachBody._attachPositioningHandler روی همه‌ی والدهای
     اسکرول‌شونده این هندلر را می‌گذارد:

         $watchers.on('scroll.select2.<id>', function () {
             $(this).scrollTop(position.y);   // اسکرول را به عقب برمی‌گرداند
         });

     یعنی تا وقتی dropdown باز است بدنه‌ی مودال عملاً قفل است و چرخش موس به
     صفحه‌ی پشت سرایت می‌کند. بدتر اینکه _detachPositioningHandler هنگام بستن،
     دوباره لیست والدها را از نو حساب می‌کند؛ اگر بین باز و بسته شدن، فرم را با
     AJAX دوباره رندر کرده باشیم (که در تسویه حساب مدام اتفاق می‌افتد) آن والدها
     دیگر همان‌ها نیستند و off اجرا نمی‌شود — قفل تا آخر عمر صفحه باقی می‌ماند.
     این همان حالتی است که «فرقی نمی‌کند کدام تب، همیشه کل صفحه اسکرول می‌شود».

     dropdownParent هم چاره‌ساز نیست: AttachBody همیشه در زنجیره‌ی دکوریتورهای
     select2 هست و فقط محل append شدن dropdown را عوض می‌کند، نه این قفل را.
     ======================================================================= */
  var NIAS_SCROLL_AREAS =
    "#nias-login-fastsell-modal .nias-login-fastsell-body," +
    "#nias-login-fastsell-modal .nias-fastsell-scroll-area," +
    "#nias-login-fastsell-modal .nias-login-fastsell-modal-card," +
    "#nias-login-fastsell-modal .nias-login-fastsell-modal-content";

  /** قفل‌های اسکرولِ جامانده از select2 را پاک کن (namespace خودمان دست نمی‌خورد) */
  function releaseSelect2ScrollLock($scope) {
    if ($scope && $scope.length) {
      $scope.parents().off("scroll.select2");
    }
    $(NIAS_SCROLL_AREAS).off("scroll.select2");
  }

  // select2 قفل را در هندلر داخلی open می‌گذارد و بعد select2:open را relay
  // می‌کند، پس اینجا قفل حتماً وجود دارد و می‌شود برداشتش
  $(document).on(
    "select2:open",
    "#nias-login-fastsell-modal select",
    function () {
      releaseSelect2ScrollLock($(this));
      niasMarkSelect2Dropdown();
    },
  );

  // بستن هم باید پاک‌سازی شود: _detachPositioningHandler لیست والدها را از نو
  // حساب می‌کند و اگر فرم بین باز و بسته شدن با AJAX دوباره رندر شده باشد،
  // off روی عناصر دیگری اجرا می‌شود و قفل روی عناصر واقعی جا می‌ماند.
  // با setTimeout بعد از خود select2 اجرا می‌شویم.
  $(document).on(
    "select2:close",
    "#nias-login-fastsell-modal select",
    function () {
      var $el = $(this);
      setTimeout(function () {
        releaseSelect2ScrollLock($el);
      }, 0);
    },
  );

  // dropdown به body چسبیده و همراه محتوا حرکت نمی‌کند، پس با اسکرول ظرف باید
  // بسته شود. scroll بابل نمی‌شود، بنابراین در فاز capture گوشش می‌دهیم.
  document.addEventListener(
    "scroll",
    function (e) {
      if (!document.querySelector(".select2-container--open")) return;

      var target = e.target;
      if (!target || !target.closest || !target.closest("#nias-login-fastsell-modal")) {
        return;
      }

      $("#nias-login-fastsell-modal select.select2-hidden-accessible").each(
        function () {
          var data = $(this).data("select2");
          if (data && typeof data.isOpen === "function" && data.isOpen()) {
            try {
              $(this).select2("close");
            } catch (err) {}
          }
        },
      );
    },
    true,
  );

  /* =======================================================================
     مهار اسکرول مودال
     -----------------------------------------------------------------------
     تکیه بر CSS برای نگه داشتن اسکرول داخل مودال کافی نیست، چون سه چیز
     مستقل می‌تواند آن را بشکند:

       ۱) قفل جامانده‌ی select2 روی والدهای اسکرول‌شونده (بالا توضیح داده شد)؛
       ۲) قالب‌هایی که عنصر اسکرول‌شونده‌شان html/body نیست (یک wrapper با
          overflow:auto)؛ آن‌جا overflow:hidden ما هیچ اثری ندارد و صفحه
          پشت مودال آزادانه اسکرول می‌شود؛
       ۳) رسیدن به انتهای ناحیه‌ی اسکرول که چرخش را به بیرون سرایت می‌دهد
          (overscroll-behavior در سافاری قدیمی و بعضی موتورها اعمال نمی‌شود).

     پس به‌جای امید بستن به CSS، خودمان چرخ موس را می‌گیریم: نزدیک‌ترین ظرفِ
     واقعاً اسکرول‌شونده‌ی داخل مودال را پیدا می‌کنیم، دستی جابه‌جایش می‌کنیم و
     در هر حالت جلوی رفتار پیش‌فرض را می‌گیریم تا هیچ‌چیز بیرون مودال تکان نخورد.

     نکته: لیسنر مستقیماً روی خود مودال بسته می‌شود نه روی document — کروم
     لیسنرهای wheel روی window/document/body را passive در نظر می‌گیرد و
     preventDefault در آن‌ها بی‌اثر است.
     ======================================================================= */

  /** نزدیک‌ترین والدِ اسکرول‌شونده که در جهت خواسته‌شده هنوز جا دارد */
  function niasScrollableUnder(node, root, deltaY) {
    while (node && node.nodeType === 1) {
      var style = window.getComputedStyle(node);
      var oy = style.overflowY;

      if (
        (oy === "auto" || oy === "scroll") &&
        node.scrollHeight > node.clientHeight + 1
      ) {
        var atTop = node.scrollTop <= 0;
        var atBottom =
          node.scrollTop + node.clientHeight >= node.scrollHeight - 1;
        if (!((deltaY < 0 && atTop) || (deltaY > 0 && atBottom))) {
          return node;
        }
      }

      if (node === root) break;
      node = node.parentNode;
    }
    return null;
  }

  function niasNormalizeDelta(e, fallbackHeight) {
    var delta = e.deltaY;
    if (e.deltaMode === 1) delta *= 16; // واحد: خط
    else if (e.deltaMode === 2) delta *= fallbackHeight; // واحد: صفحه
    return delta;
  }

  // بلوک درون‌صفحه‌ای ([nias_fastsell]) باید با خود صفحه اسکرول شود
  if (_modalEl && !_inline) {
    _modalEl.addEventListener(
      "wheel",
      function (e) {
        var delta = niasNormalizeDelta(e, _modalEl.clientHeight);
        if (!delta) return;

        var target = niasScrollableUnder(e.target, _modalEl, delta);

        // چه ظرفی پیدا شود چه نشود، صفحه‌ی پشت مودال نباید تکان بخورد
        e.preventDefault();

        if (target) {
          target.scrollTop += delta;
        }
      },
      { passive: false },
    );

    // همان منطق برای لمس؛ جهت از اختلاف نقطه‌ی شروع محاسبه می‌شود
    var _touchStartY = 0;
    _modalEl.addEventListener(
      "touchstart",
      function (e) {
        if (e.touches && e.touches.length === 1) {
          _touchStartY = e.touches[0].clientY;
        }
      },
      { passive: true },
    );

    _modalEl.addEventListener(
      "touchmove",
      function (e) {
        if (!e.touches || e.touches.length !== 1) return;

        // انگشت به پایین = محتوا به بالا = delta منفی
        var delta = _touchStartY - e.touches[0].clientY;
        if (!delta) return;

        // اینجا اسکرول را دستی انجام نمی‌دهیم تا اینرسی بومی مرورگر حفظ شود؛
        // فقط وقتی جایی برای اسکرول نیست جلوی سرایت به صفحه را می‌گیریم
        if (!niasScrollableUnder(e.target, _modalEl, delta)) {
          e.preventDefault();
        }
      },
      { passive: false },
    );
  }

  // نشانه‌ای برای CSS تا سلکت‌های بلوک درون‌صفحه‌ای هم همان اصلاحات dropdown
  // را بگیرند (آن‌جا کلاس modal-open روی html نمی‌نشیند)
  if (_inline) {
    $("html").addClass("nias-fastsell-inline-active");
  }

  /* =======================================================================
     همگام‌سازی آدرس مقصد با محاسبه حمل و نقل
     -----------------------------------------------------------------------
     مودال خرید سریع فرم چک‌اوت ووکامرس را ندارد، پس رویداد update_order_review
     اجرا نمی‌شود و افزونه‌های حمل و نقل هیچ‌وقت مقصد واقعی را نمی‌بینند.
     اینجا با هر تغییر استان/شهر/محله/کدپستی آدرس را به سرور می‌فرستیم و
     فهرست روش‌ها و جمع سفارش را دوباره می‌گیریم.

     «افزونه حمل و نقل ووکامرس» (PWS) هم دو مشکل جانبی دارد که اینجا حل می‌شود:
       ۱) اسکریپتش فقط در is_checkout لود می‌شود، پس در صفحه محصول اصلاً نیست
       ۲) هندلرهایش را مستقیم روی DOM اولیه می‌بندد، نه delegated — و فرم ما
          بعداً با AJAX تزریق می‌شود
     برای همین لیست شهر و محله را خودمان از همان endpointهای عمومی آن
     (mahdiy_load_cities / mahdiy_load_districts) می‌گیریم.
     ======================================================================= */
  var addressSyncTimer = null;
  var addressSyncSeq = 0;

  function pwsActive() {
    return !!(niasLoginFastsellData && niasLoginFastsellData.pws_active);
  }

  function addressFieldPrefix() {
    return $('input[name="ship_to_different_address"]').is(":checked")
      ? "shipping"
      : "billing";
  }

  /**
   * افزونه حمل و نقل برای گزینه‌ی «انتخاب کنید» مقدار "0" می‌گذارد نه رشته خالی،
   * پس "0" هم یعنی انتخاب‌نشده
   */
  function normalizeId(value) {
    var v = $.trim(value || "");
    return v === "0" ? "" : v;
  }

  function fieldVal(prefix, name) {
    var $f = $("#nias-login-fastsell-checkout-container").find(
      "[name='" + prefix + "_" + name + "']",
    );
    return $f.length ? normalizeId($f.val()) : "";
  }

  function initSelectWoo($el) {
    if (!$el || !$el.length) return;
    try {
      if ($el.hasClass("select2-hidden-accessible")) {
        if (typeof $.fn.selectWoo !== "undefined") {
          $el.selectWoo("destroy");
        } else if (typeof $.fn.select2 !== "undefined") {
          $el.select2("destroy");
        }
      }
      if (typeof $.fn.selectWoo !== "undefined") {
        $el.selectWoo({ width: "100%" });
      } else if (typeof $.fn.select2 !== "undefined") {
        $el.select2({ width: "100%" });
      }
    } catch (e) {}
  }

  /** لیست شهرهای استان انتخاب‌شده را از افزونه حمل و نقل بگیر */
  function pwsLoadCities(type, stateId, done) {
    var $city = $("#" + type + "_city");
    if (!$city.length) {
      done();
      return;
    }

    $city.html('<option value="">در حال بارگذاری شهرها...</option>');
    initSelectWoo($city);

    $.post(
      niasLoginFastsellData.ajax_url,
      { action: "mahdiy_load_cities", state_id: stateId, type: type },
      function (html) {
        $city.html(html);
        initSelectWoo($city);

        // با تغییر استان، محله‌ی قبلی دیگر معتبر نیست
        var $district = $("#" + type + "_district");
        if ($district.length) {
          $district.html("");
          initSelectWoo($district);
          $("#" + type + "_district_field").hide();
        }
        done();
      },
    ).fail(function () {
      $city.html('<option value="">خطا در دریافت لیست شهرها</option>');
      initSelectWoo($city);
      done();
    });
  }

  /** لیست محله‌های شهر انتخاب‌شده */
  function pwsLoadDistricts(type, cityId, done) {
    var $district = $("#" + type + "_district");
    if (!$district.length) {
      done();
      return;
    }

    $district.html('<option value="">در حال بارگذاری محله‌ها...</option>');
    initSelectWoo($district);

    $.post(
      niasLoginFastsellData.ajax_url,
      { action: "mahdiy_load_districts", city_id: cityId, type: type },
      function (html) {
        $district.html(html);
        initSelectWoo($district);
        $("#" + type + "_district_field").toggle($.trim(html) !== "");
        done();
      },
    ).fail(function () {
      $district.html("");
      initSelectWoo($district);
      $("#" + type + "_district_field").hide();
      done();
    });
  }

  /**
   * ارسال آدرس به سرور و بازسازی روش‌های ارسال و خلاصه سفارش.
   * پاسخ‌های قدیمی نادیده گرفته می‌شوند تا تغییرات پشت‌سرهم قاطی نشوند.
   */
  function pushAddressAndRefreshShipping() {
    var prefix = addressFieldPrefix();
    var seq = ++addressSyncSeq;

    var payload = {
      action: "nias_login_fastsell_set_address",
      nonce: niasLoginFastsellData.nonce,
      country: fieldVal(prefix, "country") || fieldVal("billing", "country"),
      state: fieldVal(prefix, "state"),
      city: fieldVal(prefix, "city"),
      district: fieldVal(prefix, "district"),
      postcode: fieldVal(prefix, "postcode"),
      ship_to_different_address: prefix === "shipping" ? 1 : 0,
    };

    $(".nias-login-fastsell-shipping-options").addClass(
      "nias-fastsell-shipping-loading",
    );

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: payload,
      success: function (response) {
        if (seq !== addressSyncSeq) return; // پاسخ منسوخ

        if (!response || !response.success) {
          _warn("[NIAS fastsell] set_address failed:", response);
          return;
        }

        if (typeof response.data.shipping_html !== "undefined") {
          $(".nias-login-fastsell-shipping-options").html(
            response.data.shipping_html,
          );
        }
        if (response.data.summary_html) {
          $("#nias-fastsell-checkout-order-summary").html(
            response.data.summary_html,
          );
        }

        updateSubmitState();
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] set_address AJAX error:", status, err);
      },
      complete: function () {
        if (seq === addressSyncSeq) {
          $(".nias-login-fastsell-shipping-options").removeClass(
            "nias-fastsell-shipping-loading",
          );
        }
      },
    });
  }

  function scheduleAddressSync(delay) {
    clearTimeout(addressSyncTimer);
    addressSyncTimer = setTimeout(
      pushAddressAndRefreshShipping,
      typeof delay === "number" ? delay : 250,
    );
  }

  function initShippingAddressSync($container) {
    // فرم چک‌اوت ممکن است چند بار لود شود، اما $container ثابت می‌ماند؛ بدون off
    // هندلرها روی هم انباشته می‌شوند و هر تغییر چند درخواست همزمان می‌سازد
    $container.off(".niasAddr");

    // رندر مجدد فرم دقیقاً همان لحظه‌ای است که detach خودکار select2 شکست
    // می‌خورد و قفل اسکرول جا می‌ماند
    releaseSelect2ScrollLock();

    // change روی select2 هم شلیک می‌شود، پس نیازی به گوش دادن به select2:select نیست
    $container.on(
      "change.niasAddr",
      'select[name$="_state"], input[name$="_state"]',
      function () {
        var name = $(this).attr("name") || "";
        var type = name.indexOf("shipping") === 0 ? "shipping" : "billing";

        if (pwsActive()) {
          var stateId = normalizeId($(this).val());
          if (!stateId) {
            scheduleAddressSync();
            return;
          }
          pwsLoadCities(type, stateId, function () {
            // ممکن است افزونه خودش شهری را از پیش انتخاب کرده باشد؛ در آن حالت
            // رویداد change شلیک نمی‌شود و باید محله را هم همین‌جا بگیریم
            var cityId = normalizeId($("#" + type + "_city").val());
            if (cityId) {
              pwsLoadDistricts(type, cityId, scheduleAddressSync);
            } else {
              scheduleAddressSync();
            }
          });
          return;
        }

        scheduleAddressSync();
      },
    );

    $container.on(
      "change.niasAddr",
      'select[name$="_city"], input[name$="_city"]',
      function () {
        var name = $(this).attr("name") || "";
        var type = name.indexOf("shipping") === 0 ? "shipping" : "billing";

        if (pwsActive()) {
          var cityId = normalizeId($(this).val());
          if (cityId) {
            pwsLoadDistricts(type, cityId, scheduleAddressSync);
            return;
          }
        }

        scheduleAddressSync();
      },
    );

    $container.on(
      "change.niasAddr",
      'select[name$="_district"], input[name$="_district"], input[name$="_postcode"], select[name$="_country"]',
      function () {
        scheduleAddressSync();
      },
    );

    // تعویض بین آدرس صورتحساب و آدرس ارسال، مقصد را عوض می‌کند
    $container.on(
      "change.niasAddr",
      'input[name="ship_to_different_address"]',
      function () {
        scheduleAddressSync();
      },
    );

    // اگر آدرس از قبل کامل است (کاربر لاگین‌شده)، همان ابتدا نرخ درست را بگیر
    var prefix = addressFieldPrefix();
    if (fieldVal(prefix, "state") && fieldVal(prefix, "city")) {
      scheduleAddressSync(0);
    }
  }

  /**
   * تبدیل فیلد billing_city به select2 با استفاده از Persian_Woo_iranCities
   * فقط فیلد شهر را تغییر می‌دهد — کشور و استان دست‌نخورده می‌مانند
   */
  function initCitySelect($container) {
    // «افزونه حمل و نقل ووکامرس» خودش فیلد شهر را به select ترم‌های state_city
    // تبدیل کرده و مقدارش شناسه ترم است. بازسازی آن با نام شهرها (خروجی
    // Persian_Woo_iranCities) هم لیست را خراب می‌کند و هم باعث می‌شود روش‌های
    // ارسال مقصد را نشناسند و هزینه‌ای محاسبه نشود.
    if (niasLoginFastsellData && niasLoginFastsellData.pws_active) return;

    if (typeof window.Persian_Woo_iranCities !== "function") return;

    var $stateField = $container.find(
      'select[name="billing_state"], input[name="billing_state"]',
    );
    var stateVal = $stateField.val();
    var $cityField = $container.find("#billing_city");
    if (!$cityField.length) return;

    // دریافت لیست شهرها برای استان انتخاب‌شده
    var rawCities = stateVal ? Persian_Woo_iranCities(stateVal) : [];
    var cities = [];
    if (rawCities && rawCities.length) {
      for (var i = 0; i < rawCities.length; i++) {
        if (rawCities[i]) cities.push(rawCities[i]);
      }
    }

    // ذخیره مقدار فعلی
    var currentVal = $cityField.val();

    // اگر select2/selectWoo فعال است، destroy کن
    if ($cityField.hasClass("select2-hidden-accessible")) {
      try {
        if (typeof $.fn.selectWoo !== "undefined") {
          $cityField.selectWoo("destroy");
        } else if (typeof $.fn.select2 !== "undefined") {
          $cityField.select2("destroy");
        }
      } catch (e) {}
    }

    // اگر input است، به select تبدیل کن؛ اگر select است، خالی کن
    var $citySelect;
    if ($cityField.is("input")) {
      var cityRequired = $cityField.prop("required") ? " required" : "";
      $citySelect = $(
        '<select name="billing_city" id="billing_city" class="' +
          ($cityField.attr("class") || "") +
          '"' + cityRequired + '></select>',
      );
      $cityField.replaceWith($citySelect);
    } else {
      $citySelect = $cityField;
      $citySelect.empty();
    }

    // گزینه پیش‌فرض
    $citySelect.append('<option value="">— انتخاب شهر —</option>');
    $.each(cities, function (i, cityArr) {
      // هر آیتم آرایه‌ای مثل ["نام شهر", "id"] است
      var cityName = Array.isArray(cityArr) ? cityArr[0] : cityArr;
      var selected = cityName === currentVal ? " selected" : "";
      $citySelect.append(
        '<option value="' +
          cityName +
          '"' +
          selected +
          ">" +
          cityName +
          "</option>",
      );
    });

    // init select2/selectWoo — فقط روی فیلد شهر
    if (typeof $.fn.selectWoo !== "undefined") {
      $citySelect.selectWoo({ width: "100%" });
    } else if (typeof $.fn.select2 !== "undefined") {
      $citySelect.select2({ width: "100%" });
    }
  }

  // هایلایت کردن فیلد اشتباه (برای select2 روی wrapper مرئی)
  function markFieldError($field, hasError) {
    if (hasError) {
      if (
        $field.hasClass("select2-hidden-accessible") ||
        $field.next(".select2-container").length
      ) {
        $field
          .next(".select2-container")
          .find(".select2-selection")
          .addClass("nias-fastsell-field-error");
      } else {
        $field.addClass("nias-fastsell-field-error");
      }
    } else {
      $field.removeClass("nias-fastsell-field-error");
      $field
        .next(".select2-container")
        .find(".select2-selection")
        .removeClass("nias-fastsell-field-error");
    }
  }

  // جمع‌آوری فیلدهای required مرئی
  function getVisibleRequiredFields() {
    const fields = [];
    $("#nias-login-fastsell-checkout-container")
      .find("input[required], select[required], textarea[required]")
      .each(function () {
        const $f = $(this);
        // فیلدهای type=hidden واقعی را نادیده بگیر (نه select2)
        if ($f.attr("type") === "hidden") return;
        // فیلدهایی که در یک form-row مخفی هستند را نادیده بگیر
        const $row = $f.closest(".form-row, .woocommerce-form-row");
        if ($row.length && $row.css("display") === "none") return;
        fields.push($f);
      });
    return fields;
  }

  function validateRequiredFields() {
    let valid = true;

    getVisibleRequiredFields().forEach(function ($f) {
      const v = $f.val();
      if (!v || v === "") {
        valid = false;
      }
    });

    // بررسی روش پرداخت فقط وقتی سفارش رایگان نیست
    const cartTotal = parseFloat(
      $("#nias-fastsell-cart-total").val() || "1",
    );
    const isFreeOrder = cartTotal <= 0;
    if (!isFreeOrder) {
      const pmChecked =
        $("#nias-login-fastsell-checkout-container").find(
          'input[name="payment_method"]:checked',
        ).length > 0;
      if (!pmChecked) {
        valid = false;
      }
    }

    const shippingSection =
      $(".nias-login-fastsell-shipping-methods").length > 0;
    if (shippingSection) {
      const smChecked =
        $("#nias-login-fastsell-checkout-container").find(
          'input[name="shipping_method"]:checked',
        ).length > 0;
      if (!smChecked) {
        valid = false;
      }
    }
    return valid;
  }

  // پاک کردن cache مرحله ۲ تا دفعه بعد با داده تازه لود شود
  function clearCheckoutCache() {
    $("#nias-login-fastsell-checkout-container").empty();
    $("#nias-fastsell-checkout-order-summary").empty();
  }

  // بررسی خالی بودن سبد — پنهان/نمایش تب‌ها
  function checkCartEmpty() {
    updateCartBadge();
    const isEmpty =
      $(".nias-login-fastsell-cart-container .nias-fastsell-empty-cart")
        .length > 0;
    if (isEmpty) {
      $(".nias-login-fastsell-steps").hide();
      $(".nias-login-fastsell-coupon").hide();
      $(".nias-login-fastsell-btn-next").hide();
      // برگشت به مرحله اول در صورتی که در مرحله دوم بودیم
      if (modal.currentStep === 2) {
        modal.currentStep = 1;
        $(".nias-login-fastsell-step-content").hide();
        $("#nias-login-fastsell-step-1").show();
      }
    } else {
      $(".nias-login-fastsell-steps").show();
      $(".nias-login-fastsell-coupon").show();
      $(".nias-login-fastsell-btn-next").show();
    }
  }

  function updateSubmitState() {
    const ok = validateRequiredFields();
    const $btn = $(".nias-login-fastsell-btn-submit");
    $btn.prop("disabled", !ok);
  }

  // کلیک روی استپ‌ها
  $(".nias-login-fastsell-steps .nias-login-fastsell-step").on(
    "click",
    function () {
      const step = parseInt($(this).data("step"));
      if (step <= modal.currentStep || step === modal.currentStep + 1) {
        modal.goToStep(step);
      }
    },
  );

  // ===== نمایش پیام =====
  var niasMessageTimeout = null;

  function showNiasMessage(msg, isError) {
    var duration = 4000;
    var $msg = $("#nias-message");

    if (!$msg.length) {
      $msg = $('<div id="nias-message"></div>');
      $("body").append($msg);
    }

    if (isError) {
      $msg.css({
        background: "rgb(255 132 0)",
        border: "1px solid rgb(219 74 0)",
        color: "#ffffff",
      });
    } else {
      $msg.css({
        background: "#00ac41",
        border: "1px solid rgb(0 144 76)",
        color: "#fff",
      });
    }

    clearTimeout(niasMessageTimeout);
    $msg.removeClass("nias-message--hiding nias-message--visible");
    $msg[0].style.setProperty("--msg-duration", duration / 1000 + "s");
    $msg.html(
      '<button class="nias-message__close" title="بستن">✕</button>' + msg,
    );

    $msg.find(".nias-message__close").on("click", function () {
      hideNiasMessage();
    });

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        $msg.addClass("nias-message--visible");
      });
    });

    niasMessageTimeout = setTimeout(function () {
      hideNiasMessage();
    }, duration);
  }

  function hideNiasMessage() {
    var $msg = $("#nias-message");
    if (!$msg.length) return;
    clearTimeout(niasMessageTimeout);
    $msg.removeClass("nias-message--visible").addClass("nias-message--hiding");
    setTimeout(function () {
      $msg.removeClass("nias-message--hiding");
    }, 400);
  }

  /**
   * فرم محصولِ متناظر با دکمه شورت‌کد [nias_fastsell_add_to_cart].
   * این دکمه می‌تواند هر جای صفحه باشد (حتی بیرون از فرم محصول)، پس به‌جای
   * closest، فرم همان شناسه محصول در کل صفحه جستجو می‌شود تا ویژگی‌های
   * انتخاب‌شده‌ی محصول متغیر از دست نرود.
   */
  function getShortcodeProductForm($btn) {
    var pid = parseInt($btn.attr("data-product-id"), 10) || 0;
    if (!pid) return $();

    var $form = $('form.variations_form[data-product_id="' + pid + '"]').first();
    if ($form.length) return $form;

    return $("form.cart")
      .filter(function () {
        var $f = $(this);
        var fid =
          parseInt(
            $f.find('[name="product_id"]').val() ||
              $f.find('[name="add-to-cart"]').val() ||
              0,
            10,
          ) || 0;
        return fid === pid;
      })
      .first();
  }

  /**
   * پر کردن فیلدهای «منبع سفارش» ووکامرس در فرمِ AJAXیِ تسویه‌حساب.
   *
   * اسکریپت ووکامرس (order-attribution.js) مقادیر را فقط یک بار هنگام لود صفحه
   * روی المان‌های <wc-order-attribution-inputs> موجود می‌نویسد. فرم تسویه‌ی ما
   * بعداً با AJAX می‌آید، پس المانِ تازه اینپوت‌های خالی می‌سازد و سفارش
   * «نامشخص» ثبت می‌شود. اینجا مقادیر جاری را خودمان روی آن می‌گذاریم.
   */
  function applyOrderAttribution($container) {
    try {
      var wcOA = window.wc_order_attribution;
      if (!wcOA || typeof wcOA.getAttributionData !== "function") return;

      var values = wcOA.getAttributionData();
      $container.find("wc-order-attribution-inputs").each(function () {
        this.values = values;
      });
      _log("[NIAS fastsell] order attribution applied:", values);
    } catch (e) {
      _warn("[NIAS fastsell] order attribution failed:", e);
    }
  }

  /**
   * پاسخ «سبد خالی» سرور — چه هنگام بارگذاری فرم تسویه و چه هنگام پرداخت.
   * سبد ممکن است بدون اطلاع این صفحه خالی شده باشد (تب دیگر، دکمه Back پس از
   * یک سفارش موفق، انقضای نشست)؛ کاربر به مرحله سبد خرید برگردانده می‌شود.
   */
  function handleEmptyCartResponse(data) {
    if (data && typeof data.cart_html !== "undefined") {
      $(".nias-login-fastsell-cart-container").html(data.cart_html);
    }
    clearCheckoutCache();
    checkCartEmpty();
    modal.goToStep(1);
    showNiasMessage(
      (data && data.message) || "سبد خرید شما خالی است",
      true,
    );
  }

  function getAddToCartPayload($btn) {
    const isShortcodeBtn = $btn.hasClass("nias-fastsell-atc-shortcode");
    const $form = isShortcodeBtn
      ? getShortcodeProductForm($btn)
      : $btn.closest("form.cart");
    const payload = {
      productId: 0,
      quantity: 1,
      variationId: 0,
      variation: {},
      hasVariationFields: false,
    };

    if ($form.length) {
      var foundVariation = $form.data("nias_found_variation") || null;

      payload.productId =
        $form.find('[name="product_id"]').val() ||
        $form.find('[name="add-to-cart"]').val() ||
        $btn.val() ||
        0;
      payload.quantity = $form.find('[name="quantity"]').val() || 1;
      payload.variationId =
        $form.find('[name="variation_id"]').val() ||
        (foundVariation && foundVariation.variation_id) ||
        0;

      $form.find('[name^="attribute_"]').each(function () {
        const attrName = $(this).attr("name");
        const attrVal = $(this).val();
        payload.hasVariationFields = true;
        if (attrName && attrVal) {
          payload.variation[attrName] = attrVal;
        }
      });

      if (
        Object.keys(payload.variation).length === 0 &&
        foundVariation &&
        foundVariation.attributes
      ) {
        payload.variation = foundVariation.attributes;
      }

      // تعدادِ صریحِ شورت‌کد بر تعداد فرم محصول اولویت دارد
      if (isShortcodeBtn) {
        var scQuantity = parseInt($btn.attr("data-quantity"), 10);
        if (scQuantity > 0) {
          payload.quantity = scQuantity;
        }
      }

      return payload;
    }

    const buttonValue = $btn.is('[name="add-to-cart"]') ? $btn.val() : 0;
    const dataProductId =
      $btn.data("product_id") ||
      $btn.data("product-id") ||
      $btn.attr("data-product_id") ||
      $btn.attr("data-product-id") ||
      0;
    // شناسه محصول از لینک add-to-cart (?add-to-cart=123) — المان ممکن است خودش
    // تگ <a> باشد، یا لینک در فرزند/والد آن قرار داشته باشد
    let hrefProductId = getProductIdFromHref($btn.attr("href"));
    if (!hrefProductId) {
      hrefProductId = getProductIdFromHref(
        $btn.find('a[href*="add-to-cart="]').attr("href") ||
          $btn.closest('a[href*="add-to-cart="]').attr("href"),
      );
    }
    const dataQuantity =
      $btn.data("quantity") ||
      $btn.attr("data-quantity") ||
      $btn.closest(".product, .product-card, li.product").find(".qty").val() ||
      1;

    payload.productId = buttonValue || dataProductId || hrefProductId || 0;
    payload.quantity = dataQuantity;

    return payload;
  }

  // استخراج شناسه محصول از href دکمه‌های افزودن به سبد (?add-to-cart=123)
  function getProductIdFromHref(href) {
    if (!href) return 0;
    var m = String(href).match(/[?&]add-to-cart=(\d+)/);
    return m ? parseInt(m[1], 10) : 0;
  }

  // ساخت سلکتور دکمه‌های افزودن به سبد:
  // دکمه‌های پیش‌فرض ووکامرس/المنتور + سلکتورهای سفارشی واردشده در تنظیمات.
  // المان سفارشی می‌تواند هر تگی باشد (a، button، div ...) و با کلاس یا آیدی هدف‌گذاری شود.
  // نرمال‌سازی لیست سلکتورهای واردشده توسط کاربر (کاما جدا).
  // نام تنها (بدون نشانه سلکتور) به‌عنوان کلاس در نظر گرفته می‌شود.
  function parseUserSelectorList(raw) {
    var out = [];
    String(raw || "")
      .split(",")
      .forEach(function (sel) {
        sel = sel.trim();
        if (!sel) return;
        if (/^[A-Za-z0-9_\-]+$/.test(sel)) {
          sel = "." + sel;
        }
        out.push(sel);
      });
    return out;
  }

  function getAddToCartSelector() {
    var selectors = [
      ".single_add_to_cart_button",
      '.add_to_cart_button[name="add-to-cart"]',
      // دکمه شورت‌کد [nias_fastsell_add_to_cart]
      ".nias-fastsell-atc-shortcode",
    ];
    var custom =
      niasLoginFastsellData && niasLoginFastsellData.custom_add_to_cart_class;
    if (custom) {
      selectors = selectors.concat(parseUserSelectorList(custom));
    }
    return selectors.join(", ");
  }

  // ===== افزودن به سبد خرید =====
  $("body")
    .on("found_variation", "form.variations_form", function (e, variation) {
      $(this).data("nias_found_variation", variation || null);
    })
    .on("reset_data hide_variation", "form.variations_form", function () {
      $(this).removeData("nias_found_variation");
    });

  $("body").on(
    "click",
    getAddToCartSelector(),
    function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    const $btn = $(this);
    const payload = getAddToCartPayload($btn);
    const productId = parseInt(payload.productId, 10) || 0;
    const quantity = parseInt(payload.quantity, 10) || 1;
    const variationId = parseInt(payload.variationId, 10) || 0;
    const variation = payload.variation;

    if (!productId) {
      showNiasMessage("شناسه محصول برای افزودن به سبد پیدا نشد", true);
      return;
    }

    // اگر محصول متغیر است و variation انتخاب نشده
    if (!variationId && payload.hasVariationFields) {
      var allSelected = true;
      $btn.closest("form.cart").find('[name^="attribute_"]').each(function () {
        if (!$(this).val()) {
          allSelected = false;
          return false;
        }
      });
      if (!allSelected) {
        showNiasMessage("لطفاً تمام ویژگی‌های محصول را انتخاب کنید", true);
        return;
      }
    }

    modal.clearFatalError();
    if (_openAfterAdd) {
      modal.showLoading();
      modal.open();
    }

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_add_to_cart",
        nonce: niasLoginFastsellData.nonce,
        product_id: productId,
        quantity: quantity,
        variation_id: variationId,
        variation: variation,
      },
      success: function (response) {
        _log("[NIAS fastsell] add_to_cart response:", response);
        _log("[NIAS fastsell] cart container found:", $(".nias-login-fastsell-cart-container").length);

        // admin-ajax وقتی اکشن ثبت نشده باشد 0 و وقتی nonce رد شود -1 برمی‌گرداند.
        // کد وضعیت ۲۰۰ است ولی پاسخ ساختار ما را ندارد، پس هیچ محتوایی برای
        // تب‌ها نمی‌آید — همان مودال خالی. مثل خطای انتقالی رفتار می‌کنیم.
        if (!response || typeof response !== "object") {
          _warn("[NIAS fastsell] add_to_cart: unexpected payload", response);
          if (_openAfterAdd) {
            modal.showFatalError(NIAS_FATAL_MESSAGE);
          } else {
            showNiasMessage(NIAS_FATAL_MESSAGE, true);
          }
          return;
        }

        if (response.success) {
          $(".nias-login-fastsell-cart-container").html(
            response.data.cart_html,
          );
          _log("[NIAS fastsell] cart_html inserted, length:", response.data.cart_html ? response.data.cart_html.length : 0);
          checkCartEmpty();
          clearCheckoutCache();

          var cartIsEmpty =
            $(".nias-login-fastsell-cart-container .nias-fastsell-empty-cart")
              .length > 0;
          if (_openAfterAdd) {
            if (_buyNow && !cartIsEmpty) {
              modal.goToStep(2);
            } else {
              modal.goToStep(1);
            }
          } else {
            // مودالی باز نمی‌شود؛ مینی‌کارت/بَج سبد قالب باید تعداد تازه را نشان دهد
            $(document.body).trigger("wc_fragment_refresh");
          }

          if (response.data.message) {
            showNiasMessage(response.data.message);
          }
        } else {
          _warn("[NIAS fastsell] add_to_cart: success=false", response);
          var msg = (response && response.data && response.data.message) ? response.data.message : "خطا در افزودن به سبد خرید";
          showNiasMessage(msg, true);
        }
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] add_to_cart AJAX error:", jqXHR.status, status, err, jqXHR.responseText);
        if (_openAfterAdd) {
          modal.showFatalError(NIAS_FATAL_MESSAGE);
        } else {
          showNiasMessage(NIAS_FATAL_MESSAGE, true);
        }
      },
      complete: function () {
        modal.hideLoading();
      },
    });
    },
  );

  $("body").on("submit", "form.cart", function (e) {
    if ($(this).find(".single_add_to_cart_button").length) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  });

  // ===== باز کردن مودال روی مرحله سبد خرید (کلیک روی آیکون سبد/مینی‌کارت) =====
  // فقط محتوای فعلی سبد را نمایش می‌دهد؛ محصولی اضافه نمی‌شود.
  function openCartModal() {
    modal.clearFatalError();
    modal.showLoading();
    modal.open();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_get_cart",
        nonce: niasLoginFastsellData.nonce,
      },
      success: function (response) {
        _log("[NIAS fastsell] get_cart response:", response);

        // پاسخ 0 / -1 خودِ admin-ajax — توضیح در هندلر add_to_cart
        if (!response || typeof response !== "object") {
          _warn("[NIAS fastsell] get_cart: unexpected payload", response);
          modal.showFatalError(NIAS_FATAL_MESSAGE);
          return;
        }

        if (response.success) {
          $(".nias-login-fastsell-cart-container").html(
            response.data.cart_html,
          );
          checkCartEmpty();
          clearCheckoutCache();
          modal.goToStep(1);
        } else {
          _warn("[NIAS fastsell] get_cart: success=false", response);
        }
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] get_cart AJAX error:", jqXHR.status, status, err);
        modal.showFatalError(NIAS_FATAL_MESSAGE);
      },
      complete: function () {
        modal.hideLoading();
      },
    });
  }

  // فیلد سلکتور در تنظیمات ادمین تعیین می‌شود؛ اگر خالی باشد این قابلیت غیرفعال است.
  var _openModalSelector =
    niasLoginFastsellData && niasLoginFastsellData.open_modal_selector;
  if (_openModalSelector) {
    var _openModalSelectorStr = parseUserSelectorList(_openModalSelector).join(
      ", ",
    );
    if (_openModalSelectorStr) {
      $("body").on("click", _openModalSelectorStr, function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        openCartModal();
      });
    }
  }

  // بلوک درون‌صفحه‌ای رویداد «باز شدن» ندارد، پس سبد را همان ابتدای بارگذاری
  // صفحه می‌گیرد (همان مسیری که مودال هنگام باز شدن طی می‌کند)
  if (_inline) {
    openCartModal();
  }

  // دکمه‌ی پیام خطای انتقالی — پیام از کاربر می‌خواهد صفحه را دوباره باز کند
  $(document).on("click", ".nias-fastsell-fatal-retry", function () {
    window.location.reload();
  });

  // بستن مودال
  $(".nias-login-fastsell-modal-close, .nias-login-fastsell-modal-overlay").on(
    "click",
    function () {
      modal.close();
    },
  );

  // ===== تغییر روش حمل در مرحله تسویه حساب (step 2) =====
  $("body").on(
    "change",
    "#nias-login-fastsell-checkout-container input[name='shipping_method']",
    function () {
      var methodId = $(this).val();
      modal.showLoading();

      _ajax({
        url: niasLoginFastsellData.ajax_url,
        type: "POST",
        dataType: "json",
        data: {
          action: "nias_login_fastsell_set_shipping",
          nonce: niasLoginFastsellData.nonce,
          shipping_method: methodId,
          is_step2: 1,
        },
        success: function (response) {
          if (response && response.success && response.data.summary_html) {
            var $wrapper = $("#nias-fastsell-checkout-order-summary");
            $wrapper.html(response.data.summary_html);
          }
        },
        complete: function () {
          modal.hideLoading();
        },
      });
    },
  );

  // ===== تغییر روش حمل در مرحله سبد خرید =====
  $("body").on("change", ".nias-fastsell-cart-shipping-radio", function () {
    var methodId = $(this).val();
    modal.showLoading();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_set_shipping",
        nonce: niasLoginFastsellData.nonce,
        shipping_method: methodId,
        is_step2: 0,
      },
      success: function (response) {
        if (response && response.success) {
          var $summary = $("#nias-fastsell-order-summary");
          if ($summary.length) {
            var $parent = $summary.parent();
            $summary.replaceWith(response.data.summary_html);
            // سرور خودش گزینه انتخاب‌شده را checked می‌فرستد؛ این فقط تضمین است
            // (به کلاس رادیو محدود شده تا اینپوت دیگری با همین value را نگیرد)
            $parent
              .find('.nias-fastsell-cart-shipping-radio[value="' + methodId + '"]')
              .prop("checked", true);
          }
        }
      },
      complete: function () {
        modal.hideLoading();
      },
    });
  });

  // ===== تغییر تعداد =====
  $("body").on("click", ".nias-login-fastsell-qty-plus", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var $wrapper = $btn.closest(".nias-login-fastsell-qty-wrapper");
    var $input = $wrapper.find(".nias-login-fastsell-qty-input");
    var $minusBtn = $wrapper.find(".nias-login-fastsell-qty-minus");
    var currentQty = parseInt($input.val()) || 1;
    var newQty = currentQty + 1;
    var productId = $input.data("product-id");
    var variationId = $input.data("variation-id") || 0;

    $btn.prop("disabled", true);
    modal.showLoading();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      data: {
        action: "nias_check_stock",
        nonce: niasLoginFastsellData.nonce,
        product_id: productId,
        variation_id: variationId,
        quantity: newQty,
      },
      success: function (response) {
        if (response.success) {
          $input.val(newQty).trigger("change");
          var maxQty = response.data.max_qty;
          if (maxQty !== null && newQty >= maxQty) {
            $btn.prop("disabled", true).addClass("nias-qty-btn-disabled");
          }
          $minusBtn.prop("disabled", false).removeClass("nias-qty-btn-disabled");
        } else {
          showNiasMessage(response.data.message, true);
          $btn.prop("disabled", true).addClass("nias-qty-btn-disabled");
        }
      },
      error: function () {
        showNiasMessage("خطا در بررسی موجودی", true);
      },
      complete: function () {
        if (!$btn.hasClass("nias-qty-btn-disabled")) {
          $btn.prop("disabled", false);
        }
        modal.hideLoading();
      },
    });
  });

  $("body").on("click", ".nias-login-fastsell-qty-minus", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var $wrapper = $btn.closest(".nias-login-fastsell-qty-wrapper");
    var $input = $wrapper.find(".nias-login-fastsell-qty-input");
    var $plusBtn = $wrapper.find(".nias-login-fastsell-qty-plus");
    var currentQty = parseInt($input.val()) || 1;

    if (currentQty > 1) {
      var newQty = currentQty - 1;
      $input.val(newQty).trigger("change");
      $plusBtn.prop("disabled", false).removeClass("nias-qty-btn-disabled");
      if (newQty <= 1) {
        $btn.prop("disabled", true).addClass("nias-qty-btn-disabled");
      }
    }
  });

  // تایپ مستقیم عدد در اینپوت تعداد — برخلاف دکمه‌های + و −، اینجا هیچ چکی
  // انجام نمی‌شد و کاربر می‌توانست هر عددی وارد کند. سقف/کف را همین‌جا اعمال
  // می‌کنیم؛ اعتبارسنجی نهایی همچنان روی سرور انجام می‌شود.
  $("body").on("change", ".nias-login-fastsell-qty-input", function () {
    var $input = $(this);
    var $item = $input.closest(".nias-login-fastsell-cart-item");
    var cartKey = $item.data("key");
    var qty = parseInt($input.val(), 10);
    var maxQty = parseInt($input.attr("max"), 10);

    if (isNaN(qty) || qty < 1) {
      qty = 1;
      $input.val(qty);
    }

    if (!isNaN(maxQty) && maxQty > 0 && qty > maxQty) {
      qty = maxQty;
      $input.val(qty);
      showNiasMessage("فقط " + maxQty + " عدد در انبار موجود است", true);
    }

    updateCartItem(cartKey, qty);
  });

  // حذف محصول
  $("body").on("click", ".nias-login-fastsell-remove-item", function () {
    const $item = $(this).closest(".nias-login-fastsell-cart-item");
    const cartKey = $item.data("key");

    modal.showLoading();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_remove_cart_item",
        nonce: niasLoginFastsellData.nonce,
        cart_item_key: cartKey,
      },
      success: function (response) {
        if (response && response.success) {
          $(".nias-login-fastsell-cart-container").html(
            response.data.cart_html,
          );
          checkCartEmpty();
          clearCheckoutCache();
          if (response.data.payment_section_html !== undefined) {
            $(".nias-login-fastsell-payment-methods").html(
              response.data.payment_section_html,
            );
            togglePaymentSectionVisibility();
            updateSubmitState();
          }
        } else {
          _warn("[NIAS fastsell] remove_cart_item: success=false", response);
        }
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] remove_cart_item AJAX error:", status, err);
      },
      complete: function () {
        modal.hideLoading();
      },
    });
  });

  // اعمال کد تخفیف
  $("body").on("click", "#nias-login-fastsell-apply-coupon", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const coupon = $("#nias-login-fastsell-coupon-code").val();

    if (!coupon) {
      showNiasMessage("لطفا کد تخفیف را وارد کنید", true);
      return;
    }

    $btn.prop("disabled", true);
    modal.showLoading();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_apply_coupon",
        nonce: niasLoginFastsellData.nonce,
        coupon_code: coupon,
      },
      success: function (response) {
        if (response && response.success) {
          $(".nias-login-fastsell-cart-container").html(
            response.data.cart_html,
          );
          checkCartEmpty();
          clearCheckoutCache();
          showNiasMessage(response.data.message);
          $("#nias-login-fastsell-coupon-code").val("");
        } else {
          showNiasMessage((response && response.data && response.data.message) || "خطا در اعمال کد تخفیف", true);
        }
      },
      complete: function () {
        modal.hideLoading();
        $btn.prop("disabled", false);
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] apply_coupon AJAX error:", status, err);
        showNiasMessage("مشکل در ارتباط با سرور. لطفا دوباره تلاش کنید.", true);
      },
    });
  });

  // رفتن به مرحله بعد
  $(".nias-login-fastsell-btn-next").on("click", function () {
    modal.goToStep(2);
  });

  $(".nias-login-fastsell-btn-back").on("click", function () {
    modal.goToStep(1);
  });

  // ===== احراز هویت قبل از تسویه حساب =====
  // روش احراز از تنظیمات «عملکرد» می‌آید و دقیقاً همان شرط‌های مودال ورود را دنبال
  // می‌کند: password_otp_activate → تب رمز + تب کد، password_activate → فقط رمز،
  // هیچ‌کدام → فقط کد تایید. درخواست‌ها به همان endpointهای /nias-login و
  // /nias-verify می‌روند تا منطق ورود فقط در class-nias-login-handler.php بماند.
  var AUTH = (niasLoginFastsellData && niasLoginFastsellData.auth) || {};
  var otpCountdownTimer = null;
  var otpSent = false;
  var authState = {
    identifier: "",
    nonce: "",
    isNewUser: false,
    panel: "code",
  };

  function defaultAuthPanel() {
    return AUTH.password_activate || AUTH.password_otp_activate
      ? "password"
      : "code";
  }

  function resetFastsellAuth() {
    clearInterval(otpCountdownTimer);
    otpSent = false;
    authState = {
      identifier: "",
      nonce: "",
      isNewUser: false,
      panel: defaultAuthPanel(),
    };
  }

  /**
   * endpointهای ورود روی خطا کد HTTP غیر ۲۰۰ برمی‌گردانند اما بدنه‌شان همچنان
   * JSON معتبر است — پس شاخه error هم باید همان بدنه را به callback بدهد.
   */
  function authPost(url, payload, done) {
    modal.showLoading();

    _ajax({
      url: url,
      type: "POST",
      dataType: "json",
      data: payload,
      success: function (response) {
        done(response || {});
      },
      error: function (jqXHR, status, err) {
        if (jqXHR.responseJSON) {
          done(jqXHR.responseJSON);
          return;
        }
        _err("[NIAS fastsell] auth request failed:", url, status, err);
        showNiasMessage("خطا در ارتباط با سرور. لطفا دوباره تلاش کنید.", true);
        done(null);
      },
      complete: function () {
        modal.hideLoading();
      },
    });
  }

  function startOtpCountdown(seconds) {
    var $resend = $("#nias-fastsell-otp-resend");
    clearInterval(otpCountdownTimer);

    var remaining = parseInt(seconds, 10) || 120;

    function render() {
      if (remaining > 0) {
        $resend
          .prop("disabled", true)
          .text("ارسال مجدد (" + toPersianDigits(remaining) + ")");
      } else {
        clearInterval(otpCountdownTimer);
        $resend.prop("disabled", false).text("ارسال مجدد کد");
      }
    }

    render();
    otpCountdownTimer = setInterval(function () {
      remaining--;
      render();
    }, 1000);
  }

  /** بلوک ساخت رمز دستی فقط برای کاربر جدید — مطابق رفتار مودال ورود */
  function applyManualPasswordState() {
    var showManual = !!(AUTH.manual_password && authState.isNewUser);
    $(".nias-fastsell-otp-manual").toggle(showManual);
    $(".nias-fastsell-otp-existing-password").toggle(!showManual);
    $(".nias-fastsell-otp-forgot-row").toggle(!showManual);
  }

  function switchAuthPanel(panel) {
    var $root = $("#nias-fastsell-otp");
    var $target = $root.find(
      '.nias-fastsell-otp-panel[data-panel="' + panel + '"]',
    );

    if (!$target.length) {
      panel = $root.find(".nias-fastsell-otp-panel").first().data("panel");
    }
    authState.panel = panel;

    $root.find(".nias-fastsell-otp-tab").each(function () {
      $(this).toggleClass("is-active", $(this).data("panel") === panel);
    });
    $root.find(".nias-fastsell-otp-panel").each(function () {
      $(this).toggle($(this).data("panel") === panel);
    });
  }

  function showAuthStep2() {
    $(".nias-fastsell-otp-step-identifier").hide();
    $(".nias-fastsell-otp-step2").show();
    applyManualPasswordState();
    switchAuthPanel(authState.panel);
  }

  function showAuthStep1() {
    clearInterval(otpCountdownTimer);
    otpSent = false;
    $(".nias-fastsell-otp-step2").hide();
    $(".nias-fastsell-otp-step-identifier").show();
    $("#nias-fastsell-auth-continue").prop("disabled", false);
    $("#nias-fastsell-auth-identifier").trigger("focus");
  }

  /** مرحله ۱ — ارسال شناسه به /nias-login */
  function submitAuthIdentifier() {
    var $input = $("#nias-fastsell-auth-identifier");
    var value = $.trim($input.val() || "");

    if (!value) {
      showNiasMessage(
        "لطفاً " + ($input.data("label") || "اطلاعات") + " خود را وارد کنید",
        true,
      );
      return;
    }

    var payload = { action: "nias_login" };
    payload[$input.attr("name")] = value;

    $("#nias-fastsell-auth-continue").prop("disabled", true);

    authPost(niasLoginFastsellData.login_url, payload, function (response) {
      $("#nias-fastsell-auth-continue").prop("disabled", false);
      if (!response) return;

      var data = response.data || {};

      // کد قبلی هنوز مهلت دارد — مستقیم به مرحله وارد کردن کد
      if (!response.success && data.error_code === 123) {
        authState.identifier = data.identifier || value;
        authState.nonce = data._wpnonce || "";
        authState.panel = "code";
        otpSent = true;
        showAuthStep2();
        startOtpCountdown(data.duration);
        showNiasMessage(data.message || "کد قبلی هنوز معتبر است", true);
        $("#nias-fastsell-auth-code").val("").trigger("focus");
        return;
      }

      if (!response.success) {
        showNiasMessage(data.message || "خطا در بررسی اطلاعات", true);
        return;
      }

      authState.identifier = data.identifier || value;
      authState.nonce = data._wpnonce || "";
      authState.isNewUser = !!data.is_new_user;

      if (data.login_mode === "password_only") {
        // فقط رمز عبور فعال است
        authState.panel = "password";
        showAuthStep2();
        focusActiveAuthField();
      } else if (data.password_otp_mode) {
        // رمز + کد: کد فقط با باز کردن تب کد تایید ارسال می‌شود
        authState.panel = "password";
        otpSent = false;
        showAuthStep2();
        focusActiveAuthField();
      } else {
        // فقط کد تایید — کد همین حالا ارسال شده است
        authState.panel = "code";
        otpSent = true;
        showAuthStep2();
        startOtpCountdown(data.duration);
        focusActiveAuthField();
      }

      if (data.message) {
        showNiasMessage(data.message);
      }
    });
  }

  function focusActiveAuthField() {
    if (authState.panel === "code") {
      $("#nias-fastsell-auth-code").val("").trigger("focus");
      return;
    }
    if (AUTH.manual_password && authState.isNewUser) {
      $("#nias-fastsell-auth-new-password").trigger("focus");
      return;
    }
    $("#nias-fastsell-auth-password").trigger("focus");
  }

  /** درخواست ارسال/ارسال مجدد کد تایید */
  function requestOtp() {
    if (!authState.identifier) return;

    authPost(
      niasLoginFastsellData.login_url,
      { nias_action: "send_otp", identifier: authState.identifier },
      function (response) {
        if (!response) return;

        var data = response.data || {};

        if (response.success) {
          otpSent = true;
          startOtpCountdown(data.duration);
          showNiasMessage(data.message || "کد تایید ارسال شد");
          $("#nias-fastsell-auth-code").val("").trigger("focus");
          return;
        }

        // کد قبلی هنوز معتبر است — شمارش معکوس را با مهلت باقی‌مانده ادامه بده
        if (data.error_code === 123) {
          otpSent = true;
          startOtpCountdown(data.duration);
          showNiasMessage(data.message || "کد قبلی هنوز معتبر است", true);
          return;
        }

        showNiasMessage(data.message || "خطا در ارسال کد تایید", true);
      },
    );
  }

  /** nonce مهمانِ قبلی پس از ورود معتبر نیست */
  function refreshFastsellNonce(done) {
    $.ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: { action: "nias_login_fastsell_refresh_nonce" },
      success: function (response) {
        if (response && response.success && response.data && response.data.nonce) {
          niasLoginFastsellData.nonce = response.data.nonce;
        }
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] refresh_nonce failed:", status, err);
      },
      complete: function () {
        done();
      },
    });
  }

  /** مرحله ۲ — تایید کد یا ورود/ثبت‌نام با رمز عبور از طریق /nias-verify */
  function submitAuthVerify() {
    var payload = {
      action: "nias_verify",
      identifier: authState.identifier,
      _wpnonce: authState.nonce,
    };

    if (authState.panel === "password") {
      if (AUTH.manual_password && authState.isNewUser) {
        var newPass = $.trim($("#nias-fastsell-auth-new-password").val() || "");
        var confirmPass = $.trim(
          $("#nias-fastsell-auth-confirm-password").val() || "",
        );

        if (!newPass || !confirmPass) {
          showNiasMessage("لطفاً رمز عبور و تکرار آن را وارد کنید", true);
          return;
        }
        if (newPass !== confirmPass) {
          showNiasMessage("رمز عبور و تکرار آن یکسان نیستند", true);
          return;
        }
        if (
          newPass.length < 8 ||
          !/[A-Za-z]/.test(newPass) ||
          !/[0-9]/.test(newPass)
        ) {
          showNiasMessage(
            "رمز عبور باید حداقل ۸ کاراکتر و شامل حروف و اعداد باشد",
            true,
          );
          return;
        }

        payload.password = newPass;
        payload.register_with_password = 1;
      } else {
        var password = $("#nias-fastsell-auth-password").val() || "";
        if (!password) {
          showNiasMessage("لطفاً رمز عبور را وارد کنید", true);
          return;
        }
        payload.password = password;
        payload.login_with_password = 1;
      }
    } else {
      var code = $.trim($("#nias-fastsell-auth-code").val() || "");
      if (!code) {
        showNiasMessage("لطفاً کد تأیید را وارد کنید", true);
        return;
      }
      payload.code = code;
    }

    var $btn = $("#nias-fastsell-auth-submit").prop("disabled", true);

    authPost(niasLoginFastsellData.verify_url, payload, function (response) {
      $btn.prop("disabled", false);
      if (!response) return;

      var data = response.data || {};

      if (!response.success) {
        showNiasMessage(data.message || "اطلاعات واردشده صحیح نیست", true);
        return;
      }

      clearInterval(otpCountdownTimer);
      showNiasMessage(data.message || "ورود با موفقیت انجام شد");

      // بین «تایید شد» تا رسیدن فرم تسویه دو رفت‌وبرگشت فاصله است: تازه‌سازی
      // nonce و بعد خود فرم. تا قبل از این، فرم احراز همان‌جا می‌ماند و کاربر
      // دوباره فیلد کد را می‌دید و فکر می‌کرد تاییدش ثبت نشده. پس همین‌جا فرم
      // را برمی‌داریم و لودر را روشن می‌کنیم.
      $("#nias-login-fastsell-checkout-container").empty();
      modal.showLoading();

      refreshFastsellNonce(function () {
        // شمارنده لودر متوازن می‌ماند: loadCheckoutForm خودش showLoading می‌کند
        // و در complete آن را برمی‌دارد. چون هر دو در یک بلوک همزمان اجرا
        // می‌شوند، لودر بین این دو خط پلک نمی‌زند.
        modal.hideLoading();
        modal.loadCheckoutForm();
      });
    });
  }

  $("body").on("click", "#nias-fastsell-auth-continue", function (e) {
    e.preventDefault();
    submitAuthIdentifier();
  });

  $("body").on("click", "#nias-fastsell-auth-submit", function (e) {
    e.preventDefault();
    submitAuthVerify();
  });

  // تعویض تب رمز/کد — در حالت رمز+کد، کد فقط با اولین باز شدن تب ارسال می‌شود
  $("body").on("click", ".nias-fastsell-otp-tab", function (e) {
    e.preventDefault();

    var panel = $(this).data("panel");
    switchAuthPanel(panel);
    focusActiveAuthField();

    if (panel === "code" && !otpSent) {
      requestOtp();
    }
  });

  $("body").on("click", "#nias-fastsell-otp-resend", function (e) {
    e.preventDefault();
    requestOtp();
  });

  $("body").on("click", "#nias-fastsell-otp-edit", function (e) {
    e.preventDefault();
    showAuthStep1();
  });

  // بازیابی رمز عبور — همان endpoint مودال ورود
  $("body").on("click", "#nias-fastsell-auth-forgot", function (e) {
    e.preventDefault();

    if (!authState.identifier) {
      showNiasMessage("ابتدا اطلاعات حساب خود را وارد کنید", true);
      return;
    }

    var $btn = $(this).prop("disabled", true);

    authPost(
      niasLoginFastsellData.forgot_url,
      { action: "nias_forgot_password", identifier: authState.identifier },
      function (response) {
        $btn.prop("disabled", false);
        if (!response) return;

        var data = response.data || {};
        showNiasMessage(
          data.message ||
            (response.success ? "رمز جدید ارسال شد" : "خطا در بازیابی رمز عبور"),
          !response.success,
        );
      },
    );
  });

  // Enter در فیلدهای احراز هویت
  $("body").on("keydown", "#nias-fastsell-auth-identifier", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      submitAuthIdentifier();
    }
  });

  $("body").on(
    "keydown",
    "#nias-fastsell-auth-code, #nias-fastsell-auth-password, #nias-fastsell-auth-new-password, #nias-fastsell-auth-confirm-password",
    function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        submitAuthVerify();
      }
    },
  );

  // پردازش تسویه حساب
  $(".nias-login-fastsell-btn-submit").on("click", function (e) {
    e.preventDefault();

    const checkoutData = {};

    $("#nias-login-fastsell-checkout-container")
      .find("input, select, textarea")
      .each(function () {
        const $field = $(this);
        const name = $field.attr("name");

        if (name) {
          if ($field.attr("type") === "checkbox") {
            checkoutData[name] = $field.is(":checked") ? 1 : 0;
          } else if ($field.attr("type") === "radio") {
            if ($field.is(":checked")) {
              checkoutData[name] = $field.val();
            }
          } else {
            checkoutData[name] = $field.val();
          }
        }
      });

    // پاک کردن خطاهای قبلی
    $(".nias-fastsell-field-error").removeClass("nias-fastsell-field-error");

    let isValid = true;

    getVisibleRequiredFields().forEach(function ($f) {
      const v = $f.val();
      const empty = !v || v === "";
      markFieldError($f, empty);
      if (empty) isValid = false;
    });

    const cartTotalVal = parseFloat(
      $("#nias-fastsell-cart-total").val() || "1",
    );
    if (cartTotalVal > 0) {
      if (
        $("#nias-login-fastsell-checkout-container").find(
          'input[name="payment_method"]:checked',
        ).length === 0
      ) {
        isValid = false;
      }
    }
    if ($(".nias-login-fastsell-shipping-methods").length > 0) {
      if (
        $("#nias-login-fastsell-checkout-container").find(
          'input[name="shipping_method"]:checked',
        ).length === 0
      ) {
        isValid = false;
      }
    }

    if (!isValid) {
      showNiasMessage("لطفا فیلدهای مشخص‌شده را پر کنید", true);
      // اسکرول به اولین فیلد خطادار
      var $firstErr = $(".nias-fastsell-field-error").first();
      if ($firstErr.length) {
        $("#nias-login-fastsell-checkout-container").animate(
          { scrollTop: $firstErr.closest(".form-row, .woocommerce-form-row").position().top - 16 },
          300,
        );
      }
      return;
    }

    const $submitBtn = $(this);
    $submitBtn.prop("disabled", true);

    const isFreeCheckout = cartTotalVal <= 0;

    modal.showCheckoutProgress([
      { until: 30, text: "در حال بررسی اطلاعات سفارش..." },
      { until: 65, text: "در حال ثبت سفارش شما..." },
      {
        until: 101,
        text: isFreeCheckout
          ? "در حال نهایی کردن سفارش..."
          : "در حال اتصال به درگاه پرداخت...",
      },
    ]);

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_process_checkout",
        nonce: niasLoginFastsellData.nonce,
        checkout_data: checkoutData,
      },
      success: function (response) {
        if (response && response.success) {
          modal.finishCheckoutProgress(
            isFreeCheckout
              ? "سفارش ثبت شد؛ در حال انتقال..."
              : "در حال انتقال به درگاه پرداخت...",
            function () {
              window.location.href = response.data.redirect_url;
            },
          );
        } else {
          modal.hideCheckoutProgress();
          $submitBtn.prop("disabled", false);

          if (response && response.data && response.data.cart_empty) {
            handleEmptyCartResponse(response.data);
            return;
          }

          showNiasMessage((response && response.data && response.data.message) || "خطا در پردازش سفارش", true);
        }
      },
      error: function () {
        showNiasMessage("خطا در پردازش سفارش. لطفا دوباره تلاش کنید.", true);
        modal.hideCheckoutProgress();
        $submitBtn.prop("disabled", false);
      },
    });
  });

  function updateCartItem(cartKey, qty) {
    modal.showLoading();

    _ajax({
      url: niasLoginFastsellData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "nias_login_fastsell_update_cart",
        nonce: niasLoginFastsellData.nonce,
        cart_item_key: cartKey,
        quantity: qty,
      },
      success: function (response) {
        if (response && response.success) {
          $(".nias-login-fastsell-cart-container").html(
            response.data.cart_html,
          );
          checkCartEmpty();
          clearCheckoutCache();
          if (response.data.payment_section_html !== undefined) {
            $(".nias-login-fastsell-payment-methods").html(
              response.data.payment_section_html,
            );
            togglePaymentSectionVisibility();
            updateSubmitState();
          }
        } else {
          _warn("[NIAS fastsell] update_cart: success=false", response);

          // خطای موجودی از سرور — سبد را به مقدار واقعیِ ثبت‌شده برگردان
          var data = (response && response.data) || {};
          if (data.cart_html) {
            $(".nias-login-fastsell-cart-container").html(data.cart_html);
            checkCartEmpty();
            clearCheckoutCache();
          }
          showNiasMessage(
            data.message || "بروزرسانی تعداد انجام نشد",
            true,
          );
        }
      },
      error: function (jqXHR, status, err) {
        _err("[NIAS fastsell] update_cart AJAX error:", status, err);
        showNiasMessage("خطا در بروزرسانی سبد خرید", true);
      },
      complete: function () {
        modal.hideLoading();
      },
    });
  }
});
