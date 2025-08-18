<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
*/
$label = MPCRBM_Function::get_name();
$days = MPCRBM_Global_Function::week_day();
$days_name = array_keys($days);
$schedule = [];


function mpcrbm_check_operation_area($post_id, $start_place, $end_place)
{
    // Retrieve saved locations from post meta
    $saved_locations = get_post_meta($post_id, 'mpcrbm_terms_price_info', true);

    // Ensure $saved_locations is an array
    if (!is_array($saved_locations) || empty($saved_locations)) {
        return false; // No locations to check against
    }

    // Flags to track if start_place and end_place are found
    $start_found = false;
    $end_found = false;

    // Iterate through saved locations to check for matches
    foreach ($saved_locations as $location) {
        if (
            isset($location['start_location']) && $location['start_location'] === $start_place ||
            isset($location['end_location']) && $location['end_location'] === $start_place
        ) {
            $start_found = true;
        }
        if (
            isset($location['start_location']) && $location['start_location'] === $end_place ||
            isset($location['end_location']) && $location['end_location'] === $end_place
        ) {
            $end_found = true;
        }

        // Break and return true once both are found
        if ($start_found && $end_found) {
            return true;
        }
    }

    // Return false if either place is not found
    return false;
}


function mpcrbm_get_schedule($post_id, $days_name, $selected_day, $start_time_schedule, $return_time_schedule, $start_place_coordinates, $end_place_coordinates, $price_based)
{
    // Validate inputs
    $post_id = absint($post_id);
    if (!$post_id || !get_post($post_id)) {
        return false;
    }

    // Sanitize and validate date/time inputs
    $selected_day = sanitize_text_field($selected_day);
    $start_time_schedule = sanitize_text_field($start_time_schedule);
    $return_time_schedule = $return_time_schedule ? sanitize_text_field($return_time_schedule) : '';
    
    // Validate coordinates
    $start_place_coordinates = sanitize_text_field($start_place_coordinates);
    $end_place_coordinates = sanitize_text_field($end_place_coordinates);
    
    // Validate price based
    $price_based = sanitize_text_field($price_based);
    
    // Check if available for all time
    $available_all_time = get_post_meta($post_id, 'mpcrbm_available_for_all_time', true);
    if ($available_all_time === 'on') {
        return true;
    }

    // Initialize schedule array
    $schedule = [];
    
    // Get schedule for each day
    foreach ($days_name as $name) {
        // Sanitize day name
        $name = sanitize_text_field($name);
        
        // Get start time
        $start_time = get_post_meta($post_id, "mpcrbm_" . $name . "_start_time", true);
        if ($start_time === '') {
            $start_time = get_post_meta($post_id, "mpcrbm_default_start_time", true);
        }
        
        // Get end time
        $end_time = get_post_meta($post_id, "mpcrbm_" . $name . "_end_time", true);
        if ($end_time === '') {
            $end_time = get_post_meta($post_id, "mpcrbm_default_end_time", true);
        }
        
        // Only add to schedule if both times are set
        if ($start_time !== "" && $end_time !== "") {
            $schedule[$name] = [
                sanitize_text_field($start_time),
                sanitize_text_field($end_time)
            ];
        }
    }

    // Check schedule for selected day
    foreach ($schedule as $day => $times) {
        $day_start_time = $times[0];
        $day_end_time = $times[1];
        $day = ucwords($day);

        if ($selected_day == $day) {
            if ($return_time_schedule !== "") {
                if (
                    $return_time_schedule >= $day_start_time && 
                    $return_time_schedule <= $day_end_time && 
                    $start_time_schedule >= $day_start_time && 
                    $start_time_schedule <= $day_end_time
                ) {
                    return true;
                }
            } else {
                if ($start_time_schedule >= $day_start_time && $start_time_schedule <= $day_end_time) {
                    return true;
                }
            }
        }
    }

    // Check default times if no schedule found
    $all_empty = true;
    foreach ($schedule as $times) {
        if (!empty($times[0]) || !empty($times[1])) {
            $all_empty = false;
            break;
        }
    }

    if ($all_empty) {
        $default_start_time = get_post_meta($post_id, "mpcrbm_default_start_time", true);
        $default_end_time = get_post_meta($post_id, "mpcrbm_default_end_time", true);
        
        if ($default_start_time !== "" && $default_end_time !== "") {
            if ($return_time_schedule !== "") {
                if (
                    $return_time_schedule >= $default_start_time && 
                    $return_time_schedule <= $default_end_time && 
                    $start_time_schedule >= $default_start_time && 
                    $start_time_schedule <= $default_end_time
                ) {
                    return true;
                }
            } else {
                if ($start_time_schedule >= $default_start_time && $start_time_schedule <= $default_end_time) {
                    return true;
                }
            }
        }
    }
    return false;
}

