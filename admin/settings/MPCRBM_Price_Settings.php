<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Price_Settings' ) ) {
		class MPCRBM_Price_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'price_settings' ] );
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'price_settings' ] );
				add_action( 'save_post', [ $this, 'save_price_settings' ] );
				add_action( 'mpcrbm_settings_sec_fields', array( $this, 'settings_sec_fields' ), 10, 1 );
			}

			public function price_settings( $post_id ) {
				$time_price            = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price' );
				$manual_prices         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
				$terms_location_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_price_info', [] );
				$location_terms        = get_terms( array( 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ) );
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_settings_pricing">
                    <h2><?php esc_html_e( 'Price Settings', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'here you can set initial price, Waiting Time price, price calculation model', 'car-rental-manager' ); ?></p>
                    <section class="bg-light">
                        <h6><?php esc_html_e( 'Price Settings', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Here you can set price', 'car-rental-manager' ); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Price/Day', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php MPCRBM_Settings::info_text( 'mpcrbm_day_price' ); ?></span>
                            </div>
                            <input class="formControl price_validation" name="mpcrbm_day_price" value="<?php echo esc_attr( $time_price ); ?>" type="text" placeholder="<?php esc_html_e( 'EX:10', 'car-rental-manager' ); ?>"/>
                        </label>
                    </section>
                    <!-- Manual price -->
                    <section class="bg-light" style="margin-top: 20px;" data-collapse="#mp_manual">
                        <h6><?php esc_html_e( 'Manual Price Settings', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Manual Price Settings', 'car-rental-manager' ); ?></span>
                    </section>
                </div>
				<?php
			}

			public function save_price_settings( $post_id ) {
				if (
					! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) ), 'mpcrbm_transportation_type_nonce' ) ||
					( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
					! current_user_can( 'edit_post', $post_id )
				) {
					return;
				}
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					$price_based = "manual";
					update_post_meta( $post_id, 'mpcrbm_price_based', $price_based );
					$hour_price = isset( $_POST['mpcrbm_day_price'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_day_price'] ) ) : 0;
					update_post_meta( $post_id, 'mpcrbm_day_price', $hour_price );
					$manual_price_infos = array();
					$start_location     = isset( $_POST['mpcrbm_manual_start_location'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_manual_start_location'] ) ) : [];
					$end_location       = isset( $_POST['mpcrbm_manual_end_location'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_manual_end_location'] ) ) : [];
					$manual_price       = isset( $_POST['mpcrbm_manual_price'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_manual_price'] ) ) : [];
					if ( sizeof( $start_location ) > 1 && sizeof( $end_location ) > 1 ) {
						$count = 0;
						foreach ( $start_location as $key => $location ) {
							if ( $location && $end_location[ $key ] && $manual_price[ $key ] ) {
								$manual_price_infos[ $count ]['start_location'] = $location;
								$manual_price_infos[ $count ]['end_location']   = $end_location[ $key ];
								$count ++;
							}
						}
					}
					update_post_meta( $post_id, 'mpcrbm_manual_price_info', $manual_price_infos );
					$terms_price_infos    = array();
					$start_terms_location = isset( $_POST['mpcrbm_terms_start_location'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_terms_start_location'] ) ) : [];
					$end_terms_location   = isset( $_POST['mpcrbm_terms_end_location'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_terms_end_location'] ) ) : [];
					if ( sizeof( $start_terms_location ) > 1 && sizeof( $end_terms_location ) > 1 ) {
						$count = 0;
						foreach ( $start_terms_location as $key => $location ) {
							if ( $location && $end_terms_location[ $key ] ) {
								$terms_price_infos[ $count ]['start_location'] = $location;
								$terms_price_infos[ $count ]['end_location']   = $end_terms_location[ $key ];
								$count ++;
							}
						}
					}
					update_post_meta( $post_id, 'mpcrbm_terms_price_info', $terms_price_infos );
				}
			}

			public function settings_sec_fields( $default_fields ): array {
				// Ensure $default_fields is an array
				$default_fields = is_array( $default_fields ) ? $default_fields : array();
				$settings_fields = array(
					'mpcrbm_price_settings' => array(
						array(
							'name'    => 'mpcrbm_day_price',
							'label'   => esc_html__( 'Price/Day', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set the daily price for the car rental', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '0'
						),
						array(
							'name'    => 'mpcrbm_manual_price_info',
							'label'   => esc_html__( 'Manual Price Settings', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Configure manual pricing options', 'car-rental-manager' ),
							'type'    => 'array',
							'default' => array()
						),
						array(
							'name'    => 'mpcrbm_terms_price_info',
							'label'   => esc_html__( 'Location Based Pricing', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set prices based on locations', 'car-rental-manager' ),
							'type'    => 'array',
							'default' => array()
						)
					)
				);

				return array_merge( $default_fields, $settings_fields );
			}
		}
		new MPCRBM_Price_Settings();
	}