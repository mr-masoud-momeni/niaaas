/**
 * سیستم توست نیاس — API صریح و مستقل از jQuery.
 *
 * چرا صریح؟
 * نسخه‌ی پیشین متنِ ناحیه‌های پیام (.nias-login-message و .nias-login-result) را با
 * MutationObserver رصد می‌کرد و هر تغییری را یک «پیام جدید» فرض می‌گرفت. اما پلاگین‌های
 * ثالثی که DOM را بازنویسی می‌کنند — فارسی‌سازِ اعداد، نیم‌فاصله‌گذار، تایپوگرافی —
 * همان لحظه‌ی لود صفحه متنِ ایستای «کد 5 رقمی ارسال شده را وارد نمایید» را به
 * «کد ۵ رقمی…» تبدیل می‌کردند؛ این یک mutation بود و باعث می‌شد توست بی‌مورد و بدون
 * هیچ کنشی از کاربر نمایش داده شود.
 *
 * حالا هیچ عنصری رصد نمی‌شود: توست فقط وقتی ساخته می‌شود که کد خودمان
 * window.niasToast() را صدا بزند. هیچ پلاگین دیگری نمی‌تواند توست تولید کند.
 *
 * استفاده:
 *   niasToast('پیام');                 // نوع به‌صورت خودکار حدس زده می‌شود
 *   niasToast('پیام', 'error');        // نوع: info | success | error | warning
 */
(function (w, d) {
    'use strict';

    /* اگر به هر دلیل دوبار لود شد (مثلاً ویجت المنتور + مودال)، نسخه‌ی اول بماند */
    if (w.niasToast) return;

    var ICONS       = { info: 'ℹ', success: '✓', error: '✕', warning: '⚠' };
    var LIFETIME    = 5000; // مدت نمایش هر توست
    var FADE        = 300;  // هم‌اندازه با transition در CSS
    var DEDUPE_MS   = 600;  // پیام یکسان در این بازه دوبار نمایش داده نمی‌شود
    var _last       = { key: '', ts: 0 };

    /**
     * یکدست‌سازی متن برای مقایسه: ارقام فارسی/عربی به لاتین، حذف نیم‌فاصله و
     * کاراکترهای جهت‌دهی، و فشرده‌سازی فاصله‌ها. تا اگر پلاگینی متن را صرفاً از نظر
     * ظاهری بازنویسی کرد، «پیام تکراری» به‌درستی تشخیص داده شود.
     */
    function normalize(s) {
        return String(s)
            .replace(/[۰-۹]/g, function (c) { return c.charCodeAt(0) - 0x06F0; })
            .replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 0x0660; })
            .replace(/[\u200B-\u200F\u202A-\u202E\uFEFF]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    /** حدس نوع توست از روی متن پیام */
    function guessType(text) {
        if (/خطا|اشتباه|نادرست|ناموفق|نشد|وجود ندارد|مسدود|منقضی|تکراری|یافت نشد|نامعتبر/.test(text)) return 'error';
        if (/موفق|صحیح|تایید شد|ارسال شد|وارد شدید|ثبت شد/.test(text)) return 'success';
        if (/تلاش|مجدد|دقیقه|ثانیه|صبر/.test(text)) return 'warning';
        return 'info';
    }

    function dismiss(t) {
        if (t._tid) { clearTimeout(t._tid); t._tid = null; }
        t.classList.remove('nias-toast--show');
        setTimeout(function () {
            if (t.parentNode) t.parentNode.removeChild(t);
        }, FADE);
    }

    function el(tag, cls, text) {
        var n = d.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function niasToast(msg, type) {
        if (msg == null) return;
        msg = String(msg).trim();
        if (!msg) return;

        /* ظرف توست فقط در قالب مودال و ویجت المنتور رندر می‌شود؛ در قالب‌های شورت‌کد
           که استایل توست را ندارند، پیام‌ها همان‌طور درون‌خطی نمایش داده می‌شوند */
        var wrap = d.getElementById('nias-toast-wrap');
        if (!wrap) return;

        var key = normalize(msg);
        var now = new Date().getTime();
        if (key === _last.key && now - _last.ts < DEDUPE_MS) return;
        _last = { key: key, ts: now };

        type = type || guessType(msg);

        var t = el('div', 'nias-toast nias-toast--' + type);
        var close = el('button', 'nias-toast__close', '×');
        close.type = 'button';
        close.setAttribute('aria-label', 'بستن');
        close.addEventListener('click', function () { dismiss(t); });

        t.appendChild(el('span', 'nias-toast__icon', ICONS[type] || ICONS.info));
        t.appendChild(el('span', 'nias-toast__msg', msg));
        t.appendChild(close);

        wrap.appendChild(t);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { t.classList.add('nias-toast--show'); });
        });
        t._tid = setTimeout(function () { dismiss(t); }, LIFETIME);
    }

    niasToast.guessType = guessType;
    niasToast.normalize = normalize;
    w.niasToast = niasToast;

    /* خطای بازگشتی از ورود با گوگل — تنها توستِ زمان لود، و فقط وقتی پارامتر وجود دارد */
    function showGoogleError() {
        try {
            var gErr = new URLSearchParams(w.location.search).get('nias_google_error');
            if (gErr) niasToast(gErr, 'error');
        } catch (e) { /* URLSearchParams unsupported */ }
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', showGoogleError);
    } else {
        showGoogleError();
    }
})(window, document);