// Verify nonce
if (
    !isset($_POST['mpcrbm_transportation_type_nonce']) || 
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpcrbm_transportation_type_nonce'])), 'mpcrbm_transportation_type_nonce')
) {
    wp_send_json_error(array('message' => esc_html__('Security check failed', 'car-rental-manager')));
    wp_die();
}

// Sanitize and validate date inputs
$start_date = isset($_POST["start_date"]) ? sanitize_text_field(wp_unslash($_POST["start_date"])) : "";
if ($start_date) {
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $start_date) {
        wp_send_json_error(array('message' => esc_html__('Invalid date format', 'car-rental-manager')));
        wp_die();
    }
}

// Sanitize and validate time inputs
$start_time = isset($_POST["start_time"]) ? sanitize_text_field(wp_unslash($_POST["start_time"])) : "";
$start_time_schedule = $start_time;

if ($start_time !== "") {
    if ($start_time !== "0") {
        // Validate time format
        if (!preg_match('/^\d+(\.\d+)?$/', $start_time)) {
            wp_send_json_error(array('message' => esc_html__('Invalid time format', 'car-rental-manager')));
            wp_die();
        }

        // Convert start time to hours and minutes safely
        $time_parts = explode('.', $start_time);
        $hours = isset($time_parts[0]) ? absint($time_parts[0]) : 0;
        $decimal_part = isset($time_parts[1]) ? absint($time_parts[1]) : 0;

        // Validate hours
        if ($hours < 0 || $hours > 23) {
            wp_send_json_error(array('message' => esc_html__('Invalid hours', 'car-rental-manager')));
            wp_die();
        }

        $interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time');
        
        // Calculate minutes based on interval time
        if ($interval_time == "5" || $interval_time == "15") {
            $minutes = $decimal_part != 3 ? $decimal_part : ($decimal_part * 10);
        } else {
            $minutes = $decimal_part * 10;
        }

        // Validate minutes
        if ($minutes < 0 || $minutes > 59) {
            wp_send_json_error(array('message' => esc_html__('Invalid minutes', 'car-rental-manager')));
            wp_die();
        }
    } else {
        $hours = 0;
        $minutes = 0;
    }
} else {
    $hours = 0;
    $minutes = 0;
}

// Format time safely
$start_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

// Combine date and time if both are available
$date = $start_date ? gmdate("Y-m-d", strtotime($start_date)) : "";
if ($date && $start_time !== "") {
    $date .= " " . $start_time_formatted;
}

// Sanitize location inputs
$start_place = isset($_POST["start_place"]) ? sanitize_text_field(wp_unslash($_POST["start_place"])) : "";
$start_place_coordinates = isset($_POST["start_place_coordinates"]) 
    ? sanitize_text_field(wp_unslash($_POST["start_place_coordinates"]))
    : '';
$end_place_coordinates = isset($_POST["end_place_coordinates"])
    ? sanitize_text_field(wp_unslash($_POST["end_place_coordinates"]))
    : '';
$end_place = isset($_POST["end_place"]) ? sanitize_text_field(wp_unslash($_POST["end_place"])) : "";

// Sanitize and validate numeric inputs
$two_way = 2;
$waiting_time = isset($_POST["waiting_time"]) ? absint(wp_unslash($_POST["waiting_time"])) : 0;
$fixed_time = isset($_POST["fixed_time"]) ? sanitize_text_field(wp_unslash($_POST["fixed_time"])) : "";
$return_time_schedule = null;

// Sanitize price based input
$price_based = isset($_POST["price_based"]) ? sanitize_text_field(wp_unslash($_POST["price_based"])) : '';

