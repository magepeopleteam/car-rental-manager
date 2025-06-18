jQuery(document).ready(function () {
    if (typeof datePickerData === "undefined") return;

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
    });
});
