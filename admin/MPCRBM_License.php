<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	/*
	* @Author 		engr.sumonazma@gmail.com
	* Copyright: 	mage-people.com
	*/
	if ( ! class_exists( 'MPCRBM_License' ) ) {
		class MPCRBM_License {
			public function __construct() {
				add_action( 'mpcrbm_license_page_plugin_list', [ $this, 'licence' ], 50 );
			}

			public function licence() {
				?>
                <tr>
                    <th colspan="4" class="_textLeft"><?php echo esc_html__( 'Car Rental Manager', 'car-rental-manager' ); ?></th>
                    <th><?php esc_html_e( 'Free', 'car-rental-manager' ); ?></th>
                    <th></th>
                    <th colspan="2"><?php esc_html_e( 'Unlimited', 'car-rental-manager' ); ?></th>
                    <th colspan="3"><?php esc_html_e( 'No Need', 'car-rental-manager' ); ?></th>
                    <th class="textSuccess"><?php esc_html_e( 'Active', 'car-rental-manager' ); ?></th>
                    <td colspan="2"></td>
                </tr>
				<?php
				do_action( 'mpcrbm_addon_list' );
			}
		}
		new MPCRBM_License();
	}