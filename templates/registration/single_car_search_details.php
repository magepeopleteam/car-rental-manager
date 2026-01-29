<?php
/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly
delete_transient('mpcrbm_original_price_based');

$today = strtolower(date("l"));
$default_start_time = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_default_start_time', 00 );
$default_end_time = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_default_end_time', 24 );

$today_start_time = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_'.$today.'_start_time', '' );
$today_end_time = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_'.$today.'_end_time', '' );

if( $today_start_time ){
    $default_start_time = $today_start_time;
}

if( $today_end_time ){
    $default_end_time = $today_end_time;
}


$km_or_mile = MPCRBM_Global_Function::get_settings('mpcrbm_global_settings', 'km_or_mile', 'km');
$price_based = $price_based ?? '';
set_transient('mpcrbm_original_price_based', $price_based);
$all_dates = MPCRBM_Function::get_all_dates($price_based);
$form_style = $form_style ?? 'horizontal';
$form_style_class = $form_style == 'horizontal' ? 'inputHorizontal' : 'inputInline';
$area_class = $price_based == 'manual' ? ' ' : 'justifyBetween';
$area_class = $form_style != 'horizontal' ? 'mpcrbm_form_details_area fdColumn' : $area_class;
$mpcrbm_all_transport_id = MPCRBM_Global_Function::get_all_post_id('mpcrbm_rent');
$mpcrbm_available_for_all_time = false;
$mpcrbm_schedule = [];
$start_time =$default_start_time;
$end_time = $default_start_time;
$min_schedule_value = $default_start_time;
$max_schedule_value =$default_end_time;
$loop = 1;

$general_settings_data       = get_option( 'mpcrbm_general_settings' );

$title = 'Car Rental Booking';
$sub_title = 'Find and reserve your perfect vehicle';
if( isset( $general_settings_data['search_title_display'] ) &&  !empty( $general_settings_data['search_title_display']  ) ){
    $title = $general_settings_data['search_title_display'];
}
if( isset( $general_settings_data['search_subtitle_display'] ) &&  !empty( $general_settings_data['search_subtitle_display']  ) ){
    $sub_title = $general_settings_data['search_subtitle_display'];
}


foreach ($mpcrbm_all_transport_id as $key => $value) {
    if (MPCRBM_Global_Function::get_post_info($value, 'mpcrbm_available_for_all_time') == 'on') {
        $mpcrbm_available_for_all_time = true;
    }
}

if ($mpcrbm_available_for_all_time == false) {

    foreach ($mpcrbm_all_transport_id as $key => $value) {
        array_push($mpcrbm_schedule, MPCRBM_Function::get_schedule($value));
    }
    foreach ($mpcrbm_schedule as $dayArray) {
        foreach ($dayArray as $times) {
            if (is_array($times)) {
                if ($loop) {
                    $min_schedule_value = $times[0];
                    $max_schedule_value = $times[0];
                    $loop = 0;
                }
                // Loop through each element in the array
                foreach ($times as $time) {

                    // Update the global smallest and largest values
                    if ($time < $min_schedule_value) {
                        $min_schedule_value = $time;
                    }
                    if ($time > $max_schedule_value) {
                        $max_schedule_value = $time;
                    }
                }
            }
        }
    }
}
// Ensure the schedule values are numeric
$min_schedule_value = floatval($min_schedule_value);
$max_schedule_value = floatval($max_schedule_value);

if (!function_exists('mpcrbm_convertToMinutes')) {
    function mpcrbm_convertToMinutes($schedule_value)
    {
        $hours = floor($schedule_value); // Get the hour part
        $minutes = ($schedule_value - $hours) * 100; // Convert decimal part to minutes
        return $hours * 60 + $minutes;
    }
}

$min_minutes = mpcrbm_convertToMinutes($min_schedule_value);
$max_minutes = mpcrbm_convertToMinutes($max_schedule_value);

$buffer_time = (int) MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_buffer_time');

$current_time = time();
$current_hour = wp_date('H', $current_time);
$current_minute = wp_date('i', $current_time);

// Convert to total minutes since midnight local time
$current_minutes = intval($current_hour) * 60 + intval($current_minute);

$buffer_end_minutes = $current_minutes + $buffer_time;

