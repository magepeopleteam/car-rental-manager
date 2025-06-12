let mptbm_map;
let mptbm_map_window;
function mptbm_set_cookie_distance_duration(start_place = "", end_place = "") {
    mptbm_map = new google.maps.Map(document.getElementById("mptbm_map_area"), {
        mapTypeControl: false,
        center: mp_lat_lng,
        zoom: 15,
    });
    if (start_place && end_place) {
        let directionsService = new google.maps.DirectionsService();
        let directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(mptbm_map);
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
                let kmOrMile = document.getElementById("mptbm_km_or_mile").value;
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
                    "mptbm_distance=" + distance + "; expires=" + now + "; path=/; ";
                document.cookie =
                    "mptbm_distance_text=" +
                    distance_text +
                    "; expires=" +
                    now +
                    "; path=/; ";
                document.cookie =
                    "mptbm_duration=" + duration + ";  expires=" + now + "; path=/; ";
                document.cookie =
                    "mptbm_duration_text=" +
                    duration_text +
                    ";  expires=" +
                    now +
                    "; path=/; ";
                directionsRenderer.setDirections(result);
                jQuery(".mptbm_total_distance").html(distance_text);
                jQuery(".mptbm_total_time").html(duration_text);
                jQuery(".mptbm_distance_time").slideDown("fast");
            } else {
                //directionsRenderer.setDirections({routes: []})
                //alert('location error');
            }
        });
    } else if (start_place || end_place) {
        let place = start_place ? start_place : end_place;
        mptbm_map_window = new google.maps.InfoWindow();
        map = new google.maps.Map(document.getElementById("mptbm_map_area"), {
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
                    mptbmCreateMarker(results[i]);
                }
                map.setCenter(results[0].geometry.location);
            }
        });
    } else {
        let directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(mptbm_map);
        //document.getElementById('mptbm_map_start_place').focus();
    }
    return true;
}
function mptbmCreateMarker(place) {
    if (!place.geometry || !place.geometry.location) return;
    const marker = new google.maps.Marker({
        map,
        position: place.geometry.location,
    });
    google.maps.event.addListener(marker, "click", () => {
        mptbm_map_window.setContent(place.name || "");
        mptbm_map_window.open(map);
    });
}
jQuery(document).ready(function($) {
    "use strict";

    // Initialize map and autocomplete
    $(".mpcrbm ul.mp_input_select_list").hide();
        if ($("#mptbm_map_area").length > 0) {
            mptbm_set_cookie_distance_duration();
        if ($("#mptbm_map_start_place").length > 0 && $("#mptbm_map_end_place").length > 0) {
                let start_place = document.getElementById("mptbm_map_start_place");
                let end_place = document.getElementById("mptbm_map_end_place");
            let start_place_autoload = new google.maps.places.Autocomplete(start_place);
                let mptbm_restrict_search_to_country = $('[name="mptbm_restrict_search_country"]').val();
                let mptbm_country = $('[name="mptbm_country"]').val();
                
                if(mptbm_restrict_search_to_country == 'yes'){
                    start_place_autoload.setComponentRestrictions({
                        country: [mptbm_country]
                    });
                }
                
            google.maps.event.addListener(start_place_autoload, "place_changed", function() {
                mptbm_set_cookie_distance_duration(start_place.value, end_place.value);
            });
            
            let end_place_autoload = new google.maps.places.Autocomplete(end_place);
                if(mptbm_restrict_search_to_country == 'yes'){
                    end_place_autoload.setComponentRestrictions({
                        country: [mptbm_country]
                    });
                }
                
            google.maps.event.addListener(end_place_autoload, "place_changed", function() {
                mptbm_set_cookie_distance_duration(start_place.value, end_place.value);
            });
        }
    }

    // Handle vehicle selection
    $(document).on('click', '.mptbm_transport_select', function() {
        let $this = $(this);
        let parent = $this.closest('.mptbm_transport_search_area');
        let target_summary = parent.find('.mptbm_transport_summary');
        let target_extra_service = parent.find('.mptbm_extra_service');
        let target_extra_service_summary = parent.find('.mptbm_extra_service_summary');
        
        // Clear all extra services when selecting a new vehicle
        target_extra_service_summary.empty();
        target_extra_service.empty();
        
        // Reset all extra service inputs
        parent.find('[name="mptbm_extra_service[]"]').val('').trigger('change');
        parent.find('[name="mptbm_extra_service_qty[]"]').val('1');
        
        if ($this.hasClass('active_select')) {
            // Deselect vehicle
            $this.removeClass('active_select');
            target_summary.slideUp(400);
            target_extra_service.slideUp(400);
            target_extra_service_summary.slideUp(400);
            parent.find('[name="mptbm_post_id"]').val('');
        } else {
            // Select new vehicle
            parent.find('.mptbm_transport_select.active_select').removeClass('active_select');
            
            let transport_name = $this.attr('data-transport-name');
            let transport_price = parseFloat($this.attr('data-transport-price'));
            let post_id = $this.attr('data-post-id');
            
            // Update vehicle details in summary
            target_summary.find('.mptbm_product_name').html(transport_name);
            target_summary.find('.mptbm_product_price').html(mpcrbm_price_format(transport_price));
            target_summary.find('.mptbm_product_total_price').html(mpcrbm_price_format(transport_price));
            
            $this.addClass('active_select');
            parent.find('[name="mptbm_post_id"]').val(post_id).attr('data-price', transport_price);
            
            // Show summary sections
            target_summary.slideDown(400);
            target_extra_service.slideDown(400);
            target_extra_service_summary.slideDown(400);
            
            // Fetch available extra services
            $.ajax({
                type: 'POST',
                url: mptbm_ajax.ajax_url,
                data: {
                    action: 'get_mptbm_extra_service',
                    post_id: post_id,
                    mptbm_transportation_type_nonce: mptbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {
                    target_extra_service.html(data);
                    checkAndToggleBookNowButton(parent);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                },
                error: function(response) {
                    console.log(response);
                    mpcrbm_loader_remove(parent.find('.tabsContentNext'));
                }
            });
        }
    });

    // Handle get vehicle button
    $(document).on("click", "#mptbm_get_vehicle", function() {
        let parent = $(this).closest(".mptbm_transport_search_area");
        let mptbm_enable_return_in_different_date = parent
            .find('[name="mptbm_enable_return_in_different_date"]')
            .val();

        let target = parent.find(".tabsContentNext");
        let target_date = parent.find("#mptbm_map_start_date");
        let return_target_date = parent.find("#mptbm_map_return_date");
        let target_time = parent.find("#mptbm_map_start_time");
        let return_target_time = parent.find("#mptbm_map_return_time");
        let start_place;
        let end_place;
        let price_based = parent.find('[name="mptbm_price_based"]').val();
        let two_way = parent.find('[name="mptbm_taxi_return"]').val();
        let waiting_time = parent.find('[name="mptbm_waiting_time"]').val();
        let fixed_time = parent.find('[name="mptbm_fixed_hours"]').val();
        let mptbm_enable_view_search_result_page = parent
            .find('[name="mptbm_enable_view_search_result_page"]')
            .val();
        if (price_based === "manual") {
            start_place = document.getElementById("mptbm_manual_start_place");
            end_place = document.getElementById("mptbm_manual_end_place");
        } else {
            start_place = document.getElementById("mptbm_map_start_place");
            end_place = document.getElementById("mptbm_map_end_place");
        }
        let start_date = target_date.val();
        let return_date = return_target_date.val();
        let return_time = return_target_time.val();
        
        let start_time = target_time.val();
        if (!start_date) {
            target_date.trigger("click");
        } else if (!start_time) {
            parent
                .find("#mptbm_map_start_time")
                .closest(".mp_input_select")
                .find("input.formControl")
                .trigger("click");
        } else if (!return_date) {
            if (mptbm_enable_return_in_different_date == 'yes' && two_way != 1) {
                return_target_date.trigger("click");
            }
        } else if (!return_time) {
            if (mptbm_enable_return_in_different_date == 'yes' && two_way != 1) {
                parent
                    .find("#mptbm_map_return_time")
                    .closest(".mp_input_select")
                    .find("input.formControl")
                    .trigger("click");
            }
        } else if (!start_place.value) {
            start_place.focus();
        } else if (!end_place.value) {
            end_place.focus();
        } else {
            mpcrbm_loader(parent.find(".tabsContentNext"));
            mptbm_content_refresh(parent);
            if (price_based !== "manual") {
                mptbm_set_cookie_distance_duration(start_place.value, end_place.value);
            }
            //let price_based = parent.find('[name="mptbm_price_based"]').val();
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
                        if (!mptbm_enable_view_search_result_page) {
                            actionValue = "get_mptbm_map_search_result";
                            
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
                            actionValue = "get_mptbm_map_search_result_redirect";
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
                                    mptbm_enable_view_search_result_page: mptbm_enable_view_search_result_page,
                                    mptbm_transportation_type_nonce: mptbm_ajax.nonce
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
                    if (!mptbm_enable_view_search_result_page) {
                        actionValue = "get_mptbm_map_search_result";
                       
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
                                mptbm_transportation_type_nonce: mptbm_ajax.nonce
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
                        actionValue = "get_mptbm_map_search_result_redirect";
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
                                mptbm_enable_view_search_result_page: mptbm_enable_view_search_result_page,
                                mptbm_transportation_type_nonce: mptbm_ajax.nonce
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
    $(document).on("change", "#mptbm_map_start_date", function() {
        // Clear the time slots list
        $('#mptbm_map_start_time').siblings('.start_time_list').empty();
        $('.start_time_input,#mptbm_map_start_time').val('');
        let mptbm_enable_return_in_different_date = $('[name="mptbm_enable_return_in_different_date"]').val();
        let mptbm_buffer_end_minutes = $('[name="mptbm_buffer_end_minutes"]').val();
        let mptbm_first_calendar_date = $('[name="mptbm_first_calendar_date"]').val();
        var selectedDate = $('#mptbm_map_start_date').val();
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
                if (timeValue > parseFloat(currentTimeFormatted) && timeValue >= mptbm_buffer_end_minutes / 60) {
                    $('#mptbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                }
            });
        } else {
            if(selectedDate  == mptbm_first_calendar_date){
                console.log(mptbm_first_calendar_date);
                $('.start_time_list-no-dsiplay li').each(function () {
                    const timeValue = parseFloat($(this).attr('data-value'));
                    if (timeValue >= mptbm_buffer_end_minutes / 60) {
                        $('#mptbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                    }
                });
            }else{
                $('.start_time_list-no-dsiplay li').each(function () {
                    $('#mptbm_map_start_time').siblings('.start_time_list').append($(this).clone());
                });
            }
            
            
        }

        // Update the return date picker if needed
        if (mptbm_enable_return_in_different_date == 'yes') {
            $('#mptbm_return_date').datepicker('option', 'minDate', formattedDate);
        }

        let parent = $(this).closest(".mptbm_transport_search_area");
        mptbm_content_refresh(parent);
        parent
            .find("#mptbm_map_start_time")
            .closest(".mp_input_select")
            .find("input.formControl")
            .trigger("click");
    });

    $(document).on("change", "#mptbm_map_return_date", function() {
        let mptbm_enable_return_in_different_date = $('[name="mptbm_enable_return_in_different_date"]').val();

        if (mptbm_enable_return_in_different_date == 'yes') {
            var selectedTime = parseFloat($('#mptbm_map_start_time').val());
            var selectedDate = $('#mptbm_map_start_date').val();
            var dateValue = $('#mptbm_map_return_date').val();

            // Check if the return date is the same as the pickup date
            if (selectedDate == dateValue) {
                $('#return_time_list').show();
                // Clear existing options
                $('#mptbm_map_return_time').siblings('.mp_input_select_list').empty();
                $('.mptbm_map_return_time_input').val('');
                // If return date is the same as the pickup date, show only times after pickup time
                $('.mp_input_select_list li').each(function () {
                    var timeValue = parseFloat($(this).attr('data-value'));
                    if (timeValue > selectedTime) {
                        $('#mptbm_map_return_time').siblings('.mp_input_select_list').append($(this).clone());
                    }
                });
            } else {
                // Clear existing options
                $('#mptbm_map_return_time').siblings('.mp_input_select_list').empty();
                $('.mptbm_map_return_time_input').val('');
                $('.return_time_list-no-dsiplay li').each(function () {
                    var timeValue = parseFloat($(this).attr('data-value'));
                    $('#mptbm_map_return_time').siblings('.mp_input_select_list').append($(this).clone());
                });
            }
        }

        // Trigger refresh and display logic
        let parent = $(this).closest(".mptbm_transport_search_area");
        mptbm_content_refresh(parent);
        parent.find("#mptbm_map_return_time").closest(".mp_input_select").find("input.formControl").trigger("click");
    });

    // Handle time selection
    $(document).on("click", ".start_time_list li", function() {
        let selectedValue = $(this).attr('data-value');
        $('#mptbm_map_start_time').val(selectedValue).trigger('change');
    });

    $(document).on("click", ".return_time_list li", function() {
        let selectedValue = $(this).attr('data-value');
        $('#mptbm_map_return_time').val(selectedValue).trigger('change');
    });

    // Handle place changes
    $(document).on("change", "#mptbm_map_start_place, #mptbm_map_end_place", function() {
        let parent = $(this).closest(".mptbm_transport_search_area");
        mptbm_content_refresh(parent);
        let start_place = parent.find("#mptbm_map_start_place").val();
        let end_place = parent.find("#mptbm_map_end_place").val();
        if (start_place || end_place) {
            if (start_place) {
                mptbm_set_cookie_distance_duration(start_place);
                parent.find("#mptbm_map_end_place").focus();
            } else {
                mptbm_set_cookie_distance_duration(end_place);
                parent.find("#mptbm_map_start_place").focus();
            }
        } else {
            parent.find("#mptbm_map_start_place").focus();
        }
    });

    // Handle extra service quantity changes
    $(document).on('change', '.mptbm_transport_search_area [name="mptbm_extra_service_qty[]"]', function () {
        $(this).closest('.mptbm_extra_service_item').find('[name="mptbm_extra_service[]"]').trigger('change');
        let parent = $(this).closest('.mptbm_transport_search_area');
        checkAndToggleBookNowButton(parent);
    });

    // Handle extra service selection
    $(document).on('change', '.mptbm_transport_search_area [name="mptbm_extra_service[]"]', function () {
        let parent = $(this).closest('.mptbm_transport_search_area');
        let service_id = $(this).data('value');
        let service_value = $(this).val();
        let $qty_input = $(this).closest('.mptbm_extra_service_item').find('[name="mptbm_extra_service_qty[]"]');
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
                parent.find('.mptbm_extra_service_summary').append(new_item_html);
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

        mptbm_price_calculation(parent);
        checkAndToggleBookNowButton(parent);
    });

    // Price calculation function
    function mptbm_price_calculation(parent) {
        let target_summary = parent.find(".mptbm_transport_summary");
        let total = 0;
        let post_id = parseInt(parent.find('[name="mptbm_post_id"]').val());
        if (post_id > 0) {
            total = total + parseFloat(parent.find('[name="mptbm_post_id"]').attr("data-price"));
            parent.find(".mptbm_extra_service_item").each(function () {
                let service_name = jQuery(this).find('[name="mptbm_extra_service[]"]').val();
                if (service_name) {
                    let ex_target = jQuery(this).find('[name="mptbm_extra_service_qty[]');
                    let ex_qty = parseInt(ex_target.val());
                    let ex_price = ex_target.data("price");
                    ex_price = ex_price && ex_price > 0 ? ex_price : 0;
                    total = total + parseFloat(ex_price) * ex_qty;
                }
            });
        }
        target_summary.find(".mptbm_product_total_price").html(mpcrbm_price_format(total));
    }

    // Handle taxi return and waiting time changes
    $(document).on("change", ".mptbm_transport_search_area [name='mptbm_taxi_return'], .mptbm_transport_search_area [name='mptbm_waiting_time']", function() {
            let parent = $(this).closest(".mptbm_transport_search_area");
        mptbm_content_refresh(parent);
    });

    // Handle Book Now button click
    $(document).on("click", ".mptbm_book_now[type='button']", function() {
        let parent = $(this).closest('.mptbm_transport_search_area');
        let target_checkout = parent.find('.mptbm_checkout_area');
        let start_place = parent.find('[name="mptbm_start_place"]').val();
        let end_place = parent.find('[name="mptbm_end_place"]').val();
        let mptbm_waiting_time = parent.find('[name="mptbm_waiting_time"]').val();
        let mptbm_taxi_return = parent.find('[name="mptbm_taxi_return"]').val();
        let return_target_date = parent.find("#mptbm_map_return_date").val();
        let return_target_time = parent.find("#mptbm_map_return_time").val();
        let mptbm_fixed_hours = parent.find('[name="mptbm_fixed_hours"]').val();
        let post_id = parent.find('[name="mptbm_post_id"]').val();
        let date = parent.find('[name="mptbm_date"]').val();
        let link_id = $(this).attr('data-wc_link_id');

        if (start_place !== '' && end_place !== '' && link_id && post_id) {
            let extra_service_name = {};
            let extra_service_qty = {};
            let count = 0;
            
            // Collect extra service data
            parent.find('[name="mptbm_extra_service[]"]').each(function() {
                let ex_name = $(this).val();
                if (ex_name) {
                    extra_service_name[count] = ex_name;
                    let ex_qty = parseInt($(this).closest('.mptbm_extra_service_item').find('[name="mptbm_extra_service_qty[]"]').val());
                    ex_qty = ex_qty > 0 ? ex_qty : 1;
                    extra_service_qty[count] = ex_qty;
                    count++;
                }
            });

            // Make AJAX request to add to cart
            $.ajax({
                type: 'POST',
                url: mptbm_ajax.ajax_url,
                data: {
                    action: "mpcrm_add_to_cart",
                    link_id: link_id,
                    mptbm_start_place: start_place,
                    mptbm_end_place: end_place,
                    mptbm_waiting_time: mptbm_waiting_time,
                    mptbm_taxi_return: mptbm_taxi_return,
                    mptbm_fixed_hours: mptbm_fixed_hours,
                    mptbm_date: date,
                    mptbm_return_date: return_target_date,
                    mptbm_return_time: return_target_time,
                    mptbm_extra_service: extra_service_name,
                    mptbm_extra_service_qty: extra_service_qty,
                    mptbm_transportation_type_nonce: mptbm_ajax.nonce
                },
                beforeSend: function() {
                    mpcrbm_loader(parent.find('.tabsContentNext'));
                },
                success: function(data) {
                    if ($('<div />', { html: data }).find("div").length > 0) {
                        var mptbmTemplateExists = $(".mptbm-show-search-result").length;
                        if (mptbmTemplateExists) {
                            $(".mptbm_map_search_result").css("display", "none");
                            $(".mptbm_order_summary").css("display", "block");
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
    $(document).on("click", ".mptbm_get_vehicle_prev", function() {
        var mptbmTemplateExists = $(".mptbm-show-search-result").length;
        if (mptbmTemplateExists) {
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
            let parent = $(this).closest(".mptbm_transport_search_area");
            parent.find(".get_details_next_link").slideDown("fast");
            parent.find(".nextTab_prev").trigger("click");
        }
    });

    // Handle Summary Previous button click
    $(document).on("click", ".mptbm_summary_prev", function() {
        let mptbmTemplateExists = $(".mptbm-show-search-result").length;
        if (mptbmTemplateExists) {
            $(".mptbm_order_summary").css("display", "none");
            $(".mptbm_map_search_result").css("display", "block").hide().slideDown("slow");
            $(".step-place-order").removeClass("active");
        } else {
            let parent = $(this).closest(".mptbm_transport_search_area");
            parent.find(".nextTab_prev").trigger("click");
        }
    });
    
});

// Helper functions
function mptbm_content_refresh(parent) {
    jQuery(parent).find('[name="mptbm_post_id"]').val("");
    jQuery(parent).find(".mptbm_map_search_result").remove();
    jQuery(parent).find(".mptbm_order_summary").remove();
    jQuery(parent).find(".get_details_next_link").slideUp("fast");
}

function checkAndToggleBookNowButton(parent) {
    var $parent = jQuery(parent);
    var hasSelectedVehicle = $parent.find('.mptbm_transport_select.active_select').length > 0;
    var $bookNowButton = $parent.find('.mptbm_book_now[type="button"]');
    
    if (hasSelectedVehicle) {
        $bookNowButton.show();
    } else {
        $bookNowButton.hide();
    }
}

function gm_authFailure() {
    alert('Admin use Invalid Google Api Key . So, Google Map not working !');
}