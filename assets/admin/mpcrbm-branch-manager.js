/**
 * Branch Manager — Admin JS
 *
 * Handles the Branch Dashboard inside the Car Rental admin panel:
 *   - View Cars button   → AJAX load car cards for the selected branch
 *   - Transfer button    → AJAX move a car to a different branch
 *   - Toast notifications for success / error feedback
 *   - Hours table: disable time inputs when "Closed" checkbox is ticked
 */
jQuery(document).ready(function ($) {

    // ── Helpers ──────────────────────────────────────────────────────────

    function dashboard() { return $('.mpcrbm-branch-dashboard'); }

    function ajaxUrl()  { return dashboard().data('ajax') || (typeof ajaxurl !== 'undefined' ? ajaxurl : ''); }
    function nonce()    { return dashboard().data('nonce') || ''; }

    function formatCarCount(count) {
        count = parseInt(count, 10) || 0;
        var label = count === 1
            ? (mpcrbmBranchAdmin.carText || 'car')
            : (mpcrbmBranchAdmin.carsText || 'cars');
        return count + ' ' + label;
    }

    function branchCard(branchSlug) {
        return $('.mpcrbm-branch-card').filter(function () {
            return String($(this).data('branch-slug')) === String(branchSlug);
        }).first();
    }

    function updateBranchCount(branchSlug, count) {
        count = parseInt(count, 10) || 0;
        var $badge = branchCard(branchSlug).find('.mpcrbm-car-count-badge');
        if (!$badge.length) {
            return;
        }

        $badge
            .toggleClass('has-cars', count > 0)
            .toggleClass('is-empty', count === 0);
        $badge.find('.mpcrbm-car-count-value').text(count);
        $badge.find('.mpcrbm-car-count-status').text(
            count > 0
                ? (mpcrbmBranchAdmin.activeText || 'Active')
                : (mpcrbmBranchAdmin.emptyText || 'Empty')
        );
    }

    function preferredCarsView() {
        try {
            return window.localStorage.getItem('mpcrbmBranchCarsView') === 'list' ? 'list' : 'grid';
        } catch (error) {
            return 'grid';
        }
    }

    function applyCarsView(view, persist) {
        view = view === 'list' ? 'list' : 'grid';
        var $panel = $('.mpcrbm-branch-cars-panel');

        $panel.toggleClass('is-list-view', view === 'list');
        $('.mpcrbm-cars-view-button').each(function () {
            var isActive = $(this).data('view') === view;
            $(this).toggleClass('is-active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
        });

        if (persist) {
            try {
                window.localStorage.setItem('mpcrbmBranchCarsView', view);
            } catch (error) {
                // Storage may be unavailable in privacy-restricted browsers.
            }
        }
    }

    function showToast(message, type) {
        type = type || 'success';
        var toast = $('<div class="mpcrbm-branch-toast is-' + type + '">' + $('<span>').text(message).html() + '</div>');
        $('body').append(toast);
        setTimeout(function () { toast.fadeOut(400, function () { $(this).remove(); }); }, 3500);
    }

    // Shared feedback channel used by the in-dashboard branch Add/Edit modal.
    $(document).on('mpcrbm:branch-toast', function (event, message, type) {
        showToast(message, type);
    });

    function showLoading(panel) {
        panel.html('<div class="mpcrbm-branch-loading">' + (mpcrbmBranchAdmin.loadingText || 'Loading…') + '</div>');
    }

    // ── View Cars ─────────────────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-view-branch-cars', function () {
        var $btn        = $(this);
        var branchSlug  = $btn.data('branch-slug');
        var branchName  = $btn.data('branch-name') || branchSlug;

        // Highlight active branch card
        $('.mpcrbm-branch-card').removeClass('is-active');
        $btn.closest('.mpcrbm-branch-card').addClass('is-active');

        var $panel     = $('.mpcrbm-branch-cars-panel');
        var $panelHead = $panel.find('.mpcrbm-branch-cars-panel-header');
        var $panelBody = $panel.find('.mpcrbm-branch-cars-panel-body');

        applyCarsView(preferredCarsView(), false);
        $panelHead.show().find('.mpcrbm-panel-branch-name').text(branchName);
        $panelHead.find('.mpcrbm-panel-car-count').text('…');

        showLoading($panelBody);
        $panel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });

        $.post(ajaxUrl(), {
            action:      'mpcrbm_get_branch_cars',
            nonce:       nonce(),
            branch_slug: branchSlug,
        }, function (res) {
            if (res.success) {
                $panelBody.html(res.data.html);
                updateBranchCount(branchSlug, res.data.count);
                $panelHead.find('.mpcrbm-panel-car-count').text(formatCarCount(res.data.count));
            } else {
                $panelBody.html('<p class="mpcrbm-no-cars">' + (res.data && res.data.message ? res.data.message : 'Error loading cars.') + '</p>');
            }
        }).fail(function () {
            $panelBody.html('<p class="mpcrbm-no-cars">Network error. Please try again.</p>');
        });
    });

    // ── Grid / List View ─────────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-cars-view-button', function () {
        applyCarsView($(this).data('view'), true);
    });

    // ── Transfer Car ─────────────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-do-transfer', function () {
        var $btn       = $(this);
        var carId      = $btn.data('car-id');
        var $card      = $btn.closest('.mpcrbm-branch-car-card');
        var $select    = $card.find('.mpcrbm-transfer-target');
        var $reasonIn  = $card.find('.mpcrbm-transfer-reason');
        var toBranch   = $select.val();
        var reason     = $reasonIn.val();

        if (!toBranch) {
            showToast(mpcrbmBranchAdmin.selectBranchText || 'Please select a target branch.', 'error');
            return;
        }

        if (!confirm(mpcrbmBranchAdmin.confirmTransferText || 'Transfer this car to the selected branch?')) {
            return;
        }

        $btn.prop('disabled', true).text(mpcrbmBranchAdmin.transferringText || 'Transferring…');

        $.post(ajaxUrl(), {
            action:    'mpcrbm_transfer_car_branch',
            nonce:     nonce(),
            car_id:    carId,
            to_branch: toBranch,
            reason:    reason,
        }, function (res) {
            if (res.success) {
                showToast(res.data.message, 'success');
                // Use authoritative counts returned after the database update so the
                // source card, destination card and open panel cannot drift apart.
                updateBranchCount(res.data.from, res.data.from_count);
                updateBranchCount(res.data.to, res.data.to_count);
                $('.mpcrbm-panel-car-count').text(formatCarCount(res.data.from_count));

                // The current panel represents the source branch. Remove the moved
                // vehicle immediately and show its empty state when the last car leaves.
                $card.fadeOut(180, function () {
                    $(this).remove();
                    if (parseInt(res.data.from_count, 10) === 0) {
                        $('.mpcrbm-branch-cars-panel-body').html(
                            $('<p>', { class: 'mpcrbm-no-cars' }).text(
                                mpcrbmBranchAdmin.noCarsText || 'No cars currently at this branch.'
                            )
                        );
                    }
                });
            } else {
                showToast(res.data && res.data.message ? res.data.message : 'Transfer failed.', 'error');
                $btn.prop('disabled', false).text(mpcrbmBranchAdmin.transferText || 'Transfer');
            }
        }).fail(function () {
            showToast('Network error. Please try again.', 'error');
            $btn.prop('disabled', false).text(mpcrbmBranchAdmin.transferText || 'Transfer');
        });
    });

    // ── Hours Table: Closed checkbox toggles time inputs ─────────────────

    $(document).on('change', '.mpcrbm-day-closed', function () {
        var $row    = $(this).closest('tr');
        var isClosed = $(this).is(':checked');
        $row.find('input[type="time"]').prop('disabled', isClosed);
    });

    // Initial state on page load
    $('.mpcrbm-day-closed:checked').each(function () {
        $(this).closest('tr').find('input[type="time"]').prop('disabled', true);
    });

});
