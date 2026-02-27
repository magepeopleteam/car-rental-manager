<?php
	/*
 * @Author 		MagePeople Team
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

	// Validate and sanitize inputs
	$mpcrbm_start_place = isset($_POST['start_place']) ? sanitize_text_field(wp_unslash($_POST['start_place'])) : '';
	$mpcrbm_price_based = isset($_POST['price_based']) ? sanitize_text_field(wp_unslash($_POST['price_based'])) : '';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

	// Verify post exists and user has permission
	if (!$post_id || !get_post($post_id) || !current_user_can('read_post', $post_id)) {
		wp_send_json_error(array('message' => esc_html__('Invalid request', 'car-rental-manager')));
		wp_die();
	}

	$mpcrbm_end_locations = MPCRBM_Function::get_end_location($mpcrbm_start_place, $post_id);
	if (!empty($mpcrbm_end_locations)) {
		?>
		<span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Return Location', 'car-rental-manager'); ?></span>
		<select class="formControl mpcrbm_map_end_place" id="mpcrbm_manual_end_place">
			<option selected disabled><?php esc_html_e('Select Return Location', 'car-rental-manager'); ?></option>
			<?php foreach ($mpcrbm_end_locations as $mpcrbm_location) { ?>
				<option value="<?php echo esc_attr($mpcrbm_location); ?>">
					<?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($mpcrbm_location, 'mpcrbm_locations')); ?>
				</option>
			<?php } ?>
		</select>
	<?php } else { ?>
		<span class="fas fa-map-marker-alt">
			<?php esc_html_e('Cannot find any Return Location', 'car-rental-manager'); ?>
		</span>
	<?php }