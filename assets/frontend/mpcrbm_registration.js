let mpcrbm_map;
let mpcrbm_map_window;
function mpcrbm_set_cookie_distance_duration(start_place = "", end_place = "") {
    mpcrbm_map = new google.maps.Map(document.getElementById("mpcrbm_map_area"), {
        mapTypeControl: false,
        center: mp_lat_lng,
        zoom: 15,
    });
    if (start_place && end_place) {
        let directionsService = new google.maps.DirectionsService();
        let directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(mpcrbm_map);
        let request = {
            origin: start_place,
            destination: end_place,
            travelMode: google.maps.TravelMode.DRIVING,
            unitSystem: google.maps.UnitSystem.METRIC,
        };
        let now = new Date();
        let time = now.getTime();
        let expireTime = time + 3600 * 1000 * 12;
        now.setTime(expireTime);
        directionsService.route(request, (result, status) => {
            if (status === google.maps.DirectionsStatus.OK) {
                let distance = result.routes[0].legs[0].distance.value;
                let kmOrMile = document.getElementById("mpcrbm_km_or_mile").value;
                let distance_text = result.routes[0].legs[0].distance.text;
                let duration = result.routes[0].legs[0].duration.value;
                var duration_text = result.routes[0].legs[0].duration.text;
                if (kmOrMile == 'mile') {
                    // Convert distance from kilometers to miles
                    var distanceInKilometers = distance / 1000;
                    var distanceInMiles = distanceInKilometers * 0.621371;
                    distance_text = distanceInMiles.toFixed(1) + ' miles'; // Format to 2 decimal places
                }
                // Build the set-cookie string:
                document.cookie =
                    "mpcrbm_distance=" + distance + "; expires=" + now + "; path=/; ";
                document.cookie =
                    "mpcrbm_distance_text=" +
                    distance_text +
                    "; expires=" +
                    now +
                    "; path=/; ";
                document.cookie =
                    "mpcrbm_duration=" + duration + ";  expires=" + now + "; path=/; ";
                document.cookie =
                    "mpcrbm_duration_text=" +
                    duration_text +
                    ";  expires=" +
                    now +
                    "; path=/; ";
                directionsRenderer.setDirections(result);
                jQuery(".mpcrbm_total_distance").html(distance_text);
                jQuery(".mpcrbm_total_time").html(duration_text);
                jQuery(".mpcrbm_distance_time").slideDown("fast");
            } else {
                //directionsRenderer.setDirections({routes: []})
                //alert('location error');
            }
        });
    } else if (start_place || end_place) {
        let place = start_place ? start_place : end_place;
        mpcrbm_map_window = new google.maps.InfoWindow();
        map = new google.maps.Map(document.getElementById("mpcrbm_map_area"), {
            center: mp_lat_lng,
            zoom: 15,
        });
        const request = {
            query: place,
            fields: ["name", "geometry"],
        };
        service = new google.maps.places.PlacesService(map);
        service.findPlaceFromQuery(request, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && results) {
                for (let i = 0; i < results.length; i++) {
                    mpcrbmCreateMarker(results[i]);
                }
                map.setCenter(results[0].geometry.location);
            }
        });
    } else {
        let directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(mpcrbm_map);
        //document.getElementById('mpcrbm_map_start_place').focus();
    }
    return true;
}
function mpcrbmCreateMarker(place) {
    if (!place.geometry || !place.geometry.location) return;
    const marker = new google.maps.Marker({
        map,
        position: place.geometry.location,
    });
    google.maps.event.addListener(marker, "click", () => {
        mpcrbm_map_window.setContent(place.name || "");
        mpcrbm_map_window.open(map);
    });
}
jQuery(document).ready(function($) {

    // Multi-location support functionality
    function updateDropoffLocations(pickupLocation) {
        if (!pickupLocation) return;

        // Get all vehicle IDs
        let vehicleIds = [];
        $('input[name="mpcrbm_post_id"]').each(function() {
            let postId = $(this).val();
            if (postId) vehicleIds.push(postId);
        });

        // If no specific vehicles selected, get all available vehicles
        if (vehicleIds.length === 0) {
            // This will be populated when vehicles are loaded
            return;
        }

        // Get available dropoff locations for the selected pickup location
        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax_url,
            data: {
                action: 'mpcrbm_get_dropoff_locations',
                pickup_location: pickupLocation,
                vehicle_ids: vehicleIds,
                nonce: mpcrbm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.locations) {
                    let dropoffSelect = $('#mpcrbm_manual_end_place');
                    dropoffSelect.empty();
                    dropoffSelect.append('<option selected disabled><?php esc_html_e(" Select Return Location", "car-rental-manager"); ?></option>');

                    response.data.locations.forEach(function(location) {
                        dropoffSelect.append('<option value="' + location.slug + '">' + location.name + '</option>');
                    });
                }
            }
        });
    }

    // Handle pickup location change for multi-location support
    $(document).on('change', '#mpcrbm_manual_start_place', function() {
        let pickupLocation = $(this).val();
        updateDropoffLocations(pickupLocation);
    });
    "use strict";

    // Initialize map and autocomplete
    $(".mpcrbm ul.input_select_list").hide();
    if ($("#mpcrbm_map_area").length > 0) {
        mpcrbm_set_cookie_distance_duration();
        if ($("#mpcrbm_map_start_place").length > 0 && $("#mpcrbm_map_end_place").length > 0) {
            let start_place = document.getElementById("mpcrbm_map_start_place");
            let end_place = document.getElementById("mpcrbm_map_end_place");
            let start_place_autoload = new google.maps.places.Autocomplete(start_place);
            let mpcrbm_restrict_search_to_country = $('[name="mpcrbm_restrict_search_country"]').val();
            let mpcrbm_country = $('[name="mpcrbm_country"]').val();

            if(mpcrbm_restrict_search_to_country == 'yes'){
                start_place_autoload.setComponentRestrictions({
                    country: [mpcrbm_country]
                });
            }

            google.maps.event.addListener(start_place_autoload, "place_changed", function() {
                mpcrbm_set_cookie_distance_duration(start_place.value, end_place.value);
            });

            let end_place_autoload = new google.maps.places.Autocomplete(end_place);
            if(mpcrbm_restrict_search_to_country == 'yes'){
                end_place_autoload.setComponentRestrictions({
                    country: [mpcrbm_country]
                });
            }

            google.maps.event.addListener(end_place_autoload, "place_changed", function() {
                mpcrbm_set_cookie_distance_duration(start_place.value, end_place.value);
            });
        }
    }

    // Handle vehicle selection
    $(document).on('click', '.mpcrbm_transport_select', function() {
        let $this = $(this);

        $(".mpcrbm_car_quantity").fadeOut();

        let parent = $this.closest('.mpcrbm_transport_search_area');
        let loader_parent = $this.closest('.mpcrbm_map_search_result');
        let target_summary = parent.find('.mpcrbm_transport_summary');
        let target_extra_service = parent.find('.mpcrbm_extra_service');
        let target_extra_service_summary = parent.find('.mpcrbm_extra_service_summary');

        let car_qty_control = parent.find('.mpcrbm_add_multiple_qty');
        $this.closest('.mpcrbm_add_multiple_qty').find( '.mpcrbm_car_quantity' ).fadeIn();

        car_qty_control.each(function () {
            let $this_qty = jQuery(this);
            $this_qty.find('[name="mpcrbm_multiple_car_qty[]"]').val( 1 );
        });

        const buttonOffset = $(this).offset().top;

        // Clear all extra services when selecting a new vehicle
        target_extra_service_summary.empty();
        target_extra_service.empty();

        // Reset all extra service inputs
        parent.find('[name="mpcrbm_extra_service[]"]').val('').trigger('change');
        parent.find('[name="mpcrbm_extra_service_qty[]"]').val('1');

        if ($this.hasClass('active_select')) {
            // Deselect vehicle
            $this.removeClass('active_select');
            // The "Details" card (templates/registration/choose_vehicles.php,
            // get_search_result.php) is now visible from page load instead of only
            // appearing once a car is picked, so it no longer slides away on deselect
            // either — it resets back to a neutral $0.00 state instead, same idea as
            // the persistent Book Now button going back to disabled rather than
            // disappearing.
            target_summary.find('.mpcrbm_product_name').html('');
            target_summary.find('.mpcrbm_product_price').html(mpcrbm_price_format(0));
            target_summary.find('.mpcrbm_product_total_price').html(mpcrbm_price_format(0));
            target_summary.find('#mpcrbm_selected_vehicle_row').hide();
            target_extra_service.slideUp(400);
            target_extra_service_summary.slideUp(400);
            parent.find('[name="mpcrbm_post_id"]').val('');
            parent.find('[name="mpcrbm_security_deposit_value"]').val(0);
            target_summary.find('.mpcrbm_security_deposit_summary').remove();
            checkAndToggleBookNowButton(parent);
        } else {
            // Select new vehicle
            parent.find('.mpcrbm_transport_select.active_select').removeClass('active_select');

            let transport_name  = $this.attr('data-transport-name');
            let transport_price = parseFloat($this.attr('data-transport-price'));
            let base_price      = parseFloat($this.attr('data-base-price'));
            if (isNaN(base_price)) { base_price = transport_price; }
            let post_id         = $this.attr('data-post-id');
            let security_deposit = parseFloat($this.attr('data-security-deposit')) || 0;

            // Determine one-way fee: always trust the PHP-baked data attributes (server already
            // computed the per-car fee for the current pickup/dropoff pair), then zero out only
            // when the "same location" checkbox is checked.
            let isSameLocChk = $('#mpcrbm_is_drop_off').is(':checked');
            let $feeField    = $('#mpcrbm_branch_one_way_fee');
            let oneWayFee    = isSameLocChk ? 0 : Math.max(0, transport_price - base_price);

            // Sync hidden field (create inside parent if missing — branch-search context has no booking form)
            if (!$feeField.length) {
                $feeField = $('<input type="hidden" id="mpcrbm_branch_one_way_fee" name="mpcrbm_branch_one_way_fee">').appendTo(parent);
            }
            $feeField.val(oneWayFee);

            // Store deposit in hidden input for later quantity/extra-service recalculations
            parent.find('[name="mpcrbm_security_deposit_value"]').val(security_deposit);

            // Build initial total: base price + one-way fee + deposit
            let initial_total = base_price + oneWayFee + security_deposit;

            // Update vehicle details in summary
            target_summary.find('.mpcrbm_product_name').html(transport_name);
            target_summary.find('.mpcrbm_product_price').html(mpcrbm_price_format(transport_price));
            target_summary.find('#mpcrbm_selected_vehicle_row').show();

            // Show or update deposit row in summary
            target_summary.find('.mpcrbm_security_deposit_summary').remove();
            if (security_deposit > 0) {
                target_summary.find('.mpcrbm_extra_service_summary').after(
                    '<div class="mpcrbm_security_deposit_summary"><div class="divider"></div><div class="justifyBetween"><span>Security Deposit:</span><span class="mpcrbm_security_deposit_price _textTheme">' + mpcrbm_price_format(security_deposit) + '</span></div></div>'
                );
            }

            target_summary.find('.mpcrbm_product_total_price').html(mpcrbm_price_format(initial_total));

            // "Total Rental Duration" row — mpcrbm_calculate_rental_days() (defined
            // below, already used for day-wise extra-service pricing) reads the
            // same start/return date+time fields this whole page shares.
            target_summary.find('.mpcrbm_car_day_value').text(mpcrbm_calculate_rental_days(parent));

            $this.addClass('active_select');
            parent.find('[name="mpcrbm_post_id"]').val(post_id).attr('data-price', base_price);
            checkAndToggleBookNowButton(parent);

            // Show summary sections
            target_summary.slideDown(400);
            target_extra_service.slideDown(400);
            target_extra_service_summary.slideDown(400);

            parent.find('.mpcrbm_car_qty_display').text( 'x1' );

            // Fetch available extra services
            $.ajax({
                type: 'POST',
                url: mpcrbm_ajax.ajax_url,
                data: {
                    action: 'mpcrbm_get_extra_service',
                    post_id: post_id,
                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader( loader_parent );
                },
                success: function(data) {
                    target_extra_service.html(data);
                    checkAndToggleBookNowButton(parent);
                    mpcrbm_loader_remove( loader_parent );

                    const targetOffset = $('.mpcrbm_book_now').offset().top;
                    const distance = Math.abs(targetOffset - buttonOffset);
                    const duration = Math.min(Math.max(distance * 0.5, 300), 1500);
                    $('html, body').animate({
                        scrollTop: $(window).scrollTop() + distance
                    }, duration);

                },
                error: function(response) {
                    console.log(response);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                }
            });
        }
    });

    $(document).on('change','#mpcrbm_is_drop_off', function() {
        let $parent = $(this).closest('.mpcrbm_transport_search_area');
        if ($(this).is(':checked')) {
            $('#mpcrbm_drop_off_location').hide();
            $('#mpcrbm-vertical-divide-location').hide();
            // Mirror start_place into end_place so cart/checkout see same location
            let startVal = $parent.find('[name="mpcrbm_start_place"]').val()
                        || $('[name="mpcrbm_start_place"]').first().val();
            if (startVal) {
                $parent.find('[name="mpcrbm_end_place"]').val(startVal);
                $('[name="mpcrbm_end_place"]').val(startVal);
            }
        } else {
            $('#mpcrbm_drop_off_location').show();
            $('#mpcrbm-vertical-divide-location').show();
            // Restore end_place from the visible dropoff select
            let dropoffVal = $('#mpcrbm_manual_end_place').val();
            if (dropoffVal) {
                $parent.find('[name="mpcrbm_end_place"]').val(dropoffVal);
                $('[name="mpcrbm_end_place"]').val(dropoffVal);
            }
        }
    });

    // Marks step 2 ("Choose a vehicle") active on the #mpcrbm_progress_bar_holder
    // step indicator, cumulatively (step 1 stays active too) — without the rest
    // of active_next_tab()'s panel-switching (mp_global/assets/mp_style/
    // mpcrbm_global.js, triggered via ".nextTab_next" click). That function
    // slides one [data-tabs-next] panel out and another in, which is right for
    // the redirect/non-ajax flow (a new panel gets appended for step 2/3) but
    // wrong for ajax_search='yes': results append inline into the *same*
    // step-1 panel, so there's no separate panel to switch to.
    function mpcrbm_advance_progress_bar_to_step(parent, stepIndex) {
        parent.find('.tabListsNext:first').children('[data-tabs-target-next]').each(function (i) {
            $(this).toggleClass('active', (i + 1) <= stepIndex);
        });
    }

    // Pickup Date / Return Date required check for the main search widget
    // (get_details_new.php). Same reasoning as mpcrbm_validate_date_time_fields
    // below (defined later in this file, but function declarations are
    // hoisted so calling it here is safe): #mpcrbm_start_date/#mpcrbm_return_date
    // are readonly and always carry a server-rendered default, so a plain
    // "is it blank" check can never fail — this checks the data-user-selected="1"
    // flag that date-picker.js only sets on a real pick. Unlike
    // mpcrbm_validate_date_time_fields, this only gates the two date fields
    // (not time), matching what was actually asked to be required here.
    function mpcrbm_validate_search_dates(parent) {
        let fields = [
            { input: parent.find('#mpcrbm_start_date'), error: parent.find('#mpcrbm_pickup_date_error') },
            { input: parent.find('#mpcrbm_return_date'), error: parent.find('#mpcrbm_return_date_error') }
        ];

        let $firstInvalidWrap = null;

        fields.forEach(function (field) {
            if (!field.input.length || !field.input.is(':visible')) {
                return;
            }
            let $wrap = field.error.closest('.input_select');
            field.error.hide();
            $wrap.removeClass('mpcrbm-field-invalid');

            if (field.input.attr('data-user-selected') !== '1') {
                field.error.show();
                $wrap.addClass('mpcrbm-field-invalid');
                if (!$firstInvalidWrap) {
                    $firstInvalidWrap = $wrap;
                }
            }
        });

        if ($firstInvalidWrap && $firstInvalidWrap.length) {
            $('html, body').animate({ scrollTop: $firstInvalidWrap.offset().top - 120 }, 300);
            return false;
        }

        return true;
    }

    // Handle get vehicle button
    $(document).on("click", "#mpcrbm_get_vehicle", function() {
        let parent = $(this).closest(".mpcrbm_transport_search_area");

        if (!mpcrbm_validate_search_dates(parent)) {
            return;
        }
        let mpcrbm_enable_return_in_different_date = parent
            .find('[name="mpcrbm_enable_return_in_different_date"]')
            .val();

        let target = parent.find(".tabsContentNext");
        // .tabsContentNext also carries its own padding (--dmp) around the visible
        // search-area card, so a loader on it overlaid a plain rectangle past the
        // card's rounded corners/shadow — used for the loader only, .append(data)
        // calls below still target .tabsContentNext (the actual step-2/3 content
        // needs to land there, not inside the card).
        let loaderTarget = parent.find(".mpcrbm_search_area");
        let target_date = parent.find("#mpcrbm_map_start_date");
        let return_target_date = parent.find("#mpcrbm_map_return_date");
        let target_time = parent.find("#mpcrbm_map_start_time");
        let return_target_time = parent.find("#mpcrbm_map_return_time");
        let start_place;
        let end_place;
        let price_based = parent.find('[name="mpcrbm_price_based"]').val();
        let two_way = parent.find('[name="mpcrbm_taxi_return"]').val();
        let waiting_time = parent.find('[name="mpcrbm_waiting_time"]').val();
        let fixed_time = parent.find('[name="mpcrbm_fixed_hours"]').val();
        let ajax_search = parent.find('[name="mpcrbm_enable_ajax_search"]').val();


        let progress_bar = $("#mpcrbm_progress_bar_display").val();

        let mpcrbm_enable_view_search_result_page = parent
            .find('[name="mpcrbm_enable_view_search_result_page"]')
            .val();

        let same_end_place = false;
        if ($('#mpcrbm_is_drop_off').is(':checked')) {
            same_end_place = true;
        }

        if (price_based === "manual") {
            start_place = document.getElementById("mpcrbm_manual_start_place");
            if( same_end_place ){
                end_place = start_place;
            }else{
                end_place = document.getElementById("mpcrbm_manual_end_place");
            }

        } else {
            start_place = document.getElementById("mpcrbm_map_start_place");
            if( same_end_place ){
                end_place = start_place;
            }else{
                end_place = document.getElementById("mpcrbm_map_end_place");
            }

        }
        let start_date = target_date.val();
        let return_date = return_target_date.val();
        let return_time = return_target_time.val();

        let start_time = target_time.val();
        if (!start_date) {
            target_date.trigger("click");
        } else if (!start_time) {
            parent
                .find("#mpcrbm_map_start_time")
                .closest(".input_select")
                .find("input.formControl")
                .trigger("click");
        } else if (!return_date) {
            if (mpcrbm_enable_return_in_different_date == 'yes' && two_way != 1) {
                return_target_date.trigger("click");
            }
        } else if (!return_time) {
            if (mpcrbm_enable_return_in_different_date == 'yes' && two_way != 1) {
                parent
                    .find("#mpcrbm_map_return_time")
                    .closest(".input_select")
                    .find("input.formControl")
                    .trigger("click");
            }
        } else if (!start_place.value) {
            start_place.focus();
        } else if (!end_place.value) {
            end_place.focus();
        } else {
            mpcrbm_loader(loaderTarget);
            mpcrbm_content_refresh(parent);
            if (price_based !== "manual") {
                mpcrbm_set_cookie_distance_duration(start_place.value, end_place.value);
            }
            //let price_based = parent.find('[name="mpcrbm_price_based"]').val();
            function getGeometryLocation(address, callback) {
                var geocoder = new google.maps.Geocoder();
                var coordinatesOfPlace = {};
                geocoder.geocode({ address: address }, function (results, status) {
                    if (status === "OK") {
                        var latitude = results[0].geometry.location.lat();
                        var longitude = results[0].geometry.location.lng();
                        coordinatesOfPlace["latitude"] = latitude;
                        coordinatesOfPlace["longitude"] = longitude;
                        // Call the callback function with the coordinates
                        callback(coordinatesOfPlace);
                    } else {
                        console.error(
                            "Geocode was not successful for the following reason: " + status
                        );
                        // Call the callback function with null to indicate failure
                        callback(null);
                    }
                });
            }
            // Define a function to get the coordinates asynchronously and return a Deferred object
            function getCoordinatesAsync(address) {
                var deferred = $.Deferred();
                getGeometryLocation(address, function (coordinates) {
                    deferred.resolve(coordinates);
                });
                return deferred.promise();
            }

            if (price_based !== 'manual') {

                $.when(
                    getCoordinatesAsync(start_place.value),
                    getCoordinatesAsync(end_place.value)
                ).done(function (startCoordinates, endCoordinates) {
                    if (start_place.value && end_place.value && start_date && start_time && return_date && return_time) {
                        let actionValue;
                        if ( mpcrbm_enable_view_search_result_page == 'No' ) {

                            actionValue = "mpcrbm_get_map_search_result";

                            $.ajax({
                                type: "POST",
                                url: mpcrbm_ajax_url,
                                data: {
                                    action: actionValue,
                                    start_place: start_place.value,
                                    start_place_coordinates: startCoordinates,
                                    end_place_coordinates: endCoordinates,
                                    end_place: end_place.value,
                                    start_date: start_date,
                                    start_time: start_time,
                                    price_based: price_based,
                                    two_way: two_way,
                                    waiting_time: waiting_time,
                                    fixed_time: fixed_time,
                                    return_date: return_date,
                                    return_time: return_time,
                                    ajax_search: ajax_search,
                                },
                                beforeSend: function () {
                                    //mpcrbm_loader(target);
                                },
                                success: function (data) {
                                    target
                                        .append(data)
                                        .promise()
                                        .done(function () {
                                            mpcrbm_loader_remove(loaderTarget);

                                            if( ajax_search !== 'yes' ) {
                                                parent.find(".nextTab_next").trigger("click");
                                            }
                                            // Always advance the step indicator itself, regardless of
                                            // whether .nextTab_next's full panel-switch (active_next_tab(),
                                            // mp_global/assets/mp_style/mpcrbm_global.js) ran above — that
                                            // function only marks step 2 active as a side effect of finding
                                            // and sliding in a [data-tabs-next="#mpcrbm_search_result"]
                                            // panel, so anything that keeps it from matching (timing,
                                            // markup variations) silently leaves the indicator on step 1.
                                            mpcrbm_advance_progress_bar_to_step(parent, 2);

                                            if( progress_bar === 'yes' ) {
                                                $('#mpcrbm_progress_bar_holder').css('display', 'flex');
                                            }
                                        });
                                },
                                error: function (response) {
                                    console.log(response);
                                },
                            });
                        } else {
                            actionValue = "mpcrbm_get_map_search_result_redirect";
                            $.ajax({
                                type: "POST",
                                url: mpcrbm_ajax_url,
                                data: {
                                    action: actionValue,
                                    start_place: start_place.value,
                                    start_place_coordinates: startCoordinates,
                                    end_place_coordinates: endCoordinates,
                                    end_place: end_place.value,
                                    start_date: start_date,
                                    start_time: start_time,
                                    price_based: price_based,
                                    two_way: two_way,
                                    waiting_time: waiting_time,
                                    fixed_time: fixed_time,
                                    return_date: return_date,
                                    return_time: return_time,
                                    progress_bar: progress_bar,
                                    mpcrbm_enable_view_search_result_page: mpcrbm_enable_view_search_result_page,
                                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                                },
                                beforeSend: function () {
                                    mpcrbm_loader(loaderTarget);
                                },
                                success: function (data) {
                                    // Check if response is an error object
                                    if (data && typeof data === 'object' && data.success === false) {
                                        // Handle error response
                                        if (data.message) {
                                            alert('Error: ' + data.message);
                                        } else {
                                            alert('An error occurred. Please try again.');
                                        }
                                        return;
                                    }

                                    // Handle successful response
                                    if (typeof data === 'string') {
                                        var cleanedURL = data.replace(/"/g, ""); // Remove all double quotes from the string
                                        window.location.href = cleanedURL; // Redirect to the URL received from the server
                                    } else if (data && typeof data === 'object' && data.url) {
                                        window.location.href = data.url;
                                    } else {
                                        console.error('Invalid response format:', data);
                                    }

                                    // No progress-bar reveal here: this branch always navigates away via
                                    // window.location.href above, so showing it just flashes the step
                                    // indicator on the page for a moment before the browser leaves it —
                                    // setting display:flex here has no effect on the destination page.
                                },
                                error: function (response) {
                                    console.log(response);
                                },
                            });
                        }
                    }
                });
            } else {
                if (start_place.value && end_place.value && start_date && start_time && return_date && return_time) {

                    let actionValue;
                    if ( mpcrbm_enable_view_search_result_page === 'No' ) {
                        actionValue = "mpcrbm_get_map_search_result";

                        $.ajax({
                            type: "POST",
                            url: mpcrbm_ajax_url,
                            data: {
                                action: actionValue,
                                start_place: start_place.value,
                                end_place: end_place.value,
                                start_date: start_date,
                                start_time: start_time,
                                price_based: price_based,
                                two_way: two_way,
                                waiting_time: waiting_time,
                                fixed_time: fixed_time,
                                return_date: return_date,
                                return_time: return_time,
                                ajax_search: ajax_search,
                                mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                            },
                            beforeSend: function () {
                                //mpcrbm_loader(target);
                            },
                            success: function (data) {

                                if( ajax_search === 'yes' ){
                                    $("#mpcrbm_search_result").empty();
                                    // $("#mpcrbm_search_result").hide();
                                    $("#mpcrbm_empty_result").hide();

                                    // $("#mpcrbm_search_result").append('<div class="mpcrbm_search_empty_data">Search Data Loading...</div>');
                                    jQuery(parent).find(".mpcrbm_map_search_result").remove();
                                    jQuery(parent).find(".mpcrbm_order_summary").remove();
                                }

                                target
                                    .append(data)
                                    .promise()
                                    .done(function () {
                                        mpcrbm_loader_remove(loaderTarget);
                                        if( ajax_search !== 'yes' ) {
                                            parent.find(".nextTab_next").trigger("click");
                                        }
                                        // Always advance the step indicator itself, regardless of
                                        // whether .nextTab_next's full panel-switch (active_next_tab(),
                                        // mp_global/assets/mp_style/mpcrbm_global.js) ran above — see
                                        // note in the other mpcrbm_get_map_search_result success handler
                                        // above (same pattern, price_based !== 'manual' branch).
                                        mpcrbm_advance_progress_bar_to_step(parent, 2);
                                        if( progress_bar === 'yes') {
                                            $('#mpcrbm_progress_bar_holder').css('display', 'flex');
                                        }
                                    });
                            },
                            error: function (response) {
                                console.log(response);
                            },
                        });
                    } else {
                        actionValue = "mpcrbm_get_map_search_result_redirect";
                        $.ajax({
                            type: "POST",
                            url: mpcrbm_ajax_url,
                            data: {
                                action: actionValue,
                                start_place: start_place.value,
                                end_place: end_place.value,
                                start_date: start_date,
                                start_time: start_time,
                                price_based: price_based,
                                two_way: two_way,
                                waiting_time: waiting_time,
                                fixed_time: fixed_time,
                                return_date: return_date,
                                return_time: return_time,
                                progress_bar: progress_bar,
                                mpcrbm_enable_view_search_result_page: mpcrbm_enable_view_search_result_page,
                                mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                            },
                            beforeSend: function (xhr, settings) {
                                mpcrbm_loader(loaderTarget);
                            },
                            success: function (data) {
                                // Check if response is an error object
                                if (data && typeof data === 'object' && data.success === false) {
                                    // Handle error response
                                    if (data.message) {
                                        alert('Error: ' + data.message);
                                    } else {
                                        alert('An error occurred. Please try again.');
                                    }
                                    return;
                                }

                                // Handle successful response
                                if (typeof data === 'string') {
                                    window.location.href = data.replace(/"/g, ""); // Remove all double quotes from the string
                                } else if (data && typeof data === 'object' && data.url) {
                                    window.location.href = data.url;
                                } else {
                                    console.error('Invalid response format:', data);
                                }

                                // No progress-bar reveal here: this branch always navigates away via
                                // window.location.href above, so showing it just flashes the step
                                // indicator on the page for a moment before the browser leaves it —
                                // setting display:flex here has no effect on the destination page.
                            },
                            error: function (response) {
                                console.log(response);
                            },
                        });
                    }
                }
            }
        }
    });

    // Handle date and time changes
    $(document).on("change", "#mpcrbm_map_start_date", function() {
        // Clear the time slots list
        $('#mpcrbm_map_start_time').siblings('.start_time_list').empty();
        $('.start_time_input,#mpcrbm_map_start_time').val('');
        let mpcrbm_enable_return_in_different_date = $('[name="mpcrbm_enable_return_in_different_date"]').val();
        let mpcrbm_buffer_end_minutes = $('[name="mpcrbm_buffer_end_minutes"]').val();
        let mpcrbm_first_calendar_date = $('[name="mpcrbm_first_calendar_date"]').val();
        var selectedDate = $('#mpcrbm_map_start_date').val();
        var formattedDate = $.datepicker.parseDate('yy-mm-dd', selectedDate);

        // Get today's date in YYYY-MM-DD format
        var today = new Date();
        var day = String(today.getDate()).padStart(2, '0');
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var year = today.getFullYear();
        var currentDate = year + '-' + month + '-' + day;

        if (selectedDate == currentDate) {
            var currentTime = new Date();
            var currentHour = currentTime.getHours();
            var currentMinutes = currentTime.getMinutes();

            // Format minutes to always have two digits (e.g., 5 -> 05)
            var formattedMinutes = String(currentMinutes).padStart(2, '0');

            // Combine hours and formatted minutes
            var currentTimeFormatted = currentHour + '.' + formattedMinutes;
            $('.start_time_list-no-dsiplay li').each(function () {
                const timeValue = parseFloat($(this).attr('data-value'));
                if (timeValue > parseFloat(currentTimeFormatted) && timeValue >= mpcrbm_buffer_end_minutes / 60) {
                    $('#mpcrbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                }
            });
        } else {
            if(selectedDate  == mpcrbm_first_calendar_date){
                console.log(mpcrbm_first_calendar_date);
                $('.start_time_list-no-dsiplay li').each(function () {
                    const timeValue = parseFloat($(this).attr('data-value'));
                    if (timeValue >= mpcrbm_buffer_end_minutes / 60) {
                        $('#mpcrbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                    }
                });
            }else{
                $('.start_time_list-no-dsiplay li').each(function () {
                    $('#mpcrbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                });
            }


        }

        // Update the return date picker if needed
        if (mpcrbm_enable_return_in_different_date == 'yes') {
            $('#mpcrbm_return_date').datepicker('option', 'minDate', formattedDate);
        }

        let parent = $(this).closest(".mpcrbm_transport_search_area");
        mpcrbm_content_refresh(parent);
        parent
            .find("#mpcrbm_map_start_time")
            .closest(".input_select")
            .find("input.formControl")
            .trigger("click");
    });

    $(document).on("change", "#mpcrbm_map_return_date", function() {
        let mpcrbm_enable_return_in_different_date = $('[name="mpcrbm_enable_return_in_different_date"]').val();

        if (mpcrbm_enable_return_in_different_date == 'yes') {
            var selectedTime = parseFloat($('#mpcrbm_map_start_time').val());
            var selectedDate = $('#mpcrbm_map_start_date').val();
            var dateValue = $('#mpcrbm_map_return_date').val();

            // Check if the return date is the same as the pickup date
            if (selectedDate == dateValue) {
                $('#return_time_list').show();
                // Clear existing options
                $('#mpcrbm_map_return_time').siblings('.input_select_list').empty();
                $('.mpcrbm_map_return_time_input').val('');
                // If return date is the same as the pickup date, show only times after pickup time
                $('.input_select_list li').each(function () {
                    var timeValue = parseFloat($(this).attr('data-value'));
                    if (timeValue > selectedTime) {
                        $('#mpcrbm_map_return_time').siblings('.input_select_list').append($(this).clone());
                    }
                });
            } else {
                // Clear existing options
                $('#mpcrbm_map_return_time').siblings('.input_select_list').empty();
                $('.mpcrbm_map_return_time_input').val('');
                $('.return_time_list-no-dsiplay li').each(function () {
                    var timeValue = parseFloat($(this).attr('data-value'));
                    $('#mpcrbm_map_return_time').siblings('.input_select_list').append($(this).clone());
                });
            }
        }

        // Trigger refresh and display logic
        let parent = $(this).closest(".mpcrbm_transport_search_area");
        mpcrbm_content_refresh(parent);
        parent.find("#mpcrbm_map_return_time").closest(".input_select").find("input.formControl").trigger("click");
    });

    // Handle time selection
    $(document).on("click", ".start_time_list li", function() {
        let selectedValue = $(this).attr('data-value');
        $('#mpcrbm_map_start_time').val(selectedValue).trigger('change');

        mpcrbm_get_selected_days();

        // #mpcrbm_map_start_time/#mpcrbm_map_return_time always carry a
        // server-rendered default, so they're never actually *empty* — this
        // flag marks a *real* user pick for mpcrbm_validate_date_time_fields()
        // to check instead of a plain "is it blank" test.
        $('#mpcrbm_map_start_time').attr('data-user-selected', '1');

        // Clear the inline "required" notice (if shown) now that a time is picked.
        $('#mpcrbm_pickup_time_error').hide().closest('.input_select').removeClass('mpcrbm-field-invalid');

        // Guided single-date flow, car-details page only (single_car_search_details.php
        // renders .mpcrbm-date-step-return only there — get_details_new.php, the main
        // search widget on other pages, has no such element, so this is a no-op
        // everywhere else). Once a pick-up time is chosen, reveal the Return step
        // instead of showing both at once.
        let $returnStep = $('#mpcrbm_date_step_return.mpcrbm-date-step-return');
        if ($returnStep.length) {
            $(this).closest('.mpcrbm-date-step-pickup').addClass('is-complete');
            if ($returnStep.hasClass('is-locked')) {
                $returnStep.removeClass('is-locked').hide().slideDown(300);
            }
        }
    });

    $(document).on("click", ".return_time_list li", function() {
        let selectedValue = $(this).attr('data-value');
        $('#mpcrbm_map_return_time').val(selectedValue).trigger('change');
        $('#mpcrbm_map_return_time').attr('data-user-selected', '1');

        mpcrbm_get_selected_days();

        // Clear the inline "required" notice (if shown) now that a time is picked.
        $('#mpcrbm_return_time_error').hide().closest('.input_select').removeClass('mpcrbm-field-invalid');

        // Guided single-date flow, car-details page only (see note above).
        let $returnStep = $(this).closest('.mpcrbm-date-step-return');
        if ($returnStep.length) {
            $returnStep.addClass('is-complete');

            // Deferred to the next tick: this same click also matches the generic
            // "div.mpcrbm .input_select .input_select_list li" handler
            // (mp_global/assets/mp_style/mpcrbm_global.js), which is what actually
            // writes the human-readable text (e.g. "12:00 am") into the visible
            // date/time inputs this reads below — deferring avoids a registration-
            // order race where this could read the value from *before* the click.
            setTimeout(function () {
                let $summary = $('#mpcrbm_date_range_summary');
                if (!$summary.length) {
                    return;
                }
                let pickupDate = $('#mpcrbm_start_date').val();
                let returnDate = $('#mpcrbm_return_date').val();
                let pickupTime = $('#mpcrbm_map_start_time').closest('.input_select').find('input.formControl').val();
                let returnTime = $('#mpcrbm_map_return_time').closest('.input_select').find('input.formControl').val();

                $('#mpcrbm_date_range_text').text(pickupDate + ' → ' + returnDate);
                $('#mpcrbm_summary_pickup_time').text(pickupTime);
                $('#mpcrbm_summary_return_time').text(returnTime);

                if ($summary.is(':hidden')) {
                    $summary.slideDown(300);
                }
            }, 0);
        }
    });

    // Handle place changes
    $(document).on("change", "#mpcrbm_map_start_place, #mpcrbm_map_end_place", function() {
        let parent = $(this).closest(".mpcrbm_transport_search_area");
        mpcrbm_content_refresh(parent);
        let start_place = parent.find("#mpcrbm_map_start_place").val();
        let end_place = parent.find("#mpcrbm_map_end_place").val();
        if (start_place || end_place) {
            if (start_place) {
                mpcrbm_set_cookie_distance_duration(start_place);
                parent.find("#mpcrbm_map_end_place").focus();
            } else {
                mpcrbm_set_cookie_distance_duration(end_place);
                parent.find("#mpcrbm_map_start_place").focus();
            }
        } else {
            parent.find("#mpcrbm_map_start_place").focus();
        }
    });

    // Handle car quantity changes
    $(document).on('change', '.mpcrbm_transport_search_area [name="mpcrbm_multiple_car_qty[]"]', function () {
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        let $qty_input = $(this).closest('.mpcrbm_add_multiple_qty').find('[name="mpcrbm_multiple_car_qty[]"]');
        let qty = parseInt($qty_input.val()) || 1;
        mpcrbm_price_calculation( parent );
    });
    // Handle car quantity changes
    $(document).on('change', '.mpcrbm_car_details [name="mpcrbm_get_car_qty"]', function () {
        let parent = $(this).closest('.mpcrbm_car_details');
        let $qty_input = parent.find('[name="mpcrbm_get_car_qty"]').val();
        let qty = parseInt($qty_input) || 1;
        // mpcrbm_price_calculation( parent );
        mpcrbm_price_calculation_car_details_page(parent, qty);
        parent.find( '.mpcrbm_car_qty_display' ).text( 'X'+qty );
    });

    // Handle extra service quantity changes
    $(document).on('change', '.mpcrbm_transport_search_area [name="mpcrbm_extra_service_qty[]"]', function () {
        $(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service[]"]').trigger('change');
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        checkAndToggleBookNowButton(parent);
    });

    // Handle delivery/collection checkbox toggle in the search-result "choose vehicle"
    // flow (registration/extra_service.php, AJAX-loaded into .mpcrbm_transport_search_area)
    $(document).on('change', '.mpcrbm_transport_search_area .mpcrbm_dc_checkbox', function () {
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        mpcrbm_price_calculation(parent);
    });

    // Handle extra service quantity changes
    $(document).on('change', '.mpcrbm_car_details [name="mpcrbm_extra_service_qty[]"]', function () {
        $(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service[]"]').trigger('change');
    });


    // Handle extra service selection
    $(document).on('change', '.mpcrbm_transport_search_area [name="mpcrbm_extra_service[]"]', function () {
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        let service_id = $(this).data('value');
        let service_value = $(this).val();
        let $qty_input = $(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service_qty[]"]');
        let qty = parseInt($qty_input.val()) || 1;
        let price_per_item = parseFloat($qty_input.data('price')) || 0;
        let total_price_for_item = price_per_item * qty;
        let $button = $(this).closest('[data-extra-item]');

        if (service_value) {
            let service_name_display = service_id;
            let summary_item = parent.find('[data-extra-service-id="' + service_id + '"]');

            if (summary_item.length === 0) {
                let new_item_html = `
                    <div class="_textColor_4_dFlex_flexWrap_justifyBetween book-items" data-extra-service-id="${service_id}" data-price="${price_per_item}">
                        <p class="_dFlex_alignCenter">
                            <span class="fas fa-check-square _textTheme_mR_xs"></span>
                            <span class="">${service_name_display}</span> &nbsp;
                            <span class="textTheme ex_service_qty">x${qty}</span>
                        </p>
                        <p>
                            
                            <span class="textTheme"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol"></span>${mpcrbm_price_format(total_price_for_item)}</span></span>
                        </p>
                    </div>
                `;
                parent.find('.mpcrbm_extra_service_summary').append(new_item_html);
            } else {
                summary_item.find('.ex_service_qty').text('x' + qty);
                summary_item.find('.woocommerce-Price-amount').html(mpcrbm_price_format(total_price_for_item));
            }

            $button.addClass('mActive');
            $button.find('[data-text]').text($button.data('close-text'));
            if ($button.data('close-icon')) {
                $button.find('[data-icon]').attr('class', 'mL_xs ' + $button.data('close-icon'));
            }
        } else {
            let summary_item = parent.find('[data-extra-service-id="' + service_id + '"]');
            if (summary_item.length > 0) {
                summary_item.slideUp(350, function() {
                    $(this).remove();
                });
            }

            $button.removeClass('mActive');
            $button.find('[data-text]').text($button.data('open-text'));
            if ($button.data('open-icon')) {
                $button.find('[data-icon]').attr('class', 'mL_xs ' + $button.data('open-icon'));
            }
        }

        mpcrbm_price_calculation(parent);
        checkAndToggleBookNowButton(parent);
    });

    // Handle extra service selection
    $(document).on('change', '.mpcrbm_car_details [name="mpcrbm_extra_service[]"]', function () {
        let parent = $(this).closest('.mpcrbm_car_details');
        let $qty_input = parent.find('[name="mpcrbm_get_car_qty"]').val();
        let qty = parseInt($qty_input) || 1;
        // mpcrbm_price_calculation( parent );
        mpcrbm_price_calculation_car_details_page(parent, qty);
    });

    // Handle delivery/collection checkbox toggle (registration/delivery_collection_display.php)
    $(document).on('change', '.mpcrbm_car_details .mpcrbm_dc_checkbox', function () {
        let parent = $(this).closest('.mpcrbm_car_details');
        let qty = parseInt(parent.find('[name="mpcrbm_get_car_qty"]').val()) || 1;
        mpcrbm_price_calculation_car_details_page(parent, qty);
    });


    function mpcrbm_number_of_car_booked(parent) {
        let total_car_qty = 0;

        parent.find(".mpcrbm_add_multiple_qty").each(function () {
            let $this = jQuery(this);
            let selectBtn = $this.find(".mpcrbm_transport_select");

            if (selectBtn.hasClass("active_select")) {
                let target_car_qty = $this.find('[name="mpcrbm_multiple_car_qty[]"]');
                let qty = parseInt(target_car_qty.val()) || 0;
                total_car_qty += qty;
            }
        });

        parent.find('#mpcrbm_selected_car_quantity').val( total_car_qty );
        parent.find('.mpcrbm_car_qty_display').text( 'x'+total_car_qty );
        console.log( total_car_qty );
        if( total_car_qty === 0 ){
            return  1;
        }else{
            return total_car_qty;
        }



    }

    // Computes the selected rental duration (in whole days) directly from the start/return
    // date+time fields. Used for day-wise extra-service pricing estimates. Reading straight
    // from these fields (instead of the #mpcrbm_car_selected_day label, which only exists on
    // the single car-details page and only refreshes after a return-time pick) keeps this
    // working consistently on both the search-results flow and the car-details page.
    function mpcrbm_calculate_rental_days(parent) {
        let startDate = parent.find('#mpcrbm_map_start_date').first().val() || $('#mpcrbm_map_start_date').first().val();
        let endDate = parent.find('#mpcrbm_map_return_date').first().val() || $('#mpcrbm_map_return_date').first().val();
        if (!startDate || !endDate) {
            return 1;
        }
        let start_time = parseFloat(parent.find('#mpcrbm_map_start_time').first().val() || $('#mpcrbm_map_start_time').first().val()) || 0;
        let return_time = parseFloat(parent.find('#mpcrbm_map_return_time').first().val() || $('#mpcrbm_map_return_time').first().val()) || 0;
        let startDateTime = new Date(startDate);
        startDateTime.setHours(start_time);
        let endDateTime = new Date(endDate);
        endDateTime.setHours(return_time);
        let diffMs = endDateTime - startDateTime;
        if (isNaN(diffMs) || diffMs <= 0) {
            return 1;
        }
        return Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
    }

    // Price calculation function
    function mpcrbm_price_calculation(parent) {
        let number_of_car = mpcrbm_number_of_car_booked( parent );

        let target_summary = parent.find(".mpcrbm_transport_summary");
        let total = 0;
        let post_id = parseInt(parent.find('[name="mpcrbm_post_id"]').val());
        if (post_id > 0) {
            total = total + parseFloat(parent.find('[name="mpcrbm_post_id"]').attr("data-price"));

            total = total * number_of_car;

            parent.find(".mpcrbm_extra_service_item").each(function () {
                let service_name = jQuery(this).find('[name="mpcrbm_extra_service[]"]').val();
                if (service_name) {
                    let ex_target = jQuery(this).find('[name="mpcrbm_extra_service_qty[]');
                    let ex_qty = parseInt(ex_target.val());
                    let ex_price = ex_target.data("price");
                    ex_price = ex_price && ex_price > 0 ? ex_price : 0;
                    let ex_days = ex_target.attr("data-price-type") === "day" ? mpcrbm_calculate_rental_days(parent) : 1;
                    total = total + parseFloat(ex_price) * ex_qty * ex_days;
                }
            });

            let deposit = parseFloat(parent.find('[name="mpcrbm_security_deposit_value"]').val()) || 0;
            if (deposit > 0) {
                let total_deposit = deposit * number_of_car;
                total = total + total_deposit;
                let deposit_row = target_summary.find('.mpcrbm_security_deposit_summary');
                if (deposit_row.length > 0) {
                    deposit_row.find('.mpcrbm_security_deposit_price').html(mpcrbm_price_format(total_deposit));
                }
            }

            // Add one-way fee when different locations are selected
            let oneWayFee = parseFloat($('#mpcrbm_branch_one_way_fee').val()) || 0;
            if (oneWayFee > 0) {
                total = total + oneWayFee * number_of_car;
            }

            let basePerCar = total / number_of_car - (oneWayFee > 0 ? oneWayFee : 0);
            ['delivery', 'collection'].forEach(function (kind) {
                let $box = parent.find('[name="mpcrbm_' + kind + '_requested"]');
                let $row = $('#mpcrbm_car_' + kind + '_fee_row');
                if ($box.length && $box.is(':checked')) {
                    let feeVal = parseFloat($box.data('fee')) || 0;
                    let fee = $box.data('fee-type') === 'percentage' ? (basePerCar * feeVal / 100) : feeVal;
                    if (fee > 0) {
                        total = total + fee * number_of_car;
                        if ($row.length) {
                            $('#mpcrbm_car_' + kind + '_fee_display').html(mpcrbm_price_format(fee));
                            $row.show();
                        }
                    } else if ($row.length) {
                        $row.hide();
                    }
                } else if ($row.length) {
                    $row.hide();
                }
            });
        }
        target_summary.find(".mpcrbm_product_total_price").html(mpcrbm_price_format(total));
    }

    // When the one-way fee changes (location re-selected), recalculate the active vehicle's total
    $(document).on('mpcrbm_one_way_fee_changed', function (e, fee) {
        let $active = $('.mpcrbm_transport_select.active_select');
        if (!$active.length) { return; }
        let parent = $active.closest('.mpcrbm_transport_search_area');
        if (!parent.length) { return; }
        mpcrbm_price_calculation(parent);
    });

    function mpcrbm_price_calculation_car_details_page(parent, number_of_car) {
        // let number_of_car = mpcrbm_number_of_car_booked( parent );

        let target_summary = parent.find(".mpcrbm_transport_summary");
        let total = 0;
        let post_id = parseInt(parent.find('[name="mpcrbm_post_id"]').val());
        if (post_id > 0) {
            total = total + parseFloat(parent.find('[name="mpcrbm_post_id"]').attr("data-price"));

            total = total * number_of_car;
            let basePerCar = total / number_of_car;

            parent.find(".mpcrbm_extra_service_item").each(function () {
                let service_name = jQuery(this).find('[name="mpcrbm_extra_service[]"]').val();
                if (service_name) {
                    let ex_target = jQuery(this).find('[name="mpcrbm_extra_service_qty[]');
                    let ex_qty = parseInt(ex_target.val());
                    let ex_price = ex_target.data("price");
                    ex_price = ex_price && ex_price > 0 ? ex_price : 0;
                    let ex_days = ex_target.attr("data-price-type") === "day" ? mpcrbm_calculate_rental_days(parent) : 1;
                    total = total + parseFloat(ex_price) * ex_qty * ex_days;
                }
            });

            let deposit = parseFloat(parent.find('[name="mpcrbm_security_deposit_value"]').val()) || 0;
            if (deposit > 0) {
                let total_deposit = deposit * number_of_car;
                total = total + total_deposit;
                target_summary.find('.mpcrbm_security_deposit_price').html(mpcrbm_price_format(total_deposit));
            }

            let oneWayFee = parseFloat($('#mpcrbm_branch_one_way_fee').val()) || 0;
            if (oneWayFee > 0) {
                let oneWayTotal = oneWayFee * number_of_car;
                total = total + oneWayTotal;
                let $feeDisplay = parent.find('#mpcrbm_car_one_way_fee_display');
                if ($feeDisplay.length) {
                    $feeDisplay.html(mpcrbm_price_format(oneWayFee) + ' &times; ' + number_of_car + ' = ' + mpcrbm_price_format(oneWayTotal));
                }
            }

            ['delivery', 'collection'].forEach(function (kind) {
                let $box = parent.find('[name="mpcrbm_' + kind + '_requested"]');
                let $row = parent.find('#mpcrbm_car_' + kind + '_fee_row');
                if ($box.length && $box.is(':checked')) {
                    let feeVal = parseFloat($box.data('fee')) || 0;
                    let fee = $box.data('fee-type') === 'percentage' ? (basePerCar * feeVal / 100) : feeVal;
                    if (fee > 0) {
                        total = total + fee * number_of_car;
                        $('#mpcrbm_car_' + kind + '_fee_display').html(mpcrbm_price_format(fee));
                        $row.show();
                    } else {
                        $row.hide();
                    }
                } else {
                    $row.hide();
                }
            });
        }
        target_summary.find(".mpcrbm_product_total_price").html(mpcrbm_price_format(total));
    }

    // Handle taxi return and waiting time changes
    $(document).on("change", ".mpcrbm_transport_search_area [name='mpcrbm_taxi_return'], .mpcrbm_transport_search_area [name='mpcrbm_waiting_time']", function() {
        let parent = $(this).closest(".mpcrbm_transport_search_area");
        mpcrbm_content_refresh(parent);
    });

    // Handle Book Now button click
    $(document).on("click", ".mpcrbm_book_now[type='button']", function() {
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        let target_checkout = parent.find('.mpcrbm_checkout_area');
        let start_place = parent.find('[name="mpcrbm_start_place"]').val();
        let end_place   = parent.find('[name="mpcrbm_end_place"]').val();
        // When "same location" checkbox is checked, treat end_place = start_place
        if ($('#mpcrbm_is_drop_off').is(':checked')) {
            end_place = start_place;
        }
        let mpcrbm_waiting_time = parent.find('[name="mpcrbm_waiting_time"]').val();
        let mpcrbm_taxi_return = parent.find('[name="mpcrbm_taxi_return"]').val();
        let return_target_date = parent.find("#mpcrbm_map_return_date").val();
        let return_target_time = parent.find("#mpcrbm_map_return_time").val();
        let mpcrbm_fixed_hours = parent.find('[name="mpcrbm_fixed_hours"]').val();
        // The CAR is the booking's identity; link_id is the hidden WooCommerce mirror
        // product, which simply does not exist in Custom Payment mode. Fall back to the
        // car id the Book Now button carries so a missing hidden field can't strand us.
        let post_id = parent.find('[name="mpcrbm_post_id"]').val() || $(this).attr('data-car-id') || '';
        let date = parent.find('[name="mpcrbm_date"]').val();
        let link_id = $(this).attr('data-wc_link_id') || '';

        let car_quantity = parent.find('[name="mpcrbm_selected_car_quantity"]').val();
        if( car_quantity == 0 ){
            car_quantity = 1;
        }

        // Deliberately NOT requiring link_id: without WooCommerce there is no mirror
        // product, and demanding one here made "Book Now" silently do nothing at all.
        if (start_place !== '' && end_place !== '' && post_id) {
            let extra_service_name = {};
            let extra_service_qty = {};
            let count = 0;

            // Collect extra service data
            parent.find('[name="mpcrbm_extra_service[]"]').each(function() {
                let ex_name = $(this).val();
                if (ex_name) {
                    extra_service_name[count] = ex_name;
                    let ex_qty = parseInt($(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service_qty[]"]').val());
                    ex_qty = ex_qty > 0 ? ex_qty : 1;
                    extra_service_qty[count] = ex_qty;
                    count++;
                }
            });

            // Make AJAX request to add to cart
            $.ajax({
                type: 'POST',
                url: mpcrbm_ajax.ajax_url,
                data: {
                    action: "mpcrbm_add_to_cart",
                    link_id: link_id,
                    // Always sent now. Previously only link_id went along, so in Custom
                    // Payment mode the server had no way to tell which car was booked.
                    post_id: post_id,
                    mpcrbm_start_place: start_place,
                    mpcrbm_end_place: end_place,
                    mpcrbm_waiting_time: mpcrbm_waiting_time,
                    mpcrbm_taxi_return: mpcrbm_taxi_return,
                    mpcrbm_fixed_hours: mpcrbm_fixed_hours,
                    mpcrbm_date: date,
                    mpcrbm_return_date: return_target_date,
                    mpcrbm_return_time: return_target_time,
                    mpcrbm_extra_service: extra_service_name,
                    mpcrbm_extra_service_qty: extra_service_qty,
                    mpcrbm_car_quantity: car_quantity,
                    mpcrbm_delivery_requested: parent.find('[name="mpcrbm_delivery_requested"]').is(':checked') ? '1' : '',
                    mpcrbm_delivery_address: parent.find('[name="mpcrbm_delivery_address"]').val(),
                    mpcrbm_collection_requested: parent.find('[name="mpcrbm_collection_requested"]').is(':checked') ? '1' : '',
                    mpcrbm_collection_address: parent.find('[name="mpcrbm_collection_address"]').val(),
                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {
                    var mpcrbm_trimmed = $.trim(data);

                    // "0" is admin-ajax's bare response when the handler bails — here that
                    // means the car is fully booked for the chosen dates. It must be caught
                    // before the redirect branch below, or we'd navigate to a page named "0".
                    if (mpcrbm_trimmed === '0' || mpcrbm_trimmed === '') {
                        mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                        alert(mpcrbm_ajax.i18n_unavailable || 'This vehicle is not available for the selected dates. Please choose another date or vehicle.');
                        return;
                    }

                    // A URL response means "go here" (WooCommerce checkout, or the
                    // standalone Custom Payment checkout) — only HTML renders in place.
                    if (mpcrbm_trimmed.charAt(0) !== '<') {
                        window.location.href = mpcrbm_trimmed;
                        return;
                    }
                    if ($('<div />', { html: data }).find("div").length > 0) {
                        var mpcrbmTemplateExists = $(".mpcrbm-show-search-result").length;
                        if (mpcrbmTemplateExists) {
                            $(".mpcrbm_map_search_result").css("display", "none");
                            $(".mpcrbm_order_summary").css("display", "block");
                            $(".step-place-order").addClass('active');
                        }
                        target_checkout.html(data).promise().done(function() {
                            target_checkout.find('.woocommerce-billing-fields .required').each(function() {
                                $(this).closest('p').find('.input-text, select, textarea').attr('required', 'required');
                            });
                            $(document.body).trigger('init_checkout');
                            if ($('body select#billing_country').length > 0) {
                                $('body select#billing_country').select2({});
                            }
                            if ($('body select#billing_state').length > 0) {
                                $('body select#billing_state').select2({});
                            }
                            mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                            parent.find('.nextTab_next').trigger('click');
                        });
                    } else {
                        window.location.href = data;
                    }
                },
                error: function(response) {
                    console.log(response);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                }
            });
        }
    });
    // #mpcrbm_start_date/#mpcrbm_return_date and the time fields are readonly
    // AND always carry a server-rendered default (single_car_search_details.php:
    // $mpcrbm_start_date = today, $mpcrbm_start_time = the car's default start
    // time, etc.) so they are never actually *empty* — a plain "is it blank"
    // check can never fail, and "required" has no effect on readonly fields
    // anyway. Instead this checks the data-user-selected="1" flag that
    // date-picker.js / the .start_time_list & .return_time_list click
    // handlers below only set on a *real* pick, and shows/clears the inline
    // ".mpcrbm_field_error" notice next to whichever field(s) are still
    // unpicked (car-details page only — single_car_search_details.php only
    // renders those spans there), scrolling to the first one.
    function mpcrbm_validate_date_time_fields(parent) {
        let fields = [
            { input: parent.find('#mpcrbm_start_date'), error: parent.find('#mpcrbm_pickup_date_error') },
            { input: parent.find('#mpcrbm_map_start_time'), error: parent.find('#mpcrbm_pickup_time_error') },
            { input: parent.find('#mpcrbm_return_date'), error: parent.find('#mpcrbm_return_date_error') },
            { input: parent.find('#mpcrbm_map_return_time'), error: parent.find('#mpcrbm_return_time_error') }
        ];

        let $firstInvalidWrap = null;

        fields.forEach(function (field) {
            let $wrap = field.error.closest('.input_select');
            field.error.hide();
            $wrap.removeClass('mpcrbm-field-invalid');

            if (field.input.attr('data-user-selected') !== '1') {
                field.error.show();
                $wrap.addClass('mpcrbm-field-invalid');
                if (!$firstInvalidWrap) {
                    $firstInvalidWrap = $wrap;
                }
            }
        });

        if ($firstInvalidWrap && $firstInvalidWrap.length) {
            $('html, body').animate({ scrollTop: $firstInvalidWrap.offset().top - 120 }, 300);
            return false;
        }

        return true;
    }

    // Handle Book Now button click

    $(document).on("click", ".mpcrbm_car_details_continue_btn", function() {
        let parent = $(this).closest('.mpcrbm_car_details_wrapper');

        if (!mpcrbm_validate_date_time_fields(parent)) {
            return;
        }

        let start_place = parent.find('#mpcrbm_manual_start_place').val();
        let end_place   = parent.find('#mpcrbm_manual_end_place').val();
        let mpcrbm_waiting_time = '';
        let mpcrbm_taxi_return = '';
        let mpcrbm_start_date = parent.find("#mpcrbm_map_start_date").val();
        let mpcrbm_start_time = parent.find("#mpcrbm_map_start_time").val();
        let return_target_date = parent.find("#mpcrbm_map_return_date").val();
        let return_target_time = parent.find("#mpcrbm_map_return_time").val();
        let mpcrbm_fixed_hours = parent.find('[name="mpcrbm_fixed_hours"]').val();
        // let date = parent.find('[name="mpcrbm_date"]').val();
        // link_id is the hidden WooCommerce mirror product. It is absent in Custom
        // Payment mode, so it must never be treated as required — see the guard below.
        let link_id = $(this).attr('data-wc_link_id') || '';
        let post_id = $(this).attr('data-car-id');

        let [hour, minute] = mpcrbm_start_time.split(".");
        minute = minute ? minute.padEnd(2, "0") : "00";
        hour = hour.padStart(2, "0");
        let date = `${mpcrbm_start_date} ${hour}:${minute}`;
        if( start_place === null ){
            start_place = '';
        }
        // When "same location" checkbox is checked, treat end = start (no one-way fee)
        if ( $('#mpcrbm_is_drop_off').is(':checked') || end_place === '' || end_place === null ) {
            end_place = start_place;
        }
        // console.log( date );


        let car_quantity = parent.find('[name="mpcrbm_get_car_qty"]').val();

        // Only the car id is required. Requiring link_id here is what made "Continue"
        // do nothing at all — with WooCommerce inactive there is no mirror product, so
        // the whole request was skipped without any message to the customer.
        if ( post_id ) {
            let extra_service_name = {};
            let extra_service_qty = {};
            let count = 0;

            // Collect extra service data
            parent.find('[name="mpcrbm_extra_service[]"]').each(function() {
                let ex_name = $(this).val();
                if (ex_name) {
                    extra_service_name[count] = ex_name;
                    let ex_qty = parseInt($(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service_qty[]"]').val());
                    ex_qty = ex_qty > 0 ? ex_qty : 1;
                    extra_service_qty[count] = ex_qty;
                    count++;
                }
            });

            // Make AJAX request to add to cart
            $.ajax({
                type: 'POST',
                url: mpcrbm_ajax.ajax_url,
                data: {
                    action: "mpcrbm_add_to_cart",
                    link_id: link_id,
                    post_id: post_id,
                    mpcrbm_start_place: start_place,
                    mpcrbm_end_place: end_place,
                    mpcrbm_waiting_time: mpcrbm_waiting_time,
                    mpcrbm_taxi_return: mpcrbm_taxi_return,
                    mpcrbm_fixed_hours: mpcrbm_fixed_hours,
                    mpcrbm_date: date,
                    mpcrbm_return_date: return_target_date,
                    mpcrbm_return_time: return_target_time,
                    mpcrbm_extra_service: extra_service_name,
                    mpcrbm_extra_service_qty: extra_service_qty,
                    mpcrbm_car_quantity: car_quantity,
                    mpcrbm_delivery_requested: parent.find('[name="mpcrbm_delivery_requested"]').is(':checked') ? '1' : '',
                    mpcrbm_delivery_address: parent.find('[name="mpcrbm_delivery_address"]').val(),
                    mpcrbm_collection_requested: parent.find('[name="mpcrbm_collection_requested"]').is(':checked') ? '1' : '',
                    mpcrbm_collection_address: parent.find('[name="mpcrbm_collection_address"]').val(),
                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {

                    if( data == 0 ){
                        alert( 'This Day Is Already Booked Select Another Date');
                        mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                    }else {
                        var mpcrbm_response = $.trim(data);

                        // The server decides where a booking goes next, because that
                        // depends on the Booking Mode: WooCommerce returns its checkout
                        // URL, Custom Payment returns the standalone checkout URL, and an
                        // unpayable site returns an HTML error block to show in place.
                        // Hard-coding "/checkout/" here used to break both of those, and
                        // any store whose checkout page isn't at that slug.
                        if (mpcrbm_response.charAt(0) === '<') {
                            mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                            var $mpcrbm_target = parent.find('.mpcrbm_order_proceed_area');
                            if (!$mpcrbm_target.length) { $mpcrbm_target = parent; }
                            $mpcrbm_target.html(mpcrbm_response);
                        } else if (mpcrbm_response) {
                            window.location.href = mpcrbm_response;
                        } else {
                            mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                        }
                    }
                },
                error: function(response) {
                    console.log(response);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                }
            });
        }
    });

    // Handle Previous button click
    $(document).on("click", ".mpcrbm_get_vehicle_prev", function() {
        var mpcrbmTemplateExists = $(".mpcrbm-show-search-result").length;

        let progress_bar = $("#mpcrbm_progress_bar_display").val();
        if (mpcrbmTemplateExists) {
            // Function to retrieve cookie value by name
            function getCookie(name) {
                var cookies = document.cookie.split(";");
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i].trim();
                    if (cookie.startsWith(name + "=")) {
                        return cookie.substring(name.length + 1);
                    }
                }
                return null;
            }

            // Get the referrer URL from cookie
            var httpReferrerValue = getCookie("httpReferrer");

            // Function to delete a cookie
            function deleteCookie(name) {
                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            }

            // Delete the referrer cookie and redirect
            deleteCookie("httpReferrer");
            window.location.href = httpReferrerValue;
        } else {
            let parent = $(this).closest(".mpcrbm_transport_search_area");
            parent.find(".get_details_next_link").slideDown("fast");
            parent.find(".nextTab_prev").trigger("click");
        }

        if( progress_bar === 'yes') {
            $('#mpcrbm_progress_bar_holder').fadeOut();
        }
    });

    // Handle Summary Previous button click
    $(document).on("click", ".mpcrbm_summary_prev", function() {
        let mpcrbmTemplateExists = $(".mpcrbm-show-search-result").length;
        if (mpcrbmTemplateExists) {
            $(".mpcrbm_order_summary").css("display", "none");
            $(".mpcrbm_map_search_result").css("display", "block").hide().slideDown("slow");
            $(".step-place-order").removeClass("active");
        } else {
            let parent = $(this).closest(".mpcrbm_transport_search_area");
            parent.find(".nextTab_prev").trigger("click");
        }
    });

    $(document).on('click', '.mpcrbm-filter-title', function() {
        $(this).closest('.mpcrbm-filter-group').toggleClass('mpcrbm-filter-collapsed');
    });

    $(document).on('change', '.mpcrbm-filter-checkbox', function() {

        let parent = $(this).closest(".mpcrbm_transport_search_area");
        let selectedValues = [];
        $('.mpcrbm-filter-checkbox:checked').each(function() {
            selectedValues.push($(this).val().toLowerCase());
        });

        $('#mpcrbm_selected_filters').val(selectedValues.join(','));

        if (selectedValues.length === 0) {
            $('.mpcrbm_booking_item').show();
            return;
        }

        parent.find('.mpcrbm_booking_item').each(function() {
            let $item = $(this);
            let itemData = $item.attr('data-filter-category-items') || '';
            let itemValues = itemData.toLowerCase().split(',').map(v => v.trim());
            let hasMatch = selectedValues.some(value => itemValues.includes(value));
            if (hasMatch) {
                $item.fadeIn();
            } else {
                $item.fadeOut();
            }
        });

    });

    $(document).on("click", ".mpcrbm_gallery_image", function() {
        let feature_image = $('#mpcrbm_car_details_feature_image');
        let gallery_image = $(this);
        let gallery_url = gallery_image.attr('src');
        let feature_url = feature_image.attr('src');
        feature_image.addClass('mpcrbm_gallery_image_fade_out');
        gallery_image.addClass('mpcrbm_gallery_image_fade_out');
        setTimeout(function() {
            feature_image.attr('src', gallery_url).removeClass('mpcrbm_gallery_image_fade_out').addClass('mpcrbm_gallery_image_fade_in');
            gallery_image.attr('src', feature_url).removeClass('mpcrbm_gallery_image_fade_out').addClass('mpcrbm_gallery_image_fade_in');

            setTimeout(function() {
                feature_image.removeClass('mpcrbm_gallery_image_fade_in');
                gallery_image.removeClass('mpcrbm_gallery_image_fade_in');
            }, 300);
        }, 300);
    });
    $(".mpcrbm_car_details_tabs button").on("click", function(){
        var tabId = $(this).data('tab');

        $('.mpcrbm_car_details_tabs button').removeClass('active');
        $(this).addClass('active');

        $('html, body').animate({
            scrollTop: $('#' + tabId).offset().top - 50
        }, 400);

        const target = $('#' + tabId);
        target.addClass('focus-highlight');

        setTimeout(() => {
            target.removeClass('focus-highlight');
        }, 800);
    });


    let currentIndex = 0;
    const $popup = $('.mpcrbm_gallery_image_popup_wrapper');
    const $images = $('.mpcrbm_gallery_image_popup_item');

    $(document).on('click', '.mpcrbm_car_image_details', function() {
        $popup.fadeIn(300);
        showImage(currentIndex);
    });

    $(document).on('click', '.mpcrbm_gallery_image_popup_next', function() {
        currentIndex = (currentIndex + 1) % $images.length;
        showImage(currentIndex);
    });

    $(document).on('click', '.mpcrbm_gallery_image_popup_prev', function() {
        currentIndex = (currentIndex - 1 + $images.length) % $images.length;
        showImage(currentIndex);
    });

    $(document).on('click', '.mpcrbm_gallery_image_popup_close, .mpcrbm_gallery_image_popup_overlay', function() {
        $popup.fadeOut(300);
    });

    $(document).on("click", ".mpcrbm_car_details_faq_question", function(){

        let currentItem = $(this).closest(".mpcrbm_car_details_faq_item");
        let currentAnswer = currentItem.find(".mpcrbm_car_details_faq_answer");
        $(".mpcrbm_car_details_faq_item").not(currentItem).removeClass("active")
            .find(".mpcrbm_car_details_faq_answer").slideUp(300);
        currentItem.toggleClass("active");
        currentAnswer.stop(true, true).slideToggle(300);

    });

    //Load moore car with search form handle
    function mpcrbmLoadMore(options) {

        const settings = $.extend({
            wrapper: '.mpcrbm_search_result_holder',
            itemClass: '.mpcrbm_with_search_form',
            loadMoreWrapper: '.mpcrbm_search_result_load_more_holder',
            loadMoreBtn: '.mpcrbm_load_more_btn',
            itemsPerLoad: 10
        }, options);

        const wrapper = $(settings.wrapper);
        const items = wrapper.find(settings.itemClass);
        const loadMoreWrapper = $(settings.loadMoreWrapper);

        let visibleCount = settings.itemsPerLoad;
        const totalItems = items.length;

        // Initial setup
        items.hide();
        items.slice(0, settings.itemsPerLoad).show();

        if (totalItems <= settings.itemsPerLoad) {
            loadMoreWrapper.hide();
        }
        loadMoreWrapper.on('click', settings.loadMoreBtn, function () {

            const nextCount = visibleCount + settings.itemsPerLoad;
            items.slice(visibleCount, nextCount).fadeIn(300);
            visibleCount = nextCount;

            if (visibleCount >= totalItems) {
                loadMoreWrapper.fadeOut(300);
            }
        });
    }
    $(document).ready(function () {
        mpcrbmLoadMore({
            itemsPerLoad: 10
        });
    });
    //End

    function showImage(index) {
        $images.removeClass('active').css({ opacity: 0 });
        $images.eq(index).addClass('active').animate({ opacity: 1 }, 300);
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

        // Either time can still be unset mid-selection (guided single-date flow picks
        // date and time separately) — bail out instead of letting NaN through, which
        // "diffMs < 0" below does NOT catch (NaN < 0 is false), and previously ended up
        // writing "NaN x days" / "$NaN" over the server-rendered defaults.
        if (isNaN(start_time) || isNaN(return_time)) {
            return;
        }

        let start = new Date(startDate);
        let end = new Date(endDate);

        let startDateTime = new Date(start);
        startDateTime.setHours(start_time);
        let endDateTime = new Date(end);
        endDateTime.setHours(return_time);

        let diffMs = endDateTime - startDateTime;

        if (isNaN(diffMs) || diffMs < 0) {
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


        // Loading state + "reveal" pulse on the summary once both pick-up and
        // return are selected — reuses the existing mpcrbm_loader()/
        // mpcrbm_loader_remove() pair (mp_global/assets/mp_style/mpcrbm_global.js)
        // already used elsewhere in this file, and .mpcrbm_car_details_price_box
        // (already scoped to this page — see mpcrbm_car_details.css) as the loader
        // target since it wraps both the rate header and the "Details" summary
        // card this AJAX call updates.
        let $priceBox = parentClass.find('.mpcrbm_car_details_price_box');

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
            beforeSend: function () {
                if ($priceBox.length) {
                    mpcrbm_loader($priceBox);
                }
            },
            success: function (data) {

                if (data.success && data.data && data.data.calculated_price !== undefined) {
                    let calculated_price = mpcrbm_price_format( data.data.calculated_price );
                    let day_wise = data.data.calculated_price/totalDays;
                    let day_wise_price = mpcrbm_price_format( day_wise );
                    parentClass.find("#mpcrbm_selected_car_price").html(day_wise_price);
                    parentClass.find("#mpcrbm_total_day_price").html(day_wise_price);
                    $('.mpcrbm_car_details').find('[name="mpcrbm_post_id"]').attr("data-price", data.data.calculated_price );
                    // Re-apply one-way fee on top of the updated base price
                    let oneWayFee = parseFloat($('#mpcrbm_branch_one_way_fee').val()) || 0;
                    let carQty = parseInt(parentClass.find('#mpcrbm_selected_car_quantity').val()) || 1;
                    let deposit = parseFloat(parentClass.find('#mpcrbm_security_deposit_value').val()) || 0;
                    let totalWithFee = data.data.calculated_price + (oneWayFee * carQty) + deposit;
                    parentClass.find("#mpcrbm_car_total_price").html(mpcrbm_price_format(totalWithFee));

                    let $summary = parentClass.find('.mpcrbm_transport_summary');
                    $summary.addClass('mpcrbm-summary-pulse');
                    setTimeout(function () {
                        $summary.removeClass('mpcrbm-summary-pulse');
                    }, 900);
                }
            },
            error: function(response) {
                console.log(response);
            },
            complete: function () {
                if ($priceBox.length) {
                    mpcrbm_loader_remove($priceBox);
                }
            }
        });

    }

});

// Helper functions
function mpcrbm_content_refresh(parent) {
    let ajax_search = parent.find('[name="mpcrbm_enable_ajax_search"]').val();
    if( ajax_search !== 'yes' ) {
        jQuery(parent).find('[name="mpcrbm_post_id"]').val("");
        jQuery(parent).find(".mpcrbm_map_search_result").remove();
        jQuery(parent).find(".mpcrbm_order_summary").remove();
        jQuery(parent).find(".get_details_next_link").slideUp("fast");
    }
}

// Book Now (templates/registration/summary_new.php) is now a single button
// that's always present on the search-results page, rather than only
// existing in the DOM once the per-car extra-services AJAX response
// (templates/registration/extra_service.php) had loaded. So instead of
// show()/hide(), this enables/disables it and syncs data-wc_link_id from
// whichever vehicle's Select Car button is currently active — the existing
// ".mpcrbm_book_now[type='button']" click handler already reads that
// attribute plus the #mpcrbm_post_id hidden field at click time, so it needs
// no changes to work with either the old or new button.
function checkAndToggleBookNowButton(parent) {
    var $parent = jQuery(parent);
    var $activeSelect = $parent.find('.mpcrbm_transport_select.active_select');
    var hasSelectedVehicle = $activeSelect.length > 0;
    var $bookNowButton = $parent.find('.mpcrbm_book_now[type="button"]');

    $bookNowButton.prop('disabled', !hasSelectedVehicle);
    $bookNowButton.attr('data-wc_link_id', hasSelectedVehicle ? ($activeSelect.attr('data-wc_link_id') || '') : '');
    // Carry the CAR id across too. data-wc_link_id is the hidden WooCommerce product,
    // which is empty in Custom Payment mode — without this the click handler had no
    // usable identifier for the selected vehicle and gave up silently.
    $bookNowButton.attr('data-car-id', hasSelectedVehicle ? ($activeSelect.attr('data-post-id') || '') : '');
}

function gm_authFailure() {
    alert('Admin use Invalid Google Api Key . So, Google Map not working !');
}