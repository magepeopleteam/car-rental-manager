/**
 * "Request a Better Price" button injected next to the Total on the
 * WooCommerce Checkout block. Only enqueued on the Checkout page, and only
 * when the admin has turned the feature on (see MPCRBM_Quote_Requests::
 * enqueue_checkout_assets()).
 *
 * The Checkout block renders asynchronously via React, not server-side PHP —
 * classic `woocommerce_review_order_after_order_total`-style action hooks
 * never fire inside it. So this waits for its rendered order-summary total
 * row (".wc-block-components-totals-footer-item", a stable WooCommerce
 * Blocks class) to appear, then plugs a plain button in after it. If that
 * class is ever renamed by a WooCommerce update, this simply never finds its
 * target and stays a no-op — it does not touch anything else on the page.
 */
(function ($) {
	'use strict';

	if (typeof mpcrbmRfqCheckout === 'undefined') {
		return;
	}

	var i18n = mpcrbmRfqCheckout.i18n || {};
	var injected = false;

	function buildForm() {
		var $wrap = $('<div class="mpcrbm-rfq-checkout-wrap" style="margin-top:10px;"></div>');
		var $btn = $('<button type="button" class="mpcrbm-rfq-checkout-btn" style="width:100%;padding:10px;border:1px dashed #999;background:transparent;border-radius:6px;cursor:pointer;"></button>')
			.text(i18n.button || 'Request a Better Price');

		var $panel = $('<div class="mpcrbm-rfq-checkout-panel" style="display:none;margin-top:10px;padding:14px;border:1px solid #e5e9f0;border-radius:8px;background:#fafbfc;"></div>');
		$panel.append('<p style="margin:0 0 10px;font-size:13px;color:#555;">' + (i18n.intro || '') + '</p>');

		function field(labelText, id, type) {
			var $f = $('<div style="margin-bottom:10px;"></div>');
			$f.append($('<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;"></label>').text(labelText));
			$f.append($('<input>').attr({ type: type, id: id }).css({ width: '100%', padding: '8px 10px', border: '1px solid #d1d5db', borderRadius: '6px', boxSizing: 'border-box' }));
			return $f;
		}

		var $name  = field(i18n.nameLabel || 'Your Name', 'mpcrbm_rfq_co_name', 'text');
		var $email = field(i18n.emailLabel || 'Email', 'mpcrbm_rfq_co_email', 'email');
		var $phone = field(i18n.phoneLabel || 'Phone', 'mpcrbm_rfq_co_phone', 'text');
		var $price = field(i18n.priceLabel || 'Your Proposed Price', 'mpcrbm_rfq_co_price', 'number');
		$price.find('input').attr({ min: '0', step: '0.01' });

		var $note = $('<div style="margin-bottom:10px;"></div>');
		$note.append($('<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;"></label>').text(i18n.noteLabel || 'Note (optional)'));
		$note.append($('<textarea id="mpcrbm_rfq_co_note" rows="2"></textarea>').css({ width: '100%', padding: '8px 10px', border: '1px solid #d1d5db', borderRadius: '6px', boxSizing: 'border-box' }));

		var $result = $('<div class="mpcrbm-rfq-checkout-result" style="margin:10px 0;font-size:13px;"></div>');

		var $actions = $('<div style="display:flex;gap:8px;justify-content:flex-end;"></div>');
		var $cancel = $('<button type="button" style="padding:8px 14px;border-radius:6px;border:1px solid #d1d5db;background:#fff;cursor:pointer;"></button>').text(i18n.cancel || 'Cancel');
		var $send   = $('<button type="button" class="mpcrbm-rfq-checkout-send" style="padding:8px 14px;border-radius:6px;border:none;background:#111827;color:#fff;cursor:pointer;"></button>').text(i18n.send || 'Send Request');
		$actions.append($cancel, $send);

		$panel.append($name, $email, $phone, $price, $note, $result, $actions);

		$cancel.on('click', function () {
			$panel.slideUp(150);
		});

		$send.on('click', function () {
			var name  = $('#mpcrbm_rfq_co_name').val();
			var email = $('#mpcrbm_rfq_co_email').val();
			var price = $('#mpcrbm_rfq_co_price').val();

			if (!name || !email) {
				$result.css('color', '#b91c1c').text(i18n.enterNameEmail || 'Please enter your name and email.');
				return;
			}
			if (!price || parseFloat(price) <= 0) {
				$result.css('color', '#b91c1c').text(i18n.enterPrice || 'Please enter your proposed price.');
				return;
			}

			$send.prop('disabled', true);
			$result.css('color', '#555').text(i18n.sending || 'Sending…');

			$.post(mpcrbmRfqCheckout.ajaxUrl, {
				action: 'mpcrbm_submit_quote_request',
				nonce: mpcrbmRfqCheckout.nonce,
				name: name,
				email: email,
				phone: $('#mpcrbm_rfq_co_phone').val(),
				requested_price: price,
				note: $('#mpcrbm_rfq_co_note').val()
			}, function (r) {
				$send.prop('disabled', false);
				if (r && r.success) {
					$result.css('color', '#166534').text(r.data.message);
					$name.add($email).add($phone).add($price).add($note).find('input,textarea').val('');
				} else {
					$result.css('color', '#b91c1c').text((r && r.data && r.data.message) ? r.data.message : (i18n.error || 'Something went wrong.'));
				}
			}).fail(function () {
				$send.prop('disabled', false);
				$result.css('color', '#b91c1c').text(i18n.error || 'Something went wrong.');
			});
		});

		$btn.on('click', function () {
			$panel.slideToggle(150);
			// Best-effort prefill from whatever the Checkout block has already
			// saved to the session cart (Store API). Silently does nothing if
			// the request fails or the shape isn't what's expected — the
			// fields stay editable either way.
			if (!$panel.data('prefilled')) {
				$panel.data('prefilled', true);
				fetchPrefill($name, $email, $phone);
			}
		});

		$wrap.append($btn, $panel);
		return $wrap;
	}

	function fetchPrefill($name, $email, $phone) {
		try {
			fetch('/wp-json/wc/store/v1/cart', { credentials: 'same-origin' })
				.then(function (res) { return res.ok ? res.json() : null; })
				.then(function (data) {
					if (!data || !data.billing_address) {
						return;
					}
					var addr = data.billing_address;
					var full = [addr.first_name, addr.last_name].filter(Boolean).join(' ');
					if (full) { $name.find('input').val(full); }
					if (addr.email) { $email.find('input').val(addr.email); }
					if (addr.phone) { $phone.find('input').val(addr.phone); }
				})
				.catch(function () {});
		} catch (e) {
			// Older browsers without fetch() just skip prefill.
		}
	}

	function tryInject() {
		if (injected) {
			return;
		}
		// ".wc-block-components-totals-footer-item" isn't unique to the
		// Checkout block's own order summary — a header Mini-Cart block uses
		// the same class for its total row. Scope the search to the Checkout
		// block's sidebar (where its order summary lives) so a Mini-Cart
		// elsewhere on the page is never matched instead.
		var $scope = $('.wc-block-checkout__sidebar');
		if (!$scope.length) {
			$scope = $('.wc-block-checkout, .wp-block-woocommerce-checkout');
		}
		var $totalRows = $scope.find('.wc-block-components-totals-footer-item');
		if (!$totalRows.length) {
			return;
		}
		// The grand Total is the last footer item in the order-summary block.
		$totalRows.last().after(buildForm());
		injected = true;
	}

	$(function () {
		tryInject();
		// The Checkout block mounts asynchronously and re-renders as the
		// customer edits fields, so poll briefly rather than relying on a
		// single DOMContentLoaded check. Stops once injected or after ~15s.
		var attempts = 0;
		var timer = setInterval(function () {
			attempts++;
			tryInject();
			if (injected || attempts > 30) {
				clearInterval(timer);
			}
		}, 500);
	});
})(jQuery);
