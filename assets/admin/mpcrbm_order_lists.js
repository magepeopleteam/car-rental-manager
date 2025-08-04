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
        let userFilterKey = $('#mpcrbm_user_info_filter_by').val();
        let userFilterVal = $('#mpcrbm_user_info_value').val();

        // alert( selectedDate );



        $('tbody tr').each(function () {
            var $row = $(this);

            var pickupDate = $row.data('filtar-pickup-date') || '';
            var pickupPlace = ($row.data('filtar-pickup-place') || '').toString();
            var postName = ($row.data('filtar-post-name') || '').toString();
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

            console.log( userInfo, userFilterVal );

            if (userFilterKey && userFilterKey !== 'all' && userFilterVal && userInfo.includes(userFilterVal)) {
                match = true;
            }

            // সব ফিল্ড ফাঁকা হলে সব রো দেখাও
            if (!selectedDate && !selectedPlace && !selectedPost && (!userFilterKey || userFilterKey === 'all' || !userFilterVal)) {
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
    $(document).on('change', '#mpcrbm_order_list__start_date, #mpcrbm_order_list__pickup_place, #mpcrbm_order_list__post_name' , function () {
        filterOrderRows();
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



    // $('#mpcrbm_order_list__start_date').datepicker('show');

})(jQuery);
