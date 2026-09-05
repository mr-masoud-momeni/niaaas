/* ============================================================
   NIAS Login — Admin Settings interactivity
   (design: nias-admin/Nias Settings.html)
   ============================================================ */

/* -------------------------------------------------------------------------- */
/*                       Tab navigation (global)                              */
/* -------------------------------------------------------------------------- */
function niasSetTab(name) {
  var app = document.querySelector(".nias-login-admin");
  if (!app) return;
  app.querySelectorAll(".nias-login-panel").forEach(function (p) {
    var on = p.id === name;
    p.classList.toggle("active", on);
    p.style.display = on ? "" : "none";
  });
  app.querySelectorAll(".nias-login-navitem").forEach(function (n) {
    n.classList.toggle("active", n.getAttribute("data-tab") === name);
  });
  try { localStorage.setItem("nias_tab", name); } catch (e) {}
  var mainEl = app.querySelector(".nias-login-main");
  if (mainEl) mainEl.scrollTop = 0;
}

/* kept global so inline onclick="niasopentab(event,'nsX')" keeps working
   (the dashboard chart loader and fastsell sortable hook into those clicks) */
function niasopentab(evt, name) {
  niasSetTab(name);
}

document.addEventListener("DOMContentLoaded", function () {
  var app = document.querySelector(".nias-login-admin");
  if (!app) return;

  var LS = {
    get: function (k, d) { try { return localStorage.getItem(k) || d; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  /* -------- direction switcher -------- */
  function setDir(dir) {
    app.setAttribute("data-dir", dir);
    LS.set("nias_dir", dir);
    app.querySelectorAll(".nias-login-dirswitch__btn").forEach(function (b) {
      b.setAttribute("aria-pressed", b.getAttribute("data-dir") === dir ? "true" : "false");
    });
  }
  app.querySelectorAll(".nias-login-dirswitch__btn").forEach(function (b) {
    b.addEventListener("click", function () { setDir(b.getAttribute("data-dir")); });
  });
  setDir(LS.get("nias_dir", "a"));

  /* -------- nias-login-rail pin -------- */
  var pinBtn = app.querySelector(".nias-login-rail__pin");
  function setPin(on) { app.classList.toggle("nias-login-rail-pinned", on); LS.set("nias_pin", on ? "1" : "0"); }
  if (pinBtn) pinBtn.addEventListener("click", function () { setPin(!app.classList.contains("nias-login-rail-pinned")); });
  setPin(LS.get("nias_pin", "0") === "1");

  /* -------- restore last tab -------- */
  var savedTab = LS.get("nias_tab", "nsdashboard");
  if (document.getElementById(savedTab)) niasSetTab(savedTab);

  /* -------- notification drawer -------- */
  var nTrigger = document.getElementById("nsnotif-trigger");
  var nDrawer = document.getElementById("nsnotif-panel");
  var nOverlay = document.getElementById("nsnotif-overlay");
  var nClose = document.getElementById("nsnotif-close");
  function closeDrawer() {
    if (nDrawer) nDrawer.classList.remove("open");
    if (nOverlay) nOverlay.classList.remove("open");
  }
  if (nTrigger && nDrawer && nOverlay) {
    nTrigger.addEventListener("click", function () {
      nDrawer.classList.add("open");
      nOverlay.classList.add("open");
    });
  }
  if (nClose) nClose.addEventListener("click", closeDrawer);
  if (nOverlay) nOverlay.addEventListener("click", closeDrawer);

  /* -------- generic nias-login-reveal helper -------- */
  function bindReveal(triggerId, targetId) {
    var t = document.getElementById(triggerId);
    var tgt = document.getElementById(targetId);
    if (!t || !tgt) return;
    function sync() { tgt.hidden = !t.checked; }
    t.addEventListener("change", sync);
    sync();
  }

  /* -------- password structure (either of the two password toggles) -------- */
  function syncPwStructure() {
    var a = document.getElementById("nias_password_activate");
    var b = document.getElementById("nias_password_otp_activate");
    var sec = document.getElementById("nias-password-structure-section");
    if (!sec) return;
    sec.hidden = !((a && a.checked) || (b && b.checked));
  }
  ["nias_password_activate", "nias_password_otp_activate"].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("change", function () {
      if (id === "nias_password_activate" && el.checked) {
        var o = document.getElementById("nias_password_otp_activate"); if (o) o.checked = false;
      }
      if (id === "nias_password_otp_activate" && el.checked) {
        var o = document.getElementById("nias_password_activate"); if (o) o.checked = false;
      }
      syncPwStructure();
    });
  });
  syncPwStructure();

  /* -------- email method mutual exclusivity -------- */
  ["nias_email_activate", "nias_email_phone_activate"].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("change", function () {
      if (el.checked) {
        var other = id === "nias_email_activate" ? "nias_email_phone_activate" : "nias_email_activate";
        var o = document.getElementById(other); if (o) o.checked = false;
      }
    });
  });

  /* -------- two-way toggle → hidden radio sync -------- */
  app.querySelectorAll('.nias-login-toggle-input[data-sync]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var name = cb.getAttribute('data-sync');
      var val  = cb.checked ? cb.getAttribute('data-on') : cb.getAttribute('data-off');
      var radio = app.querySelector('input[name="' + name + '"][value="' + val + '"]');
      if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  });

  /* -------- email gateway → SMTP nias-login-reveal -------- */
  (function () {
    var smtp = document.getElementById("smtp-settings");
    if (!smtp) return;
    function sync() {
      var checked = app.querySelector('input[name="nias_email_gateway"]:checked');
      smtp.hidden = !checked || checked.value !== "smtp";
    }
    app.querySelectorAll('input[name="nias_email_gateway"]').forEach(function (r) {
      r.addEventListener("change", sync);
    });
    sync();
  })();

  /* -------- security mode nias-login-reveal -------- */
  (function () {
    var radios = app.querySelectorAll('input[name="nias_security_mode"]');
    if (!radios.length) return;
    function sync() {
      var mode = app.querySelector('input[name="nias_security_mode"]:checked');
      if (!mode) return;
      var isBlock = mode.value === "block";
      var block = document.getElementById("block-mode-settings");
      var cool = document.getElementById("cooldown-mode-settings");
      var bInfo = document.getElementById("block-mode-info");
      var cInfo = document.getElementById("cooldown-mode-info");
      if (block) block.hidden = !isBlock;
      if (cool) cool.hidden = isBlock;
      if (bInfo) bInfo.hidden = !isBlock;
      if (cInfo) cInfo.hidden = isBlock;
    }
    radios.forEach(function (r) { r.addEventListener("change", sync); });
    sync();
  })();

  /* -------- operator gateway field visibility -------- */
  (function () {
    // niasGatewayFields is injected by tab-operator.php as a <script> block.
    // Fall back to a sensible default so the page doesn't break if the var
    // isn't present (e.g. when another tab is active on first load).
    var GW_FIELDS = (typeof niasGatewayFields !== "undefined") ? niasGatewayFields : {};

    // Label overrides for the "pattern" field per gateway.
    var PATTERN_LABELS = {
      national_sms: "کد متن (کد قالب پترن)",
      negar_payam:  "کد متن (کد قالب پترن)",
      sms_ir:       "Template ID (شناسه قالب)",
      "smsir-new":  "Template ID (شناسه قالب)"
    };

    var opSelect = document.getElementById("nias_operator");
    if (!opSelect) return;

    function sync() {
      var val    = opSelect.value;
      // Default: show username + password + localnumber + smstext for unknown gateways.
      var fields = GW_FIELDS[val] || ["username", "password", "localnumber", "smstext"];

      app.querySelectorAll("[data-gw]").forEach(function (el) {
        el.hidden = fields.indexOf(el.getAttribute("data-gw")) === -1;
      });

      var pl = document.getElementById("pattern-label");
      if (pl) {
        pl.textContent = PATTERN_LABELS[val] || "کد پترن";
      }
    }

    opSelect.addEventListener("change", sync);
    sync();
  })();

  /* -------- design elementor shortcode reveals -------- */
  bindReveal("nias_desginform_toggle", "nias-shortcode-part");
  (function () {
    var t = document.getElementById("nias_desginform_toggle");
    var tgt = document.getElementById("nias-default-style-card");
    if (!t || !tgt) return;
    function sync() { tgt.hidden = t.checked; }
    t.addEventListener("change", sync);
    sync();
  })();
  (function () {
    var t = document.getElementById("nias_fastsell_elementory_toggle");
    var tgt = document.getElementById("nias-fastsell-shortcode-design-wrapper");
    if (!t || !tgt) return;
    function sync() {
      tgt.hidden = !t.checked;
      if (!t.checked) {
        var scInput = document.getElementById("nias-fastsell-shortcode-design");
        if (scInput) scInput.value = "";
      }
    }
    t.addEventListener("change", sync);
    sync();
  })();

  /* -------- password eye toggles -------- */
  app.querySelectorAll(".nias-login-input-eye").forEach(function (eyeBtn) {
    eyeBtn.addEventListener("click", function () {
      var inp = document.getElementById(eyeBtn.getAttribute("data-target"));
      if (!inp) return;
      var open = eyeBtn.querySelector(".nias-login-eye-open");
      var closed = eyeBtn.querySelector(".nias-login-eye-closed");
      if (inp.type === "password") {
        inp.type = "text";
        if (open) open.hidden = true;
        if (closed) closed.hidden = false;
      } else {
        inp.type = "password";
        if (open) open.hidden = false;
        if (closed) closed.hidden = true;
      }
    });
  });
});

