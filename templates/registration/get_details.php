<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly
delete_transient('mprcm_original_price_based');


$km_or_mile = MPCRM_Global_Function::mpcrm_get_settings('mp_global_settings', 'km_or_mile', 'km');
$price_based = $price_based ?? '';
set_transient('mprcm_original_price_based', $price_based);
$all_dates = MPTBM_Function::mpcrm_get_all_dates($price_based);
$form_style = $form_style ?? 'horizontal';
$form_style_class = $form_style == 'horizontal' ? 'inputHorizontal' : 'inputInline';
$area_class = $price_based == 'manual' ? ' ' : 'justifyBetween';
$area_class = $form_style != 'horizontal' ? 'mptbm_form_details_area fdColumn' : $area_class;
$mptbm_all_transport_id = MPCRM_Global_Function::mpcrm_get_all_post_id('mptbm_rent');
$mptbm_available_for_all_time = false;
$mptbm_schedule = [];
$min_schedule_value = 0;
$max_schedule_value = 24;
$loop = 1;
foreach ($mptbm_all_transport_id as $key => $value) {
	if (MPCRM_Global_Function::mpcrm_get_post_info($value, 'mptbm_available_for_all_time') == 'on') {
		$mptbm_available_for_all_time = true;
	}
}

if ($mptbm_available_for_all_time == false) {

	foreach ($mptbm_all_transport_id as $key => $value) {
		array_push($mptbm_schedule, MPTBM_Function::mpcrm_get_schedule($value));
	}
	foreach ($mptbm_schedule as $dayArray) {
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

if (!function_exists('mpcrm_convertToMinutes')) {
	function mpcrm_convertToMinutes($schedule_value)
	{
		$hours = floor($schedule_value); // Get the hour part
		$minutes = ($schedule_value - $hours) * 100; // Convert decimal part to minutes
		return $hours * 60 + $minutes;
	}
}

$min_minutes = mpcrm_convertToMinutes($min_schedule_value);
$max_minutes = mpcrm_convertToMinutes($max_schedule_value);

$buffer_time = (int) MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'enable_buffer_time');

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
if (sizeof($all_dates) > 0) {
	$taxi_return = MPTBM_Function::mpcrm_get_general_settings('taxi_return', 'enable');
	$interval_time = MPTBM_Function::mpcrm_get_general_settings('mptbm_pickup_interval_time', '30');
	$interval_hours = $interval_time / 60;
	$waiting_time_check = MPTBM_Function::mpcrm_get_general_settings('taxi_waiting_time', 'enable');
?>
	<div class="<?php echo esc_attr($area_class); ?> ">
		<div class="_dLayout mptbm_search_area <?php echo esc_attr($form_style_class); ?> <?php echo esc_attr($price_based == 'manual' ? 'mAuto' : ''); ?>">
			<div class="mpForm">
				<?php wp_nonce_field('mptbm_transportation_type_nonce', 'mptbm_transportation_type_nonce'); ?>
				<input type="hidden" id="mptbm_km_or_mile" name="mptbm_km_or_mile" value="<?php echo esc_attr($km_or_mile); ?>" />
				<input type="hidden" name="mptbm_price_based" value="<?php echo esc_attr($price_based); ?>" />
				<input type="hidden" name="mptbm_post_id" value="" />
				<input type="hidden" id="mptbm_enable_view_search_result_page" name="mptbm_enable_view_search_result_page" value="<?php echo esc_attr( MPCRM_Global_Function::mpcrm_get_settings( 'mptbm_general_settings', 'enable_view_search_result_page' ) ); ?>" />
				<input type='hidden' id="mptbm_enable_return_in_different_date" name="mptbm_enable_return_in_different_date" value="yes" />
				<input type="hidden" id="mptbm_enable_filter_via_features" name="mptbm_enable_filter_via_features" value="<?php echo esc_attr( MPCRM_Global_Function::mpcrm_get_settings( 'mptbm_general_settings', 'enable_filter_via_features' ) ); ?>" />
				<input type="hidden" id="mptbm_buffer_end_minutes" name="mptbm_buffer_end_minutes" value="<?php echo esc_attr( $buffer_end_minutes ); ?>" />
				<input type="hidden" id="mptbm_first_calendar_date" name="mptbm_first_calendar_date" value="<?php echo esc_attr( $all_dates[0] ); ?>" />

				<div class="inputList">
					<label class="fdColumn">
						<input type="hidden" id="mptbm_map_start_date" value="" />
						<span><i class="fas fa-calendar-alt _textTheme_mR_xs"></i><?php esc_html_e('Pickup Date', 'car-rental-manager'); ?></span>
						<input type="text" id="mptbm_start_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="" readonly />
						<span class="far fa-calendar-alt mptbm_left_icon allCenter"></span>
					</label>
				</div>

				<div class="inputList mp_input_select">
					<input type="hidden" id="mptbm_map_start_time" value="" />
					<label class="fdColumn">
						<span><i class="far fa-clock _textTheme_mR_xs"></i><?php esc_html_e('Pickup Time', 'car-rental-manager'); ?></span>
						<input type="text" class="formControl" placeholder="<?php esc_html_e('Please Select Time', 'car-rental-manager'); ?>" value="" readonly />
						<span class="far fa-clock mptbm_left_icon allCenter"></span>
					</label>

					<ul class="mp_input_select_list start_time_list">
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
							<li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRM_Global_Function::date_format($time_formatted, 'time')); ?></li>
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
							<li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRM_Global_Function::date_format($time_formatted, 'time')); ?></li>
						<?php } ?>

					</ul>

				</div>
				<div class="inputList">
					<label class="fdColumn ">
						<span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Pickup Location', 'car-rental-manager'); ?></span>
						<?php if ($price_based == 'manual') {
						?>
							<?php $all_start_locations = MPTBM_Function::mpcrm_get_all_start_location(); ?>
							<select id="mptbm_manual_start_place" class="mptbm_manual_start_place formControl">
								<option selected disabled><?php esc_html_e(' Select Pick-Up Location', 'car-rental-manager'); ?></option>
								<?php if (sizeof($all_start_locations) > 0) { ?>
									<?php foreach ($all_start_locations as $start_location) { ?>
										<option value="<?php echo esc_attr($start_location); ?>"><?php echo esc_html(MPTBM_Function::mpcrm_get_taxonomy_name_by_slug($start_location, 'mpcrm_locations')); ?></option>
									<?php } ?>
								<?php } ?>
							</select>
						<?php } else { ?>
							<input type="text" id="mptbm_map_start_place" class="formControl" placeholder="<?php esc_html_e('Enter Pick-Up Location', 'car-rental-manager'); ?>" value="" />
						<?php } ?>
					</label>
				</div>
				<?php
				if (MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'enable_view_find_location_page')) {
				?>
					<a href="<?php echo esc_url( MPCRM_Global_Function::mpcrm_get_settings( 'mptbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mptbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
					<?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
				<?php
				}
				?>
				<div class="inputList">
					<label class="fdColumn mptbm_manual_end_place">
						<span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Return Location', 'car-rental-manager'); ?></span>
						<?php if ($price_based == 'manual') { ?>
							<select id="mptbm_manual_end_place" class="mptbm_map_end_place formControl">
								<option selected disabled><?php esc_html_e(' Select Return Location', 'car-rental-manager'); ?></option>
								<?php if (sizeof($all_start_locations) > 0) { ?>
									<?php foreach ($all_start_locations as $start_location) { ?>
										<option value="<?php echo esc_attr($start_location); ?>"><?php echo esc_html(MPTBM_Function::mpcrm_get_taxonomy_name_by_slug($start_location, 'mpcrm_locations')); ?></option>
									<?php } ?>
								<?php } ?>
							</select>
						<?php } else { ?>
							<input class="formControl textCapitalize" type="text" id="mptbm_map_end_place" class="formControl" placeholder="<?php esc_html_e(' Enter Return Location', 'car-rental-manager'); ?>" value="" />
						<?php } ?>
					</label>
				</div>
				<?php
				if (MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'enable_view_find_location_page')) {
				?>
					<a href="<?php echo esc_url( MPCRM_Global_Function::mpcrm_get_settings( 'mptbm_general_settings', 'enable_view_find_location_page' ) ); ?>" class="mptbm_find_location_btn"><?php esc_html_e( 'Click here', 'car-rental-manager' ); ?></a>
					<?php esc_html_e('If you are not able to find your desired location', 'car-rental-manager'); ?>
				<?php
				}
				?>
			</div>
			<div class="mpForm">
				<div class="inputList" >
					<label class="fdColumn">
						<input type="hidden" id="mptbm_map_return_date" value="" />
						<span><i class="fas fa-calendar-alt _textTheme_mR_xs"></i><?php esc_html_e('Return Date', 'car-rental-manager'); ?></span>
						<input type="text" id="mptbm_return_date" class="formControl" placeholder="<?php esc_attr_e('Select Date', 'car-rental-manager'); ?>" value="" readonly name="return_date"/>
						<span class="far fa-calendar-alt mptbm_left_icon allCenter"></span>
					</label>
				</div>
				<div class="inputList mp_input_select">
					<input type="hidden" id="mptbm_map_return_time" value="" />
					<label class="fdColumn">
						<span><i class="far fa-clock _textTheme_mR_xs"></i><?php esc_html_e('Return Time', 'car-rental-manager'); ?></span>
						<input type="text" class="formControl" placeholder="<?php esc_html_e('Please Select Time', 'car-rental-manager'); ?>" value="" readonly name="return_time" />
						<span class="far fa-clock mptbm_left_icon allCenter"></span>
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
							<li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRM_Global_Function::date_format($time_formatted, 'time')); ?></li>
						<?php } ?>
					</ul>
					<ul class="mp_input_select_list return_time_list">
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
							<li data-value="<?php echo esc_attr($data_value); ?>"><?php echo esc_html(MPCRM_Global_Function::date_format($time_formatted, 'time')); ?></li>
						<?php } ?>
					</ul>
				</div>

				<?php if ($form_style == 'horizontal') { ?>
					<div class="divider"></div>
				<?php } ?>
				<div class="inputList justifyBetween _fdColumn">
					<span>&nbsp;</span>
					<button type="button" class="_themeButton_fullWidth" id="mptbm_get_vehicle">
						<span class="fas fa-search-location mR_xs"></span>
						<?php esc_html_e('Search', 'car-rental-manager'); ?>
					</button>
				</div>
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
					<div class="inputList"></div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="_fullWidth get_details_next_link">
		<div class="divider"></div>
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
	<?php do_action('mpcrm_mp_load_date_picker_js', '#mptbm_start_date', $all_dates); ?>
	<?php do_action('mpcrm_mp_load_date_picker_js', '#mptbm_return_date', $all_dates); ?>
<?php } else { ?>
	<div class="dLayout">
		<h3 class="_textDanger_textCenter">
			<?php
			$transportaion_label = MPTBM_Function::mpcrm_get_name();

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
