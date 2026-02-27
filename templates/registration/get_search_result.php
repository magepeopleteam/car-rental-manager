<?php
$mpcrbm_label = MPCRBM_Function::get_name();
$mpcrbm_days = MPCRBM_Global_Function::week_day();
$mpcrbm_days_name = array_keys($mpcrbm_days);
$mpcrbm_schedule = [];
$mpcrbm_is_redirect = 'no';

$mpcrbm_start_date = gmdate('Y-m-d');
$mpcrbm_return_date = gmdate('Y-m-d', strtotime('+1 day'));
$mpcrbm_price_based = '';
$mpcrbm_start_time = "10";
$mpcrbm_return_time = "10";
$mpcrbm_start_place = $mpcrbm_get_start_location;
$mpcrbm_end_place = $mpcrbm_start_place;
//error_log( print_r( [ '$get_start_location' => $get_start_location ], true ) );

if ($mpcrbm_start_date) {
    // Validate date format
    $mpcrbm_date_obj = DateTime::createFromFormat('Y-m-d', $mpcrbm_start_date);
    if (!$mpcrbm_date_obj || $mpcrbm_date_obj->format('Y-m-d') !== $mpcrbm_start_date) {
        wp_send_json_error(array('message' => esc_html__('Invalid date format', 'car-rental-manager')));
        wp_die();
    }
}

$mpcrbm_start_time_schedule = $mpcrbm_start_time;

if ($mpcrbm_start_time !== "") {
    if ($mpcrbm_start_time !== "0") {
        // Validate time format
        if (!preg_match('/^\d+(\.\d+)?$/', $mpcrbm_start_time)) {
            wp_send_json_error(array('message' => esc_html__('Invalid time format', 'car-rental-manager')));
            wp_die();
        }

        // Convert start time to hours and minutes safely
        $mpcrbm_time_parts = explode('.', $mpcrbm_start_time);
        $mpcrbm_hours = isset($mpcrbm_time_parts[0]) ? absint($mpcrbm_time_parts[0]) : 0;
        $mpcrbm_decimal_part = isset($mpcrbm_time_parts[1]) ? absint($mpcrbm_time_parts[1]) : 0;

        // Validate hours
        if ($mpcrbm_hours < 0 || $mpcrbm_hours > 23) {
            wp_send_json_error(array('message' => esc_html__('Invalid hours', 'car-rental-manager')));
            wp_die();
        }

        $mpcrbm_interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time');

        // Calculate minutes based on interval time
        if ($mpcrbm_interval_time == "5" || $mpcrbm_interval_time == "15") {
            $mpcrbm_minutes = $mpcrbm_decimal_part != 3 ? $mpcrbm_decimal_part : ($mpcrbm_decimal_part * 10);
        } else {
            $mpcrbm_minutes = $mpcrbm_decimal_part * 10;
        }

        // Validate minutes
        if ($mpcrbm_minutes < 0 || $mpcrbm_minutes > 59) {
            wp_send_json_error(array('message' => esc_html__('Invalid minutes', 'car-rental-manager')));
            wp_die();
        }
    } else {
        $mpcrbm_hours = 0;
        $mpcrbm_minutes = 0;
    }
} else {
    $mpcrbm_hours = 0;
    $mpcrbm_minutes = 0;
}

// Format time safely
$mpcrbm_start_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes );

// Combine date and time if both are available
$mpcrbm_date = $mpcrbm_start_date ? gmdate("Y-m-d", strtotime($mpcrbm_start_date)) : "";
if ($mpcrbm_date && $mpcrbm_start_time !== "") {
    $mpcrbm_date .= " " . $mpcrbm_start_time_formatted;
}


$mpcrbm_two_way = 1;
$mpcrbm_return_time_schedule = null;
// Format return time safely
$mpcrbm_return_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes);

// Combine return date and time if both are available
$mpcrbm_return_date_time = $mpcrbm_return_date ? gmdate("Y-m-d", strtotime($mpcrbm_return_date)) : "";
if ($mpcrbm_return_date_time && $mpcrbm_return_time !== "") {
    $mpcrbm_return_date_time .= " " . $mpcrbm_return_time_formatted;
}

// Get available vehicles
$mpcrbm_bags = [];
$mpcrbm_passengers = [];
$mpcrbm_all_transport_id = MPCRBM_Global_Function::get_all_post_id('mpcrbm_rent');

if (!empty($mpcrbm_all_transport_id)) {
    foreach ($mpcrbm_all_transport_id as $mpcrbm_value) {
        if ($mpcrbm_value && get_post($mpcrbm_value)) {
            $mpcrbm_bags[] = MPCRBM_Global_Function::get_post_info($mpcrbm_value,'mpcrbm_maximum_bag',0);
            $mpcrbm_passengers[] = MPCRBM_Global_Function::get_post_info($mpcrbm_value,'mpcrbm_maximum_passenger',0);
        }
    }
}
$mpcrbm_bags = !empty($mpcrbm_bags) ? max($mpcrbm_bags) : 0;
$mpcrbm_passengers = !empty($mpcrbm_passengers) ? max($mpcrbm_passengers) : 0;



