jQuery(document).ready(function ($) {
    /*if (typeof datePickerData === "undefined") return;

    let { availableDates, startDate, endDate } = datePickerData;
    let selectors = ['#mpcrbm_start_date', '#mpcrbm_return_date'];

    selectors.forEach(function (selector) {
        jQuery(selector).datepicker({
            dateFormat: mpcrbm_date_format,
            minDate: new Date(startDate.year, startDate.month, startDate.day),
            maxDate: new Date(endDate.year, endDate.month, endDate.day),
            autoSize: true,
            changeMonth: true,
            changeYear: true,
            beforeShowDay: function (date) {
                let dmy = date.getDate() + "-" + (date.getMonth() + 1) + "-" + date.getFullYear();
                return [availableDates.includes(dmy), "", availableDates.includes(dmy) ? "Available" : "Unavailable"];
            },
            onSelect: function (dateString, data) {
                let date = data.selectedYear + '-' + ('0' + (parseInt(data.selectedMonth) + 1)).slice(-2) + '-' + ('0' + parseInt(data.selectedDay)).slice(-2);
                jQuery(this).closest('label').find('input[type="hidden"]').val(date).trigger('change');
            }
        });
    });*/

    function get_off_days_numbers(off_days_string) {
        // Split the string and normalize to lowercase
        let days = off_days_string.toLowerCase().split(',').map(d => d.trim());

        // Define day map (Sunday = 0)
        const day_map = {
            monday: 1,
            tuesday: 2,
            wednesday: 3,
            thursday: 4,
            friday: 5,
            saturday: 6,
            sunday: 0
        };

        // Convert to numbers
        let result = [];
        days.forEach(day => {
            if (day_map.hasOwnProperty(day)) {
                result.push(day_map[day]);
            }
        });

        return result;
    }

    function mpcrbm_get_selected_days() {
        let parentClass = $('.mpcrbm_car_details_container');

        let startDate = parentClass.find("#mpcrbm_map_start_date").val();
        let endDate = parentClass.find("#mpcrbm_map_return_date").val();
        if (!endDate || endDate.trim() === "") {
            return;
        }

        let start_time = parseFloat(parentClass.find("#mpcrbm_map_start_time").val() );
        let return_time = parseFloat(parentClass.find("#mpcrbm_map_return_time") .val() );
        let start = new Date(startDate);
        let end = new Date(endDate);

        let startDateTime = new Date(start);
        startDateTime.setHours(start_time);
        let endDateTime = new Date(end);
        endDateTime.setHours(return_time);

        let diffMs = endDateTime - startDateTime;

        if (diffMs < 0) {
            console.log("End date/time must be after start date/time");
            return;
        }
        let diffDays = diffMs / (1000 * 60 * 60 * 24);
        let totalDays = Math.ceil(diffDays);
        let dayPrice = parseFloat( parentClass.find("#mpcrbm_car_day_price").val() );
        let dayWisePrice = parseFloat( parentClass.find("#mpcrbm_car_day_wise_price").val() );
        let car_id = parseInt( parentClass.find("#mpcrbm_car_id").val() );
        let get_price = dayWisePrice * totalDays;
        dayPrice = mpcrbm_price_format( dayPrice );
        parentClass.find("#mpcrbm_car_selected_day").text(totalDays);
        parentClass.find("#mpcrbm_selected_car_price").html(dayPrice);

        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax.ajax_url,
            data: {
                action: "mpcrbm_get_total_count_price_selected_car",
                start_date: startDate,
                start_time: start_time,
                car_id: car_id,
                total_price: get_price,
                total_days: totalDays,
                _nonce: mpcrbm_ajax.nonce
            },
            success: function (data) {

                if (data.success && data.data && data.data.calculated_price !== undefined) {
                    let calculated_price = mpcrbm_price_format( data.data.calculated_price );
                    parentClass.find("#mpcrbm_car_total_price").html(calculated_price);
                }
            },
            error: function(response) {
                console.log(response);
            }
        });

    }


    let mpcrbm_off_dates = '';
    let mpcrbm_off_days = '';
    let mpcrbm_offDates = [];
    let mpcrbm_off_days_ary = [];

    let parent = $('.mpcrbm_car_details');
    mpcrbm_off_dates = parent.find("#mpcrbm_off_dates").val();
    mpcrbm_off_days = parent.find( "#mpcrbm_off_days").val();

    if( mpcrbm_off_dates ){
        mpcrbm_offDates = mpcrbm_off_dates.split(',');
    }
    if( mpcrbm_off_days ){
        mpcrbm_off_days_ary = get_off_days_numbers( mpcrbm_off_days );
    }

    let selectors = ['#mpcrbm_start_date', '#mpcrbm_return_date'];
    let mpcrbm_start_date = parent.find( "#mpcrbm_start_calendar_day").val();
    selectors.forEach(function (selector) {
        flatpickr( selector, {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            showMonths: 2,
            locale: {
                firstDayOfWeek: mpcrbm_start_date // 1 = Monday
            },
            disable: [
                function(date) {
                    return mpcrbm_off_days_ary.includes(date.getDay());
                },
                ...mpcrbm_offDates.map(d => new Date(d))
            ],

            onChange: function( selectedDates, dateStr, instance ) {

                if(selectedDates.length > 0){
                    let startDate = instance.formatDate(selectedDates[0], "Y-m-d");
                    let endDate = selectedDates[1] ? instance.formatDate(selectedDates[1], "Y-m-d") : '';

                    let startDateDisplay = instance.formatDate(selectedDates[0], "D M d Y");
                    let endDateDisplay = selectedDates[1] ? instance.formatDate(selectedDates[1], "D M d Y") : '';

                    // Set visible inputs
                    $("#mpcrbm_start_date").val( startDateDisplay );
                    $("#mpcrbm_return_date").val( endDateDisplay );

                    // Set hidden inputs and trigger change
                    $("#mpcrbm_start_date").closest('label').find('input[type="hidden"]').val(startDate);
                    $("#mpcrbm_return_date").closest('label').find('input[type="hidden"]').val(endDate).trigger('change');

                    mpcrbm_get_selected_days();
                }
            }
        });
    });

});


