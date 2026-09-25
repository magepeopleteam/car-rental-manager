<?php
	/**
	 * Global Settings > Integrations: SecureHold WP deposit holds.
	 *
	 * Shows what has to be in place before SecureHold can hold car deposits on the
	 * customer's card, and runs the steps that belong to this plugin or write one
	 * SecureHold option. Plugin installs and third-party settings link to the screen
	 * that does them.
	 *
	 * @package Car_Rental_Manager
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_Integrations_Settings' ) ) {
		class MPCRBM_Integrations_Settings {

			const SECTION       = 'mpcrbm_integrations_settings';
			const STRIPE_PLUGIN = 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php';
			const STRIPE_SLUG   = 'woocommerce-gateway-stripe';
			const NONCE_ACTION  = 'mpcrbm_securehold_setup_';

			public function __construct() {
				add_filter( 'mpcrbm_settings_sec_reg', array( $this, 'register_section' ), 20 );
				add_action( 'admin_post_mpcrbm_securehold_setup', array( $this, 'handle_step' ) );
			}

			/** Add the Integrations tab, after Payments and Currency. */
			public function register_section( $sections ) {
				$sections[] = array(
					'id'       => self::SECTION,
					'icon'     => 'fas fa-plug',
					'title'    => esc_html__( 'Integrations', 'car-rental-manager' ),
					'callback' => array( $this, 'render' ),
				);

				return $sections;
			}

			private function settings_url() {
				return admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_settings_page' );
			}

			/**
			 * Link that runs one setup step (see handle_step()).
			 *
			 * @param string $step Step name.
			 * @return string
			 */
			private function step_url( $step ) {
				return wp_nonce_url( admin_url( 'admin-post.php?action=mpcrbm_securehold_setup&step=' . $step ), self::NONCE_ACTION . $step );
			}

			/**
			 * Run a setup step, then return to the Integrations tab.
			 *
			 * - bridge: SecureHold's "Use MagePeople security deposit amounts" on, the
			 *   same option and value its own Rule Engine tab saves.
			 * - global_hold_off: SecureHold's Default Hold Amount to 0, so it only holds
			 *   fixed car (and rental) deposits.
			 * - woocommerce_mode: car bookings use the WooCommerce checkout, as the
			 *   Payments tab's Booking Mode selector does.
			 */
			public function handle_step() {
				$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '';
				check_admin_referer( self::NONCE_ACTION . $step );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have permission to do that.', 'car-rental-manager' ), 403 );
				}

				$done = false;
				switch ( $step ) {
					case 'bridge':
						if ( defined( 'SECUREHOLD_PLUGIN_DIR' ) ) {
							update_option( MPCRBM_SecureHold_Compat::BRIDGE_OPTION, 'yes' );
							$done = true;
						}
						break;
					case 'global_hold_off':
						if ( defined( 'SECUREHOLD_PLUGIN_DIR' ) ) {
							update_option( MPCRBM_SecureHold_Compat::GLOBAL_OPTION, '0' );
							$done = true;
						}
						break;
					case 'woocommerce_mode':
						// Same rule as the Payments tab: only a real two-way choice can be changed.
						if ( 'both' === MPCRBM_Booking_Mode::availability() ) {
							MPCRBM_Booking_Mode::set_mode( MPCRBM_Booking_Mode::WOOCOMMERCE );
							$done = true;
						}
						break;
				}

				wp_safe_redirect( add_query_arg( 'mpcrbm_integration', $done ? 'done' : 'failed', $this->settings_url() ) );
				exit;
			}

			/**
			 * Installed / active / version of a plugin.
			 *
			 * @param string $file Plugin file relative to the plugins directory.
			 * @return array{installed:bool,active:bool,version:string}
			 */
			private function plugin_state( $file ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				$plugins = get_plugins();

				return array(
					'installed' => isset( $plugins[ $file ] ),
					'active'    => is_plugin_active( $file ),
					'version'   => isset( $plugins[ $file ]['Version'] ) ? (string) $plugins[ $file ]['Version'] : '',
				);
			}

			/**
			 * Install, update or activate link for a wordpress.org plugin, if one is needed.
			 *
			 * @param string $file   Plugin file.
			 * @param string $slug   wordpress.org slug.
			 * @param array  $state  plugin_state() result.
			 * @param bool   $update Whether the installed version is too old.
			 * @return array{url:string,label:string}|null
			 */
			private function plugin_action( $file, $slug, $state, $update = false ) {
				if ( ! $state['installed'] && current_user_can( 'install_plugins' ) ) {
					return array(
						'url'   => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug ),
						'label' => __( 'Install', 'car-rental-manager' ),
					);
				}
				if ( $state['installed'] && $update && current_user_can( 'update_plugins' ) ) {
					return array(
						'url'   => wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $file ) ), 'upgrade-plugin_' . $file ),
						'label' => __( 'Update', 'car-rental-manager' ),
					);
				}
				if ( $state['installed'] && ! $state['active'] && current_user_can( 'activate_plugins' ) ) {
					return array(
						'url'   => wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file ),
						'label' => __( 'Activate', 'car-rental-manager' ),
					);
				}

				return null;
			}

			/** Enabled WooCommerce payment methods that are not Stripe, which get no hold. */
			private function other_enabled_gateways() {
				if ( ! function_exists( 'WC' ) || ! WC() || ! method_exists( WC(), 'payment_gateways' ) ) {
					return array();
				}
				$titles = array();
				foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
					if ( 'yes' === $gateway->enabled && false === strpos( (string) $gateway->id, 'stripe' ) ) {
						$titles[] = wp_strip_all_tags( $gateway->get_method_title() ? $gateway->get_method_title() : $gateway->get_title() );
					}
				}

				return $titles;
			}

			/** Number of published cars whose enabled deposit is a percentage, which SecureHold cannot hold. */
			private function percentage_deposit_cars() {
				$query = new WP_Query(
					array(
						'post_type'      => MPCRBM_Function::get_cpt(),
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array( 'key' => 'mpcrbm_security_deposit_enable', 'value' => 'on' ),
							array( 'key' => 'mpcrbm_security_deposit_type', 'value' => 'percentage' ),
						),
					)
				);

				return (int) $query->found_posts;
			}

			/**
			 * One status line.
			 *
			 * @param string     $label  What is checked.
			 * @param string     $state  ready | action | info.
			 * @param string     $detail Current state in words.
			 * @param array|null $action Optional {url,label,confirm?,external?}.
			 */
			private function status_row( $label, $state, $detail, $action = null ) {
				$icons = array(
					'ready'  => 'fas fa-check-circle',
					'action' => 'fas fa-exclamation-circle',
					'info'   => 'fas fa-info-circle',
				);
				?>
				<li class="mpcrbm-sh-row is-<?php echo esc_attr( $state ); ?>">
					<span class="<?php echo esc_attr( $icons[ $state ] ); ?>" aria-hidden="true"></span>
					<span class="mpcrbm-sh-row-text">
						<strong><?php echo esc_html( $label ); ?></strong>
						<span><?php echo esc_html( $detail ); ?></span>
					</span>
					<?php if ( $action ) : ?>
						<a class="mpcrbm-sh-btn<?php echo 'action' === $state ? ' is-primary' : ''; ?>" href="<?php echo esc_url( $action['url'] ); ?>"<?php echo ! empty( $action['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo ! empty( $action['confirm'] ) ? ' data-mpcrbm-confirm="' . esc_attr( $action['confirm'] ) . '"' : ''; ?>><?php echo esc_html( $action['label'] ); ?></a>
					<?php endif; ?>
				</li>
				<?php
			}

			/** The SecureHold card. */
			public function render() {
				$sh            = $this->plugin_state( MPCRBM_SecureHold_Compat::PLUGIN );
				$stripe_plugin = $this->plugin_state( self::STRIPE_PLUGIN );
				$keys          = MPCRBM_SecureHold_Compat::stripe_status();
				$woo_mode      = MPCRBM_Booking_Mode::is_woocommerce();
				$version_ready = $sh['installed'] && version_compare( $sh['version'], MPCRBM_SecureHold_Compat::MIN_VERSION, '>=' );
				$gateway_ready = $stripe_plugin['active'] && $keys['gateway_enabled'] && $keys['gateway_keys'];
				$link_ready    = $sh['active'] && $keys['securehold_keys'] && $keys['modes_match'];
				$bridge_ready  = $sh['active'] && 'yes' === get_option( MPCRBM_SecureHold_Compat::BRIDGE_OPTION, 'no' );
				$ready         = $woo_mode && $gateway_ready && $sh['active'] && $version_ready && $link_ready && $bridge_ready;
				$modes         = array(
					'test' => __( 'test', 'car-rental-manager' ),
					'live' => __( 'live', 'car-rental-manager' ),
				);
				$result        = isset( $_GET['mpcrbm_integration'] ) ? sanitize_key( wp_unslash( $_GET['mpcrbm_integration'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				?>
				<style>
				/* Scoped under ".mpcrbm-shell-body .mpcrbm-sh-card" so the card outranks the
				   plugin's blanket rules in mp_global/assets/mp_style/mpcrbm_global.css:
				   ".mpcrbm span {display:inline-block}" (breaks every flex box below) and
				   ".mpcrbm a {color:var(--color_theme)}" + "a:hover {opacity:.5}" (turned the
				   buttons' text the same blue as their background). The buttons use their own
				   class instead of WP's .button/.button-primary for the same reason. */
				.mpcrbm-shell-body .mpcrbm-sh-card{max-width:860px;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-top{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-logo{flex:0 0 44px;width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#3157b7,#1e3a8a);color:#fff;font-size:19px;line-height:1;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-logo .fas{display:block;line-height:1;margin:0;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-top h3{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin:0 0 4px;font-size:16px;line-height:1.3;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-top p{margin:0;color:#4b5563;font-size:13px;line-height:1.55;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-state{padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;line-height:1.6;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-state.is-ready{background:#dcfce7;color:#15803d;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-state.is-action{background:#fff7ed;color:#9a3412;}
				.mpcrbm-shell-body .mpcrbm-sh-card ul.mpcrbm-sh-rows{list-style:none;margin:0;padding:0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
				.mpcrbm-shell-body .mpcrbm-sh-card ul.mpcrbm-sh-rows li.mpcrbm-sh-row{display:flex;align-items:center;gap:14px;margin:0;padding:14px 18px;border-top:1px solid #f1f2f6;background:#fff;line-height:1.45;}
				.mpcrbm-shell-body .mpcrbm-sh-card ul.mpcrbm-sh-rows li.mpcrbm-sh-row:first-child{border-top:0;}
				.mpcrbm-shell-body .mpcrbm-sh-card li.mpcrbm-sh-row > span.fas{flex:0 0 auto;font-size:17px;}
				.mpcrbm-shell-body .mpcrbm-sh-card li.mpcrbm-sh-row.is-ready > span.fas{color:#16a34a;}
				.mpcrbm-shell-body .mpcrbm-sh-card li.mpcrbm-sh-row.is-action > span.fas{color:#ea580c;}
				.mpcrbm-shell-body .mpcrbm-sh-card li.mpcrbm-sh-row.is-info > span.fas{color:#3157b7;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-row-text{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:3px;font-size:13px;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-row-text strong{display:block;font-weight:600;color:#111827;}
				.mpcrbm-shell-body .mpcrbm-sh-card span.mpcrbm-sh-row-text span{display:block;color:#6b7280;}
				.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;height:auto;min-height:0;padding:7px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#1f2937;font-size:12.5px;font-weight:600;line-height:1.4;white-space:nowrap;text-decoration:none;box-shadow:none;opacity:1;}
				.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn:hover,.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn:focus{border-color:#94a3b8;background:#f8fafc;color:#111827;opacity:1;}
				.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn.is-primary{border-color:#2563eb;background:#2563eb;color:#fff;}
				.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn.is-primary:hover,.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn.is-primary:focus{border-color:#1d4ed8;background:#1d4ed8;color:#fff;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-notes{margin:16px 0 0;padding:14px 18px;background:#f8fafc;border-radius:10px;font-size:12.5px;line-height:1.6;color:#475569;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-notes p{margin:0 0 6px;font-size:12.5px;line-height:1.6;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-notes p:last-child{margin:0;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-result{margin:0 0 12px;padding:8px 12px;border-radius:8px;font-size:13px;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-result.is-done{background:#ecfdf5;color:#166534;}
				.mpcrbm-shell-body .mpcrbm-sh-card .mpcrbm-sh-result.is-failed{background:#fef2f2;color:#991b1b;}
				@media (max-width:782px){.mpcrbm-shell-body .mpcrbm-sh-card ul.mpcrbm-sh-rows li.mpcrbm-sh-row{flex-wrap:wrap;}.mpcrbm-shell-body .mpcrbm-sh-card a.mpcrbm-sh-btn{margin-left:31px;}}
				</style>
				<div class="mpcrbm-sh-card">
					<?php if ( 'done' === $result ) : ?>
						<p class="mpcrbm-sh-result is-done"><?php esc_html_e( 'Done.', 'car-rental-manager' ); ?></p>
					<?php elseif ( 'failed' === $result ) : ?>
						<p class="mpcrbm-sh-result is-failed"><?php esc_html_e( 'That step could not be completed. Check the status below and try again.', 'car-rental-manager' ); ?></p>
					<?php endif; ?>
					<div class="mpcrbm-sh-top">
						<span class="mpcrbm-sh-logo"><span class="fas fa-lock" aria-hidden="true"></span></span>
						<div>
							<h3>
								<?php esc_html_e( 'SecureHold WP', 'car-rental-manager' ); ?>
								<span class="mpcrbm-sh-state is-<?php echo $ready ? 'ready' : 'action'; ?>"><?php $ready ? esc_html_e( 'Ready', 'car-rental-manager' ) : esc_html_e( 'Setup needed', 'car-rental-manager' ); ?></span>
							</h3>
							<p><?php esc_html_e( 'Hold a car’s fixed security deposit on the customer’s card at WooCommerce checkout instead of charging it. The hold is released after the rental unless you capture it for damage, so there is nothing to refund.', 'car-rental-manager' ); ?></p>
						</div>
					</div>
					<ul class="mpcrbm-sh-rows">
						<?php
						// Booking checkout. SecureHold only works on WooCommerce orders.
						if ( $woo_mode ) {
							$this->status_row( __( 'WooCommerce checkout', 'car-rental-manager' ), 'ready', __( 'Car bookings use the WooCommerce checkout', 'car-rental-manager' ) );
						} else {
							$this->status_row(
								__( 'WooCommerce checkout', 'car-rental-manager' ),
								'action',
								MPCRBM_Booking_Mode::has_woo()
									? __( 'Car bookings use the Custom Payment checkout. SecureHold works only with WooCommerce orders, so deposits there stay in the booking total.', 'car-rental-manager' )
									: __( 'WooCommerce is not active. Car bookings use the Custom Payment checkout, where deposits stay in the booking total.', 'car-rental-manager' ),
								'both' === MPCRBM_Booking_Mode::availability()
									? array(
										'url'     => $this->step_url( 'woocommerce_mode' ),
										'label'   => __( 'Use WooCommerce checkout', 'car-rental-manager' ),
										'confirm' => __( 'Car bookings will use the WooCommerce checkout instead of the Custom Payment checkout. Continue?', 'car-rental-manager' ),
									)
									: null
							);
						}

						// WooCommerce Stripe gateway: SecureHold holds on the card paid with it.
						if ( ! $stripe_plugin['active'] ) {
							$detail = $stripe_plugin['installed'] ? __( 'Installed but not active', 'car-rental-manager' ) : __( 'Not installed', 'car-rental-manager' );
							$action = $this->plugin_action( self::STRIPE_PLUGIN, self::STRIPE_SLUG, $stripe_plugin );
						} elseif ( ! $keys['gateway_enabled'] || ! $keys['gateway_keys'] ) {
							$detail = ! $keys['gateway_enabled'] ? __( 'Enable Stripe card payments in WooCommerce', 'car-rental-manager' ) : __( 'Connect your Stripe account in WooCommerce', 'car-rental-manager' );
							$action = array(
								'url'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe' ),
								'label' => __( 'Stripe settings', 'car-rental-manager' ),
							);
						} else {
							/* translators: %s: Stripe mode, test or live. */
							$detail = sprintf( __( 'Taking card payments in %s mode', 'car-rental-manager' ), $modes[ $keys['gateway_mode'] ] );
							$action = null;
						}
						$this->status_row( __( 'WooCommerce Stripe Gateway', 'car-rental-manager' ), $gateway_ready ? 'ready' : 'action', $detail, $action );

						// SecureHold itself.
						if ( ! $sh['installed'] ) {
							$detail = __( 'Not installed', 'car-rental-manager' );
						} elseif ( ! $version_ready ) {
							/* translators: 1: installed version, 2: required version. */
							$detail = sprintf( __( 'Version %1$s installed; %2$s or later is required', 'car-rental-manager' ), $sh['version'], MPCRBM_SecureHold_Compat::MIN_VERSION );
						} elseif ( ! $sh['active'] ) {
							$detail = __( 'Installed but not active', 'car-rental-manager' );
						} else {
							/* translators: %s: installed version. */
							$detail = sprintf( __( 'Version %s active', 'car-rental-manager' ), $sh['version'] );
						}
						$this->status_row(
							__( 'SecureHold WP', 'car-rental-manager' ),
							$sh['active'] && $version_ready ? 'ready' : 'action',
							$detail,
							$this->plugin_action( MPCRBM_SecureHold_Compat::PLUGIN, MPCRBM_SecureHold_Compat::SLUG, $sh, ! $version_ready )
						);

						// SecureHold's own Stripe connection, in the gateway's mode.
						if ( ! $sh['active'] ) {
							$detail = __( 'Available once SecureHold is active', 'car-rental-manager' );
						} elseif ( ! $keys['securehold_keys'] ) {
							/* translators: %s: Stripe mode, test or live. */
							$detail = sprintf( __( 'Add your Stripe %s mode API keys in SecureHold', 'car-rental-manager' ), $modes[ $keys['securehold_mode'] ] );
						} elseif ( ! $keys['modes_match'] ) {
							$detail = sprintf(
								/* translators: 1: SecureHold Stripe mode, 2: WooCommerce Stripe mode. */
								__( 'SecureHold uses %1$s mode but the Stripe gateway uses %2$s mode; both must match', 'car-rental-manager' ),
								$modes[ $keys['securehold_mode'] ],
								$modes[ $keys['gateway_mode'] ]
							);
						} else {
							/* translators: %s: Stripe mode, test or live. */
							$detail = sprintf( __( '%s mode API keys saved', 'car-rental-manager' ), ucfirst( $modes[ $keys['securehold_mode'] ] ) );
						}
						$this->status_row(
							__( 'SecureHold Stripe connection', 'car-rental-manager' ),
							$link_ready ? 'ready' : 'action',
							$detail,
							$sh['active'] && ! $link_ready
								? array(
									'url'      => admin_url( 'admin.php?page=securehold-settings&tab=connection' ),
									'label'    => __( 'Connect Stripe in SecureHold', 'car-rental-manager' ),
									'external' => true,
								)
								: null
						);

						// SecureHold's MagePeople bridge, which car deposits go through.
						$this->status_row(
							__( 'MagePeople compatibility', 'car-rental-manager' ),
							$bridge_ready ? 'ready' : 'action',
							$bridge_ready
								? __( 'Enabled in SecureHold', 'car-rental-manager' )
								: __( 'Turn on “Use MagePeople security deposit amounts” in SecureHold', 'car-rental-manager' ),
							$sh['active'] && ! $bridge_ready
								? array(
									'url'   => $this->step_url( 'bridge' ),
									'label' => __( 'Enable compatibility', 'car-rental-manager' ),
								)
								: null
						);

						// Things that work, but not the way an admin may expect.
						if ( $sh['active'] ) {
							$global = trim( (string) get_option( MPCRBM_SecureHold_Compat::GLOBAL_OPTION, '300' ) );
							if ( (float) str_replace( '%', '', $global ) > 0 ) {
								$this->status_row(
									__( 'SecureHold default hold', 'car-rental-manager' ),
									'info',
									sprintf(
										/* translators: %s: SecureHold Default Hold Amount, e.g. "300" or "20%". */
										__( 'SecureHold also holds its default %s on every WooCommerce order without a fixed car deposit, including car bookings whose percentage deposit is already charged.', 'car-rental-manager' ),
										$global
									),
									array(
										'url'     => $this->step_url( 'global_hold_off' ),
										'label'   => __( 'Hold only car deposits', 'car-rental-manager' ),
										'confirm' => __( 'SecureHold’s Default Hold Amount will be set to 0, so it only holds fixed security deposits. Continue?', 'car-rental-manager' ),
									)
								);
							}
						}
						if ( $woo_mode && $gateway_ready ) {
							$others = $this->other_enabled_gateways();
							if ( $others ) {
								$this->status_row(
									__( 'Other payment methods', 'car-rental-manager' ),
									'info',
									sprintf(
										/* translators: %s: comma-separated payment method names. */
										__( 'Orders paid with %s get no card hold, and a held deposit is not charged on them either. Collect it before the rental; the Bookings list flags these bookings.', 'car-rental-manager' ),
										implode( ', ', $others )
									)
								);
							}
						}
						$percentage = $this->percentage_deposit_cars();
						if ( $percentage > 0 ) {
							$this->status_row(
								__( 'Percentage deposits', 'car-rental-manager' ),
								'info',
								sprintf(
									/* translators: %d: number of cars. */
									_n( '%d car uses a percentage deposit. SecureHold holds fixed amounts only, so that deposit stays in the booking total.', '%d cars use a percentage deposit. SecureHold holds fixed amounts only, so those deposits stay in the booking total.', $percentage, 'car-rental-manager' ),
									$percentage
								)
							);
						}
						?>
					</ul>
					<div class="mpcrbm-sh-notes">
						<p><?php esc_html_e( 'WooCommerce checkout: a car with a fixed security deposit shows the deposit as held on the card. It is left out of the amount paid, and SecureHold authorizes it on the same card after payment (car quantity included).', 'car-rental-manager' ); ?></p>
						<p><?php esc_html_e( 'Custom Payment checkout, percentage deposits, cars excluded in SecureHold, or Stripe not ready: the deposit is charged with the booking as before, so it is never lost.', 'car-rental-manager' ); ?></p>
						<p><?php esc_html_e( 'Holds are listed under each booking’s total on the Bookings page, with a link to capture or release them in SecureHold.', 'car-rental-manager' ); ?></p>
					</div>
				</div>
				<script>
				( function () {
					document.querySelectorAll( '.mpcrbm-sh-card [data-mpcrbm-confirm]' ).forEach( function ( link ) {
						link.addEventListener( 'click', function ( event ) {
							if ( ! window.confirm( link.getAttribute( 'data-mpcrbm-confirm' ) ) ) {
								event.preventDefault();
							}
						} );
					} );
					<?php if ( $result ) : ?>
					// Back from a setup step: reopen this tab (the settings tabs have no deep link).
					window.addEventListener( 'load', function () {
						var tab = document.querySelector( '[data-tabs-target="#<?php echo esc_js( self::SECTION ); ?>"]' );
						if ( tab && ! tab.classList.contains( 'active' ) && window.jQuery ) {
							window.jQuery( tab ).trigger( 'click' );
						}
					} );
					<?php endif; ?>
				}() );
				</script>
				<?php
			}
		}

		new MPCRBM_Integrations_Settings();
	}
