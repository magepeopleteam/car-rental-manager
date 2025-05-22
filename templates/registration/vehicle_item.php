<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */

// Verify nonce
if (
    !isset($_POST['mptbm_transportation_type_nonce']) || 
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mptbm_transportation_type_nonce'])), 'mptbm_transportation_type_nonce')
) {
    wp_send_json_error(array('message' => esc_html__('Security check failed', 'car-rental-manager')));
    wp_die();
}

// Initialize variables with defaults
$date = $date ?? '';
$start_date_time = $date;
$return_date_time = $return_date_time ?? '';
$post_id = $post_id ?? '';
$original_price_based = $price_based ?? '';

// Validate post_id
if (!$post_id || !get_post($post_id)) {
    wp_send_json_error(array('message' => esc_html__('Invalid vehicle', 'car-rental-manager')));
    wp_die();
}

// Feature class handling
$feature_class = '';
if (MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'enable_filter_via_features') == 'yes') {
    $max_passenger = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_maximum_passenger');
    $max_bag = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_maximum_bag');
    if (!empty($max_passenger) && !empty($max_bag)) {
        $feature_class = sprintf(
            'feature_passenger_%d_feature_bag_%d_post_id_%d',
            absint($max_passenger),
            absint($max_bag),
            absint($post_id)
        );
    }
}

// Sanitize and validate inputs
$fixed_time = $fixed_time ?? 0;
$start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
$start_date = $start_date ? gmdate('Y-m-d', strtotime($start_date)) : '';

// Validate dates
$all_dates = MPTBM_Function::mpcrm_get_date($post_id);
if (empty($all_dates) || !in_array($start_date, $all_dates, true)) {
    return;
}

// View settings
$mptbm_enable_view_search_result_page = MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'enable_view_search_result_page');
$hidden_class = $mptbm_enable_view_search_result_page == '' ? '' : '';

// Sanitize location data
$label = $label ?? MPTBM_Function::mpcrm_get_name();
$start_place = $start_place ?? isset($_POST['start_place']) ? sanitize_text_field(wp_unslash($_POST['start_place'])) : '';
$end_place = $end_place ?? isset($_POST['end_place']) ? sanitize_text_field(wp_unslash($_POST['end_place'])) : '';
$two_way = $two_way ?? 1;

if ($post_id) {
    // Get vehicle data
    $thumbnail = MPCRM_Global_Function::mpcrm_get_image_url($post_id);
    $price = MPTBM_Function::mpcrm_get_price($post_id, $start_place, $end_place, $start_date_time, $return_date_time);
    
    if (!$price || $price <= 0) {
        return;
    }
    
    $wc_price = MPCRM_Global_Function::wc_price($post_id, $price);
    $raw_price = MPCRM_Global_Function::price_convert_raw($wc_price);
    $display_features = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'display_mptbm_features', 'on');
    $all_features = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_features');
    ?>
    <div class="_dLayout_dFlex mptbm_booking_item <?php echo esc_attr('mptbm_booking_item_' . $post_id); ?> <?php echo esc_attr($hidden_class); ?> <?php echo esc_attr($feature_class); ?>" data-placeholder>
        <div class="_max_200_mR">
            <div class="bg_image_area" data-placeholder>
                <div data-bg-image="<?php echo esc_attr($thumbnail); ?>"></div>
            </div>
        </div>
        <div class="fdColumn _fullWidth mptbm_list_details">
            <h5><?php echo esc_html(get_the_title($post_id)); ?></h5>
            <div class="justifyBetween _mT_xs">
                <?php if ($display_features === 'on' && is_array($all_features) && !empty($all_features)) { ?>
                    <ul class="list_inline_two">
                        <?php
                        foreach ($all_features as $features) {
                            if (!is_array($features)) {
                                continue;
                            }
                            $label = isset($features['label']) ? sanitize_text_field($features['label']) : '';
                            $text = isset($features['text']) ? sanitize_text_field($features['text']) : '';
                            $icon = isset($features['icon']) ? sanitize_text_field($features['icon']) : '';
                            $image = isset($features['image']) ? sanitize_text_field($features['image']) : '';
                            ?>
                            <li>
                                <?php if ($icon) { ?>
                                    <span class="<?php echo esc_attr($icon); ?> _mR_xs"></span>
                                <?php } ?>
                                <?php echo esc_html($label); ?>&nbsp;:&nbsp;<?php echo esc_html($text); ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <div></div>
                <?php } ?>
                <div class="_min_150_mL_xs">
                    <h4 class="textCenter"><?php echo wp_kses_post(wc_price($raw_price)); ?></h4>
                    <button type="button" 
                        class="_mpBtn_xs_w_150 mptbm_transport_select" 
                        data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                        data-transport-price="<?php echo esc_attr($raw_price); ?>" 
                        data-post-id="<?php echo esc_attr($post_id); ?>" 
                        data-open-text="<?php esc_attr_e('Select Car', 'car-rental-manager'); ?>" 
                        data-close-text="<?php esc_html_e('Selected', 'car-rental-manager'); ?>" 
                        data-open-icon="" 
                        data-close-icon="fas fa-check mR_xs">
                        <span class="" data-icon></span>
                        <span data-text><?php esc_html_e('Select Car', 'car-rental-manager'); ?></span>
                    </button>
                </div>
            </div>
            <?php do_action('mptbm_booking_item_after_feature', $post_id); ?>
        </div>
    </div>
    <?php
}
?>