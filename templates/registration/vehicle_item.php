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

    $days = MPCRBM_Function::get_days_from_start_end_date( $start_date_time, $return_date_time );
    // Use multi-location pricing if enabled, otherwise use default pricing
    $price = MPCRBM_Function::calculate_multi_location_price($post_id, $start_place, $end_place, $start_date_time, $return_date_time);

    if (!$price || $price <= 0) {
        return;
    }
    
    $wc_price = MPCRBM_Global_Function::wc_price( $post_id, $price );
    $raw_price = MPCRBM_Global_Function::price_convert_raw( $wc_price );

    $display_features = MPCRBM_Global_Function::get_post_info($post_id, 'display_mpcrbm_features', 'on');
    $all_features = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_features');

    $all_car_type = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_car_type');
    $all_fuel_type = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_fuel_type');
    $all_seating_capacity = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_seating_capacity');
    $all_car_brand = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_car_brand');
    $all_car_year = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_make_year');

    $all_car_type_str         = (is_array($all_car_type) && !empty($all_car_type)) ? implode(',', $all_car_type) : '';
    $all_fuel_type_str        = (is_array($all_fuel_type) && !empty($all_fuel_type)) ? implode(',', $all_fuel_type) : '';
    $all_seating_capacity_str = (is_array($all_seating_capacity) && !empty($all_seating_capacity)) ? implode(', ', $all_seating_capacity) : '';
    $all_car_brand_str        = (is_array($all_car_brand) && !empty($all_car_brand)) ? implode(',', $all_car_brand) : '';
    $all_car_year_str         = (is_array($all_car_year) && !empty($all_car_year)) ? implode(',', $all_car_year) : '';

    $all_filters = [
        $all_car_type,
        $all_fuel_type,
        $all_seating_capacity,
        $all_car_brand,
        $all_car_year
    ];
    $merged_values = [];
    foreach ($all_filters as $filter) {
        if (is_array($filter) && !empty($filter)) {
            $merged_values = array_merge($merged_values, $filter);
        }
    }
    $final_filter_string = !empty($merged_values) ? implode(', ', $merged_values) : '';


    /*$startDate  = new DateTime( $start_date_time );
    $returnDate = new DateTime( $return_date_time );
    $interval = $startDate->diff( $returnDate );
    $minutes        = ( $interval->days * 24 * 60 ) + ( $interval->h * 60 ) + $interval->i;
    $minutes_to_day = ceil( $minutes / 1440 );*/


    $price_per_day = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price', 0 );

    $total_base_price = $minutes_to_day * $price_per_day;
    $total_save = $total_base_price - $raw_price ;

    $day_price = $raw_price/$days;
    $day_price = round( $day_price, 2 );

    $enable_seasonal    = (int)get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );
    $enable_day_wise    = (int)get_post_meta( $post_id, 'mpcrbm_enable_day_wise_discount', true );
    $enable_tired       =  (int)get_post_meta( $post_id, 'mpcrbm_enable_tired_discount', true );

    $line_through = '';
    if( $enable_seasonal === 1 || $enable_tired == 1 || $enable_day_wise === 1 ){
        $line_through = 'mpcrbm_line_through';
    }

    ?>
    <div class="mpcrbm_booking_vehicle mpcrbm_booking_item <?php echo esc_attr('mpcrbm_booking_item_' . $post_id); ?> <?php echo esc_attr($hidden_class); ?> <?php echo esc_attr($feature_class); ?>" data-placeholder
         data-car-type="<?php echo esc_attr( $all_car_type_str)?>"
         data-fuel-type="<?php echo esc_attr( $all_fuel_type_str)?>"
         data-seating-capacity="<?php echo esc_attr( $all_seating_capacity_str)?>"
         data-car-brand="<?php echo esc_attr( $all_car_brand_str)?>"
         data-car-year="<?php echo esc_attr( $all_car_year_str)?>"
         data-filter-category-items="<?php echo esc_attr( $final_filter_string)?>"
    >
        <div class="mpcrbm-image-box">
            <div class="bg_image_area" data-placeholder>
                <?php if( $ajax_search === 'yes' ){
                    ?>
                    <img src="<?php echo esc_attr($thumbnail); ?>">
                <?php }else{?>
                    <div data-bg-image="<?php echo esc_attr($thumbnail); ?>"></div>
                <?php }?>

            </div>
        </div>
        <div class="mpcrbm_list_details">
            <h2><?php echo esc_html(get_the_title($post_id)); ?></h2>
            <div class=" mpcrbm_list">
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
                                    <span class="<?php echo esc_attr($icon); ?>"></span>
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
                <?php }
                ?>
                <div class="mpcrbm_discount_booking">
                    <div class="mpcrbm_discount_info <?php echo esc_attr(( $enable_seasonal === 1 || $enable_tired == 1 || $enable_day_wise === 1 ) ? 'mpcrbm-discount-seasonal':''); ?>">
                        <div class="mpcrbm_price-breakdown <?php echo esc_attr( $line_through );?>"><?php echo wp_kses_post( wc_price($price_per_day ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                        <?php
                        if( $enable_seasonal === 1 || $enable_tired == 1 || $enable_day_wise === 1 ){ ?>
                            <div class="mpcrbm_price-main"><?php echo wp_kses_post( wc_price( $day_price ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                        <?php } ?>
                    </div>
                    <div class="_min_150_mL_xs mpcrbm_booking_items">
                        <?php
                        // Calculate Early Bird Discount and apply to price
                        $early_bird_discount = 0;
                        $early_bird_info = null;
                        $discounted_price = $raw_price;

                        if (class_exists('MPCRBM_Frontend_Early_Bird')) {
                            $early_bird_info = MPCRBM_Frontend_Early_Bird::get_early_bird_discount_info($post_id, $start_date_time);
                            if ($early_bird_info && $early_bird_info['applicable']) {
                                $early_bird_discount = MPCRBM_Frontend_Early_Bird::calculate_discount_amount($post_id, $start_date_time, $raw_price);
                                $discounted_price = $raw_price - $early_bird_discount;
                            }
                        }
                        ?>

                        <div class="mpcrbm-price-container">
                            <?php if ($early_bird_discount > 0): ?>
                                <div class="mpcrbm-original-price" style="text-decoration: line-through; color: #999; font-size: 0.9em; text-align: end">
                                    <?php echo wp_kses_post(wc_price($raw_price)); ?>
                                </div>
                                <div class="mpcrbm-discounted-price" style="font-size: 1.2em; font-weight: bold; color: #2c3338;">
                                    <?php echo wp_kses_post(wc_price($discounted_price)); ?>
                                </div>
                                <div class="mpcrbm_early_bird_promotion_badge" style="margin: 3px 0 0 0;">
                                    <span class="fas fa-clock"></span>
                                    <div class="early_bird_text">
                                        <strong><?php esc_html_e('Early Bird Special!', 'car-rental-manager'); ?></strong>
                                        <span>
                                        <?php
                                        $discount_text = $early_bird_info['discount_type'] === 'percentage'
                                            ? $early_bird_info['discount_value'] . '%'
                                            : wc_price($early_bird_info['discount_value']);
                                        echo esc_html(sprintf(
                                            __('Save %s (Early Bird)', 'car-rental-manager'),
                                            $discount_text
                                        ));
                                        ?>
                                    </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mpcrbm-price" style="font-size: 1.2em; font-weight: bold; color: #2c3338; text-align: end">
                                    <?php echo wp_kses_post(wc_price($raw_price)); ?>
                                </div>
                            <?php endif; ?>

                            <div class="mpcrbm_price-total" style="margin-top: 2px; color: #666; font-size: 0.85em;">
                                <?php echo esc_attr($minutes_to_day); ?>-day total
                                <?php if ($early_bird_discount > 0): ?>
                                    <span style="color: #4CAF50; font-weight: bold; margin-left: 5px;">
                                    (<?php echo wp_kses_post(wc_price($early_bird_discount)); ?> saved)
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($total_save > 0): ?>
                                <div class="mpcrbm_discount-info" style="color: #d26e4b; font-weight: bold; margin-top: 2px;">
                                    <?php echo sprintf(esc_html__('You saved %s', 'car-rental-manager'), wp_kses_post(wc_price($total_save))); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            <div class="mpcrbm_add_multiple_qty">
                <div class="_mR_min_100 mpcrbm_car_quantity" data-collapse="<?php echo esc_attr($post_id); ?>" style="display: none">
                    <?php MPCRBM_Custom_Layout::qty_input('mpcrbm_multiple_car_qty[]', $raw_price, 100, 1, 0); ?>
                </div>
                <button type="button"
                        class="_mpBtn_xs mpcrbm_transport_select"
                        data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>"
                        data-transport-price="<?php echo esc_attr($discounted_price); ?>"
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        data-open-text="<?php esc_attr_e('Select Car', 'car-rental-manager'); ?>"
                        data-close-text="<?php esc_html_e('Selected', 'car-rental-manager'); ?>"
                        data-open-icon=""
                        data-close-icon="fas fa-check mR_xs">
                    <span class="" data-icon></span>
                    <span data-text><?php esc_html_e('Select Car', 'car-rental-manager'); ?></span>
                </button>
            </div>

            <?php do_action('mpcrbm_booking_item_after_feature', $post_id); ?>
        </div>
    </div>
    <?php
}
?>