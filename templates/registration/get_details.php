<?php
/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly
delete_transient('mpcrbm_original_price_based');


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
$min_schedule_value = 0;
$max_schedule_value = 24;
$loop = 1;
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
}else{
    $type_text_pickup = 'Pickup';
    $type_text_return = 'Return';
}

if( $is_title === 'no' ){
    $d_class = '_dLayout';
}else{
    $d_class = '';
}

if (sizeof($all_dates) > 0) {
	$taxi_return = MPCRBM_Function::get_general_settings('taxi_return', 'enable');
	$interval_time = MPCRBM_Function::get_general_settings('pickup_interval_time', '30');
	$interval_hours = $interval_time / 60;
	$waiting_time_check = MPCRBM_Function::get_general_settings('taxi_waiting_time', 'enable');
?>
	<div class="<?php echo esc_attr($area_class); ?> ">

		<div class=" <?php echo esc_attr( $d_class );?> mpcrbm_search_area <?php echo esc_attr($form_style_class); ?> <?php echo esc_attr($price_based == 'manual' ? 'mAuto' : ''); ?>">
            <?php if( $form_style === 'horizontal' ){?>
<!--            <h2 class="mpcrbm_horizontal_booking_title">--><?php //esc_attr_e( 'Car Rental', 'car-rental-manager');?><!--</h2>-->
            <?php }?>

            <?php if( $is_title === 'yes'){?>
                <div class="booking-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"></path>
                            </svg>
                        </div>
                        <div class="header-text">
                            <h2><?php esc_attr_e( 'Car Rental Booking', 'car-rental-manager' );?></h2>
                            <p><?php esc_attr_e( 'Find and reserve your perfect vehicle', 'car-rental-manager' );?></p>
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
                    <input type="hidden" id="mpcrbm_enable_view_search_result_page" name="mpcrbm_enable_view_search_result_page" value="<?php echo esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_view_search_result_page' ) ); ?>" />
                    <input type='hidden' id="mpcrbm_enable_return_in_different_date" name="mpcrbm_enable_return_in_different_date" value="yes" />
                    <input type="hidden" id="mpcrbm_enable_filter_via_features" name="mpcrbm_enable_filter_via_features" value="<?php echo esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_filter_via_features' ) ); ?>" />
                    <input type="hidden" id="mpcrbm_buffer_end_minutes" name="mpcrbm_buffer_end_minutes" value="<?php echo esc_attr( $buffer_end_minutes ); ?>" />
                    <input type="hidden" id="mpcrbm_first_calendar_date" name="mpcrbm_first_calendar_date" value="<?php echo esc_attr( $all_dates[0] ); ?>" />
                    <?php if( $form_style === 'horizontal' ){?>
                        <div class="mpcrbm_horizontal_section_label"><?php esc_attr_e( 'Pickup', 'car-rental-manager');?></div>
                    <?php } ?>


                    <?php if( $form_style === 'inline' ){?>
                    <div class="mpcrbm_pickup_drop_off_checkbox inputList">
                        <?php }?>
                        <label class="fdColumn ">
                            <span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Pick-up Location', 'car-rental-manager'); ?></span>
                            <?php if ($price_based == 'manual') {
                                ?>
                                <?php $all_start_locations = MPCRBM_Function::get_all_start_location(); ?>
                                <select id="mpcrbm_manual_start_place" class="mpcrbm_manual_start_place formControl">
                                    <option selected disabled><?php esc_html_e(' Select Pick-Up Location', 'car-rental-manager'); ?></option>
                                    <?php if (sizeof($all_start_locations) > 0) { ?>
                                        <?php foreach ($all_start_locations as $start_location) { ?>
                                            <option value="<?php echo esc_attr($start_location); ?>"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_location, 'mpcrbm_locations')); ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            <?php } else { ?>
                                <input type="text" id="mpcrbm_map_start_place" class="formControl" placeholder="<?php esc_html_e('Enter Pick-Up Location', 'car-rental-manager'); ?>" value="" />
                            <?php } ?>
                        </label>
                        <?php if( $form_style === 'inline' ){?>
                    </div>
                <?php }?>

                    <div class="inputList" id="mpcrbm_drop_off_location" style="display: none">
                        <label class="fdColumn mpcrbm_manual_end_place" >
                            <span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Drop-off Location', 'car-rental-manager'); ?></span>
                            <?php if ($price_based == 'manual') { ?>
                                <select id="mpcrbm_manual_end_place" class="mpcrbm_map_end_place formControl">
                                    <option selected disabled><?php esc_html_e(' Select Return Location', 'car-rental-manager'); ?></option>
                                    <?php if (sizeof($all_start_locations) > 0) { ?>
                                        <?php foreach ($all_start_locations as $start_location) { ?>
                                            <option value="<?php echo esc_attr($start_location); ?>"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_location, 'mpcrbm_locations')); ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            <?php } else { ?>
                                <input class="formControl textCapitalize" type="text" id="mpcrbm_map_end_place" class="formControl" placeholder="<?php esc_html_e(' Enter Return Location', 'car-rental-manager'); ?>" value="" />
                            <?php } ?>
                        </label>
                    </div>

                    <?php if( $form_style === 'horizontal' ){?>
                        <div class="mprcbm_checkbox_group">
                            <input type="checkbox" name="mpcrbm_is_drop_off" id="mpcrbm_is_drop_off" class="mpcrbm_my-checkbox mpcrbm_is_drop_off" checked="">
                            <span for="is-drop-off" class="mpcrbm-my-checkbox-label drop-off"><?php esc_html_e( 'Return car in same location', 'car-rental-manager'); ?></span>
                        </div>
                    <?php }?>

                    <?php if( $form_style === 'horizontal' ){?>
                    <div class="mpcrbm_horizontal_date_time_input">
                        <?php } ?>
                        <div class="inputList">
                            <label class="fdColumn">
                                <input type="hidden" id="mpcrbm_map_start_date" value="" />
                                <span><i class="fas fa-calendar-alt _textTheme_mR_xs"></i><?php esc_html_e($type_text_pickup.' Date', 'car-rental-manager'); ?></span>
                                <input type="text" id="mpcrbm_start_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="" readonly />
                            </label>
                        </div>

                        <div class="inputList input_select">
                            <input type="hidden" id="mpcrbm_map_start_time" value="" />
                            <label class="fdColumn">
                                <span><i class="far fa-clock _textTheme_mR_xs"></i><?php esc_html_e($type_text_pickup.' Time', 'car-rental-manager'); ?></span>
                                <input type="text" class="formControl" placeholder="<?php esc_html_e('Please Select Time', 'car-rental-manager'); ?>" value="" readonly />
                            </label>

                            <ul class="input_select_list start_time_list">
                                <?php
                                for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {


                                    // Calculate hours and minutes
                                    $hours = floor($i / 60);
                                    $minutes = $i % 60;

                                    // Generate the data-value as hours + fraction (minutes / 60)
                                    $data_value = $hours + ($minutes / 100);

                                    // Format the time for display
                                    $time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                                    ?>
                                    <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                <?php } ?>

                            </ul>
                            <ul class="start_time_list-no-dsiplay" style="display:none">
                                <?php

                                for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {

                                    // Calculate hours and minutes
                                    $hours = floor($i / 60);
                                    $minutes = $i % 60;

                                    // Generate the data-value as hours + fraction (minutes / 60)
                                    $data_value = $hours + ($minutes / 100);

                                    // Format the time for display
                                    $time_formatted = sprintf('%02d:%02d', $hours, $minutes);

                                    ?>
                                    <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                <?php } ?>

                            </ul>

                        </div>

                        <?php if( $form_style === 'horizontal' ) {?>
                    </div>
                <?php }?>

                    <?php
                    if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_find_location_page')) {
                        ?>
                        <a href="<?php echo esc_url( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mpcrbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
                        <?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
                        <?php
                    }
                    ?>

                    <?php if( $form_style === 'horizontal' ){?>
                        <div class="mpcrbm_horizontal_section_label"><?php esc_attr_e( 'Return', 'car-rental-manager');?></div>
                    <?php }?>


                    <?php
                    if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_find_location_page')) {
                        ?>
                        <a href="<?php echo esc_url( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mpcrbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
                        <?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
                        <?php
                    }
                    ?>
                </div>


                <?php if( $form_style === 'inline' ){?>
                    <div class="mprcbm_checkbox_group">
                        <input type="checkbox" name="mpcrbm_is_drop_off" id="mpcrbm_is_drop_off" class="mpcrbm_my-checkbox mpcrbm_is_drop_off" checked="">
                        <span for="is-drop-off" class="mpcrbm-my-checkbox-label drop-off"><?php esc_html_e( 'Return car in same location', 'car-rental-manager'); ?></span>
                    </div>
                <?php }?>

                <div class="mpForm second-row">

                    <?php  if( $form_style === 'horizontal' ){ ?>
                    <div class="mpcrbm_horizontal_date_time_input">
                        <?php }?>
                        <div class="inputList" >
                            <label class="fdColumn">
                                <input type="hidden" id="mpcrbm_map_return_date" value="" />
                                <span><i class="fas fa-calendar-alt _textTheme_mR_xs"></i><?php esc_html_e($type_text_return.' Date', 'car-rental-manager'); ?></span>
                                <input type="text" id="mpcrbm_return_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="" readonly name="return_date"/>
                                <!--						<span class="far fa-calendar-alt mpcrbm_left_icon allCenter"></span>-->
                            </label>
                        </div>
                        <div class="inputList input_select">
                            <input type="hidden" id="mpcrbm_map_return_time" value="" />
                            <label class="fdColumn">
                                <span><i class="far fa-clock _textTheme_mR_xs"></i><?php esc_html_e($type_text_return.' Time', 'car-rental-manager'); ?></span>
                                <input type="text" class="formControl" placeholder="<?php esc_html_e('Please Select Time', 'car-rental-manager'); ?>" value="" readonly name="return_time" />
                                <!--						<span class="far fa-clock mpcrbm_left_icon allCenter"></span>-->
                            </label>
                            <ul class="return_time_list-no-dsiplay" style="display:none">
                                <?php

                                for ($i = $min_minutes; $i <= $max_minutes; $i += $interval_time) {

                                    // Calculate hours and minutes
                                    $hours = floor($i / 60);
                                    $minutes = $i % 60;

                                    // Generate the data-value as hours + fraction (minutes / 60)
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

                                    // Generate the data-value as hours + fraction (minutes / 60)
                                    $data_value = $hours + ($minutes / 100);

                                    // Format the time for display
                                    $time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                                    ?>
                                    <li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRBM_Global_Function::date_format($time_formatted, 'time')); ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <?php  if( $form_style === 'horizontal' ){ ?>
                    </div>
                <?php }?>
                    <?php if ($form_style != 'horizontal') { ?>
                        <?php if ($taxi_return != 'enable' && $price_based != 'fixed_hourly') { ?>
                            <div class="inputList"></div>
                        <?php } ?>
                        <?php if ($waiting_time_check != 'enable' && $price_based != 'fixed_hourly') { ?>
                            <div class="inputList"></div>
                        <?php } ?>
                        <?php if ($price_based == 'fixed_hourly') { ?>
                            <div class="inputList"></div>
                        <?php } ?>
                        <!-- <div class="inputList"></div> -->
                    <?php } ?>
                </div>

                <div class="mprcbm_search_button_holder inputList ">
<!--                    <span>&nbsp;</span>-->
                   <!-- <button type="button" class="_themeButton_fullWidth" id="mpcrbm_get_vehicle">
                        <span class="fas fa-search-location mR_xs"></span>
                        <?php /*esc_html_e('Search', 'car-rental-manager'); */?>
                    </button>-->

                    <button type="button" class="mpcrbm_search-button" id="mpcrbm_get_vehicle">
                        <svg class="mprcbm_search_icon " viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                        </svg>
                        <?php esc_html_e('Search', 'car-rental-manager'); ?>
                    </button>
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
