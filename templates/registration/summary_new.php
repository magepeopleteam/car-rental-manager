<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly
$label = $label ?? MPCRBM_Function::get_name();
$date = $date ?? '';
$start_place = $start_place ?? '';
$end_place = $end_place ?? '';
$two_way = $two_way ?? 1;
$return_date_time = $return_date_time ?? '';
$price_based = $price_based ?? '';

?>
    <div class="mpcrbm_leftSidebar">
        <div class="">
            <div class="sticky_on_scroll">
                <div class="mpcrbm_dFlex_fdColumn_btLight">
                    <div class="mpcrbm_summary_title">
                        📋 <?php esc_html_e('Booking Summary', 'car-rental-manager'); ?>
                    </div>
                    <div class="dividerL"></div>

                    <div class="mpcrbm_duration-highlight">
                        <div class="mpcrbm_duration-days"><?php echo esc_attr( $minutes_to_day );?> <?php esc_html_e('Days', 'car-rental-manager'); ?></div>
                        <div class="mpcrbm_duration-label"><?php esc_html_e('Rental Period', 'car-rental-manager'); ?></div>
                    </div>

                    <div class="mpcrbm_transport_summary">
                        <div class="dividerL"></div>
                        <h6 class="_mB_xs"><?php echo esc_html($label) . ' ' . esc_html__(' Details', 'car-rental-manager') ?></h6>
                        <div class="_textColor_4 justifyBetween">
                            <div class="_dFlex_alignCenter">
                                <span class="fas fa-check-square _textTheme_mR_xs"></span>
                                <span class="mpcrbm_product_name"></span>
                            </div>
                            <span class="textTheme mpcrbm_car_qty_display">x1</span><span class="mpcrbm_product_price _textTheme"></span>
                        </div>
                        <div class="mpcrbm_extra_service_summary"></div>
                        <div class="dividerL"></div>
                        <div class="justifyBetween">
                            <h4><?php esc_html_e('Total : ', 'car-rental-manager'); ?></h4>
                            <h6 class="mpcrbm_product_total_price"></h6>
                        </div>
                    </div>
                </div>
                <!--				<div class="divider"></div>-->
                <?php
                if( $is_redirect === 'no' ){
                    if( $ajax_search !== 'yes' ){ ?>
                        <button type="button" class="mpcrbm_next_button mpcrbm_get_vehicle_prev">
                            <span>&longleftarrow; &nbsp;<?php esc_html_e('Previous', 'car-rental-manager'); ?></span>
                        </button>
                    <?php }
                }?>
            </div>
        </div>
    </div>
<?php
