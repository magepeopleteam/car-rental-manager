<?php
	/**
	 * Admin notices about payment readiness.
	 *
	 * Two distinct notices, deliberately kept apart because they answer different
	 * questions:
	 *
	 *  1. The SETUP notice — "bookings cannot currently be paid for". Adapts to the live
	 *     Booking Mode (see MPCRBM_Booking_Mode) so it always names the one action that
	 *     actually fixes the site's situation, instead of a generic "configure payments".
	 *     Not dismissible: an unpayable booking form is a broken storefront, not a
	 *     preference.
	 *
	 *  2. The PRO notice — a dismissible upsell shown only once the site is already
	 *     working in Custom Payment mode without Pro. It never competes with the setup
	 *     notice: an admin who can't take bookings at all should not be sold an upgrade.
	 *
	 * Both are scoped to this plugin's own admin screens. A payment problem here is not
	 * the concern of someone writing a blog post, and nagging across all of wp-admin is
	 * what makes plugin notices hated.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_Payment_Notices' ) ) :
		class MPCRBM_Payment_Notices {

			/** User meta flag set when the Pro upsell is dismissed. */
			const PRO_NOTICE_DISMISSED = 'mpcrbm_pro_payment_notice_dismissed';

			public function __construct() {
				add_action( 'admin_notices', array( $this, 'render_notices' ) );
				add_action( 'wp_ajax_mpcrbm_dismiss_pro_payment_notice', array( $this, 'ajax_dismiss_pro_notice' ) );
			}

			/* --------------------------------------------------------------
			 * Scope
			 * ------------------------------------------------------------ */

			/**
			 * Only this plugin's own screens: its dashboard-style pages, the car list, and
			 * the car add/edit screen.
			 */
			private function is_our_screen(): bool {
				if ( ! function_exists( 'get_current_screen' ) || ! class_exists( 'MPCRBM_Function' ) ) {
					return false;
				}
				$screen = get_current_screen();
				if ( ! $screen ) {
					return false;
				}
				if ( class_exists( 'MPCRBM_Admin_Shell' ) && ( MPCRBM_Admin_Shell::is_plugin_screen() || MPCRBM_Admin_Shell::is_metabox_screen() ) ) {
					return true;
				}

				return isset( $screen->post_type ) && $screen->post_type === MPCRBM_Function::get_cpt();
			}

			private function payments_url(): string {
				$cpt = MPCRBM_Function::get_cpt();

				return admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_settings_page&mpcrbm_tab=payments' );
			}

			/** Filterable upgrade destination shown on the Pro upsell. */
			private function upgrade_url(): string {
				return apply_filters( 'mpcrbm_pro_upgrade_url', 'https://mage-people.com/product/car-rental-manager/' );
			}

			/* --------------------------------------------------------------
			 * Rendering
			 * ------------------------------------------------------------ */

			public function render_notices() {
				if ( ! current_user_can( 'manage_options' ) || ! $this->is_our_screen() ) {
					return;
				}
				if ( ! class_exists( 'MPCRBM_Booking_Mode' ) ) {
					return;
				}

				// The setup notice wins outright — see the class docblock.
				if ( $this->render_setup_notice() ) {
					return;
				}
				$this->render_pro_notice();
			}

			/**
			 * "Bookings cannot be paid for yet" — adaptive per booking mode.
			 *
			 * @return bool True when a notice was printed (so the upsell stands down).
			 */
			private function render_setup_notice(): bool {
				$needs_choice = MPCRBM_Booking_Mode::needs_selection();
				$has_gateway  = MPCRBM_Booking_Mode::has_gateway_for_active_mode();

				// Everything is configured and a mode is chosen: nothing to say.
				if ( ! $needs_choice && $has_gateway ) {
					return false;
				}

				if ( $needs_choice ) {
					$title = __( 'Choose how bookings are paid for', 'car-rental-manager' );
					$body  = __( 'Both WooCommerce and the built-in Custom Payment checkout are available. Pick one as your Booking Mode so the two flows never both try to handle the same booking.', 'car-rental-manager' );
					$cta   = __( 'Choose Booking Mode', 'car-rental-manager' );
				} elseif ( MPCRBM_Booking_Mode::is_woocommerce() ) {
					$title = __( 'No WooCommerce payment gateway is enabled', 'car-rental-manager' );
					$body  = __( 'Your Booking Mode is WooCommerce, but every WooCommerce payment gateway is currently disabled — customers cannot complete a rental booking. Enable at least one gateway to start taking payments.', 'car-rental-manager' );
					$cta   = __( 'Enable a Payment Gateway', 'car-rental-manager' );
				} else {
					$title = __( 'No payment method is enabled', 'car-rental-manager' );
					$body  = class_exists( 'MPCRBM_Plugin_Pro' )
						? __( 'Your Booking Mode is Custom Payment, but none of PayPal, Stripe or Offline Payment is enabled — customers cannot complete a rental booking. Enable at least one gateway to start taking payments.', 'car-rental-manager' )
						: __( 'Your Booking Mode is Custom Payment, but no gateway is enabled — customers cannot complete a rental booking. Enable Offline Payment (included free) to accept bank transfer, cash, or pay-on-pickup bookings.', 'car-rental-manager' );
					$cta   = __( 'Enable a Payment Method', 'car-rental-manager' );
				}

				$this->print_notice_styles();
				?>
				<div class="notice mpcrbm-pay-notice mpcrbm-pay-notice--setup">
					<div class="mpcrbm-pay-notice-icon" aria-hidden="true">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="mpcrbm-pay-notice-body">
						<h3><?php echo esc_html( $title ); ?></h3>
						<p><?php echo esc_html( $body ); ?></p>
					</div>
					<div class="mpcrbm-pay-notice-actions">
						<a class="mpcrbm-pay-notice-btn" href="<?php echo esc_url( $this->payments_url() ); ?>">
							<?php echo esc_html( $cta ); ?>
						</a>
					</div>
				</div>
				<?php

				return true;
			}

			/** Dismissible "PayPal & Stripe are in Pro" upsell for a working Custom Payment site. */
			private function render_pro_notice() {
				if ( class_exists( 'MPCRBM_Plugin_Pro' ) ) {
					return;
				}
				if ( ! MPCRBM_Booking_Mode::is_custom() ) {
					return;
				}
				if ( get_user_meta( get_current_user_id(), self::PRO_NOTICE_DISMISSED, true ) ) {
					return;
				}

				$this->print_notice_styles();
				?>
				<div class="notice mpcrbm-pay-notice mpcrbm-pay-notice--pro" data-mpcrbm-pro-notice
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'mpcrbm_dismiss_pro_payment_notice' ) ); ?>">
					<div class="mpcrbm-pay-notice-icon mpcrbm-pay-notice-icon--pro" aria-hidden="true">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<div class="mpcrbm-pay-notice-body">
						<h3><?php esc_html_e( 'Take card payments online', 'car-rental-manager' ); ?></h3>
						<p><?php esc_html_e( 'Offline Payment is working — customers can book and settle on pickup. Car Rental Manager Pro adds PayPal and Stripe to the same Custom Payment checkout, so rentals can be paid for upfront without WooCommerce.', 'car-rental-manager' ); ?></p>
					</div>
					<div class="mpcrbm-pay-notice-actions">
						<a class="mpcrbm-pay-notice-btn mpcrbm-pay-notice-btn--pro" href="<?php echo esc_url( $this->upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'See Pro Features', 'car-rental-manager' ); ?>
						</a>
					</div>
					<button type="button" class="notice-dismiss mpcrbm-pay-notice-dismiss">
						<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'car-rental-manager' ); ?></span>
					</button>
				</div>
				<script>
				jQuery(function($){
					$(document).on('click', '.mpcrbm-pay-notice-dismiss', function(){
						var $notice = $(this).closest('[data-mpcrbm-pro-notice]');
						$notice.fadeOut(200);
						$.post(ajaxurl, {
							action: 'mpcrbm_dismiss_pro_payment_notice',
							nonce: $notice.data('nonce')
						});
					});
				});
				</script>
				<?php
			}

			/** Shared notice styling, printed at most once per request. */
			private function print_notice_styles() {
				static $printed = false;
				if ( $printed ) {
					return;
				}
				$printed = true;
				?>
				<style>
				.mpcrbm-pay-notice{position:relative;display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding:18px 22px;margin:16px 20px 16px 2px;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-left:4px solid var(--mpcrbm-shell-primary,#667eea);border-radius:var(--mpcrbm-shell-radius,16px);background:#fff;box-shadow:0 5px 15px -5px rgba(115,125,146,.11),0 1px 2px 0 rgba(160,170,185,.6);}
				.mpcrbm-pay-notice--setup{border-left-color:#f59e0b;background:linear-gradient(180deg,#fffdf7,#fff);}
				.mpcrbm-pay-notice--pro{border-left-color:#667eea;background:linear-gradient(180deg,#f7f8ff,#fff);padding-right:44px;}
				.mpcrbm-pay-notice-icon{flex:0 0 auto;width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#fff2cf;color:#c07d16;border:1px solid #f2d484;}
				.mpcrbm-pay-notice-icon .dashicons{font-size:22px;width:22px;height:22px;}
				.mpcrbm-pay-notice-icon--pro{background:#eef1fe;color:#667eea;border-color:#d5dbfb;}
				.mpcrbm-pay-notice-body{flex:1 1 340px;min-width:0;}
				.mpcrbm-pay-notice-body h3{margin:0 0 5px;font-size:15px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);line-height:1.35;}
				.mpcrbm-pay-notice-body p{margin:0;font-size:13px;line-height:1.6;color:var(--mpcrbm-shell-text-faded,#788291);max-width:760px;}
				.mpcrbm-pay-notice-actions{flex:0 0 auto;}
				.mpcrbm-pay-notice-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 20px;border-radius:var(--mpcrbm-shell-radius-xs,8px);background:#f59e0b;color:#fff !important;font-size:13.5px;font-weight:600;text-decoration:none;line-height:38px;box-shadow:0 2px 6px rgba(245,158,11,.3);transition:transform .15s ease,box-shadow .15s ease,background .15s ease;}
				.mpcrbm-pay-notice-btn:hover{background:#d97f07;transform:translateY(-1px);box-shadow:0 5px 14px rgba(245,158,11,.34);color:#fff !important;}
				.mpcrbm-pay-notice-btn--pro{background:var(--mpcrbm-shell-primary,#667eea);box-shadow:0 2px 6px rgba(102,126,234,.3);}
				.mpcrbm-pay-notice-btn--pro:hover{background:#5568d3;box-shadow:0 5px 14px rgba(102,126,234,.34);}
				.mpcrbm-pay-notice-dismiss{top:12px;right:8px;}
				@media (max-width:782px){.mpcrbm-pay-notice{margin-right:12px;}.mpcrbm-pay-notice-actions{flex:1 1 100%;}.mpcrbm-pay-notice-btn{width:100%;justify-content:center;}}
				</style>
				<?php
			}

			/* --------------------------------------------------------------
			 * AJAX
			 * ------------------------------------------------------------ */

			/**
			 * Dismissal is stored per user, not site-wide: one admin hiding an upsell
			 * shouldn't silently hide it from their colleagues.
			 */
			public function ajax_dismiss_pro_notice() {
				check_ajax_referer( 'mpcrbm_dismiss_pro_payment_notice', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( __( 'Permission denied.', 'car-rental-manager' ), 403 );
				}
				update_user_meta( get_current_user_id(), self::PRO_NOTICE_DISMISSED, 1 );
				wp_send_json_success();
			}
		}

		new MPCRBM_Payment_Notices();
	endif;