$buffer_end_minutes = max($buffer_end_minutes, 0);
while ($buffer_end_minutes > 1440) {
    array_shift($all_dates);
    $buffer_end_minutes -= 1440;
}

if( $form_style === 'horizontal' ){
    $type_text_pickup = $type_text_return = '';
    $form_class = 'mpcrbm_horizontal_search_form';
    $width_class = 'mpcrbm_100_width';
}else{
    $type_text_pickup = 'Pickup';
    $type_text_return = 'Return';
    $form_class = 'mpcrbm_inline_search_form';
    $width_class = 'mpcrbm_width_33';
}

if( $is_title === 'no' ){
    $d_class = '_dLayout';
}else{
    $d_class = '';
}

$pickup_location = '';
$return_location = '';

$start_date = date('Y-m-d');
$formatted_start_date = date('D d M, Y', strtotime( $start_date ));
$formatted_start_time = MPCRBM_Global_Function::format_custom_time( $start_time );
$end_date = date('Y-m-d', strtotime('+1 day'));
$formatted_end_date = date('D d M, Y', strtotime( $end_date ));
$formatted_end_time = MPCRBM_Global_Function::format_custom_time( $end_time );

$single_page = isset( $params['single_page'] ) ? $params['single_page'] : '';
if( $single_page === 'yes' ){
    $pickup_location = $return_location = $params['pickup_location'];
}

$hide_time_input_field = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'hide_time_input_field_search_form', 'no' );
$input_time = 'block';
if( $hide_time_input_field === 'yes' ){
    $input_time = 'none';
    $end_time = 23;
}

