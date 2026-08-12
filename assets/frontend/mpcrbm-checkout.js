/* global mpcrbmCheckout, jQuery */
/**
 * Standalone (Custom Payment) checkout submit.
 *
 * The whole booking — vehicle, dates, extras and price — was already computed and
 * parked server-side when "Book Now" was clicked, keyed by the token in this form's
 * data attribute. This file therefore posts only the customer's own details: the
 * price is never sent from the browser and so can never be tampered with.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mpcrbmCheckout || {};
	var i18n = cfg.i18n || {};

	$( function () {
		var $form = $( '#mpcrbm-checkout-form' );
		if ( ! $form.length ) {
			return;
		}

		var $submit = $form.find( '.mpcrbm-checkout-submit' );
		var $error = $form.find( '#mpcrbm-checkout-error' );
		var $emailInput = $form.find( '#mpcrbm_co_email' );
		var $couponBlock = $form.find( '#mpcrbm-coupon-block' );
		var $couponInput = $form.find( '#mpcrbm_co_coupon' );
		var $couponCheck = $form.find( '#mpcrbm-coupon-check' );
		var $couponResult = $form.find( '#mpcrbm-coupon-result' );
		var couponAvailabilityXhr = null;

		// Free ships Offline only and posts to its own endpoint; Pro offers every enabled
		// gateway and posts to its. The form declares which one it wants, so this single
		// file serves both without either plugin needing its own copy.
		var endpoint = $form.data( 'action' ) || 'mpcrbm_offline_place_order';

		// Highlight the chosen payment method. Free renders one non-interactive card, Pro
		// renders a radio per gateway — this is a no-op in the former.
		$form.on( 'change', '.mpcrbm-pay-methods input[name="gateway"]', function () {
			$form.find( '.mpcrbm-pay-method' ).removeClass( 'is-selected' );
			$( this ).closest( '.mpcrbm-pay-method' ).addClass( 'is-selected' );
		} );

		function showError( message ) {
			$error.text( message ).prop( 'hidden', false );
			// Scroll the message into view: on a long form the button can be far below
			// the fold, so an error rendered in place would go unseen.
			if ( $error.get( 0 ) && $error.get( 0 ).scrollIntoView ) {
				$error.get( 0 ).scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}

		// The coupon field starts hidden — most customers were never given a code, so
		// an always-visible box just invites guessing. It only appears once the typed
		// email is confirmed (server-side) to have one waiting.
		function checkCouponAvailability() {
			if ( ! $couponBlock.length ) {
				return;
			}
			var email = $.trim( $emailInput.val() );
			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				$couponBlock.prop( 'hidden', true );
				return;
			}
			if ( couponAvailabilityXhr ) {
				couponAvailabilityXhr.abort();
			}
			couponAvailabilityXhr = $.post( cfg.ajaxUrl, {
				action: 'mpcrbm_coupon_availability',
				nonce: cfg.nonces && cfg.nonces.couponAvailability,
				email: email
			} ).done( function ( res ) {
				var has = !! ( res && res.success && res.data && res.data.has_coupon );
				$couponBlock.prop( 'hidden', ! has );
				if ( ! has ) {
					$couponInput.val( '' );
					$couponResult.text( '' ).removeClass( 'is-success is-error' );
				}
			} );
		}

		if ( $couponBlock.length ) {
			$emailInput.on( 'blur change', checkCouponAvailability );
			// A logged-in customer's email is often pre-filled from their account, so
			// they may never trigger blur/change on it — check once up front too.
			checkCouponAvailability();

			$couponCheck.on( 'click', function () {
				var code = $.trim( $couponInput.val() );
				if ( ! code ) {
					$couponResult.text( i18n.couponEnterCode || 'Enter a coupon code first.' )
						.removeClass( 'is-success' ).addClass( 'is-error' );
					return;
				}
				$couponCheck.prop( 'disabled', true );
				$couponResult.text( i18n.couponChecking || 'Checking…' ).removeClass( 'is-success is-error' );
				$.post( cfg.ajaxUrl, {
					action: 'mpcrbm_coupon_check',
					nonce: cfg.nonces && cfg.nonces.couponCheck,
					token: $form.data( 'token' ),
					email: $emailInput.val(),
					code: code
				} ).done( function ( res ) {
					if ( res && res.success && res.data ) {
						$couponResult.text( res.data.message ).removeClass( 'is-error' ).addClass( 'is-success' );
					} else {
						$couponResult.text( ( res && res.data && res.data.message ) || i18n.error || 'Something went wrong.' )
							.removeClass( 'is-success' ).addClass( 'is-error' );
					}
				} ).fail( function () {
					$couponResult.text( i18n.error || 'Something went wrong.' ).removeClass( 'is-success' ).addClass( 'is-error' );
				} ).always( function () {
					$couponCheck.prop( 'disabled', false );
				} );
			} );

			// Enter inside the coupon field would otherwise submit the whole form (the
			// Place Booking button is the form's default submit control) — run the
			// coupon check instead, since that's what the customer meant to trigger.
			$couponInput.on( 'keydown', function ( e ) {
				if ( 13 === e.which ) {
					e.preventDefault();
					$couponCheck.trigger( 'click' );
				}
			} );
		}

		$form.on( 'submit', function ( e ) {
			e.preventDefault();

			// Let the browser's own required/type validation speak first — it points at
			// the offending field, which a single summary message cannot.
			if ( this.checkValidity && ! this.checkValidity() ) {
				this.reportValidity();
				return;
			}

			$error.prop( 'hidden', true ).text( '' );
			$submit.prop( 'disabled', true ).text( i18n.placing || 'Placing your booking…' );

			$.post( cfg.ajaxUrl, {
				action: endpoint,
				nonce: $form.data( 'nonce' ),
				token: $form.data( 'token' ),
				first_name: $form.find( '[name="first_name"]' ).val(),
				last_name: $form.find( '[name="last_name"]' ).val(),
				email: $form.find( '[name="email"]' ).val(),
				phone: $form.find( '[name="phone"]' ).val(),
				note: $form.find( '[name="note"]' ).val(),
				coupon_code: $form.find( '[name="coupon_code"]' ).val(),
				gateway: $form.find( '[name="gateway"]:checked' ).val() || 'offline'
			} ).done( function ( res ) {
				if ( res && res.success && res.data && res.data.redirect ) {
					// Deliberately no re-enable here: the booking is placed and the page
					// is navigating away. Re-enabling would invite a double submission
					// during the redirect.
					window.location.href = res.data.redirect;
					return;
				}
				showError( ( res && res.data && res.data.message ) || i18n.error || 'Something went wrong.' );
				$submit.prop( 'disabled', false ).text( i18n.submit || 'Place Booking' );
			} ).fail( function () {
				showError( i18n.error || 'Something went wrong.' );
				$submit.prop( 'disabled', false ).text( i18n.submit || 'Place Booking' );
			} );
		} );
	} );
} )( jQuery );
