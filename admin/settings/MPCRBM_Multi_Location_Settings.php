<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Multi_Location_Settings' ) ) {
		class MPCRBM_Multi_Location_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'multi_location_settings' ] );
				add_action( 'save_post', array( $this, 'save_multi_location_settings' ), 99, 1 );
			}

			public function multi_location_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_multi_location_nonce', 'mpcrbm_multi_location' );
				
				// Get saved multi-location data
				$multi_location_enabled = get_post_meta( $post_id, 'mpcrbm_multi_location_enabled', true );
				$location_prices = get_post_meta( $post_id, 'mpcrbm_location_prices', true );
				
				// Fetch all location terms
				$location_terms = get_terms( array( 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ) );
				
				$enabled_checked = $multi_location_enabled ? 'checked' : '';
				$enabled_display = $multi_location_enabled ? 'block' : 'none';
				?>
				
				<div class="tabsItem" data-tabs="#mpcrbm_setting_multi_location">
					<h2><?php esc_html_e( 'Multi-Location Support', 'car-rental-manager' ); ?></h2>
					<p><?php esc_html_e( 'Enable multiple pickup and drop-off locations for this vehicle with location-based pricing', 'car-rental-manager' ); ?></p>
					
					<!-- Enable Multi-Location -->
					<section class="bg-light">
						<h6><?php esc_html_e( 'Multi-Location Settings', 'car-rental-manager' ); ?></h6>
						<span><?php esc_html_e( 'Configure multiple locations and pricing', 'car-rental-manager' ); ?></span>
					</section>

					<section>
                        <div class="label">
                            <div>
                                <h6><?php esc_html_e( 'Enable Multi-Location Support', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php esc_html_e( 'Allow this vehicle to be rented from multiple pickup and drop-off locations', 'car-rental-manager' ); ?></span>
                            </div>
							<?php MPCRBM_Custom_Layout::switch_button( 'mpcrbm_multi_location_enabled', $enabled_checked ); ?>
                        </div>
                    </section>
					
					<!-- Multi-Location Configuration -->
					<div class="mpcrbm-section" id="mpcrbm-multi-location-config" style="display: <?php echo esc_attr( $enabled_display ); ?>" data-collapse="#mpcrbm_multi_location_enabled">
						
						
						<section class="bg-light">
							<h6><?php esc_html_e( 'Location-Based Pricing', 'car-rental-manager' ); ?></h6>
							<span><?php esc_html_e( 'Set different prices for different pickup/drop-off location combinations', 'car-rental-manager' ); ?></span>
						</section>
						
						<section>
							<!-- Location Management Info -->
							<div class="mpcrbm-info-box">
								<h6><?php esc_html_e( 'How Multi-Location Works:', 'car-rental-manager' ); ?></h6>
								<ul style="margin: 10px 0; padding-left: 20px;">
									<li><?php esc_html_e( 'Daily rates are taken from the main pricing settings - no need to set them here', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Configure transfer fees for rentals between different pickup/dropoff locations', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Customers can select their preferred pickup and dropoff locations', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Pricing automatically adjusts based on selected locations', 'car-rental-manager' ); ?></li>
								</ul>
							</div>

							<div id="mpcrbm-location-prices-container">
								<?php
								if ( ! empty( $location_prices ) && is_array( $location_prices ) ) {
									foreach ( $location_prices as $index => $price_data ) {
										$this->render_location_price_row( $index, $price_data, $location_terms );
									}
								} else {
									// Add at least one empty row if no data exists
									$this->render_location_price_row( 0, array(), $location_terms );
								}
								?>
							</div>
							<button type="button" id="mpcrbm-add-location-price" class="_themeButton_xs_mT_xs ">
								<i class="mi mi-plus"></i> <?php esc_html_e( 'Add Location Price', 'car-rental-manager' ); ?>
							</button>
						</section>
					</div>
				</div>
				<?php
			}
			
			private function render_location_price_row( $index, $price_data, $location_terms ) {
				$pickup_location = isset( $price_data['pickup_location'] ) ? $price_data['pickup_location'] : '';
				$dropoff_location = isset( $price_data['dropoff_location'] ) ? $price_data['dropoff_location'] : '';
				// Removed daily_price - using base pricing from main settings instead
				$transfer_fee = isset( $price_data['transfer_fee'] ) ? $price_data['transfer_fee'] : '';
				?>
				
				<div class="mpcrbm-location-price-row" data-index="<?php echo esc_attr( $index ); ?>">
					<div class="mpcrbm-location-price-grid mpcrbm-transfer-fee-only">
						<div class="mpcrbm-location-field">
							<label><?php esc_html_e( 'Pickup Location', 'car-rental-manager' ); ?></label>
							<select name="mpcrbm_location_prices[<?php echo esc_attr( $index ); ?>][pickup_location]" class="mpcrbm-location-select">
								<option value=""><?php esc_html_e( 'Select Location', 'car-rental-manager' ); ?></option>
								<?php foreach ( $location_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $pickup_location, $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="mpcrbm-location-field">
							<label><?php esc_html_e( 'Drop-off Location', 'car-rental-manager' ); ?></label>
							<select name="mpcrbm_location_prices[<?php echo esc_attr( $index ); ?>][dropoff_location]" class="mpcrbm-location-select">
								<option value=""><?php esc_html_e( 'Select Location', 'car-rental-manager' ); ?></option>
								<?php foreach ( $location_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $dropoff_location, $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<!-- Daily Price field removed - using base pricing from main settings -->
						
						<div class="mpcrbm-location-field">
							<label><?php esc_html_e( 'Transfer Fee', 'car-rental-manager' ); ?></label>
							<input type="number" name="mpcrbm_location_prices[<?php echo esc_attr( $index ); ?>][transfer_fee]" 
								   value="<?php echo esc_attr( $transfer_fee ); ?>" step="0.01" min="0" 
								   placeholder="<?php esc_html_e( 'One-way Fee', 'car-rental-manager' ); ?>" />
						</div>
						
						<div class="mpcrbm-location-field">
							<label>&nbsp;</label>
							<button type="button" class="button button-small mpcrbm-remove-location-price" 
									data-index="<?php echo esc_attr( $index ); ?>">
								<?php esc_html_e( 'Remove', 'car-rental-manager' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php
			}

			public function save_multi_location_settings( $post_id ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_multi_location'] ) ) {
					return;
				}
				
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_multi_location'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_multi_location_nonce' ) ) {
					return;
				}
				
				// Save multi-location enabled status
				$multi_location_enabled = isset( $_POST['mpcrbm_multi_location_enabled'] ) ? 1 : 0;
				update_post_meta( $post_id, 'mpcrbm_multi_location_enabled', $multi_location_enabled );
				
				if ( $multi_location_enabled ) {
					// Save location prices
					$location_prices = array();
					if ( isset( $_POST['mpcrbm_location_prices'] ) && is_array( $_POST['mpcrbm_location_prices'] ) ) {
						foreach ( $_POST['mpcrbm_location_prices'] as $index => $price_data ) {
							if ( ! empty( $price_data['pickup_location'] ) && ! empty( $price_data['dropoff_location'] ) ) {
								$location_prices[] = array(
									'pickup_location' => sanitize_text_field( $price_data['pickup_location'] ),
									'dropoff_location' => sanitize_text_field( $price_data['dropoff_location'] ),
									'transfer_fee' => floatval( $price_data['transfer_fee'] )
								);
							}
						}
					}
					update_post_meta( $post_id, 'mpcrbm_location_prices', $location_prices );
				}
			}
		}
		new MPCRBM_Multi_Location_Settings();
	}
