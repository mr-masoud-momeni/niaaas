(function () {
  'use strict';

  if (typeof window.niasBaleResend === 'undefined') return;

  var config = window.niasBaleResend;
  var boxSelector = '.nias-resendbox';
  var originalSelector = '.nias-resend';
  var baleSelector = '.nias-bale-resend-button';
  var observer = null;

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

  function getIdentifier() {
    var input = document.querySelector('#nias-code-form input[name="phone"]');
    if (input && input.value) return input.value.trim();

    input = document.querySelector('#niasphoneinput');
    if (input && input.value) return input.value.trim();

    // fallback برای ساختارهایی که شماره در فیلد identifier نگهداری می‌شود
    input = document.querySelector('#nias-code-form input[name="identifier"]');
    return input ? (input.value || '').trim() : '';
  }

  function normalizeDigits(value) {
    return String(value || '').replace(/[۰-۹]/g, function (digit) {
      return '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
    }).replace(/[٠-٩]/g, function (digit) {
      return '٠١٢٣٤٥٦٧٨٩'.indexOf(digit);
    });
  }

  function clearCodeInputs() {
    document.querySelectorAll('.nias-code-box input').forEach(function (input) {
      input.value = '';
    });
  }

  function showMessage(message) {
    var messageBox = document.querySelector('.nias-login-message');
    if (messageBox) {
      messageBox.textContent = message;
      messageBox.classList.add('active');
    }
  }

  function createBaleButton() {
    var box = getBox();
    if (!box || !isActive()) return;

    if (getBaleButton()) return;

    // مهم: به دکمه و رفتار اصلی Nias دست نمی‌زنیم.
    // دکمه Bale کاملاً مستقل از .nias-resend ساخته می‌شود.
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'nias-bale-resend-button';
    button.textContent = config.label;
    button.setAttribute('aria-label', config.label);

    button.style.cssText = [
      'display:block',
      'width:auto',
      'margin:8px auto 0',
      'padding:0',
      'background:transparent',
      'border:0',
      'outline:0',
      'box-shadow:none',
      'color:var(--nias-primary,#043ccc)',
      'font:inherit',
      'cursor:pointer'
    ].join(';');

    box.appendChild(button);
  }

  function removeBaleButton() {
    var button = getBaleButton();
    if (button) button.remove();
  }

  function sendBale(button) {
    var identifier = normalizeDigits(getIdentifier());

    if (!/^09\d{9}$/.test(identifier)) {
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

        // بعد از ارسال موفق، فقط دکمه Bale خودمان را حذف می‌کنیم.
        // تایمر و وضعیت مودال کاملاً در اختیار Nias باقی می‌ماند.
        removeBaleButton();

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

    event.preventDefault();
    event.stopPropagation();
    sendBale(button);
  });

  function syncButton() {
    if (isActive()) {
      createBaleButton();
    } else {
      removeBaleButton();
    }
  }

  function init() {
    syncButton();

    // فقط تغییرات خود resendbox را زیر نظر می‌گیریم؛ هیچ کلاس یا وضعیت Nias را تغییر نمی‌دهیم.
    var box = getBox();
    if (box && window.MutationObserver) {
      observer = new MutationObserver(function () {
        syncButton();
      });
      observer.observe(box, {
        attributes: true,
        attributeFilter: ['class']
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();