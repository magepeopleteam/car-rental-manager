<?php
/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.

if (!class_exists('MPTBM_Date_Settings')) {
	class MPTBM_Date_Settings
	{
		public function __construct()
		{
			add_action('mpcrm_settings_tab_content', [$this, 'date_settings']);
			add_action('mpcrm_settings_tab_content', [$this, 'date_settings']);
			add_action('save_post', [$this, 'save_date_time_settings']);
			add_action('mptbm_settings_sec_fields', array($this, 'settings_sec_fields'), 10, 1);
		}
		public function default_text($day)
		{
			if ($day == 'default') {
				esc_html_e('Please select', 'car-rental-manager');
			} else {
				esc_html_e('Default', 'car-rental-manager');
			}
		}
		public function time_slot($time, $stat_time = '', $end_time = '')
		{
			if ($stat_time >= 0 || $stat_time == '') {
				$time_count = $stat_time == '' ? 0 : $stat_time;
				$end_time = $end_time != '' ? $end_time : 48 * 30;

				for ($i = 30; $i <= $end_time; $i += 30) {
					// Calculate hours and minutes
					$hours = floor($i / 60);
					$minutes = $i % 60;

					// Generate the data-value as hours + fraction (minutes / 60)
					$data_value = $hours + ($minutes / 100);

					// Format the time for display
					$time_formatted = sprintf('%02d:%02d', $hours, $minutes);
?>
					<option value="<?php echo esc_attr($data_value); ?>" <?php echo esc_attr($time != '' && $time == $data_value ? 'selected' : ''); ?>><?php echo esc_html(MPCRM_Global_Function::date_format($time_formatted, 'time')); ?></option>
			<?php }
			}
		}

		public function end_time_slot($post_id, $day, $start_time)
		{
			$end_name = 'mptbm_' . $day . '_end_time';
			$default_end_time = $day == 'default' ? 24 : '';
			$end_time = MPCRM_Global_Function::mpcrm_get_post_info($post_id, $end_name, $default_end_time);
			?>
			<label>
				<select class="formControl " name="<?php echo esc_attr($end_name); ?>">
					<?php if ($start_time == '') { ?>
						<option value="" selected><?php $this->default_text($day); ?></option>
					<?php } ?>
					<?php $this->time_slot($end_time, $start_time); ?>
				</select>
			</label>
		<?php
		}
		/*************************************/
		//			public function get_mptbm_end_time_slot() {
		//				$post_id = isset($_REQUEST['post_id']) ? MPCRM_Global_Function::data_sanitize($_REQUEST['post_id']) : '';
		//				$day = isset($_REQUEST['day_name']) ? MPCRM_Global_Function::data_sanitize($_REQUEST['day_name']) : '';
		//				$start_time = isset($_REQUEST['start_time']) ? MPCRM_Global_Function::data_sanitize($_REQUEST['start_time']) : '';
		//				$this->end_time_slot($post_id, $day, $start_time);
		//				die();
		//			}
		public function time_slot_tr($post_id, $day)
		{
			$start_name = 'mptbm_' . $day . '_start_time';
			$default_start_time = $day == 'default' ? 0.5 : '';

			$start_time = MPCRM_Global_Function::mpcrm_get_post_info($post_id, $start_name, $default_start_time);

			$end_name = 'mptbm_' . $day . '_end_time';
			$default_end_time = $day == 'default' ? 24 : '';

			$end_time = MPCRM_Global_Function::mpcrm_get_post_info($post_id, $end_name, $default_end_time);

		?>
			<tr>
				<th style="text-transform: capitalize;"><?php echo esc_html($day); ?></th>
				<td class="mptbm_start_time" data-day-name="<?php echo esc_attr($day); ?>">
					<label>
						<select class="formControl" name="<?php echo esc_attr($start_name); ?>">
							<option value="" <?php echo esc_attr($start_time == '' ? 'selected' : ''); ?>>
								<?php $this->default_text($day); ?>
							</option>
							<?php $this->time_slot($start_time); ?>
						</select>
					</label>
				</td>
				<td class="textCenter">
					<strong><?php esc_html_e('To', 'car-rental-manager'); ?></strong>
				</td>
				<td class="mptbm_end_time">
					<select class="formControl" name="<?php echo esc_attr($end_name); ?>">
						<option value="" <?php echo esc_attr($end_time == '' ? 'selected' : ''); ?>>
							<?php $this->default_text($day); ?>
						</option>
						<?php $this->time_slot($end_time); ?>
					</select>

				</td>

			</tr>
		<?php
		}

		public function date_settings($post_id)
		{
			wp_nonce_field('mptbm_save_date_time_settings', 'mptbm_date_nonce');
			$date_format = MPCRM_Global_Function::date_picker_format();
			$now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
			$date_type = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_date_type', 'repeated');

		?>
			<div class="tabsItem" data-tabs="#mptbm_settings_date">
				<h2><?php esc_html_e('Date Settings', 'car-rental-manager'); ?></h2>
				<p><?php esc_html__('Here you can configure date.', 'car-rental-manager'); ?></p>
				<!-- General Date config -->
				<section class="bg-light">
					<h6><?php esc_html__('General Date Configuration', 'car-rental-manager'); ?></h6>
					<span><?php esc_html__('Here you can configure general date', 'car-rental-manager'); ?></span>
				</section>

				<section>
					<label class="label">
						<div>
							<h6><?php esc_html_e('Date Type', 'car-rental-manager'); ?><span class="textRequired">&nbsp;*</span></h6>
							<span class="desc"><?php esc_html__('Specifies the date type: "Repeated" for recurring dates, or "Particular" for a specific date', "car-rental-manager"); ?></span>
						</div>
						<select class="formControl" name="mptbm_date_type" data-collapse-target required>
							<option disabled selected><?php esc_html_e('Please select ...', 'car-rental-manager'); ?></option>
							<option value="particular" data-option-target="#mp_particular" <?php echo esc_attr($date_type == 'particular' ? 'selected' : ''); ?>><?php esc_html_e('Particular', 'car-rental-manager'); ?></option>
							<option value="repeated" data-option-target="#mp_repeated" <?php echo esc_attr($date_type == 'repeated' ? 'selected' : ''); ?>><?php esc_html_e('Repeated', 'car-rental-manager'); ?></option>
						</select>
					</label>
				</section>
				<section data-collapse="#mp_particular" class="<?php echo esc_attr($date_type == 'particular' ? 'mActive' : ''); ?>">
					<label class="label" style="align-items: start;">
						<div>
							<h6><?php esc_html_e('Particular Dates', 'car-rental-manager'); ?></h6>
							<span class="desc"><?php esc_html_e('Add Particular Dates', 'car-rental-manager'); ?></span>
						</div>

						<div class="mp_settings_area">
							<div class="mp_item_insert mp_sortable_area">
								<?php
								$particular_date_lists = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_particular_dates', array());
								if (sizeof($particular_date_lists)) {
									foreach ($particular_date_lists as $particular_date) {
										if ($particular_date) {
											$this->particular_date_item('mptbm_particular_dates[]', $particular_date);
										}
									}
								}
								?>
							</div>
							<?php MPCRM_Custom_Layout::mpcrm_new_button(esc_html__('Add New Particular date', 'car-rental-manager')); ?>
							<div class="mp_hidden_content">
								<div class="mp_hidden_item">
									<?php $this->particular_date_item('mptbm_particular_dates[]'); ?>
								</div>
							</div>
						</div>
					</label>
				</section>
				<?php
				$repeated_start_date = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_repeated_start_date');
				$hidden_repeated_start_date = $repeated_start_date ? gmdate('Y-m-d', strtotime($repeated_start_date)) : '';
				$visible_repeated_start_date = $repeated_start_date ? date_i18n($date_format, strtotime($repeated_start_date)) : '';
				$repeated_after = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_repeated_after', 1);
				$active_days = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_active_days', 60);
				$available_for_all_time = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_available_for_all_time', 'on');
				$active = $available_for_all_time == 'off' ? '' : 'mActive';
				$checked = $available_for_all_time == 'off' ? '' : 'checked';

				?>
				<section data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
					<label class="label">
						<div>
							<h6><?php esc_html_e('Repeated Start Date', 'car-rental-manager'); ?><span class="textRequired">&nbsp;*</span></h6>
							<span class="desc"><?php esc_html_e('Sets the start date for recurring services', 'car-rental-manager'); ?></span>
						</div>
						<div>
							<input type="hidden" name="mptbm_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>" required />
							<input type="text" readonly required name="" class="formControl date_type" value="<?php echo esc_attr($visible_repeated_start_date); ?>" placeholder="<?php echo esc_attr($now); ?>" />
						</div>
					</label>
				</section>

				<section data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
					<label class="label">
						<div>
							<h6><?php esc_html_e('Repeated after', 'car-rental-manager'); ?><span class="textRequired">&nbsp;*</span></h6>
							<span class="desc"><?php esc_html_e('Defines the number of days after which the service or event will repeat', 'car-rental-manager'); ?></span>
						</div>
						<input type="text" name="mptbm_repeated_after" class="formControl mp_number_validation" value="<?php echo esc_attr($repeated_after); ?>" />
					</label>
				</section>

				<section data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
					<label class="label">
						<div>
							<h6><?php esc_html_e('Maximum Advanced Day Booking', 'car-rental-manager'); ?><span class="textRequired">&nbsp;*</span></h6>
							<span class="desc"><?php esc_html_e('Sets the maximum number of days in advance a booking can be made', 'car-rental-manager'); ?></span>
						</div>
						<input type="text" name="mptbm_active_days" class="formControl mp_number_validation" value="<?php echo esc_attr($active_days); ?>" />
					</label>
				</section>

				<section>
					<label class="label">
						<div>
							<h6><?php esc_html_e('Make Transport Available For 24 Hours', 'car-rental-manager'); ?></h6>
							<span class="desc"><?php MPTBM_Settings::info_text('display_mptbm_features'); ?></span>
						</div>
						<?php MPCRM_Custom_Layout::switch_button('mptbm_available_for_all_time', $checked); ?>
					</label>
				</section>

				<section class="bg-light" style="margin-top: 20px;">
					<h6><?php esc_html__('Schedule Date Configuration', 'car-rental-manager'); ?></h6>
					<span><?php esc_html__('Here you can configure Schedule date.', 'car-rental-manager'); ?></span>
				</section>
				<section>
					<table>
						<thead>
							<tr>
								<th><?php esc_html_e('Day', 'car-rental-manager'); ?></th>
								<th><?php esc_html_e('Start Time', 'car-rental-manager'); ?></th>
								<th><?php esc_html_e('To', 'car-rental-manager'); ?></th>
								<th><?php esc_html_e('End Time', 'car-rental-manager'); ?></th>

							</tr>
						</thead>
						<tbody>
							<?php $this->time_slot_tr($post_id, 'default');
							$days = MPCRM_Global_Function::week_day();
							foreach ($days as $key => $day) {
								$this->time_slot_tr($post_id, $key);
							}
							?>
						</tbody>
					</table>
				</section>
				<!-- End Schedule date config -->

				<section class="bg-light" style="margin-top: 20px;">

					<h6><?php esc_html__('Off Days & Dates Configuration', 'car-rental-manager'); ?></h6>
					<span><?php esc_html__('Here you can configure Off Days & Dates.', 'car-rental-manager'); ?></span>

				</section>

				<section data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
					<label class="label">
						<div>
							<h6><?php esc_html_e('Off Day', 'car-rental-manager'); ?></h6>
							<span class="desc"><?php esc_html_e('Select checkbox for off day', 'car-rental-manager'); ?></span>
						</div>
						<div>
							<?php

							$off_days = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_off_days');
							$days = MPCRM_Global_Function::week_day();
							$off_day_array = explode(',', $off_days);
							?>
							<div class="groupCheckBox">
								<input type="hidden" name="mptbm_off_days" value="<?php echo esc_attr($off_days); ?>" />
								<?php foreach ($days as $key => $day) { ?>
									<label class="customCheckboxLabel">
										<input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>" />
										<span class="customCheckbox me-1"><?php echo esc_html($day); ?></span>
									</label>
								<?php } ?>
							</div>
						</div>
					</label>
				</section>

				<section>
					<label class="label" style="align-items: start;">
						<div>
							<h6><?php esc_html_e('Off Dates', 'car-rental-manager'); ?></h6>
							<span class="desc"><?php esc_html_e('Add off dates', 'car-rental-manager'); ?></span>
						</div>
						<div class="mp_settings_area">
							<div class="mp_item_insert mp_sortable_area mb-1">
								<?php
								$off_day_lists = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_off_dates', array());
								if (sizeof($off_day_lists)) {
									foreach ($off_day_lists as $off_day) {
										if ($off_day) {
											$this->particular_date_item('mptbm_off_dates[]', $off_day);
										}
									}
								}
								?>
							</div>
							<?php MPCRM_Custom_Layout::mpcrm_new_button(esc_html__('Add New Off date', 'car-rental-manager')); ?>
							<div class="mp_hidden_content">
								<div class="mp_hidden_item">
									<?php $this->particular_date_item('mptbm_off_dates[]'); ?>
								</div>
							</div>
						</div>
					</label>
				</section>

				<!-- End Off days and date config -->
			</div>
		<?php
		}
		public function particular_date_item($name, $date = '')
		{
			$date_format = MPCRM_Global_Function::date_picker_format();
			$now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
			$hidden_date = $date ? gmdate('Y-m-d', strtotime($date)) : '';
			$visible_date = $date ? date_i18n($date_format, strtotime($date)) : '';
		?>
			<div class="mp_remove_area my-1">
				<div class="justifyBetween bg-light p-1">
					<label class="col_8">
						<input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($hidden_date); ?>" />
						<input value="<?php echo esc_attr($visible_date); ?>" class="formControl date_type" placeholder="<?php echo esc_attr($now); ?>" />
					</label>
					<?php MPCRM_Custom_Layout::move_remove_button(); ?>
				</div>

			</div>
<?php
		}
		/*************************************/
		public function save_date_time_settings($post_id)
		{
			
			// Check if nonce is set
			if (!isset($_POST['mptbm_date_nonce'])) {
				return;
			}

			// Unslash and sanitize the nonce
			$nonce = isset($_POST['mptbm_date_nonce']) ? sanitize_text_field(wp_unslash($_POST['mptbm_date_nonce'])) : '';
			if (!wp_verify_nonce($nonce, 'mptbm_save_date_time_settings')) {
				return;
			}
			if (get_post_type($post_id) == MPTBM_Function::mpcrm_get_cpt()) {
				//************************************//
				$mptbm_date_type = isset($_POST['mptbm_date_type']) ? sanitize_text_field(wp_unslash($_POST['mptbm_date_type'])) : '';
				update_post_meta($post_id, 'mptbm_date_type', $mptbm_date_type);
				//**********************//



				$particular_dates = isset($_POST['mptbm_particular_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mptbm_particular_dates'])) : [];
				$particular = array();
				if (sizeof($particular_dates) > 0) {
					foreach ($particular_dates as $particular_date) {
						if ($particular_date) {
							$particular[] = gmdate('Y-m-d', strtotime($particular_date));
						}
					}
				}

				$mptbm_available_for_all_time = isset($_POST['mptbm_available_for_all_time']) && sanitize_text_field(wp_unslash($_POST['mptbm_available_for_all_time'])) ? 'on' : 'off';
				update_post_meta($post_id, 'mptbm_available_for_all_time', $mptbm_available_for_all_time);

				update_post_meta($post_id, 'mptbm_particular_dates', $particular);
				//*************************//
				$repeated_start_date =  isset($_POST['mptbm_repeated_start_date']) ? sanitize_text_field(wp_unslash($_POST['mptbm_repeated_start_date'])) : '';
				$repeated_start_date = $repeated_start_date ? gmdate('Y-m-d', strtotime($repeated_start_date)) : '';
				update_post_meta($post_id, 'mptbm_repeated_start_date', $repeated_start_date);
				$repeated_after = isset($_POST['mptbm_repeated_after']) ? sanitize_text_field(wp_unslash($_POST['mptbm_repeated_after'])) : '';
				update_post_meta($post_id, 'mptbm_repeated_after', $repeated_after);
				$active_days = isset($_POST['mptbm_active_days']) ? sanitize_text_field(wp_unslash($_POST['mptbm_active_days'])) : '';
				update_post_meta($post_id, 'mptbm_active_days', $active_days);
				//**********************//
				if (isset($_POST['mptbm_off_days'])) {
					$off_days_arr = explode(',', sanitize_text_field(wp_unslash($_POST['mptbm_off_days'])));
					$off_days = is_array($off_days_arr) ? array_map('sanitize_text_field', $off_days_arr) : [];
					$off_days = implode(',', $off_days);

					update_post_meta($post_id, 'mptbm_off_days', $off_days);
				}

				//**********************//
				$off_dates = isset($_POST['mptbm_off_dates']) && is_array($_POST['mptbm_off_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mptbm_off_dates'])) : [];
				$_off_dates = array();
				if (sizeof($off_dates) > 0) {
					foreach ($off_dates as $off_date) {
						if ($off_date) {
							$_off_dates[] = gmdate('Y-m-d', strtotime($off_date));
						}
					}
				}
				update_post_meta($post_id, 'mptbm_off_dates', $_off_dates);
				$this->save_schedule($post_id, 'default');
				$days = MPCRM_Global_Function::week_day();
				foreach ($days as $key => $day) {
					$this->save_schedule($post_id, $key);
				}
			}
		}
		public function mpcrm_get_submit_info($key, $default = '')
		{
			// Check if nonce is set
			if (!isset($_POST['mptbm_date_nonce'])) {
				return;
			}

			// Unslash and verify the nonce
			$nonce = isset($_POST['mptbm_date_nonce']) ? sanitize_text_field(wp_unslash($_POST['mptbm_date_nonce'])) : '';
			if (!wp_verify_nonce($nonce, 'mptbm_save_date_time_settings')) {
				return;
			}
			$value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default;
			return $this->data_sanitize($value);
		}
		public function data_sanitize($data)
		{
			$data = maybe_unserialize($data);
			if (is_string($data)) {
				$data = maybe_unserialize($data);
				if (is_array($data)) {
					$data = $this->data_sanitize($data);
				} else {

					$data = sanitize_text_field(stripslashes(wp_strip_all_tags($data)));
				}
			} elseif (is_array($data)) {
				foreach ($data as &$value) {
					if (is_array($value)) {
						$value = $this->data_sanitize($value);
					} else {
						$value = sanitize_text_field(stripslashes(wp_strip_all_tags($value)));
					}
				}
			}
			return $data;
		}
		public function save_schedule($post_id, $day)
		{
			$start_name = 'mptbm_' . $day . '_start_time';
			$start_time = $this->mpcrm_get_submit_info($start_name);
			update_post_meta($post_id, $start_name, $start_time);
			$end_name = 'mptbm_' . $day . '_end_time';
			$end_time = $this->mpcrm_get_submit_info($end_name);
			update_post_meta($post_id, $end_name, $end_time);
		}
		public function settings_sec_fields($default_fields): array {
			// Ensure $default_fields is an array
			$default_fields = is_array($default_fields) ? $default_fields : array();
			
			$settings_fields = array(
				'mptbm_date_settings' => array(
					array(
						'name' => 'mptbm_date_type',
						'label' => esc_html__('Date Type', 'car-rental-manager'),
						'desc' => esc_html__('Select date type (Repeated or Particular)', 'car-rental-manager'),
						'type' => 'select',
						'default' => 'repeated',
						'options' => array(
							'repeated' => esc_html__('Repeated', 'car-rental-manager'),
							'particular' => esc_html__('Particular', 'car-rental-manager')
						)
					),
					array(
						'name' => 'mptbm_repeated_start_date',
						'label' => esc_html__('Repeated Start Date', 'car-rental-manager'),
						'desc' => esc_html__('Set the start date for repeated bookings', 'car-rental-manager'),
						'type' => 'date',
						'default' => ''
					),
					array(
						'name' => 'mptbm_particular_dates',
						'label' => esc_html__('Particular Dates', 'car-rental-manager'),
						'desc' => esc_html__('Set particular dates for bookings', 'car-rental-manager'),
						'type' => 'array',
						'default' => array()
					)
				)
			);
			
			return array_merge($default_fields, $settings_fields);
		}
	}
	new MPTBM_Date_Settings();
}
