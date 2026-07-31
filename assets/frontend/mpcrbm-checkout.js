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
