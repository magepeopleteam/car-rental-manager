<?php
	/**
	 * Single source of truth for "which flow processes a booking right now: WooCommerce
	 * or the custom/standalone checkout?"
	 *
	 * The plugin used to hard-require WooCommerce, so there was nothing to decide. Now
	 * that the standalone Offline checkout ships in the free plugin, two flows can both
	 * be present at once — and without one explicit switch they would race: an admin
	 * could enable Offline while WooCommerce quietly kept taking every booking. This
	 * class, and the Booking Mode selector in
	 * admin/settings/MPCRBM_Payment_Settings.php that writes to it, make that choice
	 * explicit and give both plugins one flag to read.
	 *
	 * Both frontend/MPCRBM_Woocommerce.php (free) and MPCRBM_Dependencies_Pro.php (Pro)
	 * gate on get_mode() so they always agree on who owns a given booking.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_Booking_Mode' ) ) {
		class MPCRBM_Booking_Mode {

			const OPTION = 'mpcrbm_payment_settings';
			const KEY    = 'mpcrbm_booking_mode';

			const WOOCOMMERCE = 'woocommerce';
			const CUSTOM      = 'custom';

			/** Legacy on/off key this class migrates away from (see get_stored_mode()). */
			const LEGACY_KEY = 'mpcrbm_enable_wc_payment';

			public static function has_woo() {
				return class_exists( 'MPCRBM_Global_Function' ) && MPCRBM_Global_Function::check_woocommerce() === 1;
			}

			/** The Pro plugin adds PayPal, Stripe, the customer portal and the richer checkout. */
			public static function has_pro() {
				return class_exists( 'MPCRBM_Plugin_Pro' );
			}

			/**
			 * Whether the custom/standalone flow exists on this site.
			 *
			 * Custom Payment is a FREE capability because the core plugin ships the
			 * standalone Offline checkout. Gateway readiness is deliberately checked
			 * separately by has_gateway_for_active_mode(): requiring Offline to already be
			 * enabled here would hide the very controls needed to enable it.
			 *
			 * PayPal and Stripe stay Pro-only at their individual gateway boundaries.
			 */
			public static function has_custom() {
				return true;
			}

			/**
			 * Whether a real choice exists at all. When only one side is available there is
			 * nothing to choose — the mode is simply whichever one can run.
			 *
			 * @return string 'both' | 'woocommerce_only' | 'custom_only' | 'none'
			 */
			public static function availability() {
				$woo    = self::has_woo();
				$custom = self::has_custom();
				if ( $woo && $custom ) {
					return 'both';
				}
				if ( $woo ) {
					return 'woocommerce_only';
				}
				if ( $custom ) {
					return 'custom_only';
				}

				return 'none';
			}

			/**
			 * The admin's saved choice, or '' if they have never made one. Transparently
			 * migrates the old on/off checkbox the first time it is read, so upgrading
			 * sites keep behaving exactly as before until the admin actively changes it.
			 *
			 * @return string self::WOOCOMMERCE | self::CUSTOM | ''
			 */
			public static function get_stored_mode() {
				$opts = get_option( self::OPTION, array() );
				$opts = is_array( $opts ) ? $opts : array();

				if ( ! empty( $opts[ self::KEY ] ) && in_array( $opts[ self::KEY ], array( self::WOOCOMMERCE, self::CUSTOM ), true ) ) {
					return $opts[ self::KEY ];
				}

				if ( isset( $opts[ self::LEGACY_KEY ] ) ) {
					$migrated          = ( 'off' === $opts[ self::LEGACY_KEY ] ) ? self::CUSTOM : self::WOOCOMMERCE;
					$opts[ self::KEY ] = $migrated;
					update_option( self::OPTION, $opts );

					return $migrated;
				}

				return '';
			}

			/** True only when there is a real choice to make and the admin has not made it yet. */
			public static function needs_selection() {
				return 'both' === self::availability() && '' === self::get_stored_mode();
			}

			/**
			 * The mode actually in effect. This is what booking-flow gates must call.
			 *
			 * @return string self::WOOCOMMERCE | self::CUSTOM | '' (nothing can process a booking)
			 */
			public static function get_mode() {
				switch ( self::availability() ) {
					case 'woocommerce_only':
						return self::remember( self::WOOCOMMERCE );
					case 'custom_only':
						return self::remember( self::CUSTOM );
					case 'none':
						return '';
					case 'both':
					default:
						// Safe default (matches the plugin's historic WooCommerce-only
						// behaviour) until an explicit choice is saved.
						return self::get_stored_mode() ?: self::WOOCOMMERCE;
				}
			}

			/**
			 * Record a mode that was auto-resolved because it was the ONLY flow available.
			 *
			 * Without this, a site running one flow has nothing stored, so the day the
			 * other flow becomes available two things go wrong: the admin is nagged to
			 * choose a mode they effectively already had, and — worse — the 'both'
			 * fallback silently hands bookings to WooCommerce. A site taking Offline
			 * bookings would have its checkout hijacked just by activating WooCommerce.
			 *
			 * Only ever fills a blank: an explicit choice (or an earlier auto-resolution)
			 * is never overwritten, so deactivating a flow temporarily doesn't erase intent.
			 *
			 * @return string The mode passed in, so callers can return it directly.
			 */
			private static function remember( $mode ) {
				if ( '' === self::get_stored_mode() ) {
					self::set_mode( $mode );
				}

				return $mode;
			}

			public static function is_woocommerce() {
				return self::WOOCOMMERCE === self::get_mode();
			}

			public static function is_custom() {
				return self::CUSTOM === self::get_mode();
			}

			/** Persist an explicit choice. Only meaningful when availability() === 'both'. */
			public static function set_mode( $mode ) {
				if ( ! in_array( $mode, array( self::WOOCOMMERCE, self::CUSTOM ), true ) ) {
					return false;
				}
				$opts              = get_option( self::OPTION, array() );
				$opts              = is_array( $opts ) ? $opts : array();
				$opts[ self::KEY ] = $mode;

				return update_option( self::OPTION, $opts );
			}

			/**
			 * Does the currently-active mode actually have a usable payment method?
			 * Reuses the provider counts MPCRBM_Payment_Status_Checker already computes —
			 * no new gateway-counting logic.
			 */
			public static function has_gateway_for_active_mode() {
				if ( ! class_exists( 'MPCRBM_Payment_Status_Checker' ) ) {
					return true; // Fail open — the generic notice still catches a total absence.
				}
				if ( self::is_woocommerce() ) {
					return MPCRBM_Payment_Status_Checker::get_enabled_woocommerce_gateway_count() > 0;
				}
				if ( self::is_custom() ) {
					return MPCRBM_Payment_Status_Checker::get_enabled_pro_payment_method_count() > 0;
				}

				return false;
			}
		}
	}