// Handle return journey if two-way
if ($two_way > 1) {
    // Sanitize and validate return date
    $return_date = isset($_POST["return_date"]) ? sanitize_text_field(wp_unslash($_POST["return_date"])) : "";
    if ($return_date) {
        // Validate return date format
        $date_obj = DateTime::createFromFormat('Y-m-d', $return_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $return_date) {
            wp_send_json_error(array('message' => esc_html__('Invalid return date format', 'car-rental-manager')));
            wp_die();
        }
        
        // Ensure return date is not before start date
        if ($start_date && strtotime($return_date) < strtotime($start_date)) {
            wp_send_json_error(array('message' => esc_html__('Return date cannot be before start date', 'car-rental-manager')));
            wp_die();
        }
    }

    // Sanitize and validate return time
    $return_time = isset($_POST["return_time"]) ? sanitize_text_field(wp_unslash($_POST["return_time"])) : "";
    $return_time_schedule = $return_time;

    if ($return_time !== "") {
        if ($return_time !== "0") {
            // Validate return time format
            if (!preg_match('/^\d+(\.\d+)?$/', $return_time)) {
                wp_send_json_error(array('message' => esc_html__('Invalid return time format', 'car-rental-manager')));
                wp_die();
            }

            // Convert return time to hours and minutes safely
            $time_parts = explode('.', $return_time);
            $hours = isset($time_parts[0]) ? absint($time_parts[0]) : 0;
            $decimal_part = isset($time_parts[1]) ? absint($time_parts[1]) : 0;

            // Validate hours
            if ($hours < 0 || $hours > 23) {
                wp_send_json_error(array('message' => esc_html__('Invalid return hours', 'car-rental-manager')));
                wp_die();
            }

            $interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time');
            
            // Calculate minutes based on interval time
            if ($interval_time == "5" || $interval_time == "15") {
                $minutes = $decimal_part * 1;
            } else {
                $minutes = $decimal_part * 10;
            }

            // Validate minutes
            if ($minutes < 0 || $minutes > 59) {
                wp_send_json_error(array('message' => esc_html__('Invalid return minutes', 'car-rental-manager')));
                wp_die();
            }
        } else {
            $hours = 0;
            $minutes = 0;
        }
    } else {
        $hours = 0;
        $minutes = 0;
    }

    // Format return time safely
    $return_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

    // Combine return date and time if both are available
    $return_date_time = $return_date ? gmdate("Y-m-d", strtotime($return_date)) : "";
    if ($return_date_time && $return_time !== "") {
        $return_date_time .= " " . $return_time_formatted;
    }
}

// Handle feature filtering
if (MPCRBM_Global_Function::get_settings("mpcrbm_general_settings", "enable_filter_via_features") == "yes") {
    $feature_passenger_number = isset($_POST["feature_passenger_number"]) 
        ? absint(wp_unslash($_POST["feature_passenger_number"])) 
        : 0;
    $feature_bag_number = isset($_POST["feature_bag_number"]) 
        ? absint(wp_unslash($_POST["feature_bag_number"])) 
        : 0;
}

// Get available vehicles
$mpcrbm_bags = [];
$mpcrbm_passengers = [];
$mpcrbm_all_transport_id = MPCRBM_Global_Function::get_all_post_id('mpcrbm_rent');

if (!empty($mpcrbm_all_transport_id)) {
    foreach ($mpcrbm_all_transport_id as $value) {
        if ($value && get_post($value)) {
            $mpcrbm_bags[] = MPCRBM_Global_Function::get_post_info($value,'mpcrbm_maximum_bag',0);
            $mpcrbm_passengers[] = MPCRBM_Global_Function::get_post_info($value,'mpcrbm_maximum_passenger',0);
        }
    }
}

$mpcrbm_bags = !empty($mpcrbm_bags) ? max($mpcrbm_bags) : 0;
$mpcrbm_passengers = !empty($mpcrbm_passengers) ? max($mpcrbm_passengers) : 0;

$startDate_str  = new DateTime( $date );
$returnDate_str = new DateTime( $return_date_time );
$interval = $startDate_str->diff( $returnDate_str );
$minutes_all        = ( $interval->days * 24 * 60 ) + ( $interval->h * 60 ) + $interval->i;
$minutes_to_day = ceil( $minutes_all / 1440 );
;

