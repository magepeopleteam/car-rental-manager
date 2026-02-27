<?php
	/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$progressbar = $progressbar ?? 'yes';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$progressbar_class = $progressbar == 'yes' ? '' : 'dNone';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    $search_page_slug = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    $redirect = 'yes';
    if( $search_page_slug === '' ){
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        $redirect = 'no';
    }
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    $post_id = isset( $post_id )  ? $post_id  : '';
?>
	<div class="mpcrbm mpcrbm_transport_search_area">
		<div class="mpcrbm_tab_next _mT">

            <input type="hidden" name="mpcrbm_progress_bar_display" id="mpcrbm_progress_bar_display" value="<?php echo esc_attr( $progressbar ); ?>">
            <input type="hidden" name="mpcrbm_redirect_another_page" id="mpcrbm_redirect_another_page" value="<?php echo esc_attr( $redirect ); ?>">

			<div class="tabListsNext <?php echo esc_attr($progressbar_class); ?>" id="mpcrbm_progress_bar_holder" style="display: none">
				<div data-tabs-target-next="#mpcrbm_pick_up_details" class="tabItemNext active" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
					<h4 class="circleIcon" data-class>
						<span class="mp_zero" data-icon></span>
						<span class="mp_zero" data-text>1</span>
					</h4>
					<h6 class="circleTitle" data-class><?php esc_html_e('Enter Ride Details', 'car-rental-manager'); ?></h6>
				</div>
				<div data-tabs-target-next="#mpcrbm_search_result" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
					<h4 class="circleIcon" data-class>
						<span class="mp_zero" data-icon></span>
						<span class="mp_zero" data-text>2</span>
					</h4>
					<h6 class="circleTitle" data-class><?php esc_html_e('Choose a vehicle', 'car-rental-manager'); ?></h6>
				</div>
				<div data-tabs-target-next="#mpcrbm_order_summary" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
					<h4 class="circleIcon" data-class>
						<span class="mp_zero" data-icon></span>
						<span class="mp_zero" data-text>3</span>
					</h4>
					<h6 class="circleTitle" data-class><?php esc_html_e('Place Order', 'car-rental-manager'); ?></h6>
				</div>
			</div>

			<div class="tabsContentNext">
				<div data-tabs-next="#mpcrbm_pick_up_details" class="active mpcrbm_pick_up_details">
					<?php
                    if( $post_id ){
                        include MPCRBM_Function::template_path('registration/single_car_search_details.php');
                    }else{
                        include MPCRBM_Function::template_path('registration/get_details_new.php');
                    }
                    ?>
				</div>
			</div>
		</div>
	</div>
<?php
