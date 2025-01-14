<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly

$date = $date ?? '';
$start_date_time = $date;
$return_date_time = $return_date_time ?? '';

$post_id = $post_id ?? '';
$original_price_based = $price_based ?? '';
if (MP_Global_Function::get_settings('mptbm_general_settings', 'enable_filter_via_features') == 'yes') {
    $max_passenger = MP_Global_Function::get_post_info($post_id, 'mptbm_maximum_passenger');
    $max_bag = MP_Global_Function::get_post_info($post_id, 'mptbm_maximum_bag');
    if ($max_passenger != '' && $max_bag != '') {
        $feature_class = 'feature_passenger_'.$max_passenger.'_feature_bag_'.$max_bag.'_post_id_'.$post_id;
    }else{
        $feature_class = '';
    }
}

$fixed_time = $fixed_time ?? 0;
$start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
$start_date = $start_date ? date('Y-m-d', strtotime($start_date)) : '';
$all_dates = MPTBM_Function::get_date($post_id);
$mptbm_enable_view_search_result_page  = MP_Global_Function::get_settings('mptbm_general_settings', 'enable_view_search_result_page');
if ($mptbm_enable_view_search_result_page == '') {
    $hidden_class = 'mptbm_booking_item_hidden';
} else {
    $hidden_class = '';
}
if (sizeof($all_dates) > 0 && in_array($start_date, $all_dates)) {
   
    $label = $label ?? MPTBM_Function::get_name();
    $start_place = $start_place ?? isset($_POST['start_place']) ? sanitize_text_field($_POST['start_place']) : '';
    $end_place = $end_place ?? isset($_POST['end_place']) ? sanitize_text_field($_POST['end_place']) : '';
    $two_way = $two_way ?? 1;
    
   
    if ( $post_id) {
        
        //$product_id = MP_Global_Function::get_post_info($post_id, 'link_wc_product');
        $thumbnail = MP_Global_Function::get_image_url($post_id);
        $price = MPTBM_Function::get_price($post_id,  $start_place, $end_place , $start_date_time,$return_date_time);
       
        if(!$price || $price == 0){
            return false;
        }
        $wc_price = MP_Global_Function::wc_price($post_id, $price);
        $raw_price = MP_Global_Function::price_convert_raw($wc_price);
        $display_features = MP_Global_Function::get_post_info($post_id, 'display_mptbm_features', 'on');
        $all_features = MP_Global_Function::get_post_info($post_id, 'mptbm_features');
        
?>
        <div class="_dLayout_dFlex mptbm_booking_item <?php echo esc_attr( 'mptbm_booking_item_' . $post_id ); ?> <?php echo esc_attr( $hidden_class ); ?> <?php echo esc_attr( $feature_class ); ?>" data-placeholder>
            <div class="_max_200_mR">
                <div class="bg_image_area"  data-placeholder>
                    <div data-bg-image="<?php echo esc_attr($thumbnail); ?>"></div>
                </div>
            </div>
            <div class="fdColumn _fullWidth mptbm_list_details">
                <h5><?php echo esc_html(get_the_title($post_id)); ?></h5>
                <div class="justifyBetween _mT_xs">
                    <?php if ($display_features == 'on' && is_array($all_features) && sizeof($all_features) > 0) { ?>
                        <ul class="list_inline_two">
                            <?php
                            foreach ($all_features as $features) {
                                $label = array_key_exists('label', $features) ? $features['label'] : '';
                                $text = array_key_exists('text', $features) ? $features['text'] : '';
                                $icon = array_key_exists('icon', $features) ? $features['icon'] : '';
                                $image = array_key_exists('image', $features) ? $features['image'] : '';
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
                        <h4 class="textCenter"> <?php echo wp_kses_post(wc_price($raw_price)); ?></h4>
                        <button type="button" class="_mpBtn_xs_w_150 mptbm_transport_select" data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-transport-price="<?php echo esc_attr($raw_price); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-open-text="<?php esc_attr_e('Select Car', 'wpcarrently'); ?>" data-close-text="<?php esc_html_e('Selected', 'wpcarrently'); ?>" data-open-icon="" data-close-icon="fas fa-check mR_xs">
                            <span class="" data-icon></span>
                            <span data-text><?php esc_html_e('Select Car', 'wpcarrently'); ?></span>
                        </button>
                    </div>
                </div>
                <!-- poro feature used this hook for showing driver's data -->
                <?php do_action('mptbm_booking_item_after_feature',$post_id); ?>
            </div>
        </div>
<?php
    }
}
?>