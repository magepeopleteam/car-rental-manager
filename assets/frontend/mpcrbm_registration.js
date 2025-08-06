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
        let parent = $this.closest('.mpcrbm_transport_search_area');
        let target_summary = parent.find('.mpcrbm_transport_summary');
        let target_extra_service = parent.find('.mpcrbm_extra_service');
        let target_extra_service_summary = parent.find('.mpcrbm_extra_service_summary');

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
            target_summary.slideUp(400);
            target_extra_service.slideUp(400);
            target_extra_service_summary.slideUp(400);
            parent.find('[name="mpcrbm_post_id"]').val('');
        } else {
            // Select new vehicle
            parent.find('.mpcrbm_transport_select.active_select').removeClass('active_select');
            
            let transport_name = $this.attr('data-transport-name');
            let transport_price = parseFloat($this.attr('data-transport-price'));
            let post_id = $this.attr('data-post-id');
            
            // Update vehicle details in summary
            target_summary.find('.mpcrbm_product_name').html(transport_name);
            target_summary.find('.mpcrbm_product_price').html(mpcrbm_price_format(transport_price));
            target_summary.find('.mpcrbm_product_total_price').html(mpcrbm_price_format(transport_price));
            
            $this.addClass('active_select');
            parent.find('[name="mpcrbm_post_id"]').val(post_id).attr('data-price', transport_price);
            
            // Show summary sections
            target_summary.slideDown(400);
            target_extra_service.slideDown(400);
            target_extra_service_summary.slideDown(400);
            
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
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {
                    target_extra_service.html(data);
                    checkAndToggleBookNowButton(parent);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));

                    const targetOffset = $('.mpcrbm_book_now').offset().top;
                    const distance = Math.abs(targetOffset - buttonOffset);
                    const duration = Math.min(Math.max(distance * 0.5, 300), 1500); // Clamp between 300ms and 1500ms
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

    $(document).on('change', '#mpcrbm_is_drop_off', function() {
        if ($(this).is(':checked')) {
            $('#mpcrbm_drop_off_location').hide();
        } else {
            $('#mpcrbm_drop_off_location').show();
        }
    });

    // Handle get vehicle button
    $(document).on("click", "#mpcrbm_get_vehicle", function() {
        let parent = $(this).closest(".mpcrbm_transport_search_area");
        let mpcrbm_enable_return_in_different_date = parent
            .find('[name="mpcrbm_enable_return_in_different_date"]')
            .val();

        let target = parent.find(".tabsContentNext");
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
            mpcrbm_loader(parent.find(".tabsContentNext"));
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
                        if (!mpcrbm_enable_view_search_result_page) {
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
                                },
                                beforeSend: function () {
                                    //mpcrbm_loader(target);
                                },
                                success: function (data) {
                                    target
                                        .append(data)
                                        .promise()
                                        .done(function () {
                                            mpcrbm_loader_remove(parent.find(".tabsContentNext"));
                                            parent.find(".nextTab_next").trigger("click");
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
                                    mpcrbm_enable_view_search_result_page: mpcrbm_enable_view_search_result_page,
                                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                                },
                                beforeSend: function () {
                                    mpcrbm_loader(target);
                                },
                                success: function (data) {
                                    var cleanedURL = data.replace(/"/g, ""); // Remove all double quotes from the string
                                    window.location.href = cleanedURL; // Redirect to the URL received from the server
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
                    if (!mpcrbm_enable_view_search_result_page) {
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
                                mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                            },
                            beforeSend: function () {
                                //mpcrbm_loader(target);
                            },
                            success: function (data) {
                                target
                                    .append(data)
                                    .promise()
                                    .done(function () {
                                        mpcrbm_loader_remove(parent.find(".tabsContentNext"));
                                        parent.find(".nextTab_next").trigger("click");
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
                                mpcrbm_enable_view_search_result_page: mpcrbm_enable_view_search_result_page,
                                mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                            },
                            beforeSend: function () {
                                mpcrbm_loader(target);
                            },
                            success: function (data) {
                                var cleanedURL = data.replace(/"/g, ""); // Remove all double quotes from the string
                                window.location.href = cleanedURL; // Redirect to the URL received from the server
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
    });

    $(document).on("click", ".return_time_list li", function() {
        let selectedValue = $(this).attr('data-value');
        $('#mpcrbm_map_return_time').val(selectedValue).trigger('change');
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

    // Handle extra service quantity changes
    $(document).on('change', '.mpcrbm_transport_search_area [name="mpcrbm_extra_service_qty[]"]', function () {
        $(this).closest('.mpcrbm_extra_service_item').find('[name="mpcrbm_extra_service[]"]').trigger('change');
        let parent = $(this).closest('.mpcrbm_transport_search_area');
        checkAndToggleBookNowButton(parent);
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
                    <div class="_textLight_1_dFlex_flexWrap_justifyBetween" data-extra-service-id="${service_id}" data-price="${price_per_item}">
                        <div class="_dFlex_alignCenter">
                            <span class="fas fa-check-square _textTheme_mR_xs"></span>
                            <span>${service_name_display}</span>
                        </div>
                        <p>
                            <span class="textTheme ex_service_qty">x${qty}</span>&nbsp;|&nbsp;
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

    // Price calculation function
    function mpcrbm_price_calculation(parent) {
        let target_summary = parent.find(".mpcrbm_transport_summary");
        let total = 0;
        let post_id = parseInt(parent.find('[name="mpcrbm_post_id"]').val());
        if (post_id > 0) {
            total = total + parseFloat(parent.find('[name="mpcrbm_post_id"]').attr("data-price"));
            parent.find(".mpcrbm_extra_service_item").each(function () {
                let service_name = jQuery(this).find('[name="mpcrbm_extra_service[]"]').val();
                if (service_name) {
                    let ex_target = jQuery(this).find('[name="mpcrbm_extra_service_qty[]');
                    let ex_qty = parseInt(ex_target.val());
                    let ex_price = ex_target.data("price");
                    ex_price = ex_price && ex_price > 0 ? ex_price : 0;
                    total = total + parseFloat(ex_price) * ex_qty;
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
        let end_place = parent.find('[name="mpcrbm_end_place"]').val();
        let mpcrbm_waiting_time = parent.find('[name="mpcrbm_waiting_time"]').val();
        let mpcrbm_taxi_return = parent.find('[name="mpcrbm_taxi_return"]').val();
        let return_target_date = parent.find("#mpcrbm_map_return_date").val();
        let return_target_time = parent.find("#mpcrbm_map_return_time").val();
        let mpcrbm_fixed_hours = parent.find('[name="mpcrbm_fixed_hours"]').val();
        let post_id = parent.find('[name="mpcrbm_post_id"]').val();
        let date = parent.find('[name="mpcrbm_date"]').val();
        let link_id = $(this).attr('data-wc_link_id');

        if (start_place !== '' && end_place !== '' && link_id && post_id) {
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
                    mpcrbm_transportation_type_nonce: mpcrbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {
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

    // Handle Previous button click
    $(document).on("click", ".mpcrbm_get_vehicle_prev", function() {
        var mpcrbmTemplateExists = $(".mpcrbm-show-search-result").length;
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
    
});

// Helper functions
function mpcrbm_content_refresh(parent) {
    jQuery(parent).find('[name="mpcrbm_post_id"]').val("");
    jQuery(parent).find(".mpcrbm_map_search_result").remove();
    jQuery(parent).find(".mpcrbm_order_summary").remove();
    jQuery(parent).find(".get_details_next_link").slideUp("fast");
}

function checkAndToggleBookNowButton(parent) {
    var $parent = jQuery(parent);
    var hasSelectedVehicle = $parent.find('.mpcrbm_transport_select.active_select').length > 0;
    var $bookNowButton = $parent.find('.mpcrbm_book_now[type="button"]');
    
    if (hasSelectedVehicle) {
        $bookNowButton.show();
    } else {
        $bookNowButton.hide();
    }
}

function gm_authFailure() {
    alert('Admin use Invalid Google Api Key . So, Google Map not working !');
}