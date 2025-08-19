<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */

// Verify nonce
if (
    !isset($_POST['mpcrbm_transportation_type_nonce']) || 
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpcrbm_transportation_type_nonce'])), 'mpcrbm_transportation_type_nonce')
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
if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_filter_via_features') == 'yes') {
    $max_passenger = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_maximum_passenger');
    $max_bag = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_maximum_bag');
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
$all_dates = MPCRBM_Function::get_date($post_id);
if (empty($all_dates) || !in_array($start_date, $all_dates, true)) {
    return;
}

// View settings
$mpcrbm_enable_view_search_result_page = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');
$hidden_class = $mpcrbm_enable_view_search_result_page == '' ? '' : '';

// Sanitize location data
$label = $label ?? MPCRBM_Function::get_name();
$start_place = $start_place ?? isset($_POST['start_place']) ? sanitize_text_field(wp_unslash($_POST['start_place'])) : '';
$end_place = $end_place ?? isset($_POST['end_place']) ? sanitize_text_field(wp_unslash($_POST['end_place'])) : '';
$two_way = $two_way ?? 1;

if ($post_id) {
    // Get vehicle data
    $thumbnail = MPCRBM_Global_Function::get_image_url($post_id);
    $price = MPCRBM_Function::get_price($post_id, $start_place, $end_place, $start_date_time, $return_date_time );
    
    if (!$price || $price <= 0) {
        return;
    }else{
        $price = MPCRBM_Function::mpcrbm_calculate_price( $post_id, $start_date_time, $minutes_to_day, $price );
    }
    
    $wc_price = MPCRBM_Global_Function::wc_price($post_id, $price);
    $raw_price = MPCRBM_Global_Function::price_convert_raw($wc_price);

    $display_features = MPCRBM_Global_Function::get_post_info($post_id, 'display_mpcrbm_features', 'on');
    $all_features = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_features');

    /*$startDate  = new DateTime( $start_date_time );
    $returnDate = new DateTime( $return_date_time );
    $interval = $startDate->diff( $returnDate );
    $minutes        = ( $interval->days * 24 * 60 ) + ( $interval->h * 60 ) + $interval->i;
    $minutes_to_day = ceil( $minutes / 1440 );*/


    $price_per_day = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price', 0 );

    $total_base_price = $minutes_to_day * $price_per_day;
    $total_save = $total_base_price - $raw_price ;

    $enable_seasonal    = (int)get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );

    ?>
    <div class="mpcrbm_booking_vehicle mpcrbm_booking_item <?php echo esc_attr('mpcrbm_booking_item_' . $post_id); ?> <?php echo esc_attr($hidden_class); ?> <?php echo esc_attr($feature_class); ?>" data-placeholder>
        <div class="_max_180">
            <div class="bg_image_area" data-placeholder>
                <div data-bg-image="<?php echo esc_attr($thumbnail); ?>"></div>
            </div>
        </div>
        <div class="fdColumn _fullWidth mpcrbm_list_details">
            <h5><?php echo esc_html(get_the_title($post_id)); ?></h5>
            <div class="justifyBetween _alignStart_mT_xs mpcrbm_list">
                <?php if ($display_features === 'on' && is_array($all_features) && !empty($all_features)) { ?>
                    <div class="mpcrbm_car_specs_lists">
                        <?php
                        $i = 1;

                        $count_total_features = count( $all_features );
                        $remaining_features = $count_total_features - 6;

                        foreach ($all_features as $key => $features) {

                            if (!is_array($features)) {
                                continue;
                            }
                            $label = isset($features['label']) ? sanitize_text_field($features['label']) : '';
                            $text = isset($features['text']) ? sanitize_text_field($features['text']) : '';
                            $icon = isset($features['icon']) ? sanitize_text_field($features['icon']) : '';
                            $image = isset($features['image']) ? sanitize_text_field($features['image']) : '';
                            ?>
                            <div class="mpcrbm_car_spec">
                                <?php if ($icon) {
                                    if( $i > 4 ){
                                        $icon_class = 'mpcrbm_feature_icon_color';
                                    }else{
                                        $icon_class = 'mpcrbm_feature_icon_color_'.$i;
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr($icon); ?> <?php echo esc_attr( $icon_class);?>"></span>
                                <?php }  echo esc_html($text); ?>
                            </div>
                        <?php

                            if( $i === 6 ){
                                break;
                            }
                            $i++;
                        }

                        if( $remaining_features > 0 ){ ?>
                            <div class="mpcrbm_car_spec">
                                <span class="">+<?php echo esc_attr( $remaining_features );?> more</span>
                            </div>
                        <?php }?>

                    </div>
                <?php
                } else { ?>
                    <div></div>
                <?php } ?>
                <div class="mpcrbm_discount_info <?php echo esc_attr(( $enable_seasonal === 1 ) ? 'mpcrbm-discount-seasonal':''); ?>">
                    <div class="mpcrbm_price-breakdown"><?php echo wp_kses_post( wc_price($price_per_day ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                    <?php
                    if( $enable_seasonal === 1 ){
                        $seasonal_data = MPCRBM_Function::get_seasonal_rate( $post_id, $price_per_day, $start_date, $enable_seasonal );
                        $seasonal_price_per_day = $seasonal_data['seasonal_price_per_day'];
                        if( isset( $seasonal_data['name'] ) && !empty( $seasonal_data['name'] ) ){
                        ?>
                        <div class="mpcrbm_price-main"><?php echo wp_kses_post( wc_price($seasonal_price_per_day ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                        <div class="mpcrbm_seasonal-info"><?php echo esc_attr( $seasonal_data['name'] )?> rate</div>
                    <?php }
                    }?>
                </div>
                <div class="_min_150_mL_xs mpcrbm_booking_items">
                    <h4 class="mpcrbm_mpcrbm_textRight"><?php echo wp_kses_post( wc_price( $raw_price) ); ?></h4>
                    <div class="mpcrbm_price-total"><?php echo esc_attr($minutes_to_day); ?>-day total</div>

                    <?php if( $total_save > 0 ){?>
                        <div class="mpcrbm_discount-info">Save <?php echo wp_kses_post( wc_price( $total_save ) );?></div>
                    <?php }?>
                    <button type="button" 
                        class="_mpBtn_xs mpcrbm_transport_select"
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
            <?php do_action('mpcrbm_booking_item_after_feature', $post_id); ?>
        </div>
    </div>
    <?php
}
?>