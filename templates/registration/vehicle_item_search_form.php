<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */

// Verify nonce


// Initialize variables with defaults
$mpcrbm_date = $mpcrbm_date ?? '';
$mpcrbm_start_date_time = $mpcrbm_date;
$mpcrbm_return_date_time = $mpcrbm_return_date_time ?? '';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$post_id = $mpcrbm_post_id ?? '';
$mpcrbm_original_price_based = $mpcrbm_price_based ?? '';
$mpcrbm_start_place = $mpcrbm_start_place ?? '';
$mpcrbm_end_place = $mpcrbm_end_place ?? '';


$mpcrbm_pricing_rule_data = MPCRBM_Function::display_pricing_rules( $post_id );
$mpcrbm_is_discount = isset( $mpcrbm_pricing_rule_data['is_discount'] ) ? $mpcrbm_pricing_rule_data['is_discount'] : false;
$mpcrbm_base_price = isset( $mpcrbm_pricing_rule_data['base_price'] ) ? $mpcrbm_pricing_rule_data['base_price'] : false;
$mpcrbm_show_car_class = isset( $car_class ) ? $car_class : '';

// Validate post_id
if (!$post_id || !get_post($post_id)) {
    wp_send_json_error(array('message' => esc_html__('Invalid vehicle', 'car-rental-manager')));
    wp_die();
}


// Feature class handling
$mpcrbm_feature_class = '';
if (MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_filter_via_features') == 'yes') {
    $mpcrbm_max_passenger = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_maximum_passenger');
    $mpcrbm_max_bag = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_maximum_bag');
    if (!empty($mpcrbm_max_passenger) && !empty($mpcrbm_max_bag)) {
        $mpcrbm_feature_class = sprintf(
            'feature_passenger_%d_feature_bag_%d_post_id_%d',
            absint($mpcrbm_max_passenger),
            absint($mpcrbm_max_bag),
            absint($post_id)
        );
    }
}

// Sanitize and validate inputs
$mpcrbm_fixed_time = $mpcrbm_fixed_time ?? 0;
$mpcrbm_start_date = $mpcrbm_start_date ? gmdate('Y-m-d', strtotime($mpcrbm_start_date)) : '';

// Validate dates
$mpcrbm_all_dates = MPCRBM_Function::get_date($post_id);
if (empty($mpcrbm_all_dates) || !in_array($mpcrbm_start_date, $mpcrbm_all_dates, true)) {
    return;
}

// View settings
$mpcrbm_enable_view_search_result_page = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');
$mpcrbm_hidden_class = $mpcrbm_enable_view_search_result_page == '' ? '' : '';

// Sanitize location data
$mpcrbm_label = $mpcrbm_label ?? MPCRBM_Function::get_name();
$mpcrbm_two_way = $mpcrbm_two_way ?? 1;

