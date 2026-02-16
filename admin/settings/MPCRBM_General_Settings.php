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
				add_action( 'save_post', [ $this, 'save_general_settings' ] );
				add_action('save_post', function() {
					add_action('admin_notices', function() {
						echo '<div class="notice notice-success"><p>Saved!</p></div>';
					});
				});
			}

			public function general_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_general_settings', 'mpcrbm_nonce' );
				// Get all taxonomy terms
				$car_types = get_terms(['taxonomy' => 'mpcrbm_car_type', 'hide_empty' => false]);
				$fuel_types = get_terms(['taxonomy' => 'mpcrbm_fuel_type', 'hide_empty' => false]);
				$seating_capacities = get_terms(['taxonomy' => 'mpcrbm_seating_capacity', 'hide_empty' => false]);
				$car_brands = get_terms(['taxonomy' => 'mpcrbm_car_brand', 'hide_empty' => false]);
				$make_years = get_terms(['taxonomy' => 'mpcrbm_make_year', 'hide_empty' => false]);

				// Get selected taxonomy terms for this post
				$selected_car_type  = wp_get_post_terms($post_id, 'mpcrbm_car_type', ['fields' => 'ids']);
				$selected_fuel_type = wp_get_post_terms($post_id, 'mpcrbm_fuel_type', ['fields' => 'ids']);
				$selected_seating   = wp_get_post_terms($post_id, 'mpcrbm_seating_capacity', ['fields' => 'ids']);
				$selected_brand     = wp_get_post_terms($post_id, 'mpcrbm_car_brand', ['fields' => 'ids']);
				$selected_year      = wp_get_post_terms($post_id, 'mpcrbm_make_year', ['fields' => 'ids']);
				
				$max_passenger    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_maximum_passenger' );
				$max_bag          = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_maximum_bag' );
                $enable_driver_information    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_enable_driver_information' );

                $is_driver_checked = '';
                $is_info_show = 'none';
                if( $enable_driver_information === 'on' ){
                    $is_driver_checked = 'checked';
                    $is_info_show = 'block';
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
						<!-- Car Type -->
						<section>
							<label class="label">
								<div>
									<h6><?php esc_html_e('Car Type', 'car-rental-manager'); ?></h6>
									<span class="desc"><?php MPCRBM_Settings::info_text('display_mpcrbm_features'); ?></span>
								</div>
								<select name="tax_input[mpcrbm_car_type][]" class="formControl">
									<option value=""><?php esc_html_e('— Select Car Type —','car-rental-manager'); ?></option>
									<?php foreach ($car_types as $car_type): ?>
										<option value="<?php echo esc_attr($car_type->term_id); ?>" <?php selected(in_array($car_type->term_id, $selected_car_type)); ?>>
											<?php echo esc_html($car_type->name); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</section>

						<!-- Fuel Type -->
						<section>
							<label class="label">
								<div>
									<h6><?php esc_html_e('Fuel Type', 'car-rental-manager'); ?></h6>
									<span class="desc"><?php MPCRBM_Settings::info_text('display_mpcrbm_features'); ?></span>
								</div>
								<select name="tax_input[mpcrbm_fuel_type][]" class="formControl">
									<option value=""><?php esc_html_e('— Select Fuel Type —','car-rental-manager'); ?></option>
									<?php foreach ($fuel_types as $fuel_type): ?>
										<option value="<?php echo esc_attr($fuel_type->term_id); ?>" <?php selected(in_array($fuel_type->term_id, $selected_fuel_type)); ?>>
											<?php echo esc_html($fuel_type->name); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</section>

						<!-- Seating Capacity -->
						<section>
							<label class="label">
								<div>
									<h6><?php esc_html_e('Seating Capacity', 'car-rental-manager'); ?></h6>
								</div>
								<select name="tax_input[mpcrbm_seating_capacity][]" class="formControl">
									<option value=""><?php esc_html_e('— Select Seating —','car-rental-manager'); ?></option>
									<?php foreach ($seating_capacities as $seat): ?>
										<option value="<?php echo esc_attr($seat->term_id); ?>" <?php selected(in_array($seat->term_id, $selected_seating)); ?>>
											<?php echo esc_html($seat->name); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</section>

						<!-- Car Brand -->
						<section>
							<label class="label">
								<div>
									<h6><?php esc_html_e('Car Brand', 'car-rental-manager'); ?></h6>
								</div>
								<select name="tax_input[mpcrbm_car_brand][]" class="formControl">
									<option value=""><?php esc_html_e('— Select Brand —','car-rental-manager'); ?></option>
									<?php foreach ($car_brands as $brand): ?>
										<option value="<?php echo esc_attr($brand->term_id); ?>" <?php selected(in_array($brand->term_id, $selected_brand)); ?>>
											<?php echo esc_html($brand->name); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</section>

						<!-- Make Year -->
						<section>
							<label class="label">
								<div>
									<h6><?php esc_html_e('Make Year', 'car-rental-manager'); ?></h6>
								</div>
								<select name="tax_input[mpcrbm_make_year][]" class="formControl">
									<option value=""><?php esc_html_e('— Select Year —','car-rental-manager'); ?></option>
									<?php foreach ($make_years as $year): ?>
										<option value="<?php echo esc_attr($year->term_id); ?>" <?php selected(in_array($year->term_id, $selected_year)); ?>>
											<?php echo esc_html($year->name); ?>
										</option>
									<?php endforeach; ?>
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

                        <div class="mpcrbm_driver_info_holder" id="mpcrbm_driver_info_holder">
                            <section class="bg-light" style="margin-top: 20px;">
                                <!--<label class="label">
                                    <div>
                                        <h6><?php /*esc_html_e( 'Driver Information', 'car-rental-manager' ); */?></h6>
                                        <span class="desc"><?php /*MPCRBM_Settings::info_text( 'mpcrbm_driver_details' ); */?></span>
                                    </div>
                                </label>-->

                                <div class="label">
                                    <div>
                                        <h6>Enable Driver Information</h6>
                                        <span class="desc">By default show driver information in car OFF but you can keep it on by switching this option</span>
                                    </div>
                                    <label class="roundSwitchLabel">
                                        <input type="checkbox" class="mpcrbm_switch_checkbox" id="mpcrbm_enable_driver_information" name="mpcrbm_enable_driver_information" <?php echo esc_attr( $is_driver_checked );?> >
                                        <span class="roundSwitch" data-collapse-target="#mpcrbm_enable_driver_information"></span>
                                    </label>
                                </div>
                            </section>
                            <div class="mpcrbm_driver_info" id="mpcrbm_get_driver_info" style="display: <?php echo esc_attr($is_info_show);?>">
                                <?php MPCRBM_Settings::mpcrbm_driver_info_box_callback( $post_id )?>
                            </div>
						</div>

                    </div>
                </div>
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
					$max_passenger = isset( $_POST['mpcrbm_maximum_passenger'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_maximum_passenger'] ) ) : '';
					$max_bag       = isset( $_POST['mpcrbm_maximum_bag'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_maximum_bag'] ) ) : '';
					$taxonomies = [
						'mpcrbm_car_type',
						'mpcrbm_fuel_type',
						'mpcrbm_seating_capacity',
						'mpcrbm_car_brand',
						'mpcrbm_make_year'
					];

					foreach ($taxonomies as $taxonomy) {
						if (isset($_POST['tax_input'][$taxonomy])) {
							$term_ids = array_map('intval', $_POST['tax_input'][$taxonomy]);
							wp_set_object_terms($post_id, $term_ids, $taxonomy);
						} else {
							wp_set_object_terms($post_id, [], $taxonomy); // clear terms if none selected
						}
					}

                    $mpcrbm_enable_driver_information = isset( $_POST['mpcrbm_enable_driver_information'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_enable_driver_information'] ) ) ? 'on' : 'off';
//                    error_log( print_r( [ '$mpcrbm_enable_driver_information' => $mpcrbm_enable_driver_information ], true ) );

					update_post_meta( $post_id, 'mpcrbm_maximum_passenger', $max_passenger );
					update_post_meta( $post_id, 'mpcrbm_maximum_bag', $max_bag );
					update_post_meta( $post_id, 'mpcrbm_enable_driver_information', $mpcrbm_enable_driver_information );

                    if ( isset( $_POST['mpcrbm_driver_info'] ) && is_array( $_POST['mpcrbm_driver_info'] ) ) {
                        $driver_info = array_map( 'sanitize_text_field', $_POST['mpcrbm_driver_info'] );
                        update_post_meta( $post_id, 'mpcrbm_driver_info', $driver_info );
                    }
					
				}
			}

		}
		new MPCRBM_General_Settings();
	}
