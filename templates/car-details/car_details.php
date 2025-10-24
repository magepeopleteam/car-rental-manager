<?php
// Template Name: Default Theme
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$post_id = $post_id ?? get_the_id();
$thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');

$include_features = get_post_meta( $post_id, 'mpcrbm_include_features', true );
$include_feature_names = [];
if( is_array( $include_features ) && !empty( $include_features ) ){
    foreach ($include_features as $term_id) {
        $term_name = MPCRBM_Function::get_taxonomy_name_by_id( $term_id, 'mpcrbm_car_feature' );
        if ( $term_name ) {
            $include_feature_names[] = $term_name;
        }
    }
}

$exclude_features = get_post_meta( $post_id, 'mpcrbm_exclude_features', true );
$exclude_feature_names = [];
if( is_array( $exclude_features ) && !empty( $exclude_features ) ){
    foreach ($exclude_features as $term_id) {
        $term_name = MPCRBM_Function::get_taxonomy_name_by_id( $term_id, 'mpcrbm_car_feature' );
        if ( $term_name ) {
            $exclude_feature_names[] = $term_name;
        }
    }
}

$faqs = get_option( 'mpcrbm_faq_list', [] );
$added_faqs = get_post_meta( $post_id, 'mpcrbm_added_faq', true );
$selected_faqs_data = [];
if (!empty($added_faqs) && !empty( $faqs ) ) {
    foreach ($added_faqs as $faq_key) {
        if (isset($faqs[$faq_key])) {
            $selected_faqs_data[$faq_key] = $faqs[$faq_key];
        }
    }
}

$all_term_condition = get_option( 'mpcrbm_term_condition_list', [] );
$added_term_condition = get_post_meta( $post_id, 'mpcrbm_term_condition_list', true );
$selected_term_condition = [];
if (!empty($added_term_condition) && !empty( $all_term_condition ) ) {
    foreach ($added_term_condition as $faq_key) {
        if (isset($all_term_condition[$faq_key])) {
            $selected_term_condition[$faq_key] = $all_term_condition[$faq_key];
        }
    }
}

//$daily_price = get_post_meta( $post_id, 'mpcrbm_base_daily_price', true );
$day_price = get_post_meta( $post_id, 'mpcrbm_day_price', true );
$extra_service = get_post_meta( $post_id, 'mpcrbm_extra_service_infos', true );
$price_based = get_post_meta( $post_id, 'mpcrbm_price_based', true );
$link_wc_product = get_post_meta( $post_id, 'link_wc_product', true );
$display_faq = get_post_meta( $post_id, 'mpcrbm_display_faq', true );

$nable_seasonal = get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );
$seasonal_pricing = get_post_meta( $post_id, 'mpcrbm_seasonal_pricing', true );
$enable_day_wise = get_post_meta( $post_id, 'mpcrbm_enable_day_wise_discount', true );
$day_wise_pricing = get_post_meta( $post_id, 'mpcrbm_daywise_pricing', true );
$tiered_discounts = get_post_meta( $post_id, 'mpcrbm_tiered_discounts', true );
$location_prices = get_post_meta( $post_id, 'mpcrbm_location_prices', true );


$make_year = get_post_meta( $post_id, 'mpcrbm_make_year', true );
$make_year = !empty($make_year) ? $make_year[0] : '';

$car_brand = get_post_meta( $post_id, 'mpcrbm_car_brand', true );
$car_brand = !empty($car_brand) ? $car_brand[0] : '';

$seating_capacity = get_post_meta( $post_id, 'mpcrbm_seating_capacity', true );
$seating_capacity = !empty($seating_capacity) ? $seating_capacity[0] : '';

$fuel_type = get_post_meta( $post_id, 'mpcrbm_fuel_type', true );
$fuel_type = !empty($fuel_type) ? $fuel_type[0] : '';

$car_type = get_post_meta( $post_id, 'mpcrbm_car_type', true );
$car_type = !empty($car_type) ? $car_type[0] : '';

$maximum_bag = get_post_meta( $post_id, 'mpcrbm_maximum_bag', true );
$maximum_bag = !empty($maximum_bag) ? $maximum_bag : '';


