<?php
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly

// Verify nonce
if (
	!isset($_POST['mpcrbm_transportation_type_nonce']) || 
	!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpcrbm_transportation_type_nonce'])), 'mpcrbm_transportation_type_nonce')
) {
	wp_send_json_error(array('message' => esc_html__('Security check failed', 'car-rental-manager')));
	wp_die();
}

// Validate and sanitize post_id
$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
if (!$post_id || !get_post($post_id)) {
	wp_send_json_error(array('message' => esc_html__('Invalid post ID', 'car-rental-manager')));
	wp_die();
}

// Verify user has permission
/*if (!current_user_can('read_post', $post_id)) {
	wp_send_json_error(array('message' => esc_html__('Permission denied', 'car-rental-manager')));
	wp_die();
}*/

// Get service data
$link_wc_product = MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product');
$display_extra_services = MPCRBM_Global_Function::get_post_info($post_id, 'display_mpcrbm_extra_services', 'on');
$service_id = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_extra_services_id', $post_id);
$extra_services = MPCRBM_Global_Function::get_post_info($service_id, 'mpcrbm_extra_service_infos', []);

if ($display_extra_services == 'on' && is_array($extra_services) && sizeof($extra_services) > 0) {
?>
		<div class="mpcrbm_extra_service_layout">
			<h3><?php esc_html_e('Choose Extra Features (Optional)', 'car-rental-manager'); ?></h3>
			<div class="divider"></div>
			<?php foreach ($extra_services as $service) { 
				// Validate and sanitize service data
				if (!is_array($service)) {
					continue;
				}

				$service_icon = isset($service['service_icon']) ? sanitize_text_field($service['service_icon']) : '';
				$service_image = isset($service['service_image']) ? absint($service['service_image']) : 0;
				$service_name = isset($service['service_name']) ? sanitize_text_field($service['service_name']) : '';
				$service_price = isset($service['service_price']) ? floatval($service['service_price']) : 0;
				$description = isset($service['extra_service_description']) ? wp_kses_post($service['extra_service_description']) : '';

				// Skip if required fields are missing
				if (!$service_name || $service_price < 0) {
					continue;
				}

				$wc_price = MPCRBM_Global_Function::wc_price($post_id, $service_price);
				$service_price = MPCRBM_Global_Function::price_convert_raw($wc_price);
				$ex_unique_id = '#ex_service_' . uniqid();
			?>
				<div class="dFlex mpcrbm_extra_service_item">
					<?php if ($service_image) { ?>
						<div class="service_img_area alignCenter">
							<div class="bg_image_area">
								<div data-bg-image="<?php echo esc_attr(MPCRBM_Global_Function::get_image_url('', $service_image, 'medium')); ?>"></div>
							</div>
						</div>
					<?php } ?>
					<div class="fdColumn _fullWidth">
						<h4>
							<?php if ($service_icon) { ?>
								<span class="<?php echo esc_attr($service_icon); ?>"></span>
							<?php } ?>
							<?php echo esc_html($service_name); ?>
							<sub class="textTheme"> &nbsp;&nbsp;<?php echo wp_kses_post(wc_price($service_price)); ?></sub>
						</h4>
						<div class="_equalChild">
							<div class="_mR_xs">
								<?php MPCRBM_Custom_Layout::load_more_text($description, 100); ?>
							</div>
							<div>
								<div class="justifyEnd">
									<div class="_mR_min_100" data-collapse="<?php echo esc_attr($ex_unique_id); ?>">
										<?php MPCRBM_Custom_Layout::qty_input('mpcrbm_extra_service_qty[]', $service_price, 100, 1, 0); ?>
									</div>
									<button type="button" class="_mpBtn_dBR_min_150 mpcrbm_price_calculation" data-extra-item data-collapse-target="<?php echo esc_attr($ex_unique_id); ?>" data-open-icon="far fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e('Select', 'car-rental-manager'); ?>" data-close-text="<?php esc_attr_e('Selected', 'car-rental-manager'); ?>" data-add-class="mActive">
										<input type="hidden" name="mpcrbm_extra_service[]" data-value="<?php echo esc_attr($service_name); ?>" value="" />
										<span data-text><?php esc_html_e('Select', 'car-rental-manager'); ?></span>
										<span data-icon class="mL_xs"></span>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="divider"></div>
			<?php } ?>
		</div>
	<?php } ?>
	<div class="divider"></div>
	<div class="justifyBetween">
		<div></div>
		<button class="_successButton_min_200 mpcrbm_book_now" style="display:none;" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>">
			<span class="fas fa-cart-plus _mR_xs"></span>
			<?php esc_html_e('Book Now', 'car-rental-manager'); ?>
		</button>
	</div>
