/**
 * "Join Waitlist" modal for the car-details page. Only enqueued on a car
 * post's own page, and only when the admin has turned the feature on (see
 * MPCRBM_Waitlist::enqueue_frontend_assets()).
 *
 * mpcrbm_registration.js's "Continue"/"Book Now" click handler shows a plain
 * alert() when the selected car/day is already booked (add-to-cart AJAX
 * returned the string "0" — see MPCRBM_Woocommerce::mpcrbm_add_to_cart()).
 * This listens for that same custom event instead of touching that file's
 * alert() directly, so both files stay independent of each other.
 */
(function ($) {
	'use strict';

	if (typeof mpcrbmWaitlist === 'undefined') {
		return;
	}

	var i18n = mpcrbmWaitlist.i18n || {};
	var $overlay, $modal;

	// Marks a fully-booked calendar day as clickable (date-picker.js adds this
	// class + the click listener only when this script is loaded — see its
	// onDayCreate). Kept here rather than a static stylesheet since it only
	// ever matters while this feature is switched on.
	$('<style>.flatpickr-day.mpcrbm-day-booked{cursor:pointer!important;background:#fef3c7!important;color:#92400e!important;position:relative;}.flatpickr-day.mpcrbm-day-booked:hover{background:#fde68a!important;}</style>').appendTo('head');

	function buildModal() {
		$overlay = $('<div class="mpcrbm-wl-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:99999;align-items:center;justify-content:center;"></div>');
		$modal = $('<form class="mpcrbm-wl-modal" style="background:#fff;border-radius:12px;width:100%;max-width:420px;padding:24px;max-height:90vh;overflow-y:auto;"></form>');

		$modal.append($('<h3 style="margin:0 0 6px;"></h3>').text(i18n.title || 'This car is booked for that day'));
		$modal.append($('<p style="margin:0 0 16px;font-size:13px;color:#555;"></p>').text(i18n.intro || ''));

		function field(labelText, id, type) {
			var $f = $('<div style="margin-bottom:12px;"></div>');
			$f.append($('<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;"></label>').text(labelText));
			$f.append($('<input>').attr({ type: type, id: id }).css({ width: '100%', padding: '8px 10px', border: '1px solid #d1d5db', borderRadius: '6px', boxSizing: 'border-box' }));
			return $f;
		}

		var $name  = field(i18n.nameLabel || 'Your Name', 'mpcrbm-wl-name', 'text');
		var $email = field(i18n.emailLabel || 'Email', 'mpcrbm-wl-email', 'email');
		var $phone = field(i18n.phoneLabel || 'Phone (optional)', 'mpcrbm-wl-phone', 'text');
		var $result = $('<div class="mpcrbm-wl-result" style="margin:10px 0;font-size:13px;"></div>');

		var $actions = $('<div style="display:flex;gap:8px;justify-content:flex-end;"></div>');
		var $cancel = $('<button type="button" style="padding:8px 14px;border-radius:6px;border:1px solid #d1d5db;background:#fff;cursor:pointer;"></button>').text(i18n.cancel || 'Cancel');
		var $send   = $('<button type="submit" style="padding:8px 14px;border-radius:6px;border:none;background:#111827;color:#fff;cursor:pointer;"></button>').text(i18n.send || 'Join Waitlist');
		$actions.append($cancel, $send);

		$modal.append($name, $email, $phone, $result, $actions);
		$overlay.append($modal);
		$('body').append($overlay);

		$cancel.on('click', closeModal);
		$overlay.on('click', function (e) {
			if (e.target === $overlay[0]) { closeModal(); }
		});

		$modal.on('submit', function (e) {
			e.preventDefault();

			var name  = $('#mpcrbm-wl-name').val();
			var email = $('#mpcrbm-wl-email').val();

			if (!name || !email) {
				$result.css('color', '#b91c1c').text(i18n.enterNameEmail || 'Please enter your name and a valid email address.');
				return;
			}

			$send.prop('disabled', true);
			$result.css('color', '#555').text(i18n.sending || 'Sending…');

			$.post(mpcrbmWaitlist.ajaxUrl, {
				action: 'mpcrbm_join_waitlist',
				nonce: mpcrbmWaitlist.nonce,
				car_id: $modal.data('car-id'),
				pickup: $modal.data('pickup'),
				return: $modal.data('return'),
				name: name,
				email: email,
				phone: $('#mpcrbm-wl-phone').val()
			}, function (r) {
				if (r && r.success) {
					$result.css('color', '#166534').text((r.data && r.data.message) || i18n.success || '');
					$actions.hide();
				} else {
					$send.prop('disabled', false);
					$result.css('color', '#b91c1c').text((r && r.data && r.data.message) ? r.data.message : (i18n.error || 'Something went wrong.'));
				}
			}).fail(function () {
				$send.prop('disabled', false);
				$result.css('color', '#b91c1c').text(i18n.error || 'Something went wrong.');
			});
		});
	}

	function openModal(carId, pickup, returnDate) {
		if (!$overlay) {
			buildModal();
		}
		$modal[0].reset();
		$modal.data({ 'car-id': carId, pickup: pickup, return: returnDate });
		$modal.find('.mpcrbm-wl-result').text('');
		$modal.find('button[type=submit]').prop('disabled', false);
		$modal.find('div').last().show();
		$overlay.css('display', 'flex');
	}

	function closeModal() {
		if ($overlay) {
			$overlay.css('display', 'none');
		}
	}

	$(document).on('mpcrbm:car_fully_booked', function (e, detail) {
		detail = detail || {};
		openModal(detail.carId, detail.pickup || '', detail.returnDate || '');
	});
})(jQuery);
