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
?>
<div class="justifyBetween">
    <div></div>
    <button class="_successButton_min_200 mpcrbm_book_now" style="display:none;" type="button" data-wc_link_id="<?php echo esc_attr($mpcrbm_link_wc_product); ?>">
        <span class="fas fa-cart-plus _mR_xs"></span>
        <?php esc_html_e('Book Now', 'car-rental-manager'); ?>
    </button>
</div>
