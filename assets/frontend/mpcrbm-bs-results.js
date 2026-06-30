/**
 * Branch Search — Results layout  (mpcrbm-bs-results.js)
 *
 * New layout after AJAX injection:
 *
 *   .mpcrbm_main_content
 *     .mpcrbm-bsr-topbar     ← booking summary strip (full width, top)
 *     .mpcrbm-bsr-body       ← two-column row below
 *       .mpcrbm_mainSection  ← car list (left)
 *       .mpcrbm-bsr-right    ← transport summary + extra service (right)
 *
 * ≤700 px: body becomes single column.
 */
(function ($) {
    'use strict';

    // ── Init ─────────────────────────────────────────────────────────

    $(function () {
        $('.mpcrbm-bs').each(function () {
            observeResults($(this));
        });
    });

    // ── Watch #mpcrbm_bs_cars for AJAX injection ──────────────────────

    function observeResults($wrap) {
        var carsEl = $wrap.find('#mpcrbm_bs_cars')[0];
        if (!carsEl) { return; }

        var mo = new MutationObserver(function () {
            var $area = $wrap.find('.mpcrbm_transport_search_area');
            if ($area.length && !$area.hasClass('mpcrbm-bsr-done')) {
                $area.addClass('mpcrbm-bsr-done');
                buildLayout($area);
                watchCheckout($area);
            }
        });
        mo.observe(carsEl, { childList: true });
    }

    // ── Rebuild .mpcrbm_main_content into the new 3-zone layout ──────

    function buildLayout($area) {
        var $mc = $area.find('.mpcrbm_main_content');
        if (!$mc.length || $mc.find('.mpcrbm-bsr-body').length) { return; }

        // Detach all four direct children
        var $leftSidebar = $mc.children('.mpcrbm_leftSidebar').detach();
        var $mainSection = $mc.children('.mpcrbm_mainSection').detach();
        var $carSummary  = $mc.children('.mpcrbm_transport_summary').detach();
        var $extraSvc    = $mc.children('.mpcrbm_extra_service').detach();

        // 1. Top bar — booking summary (horizontal strip)
        var $topbar = $('<div class="mpcrbm-bsr-topbar">').append($leftSidebar);

        // 2. Right column — selected-car summary + extra services + Book Now
        var $right = $('<div class="mpcrbm-bsr-right">').append($carSummary).append($extraSvc);

        // 3. Body row — car list (left) + right panel
        var $body = $('<div class="mpcrbm-bsr-body">').append($mainSection).append($right);

        // Rebuild: topbar first, then body
        $mc.empty().append($topbar).append($body);
    }

    // ── Selected-car card highlight ───────────────────────────────────

    $(document).on('click', '.mpcrbm-bs .mpcrbm_transport_select', function () {
        var $btn    = $(this);
        var $card   = $btn.closest('.mpcrbm_booking_vehicle');
        var $bsWrap = $btn.closest('.mpcrbm-bs');

        setTimeout(function () {
            $bsWrap.find('.mpcrbm_booking_vehicle').removeClass('mpcrbm-bsr-selected');
            if ($btn.hasClass('active_select')) {
                $card.addClass('mpcrbm-bsr-selected');
            }
        }, 0);
    });

    // ── Show order summary after Book Now AJAX ────────────────────────

    function watchCheckout($area) {
        var checkoutEl = $area.find('.mpcrbm_checkout_area')[0];
        if (!checkoutEl) { return; }

        var mo = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes.length) {
                    var $orderSummary = $(checkoutEl).closest('.mpcrbm_order_summary');
                    $orderSummary.addClass('mpcrbm-bsr-on');
                    $('html, body').animate(
                        { scrollTop: $orderSummary.offset().top - 20 },
                        400
                    );
                    mo.disconnect();
                    break;
                }
            }
        });
        mo.observe(checkoutEl, { childList: true });
    }

}(jQuery));
