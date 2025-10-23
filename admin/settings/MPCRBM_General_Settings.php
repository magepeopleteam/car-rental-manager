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
                        
						<?php
							$car_types = get_terms(array('taxonomy' => 'mpcrbm_car_type', 'hide_empty' => false));
							$fuel_types = get_terms(array('taxonomy' => 'mpcrbm_fuel_type', 'hide_empty' => false));
							$seating_capacities = get_terms(array('taxonomy' => 'mpcrbm_seating_capacity', 'hide_empty' => false));
							$car_brands = get_terms(array('taxonomy' => 'mpcrbm_car_brand', 'hide_empty' => false));
							$make_years = get_terms(array('taxonomy' => 'mpcrbm_make_year', 'hide_empty' => false));
							
							$selected_car_type  = get_post_meta( $post_id, 'mpcrbm_car_type', true );
							$selected_car_type  = empty($selected_car_type) ? $selected_car_type : '';

							$selected_fuel_type = get_post_meta( $post_id, 'mpcrbm_fuel_type', true );
							$selected_fuel_type  = empty($selected_fuel_type) ? $selected_fuel_type : '';

							$selected_seating   = get_post_meta( $post_id, 'mpcrbm_seating_capacity', true );
							$selected_seating  = empty($selected_seating) ? $selected_seating : '';

							$selected_brand     = get_post_meta( $post_id, 'mpcrbm_car_brand', true );
							$selected_brand  = empty($selected_brand) ? $selected_brand : '';

							$selected_year      = get_post_meta( $post_id, 'mpcrbm_make_year', true );
							$selected_year  = empty($selected_year) ? $selected_year : '';

						?>
						<section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Car Type', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								
								<select name="mpcrbm_car_type" class="formControl">
									<?php foreach ($car_types as $car_type):?>
									<option value="<?php echo esc_html($car_type->term_id); ?>" <?php echo ($car_type->term_id==$selected_car_type)?'selected':''; ?>><?php echo esc_html($car_type->name); ?></option>
									<?php endforeach; ?>
								</select>
                            </label>
                        </section>
						<section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Fuel Type', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="mpcrbm_fuel_type" class="formControl">
									<?php foreach ($fuel_types as $fuel_type):?>
									<option value="<?php echo esc_html($fuel_type->term_id); ?>" <?php echo ($fuel_type->term_id==$selected_fuel_type)?'selected':''; ?>><?php echo esc_html($fuel_type->name); ?></option>
									<?php endforeach; ?>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Seating Capacity', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="mpcrbm_seating_capacity" class="formControl">
									<?php foreach ($seating_capacities as $seating_capacity):?>
									<option value="<?php echo esc_html($seating_capacity->term_id); ?>" <?php echo ($seating_capacity->term_id==$selected_seating)?'selected':''; ?>><?php echo esc_html($seating_capacity->name); ?></option>
									<?php endforeach; ?>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Car Brand', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="mpcrbm_car_brand" class="formControl">
									<?php foreach ($car_brands as $car_brand):?>
									<option value="<?php echo esc_html($car_brand->term_id); ?>" <?php echo ($car_brand->term_id==$selected_brand)?'selected':''; ?>><?php echo esc_html($car_brand->name); ?></option>
									<?php endforeach; ?>
								</select>
                            </label>
                        </section>
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Make Years', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                                </div>
								<select name="mpcrbm_make_year" class="formControl">
									<?php foreach ($make_years as $make_year):?>
										<option value="<?php echo esc_html($make_year->term_id); ?>" <?php echo ($make_year->term_id==$selected_year)?'selected':''; ?>><?php echo esc_html($make_year->name); ?></option>
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
					$car_type      = isset( $_POST['mpcrbm_car_type'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_car_type'] ) ) ? $_POST['mpcrbm_car_type'] : '';
					$fuel_type      = isset( $_POST['mpcrbm_fuel_type'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_fuel_type'] ) ) ? $_POST['mpcrbm_fuel_type'] : '';
					$seating_capacity      = isset( $_POST['mpcrbm_seating_capacity'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_seating_capacity'] ) ) ? $_POST['mpcrbm_seating_capacity'] : '';
					$car_brand      = isset( $_POST['mpcrbm_car_brand'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_car_brand'] ) ) ? $_POST['mpcrbm_car_brand'] : '';
					$make_year      = isset( $_POST['mpcrbm_make_year'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_make_year'] ) ) ? $_POST['mpcrbm_make_year'] : '';
					
					update_post_meta( $post_id, 'mpcrbm_car_type', $car_type );
					update_post_meta( $post_id, 'mpcrbm_fuel_type', $fuel_type );
					update_post_meta( $post_id, 'mpcrbm_seating_capacity', $seating_capacity );
					update_post_meta( $post_id, 'mpcrbm_car_brand', $car_brand );
					update_post_meta( $post_id, 'mpcrbm_make_year', $make_year );

					update_post_meta( $post_id, 'mpcrbm_maximum_passenger', $max_passenger );
					update_post_meta( $post_id, 'mpcrbm_maximum_bag', $max_bag );
					
				}
			}

		}
		new MPCRBM_General_Settings();
	}
