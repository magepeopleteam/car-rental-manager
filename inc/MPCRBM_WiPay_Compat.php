<?php
	/**
	 * WiPay WooCommerce Blocks compatibility.
	 *
	 * The official "Credit/Debit Card (via WiPay)" plugin (gateway id wipay_credit)
	 * is a classic WC_Payment_Gateway only. WooCommerce's Checkout block renders a
	 * gateway only when a block payment-method integration is registered for it
	 * (woocommerce_blocks_payment_method_type_registration plus a client-side
	 * window.wc.wcBlocksRegistry.registerPaymentMethod call), so WiPay is invisible
	 * in block checkout even though the Store API lists it as an available gateway.
	 *
	 * This class registers that missing integration, but only for carts that contain
	 * a car-rental booking (cart item key mpcrbm_id). Non-car carts and classic
	 * checkout are untouched. It stands down silently when WooCommerce, the WiPay
	 * gateway, or the WooCommerce Blocks payment API is unavailable, when the gateway
	 * is not enabled/available, or when WiPay itself already registers a block
	 * integration (the registry already has wipay_credit).
	 *
	 * @package Car_Rental_Manager
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MPCRBM_WiPay_Compat' ) ) {
		class MPCRBM_WiPay_Compat {

			const GATEWAY_ID    = 'wipay_credit';
			const SCRIPT_HANDLE = 'mpcrbm-wipay-blocks';
			const SCRIPT_FILE   = 'assets/frontend/mpcrbm-wipay-blocks.js';

			public static function init() {
				// Late enough that core and a future WiPay block integration have registered.
				add_action( 'woocommerce_blocks_payment_method_type_registration', array( __CLASS__, 'register' ), 20 );
				// WiPay's classic checkout hook fills its payment metadata on
				// woocommerce_checkout_update_order_meta, which the Store API never fires.
				add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'sync_order_meta' ), 10, 2 );
			}

			/** Whether the site owner kept this compatibility layer on. */
			public static function is_enabled() {
				return (bool) apply_filters( 'mpcrbm_enable_wipay_block_checkout', true );
			}

			/** The live WiPay gateway instance, or null when its plugin is absent. */
			public static function gateway() {
				if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
					return null;
				}
				// WC()->payment_gateways is a magic property, so it must be read, not tested with empty().
				$gateways_api = WC()->payment_gateways;
				if ( ! is_object( $gateways_api ) ) {
					return null;
				}
				$gateways = $gateways_api->payment_gateways();
				if ( ! isset( $gateways[ self::GATEWAY_ID ] ) || ! ( $gateways[ self::GATEWAY_ID ] instanceof WC_Payment_Gateway ) ) {
					return null;
				}

				return $gateways[ self::GATEWAY_ID ];
			}

			/**
			 * Whether the cart holds a car-rental booking.
			 *
			 * Same convention as MPCRBM_SecureHold_Compat::cart_has_cars(): a
			 * WooCommerce cart item that carries the mpcrbm_id cart item key.
			 */
			public static function cart_has_cars() {
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
			 * Fills the order metadata WiPay's process_payment() requires.
			 *
			 * The WiPay plugin writes total_nofee/fee_structure/environment from
			 * woocommerce_checkout_update_order_meta, a classic-checkout hook the
			 * Store API does not fire. Without total_nofee, its process_payment()
			 * calls number_format( '' ) and fatals on PHP 8 before redirecting.
			 * This only fills missing gateway metadata from the order's own total;
			 * it never changes prices, totals, or the order schema.
			 *
			 * @param WC_Order        $order   Order being processed by the Store API.
			 * @param WP_REST_Request $request Checkout request.
			 */
			public static function sync_order_meta( $order, $request ) {
				if ( ! self::is_enabled() || ! ( $order instanceof WC_Order ) || self::GATEWAY_ID !== $order->get_payment_method() ) {
					return;
				}
				if ( (string) $order->get_meta( 'total_nofee' ) === '' ) {
					$order->update_meta_data( 'total_nofee', number_format( (float) $order->get_total(), 2, '.', '' ) );
				}
				if ( (string) $order->get_meta( 'fee_structure' ) === '' ) {
					$settings = get_option( 'woocommerce_' . self::GATEWAY_ID . '_settings', array() );
					$settings = is_array( $settings ) ? $settings : array();
					$order->update_meta_data( 'fee_structure', isset( $settings['fee_structure'] ) ? (string) $settings['fee_structure'] : 'merchant_absorb' );
					$order->update_meta_data( 'environment', ( isset( $settings['sandbox_enabled'] ) && 'yes' === $settings['sandbox_enabled'] ) ? 'sandbox' : 'live' );
					$order->update_meta_data( 'total_fee', '0.00' );
				}
			}

			/**
			 * Registers the block integration for the current request.
			 *
			 * @param object $registry PaymentMethodRegistry instance from WooCommerce Blocks.
			 */
			public static function register( $registry ) {
				if ( ! self::is_enabled() || ! is_object( $registry )
					|| ! method_exists( $registry, 'is_registered' ) || ! method_exists( $registry, 'register' ) ) {
					return;
				}
				if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' )
					|| ! class_exists( 'WC_Payment_Gateway' ) ) {
					return;
				}
				// A future WiPay release may register its own block integration; never double-register.
				if ( $registry->is_registered( self::GATEWAY_ID ) ) {
					return;
				}
				// Lightweight registration only. The gateway and cart are resolved lazily in
				// is_active(), which WooCommerce calls only for the cart/checkout blocks;
				// this keeps every other request free of gateway/cart work.
				$integration = mpcrbm_wipay_block_payment_method();
				if ( $integration ) {
					$registry->register( $integration );
				}
			}
		}
		MPCRBM_WiPay_Compat::init();
	}

	if ( ! function_exists( 'mpcrbm_wipay_block_payment_method' ) ) {
		/**
		 * Builds the block payment-method integration.
		 *
		 * The integration class is declared here, lazily, because this file loads with
		 * the plugin even on a site without WooCommerce or WooCommerce Blocks: an
		 * unconditional `extends AbstractPaymentMethodType` would be a fatal error there.
		 * Callers must confirm the abstract class exists first.
		 *
		 * @return object|null
		 */
		function mpcrbm_wipay_block_payment_method() {
			if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				return null;
			}

			if ( ! class_exists( 'MPCRBM_WiPay_Block_Payment_Method' ) ) {
				class MPCRBM_WiPay_Block_Payment_Method extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

					public function __construct() {
						$this->name = MPCRBM_WiPay_Compat::GATEWAY_ID;
					}

					public function initialize() {
						// Nothing to load: the gateway already exposes title/description.
					}

					public function is_active() {
						if ( ! MPCRBM_WiPay_Compat::is_enabled() || ! MPCRBM_WiPay_Compat::cart_has_cars() ) {
							return false;
						}
						$gateway = MPCRBM_WiPay_Compat::gateway();

						return $gateway && $gateway->is_available();
					}

					public function get_payment_method_script_handles() {
						$script_path = MPCRBM_PLUGIN_DIR . '/' . MPCRBM_WiPay_Compat::SCRIPT_FILE;
						$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : MPCRBM_PLUGIN_VERSION;
						wp_register_script(
							MPCRBM_WiPay_Compat::SCRIPT_HANDLE,
							MPCRBM_PLUGIN_URL . MPCRBM_WiPay_Compat::SCRIPT_FILE,
							array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ),
							$version,
							true
						);

						return array( MPCRBM_WiPay_Compat::SCRIPT_HANDLE );
					}

					public function get_payment_method_data() {
						$gateway = MPCRBM_WiPay_Compat::gateway();
						$title   = $gateway instanceof WC_Payment_Gateway ? wp_strip_all_tags( $gateway->get_title() ) : '';
						$title = $title ? html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : __( 'Credit/Debit Card (via WiPay)', 'car-rental-manager' );
						$description = $gateway instanceof WC_Payment_Gateway ? $gateway->get_description() : '';

						return array(
							'name'        => MPCRBM_WiPay_Compat::GATEWAY_ID,
							'title'       => $title,
							// Sanitised server-side; the block script renders it as HTML.
							'description' => $description ? wp_kses_post( $description ) : '',
							'supports'    => array( 'products' ),
						);
					}
				}
			}

			return new MPCRBM_WiPay_Block_Payment_Method();
		}
	}
