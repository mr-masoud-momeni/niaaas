(function () {
  'use strict';

  if (typeof window.niasBaleResend === 'undefined') return;

  var config = window.niasBaleResend;
  var selector = '.nias-resendbox .nias-resend';
  var pollTimer = null;
  var baleCountdownTimer = null;

  function getButton() {
    return document.querySelector(selector);
  }

  function getBox() {
    return document.querySelector('.nias-resendbox');
  }

  function isActive() {
    var box = getBox();
    return !!(box && box.classList.contains('active'));
  }

  function setButtonLabel() {
    var button = getButton();
    if (!button || !isActive()) return;

    button.textContent = config.label;
    button.setAttribute('aria-label', config.label);
    button.removeAttribute('disabled');
  }

  function getIdentifier() {
    var input = document.querySelector('#nias-code-form input[name="identifier"]');
    return input ? (input.value || '').trim() : '';
  }

  function clearCodeInputs() {
    document.querySelectorAll('.nias-code-box input').forEach(function (input) {
      input.value = '';
      input.disabled = false;
    });
  }

  function showMessage(message) {
    var box = document.querySelector('.nias-login-message');
    if (box) {
      box.textContent = message;
      box.classList.add('active');
    }
  }

  function renderCountdown(seconds) {
    var value = Math.max(0, parseInt(seconds, 10) || 0);
    var minutes = String(Math.floor(value / 60)).padStart(2, '0');
    var secs = String(value % 60).padStart(2, '0');

    document.querySelectorAll('.nias-countdown').forEach(function (el) {
      el.textContent = minutes + ':' + secs;
    });
  }

  function startBaleCountdown(duration) {
    var remaining = Math.max(1, parseInt(duration, 10) || 120);
    var box = getBox();

    if (baleCountdownTimer) {
      clearInterval(baleCountdownTimer);
      baleCountdownTimer = null;
    }

    if (box) box.classList.remove('active');
    renderCountdown(remaining);

    baleCountdownTimer = setInterval(function () {
      remaining--;
      renderCountdown(remaining);

      if (remaining <= 0) {
        clearInterval(baleCountdownTimer);
        baleCountdownTimer = null;

        var resendBox = getBox();
        if (resendBox) resendBox.classList.add('active');
        setButtonLabel();
      }
    }, 1000);
  }

  function sendBale(button) {
    var identifier = getIdentifier();

    if (!identifier) {
      showMessage('شماره موبایل پیدا نشد. لطفاً دوباره تلاش کنید.');
      return;
    }

    button.disabled = true;
    button.textContent = config.sending;

    var body = new URLSearchParams();
    body.append('action', 'nias_bale_resend');
    body.append('nonce', config.nonce);
    body.append('identifier', identifier);

    fetch(config.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result || !result.success) {
          var message = result && result.data && result.data.message
            ? result.data.message
            : 'خطا در ارسال کد با بله.';

          showMessage(message);
          button.disabled = false;
          button.textContent = config.label;
          return;
        }

        clearCodeInputs();
        showMessage(result.data.message || 'کد جدید با پیام‌رسان بله ارسال شد.');
        startBaleCountdown(result.data.duration || 120);

        var firstInput = document.querySelector('.nias-code-box input');
        if (firstInput) firstInput.focus();
      })
      .catch(function () {
        showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
        button.disabled = false;
        button.textContent = config.label;
      });
  }

  // فقط کلیک روی دکمه فعال بله را از handler اصلی resend جدا می‌کنیم.
  // هیچ تغییری در باز/بسته شدن مودال یا فرم اصلی انجام نمی‌شود.
  document.addEventListener('click', function (event) {
    var target = event.target;
    var button = target && target.closest ? target.closest(selector) : null;

    if (!button || !isActive()) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    if (!button.disabled) sendBale(button);
  }, true);

  // بدون MutationObserver روی کل DOM؛ فقط وضعیت resendbox را سبک بررسی می‌کنیم.
  function watchResendBox() {
    setButtonLabel();

    if (!pollTimer) {
      pollTimer = setInterval(function () {
        if (isActive()) setButtonLabel();
      }, 500);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', watchResendBox);
  } else {
    watchResendBox();
  }
})();
