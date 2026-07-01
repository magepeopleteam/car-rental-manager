(function ($) {
    function mpcrbmHandleUserInfoFilter(value) {
        const container = document.getElementById('mpcrbm_order_list__user_input_container');
        let html = '';

        if (value === 'name') {
            html = `<input type="text" placeholder="Enter name" id="mpcrbm_user_info_value" class="mpcrbm_order_list__input">`;
        } else if (value === 'email') {
            html = `<input type="email" placeholder="Enter email" id="mpcrbm_user_info_value" class="mpcrbm_order_list__input">`;
        } else if (value === 'phone') {
            html = `<input type="number" placeholder="Enter phone number" id="mpcrbm_user_info_value" class="mpcrbm_order_list__input">`;
        }

        container.innerHTML = html;
    }

    $('.mpcrbm_order_list_user_info_filter').on('change', function() {
        var selectedValue = $(this).val();
        mpcrbmHandleUserInfoFilter(selectedValue);
    });


    function filterOrderRows() {
        let selectedDate = $('#mpcrbm_order_list__start_date').val();
        let selectedPlace = $('#mpcrbm_order_list__pickup_place').val();
        let selectedPost = $('#mpcrbm_order_list__post_name').val();
        let order_status = $('#mpcrbm_order_list__order_status').val();
        let userFilterKey = $('#mpcrbm_user_info_filter_by').val();
        let userFilterVal = $('#mpcrbm_user_info_value').val();

        $('tbody tr').each(function () {
            var $row = $(this);

            var pickupDate = $row.data('filtar-pickup-date') || '';
            var pickupPlace = ($row.data('filtar-pickup-place') || '').toString();
            var postName = ($row.data('filtar-post-name') || '').toString();
            var orderStatus = ($row.data('filtar-order-status') || '').toString();
            var userInfoRaw = userFilterKey && userFilterKey !== 'all' ? $row.data('filtar-user-' + userFilterKey) : null;


            var userInfo = userInfoRaw ? userInfoRaw.toString() : '';

            let match = false;

            // যদি কোন ফিল্টার পূরণ হয়, match true হবে (OR কন্ডিশন)
            if (selectedDate && selectedDate === pickupDate) {
                match = true;
            }

            if (selectedPlace && selectedPlace === pickupPlace) {
                match = true;
            }

            if (selectedPost && selectedPost === postName) {
                match = true;
            }

            if ( order_status &&  order_status === orderStatus ) {
                match = true;
            }

            if ( userFilterKey && userFilterKey !== 'all' && userFilterVal && userInfo.includes(userFilterVal)) {
                match = true;
            }
            // সব ফিল্ড ফাঁকা হলে সব রো দেখাও

            if ( selectedDate === '' && selectedPlace === 'all' && selectedPost === 'all'  && order_status === 'all' && userFilterKey === 'all' ) {
                match = true;
            }

            $row.toggle(match);
        });
    }



// Separate `keyup` for user info value only
    $(document).on('keyup', '#mpcrbm_user_info_value', function () {
        filterOrderRows();
    });

// Separate `change` events for others
    $(document).on('change', '#mpcrbm_order_list__start_date, #mpcrbm_order_list__pickup_place, #mpcrbm_order_list__order_status, #mpcrbm_order_list__post_name' , function () {
        filterOrderRows();
    });

    $(document).on('click', '.pcrbm_order_extra_service_btn', function () {
        $(".mpcrbm_order_extra_service_holder").hide();
        $(this).parent().siblings().fadeIn();

    });

    $(document).on('click', '.mpcrbm_filter_date', function () {

        $('.mpcrbm_filter_date').removeClass('mpcrbm_data_selected');
        $(this).addClass('mpcrbm_data_selected');

        let clickedId = $(this).attr('id');
        let today = new Date();
        today.setHours(0, 0, 0, 0);

        let startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay()); // Start of this week (Sunday)

        let startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1); // First day of month

        $('tbody tr').each(function () {
            if (clickedId === 'all') {
                $(this).show();
                return;
            }

            let pickupDateStr = $(this).data('filtar-pickup-date');
            if (!pickupDateStr) {
                $(this).hide();
                return;
            }

            let pickupDate = new Date(pickupDateStr);
            pickupDate.setHours(0, 0, 0, 0);

            let show = false;

            if (clickedId === 'today') {
                show = (pickupDate.getTime() === today.getTime());
            } else if (clickedId === 'week') {
                show = (pickupDate >= startOfWeek );
            } else if (clickedId === 'month') {
                show = (pickupDate >= startOfMonth );
            }

            $(this).toggle(show);
        });
    });

    // ── Modification Requests ────────────────────────────────────────────

    $(document).on('click', '.mpcrbm-btn-mod-requests', function () {
        var id      = $(this).data('booking-id');
        var nonce   = (typeof mpcrbm_order_action !== 'undefined') ? mpcrbm_order_action.nonce   : '';
        var ajaxUrl = (typeof mpcrbm_order_action !== 'undefined') ? mpcrbm_order_action.ajax_url : ajaxurl;

        $('#mpcrbm-order-modal-title').text('Modification Requests — Booking #' + id);
        $('#mpcrbm-order-modal-body').html(
            '<div class="mpcrbm-modal-loader"><span class="spinner is-active" style="float:none;margin:0 auto;"></span> Loading…</div>'
        );
        $('#mpcrbm-order-detail-modal').fadeIn(180);
        $('body').css('overflow', 'hidden');

        $.post(ajaxUrl, {
            action:     'mpcrbm_admin_view_mod_requests',
            nonce:      nonce,
            booking_id: id
        }, function (r) {
            if (r.success) {
                $('#mpcrbm-order-modal-body').html(r.data.html);
            } else {
                $('#mpcrbm-order-modal-body').html('<div class="notice notice-error inline"><p>' + (r.data && r.data.message ? r.data.message : 'Error loading requests.') + '</p></div>');
            }
        }).fail(function () {
            $('#mpcrbm-order-modal-body').html('<div class="notice notice-error inline"><p>Network error.</p></div>');
        });
    });

    // Panel collapse toggle
    $(document).on('click', '#mpcrbm-mod-panel-toggle', function () {
        var $body = $('#mpcrbm-mod-panel-body');
        var $icon = $(this).find('.dashicons');
        $body.slideToggle(200);
        $icon.toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
    });

    $(document).on('click', '.mpcrbm-mod-action-btn', function () {
        var $btn        = $(this);
        var bookingId   = $btn.data('booking-id');
        var reqIndex    = $btn.data('req-index');
        var actionType  = $btn.data('action');
        var nonce       = (typeof mpcrbm_order_action !== 'undefined') ? mpcrbm_order_action.nonce   : '';
        var ajaxUrl     = (typeof mpcrbm_order_action !== 'undefined') ? mpcrbm_order_action.ajax_url : ajaxurl;
        var label       = actionType === 'approve' ? 'Approve' : 'Reject';

        if ( ! confirm( label + ' this request?' ) ) return;

        $btn.closest('.mpcrbm-mod-panel-actions, .mpcrbm-mod-req-actions')
            .find('.mpcrbm-mod-action-btn').prop('disabled', true);
        $btn.text('Processing…');

        $.post(ajaxUrl, {
            action:      'mpcrbm_admin_mod_request_action',
            nonce:       nonce,
            booking_id:  bookingId,
            req_index:   reqIndex,
            action_type: actionType
        }, function (r) {
            if (r.success) {
                var cls    = actionType === 'approve' ? 'approved' : 'rejected';
                var doneHtml = '<span class="mpcrbm-mod-req-actioned mpcrbm-mod-req-actioned--' + cls + '">' + (actionType === 'approve' ? '✓ Approved' : '✗ Rejected') + '</span>';

                // In the modal (view popup)
                var $modalActions = $btn.closest('.mpcrbm-mod-req-actions');
                if ($modalActions.length) {
                    $modalActions.replaceWith(doneHtml);
                }

                // In the panel row — remove the entire row after brief delay
                var $panelRow = $btn.closest('tr');
                if ($panelRow.length) {
                    $panelRow.find('td:last').html(doneHtml);
                    setTimeout(function () {
                        $panelRow.fadeOut(400, function () {
                            $(this).remove();
                            // Hide panel if no more rows
                            if ($('#mpcrbm-mod-panel-body tbody tr').length === 0) {
                                $('#mpcrbm-mod-panel').slideUp(300);
                            }
                        });
                    }, 1200);
                }

                // Remove flag button from the order list row
                $('button.mpcrbm-btn-mod-requests[data-booking-id="' + bookingId + '"]').remove();
            } else {
                $btn.closest('.mpcrbm-mod-panel-actions, .mpcrbm-mod-req-actions')
                    .find('.mpcrbm-mod-action-btn').prop('disabled', false);
                $btn.text(label);
                alert(r.data && r.data.message ? r.data.message : 'An error occurred.');
            }
        }).fail(function () {
            $btn.closest('.mpcrbm-mod-panel-actions, .mpcrbm-mod-req-actions')
                .find('.mpcrbm-mod-action-btn').prop('disabled', false);
            $btn.text(label);
            alert('Network error. Please try again.');
        });
    });

})(jQuery);
