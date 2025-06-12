<?php
	/*
* @Author 		magePeople
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly

	// Verify nonce
	if (
		!isset($_POST['mptbm_transportation_type_nonce']) || 
		!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mptbm_transportation_type_nonce'])), 'mptbm_transportation_type_nonce')
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
	if (!current_user_can('read_post', $post_id)) {
		wp_send_json_error(array('message' => esc_html__('Permission denied', 'car-rental-manager')));
		wp_die();
	}

	// Get service data
	$display_extra_services = MPCRBM_Global_Function::mpcrm_get_post_info($post_id, 'display_mptbm_extra_services', 'on');
	$service_id = MPCRBM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_extra_services_id', $post_id);
	$extra_services = MPCRBM_Global_Function::mpcrm_get_post_info($service_id, 'mptbm_extra_service_infos', []);

	if ($display_extra_services == 'on' && is_array($extra_services) && sizeof($extra_services) > 0) {
	?>
		<div class="dLayout">
			<h3><?php esc_html_e('Extra Features', 'car-rental-manager'); ?></h3>
			<div class="divider"></div>
			<?php foreach ($extra_services as $service) { 
				// Validate and sanitize service data
				if (!is_array($service)) {
					continue;
				}

				$service_name = isset($service['service_name']) ? sanitize_text_field($service['service_name']) : '';
				$service_price = isset($service['service_price']) ? floatval($service['service_price']) : 0;

				// Skip if required fields are missing
				if (!$service_name || $service_price < 0) {
					continue;
				}

				$wc_price = MPCRBM_Global_Function::wc_price($post_id, $service_price);
				$service_price = MPCRBM_Global_Function::price_convert_raw($wc_price);
			?>
				<div class="justifyBetween">
					<h6><?php echo esc_html($service_name); ?></h6>
					<span class="textTheme"><?php echo wp_kses_post(wc_price($service_price)); ?></span>
				</div>
				<div class="divider"></div>
			<?php } ?>
		</div>
	<?php } ?>