<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
*/
if (!defined("ABSPATH")) {
    die();
} // Cannot access pages directly
$label = MPTBM_Function::get_name();
$days = MPCR_Global_Function::week_day();
$days_name = array_keys($days);
$schedule = [];


function wptbm_check_operation_area($post_id, $start_place, $end_place)
{
    // Retrieve saved locations from post meta
    $saved_locations = get_post_meta($post_id, 'mptbm_terms_price_info', true);

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


function wptbm_get_schedule($post_id, $days_name, $selected_day, $start_time_schedule, $return_time_schedule, $start_place_coordinates, $end_place_coordinates, $price_based)
{
    // Check if nonce is set
    if (!isset($_POST['mptbm_transportation_type_nonce'])) {
        return;
    }

    // Unslash and verify the nonce
    $nonce = wp_unslash($_POST['mptbm_transportation_type_nonce']);
    if (!wp_verify_nonce($nonce, 'mptbm_transportation_type_nonce')) {
        return;
    }
    $timestamp = strtotime($selected_day);

    $selected_day = gmdate('l', $timestamp);


    //Schedule array
    $schedule = [];
?>
    
<?php


    $available_all_time = get_post_meta($post_id, 'mptbm_available_for_all_time');

    if ($available_all_time[0] == 'on') {
        return true;
    }
    foreach ($days_name as $name) {
        $start_time = get_post_meta($post_id, "mptbm_" . $name . "_start_time", true);
        if ($start_time == '') {
            $start_time = get_post_meta($post_id, "mptbm_default_start_time", true);
        }
        $end_time = get_post_meta($post_id, "mptbm_" . $name . "_end_time", true);
        if ($end_time == '') {
            $end_time = get_post_meta($post_id, "mptbm_default_end_time", true);
        }
        if ($start_time !== "" && $end_time !== "") {
            $schedule[$name] = [$start_time, $end_time];
        }
    }

    foreach ($schedule as $day => $times) {
        $day_start_time = $times[0];
        $day_end_time = $times[1];
        $day = ucwords($day);

        if ($selected_day == $day) {
            if (isset($return_time_schedule) && $return_time_schedule !== "") {
                if ($return_time_schedule >= $day_start_time && $return_time_schedule <= $day_end_time && ($start_time_schedule >= $day_start_time && $start_time_schedule <= $day_end_time)) {
                    return true;
                }
            } else {
                if ($start_time_schedule >= $day_start_time && $start_time_schedule <= $day_end_time) {
                    return true;
                }
            }
        }
    }
    // If all other days have empty start and end times, check the 'default' day
    $all_empty = true;
    foreach ($schedule as $times) {
        if (!empty($times[0]) || !empty($times[1])) {
            $all_empty = false;
            break;
        }
    }

    if ($all_empty) {
        $default_start_time = get_post_meta($post_id, "mptbm_default_start_time", true);
        $default_end_time = get_post_meta($post_id, "mptbm_default_end_time", true);
        if ($default_start_time !== "" && $default_end_time !== "") {
            if (isset($return_time_schedule) && $return_time_schedule !== "") {
                if ($return_time_schedule >= $default_start_time && $return_time_schedule <= $default_end_time && ($start_time_schedule >= $default_start_time && $start_time_schedule <= $default_end_time)) {
                    return true; // $start_time_schedule and $return_time_schedule are within the schedule for this day

                }
            } else {
                if ($start_time_schedule >= $default_start_time && $start_time_schedule <= $default_end_time) {
                    return true; // $start_time_schedule is within the schedule for this day

                }
            }
        }
    }
    return false;
}
// Check if nonce is set
if (!isset($_POST['mptbm_transportation_type_nonce'])) {
    return;
}

// Unslash and verify the nonce
$nonce = wp_unslash($_POST['mptbm_transportation_type_nonce']);
if (!wp_verify_nonce($nonce, 'mptbm_transportation_type_nonce')) {
    return;
}
$start_date = isset($_POST["start_date"]) ? sanitize_text_field(wp_unslash($_POST["start_date"])) : "";

$start_time_schedule = isset($_POST["start_time"]) ? sanitize_text_field(wp_unslash($_POST["start_time"])) : "";
$start_time = isset($_POST["start_time"]) ? sanitize_text_field(wp_unslash($_POST["start_time"])) : "";

if ($start_time !== "") {
    if ($start_time !== "0") {

        // Convert start time to hours and minutes
        list($hours, $decimal_part) = explode('.', $start_time);
        $interval_time = MPTBM_Function::get_general_settings('mptbm_pickup_interval_time');

        if ($interval_time == "5" || $interval_time == "15") {
            if ($decimal_part != 3) {
                $minutes = isset($decimal_part) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
            } else {
                $minutes = isset($decimal_part) ? (int) $decimal_part * 10 : 0; // Multiply by 1 to convert to minutes
            }
        } else {
            $minutes = isset($decimal_part) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
        }
    } else {
        $hours = 0;
        $minutes = 0;
    }
} else {
    $hours = 0;
    $minutes = 0;
}

// Format hours and minutes
$start_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

// Combine date and time if both are available
$date = $start_date ? gmdate("Y-m-d", strtotime($start_date)) : "";
if ($date && $start_time !== "") {
    $date .= " " . $start_time_formatted;
}

$start_place = isset($_POST["start_place"]) ? sanitize_text_field(wp_unslash($_POST["start_place"])) : "";
$start_place_coordinates = isset($_POST["start_place_coordinates"])
    ? sanitize_text_field(wp_unslash($_POST["start_place_coordinates"]))
    : '';
$end_place_coordinates = $end_place_coordinates = isset($_POST["end_place_coordinates"])
    ? sanitize_text_field(wp_unslash($_POST["end_place_coordinates"]))
    : '';
$end_place = isset($_POST["end_place"]) ? sanitize_text_field(wp_unslash($_POST["end_place"])) : "";
$two_way = 2;
$waiting_time = isset($_POST["waiting_time"]) ? sanitize_text_field(wp_unslash($_POST["waiting_time"])) : 0;
$fixed_time = isset($_POST["fixed_time"]) ? sanitize_text_field(wp_unslash($_POST["fixed_time"])) : "";
$return_time_schedule = null;

$price_based = isset($_POST["price_based"]) ? sanitize_text_field(wp_unslash($_POST["price_based"])) : '';


if ($two_way > 1) {
    $return_date = isset($_POST["return_date"]) ? sanitize_text_field(wp_unslash($_POST["return_date"])) : "";
    $return_time = isset($_POST["return_time"]) ? sanitize_text_field(wp_unslash($_POST["return_time"])) : "";
    $return_time_schedule = isset($_POST["return_time"]) ? sanitize_text_field(wp_unslash($_POST["return_time"])) : "";

    if ($return_time !== "") {
        if ($return_time !== "0") {
            // Convert start time to hours and minutes
            list($hours, $decimal_part) = explode('.', $return_time);
            $interval_time = MPTBM_Function::get_general_settings('mptbm_pickup_interval_time');
            if ($interval_time == "5" || $interval_time == "15") {
                $minutes = isset($decimal_part) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
            } else {
                $minutes = isset($decimal_part) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
            }
        } else {
            $hours = 0;
            $minutes = 0;
        }
    } else {
        $hours = 0;
        $minutes = 0;
    }

    // Format hours and minutes
    $return_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

    // Combine date and time if both are available
    $return_date_time = $return_date ? gmdate("Y-m-d", strtotime($return_date)) : "";

    if ($return_date_time && $return_time !== "") {
        $return_date_time .= " " . $return_time_formatted;
    }
}
if (MPCR_Global_Function::get_settings("mptbm_general_settings", "enable_filter_via_features") == "yes") {
    $feature_passenger_number = isset($_POST["feature_passenger_number"]) ? sanitize_text_field(wp_unslash($_POST["feature_passenger_number"])) : "";
    $feature_bag_number = isset($_POST["feature_bag_number"]) ? sanitize_text_field(wp_unslash($_POST["feature_bag_number"])) : "";
}
$mptbm_bags = [];
$mptbm_passengers = [];
$mptbm_all_transport_id = MPCR_Global_Function::get_all_post_id('mptbm_rent');
foreach ($mptbm_all_transport_id as $key => $value) {
    array_push($mptbm_bags, MPTBM_Function::get_feature_bag($value));
    array_push($mptbm_passengers, MPTBM_Function::get_feature_passenger($value));
}
$mptbm_bags =  max($mptbm_bags);
$mptbm_passengers = max($mptbm_passengers);
?>
<div data-tabs-next="#mptbm_search_result" class="mptbm_map_search_result">
    <input type="hidden" name="mptbm_post_id" value="" data-price="" />
    <input type="hidden" name="mptbm_start_place" value="<?php echo esc_attr($start_place); ?>" />
    <input type="hidden" name="mptbm_end_place" value="<?php echo esc_attr($end_place); ?>" />
    <input type="hidden" name="mptbm_date" value="<?php echo esc_attr($date); ?>" />
    <input type="hidden" name="mptbm_taxi_return" value="<?php echo esc_attr($two_way); ?>" />

    <input type="hidden" name="mptbm_map_return_date" id="mptbm_map_return_date" value="<?php echo esc_attr($return_date); ?>" />
    <input type="hidden" name="mptbm_map_return_time" id="mptbm_map_return_time" value="<?php echo esc_attr($return_time); ?>" />



    <div class="mp_sticky_section">
        <div class="flexWrap">

            <?php include MPTBM_Function::template_path("registration/summary.php"); ?>
            <div class="mainSection ">
                <div class="mp_sticky_depend_area fdColumn">
                    <!-- Filter area start -->
                    <?php if (MPCR_Global_Function::get_settings("mptbm_general_settings", "enable_filter_via_features") == "yes") { ?>
                        <div class="_dLayout_dFlex_fdColumn_btLight_2 mptbm-filter-feature">
                            <div class="mptbm-filter-feature-input">
                                <span><i class="fas fa-users _textTheme_mR_xs"></i><?php esc_html_e("Number Of Passengers", "car-rental-manager"); ?></span>
                                <label>
                                    <select id="mptbm_passenger_number" class="formControl" name="mptbm_passenger_number">
                                        <?php
                                        for ($i = 0; $i <= $mptbm_passengers[0]; $i++) {
                                            echo '<option value="' . esc_html($i) . '">' .  esc_html($i) . '</option>';
                                        }
                                        ?>
                                    </select>

                                </label>
                            </div>
                            <div class="mptbm-filter-feature-input">
                                <span><i class="fa  fa-shopping-bag _textTheme_mR_xs"></i><?php esc_html_e("Number Of Bags", "car-rental-manager"); ?></span>
                                <label>
                                    <select id="mptbm_shopping_number" class="formControl" name="mptbm_shopping_number">
                                        <?php
                                        for ($i = 0; $i <= $mptbm_bags[0]; $i++) {
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

                    $all_posts = MPTBM_Query::query_transport_list($price_based);

                    if ($all_posts->found_posts > 0) {
                        $posts = $all_posts->posts;
                        $vehicle_item_count = 0;
                        $remove_class_item_post_id = [];
                        foreach ($posts as $post) {

                            $post_id = $post->ID;
                            $check_schedule = wptbm_get_schedule($post_id, $days_name, $start_date, $start_time_schedule, $return_time_schedule, $start_place_coordinates, $end_place_coordinates, $price_based);
                            $check_operation_area = wptbm_check_operation_area($post_id, $start_place, $end_place);
                            
                           
                            if ($check_schedule && $check_operation_area) {

                                $vehicle_item_count = $vehicle_item_count + 1;
                                include MPTBM_Function::template_path("registration/vehicle_item.php");
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
                    <div class="mptbm_extra_service"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div data-tabs-next="#mptbm_order_summary" class="mptbm_order_summary">
    <div class="mp_sticky_section">
        <div class="flexWrap">
            <?php include MPTBM_Function::template_path("registration/summary.php"); ?>
            <div class="mainSection ">
                <div class="mp_sticky_depend_area fdColumn mptbm_checkout_area">
                </div>
            </div>
        </div>
    </div>
</div>
<?php

?>