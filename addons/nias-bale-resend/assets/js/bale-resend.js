(function () {
  'use strict';

  if (typeof window.niasBaleResend === 'undefined') return;

  var config = window.niasBaleResend;
  var boxSelector = '.nias-resendbox';
  var originalSelector = '.nias-resend';
  var baleSelector = '.nias-bale-resend-button';
  var pollTimer = null;
  var baleCountdownTimer = null;

  function getBox() {
    return document.querySelector(boxSelector);
  }

  function isActive() {
    var box = getBox();
    return !!(box && box.classList.contains('active'));
  }

  function getBaleButton() {
    return document.querySelector(baleSelector);
  }

  function createBaleButton() {
    var box = getBox();
    if (!box || !isActive()) return null;

    var existing = getBaleButton();
    if (existing) return existing;

    var original = box.querySelector(originalSelector);
    if (!original) return null;

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'nias-bale-resend-button';
    button.textContent = config.label;
    button.setAttribute('aria-label', config.label);

    button.style.cssText = [
      'width:auto',
      'background:transparent',
      'color:var(--nias-primary,#043ccc)',
      'border:0',
      'outline:0',
      'padding:0',
      'margin:0',
      'font:inherit',
      'cursor:pointer',
      'display:inline-flex',
      'align-items:center',
      'justify-content:center'
    ].join(';');

    // دکمه اصلی Nias فقط پنهان می‌شود؛ handler آن را دستکاری یا متوقف نمی‌کنیم.
    original.style.display = 'none';
    original.setAttribute('aria-hidden', 'true');

    box.insertBefore(button, original);
    return button;
  }

  function removeBaleButton() {
    var button = getBaleButton();
    if (button) button.remove();
  }

  function getIdentifier() {
    var input = document.querySelector('#nias-code-form input[name="phone"]');
    if (input && input.value) return input.value.trim();

    input = document.querySelector('#niasphoneinput');
    return input ? (input.value || '').trim() : '';
  }

  function normalizeDigits(value) {
    return String(value || '').replace(/[۰-۹]/g, function (digit) {
      return '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
    });
  }

  function clearCodeInputs() {
    document.querySelectorAll('.nias-code-box input').forEach(function (input) {
      input.value = '';
    });
  }

  function showMessage(message) {
    var box = document.querySelector('.nias-login-code .nias-login-message');
    if (!box) box = document.querySelector('.nias-login-message');
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

    if (baleCountdownTimer) {
      clearInterval(baleCountdownTimer);
      baleCountdownTimer = null;
    }

    var box = getBox();
    if (box) box.classList.remove('active');
    removeBaleButton();
    renderCountdown(remaining);

    baleCountdownTimer = setInterval(function () {
      remaining--;
      renderCountdown(remaining);

      if (remaining <= 0) {
        clearInterval(baleCountdownTimer);
        baleCountdownTimer = null;

        var resendBox = getBox();
        if (resendBox) {
          resendBox.classList.add('active');
          createBaleButton();
        }
      }
    }, 1000);
  }

  function sendBale(button) {
    var identifier = normalizeDigits(getIdentifier());

    if (!/^09\d{9}$/.test(identifier)) {
      showMessage('شماره موبایل پیدا نشد. لطفاً دوباره شماره را وارد کنید.');
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

  document.addEventListener('click', function (event) {
    var target = event.target;
    var button = target && target.closest ? target.closest(baleSelector) : null;

    if (!button) return;
    sendBale(button);
  });

  function watchResendBox() {
    if (isActive()) createBaleButton();

    if (!pollTimer) {
      pollTimer = setInterval(function () {
        if (isActive()) {
          createBaleButton();
        }
      }, 500);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', watchResendBox);
  } else {
    watchResendBox();
  }
})();