if ($post_id) {
    // Get vehicle data
    $mpcrbm_thumbnail = MPCRBM_Global_Function::get_image_url($post_id);

    $mpcrbm_get_stock_data = $mpcrbm_available_cars_car_ids[$post_id];
    $mpcrbm_car_qty = isset( $mpcrbm_get_stock_data['available'] ) ? $mpcrbm_get_stock_data['available'] : 1;

    $mpcrbm_days = MPCRBM_Function::get_days_from_start_end_date( $mpcrbm_start_date_time, $mpcrbm_return_date_time );
    // Use multi-location pricing if enabled, otherwise use default pricing
    $mpcrbm_price = MPCRBM_Function::calculate_multi_location_price($post_id, $mpcrbm_start_place, $mpcrbm_end_place, $mpcrbm_start_date_time, $mpcrbm_return_date_time);

    if (!$mpcrbm_price || $mpcrbm_price <= 0) {
        return;
    }

    $mpcrbm_wc_price = MPCRBM_Global_Function::wc_price( $post_id, $mpcrbm_price );
    $mpcrbm_raw_price = MPCRBM_Global_Function::price_convert_raw( $mpcrbm_wc_price );

    $mpcrbm_all_car_type = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_car_type');
    $mpcrbm_all_fuel_type = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_fuel_type');
    $mpcrbm_all_seating_capacity = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_seating_capacity');
    $mpcrbm_all_car_brand = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_car_brand');
    $mpcrbm_all_car_year = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_make_year');

    $mpcrbm_all_car_type_str         = (is_array($mpcrbm_all_car_type) && !empty($mpcrbm_all_car_type)) ? implode(',', $mpcrbm_all_car_type) : '';
    $mpcrbm_all_fuel_type_str        = (is_array($mpcrbm_all_fuel_type) && !empty($mpcrbm_all_fuel_type)) ? implode(',', $mpcrbm_all_fuel_type) : '';
    $mpcrbm_all_seating_capacity_str = (is_array($mpcrbm_all_seating_capacity) && !empty($mpcrbm_all_seating_capacity)) ? implode(', ', $mpcrbm_all_seating_capacity) : '';
    $mpcrbm_all_car_brand_str        = (is_array($mpcrbm_all_car_brand) && !empty($mpcrbm_all_car_brand)) ? implode(',', $mpcrbm_all_car_brand) : '';
    $mpcrbm_all_car_year_str         = (is_array($mpcrbm_all_car_year) && !empty($mpcrbm_all_car_year)) ? implode(',', $mpcrbm_all_car_year) : '';

    $mpcrbm_all_filters = [
        $mpcrbm_all_car_type,
        $mpcrbm_all_fuel_type,
        $mpcrbm_all_seating_capacity,
        $mpcrbm_all_car_brand,
        $mpcrbm_all_car_year
    ];
    $mpcrbm_merged_values = [];
    foreach ($mpcrbm_all_filters as $mpcrbm_filter) {
        if (is_array($mpcrbm_filter) && !empty($mpcrbm_filter)) {
            $mpcrbm_merged_values = array_merge($mpcrbm_merged_values, $mpcrbm_filter);
        }
    }
    $mpcrbm_final_filter_string = !empty($mpcrbm_merged_values) ? implode(', ', $mpcrbm_merged_values) : '';

    $mpcrbm_price_per_day = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price', 0 );

    $mpcrbm_total_base_price = $mpcrbm_minutes_to_day * $mpcrbm_price_per_day;
    $mpcrbm_total_save = $mpcrbm_total_base_price - $mpcrbm_raw_price ;

    $mpcrbm_day_price = $mpcrbm_raw_price/$mpcrbm_days;
    $mpcrbm_day_price = round( $mpcrbm_day_price, 2 );

    $mpcrbm_enable_seasonal    = (int)get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );
    $mpcrbm_enable_day_wise    = (int)get_post_meta( $post_id, 'mpcrbm_enable_day_wise_discount', true );
    $mpcrbm_enable_tired       =  (int)get_post_meta( $post_id, 'mpcrbm_enable_tired_discount', true );

    $mpcrbm_line_through = '';
    if( $mpcrbm_is_discount && $mpcrbm_base_price !== $mpcrbm_day_price ){
        $mpcrbm_line_through = 'mpcrbm_line_through';
    }

    $mpcrbm_car_type_terms     = get_post_meta($post_id, 'mpcrbm_car_type', true);
    $mpcrbm_fuel_type_terms    = get_post_meta($post_id, 'mpcrbm_fuel_type', true);
    $mpcrbm_seating_terms      = get_post_meta($post_id, 'mpcrbm_seating_capacity', true);
    $mpcrbm_brand_terms        = get_post_meta($post_id, 'mpcrbm_car_brand', true);
    $mpcrbm_year_terms         = get_post_meta($post_id, 'mpcrbm_make_year', true);
    $mpcrbm_maximum_bag        = get_post_meta($post_id, 'mpcrbm_maximum_bag', true);

    // --- Safely extract names (handles single or multiple terms) ---
    $mpcrbm_car_type_name   = !empty($mpcrbm_car_type_terms)  ? esc_html($mpcrbm_car_type_terms[0])   : '—';
    $mpcrbm_fuel_type_name  = !empty($mpcrbm_fuel_type_terms) ? esc_html($mpcrbm_fuel_type_terms[0])  : '—';
    $mpcrbm_seating_name    = !empty($mpcrbm_seating_terms)   ? esc_html($mpcrbm_seating_terms[0])    : '—';
    $mpcrbm_brand_name      = !empty($mpcrbm_brand_terms)     ? esc_html($mpcrbm_brand_terms[0])      : '—';
    $mpcrbm_year_name       = !empty($mpcrbm_year_terms)      ? esc_html($mpcrbm_year_terms[0])       : '—';
    $mpcrbm_bag_count       = !empty($mpcrbm_maximum_bag)     ? esc_html($mpcrbm_maximum_bag)         : '—';
    ?>
    <div class="mpcrbm_booking_vehicle <?php echo esc_attr($mpcrbm_show_car_class);?> mpcrbm_booking_item <?php echo esc_attr('mpcrbm_booking_item_' . $post_id); ?> <?php echo esc_attr($mpcrbm_hidden_class); ?> <?php echo esc_attr($mpcrbm_feature_class); ?>" data-placeholder
         data-car-type="<?php echo esc_attr( $mpcrbm_all_car_type_str)?>"
         data-fuel-type="<?php echo esc_attr( $mpcrbm_all_fuel_type_str)?>"
         data-seating-capacity="<?php echo esc_attr( $mpcrbm_all_seating_capacity_str)?>"
         data-car-brand="<?php echo esc_attr( $mpcrbm_all_car_brand_str)?>"
         data-car-year="<?php echo esc_attr( $mpcrbm_all_car_year_str)?>"
         data-filter-category-items="<?php echo esc_attr( $mpcrbm_final_filter_string)?>"
    >
        <div class="mpcrbm-image-box">
            <div class="bg_image_area" data-placeholder>
                <img src="<?php echo esc_attr($mpcrbm_thumbnail); ?>">
            </div>
        </div>
        <div class="mpcrbm_list_details">
            <h2><?php echo esc_html(get_the_title($post_id)); ?></h2>
            <div class=" mpcrbm_list">
                <div class="mpcrbm_car_specs_lists">
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-car"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Car Type ','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_car_type_name); ?></div>
                        </div>
                    </div>
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-gas-pump-alt"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Fuel Type ','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_fuel_type_name); ?></div>
                        </div>
                    </div>
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-bonus"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Brands','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_brand_name); ?></div>
                        </div>
                    </div>
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-time-quarter-to"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Make Year','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_year_name); ?></div>
                        </div>
                    </div>
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-person-seat"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Seating Capacity','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_seating_name); ?></div>
                        </div>
                    </div>
                    <div class="mpcrbm_car_spec">
                        <i class="mi mi-person-luggage"></i>
                        <div>
                            <div class="spec-label"><?php echo esc_html__('Maximum Bags','car-rental-manager'); ?></div>
                            <div class="spec-value"><?php echo esc_html($mpcrbm_bag_count); ?></div>
                        </div>
                    </div>
                </div>
                <div class="mpcrbm_discount_booking">

                    <div class="mpcrbm_price_holder">
                        <div class="mpcrbm_discount_info <?php echo esc_attr(( $mpcrbm_is_discount && $mpcrbm_base_price !== $mpcrbm_day_price ) ? 'mpcrbm-discount-seasonal':''); ?>">
                            <div class="" style="display: flex;justify-content: space-between">
                                <div class="mpcrbm_price-breakdown <?php echo esc_attr( $mpcrbm_line_through );?>"><?php echo wp_kses_post( wc_price($mpcrbm_price_per_day ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                                <?php if( $mpcrbm_is_discount && $mpcrbm_base_price !== $mpcrbm_day_price ){
                                    $mpcrbm_pricing_rules = isset( $mpcrbm_pricing_rule_data['pricing_rules'] ) ? $mpcrbm_pricing_rule_data['pricing_rules'] : '';
                                    ?>
                                    <div class="mpcrbm_car_price_holder" style="display: flex; justify-content: space-between">
                                        <div class="mpcrbm_price_hover_wrap">
                                        <span class="mpcrbm_price_info">
                                            ℹ
                                        </span>
                                            <div class=""><?php echo wp_kses_post( $mpcrbm_pricing_rules );?></div>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>
                            <?php
                            if( $mpcrbm_is_discount && $mpcrbm_base_price !== $mpcrbm_day_price ){ ?>
                                <div class="mpcrbm_price-main"><?php echo wp_kses_post( wc_price( $mpcrbm_day_price ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                            <?php } ?>
                        </div>
                        <div class="mpcrbm_booking_items">
                            <?php
                            // Calculate Early Bird Discount and apply to price
                            $mpcrbm_early_bird_discount = 0;
                            $mpcrbm_early_bird_info = null;
                            $mpcrbm_discounted_price = $mpcrbm_raw_price;

                            if (class_exists('MPCRBM_Frontend_Early_Bird')) {
                                $mpcrbm_early_bird_info = MPCRBM_Frontend_Early_Bird::get_early_bird_discount_info($post_id, $mpcrbm_start_date_time);
                                if ($mpcrbm_early_bird_info && $mpcrbm_early_bird_info['applicable']) {
                                    $mpcrbm_early_bird_discount = MPCRBM_Frontend_Early_Bird::calculate_discount_amount($post_id, $mpcrbm_start_date_time, $mpcrbm_raw_price);
                                    $mpcrbm_discounted_price = $mpcrbm_raw_price - $mpcrbm_early_bird_discount;
                                }
                            }
                            ?>

                            <div class="mpcrbm-price-container">
                                <?php if ($mpcrbm_early_bird_discount > 0): ?>
                                    <div class="mpcrbm-original-price" style="text-decoration: line-through; color: #999; font-size: 0.9em; text-align: end">
                                        <?php echo wp_kses_post(wc_price($mpcrbm_raw_price)); ?>
                                    </div>
                                    <div class="mpcrbm-discounted-price" style="font-size: 1.2em; font-weight: bold; color: #2c3338;">
                                        <?php echo wp_kses_post(wc_price($mpcrbm_discounted_price)); ?>
                                    </div>
                                    <div class="mpcrbm_early_bird_promotion_badge" style="margin: 3px 0 0 0;">
                                        <span class="fas fa-clock"></span>
                                        <div class="early_bird_text">
                                            <strong><?php esc_html_e('Early Bird Special!', 'car-rental-manager'); ?></strong>
                                            <span>
                                        <?php
                                        $mpcrbm_discount_text = $mpcrbm_early_bird_info['discount_type'] === 'percentage'
                                            ? $mpcrbm_early_bird_info['discount_value'] . '%'
                                            : wc_price($mpcrbm_early_bird_info['discount_value']);
                                        echo esc_html(sprintf(
                                        // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                                            __('Save %s (Early Bird)', 'car-rental-manager'),
                                            $mpcrbm_discount_text
                                        ));
                                        ?>
                                    </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="mpcrbm-price">
                                        <?php echo wp_kses_post(wc_price($mpcrbm_raw_price)); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mpcrbm_price-total">
                                    <?php echo esc_attr($mpcrbm_minutes_to_day); ?>-day total
                                    <?php if ($mpcrbm_early_bird_discount > 0): ?>
                                        <span>
                                    (<?php echo wp_kses_post(wc_price($mpcrbm_early_bird_discount)); ?> saved)
                                </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($mpcrbm_total_save > 0): ?>
                                    <div class="mpcrbm_discount-info" style="color: #d26e4b; font-weight: bold; margin-top: 2px;">
                                        <?php echo sprintf(
                                        // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                                                esc_html__('You saved %s', 'car-rental-manager'), wp_kses_post(wc_price($mpcrbm_total_save))
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                    <div class="mpcrbm_add_multiple_qty">
                        <div class=" mpcrbm_car_quantity" data-collapse="<?php echo esc_attr($post_id); ?>" style="display: none">
                            <?php MPCRBM_Custom_Layout::qty_input('mpcrbm_multiple_car_qty[]', $mpcrbm_raw_price, $mpcrbm_car_qty, 1, 0); ?>
                        </div>
                        <button type="button"
                                class="_mpBtn_xs mpcrbm_transport_select"
                                data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                data-transport-price="<?php echo esc_attr($mpcrbm_discounted_price); ?>"
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
            </div>


            <?php do_action('mpcrbm_booking_item_after_feature', $post_id); ?>
        </div>
    </div>
    <?php
}

?>