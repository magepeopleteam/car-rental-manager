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
		!isset($_POST['mpcrbm_transportation_type_nonce']) || 
		!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpcrbm_transportation_type_nonce'])), 'mpcrbm_transportation_type_nonce')
	) {
		wp_send_json_error(array('message' => esc_html__('Security check failed', 'car-rental-manager')));
		wp_die();
	}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
	$mpcrbm_display_extra_services = MPCRBM_Global_Function::get_post_info($post_id, 'display_mpcrbm_extra_services', 'on');
	$mpcrbm_service_id = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_extra_services_id', $post_id);
	$mpcrbm_extra_services = MPCRBM_Global_Function::get_post_info($mpcrbm_service_id, 'mpcrbm_extra_service_infos', []);

	if ($mpcrbm_display_extra_services == 'on' && is_array($mpcrbm_extra_services) && sizeof($mpcrbm_extra_services) > 0) {
	?>
		<div class="dLayout">
			<h3><?php esc_html_e('Extra Features', 'car-rental-manager'); ?></h3>
			<div class="divider"></div>
			<?php foreach ($mpcrbm_extra_services as $mpcrbm_service) {
				// Validate and sanitize service data
				if (!is_array($mpcrbm_service)) {
					continue;
				}

				$mpcrbm_service_name = isset($mpcrbm_service['service_name']) ? sanitize_text_field($mpcrbm_service['service_name']) : '';
				$mpcrbm_service_price = isset($mpcrbm_service['service_price']) ? floatval($mpcrbm_service['service_price']) : 0;

				// Skip if required fields are missing
				if (!$mpcrbm_service_name || $mpcrbm_service_price < 0) {
					continue;
				}

				$mpcrbm_wc_price = MPCRBM_Global_Function::wc_price($post_id, $mpcrbm_service_price);
                $mpcrbm_service_price = MPCRBM_Global_Function::price_convert_raw($mpcrbm_wc_price);
			?>
				<div class="justifyBetween">
					<h6><?php echo esc_html($mpcrbm_service_name); ?></h6>
					<span class="textTheme"><?php echo wp_kses_post(wc_price($mpcrbm_service_price)); ?></span>
				</div>
				<div class="divider"></div>
			<?php } ?>
		</div>
	<?php } ?>