/* -------------------------------------------------------------------------- */
/*                          پیش‌نمایش ساختار پسورد                            */
/* -------------------------------------------------------------------------- */
jQuery(document).ready(function ($) {
  function generatePasswordPreview() {
    var length = parseInt($("#nias_password_length").val()) || 12;
    var includeUppercase = $('input[name="nias_password_include_uppercase"]').is(":checked");
    var includeLowercase = $('input[name="nias_password_include_lowercase"]').is(":checked");
    var includeNumbers = $('input[name="nias_password_include_numbers"]').is(":checked");
    var includeSpecial = $('input[name="nias_password_include_special"]').is(":checked");
    var prefix = $("#nias_password_prefix").val() || "";

    var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var lowercase = "abcdefghijklmnopqrstuvwxyz";
    var numbers = "0123456789";
    var special = "!@#$%^&*";

    var allChars = "";
    var password = "";
    var requiredChars = 0;

    if (includeUppercase) { allChars += uppercase; password += uppercase.charAt(Math.floor(Math.random() * uppercase.length)); requiredChars++; }
    if (includeLowercase) { allChars += lowercase; password += lowercase.charAt(Math.floor(Math.random() * lowercase.length)); requiredChars++; }
    if (includeNumbers)   { allChars += numbers;   password += numbers.charAt(Math.floor(Math.random() * numbers.length)); requiredChars++; }
    if (includeSpecial)   { allChars += special;   password += special.charAt(Math.floor(Math.random() * special.length)); requiredChars++; }

    if (allChars === "") {
      allChars = uppercase + lowercase + numbers + special;
      password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
      password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
      password += numbers.charAt(Math.floor(Math.random() * numbers.length));
      password += special.charAt(Math.floor(Math.random() * special.length));
      requiredChars = 4;
    }

    var prefixLength = prefix.length;
    var remainingLength = Math.max(0, length - prefixLength - requiredChars);
    for (var i = 0; i < remainingLength; i++) {
      password += allChars.charAt(Math.floor(Math.random() * allChars.length));
    }
    password = password.split("").sort(function () { return 0.5 - Math.random(); }).join("");
    $("#nias-password-preview").text(prefix + password);
  }

  $("#nias_password_length, #nias_password_prefix").on("nias-login-input", generatePasswordPreview);
  $('input[name="nias_password_include_uppercase"], nias-login-input[name="nias_password_include_lowercase"], nias-login-input[name="nias_password_include_numbers"], nias-login-input[name="nias_password_include_special"]').on("change", generatePasswordPreview);
  $("#nias-generate-preview").on("click", function (e) { e.preventDefault(); generatePasswordPreview(); });
  if ($("#nias-password-preview").length) generatePasswordPreview();
});