$mpcrbm_start_date_str  = new DateTime( $mpcrbm_date );
$mpcrbm_return_date_str = new DateTime( $mpcrbm_return_date_time );
$mpcrbm_interval = $mpcrbm_start_date_str->diff( $mpcrbm_return_date_str );
$mpcrbm_minutes_all        = ( $mpcrbm_interval->days * 24 * 60 ) + ( $mpcrbm_interval->h * 60 ) + $mpcrbm_interval->i;
$mpcrbm_minutes_to_day = ceil( $mpcrbm_minutes_all / 1440 );

$mpcrbm_all_posts = MPCRBM_Query::query_transport_list($mpcrbm_price_based);
$mpcrbm_post_ids = $mpcrbm_left_side_filter = [];
if ( $mpcrbm_all_posts->found_posts > 0 ) {
    $mpcrbm_posts = $mpcrbm_all_posts->posts;
    $mpcrbm_vehicle_item_count = 0;
    $mpcrbm_remove_class_item_post_id = [];
    foreach ($mpcrbm_posts as $mpcrbm_post) {
        $mpcrbm_post_id = $mpcrbm_post->ID;
        $mpcrbm_check_schedule = MPCRBM_Function::mpcrbm_get_schedule_search_form($mpcrbm_post_id, $mpcrbm_days_name, $mpcrbm_start_date, $mpcrbm_start_time_schedule, $mpcrbm_return_time_schedule, $mpcrbm_price_based);
        $mpcrbm_check_operation_area = MPCRBM_Function::mpcrbm_check_operation_area_seach_form($mpcrbm_post_id, $mpcrbm_start_place, $mpcrbm_end_place);

        if ($mpcrbm_check_schedule && $mpcrbm_check_operation_area) {
            $mpcrbm_post_ids[] = $mpcrbm_post_id;
        }
    }
}


if( count( $mpcrbm_post_ids ) > 0 ){
    $mpcrbm_left_side_filter = MPCRBM_Global_Function::get_meta_key( $mpcrbm_post_ids );
}

// Given datetime
$mpcrbm_final_start_datetime = $mpcrbm_start_date . ' ' . $mpcrbm_start_time . ':00';
//$mpcrbm_all_booked_car_ids = MPCRBM_Global_Function::get_mpcrbm_ids_by_datetime( $mpcrbm_final_start_datetime );

$mpcrbm_all_cal_with_stock = MPCRBM_Global_Function::get_available_cars_by_datetime( $mpcrbm_final_start_datetime );
$mpcrbm_all_booked_car_ids = $mpcrbm_all_cal_with_stock['unavailable_cars'];
$mpcrbm_available_cars_car_ids    = $mpcrbm_all_cal_with_stock['available_cars'];

if( is_array( $mpcrbm_post_ids ) && is_array( $mpcrbm_all_booked_car_ids ) ){
    $mpcrbm_post_ids = array_diff( $mpcrbm_post_ids, $mpcrbm_all_booked_car_ids);
}


?>
<div class="mpcrbm_map_search_result" id="mpcrbm_search_result">
    <input type="hidden" name="mpcrbm_post_id" value="" data-price="" />
    <input type="hidden" name="mpcrbm_start_place" value="<?php echo esc_attr($mpcrbm_start_place); ?>" />
    <input type="hidden" name="mpcrbm_end_place" value="<?php echo esc_attr($mpcrbm_end_place); ?>" />
    <input type="hidden" name="mpcrbm_date" value="<?php echo esc_attr($mpcrbm_date); ?>" />
    <input type="hidden" name="mpcrbm_taxi_return" value="<?php echo esc_attr($mpcrbm_two_way); ?>" />

    <input type="hidden" name="mpcrbm_map_return_date" id="mpcrbm_map_return_date" value="<?php echo esc_attr($mpcrbm_return_date); ?>" />
    <input type="hidden" name="mpcrbm_map_return_time" id="mpcrbm_map_return_time" value="<?php echo esc_attr($mpcrbm_return_time); ?>" />

    <input type="hidden" id="mpcrbm_selected_car_quantity" name="mpcrbm_selected_car_quantity"  value="1" />

    <div class="sticky_section mpcrbm_search_result_holder" >
        <div class="mpcrbm_left_filter">
            <?php do_action( 'mpcrbm_left_side_car_filter', $mpcrbm_left_side_filter );?>
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
                                        for ($mpcrbm_i = 0; $mpcrbm_i <= $mpcrbm_passengers; $mpcrbm_i++) {
                                            echo '<option value="' . esc_html($mpcrbm_i) . '">' .  esc_html($mpcrbm_i) . '</option>';
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
                                        for ($mpcrbm_i = 0; $mpcrbm_i <= $mpcrbm_bags; $mpcrbm_i++) {
                                            echo '<option value="' . esc_html($mpcrbm_i) . '">' .  esc_html($mpcrbm_i) . '</option>';
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

                    if ( count( $mpcrbm_post_ids ) > 0 ) {
                        foreach ( $mpcrbm_post_ids as $mpcrbm_post_id) {
                            include MPCRBM_Function::template_path("registration/vehicle_item_search_form.php");
                        }
                        if( count( $mpcrbm_post_ids ) > 10 ){ ?>
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
