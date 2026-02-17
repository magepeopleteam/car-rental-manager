<?php
$label = MPCRBM_Function::get_name();
$days = MPCRBM_Global_Function::week_day();
$days_name = array_keys($days);
$schedule = [];
$is_redirect = 'no';

$start_date = date('Y-m-d');
$return_date = date('Y-m-d', strtotime('+1 day'));
$price_based = '';
$start_time = "10";
$return_time = "10";
$start_place = $get_start_location;
$end_place = $start_place;
//error_log( print_r( [ '$get_start_location' => $get_start_location ], true ) );

if ($start_date) {
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $start_date) {
        wp_send_json_error(array('message' => esc_html__('Invalid date format', 'car-rental-manager')));
        wp_die();
    }
}

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


$two_way = 1;
$return_time_schedule = null;
// Format return time safely
$return_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

// Combine return date and time if both are available
$return_date_time = $return_date ? gmdate("Y-m-d", strtotime($return_date)) : "";
if ($return_date_time && $return_time !== "") {
    $return_date_time .= " " . $return_time_formatted;
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

$all_posts = MPCRBM_Query::query_transport_list($price_based);
$post_ids = $left_side_filter = [];
if ( $all_posts->found_posts > 0 ) {
    $posts = $all_posts->posts;
    $vehicle_item_count = 0;
    $remove_class_item_post_id = [];
    foreach ($posts as $post) {
        $post_id = $post->ID;
        $check_schedule = MPCRBM_Function::mpcrbm_get_schedule_search_form($post_id, $days_name, $start_date, $start_time_schedule, $return_time_schedule, $price_based);
        $check_operation_area = MPCRBM_Function::mpcrbm_check_operation_area_seach_form($post_id, $start_place, $end_place);

        if ($check_schedule && $check_operation_area) {
            $post_ids[] = $post_id;
        }
    }
}


if( count( $post_ids ) > 0 ){
    $left_side_filter = MPCRBM_Global_Function::get_meta_key( $post_ids );
}

// Given datetime
$final_start_datetime = $start_date . ' ' . $start_time . ':00';
//$mpcrbm_all_booked_car_ids = MPCRBM_Global_Function::get_mpcrbm_ids_by_datetime( $final_start_datetime );

$all_cal_with_stock = MPCRBM_Global_Function::get_available_cars_by_datetime( $final_start_datetime );
$mpcrbm_all_booked_car_ids = $all_cal_with_stock['unavailable_cars'];
$available_cars_car_ids    = $all_cal_with_stock['available_cars'];

if( is_array( $post_ids ) && is_array( $mpcrbm_all_booked_car_ids ) ){
    $post_ids = array_diff( $post_ids, $mpcrbm_all_booked_car_ids);
}


?>
<div class="mpcrbm_map_search_result" id="mpcrbm_search_result">
    <input type="hidden" name="mpcrbm_post_id" value="" data-price="" />
    <input type="hidden" name="mpcrbm_start_place" value="<?php echo esc_attr($start_place); ?>" />
    <input type="hidden" name="mpcrbm_end_place" value="<?php echo esc_attr($end_place); ?>" />
    <input type="hidden" name="mpcrbm_date" value="<?php echo esc_attr($date); ?>" />
    <input type="hidden" name="mpcrbm_taxi_return" value="<?php echo esc_attr($two_way); ?>" />

    <input type="hidden" name="mpcrbm_map_return_date" id="mpcrbm_map_return_date" value="<?php echo esc_attr($return_date); ?>" />
    <input type="hidden" name="mpcrbm_map_return_time" id="mpcrbm_map_return_time" value="<?php echo esc_attr($return_time); ?>" />

    <input type="hidden" id="mpcrbm_selected_car_quantity" name="mpcrbm_selected_car_quantity"  value="1" />

    <div class="sticky_section mpcrbm_search_result_holder" >
        <div class="mpcrbm_left_filter">
            <?php do_action( 'mpcrbm_left_side_car_filter', $left_side_filter );?>
        </div>
        <div class="mpcrbm_main_content">
            <div class="mpcrbm_mainSection ">
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

                    $car_class = 'mpcrbm_with_search_form';
                    //                    $all_posts = MPCRBM_Query::query_transport_list($price_based);

                    if ( count( $post_ids ) > 0 ) {
                        foreach ( $post_ids as $post_id) {
                            include MPCRBM_Function::template_path("registration/vehicle_item_search_form.php");
                        }
                        if( count( $post_ids ) > 10 ){ ?>
                            <div class="mpcrbm_search_result_load_more_holder">
                                <div class="mpcrbm_load_more_btn">Load More Car</div>
                            </div>
                       <?php }
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

                </div>
            </div>

            <div class="mpcrbm_transport_summary">
                <h3 ><?php esc_html_e(' Details', 'car-rental-manager') ?></h3>
                <div class="divider"></div>
                <div class="_textColor_4 justifyBetween book-items">
                    <p class="_dFlex_alignCenter">
                        <span class="fas fa-check-square _textTheme_mR_xs"></span>
                        <span class="mpcrbm_product_name"></span>&nbsp;
                        <span class="textTheme mpcrbm_car_qty_display">x1</span>
                    </p>
                    <p class="mpcrbm_product_price _textTheme"></p>
                </div>
                <div class="mpcrbm_extra_service_summary"></div>
                <div class="justifyBetween total">
                    <h6><?php esc_html_e('Total : ', 'car-rental-manager'); ?></h6>
                    <h3 class="mpcrbm_product_total_price"></h3>
                </div>
            </div>


            <div class="mpcrbm_extra_service"></div>
        </div>
    </div>

</div>
<div data-tabs-next="#mpcrbm_order_summary" class="mpcrbm_order_summary">
    <div class="sticky_section">
        <div class="flexWrap">
            <?php
//            include MPCRBM_Function::template_path("registration/summary.php"); ?>
            <div class="mpcrbm_mainSection ">
                <div class="sticky_depend_area fdColumn mpcrbm_checkout_area">
                </div>
            </div>
        </div>
    </div>
</div>
