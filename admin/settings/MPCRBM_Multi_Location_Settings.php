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
					<div class="mpcrbm-info-card">
						<div class="mpcrbm-info-card-header">
							<i class="fas fa-map-pin"></i>
							<div>
								<h3><?php esc_html_e( 'Multi-Location Settings', 'car-rental-manager' ); ?></h3>
								<span class="desc"><?php esc_html_e( 'Allow this vehicle to be rented from multiple pickup and drop-off locations', 'car-rental-manager' ); ?></span>
							</div>
							<?php MPCRBM_Custom_Layout::switch_button( 'mpcrbm_multi_location_enabled', $enabled_checked ); ?>
						</div>
						<div class="mpcrbm-info-card-body mpcrbm-multi-location-body">
						<section id="mpcrbm-multi-location-config" style="display: <?php echo esc_attr( $enabled_display ); ?>" data-collapse="#mpcrbm_multi_location_enabled">
							<!-- Location Management Info -->
							<div class="mpcrbm-location-info-banner">
								<i class="mi mi-info"></i>
								<div>
									<strong><?php esc_html_e( 'How Multi-Location Works', 'car-rental-manager' ); ?></strong>
									<ul>
										<li><?php esc_html_e( 'Daily rates are taken from the main pricing settings - no need to set them here', 'car-rental-manager' ); ?></li>
										<li><?php esc_html_e( 'Configure transfer fees for rentals between different pickup/dropoff locations', 'car-rental-manager' ); ?></li>
										<li><?php esc_html_e( 'Customers can select their preferred pickup and dropoff locations', 'car-rental-manager' ); ?></li>
										<li><?php esc_html_e( 'Pricing automatically adjusts based on selected locations', 'car-rental-manager' ); ?></li>
									</ul>
								</div>
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
							<button type="button" id="mpcrbm-add-location-price" class="_themeButton_xs_mT_xs mpcrbm-price-add-btn">
								<i class="mi mi-plus"></i> <?php esc_html_e( 'Add Location Price', 'car-rental-manager' ); ?>
							</button>
						</section>
						</div>
					</div>

					<?php
					// "Security Deposit" card (MPCRBM_Security_Deposit_Setting::security_deposit_settings())
					// moved here, and out of its own separate tab — this tab was renamed from
					// "Multi-Location Fee" to "Fee & Deposit" to reflect it, and the "Security
					// Deposit" nav <li> was removed from MPCRBM_Settings.php.
					do_action( 'mpcrbm_multi_location_tab_after_pricing', $post_id );
					?>
				</div>
				<?php
			}

			private function render_location_price_row( $index, $price_data, $location_terms ) {
				$pickup_location = isset( $price_data['pickup_location'] ) ? $price_data['pickup_location'] : '';
				$dropoff_location = isset( $price_data['dropoff_location'] ) ? $price_data['dropoff_location'] : '';
				// Removed daily_price - using base pricing from main settings instead
				$transfer_fee = isset( $price_data['transfer_fee'] ) ? $price_data['transfer_fee'] : '';
				$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
				?>

				<div class="mpcrbm-location-price-row mpcrbm-season-row" data-index="<?php echo esc_attr( $index ); ?>">
					<div class="mpcrbm-season-badge">
						<span class="mpcrbm-season-badge-label"><?php esc_html_e( 'Route', 'car-rental-manager' ); ?></span>
						<span class="mpcrbm-season-badge-num mpcrbm-location-badge-num"><?php echo esc_html( str_pad( $index + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
					</div>
					<div class="mpcrbm-season-fields mpcrbm-location-price-grid mpcrbm-transfer-fee-only">
						<div class="mpcrbm-season-field mpcrbm-location-field">
							<label title="<?php esc_attr_e( 'Select the location where customers will pick up the vehicle', 'car-rental-manager' ); ?>"><?php esc_html_e( 'Pickup Location', 'car-rental-manager' ); ?></label>
							<select name="mpcrbm_location_prices[<?php echo esc_attr( $index ); ?>][pickup_location]" class="mpcrbm-location-select">
								<option value=""><?php esc_html_e( 'Select Location', 'car-rental-manager' ); ?></option>
								<?php foreach ( $location_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $pickup_location, $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mpcrbm-season-field mpcrbm-location-field">
							<label title="<?php esc_attr_e( 'Select the location where customers will return the vehicle', 'car-rental-manager' ); ?>"><?php esc_html_e( 'Drop-off Location', 'car-rental-manager' ); ?></label>
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

						<div class="mpcrbm-season-field mpcrbm-location-field">
							<label title="<?php esc_attr_e( 'Additional fee for rentals with different pickup and dropoff locations', 'car-rental-manager' ); ?>"><?php esc_html_e( 'Transfer Fee', 'car-rental-manager' ); ?></label>
							<div class="mpcrbm-season-adjustment">
								<input type="number" name="mpcrbm_location_prices[<?php echo esc_attr( $index ); ?>][transfer_fee]"
									   value="<?php echo esc_attr( $transfer_fee ); ?>" step="0.01" min="0"
									   class="mpcrbm-season-value"
									   placeholder="<?php esc_attr_e( 'One-way Fee', 'car-rental-manager' ); ?>">
								<span class="mpcrbm-season-unit"><?php echo esc_html( $currency ); ?></span>
							</div>
						</div>
					</div>
					<button type="button" class="mpcrbm-remove-location-price mpcrbm-season-remove"
							data-index="<?php echo esc_attr( $index ); ?>"
							title="<?php esc_attr_e( 'Remove', 'car-rental-manager' ); ?>">
						<i class="mi mi-trash"></i>
					</button>
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
						// Unslash the whole array once to make the loop cleaner
                        // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$posted_prices = isset( $_POST['mpcrbm_location_prices'] ) ?  $_POST['mpcrbm_location_prices'] : array();

						foreach ( $posted_prices as $index => $price_data ) {
							if ( ! empty( $price_data['pickup_location'] ) && ! empty( $price_data['dropoff_location'] ) ) {
								$location_prices[] = array(
									'pickup_location'  => sanitize_text_field( $price_data['pickup_location'] ),
									'dropoff_location' => sanitize_text_field( $price_data['dropoff_location'] ),
									// Use wc_format_decimal or floatval, but ensure it's unslashed (handled above)
									'transfer_fee'     => floatval( $price_data['transfer_fee'] ),
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
