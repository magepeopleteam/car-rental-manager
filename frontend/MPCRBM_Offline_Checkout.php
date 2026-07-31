<?php
	/**
	 * Standalone (no-WooCommerce) checkout for the built-in FREE Offline payment method.
	 *
	 * Offline is the one custom payment method that ships with the free plugin: it needs
	 * no online processor, so a rental can be recorded as "pending" and settled on pickup
	 * or by bank transfer. PayPal & Stripe (and the richer Pro checkout — customer portal,
	 * gateway returns, PDF) stay in the Pro plugin.
	 *
	 * Flow:
	 *   1. "Book Now" hits MPCRBM_Woocommerce::mpcrbm_add_to_cart(). When Booking Mode is
	 *      not WooCommerce it applies the `mpcrbm_custom_payment_add_to_cart` filter,
	 *      which lands here.
	 *   2. The fare is recomputed SERVER-SIDE (never trusting a posted price) and the whole
	 *      booking draft is parked in a short-lived transient keyed by a random token. The
	 *      customer is sent to the auto-created Checkout page with that token.
	 *   3. The Checkout page ([mpcrbm_checkout]) shows the booking summary + the customer
	 *      fields + the Offline payment card.
	 *   4. Placing the order recomputes NOTHING from the browser — it reads the parked
	 *      draft, writes an `mpcrbm_booking` post using the SAME meta schema as the
	 *      WooCommerce flow (MPCRBM_Woocommerce::checkout_order_processed), and redirects
	 *      to the Booking Confirmation page keyed by booking id + PIN, so a booking can't
	 *      be read by guessing ids.
	 *
	 * Stands down COMPLETELY when the Pro plugin is active — Pro's MPCRBM_Native_Checkout
	 * owns the standalone flow there and registers the same shortcodes. Pro is detected at
	 * hook/request time (both plugins are loaded by then), never at include time, because
	 * plugin load order is not guaranteed.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_Offline_Checkout' ) ) {
		class MPCRBM_Offline_Checkout {

			const CHECKOUT_SHORTCODE     = 'mpcrbm_checkout';
			const CONFIRMATION_SHORTCODE = 'mpcrbm_booking_confirmation';
			const SETTINGS_OPTION        = 'mpcrbm_payment_settings';
			const CHECKOUT_PAGE_OPTION   = 'mpcrbm_checkout_page_auto';
			const CONFIRM_PAGE_OPTION    = 'mpcrbm_confirmation_page_auto';

			/** How long a parked booking draft stays valid (seconds). */
			const DRAFT_TTL = 2 * HOUR_IN_SECONDS;

			public function __construct() {
				add_filter( 'mpcrbm_custom_payment_add_to_cart', array( $this, 'process_add_to_cart' ), 10, 2 );
				add_action( 'init', array( $this, 'register_shortcodes' ) );
				add_action( 'admin_init', array( $this, 'ensure_pages' ) );
				add_action( 'wp_ajax_mpcrbm_offline_place_order', array( $this, 'ajax_place_order' ) );
				add_action( 'wp_ajax_nopriv_mpcrbm_offline_place_order', array( $this, 'ajax_place_order' ) );
			}

			/* --------------------------------------------------------------
			 * Availability
			 * ------------------------------------------------------------ */

			/** Pro owns the standalone flow when present, so this class must not run. */
			private function pro_active(): bool {
				return class_exists( 'MPCRBM_Plugin_Pro' ) || class_exists( 'MPCRBM_Native_Checkout' );
			}

			/** Should this free Offline flow handle the booking right now? */
			private function is_active(): bool {
				if ( $this->pro_active() ) {
					return false;
				}
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) || ! MPCRBM_Booking_Mode::is_custom() ) {
					return false;
				}

				return class_exists( 'MPCRBM_Function' ) && MPCRBM_Function::offline_payment_enabled();
			}

			private function opt( $key, $default = '' ) {
				$o = get_option( self::SETTINGS_OPTION, array() );

				return ( is_array( $o ) && isset( $o[ $key ] ) && '' !== $o[ $key ] ) ? $o[ $key ] : $default;
			}

			private function offline_label(): string {
				return (string) $this->opt( 'mpcrbm_offline_label', __( 'Offline Payment', 'car-rental-manager' ) );
			}

			private function money( $amount ): string {
				return class_exists( 'MPCRBM_Global_Function' )
					? MPCRBM_Global_Function::format_price( (float) $amount )
					: number_format( (float) $amount, 2 );
			}

			/* --------------------------------------------------------------
			 * Pages & shortcodes
			 * ------------------------------------------------------------ */

			public function register_shortcodes() {
				// Registered even when Pro is active would collide with Pro's own
				// shortcodes of the same name, so bail early there.
				if ( $this->pro_active() ) {
					return;
				}
				add_shortcode( self::CHECKOUT_SHORTCODE, array( $this, 'render_checkout_page' ) );
				add_shortcode( self::CONFIRMATION_SHORTCODE, array( $this, 'render_confirmation_page' ) );
			}

			/**
			 * Create the Checkout + Booking Confirmation pages once, on demand.
			 *
			 * Deliberately lazy (only when Custom Payment is actually in use) rather than
			 * on activation: a site that never leaves WooCommerce mode should not have two
			 * stray pages appear in its menus.
			 */
			public function ensure_pages() {
				if ( $this->pro_active() || ! class_exists( 'MPCRBM_Booking_Mode' ) || ! MPCRBM_Booking_Mode::is_custom() ) {
					return;
				}
				$this->ensure_page( self::CHECKOUT_PAGE_OPTION, 'mpcrbm-checkout', __( 'Rental Checkout', 'car-rental-manager' ), '[' . self::CHECKOUT_SHORTCODE . ']' );
				$this->ensure_page( self::CONFIRM_PAGE_OPTION, 'mpcrbm-booking-confirmation', __( 'Booking Confirmation', 'car-rental-manager' ), '[' . self::CONFIRMATION_SHORTCODE . ']' );
			}

			private function ensure_page( $option, $slug, $title, $content ) {
				$existing = absint( get_option( $option, 0 ) );
				if ( $existing && 'page' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) ) {
					return $existing;
				}
				// An admin may have made the page by hand before we got here — reuse it
				// rather than creating a confusing duplicate.
				$by_slug = get_page_by_path( $slug );
				if ( $by_slug ) {
					update_option( $option, $by_slug->ID );

					return $by_slug->ID;
				}
				$page_id = wp_insert_post( array(
					'post_type'    => 'page',
					'post_name'    => $slug,
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
				) );
				if ( $page_id && ! is_wp_error( $page_id ) ) {
					update_option( $option, $page_id );

					return $page_id;
				}

				return 0;
			}

			private function checkout_url(): string {
				$page_id = absint( get_option( self::CHECKOUT_PAGE_OPTION, 0 ) );
				if ( ! $page_id ) {
					$page_id = $this->ensure_page( self::CHECKOUT_PAGE_OPTION, 'mpcrbm-checkout', __( 'Rental Checkout', 'car-rental-manager' ), '[' . self::CHECKOUT_SHORTCODE . ']' );
				}

				return $page_id ? get_permalink( $page_id ) : home_url( '/' );
			}

			/** The admin's chosen confirmation page, falling back to the auto-created one. */
			private function confirmation_url(): string {
				$chosen = absint( $this->opt( 'mpcrbm_confirmation_page_id', 0 ) );
				if ( $chosen && 'page' === get_post_type( $chosen ) ) {
					return get_permalink( $chosen );
				}
				$page_id = absint( get_option( self::CONFIRM_PAGE_OPTION, 0 ) );
				if ( ! $page_id ) {
					$page_id = $this->ensure_page( self::CONFIRM_PAGE_OPTION, 'mpcrbm-booking-confirmation', __( 'Booking Confirmation', 'car-rental-manager' ), '[' . self::CONFIRMATION_SHORTCODE . ']' );
				}

				return $page_id ? get_permalink( $page_id ) : home_url( '/' );
			}

			/* --------------------------------------------------------------
			 * Step 1 — park the booking draft, hand back a checkout URL
			 * ------------------------------------------------------------ */

			/**
			 * `mpcrbm_custom_payment_add_to_cart` handler.
			 *
			 * @param string $response Whatever an earlier handler produced ('' = unhandled).
			 * @param array  $post     The raw $_POST from the add-to-cart request.
			 *
			 * @return string A URL (the frontend redirects to it) or HTML (rendered inline).
			 */
			public function process_add_to_cart( $response, $post ) {
				if ( '' !== $response || ! $this->is_active() ) {
					return $response;
				}

				$post    = is_array( $post ) ? $post : array();
				$link_id = isset( $post['link_id'] ) ? absint( $post['link_id'] ) : 0;
				$car_id  = isset( $post['post_id'] ) ? absint( $post['post_id'] ) : 0;

				// The search-results page posts only link_id (the hidden WooCommerce
				// product), the car-details page posts both. In Custom Payment mode there
				// may be no mirror product at all, so resolve the real car id from
				// whichever identifier actually arrived.
				if ( ! $car_id || get_post_type( $car_id ) !== MPCRBM_Function::get_cpt() ) {
					$car_id = $link_id ? absint( get_post_meta( $link_id, 'link_mpcrbm_id', true ) ) : 0;
				}
				if ( ! $car_id || get_post_type( $car_id ) !== MPCRBM_Function::get_cpt() ) {
					return $this->error_html( __( 'Sorry, we could not identify the vehicle for this booking. Please search again.', 'car-rental-manager' ) );
				}

				// Recompute the fare from the request server-side. mpcrbm_get_cart_total_price()
				// re-verifies the nonce itself and reads only $_POST, so a tampered price in
				// the browser can never reach a stored booking.
				$total = (float) MPCRBM_Woocommerce::mpcrbm_get_cart_total_price( $car_id );
				if ( $total <= 0 ) {
					return $this->error_html( __( 'Sorry, we could not calculate a price for this booking. Please search again.', 'car-rental-manager' ) );
				}

				$draft = $this->build_draft( $car_id, $total, $post );
				$token = wp_generate_password( 32, false, false );
				set_transient( 'mpcrbm_checkout_' . $token, $draft, self::DRAFT_TTL );

				return add_query_arg( 'mpcrbm_checkout', rawurlencode( $token ), $this->checkout_url() );
			}

			/**
			 * Normalise the add-to-cart request into the meta shape the booking record
			 * uses, so step 4 only has to add customer details and save.
			 */
			private function build_draft( $car_id, $total, array $post ): array {
				$get = static function ( $key ) use ( $post ) {
					return isset( $post[ $key ] ) ? sanitize_text_field( wp_unslash( $post[ $key ] ) ) : '';
				};

				$start_date       = $get( 'mpcrbm_date' );
				$return_date      = $get( 'mpcrbm_return_date' );
				$return_time      = $get( 'mpcrbm_return_time' );
				$car_quantity     = isset( $post['mpcrbm_car_quantity'] ) ? max( 1, absint( $post['mpcrbm_car_quantity'] ) ) : 1;
				$return_date_time = $return_date ? trim( $return_date . ' ' . $return_time ) : '';
				$rental_days      = ( $return_date_time && class_exists( 'MPCRBM_Function' ) )
					? MPCRBM_Function::get_days_from_start_end_date( $start_date, $return_date_time )
					: 1;

				// Extra services are priced by the same helper the WooCommerce cart uses,
				// so both flows produce an identical mpcrbm_service_info structure.
				$service_info = MPCRBM_Woocommerce::cart_extra_service_info( $car_id, $rental_days );

				$delivery_requested   = isset( $post['mpcrbm_delivery_requested'] ) && '1' === $post['mpcrbm_delivery_requested'];
				$collection_requested = isset( $post['mpcrbm_collection_requested'] ) && '1' === $post['mpcrbm_collection_requested'];

				$base_per_car = $car_quantity > 0 ? ( $total / $car_quantity ) : $total;
				$dc_meta      = array();
				foreach ( array( 'delivery', 'collection' ) as $kind ) {
					$requested = ( 'delivery' === $kind ) ? $delivery_requested : $collection_requested;
					$fee       = 0;
					if ( $requested && class_exists( 'MPCRBM_Delivery_Collection_Settings' ) ) {
						$fee = (float) MPCRBM_Delivery_Collection_Settings::get_fee( $car_id, $kind, $base_per_car );
					}
					$dc_meta[ 'mpcrbm_' . $kind . '_fee' ]     = $fee;
					$dc_meta[ 'mpcrbm_' . $kind . '_address' ] = $requested ? $get( 'mpcrbm_' . $kind . '_address' ) : '';
				}

				$security_deposit = 0;
				if ( 'on' === get_post_meta( $car_id, 'mpcrbm_security_deposit_enable', true ) ) {
					$amount = (float) get_post_meta( $car_id, 'mpcrbm_security_deposit', true );
					if ( $amount > 0 ) {
						$security_deposit = ( 'percentage' === get_post_meta( $car_id, 'mpcrbm_security_deposit_type', true ) )
							? round( $base_per_car * $amount / 100, 2 )
							: $amount;
					}
				}

				$one_way_fee = 0;
				$start_place = $get( 'mpcrbm_start_place' );
				$end_place   = $get( 'mpcrbm_end_place' );
				if ( get_post_meta( $car_id, 'mpcrbm_car_one_way_enabled', true ) && $start_place !== $end_place ) {
					$ow_value = (float) get_post_meta( $car_id, 'mpcrbm_car_one_way_fee', true );
					$ow_type  = get_post_meta( $car_id, 'mpcrbm_car_one_way_fee_type', true );
					$one_way_fee = ( 'percentage' === $ow_type ) ? round( $base_per_car * $ow_value / 100, 2 ) : $ow_value;
				}

				return array(
					'mpcrbm_id'                          => $car_id,
					'mpcrbm_date'                        => $start_date,
					'return_date_time'                   => $return_date_time,
					'mpcrbm_return_target_date'          => $return_date,
					'mpcrbm_return_target_time'          => $return_time,
					'mpcrbm_start_place'                 => $start_place,
					'mpcrbm_end_place'                   => $end_place,
					'mpcrbm_waiting_time'                => $get( 'mpcrbm_waiting_time' ),
					'mpcrbm_taxi_return'                 => $get( 'mpcrbm_taxi_return' ),
					'mpcrbm_fixed_hours'                 => $get( 'mpcrbm_fixed_hours' ),
					'mpcrbm_distance'                    => $get( 'mpcrbm_distance' ),
					'mpcrbm_duration'                    => $get( 'mpcrbm_duration' ),
					'mpcrbm_base_price'                  => $total,
					'mpcrbm_tp'                          => $total,
					'mpcrbm_car_quantity'                => $car_quantity,
					'mpcrbm_service_info'                => is_array( $service_info ) ? array_values( $service_info ) : array(),
					'mpcrbm_security_deposit_amount'     => $security_deposit,
					'mpcrbm_branch_one_way_fee'          => $one_way_fee,
					'mpcrbm_rental_days'                 => $rental_days,
					'mpcrbm_target_pickup_interval_time' => class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_general_settings( 'pickup_interval_time', '30' ) : '30',
				) + $dc_meta;
			}

			private function error_html( $message ): string {
				return '<div class="dLayout mpcrbm-cart-error"><p class="mpcrbm-error-message">' . esc_html( $message ) . '</p></div>';
			}

			/* --------------------------------------------------------------
			 * Step 3 — the checkout page
			 * ------------------------------------------------------------ */

			public function render_checkout_page() {
				if ( ! $this->is_active() ) {
					return $this->notice_html( __( 'Standalone checkout is not enabled on this site.', 'car-rental-manager' ) );
				}

				$token = isset( $_GET['mpcrbm_checkout'] ) ? sanitize_text_field( wp_unslash( $_GET['mpcrbm_checkout'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page key, not a state change.
				$draft = $token ? get_transient( 'mpcrbm_checkout_' . $token ) : false;
				if ( ! $token || ! is_array( $draft ) ) {
					return $this->notice_html( __( 'This checkout session has expired. Please search and select your vehicle again.', 'car-rental-manager' ) );
				}

				$car_id = absint( $draft['mpcrbm_id'] );
				$user   = wp_get_current_user();
				$this->enqueue_checkout_assets();

				ob_start();
				?>
				<div class="mpcrbm-checkout mpcrbm_style">
					<div class="mpcrbm-checkout-grid">

						<form class="mpcrbm-checkout-form" id="mpcrbm-checkout-form"
							data-token="<?php echo esc_attr( $token ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'mpcrbm_offline_place_order' ) ); ?>">
							<h2 class="mpcrbm-checkout-title"><?php esc_html_e( 'Your details', 'car-rental-manager' ); ?></h2>

							<div class="mpcrbm-checkout-fields">
								<p class="mpcrbm-field">
									<label for="mpcrbm_co_first_name"><?php esc_html_e( 'First name', 'car-rental-manager' ); ?> <span aria-hidden="true">*</span></label>
									<input type="text" id="mpcrbm_co_first_name" name="first_name" required value="<?php echo esc_attr( $user->first_name ); ?>">
								</p>
								<p class="mpcrbm-field">
									<label for="mpcrbm_co_last_name"><?php esc_html_e( 'Last name', 'car-rental-manager' ); ?> <span aria-hidden="true">*</span></label>
									<input type="text" id="mpcrbm_co_last_name" name="last_name" required value="<?php echo esc_attr( $user->last_name ); ?>">
								</p>
								<p class="mpcrbm-field">
									<label for="mpcrbm_co_email"><?php esc_html_e( 'Email address', 'car-rental-manager' ); ?> <span aria-hidden="true">*</span></label>
									<input type="email" id="mpcrbm_co_email" name="email" required value="<?php echo esc_attr( $user->user_email ); ?>">
								</p>
								<p class="mpcrbm-field">
									<label for="mpcrbm_co_phone"><?php esc_html_e( 'Phone', 'car-rental-manager' ); ?> <span aria-hidden="true">*</span></label>
									<input type="tel" id="mpcrbm_co_phone" name="phone" required value="<?php echo esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ); ?>">
								</p>
								<p class="mpcrbm-field mpcrbm-field--full">
									<label for="mpcrbm_co_note"><?php esc_html_e( 'Booking notes (optional)', 'car-rental-manager' ); ?></label>
									<textarea id="mpcrbm_co_note" name="note" rows="3"></textarea>
								</p>
							</div>

							<h2 class="mpcrbm-checkout-title"><?php esc_html_e( 'Payment', 'car-rental-manager' ); ?></h2>
							<div class="mpcrbm-pay-method is-selected">
								<span class="mpcrbm-pay-method-radio" aria-hidden="true"></span>
								<span class="mpcrbm-pay-method-text">
									<strong><?php echo esc_html( $this->offline_label() ); ?></strong>
									<?php $instructions = $this->opt( 'mpcrbm_offline_instructions' ); ?>
									<span><?php echo esc_html( $instructions ? $instructions : __( 'Pay on pickup, by cash or bank transfer. Your booking is confirmed once we receive payment.', 'car-rental-manager' ) ); ?></span>
								</span>
							</div>

							<p class="mpcrbm-checkout-error" id="mpcrbm-checkout-error" role="alert" hidden></p>

							<button type="submit" class="mpcrbm-checkout-submit">
								<?php esc_html_e( 'Place Booking', 'car-rental-manager' ); ?>
							</button>
						</form>

						<aside class="mpcrbm-checkout-summary">
							<h2 class="mpcrbm-checkout-title"><?php esc_html_e( 'Booking summary', 'car-rental-manager' ); ?></h2>
							<?php echo $this->render_summary( $draft, $car_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts below. ?>
						</aside>

					</div>
				</div>
				<?php

				return ob_get_clean();
			}

			/** Booking summary block, shared by the checkout page and the confirmation page. */
			private function render_summary( array $draft, $car_id ): string {
				$thumb    = get_the_post_thumbnail( $car_id, 'medium' );
				$services = isset( $draft['mpcrbm_service_info'] ) && is_array( $draft['mpcrbm_service_info'] ) ? $draft['mpcrbm_service_info'] : array();

				ob_start();
				?>
				<div class="mpcrbm-summary-card">
					<?php if ( $thumb ) : ?>
						<div class="mpcrbm-summary-thumb"><?php echo wp_kses_post( $thumb ); ?></div>
					<?php endif; ?>
					<h3 class="mpcrbm-summary-car"><?php echo esc_html( get_the_title( $car_id ) ); ?></h3>

					<ul class="mpcrbm-summary-rows">
						<?php if ( ! empty( $draft['mpcrbm_date'] ) ) : ?>
							<li><span><?php esc_html_e( 'Pickup', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $draft['mpcrbm_date'] ); ?></strong></li>
						<?php endif; ?>
						<?php if ( ! empty( $draft['return_date_time'] ) ) : ?>
							<li><span><?php esc_html_e( 'Return', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $draft['return_date_time'] ); ?></strong></li>
						<?php endif; ?>
						<?php if ( ! empty( $draft['mpcrbm_start_place'] ) ) : ?>
							<li><span><?php esc_html_e( 'Pickup location', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $draft['mpcrbm_start_place'] ); ?></strong></li>
						<?php endif; ?>
						<?php if ( ! empty( $draft['mpcrbm_end_place'] ) ) : ?>
							<li><span><?php esc_html_e( 'Drop-off location', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $draft['mpcrbm_end_place'] ); ?></strong></li>
						<?php endif; ?>
						<li><span><?php esc_html_e( 'Vehicles', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( (string) $draft['mpcrbm_car_quantity'] ); ?></strong></li>

						<?php foreach ( $services as $service ) : ?>
							<li>
								<span><?php echo esc_html( $service['service_name'] ); ?> &times; <?php echo esc_html( (string) $service['service_quantity'] ); ?></span>
								<strong><?php echo esc_html( $this->money( (float) $service['service_price'] * (int) $service['service_quantity'] ) ); ?></strong>
							</li>
						<?php endforeach; ?>

						<?php if ( ! empty( $draft['mpcrbm_security_deposit_amount'] ) ) : ?>
							<li><span><?php esc_html_e( 'Security deposit', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $this->money( $draft['mpcrbm_security_deposit_amount'] ) ); ?></strong></li>
						<?php endif; ?>
					</ul>

					<div class="mpcrbm-summary-total">
						<span><?php esc_html_e( 'Total', 'car-rental-manager' ); ?></span>
						<strong><?php echo esc_html( $this->money( $draft['mpcrbm_tp'] ) ); ?></strong>
					</div>
					<?php if ( ! empty( $draft['mpcrbm_security_deposit_amount'] ) ) : ?>
						<p class="mpcrbm-summary-note"><?php esc_html_e( 'The security deposit is refundable and collected separately.', 'car-rental-manager' ); ?></p>
					<?php endif; ?>
				</div>
				<?php

				return ob_get_clean();
			}

			private function notice_html( $message ): string {
				return '<div class="mpcrbm-checkout-notice">' . esc_html( $message ) . '</div>';
			}

			/* --------------------------------------------------------------
			 * Step 4 — place the booking
			 * ------------------------------------------------------------ */

			public function ajax_place_order() {
				check_ajax_referer( 'mpcrbm_offline_place_order', 'nonce' );

				if ( ! $this->is_active() ) {
					wp_send_json_error( array( 'message' => __( 'Offline payment is not available right now.', 'car-rental-manager' ) ) );
				}

				$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
				$draft = $token ? get_transient( 'mpcrbm_checkout_' . $token ) : false;
				if ( ! $token || ! is_array( $draft ) ) {
					wp_send_json_error( array( 'message' => __( 'Your checkout session has expired. Please search and select your vehicle again.', 'car-rental-manager' ) ) );
				}

				$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
				$last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
				$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
				$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

				if ( '' === $first || '' === $last || '' === $phone ) {
					wp_send_json_error( array( 'message' => __( 'Please fill in your name and phone number.', 'car-rental-manager' ) ) );
				}
				if ( ! is_email( $email ) ) {
					wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'car-rental-manager' ) ) );
				}

				$car_id = absint( $draft['mpcrbm_id'] );
				if ( ! $car_id || get_post_type( $car_id ) !== MPCRBM_Function::get_cpt() ) {
					wp_send_json_error( array( 'message' => __( 'This vehicle is no longer available.', 'car-rental-manager' ) ) );
				}

				// Re-check availability at the moment of purchase, not just when the
				// customer started checking out — someone else may have taken the last
				// vehicle while this form sat open.
				if ( class_exists( 'MPCRBM_Frontend' ) ) {
					$available = MPCRBM_Frontend::mpcrbm_get_available_stock_by_date( $car_id, $draft['mpcrbm_date'] );
					if ( 0 === $available ) {
						wp_send_json_error( array( 'message' => __( 'Sorry, this vehicle has just been booked for your selected dates. Please choose another date or vehicle.', 'car-rental-manager' ) ) );
					}
				}

				// The stored draft is the ONLY price source — the browser never gets a
				// say in what a booking costs.
				$booking_meta = array_merge( $draft, array(
					'mpcrbm_order_id'        => 0,
					'mpcrbm_order_status'    => 'pending',
					'mpcrbm_payment_method'  => $this->offline_label(),
					'mpcrbm_payment_gateway' => 'offline',
					'mpcrbm_booking_source'  => 'native',
					'mpcrbm_user_id'         => get_current_user_id(),
					'mpcrbm_billing_name'    => trim( $first . ' ' . $last ),
					'mpcrbm_billing_email'   => $email,
					'mpcrbm_billing_phone'   => $phone,
					'mpcrbm_customer_note'   => $note,
					'mpcrbm_booking_date'    => current_time( 'mysql' ),
				) );

				/** Same filter the WooCommerce flow applies, so integrations see both. */
				$booking_meta = apply_filters( 'mpcrbm_add_booking_data', $booking_meta, $car_id );

				$booking_id = $this->insert_booking( $booking_meta );
				if ( ! $booking_id ) {
					wp_send_json_error( array( 'message' => __( 'We could not save your booking. Please try again.', 'car-rental-manager' ) ) );
				}

				// Extra services get their own records, mirroring the WooCommerce flow so
				// service reporting works identically in both modes.
				if ( ! empty( $booking_meta['mpcrbm_service_info'] ) && is_array( $booking_meta['mpcrbm_service_info'] ) ) {
					foreach ( $booking_meta['mpcrbm_service_info'] as $service ) {
						MPCRBM_Woocommerce::mpcrbm_cpt_data( 'mpcrbm_service_booking', '#' . $booking_id . $service['service_name'], array(
							'mpcrbm_id'               => $car_id,
							'mpcrbm_date'             => $booking_meta['mpcrbm_date'],
							'mpcrbm_order_id'         => 0,
							'mpcrbm_booking_id'       => $booking_id,
							'mpcrbm_order_status'     => 'pending',
							'mpcrbm_service_name'     => $service['service_name'],
							'mpcrbm_service_quantity' => $service['service_quantity'],
							'mpcrbm_service_price'    => $service['service_price'],
							'mpcrbm_payment_method'   => $this->offline_label(),
							'mpcrbm_user_id'          => get_current_user_id(),
						) );
					}
				}

				delete_transient( 'mpcrbm_checkout_' . $token );

				$this->send_booking_email( $booking_id, $booking_meta );

				/** Fires once a standalone booking is fully recorded. */
				do_action( 'mpcrbm_native_booking_created', $booking_id, $booking_meta );

				$url = add_query_arg( array(
					'mpcrbm_booking_id' => $booking_id,
					'mpcrbm_key'        => MPCRBM_Function::issue_booking_access_token( $booking_id ),
				), $this->confirmation_url() );

				wp_send_json_success( array( 'redirect' => $url ) );
			}

			/**
			 * Write the booking post through MPCRBM_Woocommerce::mpcrbm_cpt_data(), so the
			 * PIN + order-post-id conventions live in exactly one place and a standalone
			 * booking is indistinguishable from a WooCommerce one to everything that
			 * reads bookings later.
			 */
			private function insert_booking( array $meta ) {
				$booking_id = MPCRBM_Woocommerce::mpcrbm_cpt_data( 'mpcrbm_booking', $meta['mpcrbm_billing_name'], $meta );

				return ( $booking_id && ! is_wp_error( $booking_id ) ) ? absint( $booking_id ) : 0;
			}

			/** Plain confirmation email to the customer. */
			private function send_booking_email( $booking_id, array $meta ) {
				$to = $meta['mpcrbm_billing_email'];
				if ( ! is_email( $to ) ) {
					return;
				}
				$subject = sprintf(
					/* translators: %s: booking reference number. */
					__( 'Your booking #%s is received', 'car-rental-manager' ),
					$booking_id
				);

				$lines   = array();
				$lines[] = '<p>' . sprintf(
					/* translators: %s: customer first name. */
					esc_html__( 'Hi %s,', 'car-rental-manager' ),
					esc_html( $meta['mpcrbm_billing_name'] )
				) . '</p>';
				$lines[] = '<p>' . esc_html__( 'Thanks for your booking. Here are the details:', 'car-rental-manager' ) . '</p>';
				$lines[] = '<ul>';
				$lines[] = '<li><strong>' . esc_html__( 'Reference', 'car-rental-manager' ) . ':</strong> #' . esc_html( (string) $booking_id ) . '</li>';
				$lines[] = '<li><strong>' . esc_html__( 'Vehicle', 'car-rental-manager' ) . ':</strong> ' . esc_html( get_the_title( $meta['mpcrbm_id'] ) ) . '</li>';
				if ( ! empty( $meta['mpcrbm_date'] ) ) {
					$lines[] = '<li><strong>' . esc_html__( 'Pickup', 'car-rental-manager' ) . ':</strong> ' . esc_html( $meta['mpcrbm_date'] ) . '</li>';
				}
				if ( ! empty( $meta['return_date_time'] ) ) {
					$lines[] = '<li><strong>' . esc_html__( 'Return', 'car-rental-manager' ) . ':</strong> ' . esc_html( $meta['return_date_time'] ) . '</li>';
				}
				$lines[] = '<li><strong>' . esc_html__( 'Total', 'car-rental-manager' ) . ':</strong> ' . esc_html( $this->money( $meta['mpcrbm_tp'] ) ) . '</li>';
				$lines[] = '<li><strong>' . esc_html__( 'Payment', 'car-rental-manager' ) . ':</strong> ' . esc_html( $this->offline_label() ) . '</li>';
				$lines[] = '</ul>';

				$instructions = $this->opt( 'mpcrbm_offline_instructions' );
				if ( $instructions ) {
					$lines[] = '<p>' . esc_html( $instructions ) . '</p>';
				}

				$body = apply_filters( 'mpcrbm_native_booking_email_body', implode( "\n", $lines ), $booking_id, $meta );

				wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			}

			/* --------------------------------------------------------------
			 * Confirmation page
			 * ------------------------------------------------------------ */

			public function render_confirmation_page() {
				$booking_id = isset( $_GET['mpcrbm_booking_id'] ) ? absint( $_GET['mpcrbm_booking_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$key        = isset( $_GET['mpcrbm_key'] ) ? sanitize_text_field( wp_unslash( $_GET['mpcrbm_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( ! $booking_id || 'mpcrbm_booking' !== get_post_type( $booking_id ) ) {
					return $this->notice_html( __( 'Booking not found.', 'car-rental-manager' ) );
				}

				// The random access token is what makes this page safe to visit while
				// logged out. It deliberately is NOT `mpcrbm_pin` — that value is derived
				// from sequential ids, so anyone could enumerate other customers' bookings
				// with it. See MPCRBM_Function::issue_booking_access_token().
				if ( ! MPCRBM_Function::verify_booking_access_token( $booking_id, $key ) ) {
					return $this->notice_html( __( 'Booking not found.', 'car-rental-manager' ) );
				}

				$car_id = absint( get_post_meta( $booking_id, 'mpcrbm_id', true ) );
				$draft  = array(
					'mpcrbm_id'                      => $car_id,
					'mpcrbm_date'                    => get_post_meta( $booking_id, 'mpcrbm_date', true ),
					'return_date_time'               => get_post_meta( $booking_id, 'return_date_time', true ),
					'mpcrbm_start_place'             => get_post_meta( $booking_id, 'mpcrbm_start_place', true ),
					'mpcrbm_end_place'               => get_post_meta( $booking_id, 'mpcrbm_end_place', true ),
					'mpcrbm_car_quantity'            => get_post_meta( $booking_id, 'mpcrbm_car_quantity', true ),
					'mpcrbm_tp'                      => get_post_meta( $booking_id, 'mpcrbm_tp', true ),
					'mpcrbm_service_info'            => get_post_meta( $booking_id, 'mpcrbm_service_info', true ),
					'mpcrbm_security_deposit_amount' => get_post_meta( $booking_id, 'mpcrbm_security_deposit_amount', true ),
				);

				$this->enqueue_checkout_assets();

				ob_start();
				?>
				<div class="mpcrbm-checkout mpcrbm-confirmation mpcrbm_style">
					<div class="mpcrbm-confirm-head">
						<span class="mpcrbm-confirm-tick" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
						</span>
						<h2><?php esc_html_e( 'Booking received', 'car-rental-manager' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %s: booking reference number. */
								esc_html__( 'Your booking reference is #%s. We have emailed a copy of these details to you.', 'car-rental-manager' ),
								esc_html( (string) $booking_id )
							);
							?>
						</p>
					</div>

					<?php echo $this->render_summary( $draft, $car_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>

					<?php $instructions = $this->opt( 'mpcrbm_offline_instructions' ); ?>
					<?php if ( $instructions ) : ?>
						<div class="mpcrbm-confirm-instructions">
							<h3><?php esc_html_e( 'Payment instructions', 'car-rental-manager' ); ?></h3>
							<p><?php echo esc_html( $instructions ); ?></p>
						</div>
					<?php endif; ?>
				</div>
				<?php

				return ob_get_clean();
			}

			/* --------------------------------------------------------------
			 * Assets
			 * ------------------------------------------------------------ */

			private function enqueue_checkout_assets() {
				static $done = false;
				if ( $done ) {
					return;
				}
				$done = true;

				$css_path = MPCRBM_PLUGIN_DIR . '/assets/frontend/mpcrbm-checkout.css';
				$js_path  = MPCRBM_PLUGIN_DIR . '/assets/frontend/mpcrbm-checkout.js';

				wp_enqueue_style(
					'mpcrbm-checkout',
					MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-checkout.css',
					array(),
					file_exists( $css_path ) ? (string) filemtime( $css_path ) : MPCRBM_PLUGIN_VERSION
				);
				wp_enqueue_script(
					'mpcrbm-checkout',
					MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-checkout.js',
					array( 'jquery' ),
					file_exists( $js_path ) ? (string) filemtime( $js_path ) : MPCRBM_PLUGIN_VERSION,
					true
				);
				wp_localize_script( 'mpcrbm-checkout', 'mpcrbmCheckout', array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'placing' => __( 'Placing your booking…', 'car-rental-manager' ),
						'submit'  => __( 'Place Booking', 'car-rental-manager' ),
						'error'   => __( 'Something went wrong. Please try again.', 'car-rental-manager' ),
					),
				) );
			}
		}

		new MPCRBM_Offline_Checkout();
	}