if( $is_redirect === 'yes' ){
?>
<div data-tabs-next_redirect="#mpcrbm_search_result" class="mpcrbm_map_search_result">
<?php } else {?>
<div data-tabs-next="#mpcrbm_search_result" class="mpcrbm_map_search_result">
<?php }?>
    <input type="hidden" name="mpcrbm_post_id" value="" data-price="" />
    <input type="hidden" name="mpcrbm_start_place" value="<?php echo esc_attr($start_place); ?>" />
    <input type="hidden" name="mpcrbm_end_place" value="<?php echo esc_attr($end_place); ?>" />
    <input type="hidden" name="mpcrbm_date" value="<?php echo esc_attr($date); ?>" />
    <input type="hidden" name="mpcrbm_taxi_return" value="<?php echo esc_attr($two_way); ?>" />

    <input type="hidden" name="mpcrbm_map_return_date" id="mpcrbm_map_return_date" value="<?php echo esc_attr($return_date); ?>" />
    <input type="hidden" name="mpcrbm_map_return_time" id="mpcrbm_map_return_time" value="<?php echo esc_attr($return_time); ?>" />



    <div class="sticky_section">
        <div class="flexWrap">

            <?php include MPCRBM_Function::template_path("registration/summary.php"); ?>
            <div class="mainSection ">
                <div class="sticky_depend_area fdColumn">
                    <!-- Filter area start -->
                    <?php if (MPCRBM_Global_Function::get_settings("mpcrbm_general_settings", "enable_filter_via_features") == "yes") { ?>
                        <div class="_dLayout_dFlex_fdColumn_btLight_2 mpcrbm-filter-feature">
                            <div class="mpcrbm-filter-feature-input">
                                <span><i class="fas fa-users _textTheme_mR_xs"></i><?php esc_html_e("Number Of Passengers", "car-rental-manager"); ?></span>
                                <label>
                                    <select id="mpcrbm_passenger_number" class="formControl" name="mpcrbm_passenger_number">
                                        <?php
                                        for ($i = 0; $i <= $mpcrbm_passengers; $i++) {
                                            echo '<option value="' . esc_html($i) . '">' .  esc_html($i) . '</option>';
                                        }
                                        ?>
                                    </select>

                                </label>
                            </div>
                            <div class="mpcrbm-filter-feature-input">
                                <span><i class="fa  fa-shopping-bag _textTheme_mR_xs"></i><?php esc_html_e("Number Of Bags", "car-rental-manager"); ?></span>
                                <label>
                                    <select id="mpcrbm_shopping_number" class="formControl" name="mpcrbm_shopping_number">
                                        <?php
                                        for ($i = 0; $i <= $mpcrbm_bags; $i++) {
                                            echo '<option value="' . esc_html($i) . '">' .  esc_html($i) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </label>
                            </div>

                        </div>
                    <?php
                    } ?>
                    <!-- Filter area end -->
                    <?php

                    $all_posts = MPCRBM_Query::query_transport_list($price_based);

                    if ($all_posts->found_posts > 0) {
                        $posts = $all_posts->posts;
                        $vehicle_item_count = 0;
                        $remove_class_item_post_id = [];
                        foreach ($posts as $post) {

                            $post_id = $post->ID;
                            $check_schedule = mpcrbm_get_schedule($post_id, $days_name, $start_date, $start_time_schedule, $return_time_schedule, $start_place_coordinates, $end_place_coordinates, $price_based);
                            $check_operation_area = mpcrbm_check_operation_area($post_id, $start_place, $end_place);
                            
                           
                            if ($check_schedule && $check_operation_area) {

                                $vehicle_item_count = $vehicle_item_count + 1;
                                include MPCRBM_Function::template_path("registration/vehicle_item.php");
                            }
                        }
                    } else {
                    ?>
                        <div class="_dLayout_mT_bgWarning">
                            <h3><?php esc_html_e("No Transport Available !", "car-rental-manager"); ?></h3>
                        </div>
                    <?php
                    }
                    ?>
                    <div class="_dLayout_mT_bgWarning geo-fence-no-transport">
                        <h3><?php esc_html_e("No Transport Available !!", "car-rental-manager"); ?></h3>
                    </div>
                    <div class="mpcrbm_extra_service"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div data-tabs-next="#mpcrbm_order_summary" class="mpcrbm_order_summary">
    <div class="sticky_section">
        <div class="flexWrap">
            <?php
            include MPCRBM_Function::template_path("registration/summary.php"); ?>
            <div class="mainSection ">
                <div class="sticky_depend_area fdColumn mpcrbm_checkout_area">
                </div>
            </div>
        </div>
    </div>
</div>
<?php

?>