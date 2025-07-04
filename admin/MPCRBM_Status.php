<?php
	/*
   * @Author 		MagePeople Team
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Status' ) ) {
		class MPCRBM_Status {
			public function __construct() {
				add_action( 'admin_menu', array( $this, 'status_menu' ) );
			}

			public function status_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page( 'edit.php?post_type=' . $cpt, esc_html__( 'Status', 'car-rental-manager' ), '<span style="color:yellow">' . esc_html__( 'Status', 'car-rental-manager' ) . '</span>', 'manage_options', 'mpcrbm_status_page', array( $this, 'status_page' ) );
			}

			public function status_page() {
				$label      = MPCRBM_Function::get_name();
				$wc_i       = MPCRBM_Global_Function::check_woocommerce();
				$wc_i_text  = $wc_i == 1 ? esc_html__( 'Yes', 'car-rental-manager' ) : esc_html__( 'No', 'car-rental-manager' );
				$wp_v       = get_bloginfo( 'version' );
				$wc_v       = WC()->version;
				$from_name  = get_option( 'woocommerce_email_from_name' );
				$from_email = get_option( 'woocommerce_email_from_address' );
				?>
                <div class="wrap"></div>
                <div class="mpcrbm">
					<?php do_action( 'mpcrbm_status_notice_sec' ); ?>
                    <div class=_dShadow_6_adminLayout">
                        <h2 class="textCenter"><?php echo esc_html( $label ) . '  ' . esc_html__( 'For Woocommerce Environment Status', 'car-rental-manager' ); ?></h2>
                        <div class="divider"></div>
                        <table>
                            <tbody>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e( 'WordPress Version : ', 'car-rental-manager' ); ?></th>
                                <th class="<?php echo esc_attr( $wp_v > 5.5 ? 'textSuccess' : 'textWarning' ); ?>">
                                    <span class="<?php echo esc_attr( $wp_v > 5.5 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wp_v ); ?>
                                </th>
                            </tr>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e( 'Woocommerce Installed : ', 'car-rental-manager' ); ?></th>
                                <th class="<?php echo esc_attr( $wc_i == 1 ? 'textSuccess' : 'textWarning' ); ?>">
                                    <span class="<?php echo esc_attr( $wc_i == 1 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wc_i_text ); ?>
                                </th>
                            </tr>
							<?php if ( $wc_i == 1 ) { ?>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e( 'Woocommerce Version : ', 'car-rental-manager' ); ?></th>
                                    <th class="<?php echo esc_attr( $wc_v > 4.8 ? 'textSuccess' : 'textWarning' ); ?>">
                                        <span class="<?php echo esc_attr( $wc_v > 4.8 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wc_v ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e( 'Name : ', 'car-rental-manager' ); ?></th>
                                    <th class="<?php echo esc_attr( $from_name ? 'textSuccess' : 'textWarning' ); ?>">
                                        <span class="<?php echo esc_attr( $from_name ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $from_name ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e( 'Email Address : ', 'car-rental-manager' ); ?></th>
                                    <th class="<?php echo esc_attr( $from_email ? 'textSuccess' : 'textWarning' ); ?>">
                                        <span class="<?php echo esc_attr( $from_email ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $from_email ); ?>
                                    </th>
                                </tr>
							<?php }
								do_action( 'mpcrbm_status_table_item_sec' ); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
				<?php
			}
		}
		new MPCRBM_Status();
	}