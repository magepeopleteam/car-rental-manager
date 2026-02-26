<?php
/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly
delete_transient('mpcrbm_original_price_based');


$mpcrbm_km_or_mile = MPCRBM_Global_Function::get_settings('mpcrbm_global_settings', 'km_or_mile', 'km');
$mpcrbm_price_based = $price_based ?? '';
set_transient('mpcrbm_original_price_based', $mpcrbm_price_based);
$mpcrbm_all_dates = MPCRBM_Function::get_all_dates($mpcrbm_price_based);
$mpcrbm_form_style = $form_style ?? 'horizontal';
$mpcrbm_form_style_class = $form_style == 'horizontal' ? 'inputHorizontal' : 'inputInline';
$mpcrbm_area_class = $mpcrbm_price_based == 'manual' ? ' ' : 'justifyBetween';
$mpcrbm_area_class = $form_style != 'horizontal' ? 'mpcrbm_form_details_area fdColumn' : $mpcrbm_area_class;
$mpcrbm_all_transport_id = MPCRBM_Global_Function::get_all_post_id('mpcrbm_rent');
$mpcrbm_available_for_all_time = false;
$mpcrbm_schedule = [];
$mpcrbm_min_schedule_value = 0;
$mpcrbm_max_schedule_value = 24;
$mpcrbm_loop = 1;

$mpcrbm_general_settings_data  = get_option( 'mpcrbm_general_settings' );

$mpcrbm_title = 'Car Rental Booking';
$mpcrbm_sub_title = 'Find and reserve your perfect vehicle';
if( isset( $mpcrbm_general_settings_data['search_title_display'] ) &&  !empty( $mpcrbm_general_settings_data['search_title_display']  ) ){
    $mpcrbm_title = $mpcrbm_general_settings_data['search_title_display'];
}
if( isset( $mpcrbm_general_settings_data['search_subtitle_display'] ) &&  !empty( $mpcrbm_general_settings_data['search_subtitle_display']  ) ){
    $mpcrbm_sub_title = $mpcrbm_general_settings_data['search_subtitle_display'];
}


foreach ($mpcrbm_all_transport_id as $mpcrbm_key => $mpcrbm_value) {
    if (MPCRBM_Global_Function::get_post_info($mpcrbm_value, 'mpcrbm_available_for_all_time') == 'on') {
        $mpcrbm_available_for_all_time = true;
    }
}

if ($mpcrbm_available_for_all_time == false) {

    foreach ($mpcrbm_all_transport_id as $mpcrbm_key => $mpcrbm_value) {
        array_push($mpcrbm_schedule, MPCRBM_Function::get_schedule($mpcrbm_value));
    }
    foreach ($mpcrbm_schedule as $mpcrbm_dayArray) {
        foreach ($mpcrbm_dayArray as $mpcrbm_times) {
            if (is_array($mpcrbm_times)) {
                if ($mpcrbm_loop) {
                    $mpcrbm_min_schedule_value = $mpcrbm_times[0];
                    $mpcrbm_max_schedule_value = $mpcrbm_times[0];
                    $mpcrbm_loop = 0;
                }
                // Loop through each element in the array
                foreach ($mpcrbm_times as $mpcrbm_time) {

                    // Update the global smallest and largest values
                    if ($mpcrbm_time < $mpcrbm_min_schedule_value) {
                        $mpcrbm_min_schedule_value = $mpcrbm_time;
                    }
                    if ($mpcrbm_time > $mpcrbm_max_schedule_value) {
                        $mpcrbm_max_schedule_value = $mpcrbm_time;
                    }
                }
            }
        }
    }
}
// Ensure the schedule values are numeric
$mpcrbm_min_schedule_value = floatval($mpcrbm_min_schedule_value);
$mpcrbm_max_schedule_value = floatval($mpcrbm_max_schedule_value);

if (!function_exists('mpcrbm_convertToMinutes')) {
    function mpcrbm_convertToMinutes($schedule_value)
    {
        $mpcrbm_hours = floor($schedule_value); // Get the hour part
        $mpcrbm_minutes = ($schedule_value - $mpcrbm_hours) * 100; // Convert decimal part to minutes
        return $mpcrbm_hours * 60 + $mpcrbm_minutes;
    }
}

$mpcrbm_min_minutes = mpcrbm_convertToMinutes($mpcrbm_min_schedule_value);
$mpcrbm_max_minutes = mpcrbm_convertToMinutes($mpcrbm_max_schedule_value);

