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

	var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
	var lastFocusedElement = null;
	var strings = window.mpcrbmLocationsAdmin || {};

	function modal() {
		return $('#mpcrbm_location_modal_overlay');
	}

	function locationNonce() {
		return $('.mpcrbm-locations-manager').first().data('nonce')
			|| $('.mpcrbm-branch-dashboard').first().attr('data-location-nonce')
			|| '';
	}

	function requestContext() {
		return $('.mpcrbm-locations-manager').length ? 'locations' : 'branch';
	}

	function hoursField(day, part) {
		return modal().find('input[name="mpcrbm_branch_hours[' + day + '][' + part + ']"]');
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

	function clearError() {
		$('#mpcrbm_location_modal_error').removeClass('is-visible').text('');
		$('#mpcrbm_location_name').removeClass('has-error').attr('aria-invalid', 'false');
	}

	function showError(message) {
		$('#mpcrbm_location_modal_error').text(message).addClass('is-visible');
		$('#mpcrbm_location_name').addClass('has-error').attr('aria-invalid', 'true').trigger('focus');
	}

	function openModal() {
		var $modal = modal();
		if (!$modal.length) {
			return;
		}

		lastFocusedElement = document.activeElement;
		clearError();
		$('body').addClass('mpcrbm-branch-modal-open');
		$modal.attr('aria-hidden', 'false').css('display', 'flex').hide().fadeIn(150, function () {
			$('#mpcrbm_location_name').trigger('focus');
		});
	}

	function closeModal() {
		var $modal = modal();
		$modal.attr('aria-hidden', 'true').fadeOut(150, function () {
			$('body').removeClass('mpcrbm-branch-modal-open');
			clearError();
			if (lastFocusedElement && document.contains(lastFocusedElement)) {
				lastFocusedElement.focus();
			}
		});
	}

	function setSaving(isSaving) {
		var $button = $('#mpcrbm_save_location_btn');
		$button.prop('disabled', isSaving).toggleClass('is-saving', isSaving);
		$button.find('i').toggleClass('mi-disk', !isSaving).toggleClass('mi-spinner', isSaving);
		$button.find('span').text(isSaving ? (strings.savingText || 'Saving…') : (strings.saveText || 'Save Branch'));
	}

	function notify(message, type) {
		$(document).trigger('mpcrbm:branch-toast', [message, type || 'success']);
	}

	function refreshBranchSidebar(html, editedTermId) {
		var $sidebar = $('.mpcrbm-branch-sidebar').first();
		if (!$sidebar.length || !html) {
			return;
		}

		var activeTermId = parseInt($('.mpcrbm-branch-card.is-active').data('term-id'), 10) || 0;
		$sidebar.replaceWith(html);

		if (activeTermId) {
			var $activeCard = $('.mpcrbm-branch-card').filter(function () {
				return parseInt($(this).data('term-id'), 10) === activeTermId;
			}).first();
			$activeCard.addClass('is-active');

			if (editedTermId && activeTermId === editedTermId) {
				$('.mpcrbm-panel-branch-name').text($activeCard.data('name') || '');
			}
		}
	}

	function resetBranchCarsPanel() {
		var $prompt = $('<div>', { class: 'mpcrbm-select-prompt' });
		var $copy = $('<div>');
		$prompt.append($('<i>', { class: 'mi mi-map-location-track', 'aria-hidden': 'true' }));
		$copy.append($('<p>').text(strings.selectBranchTitle || 'Select a branch to view and transfer cars.'));
		$copy.append($('<span>').text(strings.selectBranchHint || 'Click any branch above to get started.'));
		$prompt.append($copy);

		$('.mpcrbm-branch-cars-panel-header').hide();
		$('.mpcrbm-panel-branch-name, .mpcrbm-panel-car-count').empty();
		$('.mpcrbm-branch-cars-panel-body').empty().append($prompt);
	}

	// ── Add ──────────────────────────────────────────────────────────────
	$(document).on('click', '#mpcrbm_add_location_btn', function () {
		$('#mpcrbm_location_modal_title').text(strings.addTitle || 'Add New Branch');
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
		var $card = $(this).closest('.mpcrbm-location-card, .mpcrbm-branch-card');
		var hours = {};
		try {
			hours = JSON.parse($card.attr('data-hours') || '{}');
		} catch (e) {
			hours = {};
		}

		$('#mpcrbm_location_modal_title').text(strings.editTitle || 'Edit Branch');
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
		if (!modal().is(':visible')) {
			return;
		}

		if (e.key === 'Escape') {
			closeModal();
			return;
		}

		if (e.key === 'Tab') {
			var $focusable = modal().find('button:not(:disabled), input:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])').filter(':visible');
			var first = $focusable.first()[0];
			var last  = $focusable.last()[0];

			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});
	$(document).on('click', '#mpcrbm_location_modal_overlay', function (e) {
		if ($(e.target).is('#mpcrbm_location_modal_overlay')) {
			closeModal();
		}
	});

	// ── Save (add or update) ─────────────────────────────────────────────
	$(document).on('submit', '#mpcrbm_location_form', function (event) {
		event.preventDefault();

		var termId  = $('#mpcrbm_location_term_id').val();
		var name    = $('#mpcrbm_location_name').val().trim();
		var nonce   = locationNonce();

		if (!name) {
			showError(strings.nameRequired || 'Branch name is required.');
			return;
		}

		if (!nonce) {
			showError(strings.saveFailed || 'Unable to save the branch.');
			return;
		}

		clearError();

		var data = {
			action:      termId ? 'mpcrbm_update_location' : 'mpcrbm_save_location',
			nonce:       nonce,
			context:     requestContext(),
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

		setSaving(true);

		$.post(typeof ajaxurl !== 'undefined' ? ajaxurl : '', data, function (res) {
			if (res.success) {
				if ($('#mpcrbm_locations_list').length && res.data.html) {
					$('#mpcrbm_locations_list').html(res.data.html);
				}
				refreshBranchSidebar(res.data.branch_html, parseInt(termId, 10) || 0);
				closeModal();
				notify(res.data.message || 'Branch saved successfully.', 'success');
			} else {
				showError((res.data && res.data.message) || strings.saveFailed || 'Unable to save the branch.');
			}
		}).fail(function () {
			showError(strings.networkError || 'Network error. Please try again.');
		}).always(function () {
			setSaving(false);
		});
	});

	// ── Delete ───────────────────────────────────────────────────────────
	$(document).on('click', '.mpcrbm-location-delete-btn', function () {
		var $card = $(this).closest('.mpcrbm-location-card, .mpcrbm-branch-card');
		var termId = $card.data('term-id');
		var wasActive = $card.hasClass('is-active');

		if (!confirm(strings.deleteConfirmText || 'Are you sure you want to delete this branch?')) {
			return;
		}

		$.post(typeof ajaxurl !== 'undefined' ? ajaxurl : '', {
			action:  'mpcrbm_delete_location',
			nonce:   locationNonce(),
			context: requestContext(),
			term_id: termId,
		}, function (res) {
			if (res.success) {
				if ($('#mpcrbm_locations_list').length && res.data.html) {
					$('#mpcrbm_locations_list').html(res.data.html);
				}
				refreshBranchSidebar(res.data.branch_html, 0);
				if (wasActive) {
					resetBranchCarsPanel();
				}
				notify(res.data.message || 'Branch deleted.', 'success');
			} else {
				notify((res.data && res.data.message) || strings.deleteFailed || 'Unable to delete the branch.', 'error');
			}
		}).fail(function () {
			notify(strings.networkError || 'Network error. Please try again.', 'error');
		});
	});

});
