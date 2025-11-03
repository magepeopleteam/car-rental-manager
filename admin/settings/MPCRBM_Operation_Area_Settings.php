<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Operation_Area_Settings' ) ) {
		class MPCRBM_Operation_Area_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'operation_area_settings' ] );
				add_action( 'save_post', array( $this, 'save_operation_area_settings' ), 99, 1 );
			}

			public function operation_area_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_operation_area_nonce', 'mpcrbm_operation_area' );
				// Fetch all terms in the 'mpcrbm_locations' taxonomy
				$location_terms = get_terms( array( 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ) );
				// Retrieve saved data from post meta
				$saved_locations = get_post_meta( $post_id, 'mpcrbm_terms_price_info', true );
				if ( $saved_locations ) {
					$saved_locations_array = array_column( $saved_locations, 'start_location' ); // Extract saved start locations into an array
				}
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_setting_operation_area">
                    <h2><?php esc_html_e( 'Operation Area', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'You can choose multiple regions as your operational area', 'car-rental-manager' ); ?></p>
                    <section class="bg-light">
						<h6><?php esc_html_e( 'Operation Area', 'car-rental-manager' ); ?></h6>
						<span><?php esc_html_e( 'Operation Area settings', 'car-rental-manager' ); ?></span>
					</section>
					<section>
						<label class="label">
							<div>
								<h6><?php esc_html_e('Select Operation area', 'car-rental-manager'); ?></h6>
								<span class="desc"><?php esc_html_e( 'Hold down the Ctrl (Windows) or Command (Mac) button to select multiple options.', 'car-rental-manager' ); ?></span>
							</div>
							<select name="mpcrbm_terms_start_location[]" id="operation_area_select" class="formControl" multiple>
								<?php
									if ( ! empty( $saved_locations_array ) && ! is_array( $saved_locations_array ) ) {
										$saved_locations_array = [ $saved_locations_array ]; // Convert single value to array
									} elseif ( empty( $saved_locations_array ) ) {
										$saved_locations_array = []; // Initialize as an empty array
									}
									if ( ! empty( $location_terms ) && ! is_wp_error( $location_terms ) ) {
										foreach ( $location_terms as $term ) {
											// Check if the term is saved and mark it as selected
											$selected = in_array( $term->slug, $saved_locations_array ) ? 'selected' : '';
											?>
											<option value="<?php echo esc_attr( $term->slug ); ?>" <?php echo esc_attr( $selected ); ?>>
												<?php echo esc_html( $term->name ); ?>
											</option>
											<?php
										}
									} else {
										?>
										<option value=""><?php esc_html_e( 'No locations found', 'car-rental-manager' ); ?></option>
										<?php
									}
								?>
							</select>
						</label>
					</section>
				</div>
				<?php
			}

			public function save_operation_area_settings( $post_id ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_operation_area'] ) ) {
					return;
				}
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_operation_area'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_operation_area_nonce' ) ) {
					return;
				}
				$terms_location = isset( $_POST['mpcrbm_terms_start_location'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_terms_start_location'] ) )
					: [];
				if ( ! empty( $terms_location ) ) {
					$terms_price_infos = [];
					foreach ( $terms_location as $index => $location ) {
						if ( $location ) {
							$terms_price_infos[ $index ] = [
								'start_location' => $location,
								'end_location'   => $location, // Or modify this if end_location differs
							];
						}
					}
					if ( ! empty( $terms_price_infos ) ) {
						update_post_meta( $post_id, 'mpcrbm_terms_price_info', $terms_price_infos );
					}
				}
			}

		}
		new MPCRBM_Operation_Area_Settings();
	}
