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
    selectors.forEach(function (selector) {
        flatpickr( selector, {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            showMonths: 2,
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
                    $("#mpcrbm_return_date").closest('label').find('input[type="hidden"]').val(endDate);
                }
            }
        });
    });

});


