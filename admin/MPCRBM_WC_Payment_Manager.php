<?php
	/**
	 * WooCommerce Payment Methods Manager for the Car Rental Manager plugin.
	 *
	 * Renders every WooCommerce payment gateway's OWN native settings form inline,
	 * inside Payments → WooCommerce. Each gateway's fields are produced by WooCommerce
	 * itself (generate_settings_html / get_form_fields) and saved through the gateway's
	 * own process_admin_options(). Nothing is re-implemented — this is WooCommerce's
	 * real configuration, embedded in the car-rental admin shell.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_WC_Payment_Manager' ) ) :

		class MPCRBM_WC_Payment_Manager {

			private static $instance = null;

			public static function instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			private function __construct() {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
				add_action( 'wp_ajax_mpcrbm_wc_save_gateway', array( $this, 'ajax_save_gateway' ) );
				add_action( 'wp_ajax_mpcrbm_wc_toggle_gateway', array( $this, 'ajax_toggle_gateway' ) );
			}

			// ---------------------------------------------------------------
			// Assets
			// ---------------------------------------------------------------

			public function enqueue_assets( $hook ) {
				unset( $hook );

				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				if ( ! $screen ) {
					return;
				}
				$is_settings_page = strpos( $screen->id, 'mpcrbm_settings_page' ) !== false;
				// Also needed on the car add/edit screen: the Payment Method sidebar card's
				// "Configure payment method" popup embeds this same manager when Booking
				// Mode is WooCommerce.
				$is_car_edit = 'post' === $screen->base
					&& class_exists( 'MPCRBM_Function' ) && $screen->post_type === MPCRBM_Function::get_cpt();
				if ( ! $is_settings_page && ! $is_car_edit ) {
					return;
				}

				// WooCommerce admin styling + the scripts its native fields rely on.
				if ( function_exists( 'WC' ) && class_exists( 'WooCommerce' ) ) {
					wp_enqueue_style( 'woocommerce_admin_styles' );
					wp_enqueue_script( 'wc-enhanced-select' );
					wp_enqueue_script( 'wc-jquery-tiptip' );
				}

				$js_path = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-wc-payment-manager.js';
				$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : ( defined( 'MPCRBM_PLUGIN_VERSION' ) ? MPCRBM_PLUGIN_VERSION : '1.0.0' );

				wp_enqueue_script(
					'mpcrbm-wc-payment-manager',
					MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm-wc-payment-manager.js',
					array( 'jquery' ),
					$js_ver,
					true
				);
				wp_localize_script(
					'mpcrbm-wc-payment-manager',
					'mpcrbmWcPaymentManager',
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'mpcrbm_wc_payment_manager' ),
						'i18n'    => array(
							'saving'    => __( 'Saving…', 'car-rental-manager' ),
							'saved'     => __( 'Saved!', 'car-rental-manager' ),
							'error'     => __( 'An error occurred. Please try again.', 'car-rental-manager' ),
							'enabled'   => __( 'Enabled', 'car-rental-manager' ),
							'disabled'  => __( 'Disabled', 'car-rental-manager' ),
							'configure' => __( 'Configure', 'car-rental-manager' ),
							'close'     => __( 'Close', 'car-rental-manager' ),
						),
					)
				);
			}

			// ---------------------------------------------------------------
			// Gateway collection (includes suppressed ones, e.g. PayPal Standard)
			// ---------------------------------------------------------------

			private function get_all_gateways() {
				if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
					return array();
				}

				$wc_defaults = array( 'WC_Gateway_BACS', 'WC_Gateway_Cheque', 'WC_Gateway_COD', 'WC_Gateway_Paypal' );
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
				$gateway_classes = apply_filters( 'woocommerce_payment_gateways', $wc_defaults );

				$loaded   = WC()->payment_gateways()->payment_gateways();
				$gateways = array();
				foreach ( $loaded as $g ) {
					if ( $g instanceof WC_Payment_Gateway ) {
						$gateways[ $g->id ] = $g;
					}
				}
				foreach ( $gateway_classes as $class ) {
					if ( ! is_string( $class ) || ! class_exists( $class ) ) {
						continue;
					}
					$already = false;
					foreach ( $gateways as $g ) {
						if ( $g instanceof $class ) {
							$already = true;
							break;
						}
					}
					if ( ! $already ) {
						$instance = new $class();
						if ( $instance instanceof WC_Payment_Gateway && ! isset( $gateways[ $instance->id ] ) ) {
							$gateways[ $instance->id ] = $instance;
						}
					}
				}

				// Respect WooCommerce's saved gateway order.
				$order = (array) get_option( 'woocommerce_gateway_order', array() );
				if ( ! empty( $order ) ) {
					uasort(
						$gateways,
						static function ( $a, $b ) use ( $order ) {
							$pa = isset( $order[ $a->id ] ) ? (int) $order[ $a->id ] : 999;
							$pb = isset( $order[ $b->id ] ) ? (int) $order[ $b->id ] : 999;

							return $pa <=> $pb;
						}
					);
				}

				return $gateways;
			}

			private function get_gateway( $gateway_id ) {
				$gateways = $this->get_all_gateways();

				return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
			}

			/**
			 * How many registered WooCommerce gateways are currently enabled. Used by
			 * MPCRBM_Payment_Status_Checker to decide whether WooCommerce contributes any
			 * usable payment method.
			 */
			public function count_enabled_gateways() {
				$count = 0;
				foreach ( $this->get_all_gateways() as $gateway ) {
					if ( 'yes' === $gateway->enabled ) {
						$count++;
					}
				}

				return $count;
			}

			/**
			 * Titles of the currently enabled WooCommerce gateways — used by the car edit
			 * screen's "Payment Method" sidebar card to show which gateway(s) are live.
			 *
			 * @return string[]
			 */
			public function get_enabled_gateway_titles() {
				$titles = array();
				foreach ( $this->get_all_gateways() as $gateway ) {
					if ( 'yes' === $gateway->enabled ) {
						$titles[] = $gateway->get_method_title() ? $gateway->get_method_title() : $gateway->get_title();
					}
				}

				return $titles;
			}

			private function verify_request() {
				check_ajax_referer( 'mpcrbm_wc_payment_manager', 'nonce' );
				if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( __( 'Permission denied.', 'car-rental-manager' ), 403 );
				}
				if ( ! class_exists( 'WooCommerce' ) ) {
					wp_send_json_error( __( 'WooCommerce is not active.', 'car-rental-manager' ) );
				}
			}

			// ---------------------------------------------------------------
			// AJAX: save one gateway's native form (process_admin_options)
			// ---------------------------------------------------------------

			public function ajax_save_gateway() {
				$this->verify_request();

				$gateway_id = isset( $_POST['gateway_id'] ) ? sanitize_key( wp_unslash( $_POST['gateway_id'] ) ) : '';
				$gateway    = $this->get_gateway( $gateway_id );
				if ( ! $gateway ) {
					wp_send_json_error( __( 'Gateway not found.', 'car-rental-manager' ) );
				}

				// process_admin_options() reads $_POST keyed as woocommerce_{id}_{field};
				// our JS submits the native form fields under exactly those names.
				$gateway->process_admin_options();

				$errors = $gateway->get_errors();
				if ( ! empty( $errors ) ) {
					wp_send_json_error( implode( ' ', array_map( 'wp_strip_all_tags', $errors ) ) );
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
				do_action( 'woocommerce_update_options_payment_gateways_' . $gateway->id );
				if ( WC()->payment_gateways() ) {
					WC()->payment_gateways()->init();
				}

				$refreshed = $this->get_gateway( $gateway_id );
				wp_send_json_success(
					array_merge(
						array(
							'message' => __( 'Settings saved successfully!', 'car-rental-manager' ),
							'enabled' => ( $refreshed && 'yes' === $refreshed->enabled ) ? 'yes' : 'no',
						),
						$this->payment_state()
					)
				);
			}

			/**
			 * Current payment-readiness state, for the browser to sync notices/cards with.
			 * Guarded because this class is loaded before MPCRBM_Payment_Settings.
			 */
			private function payment_state(): array {
				return class_exists( 'MPCRBM_Payment_Settings' ) ? MPCRBM_Payment_Settings::get_payment_state() : array();
			}

			// ---------------------------------------------------------------
			// AJAX: quick enable/disable from the card header
			// ---------------------------------------------------------------

			public function ajax_toggle_gateway() {
				$this->verify_request();

				$gateway_id = isset( $_POST['gateway_id'] ) ? sanitize_key( wp_unslash( $_POST['gateway_id'] ) ) : '';
				$enabled    = ( isset( $_POST['enabled'] ) && 'yes' === $_POST['enabled'] ) ? 'yes' : 'no';
				if ( empty( $gateway_id ) ) {
					wp_send_json_error( __( 'Invalid gateway.', 'car-rental-manager' ) );
				}

				$option_key = 'woocommerce_' . $gateway_id . '_settings';
				$opts       = get_option( $option_key, array() );
				if ( ! is_array( $opts ) ) {
					$opts = array();
				}
				$opts['enabled'] = $enabled;
				if ( 'yes' === $enabled ) {
					$opts['_should_load'] = 'yes';
				}
				update_option( $option_key, $opts );

				if ( WC()->payment_gateways() ) {
					WC()->payment_gateways()->init();
				}

				wp_send_json_success( array_merge( array( 'enabled' => $enabled ), $this->payment_state() ) );
			}

			// ---------------------------------------------------------------
			// Render — called from the WooCommerce section of the Payments tab
			// ---------------------------------------------------------------

			public function render() {
				if ( ! class_exists( 'WooCommerce' ) ) {
					return;
				}

				$gateways = $this->get_all_gateways();
				if ( empty( $gateways ) ) {
					echo '<p>' . esc_html__( 'No payment gateways are registered.', 'car-rental-manager' ) . '</p>';

					return;
				}
				?>
				<div class="mpcrbm-wc-payment-manager">
					<div class="mpcrbm-wc-pm-bar">
						<h3 class="mpcrbm-wc-pm-heading"><?php esc_html_e( 'WooCommerce Payment Methods', 'car-rental-manager' ); ?></h3>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>" class="mpcrbm-wc-pm-wc-link" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open in WooCommerce', 'car-rental-manager' ); ?>
							<span class="dashicons dashicons-external"></span>
						</a>
					</div>

					<?php
					foreach ( $gateways as $gateway ) :
						$is_enabled = ( 'yes' === $gateway->enabled );
						$title      = $gateway->get_method_title() ? $gateway->get_method_title() : $gateway->get_title();
						$desc       = $gateway->get_method_description() ? $gateway->get_method_description() : $gateway->get_description();
						?>
						<div class="mpcrbm-gw-card <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>" data-gateway-id="<?php echo esc_attr( $gateway->id ); ?>">
							<div class="mpcrbm-gw-head">
								<div class="mpcrbm-gw-head-main">
									<label class="mpcrbm-gw-toggle" title="<?php esc_attr_e( 'Enable / disable', 'car-rental-manager' ); ?>">
										<input type="checkbox" class="mpcrbm-gw-toggle-input" data-gateway-id="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $is_enabled ); ?>>
										<span class="mpcrbm-gw-toggle-slider"></span>
									</label>
									<span class="mpcrbm-gw-title"><?php echo esc_html( $title ); ?></span>
									<span class="mpcrbm-gw-badge"><?php echo $is_enabled ? esc_html__( 'Enabled', 'car-rental-manager' ) : esc_html__( 'Disabled', 'car-rental-manager' ); ?></span>
								</div>
								<button type="button" class="mpcrbm-gw-configure-btn"><?php esc_html_e( 'Configure', 'car-rental-manager' ); ?></button>
							</div>

							<?php if ( $desc ) : ?>
								<div class="mpcrbm-gw-desc"><?php echo wp_kses_post( wpautop( $desc ) ); ?></div>
							<?php endif; ?>

							<div class="mpcrbm-gw-body" style="display:none;">
								<?php
								// Deliberately a <div>, not a <form>: this sits inside the
								// settings <form>, and nested forms are invalid HTML (the
								// browser silently drops the inner one). The container's
								// inputs are serialized and saved over AJAX instead.
								?>
								<div class="mpcrbm-gw-form" data-gateway-id="<?php echo esc_attr( $gateway->id ); ?>">
									<table class="form-table mpcrbm-gw-form-table">
										<?php
										// WooCommerce's OWN field rendering for this gateway.
										echo $gateway->generate_settings_html( $gateway->get_form_fields(), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</table>
									<div class="mpcrbm-gw-form-footer">
										<button type="button" class="mpcrbm-gw-save-btn"><?php esc_html_e( 'Save changes', 'car-rental-manager' ); ?></button>
										<span class="mpcrbm-gw-status"></span>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			}
		}

		// Always instantiate so the admin_enqueue_scripts + AJAX hooks register. This runs
		// during plugin include, before WooCommerce has loaded — gating on
		// class_exists('WooCommerce') here would silently skip hook registration. Each
		// method guards WooCommerce availability internally.
		MPCRBM_WC_Payment_Manager::instance();

	endif;
