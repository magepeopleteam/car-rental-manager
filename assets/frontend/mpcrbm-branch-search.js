/**
 * Branch Search Shortcode — [mpcrbm_branch_search]
 *
 * On branch selection:
 *  1. Show the branch info card (address, phone, hours pills).
 *  2. Sync the chosen branch into the standard booking form's
 *     #mpcrbm_manual_start_place / #mpcrbm_manual_end_place selects
 *     so that selecting a branch here also drives the main search form.
 */
(function ($) {
    'use strict';

    var DAY_ORDER  = ['mon','tue','wed','thu','fri','sat','sun'];
    var DAY_LABELS = { mon:'Mon', tue:'Tue', wed:'Wed', thu:'Thu', fri:'Fri', sat:'Sat', sun:'Sun' };

    // ── Helpers ──────────────────────────────────────────────────────────

    function esc(str) { return $('<span>').text(str || '').html(); }

    function formatTime(t) {
        if (!t) return '';
        var parts = t.split(':');
        var h  = parseInt(parts[0], 10);
        var m  = parseInt(parts[1] || 0, 10);
        var sfx = h >= 12 ? 'pm' : 'am';
        var h12 = h % 12 || 12;
        return h12 + (m ? ':' + ('0' + m).slice(-2) : '') + sfx;
    }

    // ── Branch info card ──────────────────────────────────────────────────

    function buildBranchCard(meta) {
        if (!meta) return '';
        var rows = '';
        if (meta.address) {
            rows += '<div class="mpcrbm-bs__bc-row"><i class="mi mi-map-marker"></i><span>' + esc(meta.address) + '</span></div>';
        }
        if (meta.phone) {
            rows += '<div class="mpcrbm-bs__bc-row"><i class="mi mi-phone"></i><span>' + esc(meta.phone) + '</span></div>';
        }
        var pills = buildHoursPills(meta.hours || {});
        if (pills) {
            rows += '<div class="mpcrbm-bs__bc-row">' +
                    '<i class="mi mi-time-quarter-to"></i>' +
                    '<div>' +
                    '<button type="button" class="mpcrbm-bs__bc-hours-toggle">View opening hours ▾</button>' +
                    '<div class="mpcrbm-bs__bc-hours-grid" hidden>' + pills + '</div>' +
                    '</div></div>';
        }
        // Always show card — at minimum the branch name
        return '<div class="mpcrbm-bs__bc-header">' +
               '<i class="mi mi-map-location-track"></i>' +
               '<span class="mpcrbm-bs__bc-name">' + esc(meta.name) + '</span>' +
               '</div>' +
               (rows ? '<div class="mpcrbm-bs__bc-body">' + rows + '</div>' : '');
    }

    function buildHoursPills(hours) {
        var pills = '';
        DAY_ORDER.forEach(function (key) {
            var d = hours[key];
            if (!d) return;
            var label = DAY_LABELS[key] || key;
            if (d.closed) {
                pills += '<span class="mpcrbm-bs__hp mpcrbm-bs__hp--closed">' + esc(label) + ': Closed</span>';
            } else if (d.open && d.close) {
                pills += '<span class="mpcrbm-bs__hp mpcrbm-bs__hp--open">' +
                         esc(label) + ': ' + formatTime(d.open) + '–' + formatTime(d.close) + '</span>';
            }
        });
        return pills;
    }

    // ── Hours toggle ──────────────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-bs__bc-hours-toggle', function () {
        var $grid = $(this).next('.mpcrbm-bs__bc-hours-grid');
        if ($grid.prop('hidden')) {
            $grid.prop('hidden', false).hide().slideDown(200);
            $(this).text('Hide opening hours ▴');
        } else {
            $grid.slideUp(200, function () { $(this).prop('hidden', true); });
            $(this).text('View opening hours ▾');
        }
    });

    // ── Sync helper: push a branch slug into the standard booking form ────
    //
    // The standard [mpcrbm_booking] shortcode renders:
    //   #mpcrbm_manual_start_place  — pickup location <select>
    //   #mpcrbm_manual_end_place    — dropoff location <select>
    //
    // If those elements exist on the page we update them so the branch
    // selection in our widget drives the main booking form as well.

    function ensureOption($select, slug, label) {
        if (!$select.length || !slug) return;
        if (!$select.find('option[value="' + slug + '"]').length) {
            $select.append($('<option>').val(slug).text(label || slug));
        }
    }

    function syncToBookingForm(pickupSlug, dropoffSlug, branches) {
        var $mainPickup  = $('#mpcrbm_manual_start_place');
        var $mainDropoff = $('#mpcrbm_manual_end_place');

        if (!$mainPickup.length) return;   // standard booking form not on this page

        var pickupLabel  = branches[pickupSlug]  ? branches[pickupSlug].name  : pickupSlug;
        var dropoffLabel = branches[dropoffSlug] ? branches[dropoffSlug].name : dropoffSlug;

        // Ensure the branch option exists in each select
        ensureOption($mainPickup,  pickupSlug,  pickupLabel);
        ensureOption($mainDropoff, dropoffSlug, dropoffLabel);

        // Set values and fire change so the existing JS (dropoff refresh etc.) reacts
        $mainPickup.val(pickupSlug).trigger('change');

        if (dropoffSlug && dropoffSlug !== pickupSlug) {
            // Give the change handler a tick to re-populate dropoff options first
            setTimeout(function () {
                ensureOption($mainDropoff, dropoffSlug, dropoffLabel);
                $mainDropoff.val(dropoffSlug).trigger('change');
            }, 120);
        }
    }

    // ── Init each shortcode instance ──────────────────────────────────────

    $(function () {
        $('.mpcrbm-bs').each(function () {
            initInstance($(this));
        });
    });

    function initInstance($wrap) {
        var branches   = (window.mpcrbmBsData && window.mpcrbmBsData.branches) || {};
        var ajaxUrl    = $wrap.data('ajax') || '';
        var nonce      = $wrap.data('nonce') || '';
        var priceBased = $wrap.data('price-based') || 'manual';

        var $pickup        = $wrap.find('#mpcrbm_bs_pickup');
        var $startDate     = $wrap.find('#mpcrbm_bs_start_date');
        var $returnDate    = $wrap.find('#mpcrbm_bs_return_date');
        var $dateRange     = $wrap.find('#mpcrbm_bs_date_range');
        var $durationBadge = $wrap.find('#mpcrbm_bs_duration_badge');
        var $sameLoc       = $wrap.find('#mpcrbm_bs_same_loc');
        var $dropoffWrap = $wrap.find('#mpcrbm_bs_dropoff_wrap');
        var $dropoff     = $wrap.find('#mpcrbm_bs_dropoff');
        var $branchCard  = $wrap.find('#mpcrbm_bs_branch_info');
        var $searchBtn   = $wrap.find('#mpcrbm_bs_search_btn');
        var $carsHolder  = $wrap.find('#mpcrbm_bs_cars');
        var $results     = $wrap.find('#mpcrbm_bs_results_section');
        var $placeholder = $wrap.find('#mpcrbm_bs_placeholder');

        // ── Pickup change ─────────────────────────────────────────────

        $pickup.on('change', function () {
            var slug = $(this).val();

            // 1. Branch info card
            if (!slug) {
                $branchCard.prop('hidden', true).empty();
            } else {
                var html = buildBranchCard(branches[slug]);
                if (html) {
                    $branchCard.html(html).prop('hidden', false);
                } else {
                    $branchCard.prop('hidden', true).empty();
                }
            }

            // 2. Keep dropoff in sync when "same location" is on
            if ($sameLoc.is(':checked')) {
                $dropoff.val(slug);
            }

            // 3. Push to standard booking form selects
            var dropoffSlug = $sameLoc.is(':checked') ? slug : ($dropoff.val() || slug);
            syncToBookingForm(slug, dropoffSlug, branches);
        });

        // ── Dropoff change ────────────────────────────────────────────

        $dropoff.on('change', function () {
            var pickupSlug  = $pickup.val();
            var dropoffSlug = $(this).val() || pickupSlug;
            syncToBookingForm(pickupSlug, dropoffSlug, branches);
        });

        // ── Return-to-same-location toggle ────────────────────────────

        $sameLoc.on('change', function () {
            if ($(this).is(':checked')) {
                $dropoffWrap.slideUp(200);
                var slug = $pickup.val();
                $dropoff.val(slug);
                syncToBookingForm(slug, slug, branches);
            } else {
                $dropoffWrap.slideDown(200);
            }
        });

        // ── Flatpickr date range ──────────────────────────────────────

        function fmtDate(d) {
            var y  = d.getFullYear();
            var m  = ('0' + (d.getMonth() + 1)).slice(-2);
            var dy = ('0' + d.getDate()).slice(-2);
            return y + '-' + m + '-' + dy;
        }

        if (typeof flatpickr !== 'undefined' && $dateRange.length) {
            flatpickr($dateRange[0], {
                mode:        'range',
                minDate:     'today',
                dateFormat:  'Y-m-d',
                altInput:    true,
                altFormat:   'M j, Y',
                showMonths:  2,
                disableMobile: true,
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        $startDate.val(fmtDate(selectedDates[0]));
                        $returnDate.val(fmtDate(selectedDates[1]));
                        var days = Math.round(
                            (selectedDates[1] - selectedDates[0]) / 86400000
                        );
                        $durationBadge
                            .text(days + (days === 1 ? ' day' : ' days') + ' rental')
                            .prop('hidden', false);
                    } else {
                        $startDate.val('');
                        $returnDate.val('');
                        $durationBadge.prop('hidden', true);
                    }
                }
            });
        }

        // ── Search ────────────────────────────────────────────────────

        $searchBtn.on('click', function () {
            var pickup = $pickup.val();
            var start  = $startDate.val();
            var ret    = $returnDate.val();

            if (!pickup) { $pickup.focus(); alert('Please select a pickup location.'); return; }
            if (!start)  { $startDate.focus(); alert('Please select a pickup date.'); return; }
            if (!ret)    { $returnDate.focus(); alert('Please select a return date.'); return; }
            if (ret <= start) { $returnDate.focus(); alert('Return date must be after pickup date.'); return; }

            var endPlace = $sameLoc.is(':checked') ? pickup : ($dropoff.val() || pickup);

            $searchBtn.addClass('mpcrbm-bs__btn--loading').find('span').text('Searching…');
            $placeholder.hide();
            $results.show();
            $carsHolder.html('<div class="mpcrbm-bs__loading">Finding available cars…</div>');

            $.post(ajaxUrl, {
                action:                           'mpcrbm_get_map_search_result',
                mpcrbm_transportation_type_nonce: nonce,
                price_based:                      priceBased,
                start_date:                       start,
                return_date:                      ret,
                start_time:                       '10',
                return_time:                      '10',
                start_place:                      pickup,
                end_place:                        endPlace,
                ajax_search:                      'yes',
            }, function (html) {
                var $parsed = $('<div>').html(html);
                var hasCars = $parsed.find('.mpcrbm_booking_item, .mpcrbm_booking_vehicle').length > 0;

                if (hasCars) {
                    // Wrap in .mpcrbm_transport_search_area so that mpcrbm_registration.js
                    // event handlers (Select Car, extra services, Book Now, price calc) can
                    // use closest('.mpcrbm_transport_search_area') to scope their DOM queries.
                    // The hidden fields (mpcrbm_start_place, mpcrbm_date, etc.) and the
                    // .mpcrbm_transport_summary / .mpcrbm_extra_service / .mpcrbm_checkout_area
                    // divs are already present in the choose_vehicles.php AJAX response —
                    // we must NOT strip or re-inject them.
                    var $wrapper = $('<div class="mpcrbm_transport_search_area mpcrbm_tab_next _mT">');
                    $wrapper.html($parsed.html());
                    $carsHolder.html($wrapper);
                } else {
                    $carsHolder.html('<div class="mpcrbm-bs__no-results">No cars available for the selected location and dates.</div>');
                }

                $('html, body').animate({ scrollTop: $results.offset().top - 20 }, 400);
            }).fail(function () {
                $carsHolder.html('<div class="mpcrbm-bs__no-results">Something went wrong. Please try again.</div>');
            }).always(function () {
                $searchBtn.removeClass('mpcrbm-bs__btn--loading').find('span').text('Search Available Cars');
            });
        });
    }

}(jQuery));