if (sizeof($all_dates) > 0) {
    $taxi_return = MPCRBM_Function::get_general_settings('taxi_return', 'enable');
    $interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time', '30');
    $interval_hours = $interval_time / 60;
    $waiting_time_check = MPCRBM_Function::get_general_settings('taxi_waiting_time', 'enable');
    ?>
    <div class="<?php echo esc_attr($area_class); ?> ">
        <div class=" mpcrbm_search_area <?php echo esc_attr($form_style_class); ?> <?php echo esc_attr($price_based == 'manual' ? 'mAuto' : ''); ?>">
            <?php if( $is_title === 'yes'){?>
                <div class="booking-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"></path>
                            </svg>
                        </div>
                        <div class="header-text">
                            <h2 id="mpcrbm_title_change"><?php echo esc_attr( $title  );?></h2>
                            <p><?php echo esc_attr( $sub_title );?></p>
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
                    <input type="hidden" id="mpcrbm_km_or_mile" name="mpcrbm_km_or_mile" value="<?php echo esc_attr($km_or_mile); ?>" />
                    <input type="hidden" name="mpcrbm_price_based" value="<?php echo esc_attr($price_based); ?>" />
                    <input type="hidden" name="mpcrbm_post_id" value="" />
                    <?php if( $ajax_search === 'yes' ){?>
                        <input type="hidden" id="mpcrbm_enable_view_search_result_page" name="mpcrbm_enable_view_search_result_page" value="No" />
                        <input type="hidden" id="mpcrbm_enable_ajax_search" name="mpcrbm_enable_ajax_search" value="yes" />
                    <?php }else {?>
                        <input type="hidden" id="mpcrbm_enable_ajax_search" name="mpcrbm_enable_ajax_search" value="no" />
                        <input type="hidden" id="mpcrbm_enable_view_search_result_page" name="mpcrbm_enable_view_search_result_page" value="<?php echo MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page') ?>" />
                    <?php }?>
                    <input type='hidden' id="mpcrbm_enable_return_in_different_date" name="mpcrbm_enable_return_in_different_date" value="yes" />
                    <input type="hidden" id="mpcrbm_enable_filter_via_features" name="mpcrbm_enable_filter_via_features" value="<?php echo esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_filter_via_features' ) ); ?>" />
                    <input type="hidden" id="mpcrbm_buffer_end_minutes" name="mpcrbm_buffer_end_minutes" value="<?php echo esc_attr( $buffer_end_minutes ); ?>" />
                    <input type="hidden" id="mpcrbm_first_calendar_date" name="mpcrbm_first_calendar_date" value="<?php echo esc_attr( $all_dates[0] ); ?>" />


                    <div class="<?php echo esc_attr( $form_class );?>">
                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $width_class );?>">
                            <div class="mpcrbm_location_checkbox input_select">
                                <label class="mpcrbm_manual_end_place ">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-marker"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Pick-up', 'car-rental-manager'); ?></span>
                                    </span>
                                    <?php if ($price_based == 'manual') {
                                        ?>
                                        <?php
                                        // Get all available pickup locations (supporting multi-location)
                                        $all_start_locations = MPCRBM_Function::get_all_start_location();

                                        // If multi-location is enabled for any vehicle, get all unique locations
                                        $multi_location_locations = array();
                                        foreach ($mpcrbm_all_transport_id as $vehicle_id) {
                                            $vehicle_pickup_locations = MPCRBM_Function::get_vehicle_pickup_locations($vehicle_id);
                                            $multi_location_locations = array_merge($multi_location_locations, $vehicle_pickup_locations);
                                        }
                                        $multi_location_locations = array_unique($multi_location_locations);

                                        // Combine with existing locations
                                        $all_locations = array_unique(array_merge($all_start_locations, $multi_location_locations));
                                        ?>
                                        <select id="mpcrbm_manual_start_place" class="mpcrbm_manual_start_place formControl">
                                            <option selected disabled><?php esc_html_e('Pick-Up Location', 'car-rental-manager'); ?></option>
                                            <?php if (sizeof($all_locations) > 0) { ?>
                                                <?php foreach ($all_locations as $start_location) {
                                                    $start_selected = ( $pickup_location === $start_location) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo esc_attr($start_location); ?>" <?php echo $start_selected; ?>>
                                                        <?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_location, 'mpcrbm_locations')); ?>
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
                                    <?php if ($price_based == 'manual') { ?>
                                        <select id="mpcrbm_manual_end_place" class="mpcrbm_map_end_place formControl">
                                            <option selected disabled><?php esc_html_e('Return Location', 'car-rental-manager'); ?></option>
                                            <?php if (sizeof($all_locations) > 0) {
                                                ?>
                                                <?php foreach ( $all_locations as $start_location ) {
                                                    $end_selected = ( $return_location === $start_location) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo esc_attr($start_location); ?>" <?php echo $end_selected; ?>>
                                                        <?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_location, 'mpcrbm_locations')); ?>
                                                    </option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <input class="formControl textCapitalize" type="text" id="mpcrbm_map_end_place" class="formControl" placeholder="<?php esc_html_e(' Enter Return Location', 'car-rental-manager'); ?>" value="" />
                                    <?php } ?>
                                </label>
                            </div>
                        </div>

                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $width_class );?>">
                            <div class="input_select">
                                <label class="fdColumn1">
                                    <input type="hidden" id="mpcrbm_map_start_date" value="<?php echo esc_attr( $start_date );?>" />
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-calendar"></i>
                                        <span class="mprcbm_text"><?php esc_html_e($type_text_pickup.' Date', 'car-rental-manager'); ?></span>
                                    </span>
                                    <input type="text" id="mpcrbm_start_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $formatted_start_date );?>" readonly />
                                </label>
                            </div>

                            <div class="mpcrbm-vertical-divider" style="display: <?php echo esc_attr( $input_time );?>"></div>

                            <div class=" input_select" style="display: <?php echo esc_attr( $input_time );?>">
                                <input type="hidden" id="mpcrbm_map_start_time" value="<?php echo esc_attr( $start_time );?>" />
                                <label class="fdColumn1">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-clock-three"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Time', 'car-rental-manager'); ?></span>
                                    </span>
                                    <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $formatted_start_time );?>" readonly />
                                </label>

                                <ul class="input_select_list start_time_list">
                                    <?php
                                    for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {
                                        $hours = floor($i / 60);
                                        $minutes = $i % 60;
                                        if ($hours == 24) {
                                            $hours = 0;
                                        }
                                        $data_value = $hours + ($minutes / 100);
                                        $time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                                        ?>
                                        <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                    <?php } ?>

                                </ul>
                                <ul class="start_time_list-no-dsiplay" style="display:none">
                                    <?php

                                    for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {
                                        $hours = floor($i / 60);
                                        $minutes = $i % 60;
                                        if ($hours == 24) {
                                            $hours = 0;
                                        }
                                        $data_value = $hours + ($minutes / 100);
                                        $time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                                        ?>
                                        <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                    <?php } ?>

                                </ul>

                            </div>

                        </div>
                        <?php if( $form_style === 'horizontal' ){?>
                            <div class="mpcrbm_horizontal_section_label"><?php esc_attr_e( 'Return', 'car-rental-manager');?></div>
                        <?php }?>
                        <div class="mpcrbm_horizontal_date_time_input <?php echo esc_attr( $width_class );?>" >
                            <div class="input_select" >
                                <label class="fdColumn1">
                                    <input type="hidden" id="mpcrbm_map_return_date" value="<?php echo esc_attr( $end_date );?>" />
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-calendar"></i>
                                        <span class="mprcbm_text"><?php esc_html_e($type_text_return.' Date', 'car-rental-manager'); ?></span>
                                    </span>
                                    <input type="text" id="mpcrbm_return_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $formatted_end_date );?>" readonly name="return_date"/>
                                    <!--						<span class="far fa-calendar-alt mpcrbm_left_icon allCenter"></span>-->
                                </label>
                            </div>
                            <div class="mpcrbm-vertical-divider" style="display: <?php echo esc_attr( $input_time );?>"></div>
                            <div class=" input_select" style="display: <?php echo esc_attr( $input_time );?>">
                                <input type="hidden" id="mpcrbm_map_return_time" value="<?php echo esc_attr( $end_time );?>" />
                                <label class="fdColumn1">
                                    <span class="mpcrbm_search_title">
                                        <i class="mi mi-clock"></i>
                                        <span class="mprcbm_text"><?php esc_html_e('Time', 'car-rental-manager'); ?></span>
                                    </span>
                                    <input type="text" class="formControl" placeholder="<?php esc_html_e('Select Time', 'car-rental-manager'); ?>" value="<?php echo esc_attr( $formatted_end_time );?>" readonly name="return_time" />
                                    <!--						<span class="far fa-clock mpcrbm_left_icon allCenter"></span>-->
                                </label>
                                <ul class="return_time_list-no-dsiplay" style="display:none">
                                    <?php

                                    for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {

                                        // Calculate hours and minutes
                                        $hours = floor($i / 60);
                                        $minutes = $i % 60;

                                        // Handle 24-hour case - convert 24 to 0 for midnight
                                        if ($hours == 24) {
                                            $hours = 0;
                                        }

                                        // Generate the data-value as hours + fraction (minutes / 100)
                                        $data_value = $hours + ($minutes / 100);

                                        // Format the time for display
                                        $time_formatted = sprintf('%02d:%02d', $hours, $minutes);

                                        ?>
                                        <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                    <?php } ?>
                                </ul>
                                <ul class="input_select_list return_time_list">
                                    <?php
                                    for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {

                                        // Calculate hours and minutes
                                        $hours = floor($i / 60);
                                        $minutes = $i % 60;

                                        // Handle 24-hour case - convert 24 to 0 for midnight
                                        if ($hours == 24) {
                                            $hours = 0;
                                        }

                                        // Generate the data-value as hours + fraction (minutes / 100)
                                        $data_value = $hours + ($minutes / 100);

                                        // Format the time for display
                                        $time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                                        ?>
                                        <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
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

                    <?php if( $single_page !== 'yes' ){?>
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
    <?php do_action('mpcrbm_load_date_picker_js', '#mpcrbm_start_date', $all_dates); ?>
    <?php do_action('mpcrbm_load_date_picker_js', '#mpcrbm_return_date', $all_dates); ?>
<?php } else { ?>
    <div class="dLayout">
        <h3 class="_textDanger_textCenter">
            <?php
            $transportaion_label = MPCRBM_Function::get_name();

            // Translators comment to explain the placeholder
            /* translators: %s: Car label */
            $translated_string = __("No %s configured for this price setting", 'car-rental-manager');

            $formatted_string = sprintf($translated_string, $transportaion_label);
            echo esc_html($formatted_string);
            ?>
        </h3>
    </div>
    <?php
}
