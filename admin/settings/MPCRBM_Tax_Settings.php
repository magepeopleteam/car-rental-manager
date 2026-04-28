<?php
	/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Tax_Settings' ) ) {
		class MPCRBM_Tax_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'tab_content' ] );
				add_action( 'save_post', [ $this, 'settings_save' ] );
			}

			public function tab_content( $post_id ) {
				?>
                <div class="tabsItem" data-tabs="#wbtm_settings_tax">
                    <h2><?php esc_html_e( 'Tax Configuration', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'Tax Configuration settings.', 'car-rental-manager' ); ?></p>
					<?php
						$tax_status    = MPCRBM_Global_Function::get_post_info( $post_id, '_tax_status' );
						$tax_class     = MPCRBM_Global_Function::get_post_info( $post_id, '_tax_class' );
						$all_tax_class = MPCRBM_Global_Function::all_tax_list();
					?>
					<?php wp_nonce_field( 'save_tax_settings', 'tax_settings_nonce' ); ?>
                    <section class="bg-light">
                        <h6><?php esc_html_e( 'Tax Settings Information', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Configure and manage tax settings', 'car-rental-manager' ); ?></span>
                    </section>
					<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) { ?>
                        <div class="">
                            <section>
                                <label class="label">
                                    <div>
                                        <h6><?php esc_html_e( 'Tax status', 'car-rental-manager' ); ?></h6>
                                        <span class="desc"><?php esc_html_e( 'Select tax status type.', 'car-rental-manager' ); ?></span>
                                    </div>
                                    <select class="formControl max_300" name="_tax_status">
                                        <option disabled selected><?php esc_html_e( 'Please Select', 'car-rental-manager' ); ?></option>
                                        <option value="taxable" <?php echo esc_attr( $tax_status == 'taxable' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Taxable', 'car-rental-manager' ); ?>
                                        </option>
                                        <option value="shipping" <?php echo esc_attr( $tax_status == 'shipping' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Shipping only', 'car-rental-manager' ); ?>
                                        </option>
                                        <option value="none" <?php echo esc_attr( $tax_status == 'none' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'None', 'car-rental-manager' ); ?>
                                        </option>
                                    </select>
                                </label>
                            </section>
                            <section>
                                <label class="label">
                                    <div>
                                        <h6><?php esc_html_e( 'Tax class', 'car-rental-manager' ); ?></h6>
                                        <span class="desc"><?php esc_html_e( 'Select tax class.', 'car-rental-manager' ); ?></span>
                                    </div>
                                    <select class="formControl max_300" name="_tax_class">
                                        <option disabled selected><?php esc_html_e( 'Please Select', 'car-rental-manager' ); ?></option>
                                        <option value="standard" <?php echo esc_attr( $tax_class == 'standard' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Standard', 'car-rental-manager' ); ?>
                                        </option>
										<?php if ( sizeof( $all_tax_class ) > 0 ) { ?>
											<?php foreach ( $all_tax_class as $key => $class ) { ?>
                                                <option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $tax_class == $key ? 'selected' : '' ); ?>>
													<?php echo esc_html( $class ); ?>
                                                </option>
											<?php } ?>
										<?php } ?>
                                    </select>
                                </label>
                            </section>
                        </div>
					<?php } else { ?>
                        <div class="_dLayout_dFlex_justifyCenter">
							<?php MPCRBM_Layout::msg( esc_html__( 'Tax not active. Please add Tax settings from woocommerce.', 'car-rental-manager' ) ); ?>
                        </div>
					<?php } ?>
                </div>
				<?php
			}

			public function settings_save( $post_id ) {
				if (
					! isset( $_POST['tax_settings_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tax_settings_nonce'] ) ), 'save_tax_settings' ) ||
					( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
					wp_is_post_revision( $post_id ) ||
					! current_user_can( 'edit_post', $post_id )
				) {
					return;
				}
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					// Fixed by Shahnur — 2026-04-28 11:56 AM (Asia/Dhaka)
					$allowed_tax_status = array( 'taxable', 'shipping', 'none' );
					$tax_status         = isset( $_POST['_tax_status'] ) ? sanitize_text_field( wp_unslash( $_POST['_tax_status'] ) ) : 'none';
					$tax_status         = in_array( $tax_status, $allowed_tax_status, true ) ? $tax_status : 'none';
					$tax_class          = isset( $_POST['_tax_class'] ) ? sanitize_title( wp_unslash( $_POST['_tax_class'] ) ) : '';
					$allowed_tax_class  = array_keys( MPCRBM_Global_Function::all_tax_list() );
					$tax_class          = in_array( $tax_class, $allowed_tax_class, true ) ? $tax_class : '';
					update_post_meta( $post_id, '_tax_status', $tax_status );
					update_post_meta( $post_id, '_tax_class', $tax_class );
				}
			}

		}
		new MPCRBM_Tax_Settings();
	}
