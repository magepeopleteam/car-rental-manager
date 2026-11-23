/**
 * WiPay block checkout integration for car-rental carts.
 *
 * Registers the official WiPay gateway (wipay_credit) with the WooCommerce
 * Checkout block. The server only enqueues this file when the cart contains a
 * car-rental booking and the gateway is available, so classic checkout and
 * non-car carts never load it.
 */
( function () {
	'use strict';

	var wc        = window.wc || {};
	var registry  = wc.wcBlocksRegistry;
	var settings  = wc.wcSettings;
	var element   = window.wp && window.wp.element;
	var i18n      = window.wp && window.wp.i18n;

	if ( ! registry || 'function' !== typeof registry.registerPaymentMethod || ! settings || ! element ) {
		return;
	}

	var data        = settings.getPaymentMethodData( 'wipay_credit', {} ) || {};
	var title       = data.title || ( i18n ? i18n.__( 'Credit/Debit Card (via WiPay)', 'car-rental-manager' ) : 'Credit/Debit Card (via WiPay)' );
	var description = data.description || '';

	var Label = function ( props ) {
		var PaymentMethodLabel = props && props.components ? props.components.PaymentMethodLabel : null;
		if ( PaymentMethodLabel ) {
			return element.createElement( PaymentMethodLabel, { text: title } );
		}
		return element.createElement( 'span', null, title );
	};

	var Content = function () {
		if ( ! description ) {
			return null;
		}
		return element.createElement( 'div', {
			// Sanitised server-side with wp_kses_post() before it reaches wcSettings.
			dangerouslySetInnerHTML: { __html: description },
		} );
	};

	var paymentMethod = {
		name: 'wipay_credit',
		label: element.createElement( Label, {} ),
		content: element.createElement( Content ),
		edit: element.createElement( Content ),
		ariaLabel: title,
		canMakePayment: function () {
			return true;
		},
		supports: {
			features: data.supports || [ 'products' ],
		},
	};

	registry.registerPaymentMethod( paymentMethod );
}() );
