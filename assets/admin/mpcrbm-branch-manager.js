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

    function showToast(message, type) {
        type = type || 'success';
        var toast = $('<div class="mpcrbm-branch-toast is-' + type + '">' + $('<span>').text(message).html() + '</div>');
        $('body').append(toast);
        setTimeout(function () { toast.fadeOut(400, function () { $(this).remove(); }); }, 3500);
    }

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
                $panelHead.find('.mpcrbm-panel-car-count').text(res.data.count + ' ' + (mpcrbmBranchAdmin.carsText || 'cars'));
            } else {
                $panelBody.html('<p class="mpcrbm-no-cars">' + (res.data && res.data.message ? res.data.message : 'Error loading cars.') + '</p>');
            }
        }).fail(function () {
            $panelBody.html('<p class="mpcrbm-no-cars">Network error. Please try again.</p>');
        });
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
                // Remove the card from the current panel (car is now at another branch)
                $card.fadeOut(400, function () { $(this).remove(); });
                // Update the sidebar badge count
                var $currentActiveCard = $('.mpcrbm-branch-card.is-active .mpcrbm-car-count-badge');
                var currentCount = parseInt($currentActiveCard.text(), 10) || 0;
                if (currentCount > 0) {
                    $currentActiveCard.text(currentCount - 1);
                }
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
