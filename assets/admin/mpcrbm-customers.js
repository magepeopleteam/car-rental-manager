jQuery(function ($) {
	// Localized by MPCRBM_Customers::render_page() via wp_localize_script().
	var nonce = mpcrbmCustomers.nonce;
	var i18n  = mpcrbmCustomers.i18n;

	var $overlay = $('#mpcrbm-cust-modal');
	var $body    = $overlay.find('.mpcrbm-cust-modal-body');

	// Booking-history filter state for whichever customer's modal is currently
	// open — reset on every modal open, read by both the filter change handler
	// and "Load More" (which must keep re-requesting the SAME filter).
	var bookingsFilter = { preset: 'all', from: '', to: '' };

	function closeModal() { $overlay.attr('hidden', true).removeClass('is-loading'); $body.empty(); }

	$(document).on('click', '.mpcrbm-cust-view', function () {
		var key = $(this).data('key');
		bookingsFilter = { preset: 'all', from: '', to: '' };
		$overlay.removeAttr('hidden').addClass('is-loading');
		$body.html('<p class="mpcrbm-cust-modal-loading">' + i18n.loading + '</p>');

		$.post(ajaxurl, { action: 'mpcrbm_customer_detail', nonce: nonce, key: key })
			.done(function (res) {
				$overlay.removeClass('is-loading');
				if (res && res.success) {
					$body.html(res.data.html);
				} else {
					$body.html('<p class="mpcrbm-cust-modal-loading">' + ((res && res.data && res.data.message) || 'Error') + '</p>');
				}
			})
			.fail(function () {
				$overlay.removeClass('is-loading');
				$body.html('<p class="mpcrbm-cust-modal-loading">' + i18n.couldNotLoad + '</p>');
			});
	});

	// offset is a row COUNT, not a page number. A page-number counter handed
	// back and forth between server and a button's data-page attribute turned
	// out fragile in practice: jQuery caches a .data() read the first time
	// it's accessed, and a later plain .attr('data-page', ...) write doesn't
	// invalidate that cache, so the next click kept reading the ORIGINAL
	// value and re-fetching the same page forever. Counting the <tr> rows
	// actually in the table can't drift out of sync with what's on screen,
	// because it's derived from the screen itself rather than tracked
	// separately alongside it.
	function fetchBookingsPage(offset, append) {
		var $table = $body.find('.mpcrbm-cust-modal-table');
		var $tbody = $table.find('.mpcrbm-cust-bookings-body');
		var $loadMore = $body.find('.mpcrbm-cust-load-more');

		// Guards against a second click (double-click, or just an impatient
		// user) firing an overlapping request before this one's response has
		// updated the row count offset is derived from — without this, two
		// in-flight requests can both resolve with the SAME offset and both
		// get appended, duplicating rows (or duplicating the "no more
		// bookings" placeholder, with the button still visible either way).
		if ($loadMore.prop('disabled')) { return; }
		$loadMore.prop('disabled', true);

		$.post(ajaxurl, {
			action: 'mpcrbm_customer_bookings_page',
			nonce: nonce,
			key: $table.data('key'),
			offset: offset,
			preset: bookingsFilter.preset,
			date_from: bookingsFilter.from,
			date_to: bookingsFilter.to
		}).done(function (res) {
			if (!res || !res.success) { $loadMore.prop('disabled', false); return; }
			if (append) {
				$tbody.append(res.data.html);
			} else {
				$tbody.html(res.data.html);
			}
			// .prop() (not .attr()) — the reliable way to toggle a boolean IDL
			// property like "hidden" in jQuery, rather than leaning on its
			// attribute-level boolHook special-casing.
			$loadMore.prop('disabled', false).prop('hidden', !res.data.has_more);
		}).fail(function () {
			$loadMore.prop('disabled', false);
		});
	}

	function loadedRowCount($tbody) {
		return $tbody.find('tr').not('.mpcrbm-cust-modal-empty-row').length;
	}

	$(document).on('change', '.mpcrbm-cust-date-preset', function () {
		var val = $(this).val();
		var $custom = $(this).closest('.mpcrbm-cust-date-filter').find('.mpcrbm-cust-date-custom');
		if (val === 'custom') {
			$custom.removeAttr('hidden');
			return; // wait for "Apply" — two empty date inputs aren't a usable range yet
		}
		$custom.attr('hidden', true);
		bookingsFilter = { preset: val, from: '', to: '' };
		fetchBookingsPage(0, false);
	});
	$(document).on('click', '.mpcrbm-cust-date-apply', function () {
		var $filter = $(this).closest('.mpcrbm-cust-date-filter');
		bookingsFilter = {
			preset: 'custom',
			from: $filter.find('.mpcrbm-cust-date-from').val(),
			to: $filter.find('.mpcrbm-cust-date-to').val()
		};
		fetchBookingsPage(0, false);
	});
	$(document).on('click', '.mpcrbm-cust-load-more', function () {
		var $tbody = $body.find('.mpcrbm-cust-bookings-body');
		fetchBookingsPage(loadedRowCount($tbody), true);
	});

	// Own handler (not folded into the overlay one below): a click on the
	// button's icon <span> has e.target !== the button, so an
	// "e.target === this" guard — correct for the overlay's
	// click-outside-to-close — would silently swallow most clicks here.
	$(document).on('click', '.mpcrbm-cust-modal-close', function () {
		closeModal();
	});
	$(document).on('click', '.mpcrbm-cust-modal-overlay', function (e) {
		if (e.target === this) { closeModal(); }
	});
	$(document).on('keyup', function (e) {
		if (e.key === 'Escape') { closeModal(); }
	});

	// "Give Discount" — content is injected dynamically into the modal, so
	// these stay delegated on document rather than bound once at load.
	$(document).on('click', '.mpcrbm-cust-discount-toggle', function () {
		$(this).closest('.mpcrbm-cust-discount').find('.mpcrbm-cust-discount-form').toggle();
	});
	$(document).on('change', 'input[name=mpcrbm_disc_validity]', function () {
		var $form = $(this).closest('.mpcrbm-cust-discount-form');
		var isRange = $(this).val() === 'range' && $(this).is(':checked');
		if ($(this).is(':checked')) {
			$form.find('.mpcrbm-disc-validity-uses').toggle(!isRange);
			$form.find('.mpcrbm-disc-validity-range').toggle(isRange);
		}
	});
	$(document).on('click', '.mpcrbm-cust-discount-submit', function () {
		var $btn       = $(this);
		var $form      = $btn.closest('.mpcrbm-cust-discount-form');
		var $result    = $form.find('.mpcrbm-cust-discount-result');
		var type       = $form.find('input[name=mpcrbm_disc_type]:checked').val();
		var amount     = $form.find('.mpcrbm-disc-amount').val();
		var validity   = $form.find('input[name=mpcrbm_disc_validity]:checked').val();
		var maxUses    = $form.find('.mpcrbm-disc-max-uses').val();
		var validFrom  = $form.find('.mpcrbm-disc-valid-from').val();
		var validUntil = $form.find('.mpcrbm-disc-valid-until').val();

		if (!amount || parseFloat(amount) <= 0) {
			$result.html('<div class="mpcrbm-disc-error">' + i18n.enterValidAmount + '</div>');
			return;
		}
		if (validity === 'range' && !validFrom && !validUntil) {
			$result.html('<div class="mpcrbm-disc-error">' + i18n.setStartOrEndDate + '</div>');
			return;
		}

		$btn.prop('disabled', true);
		$result.html(i18n.creating);

		$.post(ajaxurl, {
			action: 'mpcrbm_customer_give_discount',
			nonce: nonce,
			email: $btn.data('email'),
			name: $btn.data('name'),
			type: type,
			amount: amount,
			validity: validity,
			max_uses: maxUses,
			valid_from: validFrom,
			valid_until: validUntil
		}).done(function (res) {
			$btn.prop('disabled', false);
			if (res && res.success) {
				$result.html(
					'<div class="mpcrbm-disc-success"><strong>' + res.data.code + '</strong><p>' + res.data.message + '</p>' +
					'<button type="button" class="mpcrbm-disc-mail-btn" data-code="' + res.data.code + '" title="' + i18n.emailThisCode + '">' +
					'<span class="dashicons dashicons-email-alt"></span> ' + i18n.sendEmail +
					'</button></div>'
				);
			} else {
				$result.html('<div class="mpcrbm-disc-error">' + ((res && res.data && res.data.message) || 'Error') + '</div>');
			}
		}).fail(function () {
			$btn.prop('disabled', false);
			$result.html('<div class="mpcrbm-disc-error">' + i18n.somethingWrong + '</div>');
		});
	});

	// "Send Mail" — appears both on each row of the past-discounts list and on
	// a freshly created coupon's success box, so it's delegated rather than
	// bound to either specific spot.
	$(document).on('click', '.mpcrbm-disc-mail-btn', function () {
		var $btn = $(this);
		if ($btn.prop('disabled')) { return; }
		var originalHtml = $btn.html();
		$btn.prop('disabled', true).addClass('is-sending').html('<span class="dashicons dashicons-update"></span>');

		$.post(ajaxurl, { action: 'mpcrbm_customer_send_discount_email', nonce: nonce, code: $btn.data('code') })
			.done(function (res) {
				$btn.prop('disabled', false).removeClass('is-sending');
				if (res && res.success) {
					$btn.addClass('is-sent').attr('title', res.data.message).html('<span class="dashicons dashicons-yes"></span>');
					setTimeout(function () { $btn.removeClass('is-sent').html(originalHtml); }, 2500);
				} else {
					$btn.html(originalHtml);
					window.alert((res && res.data && res.data.message) || i18n.somethingWrong);
				}
			})
			.fail(function () {
				$btn.prop('disabled', false).removeClass('is-sending').html(originalHtml);
				window.alert(i18n.somethingWrong);
			});
	});
});
