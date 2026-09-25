<?php
	/**
	 * SecureHold WP compatibility.
	 *
	 * SecureHold WP holds a security deposit on the customer's card (a Stripe
	 * authorization) instead of charging it. Its "Use MagePeople security deposit
	 * amounts" bridge only reads Booking and Rental Manager items, so on its own it
	 * never sees a car's deposit and this plugin keeps charging it as the
	 * "Security Deposit (Refundable)" cart fee. This class connects the two:
	 *
	 *  - WooCommerce checkout: a car's fixed-amount deposit is taken out of that
	 *    fee, and SecureHold holds it on the card after payment. The booking forms,
	 *    cart, checkout, order and Bookings list all say so.
	 *  - Wherever the hold cannot be placed, the deposit stays in the payment exactly
	 *    as before: the Custom Payment checkout (it creates no WooCommerce order, and
	 *    SecureHold only works on those), Stripe not connected yet, percentage
	 *    deposits (SecureHold holds fixed amounts only), cars excluded in SecureHold,
	 *    and carts under SecureHold's Minimum Cart Amount.
	 *
	 * SecureHold offers no filter for its cart-side calculation, which also decides
	 * whether the card is saved for the later hold. Its MagePeople bridge reads three
	 * meta keys from the cart product, so bridge_meta() answers those keys for a
	 * car's WooCommerce product whenever that car's deposit is held. The hold on an
	 * order comes only from what checkout recorded on it (HELD_META), through
	 * SecureHold's own securehold_computation_aggregate filter: see
	 * cover_held_deposits().
	 *
	 * @package Car_Rental_Manager
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_SecureHold_Compat' ) ) {
		class MPCRBM_SecureHold_Compat {

			const PLUGIN        = 'securehold-security-deposit-holds/securehold-wp-stripe-deposits.php';
			const SLUG          = 'securehold-security-deposit-holds';
			const BRIDGE_OPTION = 'securehold_magepeople_deposit_enabled';
			const GLOBAL_OPTION = 'securehold_default_hold_amount';
			const MIN_VERSION   = '3.4.11';

			/** Order item meta: the car deposit held on the card for this line instead of charged. */
			const HELD_META = '_mpcrbm_securehold_deposit';

			/** Meta keys SecureHold's MagePeople bridge reads (Securehold_Config_Resolver::get_magepeople_settings()). */
			const BRIDGE_KEYS = array( 'rbfw_enable_security_deposit', 'rbfw_security_deposit_type', 'rbfw_security_deposit_amount' );

			/** @var array|null Per-request Stripe readiness. */
			private static $stripe_status = null;

			/** @var bool Whether bridge_meta() is silent while cover_held_deposits() re-resolves an order. */
			private static $bridge_suspended = false;

			public static function init() {
				add_filter( 'get_post_metadata', array( __CLASS__, 'bridge_meta' ), 10, 3 );
				// Late, so it adjusts the final figure after any SecureHold add-on.
				add_filter( 'securehold_computation_aggregate', array( __CLASS__, 'cover_held_deposits' ), 99, 2 );

				// Cart and checkout deposit notice. For carts with cars it replaces
				// SecureHold's own notice, which cannot tell a held deposit from one this
				// plugin charges.
				add_action( 'init', array( __CLASS__, 'register_store_api_data' ) );
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_cart_notice' ), 100 );
				add_action( 'woocommerce_before_cart_totals', array( __CLASS__, 'classic_notices' ) );
				add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'classic_checkout_notices' ), 1 );

				// Deposit hold / charged deposit details under each booking's total.
				add_action( 'mpcrbm_booking_list_total_after', array( __CLASS__, 'booking_row' ) );
			}

			/** Whether SecureHold is running with "Use MagePeople security deposit amounts" on. */
			public static function bridge_enabled() {
				return defined( 'SECUREHOLD_PLUGIN_DIR' ) && 'yes' === get_option( self::BRIDGE_OPTION, 'no' );
			}

			/**
			 * Stripe settings SecureHold needs before it can hold anything.
			 *
			 * SecureHold places the hold with its own API keys, on the card the customer
			 * paid with through the official WooCommerce Stripe gateway, so that gateway
			 * must take payments and both must use the same Stripe mode. Reads options
			 * only: no API calls and no gateway loading.
			 *
			 * @return array{gateway_loaded:bool,gateway_enabled:bool,gateway_keys:bool,gateway_mode:string,securehold_keys:bool,securehold_mode:string,modes_match:bool}
			 */
			public static function stripe_status() {
				if ( null !== self::$stripe_status ) {
					return self::$stripe_status;
				}

				$gateway      = get_option( 'woocommerce_stripe_settings', array() );
				$gateway      = is_array( $gateway ) ? $gateway : array();
				$gateway_mode = ( isset( $gateway['testmode'] ) && 'yes' === $gateway['testmode'] ) ? 'test' : 'live';
				$key_prefix   = 'test' === $gateway_mode ? 'test_' : '';
				$sh_mode      = 'live' === get_option( 'securehold_stripe_mode', 'test' ) ? 'live' : 'test';

				self::$stripe_status = array(
					'gateway_loaded'  => defined( 'WC_STRIPE_VERSION' ),
					'gateway_enabled' => isset( $gateway['enabled'] ) && 'yes' === $gateway['enabled'],
					'gateway_keys'    => ! empty( $gateway[ $key_prefix . 'publishable_key' ] ) && ! empty( $gateway[ $key_prefix . 'secret_key' ] ),
					'gateway_mode'    => $gateway_mode,
					'securehold_keys' => '' !== trim( (string) get_option( 'securehold_stripe_' . $sh_mode . '_publishable_key', '' ) )
						&& '' !== trim( (string) get_option( 'securehold_stripe_' . $sh_mode . '_secret_key', '' ) ),
					'securehold_mode' => $sh_mode,
					'modes_match'     => $sh_mode === $gateway_mode,
				);

				return self::$stripe_status;
			}

			/** Whether a held deposit will actually be held rather than lost. */
			public static function can_hold() {
				if ( ! function_exists( 'WC' ) || ! class_exists( 'MPCRBM_Booking_Mode' ) || ! MPCRBM_Booking_Mode::is_woocommerce() ) {
					return false;
				}
				$stripe = self::stripe_status();
				$ready  = $stripe['gateway_loaded'] && $stripe['gateway_enabled'] && $stripe['gateway_keys']
					&& $stripe['securehold_keys'] && $stripe['modes_match'];

				/**
				 * Filters whether SecureHold can place deposit holds on this site.
				 *
				 * When false, car deposits stay in the booking total. Sites that connect
				 * Stripe in a way these option checks do not see can force it.
				 *
				 * @param bool  $ready  Whether WooCommerce checkout, the Stripe gateway and SecureHold's keys are ready.
				 * @param array $stripe Readiness details from stripe_status().
				 */
				return (bool) apply_filters( 'mpcrbm_securehold_can_hold', $ready, $stripe );
			}

			/**
			 * Whether SecureHold leaves a product out of every hold (its Exclusions settings).
			 *
			 * @param int $product_id WooCommerce product ID.
			 * @return bool
			 */
			private static function is_excluded( $product_id ) {
				if ( ! class_exists( 'Securehold_Deposit_Computation_Service' ) ) {
					$service = SECUREHOLD_PLUGIN_DIR . 'includes/services/class-securehold-wp-computation-service.php';
					if ( file_exists( $service ) ) {
						require_once $service;
					}
				}

				return method_exists( 'Securehold_Deposit_Computation_Service', 'is_product_excluded' )
					&& Securehold_Deposit_Computation_Service::is_product_excluded( (int) $product_id );
			}

			/**
			 * Deposit per car that goes on the customer's card instead of into the payment.
			 *
			 * Same eligibility as SecureHold's MagePeople bridge: a fixed amount above
			 * zero. Percentage deposits stay charged.
			 *
			 * @param int $car_id Car (mpcrbm_rent) ID.
			 * @return float Deposit per car, or 0 when it is charged with the booking.
			 */
			public static function held_unit_amount( $car_id ) {
				$car_id = (int) $car_id;
				if ( ! $car_id || ! self::bridge_enabled() || get_post_type( $car_id ) !== MPCRBM_Function::get_cpt() ) {
					return 0.0;
				}
				if ( 'on' !== get_post_meta( $car_id, 'mpcrbm_security_deposit_enable', true )
					|| 'percentage' === get_post_meta( $car_id, 'mpcrbm_security_deposit_type', true ) ) {
					return 0.0;
				}
				$amount = (float) get_post_meta( $car_id, 'mpcrbm_security_deposit', true );
				if ( $amount <= 0 || ! self::can_hold() ) {
					return 0.0;
				}
				$product_id = absint( get_post_meta( $car_id, 'link_wc_product', true ) );
				if ( ! $product_id || self::is_excluded( $product_id ) ) {
					return 0.0;
				}

				return $amount;
			}

			/**
			 * Whether the cart reaches SecureHold's Minimum Cart Amount.
			 *
			 * SecureHold skips the hold on smaller orders, and a held deposit is no
			 * longer part of the order total it compares, so check without it.
			 */
			private static function cart_meets_minimum() {
				if ( ! function_exists( 'WC' ) || ! WC()->cart || ! function_exists( 'wc_format_decimal' ) ) {
					return true;
				}
				$minimum = (float) wc_format_decimal( get_option( 'securehold_min_cart_amount', '' ) );
				if ( $minimum <= 0 ) {
					return true;
				}
				$cart  = WC()->cart;
				$total = (float) $cart->get_cart_contents_total() + (float) $cart->get_cart_contents_tax()
					+ (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();

				return $total >= $minimum;
			}

			/**
			 * Deposit of a cart line that SecureHold holds instead of the payment.
			 *
			 * MPCRBM_Woocommerce leaves this out of the "Security Deposit (Refundable)"
			 * fee and records it on the order line, so the fee, the order and the hold
			 * always agree.
			 *
			 * @param array $cart_item WooCommerce cart item.
			 * @return float Held amount for the whole line (deposit x car quantity), or 0.
			 */
			public static function cart_item_held_deposit( $cart_item ) {
				$car_id  = isset( $cart_item['mpcrbm_id'] ) ? (int) $cart_item['mpcrbm_id'] : 0;
				$deposit = isset( $cart_item['mpcrbm_security_deposit'] ) ? (float) $cart_item['mpcrbm_security_deposit'] : 0.0;
				if ( $deposit <= 0 || self::held_unit_amount( $car_id ) <= 0 || ! self::cart_meets_minimum() ) {
					return 0.0;
				}
				$quantity = isset( $cart_item['mpcrbm_car_quantity'] ) ? intval( $cart_item['mpcrbm_car_quantity'] ) : 1;

				return $deposit * $quantity;
			}

			/**
			 * Show a car's held deposit to SecureHold's MagePeople bridge.
			 *
			 * The bridge reads rbfw_enable_security_deposit, rbfw_security_deposit_type
			 * and rbfw_security_deposit_amount from the cart product. Answering them for
			 * a car's WooCommerce product (the hidden product that carries
			 * link_mpcrbm_id) makes SecureHold's cart calculation and checkout card
			 * saving treat the car deposit like a rental deposit. Only held deposits are
			 * answered, so SecureHold never holds one this plugin charges. Booking and
			 * Rental Manager never reads these keys from a car's product.
			 *
			 * @param mixed  $value     Short-circuit value, null to read the database.
			 * @param int    $object_id Post ID.
			 * @param string $meta_key  Meta key.
			 * @return mixed
			 */
			public static function bridge_meta( $value, $object_id, $meta_key ) {
				if ( null !== $value || self::$bridge_suspended || ! in_array( $meta_key, self::BRIDGE_KEYS, true ) ) {
					return $value;
				}
				$car_id = (int) get_post_meta( $object_id, 'link_mpcrbm_id', true );
				$amount = $car_id ? self::held_unit_amount( $car_id ) : 0.0;
				if ( $amount <= 0 ) {
					return $value;
				}

				switch ( $meta_key ) {
					case 'rbfw_enable_security_deposit':
						return array( 'yes' );
					case 'rbfw_security_deposit_type':
						return array( 'fixed' );
					default:
						return array( (string) $amount );
				}
			}

			/**
			 * Hold SecureHold must place to cover the car deposits taken off the payment.
			 *
			 * The car deposits count as one MagePeople deposit, with the car quantity
			 * that SecureHold itself never sees. Per Item mode sums lines, so each car
			 * line's share is replaced by that line's deposit. Per Order mode holds one
			 * winner, ranked the way SecureHold ranks a MagePeople deposit: an explicit
			 * Product Rule keeps its own hold; under Priority Chain the car deposits
			 * outrank Category Rules and the Global default; otherwise the higher amount
			 * wins.
			 *
			 * @param float  $amount    SecureHold's amount.
			 * @param string $source    SecureHold's winning source.
			 * @param string $mode      'per_order' or 'per_item_aggregated'.
			 * @param array  $breakdown SecureHold's per-item lines (Per Item mode).
			 * @param array  $held      Held car deposits by WooCommerce product ID.
			 * @param string $policy    'priority_chain' or 'highest_deposit_wins'.
			 * @return float
			 */
			private static function covering_hold( $amount, $source, $mode, $breakdown, array $held, $policy ) {
				$amount = (float) $amount;
				if ( 'per_item_aggregated' === $mode ) {
					$swapped = array();
					foreach ( (array) $breakdown as $line ) {
						$product_id = isset( $line['product_id'] ) ? (int) $line['product_id'] : 0;
						if ( ! isset( $held[ $product_id ] ) || ( isset( $line['source'] ) && 'product_rule' === $line['source'] ) ) {
							continue;
						}
						$amount                -= isset( $line['contribution'] ) ? (float) $line['contribution'] : 0.0;
						$swapped[ $product_id ] = $held[ $product_id ];
					}

					return round( max( 0.0, $amount + array_sum( $swapped ) ), 2 );
				}
				if ( 'product_rule' === $source ) {
					return $amount;
				}
				if ( 'highest_deposit_wins' === $policy || 'magepeople_deposit' === $source ) {
					return round( max( $amount, array_sum( $held ) ), 2 );
				}

				return round( array_sum( $held ), 2 );
			}

			/** SecureHold's resolution policy (Settings > Rule Engine). */
			private static function policy() {
				return method_exists( 'Securehold_Config_Resolver', 'get_active_policy' ) ? Securehold_Config_Resolver::get_active_policy() : 'priority_chain';
			}

			/**
			 * Whether an order has a car line with a security deposit, held or charged.
			 *
			 * @param WC_Order $order Order.
			 * @return bool
			 */
			private static function order_has_car_deposits( $order ) {
				foreach ( $order->get_items() as $item ) {
					if ( (float) $item->get_meta( self::HELD_META, true ) > 0 || (float) $item->get_meta( '_mpcrbm_security_deposit_amount', true ) > 0 ) {
						return true;
					}
				}

				return false;
			}

			/**
			 * Held car deposits on an order, by WooCommerce product ID.
			 *
			 * @param WC_Order $order Order.
			 * @return array<int,float>
			 */
			private static function order_held_deposits( $order ) {
				$held = array();
				foreach ( $order->get_items() as $item ) {
					$amount = (float) $item->get_meta( self::HELD_META, true );
					if ( $amount > 0 ) {
						$product_id          = (int) $item->get_product_id();
						$held[ $product_id ] = ( isset( $held[ $product_id ] ) ? $held[ $product_id ] : 0.0 ) + $amount;
					}
				}

				return $held;
			}

			/**
			 * Make SecureHold's order hold cover exactly the car deposits checkout took
			 * off the payment.
			 *
			 * SecureHold resolved the order through bridge_meta(), which answers from the
			 * car's current settings: once per car, and whether or not checkout charged
			 * the deposit (a cart under SecureHold's Minimum Cart Amount, or settings
			 * changed before a delayed hold). So the order is resolved again without the
			 * bridge, and the held deposits recorded on its lines are added to that.
			 *
			 * @param array    $result SecureHold's computed deposit configuration.
			 * @param WC_Order $order  Order the hold is for.
			 * @return array
			 */
			public static function cover_held_deposits( $result, $order ) {
				if ( self::$bridge_suspended || ! is_array( $result ) || ! is_a( $order, 'WC_Order' )
					|| ! method_exists( 'Securehold_Deposit_Computation_Service', 'compute' ) || ! self::order_has_car_deposits( $order ) ) {
					return $result;
				}

				self::$bridge_suspended = true;
				try {
					$result = Securehold_Deposit_Computation_Service::compute( $order );
				} finally {
					self::$bridge_suspended = false;
				}

				$held = self::order_held_deposits( $order );
				if ( ! $held ) {
					return $result;
				}

				$before = isset( $result['deposit_amount_resolved'] ) ? (float) $result['deposit_amount_resolved'] : 0.0;
				$source = isset( $result['source'] ) ? (string) $result['source'] : '';
				$mode   = isset( $result['aggregation_mode'] ) ? (string) $result['aggregation_mode'] : 'per_order';
				$policy = isset( $result['policy'] ) ? (string) $result['policy'] : self::policy();
				$amount = self::covering_hold( $before, $source, $mode, isset( $result['item_breakdown'] ) ? $result['item_breakdown'] : array(), $held, $policy );
				if ( abs( $amount - $before ) < 0.005 ) {
					return $result;
				}

				$result['deposit_amount']          = (string) $amount;
				$result['deposit_amount_resolved'] = $amount;
				if ( 'per_item_aggregated' !== $mode && ! in_array( $source, array( 'magepeople_deposit', 'product_rule' ), true ) ) {
					$product_id             = (int) key( $held );
					$product                = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
					$result['source']       = 'magepeople_deposit';
					$result['source_id']    = $product_id;
					$result['source_label'] = $product ? $product->get_formatted_name() : '#' . $product_id;
				}

				if ( function_exists( 'securehold_log' ) ) {
					securehold_log(
						'Car Rental Manager: hold covers the car security deposits',
						array(
							'order_id'     => $order->get_id(),
							'car_deposits' => array_sum( $held ),
							'before'       => $before,
							'amount'       => $amount,
						),
						'debug'
					);
				}

				return $result;
			}

			/** Whether the WooCommerce cart holds at least one car. */
			private static function cart_has_cars() {
				if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
					return false;
				}
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( ! empty( $cart_item['mpcrbm_id'] ) ) {
						return true;
					}
				}

				return false;
			}

			/**
			 * Deposit notices for the current cart.
			 *
			 * "held": the amount SecureHold will authorize, only when the hold can really
			 * be placed. "charged": car deposits this plugin adds to the payment, the
			 * same sum MPCRBM_Woocommerce bills as the "Security Deposit (Refundable)" fee.
			 *
			 * @return array<int,array{type:string,html:string}>
			 */
			public static function cart_deposit_notices() {
				if ( ! self::bridge_enabled() || ! self::cart_has_cars() ) {
					return array();
				}

				$cart    = WC()->cart->get_cart();
				$charged = 0.0;
				$held    = array();
				foreach ( $cart as $cart_item ) {
					if ( empty( $cart_item['mpcrbm_id'] ) ) {
						continue;
					}
					$quantity = isset( $cart_item['mpcrbm_car_quantity'] ) ? intval( $cart_item['mpcrbm_car_quantity'] ) : 1;
					$deposit  = ( isset( $cart_item['mpcrbm_security_deposit'] ) ? (float) $cart_item['mpcrbm_security_deposit'] : 0.0 ) * $quantity;
					$on_card  = self::cart_item_held_deposit( $cart_item );
					if ( $on_card > 0 ) {
						$product_id          = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
						$held[ $product_id ] = ( isset( $held[ $product_id ] ) ? $held[ $product_id ] : 0.0 ) + $on_card;
					} else {
						$charged += $deposit;
					}
				}

				$hold = 0.0;
				if ( $held ) {
					$result = array();
					if ( ! class_exists( 'Securehold_Deposit_Computation_Service' ) ) {
						$service = SECUREHOLD_PLUGIN_DIR . 'includes/services/class-securehold-wp-computation-service.php';
						if ( file_exists( $service ) ) {
							require_once $service;
						}
					}
					if ( method_exists( 'Securehold_Deposit_Computation_Service', 'compute_for_cart' ) ) {
						$result = Securehold_Deposit_Computation_Service::compute_for_cart( $cart );
					}
					$hold = self::covering_hold(
						! empty( $result['has_hold'] ) ? $result['total_amount'] : 0,
						isset( $result['source'] ) ? (string) $result['source'] : '',
						isset( $result['aggregation_mode'] ) ? (string) $result['aggregation_mode'] : 'per_order',
						isset( $result['item_breakdown'] ) ? $result['item_breakdown'] : array(),
						$held,
						self::policy()
					);
				}

				$notices = array();
				if ( $hold > 0 ) {
					$notices[] = array(
						'type' => 'held',
						'html' => sprintf(
							/* translators: %s: formatted deposit amount. */
							__( 'Security deposit of %s: not included in your total. It is held on your card when you pay by card (an authorization, not a charge) and released after the rental.', 'car-rental-manager' ),
							'<strong>' . wc_price( $hold ) . '</strong>'
						),
					);
				}
				if ( $charged > 0 ) {
					$notices[] = array(
						'type' => 'charged',
						'html' => sprintf(
							/* translators: %s: formatted deposit amount. */
							__( 'The refundable security deposit of %s is included in your total.', 'car-rental-manager' ),
							'<strong>' . wc_price( $charged ) . '</strong>'
						),
					);
				}

				return $notices;
			}

			/** Expose the notices on the Store API cart, for the Cart and Checkout blocks. */
			public static function register_store_api_data() {
				if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
					return;
				}

				woocommerce_store_api_register_endpoint_data(
					array(
						'endpoint'        => 'cart',
						'namespace'       => 'mpcrbm_securehold',
						'data_callback'   => function () {
							return array( 'notices' => self::cart_deposit_notices() );
						},
						'schema_callback' => function () {
							return array(
								'notices' => array(
									'description' => __( 'Security deposit notices for the cart.', 'car-rental-manager' ),
									'type'        => 'array',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
									'items'       => array(
										'type'       => 'object',
										'properties' => array(
											'type' => array( 'type' => 'string' ),
											'html' => array( 'type' => 'string' ),
										),
									),
								),
							);
						},
						'schema_type'     => ARRAY_A,
					)
				);
			}

			/** Load the block notice on the cart and checkout pages, instead of SecureHold's. */
			public static function enqueue_cart_notice() {
				if ( ! self::bridge_enabled() || ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) || is_wc_endpoint_url() ) {
					return;
				}

				if ( self::cart_has_cars() ) {
					wp_dequeue_script( 'securehold-checkout-blocks-notice' );
				}

				$script = MPCRBM_PLUGIN_DIR . '/assets/frontend/mpcrbm-securehold-cart-notice.js';
				wp_enqueue_script(
					'mpcrbm-securehold-cart-notice',
					MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-securehold-cart-notice.js',
					array( 'wp-element', 'wp-plugins', 'wc-blocks-checkout' ),
					file_exists( $script ) ? filemtime( $script ) : false,
					true
				);
			}

			/** Render the notices in the classic cart and checkout templates. */
			public static function classic_notices() {
				foreach ( self::cart_deposit_notices() as $notice ) {
					?>
					<div class="mpcrbm-securehold-note mpcrbm-deposit-notice-<?php echo esc_attr( $notice['type'] ); ?>">
						<span class="fas <?php echo esc_attr( 'held' === $notice['type'] ? 'fa-lock' : 'fa-info-circle' ); ?>" aria-hidden="true"></span>
						<span><?php echo wp_kses_post( $notice['html'] ); ?></span>
					</div>
					<?php
				}
			}

			/**
			 * Classic checkout: show the notices and take SecureHold's out for carts with
			 * cars, whichever of the two payment hooks its position setting uses.
			 */
			public static function classic_checkout_notices() {
				if ( self::bridge_enabled() && self::cart_has_cars() ) {
					global $wp_filter;
					foreach ( array( 'woocommerce_review_order_before_payment', 'woocommerce_review_order_after_payment' ) as $hook ) {
						if ( empty( $wp_filter[ $hook ] ) ) {
							continue;
						}
						foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
							foreach ( $callbacks as $callback ) {
								if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] )
									&& 'Securehold_Frontend_Manager' === get_class( $callback['function'][0] )
									&& 'display_checkout_message' === $callback['function'][1] ) {
									remove_action( $hook, $callback['function'], $priority );
								}
							}
						}
					}
				}

				self::classic_notices();
			}

			/**
			 * Card-hold sentence for a car's booking forms and summaries.
			 *
			 * @param int $car_id Car ID.
			 * @return string Empty when the car's deposit is charged with the booking.
			 */
			public static function form_note( $car_id ) {
				$amount = self::held_unit_amount( $car_id );
				if ( $amount <= 0 ) {
					return '';
				}

				return sprintf(
					/* translators: %s: formatted deposit amount per car. */
					__( 'The %s security deposit per car is held on your card at checkout. It is not added to the amount you pay.', 'car-rental-manager' ),
					MPCRBM_Global_Function::format_price( $amount )
				);
			}

			/**
			 * Latest SecureHold hold recorded for a WooCommerce order.
			 *
			 * @param int $order_id WooCommerce order ID.
			 * @return object|null Row from SecureHold's holds table.
			 */
			private static function order_hold( $order_id ) {
				if ( ! class_exists( 'SecureHold_DB' ) ) {
					$db = SECUREHOLD_PLUGIN_DIR . 'includes/database/class-securehold-wp-db.php';
					if ( file_exists( $db ) ) {
						require_once $db;
					}
				}

				return method_exists( 'SecureHold_DB', 'get_deposit' ) ? SecureHold_DB::get_deposit( (int) $order_id ) : null;
			}

			/**
			 * Under a WooCommerce booking's total (free Bookings list, Pro order list and
			 * order view): the deposit hold, what happens to it next, and, when the
			 * deposit was charged or not secured, what to do about it.
			 *
			 * @param array $row Booking row: ID (booking post), is_woo, order_id (WooCommerce order).
			 */
			public static function booking_row( $row ) {
				if ( ! defined( 'SECUREHOLD_PLUGIN_DIR' ) || empty( $row['is_woo'] ) || empty( $row['order_id'] ) || ! function_exists( 'wc_get_order' ) ) {
					return;
				}
				$held    = (float) get_post_meta( (int) $row['ID'], 'mpcrbm_security_deposit_held', true );
				$charged = (float) get_post_meta( (int) $row['ID'], 'mpcrbm_security_deposit_amount', true );
				// Nothing held here and the integration is off: skip the per-row lookups
				// (Pro's order list renders every booking at once).
				if ( $held <= 0 && ! self::bridge_enabled() ) {
					return;
				}
				$order = wc_get_order( (int) $row['order_id'] );
				if ( ! $order ) {
					return;
				}
				$hold = self::order_hold( $order->get_id() );
				if ( ! $hold && $held <= 0 && ( $charged <= 0 || ! self::bridge_enabled() ) ) {
					return;
				}

				$currency = array( 'currency' => $order->get_currency() );
				$date     = function ( $mysql ) {
					return $mysql ? mysql2date( get_option( 'date_format' ), $mysql ) : '';
				};
				$link     = '';
				$link_t   = '';

				if ( $hold ) {
					$amount   = (float) $hold->amount;
					$captured = (float) $hold->captured_amount;
					switch ( $hold->status ) {
						case 'authorized':
							$badge  = __( 'Held, not charged', 'car-rental-manager' );
							$state  = 'held';
							$detail = 'yes' === get_option( 'securehold_auto_release', 'no' )
								/* translators: %s: release date. */
								? sprintf( __( 'Released automatically on %s unless you capture it for damages in SecureHold. Nothing needs refunding.', 'car-rental-manager' ), $date( $hold->expires_at ) )
								/* translators: %s: hold expiry date. */
								: sprintf( __( 'Auto-release is off in SecureHold: capture or release it there before %s. Stripe cancels an uncaptured hold after about 7 days.', 'car-rental-manager' ), $date( $hold->expires_at ) );
							break;
						case 'captured':
							$state = 'captured';
							if ( $captured > 0 && $captured < $amount ) {
								$badge  = __( 'Partly captured', 'car-rental-manager' );
								$detail = sprintf(
									/* translators: 1: captured amount, 2: held amount. */
									__( '%1$s of the %2$s hold was charged for damages; the rest was released. Refund any of it from Stripe if needed.', 'car-rental-manager' ),
									wp_strip_all_tags( wc_price( $captured, $currency ) ),
									wp_strip_all_tags( wc_price( $amount, $currency ) )
								);
							} else {
								$badge  = __( 'Captured', 'car-rental-manager' );
								$detail = __( 'The deposit was charged for damages. Refund it from Stripe if needed.', 'car-rental-manager' );
							}
							break;
						case 'released':
							$badge = __( 'Released', 'car-rental-manager' );
							$state = 'released';
							/* translators: %s: release date. */
							$detail = sprintf( __( 'Released on %s. The customer was never charged.', 'car-rental-manager' ), $date( $hold->released_at ? $hold->released_at : $hold->updated_at ) );
							break;
						case 'failed':
							$badge  = __( 'Hold failed', 'car-rental-manager' );
							$state  = 'failed';
							$reason = (string) $order->get_meta( '_securehold_hold_failure_reason', true );
							$detail = $reason
								/* translators: %s: failure reason code. */
								? sprintf( __( 'No deposit is secured for this booking (%s).', 'car-rental-manager' ), $reason )
								: __( 'No deposit is secured for this booking.', 'car-rental-manager' );
							break;
						default:
							$badge  = ucfirst( (string) $hold->status );
							$state  = 'pending';
							$detail = __( 'SecureHold has not placed the hold yet.', 'car-rental-manager' );
					}
					$figure = wc_price( $amount, $currency );
					$label  = __( 'Deposit hold', 'car-rental-manager' );
					if ( current_user_can( 'manage_woocommerce' ) ) {
						$link   = admin_url( 'admin.php?page=securehold-deposit-details&deposit_id=' . (int) $hold->id );
						$link_t = __( 'Manage in SecureHold', 'car-rental-manager' );
					}
				} elseif ( $held > 0 ) {
					$figure = wc_price( $held, $currency );
					$label  = __( 'Deposit hold', 'car-rental-manager' );
					if ( false === strpos( (string) $order->get_payment_method(), 'stripe' ) ) {
						$badge  = __( 'Not secured', 'car-rental-manager' );
						$state  = 'failed';
						$detail = sprintf(
							/* translators: %s: payment method title. */
							__( 'Paid with %s, so SecureHold could not hold the deposit and it was not charged either. Collect it before the rental.', 'car-rental-manager' ),
							$order->get_payment_method_title() ? $order->get_payment_method_title() : $order->get_payment_method()
						);
					} else {
						$badge  = __( 'Pending', 'car-rental-manager' );
						$state  = 'pending';
						$detail = __( 'SecureHold has not placed the hold yet.', 'car-rental-manager' );
					}
				} else {
					$badge  = __( 'Charged with the order', 'car-rental-manager' );
					$state  = 'charged';
					$detail = __( 'This deposit could not be held on the card, so it was paid with the booking. It is not refunded automatically: refund it from the order after the rental.', 'car-rental-manager' );
					$figure = wc_price( $charged, $currency );
					$label  = __( 'Security deposit', 'car-rental-manager' );
				}
				if ( ! $link && current_user_can( 'edit_shop_orders' ) ) {
					$link   = $order->get_edit_order_url();
					$link_t = __( 'Open order', 'car-rental-manager' );
				}
				?>
				<div class="mpcrbm-sh-booking">
					<span class="mpcrbm-sh-head">
						<span class="dashicons <?php echo esc_attr( $hold || $held > 0 ? 'dashicons-lock' : 'dashicons-money-alt' ); ?>" aria-hidden="true"></span>
						<span><?php echo esc_html( $label ); ?></span>
						<strong><?php echo wp_kses_post( $figure ); ?></strong>
					</span>
					<span class="mpcrbm-sh-badge is-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $badge ); ?></span>
					<span class="mpcrbm-sh-detail"><?php echo esc_html( $detail ); ?></span>
					<?php if ( $link ) : ?>
						<a class="mpcrbm-sh-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $link_t ); ?></a>
					<?php endif; ?>
				</div>
				<?php
			}

			/** Styles for booking_row(), for admin screens that do not print their own. */
			public static function booking_row_styles() {
				?>
				<style>
				.mpcrbm-sh-booking{display:flex;flex-direction:column;align-items:flex-start;gap:4px;margin-top:8px;padding-top:6px;border-top:1px dashed #e7e7ea;font-size:11.5px;line-height:1.5;color:#334155;white-space:normal;max-width:280px;}
				.mpcrbm-sh-head{display:flex;align-items:center;gap:5px;}
				.mpcrbm-sh-head .dashicons{color:#3157b7;font-size:15px;width:15px;height:15px;}
				.mpcrbm-sh-badge{border-radius:999px;font-size:10.5px;font-weight:600;padding:1px 8px;}
				.mpcrbm-sh-badge.is-held{background:#e0ecff;color:#1d4ed8;}
				.mpcrbm-sh-badge.is-captured{background:#fff4e5;color:#b45309;}
				.mpcrbm-sh-badge.is-released{background:#e8f7ee;color:#15803d;}
				.mpcrbm-sh-badge.is-failed{background:#fdecec;color:#b42318;}
				.mpcrbm-sh-badge.is-charged,.mpcrbm-sh-badge.is-pending{background:#f1f5f9;color:#475569;}
				.mpcrbm-sh-detail{color:#64748b;}
				.mpcrbm-sh-link{font-weight:600;}
				</style>
				<?php
			}
		}

		MPCRBM_SecureHold_Compat::init();
	}
