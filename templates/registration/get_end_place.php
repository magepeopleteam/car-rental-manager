<?php
	/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly
    if (!isset($_POST['mptbm_transportation_type_nonce'])) {
		return;
	}
	
	// Unslash and verify the nonce
	$nonce = wp_unslash($_POST['mptbm_transportation_type_nonce']);
	if (!wp_verify_nonce($nonce, 'mptbm_transportation_type_nonce')) {
		return;
	}
	$start_place = isset($_POST['start_place']) ? sanitize_text_field(wp_unslash($_POST['start_place'])) : '';
    $price_based = isset($_POST['price_based']) ? sanitize_text_field(wp_unslash($_POST['price_based'])) : '';
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    
    $end_locations = MPTBM_Function::get_end_location($start_place, $post_id);
    if (sizeof($end_locations) > 0) {
        ?>
	    <span><i class="fas fa-map-marker-alt _textTheme_mR_xs"></i><?php esc_html_e('Return Location', 'car-rental-manager'); ?></span>
        <select class="formControl mptbm_map_end_place" id="mptbm_manual_end_place">
            <option selected disabled><?php esc_html_e(' Select Return Location', 'car-rental-manager'); ?></option>
            <?php foreach ($end_locations as $location) { ?>
                <option value="<?php echo esc_attr($location); ?>"><?php echo esc_html(MPTBM_Function::get_taxonomy_name_by_slug( $location,'locations' )); ?></option>
            <?php } ?>
        </select>
    <?php } else { ?>
        <span class="fas fa-map-marker-alt"><?php esc_html_e(' Can not find any Return Location', 'car-rental-manager'); ?></span><?php
    }