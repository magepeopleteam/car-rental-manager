<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPTBM_License')) {
		class MPTBM_License {
			public function __construct() {
				add_action('mp_license_page_plugin_list', [$this, 'tour_licence'], 50);
			}
			public function tour_licence() {
				?>
				<tr>
					<th colspan="4" class="_textLeft"><?php echo esc_html('car-rental-manager Car Rental Manager','car-rental-manager'); ?></th>
					<th><?php esc_html_e('Free','car-rental-manager'); ?></th>
					<th></th>
					<th colspan="2"><?php esc_html_e('Unlimited','car-rental-manager'); ?></th>
					<th colspan="3"><?php esc_html_e('No Need','car-rental-manager'); ?></th>
					<th class="textSuccess"><?php esc_html_e('Active','car-rental-manager'); ?></th>
					<td colspan="2"></td>
				</tr>
				<?php
				do_action('mptbm_addon_list');
			}
		}
		new MPTBM_License();
	}