/* -------------------------------------------------------------------------- */
/*         Unsaved-changes detector for the "test send" actions               */
/* -------------------------------------------------------------------------- */
/* دکمه‌های تست، پیامک/ایمیل را با مقادیر ذخیره‌شده در دیتابیس می‌فرستند نه با
   چیزی که در فرم تایپ شده. اگر کاربر فیلدی را عوض کرده و ذخیره نکرده باشد،
   نتیجه تست گمراه‌کننده است؛ پس قبل از ارسال باید جلویش گرفته شود.

   یک المان «تغییرکرده» حساب می‌شود وقتی مقدار فعلی‌اش هم با مقدار لحظه‌ی
   بارگذاری صفحه فرق داشته باشد و هم با مقداری که سرور رندر کرده (defaultValue).
   شرط دوم جلوی هشدارهای الکی را می‌گیرد: اسکریپت‌های خود تب‌ها هنگام لود بعضی
   فیلدها را مقداردهی/نرمال می‌کنند و مرورگر هم ممکن است فیلد رمز را autofill کند. */
(function () {
  var snapshot = [];
  var ready = false;

  function formEl() {
    return document.querySelector('form[action="options.php"]');
  }

  function controls() {
    var form = formEl();
    if (!form) return [];
    var els = form.querySelectorAll('input[name], select[name], textarea[name]');
    return Array.prototype.filter.call(els, function (el) {
      return !el.disabled && el.type !== 'submit' && el.type !== 'button';
    });
  }

  function currentValue(el) {
    if (el.type === 'checkbox' || el.type === 'radio') return el.checked ? '1' : '0';
    if (el.options && el.multiple) {
      return Array.prototype.filter.call(el.options, function (o) { return o.selected; })
        .map(function (o) { return o.value; }).join('');
    }
    return el.value;
  }

  function serverValue(el) {
    if (el.type === 'checkbox' || el.type === 'radio') return el.defaultChecked ? '1' : '0';
    if (el.options) {
      var def = Array.prototype.filter.call(el.options, function (o) { return o.defaultSelected; });
      if (el.multiple) return def.map(function (o) { return o.value; }).join('');
      if (def.length) return def[0].value;
      return el.options.length ? el.options[0].value : '';
    }
    return el.defaultValue;
  }

  function capture() {
    snapshot = controls().map(function (el) {
      return { el: el, value: currentValue(el) };
    });
    ready = true;
  }

  // بعد از load تا اسکریپت‌های تب‌ها مقداردهی اولیه‌شان را تمام کرده باشند
  if (document.readyState === 'complete') {
    setTimeout(capture, 0);
  } else {
    window.addEventListener('load', function () { setTimeout(capture, 0); });
  }

  /* filter: یا سلکتور ظرف (مثل '#nswcsms') یا آرایه‌ای از نام فیلدها */
  function inScope(el, filter) {
    if (typeof filter === 'string') {
      return !!(el.closest && el.closest(filter));
    }
    if (Object.prototype.toString.call(filter) === '[object Array]') {
      var base = String(el.name || '').replace(/\[.*$/, '');
      return filter.indexOf(el.name) !== -1 || filter.indexOf(base) !== -1;
    }
    return false;
  }

  /**
   * آیا فیلدهای مشمول filter بعد از آخرین ذخیره تغییر کرده‌اند؟
   */
  window.niasSettingsDirty = function (filter) {
    if (!ready || !filter) return false;

    var counted = 0;
    for (var i = 0; i < snapshot.length; i++) {
      var item = snapshot[i];
      if (!inScope(item.el, filter)) continue;
      if (!document.contains(item.el)) return true; // فیلد حذف شده (مثل حذف یک ردیف قانون)
      var now = currentValue(item.el);
      if (now !== item.value && now !== serverValue(item.el)) return true;
      counted++;
    }

    // فیلد تازه اضافه‌شده (مثل افزودن ردیف قانون جدید)
    var live = controls().filter(function (el) { return inScope(el, filter); });
    return live.length !== counted;
  };
})();

/* -------------------------------------------------------------------------- */
/*                       Operator Tab — Gateway Test Send                     */
/* -------------------------------------------------------------------------- */
jQuery(document).ready(function ($) {
  // فیلدهایی که نتیجه هر تست به آن‌ها وابسته است
  var NIAS_SMS_FIELDS = [
    'nias_operator', 'nias_username', 'nias_password', 'nias_localnumber',
    'nias_api', 'nias_pattern', 'nias_var1', 'nias_var2', 'nias_sms_text'
  ];
  var NIAS_TEST_SCOPE = {
    otp_sms:  NIAS_SMS_FIELDS,
    pass_sms: NIAS_SMS_FIELDS.concat(['nias_password_pattern', 'nias_password_var']),
    bale:     ['nias_bale_activate', 'nias_bale_botid', 'nias_bale_api'],
    email:    '#nsemail'
  };

  function niasTestResult(ok, msg) {
    var bg = ok ? 'rgba(52,211,153,.12)' : 'rgba(248,113,113,.12)';
    var cl = ok ? 'var(--success)' : 'var(--danger)';
    $('#nias-test-result')
      .html('<div style="padding:12px 16px;border-radius:var(--radius-sm);background:' + bg + ';color:' + cl + ';font-size:13px;">' + msg + '</div>')
      .show();
  }

  $('[data-test]').on('click', function () {
    var type    = $(this).data('test');
    var phone   = $('#nias_test_phone').val().trim();
    var email   = $('#nias_test_email').val().trim();
    var $result = $('#nias-test-result');
    var $spin   = $('#nias-test-spinner');

    // تست با مقادیر ذخیره‌شده انجام می‌شود؛ با تغییرات ذخیره‌نشده نتیجه‌اش بی‌معنی است
    if (typeof window.niasSettingsDirty === 'function' && window.niasSettingsDirty(NIAS_TEST_SCOPE[type])) {
      niasTestResult(false, 'ابتدا تغییرات را ذخیره کنید — تست با مقادیر ذخیره‌شده انجام می‌شود، نه مقادیری که هنوز ذخیره نکرده‌اید.');
      return;
    }

    $result.hide();
    $spin.show();
    $('[data-test]').prop('disabled', true);

    $.ajax({
      url:      niasAdminData.ajaxurl,
      type:     'POST',
      dataType: 'text',
      data: { action: 'nias_test_gateway', nonce: niasAdminData.nonce, type: type, phone: phone, email: email },
      complete: function () { $spin.hide(); $('[data-test]').prop('disabled', false); },
      success: function (raw) {
        var r = null;
        try { var s = raw.substring(raw.indexOf('{')); r = JSON.parse(s.substring(0, s.lastIndexOf('}') + 1)); } catch (e) {}
        if (r && r.success && r.data) {
          var ok = r.data.ok;
          niasTestResult(ok, r.data.msg || (ok ? 'ارسال موفق ✓' : 'ارسال ناموفق'));
        } else if (r && !r.success) {
          niasTestResult(false, r.data || 'خطا');
        }
      }
    });
  });
});

/* -------------------------------------------------------------------------- */
/*                         Database Tab Management                            */
/* -------------------------------------------------------------------------- */
jQuery(document).ready(function ($) {
  var currentPage = 1;
  var perPage = 10;
  var totalPages = 1;

  /* safely extract JSON from a WordPress AJAX response that may have
     extra PHP output (notices/warnings) prepended before the JSON */
  function wpParse(raw) {
    var s = typeof raw === 'string' ? raw : '';
    var i = s.indexOf('{');
    var j = s.lastIndexOf('}');
    if (i === -1 || j === -1) return null;
    try { return JSON.parse(s.substring(i, j + 1)); } catch (e) { return null; }
  }

  function niasPost(args) {
    $.ajax({
      url:      niasAdminData.ajaxurl,
      type:     'POST',
      dataType: 'text',
      data:     args.data,
      beforeSend: args.before || $.noop,
      complete: args.after   || $.noop,
      success: function (raw) {
        var r = wpParse(raw);
        if (!r) { args.fail && args.fail('پاسخ نامعتبر از سرور'); return; }
        if (r.success) { args.ok && args.ok(r.data); }
        else           { args.fail && args.fail(r.data || 'خطای سرور'); }
      },
      error: function (xhr) {
        var r = wpParse(xhr.responseText);
        if (r && r.success !== undefined) {
          if (r.success) { args.ok && args.ok(r.data); }
          else           { args.fail && args.fail(r.data || 'خطای سرور'); }
        } else {
          args.fail && args.fail('خطا در ارتباط با سرور (' + xhr.status + ')');
        }
      }
    });
  }

  $("#load-blocked-ips").on("click", function () { loadBlockedIPs(1); });

  $("#clear-blocked-ips").on("click", function () {
    if (!confirm("آیا مطمئن هستید که می‌خواهید تمام آیپی های بلاک شده را پاک کنید؟")) return;
    niasPost({
      data:   { action: "nias_clear_blocked_ips", nonce: niasAdminData.nonce },
      before: function () { $("#clear-blocked-ips").prop("disabled", true).text("در حال پاکسازی..."); },
      after:  function () { $("#clear-blocked-ips").prop("disabled", false).text("پاکسازی همه آیپی‌ها"); },
      ok:     function () { alert("تمام آیپی ها با موفقیت پاک شدند"); $("#blocked-ips-container").hide(); },
      fail:   function (msg) { alert("خطا: " + msg); }
    });
  });

  $("#load-recent-codes").on("click", function () { loadRecentCodes(); });

  function loadBlockedIPs(page) {
    currentPage = page;
    niasPost({
      data: { action: "nias_get_blocked_ips", page: page, per_page: perPage, nonce: niasAdminData.nonce },
      before: function () {
        $("#blocked-ips-container").show();
        $("#blocked-ips-table").html('<div class="nias-loading">در حال بارگذاری...</div>');
        $("#blocked-ips-pagination").html("");
      },
      ok: function (data) {
        if (data.items.length === 0) {
          $("#blocked-ips-table").html('<div class="nias-empty-state"><p>هیچ آیپی بلاک شده‌ای وجود ندارد</p></div>');
          return;
        }
        var html = '<div class="nias-db-table"><table><thead><tr><th>ردیف</th><th>آدرس IP</th><th>تاریخ بلاک</th><th>وضعیت</th></tr></thead><tbody>';
        data.items.forEach(function (item, index) {
          var rowNum = (currentPage - 1) * perPage + index + 1;
          html += "<tr><td>" + rowNum + '</td><td dir="ltr" style="text-align:right;">' + item.ip + "</td><td>" + item.time + "</td><td>" +
            (item.block == 1 ? '<span style="color:#f87171;">بلاک شده</span>' : '<span style="color:#34d399;">فعال</span>') + "</td></tr>";
        });
        html += "</tbody></table></div>";
        $("#blocked-ips-table").html(html);
        totalPages = data.total_pages;
        renderPagination(data.total_pages, currentPage);
      },
      fail: function (msg) { $("#blocked-ips-table").html('<div class="nias-empty-state"><p>' + msg + '</p></div>'); }
    });
  }

  function loadRecentCodes() {
    niasPost({
      data: { action: "nias_get_recent_codes", nonce: niasAdminData.nonce },
      before: function () {
        $("#recent-codes-container").show();
        $("#recent-codes-table").html('<div class="nias-loading">در حال بارگذاری...</div>');
      },
      ok: function (data) {
        if (data.length === 0) {
          $("#recent-codes-table").html('<div class="nias-empty-state"><p>هیچ لاگ ارسالی ثبت نشده است</p></div>');
          return;
        }
        var html = '<div class="nias-db-table"><table><thead><tr><th>ردیف</th><th>شماره/ایمیل</th><th>کد</th><th>روش ارسال</th><th>نوع کاربر</th><th>IP</th><th>تلاش‌ها</th><th>تاریخ ارسال</th><th>تاریخ تایید</th><th>وضعیت</th></tr></thead><tbody>';
        data.forEach(function (item, index) {
          var identifier = item.phone !== "-" ? item.phone : item.email;
          html += "<tr><td>" + (index + 1) + '</td><td dir="ltr" style="text-align:right;">' + identifier +
            '</td><td><code style="background:rgba(255,255,255,0.08);padding:2px 8px;border-radius:4px;font-weight:600;">' + item.code + "</code></td>" +
            '<td><span class="nias-dash-badge nias-dash-badge--blue">' + item.send_method + "</span></td><td>" + item.user_status +
            '</td><td dir="ltr" style="text-align:right;font-size:11px;">' + item.ip + '</td><td style="text-align:center;">' + item.attempts +
            '</td><td style="font-size:11px;">' + item.created_at + '</td><td style="font-size:11px;">' + item.verified_at +
            '</td><td><span style="color:' + item.status_color + ';">' + item.status + "</span></td></tr>";
        });
        html += "</tbody></table></div>";
        $("#recent-codes-table").html(html);
      },
      fail: function (msg) { $("#recent-codes-table").html('<div class="nias-empty-state"><p>' + msg + '</p></div>'); }
    });
  }

  function renderPagination(totalPages, currentPage) {
    if (totalPages <= 1) { $("#blocked-ips-pagination").html(""); return; }
    var html = '<div class="nias-pagination">';
    html += "<button " + (currentPage === 1 ? "disabled" : "") + ' onclick="loadBlockedIPsPage(' + (currentPage - 1) + ')">قبلی</button>';
    for (var i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
        html += '<button class="' + (i === currentPage ? "active" : "") + '" onclick="loadBlockedIPsPage(' + i + ')">' + i + "</button>";
      } else if (i === currentPage - 3 || i === currentPage + 3) {
        html += "<span>...</span>";
      }
    }
    html += "<button " + (currentPage === totalPages ? "disabled" : "") + ' onclick="loadBlockedIPsPage(' + (currentPage + 1) + ')">بعدی</button>';
    html += "</div>";
    $("#blocked-ips-pagination").html(html);
  }

  window.loadBlockedIPsPage = function (page) { loadBlockedIPs(page); };

  $("#migrate-meta-data").on("click", function () {
    var oldMetaKey = $("#old_meta_key").val().trim();
    if (!oldMetaKey) { alert("لطفاً نام فیلد متای قدیمی را وارد کنید"); return; }
    if (!confirm('آیا مطمئن هستید که می‌خواهید فیلد "' + oldMetaKey + '" را به "phone" تبدیل کنید؟')) return;
    niasPost({
      data:   { action: "nias_migrate_meta", old_meta_key: oldMetaKey, nonce: niasAdminData.nonce },
      before: function () { $("#migrate-meta-data").prop("disabled", true).text("در حال انتقال..."); $("#migration-result").html(""); },
      after:  function () { $("#migrate-meta-data").prop("disabled", false).text("انتقال داده‌ها"); },
      ok:   function (data) {
        $("#migration-result").html('<div class="nias-login-note nias-login-note--success" style="margin-top:6px"><span>✓ ' + data.message + "</span></div>");
        $("#old_meta_key").val("");
      },
      fail: function (msg) {
        $("#migration-result").html('<div class="nias-login-note nias-login-note--warn" style="margin-top:6px"><span>✗ ' + msg + "</span></div>");
      }
    });
  });

  $("#nias-repair-tables").on("click", function () {
    var $btn = $(this);
    var orig = $btn.html();
    if (!confirm("بررسی و ترمیم جداول دیتابیس انجام شود؟ این عملیات هیچ داده‌ای را حذف نمی‌کند.")) return;
    niasPost({
      data:   { action: "nias_repair_tables", nonce: niasAdminData.nonce },
      before: function () { $btn.prop("disabled", true).text("در حال بررسی..."); $("#nias-repair-result").html(""); },
      after:  function () { $btn.prop("disabled", false).html(orig); },
      ok:     function (data) { $("#nias-repair-result").html(data.message || ""); },
      fail:   function (msg) {
        $("#nias-repair-result").html('<div class="nias-login-note nias-login-note--warn" style="margin-top:6px"><span>✗ ' + msg + "</span></div>");
      }
    });
  });

  /* ---------------- بررسی و اصلاح شماره‌های نامعتبر ---------------- */

  function niasEsc(v) {
    return String(v == null ? "" : v).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function niasNote(type, msg) {
    return '<div class="nias-login-note nias-login-note--' + type + '" style="margin-top:6px"><span>' + msg + "</span></div>";
  }

  function renderInvalidSamples(data) {
    var html = "";
    if (data.prefixes && data.prefixes.length) {
      html += '<div style="margin-bottom:10px;font-size:13px;color:var(--text-muted)">پرتکرارترین ۴ رقم ابتدایی: ';
      html += data.prefixes.map(function (p) {
        return '<code class="nias-login-mono">' + niasEsc(p.prefix) + "</code> (" + p.count + ")";
      }).join("، ");
      html += "</div>";
    }
    if (!data.samples || !data.samples.length) {
      return html;
    }
    html += '<div class="nias-db-table"><table><thead><tr><th>شناسه کاربر</th><th>مقدار خام</th><th>طول</th><th>اصلاح دستی</th></tr></thead><tbody>';
    data.samples.forEach(function (s) {
      html += '<tr data-user-id="' + niasEsc(s.user_id) + '">' +
        "<td>" + niasEsc(s.user_id) + "</td>" +
        "<td>" + niasEsc(s.raw) + "</td>" +
        "<td>" + niasEsc(s.length) + "</td>" +
        '<td><div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">' +
          '<input type="text" class="nias-login-input nias-login-input--mono nias-manual-phone" value="' + niasEsc(s.digits) + '" style="max-width:150px;padding:6px 8px">' +
          '<button type="button" class="nias-login-btn nias-manual-save" style="padding:6px 10px">ذخیره</button>' +
          '<span class="nias-manual-status" style="font-size:12px"></span>' +
        "</div></td>" +
      "</tr>";
    });
    html += "</tbody></table></div>";
    if (data.invalid_count > data.samples.length) {
      html += '<div style="margin-top:6px;font-size:12px;color:var(--text-faint)">نمایش ' + data.samples.length + " نمونه از " + data.invalid_count + " مورد.</div>";
    }
    return html;
  }

  function renderFixSamples(data) {
    if (!data.samples || !data.samples.length) return "";
    var html = '<div class="nias-db-table"><table><thead><tr><th>شناسه کاربر</th><th>قبل</th><th>بعد</th><th>نتیجه</th></tr></thead><tbody>';
    data.samples.forEach(function (s) {
      html += "<tr><td>" + niasEsc(s.user_id) + '</td><td class="nias-login-mono">' + niasEsc(s.before) + '</td><td class="nias-login-mono">' + niasEsc(s.after) + "</td><td>" + (s.ok ? "✓ معتبر" : "✗ نامعتبر") + "</td></tr>";
    });
    html += "</tbody></table></div>";
    return html;
  }

  $("#scan-invalid-phones").on("click", function () {
    var $btn = $(this);
    var orig = $btn.html();
    var metaKey = ($("#invalid_meta_key").val() || "").trim() || "phone";
    niasPost({
      data:   { action: "nias_preview_invalid_phones", meta_key: metaKey, nonce: niasAdminData.nonce },
      before: function () { $btn.prop("disabled", true).text("در حال بررسی..."); $("#invalid-phones-summary").html(""); $("#invalid-phones-samples").html(""); $("#invalid-fix-panel").hide(); },
      after:  function () { $btn.prop("disabled", false).html(orig); },
      ok: function (data) {
        if (data.invalid_count === 0) {
          $("#invalid-phones-summary").html(niasNote("success", "✓ همه‌ی " + data.total + " شماره در فیلد «" + niasEsc(data.meta_key) + "» معتبر هستند."));
          return;
        }
        $("#invalid-phones-summary").html(niasNote("warn", "از مجموع " + data.total + " رکورد، <strong>" + data.invalid_count + "</strong> شماره نامعتبر است."));
        $("#invalid-phones-samples").html(renderInvalidSamples(data));
        $("#invalid-fix-panel").show();
      },
      fail: function (msg) { $("#invalid-phones-summary").html(niasNote("warn", "✗ " + msg)); }
    });
  });

  function runFixInvalid(dryRun) {
    var metaKey = ($("#invalid_meta_key").val() || "").trim() || "phone";
    var strip   = parseInt($("#fix_strip_count").val(), 10) || 0;
    var prefix  = ($("#fix_add_prefix").val() || "").trim();
    if (strip === 0 && prefix === "") { alert("حداقل یک تغییر را مشخص کنید: افزودن پیش‌شماره یا حذف ارقام ابتدایی."); return; }
    if (!dryRun && !confirm("تغییرات روی شماره‌های نامعتبر اعمال و در فیلد phone ذخیره شود؟ بهتر است پیش از این کار از دیتابیس بکاپ بگیرید.")) return;

    var $preview = $("#preview-fix-invalid");
    var $apply   = $("#apply-fix-invalid");
    niasPost({
      data:   { action: "nias_fix_invalid_phones", meta_key: metaKey, strip_count: strip, add_prefix: prefix, dry_run: dryRun ? 1 : 0, nonce: niasAdminData.nonce },
      before: function () { $preview.prop("disabled", true); $apply.prop("disabled", true); $("#invalid-fix-result").html('<div class="nias-loading">در حال پردازش...</div>'); },
      after:  function () { $preview.prop("disabled", false); $apply.prop("disabled", false); },
      ok: function (data) {
        var note = niasNote(data.fixed > 0 ? "success" : "warn", (data.dry_run ? "👁 " : "✓ ") + data.message);
        $("#invalid-fix-result").html(note + renderFixSamples(data));
        if (!data.dry_run) {
          // بازخوانی وضعیت پس از ذخیره
          $("#scan-invalid-phones").trigger("click");
        }
      },
      fail: function (msg) { $("#invalid-fix-result").html(niasNote("warn", "✗ " + msg)); }
    });
  }

  $("#preview-fix-invalid").on("click", function () { runFixInvalid(true); });
  $("#apply-fix-invalid").on("click", function () { runFixInvalid(false); });

  // اصلاح دستی هر ردیف
  $("#invalid-phones-samples").on("click", ".nias-manual-save", function () {
    var $btn    = $(this);
    var $row    = $btn.closest("tr");
    var userId  = $row.data("user-id");
    var $input  = $row.find(".nias-manual-phone");
    var $status = $row.find(".nias-manual-status");
    var phone   = ($input.val() || "").trim();
    if (!phone) { $status.css("color", "var(--danger, #d33)").text("شماره را وارد کنید"); return; }

    niasPost({
      data:   { action: "nias_save_single_phone", user_id: userId, phone: phone, nonce: niasAdminData.nonce },
      before: function () { $btn.prop("disabled", true).text("..."); $status.css("color", "var(--text-muted)").text(""); },
      after:  function () { $btn.prop("disabled", false).text("ذخیره"); },
      ok: function (data) {
        $input.val(data.phone);
        $status.css("color", data.valid ? "var(--success, #2a9d3f)" : "var(--warn, #c47f00)")
               .text(data.valid ? "✓ ذخیره شد" : "⚠ ذخیره شد (نامعتبر)");
      },
      fail: function (msg) { $status.css("color", "var(--danger, #d33)").text("✗ " + msg); }
    });
  });

  // Enter در فیلد اصلاح دستی = کلیک روی ذخیره
  $("#invalid-phones-samples").on("keydown", ".nias-manual-phone", function (e) {
    if (e.which === 13) { e.preventDefault(); $(this).closest("tr").find(".nias-manual-save").trigger("click"); }
  });
});

/* -------------------------------------------------------------------------- */
/*                       خرید سریع — مرتب‌سازی فیلدها                        */
/* -------------------------------------------------------------------------- */
jQuery(document).ready(function ($) {
  var $sortable = $("#nias-fastsell-fields-sortable");

  function initFastsellSortable() {
    if (!$sortable.length) return;
    if ($sortable.hasClass("ui-sortable")) return;
    if (!$.fn.sortable) return;
    $sortable.sortable({
      handle: ".nias-fastsell-drag-handle",
      axis: "y",
      cursor: "grabbing",
      opacity: 0.8,
      placeholder: "nias-fastsell-sort-placeholder",
      forcePlaceholderSize: true,
      update: function () {
        $sortable.find("li").each(function (index) {
          $(this).find(".nias-fastsell-order-input").val(index);
        });
      }
    });
  }

  $(document).on("click", '[onclick*="nsfastsell"]', function () { setTimeout(initFastsellSortable, 50); });
  if ($("#nsfastsell").hasClass("active")) initFastsellSortable();

  // when visibility is turned off, also turn off required
  $(document).on("change", ".nias-fastsell-visible-cb", function () {
    var fieldKey = $(this).data("field");
    if (!$(this).is(":checked")) {
      $("#nias_fastsell_required_" + fieldKey).prop("checked", false);
    }
  });

  // باکس درگاه‌ها و روش‌های حمل خرید تکی — فقط وقتی تاگل فعال است نمایش داده شود
  $(document).on("change", "#nias_fastsell_buy_now", function () {
    if ($(this).is(":checked")) {
      $("#nias-fastsell-buy-now-box").slideDown(200);
    } else {
      $("#nias-fastsell-buy-now-box").slideUp(150);
    }
  });

  /* -------- ساخت خودکار قالب المنتور --------
     یک قالب ذخیره‌شده با ویجت مربوطه می‌سازد، شناسه‌اش را در همان فیلد
     شورت‌کد/شناسه می‌گذارد و ویرایشگر المنتور را در تب تازه باز می‌کند.
     فرم تنظیمات دست‌نخورده می‌ماند؛ کاربر باید خودش ذخیره کند. */
  $(document).on("click", ".nias-elementor-create-template", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var $status = $btn.siblings(".nias-elementor-create-template-status");
    var targetId = $btn.data("target");

    if ($btn.prop("disabled")) return;
    $btn.prop("disabled", true);
    $status.css("color", "").text("در حال ساخت قالب...");

    $.post(niasAdminData.ajaxurl, {
      action: "nias_elementor_create_template",
      type: $btn.data("type"),
      nonce: niasAdminData.templateNonce,
    })
      .done(function (res) {
        if (res && res.success && res.data) {
          var $input = $("#" + targetId);
          $input.val(res.data.id).trigger("change");
          $status
            .css("color", "#15803d")
            .text("قالب ساخته شد (شناسه " + res.data.id + ") — تنظیمات را ذخیره کنید.");

          if (res.data.edit_url) {
            window.open(res.data.edit_url, "_blank", "noopener");
          }
        } else {
          var msg = res && res.data && res.data.message ? res.data.message : "ساخت قالب انجام نشد.";
          $status.css("color", "#b45309").text(msg);
        }
      })
      .fail(function () {
        $status.css("color", "#b45309").text("خطا در ارتباط با سرور.");
      })
      .always(function () {
        $btn.prop("disabled", false);
      });
  });
});
