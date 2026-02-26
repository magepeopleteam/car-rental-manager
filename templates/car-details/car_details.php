<?php
// Template Name: Default Theme
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$mpcrbm_post_id = $post_id ?? get_the_id();
$mpcrbm_thumbnail_url = get_the_post_thumbnail_url($mpcrbm_post_id, 'full');

$mpcrbm_include_features = get_post_meta( $mpcrbm_post_id, 'mpcrbm_include_features', true );
$mpcrbm_include_feature_names = [];
if( is_array( $mpcrbm_include_features ) && !empty( $mpcrbm_include_features ) ){
    foreach ($mpcrbm_include_features as $mpcrbm_term_id) {
        $mpcrbm_term_name = MPCRBM_Function::get_taxonomy_name_by_id( $mpcrbm_term_id, 'mpcrbm_car_feature' );
        if ( $mpcrbm_term_name ) {
            $mpcrbm_include_feature_names[] = $mpcrbm_term_name;
        }
    }
}

$mpcrbm_exclude_features = get_post_meta( $mpcrbm_post_id, 'mpcrbm_exclude_features', true );
$mpcrbm_exclude_feature_names = [];
if( is_array( $mpcrbm_exclude_features ) && !empty( $mpcrbm_exclude_features ) ){
    foreach ($mpcrbm_exclude_features as $mpcrbm_term_id) {
        $mpcrbm_term_name = MPCRBM_Function::get_taxonomy_name_by_id( $mpcrbm_term_id, 'mpcrbm_car_feature' );
        if ( $mpcrbm_term_name ) {
            $mpcrbm_exclude_feature_names[] = $mpcrbm_term_name;
        }
    }
}

$mpcrbm_faqs = get_option( 'mpcrbm_faq_list', [] );
$mpcrbm_added_faqs = get_post_meta( $mpcrbm_post_id, 'mpcrbm_added_faq', true );
$mpcrbm_selected_faqs_data = [];
if (!empty($mpcrbm_added_faqs) && !empty( $mpcrbm_faqs ) ) {
    foreach ($mpcrbm_added_faqs as $mpcrbm_faq_key) {
        if (isset($mpcrbm_faqs[$mpcrbm_faq_key])) {
            $mpcrbm_selected_faqs_data[$mpcrbm_faq_key] = $mpcrbm_faqs[$mpcrbm_faq_key];
        }
    }
}

$mpcrbm_all_term_condition = get_option( 'mpcrbm_term_condition_list', [] );
$mpcrbm_added_term_condition = get_post_meta( $mpcrbm_post_id, 'mpcrbm_term_condition_list', true );
$mpcrbm_selected_term_condition = [];
if (!empty($mpcrbm_added_term_condition) && !empty( $mpcrbm_all_term_condition ) ) {
    foreach ($mpcrbm_added_term_condition as $mpcrbm_faq_key) {
        if (isset($mpcrbm_all_term_condition[$mpcrbm_faq_key])) {
            $mpcrbm_selected_term_condition[$mpcrbm_faq_key] = $mpcrbm_all_term_condition[$mpcrbm_faq_key];
        }
    }
}

//$daily_price = get_post_meta( $mpcrbm_post_id, 'mpcrbm_base_daily_price', true );
//$mpcrbm_day_price = get_post_meta( $mpcrbm_post_id, 'mpcrbm_day_price', true );
$mpcrbm_price = get_post_meta( $mpcrbm_post_id, 'mpcrbm_day_price', true );
$mpcrbm_extra_service = get_post_meta( $mpcrbm_post_id, 'mpcrbm_extra_service_infos', true );
$mpcrbm_price_based = get_post_meta( $mpcrbm_post_id, 'mpcrbm_price_based', true );
$mpcrbm_link_wc_product = get_post_meta( $mpcrbm_post_id, 'link_wc_product', true );
$mpcrbm_display_faq = get_post_meta( $mpcrbm_post_id, 'mpcrbm_display_faq', true );

