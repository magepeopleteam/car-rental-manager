/**
 * Branch Manager — Frontend JS
 *
 * Shows branch info card when a customer picks a pickup location.
 * Shows a one-way fee notice when pickup and dropoff branches differ,
 * if the selected car has a one-way fee configured.
 *
 * Depends on wp_localize_script variable: mpcrbmBranchL10n
 *   .ajax_url          string  WordPress AJAX URL
 *   .car_one_way_fees  object  { car_id: { enabled: true, fee: amount } }
 *   .currency          string  e.g. "$"
 *   .strings.*         i18n strings
 */
(function ($) {
    'use strict';

    if (typeof mpcrbmBranchL10n === 'undefined') { return; }

    var L10n           = mpcrbmBranchL10n;
    var ajaxUrl        = L10n.ajax_url || '';
    var carOneWayFees  = L10n.car_one_way_fees || {};
    var currency       = L10n.currency || '$';

    var PICKUP_SEL  = '#mpcrbm_manual_start_place';
    var DROPOFF_SEL = '#mpcrbm_manual_end_place';

    // Cache for branch meta fetched via AJAX
    var branchCache = {};

    // ── Helpers ──────────────────────────────────────────────────────────

    function formatPrice(amount) {
        var num = parseFloat(amount) || 0;
        return currency + num.toFixed(2);
    }

    function getBranchMeta(slug, callback) {
        if (branchCache[slug]) {
            callback(branchCache[slug]);
            return;
        }
        $.post(ajaxUrl, {
            action:      'mpcrbm_get_branch_info',
            branch_slug: slug,
        }, function (res) {
            if (res.success && res.data) {
                branchCache[slug] = res.data;
                callback(res.data);
            } else {
                callback(null);
            }
        }).fail(function () {
            callback(null);
        });
    }

    function getCurrentCarId() {
        var carId = $('.mpcrbm_car_details_continue_btn').data('car-id');
        return carId ? String(carId) : null;
    }

    // ── Branch Info Card ─────────────────────────────────────────────────

    function buildInfoCard(meta) {
        if (!meta || !meta.name) { return ''; }

        var rows = '';

        if (meta.address) {
            rows += '<div class="mpcrbm-branch-info-row">' +
                    '<i class="mi mi-map-marker"></i>' +
                    '<span>' + escHtml(meta.address) + '</span>' +
                    '</div>';
        }
        if (meta.phone) {
            rows += '<div class="mpcrbm-branch-info-row">' +
                    '<i class="mi mi-phone"></i>' +
                    '<span>' + escHtml(meta.phone) + '</span>' +
                    '</div>';
        }

        // Hours
        var hoursHtml = buildHoursTable(meta.hours || {});
        if (hoursHtml) {
            rows += '<div class="mpcrbm-branch-info-row">' +
                    '<i class="mi mi-time-quarter-to"></i>' +
                    '<div>' +
                    '<button type="button" class="mpcrbm-branch-hours-toggle">' +
                    (L10n.strings && L10n.strings.viewHours ? L10n.strings.viewHours : 'View opening hours') +
                    '</button>' +
                    '<div class="mpcrbm-branch-hours-content" style="display:none">' + hoursHtml + '</div>' +
                    '</div>' +
                    '</div>';
        }

        return '<div class="mpcrbm-branch-info-card">' +
            '<div class="mpcrbm-branch-info-card-header">' +
            '<i class="mi mi-map-location-track"></i>' +
            '<strong>' + escHtml(meta.name) + '</strong>' +
            '</div>' +
            '<div class="mpcrbm-branch-info-rows">' + rows + '</div>' +
            '</div>';
    }

    var dayOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    var dayNames = {
        mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday',
        thu: 'Thursday', fri: 'Friday', sat: 'Saturday', sun: 'Sunday'
    };

    function buildHoursTable(hours) {
        if (!hours || Object.keys(hours).length === 0) { return ''; }
        var rows = '';
        dayOrder.forEach(function (key) {
            if (!hours[key]) { return; }
            var d        = hours[key];
            var isClosed = d.closed;
            var label    = dayNames[key] || key;
            var timeStr  = isClosed
                ? '<span class="is-closed">' + (L10n.strings && L10n.strings.closed ? L10n.strings.closed : 'Closed') + '</span>'
                : '<span class="is-open">' + escHtml(d.open) + ' – ' + escHtml(d.close) + '</span>';
            rows += '<tr><td>' + escHtml(label) + '</td><td>' + timeStr + '</td></tr>';
        });
        if (!rows) { return ''; }
        return '<table class="mpcrbm-branch-hours-table">' +
            '<thead><tr>' +
            '<th>' + (L10n.strings && L10n.strings.day ? L10n.strings.day : 'Day') + '</th>' +
            '<th>' + (L10n.strings && L10n.strings.hours ? L10n.strings.hours : 'Hours') + '</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>';
    }

    // ── One-Way Fee Notice ────────────────────────────────────────────────

    function buildOneWayNotice(fee) {
        if (!(parseFloat(fee) > 0)) { return ''; }
        return '<div class="mpcrbm-oneway-fee-notice">' +
            '<i class="mi mi-car-journey"></i>' +
            '<div class="mpcrbm-oneway-fee-notice-body">' +
            '<strong>' + formatPrice(fee) + ' ' + (L10n.strings && L10n.strings.oneWayFeeLabel ? L10n.strings.oneWayFeeLabel : 'One-way return fee') + '</strong>' +
            '<span>' + (L10n.strings && L10n.strings.oneWayFeeDesc ? L10n.strings.oneWayFeeDesc : 'Applied because the return location is a different branch.') + '</span>' +
            '</div>' +
            '</div>';
    }

    // ── DOM helpers ───────────────────────────────────────────────────────

    function escHtml(str) {
        return $('<span>').text(str || '').html();
    }

    function getOrCreateContainer(afterEl, className) {
        var $after = $(afterEl);
        var $wrap  = $after.next('.' + className);
        if (!$wrap.length) {
            $wrap = $('<div class="' + className + '"></div>').insertAfter($after);
        }
        return $wrap;
    }

    // ── Pickup location change ────────────────────────────────────────────

    $(document).on('change', PICKUP_SEL, function () {
        /*var slug = $(this).val();
        var $container = getOrCreateContainer(this, 'mpcrbm-pickup-branch-info');

        if (!slug) {
            $container.empty();
            return;
        }

        $container.html('<div class="mpcrbm-branch-info-loading">' +
            (L10n.strings && L10n.strings.loading ? L10n.strings.loading : 'Loading branch info…') +
            '</div>');

        getBranchMeta(slug, function (meta) {
            var html = buildInfoCard(meta);
            $container.html(html);
        });

        // Re-evaluate one-way fee in case dropoff was already selected
        refreshOneWayFee();*/
    });

    // ── Dropoff location change ───────────────────────────────────────────

    $(document).on('change', DROPOFF_SEL, function () {
        refreshOneWayFee();
    });

    // Also catch the case where the dropoff section is rendered into the DOM after pickup selection.
    observeDropoffAppearance();

    function refreshOneWayFee() {
        var pickupSlug   = $(PICKUP_SEL).val();
        var $dropoff     = $(DROPOFF_SEL);
        var dropoffSlug  = $dropoff.val();
        var isSameLocChk = $('#mpcrbm_is_drop_off').is(':checked');

        // Same location or missing values: no fee
        if (!pickupSlug || !dropoffSlug || pickupSlug === dropoffSlug || isSameLocChk) {
            setOneWayFee(0);
            return;
        }

        // Per-car fee lookup (only on car details page where a car ID is present)
        var carId = getCurrentCarId();
        if (carId) {
            var carFeeData = carOneWayFees[carId];
            if (carFeeData && carFeeData.enabled && parseFloat(carFeeData.fee) > 0) {
                setOneWayFee(parseFloat(carFeeData.fee));
            } else {
                setOneWayFee(0);
            }
        } else {
            setOneWayFee(0);
        }
    }

    /** Persist fee to hidden field and notify all price-calculation contexts. */
    function setOneWayFee(fee) {
        fee = parseFloat(fee) || 0;

        var $hiddenFee = $('#mpcrbm_branch_one_way_fee');
        if (!$hiddenFee.length) {
            $hiddenFee = $('<input type="hidden" id="mpcrbm_branch_one_way_fee" name="mpcrbm_branch_one_way_fee">').appendTo('form');
        }
        $hiddenFee.val(fee);

        // Let search-results page recalculate the currently selected vehicle total
        $(document).trigger('mpcrbm_one_way_fee_changed', [fee]);

        updateCarDetailsSummary(fee);
    }

    /** Show/hide the one-way fee row in the single-car details page summary. */
    function updateCarDetailsSummary(fee) {
        var $row     = $('#mpcrbm_car_one_way_fee_row');
        var $display = $('#mpcrbm_car_one_way_fee_display');
        if (!$row.length) { return; }

        fee = parseFloat(fee) || 0;
        var qty = parseInt($('#mpcrbm_selected_car_quantity').val()) || 1;
        var priceFormatter = (typeof mpcrbm_price_format === 'function') ? mpcrbm_price_format : formatPrice;

        if (fee > 0) {
            var feeTotal = fee * qty;
            $display.html(priceFormatter(fee) + ' &times; ' + qty + ' = ' + priceFormatter(feeTotal));
            $row.show();
        } else {
            $row.hide();
            $display.html('');
        }

        // Adjust the total shown in the car details summary
        var basePrice = parseFloat($('[name="mpcrbm_post_id"]').attr('data-price')) || 0;
        if (basePrice > 0) {
            var deposit = parseFloat($('#mpcrbm_security_deposit_value').val()) || 0;
            $('#mpcrbm_car_total_price').html(priceFormatter(basePrice + fee * qty + deposit));
        }
    }

    // ── MutationObserver: catch dynamically injected dropoff select ───────

    function observeDropoffAppearance() {
        if (!window.MutationObserver) { return; }
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) { return; }
                    var $node = $(node);
                    var $sel  = $node.is(DROPOFF_SEL) ? $node : $node.find(DROPOFF_SEL);
                    if ($sel.length) {
                        refreshOneWayFee();
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // ── Same-location checkbox ────────────────────────────────────────────

    $(document).on('change', '#mpcrbm_is_drop_off', function () {
        refreshOneWayFee();
    });

    // Initialise on page load in case the selects are already populated
    $(function () {
        if ($(PICKUP_SEL).val()) {
            refreshOneWayFee();
        }
    });

    // ── Hours toggle ──────────────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-branch-hours-toggle', function () {
        var $content = $(this).next('.mpcrbm-branch-hours-content');
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $(this).text(L10n.strings && L10n.strings.viewHours ? L10n.strings.viewHours : 'View opening hours');
        } else {
            $content.slideDown(200);
            $(this).text(L10n.strings && L10n.strings.hideHours ? L10n.strings.hideHours : 'Hide opening hours');
        }
    });

}(jQuery));