$off_dates = get_post_meta( $post_id, 'mpcrbm_off_dates', true );
$off_dates_str = '';
if( is_array( $off_dates ) && !empty( $off_dates ) ){
    $off_dates_str = implode( ',' , $off_dates);
}
$off_days = get_post_meta( $post_id, 'mpcrbm_off_days', true );

//error_log( print_r( [ '$location_prices' => $location_prices ], true ) );


$gallery_images = get_post_meta( $post_id, 'mpcrbm_gallery_images', true );
$gallery_image_urls = [];
if (!empty($gallery_images) && is_array($gallery_images)) {
    $gallery_image_urls = array_map(function ( $id ) {
        return wp_get_attachment_url( $id );
    }, $gallery_images );
}
$all_image_urls = $gallery_image_urls;
if( $thumbnail_url ){
    array_push( $all_image_urls, $thumbnail_url );
}

$car_name = get_the_title( $post_id );
$car_description = get_the_content( $post_id );

$date = date('Y-m-d') . ' 10:00';
$start_place = $end_place = isset( $location_prices[0]['pickup_location'] ) ? $location_prices[0]['pickup_location'] : '';
$return_date = date('Y-m-d', strtotime('+1 day'));
$start_time = $return_time = 10.00;
$two_way = 2;

$return_date_time = $return_date. ' 10:00';

$discount_price = MPCRBM_Function::calculate_multi_location_price( $post_id, $start_place, $end_place, $date, $return_date_time );

