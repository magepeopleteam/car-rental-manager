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

                    <div class="mpcrbm_booking_summary_description">

                        <div class="mpcrbm_summary_show">
                            <span class=""><?php esc_html_e('Pickup Location', 'car-rental-manager'); ?></span>
                            <?php if($price_based == 'manual'){ ?>
                                <p class="_textLight_1 mpcrbm_manual_start_place"><i class="fas fa-map-marker-alt mpcrbm_location_icon_margin"></i><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug( $start_place,'mpcrbm_locations' )); ?></p>
                            <?php }else{ ?>
                                <p class="_textLight_1 mpcrbm_map_start_place"><i class="fas fa-map-marker-alt mpcrbm_location_icon_margin"></i><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_place, 'mpcrbm_locations')); ?></p>
                            <?php } ?>
                        </div>

                        <div class="mpcrbm_summary_show">
                            <span class=""><?php esc_html_e('Pickup Date & Time', 'car-rental-manager'); ?></span>
                            <p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($date)) .'.'. esc_html(MPCRBM_Global_Function::date_format($date, 'time')); ?></p>
                        </div>


                       <!-- <div class="mpcrbm_summary_show">
                            <span class=""><?php /*esc_html_e('Return Location', 'car-rental-manager'); */?></span>
                            <?php /*if($price_based == 'manual'){ */?>
                                <p class="_textLight_1 mpcrbm_map_end_place"><?php /*echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug( $end_place,'mpcrbm_locations' )); */?></p>
                            <?php /*}else{ */?>
                                <p class="_textLight_1 mpcrbm_map_end_place"><?php /*echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($end_place, 'mpcrbm_locations')); */?></p>
                            <?php /*} */?>
                        </div>-->

                        <?php if($two_way>1){
                            ?>
                            <?php if(!empty($return_date_time)){ ?>
                            <div class="mpcrbm_summary_show">
                                <span class=""><?php esc_html_e('Return Date & Time', 'car-rental-manager'); ?></span>
                                <p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($return_date_time)) .'.'.esc_html(MPCRBM_Global_Function::date_format($return_date_time,'time')) ?></p>
                            </div>
                            <?php } ?>
                        <?php } ?>

                        <div class="mpcrbm_summary_show">
                            <div class="mpcrbm_duration-highlight">
                                <div class="mpcrbm_duration-days"><?php echo esc_attr( $minutes_to_day );?> <?php esc_html_e('Days', 'car-rental-manager'); ?></div>
                                <div class="mpcrbm_duration-label"><?php esc_html_e('Rental Period', 'car-rental-manager'); ?></div>
                            </div>
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
