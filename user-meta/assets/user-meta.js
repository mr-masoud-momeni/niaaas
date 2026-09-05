(function ($) {

  /* ── toast ──────────────────────────────────────────────────── */
  var TOAST_COLORS = {
    success: { bg: '#14532d', color: '#86efac', border: 'rgba(134,239,172,.25)' },
    error:   { bg: '#450a0a', color: '#fca5a5', border: 'rgba(252,165,165,.25)' },
    info:    { bg: '#1e3a5f', color: '#93c5fd', border: 'rgba(147,197,253,.25)' },
  };

  function metaToast(msg, type) {
    type = type || 'info';
    var wrap = document.getElementById('nias-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'nias-meta-toast-wrap';
      wrap.style.cssText =
        'position:fixed;top:20px;left:50%;transform:translateX(-50%);' +
        'z-index:999999;display:flex;flex-direction:column;align-items:center;' +
        'gap:10px;pointer-events:none;width:max-content;max-width:min(400px,calc(100vw - 32px));';
      document.body.appendChild(wrap);
    }
    var c = TOAST_COLORS[type] || TOAST_COLORS.info;
    var t = document.createElement('div');
    t.style.cssText =
      'display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;' +
      'font-size:14px;line-height:1.5;direction:rtl;font-family:inherit;' +
      'pointer-events:auto;box-shadow:0 4px 24px rgba(0,0,0,.35);' +
      'opacity:0;transform:translateY(-10px) scale(.96);' +
      'transition:opacity .25s ease,transform .25s ease;min-width:220px;' +
      'background:' + c.bg + ';color:' + c.color + ';border:1px solid ' + c.border + ';';
    var icons = { success: '✓', error: '✕', info: 'ℹ' };
    t.innerHTML =
      '<span style="flex-shrink:0;">' + (icons[type] || 'ℹ') + '</span>' +
      '<span style="flex:1;">' + msg + '</span>' +
      '<button type="button" style="background:none;border:none;cursor:pointer;color:inherit;' +
        'opacity:.55;font-size:18px;line-height:1;padding:0;margin-right:auto;"' +
        ' onclick="this.parentNode.remove()">×</button>';
    wrap.appendChild(t);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        t.style.opacity = '1';
        t.style.transform = 'none';
      });
    });
    var tid = setTimeout(function () { dismiss(t); }, 5000);
    t.querySelector('button').addEventListener('click', function () {
      clearTimeout(tid);
      dismiss(t);
    });
    function dismiss(el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(-10px) scale(.96)';
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
    }
  }

  /* ── spinner SVG ─────────────────────────────────────────────── */
  var SPINNER =
    '<span class="nias-meta-btn-spinner"></span>';

  /* ── helpers ─────────────────────────────────────────────────── */
  function lockBody() {
    $('body').addClass('nias-login-meta-locked');
  }

  function tryParseJSON(str) {
    try { return JSON.parse(str); } catch (e) { return null; }
  }

  function handleError(res, $form, $btn, btnText) {
    $btn.prop('disabled', false).html(btnText).removeClass('loading');
    if (res && res.data) {
      if (res.data.fields && res.data.fields.length) {
        res.data.fields.forEach(function (k) {
          $form.find('[name="' + k + '"]').closest('.nias-login-meta-field').addClass('error');
        });
      }
      metaToast(res.data.message || 'لطفاً فیلدهای ضروری را تکمیل کنید', 'error');
    } else {
      metaToast('خطا در ارسال اطلاعات، لطفاً مجدداً تلاش کنید', 'error');
    }
  }

  /* ── form submit ─────────────────────────────────────────────── */
  function submitForm(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn  = $form.find('.nias-login-meta-submit');
    var btnText = $btn.html();

    $form.find('.nias-login-meta-field').removeClass('error');
    $btn.prop('disabled', true).html(SPINNER).addClass('loading');

    var data = {};
    $form.find('[name]').each(function () {
      data[$(this).attr('name')] = $(this).val();
    });
    data.action = 'nias-login-meta_save';
    data.nonce  = window.niasLoginMeta && window.niasLoginMeta.nonce;

    $.post(window.niasLoginMeta.ajaxUrl, data)
      .done(function (res) {
        // jQuery may pass a raw string if Content-Type was not set (stray PHP output
        // before wp_send_json_success causes headers to be sent early). Parse manually.
        if (typeof res === 'string') {
          res = tryParseJSON(res);
        }
        if (res && res.success) {
          metaToast((res.data && res.data.message) || 'اطلاعات با موفقیت ذخیره شد', 'success');
          setTimeout(function () { window.location.reload(); }, 1600);
        } else {
          handleError(res, $form, $btn, btnText);
        }
      })
      .fail(function (jqXHR) {
        // jQuery calls .fail() for non-2xx (e.g. 400 validation errors from wp_send_json_error).
        // Parse the actual server response so we can show the real message + highlight fields.
        var res = tryParseJSON(jqXHR.responseText);
        handleError(res, $form, $btn, btnText);
      });
  }

  /* ── init ────────────────────────────────────────────────────── */
  $(function () {
    if (window.niasLoginMeta) {
      lockBody();
      $(document).on('submit', '.nias-login-meta-form', submitForm);
    }
  });

})(jQuery);