?>
<div class="mpcrbm_car_details">
    <input type="hidden" name="mpcrbm_post_id" value="<?php echo esc_attr( $post_id );?>" data-price="" />
    <input type="hidden" name="mpcrbm_start_place" value="<?php echo esc_attr($start_place); ?>" />
    <input type="hidden" name="mpcrbm_end_place" value="<?php echo esc_attr($end_place); ?>" />
    <input type="hidden" name="mpcrbm_date" value="<?php echo esc_attr($date); ?>" />
    <input type="hidden" name="mpcrbm_start_time" id="mpcrbm_start_time" value="<?php echo esc_attr($start_time); ?>" />
    <input type="hidden" name="mpcrbm_taxi_return" value="<?php echo esc_attr($two_way); ?>" />

    <input type="hidden" name="mpcrbm_map_return_date" id="mpcrbm_map_return_date" value="<?php echo esc_attr($return_date); ?>" />
    <input type="hidden" name="mpcrbm_map_return_time" id="mpcrbm_map_return_time" value="<?php echo esc_attr($return_time); ?>" />

    <input type="hidden" id="mpcrbm_selected_car_quantity" name="mpcrbm_selected_car_quantity"  value="1" />

    <input type="hidden" id="mpcrbm_off_days" name="mpcrbm_car_off_days"  value="<?php echo esc_attr( $off_days );?>" />
    <input type="hidden" id="mpcrbm_off_dates" name="mpcrbm_car_off_dates"  value="<?php echo esc_attr( $off_dates_str );?>" />

    <div class="mpcrbm_gallery_image_popup_wrapper">
        <div class="mpcrbm_gallery_image_popup_overlay"></div>
        <div class="mpcrbm_gallery_image_popup_content">
            <div class="" style="display: block; float: right">
                <button class="mpcrbm_gallery_image_popup_close">✕</button>
            </div>
            <div class="mpcrbm_gallery_image_popup_container">
                <?php foreach ( $all_image_urls as $index => $img_url): ?>
                    <img src="<?php echo esc_url($img_url); ?>"
                         class="mpcrbm_gallery_image_popup_item <?php echo $index === 0 ? 'active' : ''; ?>"
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
            <?php do_action( 'mpcrbm_transport_search_form',$post_id ); ?>
            <div class="mpcrbm_car_details_wrapper">
                <h1 ><?php echo $car_name;?></h1>
                <div class="mpcrbm_car_details_container">
                    <div class="mpcrbm_car_details_left">
                        <div class="mpcrbm_car_details_images">
                            <div class="mpcrbm_car_details_feature_image">
                                <?php if( $thumbnail_url ){?>
                                    <img class="mpcrbm_car_image_details" id="mpcrbm_car_details_feature_image" src="<?php echo esc_attr( $thumbnail_url );?>" alt="<?php echo esc_attr( $car_name );?>">
                                <?php }?>
                            </div>
                            <?php
                            if (!empty( $gallery_images ) && is_array( $gallery_images ) ) { ?>
                                <div class="mpcrbm_car_details_gallery">
                                    <?php
                                    $counter = 0;

                                    foreach ( $gallery_image_urls as $gallery_image_url ) {
                                        if ( !$gallery_image_url ) continue;
                                        if ( $counter < 4 ) { ?>
                                            <img class="mpcrbm_gallery_image" src=" <?php echo esc_url( $gallery_image_url );?> " alt="<?php echo esc_attr( $car_name )?> Gallery Image">
                                            <?php
                                        }
                                        $counter++;
                                    }
                                    if ( count( $all_image_urls ) > 4) { ?>
                                        <button class="mpcrbm_car_image_details mpcrbm_car_details_view_more"><?php esc_attr_e( 'View More', 'car-rental-manager' );?> →</button>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <!-- TABS -->
                        <div class="mpcrbm_car_details_tabs">
                            <button class="active" data-tab="description"><?php esc_attr_e( 'Description', 'car-rental-manager' );?></button>
                            <button data-tab="carinfo"><?php esc_attr_e( 'Car Info', 'car-rental-manager' );?></button>
                            <button data-tab="benefits" style="display: none"><?php esc_attr_e( 'Benefits', 'car-rental-manager' );?></button>
                            <button data-tab="include"><?php esc_attr_e( 'Include/Exclude', 'car-rental-manager' );?></button>
                            <button data-tab="location"><?php esc_attr_e( 'Location', 'car-rental-manager' );?></button>
                            <button data-tab="reviews"><?php esc_attr_e( 'Reviews', 'car-rental-manager' );?></button>
                            <button data-tab="faq"><?php esc_attr_e( 'FAQ’s', 'car-rental-manager' );?></button>
                            <button data-tab="terms"><?php esc_attr_e( 'Terms & Conditions', 'car-rental-manager' );?></button>
                        </div>

                        <!-- TAB CONTENT -->
                        <div id="description" class="mpcrbm_car_details_tab_content active">
                            <?php if( $car_description ){?>
                                <p><?php echo wp_strip_all_tags( $car_description );?></p>
                            <?php }?>
                        </div>

                        <div id="carinfo" class="mpcrbm_car_details_tab_content">
                            <div class="mpcrbm_car_details_info_grid">
                                <div class="sss"><i class="mi mi-tachometer-fast"></i> <?php echo esc_attr( $car_type );?></div>
                                <div class="sss"><i class="mi mi-bonus"></i> <?php echo esc_attr( $car_brand );?></div>
                                <div class="sss">👤 <?php echo esc_attr( $seating_capacity );?> <?php esc_attr_e( 'Persons', 'car-rental-manager' );?></div>
                                <div class="sss">🧳 <?php echo esc_attr( $maximum_bag );?> <?php esc_attr_e( 'Bags', 'car-rental-manager' );?></div>
                                <div class="sss">📅 <?php echo esc_attr( $make_year );?></div>
                                <div class="sss">∞ <?php esc_attr_e( 'Unlimited', 'car-rental-manager' );?></div>
                                <div class="sss">⛽ <?php echo esc_attr( $fuel_type );?></div>

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

                        <div id="include" class="mpcrbm_car_details_tab_content">
                            <div class="mpcrbm_car_details_include_exclude">
                                <div class="mpcrbm_car_details_include">
                                    <h4><?php esc_attr_e( 'Include Feature', 'car-rental-manager' );?></h4>
                                    <ul>
                                        <?php
                                        if( !empty( $include_feature_names ) ){
                                            foreach ( $include_feature_names as $include_feature ){
                                                ?>
                                                <li>✅ <?php echo esc_attr( $include_feature );?></li>
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
                                        if( !empty( $exclude_feature_names ) ){
                                            foreach ( $exclude_feature_names as $exclude_feature ){
                                                ?>
                                                <li>❌ <?php echo esc_attr( $exclude_feature );?></li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="location" class="mpcrbm_car_details_tab_content">
                            <div class="mpcrbm_car_details_map_box">
                                <iframe src="https://maps.google.com/maps?q=<?php echo esc_attr( $start_place );?>&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>
                            </div>
                        </div>

                        <div id="reviews" class="mpcrbm_car_details_tab_content">
                            <p><?php esc_attr_e( 'No reviews yet. Be the first to share your experience!', 'car-rental-manager' );?></p>
                        </div>

                        <div id="faq" class="mpcrbm_car_details_tab_content">
                            <h4><?php esc_attr_e( 'Frequently Asked Questions', 'car-rental-manager' );?></h4>
                            <?php
                            if( !empty( $selected_faqs_data ) ){
                                foreach ( $selected_faqs_data as $faq_data  ){
                                    ?>
                                    <p><strong>Q:</strong> <?php echo wp_strip_all_tags( $faq_data['title'] )?></p>
                                    <p><strong>A:</strong> <?php echo wp_strip_all_tags(  $faq_data['answer'] )?></p>
                                <?php }
                            }
                            ?>
                        </div>

                        <div id="terms" class="mpcrbm_car_details_tab_content">
                            <?php if ( ! empty( $selected_term_condition ) ) : ?>
                                <div class="tf-car-conditions-section" id="tf-tc">
                                    <h3><?php esc_attr_e( 'Tour Terms &amp; Conditions', 'car-rental-manager' );?></h3>
                                    <table class="mpcrbm_car_details_table">
                                        <tbody>
                                        <?php foreach ( $selected_term_condition as $term_condition ){?>
                                            <tr>
                                                <th><?php echo wp_strip_all_tags( $term_condition['title'] )?></th>
                                                <td><?php echo wp_strip_all_tags( $term_condition['answer'] )?></td>
                                            </tr>
                                        <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="mpcrbm_car_details_right">
                        <?php
                        $mpcrbm_booking_form = new MPCRBM_Shortcodes();

                        ?>

                        <div class="mpcrbm_car_details_price_box">
                            <h3><?php esc_attr_e( 'Total', 'car-rental-manager' );?>: <span><?php echo wp_kses_post( wc_price( $day_price ) ); ?></span> / <?php esc_attr_e( 'Day', 'car-rental-manager' );?></h3>
                            <p><?php esc_attr_e( 'Without Taxes', 'car-rental-manager' );?></p>

                            <?php
                            $attribute = [
                                'progressbar'       => 'no',
                                'title'             => 'no',
                                'single_page'       => 'yes',
                                'pickup_location'   => $start_place,
                            ];
                            echo $mpcrbm_booking_form->mpcrbm_booking( $attribute );

                            $extra_service_class = 'mpcrbm_extra_service_layout_details';

                            // Get service data
                            include( MPCRBM_Function::template_path( 'registration/extra_service_display.php' ) );?>

                            <button data-car-id="<?php echo esc_attr( $post_id );?>" data-wc_link_id="<?php echo esc_attr( $link_wc_product );?>" class="mpcrbm_car_details_continue_btn"><?php esc_attr_e( 'Continue', 'car-rental-manager' );?> →</button>

                        </div>

                        <!-- DRIVER INFO -->
                        <div class="mpcrbm_car_details_driver_box">
                            <h4><?php esc_attr_e( 'Driver details', 'car-rental-manager' );?> <span class="verified">✔ <?php esc_attr_e( 'Verified', 'car-rental-manager' );?></span></h4>
                            <p><strong><?php esc_attr_e( 'Abdullah Khan', 'car-rental-manager' );?></strong></p>
                            <p><?php esc_attr_e( 'Age 24 Years', 'car-rental-manager' );?></p>
                        </div>

                        <!-- RENTER INFO -->
                        <div class="mpcrbm_car_details_renter_box">
                            <h4><?php esc_attr_e( 'Renters Information', 'car-rental-manager' );?></h4>
                            <p><strong><?php esc_attr_e( 'Shelley Mcconnell', 'car-rental-manager' );?></strong></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
