<?php
	/**
	 * Payments settings tab for the Car Rental Manager global settings page.
	 *
	 * - Registers a "Payments" tab + a "Currency Settings" tab via mpcrbm_settings_sec_reg.
	 * - Renders the Booking Mode selector (the single switch deciding whether WooCommerce
	 *   or the standalone Custom Payment checkout takes bookings — see
	 *   inc/MPCRBM_Booking_Mode.php), the WooCommerce gateway manager, and the
	 *   PayPal / Stripe / Offline gateway cards.
	 * - Injects the gateway Configure modals + the WooCommerce install/activate modal on
	 *   admin_footer as raw HTML, so the SVG / button / input markup is not stripped by
	 *   the html field's wp_kses pass.
	 *
	 * Gateway credentials live in the mpcrbm_payment_settings option and are saved in real
	 * time over AJAX from their own modals, so they survive the Settings API saving the
	 * rest of the form (see preserve_gateway_keys()).
	 *
	 * PayPal & Stripe Configure are gated behind the Pro plugin (MPCRBM_Plugin_Pro); the
	 * free version shows a PRO badge for those two. Offline Payment and its standalone
	 * checkout ship in the FREE plugin, so its card, Configure modal, AJAX save and
	 * customer booking flow all work without Pro.
	 *
	 * Layout note: unlike a stock WP settings screen, MPCRBM_Setting_API renders each
	 * field as a <section> inside a 2-column CSS grid (.mpcrbm-info-grid), not as a
	 * <table class="form-table"> row. Every show/hide rule here therefore targets
	 * `section.<class>`, and full-width panels opt out of the grid with `mpcrbm-fullrow`.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! class_exists( 'MPCRBM_Payment_Settings' ) ) :
		class MPCRBM_Payment_Settings {

			const OPTION          = 'mpcrbm_payment_settings';
			const CURRENCY_OPTION = 'mpcrbm_currency_settings';

			public function __construct() {
				add_filter( 'mpcrbm_settings_sec_reg', array( $this, 'register_section' ), 15 );
				add_filter( 'mpcrbm_settings_sec_fields', array( $this, 'register_fields' ), 15 );

				add_action( 'admin_footer', array( $this, 'render_wc_warning_modal' ) );
				add_action( 'admin_footer', array( $this, 'render_gateway_modals' ) );
				add_action( 'admin_footer', array( $this, 'payment_tabs_script' ) );

				add_action( 'wp_ajax_mpcrbm_save_gateway_settings', array( $this, 'ajax_save_gateway_settings' ) );
				add_action( 'wp_ajax_mpcrbm_install_activate_wc', array( $this, 'ajax_install_activate_wc' ) );
				add_action( 'wp_ajax_mpcrbm_save_booking_mode', array( $this, 'ajax_save_booking_mode' ) );

				// "Payment Method" status card in the car add/edit screen sidebar, plus the
				// popup its links open — lets the admin flip Booking Mode and configure a
				// gateway without leaving the car they are editing.
				add_action( 'add_meta_boxes', array( $this, 'register_payment_sidebar_metabox' ) );
				add_action( 'edit_form_top', array( $this, 'render_payment_config_modal' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_edit_payment_assets' ) );

				// Gateway keys are managed by their own AJAX modals and never travel with
				// the settings form, so preserve them when the Settings API saves the rest.
				add_filter( 'pre_update_option_' . self::OPTION, array( $this, 'preserve_gateway_keys' ), 10, 2 );
			}

			/* --------------------------------------------------------------
			 * Screen detection / small helpers
			 * ------------------------------------------------------------ */

			/** Is this the car-rental global settings screen? */
			private function is_settings_screen() {
				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

				return $screen && strpos( $screen->id, 'mpcrbm_settings_page' ) !== false;
			}

			/** Is this the car (mpcrbm_rent) add/edit screen? */
			private function is_car_edit_screen() {
				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

				return $screen && 'post' === $screen->base
					&& class_exists( 'MPCRBM_Function' ) && $screen->post_type === MPCRBM_Function::get_cpt();
			}

			/**
			 * The gateway Configure modals and the `.gateway-card` styling are needed by
			 * the car edit screen's Payment Method popup too, not only by the Payments tab.
			 */
			private function is_settings_or_car_edit_screen() {
				return $this->is_settings_screen() || $this->is_car_edit_screen();
			}

			private function has_woo() {
				return MPCRBM_Global_Function::check_woocommerce() === 1;
			}

			private function is_pro() {
				return class_exists( 'MPCRBM_Plugin_Pro' );
			}

			private function opt( $key, $default = '' ) {
				$o = get_option( self::OPTION, array() );

				return isset( $o[ $key ] ) ? $o[ $key ] : $default;
			}

			private function settings_url() {
				$cpt = class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_cpt() : 'mpcrbm_rent';

				return admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_settings_page&mpcrbm_tab=payments' );
			}

			/** Human-readable label for the currently active booking mode. */
			private function get_booking_mode_label() {
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) ) {
					return __( 'Not set', 'car-rental-manager' );
				}
				$mode = MPCRBM_Booking_Mode::get_mode();
				if ( MPCRBM_Booking_Mode::WOOCOMMERCE === $mode ) {
					return __( 'WooCommerce', 'car-rental-manager' );
				}
				if ( MPCRBM_Booking_Mode::CUSTOM === $mode ) {
					return __( 'Custom Payment', 'car-rental-manager' );
				}

				return __( 'Not set', 'car-rental-manager' );
			}

			/**
			 * Names of the gateway(s) currently enabled for the active booking mode.
			 *
			 * @return string[]
			 */
			private function get_active_gateway_names() {
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) ) {
					return array();
				}
				$mode  = MPCRBM_Booking_Mode::get_mode();
				$names = array();

				if ( MPCRBM_Booking_Mode::WOOCOMMERCE === $mode ) {
					if ( class_exists( 'MPCRBM_WC_Payment_Manager' ) && function_exists( 'WC' ) ) {
						$names = MPCRBM_WC_Payment_Manager::instance()->get_enabled_gateway_titles();
					}

					return $names;
				}

				if ( MPCRBM_Booking_Mode::CUSTOM === $mode ) {
					$map = array(
						'mpcrbm_paypal_enable' => __( 'PayPal', 'car-rental-manager' ),
						'mpcrbm_stripe_enable' => __( 'Stripe', 'car-rental-manager' ),
					);
					foreach ( $map as $key => $label ) {
						if ( 'on' === $this->opt( $key ) ) {
							$names[] = $label;
						}
					}
					if ( class_exists( 'MPCRBM_Function' ) && MPCRBM_Function::offline_payment_enabled() ) {
						$names[] = __( 'Offline Payment', 'car-rental-manager' );
					}
				}

				return $names;
			}

			/* --------------------------------------------------------------
			 * Settings tab registration
			 * ------------------------------------------------------------ */

			/** Add the "Payments" + "Currency Settings" tabs to the settings navigation. */
			public function register_section( $sections ) {
				$sections[] = array(
					'id'    => self::OPTION,
					'icon'  => 'fas fa-credit-card',
					'title' => esc_html__( 'Payments', 'car-rental-manager' ),
				);
				// Currency formatting for the standalone / Custom Payment flow. In
				// WooCommerce mode WooCommerce's own currency settings apply instead, so
				// this only drives MPCRBM_Global_Function::native_format_amount().
				$sections[] = array(
					'id'    => self::CURRENCY_OPTION,
					'icon'  => 'fas fa-coins',
					'title' => esc_html__( 'Currency Settings', 'car-rental-manager' ),
				);

				return $sections;
			}

			/** Register the fields that make up the Payments + Currency tabs. */
			public function register_fields( $settings_fields ) {
				$settings_fields[ self::OPTION ] = array(
					array(
						'name'     => 'mpcrbm_booking_mode_selector',
						'label'    => '',
						'class'    => 'mpcrbm-fullrow mpcrbm-bm-row',
						'type'     => 'html',
						'callback' => array( $this, 'render_booking_mode_selector' ),
					),
					array(
						'name'     => 'mpcrbm_payment_woo_callout',
						'label'    => '',
						// The WooCommerce tab's own panel (install callout). Toggled by
						// updateSections() so it appears under the tabs in WooCommerce mode
						// and is hidden in Custom Payment mode.
						'class'    => 'mpcrbm-fullrow mpcrbm-woo-callout-row',
						'type'     => 'html',
						'callback' => array( $this, 'render_sub_tabs' ),
					),
					array(
						'name'     => 'mpcrbm_wc_payment_gateways_manager',
						'label'    => '',
						'class'    => 'mpcrbm-fullrow woocommerce-field wc-payment-methods-field',
						'type'     => 'html',
						'callback' => array( $this, 'render_wc_payment_manager' ),
					),
					array(
						'name'     => 'mpcrbm_wc_additional_heading',
						'label'    => '',
						'class'    => 'mpcrbm-fullrow woocommerce-field mpcrbm-acc-heading',
						'type'     => 'html',
						'callback' => array( $this, 'render_additional_settings_heading' ),
					),
					array(
						'name'    => 'mpcrbm_wc_add_to_cart_redirect',
						'label'   => __( 'After Adding to Cart, Redirect to', 'car-rental-manager' ),
						'desc'    => __( 'Select where to send the customer after a car is added to the cart.', 'car-rental-manager' ),
						'type'    => 'select',
						'default' => 'checkout',
						'options' => array(
							'cart'     => __( 'Cart', 'car-rental-manager' ),
							'checkout' => __( 'Checkout', 'car-rental-manager' ),
						),
						'class'   => 'woocommerce-field wc-additional-field',
					),
					array(
						'name'    => 'mpcrbm_wc_require_login',
						'label'   => __( 'Require Account Login', 'car-rental-manager' ),
						'desc'    => __( 'Require customers to log in before they can complete a booking.', 'car-rental-manager' ),
						'type'    => 'checkbox',
						'default' => '',
						'class'   => 'woocommerce-field wc-additional-field',
					),
					array(
						'name'    => 'mpcrbm_wc_show_billing_info',
						'label'   => __( 'Show Billing Info', 'car-rental-manager' ),
						'desc'    => __( 'Show billing fields on the WooCommerce checkout page.', 'car-rental-manager' ),
						'type'    => 'checkbox',
						'default' => '',
						'class'   => 'woocommerce-field wc-additional-field',
					),
					array(
						'name'    => 'mpcrbm_wc_confirm_status',
						'label'   => __( 'Confirm Booking Based on Payment Status', 'car-rental-manager' ),
						'desc'    => __( 'Order statuses that mark a booking as confirmed.', 'car-rental-manager' ),
						'type'    => 'multicheck',
						'default' => array( 'processing' => 'processing', 'completed' => 'completed' ),
						'options' => array(
							'pending'    => __( 'Pending payment', 'car-rental-manager' ),
							'processing' => __( 'Processing', 'car-rental-manager' ),
							'on-hold'    => __( 'On hold', 'car-rental-manager' ),
							'completed'  => __( 'Completed', 'car-rental-manager' ),
						),
						'class'   => 'woocommerce-field wc-additional-field',
					),
					array(
						'name'     => 'mpcrbm_payment_gateways_ui',
						'label'    => '',
						'class'    => 'mpcrbm-fullrow no-woocommerce-field payment-gateways-container',
						'type'     => 'html',
						'callback' => array( $this, 'render_gateway_cards' ),
					),
				);

				// Currency Settings tab — drives MPCRBM_Global_Function::native_format_amount()
				// for the standalone / Custom Payment flow (WooCommerce mode uses its own).
				$settings_fields[ self::CURRENCY_OPTION ] = array(
					array(
						'name'    => 'currency_code',
						'label'   => __( 'Currency Code', 'car-rental-manager' ),
						'desc'    => __( 'ISO code charged through PayPal/Stripe. Separate from the display symbol below — the gateways need a real code (a "$" alone is ambiguous between USD/CAD/AUD).', 'car-rental-manager' ),
						'type'    => 'select',
						'default' => 'USD',
						'options' => array(
							'USD' => 'USD - US Dollar',
							'EUR' => 'EUR - Euro',
							'GBP' => 'GBP - British Pound',
							'CAD' => 'CAD - Canadian Dollar',
							'AUD' => 'AUD - Australian Dollar',
							'NZD' => 'NZD - New Zealand Dollar',
							'JPY' => 'JPY - Japanese Yen',
							'CHF' => 'CHF - Swiss Franc',
							'SEK' => 'SEK - Swedish Krona',
							'NOK' => 'NOK - Norwegian Krone',
							'DKK' => 'DKK - Danish Krone',
							'PLN' => 'PLN - Polish Zloty',
							'CZK' => 'CZK - Czech Koruna',
							'HUF' => 'HUF - Hungarian Forint',
							'INR' => 'INR - Indian Rupee',
							'SGD' => 'SGD - Singapore Dollar',
							'HKD' => 'HKD - Hong Kong Dollar',
							'MYR' => 'MYR - Malaysian Ringgit',
							'PHP' => 'PHP - Philippine Peso',
							'THB' => 'THB - Thai Baht',
							'AED' => 'AED - UAE Dirham',
							'SAR' => 'SAR - Saudi Riyal',
							'ZAR' => 'ZAR - South African Rand',
							'MXN' => 'MXN - Mexican Peso',
							'BRL' => 'BRL - Brazilian Real',
							'BDT' => 'BDT - Bangladeshi Taka',
						),
					),
					array(
						'name'    => 'symbol',
						'label'   => __( 'Currency Symbol', 'car-rental-manager' ),
						'desc'    => __( 'Used to format every rental price while Booking Mode is Custom Payment.', 'car-rental-manager' ),
						'type'    => 'text',
						'default' => '$',
					),
					array(
						'name'    => 'position',
						'label'   => __( 'Currency Position', 'car-rental-manager' ),
						'type'    => 'select',
						'default' => 'left',
						'options' => array(
							'left'        => __( 'Left ($99.00)', 'car-rental-manager' ),
							'right'       => __( 'Right (99.00$)', 'car-rental-manager' ),
							'left_space'  => __( 'Left with space ($ 99.00)', 'car-rental-manager' ),
							'right_space' => __( 'Right with space (99.00 $)', 'car-rental-manager' ),
						),
					),
					array(
						'name'    => 'decimals',
						'label'   => __( 'Number of Decimals', 'car-rental-manager' ),
						'type'    => 'number',
						'min'     => 0,
						'max'     => 4,
						'default' => 2,
					),
					array(
						'name'    => 'decimal_separator',
						'label'   => __( 'Decimal Separator', 'car-rental-manager' ),
						'type'    => 'text',
						'default' => '.',
					),
					array(
						'name'    => 'thousand_separator',
						'label'   => __( 'Thousand Separator', 'car-rental-manager' ),
						'type'    => 'text',
						'default' => ',',
					),
				);

				return $settings_fields;
			}

			/* --------------------------------------------------------------
			 * Booking Mode selector
			 * ------------------------------------------------------------ */

			/**
			 * WooCommerce | Custom Payment segmented tab bar.
			 *
			 * The tab bar is ALWAYS shown so the admin can flip between the WooCommerce
			 * settings and the Custom Payment gateways in every state.
			 *
			 * When both flows are genuinely available it also persists the Booking Mode
			 * (the single switch deciding which flow takes bookings — see
			 * MPCRBM_Booking_Mode). When only one flow is available the mode is
			 * auto-resolved and can't be changed, so clicking a tab just reveals that
			 * section (a view switch), and a note explains the current state.
			 */
			public function render_booking_mode_selector() {
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) ) {
					return;
				}

				$availability = MPCRBM_Booking_Mode::availability();
				$can_switch   = ( 'both' === $availability );
				$needs_choice = $can_switch && MPCRBM_Booking_Mode::needs_selection();
				$wc_active    = $this->has_woo();

				// Which tab starts active. With a pending choice, leave neither marked so
				// the segmented control reads as "pick one"; otherwise use the resolved mode.
				$mode = MPCRBM_Booking_Mode::get_mode();
				if ( 'woocommerce' !== $mode && 'custom' !== $mode ) {
					$mode = $wc_active ? 'woocommerce' : 'custom';
				}
				$active      = $needs_choice ? '' : $mode;
				$is_wc       = ( 'woocommerce' === $active );
				$is_custom   = ( 'custom' === $active );
				$has_gateway = $can_switch ? MPCRBM_Booking_Mode::has_gateway_for_active_mode() : true;
				$nonce       = wp_create_nonce( 'mpcrbm_save_booking_mode' );

				// Contextual note under the tabs, per what's actually available.
				$note_class = '';
				$note_text  = '';
				if ( 'none' === $availability ) {
					$note_class = 'mpcrbm-bm-auto-note--warn';
					$note_text  = __( 'No booking flow is available yet. Use the WooCommerce tab to install/activate WooCommerce, or the Custom Payment tab to enable Offline Payment, to start taking bookings.', 'car-rental-manager' );
				} elseif ( 'woocommerce_only' === $availability ) {
					$note_text = __( 'WooCommerce is the only active flow right now, so it processes bookings automatically. Open the Custom Payment tab and enable Offline Payment (or the Pro PayPal / Stripe gateways) to unlock a real mode switch.', 'car-rental-manager' );
				} elseif ( 'custom_only' === $availability ) {
					$note_text = __( 'WooCommerce is not active, so Custom Payment is the live checkout flow. Open the Custom Payment tab to enable a gateway, or the WooCommerce tab to install & activate WooCommerce.', 'car-rental-manager' );
				}
				?>
				<div class="mpcrbm-bm-wrap" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-can-switch="<?php echo $can_switch ? '1' : '0'; ?>">
					<div class="mpcrbm-bm-head">
						<h3>
							<?php esc_html_e( 'Booking Mode', 'car-rental-manager' ); ?>
							<?php if ( $needs_choice ) : ?>
								<span class="mpcrbm-bm-required"><?php esc_html_e( 'Required', 'car-rental-manager' ); ?></span>
							<?php endif; ?>
						</h3>
						<p><?php esc_html_e( 'Switch between the WooCommerce and Custom Payment settings. When both flows are available this also sets which one processes bookings, so they never both handle the same booking.', 'car-rental-manager' ); ?></p>
					</div>

					<?php if ( $needs_choice ) : ?>
						<div class="mpcrbm-bm-nudge">
							<span class="dashicons dashicons-flag"></span>
							<?php esc_html_e( 'Please choose a booking mode to continue.', 'car-rental-manager' ); ?>
						</div>
					<?php endif; ?>

					<div class="mpcrbm-bm-cards">
						<label class="mpcrbm-bm-card<?php echo $is_wc ? ' is-selected' : ''; ?>" data-mode="woocommerce">
							<input type="radio" name="mpcrbm_booking_mode_radio" value="woocommerce" <?php checked( $is_wc ); ?>>
							<span class="mpcrbm-bm-card-icon dashicons dashicons-cart"></span>
							<span class="mpcrbm-bm-card-body">
								<span class="mpcrbm-bm-card-title-row">
									<strong><?php esc_html_e( 'WooCommerce', 'car-rental-manager' ); ?></strong>
								</span>
							</span>
						</label>
						<label class="mpcrbm-bm-card<?php echo $is_custom ? ' is-selected' : ''; ?>" data-mode="custom">
							<input type="radio" name="mpcrbm_booking_mode_radio" value="custom" <?php checked( $is_custom ); ?>>
							<span class="mpcrbm-bm-card-icon dashicons dashicons-money-alt"></span>
							<span class="mpcrbm-bm-card-body">
								<span class="mpcrbm-bm-card-title-row">
									<strong><?php esc_html_e( 'Custom Payment', 'car-rental-manager' ); ?></strong>
								</span>
							</span>
						</label>
					</div>

					<p class="mpcrbm-bm-status" role="status" aria-live="polite"></p>

					<?php if ( $note_text ) : ?>
						<div class="mpcrbm-bm-auto-note <?php echo esc_attr( $note_class ); ?>">
							<span class="dashicons <?php echo $note_class ? 'dashicons-warning' : 'dashicons-info-outline'; ?>"></span>
							<p><?php echo esc_html( $note_text ); ?></p>
						</div>
					<?php endif; ?>

					<div class="mpcrbm-bm-gateway-warning-slot">
						<?php if ( $can_switch && ! $needs_choice && ! $has_gateway ) : ?>
							<div class="mpcrbm-bm-gateway-warning">
								<span class="dashicons dashicons-warning"></span>
								<p>
									<?php if ( $is_wc ) : ?>
										<?php esc_html_e( 'WooCommerce mode is selected, but no WooCommerce payment gateway is enabled yet. Customers won\'t be able to complete a booking until you enable one below.', 'car-rental-manager' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Custom Payment mode is selected, but no gateway (PayPal, Stripe, or Offline) is enabled yet. Customers won\'t be able to complete a booking until you enable one below.', 'car-rental-manager' ); ?>
									<?php endif; ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php $this->booking_mode_styles(); ?>
				<script>
				jQuery( function ( $ ) {
					var $wrap = $( '.mpcrbm-bm-wrap' );
					if ( ! $wrap.length ) { return; }
					var nonce     = $wrap.data( 'nonce' );
					var canSwitch = String( $wrap.data( 'can-switch' ) ) === '1';
					var i18n  = {
						saving: <?php echo wp_json_encode( __( 'Saving…', 'car-rental-manager' ) ); ?>,
						saved:  <?php echo wp_json_encode( __( 'Booking mode saved.', 'car-rental-manager' ) ); ?>,
						error:  <?php echo wp_json_encode( __( 'Could not save. Please try again.', 'car-rental-manager' ) ); ?>,
						wcWarn: <?php echo wp_json_encode( __( 'WooCommerce mode is selected, but no WooCommerce payment gateway is enabled yet. Customers won\'t be able to complete a booking until you enable one below.', 'car-rental-manager' ) ); ?>,
						customWarn: <?php echo wp_json_encode( __( 'Custom Payment mode is selected, but no gateway (PayPal, Stripe, or Offline) is enabled yet. Customers won\'t be able to complete a booking until you enable one below.', 'car-rental-manager' ) ); ?>
					};

					$wrap.on( 'click', '.mpcrbm-bm-card', function () {
						var $card = $( this ), mode = $card.data( 'mode' );
						if ( $card.hasClass( 'is-selected' ) ) { return; }

						$wrap.find( '.mpcrbm-bm-card' ).removeClass( 'is-selected' );
						$card.addClass( 'is-selected' );
						$card.find( 'input[type=radio]' ).prop( 'checked', true );
						$wrap.find( '.mpcrbm-bm-nudge' ).hide();

						// Always reveal that tab's own section (view switch). The Payments
						// screen script and the car-edit popup both listen for this.
						$( document ).trigger( 'mpcrbm:mode-changed', [ mode ] );

						// Only persist a real mode change when both flows are available;
						// otherwise the mode is auto-resolved and the server rejects the save.
						if ( ! canSwitch ) { return; }

						var $status = $wrap.find( '.mpcrbm-bm-status' ).show().text( i18n.saving ).css( 'color', '#788291' );
						$.post( ajaxurl, {
							action: 'mpcrbm_save_booking_mode',
							nonce: nonce,
							mode: mode
						} ).done( function ( res ) {
							if ( res && res.success ) {
								$status.text( i18n.saved ).css( 'color', '#0a7c2f' );
								setTimeout( function () { $status.fadeOut( 400, function () { $( this ).text( '' ).show(); } ); }, 1800 );
								var $slot = $wrap.find( '.mpcrbm-bm-gateway-warning-slot' );
								$slot.empty();
								if ( res.data && res.data.has_gateway === false ) {
									var msg = ( mode === 'woocommerce' ) ? i18n.wcWarn : i18n.customWarn;
									$slot.append( '<div class="mpcrbm-bm-gateway-warning"><span class="dashicons dashicons-warning"></span><p>' + msg + '</p></div>' );
								}
							} else {
								$status.show().text( ( res && res.data ) ? res.data : i18n.error ).css( 'color', '#d63638' );
							}
						} ).fail( function () {
							$status.show().text( i18n.error ).css( 'color', '#d63638' );
						} );
					} );
				} );
				</script>
				<?php
			}

			/** Styles for the Booking Mode selector + its auto-detected notices. Printed once. */
			private function booking_mode_styles() {
				static $printed = false;
				if ( $printed ) {
					return;
				}
				$printed = true;
				?>
				<style>
				.mpcrbm-bm-wrap,
				.mpcrbm-bm-wrap *,
				.mpcrbm-bm-auto-note,
				.mpcrbm-bm-auto-note *{box-sizing:border-box;}
				.mpcrbm-bm-wrap{background:transparent;padding:0;margin:0 0 4px;max-width:100%;}
				.mpcrbm-bm-head h3{margin:0 0 4px;font-size:15px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);display:flex;align-items:center;gap:8px;}
				.mpcrbm-bm-required{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;}
				.mpcrbm-bm-head p{margin:0 0 14px;font-size:12.5px;color:var(--mpcrbm-shell-text-faded,#788291);max-width:640px;line-height:1.55;}
				.mpcrbm-bm-nudge{display:flex;align-items:center;gap:8px;background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;border-radius:var(--mpcrbm-shell-radius-xs,8px);padding:9px 13px;font-size:12.5px;font-weight:600;margin-bottom:12px;}
				/* WooCommerce | Custom Payment segmented tab bar. */
				.mpcrbm-bm-cards{display:inline-flex;gap:4px;padding:4px;margin:0;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius-sm,12px);background:var(--mpcrbm-shell-bg,#f5f6fa);max-width:100%;box-sizing:border-box;}
				.mpcrbm-bm-card{position:relative;display:flex !important;align-items:center;gap:9px;padding:9px 18px;border:none;border-radius:var(--mpcrbm-shell-radius-xs,8px);background:transparent;cursor:pointer;transition:background .15s,color .15s,box-shadow .15s;min-width:0;}
				.mpcrbm-bm-card:hover{background:rgba(255,255,255,.75);}
				.mpcrbm-bm-card.is-selected{background:var(--mpcrbm-shell-primary,#667eea);box-shadow:0 4px 12px rgba(102,126,234,.30);}
				.mpcrbm-bm-card input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
				.mpcrbm-bm-card-icon{flex:0 0 auto;width:24px;height:24px;border-radius:7px;background:rgba(102,126,234,.12);color:var(--mpcrbm-shell-primary,#667eea);display:flex !important;align-items:center !important;justify-content:center !important;font-size:13px;box-sizing:border-box;padding:5px;transition:background .15s,color .15s;}
				.mpcrbm-bm-card.is-selected .mpcrbm-bm-card-icon{background:rgba(255,255,255,.24);color:#fff;}
				.mpcrbm-bm-card-body{display:inline-flex !important;align-items:center;flex:0 0 auto;min-width:0;white-space:nowrap !important;}
				.mpcrbm-bm-card-title-row{display:inline-flex !important;align-items:center;gap:8px;margin:0 !important;width:auto;white-space:nowrap !important;}
				.mpcrbm-bm-card-body strong{display:inline-block !important;font-size:13px;font-weight:600;line-height:1.3;color:#39445A;white-space:nowrap !important;}
				.mpcrbm-bm-card.is-selected .mpcrbm-bm-card-body strong{color:#fff;}
				.mpcrbm-bm-status{min-height:16px;margin:8px 2px 0;font-size:12px;font-weight:600;}
				.mpcrbm-bm-gateway-warning{display:flex;align-items:flex-start;gap:9px;margin-top:12px;padding:11px 14px;border-radius:var(--mpcrbm-shell-radius-xs,8px);background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12.5px;line-height:1.55;}
				.mpcrbm-bm-gateway-warning p{margin:0;}
				.mpcrbm-bm-auto-note{display:flex;align-items:center;gap:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#14532d;border-radius:var(--mpcrbm-shell-radius-sm,12px);padding:14px 16px;margin:12px 0 0;font-size:12.5px;line-height:1.55;}
				.mpcrbm-bm-auto-note .dashicons{flex:0 0 auto;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;border-radius:9px;background:#dcfce7;color:#16a34a;}
				.mpcrbm-bm-auto-note p{margin:0;font-weight:500;}
				.mpcrbm-bm-auto-note--warn{background:#fff5f5;border-color:#fbcfcf;color:#8a1c1c;}
				.mpcrbm-bm-auto-note--warn .dashicons{background:#fee2e2;color:#dc2626;}
				@media (max-width:680px){.mpcrbm-bm-cards{display:flex;width:100%;}.mpcrbm-bm-card{flex:1;justify-content:center;}}
				</style>
				<?php
			}

			/* --------------------------------------------------------------
			 * WooCommerce section panels
			 * ------------------------------------------------------------ */

			/**
			 * The WooCommerce tab's own panel: a callout offering install/activate while
			 * WooCommerce is inactive. (There is deliberately no second sub-tab bar here —
			 * the Booking Mode cards above are the single switch, and two controls for one
			 * decision could disagree.)
			 */
			public function render_sub_tabs() {
				$wc_active    = $this->has_woo();
				$is_installed = file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
				$btn_text     = $is_installed
					? __( 'Activate WooCommerce Now', 'car-rental-manager' )
					: __( 'Install &amp; Activate Now', 'car-rental-manager' );
				?>
				<div class="payment-sub-tabs-wrapper">
					<?php if ( ! $wc_active ) : ?>
						<div class="woocommerce-field">
							<div class="mpcrbm-wc-callout">
								<div class="mpcrbm-wc-callout-head">
									<span class="mpcrbm-wc-callout-icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
									</span>
									<h4 class="mpcrbm-wc-callout-title"><?php esc_html_e( 'WooCommerce is not activated', 'car-rental-manager' ); ?></h4>
								</div>
								<p class="mpcrbm-wc-callout-text"><?php esc_html_e( 'To take bookings through the WooCommerce cart & checkout flow, install and activate WooCommerce. Prefer not to use it? Choose Custom Payment (Standalone) as your Booking Mode above.', 'car-rental-manager' ); ?></p>
								<button type="button" class="mpcrbm-install-wc-trigger mpcrbm-wc-callout-btn">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v11"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
									<?php echo wp_kses_post( $btn_text ); ?>
								</button>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}

			/**
			 * Collapsible header for the WooCommerce "Additional Settings" group. The
			 * fields it controls are ordinary grid cells tagged `wc-additional-field`;
			 * payment_tabs_script() wires the toggle.
			 */
			public function render_additional_settings_heading() {
				?>
				<div class="mpcrbm-acc-bar" role="button" tabindex="0" aria-expanded="false">
					<span class="mpcrbm-acc-title"><?php esc_html_e( 'Additional Settings', 'car-rental-manager' ); ?></span>
					<span class="mpcrbm-acc-arrow dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<?php
			}

			/** WooCommerce native payment-methods manager. */
			public function render_wc_payment_manager() {
				if ( class_exists( 'WooCommerce' ) && class_exists( 'MPCRBM_WC_Payment_Manager' ) ) {
					MPCRBM_WC_Payment_Manager::instance()->render();
				}
			}

			/* --------------------------------------------------------------
			 * Custom Payment gateway cards
			 * ------------------------------------------------------------ */

			/** PayPal / Stripe / Offline gateway cards + confirmation page + login rule. */
			public function render_gateway_cards() {
				$this->render_gateway_cards_list();

				$is_pro    = $this->is_pro();
				$conf_page = absint( $this->opt( 'mpcrbm_confirmation_page_id', 0 ) );
				$pro_badge = '<span class="mpcrbm-gw-pro-badge" title="' . esc_attr__( 'Available in Pro version', 'car-rental-manager' ) . '">PRO</span>';
				?>
				<!-- Booking Confirmation Page -->
				<div class="mpcrbm-conf-page">
					<div class="mpcrbm-conf-page-label">
						<label><?php esc_html_e( 'Booking Confirmation Page', 'car-rental-manager' ); ?></label>
						<span><?php esc_html_e( 'In Standalone / Custom Payment mode, customers are shown a confirmation after booking. Optionally choose a dedicated page here.', 'car-rental-manager' ); ?></span>
					</div>
					<div class="mpcrbm-conf-page-field">
						<?php
							wp_dropdown_pages( array(
								'name'              => self::OPTION . '[mpcrbm_confirmation_page_id]',
								'id'                => 'mpcrbm_confirmation_page_id',
								'selected'          => $conf_page,
								'show_option_none'  => __( '— Default —', 'car-rental-manager' ),
								'option_none_value' => '0',
							) );
						?>
					</div>
				</div>

				<!-- Require customer login (Pro custom booking flow + portal) -->
				<?php $require_login = $this->opt( 'mpcrbm_require_login', 'no' ); ?>
				<div class="mpcrbm-conf-page">
					<div class="mpcrbm-conf-page-label">
						<label><?php esc_html_e( 'Require Customer Login', 'car-rental-manager' ); ?></label>
						<span><?php esc_html_e( 'When enabled, customers must log in (or register) before they can complete a Custom Payment booking or view the My Bookings portal. When disabled, guests can book and track by email + reference.', 'car-rental-manager' ); ?></span>
					</div>
					<div class="mpcrbm-conf-page-field">
						<?php if ( $is_pro ) : ?>
							<select name="<?php echo esc_attr( self::OPTION ); ?>[mpcrbm_require_login]">
								<option value="yes" <?php selected( $require_login, 'yes' ); ?>><?php esc_html_e( 'Yes — require login / registration', 'car-rental-manager' ); ?></option>
								<option value="no" <?php selected( $require_login, 'no' ); ?>><?php esc_html_e( 'No — allow guest checkout', 'car-rental-manager' ); ?></option>
							</select>
						<?php else : ?>
							<?php echo wp_kses_post( $pro_badge ); ?>
						<?php endif; ?>
					</div>
				</div>
				<?php
			}

			/**
			 * The PayPal / Stripe / Offline gateway cards on their own, without the
			 * Booking Confirmation Page / Require Customer Login controls below them
			 * (those are classic Settings-API fields tied to the real Payments form and
			 * don't save outside it). Shared by the real settings page and the car edit
			 * screen's "Configure payment method" popup.
			 */
			public function render_gateway_cards_list() {
				$is_pro      = $this->is_pro();
				$pp_enabled  = 'on' === $this->opt( 'mpcrbm_paypal_enable' );
				$st_enabled  = 'on' === $this->opt( 'mpcrbm_stripe_enable' );
				$off_enabled = class_exists( 'MPCRBM_Function' ) && MPCRBM_Function::offline_payment_enabled();

				$enabled_txt  = __( 'Enabled', 'car-rental-manager' );
				$disabled_txt = __( 'Disabled', 'car-rental-manager' );
				$pro_badge    = '<span class="mpcrbm-gw-pro-badge" title="' . esc_attr__( 'Available in Pro version', 'car-rental-manager' ) . '">PRO</span>';
				?>
				<div class="mpcrbm-gw-intro">
					<h3><?php esc_html_e( 'Custom Payment Gateways', 'car-rental-manager' ); ?></h3>
					<p><?php esc_html_e( 'Accept payments directly without WooCommerce. Configure a gateway below, then enable it for the Standalone / Custom Payment checkout.', 'car-rental-manager' ); ?></p>
				</div>

				<!-- PayPal Card -->
				<div class="gateway-card paypal-card">
					<div class="gateway-header">
						<div class="gateway-id">
							<span class="gateway-icon">
								<svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106z" fill="#fff"/>
								</svg>
							</span>
							<span class="gateway-meta">
								<span class="gateway-name"><?php esc_html_e( 'PayPal', 'car-rental-manager' ); ?></span>
								<span class="gateway-sub"><?php esc_html_e( 'Cards & PayPal balance', 'car-rental-manager' ); ?></span>
							</span>
						</div>
						<?php if ( $is_pro ) : ?>
							<span class="gateway-status <?php echo $pp_enabled ? 'active' : ''; ?>"><?php echo esc_html( $pp_enabled ? $enabled_txt : $disabled_txt ); ?></span>
						<?php endif; ?>
						<div class="gateway-actions">
							<?php if ( $is_pro ) : ?>
								<button type="button" class="gateway-configure-btn" id="mpcrbm-paypal-configure-btn"><?php esc_html_e( 'Configure', 'car-rental-manager' ); ?></button>
							<?php else : ?>
								<?php echo wp_kses_post( $pro_badge ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Stripe Card -->
				<div class="gateway-card stripe-card">
					<div class="gateway-header">
						<div class="gateway-id">
							<span class="gateway-icon">
								<svg width="26" height="26" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
									<path fill="#fff" d="M14.07 15.11c-1.85-.43-2.61-.79-2.61-1.63 0-.79.75-1.33 1.95-1.33 1.34 0 2.87.41 4.31 1.09V8.65c-1.39-.56-2.93-.84-4.52-.84-3.8 0-6.66 1.96-6.66 5.25 0 3.73 3.32 4.96 6.03 5.61 2.05.49 2.8.92 2.8 1.8 0 .86-.87 1.48-2.3 1.48-1.57 0-3.37-.53-5.06-1.54v4.75c1.67.75 3.59 1.13 5.51 1.13 4.13 0 7-2 7-5.34-.01-3.6-3.6-4.41-6.45-5.84z"/>
								</svg>
							</span>
							<span class="gateway-meta">
								<span class="gateway-name"><?php esc_html_e( 'Stripe', 'car-rental-manager' ); ?></span>
								<span class="gateway-sub"><?php esc_html_e( 'Credit & debit cards', 'car-rental-manager' ); ?></span>
							</span>
						</div>
						<?php if ( $is_pro ) : ?>
							<span class="gateway-status <?php echo $st_enabled ? 'active' : ''; ?>"><?php echo esc_html( $st_enabled ? $enabled_txt : $disabled_txt ); ?></span>
						<?php endif; ?>
						<div class="gateway-actions">
							<?php if ( $is_pro ) : ?>
								<button type="button" class="gateway-configure-btn" id="mpcrbm-stripe-configure-btn"><?php esc_html_e( 'Configure', 'car-rental-manager' ); ?></button>
							<?php else : ?>
								<?php echo wp_kses_post( $pro_badge ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Offline Payment Card -->
				<div class="gateway-card offline-card">
					<div class="gateway-header">
						<div class="gateway-id">
							<span class="gateway-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M3 19h18a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/>
									<path d="M2 10h20M6 14h4" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
								</svg>
							</span>
							<span class="gateway-meta">
								<span class="gateway-name"><?php esc_html_e( 'Offline Payment', 'car-rental-manager' ); ?></span>
								<span class="gateway-sub"><?php esc_html_e( 'Bank transfer, cash, pay on pickup', 'car-rental-manager' ); ?></span>
							</span>
						</div>
						<!-- Offline needs no online processor, so it is available in the free plugin. -->
						<span class="gateway-status <?php echo $off_enabled ? 'active' : ''; ?>"><?php echo esc_html( $off_enabled ? $enabled_txt : $disabled_txt ); ?></span>
						<div class="gateway-actions">
							<button type="button" class="gateway-configure-btn" id="mpcrbm-offline-configure-btn"><?php esc_html_e( 'Configure', 'car-rental-manager' ); ?></button>
						</div>
					</div>
				</div>
				<?php
			}

			/* --------------------------------------------------------------
			 * Car edit screen: Payment Method sidebar card + popup
			 * ------------------------------------------------------------ */

			/** Compact "Payment Method" card in the car add/edit screen sidebar. */
			public function register_payment_sidebar_metabox() {
				if ( ! class_exists( 'MPCRBM_Function' ) ) {
					return;
				}
				add_meta_box(
					'mpcrbm_payment_method_box',
					esc_html__( 'Payment Method', 'car-rental-manager' ),
					array( $this, 'render_payment_sidebar_card' ),
					MPCRBM_Function::get_cpt(),
					'side',
					'default'
				);
			}

			/** Shows the live booking mode + enabled gateway(s), and links out to configure them. */
			public function render_payment_sidebar_card() {
				$mode_label    = $this->get_booking_mode_label();
				$gateway_names = $this->get_active_gateway_names();
				$has_gateway   = class_exists( 'MPCRBM_Booking_Mode' ) ? MPCRBM_Booking_Mode::has_gateway_for_active_mode() : false;
				?>
				<div class="mpcrbm_payment_method_card">
					<div class="mpcrbm_payment_info_row">
						<span><?php esc_html_e( 'Active Method', 'car-rental-manager' ); ?></span>
						<strong><?php echo esc_html( $mode_label ); ?></strong>
					</div>
					<div class="mpcrbm_payment_info_row">
						<span><?php esc_html_e( 'Active Gateway', 'car-rental-manager' ); ?></span>
						<strong><?php echo esc_html( $gateway_names ? implode( ', ', $gateway_names ) : __( 'None', 'car-rental-manager' ) ); ?></strong>
					</div>

					<?php if ( $gateway_names ) : ?>
						<p class="mpcrbm_payment_link">
							<a href="#" data-mpcrbm-payment-modal-open><?php esc_html_e( 'Payment Settings', 'car-rental-manager' ); ?></a>
						</p>
					<?php endif; ?>

					<?php if ( ! $has_gateway ) : ?>
						<p class="mpcrbm_payment_warning">
							<a href="#" data-mpcrbm-payment-modal-open><?php esc_html_e( 'Configure payment method', 'car-rental-manager' ); ?></a>
						</p>
					<?php endif; ?>
				</div>
				<style>
				.mpcrbm_payment_method_card .mpcrbm_payment_info_row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 0;font-size:12.5px;border-bottom:1px solid #f0f0f1;}
				.mpcrbm_payment_method_card .mpcrbm_payment_info_row:last-of-type{border-bottom:none;}
				.mpcrbm_payment_method_card .mpcrbm_payment_info_row span{color:var(--mpcrbm-shell-text-faded,#788291);}
				.mpcrbm_payment_method_card .mpcrbm_payment_info_row strong{color:var(--mpcrbm-shell-text,#1f222b);text-align:right;}
				.mpcrbm_payment_method_card .mpcrbm_payment_link{margin:12px 0 0;font-size:12.5px;}
				.mpcrbm_payment_method_card .mpcrbm_payment_link a{color:var(--mpcrbm-shell-primary,#667eea);font-weight:600;text-decoration:none;}
				.mpcrbm_payment_method_card .mpcrbm_payment_link a:hover{text-decoration:underline;}
				.mpcrbm_payment_method_card .mpcrbm_payment_warning{margin:10px 0 0;font-size:12.5px;}
				.mpcrbm_payment_method_card .mpcrbm_payment_warning a{color:#b42318;font-weight:600;text-decoration:underline;}
				</style>
				<?php
			}

			/**
			 * Popup opened by the sidebar card's links — lets the admin flip the Booking
			 * Mode and enable/configure a gateway without leaving the car edit screen.
			 * Reuses the exact same self-contained, AJAX-saving pieces the Payments tab
			 * uses (Booking Mode selector, WooCommerce Payment Methods manager, Custom
			 * Payment gateway cards + their Configure modals) — nothing is re-implemented.
			 *
			 * Deliberately leaves out the classic Settings-API-only controls (Booking
			 * Confirmation Page, Require Customer Login, WooCommerce Additional Settings,
			 * Currency tab) since those only save via the real settings form's submit — the
			 * popup links out to the full Payments tab for those instead.
			 */
			public function render_payment_config_modal() {
				if ( ! $this->is_car_edit_screen() ) {
					return;
				}
				$mode          = class_exists( 'MPCRBM_Booking_Mode' ) ? MPCRBM_Booking_Mode::get_mode() : '';
				$is_wc         = ( class_exists( 'MPCRBM_Booking_Mode' ) && MPCRBM_Booking_Mode::WOOCOMMERCE === $mode );
				$wc_downloaded = file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
				$wc_cta_label  = $wc_downloaded
					? __( 'Activate WooCommerce', 'car-rental-manager' )
					: __( 'Install & Activate WooCommerce', 'car-rental-manager' );
				$needs_notice  = class_exists( 'MPCRBM_Booking_Mode' ) ? ! MPCRBM_Booking_Mode::has_gateway_for_active_mode() : false;
				?>
				<?php if ( $needs_notice ) : ?>
					<div class="mpcrbm-edit-payment-notice" id="mpcrbm-edit-payment-notice">
						<?php esc_html_e( 'No payment method is currently configured.', 'car-rental-manager' ); ?>
						<a href="#" class="mpcrbm-edit-payment-notice-link" data-mpcrbm-payment-modal-open>
							<?php esc_html_e( 'Please configure a payment method to accept bookings.', 'car-rental-manager' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<div class="mpcrbm-payment-modal" id="mpcrbm-payment-modal" style="display:none;">
					<div class="mpcrbm-payment-modal-box">
						<div class="mpcrbm-payment-modal-head">
							<h2><?php esc_html_e( 'Payment Method', 'car-rental-manager' ); ?></h2>
							<button type="button" class="mpcrbm-payment-modal-close" aria-label="<?php esc_attr_e( 'Close', 'car-rental-manager' ); ?>">&times;</button>
						</div>
						<div class="mpcrbm-payment-modal-body">
							<?php $this->render_booking_mode_selector(); ?>

							<div class="mpcrbm-payment-modal-section" data-mode-section="woocommerce"<?php echo $is_wc ? '' : ' style="display:none;"'; ?>>
								<?php if ( ! $this->has_woo() ) : ?>
									<div class="mpcrbm-payment-modal-wc-cta">
										<div id="mpcrbm-payment-wc-info">
											<button type="button" id="mpcrbm-payment-wc-install-btn" class="button button-primary">
												<?php echo esc_html( $wc_cta_label ); ?>
											</button>
										</div>
										<div id="mpcrbm-payment-wc-progress" style="display:none;">
											<div class="mpcrbm-payment-wc-progress-track">
												<div id="mpcrbm-payment-wc-progress-fill" class="mpcrbm-payment-wc-progress-fill"></div>
											</div>
											<p id="mpcrbm-payment-wc-progress-status" class="mpcrbm-payment-wc-progress-status"></p>
										</div>
									</div>
									<?php
									$this->wc_install_progress_script(
										'mpcrbm-payment-wc-install-btn',
										'mpcrbm-payment-wc-info',
										'mpcrbm-payment-wc-progress',
										'mpcrbm-payment-wc-progress-fill',
										'mpcrbm-payment-wc-progress-status',
										$wc_downloaded,
										true
									);
									?>
								<?php endif; ?>
								<?php $this->render_wc_payment_manager(); ?>
							</div>
							<div class="mpcrbm-payment-modal-section" data-mode-section="custom"<?php echo $is_wc ? ' style="display:none;"' : ''; ?>>
								<?php $this->render_gateway_cards_list(); ?>
							</div>

							<p class="mpcrbm-payment-modal-more">
								<a href="<?php echo esc_url( $this->settings_url() ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'More payment settings (confirmation page, checkout options, currency)', 'car-rental-manager' ); ?>
								</a>
							</p>
						</div>
					</div>
				</div>
				<style>
				.mpcrbm-edit-payment-notice{width:100%;box-sizing:border-box;text-align:center;background:#eef1fe;color:#39445A;font-size:13px;font-weight:600;padding:10px 26px;margin:12px 0 0;border-radius:var(--mpcrbm-shell-radius-xs,8px);}
				.mpcrbm-edit-payment-notice-link{color:#b42318;text-decoration:underline;margin-left:4px;cursor:pointer;}
				.mpcrbm-edit-payment-notice-link:hover{color:#8a1c1c;}
				.mpcrbm-payment-modal{position:fixed;inset:0;background:rgba(31,34,43,.55);z-index:100001;align-items:center;justify-content:center;padding:20px;}
				.mpcrbm-payment-modal-box{background:#fff;border-radius:var(--mpcrbm-shell-radius,16px);max-width:820px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 24px 60px rgba(31,34,43,.32);}
				.mpcrbm-payment-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--mpcrbm-shell-border,#e7e7ea);position:sticky;top:0;background:#fff;z-index:1;}
				.mpcrbm-payment-modal-head h2{margin:0;font-size:17px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);}
				.mpcrbm-payment-modal-close{border:none;background:transparent;font-size:22px;line-height:1;cursor:pointer;color:#788291;padding:4px 8px;}
				.mpcrbm-payment-modal-close:hover{color:#1f222b;}
				.mpcrbm-payment-modal-body{padding:20px 24px 28px;}
				.mpcrbm-payment-modal-wc-cta{margin-bottom:16px;text-align:left;}
				.mpcrbm-payment-modal-wc-cta #mpcrbm-payment-wc-install-btn.button.button-primary{border-radius:var(--mpcrbm-shell-radius-xs,8px);}
				.mpcrbm-payment-wc-progress-track{width:100%;height:8px;background:#f0f0f1;border-radius:100px;overflow:hidden;margin-bottom:10px;}
				.mpcrbm-payment-wc-progress-fill{height:100%;width:0%;border-radius:100px;background:linear-gradient(90deg,#667eea,#5568d3);transition:width .5s cubic-bezier(.16,1,.3,1);}
				.mpcrbm-payment-wc-progress-status{font-size:13px;color:#788291;margin:0;min-height:20px;}
				.mpcrbm-payment-modal-section{margin-top:16px;}
				.mpcrbm-payment-modal-more{margin:18px 0 0;padding-top:14px;border-top:1px solid var(--mpcrbm-shell-border,#e7e7ea);font-size:12.5px;}
				.mpcrbm-payment-modal-more a{color:var(--mpcrbm-shell-primary,#667eea);font-weight:600;text-decoration:none;}
				.mpcrbm-payment-modal-more a:hover{text-decoration:underline;}
				</style>
				<script>
				jQuery(function($){
					var $modal = $('#mpcrbm-payment-modal');
					if (!$modal.length) { return; }
					$modal.appendTo('body');

					// The Booking Mode selector's own "WooCommerce is not active, so…" note
					// is only relevant next to the WooCommerce section/CTA — hide it while
					// Custom Payment is showing (kept in sync on every mode switch below).
					$modal.find('.mpcrbm-bm-auto-note').toggle(<?php echo $is_wc ? 'true' : 'false'; ?>);

					$(document).on('click', '[data-mpcrbm-payment-modal-open]', function(e){
						e.preventDefault();
						$modal.css('display', 'flex');
					});
					$(document).on('click', '.mpcrbm-payment-modal-close', function(){
						$modal.hide();
					});
					$modal.on('click', function(e){
						if (e.target === this) { $modal.hide(); }
					});
					$(document).on('keydown', function(e){
						if ((e.key === 'Escape' || e.keyCode === 27) && $modal.is(':visible')) {
							$modal.hide();
						}
					});

					// Keep the popup's WooCommerce/Custom Payment section in sync with the
					// Booking Mode cards above it. That selector's own script triggers this
					// event on every click, even when the mode can't actually be changed.
					$(document).on('mpcrbm:mode-changed', function(e, mode){
						$modal.find('[data-mode-section="woocommerce"]').toggle(mode === 'woocommerce');
						$modal.find('[data-mode-section="custom"]').toggle(mode === 'custom');
						$modal.find('.mpcrbm-bm-auto-note').toggle(mode === 'woocommerce');
					});

					// Reopen on the WooCommerce section right after the reload that follows a
					// successful install/activate — picks up where the admin left off instead
					// of landing them back on a closed modal. Clicking the real card (rather
					// than only firing the view-switch event) also persists WooCommerce as the
					// Booking Mode now that it is actually available, which is the whole point
					// of the flow they were in the middle of.
					try {
						if (sessionStorage.getItem('mpcrbmReopenPaymentModal')) {
							sessionStorage.removeItem('mpcrbmReopenPaymentModal');
							$modal.css('display', 'flex');
							$modal.find('.mpcrbm-bm-card[data-mode="woocommerce"]').trigger('click');
						}
					} catch (err) {}
				});
				</script>
				<?php
			}

			/** WooCommerce admin assets the popup's native gateway forms benefit from. */
			public function maybe_enqueue_edit_payment_assets( $hook ) {
				if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
					return;
				}
				if ( ! $this->is_car_edit_screen() ) {
					return;
				}
				if ( $this->has_woo() ) {
					wp_enqueue_style( 'woocommerce_admin_styles' );
					wp_enqueue_script( 'wc-enhanced-select' );
					wp_enqueue_script( 'wc-jquery-tiptip' );
				}
			}

			/* --------------------------------------------------------------
			 * WooCommerce install / activate
			 * ------------------------------------------------------------ */

			/**
			 * Self-contained "install → activate" progress-bar script, parameterized by
			 * this instance's own element ids so it can be embedded in more than one place
			 * (the Payments tab's install modal AND the car edit screen's Payment Method
			 * popup) without either depending on the other's script having already run.
			 *
			 * @param bool $reopen_popup_on_reload Only true for the Payment Method popup's
			 *             instance — sets a sessionStorage flag (consumed by
			 *             render_payment_config_modal()'s script) so the popup reopens on
			 *             the WooCommerce section after the reload. Must stay false for the
			 *             settings page's instance: sessionStorage persists for the whole
			 *             tab, so setting it there too would leave a stale flag that wrongly
			 *             reopens the popup the next time the admin opens a car in that tab.
			 */
			private function wc_install_progress_script( $btn_id, $info_id, $progress_id, $fill_id, $status_id, $is_installed, $reopen_popup_on_reload = false ) {
				?>
				<script>
				jQuery(function($){
					var $btn = $('#<?php echo esc_js( $btn_id ); ?>');
					if (!$btn.length) { return; }
					var $info     = $('#<?php echo esc_js( $info_id ); ?>');
					var $progress = $('#<?php echo esc_js( $progress_id ); ?>');
					var $fill     = $('#<?php echo esc_js( $fill_id ); ?>');
					var $status   = $('#<?php echo esc_js( $status_id ); ?>');
					var isInstalled = <?php echo $is_installed ? 'true' : 'false'; ?>;
					var nonce       = '<?php echo esc_js( wp_create_nonce( 'mpcrbm_install_wc' ) ); ?>';
					var i18n = {
						downloading: <?php echo wp_json_encode( __( 'Downloading & installing WooCommerce…', 'car-rental-manager' ) ); ?>,
						activating:  <?php echo wp_json_encode( __( 'Activating WooCommerce…', 'car-rental-manager' ) ); ?>,
						done:        <?php echo wp_json_encode( __( 'Successfully activated! 100%', 'car-rental-manager' ) ); ?>,
						timeout:     <?php echo wp_json_encode( __( 'This is taking longer than expected — your connection or server may be slow. Please wait, or try again in a moment.', 'car-rental-manager' ) ); ?>,
						networkErr:  <?php echo wp_json_encode( __( 'A network error occurred. Please try again.', 'car-rental-manager' ) ); ?>,
						errorPrefix: <?php echo wp_json_encode( __( 'Error: ', 'car-rental-manager' ) ); ?>
					};

					// Fallback shown only once the automatic install/activate genuinely fails
					// — a plain link to WordPress's own Add Plugins screen, pre-filled for
					// WooCommerce, so the admin can install it by hand without needing to
					// know the plugin slug or where to look.
					var manualInstallUrl = <?php echo wp_json_encode( admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) ); ?>;
					var $manualBtn = $('<a/>', {
						href: manualInstallUrl,
						target: '_blank',
						rel: 'noopener noreferrer',
						text: <?php echo wp_json_encode( __( 'Install WooCommerce Manually', 'car-rental-manager' ) ); ?>
					}).addClass('button').css({ display: 'none', 'margin-left': '10px' });
					$info.append($manualBtn);

					$btn.on('click', function(){
						$manualBtn.hide();
						$info.hide(); $fill.css('width','0%'); $progress.show();

						// Two real, separately-timed requests when a full install is needed
						// (download+unpack, then activate) instead of one long blocking call —
						// each request is shorter (less likely to hit a host's
						// max_execution_time on a slow connection) and the bar reflects
						// genuine per-step completion rather than a guessed animation.
						var stages = isInstalled
							? [ { step: 'activate', label: i18n.activating, from: 10, to: 100 } ]
							: [
								{ step: 'install',  label: i18n.downloading, from: 5,  to: 55 },
								{ step: 'activate', label: i18n.activating,  from: 55, to: 100 }
							];
						var stageIndex = 0, crawlId = null;

						function stopCrawl(){
							if (crawlId) { clearInterval(crawlId); crawlId = null; }
						}

						// Eases the bar from the stage's floor toward (but never reaching) its
						// ceiling while the real request is still in flight — signals "still
						// working" without ever claiming a step is done before its response
						// actually arrives.
						function crawlWithinStage(stage){
							var start = Date.now();
							$fill.css('width', stage.from + '%');
							$status.text(stage.label + ' ' + stage.from + '%');
							crawlId = setInterval(function(){
								var elapsed = Date.now() - start;
								var eased = stage.from + (stage.to - stage.from) * (1 - Math.exp(-elapsed / 4000));
								var pct = Math.min(eased, stage.to - 2);
								$fill.css('width', pct + '%');
								$status.text(stage.label + ' ' + Math.round(pct) + '%');
							}, 200);
						}

						function runStage(){
							var stage = stages[stageIndex];
							crawlWithinStage(stage);

							$.ajax({
								url: ajaxurl, type: 'POST', timeout: 120000,
								data: { action: 'mpcrbm_install_activate_wc', nonce: nonce, step: stage.step }
							}).done(function(response){
								stopCrawl();
								if (!response || !response.success) {
									$fill.css('width','100%');
									$status.css('color','#d92d20').text(i18n.errorPrefix + ((response && response.data) || 'Unknown error'));
									$manualBtn.show();
									setTimeout(function(){ $progress.hide(); $info.show(); }, 5000);
									return;
								}

								$fill.css('width', stage.to + '%');
								$status.css('color','').text(stage.label + ' ' + stage.to + '%');

								stageIndex++;
								if (stageIndex < stages.length) {
									setTimeout(runStage, 200);
									return;
								}
								$status.css('color','#039855').text(i18n.done);
								<?php if ( $reopen_popup_on_reload ) : ?>
								try { sessionStorage.setItem( 'mpcrbmReopenPaymentModal', '1' ); } catch ( err ) {}
								<?php endif; ?>
								setTimeout(function(){ location.reload(); }, 1000);
							}).fail(function(jqXHR, textStatus){
								stopCrawl();
								$fill.css('width','100%');
								$status.css('color','#d92d20').text(textStatus === 'timeout' ? i18n.timeout : i18n.networkErr);
								$manualBtn.show();
								setTimeout(function(){ $progress.hide(); $info.show(); }, 6000);
							});
						}

						runStage();
					});
				});
				</script>
				<?php
			}

			/**
			 * WooCommerce install/activate modal (footer) — used by the Payments tab's
			 * WooCommerce callout only. The car edit screen's Payment Method popup has its
			 * own inline install UI using the same shared progress script, rather than
			 * opening this modal on top of itself.
			 */
			public function render_wc_warning_modal() {
				if ( ! $this->is_settings_screen() || $this->has_woo() ) {
					return;
				}
				$is_installed = file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
				$modal_desc   = $is_installed
					? __( 'WooCommerce is already installed but not active. Click the button below to activate it now.', 'car-rental-manager' )
					: __( 'WooCommerce is required to process payments through the cart/checkout flow. We will securely download, install, and activate it for you now.', 'car-rental-manager' );
				$modal_btn    = $is_installed
					? __( 'Activate WooCommerce Now', 'car-rental-manager' )
					: __( 'Install &amp; Activate Now', 'car-rental-manager' );
				?>
				<div id="mpcrbm-wc-install-modal" class="mpcrbm-wc-install-modal">
					<div class="mpcrbm-wc-install-modal-box">
						<div class="mpcrbm-wc-install-modal-head">
							<h3>
								<span class="dashicons dashicons-plugins-checked"></span>
								<?php esc_html_e( 'Set Up WooCommerce', 'car-rental-manager' ); ?>
							</h3>
							<button type="button" id="mpcrbm-wc-install-modal-close">&times;</button>
						</div>
						<div class="mpcrbm-wc-install-modal-body">
							<div id="mpcrbm-wc-modal-info">
								<p><?php echo esc_html( $modal_desc ); ?></p>
								<button type="button" id="mpcrbm-wc-modal-action-btn" class="button button-primary"><?php echo wp_kses_post( $modal_btn ); ?></button>
							</div>
							<div id="mpcrbm-wc-modal-progress" style="display:none;">
								<div class="mpcrbm-wc-modal-progress-track">
									<div id="mpcrbm-wc-modal-progress-fill" class="mpcrbm-wc-modal-progress-fill"></div>
								</div>
								<p id="mpcrbm-wc-modal-status-text" class="mpcrbm-wc-modal-status-text"></p>
							</div>
						</div>
					</div>
				</div>
				<style>
				.mpcrbm-wc-install-modal{display:none;position:fixed;z-index:999999;inset:0;background:rgba(31,34,43,.6);align-items:center;justify-content:center;}
				.mpcrbm-wc-install-modal-box{background:#fff;border-radius:var(--mpcrbm-shell-radius,16px);width:520px;max-width:92vw;box-shadow:0 20px 50px rgba(31,34,43,.35);overflow:hidden;}
				.mpcrbm-wc-install-modal-head{padding:18px 24px;border-bottom:1px solid var(--mpcrbm-shell-border,#e7e7ea);display:flex;justify-content:space-between;align-items:center;background:#fafbfc;}
				.mpcrbm-wc-install-modal-head h3{margin:0;font-size:17px;color:var(--mpcrbm-shell-text,#1f222b);display:flex;align-items:center;gap:8px;}
				.mpcrbm-wc-install-modal-head .dashicons{font-size:20px;color:var(--mpcrbm-shell-primary,#667eea);}
				#mpcrbm-wc-install-modal-close{background:none;border:none;font-size:24px;line-height:1;cursor:pointer;color:#788291;padding:0;}
				.mpcrbm-wc-install-modal-body{padding:24px;}
				.mpcrbm-wc-install-modal-body p{margin:0 0 18px;font-size:14px;color:#39445A;line-height:1.6;}
				.mpcrbm-wc-modal-progress-track{width:100%;height:8px;background:#f0f0f1;border-radius:100px;overflow:hidden;margin-bottom:10px;}
				.mpcrbm-wc-modal-progress-fill{height:100%;width:0%;border-radius:100px;background:linear-gradient(90deg,#667eea,#5568d3);transition:width .5s cubic-bezier(.16,1,.3,1);}
				.mpcrbm-wc-modal-status-text{font-size:13px;color:#788291;margin:0 !important;text-align:center;min-height:20px;}
				</style>
				<script>
				jQuery(function($){
					$(document).on('click', '.mpcrbm-install-wc-trigger', function(e){
						e.preventDefault();
						$('#mpcrbm-wc-install-modal').css('display','flex').hide().fadeIn(200);
					});
					$('#mpcrbm-wc-install-modal-close').on('click', function(){ $('#mpcrbm-wc-install-modal').fadeOut(200); });
					$(document).on('click', '#mpcrbm-wc-install-modal', function(e){
						if ($(e.target).is('#mpcrbm-wc-install-modal')) { $(this).fadeOut(200); }
					});
				});
				</script>
				<?php
				$this->wc_install_progress_script(
					'mpcrbm-wc-modal-action-btn',
					'mpcrbm-wc-modal-info',
					'mpcrbm-wc-modal-progress',
					'mpcrbm-wc-modal-progress-fill',
					'mpcrbm-wc-modal-status-text',
					$is_installed
				);
			}

			/* --------------------------------------------------------------
			 * Gateway Configure modals
			 * ------------------------------------------------------------ */

			/** PayPal / Stripe / Offline Configure modals (footer). Pro-only for PayPal/Stripe. */
			public function render_gateway_modals() {
				// Also needed on the car edit screen: the Payment Method popup embeds the
				// same gateway cards, whose Configure buttons open these same modals.
				if ( ! $this->is_settings_or_car_edit_screen() ) {
					return;
				}
				$pp_enabled  = 'on' === $this->opt( 'mpcrbm_paypal_enable' );
				$pp_sandbox  = 'on' === $this->opt( 'mpcrbm_paypal_sandbox' );
				$pp_client   = $this->opt( 'mpcrbm_paypal_client_id' );
				$pp_secret   = $this->opt( 'mpcrbm_paypal_secret' );
				$st_enabled  = 'on' === $this->opt( 'mpcrbm_stripe_enable' );
				$st_sandbox  = 'on' === $this->opt( 'mpcrbm_stripe_sandbox' );
				$st_test_pub = $this->opt( 'mpcrbm_stripe_test_pub' );
				$st_test_sec = $this->opt( 'mpcrbm_stripe_test_sec' );
				$st_live_pub = $this->opt( 'mpcrbm_stripe_live_pub' );
				$st_live_sec = $this->opt( 'mpcrbm_stripe_live_sec' );
				$off_enabled = class_exists( 'MPCRBM_Function' ) && MPCRBM_Function::offline_payment_enabled();
				$off_label   = $this->opt( 'mpcrbm_offline_label', __( 'Offline Payment', 'car-rental-manager' ) );
				$nonce       = wp_create_nonce( 'mpcrbm_save_gateway' );
				$is_pro      = $this->is_pro();
				?>
				<style>
				.mpcrbm-gw-modal{display:none;position:fixed;inset:0;z-index:999999;background:rgba(31,34,43,.65);align-items:center;justify-content:center;}
				.mpcrbm-gw-modal-box{background:#fff;border-radius:var(--mpcrbm-shell-radius,16px);width:540px;max-width:94vw;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px rgba(31,34,43,.32);}
				.mpcrbm-gw-modal-header{padding:22px 26px;display:flex;align-items:center;justify-content:space-between;border-radius:var(--mpcrbm-shell-radius,16px) var(--mpcrbm-shell-radius,16px) 0 0;}
				.mpcrbm-gw-modal-header h2{margin:0;font-size:19px;font-weight:700;color:#fff;display:flex;align-items:center;gap:12px;}
				.mpcrbm-gw-modal-close{background:rgba(255,255,255,.2);border:none;border-radius:50%;width:34px;height:34px;font-size:20px;line-height:1;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;}
				.mpcrbm-gw-modal-body{padding:26px 26px 10px;}
				.mpcrbm-gw-field{margin-bottom:20px;}
				.mpcrbm-gw-field label.mpcrbm-gw-label{display:block;font-weight:600;font-size:13px;color:#39445A;margin-bottom:7px;}
				.mpcrbm-gw-field input[type="text"],.mpcrbm-gw-field input[type="password"]{width:100%;padding:10px 14px;border:1.5px solid #d9dce3;border-radius:var(--mpcrbm-shell-radius-xs,8px);font-size:14px;color:#1f222b;background:#fafbfc;box-sizing:border-box;}
				.mpcrbm-gw-field input[type="text"]:focus,.mpcrbm-gw-field input[type="password"]:focus{border-color:var(--mpcrbm-shell-primary,#667eea);box-shadow:0 0 0 3px rgba(102,126,234,.14);outline:none;background:#fff;}
				.mpcrbm-gw-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;background:#fafbfc;border-radius:var(--mpcrbm-shell-radius-sm,12px);margin-bottom:20px;border:1.5px solid var(--mpcrbm-shell-border,#e7e7ea);}
				.mpcrbm-gw-toggle-label{font-weight:600;font-size:14px;color:#1f222b;}
				.mpcrbm-gw-toggle-sub{font-size:12px;color:#788291;margin-top:2px;}
				.mpcrbm-gw-divider{border:none;border-top:1px solid var(--mpcrbm-shell-border,#e7e7ea);margin:4px 0 20px;}
				.mpcrbm-gw-section-title{font-size:12px;font-weight:700;color:#9aa2b1;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;}
				.mpcrbm-gw-modal-footer{padding:16px 26px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
				.mpcrbm-gw-modal-save-btn{padding:11px 28px;border:none;border-radius:var(--mpcrbm-shell-radius-xs,8px);font-size:15px;font-weight:700;cursor:pointer;color:#fff;flex-shrink:0;}
				.mpcrbm-gw-save-msg{display:none;padding:9px 14px;border-radius:7px;font-size:13px;font-weight:500;flex:1;}
				.mpcrbm-gw-switch{position:relative;display:inline-block;width:48px;height:26px;flex-shrink:0;}
				.mpcrbm-gw-switch input{opacity:0;width:0;height:0;}
				.mpcrbm-gw-slider{position:absolute;cursor:pointer;inset:0;background:#d9dce3;border-radius:26px;transition:.3s;}
				.mpcrbm-gw-slider:before{content:"";position:absolute;height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
				.mpcrbm-gw-switch input:checked + .mpcrbm-gw-slider{background:#22c55e;}
				.mpcrbm-gw-switch input:checked + .mpcrbm-gw-slider:before{transform:translateX(22px);}
				</style>

				<?php if ( $is_pro ) : ?>
				<!-- PayPal Config Modal -->
				<div id="mpcrbm-paypal-modal" class="mpcrbm-gw-modal">
					<div class="mpcrbm-gw-modal-box">
						<div class="mpcrbm-gw-modal-header" style="background:linear-gradient(135deg,#003087 0%,#0079C1 100%);">
							<h2><?php esc_html_e( 'PayPal Configuration', 'car-rental-manager' ); ?></h2>
							<button type="button" class="mpcrbm-gw-modal-close">&times;</button>
						</div>
						<div class="mpcrbm-gw-modal-body">
							<div class="mpcrbm-gw-toggle-row">
								<div>
									<div class="mpcrbm-gw-toggle-label"><?php esc_html_e( 'Enable PayPal', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm-gw-toggle-sub"><?php esc_html_e( 'Accept payments via PayPal', 'car-rental-manager' ); ?></div>
								</div>
								<label class="mpcrbm-gw-switch"><input type="checkbox" data-field="mpcrbm_paypal_enable" <?php checked( $pp_enabled ); ?>><span class="mpcrbm-gw-slider"></span></label>
							</div>
							<div class="mpcrbm-gw-toggle-row">
								<div>
									<div class="mpcrbm-gw-toggle-label"><?php esc_html_e( 'Sandbox / Test Mode', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm-gw-toggle-sub"><?php esc_html_e( 'Use sandbox credentials for testing', 'car-rental-manager' ); ?></div>
								</div>
								<label class="mpcrbm-gw-switch"><input type="checkbox" data-field="mpcrbm_paypal_sandbox" <?php checked( $pp_sandbox ); ?>><span class="mpcrbm-gw-slider"></span></label>
							</div>
							<hr class="mpcrbm-gw-divider">
							<p class="mpcrbm-gw-section-title"><?php esc_html_e( 'API Credentials', 'car-rental-manager' ); ?></p>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'PayPal Client ID', 'car-rental-manager' ); ?></label>
								<input type="text" data-field="mpcrbm_paypal_client_id" value="<?php echo esc_attr( $pp_client ); ?>" placeholder="<?php esc_attr_e( 'Enter your PayPal Client ID', 'car-rental-manager' ); ?>">
							</div>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'PayPal Secret Key', 'car-rental-manager' ); ?></label>
								<input type="password" data-field="mpcrbm_paypal_secret" value="<?php echo esc_attr( $pp_secret ); ?>" placeholder="<?php esc_attr_e( 'Enter your PayPal Secret Key', 'car-rental-manager' ); ?>">
							</div>
						</div>
						<div class="mpcrbm-gw-modal-footer">
							<button type="button" class="mpcrbm-gw-modal-save-btn" data-gateway="paypal" style="background:linear-gradient(135deg,#003087,#0079C1);"><?php esc_html_e( 'Save PayPal Settings', 'car-rental-manager' ); ?></button>
							<span class="mpcrbm-gw-save-msg"></span>
						</div>
					</div>
				</div>

				<!-- Stripe Config Modal -->
				<div id="mpcrbm-stripe-modal" class="mpcrbm-gw-modal">
					<div class="mpcrbm-gw-modal-box">
						<div class="mpcrbm-gw-modal-header" style="background:linear-gradient(135deg,#635bff 0%,#3f36c5 100%);">
							<h2><?php esc_html_e( 'Stripe Configuration', 'car-rental-manager' ); ?></h2>
							<button type="button" class="mpcrbm-gw-modal-close">&times;</button>
						</div>
						<div class="mpcrbm-gw-modal-body">
							<div class="mpcrbm-gw-toggle-row">
								<div>
									<div class="mpcrbm-gw-toggle-label"><?php esc_html_e( 'Enable Stripe', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm-gw-toggle-sub"><?php esc_html_e( 'Accept payments via Stripe', 'car-rental-manager' ); ?></div>
								</div>
								<label class="mpcrbm-gw-switch"><input type="checkbox" data-field="mpcrbm_stripe_enable" <?php checked( $st_enabled ); ?>><span class="mpcrbm-gw-slider"></span></label>
							</div>
							<div class="mpcrbm-gw-toggle-row">
								<div>
									<div class="mpcrbm-gw-toggle-label"><?php esc_html_e( 'Sandbox / Test Mode', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm-gw-toggle-sub"><?php esc_html_e( 'Use test keys instead of live keys', 'car-rental-manager' ); ?></div>
								</div>
								<label class="mpcrbm-gw-switch"><input type="checkbox" data-field="mpcrbm_stripe_sandbox" <?php checked( $st_sandbox ); ?>><span class="mpcrbm-gw-slider"></span></label>
							</div>
							<hr class="mpcrbm-gw-divider">
							<p class="mpcrbm-gw-section-title"><?php esc_html_e( 'Test / Sandbox Keys', 'car-rental-manager' ); ?></p>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Test Publishable Key', 'car-rental-manager' ); ?></label>
								<input type="text" data-field="mpcrbm_stripe_test_pub" value="<?php echo esc_attr( $st_test_pub ); ?>" placeholder="pk_test_...">
							</div>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Test Secret Key', 'car-rental-manager' ); ?></label>
								<input type="password" data-field="mpcrbm_stripe_test_sec" value="<?php echo esc_attr( $st_test_sec ); ?>" placeholder="sk_test_...">
							</div>
							<hr class="mpcrbm-gw-divider">
							<p class="mpcrbm-gw-section-title"><?php esc_html_e( 'Live Keys', 'car-rental-manager' ); ?></p>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Live Publishable Key', 'car-rental-manager' ); ?></label>
								<input type="text" data-field="mpcrbm_stripe_live_pub" value="<?php echo esc_attr( $st_live_pub ); ?>" placeholder="pk_live_...">
							</div>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Live Secret Key', 'car-rental-manager' ); ?></label>
								<input type="password" data-field="mpcrbm_stripe_live_sec" value="<?php echo esc_attr( $st_live_sec ); ?>" placeholder="sk_live_...">
							</div>
						</div>
						<div class="mpcrbm-gw-modal-footer">
							<button type="button" class="mpcrbm-gw-modal-save-btn" data-gateway="stripe" style="background:linear-gradient(135deg,#635bff,#3f36c5);"><?php esc_html_e( 'Save Stripe Settings', 'car-rental-manager' ); ?></button>
							<span class="mpcrbm-gw-save-msg"></span>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Offline Payment Config Modal (free — no online processor needed). -->
				<div id="mpcrbm-offline-modal" class="mpcrbm-gw-modal">
					<div class="mpcrbm-gw-modal-box">
						<div class="mpcrbm-gw-modal-header" style="background:linear-gradient(135deg,#0f766e 0%,#115e59 100%);">
							<h2><?php esc_html_e( 'Offline Payment Configuration', 'car-rental-manager' ); ?></h2>
							<button type="button" class="mpcrbm-gw-modal-close">&times;</button>
						</div>
						<div class="mpcrbm-gw-modal-body">
							<div class="mpcrbm-gw-toggle-row">
								<div>
									<div class="mpcrbm-gw-toggle-label"><?php esc_html_e( 'Enable Offline Payment', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm-gw-toggle-sub"><?php esc_html_e( 'Let customers pay offline (bank transfer, cash, pay on pickup).', 'car-rental-manager' ); ?></div>
								</div>
								<label class="mpcrbm-gw-switch"><input type="checkbox" data-field="mpcrbm_offline_enable" <?php checked( $off_enabled ); ?>><span class="mpcrbm-gw-slider"></span></label>
							</div>
							<hr class="mpcrbm-gw-divider">
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Payment Label', 'car-rental-manager' ); ?></label>
								<input type="text" data-field="mpcrbm_offline_label" value="<?php echo esc_attr( $off_label ); ?>" placeholder="<?php esc_attr_e( 'Pay on Pickup / Bank Transfer', 'car-rental-manager' ); ?>">
								<p style="margin:8px 0 0;font-size:12px;color:#788291;"><?php esc_html_e( 'This label is shown to customers on the frontend payment step.', 'car-rental-manager' ); ?></p>
							</div>
							<div class="mpcrbm-gw-field">
								<label class="mpcrbm-gw-label"><?php esc_html_e( 'Payment Instructions', 'car-rental-manager' ); ?></label>
								<input type="text" data-field="mpcrbm_offline_instructions" value="<?php echo esc_attr( $this->opt( 'mpcrbm_offline_instructions' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Pay the driver in cash at pickup', 'car-rental-manager' ); ?>">
								<p style="margin:8px 0 0;font-size:12px;color:#788291;"><?php esc_html_e( 'Shown on the confirmation page and in the booking email.', 'car-rental-manager' ); ?></p>
							</div>
						</div>
						<div class="mpcrbm-gw-modal-footer">
							<button type="button" class="mpcrbm-gw-modal-save-btn" data-gateway="offline" style="background:linear-gradient(135deg,#0f766e,#115e59);"><?php esc_html_e( 'Save Offline Settings', 'car-rental-manager' ); ?></button>
							<span class="mpcrbm-gw-save-msg"></span>
						</div>
					</div>
				</div>

				<script>
				var mpcrbmGateway = <?php echo wp_json_encode( array(
					'nonce'    => $nonce,
					'enabled'  => __( 'Enabled', 'car-rental-manager' ),
					'disabled' => __( 'Disabled', 'car-rental-manager' ),
					'netError' => __( 'A network error occurred.', 'car-rental-manager' ),
				) ); ?>;
				jQuery(function($){
					$(document).on('click', '#mpcrbm-paypal-configure-btn', function(e){ e.preventDefault(); $('#mpcrbm-paypal-modal').css('display','flex').hide().fadeIn(220); });
					$(document).on('click', '#mpcrbm-stripe-configure-btn', function(e){ e.preventDefault(); $('#mpcrbm-stripe-modal').css('display','flex').hide().fadeIn(220); });
					$(document).on('click', '#mpcrbm-offline-configure-btn', function(e){ e.preventDefault(); $('#mpcrbm-offline-modal').css('display','flex').hide().fadeIn(220); });
					$(document).on('click', '.mpcrbm-gw-modal-close', function(){ $('.mpcrbm-gw-modal').fadeOut(200); });
					$(document).on('click', '.mpcrbm-gw-modal', function(e){ if ($(e.target).hasClass('mpcrbm-gw-modal')) $(this).fadeOut(200); });

					$(document).on('click', '.mpcrbm-gw-modal-save-btn', function(e){
						e.preventDefault();
						var $btn=$(this), $box=$btn.closest('.mpcrbm-gw-modal-box'), gateway=$btn.data('gateway'),
						    $msg=$box.find('.mpcrbm-gw-save-msg'), fields={};
						$box.find('input[data-field]').each(function(){
							var key=$(this).data('field');
							fields[key]=($(this).attr('type')==='checkbox') ? ($(this).is(':checked')?'on':'off') : $(this).val();
						});
						$btn.prop('disabled',true).css('opacity','0.7'); $msg.hide();
						$.ajax({
							url: ajaxurl, type:'POST',
							data:{ action:'mpcrbm_save_gateway_settings', nonce:mpcrbmGateway.nonce, gateway:gateway, fields:fields },
							success: function(res){
								if(res.success){
									$msg.css({'color':'#0f5132','background':'#d1e7dd','border':'1px solid #badbcc'}).text(res.data).fadeIn(200);
									setTimeout(function(){ $msg.fadeOut(400); }, 1200);
									var isEnabled = fields['mpcrbm_'+gateway+'_enable']==='on';
									$('.'+gateway+'-card .gateway-status').text(isEnabled?mpcrbmGateway.enabled:mpcrbmGateway.disabled).toggleClass('active',isEnabled);
									// The Booking Mode warning ("no gateway enabled yet") is
									// now stale — re-evaluate it against the change just saved.
									$(document).trigger('mpcrbm:custom-gateways-changed');
								} else {
									$msg.css({'color':'#842029','background':'#f8d7da','border':'1px solid #f5c2c7'}).text(res.data).fadeIn(200);
									setTimeout(function(){ $msg.fadeOut(400); }, 1500);
								}
							},
							error: function(){
								$msg.css({'color':'#842029','background':'#f8d7da','border':'1px solid #f5c2c7'}).text(mpcrbmGateway.netError).fadeIn(200);
								setTimeout(function(){ $msg.fadeOut(400); }, 1500);
							},
							complete: function(){ $btn.prop('disabled',false).css('opacity','1'); }
						});
					});

					// Once any gateway toggle changes, the "no gateway is enabled" warning
					// beside the Booking Mode cards may no longer be true. Clearing it only
					// when a gateway was just ENABLED keeps the warning honest — disabling
					// the last one still leaves it (a reload re-derives the real state).
					$(document).on('mpcrbm:custom-gateways-changed mpcrbm:wc-gateways-changed', function(){
						if ($('.gateway-status.active').length || $('.mpcrbm-gw-card.is-enabled').length) {
							$('.mpcrbm-bm-gateway-warning').remove();
						}
					});
				});
				</script>
				<?php
			}

			/* --------------------------------------------------------------
			 * Payments tab styling + section switching
			 * ------------------------------------------------------------ */

			/** Section show/hide + gateway card styling (footer). */
			public function payment_tabs_script() {
				// Also needed on the car edit screen so the popup's gateway cards get the
				// same `.gateway-card` styling defined below. The section-switching JS
				// early-returns once it fails to find the settings page's own markup, so
				// printing it there is safe.
				if ( ! $this->is_settings_or_car_edit_screen() ) {
					return;
				}
				$wc_active = $this->has_woo() ? 'true' : 'false';
				?>
				<style>
				/* ── Payments tab: full-width panels opt out of the 2-column field grid ── */
				.mpcrbm-info-grid > section.mpcrbm-fullrow{grid-column:1 / -1;}
				.mpcrbm-info-grid > section.mpcrbm-bm-row{padding-bottom:4px;}

				/* WooCommerce-not-activated callout */
				.mpcrbm-wc-callout{background:linear-gradient(180deg,#fffdf6,#fff8e8);border:1px solid #f2e0b0;border-radius:var(--mpcrbm-shell-radius,16px);padding:18px;margin:8px 0 0;}
				.mpcrbm-wc-callout .mpcrbm-wc-callout-head{display:flex !important;align-items:center;gap:12px;margin-bottom:9px;}
				.mpcrbm-wc-callout .mpcrbm-wc-callout-icon{flex:0 0 auto;width:40px;height:40px;border-radius:11px;background:#fff2cf;color:#c07d16;border:1px solid #f2d484;display:flex !important;align-items:center;justify-content:center;}
				.mpcrbm-wc-callout .mpcrbm-wc-callout-icon svg{width:21px;height:21px;display:block;}
				.mpcrbm-wc-callout .mpcrbm-wc-callout-title{margin:0;padding:0;font-size:15px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);line-height:1.3;text-transform:none;}
				.mpcrbm-wc-callout-text{margin:0 0 15px;font-size:13px;color:var(--mpcrbm-shell-text-faded,#788291);line-height:1.55;max-width:720px;}
				.mpcrbm-wc-callout-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 18px;border:none;border-radius:var(--mpcrbm-shell-radius-xs,8px);background:#7f54b3;color:#fff !important;font-size:13.5px;font-weight:600;cursor:pointer;line-height:1;box-shadow:0 2px 6px rgba(127,84,179,.3);transition:all .18s ease;text-decoration:none;}
				.mpcrbm-wc-callout-btn:hover{background:#6b4599;transform:translateY(-1px);box-shadow:0 5px 14px rgba(127,84,179,.34);color:#fff !important;}
				.mpcrbm-wc-callout-btn:active{transform:translateY(0);}
				.mpcrbm-wc-callout-btn svg{width:16px;height:16px;display:block;}

				/* Custom Payment intro */
				.mpcrbm-gw-intro{margin:4px 0 18px;}
				.mpcrbm-gw-intro h3{margin:0 0 6px;font-size:16px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);}
				.mpcrbm-gw-intro p{margin:0;font-size:13px;color:var(--mpcrbm-shell-text-faded,#788291);max-width:680px;line-height:1.6;}

				/* Gateway cards (Custom Payment) */
				.gateway-card{position:relative;background:#fff;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius,16px);margin-bottom:13px;box-shadow:0 1px 2px rgba(31,34,43,.04);width:100%;box-sizing:border-box;color:var(--mpcrbm-shell-text,#1f222b);overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}
				.gateway-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(31,34,43,.10);}
				.gateway-card .gateway-header{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 20px;}
				.gateway-card .gateway-id{display:flex;align-items:center;gap:14px;min-width:0;flex:1 1 0;}
				.gateway-card .gateway-icon{flex:0 0 auto;width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 4px 10px rgba(31,34,43,.13);}
				.gateway-card .gateway-meta{display:flex;flex-direction:column;min-width:0;}
				.gateway-card .gateway-name{font-size:15px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);line-height:1.3;}
				.gateway-card .gateway-sub{font-size:12px;color:var(--mpcrbm-shell-text-faded,#788291);line-height:1.4;}
				.gateway-card .gateway-actions{display:flex;align-items:center;justify-content:flex-end;gap:12px;flex:1 1 0;}
				.gateway-card .gateway-status{display:inline-block;min-width:74px;text-align:center;font-size:10.5px;text-transform:uppercase;letter-spacing:.4px;padding:4px 11px;border-radius:20px;background:#f1f2f6;color:#788291;border:1px solid #e5e6ea;font-weight:700;}
				.gateway-card .gateway-status.active{background:#dcfce7;color:#15803d;border-color:#bbf7d0;}
				.gateway-card.paypal-card{background:#f4f9fe;}
				.gateway-card.paypal-card .gateway-icon{background:linear-gradient(135deg,#0079C1,#003087);}
				.gateway-card.stripe-card{background:#f6f5ff;}
				.gateway-card.stripe-card .gateway-icon{background:linear-gradient(135deg,#7a73ff,#4f46e5);}
				.gateway-card.offline-card{background:#f0faf8;}
				.gateway-card.offline-card .gateway-icon{background:linear-gradient(135deg,#14b8a6,#0f766e);}
				.gateway-card .gateway-configure-btn{cursor:pointer;color:#fff !important;border:none !important;font-weight:600 !important;font-size:13px !important;border-radius:var(--mpcrbm-shell-radius-xs,8px) !important;padding:8px 16px !important;line-height:1.4 !important;box-shadow:0 2px 6px rgba(31,34,43,.14) !important;transition:transform .15s ease,opacity .15s ease;}
				.gateway-card.paypal-card .gateway-configure-btn{background:#0070ba !important;}
				.gateway-card.stripe-card .gateway-configure-btn{background:#635bff !important;}
				.gateway-card.offline-card .gateway-configure-btn{background:#0f766e !important;}
				.gateway-card .gateway-configure-btn:hover{transform:translateY(-1px);opacity:.94;}
				.mpcrbm-gw-pro-badge{display:inline-block;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff;padding:5px 12px;border-radius:20px;font-weight:800;font-size:10.5px;text-transform:uppercase;letter-spacing:.5px;box-shadow:0 2px 6px rgba(245,158,11,.3);}

				/* Booking confirmation page / require-login rows */
				.mpcrbm-conf-page{margin-top:12px;padding:20px 22px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;background:#fff;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius,16px);box-shadow:0 1px 2px rgba(31,34,43,.04);transition:border-color .18s ease,box-shadow .18s ease;}
				.mpcrbm-conf-page:hover{border-color:#d5d7dd;box-shadow:0 4px 14px rgba(31,34,43,.06);}
				.mpcrbm-conf-page-label{flex:1 1 260px;}
				.mpcrbm-conf-page-label label{display:block;font-weight:700;font-size:14px;color:var(--mpcrbm-shell-text,#1f222b);margin:0 0 4px;}
				.mpcrbm-conf-page-label span{display:block;font-size:12px;color:var(--mpcrbm-shell-text-faded,#788291);line-height:1.6;}
				.mpcrbm-conf-page-field{flex:0 0 auto;}
				.mpcrbm-conf-page-field select{width:100%;max-width:320px;min-width:230px;border:1px solid #d9dce3;border-radius:var(--mpcrbm-shell-radius-xs,8px);padding:9px 13px;font-size:13px;font-weight:500;color:#39445A;background:#fff;}
				.mpcrbm-conf-page-field select:hover{border-color:#9aa2b1;}
				.mpcrbm-conf-page-field select:focus{border-color:var(--mpcrbm-shell-primary,#667eea);box-shadow:0 0 0 3px rgba(102,126,234,.16);outline:none;}

				/* "Additional Settings" collapsible header */
				.mpcrbm-acc-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;cursor:pointer;user-select:none;background:#fff;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius-sm,12px);padding:14px 20px;margin:8px 0 0;transition:background .2s ease,border-color .2s ease,box-shadow .2s ease;}
				.mpcrbm-acc-bar:hover{border-color:#c7cbf0;box-shadow:0 2px 8px rgba(31,34,43,.06);}
				.mpcrbm-acc-bar.open{background:#f3f4fd;border-color:var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-acc-title{font-size:14px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);}
				.mpcrbm-acc-bar.open .mpcrbm-acc-title{color:var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-acc-arrow{transition:transform .2s ease;color:#788291;line-height:1;}
				.mpcrbm-acc-bar.open .mpcrbm-acc-arrow{transform:rotate(180deg);color:var(--mpcrbm-shell-primary,#667eea);}

				/* The accordion header already names the section, so hide the manager's own
				   duplicate heading but keep its bar (it holds the "Open in WooCommerce" link). */
				.mpcrbm-wc-payment-manager{display:block;width:100%;box-sizing:border-box;margin-top:8px;}
				.mpcrbm-wc-pm-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;}
				.mpcrbm-wc-pm-heading{margin:0;font-size:15px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);}
				.mpcrbm-wc-pm-wc-link{font-size:12.5px;font-weight:600;color:var(--mpcrbm-shell-primary,#667eea);text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
				.mpcrbm-wc-pm-wc-link:hover{text-decoration:underline;}
				.mpcrbm-wc-pm-wc-link .dashicons{font-size:14px;width:14px;height:14px;line-height:1.4;}

				.mpcrbm-gw-card{border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius-sm,12px);background:#fff;margin-bottom:14px;overflow:hidden;box-shadow:0 1px 2px rgba(31,34,43,.04);transition:box-shadow .18s ease;}
				.mpcrbm-gw-card:hover{box-shadow:0 4px 14px rgba(31,34,43,.08);}
				.mpcrbm-gw-card.is-enabled{border-left:3px solid var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-gw-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;}
				.mpcrbm-gw-head-main{display:flex;align-items:center;gap:12px;}
				.mpcrbm-gw-title{font-size:14px;font-weight:600;color:var(--mpcrbm-shell-text,#1f222b);}
				.mpcrbm-gw-badge{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;padding:2px 8px;border-radius:9px;background:#f1f2f6;color:#788291;}
				.mpcrbm-gw-card.is-enabled .mpcrbm-gw-badge{background:#e6f4ea;color:#0a7c2f;}
				.mpcrbm-gw-desc{padding:0 16px 12px;color:#788291;font-size:13px;}
				.mpcrbm-gw-desc p{margin:0 0 6px;}
				.mpcrbm-gw-configure-btn,.mpcrbm-gw-save-btn{cursor:pointer;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);background:#fff;color:#39445A;font-size:13px;font-weight:600;border-radius:var(--mpcrbm-shell-radius-xs,8px);padding:7px 15px;line-height:1.4;transition:border-color .15s,background .15s;}
				.mpcrbm-gw-configure-btn:hover{border-color:var(--mpcrbm-shell-primary,#667eea);color:var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-gw-save-btn{background:var(--mpcrbm-shell-primary,#667eea);border-color:var(--mpcrbm-shell-primary,#667eea);color:#fff;}
				.mpcrbm-gw-save-btn:hover{background:#5568d3;border-color:#5568d3;}
				.mpcrbm-gw-body{padding:6px 16px 16px;border-top:1px solid #f1f2f6;background:#fafbfc;}
				.mpcrbm-gw-form-table{width:100%;background:transparent;}
				.mpcrbm-gw-form-table th{width:200px;padding:14px 10px 14px 0;background:transparent;font-weight:600;vertical-align:top;}
				.mpcrbm-gw-form-table td{padding:12px 0;background:transparent;}
				.mpcrbm-gw-form-table input[type=text],.mpcrbm-gw-form-table input[type=password],
				.mpcrbm-gw-form-table input[type=email],.mpcrbm-gw-form-table input[type=number],
				.mpcrbm-gw-form-table textarea,.mpcrbm-gw-form-table select{min-width:320px;max-width:100%;}
				.mpcrbm-gw-form-footer{display:flex;align-items:center;gap:12px;margin-top:8px;padding-top:12px;border-top:1px solid #f1f2f6;}
				.mpcrbm-gw-status{font-size:13px;}
				.mpcrbm-gw-status.is-success{color:#0a7c2f;}
				.mpcrbm-gw-status.is-error{color:#d63638;}

				/* Toggle switch on each WooCommerce gateway card */
				.mpcrbm-gw-toggle{position:relative;display:inline-block;width:42px;height:24px;cursor:pointer;flex:0 0 auto;}
				.mpcrbm-gw-toggle-input{position:absolute;inset:0;margin:0;padding:0;width:100%;height:100%;min-width:0 !important;min-height:0 !important;opacity:0 !important;cursor:pointer;z-index:1;-webkit-appearance:none !important;-moz-appearance:none !important;appearance:none !important;background:none !important;border:none !important;box-shadow:none !important;}
				.mpcrbm-gw-toggle-input::before,.mpcrbm-gw-toggle-input::after{content:none !important;display:none !important;}
				.mpcrbm-gw-toggle-slider{position:absolute;inset:0;background:#c3c6ce;border-radius:24px;transition:background .2s;}
				.mpcrbm-gw-toggle-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.3);}
				.mpcrbm-gw-toggle-input:checked + .mpcrbm-gw-toggle-slider{background:var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-gw-toggle-input:checked + .mpcrbm-gw-toggle-slider::before{transform:translateX(18px);}
				.mpcrbm-gw-toggle-input:disabled + .mpcrbm-gw-toggle-slider{opacity:.5;cursor:not-allowed;}

				/* Bottom-right AJAX confirmation toast */
				.mpcrbm_toast{position:fixed;right:24px;bottom:24px;z-index:1000000;display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:var(--mpcrbm-shell-radius-sm,12px);background:#1f222b;color:#fff;font-size:13.5px;font-weight:600;box-shadow:0 12px 30px rgba(31,34,43,.28);opacity:0;transform:translateY(12px);transition:opacity .25s ease,transform .25s ease;}
				.mpcrbm_toast.is-show{opacity:1;transform:translateY(0);}
				.mpcrbm_toast_icon{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;flex:0 0 auto;}
				.mpcrbm_toast_success .mpcrbm_toast_icon{background:#16a34a;}
				.mpcrbm_toast_error .mpcrbm_toast_icon{background:#dc2626;}
				</style>
				<script>
				jQuery(function($){
					// Deep-link from the "Go to Payment Settings" admin notice: open the
					// Payments tab instead of landing on the default first tab.
					(function(){
						var params = new URLSearchParams(window.location.search || '');
						if (params.get('mpcrbm_tab') !== 'payments') { return; }
						var $target = $('[data-tabs-target="#<?php echo esc_js( self::OPTION ); ?>"]');
						if (!$target.length) { return; }
						setTimeout(function(){
							$target.trigger('click');
							var el = $target.get(0);
							if (el && el.scrollIntoView) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
						}, 200);
					})();

					var $panel = $('div.tabsItem[data-tabs="#<?php echo esc_js( self::OPTION ); ?>"]');
					if (!$panel.length) { return; }

					var wcActive = <?php echo $wc_active; ?>;

					// The mode actually in effect, resolved server-side. Used when the
					// Booking Mode cards aren't rendered (only one flow is available, so
					// there is nothing to choose) — the correct section must still be the
					// visible one. When neither flow is ready, get_mode() is intentionally
					// empty; show the Custom section if WooCommerce is unavailable, so the
					// admin can enable Offline Payment instead of hiding the only control
					// that could resolve the situation.
					var resolvedMode = <?php echo wp_json_encode( class_exists( 'MPCRBM_Booking_Mode' ) ? MPCRBM_Booking_Mode::get_mode() : 'woocommerce' ); ?>;
					if (resolvedMode !== 'woocommerce' && resolvedMode !== 'custom') {
						resolvedMode = wcActive ? 'woocommerce' : 'custom';
					}

					var $accBar          = $panel.find('.mpcrbm-acc-bar');
					var $additionalCells = $panel.find('section.wc-additional-field');

					function refreshAccordion(){
						var open = $accBar.hasClass('open');
						$accBar.attr('aria-expanded', open ? 'true' : 'false');
						// Only reveal the additional fields when WooCommerce mode is also the
						// active section — otherwise an open accordion would leak WooCommerce
						// fields into the Custom Payment view.
						if (open && activeMode() === 'woocommerce' && wcActive) {
							$additionalCells.show();
						} else {
							$additionalCells.hide();
						}
					}

					$accBar.on('click keydown', function(e){
						if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') { return; }
						e.preventDefault();
						$accBar.toggleClass('open');
						refreshAccordion();
					});

					// Which settings section is showing follows the Booking Mode — the
					// selected card when the selector is on screen, otherwise the
					// server-resolved mode.
					function activeMode(){
						var $selected = $panel.find('.mpcrbm-bm-card.is-selected');
						return $selected.length ? String($selected.data('mode')) : resolvedMode;
					}

					function updateSections(){
						$panel.find('section.woocommerce-field, section.no-woocommerce-field, section.mpcrbm-woo-callout-row').hide();

						if (activeMode() === 'custom') {
							// Custom Payment: only the gateway cards. The WooCommerce install
							// callout belongs to the WooCommerce tab, not here.
							$panel.find('section.no-woocommerce-field').show();
						} else {
							// WooCommerce: the install callout shows whether or not WooCommerce
							// is active; the payment-methods manager and the additional fields
							// only once it actually is.
							$panel.find('section.mpcrbm-woo-callout-row').show();
							if (wcActive) {
								$panel.find('section.woocommerce-field').show();
							}
						}
						refreshAccordion();
					}

					// Fired by the Booking Mode selector after every card click.
					$(document).on('mpcrbm:mode-changed', updateSections);

					updateSections();
				});
				</script>
				<?php
			}

			/* --------------------------------------------------------------
			 * AJAX handlers
			 * ------------------------------------------------------------ */

			/** AJAX: save a single gateway's settings (real-time from its modal). */
			public function ajax_save_gateway_settings() {
				check_ajax_referer( 'mpcrbm_save_gateway', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( __( 'Permission denied.', 'car-rental-manager' ) );
				}

				$gateway  = isset( $_POST['gateway'] ) ? sanitize_key( wp_unslash( $_POST['gateway'] ) ) : '';
				$fields   = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is sanitized individually below.
				$existing = get_option( self::OPTION, array() );
				if ( ! is_array( $existing ) ) {
					$existing = array();
				}

				$allowed = array(
					'paypal'  => array( 'mpcrbm_paypal_enable', 'mpcrbm_paypal_sandbox', 'mpcrbm_paypal_client_id', 'mpcrbm_paypal_secret' ),
					'stripe'  => array( 'mpcrbm_stripe_enable', 'mpcrbm_stripe_sandbox', 'mpcrbm_stripe_test_pub', 'mpcrbm_stripe_test_sec', 'mpcrbm_stripe_live_pub', 'mpcrbm_stripe_live_sec' ),
					'offline' => array( 'mpcrbm_offline_enable', 'mpcrbm_offline_label', 'mpcrbm_offline_instructions' ),
				);

				if ( ! isset( $allowed[ $gateway ] ) ) {
					wp_send_json_error( __( 'Invalid gateway.', 'car-rental-manager' ) );
				}

				// PayPal & Stripe configuration is Pro-only; never persist them from the free
				// build. Offline needs no online processor, so it stays configurable in free.
				if ( 'offline' !== $gateway && ! $this->is_pro() ) {
					wp_send_json_error( __( 'This gateway is available in the Pro version.', 'car-rental-manager' ) );
				}

				$toggles = array( 'mpcrbm_paypal_enable', 'mpcrbm_paypal_sandbox', 'mpcrbm_stripe_enable', 'mpcrbm_stripe_sandbox', 'mpcrbm_offline_enable' );
				foreach ( $allowed[ $gateway ] as $key ) {
					$val = isset( $fields[ $key ] ) ? $fields[ $key ] : 'off';
					if ( in_array( $key, $toggles, true ) ) {
						$existing[ $key ] = ( 'on' === $val ) ? 'on' : 'off';
					} else {
						$existing[ $key ] = sanitize_text_field( $val );
					}
				}

				update_option( self::OPTION, $existing );
				wp_send_json_success( __( 'Settings saved successfully!', 'car-rental-manager' ) );
			}

			/** AJAX: save the Booking Mode selector (real-time, no page reload). */
			public function ajax_save_booking_mode() {
				check_ajax_referer( 'mpcrbm_save_booking_mode', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( __( 'Permission denied.', 'car-rental-manager' ) );
				}
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) || 'both' !== MPCRBM_Booking_Mode::availability() ) {
					wp_send_json_error( __( 'Booking mode cannot be changed right now.', 'car-rental-manager' ) );
				}

				$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
				if ( ! MPCRBM_Booking_Mode::set_mode( $mode ) ) {
					wp_send_json_error( __( 'Invalid booking mode.', 'car-rental-manager' ) );
				}

				wp_send_json_success( array(
					'message'     => __( 'Booking mode saved.', 'car-rental-manager' ),
					'mode'        => $mode,
					'has_gateway' => MPCRBM_Booking_Mode::has_gateway_for_active_mode(),
				) );
			}

			/**
			 * AJAX: install (download + unpack) or activate WooCommerce, one step per
			 * request instead of both in a single call — a slow connection or a loaded
			 * server only has to survive one focused request at a time, and the frontend
			 * can show a progress bar with real milestones instead of a guessed animation.
			 * $_POST['step'] is 'install' (default) or 'activate'.
			 */
			public function ajax_install_activate_wc() {
				check_ajax_referer( 'mpcrbm_install_wc', 'nonce' );
				if ( ! current_user_can( 'install_plugins' ) ) {
					wp_send_json_error( __( 'Permission denied.', 'car-rental-manager' ) );
				}

				// The install step downloads and unpacks a real plugin zip, which can be slow
				// on a poor connection or a tightly-limited host — raise both caps for this
				// request rather than letting a slow download get killed mid-way by a typical
				// shared-host default (30s execution time).
				if ( function_exists( 'set_time_limit' ) ) {
					@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
				if ( function_exists( 'wp_raise_memory_limit' ) ) {
					wp_raise_memory_limit( 'admin' );
				}

				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/misc.php';

				$plugin_file = 'woocommerce/woocommerce.php';
				$step        = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : 'install';

				if ( 'activate' === $step ) {
					if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
						wp_send_json_error( __( 'WooCommerce is not installed yet — please install it first.', 'car-rental-manager' ) );
					}

					// Activate via the options table to avoid loading woocommerce.php into
					// this process, which would clash with the currently-loaded no-WC
					// fallbacks (MPCRBM_Global_Function::format_price() et al).
					$active = get_option( 'active_plugins', array() );
					if ( ! in_array( $plugin_file, $active, true ) ) {
						$active[] = $plugin_file;
						sort( $active );
						update_option( 'active_plugins', $active );
					}
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hooks.
					do_action( 'activate_' . $plugin_file );
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hooks.
					do_action( 'activated_plugin', $plugin_file, false );

					// Cars published while WooCommerce was inactive have no hidden mirror
					// product, so "Book Now" would fail with a cart error the moment the site
					// switches to WooCommerce mode. Backfill them now, while we know the
					// exact transition just happened.
					if ( class_exists( 'MPCRBM_Hidden_Product' ) ) {
						MPCRBM_Hidden_Product::repair_all_hidden_products();
					}

					wp_send_json_success( __( 'WooCommerce activated successfully!', 'car-rental-manager' ) );
				}

				// step === 'install': download + unpack only. Activation is a separate
				// request (above) so a slow download can't also delay/block it.
				if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
					wp_send_json_success( __( 'WooCommerce is already installed.', 'car-rental-manager' ) );
				}

				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

				$api = plugins_api( 'plugin_information', array(
					'slug'   => 'woocommerce',
					'fields' => array( 'sections' => false ),
				) );
				if ( is_wp_error( $api ) ) {
					wp_send_json_error( $api->get_error_message() );
				}
				$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
				$result   = $upgrader->install( $api->download_link );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( $result->get_error_message() );
				} elseif ( ! $result ) {
					wp_send_json_error( __( 'Installation failed. Please try manually.', 'car-rental-manager' ) );
				}

				wp_send_json_success( __( 'WooCommerce installed successfully!', 'car-rental-manager' ) );
			}

			/**
			 * Keep values saved outside this form (gateway credentials + the Booking Mode,
			 * both written by their own AJAX handlers and never travelling with the form)
			 * when the Settings API saves the rest. Only restores a key when it is ABSENT
			 * from the incoming value, so an AJAX save with new values is never clobbered.
			 */
			public function preserve_gateway_keys( $new_value, $old_value ) {
				$protected = array(
					'mpcrbm_paypal_enable', 'mpcrbm_paypal_sandbox', 'mpcrbm_paypal_client_id', 'mpcrbm_paypal_secret',
					'mpcrbm_stripe_enable', 'mpcrbm_stripe_sandbox', 'mpcrbm_stripe_test_pub', 'mpcrbm_stripe_test_sec',
					'mpcrbm_stripe_live_pub', 'mpcrbm_stripe_live_sec',
					'mpcrbm_offline_enable', 'mpcrbm_offline_label', 'mpcrbm_offline_instructions',
					'mpcrbm_booking_mode',
					'mpcrbm_require_login',
				);
				if ( ! is_array( $new_value ) ) {
					return $new_value;
				}
				if ( is_array( $old_value ) ) {
					foreach ( $protected as $key ) {
						if ( ! isset( $new_value[ $key ] ) && isset( $old_value[ $key ] ) ) {
							$new_value[ $key ] = $old_value[ $key ];
						}
					}
				}

				return $new_value;
			}
		}

		new MPCRBM_Payment_Settings();
	endif;
