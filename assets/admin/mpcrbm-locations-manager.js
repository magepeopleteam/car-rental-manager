/**
 * Locations Manager — Admin JS
 *
 * Add/Edit/Delete for the mpcrbm_locations taxonomy from inside the shell.
 * The "Closed" checkbox → disable open/close inputs behavior for the hours
 * table is already handled globally by mpcrbm-branch-manager.js
 * ($(document).on('change', '.mpcrbm-day-closed', ...)) — this file only
 * needs to reset/populate the shared table's values per open/edit.
 */
jQuery(document).ready(function ($) {

	var $wrap   = $('.mpcrbm-locations-manager');
	var nonce   = $wrap.data('nonce');
	var days    = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

	function hoursField(day, part) {
		return $('input[name="mpcrbm_branch_hours[' + day + '][' + part + ']"]');
	}

	function resetHoursTable() {
		days.forEach(function (day) {
			hoursField(day, 'open').val('08:00').prop('disabled', false);
			hoursField(day, 'close').val('18:00').prop('disabled', false);
			hoursField(day, 'closed').prop('checked', false);
		});
	}

	function populateHoursTable(hours) {
		hours = hours || {};
		days.forEach(function (day) {
			var dayData = hours[day] || {};
			var closed  = !!dayData.closed;
			hoursField(day, 'open').val(dayData.open || '08:00').prop('disabled', closed);
			hoursField(day, 'close').val(dayData.close || '18:00').prop('disabled', closed);
			hoursField(day, 'closed').prop('checked', closed);
		});
	}

	function openModal() {
		// Explicit "flex" (not fadeIn's auto-detected display) — the overlay
		// uses flex to center the modal, and jQuery's default fallback for a
		// plain <div> is "block".
		$('#mpcrbm_location_modal_overlay').css('display', 'flex').hide().fadeIn(150);
	}

	function closeModal() {
		$('#mpcrbm_location_modal_overlay').fadeOut(150);
	}

	// ── Add ──────────────────────────────────────────────────────────────
	$(document).on('click', '#mpcrbm_add_location_btn', function () {
		$('#mpcrbm_location_modal_title').text('Add Location');
		$('#mpcrbm_location_term_id').val('');
		$('#mpcrbm_location_name').val('');
		$('#mpcrbm_location_slug').val('');
		$('#mpcrbm_location_desc').val('');
		$('#mpcrbm_location_address').val('');
		$('#mpcrbm_location_phone').val('');
		resetHoursTable();
		openModal();
	});

	// ── Edit ─────────────────────────────────────────────────────────────
	$(document).on('click', '.mpcrbm-location-edit-btn', function () {
		var $card = $(this).closest('.mpcrbm-location-card');
		var hours = {};
		try {
			hours = JSON.parse($card.attr('data-hours') || '{}');
		} catch (e) {
			hours = {};
		}

		$('#mpcrbm_location_modal_title').text('Edit Location');
		$('#mpcrbm_location_term_id').val($card.data('term-id'));
		$('#mpcrbm_location_name').val($card.data('name'));
		$('#mpcrbm_location_slug').val($card.data('slug'));
		$('#mpcrbm_location_desc').val($card.attr('data-desc') || '');
		$('#mpcrbm_location_address').val($card.attr('data-address') || '');
		$('#mpcrbm_location_phone').val($card.data('phone'));
		populateHoursTable(hours);
		openModal();
	});

	// ── Cancel / close ───────────────────────────────────────────────────
	$(document).on('click', '#mpcrbm_cancel_location_btn', closeModal);
	$(document).on('click', '#mpcrbm_close_location_modal', closeModal);
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && $('#mpcrbm_location_modal_overlay').is(':visible')) {
			closeModal();
		}
	});
	$(document).on('click', '#mpcrbm_location_modal_overlay', function (e) {
		if ($(e.target).is('#mpcrbm_location_modal_overlay')) {
			closeModal();
		}
	});

	// ── Save (add or update) ─────────────────────────────────────────────
	$(document).on('click', '#mpcrbm_save_location_btn', function () {
		var $btn    = $(this);
		var termId  = $('#mpcrbm_location_term_id').val();
		var name    = $('#mpcrbm_location_name').val().trim();

		if (!name) {
			alert('Name is required.');
			return;
		}

		var data = {
			action:      termId ? 'mpcrbm_update_location' : 'mpcrbm_save_location',
			nonce:       nonce,
			name:        name,
			slug:        $('#mpcrbm_location_slug').val(),
			description: $('#mpcrbm_location_desc').val(),
			mpcrbm_branch_address: $('#mpcrbm_location_address').val(),
			mpcrbm_branch_phone:   $('#mpcrbm_location_phone').val(),
		};
		if (termId) {
			data.term_id = termId;
		}
		days.forEach(function (day) {
			data['mpcrbm_branch_hours[' + day + '][open]']   = hoursField(day, 'open').val();
			data['mpcrbm_branch_hours[' + day + '][close]']  = hoursField(day, 'close').val();
			if (hoursField(day, 'closed').is(':checked')) {
				data['mpcrbm_branch_hours[' + day + '][closed]'] = '1';
			}
		});

		$btn.prop('disabled', true).text('Saving…');

		$.post(ajaxurl, data, function (res) {
			if (res.success) {
				$('#mpcrbm_locations_list').html(res.data.html);
				closeModal();
			} else {
				alert((res.data && res.data.message) || 'Failed to save location.');
			}
		}).fail(function () {
			alert('Network error. Please try again.');
		}).always(function () {
			$btn.prop('disabled', false).text('Save Changes');
		});
	});

	// ── Delete ───────────────────────────────────────────────────────────
	$(document).on('click', '.mpcrbm-location-delete-btn', function () {
		var $card = $(this).closest('.mpcrbm-location-card');
		var termId = $card.data('term-id');

		if (!confirm('Are you sure you want to delete this location?')) {
			return;
		}

		$.post(ajaxurl, {
			action:  'mpcrbm_delete_location',
			nonce:   nonce,
			term_id: termId,
		}, function (res) {
			if (res.success) {
				$('#mpcrbm_locations_list').html(res.data.html);
			} else {
				alert((res.data && res.data.message) || 'Failed to delete location.');
			}
		}).fail(function () {
			alert('Network error. Please try again.');
		});
	});

});