$mpcrbm_buffer_time = (int) MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_buffer_time');

$mpcrbm_current_time = time();
$mpcrbm_current_hour = wp_date('H', $mpcrbm_current_time);
$mpcrbm_current_minute = wp_date('i', $mpcrbm_current_time);

// Convert to total minutes since midnight local time
$mpcrbm_current_minutes = intval($mpcrbm_current_hour) * 60 + intval($mpcrbm_current_minute);

$mpcrbm_buffer_end_minutes = $mpcrbm_current_minutes + $mpcrbm_buffer_time;

$mpcrbm_buffer_end_minutes = max($mpcrbm_buffer_end_minutes, 0);
while ($mpcrbm_buffer_end_minutes > 1440) {
    array_shift($mpcrbm_all_dates);
    $mpcrbm_buffer_end_minutes -= 1440;
}

if( $mpcrbm_form_style === 'horizontal' ){
    $mpcrbm_type_text_pickup = $mpcrbm_type_text_return = '';
    $mpcrbm_form_class = 'mpcrbm_horizontal_search_form';
    $mpcrbm_width_class = 'mpcrbm_100_width';
}else{
    $mpcrbm_type_text_pickup = 'Pickup';
    $mpcrbm_type_text_return = 'Return';
    $mpcrbm_form_class = 'mpcrbm_inline_search_form';
    $mpcrbm_width_class = 'mpcrbm_width_33';
}

if( $is_title === 'no' ){
    $mpcrbm_d_class = '_dLayout';
}else{
    $mpcrbm_d_class = '';
}

$mpcrbm_pickup_location = '';
$mpcrbm_return_location = '';


$mpcrbm_start_time = $mpcrbm_end_time = 10.00;
$mpcrbm_start_date = gmdate('Y-m-d');
$mpcrbm_formatted_start_date = gmdate('D d M, Y', strtotime( $mpcrbm_start_date ));
$mpcrbm_formatted_start_time = MPCRBM_Global_Function::format_custom_time( $mpcrbm_start_time );
$mpcrbm_end_date = gmdate('Y-m-d', strtotime('+1 day'));
$mpcrbm_formatted_end_date = gmdate('D d M, Y', strtotime( $mpcrbm_end_date ));
$mpcrbm_formatted_end_time = MPCRBM_Global_Function::format_custom_time( $mpcrbm_end_time );




if( is_array( $search_date ) && !empty( $search_date ) ){
    $mpcrbm_pickup_location = isset( $search_date['start_place'] ) ? $search_date['start_place'] : '' ;
    $mpcrbm_return_location =  isset( $search_date['end_place'] ) ? $search_date['end_place'] : '' ;
    $mpcrbm_start_date =  isset( $search_date['start_date'] ) ? $search_date['start_date'] : '' ;
    $mpcrbm_formatted_start_date = gmdate('D d M, Y', strtotime( $mpcrbm_start_date ));
    $mpcrbm_start_time =  isset( $search_date['start_time'] ) ? $search_date['start_time'] : '' ;
    $mpcrbm_formatted_start_time = MPCRBM_Global_Function::format_custom_time( $mpcrbm_start_time );
    $mpcrbm_end_date =  isset( $search_date['return_date'] ) ? $search_date['return_date'] : '' ;
    $mpcrbm_formatted_end_date = gmdate('D d M, Y', strtotime( $mpcrbm_end_date ));
    $mpcrbm_end_time =  isset( $search_date['return_time'] ) ? $search_date['return_time'] : '' ;
    $mpcrbm_formatted_end_time = MPCRBM_Global_Function::format_custom_time( $mpcrbm_end_time );
}

$mpcrbm_single_page = isset( $params['single_page'] ) ? $params['single_page'] : '';
if( $mpcrbm_single_page === 'yes' ){
    $mpcrbm_pickup_location = $mpcrbm_return_location = $params['pickup_location'];
}

$mpcrbm_hide_time_input_field = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'hide_time_input_field_search_form', 'no' );
$mpcrbm_input_time = 'block';
if( $mpcrbm_hide_time_input_field === 'yes' ){
    $mpcrbm_input_time = 'none';
    $mpcrbm_end_time = 23;
}

$mpcrbm_time_format_display = (int)MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'time_format_display');

