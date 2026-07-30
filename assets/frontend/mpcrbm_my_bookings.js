jQuery(function ($) {
    var $wrap = $('.mpcrbm-mb-wrap');
    if ( ! $wrap.length ) return;

    var ajaxUrl = $wrap.data('ajax');
    var nonce   = $wrap.data('nonce');
    var $grid   = $('#mpcrbm-mb-grid');
    var $lmBtn  = $('#mpcrbm-mb-loadmore');
    var loading = false;

    // ── Load More ────────────────────────────────────────────────────────

    $(document).on('click', '#mpcrbm-mb-loadmore', function () {
        if (loading) return;
        loading = true;

        var page = $lmBtn.data('page');
        $lmBtn.prop('disabled', true)
              .find('.mpcrbm-mb-loadmore-text').hide().end()
              .find('.mpcrbm-mb-loadmore-spinner').show();

        $.post(ajaxUrl, {
            action: 'mpcrbm_mb_load',
            nonce:  nonce,
            page:   page
        }, function (res) {
            if (res.success && res.data.html) {
                $grid.append(res.data.html);
            }

            if (res.success && res.data.has_more) {
                $lmBtn.data('page', page + 1);
                $lmBtn.prop('disabled', false)
                      .find('.mpcrbm-mb-loadmore-text').show().end()
                      .find('.mpcrbm-mb-loadmore-spinner').hide();
            } else {
                $('#mpcrbm-mb-loadmore-wrap').hide();
            }
        }).fail(function () {
            $lmBtn.prop('disabled', false)
                  .find('.mpcrbm-mb-loadmore-text').show().end()
                  .find('.mpcrbm-mb-loadmore-spinner').hide();
        }).always(function () {
            loading = false;
        });
    });

    // ── Modal ────────────────────────────────────────────────────────────

    var $modal = $('#mpcrbm-mb-modal');
    var $mBody = $('#mpcrbm-mb-modal-body');

    function openModal() {
        $modal.addClass('is-open');
        $('body').css('overflow', 'hidden');
    }

    function closeModal() {
        $modal.removeClass('is-open');
        $('body').css('overflow', '');
        setTimeout(function () {
            $mBody.html('<div class="mpcrbm-mb-loading"><div class="mpcrbm-mb-spinner"></div></div>');
        }, 250);
    }

    $(document).on('click', '.js-mpcrbm-mb-view', function () {
        var id = $(this).data('id');
        openModal();

        $.post(ajaxUrl, {
            action: 'mpcrbm_mb_detail',
            nonce:  nonce,
            id:     id
        }, function (res) {
            if (res.success) {
                $mBody.html(res.data.html);
            } else {
                $mBody.html('<div class="mpcrbm-mb-empty"><i class="mi mi-car"></i><p>' + (res.data && res.data.message ? res.data.message : 'Unable to load booking.') + '</p></div>');
            }
        }).fail(function () {
            $mBody.html('<div class="mpcrbm-mb-empty"><p>Network error. Please try again.</p></div>');
        });
    });

    $('#mpcrbm-mb-modal-close, #mpcrbm-mb-modal-backdrop').on('click', closeModal);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $modal.hasClass('is-open')) {
            closeModal();
        }
    });

    // ── Modification request ─────────────────────────────────────────────

    $(document).on('click', '.js-mpcrbm-mod-open', function () {
        var target   = $(this).data('target');
        var $section = $(this).closest('.mpcrbm-mb-mod-section');
        var $form    = $section.find('#' + target);

        $section.find('.mpcrbm-mb-mod-form').slideUp(150);
        $form.slideDown(200);

        if (target === 'mpcrbm-mod-date-form' && typeof flatpickr !== 'undefined') {
            setTimeout(function () {
                // Booked dates for this car (excluding this booking's own current dates)
                // — same idea as the main booking calendar's off-dates disable list
                // (mp_global/assets/date-picker/date-picker.js), computed server-side
                // in MPCRBM_Shortcodes::mpcrbm_mb_render_detail().
                var unavailableRaw = $form.data('unavailable-dates') || '';
                var unavailableDates = String(unavailableRaw).split(',')
                    .map(function (d) { return d.trim(); })
                    .filter(function (d) { return d; })
                    .map(function (d) { return new Date(d); });

                $form.find('.mpcrbm-mod-datepicker').each(function () {
                    if (this._flatpickr) { this._flatpickr.destroy(); }
                    var defaultVal = this.getAttribute('data-default') || null;
                    flatpickr(this, {
                        enableTime:  true,
                        dateFormat:  'Y-m-d H:i',
                        time_24hr:   true,
                        minDate:     'today',
                        allowInput:  true,
                        defaultDate: defaultVal,
                        appendTo:    document.body,
                        disable:     unavailableDates
                    });
                });
            }, 100);
        }
    });

    $(document).on('click', '.js-mpcrbm-mod-dismiss', function () {
        $(this).closest('.mpcrbm-mb-mod-form').slideUp(150);
    });

    $(document).on('submit', '.mpcrbm-mb-mod-form', function (e) {
        e.preventDefault();
        var $form    = $(this);
        var $section = $form.closest('.mpcrbm-mb-mod-section');
        var $btn     = $form.find('.mpcrbm-mb-mod-submit-btn');
        var $result  = $form.find('.mpcrbm-mb-mod-result');

        if ( typeof flatpickr !== 'undefined' && $form.data('type') === 'date_change' ) {
            var pickup = $form.find('[name="new_pickup"]').val();
            var ret    = $form.find('[name="new_return"]').val();
            if ( ! pickup || ! ret ) {
                $result.html('<div class="mpcrbm-mb-mod-msg mpcrbm-mb-mod-msg--error">Please select both dates.</div>');
                return;
            }
        }

        $btn.prop('disabled', true);
        $result.html('');

        $.post(ajaxUrl, {
            action:     'mpcrbm_mb_mod_request',
            nonce:      nonce,
            booking_id: $section.data('booking-id'),
            req_type:   $form.data('type'),
            note:       $form.find('[name="note"]').val(),
            new_pickup: $form.find('[name="new_pickup"]').val(),
            new_return: $form.find('[name="new_return"]').val()
        }, function (res) {
            if (res.success) {
                $result.html('<div class="mpcrbm-mb-mod-msg mpcrbm-mb-mod-msg--success">' + res.data.message + '</div>');
                setTimeout(function () {
                    $section.find('.mpcrbm-mb-mod-btns').slideUp(200);
                    $section.find('.mpcrbm-mb-mod-form').slideUp(200);
                    $section.find('.mpcrbm-mb-mod-pending-after').slideDown(200);
                }, 1200);
            } else {
                var msg = res.data && res.data.message ? res.data.message : 'An error occurred.';
                $result.html('<div class="mpcrbm-mb-mod-msg mpcrbm-mb-mod-msg--error">' + msg + '</div>');
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            $result.html('<div class="mpcrbm-mb-mod-msg mpcrbm-mb-mod-msg--error">Network error. Please try again.</div>');
            $btn.prop('disabled', false);
        });
    });

    // ── Vehicle replacement: Accept / Reject ───────────────────────────────

    function replaceNoticeMsg($notice, msg, isError) {
        $notice.find('.mpcrbm-mb-replace-result').html(
            '<div class="mpcrbm-mb-mod-msg mpcrbm-mb-mod-msg--' + (isError ? 'error' : 'success') + '">' + msg + '</div>'
        );
    }

    $(document).on('click', '.mpcrbm-mb-replace-accept-btn', function () {
        var $btn    = $(this);
        var $notice = $btn.closest('.mpcrbm-mb-replace-notice');
        var id      = $btn.data('id');
        $btn.prop('disabled', true);

        $.post(ajaxUrl, {
            action:     'mpcrbm_mb_accept_replacement',
            nonce:      nonce,
            booking_id: id
        }, function (res) {
            if (res.success) {
                replaceNoticeMsg($notice, res.data.message, false);
                $notice.find('.mpcrbm-mb-replace-actions, .mpcrbm-mb-replace-reject-panel').slideUp(150);
            } else {
                replaceNoticeMsg($notice, (res.data && res.data.message) || 'An error occurred.', true);
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            replaceNoticeMsg($notice, 'Network error. Please try again.', true);
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.mpcrbm-mb-replace-reject-btn', function () {
        $(this).closest('.mpcrbm-mb-replace-notice').find('.mpcrbm-mb-replace-reject-panel').slideDown(150);
    });

    function submitReplacementReject($notice, id, note, then) {
        return $.post(ajaxUrl, {
            action:     'mpcrbm_mb_reject_replacement',
            nonce:      nonce,
            booking_id: id,
            note:       note
        }, then);
    }

    $(document).on('click', '.mpcrbm-mb-replace-confirm-reject-btn', function () {
        var $btn    = $(this);
        var $notice = $btn.closest('.mpcrbm-mb-replace-notice');
        var id      = $btn.data('id');
        var note    = $notice.find('.mpcrbm-mb-replace-reject-note').val();
        $notice.find('.mpcrbm-mb-replace-actions button, .mpcrbm-mb-replace-reject-panel button').prop('disabled', true);

        submitReplacementReject($notice, id, note, function (res) {
            if (res.success) {
                replaceNoticeMsg($notice, res.data.message, false);
                $notice.find('.mpcrbm-mb-replace-actions, .mpcrbm-mb-replace-reject-panel').slideUp(150);
            } else {
                replaceNoticeMsg($notice, (res.data && res.data.message) || 'An error occurred.', true);
                $notice.find('.mpcrbm-mb-replace-actions button, .mpcrbm-mb-replace-reject-panel button').prop('disabled', false);
            }
        }).fail(function () {
            replaceNoticeMsg($notice, 'Network error. Please try again.', true);
            $notice.find('.mpcrbm-mb-replace-actions button, .mpcrbm-mb-replace-reject-panel button').prop('disabled', false);
        });
    });

    function submitFollowUpModRequest($notice, id, reqType, note, successMsg) {
        return $.post(ajaxUrl, {
            action:     'mpcrbm_mb_mod_request',
            nonce:      nonce,
            booking_id: id,
            req_type:   reqType,
            note:       note
        }, function (res) {
            if (res.success) {
                replaceNoticeMsg($notice, successMsg, false);
            } else {
                replaceNoticeMsg($notice, (res.data && res.data.message) || 'An error occurred.', true);
            }
        }).fail(function () {
            replaceNoticeMsg($notice, 'Network error. Please try again.', true);
        });
    }

    $(document).on('click', '.mpcrbm-mb-replace-cancel-booking-btn, .mpcrbm-mb-replace-refund-btn', function () {
        var $btn     = $(this);
        var $notice  = $btn.closest('.mpcrbm-mb-replace-notice');
        var id       = $btn.data('id');
        var note     = $notice.find('.mpcrbm-mb-replace-reject-note').val();
        var isRefund = $btn.hasClass('mpcrbm-mb-replace-refund-btn');
        $notice.find('.mpcrbm-mb-replace-actions button, .mpcrbm-mb-replace-reject-panel button').prop('disabled', true);

        // Reject the proposal first, then submit the follow-up request the
        // customer chose — reusing the existing cancellation/refund_request
        // modification-request flow rather than building a new one.
        submitReplacementReject($notice, id, note, function () {
            submitFollowUpModRequest(
                $notice, id,
                isRefund ? 'refund_request' : 'cancellation',
                note,
                isRefund
                    ? 'Vehicle change rejected and your refund request has been submitted.'
                    : 'Vehicle change rejected and your cancellation request has been submitted.'
            ).always(function () {
                $notice.find('.mpcrbm-mb-replace-actions, .mpcrbm-mb-replace-reject-panel').slideUp(150);
            });
        });
    });

});