$mpcrbm_enable_seasonal = get_post_meta( $mpcrbm_post_id, 'mpcrbm_enable_seasonal_discount', true );
$mpcrbm_seasonal_pricing = get_post_meta( $mpcrbm_post_id, 'mpcrbm_seasonal_pricing', true );
$mpcrbm_enable_day_wise = get_post_meta( $mpcrbm_post_id, 'mpcrbm_enable_day_wise_discount', true );
$mpcrbm_day_wise_pricing = get_post_meta( $mpcrbm_post_id, 'mpcrbm_daywise_pricing', true );
$mpcrbm_tiered_discounts = get_post_meta( $mpcrbm_post_id, 'mpcrbm_tiered_discounts', true );
$mpcrbm_location_prices = get_post_meta( $mpcrbm_post_id, 'mpcrbm_location_prices', true );

$mpcrbm_location_price_info = get_post_meta( $mpcrbm_post_id, 'mpcrbm_terms_price_info', true );
$mpcrbm_map_location = '';
if( is_array( $mpcrbm_location_price_info ) && !empty( $mpcrbm_location_price_info ) ){
    $mpcrbm_map_location = isset( $mpcrbm_location_price_info[0]['start_location'] ) ? $mpcrbm_location_price_info[0]['start_location'] : '';
}

$mpcrbm_make_year = get_post_meta( $mpcrbm_post_id, 'mpcrbm_make_year', true );
$mpcrbm_make_year = !empty($mpcrbm_make_year) ? $mpcrbm_make_year[0] : '';

$mpcrbm_car_brand = get_post_meta( $mpcrbm_post_id, 'mpcrbm_car_brand', true );
$mpcrbm_car_brand = !empty($mpcrbm_car_brand) ? $mpcrbm_car_brand[0] : '';

$mpcrbm_seating_capacity = get_post_meta( $mpcrbm_post_id, 'mpcrbm_seating_capacity', true );
$mpcrbm_seating_capacity = !empty($mpcrbm_seating_capacity) ? $mpcrbm_seating_capacity[0] : '';

$mpcrbm_fuel_type = get_post_meta( $mpcrbm_post_id, 'mpcrbm_fuel_type', true );
$mpcrbm_fuel_type = !empty($mpcrbm_fuel_type) ? $mpcrbm_fuel_type[0] : '';

$mpcrbm_car_type = get_post_meta( $mpcrbm_post_id, 'mpcrbm_car_type', true );
$mpcrbm_car_type = !empty($mpcrbm_car_type) ? $mpcrbm_car_type[0] : '';

$mpcrbm_maximum_bag = get_post_meta( $mpcrbm_post_id, 'mpcrbm_maximum_bag', true );
$mpcrbm_maximum_bag = !empty($mpcrbm_maximum_bag) ? $mpcrbm_maximum_bag : '';


$mpcrbm_off_dates = get_post_meta( $mpcrbm_post_id, 'mpcrbm_off_dates', true );
if( !is_array( $mpcrbm_off_dates ) && empty( $mpcrbm_off_dates ) ){
    $mpcrbm_off_dates = [];
}
$mpcrbm_booking_dates = [];
//$mpcrbm_booking_dates = MPCRBM_Frontend::mpcrbm_get_all_booking_dates_between_start_end( $mpcrbm_post_id );
$mpcrbm_booking_dates = MPCRBM_Frontend::mpcrbm_get_unavailable_dates_by_stock( $mpcrbm_post_id );
$mpcrbm_booking_btn_show = 'none';
$mpcrbm_is_already_booked = 'block';
$mpcrbm_available_stock = MPCRBM_Frontend::mpcrbm_get_available_stock_by_date( $mpcrbm_post_id, gmdate('Y-m-d') );
if( $mpcrbm_available_stock > 0 ){
    $mpcrbm_booking_btn_show = 'block';
    $mpcrbm_is_already_booked = 'none';
}

$mpcrbm_off_dates = array_merge( $mpcrbm_off_dates, $mpcrbm_booking_dates );

$mpcrbm_off_dates_str = '';
if( is_array( $mpcrbm_off_dates ) && !empty( $mpcrbm_off_dates ) ){
    $mpcrbm_off_dates_str = implode( ',' , $mpcrbm_off_dates);
}
$mpcrbm_off_days = get_post_meta( $mpcrbm_post_id, 'mpcrbm_off_days', true );


