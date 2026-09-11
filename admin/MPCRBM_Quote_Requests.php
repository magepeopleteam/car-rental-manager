<?php
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 *
	 * "Request a Better Price" (RFQ) inbox.
	 *
	 * A customer who feels the listed price is too high (bulk/long-term/corporate
	 * terms) can submit a request right at checkout, next to the order Total,
	 * instead of leaving the site. Car + dates are resolved server-side from
	 * their live WooCommerce cart (see resolve_cart_booking()) rather than
	 * trusting anything the browser posts, and the checkout form is already
	 * collecting their name/email/phone, so the request only ever needs a
	 * price (+ optional note) typed in — see assets/frontend/mpcrbm-rfq-checkout.js.
	 *
	 * This screen is where the admin reviews those requests and responds —
	 * deliberately NOT a new pricing/checkout system: responding creates a personal,
	 * email-restricted WooCommerce coupon through the exact same code path as the
	 * "Give Discount" feature on the Customers screen (MPCRBM_Customers::
	 * ajax_give_discount()/ajax_send_discount_email()), which already works across
	 * WooCommerce checkout AND both Custom Payment flows (free + Pro). Reusing it
	 * means the price-override side of RFQ needed zero new checkout code.
	 *
	 * Data lives on a hidden CPT (mpcrbm_quote) — not public, no native UI, browsed
	 * only through this screen — one post per request.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MPCRBM_Quote_Requests' ) ) {
		class MPCRBM_Quote_Requests {

			const CPT  = 'mpcrbm_quote';
			const SLUG = 'mpcrbm_quote_requests';

			public function __construct() {
				add_action( 'init', [ $this, 'register_cpt' ] );
				add_action( 'admin_menu', [ $this, 'add_menu' ] );
				add_filter( 'mpcrbm_shell_menu_items', [ $this, 'add_shell_menu_item' ] );
				add_filter( 'mpcrbm_shell_screen_ids', [ $this, 'add_shell_screen_id' ] );

				add_action( 'wp_ajax_mpcrbm_submit_quote_request', [ $this, 'ajax_submit_quote_request' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_submit_quote_request', [ $this, 'ajax_submit_quote_request' ] );
				add_action( 'wp_ajax_mpcrbm_resolve_quote_request', [ $this, 'ajax_resolve_quote_request' ] );
				add_action( 'wp_ajax_mpcrbm_dismiss_quote_request', [ $this, 'ajax_dismiss_quote_request' ] );
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_assets' ] );
			}

			/**
			 * Only on the WooCommerce Checkout page, and only when the admin has
			 * turned the button on. jQuery is declared as a hard dependency since
			 * the WooCommerce Checkout block doesn't load it itself.
			 */
			public function enqueue_checkout_assets() {
				if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
					return;
				}
				if ( MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'car_details_rfq_button' ) !== 'yes' ) {
					return;
				}

				$path = MPCRBM_PLUGIN_DIR . '/assets/frontend/mpcrbm-rfq-checkout.js';
				wp_enqueue_script(
					'mpcrbm-rfq-checkout',
					MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-rfq-checkout.js',
					[ 'jquery' ],
					file_exists( $path ) ? (string) filemtime( $path ) : MPCRBM_PLUGIN_VERSION,
					true
				);
				wp_localize_script( 'mpcrbm-rfq-checkout', 'mpcrbmRfqCheckout', [
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mpcrbm_quote_request' ),
					'i18n'    => [
						'button'      => __( 'Request a Better Price', 'car-rental-manager' ),
						'title'       => __( 'Request a Better Price', 'car-rental-manager' ),
						'intro'       => __( 'We\'ll email you a special price for this booking if we can offer one.', 'car-rental-manager' ),
						'priceLabel'  => __( 'Your Proposed Price', 'car-rental-manager' ),
						'noteLabel'   => __( 'Note (optional)', 'car-rental-manager' ),
						'send'        => __( 'Send Request', 'car-rental-manager' ),
						'cancel'      => __( 'Cancel', 'car-rental-manager' ),
						'sending'     => __( 'Sending…', 'car-rental-manager' ),
						'enterPrice'  => __( 'Please enter your proposed price.', 'car-rental-manager' ),
						'nameLabel'   => __( 'Your Name', 'car-rental-manager' ),
						'emailLabel'  => __( 'Email', 'car-rental-manager' ),
						'phoneLabel'  => __( 'Phone (optional)', 'car-rental-manager' ),
						'enterNameEmail' => __( 'Please enter your name and email.', 'car-rental-manager' ),
						'error'       => __( 'Something went wrong. Please try again.', 'car-rental-manager' ),
					],
				] );
			}

			public function register_cpt() {
				register_post_type( self::CPT, [
					'label'               => __( 'Quote Requests', 'car-rental-manager' ),
					'public'              => false,
					'show_ui'             => false,
					'show_in_menu'        => false,
					'supports'            => [ 'title' ],
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'exclude_from_search' => true,
					'show_in_nav_menus'   => false,
					'has_archive'         => false,
					'rewrite'             => false,
				] );
			}

			public function add_menu() {
				add_submenu_page(
					'edit.php?post_type=' . MPCRBM_Function::get_cpt(),
					__( 'Quote Requests', 'car-rental-manager' ),
					__( 'Quote Requests', 'car-rental-manager' ),
					'manage_options',
					self::SLUG,
					[ $this, 'render_page' ]
				);
			}

			public function add_shell_menu_item( $items ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $items;
				}
				$items[] = [
					'slug'  => self::SLUG,
					'label' => __( 'Quote Requests', 'car-rental-manager' ),
					'icon'  => 'fas fa-hand-holding-usd',
					'link'  => admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=' . self::SLUG ),
				];

				return $items;
			}

			public function add_shell_screen_id( $ids ) {
				$ids[] = MPCRBM_Function::get_cpt() . '_page_' . self::SLUG;

				return $ids;
			}

			/**
			 * Resolves which car/dates the "Request a Better Price" click was for,
			 * without ever trusting the browser for it — checked against two
			 * possible server-side sources depending on which checkout is active:
			 *
			 * 1. WooCommerce Checkout (block or classic) — reads the plugin's own
			 *    cart item (MPCRBM_Woocommerce::cart_item_data()) out of the
			 *    customer's live WC cart.
			 * 2. Custom Payment checkout (free MPCRBM_Offline_Checkout or Pro
			 *    MPCRBM_Native_Checkout — both park an identical booking draft in
			 *    a transient keyed "mpcrbm_checkout_" + a per-session token, so one
			 *    lookup here covers either plugin) — reads that parked draft using
			 *    the token the checkout page itself embedded in its own markup.
			 *
			 * @return array{car_id:int,pickup:string,return:string}|null
			 */
			private function resolve_cart_booking() {
				if ( function_exists( 'WC' ) && WC()->cart ) {
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( ! empty( $cart_item['mpcrbm_id'] ) ) {
							return [
								'car_id' => absint( $cart_item['mpcrbm_id'] ),
								'pickup' => (string) ( $cart_item['mpcrbm_date'] ?? '' ),
								'return' => (string) ( $cart_item['return_date_time'] ?? '' ),
							];
						}
					}
				}

				$checkout_token = isset( $_POST['checkout_token'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_token'] ) ) : '';
				if ( '' !== $checkout_token ) {
					$draft = get_transient( 'mpcrbm_checkout_' . $checkout_token );
					if ( is_array( $draft ) && ! empty( $draft['mpcrbm_id'] ) ) {
						return [
							'car_id' => absint( $draft['mpcrbm_id'] ),
							'pickup' => (string) ( $draft['mpcrbm_date'] ?? '' ),
							'return' => (string) ( $draft['return_date_time'] ?? '' ),
						];
					}
				}

				return null;
			}

			// =========================================================
			// Frontend: customer submits a request from the Checkout page
			// =========================================================
			public function ajax_submit_quote_request() {
				check_ajax_referer( 'mpcrbm_quote_request', 'nonce' );

				$booking = $this->resolve_cart_booking();
				if ( ! $booking ) {
					wp_send_json_error( [ 'message' => __( 'We could not find a vehicle in your cart. Please select a car and dates first.', 'car-rental-manager' ) ] );
				}
				$car_id = $booking['car_id'];
				$pickup = $booking['pickup'];
				$return = $booking['return'];

				$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
				$requested_price = isset( $_POST['requested_price'] ) && is_numeric( $_POST['requested_price'] )
					? abs( floatval( wp_unslash( $_POST['requested_price'] ) ) )
					: 0.0;
				$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

				if ( '' === $name || ! is_email( $email ) ) {
					wp_send_json_error( [ 'message' => __( 'Please enter your name and a valid email address.', 'car-rental-manager' ) ] );
				}
				if ( $requested_price <= 0 ) {
					wp_send_json_error( [ 'message' => __( 'Please enter your proposed price.', 'car-rental-manager' ) ] );
				}

				$post_id = wp_insert_post( [
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => sprintf( '%s — %s', get_the_title( $car_id ), $name ),
				], true );

				if ( is_wp_error( $post_id ) || ! $post_id ) {
					wp_send_json_error( [ 'message' => __( 'Could not submit your request. Please try again.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $post_id, 'mpcrbm_rfq_car_id', $car_id );
				update_post_meta( $post_id, 'mpcrbm_rfq_name', $name );
				update_post_meta( $post_id, 'mpcrbm_rfq_email', $email );
				update_post_meta( $post_id, 'mpcrbm_rfq_phone', $phone );
				update_post_meta( $post_id, 'mpcrbm_rfq_pickup', $pickup );
				update_post_meta( $post_id, 'mpcrbm_rfq_return', $return );
				update_post_meta( $post_id, 'mpcrbm_rfq_requested_price', $requested_price );
				update_post_meta( $post_id, 'mpcrbm_rfq_note', $note );
				update_post_meta( $post_id, 'mpcrbm_rfq_status', 'pending' );

				// Let the admin know a request is waiting, without requiring them to
				// keep the Quote Requests screen open.
				$admin_email = get_option( 'admin_email' );
				if ( $admin_email ) {
					wp_mail(
						$admin_email,
						sprintf( /* translators: %s: site name */ __( '[%s] New price request', 'car-rental-manager' ), get_bloginfo( 'name' ) ),
						sprintf(
							/* translators: 1: car name, 2: customer name, 3: customer email, 4: admin link */
							__( "%2\$s (%3\$s) asked for a better price on \"%1\$s\". Review it here: %4\$s", 'car-rental-manager' ),
							get_the_title( $car_id ),
							$name,
							$email,
							admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=' . self::SLUG )
						)
					);
				}

				wp_send_json_success( [
					'message' => __( 'Thanks! We\'ll email you a special price shortly.', 'car-rental-manager' ),
				] );
			}

			// =========================================================
			// Admin: mark a request resolved once a discount coupon has
			// been created + emailed for it (via the existing Customers
			// screen AJAX actions, called from this screen's own JS).
			// =========================================================
			public function ajax_resolve_quote_request() {
				check_ajax_referer( 'mpcrbm_quote_requests_admin', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
				}

				$quote_id = isset( $_POST['quote_id'] ) ? absint( $_POST['quote_id'] ) : 0;
				$code     = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
				if ( ! $quote_id || get_post_type( $quote_id ) !== self::CPT ) {
					wp_send_json_error( [ 'message' => __( 'Request not found.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $quote_id, 'mpcrbm_rfq_status', 'resolved' );
				update_post_meta( $quote_id, 'mpcrbm_rfq_coupon_code', $code );
				update_post_meta( $quote_id, 'mpcrbm_rfq_resolved_at', current_time( 'mysql' ) );

				wp_send_json_success( [ 'html' => self::render_list() ] );
			}

			public function ajax_dismiss_quote_request() {
				check_ajax_referer( 'mpcrbm_quote_requests_admin', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
				}

				$quote_id = isset( $_POST['quote_id'] ) ? absint( $_POST['quote_id'] ) : 0;
				if ( ! $quote_id || get_post_type( $quote_id ) !== self::CPT ) {
					wp_send_json_error( [ 'message' => __( 'Request not found.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $quote_id, 'mpcrbm_rfq_status', 'dismissed' );

				wp_send_json_success( [ 'html' => self::render_list() ] );
			}

			// =========================================================
			// Admin page
			// =========================================================
			private static function status_badge( $status ) {
				$map = [
					'pending'   => [ '#b45309', '#fffbeb', __( 'Pending', 'car-rental-manager' ) ],
					'resolved'  => [ '#166534', '#f0fdf4', __( 'Resolved', 'car-rental-manager' ) ],
					'dismissed' => [ '#6b7280', '#f3f4f6', __( 'Dismissed', 'car-rental-manager' ) ],
				];
				[ $color, $bg, $label ] = $map[ $status ] ?? $map['pending'];

				return sprintf(
					'<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;color:%s;background:%s;">%s</span>',
					esc_attr( $color ),
					esc_attr( $bg ),
					esc_html( $label )
				);
			}

			public static function render_list(): string {
				$query = new WP_Query( [
					'post_type'      => self::CPT,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				] );

				ob_start();

				if ( ! $query->have_posts() ) {
					?>
					<div class="mpcrbm-rfq-empty">
						<i class="fas fa-hand-holding-usd"></i>
						<p><?php esc_html_e( 'No price requests yet.', 'car-rental-manager' ); ?></p>
						<span><?php esc_html_e( 'When a customer asks for a better price on a car details page, it will show up here.', 'car-rental-manager' ); ?></span>
					</div>
					<?php
					wp_reset_postdata();

					return (string) ob_get_clean();
				}

				$currency = class_exists( 'MPCRBM_Global_Function' ) ? html_entity_decode( MPCRBM_Global_Function::currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '';

				echo '<div class="mpcrbm-rfq-list">';
				while ( $query->have_posts() ) {
					$query->the_post();
					$id              = get_the_ID();
					$car_id          = (int) get_post_meta( $id, 'mpcrbm_rfq_car_id', true );
					$name            = get_post_meta( $id, 'mpcrbm_rfq_name', true );
					$email           = get_post_meta( $id, 'mpcrbm_rfq_email', true );
					$phone           = get_post_meta( $id, 'mpcrbm_rfq_phone', true );
					$pickup          = get_post_meta( $id, 'mpcrbm_rfq_pickup', true );
					$return          = get_post_meta( $id, 'mpcrbm_rfq_return', true );
					$requested_price = floatval( get_post_meta( $id, 'mpcrbm_rfq_requested_price', true ) );
					$note            = get_post_meta( $id, 'mpcrbm_rfq_note', true );
					$status          = get_post_meta( $id, 'mpcrbm_rfq_status', true ) ?: 'pending';
					$coupon_code     = get_post_meta( $id, 'mpcrbm_rfq_coupon_code', true );
					$car_name        = $car_id ? get_the_title( $car_id ) : __( '(vehicle removed)', 'car-rental-manager' );
					?>
					<div class="mpcrbm-rfq-card" data-quote-id="<?php echo esc_attr( $id ); ?>">
						<div class="mpcrbm-rfq-card-top">
							<div>
								<strong class="mpcrbm-rfq-car"><?php echo esc_html( $car_name ); ?></strong>
								<span class="mpcrbm-rfq-date"><?php echo esc_html( get_the_date( 'j M Y, g:ia' ) ); ?></span>
							</div>
							<?php echo wp_kses_post( self::status_badge( $status ) ); ?>
						</div>
						<div class="mpcrbm-rfq-card-body">
							<div><strong><?php esc_html_e( 'Customer:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $name ); ?> — <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php echo $phone ? ' — ' . esc_html( $phone ) : ''; ?></div>
							<?php if ( $pickup || $return ) : ?>
								<div><strong><?php esc_html_e( 'Dates:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $pickup ); ?><?php echo $return ? ' → ' . esc_html( $return ) : ''; ?></div>
							<?php endif; ?>
							<?php if ( $requested_price > 0 ) : ?>
								<div><strong><?php esc_html_e( 'Customer proposed:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $currency . number_format( $requested_price, 2 ) ); ?></div>
							<?php endif; ?>
							<?php if ( $note ) : ?>
								<div class="mpcrbm-rfq-note"><strong><?php esc_html_e( 'Note:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $note ); ?></div>
							<?php endif; ?>
							<?php if ( 'resolved' === $status && $coupon_code ) : ?>
								<div><strong><?php esc_html_e( 'Coupon sent:', 'car-rental-manager' ); ?></strong> <code><?php echo esc_html( $coupon_code ); ?></code></div>
							<?php endif; ?>
						</div>
						<?php if ( 'pending' === $status ) : ?>
							<div class="mpcrbm-rfq-card-actions">
								<button type="button" class="button button-primary mpcrbm-rfq-respond-btn"
									data-quote-id="<?php echo esc_attr( $id ); ?>"
									data-name="<?php echo esc_attr( $name ); ?>"
									data-email="<?php echo esc_attr( $email ); ?>">
									<?php esc_html_e( 'Respond with a Discount', 'car-rental-manager' ); ?>
								</button>
								<button type="button" class="button mpcrbm-rfq-dismiss-btn" data-quote-id="<?php echo esc_attr( $id ); ?>">
									<?php esc_html_e( 'Dismiss', 'car-rental-manager' ); ?>
								</button>
							</div>
						<?php endif; ?>
					</div>
					<?php
				}
				echo '</div>';
				wp_reset_postdata();

				return (string) ob_get_clean();
			}

			public function render_page() {
				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Quote Requests', 'car-rental-manager' ) );
				$admin_nonce = wp_create_nonce( 'mpcrbm_quote_requests_admin' );
				$customers_nonce = wp_create_nonce( 'mpcrbm_customers' );
				?>
				<style>
				.mpcrbm-rfq-head{margin-bottom:20px;}
				.mpcrbm-rfq-head h2{margin:0 0 6px;font-size:22px;}
				.mpcrbm-rfq-head p{margin:0;color:#6b7280;font-size:13px;}
				.mpcrbm-rfq-empty{text-align:center;padding:60px 20px;color:#9ca3af;}
				.mpcrbm-rfq-empty i{font-size:32px;margin-bottom:10px;display:block;}
				.mpcrbm-rfq-list{display:flex;flex-direction:column;gap:12px;}
				.mpcrbm-rfq-card{background:#fff;border:1px solid #e5e9f0;border-radius:10px;padding:16px 18px;}
				.mpcrbm-rfq-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
				.mpcrbm-rfq-car{font-size:15px;color:#111827;}
				.mpcrbm-rfq-date{margin-left:10px;font-size:12px;color:#9ca3af;}
				.mpcrbm-rfq-card-body{font-size:13px;color:#374151;display:flex;flex-direction:column;gap:4px;}
				.mpcrbm-rfq-note{background:#f9fafb;padding:8px 10px;border-radius:6px;}
				.mpcrbm-rfq-card-actions{margin-top:12px;display:flex;gap:8px;}
				.mpcrbm-rfq-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:99999;align-items:center;justify-content:center;}
				.mpcrbm-rfq-modal-overlay.is-open{display:flex;}
				.mpcrbm-rfq-modal{background:#fff;border-radius:12px;width:100%;max-width:460px;padding:24px;max-height:90vh;overflow-y:auto;}
				.mpcrbm-rfq-modal h3{margin:0 0 16px;}
				.mpcrbm-rfq-modal label{display:block;font-weight:600;font-size:12px;margin:12px 0 4px;}
				.mpcrbm-rfq-modal input[type=text],.mpcrbm-rfq-modal input[type=number],.mpcrbm-rfq-modal input[type=date],.mpcrbm-rfq-modal select{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;}
				.mpcrbm-rfq-modal-actions{margin-top:20px;display:flex;gap:8px;justify-content:flex-end;}
				.mpcrbm-rfq-modal-result{margin-top:12px;}

				/* ── In-page guide ─────────────────────────────────── */
				.mpcrbm-rfq-guide{border:1px solid #e5e9f0;border-radius:12px;background:#fff;margin-bottom:24px;overflow:hidden;}
				.mpcrbm-rfq-guide-toggle{width:100%;text-align:left;background:linear-gradient(135deg,#eef2ff,#f5f3ff);border:none;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;gap:12px;}
				.mpcrbm-rfq-guide-toggle-left{display:flex;align-items:center;gap:12px;}
				.mpcrbm-rfq-guide-toggle-left i{font-size:18px;color:#4f46e5;width:34px;height:34px;border-radius:10px;background:#e0e7ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
				.mpcrbm-rfq-guide-toggle-left strong{display:block;font-size:14px;color:#1e1b4b;}
				.mpcrbm-rfq-guide-toggle-left span{display:block;font-size:12px;color:#6366f1;}
				.mpcrbm-rfq-guide-toggle-caret{color:#6366f1;transition:transform .2s;}
				.mpcrbm-rfq-guide.is-collapsed .mpcrbm-rfq-guide-toggle-caret{transform:rotate(-90deg);}
				.mpcrbm-rfq-guide-body{padding:22px 24px;}
				.mpcrbm-rfq-guide.is-collapsed .mpcrbm-rfq-guide-body{display:none;}

				.mpcrbm-rfq-guide-why{display:flex;gap:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-bottom:24px;}
				.mpcrbm-rfq-guide-why i{color:#2563eb;font-size:18px;margin-top:2px;}
				.mpcrbm-rfq-guide-why strong{display:block;color:#1e3a8a;font-size:13px;margin-bottom:3px;}
				.mpcrbm-rfq-guide-why span{color:#1e40af;font-size:13px;line-height:1.6;}

				.mpcrbm-rfq-guide-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;margin:0 0 12px;}

				.mpcrbm-rfq-flow{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:26px;}
				.mpcrbm-rfq-flow-step{border-radius:10px;padding:14px;border:1.5px solid;}
				.mpcrbm-rfq-flow-step .num{width:24px;height:24px;border-radius:50%;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-bottom:8px;}
				.mpcrbm-rfq-flow-step strong{display:block;font-size:13px;margin-bottom:4px;}
				.mpcrbm-rfq-flow-step span{font-size:12px;line-height:1.5;display:block;}
				.mpcrbm-rfq-flow-step.c1{background:#eff6ff;border-color:#bfdbfe;} .mpcrbm-rfq-flow-step.c1 .num{background:#3b82f6;} .mpcrbm-rfq-flow-step.c1 strong{color:#1e3a8a;} .mpcrbm-rfq-flow-step.c1 span{color:#1e40af;}
				.mpcrbm-rfq-flow-step.c2{background:#fefce8;border-color:#fde68a;} .mpcrbm-rfq-flow-step.c2 .num{background:#d97706;} .mpcrbm-rfq-flow-step.c2 strong{color:#78350f;} .mpcrbm-rfq-flow-step.c2 span{color:#92400e;}
				.mpcrbm-rfq-flow-step.c3{background:#faf5ff;border-color:#e9d5ff;} .mpcrbm-rfq-flow-step.c3 .num{background:#9333ea;} .mpcrbm-rfq-flow-step.c3 strong{color:#581c87;} .mpcrbm-rfq-flow-step.c3 span{color:#6b21a8;}
				.mpcrbm-rfq-flow-step.c4{background:#f0fdf4;border-color:#bbf7d0;} .mpcrbm-rfq-flow-step.c4 .num{background:#16a34a;} .mpcrbm-rfq-flow-step.c4 strong{color:#14532d;} .mpcrbm-rfq-flow-step.c4 span{color:#166534;}

				.mpcrbm-rfq-howto{list-style:none;margin:0 0 26px;padding:0;display:flex;flex-direction:column;gap:10px;}
				.mpcrbm-rfq-howto li{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#374151;line-height:1.6;}
				.mpcrbm-rfq-howto li b{background:#111827;color:#fff;border-radius:50%;width:20px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;margin-top:1px;}
				.mpcrbm-rfq-howto li code{background:#f3f4f6;padding:1px 6px;border-radius:4px;font-size:12px;}

				.mpcrbm-rfq-legend{display:flex;flex-wrap:wrap;gap:18px;background:#f9fafb;border-radius:10px;padding:12px 16px;}
				.mpcrbm-rfq-legend-item{display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;}
				</style>

				<div class="mpcrbm-rfq-head">
					<h2><?php esc_html_e( 'Quote Requests', 'car-rental-manager' ); ?></h2>
					<p><?php esc_html_e( 'Requests customers submitted at checkout, asking for a special price. Respond with a personal discount code — it works across WooCommerce and Custom Payment checkout alike.', 'car-rental-manager' ); ?></p>
				</div>

				<div class="mpcrbm-rfq-guide" id="mpcrbm-rfq-guide">
					<button type="button" class="mpcrbm-rfq-guide-toggle" id="mpcrbm-rfq-guide-toggle">
						<span class="mpcrbm-rfq-guide-toggle-left">
							<i class="fas fa-circle-info"></i>
							<span>
								<strong><?php esc_html_e( 'What is this page & how do I use it?', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'Click to read a quick guide', 'car-rental-manager' ); ?></span>
							</span>
						</span>
						<i class="fas fa-chevron-down mpcrbm-rfq-guide-toggle-caret"></i>
					</button>

					<div class="mpcrbm-rfq-guide-body">
						<div class="mpcrbm-rfq-guide-why">
							<i class="fas fa-lightbulb"></i>
							<div>
								<strong><?php esc_html_e( 'Why this exists', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'Some customers think your listed price is too high, or want a special rate for a long booking, bulk hire, or corporate deal. Without this, they simply leave your site. This page lets you catch that request and win the booking back with a price you choose — instead of losing it.', 'car-rental-manager' ); ?></span>
							</div>
						</div>

						<p class="mpcrbm-rfq-guide-section-title"><?php esc_html_e( 'How it works, step by step', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-rfq-flow">
							<div class="mpcrbm-rfq-flow-step c1">
								<span class="num">1</span>
								<strong><?php esc_html_e( 'Customer asks', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'At checkout, next to the Total, the customer clicks "Request a Better Price" and types the price they want.', 'car-rental-manager' ); ?></span>
							</div>
							<div class="mpcrbm-rfq-flow-step c2">
								<span class="num">2</span>
								<strong><?php esc_html_e( 'It lands here', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'The request appears below as "Pending", with the car, dates, and the customer\'s contact info.', 'car-rental-manager' ); ?></span>
							</div>
							<div class="mpcrbm-rfq-flow-step c3">
								<span class="num">3</span>
								<strong><?php esc_html_e( 'You respond', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'Click "Respond with a Discount", enter an amount, and a personal coupon is created just for that customer\'s email.', 'car-rental-manager' ); ?></span>
							</div>
							<div class="mpcrbm-rfq-flow-step c4">
								<span class="num">4</span>
								<strong><?php esc_html_e( 'Customer books', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'The coupon code is emailed automatically. The customer applies it at checkout and completes the booking at the agreed price.', 'car-rental-manager' ); ?></span>
							</div>
						</div>

						<p class="mpcrbm-rfq-guide-section-title"><?php esc_html_e( 'Using this page', 'car-rental-manager' ); ?></p>
						<ul class="mpcrbm-rfq-howto">
							<li><b>1</b><span><?php echo wp_kses_post( __( 'First, make sure the feature is turned on: go to <code>Settings → General → Show "Request a Better Price" Button</code> and set it to Yes.', 'car-rental-manager' ) ); ?></span></li>
							<li><b>2</b><span><?php esc_html_e( 'New requests show up here automatically, and you also get an email the moment one is submitted — no need to keep this page open.', 'car-rental-manager' ); ?></span></li>
							<li><b>3</b><span><?php echo wp_kses_post( __( 'Click <strong>Respond with a Discount</strong>, choose <strong>Fixed Amount</strong> (a flat amount off) or <strong>Percentage</strong>, and enter the value. For a Fixed Amount: <code>discount = normal price − the price you want to give</code>.', 'car-rental-manager' ) ); ?></span></li>
							<li><b>4</b><span><?php esc_html_e( 'Set "Max Uses" to 1 unless you want the same customer to reuse this code more than once.', 'car-rental-manager' ); ?></span></li>
							<li><b>5</b><span><?php esc_html_e( 'Click "Create & Email Discount" — the coupon is created, emailed to the customer, and shown to you too so you can share it yourself (SMS, WhatsApp, phone) if you like.', 'car-rental-manager' ); ?></span></li>
							<li><b>6</b><span><?php esc_html_e( 'Not able to offer a better price? Click "Dismiss" instead — no coupon is created and the request is closed.', 'car-rental-manager' ); ?></span></li>
						</ul>

						<p class="mpcrbm-rfq-guide-section-title"><?php esc_html_e( 'Status colors', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-rfq-legend">
							<div class="mpcrbm-rfq-legend-item"><?php echo wp_kses_post( self::status_badge( 'pending' ) ); ?> <?php esc_html_e( 'waiting for your response', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-rfq-legend-item"><?php echo wp_kses_post( self::status_badge( 'resolved' ) ); ?> <?php esc_html_e( 'a discount was created and emailed', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-rfq-legend-item"><?php echo wp_kses_post( self::status_badge( 'dismissed' ) ); ?> <?php esc_html_e( 'closed without a discount', 'car-rental-manager' ); ?></div>
						</div>
					</div>
				</div>

				<script>
				(function($){
					$(document).on('click', '#mpcrbm-rfq-guide-toggle', function(){
						$('#mpcrbm-rfq-guide').toggleClass('is-collapsed');
						try {
							localStorage.setItem('mpcrbm_rfq_guide_collapsed', $('#mpcrbm-rfq-guide').hasClass('is-collapsed') ? '1' : '0');
						} catch(e) {}
					});
					try {
						if( localStorage.getItem('mpcrbm_rfq_guide_collapsed') === '1' ){
							$('#mpcrbm-rfq-guide').addClass('is-collapsed');
						}
					} catch(e) {}
				})(jQuery);
				</script>

				<div id="mpcrbm-rfq-list-holder"><?php echo self::render_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html()/esc_attr()'d pieces above. ?></div>

				<div class="mpcrbm-rfq-modal-overlay" id="mpcrbm-rfq-modal-overlay">
					<form class="mpcrbm-rfq-modal" id="mpcrbm-rfq-modal-form">
						<h3><?php esc_html_e( 'Send a Personal Discount', 'car-rental-manager' ); ?></h3>
						<input type="hidden" id="mpcrbm-rfq-quote-id">
						<input type="hidden" id="mpcrbm-rfq-email">

						<div id="mpcrbm-rfq-form-fields">
							<label for="mpcrbm-rfq-name-display"><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></label>
							<input type="text" id="mpcrbm-rfq-name-display" disabled>

							<label for="mpcrbm-rfq-type"><?php esc_html_e( 'Discount Type', 'car-rental-manager' ); ?></label>
							<select id="mpcrbm-rfq-type">
								<option value="fixed"><?php esc_html_e( 'Fixed Amount', 'car-rental-manager' ); ?></option>
								<option value="percent"><?php esc_html_e( 'Percentage', 'car-rental-manager' ); ?></option>
							</select>

							<label for="mpcrbm-rfq-amount"><?php esc_html_e( 'Amount', 'car-rental-manager' ); ?></label>
							<input type="number" id="mpcrbm-rfq-amount" min="0.01" step="0.01" required>

							<label for="mpcrbm-rfq-max-uses"><?php esc_html_e( 'Max Uses', 'car-rental-manager' ); ?></label>
							<input type="number" id="mpcrbm-rfq-max-uses" min="1" step="1" value="1">
						</div>

						<div class="mpcrbm-rfq-modal-result" id="mpcrbm-rfq-modal-result"></div>

						<div class="mpcrbm-rfq-modal-actions" id="mpcrbm-rfq-form-actions">
							<button type="button" class="button" id="mpcrbm-rfq-cancel-btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
							<button type="submit" class="button button-primary" id="mpcrbm-rfq-send-btn"><?php esc_html_e( 'Create & Email Discount', 'car-rental-manager' ); ?></button>
						</div>

						<!-- Shown in place of the form once a coupon is actually created —
						     stays on screen (does NOT auto-close) so the admin can copy the
						     code and hand it to the customer some other way too, not just
						     rely on the automatic email. -->
						<div id="mpcrbm-rfq-success-box" style="display:none;">
							<p id="mpcrbm-rfq-success-message" style="color:#166534;font-weight:600;"></p>
							<label><?php esc_html_e( 'Coupon Code', 'car-rental-manager' ); ?></label>
							<div style="display:flex;gap:8px;">
								<input type="text" id="mpcrbm-rfq-success-code" readonly style="flex:1;font-weight:700;letter-spacing:1px;">
								<button type="button" class="button" id="mpcrbm-rfq-copy-code-btn"><?php esc_html_e( 'Copy', 'car-rental-manager' ); ?></button>
							</div>
							<div class="mpcrbm-rfq-modal-actions" style="margin-top:16px;">
								<button type="button" class="button button-primary" id="mpcrbm-rfq-done-btn"><?php esc_html_e( 'Done', 'car-rental-manager' ); ?></button>
							</div>
						</div>
					</form>
				</div>

				<script>
				(function($){
					'use strict';
					var ajaxUrl        = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
					var adminNonce     = '<?php echo esc_js( $admin_nonce ); ?>';
					var customersNonce = '<?php echo esc_js( $customers_nonce ); ?>';
					var currentQuoteId = 0;

					// The admin shell wraps every page in its own layout containers, and
					// at least one ancestor of this markup has a CSS transform/filter
					// somewhere in that chain — which silently turns `position:fixed`
					// into "fixed relative to that ancestor" instead of the real
					// viewport, so the overlay stopped covering the full screen and
					// page content showed through around its edges. Moving the overlay
					// to be a direct child of <body> sidesteps that entirely, regardless
					// of which shell element is actually responsible.
					$('#mpcrbm-rfq-modal-overlay').appendTo('body');

					function openModal( quoteId, name, email ){
						currentQuoteId = quoteId;
						// Reset back to the form view first — in case this modal was left
						// showing the success box from a previous request — THEN fill in
						// this request's own values, so the native reset() doesn't wipe
						// the hidden fields being set right after it.
						$('#mpcrbm-rfq-modal-form')[0].reset();
						$('#mpcrbm-rfq-success-box').hide();
						$('#mpcrbm-rfq-form-actions, #mpcrbm-rfq-form-fields').show();
						$('#mpcrbm-rfq-quote-id').val( quoteId );
						$('#mpcrbm-rfq-email').val( email );
						$('#mpcrbm-rfq-name-display').val( name + ' (' + email + ')' );
						$('#mpcrbm-rfq-modal-result').html('');
						$('#mpcrbm-rfq-modal-overlay').addClass('is-open');
					}
					function closeModal(){
						$('#mpcrbm-rfq-modal-overlay').removeClass('is-open');
					}

					$(document).on('click', '.mpcrbm-rfq-respond-btn', function(){
						openModal( $(this).data('quote-id'), $(this).data('name'), $(this).data('email') );
					});
					$(document).on('click', '#mpcrbm-rfq-cancel-btn', closeModal);
					$(document).on('click', '#mpcrbm-rfq-modal-overlay', function(e){
						if( $(e.target).is('#mpcrbm-rfq-modal-overlay') ){ closeModal(); }
					});

					$(document).on('click', '.mpcrbm-rfq-dismiss-btn', function(){
						if( !confirm('<?php echo esc_js( __( 'Dismiss this request without sending a discount?', 'car-rental-manager' ) ); ?>') ){ return; }
						var quoteId = $(this).data('quote-id');
						$.post(ajaxUrl, { action:'mpcrbm_dismiss_quote_request', nonce:adminNonce, quote_id:quoteId }, function(r){
							if( r.success ){ $('#mpcrbm-rfq-list-holder').html(r.data.html); }
						});
					});

					// Reuses MPCRBM_Customers::ajax_give_discount() / ajax_send_discount_email()
					// verbatim (same nonce/action names) — no new coupon-creation code here.
					$(document).on('submit', '#mpcrbm-rfq-modal-form', function(e){
						e.preventDefault();

						var email  = $('#mpcrbm-rfq-email').val();
						var name   = $('#mpcrbm-rfq-name-display').val();
						var type   = $('#mpcrbm-rfq-type').val();
						var amount = $('#mpcrbm-rfq-amount').val();
						var maxUses = $('#mpcrbm-rfq-max-uses').val() || 1;
						var $result = $('#mpcrbm-rfq-modal-result');
						var $btn    = $('#mpcrbm-rfq-send-btn');

						if( !amount || parseFloat(amount) <= 0 ){
							$result.html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Enter a valid amount.', 'car-rental-manager' ) ); ?></p></div>');
							return;
						}

						$btn.prop('disabled', true);
						$result.html('<span class="spinner is-active" style="float:none;"></span>');

						$.post(ajaxUrl, {
							action     : 'mpcrbm_customer_give_discount',
							nonce      : customersNonce,
							email      : email,
							name       : name,
							type       : type,
							amount     : amount,
							validity   : 'uses',
							max_uses   : maxUses
						}, function(r){
							if( !r.success ){
								$btn.prop('disabled', false);
								$result.html('<div class="notice notice-error inline"><p>' + (r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Could not create the discount.', 'car-rental-manager' ) ); ?>') + '</p></div>');
								return;
							}

							var code = ( r.data && r.data.code ) ? r.data.code : '';

							$.post(ajaxUrl, {
								action : 'mpcrbm_customer_send_discount_email',
								nonce  : customersNonce,
								code   : code
							}, function(r2){
								$.post(ajaxUrl, {
									action       : 'mpcrbm_resolve_quote_request',
									nonce        : adminNonce,
									quote_id     : currentQuoteId,
									coupon_code  : code
								}, function(r3){
									$btn.prop('disabled', false);
									if( r3.success ){
										$('#mpcrbm-rfq-list-holder').html(r3.data.html);
									}
									// Shown either way — the coupon itself was created and
									// emailed successfully regardless of whether the list
									// refresh above worked, so the admin still needs the code.
									$result.html('');
									$('#mpcrbm-rfq-form-actions, #mpcrbm-rfq-form-fields').hide();
									$('#mpcrbm-rfq-success-message').text(
										(r2 && r2.success)
											? '<?php echo esc_js( __( 'Coupon created and emailed to the customer.', 'car-rental-manager' ) ); ?>'
											: '<?php echo esc_js( __( 'Coupon created, but the email could not be sent — share the code below yourself.', 'car-rental-manager' ) ); ?>'
									);
									$('#mpcrbm-rfq-success-code').val( code );
									$('#mpcrbm-rfq-success-box').show();
								});
							});
						}).fail(function(){
							$btn.prop('disabled', false);
							$result.html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Something went wrong. Please try again.', 'car-rental-manager' ) ); ?></p></div>');
						});
					});
				$(document).on('click', '#mpcrbm-rfq-done-btn', closeModal);

				$(document).on('click', '#mpcrbm-rfq-copy-code-btn', function(){
					var $code = $('#mpcrbm-rfq-success-code');
					var $btn  = $(this);
					var restore = $btn.text();
					$code.trigger('select');
					var copied = false;
					try {
						if( navigator.clipboard && navigator.clipboard.writeText ){
							navigator.clipboard.writeText( $code.val() );
							copied = true;
						} else {
							document.execCommand('copy');
							copied = true;
						}
					} catch(e) {}
					if( copied ){
						$btn.text('<?php echo esc_js( __( 'Copied!', 'car-rental-manager' ) ); ?>');
						setTimeout(function(){ $btn.text(restore); }, 1500);
					}
				});
				})(jQuery);
				</script>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}
		}

		new MPCRBM_Quote_Requests();
	}
