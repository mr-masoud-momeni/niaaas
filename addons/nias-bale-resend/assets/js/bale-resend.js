(function () {
  'use strict';

  if (typeof window.niasBaleResend === 'undefined') {
    return;
  }

  var config = window.niasBaleResend;
  var selector = '.nias-resendbox .nias-resend';

  function getResendButton() {
    return document.querySelector(selector);
  }

  function isResendActive(button) {
    var box = button && button.closest('.nias-resendbox');
    return !!(box && box.classList.contains('active'));
  }

  function updateButton() {
    var button = getResendButton();
    if (!button) return;

    if (isResendActive(button)) {
      if (!button.dataset.niasBaleOriginalText) {
        button.dataset.niasBaleOriginalText = button.textContent.trim();
      }
      button.textContent = config.label;
      button.setAttribute('aria-label', config.label);
      button.dataset.niasBaleActive = '1';
    } else {
      button.dataset.niasBaleActive = '0';
    }
  }

  function getIdentifier() {
    var input = document.querySelector("#nias-code-form input[name='identifier']");
    if (!input) return '';
    return (input.value || '').trim();
  }

  function clearOldCode() {
    document.querySelectorAll('.nias-code-box input').forEach(function (input) {
      input.value = '';
    });
  }

  function showMessage(message, type) {
    var messageBox = document.querySelector('.nias-login-message');
    if (messageBox) {
      messageBox.textContent = message;
      messageBox.classList.add('active');
    }

    if (typeof window.niasToast === 'function') {
      window.niasToast(message, type || 'success');
    }
  }

  function restartNiasCountdown(duration) {
    // تابع اصلی Nias داخل همان اسکریپت به صورت closure است و قابل دسترسی نیست.
    // بنابراین یک تایمر کوچک مستقل فقط برای رندر UI اجرا می‌کنیم.
    var remaining = parseInt(duration, 10) || 120;
    var countdownEls = document.querySelectorAll('.nias-countdown');
    var resendBox = document.querySelector('.nias-resendbox');
    var codeInputs = document.querySelectorAll('.nias-code-box input');
    var circle = document.querySelector('.stroke-neutral-dark');
    var total = remaining;
    var timer = null;

    if (resendBox) resendBox.classList.remove('active');
    if (codeInputs.length) {
      codeInputs.forEach(function (input) {
        input.value = '';
        input.disabled = false;
      });
    }

    function render() {
      var safe = Math.max(0, remaining);
      var minute = String(Math.floor(safe / 60)).padStart(2, '0');
      var second = String(safe % 60).padStart(2, '0');

      countdownEls.forEach(function (el) {
        el.textContent = minute + ':' + second;
      });

      if (circle && total > 0) {
        var circumference = 345.575;
        circle.style.strokeDashoffset = String(circumference * ((total - safe) / total));
      }

      if (safe <= 0) {
        if (timer) clearInterval(timer);
        if (resendBox) resendBox.classList.add('active');
        updateButton();
      }
    }

    render();
    timer = setInterval(function () {
      remaining--;
      render();
    }, 1000);
  }

  function sendBale(button) {
    var identifier = getIdentifier();

    if (!identifier) {
      showMessage('شماره موبایل پیدا نشد. لطفاً دوباره تلاش کنید.', 'error');
      return;
    }

    var box = button.closest('.nias-resendbox');
    if (box) box.classList.add('loading');
    button.disabled = true;
    button.textContent = config.sending;
    clearOldCode();

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
        if (result && result.success) {
          var duration = result.data && result.data.duration ? result.data.duration : 120;
          showMessage(result.data.message || 'کد جدید با پیام‌رسان بله ارسال شد.', 'success');
          restartNiasCountdown(duration);
        } else {
          var message = result && result.data && result.data.message
            ? result.data.message
            : 'خطا در ارسال کد با بله.';
          showMessage(message, 'error');
          if (box) box.classList.add('active');
          button.disabled = false;
          button.textContent = config.label;
        }
      })
      .catch(function () {
        showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        if (box) box.classList.add('active');
        button.disabled = false;
        button.textContent = config.label;
      })
      .finally(function () {
        if (box) box.classList.remove('loading');
      });
  }

  // در capture phase اجرا می‌شود تا handler اصلی Nias قبل از رسیدن کلیک به آن متوقف شود.
  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest(selector) : null;
    if (!button || !isResendActive(button)) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    if (button.disabled) return;
    sendBale(button);
  }, true);

  // وقتی countdown اصلی Nias کلاس active را اضافه می‌کند، متن دکمه را عوض کن.
  var observer = new MutationObserver(function () {
    updateButton();
  });

  function startObserver() {
    if (!document.body) return;
    observer.observe(document.body, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ['class']
    });
    updateButton();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startObserver);
  } else {
    startObserver();
  }
})();