$mpcrbm_gallery_images = get_post_meta( $mpcrbm_post_id, 'mpcrbm_gallery_images', true );
$mpcrbm_gallery_image_urls = [];
if (!empty($mpcrbm_gallery_images) && is_array($mpcrbm_gallery_images)) {
    $mpcrbm_gallery_image_urls = array_map(function ( $id ) {
        return wp_get_attachment_url( $id );
    }, $mpcrbm_gallery_images );
}
$mpcrbm_all_image_urls = $mpcrbm_gallery_image_urls;
if( $mpcrbm_thumbnail_url ){
    array_push( $mpcrbm_all_image_urls, $mpcrbm_thumbnail_url );
}

$mpcrbm_car_name = get_the_title( $mpcrbm_post_id );
$mpcrbm_car_description = get_the_content( $mpcrbm_post_id );

$mpcrbm_date = gmdate('Y-m-d') . ' 10:00';
$mpcrbm_start_place = $mpcrbm_end_place = isset( $mpcrbm_location_prices[0]['pickup_location'] ) ? $mpcrbm_location_prices[0]['pickup_location'] : '';
$mpcrbm_return_date = gmdate('Y-m-d', strtotime('+1 day'));
$mpcrbm_start_time = $mpcrbm_return_time = 10.00;
$mpcrbm_two_way = 2;

$mpcrbm_return_date_time = $mpcrbm_return_date. ' 10:00';

$mpcrbm_discount_price = MPCRBM_Function::calculate_multi_location_price( $mpcrbm_post_id, $mpcrbm_start_place, $mpcrbm_end_place, $mpcrbm_date, $mpcrbm_return_date_time );

$mpcrbm_driver_info = get_post_meta( $mpcrbm_post_id, 'mpcrbm_driver_info', true );

$mpcrbm_start_day = get_option('start_of_week', 0);


$mpcrbm_start_date = gmdate("Y-m-d H:i");
if( $mpcrbm_post_id && $mpcrbm_price > 0 ){
    $mpcrbm_day_price = MPCRBM_Function::mpcrbm_calculate_price( $mpcrbm_post_id, $mpcrbm_start_date, 1, $mpcrbm_price );
}else{
    $mpcrbm_day_price = $mpcrbm_price;
}


$mpcrbm_show_feature_section           = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'car_details_car_feature_section');
$mpcrbm_show_pickup_location_section   = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'car_details_pickup_location_section');
$mpcrbm_show_review_section            = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'car_details_review_section');
$mpcrbm_show_faq_section               = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'car_details_faq_section');
$mpcrbm_show_term_condition            = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'car_details_term_condition');
?>
<div class="mpcrbm_car_details">
    <input type="hidden" name="mpcrbm_post_id" value="<?php echo esc_attr( $mpcrbm_post_id );?>" data-price="<?php echo esc_attr( $mpcrbm_day_price )?>" />
    <input type="hidden" name="mpcrbm_car_title" id="mpcrbm_car_title" value="<?php echo esc_attr( $mpcrbm_car_name );?>" />
    <input type="hidden" name="mpcrbm_start_place" value="<?php echo esc_attr($mpcrbm_start_place); ?>" />
    <input type="hidden" name="mpcrbm_end_place" value="<?php echo esc_attr($mpcrbm_end_place); ?>" />
    <input type="hidden" name="mpcrbm_date" value="<?php echo esc_attr($mpcrbm_date); ?>" />
    <input type="hidden" name="mpcrbm_start_time" id="mpcrbm_start_time" value="<?php echo esc_attr($mpcrbm_start_time); ?>" />
