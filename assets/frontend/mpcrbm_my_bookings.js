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
                        appendTo:    document.body
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

});
