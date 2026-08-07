<?php
	/**
	 * "Customers" admin screen — the plugin has no dedicated customer/renter data
	 * model at all (every booking only carries denormalized billing name/email/
	 * phone as post meta on `mpcrbm_booking`), so this page builds a customer view
	 * on the fly by grouping existing bookings, rather than introducing a new
	 * table/CPT. Identity key is the lowercased billing email; bookings with no
	 * email fall back to a normalized phone, and bookings with neither become
	 * their own single-booking "Unknown" row (there's nothing reliable to group
	 * them by).
	 *
	 * Deliberately separate from MPCRBM_Booking_List_Free's Pro-locked "Filter
	 * Bookings" teaser (search/status/date, rendered disabled behind a PRO
	 * overlay there) — this page's own search box is fully functional, but it's
	 * new value (customer-centric grouping), not an unlock of that existing
	 * Pro-gated mockup.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! class_exists( 'MPCRBM_Customers' ) ) {
		class MPCRBM_Customers {

			const PER_PAGE          = 20;
			const BOOKINGS_PER_PAGE = 20; // per "Load More" click in the customer detail modal
			const SLUG              = 'mpcrbm_customers';
			const REPEAT_AT         = 2; // bookings count that earns the "Repeat" badge
			const VIP_AT            = 5; // bookings count that earns the "VIP" badge
			const CACHE_KEY         = 'mpcrbm_customers_aggregate_v1';
			const CACHE_TTL         = 60; // seconds — short enough that a brand new booking shows up quickly, long enough that a burst of list/search/sort/load-more/filter clicks on one page load only pays for the full scan once
			const BLOCKLIST_OPTION  = 'mpcrbm_blocked_customers';

			public function __construct() {
				add_action( 'admin_menu', array( $this, 'add_menu' ), 21 ); // just after Bookings (20)
				add_filter( 'mpcrbm_shell_menu_items', array( $this, 'add_shell_menu_item' ) );
				add_filter( 'mpcrbm_shell_screen_ids', array( $this, 'add_shell_screen_id' ) );
				add_action( 'wp_ajax_mpcrbm_customer_detail', array( $this, 'ajax_customer_detail' ) );
				add_action( 'wp_ajax_mpcrbm_customer_bookings_page', array( $this, 'ajax_customer_bookings_page' ) );
				add_action( 'wp_ajax_mpcrbm_customer_give_discount', array( $this, 'ajax_give_discount' ) );
				add_action( 'wp_ajax_mpcrbm_customer_send_discount_email', array( $this, 'ajax_send_discount_email' ) );
				add_action( 'wp_ajax_mpcrbm_customer_toggle_block', array( $this, 'ajax_toggle_block' ) );
				// WooCommerce has no native "valid from" date on a coupon (only an
				// expiry), so date-range validity is enforced here instead — this
				// filter runs on every coupon in the shop, not just ones this screen
				// created, but it's a no-op for any coupon lacking the meta key.
				add_filter( 'woocommerce_coupon_is_valid', array( $this, 'enforce_valid_from' ), 10, 2 );
				// Blocklist enforcement at checkout. The actual match logic lives in
				// filter_is_customer_blocked(); this hooks it into BOTH WooCommerce
				// checkout UIs — the legacy [woocommerce_checkout] shortcode form
				// AND the Checkout block (Store API) — since which one fires depends
				// on how the site's Checkout page is built and both need covering.
				// MPCRBM_Offline_Checkout::ajax_place_order() calls the same filter
				// directly for the Custom Payment path.
				add_filter( 'mpcrbm_is_customer_blocked', array( $this, 'filter_is_customer_blocked' ), 10, 4 );
				add_action( 'woocommerce_after_checkout_validation', array( $this, 'block_checkout_if_blocked' ), 10, 2 );
				add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'block_store_api_checkout_if_blocked' ), 10, 2 );
			}

			public function enforce_valid_from( $valid, $coupon ) {
				if ( ! $valid ) {
					return $valid;
				}
				$valid_from = $coupon->get_meta( '_mpcrbm_valid_from' );

				return ( $valid_from && current_time( 'Y-m-d' ) < $valid_from ) ? false : $valid;
			}

			private function get_cpt() {
				return class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_cpt() : 'mpcrbm_rent';
			}

			private function base_url() {
				return admin_url( 'edit.php?post_type=' . $this->get_cpt() . '&page=' . self::SLUG );
			}

			public function add_menu() {
				add_submenu_page(
					'edit.php?post_type=' . $this->get_cpt(),
					__( 'Customers', 'car-rental-manager' ),
					__( 'Customers', 'car-rental-manager' ),
					'manage_options',
					self::SLUG,
					array( $this, 'render_page' )
				);
			}

			public function add_shell_menu_item( $items ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $items;
				}
				$items[] = array(
					'slug'  => self::SLUG,
					'label' => __( 'Customers', 'car-rental-manager' ),
					'icon'  => 'fas fa-users',
					'link'  => $this->base_url(),
				);

				return $items;
			}

			public function add_shell_screen_id( $ids ) {
				$ids[] = $this->get_cpt() . '_page_' . self::SLUG;

				return $ids;
			}

			/* --------------------------------------------------------------
			 * Data
			 * ------------------------------------------------------------ */

			private function format_price( $amount ) {
				return class_exists( 'MPCRBM_Global_Function' )
					? MPCRBM_Global_Function::format_price( (float) $amount )
					: number_format( (float) $amount, 2 );
			}

			/** Same per-booking total resolution as MPCRBM_Booking_List_Free::fetch_page(), minus the cost-breakdown parts this page doesn't need. */
			private function resolve_total( $id, $order_id, $is_woo, $wc_order ) {
				if ( $is_woo && $wc_order instanceof WC_Order ) {
					return (float) $wc_order->get_total();
				}

				return (float) get_post_meta( $id, 'mpcrbm_tp', true );
			}

			private function normalize_phone( $phone ) {
				return preg_replace( '/\D+/', '', (string) $phone );
			}

			/**
			 * Groups every `mpcrbm_booking` record into customers keyed by lowercased
			 * email (falling back to normalized phone, then a per-booking unique key).
			 *
			 * Loads every booking in one pass — acceptable for the booking volumes a
			 * single car-rental site accumulates, but this is an O(n) full-table scan,
			 * not an indexed lookup; revisit with a real customer table if a site ever
			 * grows into the tens of thousands of bookings. Cached for CACHE_TTL
			 * seconds so a page load that calls this several times (list search/sort,
			 * opening a modal, Load More, changing the date filter) only pays for the
			 * scan once; no explicit invalidation on new bookings — the short TTL is
			 * the whole mechanism.
			 *
			 * @return array<string,array> customer_key => customer row
			 */
			private function get_all_customers() {
				$cached = get_transient( self::CACHE_KEY );
				if ( is_array( $cached ) ) {
					return $cached;
				}

				$q = new WP_Query( array(
					'post_type'      => 'mpcrbm_booking',
					'post_status'    => array( 'publish', 'pending', 'draft' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => true,
				) );

				$wc_ready  = function_exists( 'wc_get_order' );
				$customers = array();

				foreach ( $q->posts as $id ) {
					$name     = trim( (string) get_post_meta( $id, 'mpcrbm_billing_name', true ) );
					$email    = trim( (string) get_post_meta( $id, 'mpcrbm_billing_email', true ) );
					$phone    = trim( (string) get_post_meta( $id, 'mpcrbm_billing_phone', true ) );
					$user_id  = absint( get_post_meta( $id, 'mpcrbm_user_id', true ) );
					$order_id = absint( get_post_meta( $id, 'mpcrbm_order_id', true ) );
					$date     = get_the_date( '', $id ); // display-formatted, for showing to the admin
					$date_raw = get_the_date( 'Y-m-d', $id ); // ISO, for sorting/filtering — get_the_date('') follows the site's configured date_format option, which isn't safe to sort/compare with strtotime()

					if ( $email !== '' ) {
						$key = 'email:' . strtolower( $email );
					} elseif ( $this->normalize_phone( $phone ) !== '' ) {
						$key = 'phone:' . $this->normalize_phone( $phone );
					} else {
						$key = 'booking:' . $id; // nothing to group by — stands alone
					}

					$wc_order = ( $wc_ready && $order_id && $order_id !== $id ) ? wc_get_order( $order_id ) : false;
					$is_woo   = ( $wc_order instanceof WC_Order );
					$total    = $this->resolve_total( $id, $order_id, $is_woo, $wc_order );
					$status   = get_post_meta( $id, 'mpcrbm_order_status', true ) ?: 'pending';

					if ( ! isset( $customers[ $key ] ) ) {
						$customers[ $key ] = array(
							'key'              => $key,
							'name'             => $name ?: __( 'Unknown', 'car-rental-manager' ),
							'email'            => $email,
							'phone'            => $phone,
							'user_id'          => $user_id,
							'bookings'         => array(),
							'total_spent'      => 0.0,
							// The query is already newest-first, so the first booking seen
							// for a brand new key is that customer's most recent one.
							'last_booking'     => $date,
							'last_booking_raw' => $date_raw,
						);
					}

					// A guest's name/email/phone can differ slightly between visits (typo,
					// updated phone number) — keep the most recently seen non-empty value
					// so the row reflects how the customer identifies themselves now.
					if ( $name !== '' ) {
						$customers[ $key ]['name'] = $name;
					}
					if ( $email !== '' ) {
						$customers[ $key ]['email'] = $email;
					}
					if ( $phone !== '' ) {
						$customers[ $key ]['phone'] = $phone;
					}
					if ( $user_id ) {
						$customers[ $key ]['user_id'] = $user_id;
					}

					$customers[ $key ]['bookings'][] = array(
						'ID'       => $id,
						'date'     => $date,
						'date_raw' => $date_raw,
						'status'   => $status,
						'total'    => $total,
						'is_woo'   => $is_woo,
					);
					$customers[ $key ]['total_spent'] += $total;
				}

				set_transient( self::CACHE_KEY, $customers, self::CACHE_TTL );

				return $customers;
			}

			private function badge_for( $count ) {
				if ( $count >= self::VIP_AT ) {
					return array( __( 'VIP', 'car-rental-manager' ), 'vip' );
				}
				if ( $count >= self::REPEAT_AT ) {
					return array( __( 'Repeat', 'car-rental-manager' ), 'repeat' );
				}

				return array( null, null );
			}

			/* --------------------------------------------------------------
			 * Blocklist — a customer with no email on file (a "booking:ID" key)
			 * can't meaningfully be blocked at checkout (nothing to match against
			 * on a future guest order), so blocking is only offered for
			 * email/phone-keyed customers; see render_page()/render_detail_modal_content().
			 * ------------------------------------------------------------ */

			private function get_blocklist() {
				$list = get_option( self::BLOCKLIST_OPTION, array() );

				return is_array( $list ) ? $list : array();
			}

			private function is_blocked( $key ) {
				return isset( $this->get_blocklist()[ $key ] );
			}

			public function ajax_toggle_block() {
				check_ajax_referer( 'mpcrbm_customers', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}

				$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
				if ( $key === '' ) {
					wp_send_json_error( array( 'message' => __( 'Invalid customer.', 'car-rental-manager' ) ) );
				}

				$customers = $this->get_all_customers();
				if ( ! isset( $customers[ $key ] ) ) {
					wp_send_json_error( array( 'message' => __( 'Customer not found.', 'car-rental-manager' ) ) );
				}

				$blocklist = $this->get_blocklist();

				if ( isset( $blocklist[ $key ] ) ) {
					unset( $blocklist[ $key ] );
					$blocked = false;
				} else {
					$c                    = $customers[ $key ];
					$blocklist[ $key ]    = array(
						'email'      => $c['email'],
						'phone'      => $c['phone'],
						'user_id'    => $c['user_id'],
						'name'       => $c['name'],
						'blocked_at' => current_time( 'mysql' ),
						'blocked_by' => get_current_user_id(),
					);
					$blocked = true;
				}

				update_option( self::BLOCKLIST_OPTION, $blocklist );

				wp_send_json_success( array(
					'blocked' => $blocked,
					'message' => $blocked
						? __( 'Customer blocked — future WooCommerce checkouts matching their email, phone, or account will be rejected.', 'car-rental-manager' )
						: __( 'Customer unblocked.', 'car-rental-manager' ),
				) );
			}

			/**
			 * Generic "is this customer blocked?" query, exposed as a filter
			 * (`mpcrbm_is_customer_blocked`) so both checkout paths can ask it
			 * without a hard class dependency on MPCRBM_Customers — mirrors the
			 * existing `mpcrbm_add_booking_data` filter idiom MPCRBM_Offline_Checkout
			 * already uses for the same reason. Matches on whichever signal is
			 * available: billing email, billing phone, or (for a logged-in
			 * customer) their WordPress user ID — so blocking still holds even if a
			 * repeat guest changes the email/phone they type in next time, as long
			 * as they're checking out while logged into the account that was blocked.
			 */
			public function filter_is_customer_blocked( $blocked, $email, $phone, $user_id = 0 ) {
				if ( $blocked ) {
					return $blocked; // an earlier callback already said yes
				}

				$blocklist = $this->get_blocklist();
				if ( empty( $blocklist ) ) {
					return false;
				}

				$email = strtolower( trim( (string) $email ) );
				$phone = $this->normalize_phone( $phone );

				foreach ( $blocklist as $entry ) {
					$email_match = $email !== '' && $email === strtolower( (string) $entry['email'] );
					$phone_match = $phone !== '' && $phone === $this->normalize_phone( $entry['phone'] );
					$user_match  = $user_id && ! empty( $entry['user_id'] ) && (int) $user_id === (int) $entry['user_id'];

					if ( $email_match || $phone_match || $user_match ) {
						return true;
					}
				}

				return false;
			}

			/** Rejects checkout for a blocked customer — the WooCommerce side of enforcement; see filter_is_customer_blocked() for the shared matching logic and MPCRBM_Offline_Checkout::ajax_place_order() for the Custom Payment side. */
			public function block_checkout_if_blocked( $data, $errors ) {
				$email   = isset( $data['billing_email'] ) ? $data['billing_email'] : '';
				$phone   = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';
				$user_id = get_current_user_id();

				if ( apply_filters( 'mpcrbm_is_customer_blocked', false, $email, $phone, $user_id ) ) {
					$errors->add(
						'mpcrbm_blocked',
						__( 'We’re unable to process a booking for this account. Please contact us for assistance.', 'car-rental-manager' )
					);
				}
			}

			/**
			 * Rejects checkout for a blocked customer — the Checkout BLOCK (Store
			 * API) side of enforcement. This is a SEPARATE hook from
			 * block_checkout_if_blocked() above because the block-based checkout
			 * never fires 'woocommerce_after_checkout_validation' at all — that
			 * hook belongs to the legacy [woocommerce_checkout] shortcode's own
			 * form processor, a completely different code path in WooCommerce
			 * core. A site whose Checkout page uses the block (the WooCommerce
			 * default since ~8.3) got no enforcement without this — confirmed by
			 * testing: blocking an email here and then completing an order through
			 * this site's actual block-based Checkout page went through anyway,
			 * because only the shortcode hook was wired up.
			 *
			 * Unlike the shortcode path (a filter that adds to a $errors object),
			 * the Store API path expects a thrown RouteException to abort the
			 * request — that's what actually turns into the error the customer
			 * sees in the block checkout UI, caught by WooCommerce's own route
			 * handler (Automattic\WooCommerce\StoreApi\Routes\V1\Checkout::get_response()).
			 *
			 * @param \WC_Order         $order   The order being placed.
			 * @param \WP_REST_Request  $request The Store API request.
			 * @throws \Exception To abort checkout; a RouteException when the class is available, for a clean 403 instead of a generic 500.
			 */
			public function block_store_api_checkout_if_blocked( $order, $request ) {
				$email   = $order->get_billing_email();
				$phone   = $order->get_billing_phone();
				$user_id = get_current_user_id();

				if ( ! apply_filters( 'mpcrbm_is_customer_blocked', false, $email, $phone, $user_id ) ) {
					return;
				}

				$message = __( 'We’re unable to process a booking for this account. Please contact us for assistance.', 'car-rental-manager' );

				if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
					throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'mpcrbm_blocked', $message, 403 );
				}
				throw new \Exception( esc_html( $message ) );
			}

			/**
			 * Turns a filter preset into a concrete [from, to] date pair (both
			 * 'Y-m-d' or ''). Resolved server-side against the site's own clock
			 * (current_time()) rather than trusting the browser's date, so an
			 * admin in a different timezone than the site still gets "today"
			 * meaning the site's today.
			 */
			private function resolve_date_range( $preset, $from, $to ) {
				$today = current_time( 'Y-m-d' );
				switch ( $preset ) {
					case 'today':
						return array( $today, $today );
					case 'week':
						return array( date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ) ), $today );
					case 'month':
						return array( date( 'Y-m-d', strtotime( 'first day of this month', current_time( 'timestamp' ) ) ), $today );
					case 'custom':
						return array(
							preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) ? $from : '',
							preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ? $to : '',
						);
					default:
						return array( '', '' ); // 'all' / anything unrecognized — no filter
				}
			}

			/**
			 * Filters a customer's already-in-memory bookings array by date range,
			 * then slices out one "Load More" batch starting at $offset. Both
			 * operations run on data get_all_customers() already fetched — no extra
			 * query per batch/filter change, just array work, which stays fast even
			 * for a customer with thousands of bookings.
			 *
			 * $offset is a row COUNT, not a page number — the browser derives it by
			 * counting the <tr> rows already in the table (see mpcrbm-customers.js),
			 * rather than a page counter either side has to keep in sync. A page
			 * number handed back and forth turned out fragile in practice (a stale
			 * jQuery .data() cache on the button repeatedly re-fetched the same
			 * page); counting what's actually rendered can't drift from it.
			 *
			 * @return array{0: array, 1: bool} [ rows, has_more ]
			 */
			private function paginate_bookings( array $bookings, $offset, $from, $to ) {
				if ( $from !== '' || $to !== '' ) {
					$bookings = array_values( array_filter( $bookings, function ( $b ) use ( $from, $to ) {
						if ( $from !== '' && $b['date_raw'] < $from ) {
							return false;
						}
						if ( $to !== '' && $b['date_raw'] > $to ) {
							return false;
						}

						return true;
					} ) );
				}

				$offset   = max( 0, (int) $offset );
				$slice    = array_slice( $bookings, $offset, self::BOOKINGS_PER_PAGE );
				$has_more = ( $offset + self::BOOKINGS_PER_PAGE ) < count( $bookings );

				return array( $slice, $has_more );
			}

			private function render_booking_rows( array $bookings ) {
				foreach ( $bookings as $booking ) {
					?>
					<tr>
						<td>#<?php echo esc_html( $booking['ID'] ); ?></td>
						<td><?php echo esc_html( $booking['date'] ); ?></td>
						<td><?php echo esc_html( ucfirst( str_replace( array( 'wc-', '-' ), array( '', ' ' ), $booking['status'] ) ) ); ?></td>
						<td><?php echo wp_kses_post( $this->format_price( $booking['total'] ) ); ?></td>
					</tr>
					<?php
				}
			}

			/* --------------------------------------------------------------
			 * AJAX: customer detail (booking history)
			 * ------------------------------------------------------------ */

			public function ajax_customer_detail() {
				check_ajax_referer( 'mpcrbm_customers', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}

				$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
				if ( $key === '' ) {
					wp_send_json_error( array( 'message' => __( 'Invalid customer.', 'car-rental-manager' ) ) );
				}

				$customers = $this->get_all_customers();
				if ( ! isset( $customers[ $key ] ) ) {
					wp_send_json_error( array( 'message' => __( 'Customer not found.', 'car-rental-manager' ) ) );
				}

				ob_start();
				$this->render_detail_modal_content( $customers[ $key ] );
				wp_send_json_success( array( 'html' => ob_get_clean() ) );
			}

			/**
			 * One page of a customer's booking-history rows — used both for "Load
			 * More" (page N+1, same filter, client appends) and for a date-filter
			 * change (page 1, new filter, client replaces the table body). Returns
			 * just the <tr> markup, not the whole modal, so re-filtering/paging
			 * doesn't re-send the discount section, stats, etc.
			 */
			public function ajax_customer_bookings_page() {
				check_ajax_referer( 'mpcrbm_customers', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}

				$key    = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
				// Row count already rendered, not a page number — see paginate_bookings()'s
				// docblock for why this replaced page-number-based paging.
				$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
				$preset = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : 'all';
				$from   = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
				$to     = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';

				$customers = $this->get_all_customers();
				if ( $key === '' || ! isset( $customers[ $key ] ) ) {
					wp_send_json_error( array( 'message' => __( 'Customer not found.', 'car-rental-manager' ) ) );
				}

				list( $date_from, $date_to ) = $this->resolve_date_range( $preset, $from, $to );
				list( $rows, $has_more )     = $this->paginate_bookings( $customers[ $key ]['bookings'], $offset, $date_from, $date_to );

				ob_start();
				if ( empty( $rows ) ) {
					?>
					<tr class="mpcrbm-cust-modal-empty-row"><td colspan="4"><?php esc_html_e( 'No bookings in this range.', 'car-rental-manager' ); ?></td></tr>
					<?php
				} else {
					$this->render_booking_rows( $rows );
				}
				$html = ob_get_clean();

				wp_send_json_success( array(
					'html'     => $html,
					'has_more' => $has_more,
				) );
			}

			/* --------------------------------------------------------------
			 * AJAX: give a customer a one-off discount
			 *
			 * WooCommerce-only (Option A): creates a native WC coupon restricted to
			 * the customer's email rather than teaching the plugin's own pricing
			 * pipeline about per-customer discounts. Reuses WooCommerce's existing
			 * coupon validation/usage-tracking/admin UI (Marketing → Coupons) instead
			 * of building a parallel one. The "Custom Payment" (non-WooCommerce)
			 * checkout path doesn't consume WC coupons at all — that would need its
			 * own mechanism wired into MPCRBM_Woocommerce::mpcrbm_get_cart_total_price()
			 * (the one function both checkout paths already share), left for later
			 * since this site currently runs in WooCommerce mode.
			 * ------------------------------------------------------------ */

			public function ajax_give_discount() {
				check_ajax_referer( 'mpcrbm_customers', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}
				if ( ! class_exists( 'WC_Coupon' ) ) {
					wp_send_json_error( array( 'message' => __( 'WooCommerce is not active — coupon discounts need it.', 'car-rental-manager' ) ) );
				}

				$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$type     = ( isset( $_POST['type'] ) && $_POST['type'] === 'fixed' ) ? 'fixed_cart' : 'percent';
				$amount   = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0.0;
				// 'uses' = admin picks how many times it can be redeemed, no time limit.
				// 'range' = unlimited redemptions, but only within an admin-chosen
				// from/until window (WC has no native "valid from", so that half is
				// enforced by enforce_valid_from() above via coupon meta).
				$validity  = ( isset( $_POST['validity'] ) && $_POST['validity'] === 'range' ) ? 'range' : 'uses';
				$max_uses  = isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 1;
				$valid_from = isset( $_POST['valid_from'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_from'] ) ) : '';
				$valid_until = isset( $_POST['valid_until'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_until'] ) ) : '';

				if ( ! is_email( $email ) ) {
					wp_send_json_error( array( 'message' => __( 'This customer has no email on file — a coupon must be restricted to an email.', 'car-rental-manager' ) ) );
				}
				if ( $amount <= 0 || ( $type === 'percent' && $amount > 100 ) ) {
					wp_send_json_error( array( 'message' => __( 'Enter a valid discount amount.', 'car-rental-manager' ) ) );
				}
				foreach ( array( $valid_from, $valid_until ) as $date_input ) {
					if ( $date_input !== '' && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_input ) ) {
						wp_send_json_error( array( 'message' => __( 'Invalid date.', 'car-rental-manager' ) ) );
					}
				}
				if ( $validity === 'uses' && $max_uses < 1 ) {
					wp_send_json_error( array( 'message' => __( 'Max uses must be at least 1.', 'car-rental-manager' ) ) );
				}
				if ( $validity === 'range' && $valid_from === '' && $valid_until === '' ) {
					wp_send_json_error( array( 'message' => __( 'Set at least a start or end date for a date-range discount.', 'car-rental-manager' ) ) );
				}
				if ( $validity === 'range' && $valid_from !== '' && $valid_until !== '' && $valid_from > $valid_until ) {
					wp_send_json_error( array( 'message' => __( 'The start date must be before the end date.', 'car-rental-manager' ) ) );
				}

				$code = $this->generate_unique_coupon_code( $name );

				$coupon = new WC_Coupon();
				$coupon->set_code( $code );
				$coupon->set_discount_type( $type );
				$coupon->set_amount( $amount );
				$coupon->set_email_restrictions( array( $email ) );
				$coupon->set_individual_use( true );

				if ( $validity === 'uses' ) {
					$coupon->set_usage_limit( $max_uses );
				} else {
					// Unlimited redemptions inside the window — the email + individual-use
					// restrictions already keep this scoped to one customer.
					$coupon->set_usage_limit( 0 );
					if ( $valid_until !== '' ) {
						$coupon->set_date_expires( $valid_until );
					}
					if ( $valid_from !== '' ) {
						$coupon->update_meta_data( '_mpcrbm_valid_from', $valid_from );
					}
				}

				$coupon->set_description(
					sprintf(
						/* translators: %s: customer name or email */
						__( 'Personal discount created from the Customers screen for %s.', 'car-rental-manager' ),
						$name ?: $email
					)
				);
				$coupon->save();

				if ( $type === 'percent' ) {
					$amount_label = rtrim( rtrim( number_format( $amount, 2 ), '0' ), '.' ) . '%';
				} else {
					$amount_label = wp_strip_all_tags( $this->format_price( $amount ) );
				}
				if ( $validity === 'uses' ) {
					/* translators: 1: amount/percentage, 2: max uses, 3: customer email */
					$message = sprintf(
						_n(
							'Created a %1$s coupon restricted to %3$s (usable %2$d time).',
							'Created a %1$s coupon restricted to %3$s (usable %2$d times).',
							$max_uses,
							'car-rental-manager'
						),
						$amount_label,
						$max_uses,
						$email
					);
				} elseif ( $valid_from && $valid_until ) {
					/* translators: 1: amount/percentage, 2: start date, 3: end date, 4: customer email */
					$message = sprintf( __( 'Created a %1$s coupon restricted to %4$s, valid %2$s – %3$s.', 'car-rental-manager' ), $amount_label, $valid_from, $valid_until, $email );
				} elseif ( $valid_until ) {
					/* translators: 1: amount/percentage, 2: end date, 3: customer email */
					$message = sprintf( __( 'Created a %1$s coupon restricted to %3$s, valid until %2$s.', 'car-rental-manager' ), $amount_label, $valid_until, $email );
				} else {
					/* translators: 1: amount/percentage, 2: start date, 3: customer email */
					$message = sprintf( __( 'Created a %1$s coupon restricted to %3$s, valid from %2$s.', 'car-rental-manager' ), $amount_label, $valid_from, $email );
				}

				wp_send_json_success( array(
					'code'    => $code,
					'message' => $message,
				) );
			}

			/**
			 * Emails a coupon's code + terms to the customer it's already
			 * email-restricted to. The coupon itself is the single source of truth
			 * for who it's for and what it's worth — this only ever looks up an
			 * EXISTING coupon by code, it never accepts a recipient address from
			 * the request, so there's no way to turn this into a mail-to-anyone tool.
			 */
			public function ajax_send_discount_email() {
				check_ajax_referer( 'mpcrbm_customers', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}
				if ( ! class_exists( 'WC_Coupon' ) ) {
					wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'car-rental-manager' ) ) );
				}

				$code      = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
				$coupon_id = $code !== '' ? wc_get_coupon_id_by_code( $code ) : 0;
				if ( ! $coupon_id ) {
					wp_send_json_error( array( 'message' => __( 'Coupon not found.', 'car-rental-manager' ) ) );
				}

				$coupon = new WC_Coupon( $coupon_id );
				$emails = $coupon->get_email_restrictions();
				$to     = ! empty( $emails ) ? $emails[0] : '';
				if ( ! is_email( $to ) ) {
					wp_send_json_error( array( 'message' => __( 'This coupon has no customer email on file to send to.', 'car-rental-manager' ) ) );
				}

				$sent = wp_mail(
					$to,
					$this->discount_email_subject(),
					$this->discount_email_body( $coupon ),
					array( 'Content-Type: text/html; charset=UTF-8' )
				);

				if ( ! $sent ) {
					wp_send_json_error( array( 'message' => __( 'Could not send the email — check your site’s mail configuration.', 'car-rental-manager' ) ) );
				}

				wp_send_json_success( array(
					/* translators: %s: email address the coupon was sent to */
					'message' => sprintf( __( 'Email sent to %s.', 'car-rental-manager' ), $to ),
				) );
			}

			private function discount_email_subject() {
				/* translators: %s: site/business name */
				return sprintf( __( 'A discount code from %s', 'car-rental-manager' ), get_bloginfo( 'name' ) );
			}

			private function discount_email_body( WC_Coupon $coupon ) {
				$amount_label = $coupon->get_discount_type() === 'percent'
					? rtrim( rtrim( number_format( (float) $coupon->get_amount(), 2 ), '0' ), '.' ) . '%'
					: wp_strip_all_tags( $this->format_price( (float) $coupon->get_amount() ) );
				$expiry     = $coupon->get_date_expires();
				$valid_from = $coupon->get_meta( '_mpcrbm_valid_from' );
				$limit      = $coupon->get_usage_limit();

				$lines   = array();
				$lines[] = '<p>' . esc_html__( 'Hi,', 'car-rental-manager' ) . '</p>';
				$lines[] = '<p>' . sprintf(
					/* translators: %s: site/business name */
					esc_html__( 'Here’s a discount code from %s for your next booking:', 'car-rental-manager' ),
					esc_html( get_bloginfo( 'name' ) )
				) . '</p>';
				$lines[] = '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;background:#f1f2f6;padding:12px 18px;display:inline-block;border-radius:8px;">' . esc_html( $coupon->get_code() ) . '</p>';
				$lines[] = '<p><strong>' . esc_html__( 'Discount:', 'car-rental-manager' ) . '</strong> ' . esc_html( $amount_label ) . '</p>';

				if ( $valid_from && $expiry ) {
					$lines[] = '<p><strong>' . esc_html__( 'Valid:', 'car-rental-manager' ) . '</strong> ' . esc_html( $valid_from ) . ' – ' . esc_html( $expiry->date( 'Y-m-d' ) ) . '</p>';
				} elseif ( $expiry ) {
					$lines[] = '<p><strong>' . esc_html__( 'Valid until:', 'car-rental-manager' ) . '</strong> ' . esc_html( $expiry->date( 'Y-m-d' ) ) . '</p>';
				} elseif ( $valid_from ) {
					$lines[] = '<p><strong>' . esc_html__( 'Valid from:', 'car-rental-manager' ) . '</strong> ' . esc_html( $valid_from ) . '</p>';
				}
				if ( $limit > 0 ) {
					$lines[] = '<p>' . esc_html( sprintf(
						/* translators: %d: number of times the code can be used */
						_n( 'Usable %d time.', 'Usable %d times.', $limit, 'car-rental-manager' ),
						$limit
					) ) . '</p>';
				}
				$lines[] = '<p>' . esc_html__( 'Enter this code at checkout to apply your discount.', 'car-rental-manager' ) . '</p>';

				return apply_filters( 'mpcrbm_discount_email_body', implode( "\n", $lines ), $coupon );
			}

			/** e.g. "Rubel Mia" -> "RUBELMI-7K2QF", collision-checked against WooCommerce's own coupon posts. */
			private function generate_unique_coupon_code( $name ) {
				$base = strtoupper( preg_replace( '/[^A-Za-z]/', '', $name ) );
				$base = $base !== '' ? substr( $base, 0, 7 ) : 'CUSTOMER';

				do {
					$code = $base . '-' . strtoupper( wp_generate_password( 5, false, false ) );
				} while ( wc_get_coupon_id_by_code( $code ) );

				return $code;
			}

			/**
			 * Coupons WooCommerce already has restricted to this email — this is the
			 * "where did the coupon I made actually go" answer: it's a normal WC
			 * coupon the whole time (Marketing → Coupons), this list just surfaces it
			 * here too instead of making the admin go find it themselves.
			 */
			private function get_customer_discounts( $email ) {
				if ( ! class_exists( 'WC_Coupon' ) || $email === '' ) {
					return array();
				}
				$q = new WP_Query( array(
					'post_type'      => 'shop_coupon',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'     => array(
						array( 'key' => 'customer_email', 'value' => $email, 'compare' => 'LIKE' ),
					),
					'orderby'        => 'date',
					'order'          => 'DESC',
				) );

				$rows = array();
				foreach ( $q->posts as $id ) {
					$coupon = new WC_Coupon( $id );
					$limit  = $coupon->get_usage_limit();
					$used   = $coupon->get_usage_count();
					$expiry = $coupon->get_date_expires();
					$from   = $coupon->get_meta( '_mpcrbm_valid_from' );

					if ( $limit > 0 && $used >= $limit ) {
						$state = 'used';
					} elseif ( $expiry && $expiry->getTimestamp() < time() ) {
						$state = 'expired';
					} else {
						$state = 'active';
					}

					$rows[] = array(
						'code'   => $coupon->get_code(),
						'amount' => $coupon->get_discount_type() === 'percent'
							? rtrim( rtrim( number_format( (float) $coupon->get_amount(), 2 ), '0' ), '.' ) . '%'
							: wp_strip_all_tags( $this->format_price( (float) $coupon->get_amount() ) ),
						'state'  => $state,
						'usage'  => $limit > 0 ? $used . ' / ' . $limit : sprintf( /* translators: %d: times used */ __( '%d used', 'car-rental-manager' ), $used ),
						'window' => trim( ( $from ? $from . ' → ' : '' ) . ( $expiry ? $expiry->date( 'Y-m-d' ) : ( $from ? __( 'no end date', 'car-rental-manager' ) : '' ) ) ),
					);
				}

				return $rows;
			}

			private function render_detail_modal_content( $customer ) {
				list( $badge_label, $badge_class ) = $this->badge_for( count( $customer['bookings'] ) );
				$bookings_url  = admin_url( 'edit.php?post_type=' . $this->get_cpt() . '&page=' . MPCRBM_Booking_List_Free::SLUG );
				$coupons_url   = admin_url( 'edit.php?post_type=shop_coupon' );
				$discounts     = $this->get_customer_discounts( $customer['email'] );
				$is_blocked    = $this->is_blocked( $customer['key'] );
				?>
				<div class="mpcrbm-cust-modal-head">
					<div>
						<h3><?php echo esc_html( $customer['name'] ); ?>
							<?php if ( $is_blocked ) : ?>
								<span class="mpcrbm-cust-badge is-blocked"><?php esc_html_e( 'Blocked', 'car-rental-manager' ); ?></span>
							<?php endif; ?>
							<?php if ( $badge_label ) : ?>
								<span class="mpcrbm-cust-badge is-<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span>
							<?php endif; ?>
						</h3>
						<div class="mpcrbm-cust-modal-contact">
							<?php if ( $customer['email'] ) : ?><span><span class="dashicons dashicons-email"></span><?php echo esc_html( $customer['email'] ); ?></span><?php endif; ?>
							<?php if ( $customer['phone'] ) : ?><span><span class="dashicons dashicons-phone"></span><?php echo esc_html( $customer['phone'] ); ?></span><?php endif; ?>
							<?php if ( $customer['user_id'] ) : ?><span><span class="dashicons dashicons-admin-users"></span><?php esc_html_e( 'Registered user', 'car-rental-manager' ); ?></span><?php else : ?><span><span class="dashicons dashicons-businessman"></span><?php esc_html_e( 'Guest', 'car-rental-manager' ); ?></span><?php endif; ?>
						</div>
						<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-block-toggle<?php echo $is_blocked ? ' is-blocked' : ''; ?>" data-key="<?php echo esc_attr( $customer['key'] ); ?>" data-context="modal">
							<span class="dashicons dashicons-<?php echo $is_blocked ? 'unlock' : 'lock'; ?>"></span><?php echo $is_blocked ? esc_html__( 'Unblock customer', 'car-rental-manager' ) : esc_html__( 'Block customer', 'car-rental-manager' ); ?>
						</button>
					</div>
					<div class="mpcrbm-cust-modal-stats">
						<div><strong><?php echo count( $customer['bookings'] ); ?></strong><span><?php esc_html_e( 'Bookings', 'car-rental-manager' ); ?></span></div>
						<div><strong><?php echo wp_kses_post( $this->format_price( $customer['total_spent'] ) ); ?></strong><span><?php esc_html_e( 'Lifetime spend', 'car-rental-manager' ); ?></span></div>
					</div>
				</div>

				<?php if ( class_exists( 'WC_Coupon' ) ) : ?>
					<div class="mpcrbm-cust-discount">
						<div class="mpcrbm-cust-discount-head">
							<strong><?php esc_html_e( 'Discounts', 'car-rental-manager' ); ?></strong>
							<a href="<?php echo esc_url( $coupons_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View all in WooCommerce → Coupons', 'car-rental-manager' ); ?></a>
						</div>

						<?php if ( ! empty( $discounts ) ) : ?>
							<table class="mpcrbm-cust-discount-list">
								<tbody>
									<?php foreach ( $discounts as $d ) : ?>
										<tr>
											<td class="mpcrbm-disc-code"><?php echo esc_html( $d['code'] ); ?></td>
											<td><?php echo esc_html( $d['amount'] ); ?></td>
											<td><span class="mpcrbm-disc-state is-<?php echo esc_attr( $d['state'] ); ?>"><?php echo esc_html( ucfirst( $d['state'] ) ); ?></span></td>
											<td class="mpcrbm-disc-usage"><?php echo esc_html( $d['usage'] ); ?><?php echo $d['window'] ? '<br>' . esc_html( $d['window'] ) : ''; ?></td>
											<td class="mpcrbm-disc-actions">
												<button type="button" class="mpcrbm-disc-mail-btn" data-code="<?php echo esc_attr( $d['code'] ); ?>" title="<?php esc_attr_e( 'Email this code to the customer', 'car-rental-manager' ); ?>">
													<span class="dashicons dashicons-email-alt"></span>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>

						<?php if ( $customer['email'] ) : ?>
							<button type="button" class="mpcrbm-btn mpcrbm-btn-primary mpcrbm-cust-discount-toggle">
								<span class="dashicons dashicons-tag"></span><?php esc_html_e( 'Give Discount', 'car-rental-manager' ); ?>
							</button>
							<div class="mpcrbm-cust-discount-form" hidden>
								<div class="mpcrbm-cust-discount-row">
									<label><input type="radio" name="mpcrbm_disc_type" value="percent" checked> <?php esc_html_e( 'Percentage', 'car-rental-manager' ); ?></label>
									<label><input type="radio" name="mpcrbm_disc_type" value="fixed"> <?php esc_html_e( 'Fixed amount', 'car-rental-manager' ); ?></label>
									<input type="number" step="0.01" min="0.01" class="mpcrbm-disc-amount" placeholder="<?php esc_attr_e( 'Amount', 'car-rental-manager' ); ?>">
								</div>

								<div class="mpcrbm-cust-discount-row">
									<label><input type="radio" name="mpcrbm_disc_validity" value="uses" checked> <?php esc_html_e( 'Limit by number of uses', 'car-rental-manager' ); ?></label>
									<label><input type="radio" name="mpcrbm_disc_validity" value="range"> <?php esc_html_e( 'Limit by date range', 'car-rental-manager' ); ?></label>
								</div>
								<div class="mpcrbm-cust-discount-row mpcrbm-disc-validity-uses">
									<input type="number" step="1" min="1" value="1" class="mpcrbm-disc-max-uses" placeholder="<?php esc_attr_e( 'Max uses', 'car-rental-manager' ); ?>">
									<span class="mpcrbm-cust-discount-inline-hint"><?php esc_html_e( 'No time limit — stays active until used up.', 'car-rental-manager' ); ?></span>
								</div>
								<div class="mpcrbm-cust-discount-row mpcrbm-disc-validity-range" hidden>
									<input type="date" class="mpcrbm-disc-valid-from" title="<?php esc_attr_e( 'Valid from (optional)', 'car-rental-manager' ); ?>">
									<input type="date" class="mpcrbm-disc-valid-until" title="<?php esc_attr_e( 'Valid until (optional)', 'car-rental-manager' ); ?>">
									<span class="mpcrbm-cust-discount-inline-hint"><?php esc_html_e( 'Unlimited uses within this window.', 'car-rental-manager' ); ?></span>
								</div>

								<p class="mpcrbm-cust-discount-hint"><?php esc_html_e( 'Locked to this customer’s email. Applies to the rental price only — not the security deposit.', 'car-rental-manager' ); ?></p>
								<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-discount-submit"
									data-email="<?php echo esc_attr( $customer['email'] ); ?>"
									data-name="<?php echo esc_attr( $customer['name'] ); ?>">
									<?php esc_html_e( 'Create Coupon', 'car-rental-manager' ); ?>
								</button>
								<div class="mpcrbm-cust-discount-result"></div>
							</div>
						<?php else : ?>
							<p class="mpcrbm-cust-discount-hint"><?php esc_html_e( 'No email on file for this customer — a discount coupon needs one.', 'car-rental-manager' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php
				list( $booking_rows, $has_more ) = $this->paginate_bookings( $customer['bookings'], 0, '', '' );
				?>
				<div class="mpcrbm-cust-bookings-head">
					<strong><?php esc_html_e( 'Booking history', 'car-rental-manager' ); ?></strong>
					<div class="mpcrbm-cust-date-filter">
						<select class="mpcrbm-cust-date-preset">
							<option value="all"><?php esc_html_e( 'All time', 'car-rental-manager' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'car-rental-manager' ); ?></option>
							<option value="week"><?php esc_html_e( 'This week', 'car-rental-manager' ); ?></option>
							<option value="month"><?php esc_html_e( 'This month', 'car-rental-manager' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom range', 'car-rental-manager' ); ?></option>
						</select>
						<span class="mpcrbm-cust-date-custom" hidden>
							<input type="date" class="mpcrbm-cust-date-from">
							<input type="date" class="mpcrbm-cust-date-to">
							<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-date-apply"><?php esc_html_e( 'Apply', 'car-rental-manager' ); ?></button>
						</span>
					</div>
				</div>
				<table class="mpcrbm-cust-modal-table" data-key="<?php echo esc_attr( $customer['key'] ); ?>">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Booking', 'car-rental-manager' ); ?></th>
							<th><?php esc_html_e( 'Date', 'car-rental-manager' ); ?></th>
							<th><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
							<th><?php esc_html_e( 'Total', 'car-rental-manager' ); ?></th>
						</tr>
					</thead>
					<tbody class="mpcrbm-cust-bookings-body">
						<?php $this->render_booking_rows( $booking_rows ); ?>
					</tbody>
				</table>
				<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-load-more" <?php echo $has_more ? '' : 'hidden'; ?>>
					<?php esc_html_e( 'Load More', 'car-rental-manager' ); ?>
				</button>
				<p class="mpcrbm-cust-modal-footnote">
					<a href="<?php echo esc_url( $bookings_url ); ?>"><?php esc_html_e( 'Open full Bookings list →', 'car-rental-manager' ); ?></a>
				</p>
				<?php
			}

			/** Dedicated CSS/JS for this screen — kept out of MPCRBM_Admin_Shell's shared assets since they're specific to the Customers page only. */
			private function enqueue_assets( $nonce ) {
				$css_path = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-customers.css';
				$js_path  = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-customers.js';

				wp_enqueue_style(
					'mpcrbm-customers',
					MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm-customers.css',
					array(),
					file_exists( $css_path ) ? filemtime( $css_path ) : MPCRBM_PLUGIN_VERSION
				);
				wp_enqueue_script(
					'mpcrbm-customers',
					MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm-customers.js',
					array( 'jquery' ),
					file_exists( $js_path ) ? filemtime( $js_path ) : MPCRBM_PLUGIN_VERSION,
					true
				);
				// Bridges the PHP-side nonce and translated strings into the static .js
				// file — a plain .js asset can't run PHP, so anything the script needs
				// from this side has to arrive this way instead of being echoed inline.
				wp_localize_script( 'mpcrbm-customers', 'mpcrbmCustomers', array(
					'nonce' => $nonce,
					'i18n'  => array(
						'loading'          => __( 'Loading…', 'car-rental-manager' ),
						'couldNotLoad'     => __( 'Could not load this customer.', 'car-rental-manager' ),
						'enterValidAmount' => __( 'Enter a valid amount.', 'car-rental-manager' ),
						'setStartOrEndDate'=> __( 'Set at least a start or end date.', 'car-rental-manager' ),
						'creating'         => __( 'Creating…', 'car-rental-manager' ),
						'somethingWrong'   => __( 'Something went wrong.', 'car-rental-manager' ),
						'sendEmail'        => __( 'Send Email', 'car-rental-manager' ),
						'emailThisCode'    => __( 'Email this code to the customer', 'car-rental-manager' ),
						'confirmBlock'     => __( 'Block this customer? They won’t be able to complete a WooCommerce checkout while blocked.', 'car-rental-manager' ),
						'block'            => __( 'Block', 'car-rental-manager' ),
						'unblock'          => __( 'Unblock', 'car-rental-manager' ),
						'blockCustomer'    => __( 'Block customer', 'car-rental-manager' ),
						'unblockCustomer'  => __( 'Unblock customer', 'car-rental-manager' ),
						'blocked'          => __( 'Blocked', 'car-rental-manager' ),
					),
				) );
			}

			/* --------------------------------------------------------------
			 * Render
			 * ------------------------------------------------------------ */

			public function render_page() {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$paged  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$sort   = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'recent'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$customers = $this->get_all_customers();

				if ( $search !== '' ) {
					$needle    = mb_strtolower( $search );
					$customers = array_filter( $customers, function ( $c ) use ( $needle ) {
						return false !== mb_strpos( mb_strtolower( $c['name'] ), $needle )
							|| false !== mb_strpos( mb_strtolower( $c['email'] ), $needle )
							|| false !== mb_strpos( $this->normalize_phone( $c['phone'] ), preg_replace( '/\D+/', '', $needle ) ?: "\0" );
					} );
				}

				$customers = array_values( $customers );

				usort( $customers, function ( $a, $b ) use ( $sort ) {
					if ( $sort === 'spend' ) {
						return $b['total_spent'] <=> $a['total_spent'];
					}
					if ( $sort === 'bookings' ) {
						return count( $b['bookings'] ) <=> count( $a['bookings'] );
					}

					return $b['last_booking_raw'] <=> $a['last_booking_raw']; // ISO Y-m-d strings sort correctly as plain strings
				} );

				$total    = count( $customers );
				$pages    = max( 1, (int) ceil( $total / self::PER_PAGE ) );
				$paged    = min( $paged, $pages );
				$page_rows = array_slice( $customers, ( $paged - 1 ) * self::PER_PAGE, self::PER_PAGE );
				$nonce    = wp_create_nonce( 'mpcrbm_customers' );

				$this->enqueue_assets( $nonce );

				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Customers', 'car-rental-manager' ) );
				?>
				<div class="mpcrbm-cust-wrap">
					<div class="mpcrbm-settings-head">
						<span class="mpcrbm-settings-head-eyebrow"><?php esc_html_e( 'Customer management', 'car-rental-manager' ); ?></span>
						<h2><?php esc_html_e( 'Customers', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-settings-head-subtitle"><?php esc_html_e( 'Every renter grouped from your bookings — who they are, how many times they’ve booked, and what they’ve spent.', 'car-rental-manager' ); ?></p>
					</div>

					<div class="mpcrbm-card mpcrbm-cust-card">
						<div class="mpcrbm-table-toolbar">
							<form method="get" class="mpcrbm-cust-search">
								<input type="hidden" name="post_type" value="<?php echo esc_attr( $this->get_cpt() ); ?>">
								<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
								<span class="dashicons dashicons-search"></span>
								<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email or phone…', 'car-rental-manager' ); ?>">
								<?php if ( $search !== '' ) : ?>
									<a class="mpcrbm-cust-search-clear" href="<?php echo esc_url( $this->base_url() ); ?>"><?php esc_html_e( 'Clear', 'car-rental-manager' ); ?></a>
								<?php endif; ?>
							</form>
							<span class="mpcrbm-result-count">
								<?php
								/* translators: %d: number of customers. */
								printf( esc_html( _n( '%d customer', '%d customers', $total, 'car-rental-manager' ) ), (int) $total );
								?>
							</span>
							<div class="mpcrbm-cust-sort">
								<label><?php esc_html_e( 'Sort by', 'car-rental-manager' ); ?></label>
								<select onchange="window.location.href=this.value">
									<?php
									$sorts = array(
										'recent'   => __( 'Most recent', 'car-rental-manager' ),
										'spend'    => __( 'Lifetime spend', 'car-rental-manager' ),
										'bookings' => __( 'Booking count', 'car-rental-manager' ),
									);
									foreach ( $sorts as $key => $label ) :
										$url = add_query_arg( array( 'sort' => $key, 'paged' => false ) );
										?>
										<option value="<?php echo esc_url( $url ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="mpcrbm-table-scroll">
							<table class="mpcrbm-cust-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Contact', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Bookings', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Lifetime spend', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Last booking', 'car-rental-manager' ); ?></th>
										<th class="mpcrbm-col-actions"><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
								<?php if ( empty( $page_rows ) ) : ?>
									<tr class="mpcrbm-empty-row">
										<td colspan="6">
											<span class="dashicons dashicons-groups"></span>
											<strong><?php esc_html_e( 'No customers found', 'car-rental-manager' ); ?></strong>
											<span><?php echo $search !== '' ? esc_html__( 'Try a different search term.', 'car-rental-manager' ) : esc_html__( 'Customers appear here as soon as your first booking comes in.', 'car-rental-manager' ); ?></span>
										</td>
									</tr>
								<?php else : ?>
									<?php $blocklist = $this->get_blocklist(); ?>
									<?php foreach ( $page_rows as $c ) : ?>
										<?php
										list( $badge_label, $badge_class ) = $this->badge_for( count( $c['bookings'] ) );
										$is_blocked = isset( $blocklist[ $c['key'] ] );
										?>
										<tr<?php echo $is_blocked ? ' class="mpcrbm-cust-row-blocked"' : ''; ?>>
											<td>
												<span class="mpcrbm-cell-strong"><?php echo esc_html( $c['name'] ); ?></span>
												<?php if ( $is_blocked ) : ?><span class="mpcrbm-cust-badge is-blocked"><?php esc_html_e( 'Blocked', 'car-rental-manager' ); ?></span><?php endif; ?>
												<?php if ( $badge_label ) : ?><span class="mpcrbm-cust-badge is-<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span><?php endif; ?>
												<?php if ( ! $c['user_id'] ) : ?><span class="mpcrbm-cell-sub"><?php esc_html_e( 'Guest', 'car-rental-manager' ); ?></span><?php endif; ?>
											</td>
											<td>
												<?php if ( $c['email'] ) : ?><span class="mpcrbm-cell-sub"><?php echo esc_html( $c['email'] ); ?></span><?php endif; ?>
												<?php if ( $c['phone'] ) : ?><span class="mpcrbm-cell-sub"><?php echo esc_html( $c['phone'] ); ?></span><?php endif; ?>
											</td>
											<td><?php echo (int) count( $c['bookings'] ); ?></td>
											<td><?php echo wp_kses_post( $this->format_price( $c['total_spent'] ) ); ?></td>
											<td><?php echo esc_html( $c['last_booking'] ); ?></td>
											<td class="mpcrbm-col-actions">
												<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-view" data-key="<?php echo esc_attr( $c['key'] ); ?>">
													<span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'View', 'car-rental-manager' ); ?>
												</button>
												<button type="button" class="mpcrbm-btn mpcrbm-btn-ghost mpcrbm-cust-block-toggle<?php echo $is_blocked ? ' is-blocked' : ''; ?>" data-key="<?php echo esc_attr( $c['key'] ); ?>" data-context="row">
													<span class="dashicons dashicons-<?php echo $is_blocked ? 'unlock' : 'lock'; ?>"></span><?php echo $is_blocked ? esc_html__( 'Unblock', 'car-rental-manager' ) : esc_html__( 'Block', 'car-rental-manager' ); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
								</tbody>
							</table>
						</div>

						<?php if ( $pages > 1 ) : ?>
							<div class="mpcrbm-pagination">
								<?php
								echo wp_kses_post( paginate_links( array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'current'   => $paged,
									'total'     => $pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								) ) );
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="mpcrbm-cust-modal-overlay" id="mpcrbm-cust-modal" hidden>
					<div class="mpcrbm-cust-modal">
						<button type="button" class="mpcrbm-cust-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
						<div class="mpcrbm-cust-modal-body"></div>
					</div>
				</div>

				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}

		}

		new MPCRBM_Customers();
	}