<!--    <input type="hidden" name="mpcrbm_taxi_return" value="--><?php //echo esc_attr($mpcrbm_two_way); ?><!--" />-->

    <input type="hidden" id="mpcrbm_start_calendar_day" name="mpcrbm_start_calendar_day" value="<?php echo esc_attr($mpcrbm_start_day); ?>" />
    <input type="hidden" name="mpcrbm_map_return_date" id="mpcrbm_map_return_date" value="<?php echo esc_attr($mpcrbm_return_date); ?>" />
    <input type="hidden" name="mpcrbm_map_return_time" id="mpcrbm_map_return_time" value="<?php echo esc_attr($mpcrbm_return_time); ?>" />

    <input type="hidden" id="mpcrbm_selected_car_quantity" name="mpcrbm_selected_car_quantity"  value="1" />

    <input type="hidden" id="mpcrbm_off_days" name="mpcrbm_car_off_days"  value="<?php echo esc_attr( $mpcrbm_off_days );?>" />
    <input type="hidden" id="mpcrbm_off_dates" name="mpcrbm_car_off_dates"  value="<?php echo esc_attr( $mpcrbm_off_dates_str );?>" />

    <div class="mpcrbm_gallery_image_popup_wrapper">
        <div class="mpcrbm_gallery_image_popup_overlay"></div>
        <div class="mpcrbm_gallery_image_popup_content">
            <div class="" style="display: block; float: right">
                <button class="mpcrbm_gallery_image_popup_close">✕</button>
            </div>
            <div class="mpcrbm_gallery_image_popup_container">
                <?php foreach ( $mpcrbm_all_image_urls as $mpcrbm_index => $mpcrbm_img_url): ?>
                    <img src="<?php echo esc_url($mpcrbm_img_url); ?>"
                         class="mpcrbm_gallery_image_popup_item <?php echo $mpcrbm_index === 0 ? 'active' : ''; ?>"
                         alt="Gallery image">
                <?php endforeach; ?>
            </div>
            <div class="mpcrbm_gallery_image_popup_prev_holder" style="display: flex; justify-content: space-between">
                <div class="">
                    <button class="mpcrbm_gallery_image_popup_prev">←</button>
                </div>
                <div class="">
                    <button class="mpcrbm_gallery_image_popup_next">→</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mpcrbm mpcrbm_default_theme">
        <div class="mpContainer" style="min-height: 1000px">
            <?php do_action( 'mpcrbm_transport_search_form',$mpcrbm_post_id ); ?>
            <div class="mpcrbm_car_details_wrapper">
                <h1 ><?php echo esc_attr( $mpcrbm_car_name);?></h1>
                <div class="mpcrbm_car_details_container">
                    <input type="hidden" id="mpcrbm_car_id" value="<?php echo esc_attr( $mpcrbm_post_id );?>">
                    <div class="mpcrbm_car_details_left">

                        <div class="mpcrbm_car_details_images">
                            <div class="mpcrbm_car_details_feature_image">
                                <?php if( $mpcrbm_thumbnail_url ){?>
                                    <img class="mpcrbm_car_image_details" id="mpcrbm_car_details_feature_image" src="<?php echo esc_attr( $mpcrbm_thumbnail_url );?>" alt="<?php echo esc_attr( $mpcrbm_car_name );?>">
                                <?php }?>
                            </div>
                            <?php
                            if (!empty( $mpcrbm_gallery_images ) && is_array( $mpcrbm_gallery_images ) ) { ?>
                                <div class="mpcrbm_car_details_gallery">
                                    <?php
                                    $mpcrbm_counter = 0;

                                    foreach ( $mpcrbm_gallery_image_urls as $mpcrbm_gallery_image_url ) {
                                        if ( !$mpcrbm_gallery_image_url ) continue;
                                        if ( $mpcrbm_counter < 4 ) { ?>
                                            <img class="mpcrbm_gallery_image" src=" <?php echo esc_url( $mpcrbm_gallery_image_url );?> " alt="<?php echo esc_attr( $mpcrbm_car_name )?> Gallery Image">
                                            <?php
                                        }
                                        $mpcrbm_counter++;
                                    }
                                    if ( count( $mpcrbm_all_image_urls ) > 4) { ?>
                                        <button class="mpcrbm_car_image_details mpcrbm_car_details_view_more"><?php esc_attr_e( 'View More', 'car-rental-manager' );?> →</button>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <div class="mpcrbm_car_details">
                            <!-- TABS -->
                            <div class="mpcrbm_car_details_tabs">
                                <button class="active" data-tab="description"><?php esc_attr_e( 'Description', 'car-rental-manager' );?></button>
                                <button data-tab="carinfo"><?php esc_attr_e( 'Car Info', 'car-rental-manager' );?></button>
                                <button data-tab="benefits" style="display: none"><?php esc_attr_e( 'Benefits', 'car-rental-manager' );?></button>
                                <?php if( $mpcrbm_show_feature_section === 'yes' ){?>
                                <button data-tab="include"><?php esc_attr_e( 'Include/Exclude', 'car-rental-manager' );?></button>
                                <?php } if( $mpcrbm_show_pickup_location_section === 'yes' ){ ?>
                                <button data-tab="location"><?php esc_attr_e( 'Location', 'car-rental-manager' );?></button>
                                <?php } if( $mpcrbm_show_review_section === 'yes' ){?>
                                <button data-tab="reviews"><?php esc_attr_e( 'Reviews', 'car-rental-manager' );?></button>
                                <?php } if( $mpcrbm_show_faq_section === 'yes' ){?>
                                <button data-tab="faq"><?php esc_attr_e( 'FAQ’s', 'car-rental-manager' );?></button>
                                <?php } if( $mpcrbm_show_term_condition === 'yes' ){?>
                                <button data-tab="terms"><?php esc_attr_e( 'Terms & Conditions', 'car-rental-manager' );?></button>
                                <?php }?>
                            </div>

                            <!-- TAB CONTENT -->
                            <div id="description" class="mpcrbm_car_details_tab_content active">
                                <?php if( $mpcrbm_car_description ){?>
                                    <p><?php echo esc_attr( wp_strip_all_tags( $mpcrbm_car_description ) );?></p>
                                <?php }?>
                            </div>

                            <div id="carinfo" class="mpcrbm_car_details_tab_content">
                                <h3><?php esc_attr_e('Car specification','car-rental-manager'); ?></h3>
                                <div class="divider"></div>
                                <div class="mpcrbm_car_details_info_grid">
                                    <div class="specification"><i class="mi mi-tachometer-fast"></i> <?php echo esc_attr( $mpcrbm_car_type );?></div>
                                    <div class="specification"><i class="mi mi-bonus"></i> <?php echo esc_attr( $mpcrbm_car_brand );?></div>
                                    <div class="specification"><i class="mi mi-person-seat"></i> <?php echo esc_attr( $mpcrbm_seating_capacity );?></div>
                                    <div class="specification"><i class="mi mi-person-luggage"></i> <?php echo esc_attr( $mpcrbm_maximum_bag );?> <?php esc_attr_e( 'Bags', 'car-rental-manager' );?></div>
                                    <div class="specification"><i class="mi mi-calendar"></i>  <?php echo esc_attr( $mpcrbm_make_year );?></div>
                                    <div class="specification"><i class="mi mi-infinity"></i> <?php esc_attr_e( 'Unlimited', 'car-rental-manager' );?></div>
                                    <div class="specification"><i class="mi mi-gas-pump-alt"></i> <?php echo esc_attr( $mpcrbm_fuel_type );?></div>

                                </div>
                            </div>

                            <div id="benefits" class="mpcrbm_car_details_tab_content" style="display: none">
                                <ul class="mpcrbm_car_details_benefit_list">
                                    <li>✅ <?php esc_attr_e( 'Most popular fuel policy', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( 'Short waiting times', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( 'Superior safety and durability', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( 'Convenient pick-up location', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( 'Free cancellation', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( '100% luxurious fleet', 'car-rental-manager' );?></li>
                                    <li>✅ <?php esc_attr_e( 'Pay at pickup option', 'car-rental-manager' );?></li>
                                </ul>
                            </div>
                            <?php if( $mpcrbm_show_feature_section === 'yes' ){?>
                            <div id="include" class="mpcrbm_car_details_tab_content">
                                <h3><?php esc_attr_e('Car Features','car-rental-manager'); ?></h3>
                                <div class="divider"></div>
                                <div class="mpcrbm_car_details_include_exclude">
                                    <div class="mpcrbm_car_details_include">
                                        <h4><?php esc_attr_e( 'Include Feature', 'car-rental-manager' );?></h4>
                                        <ul>
                                            <?php
                                            if( !empty( $mpcrbm_include_feature_names ) ){
                                                foreach ( $mpcrbm_include_feature_names as $mpcrbm_include_feature ){
                                                    ?>
                                                    <li><i class="mi mi-check"></i> <?php echo esc_attr( $mpcrbm_include_feature );?></li>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                    <div class="mpcrbm_car_details_exclude">
                                        <h4><?php esc_attr_e( 'Exclude Feature', 'car-rental-manager' );?></h4>
                                        <ul>
                                            <?php
                                            if( !empty( $mpcrbm_exclude_feature_names ) ){
                                                foreach ( $mpcrbm_exclude_feature_names as $mpcrbm_exclude_feature ){
                                                    ?>
                                                    <li><i class="mi mi-cross-small"></i> <?php echo esc_attr( $mpcrbm_exclude_feature );?></li>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php } if( $mpcrbm_show_pickup_location_section === 'yes' ){ ?>
                            <div id="location" class="mpcrbm_car_details_tab_content">
                                <h3><?php esc_attr_e('Pickup Location','car-rental-manager') ?></h3>
                                <div class="divider"></div>
                                <div class="mpcrbm_car_details_map_box">
                                    <iframe src="https://maps.google.com/maps?q=<?php echo esc_attr( $mpcrbm_map_location );?>&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>
                                </div>
                            </div>
                            <?php } if( $mpcrbm_show_review_section === 'yes' ){?>
                            <div id="reviews" class="mpcrbm_car_details_tab_content">
                                <h3><?php esc_attr_e('Reviews','car-rental-manager') ?></h3>
                                <div class="divider"></div>
                                <p><?php esc_attr_e( 'No reviews yet. Be the first to share your experience!', 'car-rental-manager' );?></p>
                            </div>
                            <?php } if( $mpcrbm_show_faq_section === 'yes' ){?>
                            <div id="faq" class="mpcrbm_car_details_tab_content mpcrbm_car_details_faq_section">
                                <h3><?php esc_attr_e( 'Frequently Asked Questions', 'car-rental-manager' );?></h3>
                                <div class="mpcrbm_car_details_divider"></div>
                                <div class="mpcrbm_car_details_faq_wrapper">
                                    <?php
                                    if( !empty( $mpcrbm_selected_faqs_data ) ){
                                        foreach ( $mpcrbm_selected_faqs_data as $mpcrbm_faq_data ){
                                            ?>
                                            <div class="mpcrbm_car_details_faq_item">
                                                <button class="mpcrbm_car_details_faq_question">
                                                    <span><?php echo esc_html( wp_strip_all_tags( $mpcrbm_faq_data['title'] ) )?></span>
                                                    <span class="mpcrbm_car_details_faq_icon">+</span>
                                                </button>
                                                <div class="mpcrbm_car_details_faq_answer">
                                                    <p><?php echo esc_html( wp_strip_all_tags( $mpcrbm_faq_data['answer'] ) )?></p>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php } if( $mpcrbm_show_term_condition === 'yes' ){?>
                            <div id="terms" class="mpcrbm_car_details_tab_content">
                                <?php if ( ! empty( $mpcrbm_selected_term_condition ) ) :
                                    ?>
                                    <div class="mpcrbm_car_details_conditions_section" id="tf-tc">
                                        <h3><?php esc_attr_e('Terms and Condition','car-rental-manager') ?></h3>
                                        <div class="divider"></div>

                                        <div class="mpcrbm_car_details_tc_wrapper">
                                            <?php
                                            foreach ( $mpcrbm_selected_term_condition as $mpcrbm_term_condition ){
                                                $mpcrbm_description = isset( $mpcrbm_term_condition['answer'] ) ? wp_strip_all_tags( $mpcrbm_term_condition['answer'] ) : '';
                                                ?>
                                                <div class="mpcrbm_car_details_tc_item">
                                                    <div class="mpcrbm_car_details_tc_title">
                                                        <?php echo esc_html( wp_strip_all_tags( $mpcrbm_term_condition['title'] ) )?>
                                                    </div>
                                                    <div class="mpcrbm_car_details_tc_description">
                                                        <?php echo esc_html( $mpcrbm_description );?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php }?>
                        </div>                    
                    </div>
                    <div class="mpcrbm_car_details_right">
                        <?php
                        $mpcrbm_booking_form = new MPCRBM_Shortcodes();


                        $mpcrbm_pricing_rule_data = MPCRBM_Function::display_pricing_rules( $mpcrbm_post_id );
                        $mpcrbm_is_discount = isset( $mpcrbm_pricing_rule_data['is_discount'] ) ? $mpcrbm_pricing_rule_data['is_discount'] : false;

                        ?>

                        <div class="mpcrbm_car_details_price_box">
                            <div class="mpcrbm-car-price-header">
                                <input type="hidden" name="mpcrbm_car_day_price" id="mpcrbm_car_day_price" value="<?php echo esc_attr( $mpcrbm_day_price );?>">
                                <input type="hidden" name="mpcrbm_car_day_wise_price" id="mpcrbm_car_day_wise_price" value="<?php echo esc_attr( $mpcrbm_price );?>">
                                <?php if( $mpcrbm_is_discount ){
                                    $mpcrbm_pricing_rules = isset( $mpcrbm_pricing_rule_data['pricing_rules'] ) ? $mpcrbm_pricing_rule_data['pricing_rules'] : '';
                                    ?>
                                    <div class="mpcrbm_car_price_holder" style="display: flex; justify-content: space-between">
                                        <div class="mpcrbm_price-breakdown mpcrbm_line_through"><?php echo wp_kses_post( wc_price($mpcrbm_price ).'/ '.esc_html__('Day','car-rental-manager') );?></div>
                                        <div class="mpcrbm_price_hover_wrap">
                                            <span class="mpcrbm_price_info">
                                                ℹ Price Rules
                                            </span>
                                            <div class=""><?php echo wp_kses_post( $mpcrbm_pricing_rules );?></div>
                                        </div>
                                    </div>
                                <?php }?>
                                <h3><?php esc_attr_e( 'Total', 'car-rental-manager' );?>: <span><?php echo wp_kses_post( wc_price( $mpcrbm_day_price ) ); ?></span> / <?php esc_attr_e( 'Day', 'car-rental-manager' );?></h3>

                                <p><?php esc_attr_e( 'Without Taxes', 'car-rental-manager' );?></p>
                            </div>
                            <?php
                            $mpcrbm_attribute = [
                                'progressbar'       => 'no',
                                'title'             => 'no',
                                'car_id'             => $mpcrbm_post_id,
                                'single_page'       => 'yes',
                                'pickup_location'   => $mpcrbm_start_place,
                            ];
                            $mpcrbm_booking_form->mpcrbm_single_page_car_booking( $mpcrbm_attribute, $mpcrbm_post_id );

//                            $extra_service_class = 'mpcrbm_extra_service_layout_details'; ?>

                            <div class="mpcrbm_car_quantity" id="mpcrbm_car_quantity_holder" data-collapse="<?php echo esc_attr($mpcrbm_post_id); ?>" style="display: flex; justify-content: space-between">
                                <div class="mpcrbm_car_quantity_title"><?php esc_html_e('Car Quantity', 'car-rental-manager') ?></div>
                                <?php
                                    MPCRBM_Custom_Layout::qty_input('mpcrbm_get_car_qty', $mpcrbm_day_price, $mpcrbm_available_stock, 1, 0);
                                ?>
                            </div>

                            <div class="mpcrbm_transport_summary" id="mpcrbm_car_summary" style="display: block">
                                <h3 ><?php esc_html_e(' Details', 'car-rental-manager') ?></h3>
                                <div class="divider"></div>
                                <div class="_textColor_4 justifyBetween book-items">
                                    <p class="_dFlex_alignCenter">
                                        <span class="fas fa-check-square _textTheme_mR_xs"></span>
                                        <span class="mpcrbm_product_name" id="mpcrbm_selected_car_name"><?php echo esc_attr( $mpcrbm_car_name );?></span>&nbsp;
                                        <span class="textTheme mpcrbm_car_qty_display">x1</span>

                                    </p>
                                    <p class="textTheme mpcrbm_car_day"><span id="mpcrbm_car_selected_day">1</span> x days</p>
                                    <p class="mpcrbm_product_price _textTheme" id="mpcrbm_selected_car_price"><?php echo wp_kses_post( wc_price( $mpcrbm_day_price ) );?></p>
                                </div>
                                <div class="mpcrbm_extra_service_summary"></div>
                                <div class="justifyBetween total">
                                    <h6><?php esc_html_e('Total : ', 'car-rental-manager'); ?></h6>
                                    <h3 class="mpcrbm_product_total_price" id="mpcrbm_car_total_price"><?php echo wp_kses_post( wc_price( $mpcrbm_day_price ) );?></h3>
                                </div>
                            </div>

                            <?php
                            // Get service data
                            include( MPCRBM_Function::template_path( 'registration/extra_service_display.php' ) );?>

                            <button style="display: <?php echo esc_attr( $mpcrbm_booking_btn_show );?>" data-car-id="<?php echo esc_attr( $mpcrbm_post_id );?>" data-wc_link_id="<?php echo esc_attr( $mpcrbm_link_wc_product );?>" class="mpcrbm_car_details_continue_btn" id="mpcrbm_car_details_continue_btn"><?php esc_attr_e( 'Continue', 'car-rental-manager' );?> →</button>
                            <div class="mpcrbm_already_booked" id="mpcrbm_car_already_booked" style="display: <?php echo esc_attr( $mpcrbm_is_already_booked );?>"><span class="">On this day the car is already booked, please select another day.</span></div>
                        </div>



                        <!-- DRIVER INFO -->
                        <?php
                        $mpcrbm_enable_driver_information    = MPCRBM_Global_Function::get_post_info( $mpcrbm_post_id, 'mpcrbm_enable_driver_information' );
                        if( $mpcrbm_enable_driver_information === 'on' ){
                            $mpcrbm_driver_image = 'https://img.freepik.com/premium-vector/driver-orange-uniform-worker-with-steering-wheel_176411-3181.jpg';
                            ?>
                        <div class="mpcrbm_car_details_driver_box">
                            <h3><?php esc_attr_e( 'Driver details', 'car-rental-manager' );?></h3>
                            <div class="divider"></div>
                            <div class="driver-data">
                                <div class="driver-picuture">
                                    <img src="<?php echo esc_url( $mpcrbm_driver_image );?>" alt="<?php echo isset( $mpcrbm_driver_info['name'] ) ? esc_attr( $mpcrbm_driver_info['name'] ) : ''; ?>">
                                    <span class="verified"><i class="mi mi-badge-check"></i> <?php esc_attr_e( 'Verified', 'car-rental-manager' );?></span>
                                </div>
                                <div class="driver-info">
                                    <div>
                                        <?php esc_attr_e( 'Name:','car-rental-manager' ); ?>
                                        <?php echo isset( $mpcrbm_driver_info['name'] ) ? esc_attr( $mpcrbm_driver_info['name'] ) : ''; ?>
                                    </div>
                                    <?php if( isset( $mpcrbm_driver_info['age'] ) ){?>
                                    <div>
                                        <?php esc_attr_e( 'Age:', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['age'] ); ?>
                                    </div>
                                    <?php }?>
                                    <?php if( isset( $mpcrbm_driver_info['phone'] ) ){?>
                                    <div>
                                        <?php esc_attr_e( 'Phone: ', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['phone'] );?>
                                        
                                    </div>
                                    <?php }?>
                                    <?php if( isset( $mpcrbm_driver_info['email'] ) ){?>
                                    <div>
                                        <?php  esc_attr_e( 'Email:', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['email'] ); ?>
                                    </div>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <?php }?>

                        <!-- RENTER INFO -->
                        <div class="mpcrbm_car_details_driver_box" style="display: none">
                            <h3><?php esc_attr_e( 'Renter details', 'car-rental-manager' );?></h3>
                            <div class="divider"></div>
                            <div class="driver-data">
                                <div class="driver-picuture">
                                    <img src="" alt="">
                                </div>
                                <div class="driver-info">
                                    <div>
                                        <?php esc_attr_e( 'Name:','car-rental-manager' ); ?>
                                        <?php echo isset( $mpcrbm_driver_info['name'] ) ? esc_attr( $mpcrbm_driver_info['name'] ) : ''; ?>
                                    </div>
                                    <?php if( isset( $mpcrbm_driver_info['age'] ) ){?>
                                    <div>
                                        <?php esc_attr_e( 'Age:', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['age'] ); ?>
                                    </div>
                                    <?php }?>
                                    <?php if( isset( $mpcrbm_driver_info['phone'] ) ){?>
                                    <div>
                                        <?php esc_attr_e( 'Phone: ', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['phone'] );?>
                                        
                                    </div>
                                    <?php }?>
                                    <?php if( isset( $mpcrbm_driver_info['email'] ) ){?>
                                    <div>
                                        <?php  esc_attr_e( 'Email:', 'car-rental-manager' ); echo esc_attr( $mpcrbm_driver_info['email'] ); ?>
                                    </div>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
