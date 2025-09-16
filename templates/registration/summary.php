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
	<div class="leftSidebar">
		<div class="">
			<div class="sticky_on_scroll">
				<div class="mpcrbm_dFlex_fdColumn_btLight">
<!--					<h3>--><?php //esc_html_e('SUMMARY', 'car-rental-manager'); ?><!--</h3>-->
                    <div class="mpcrbm_summary_title">
                        📋 Booking Summary
                    </div>
					<div class="dividerL"></div>

					<h6 class="_mB_xs"><?php esc_html_e('Pickup Date', 'car-rental-manager'); ?></h6>
					<p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($date)); ?></p>
					<div class="dividerL"></div>
					<h6 class="_mB_xs"><?php esc_html_e('Pickup Time', 'car-rental-manager'); ?></h6>
					<p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($date, 'time')); ?></p>
					<div class="dividerL"></div>
					<h6 class="_mB_xs"><?php esc_html_e('Pickup Location', 'car-rental-manager'); ?></h6>
					<?php if($price_based == 'manual'){ ?>
						<p class="_textLight_1 mpcrbm_manual_start_place"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug( $start_place,'mpcrbm_locations' )); ?></p>
					<?php }else{ ?>
						<p class="_textLight_1 mpcrbm_map_start_place"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($start_place, 'mpcrbm_locations')); ?></p>
					<?php } ?>
					<div class="dividerL"></div>
					<h6 class="_mB_xs"><?php esc_html_e('Return Location', 'car-rental-manager'); ?></h6>
					<?php if($price_based == 'manual'){ ?>
						<p class="_textLight_1 mpcrbm_map_end_place"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug( $end_place,'mpcrbm_locations' )); ?></p>
					<?php }else{ ?>
						<p class="_textLight_1 mpcrbm_map_end_place"><?php echo esc_html(MPCRBM_Function::get_taxonomy_name_by_slug($end_place, 'mpcrbm_locations')); ?></p>
					<?php } ?>
					
					<?php if($two_way>1){ 
						?>
						<div class="dividerL"></div>
						<?php if(!empty($return_date_time)){ ?>
                            <h6 class="_mB_xs"><?php esc_html_e('Return Date', 'car-rental-manager'); ?></h6>
                            <p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($return_date_time)); ?></p>
                            <div class="dividerL"></div>
                            <h6 class="_mB_xs"><?php esc_html_e('Return Time', 'car-rental-manager'); ?></h6>
                            <p class="_textLight_1"><?php echo esc_html(MPCRBM_Global_Function::date_format($return_date_time,'time')); ?></p>
                        <?php } ?>
					<?php } ?>

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
							<span class="mpcrbm_product_price _textTheme"></span>
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
                if( $is_redirect === 'no' ){?>
				<button type="button" class="mpcrbm_next_button mpcrbm_get_vehicle_prev">
					<span>&longleftarrow; &nbsp;<?php esc_html_e('Previous', 'car-rental-manager'); ?></span>
				</button>
                <?php }?>
			</div>
		</div>
	</div>
<?php
