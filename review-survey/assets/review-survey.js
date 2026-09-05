(function ($) {
    'use strict';

    var cfg = window.niasReviewSurvey || {};
    var closable = parseInt(cfg.closable, 10) === 1;

    $(function () {
        var $overlay = $('#nias-rs-overlay');
        if (!$overlay.length) return;

        /* ── باز/بسته کردن کارت با کلیک روی ردیف ───────────────────────── */
        $overlay.on('click', '.nias-rs-card__row', function () {
            var $card = $(this).closest('.nias-rs-card');
            if ($card.hasClass('nias-rs-done')) return;
            $card.toggleClass('nias-rs-open');
            if ($card.hasClass('nias-rs-open')) {
                $card.find('.nias-rs-text').focus();
            }
        });

        /* ── امتیازدهی ستاره‌ای ─────────────────────────────────────────── */
        $overlay.on('click', '.nias-rs-star', function () {
            var $star = $(this);
            var value = parseInt($star.data('value'), 10);
            var $stars = $star.closest('.nias-rs-stars');
            $stars.data('rating', value);
            $stars.find('.nias-rs-star').each(function () {
                var v = parseInt($(this).data('value'), 10);
                $(this).toggleClass('nias-rs-on', v <= value);
            });
        });

        /* ── ثبت نظر ────────────────────────────────────────────────────── */
        $overlay.on('click', '.nias-rs-submit', function () {
            var $btn = $(this);
            var $card = $btn.closest('.nias-rs-card');
            var $msg = $card.find('.nias-rs-msg');
            var productId = $card.data('product');
            var rating = parseInt($card.find('.nias-rs-stars').data('rating'), 10) || 0;
            var text = $.trim($card.find('.nias-rs-text').val());

            $msg.removeClass('nias-rs-ok').text('');

            if (rating < 1) {
                $msg.text('لطفاً امتیاز ستاره را انتخاب کنید');
                return;
            }
            if (!text) {
                $msg.text('لطفاً متن نظر را بنویسید');
                return;
            }

            $btn.prop('disabled', true).text('در حال ثبت...');

            $.ajax({
                url: cfg.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'nias_review_survey_save',
                    nonce: cfg.nonce,
                    product_id: productId,
                    rating: rating,
                    text: text
                }
            }).done(function (res) {
                if (res && res.success) {
                    $msg.addClass('nias-rs-ok').text((res.data && res.data.message) || 'ثبت شد');
                    $card.removeClass('nias-rs-open').addClass('nias-rs-done');
                    $card.find('.nias-rs-card__body').slideUp(150);

                    var remaining = res.data ? parseInt(res.data.remaining, 10) : countRemaining();
                    if (remaining <= 0) {
                        // همه نظرها ثبت شد — صفحه را تازه کن تا مودال دیگر نمایش داده نشود
                        setTimeout(function () { window.location.reload(); }, 900);
                    }
                } else {
                    var m = (res && res.data && res.data.message) ? res.data.message : 'خطا در ثبت نظر';
                    $msg.text(m);
                    $btn.prop('disabled', false).text('ثبت نظر');
                }
            }).fail(function (xhr) {
                var m = 'خطا در ثبت نظر، مجدداً تلاش کنید';
                if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    m = xhr.responseJSON.data.message;
                }
                $msg.text(m);
                $btn.prop('disabled', false).text('ثبت نظر');
            });
        });

        /* ── بستن مودال ─────────────────────────────────────────────────── */
        function showModal() {
            $overlay.removeClass('nias-rs-hidden');
        }

        function closeModal() {
            $overlay.addClass('nias-rs-hidden');
        }

        if (closable) {
            $('#nias-rs-close').on('click', closeModal);

            // کلیک روی پس‌زمینه نیز مودال را می‌بندد
            $overlay.on('click', function (e) {
                if (e.target === this) closeModal();
            });
        }
    });
})(jQuery);