if (sizeof($mpcrbm_all_dates) > 0) {
    $mpcrbm_taxi_return = MPCRBM_Function::get_general_settings('taxi_return', 'enable');
    $mpcrbm_interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time', '30');
    $mpcrbm_interval_hours = $mpcrbm_interval_time / 60;
    $mpcrbm_waiting_time_check = MPCRBM_Function::get_general_settings('taxi_waiting_time', 'enable');
    ?>
    <div class="<?php echo esc_attr($mpcrbm_area_class); ?> ">
        <div class=" mpcrbm_search_area <?php echo esc_attr($mpcrbm_form_style_class); ?> <?php echo esc_attr($mpcrbm_price_based == 'manual' ? 'mAuto' : ''); ?>">
            <?php if( $is_title === 'yes'){?>
                <div class="booking-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"></path>
                            </svg>
                        </div>
                        <div class="header-text">
                            <h2 id="mpcrbm_title_change"><?php echo esc_attr( $mpcrbm_title  );?></h2>
                            <p><?php echo esc_attr( $mpcrbm_sub_title );?></p>
                        </div>
                    </div>
                    <div class="header-badge">
                        <?php esc_attr_e( 'Quick &amp; Easy', 'car-rental-manager' );?>
                    </div>
                </div>
            <?php }?>

            <div class=" mpcrbm_search_holder ">
                <div class="mpForm">
                    <?php wp_nonce_field('mpcrbm_transportation_type_nonce', 'mpcrbm_transportation_type_nonce'); ?>
                    <input type="hidden" id="mpcrbm_km_or_mile" name="mpcrbm_km_or_mile" value="<?php echo esc_attr($mpcrbm_km_or_mile); ?>" />
                    <input type="hidden" name="mpcrbm_price_based" value="<?php echo esc_attr($mpcrbm_price_based); ?>" />
                    <input type="hidden" name="mpcrbm_post_id" value="" />
                    <?php

                   if( $ajax_search === 'yes' ){?>
                        <input type="hidden" id="mpcrbm_enable_view_search_result_page" name="mpcrbm_enable_view_search_result_page" value="No" />
                        <input type="hidden" id="mpcrbm_enable_ajax_search" name="mpcrbm_enable_ajax_search" value="yes" />
                    <?php }else {?>
                        <input type="hidden" id="mpcrbm_enable_ajax_search" name="mpcrbm_enable_ajax_search" value="no" />
                        <input type="hidden" id="mpcrbm_enable_view_search_result_page" name="mpcrbm_enable_view_search_result_page" value="<?php echo esc_attr( MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page') ); ?>" />
                    <?php }

                   ?>
                    <input type='hidden' id="mpcrbm_enable_return_in_different_date" name="mpcrbm_enable_return_in_different_date" value="yes" />
                    <input type="hidden" id="mpcrbm_enable_filter_via_features" name="mpcrbm_enable_filter_via_features" value="<?php echo esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_filter_via_features' ) ); ?>" />
                    <input type="hidden" id="mpcrbm_buffer_end_minutes" name="mpcrbm_buffer_end_minutes" value="<?php echo esc_attr( $mpcrbm_buffer_end_minutes ); ?>" />
                    <input type="hidden" id="mpcrbm_first_calendar_date" name="mpcrbm_first_calendar_date" value="<?php echo esc_attr( $mpcrbm_all_dates[0] ); ?>" />


                    <div class="<?php echo esc_attr( $mpcrbm_form_class );?>">
                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $mpcrbm_width_class );?>">
                            <div class="mpcrbm_location_checkbox input_select">
                                <label class="mpcrbm_manual_end_place ">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-marker"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Pick-up', 'car-rental-manager'); ?></span>
                                    </span>
                                    <?php if ($mpcrbm_price_based == 'manual') {
                                        ?>
                                        <?php
                                        // Get all available pickup locations (supporting multi-location)
                                        $mpcrbm_all_start_locations = MPCRBM_Function::get_all_start_location();

                                        // If multi-location is enabled for any vehicle, get all unique locations
                                        $mpcrbm_multi_location_locations = array();
                                        foreach ($mpcrbm_all_transport_id as $mpcrbm_vehicle_id) {
                                            $mpcrbm_vehicle_pickup_locations = MPCRBM_Function::get_vehicle_pickup_locations($mpcrbm_vehicle_id);
                                            $mpcrbm_multi_location_locations = array_merge($mpcrbm_multi_location_locations, $mpcrbm_vehicle_pickup_locations);
                                        }
                                        $mpcrbm_multi_location_locations = array_unique($mpcrbm_multi_location_locations);

                                        $mpcrbm_all_locations = array_unique(array_merge($mpcrbm_all_start_locations, $mpcrbm_multi_location_locations));
                                        ?>
                                        <select id="mpcrbm_manual_start_place" class="mpcrbm_manual_start_place formControl">
                                            <option selected disabled><?php esc_html_e('Pick-Up Location', 'car-rental-manager'); ?></option>
                                            <?php if (sizeof($mpcrbm_all_locations) > 0) { ?>
                                                <?php foreach ($mpcrbm_all_locations as $mpcrbm_key => $mpcrbm_start_location) {
                                                    if( $mpcrbm_key === 0 ){
                                                        $mpcrbm_get_start_location = $mpcrbm_start_location;
                                                    }

                                                    $mpcrbm_start_selected = ( $mpcrbm_pickup_location === $mpcrbm_start_location) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo esc_attr($mpcrbm_start_location); ?>" <?php echo esc_attr( $mpcrbm_start_selected ); ?>>
                                                        <?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug( $mpcrbm_start_location, 'mpcrbm_locations')); ?>
                                                    </option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <input type="text" id="mpcrbm_map_start_place" class="formControl" placeholder="<?php esc_html_e('Enter Pick-Up Location', 'car-rental-manager'); ?>" value="" />
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="mpcrbm-vertical-divider" id="mpcrbm-vertical-divide-location" style="display: none"></div>
                            <div class="mpcrbm_location_checkbox input_select" id="mpcrbm_drop_off_location" style="display: none">
                                <label class="mpcrbm_manual_end_place" >
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-marker"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Drop-off', 'car-rental-manager'); ?></span>
                                    </span>
                                    <?php if ($mpcrbm_price_based == 'manual') { ?>
                                        <select id="mpcrbm_manual_end_place" class="mpcrbm_map_end_place formControl">
                                            <option selected disabled><?php esc_html_e('Return Location', 'car-rental-manager'); ?></option>
                                            <?php if (sizeof($mpcrbm_all_locations) > 0) {
                                                ?>
                                                <?php foreach ( $mpcrbm_all_locations as $mpcrbm_start_location ) {
                                                    $mpcrbm_end_selected = ( $mpcrbm_return_location === $mpcrbm_start_location) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo esc_attr($mpcrbm_start_location); ?>" <?php echo esc_attr( $mpcrbm_end_selected );?>>
                                                        <?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($mpcrbm_start_location, 'mpcrbm_locations')); ?>
                                                    </option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <input class="formControl textCapitalize" type="text" id="mpcrbm_map_end_place" placeholder="<?php esc_html_e(' Enter Return Location', 'car-rental-manager'); ?>" value="" />
                                    <?php } ?>
                                </label>
                            </div>
                        </div>

                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $mpcrbm_width_class );?>">
                            <div class="input_select">
                                <label class="fdColumn1">
                                    <input type="hidden" id="mpcrbm_map_start_date" value="<?php echo esc_attr( $mpcrbm_start_date );?>" />
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-calendar"></i>
                                        <span class="mprcbm_text"><?php esc_html($mpcrbm_type_text_pickup.' Date'); ?></span>
                                    </span>
                                    <input type="text" id="mpcrbm_start_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_formatted_start_date );?>" readonly />
                                </label>
                            </div>

                            <div class="mpcrbm-vertical-divider" style="display: <?php echo esc_attr( $mpcrbm_input_time );?>"></div>

                            <div class=" input_select" style="display: <?php echo esc_attr( $mpcrbm_input_time );?>">
                                <input type="hidden" id="mpcrbm_map_start_time" value="<?php echo esc_attr( $mpcrbm_start_time );?>" />
                                <label class="fdColumn1">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-clock-three"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Time', 'car-rental-manager'); ?></span>
                                    </span>
                                    <?php if( $mpcrbm_time_format_display == 12 ){?>
                                        <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_formatted_start_time );?>" readonly />
                                    <?php }else{?>
                                        <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_start_time );?>" readonly />
                                    <?php }?>
                                </label>

                                <ul class="input_select_list start_time_list">
                                    <?php
                                    for ($mpcrbm_i = $mpcrbm_min_minutes; $mpcrbm_i <= $mpcrbm_max_minutes; $mpcrbm_i += $mpcrbm_interval_time) {
                                        $mpcrbm_hours = floor($mpcrbm_i / 60);
                                        $mpcrbm_minutes = $mpcrbm_i % 60;
                                        if ($mpcrbm_hours == 24) {
                                            $mpcrbm_hours = 0;
                                        }
                                        $mpcrbm_data_value = $mpcrbm_hours + ($mpcrbm_minutes / 100);
                                        $mpcrbm_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes);

                                        if( $mpcrbm_time_format_display == 12 ){ ?>
                                            <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($mpcrbm_time_formatted, 'time')); ?></li>
                                        <?php }else{?>
                                            <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html( $mpcrbm_data_value ); ?></li>
                                    <?php }
                                     } ?>

                                </ul>
                                <ul class="start_time_list-no-dsiplay" style="display:none">
                                    <?php

                                    for ($mpcrbm_i = $mpcrbm_min_minutes; $mpcrbm_i <= $mpcrbm_max_minutes; $mpcrbm_i += $mpcrbm_interval_time) {
                                        $mpcrbm_hours = floor($mpcrbm_i / 60);
                                        $mpcrbm_minutes = $mpcrbm_i % 60;
                                        if ($mpcrbm_hours == 24) {
                                            $mpcrbm_hours = 0;
                                        }
                                        $mpcrbm_data_value = $mpcrbm_hours + ($mpcrbm_minutes / 100);
                                        $mpcrbm_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes);
                                        ?>
                                        <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($mpcrbm_time_formatted, 'time')); ?></li>
                                    <?php } ?>

                                </ul>

                            </div>

                        </div>
                        <?php if( $mpcrbm_form_style === 'horizontal' ){?>
                            <div class="mpcrbm_horizontal_section_label"><?php esc_attr_e( 'Return', 'car-rental-manager');?></div>
                        <?php }?>
                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $mpcrbm_width_class );?>" >
                            <div class="input_select" >
                                <label class="fdColumn1">
                                    <input type="hidden" id="mpcrbm_map_return_date" value="<?php echo esc_attr( $mpcrbm_end_date );?>" />
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-calendar"></i>
                                        <span class="mprcbm_text"><?php esc_html($mpcrbm_type_text_return.' Date'); ?></span>
                                    </span>
                                    <input type="text" id="mpcrbm_return_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_formatted_end_date );?>" readonly name="return_date"/>
                                    <!--						<span class="far fa-calendar-alt mpcrbm_left_icon allCenter"></span>-->
                                </label>
                            </div>
                            <div class="mpcrbm-vertical-divider" style="display: <?php echo esc_attr( $mpcrbm_input_time );?>"></div>
                            <div class=" input_select" style="display: <?php echo esc_attr( $mpcrbm_input_time );?>">
                                <input type="hidden" id="mpcrbm_map_return_time" value="<?php echo esc_attr( $mpcrbm_end_time );?>" />
                                <label class="fdColumn1">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-clock"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Time', 'car-rental-manager'); ?></span>
                                    </span>
                                    <?php if( $mpcrbm_time_format_display == 12 ){?>
                                        <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_formatted_end_time );?>" readonly name="return_time" />
                                    <?php }else{?>
                                        <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $mpcrbm_end_time );?>" readonly name="return_time" />
                                    <?php }?>

                                    <!--						<span class="far fa-clock mpcrbm_left_icon allCenter"></span>-->
                                </label>
                                <ul class="return_time_list-no-dsiplay" style="display:none">
                                    <?php

                                    for ($mpcrbm_i = $mpcrbm_min_minutes; $mpcrbm_i <= $mpcrbm_max_minutes; $mpcrbm_i += $mpcrbm_interval_time) {

                                        // Calculate hours and minutes
                                        $mpcrbm_hours = floor($mpcrbm_i / 60);
                                        $mpcrbm_minutes = $mpcrbm_i % 60;

                                        // Handle 24-hour case - convert 24 to 0 for midnight
                                        if ($mpcrbm_hours == 24) {
                                            $mpcrbm_hours = 0;
                                        }

                                        // Generate the data-value as hours + fraction (minutes / 100)
                                        $mpcrbm_data_value = $mpcrbm_hours + ($mpcrbm_minutes / 100);

                                        // Format the time for display
                                        $mpcrbm_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes);
                                        if( $mpcrbm_time_format_display == 12 ){ ?>
                                            <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($mpcrbm_time_formatted, 'time')); ?></li>
                                        <?php }else{?>
                                            <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html( $mpcrbm_data_value ); ?></li>
                                    <?php }
                                    } ?>
                                </ul>
                                <ul class="input_select_list return_time_list">
                                    <?php
                                    for ($mpcrbm_i = $mpcrbm_min_minutes; $mpcrbm_i <= $mpcrbm_max_minutes; $mpcrbm_i += $mpcrbm_interval_time) {

                                        // Calculate hours and minutes
                                        $mpcrbm_hours = floor($mpcrbm_i / 60);
                                        $mpcrbm_minutes = $mpcrbm_i % 60;

                                        // Handle 24-hour case - convert 24 to 0 for midnight
                                        if ($mpcrbm_hours == 24) {
                                            $mpcrbm_hours = 0;
                                        }

                                        // Generate the data-value as hours + fraction (minutes / 100)
                                        $mpcrbm_data_value = $mpcrbm_hours + ($mpcrbm_minutes / 100);

                                        // Format the time for display
                                        $mpcrbm_time_formatted = sprintf('%02d:%02d', $mpcrbm_hours, $mpcrbm_minutes);
                                        ?>
                                        <li data-value="<?php echo esc_attr($mpcrbm_data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($mpcrbm_time_formatted, 'time')); ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <?php
                    if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_find_location_page')) {
                        ?>
                        <a href="<?php echo esc_url( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mpcrbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
                        <?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
                        <?php
                    }
                    ?>

                    <?php
                    if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_find_location_page')) {
                        ?>
                        <a href="<?php echo esc_url( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mpcrbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
                        <?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
                        <?php
                    }
                    ?>
                </div>

                <div class="mprcbm_checkbox_search_btn_holder">
                    <div class="mprcbm_checkbox_group_new">
                        <input type="checkbox" name="mpcrbm_is_drop_off" id="mpcrbm_is_drop_off" class="mpcrbm_my-checkbox mpcrbm_is_drop_off" checked="">
                        <span for="is-drop-off" class="mpcrbm-my-checkbox-label drop-off"><?php esc_html_e( 'Return car in same location', 'car-rental-manager'); ?></span>
                    </div>

                    <?php if( $mpcrbm_single_page !== 'yes' ){?>
                    <div class="mprcbm_search_button_holder ">
                        <button type="button" class="mpcrbm_search-button" id="mpcrbm_get_vehicle">
                            <i class="mi mi-search"></i>
                            <?php esc_html_e('Search', 'car-rental-manager'); ?>
                        </button>
                    </div>
                    <?php }?>
                </div>


            </div>

        </div>
    </div>
    <div class="_fullWidth get_details_next_link">
        <!--		<div class="divider"></div>-->
        <div class="justifyBetween">
            <button type="button" class="mpBtn nextTab_prev">
                <span>&larr; &nbsp;<?php esc_html_e('Previous', 'car-rental-manager'); ?></span>
            </button>
            <div></div>
            <button type="button" class="_themeButton_min_200 nextTab_next">
                <span><?php esc_html_e('Next', 'car-rental-manager'); ?>&nbsp; &rarr;</span>
            </button>
        </div>
    </div>
    <?php do_action('mpcrbm_load_date_picker_js', '#mpcrbm_start_date', $mpcrbm_all_dates); ?>
    <?php do_action('mpcrbm_load_date_picker_js', '#mpcrbm_return_date', $mpcrbm_all_dates); ?>
    <?php } else { ?>
    <div class="dLayout">
        <h3 class="_textDanger_textCenter">
            <?php
            $mpcrbm_transportaion_label = MPCRBM_Function::get_name();

            // Translators comment to explain the placeholder
            /* translators: %s: Car label */
            $mpcrbm_translated_string = __("No %s configured for this price setting", 'car-rental-manager');

            $mpcrbm_formatted_string = sprintf($mpcrbm_translated_string, $mpcrbm_transportaion_label);
            echo esc_html($mpcrbm_formatted_string);
            ?>
        </h3>
    </div>
    <?php
}
if( 1 ){
?>
<div class="mpcrbm_search_result_holder">
    <?php
        include MPCRBM_Function::template_path("registration/get_search_result.php");
    ?>
</div>
<?php }?>
