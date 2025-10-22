<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_General_Settings' ) ) {
		class MPCRBM_General_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'general_settings' ] );
				add_action( 'mpcrbm_hidden_features_item', [ $this, 'features_item' ] );
				add_action( 'save_post', [ $this, 'save_general_settings' ] );
			}

			public function general_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_general_settings', 'mpcrbm_nonce' );
				$max_passenger    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_maximum_passenger' );
				$max_bag          = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_maximum_bag' );
				$display_features = MPCRBM_Global_Function::get_post_info( $post_id, 'display_mpcrbm_features', 'on' );
				$active           = $display_features == 'off' ? '' : 'mActive';
				$checked          = $display_features == 'off' ? '' : 'checked';
				$all_features     = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_features' );
				if ( ! $all_features ) {
					$all_features = array(
						array(
							'label' => esc_html__( 'Name', 'car-rental-manager' ),
							'icon'  => 'fas fa-car-side',
							'image' => '',
							'text'  => ''
						),
						array(
							'label' => esc_html__( 'Model', 'car-rental-manager' ),
							'icon'  => 'fas fa-car',
							'image' => '',
							'text'  => ''
						),
						array(
							'label' => esc_html__( 'Engine', 'car-rental-manager' ),
							'icon'  => 'fas fa-cogs',
							'image' => '',
							'text'  => ''
						),
						array(
							'label' => esc_html__( 'Fuel Type', 'car-rental-manager' ),
							'icon'  => 'fas fa-gas-pump',
							'image' => '',
							'text'  => ''
						)
					);
				}
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_general_info">
                    <h2><?php esc_html_e( 'General Information Settings', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'Basic Configuration', 'car-rental-manager' ); ?></p>
                    <div class="settings_area">
                        <section class="bg-light">
                            <h6><?php esc_html_e( 'Feature Configuration', 'car-rental-manager' ); ?></h6>
                            <span><?php esc_html_e( 'Here you can On/Off feature list and create new feature.', 'car-rental-manager' ); ?></span>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Car Type', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="" id="">
									<option value="">Car type</option>
								</select>
                            </label>
                        </section>
						<section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Fuel Type', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="" id="">
									<option value="">Car type</option>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Car Brand', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="" id="">
									<option value="">Car type</option>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Build Year', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="" id="">
									<option value="">Car type</option>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Seating Capacity', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="" id="">
									<option value="">Car type</option>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Maximum Passenger', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'mpcrbm_maximum_passenger' ); ?></span>
                                </div>
                                <input class="formControl price_validation" name="mpcrbm_maximum_passenger" value="<?php echo esc_attr( $max_passenger ); ?>" type="text" placeholder="<?php esc_html_e( 'EX:4', 'car-rental-manager' ); ?>"/>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Maximum Bag', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'mpcrbm_maximum_bag' ); ?></span>
                                </div>
                                <input class="formControl price_validation" name="mpcrbm_maximum_bag" value="<?php echo esc_attr( $max_bag ); ?>" type="text" placeholder="<?php esc_html_e( 'EX:4', 'car-rental-manager' ); ?>"/>
                            </label>
                        </section>
                    </div>
                </div>
				<?php
			}

			public function features_item( $features = array() ) {
				$label = array_key_exists( 'label', $features ) ? $features['label'] : '';
				$text  = array_key_exists( 'text', $features ) ? $features['text'] : '';
				$icon  = array_key_exists( 'icon', $features ) ? $features['icon'] : '';
				$image = array_key_exists( 'image', $features ) ? $features['image'] : '';
				?>
                <tr class="remove_area">
                    <td valign="middle"><?php do_action( 'mpcrbm_add_icon_image', 'mpcrbm_features_icon_image[]', $icon, $image ); ?></td>
                    <td valign="middle">
                        <label>
                            <input class="formControl name_validation" name="mpcrbm_features_label[]" value="<?php echo esc_attr( $label ); ?>"/>
                        </label>
                    </td>
                    <td valign="middle">
                        <label>
                            <input class="formControl name_validation" name="mpcrbm_features_text[]" value="<?php echo esc_attr( $text ); ?>"/>
                        </label>
                    </td>
                    <td valign="middle"><?php MPCRBM_Custom_Layout::move_remove_button(); ?></td>
                </tr>
				<?php
			}

			public function save_general_settings( $post_id ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_nonce'] ) ) {
					return;
				};
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_general_settings' ) ) {
					return;
				};
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					$all_features  = [];
					$max_passenger = isset( $_POST['mpcrbm_maximum_passenger'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_maximum_passenger'] ) ) : '';
					$max_bag       = isset( $_POST['mpcrbm_maximum_bag'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_maximum_bag'] ) ) : '';
					update_post_meta( $post_id, 'mpcrbm_maximum_passenger', $max_passenger );
					update_post_meta( $post_id, 'mpcrbm_maximum_bag', $max_bag );
					$display_features = isset( $_POST['display_mpcrbm_features'] ) && sanitize_text_field( wp_unslash( $_POST['display_mpcrbm_features'] ) ) ? 'on' : 'off';
					update_post_meta( $post_id, 'display_mpcrbm_features', $display_features );
					$features_label = isset( $_POST['mpcrbm_features_label'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_features_label'] ) ) : [];
					if ( sizeof( $features_label ) > 0 ) {
						$features_text = isset( $_POST['mpcrbm_features_text'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_features_text'] ) ) : [];
						$features_icon = isset( $_POST['mpcrbm_features_icon_image'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_features_icon_image'] ) ) : [];
						$count         = 0;
						foreach ( $features_label as $label ) {
							if ( $label ) {
								$all_features[ $count ]['label'] = $label;
								$all_features[ $count ]['text']  = $features_text[ $count ];
								$all_features[ $count ]['icon']  = '';
								$all_features[ $count ]['image'] = '';
								$current_image_icon              = array_key_exists( $count, $features_icon ) ? $features_icon[ $count ] : '';
								if ( $current_image_icon ) {
									if ( preg_match( '/\s/', $current_image_icon ) ) {
										$all_features[ $count ]['icon'] = $current_image_icon;
									} else {
										$all_features[ $count ]['image'] = $current_image_icon;
									}
								}
								$count ++;
							}
						}
					}
					update_post_meta( $post_id, 'mpcrbm_features', $all_features );
				}
			}

		}
		new MPCRBM_General_Settings();
	}
