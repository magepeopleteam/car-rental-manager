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

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
$mpcrbm_extra_service_class = 'mpcrbm_extra_service_layout';

include( MPCRBM_Function::template_path( 'registration/extra_service_display.php' ) );
// Get service data
// Book Now itself now lives in templates/registration/summary_new.php as a
// single persistent button, always visible on the page instead of only
// appearing here once this AJAX response loads — see that file for details.
