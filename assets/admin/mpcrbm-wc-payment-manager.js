/* global mpcrbmWcPaymentManager, jQuery */
/**
 * WooCommerce Payment Methods manager (Payments → WooCommerce).
 *
 * Expands each gateway's native WooCommerce settings form inline and saves it over
 * AJAX through the gateway's own process_admin_options(). The markup lives inside the
 * Settings API <form>, so the per-gateway container is a <div> — this file serializes
 * it by hand rather than relying on a nested (invalid) form submit.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mpcrbmWcPaymentManager || {};
	var i18n = cfg.i18n || {};

	function ajax( data ) {
		return $.ajax( {
			url: cfg.ajaxUrl,
			method: 'POST',
			data: $.extend( { nonce: cfg.nonce }, data )
		} );
	}

	// Bottom-right confirmation toast, so an AJAX-saved setting always gets an
	// unmissable confirmation instead of a silent badge change or a blocking alert().
	var toastIcons = {
		success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
		error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
	};

	function showToast( type, message ) {
		var $toast = $( '<div/>', { 'class': 'mpcrbm_toast mpcrbm_toast_' + type } );
		$( '<span/>', { 'class': 'mpcrbm_toast_icon' } ).html( toastIcons[ type ] || '' ).appendTo( $toast );
		$( '<span/>', { 'class': 'mpcrbm_toast_msg', text: message } ).appendTo( $toast );
		$( 'body' ).append( $toast );
		window.requestAnimationFrame( function () {
			$toast.addClass( 'is-show' );
		} );
		setTimeout( function () {
			$toast.removeClass( 'is-show' );
			setTimeout( function () {
				$toast.remove();
			}, 300 );
		}, 2600 );
	}

	function applyEnabledState( $card, isOn ) {
		$card.toggleClass( 'is-enabled', isOn ).toggleClass( 'is-disabled', ! isOn );
		$card.find( '.mpcrbm-gw-badge' ).text( isOn ? ( i18n.enabled || 'Enabled' ) : ( i18n.disabled || 'Disabled' ) );
	}

	$( function () {
		var $manager = $( '.mpcrbm-wc-payment-manager' ).first();
		if ( ! $manager.length ) {
			return;
		}

		// Tag the manager's settings-table row with `woocommerce-field` so the Payments
		// tab's existing show/hide logic manages its visibility along with the rest of
		// the WooCommerce-only fields.
		if ( ! $manager.data( 'mpcrbm-relocated' ) ) {
			$manager.closest( 'tr' ).addClass( 'woocommerce-field' );
			$manager.data( 'mpcrbm-relocated', true );
		}

		// -----------------------------------------------------------
		// Expand / collapse a gateway's native settings form
		// -----------------------------------------------------------
		$manager.on( 'click', '.mpcrbm-gw-configure-btn', function () {
			var $card = $( this ).closest( '.mpcrbm-gw-card' );
			var $body = $card.find( '.mpcrbm-gw-body' );
			var open = $body.is( ':visible' );

			$manager.find( '.mpcrbm-gw-body' ).slideUp( 150 );
			$manager.find( '.mpcrbm-gw-configure-btn' ).text( i18n.configure || 'Configure' );

			if ( ! open ) {
				$body.slideDown( 150 );
				$( this ).text( i18n.close || 'Close' );
			}
		} );

		// -----------------------------------------------------------
		// Quick enable/disable toggle in the card header
		// -----------------------------------------------------------
		$manager.on( 'change', '.mpcrbm-gw-toggle-input', function () {
			var $input = $( this );
			var $card = $input.closest( '.mpcrbm-gw-card' );
			var gatewayId = $input.data( 'gateway-id' );
			var gatewayTitle = $card.find( '.mpcrbm-gw-title' ).text();
			var enabled = $input.is( ':checked' ) ? 'yes' : 'no';

			$input.prop( 'disabled', true );

			ajax( {
				action: 'mpcrbm_wc_toggle_gateway',
				gateway_id: gatewayId,
				enabled: enabled
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						var isOn = res.data.enabled === 'yes';
						applyEnabledState( $card, isOn );
						showToast( 'success', gatewayTitle + ' ' + ( isOn ? ( i18n.enabled || 'Enabled' ) : ( i18n.disabled || 'Disabled' ) ) + '.' );
						$( document ).trigger( 'mpcrbm:wc-gateways-changed' );
					} else {
						$input.prop( 'checked', ! $input.is( ':checked' ) );
						showToast( 'error', ( res && res.data ) || i18n.error );
					}
				} )
				.fail( function () {
					$input.prop( 'checked', ! $input.is( ':checked' ) );
					showToast( 'error', i18n.error );
				} )
				.always( function () {
					$input.prop( 'disabled', false );
				} );
		} );

		// -----------------------------------------------------------
		// Save a gateway's native settings (process_admin_options).
		// Bound to the button click — the container is a <div>, not a <form>.
		// -----------------------------------------------------------
		$manager.on( 'click', '.mpcrbm-gw-save-btn', function ( e ) {
			e.preventDefault();

			var $btn = $( this );
			var $form = $btn.closest( '.mpcrbm-gw-form' );
			var $card = $btn.closest( '.mpcrbm-gw-card' );
			var gatewayId = $form.data( 'gateway-id' );
			var $status = $form.find( '.mpcrbm-gw-status' );

			// Native WC field names are woocommerce_{id}_{field}; submit them as-is.
			var payload = { action: 'mpcrbm_wc_save_gateway', gateway_id: gatewayId };
			$.each( $form.find( ':input' ).serializeArray(), function ( i, f ) {
				payload[ f.name ] = f.value;
			} );

			$btn.prop( 'disabled', true );
			$status.removeClass( 'is-success is-error' ).text( i18n.saving || 'Saving…' );

			ajax( payload )
				.done( function ( res ) {
					if ( res && res.success ) {
						$status.addClass( 'is-success' ).text( res.data.message || i18n.saved );
						applyEnabledState( $card, res.data.enabled === 'yes' );
						$card.find( '.mpcrbm-gw-toggle-input' ).prop( 'checked', res.data.enabled === 'yes' );
						$( document ).trigger( 'mpcrbm:wc-gateways-changed' );
						setTimeout( function () {
							$status.removeClass( 'is-success' ).text( '' );
						}, 2500 );
					} else {
						$status.addClass( 'is-error' ).text( ( res && res.data ) || i18n.error );
					}
				} )
				.fail( function () {
					$status.addClass( 'is-error' ).text( i18n.error );
				} )
				.always( function () {
					$btn.prop( 'disabled', false );
				} );
		} );

		// Initialise WC enhanced selects / tooltips inside the forms.
		try {
			if ( $.fn.selectWoo ) {
				$manager.find( 'select.wc-enhanced-select' ).selectWoo();
			} else if ( $.fn.select2 ) {
				$manager.find( 'select.wc-enhanced-select' ).select2();
			}
			$( document.body ).trigger( 'init_tooltips' );
		} catch ( err ) {
			/* non-fatal — fields still work as plain inputs */
		}
	} );
} )( jQuery );
