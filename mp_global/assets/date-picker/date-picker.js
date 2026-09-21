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

    /**
     * Refresh the car-details price after a date pick.
     *
     * This used to be a SECOND copy of the same day-count + price AJAX that
     * mpcrbm_registration.js already owns, and both ran on a return-date pick: two
     * concurrent requests for the same car, each writing #mpcrbm_car_total_price,
     * whichever replied last winning. Worse, this copy wrote the bare server price as
     * the total — no extra services, no delivery/collection, no deposit, no one-way
     * fee — so whenever it won the race the customer's selected extras vanished from
     * the displayed total. That was the "price keeps changing / doesn't match" report.
     *
     * There is now one implementation, exported by mpcrbm_registration.js (enqueued on
     * every frontend page, so it is always present alongside this file). If it somehow
     * isn't, this is a no-op rather than a stale second opinion.
     */
    function mpcrbm_get_selected_days() {
        if (typeof window.mpcrbmRefreshCarDetailsPrice === 'function') {
            window.mpcrbmRefreshCarDetailsPrice();
        }
    }


    let mpcrbm_off_dates = '';
    let mpcrbm_off_days = '';
    let mpcrbm_offDates = [];
    let mpcrbm_off_days_ary = [];
    // Kept separate from mpcrbm_offDates (closed days): those stay hard-disabled
    // with no way forward, but a day that's just fully booked should still be
    // clickable so the customer can join the waitlist — see mpcrbm_is_booked_date()
    // and its onDayCreate click handler below. Only present in
    // #mpcrbm_booked_dates on the car-details page (car_details.php); empty
    // (so a no-op) anywhere else this same script runs.
    let mpcrbm_booked_dates = '';
    let mpcrbm_bookedDates = [];
    let mpcrbm_i18n_booked_title = (typeof mpcrbmWaitlist !== 'undefined' && mpcrbmWaitlist.i18n && mpcrbmWaitlist.i18n.title)
        ? mpcrbmWaitlist.i18n.title
        : 'Already booked for this date';

    let parent = $('.mpcrbm_car_details');
    mpcrbm_off_dates = parent.find("#mpcrbm_off_dates").val();
    mpcrbm_off_days = parent.find( "#mpcrbm_off_days").val();
    mpcrbm_booked_dates = parent.find("#mpcrbm_booked_dates").val();

    if( mpcrbm_off_dates ){
        mpcrbm_offDates = mpcrbm_off_dates.split(',');
    }
    if( mpcrbm_off_days ){
        mpcrbm_off_days_ary = get_off_days_numbers( mpcrbm_off_days );
    }
    if( mpcrbm_booked_dates ){
        mpcrbm_bookedDates = mpcrbm_booked_dates.split(',');
    }

    /**
     * True if this calendar day is fully booked (as opposed to a closed
     * off-day) — matched the same way mpcrbm_is_disabled_booking_date() below
     * matches off-dates.
     */
    function mpcrbm_is_booked_date(date) {
        for (let i = 0; i < mpcrbm_bookedDates.length; i++) {
            const bd = (mpcrbm_bookedDates[i] || '').trim();
            if (!bd) {
                continue;
            }
            const parsed = new Date(bd);
            if (!isNaN(parsed.getTime()) &&
                parsed.getFullYear() === date.getFullYear() &&
                parsed.getMonth() === date.getMonth() &&
                parsed.getDate() === date.getDate()) {
                return true;
            }
        }
        return false;
    }

    /**
     * True if this calendar day is blocked (same rules as flatpickr disable[]).
     */
    function mpcrbm_is_disabled_booking_date(date) {
        if (mpcrbm_off_days_ary.includes(date.getDay())) {
            return true;
        }
        for (let i = 0; i < mpcrbm_offDates.length; i++) {
            const od = (mpcrbm_offDates[i] || '').trim();
            if (!od) {
                continue;
            }
            const parsed = new Date(od);
            if (!isNaN(parsed.getTime()) &&
                parsed.getFullYear() === date.getFullYear() &&
                parsed.getMonth() === date.getMonth() &&
                parsed.getDate() === date.getDate()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Earliest return date on or after start + minDayNum calendar days that is not disabled.
     */
    function mpcrbm_find_minimum_valid_return(start, minDayNum) {
        let candidate = new Date(start.getTime());
        candidate.setDate(candidate.getDate() + minDayNum);
        let guard = 0;
        while (mpcrbm_is_disabled_booking_date(candidate) && guard < 370) {
            candidate.setDate(candidate.getDate() + 1);
            guard++;
        }
        return candidate;
    }

    function mpcrbm_apply_pickup_date_to_dom(date, instance) {
        let startDate = instance.formatDate(date, "Y-m-d");
        let startDateDisplay = instance.formatDate(date, "D M d Y");

        $("#mpcrbm_start_date").val(startDateDisplay);
        $("#mpcrbm_start_date").closest('label').find('input[type="hidden"]').val(startDate).trigger('change');

        // #mpcrbm_start_date/#mpcrbm_return_date always carry a server-rendered
        // default (single_car_search_details.php: $mpcrbm_start_date = today,
        // $mpcrbm_end_date = today+1), so they're never actually *empty* — a
        // plain "is it blank" required check can never fail. This flag marks
        // a *real* user pick, which mpcrbm_validate_date_time_fields()
        // (mpcrbm_registration.js) checks instead.
        $("#mpcrbm_start_date").attr('data-user-selected', '1');

        // Clear the inline "required" notice (if shown) now that a date is picked.
        $('#mpcrbm_pickup_date_error').hide().closest('.input_select').removeClass('mpcrbm-field-invalid');
    }

    function mpcrbm_apply_return_date_to_dom(date, instance) {
        let endDate = instance.formatDate(date, "Y-m-d");
        let endDateDisplay = instance.formatDate(date, "D M d Y");

        $("#mpcrbm_return_date").val(endDateDisplay);
        $("#mpcrbm_return_date").closest('label').find('input[type="hidden"]').val(endDate).trigger('change');
        $("#mpcrbm_return_date").attr('data-user-selected', '1');

        // Clear the inline "required" notice (if shown) now that a date is picked.
        $('#mpcrbm_return_date_error').hide().closest('.input_select').removeClass('mpcrbm-field-invalid');

        if (parent.length > 0) {
            parent.find("#mpcrbm_car_details_continue_btn").fadeIn();
            parent.find("#mpcrbm_car_already_booked").fadeOut();

            let startDate = $("#mpcrbm_start_date").closest('label').find('input[type="hidden"]').val();
            let car_id = parent.find('[name="mpcrbm_post_id"]').val();
            let day_wise_price = parent.find('#mpcrbm_car_day_wise_price').val();

            if (startDate) {
                mpcrbm_get_car_qty(startDate, car_id, day_wise_price);
            }
        }

        mpcrbm_get_selected_days();
    }

    let selectors = ['#mpcrbm_start_date', '#mpcrbm_return_date'];
    let mpcrbm_start_date = $( "#mpcrbm_start_calendar_day").val();
    /*selectors.forEach(function (selector) {
        flatpickr( selector, {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            showMonths: window.innerWidth < 768 ? 1 : 2,
            locale: {
                firstDayOfWeek: mpcrbm_start_date // 1 = Monday
            }
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

                    if( parent.length > 0 ){
                        parent.find( "#mpcrbm_car_details_continue_btn").fadeIn();
                        parent.find( "#mpcrbm_car_already_booked").fadeOut();

                        let car_id = parent.find('[name="mpcrbm_post_id"]').val();
                        let day_wise_price = parent.find('#mpcrbm_car_day_wise_price').val();
                        if( endDate ){
                            mpcrbm_get_car_qty( startDate, car_id, day_wise_price );
                        }

                    }

                    mpcrbm_get_selected_days();
                }
            }
        });
    });*/


    function initFlatpickr() {

        let minDay = $('input[name="mpcrbm_minimum_booking_day"]').val();
        let pickupFlatpickr;
        let returnFlatpickr;

        let commonOptions = {
            minDate: "today",
            dateFormat: "Y-m-d",
            showMonths: window.innerWidth < 768 ? 1 : 2, // ✅ responsive
            locale: {
                firstDayOfWeek: mpcrbm_start_date
            },
            disable: [
                function(date) {
                    return mpcrbm_off_days_ary.includes(date.getDay());
                },
                ...mpcrbm_offDates.map(d => new Date(d)),
                ...mpcrbm_bookedDates.map(d => new Date(d))
            ],
            // Booked days stay disabled for normal selection above (picking one
            // as a real start/return date would just fail the server-side
            // check anyway), but flatpickr only skips its OWN click handling
            // for them — it doesn't block other listeners or set
            // pointer-events:none. So a plain click listener added here still
            // fires, letting a booked day open "Join Waitlist"
            // (mpcrbm-waitlist.js, via mpcrbm:car_fully_booked) instead of
            // just sitting there greyed out with no way forward.
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                if (!mpcrbm_is_booked_date(dayElem.dateObj)) {
                    return;
                }
                // A day before today is disabled for an entirely different
                // reason (it's in the past — minDate:"today" already covers
                // this) than a booked day is — joining a waitlist for a date
                // that has already gone by makes no sense. Without this
                // check, a past date that also happened to appear in the
                // booked-dates list (e.g. yesterday's booking) would still
                // get the amber "click to join waitlist" treatment below.
                let mpcrbm_today_midnight = new Date();
                mpcrbm_today_midnight.setHours(0, 0, 0, 0);
                if (dayElem.dateObj < mpcrbm_today_midnight) {
                    return;
                }
                // Only marked clickable when mpcrbm-waitlist.js is actually
                // loaded (enqueued solely when the admin has turned the
                // feature on) — otherwise leave the day looking like any
                // other disabled date, so nothing implies clickability that
                // wouldn't do anything.
                if (typeof mpcrbmWaitlist === 'undefined') {
                    return;
                }
                dayElem.classList.add('mpcrbm-day-booked');
                dayElem.title = mpcrbm_i18n_booked_title;
                dayElem.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(document).trigger('mpcrbm:car_fully_booked', {
                        carId: parent.find('[name="mpcrbm_post_id"]').val(),
                        pickup: fp.formatDate(dayElem.dateObj, 'Y-m-d'),
                        returnDate: ''
                    });
                });
            }
        };

        // Pick-up date: a single independent picker. Selecting a date only
        // fills the pick-up field and pushes a minimum-stay floor onto the
        // return picker — it never touches the return date itself.
        pickupFlatpickr = flatpickr('#mpcrbm_start_date', Object.assign({}, commonOptions, {
            onChange: function(selectedDates, dateStr, instance) {
                if (!selectedDates.length) {
                    return;
                }

                const start = selectedDates[0];
                mpcrbm_apply_pickup_date_to_dom(start, instance);

                const minDayNum = parseInt(minDay, 10) || 0;
                const minReturn = minDayNum > 0 ? mpcrbm_find_minimum_valid_return(start, minDayNum) : start;

                if (returnFlatpickr) {
                    returnFlatpickr.set('minDate', minReturn);

                    const currentReturn = returnFlatpickr.selectedDates[0];
                    if (currentReturn && currentReturn < minReturn) {
                        // Existing return pick no longer satisfies the minimum stay — clear it
                        // so the customer explicitly re-picks instead of silently booking short.
                        returnFlatpickr.clear(false);
                        $("#mpcrbm_return_date").val('').removeAttr('data-user-selected');
                        $("#mpcrbm_return_date").closest('label').find('input[type="hidden"]').val('').trigger('change');
                        if (parent.length > 0) {
                            parent.find("#mpcrbm_car_details_continue_btn").fadeOut();
                        }
                    } else {
                        mpcrbm_get_selected_days();
                    }
                }
            }
        }));

        // Return date: a single independent picker, constrained to stay on/after
        // the minimum-stay floor set above. Selecting a date only fills the return field.
        returnFlatpickr = flatpickr('#mpcrbm_return_date', Object.assign({}, commonOptions, {
            onChange: function(selectedDates, dateStr, instance) {
                if (!selectedDates.length) {
                    return;
                }

                mpcrbm_apply_return_date_to_dom(selectedDates[0], instance);
            }
        }));
    }
    initFlatpickr();
    window.addEventListener("resize", () => {

        selectors.forEach(function(selector) {
            if (selector._flatpickr) {
                selector._flatpickr.destroy();
            }
        });

        initFlatpickr();
    });

    function mpcrbm_get_car_qty( startDate, car_id, day_wise_price ){
        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax.ajax_url,
            data: {
                action: "mpcrbm_get_car_qty_by_date",
                startDate : startDate,
                car_id : car_id,
                day_wise_price : day_wise_price,
                nonce: mpcrbm_ajax.nonce
            },
            beforeSend: function() {
                console.log( 'Request' );
            },
            success: function(response) {
                // console.log( response );
                if( response.success ){
                    parent.find('#mpcrbm_car_quantity_holder').html( response.data );
                }
            },
            error: function(response) {
                console.log(response);
            }
        });
    }

});


