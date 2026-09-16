/**
 * Extra Services Manager — list actions and accessible add/edit modal.
 */
(function ($) {
	'use strict';

	$(function () {
		var config = window.mpcrbmExServicesAdmin || {};
		var $modal = $('#mpcrbm_ex_service_modal');
		var $dialog = $modal.find('.mpcrbm-ex-service-modal');
		var $form = $('#mpcrbm_ex_service_modal_form');
		var $tbody = $modal.find('.item_insert');
		var $loading = $modal.find('.mpcrbm-ex-service-modal-loading');
		var $alert = $modal.find('.mpcrbm-ex-service-modal-alert');
		var blankRowHtml = $modal.find('.hidden_item').first().html() || '';
		var lastFocused = null;
		var activeRequest = null;

		function text(key, fallback) {
			return config[key] || fallback;
		}

		function getFocusableElements() {
			return $dialog
				.find('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])')
				.filter(':visible');
		}

		function refreshRows() {
			if ($.fn.sortable) {
				$tbody.sortable({ handle: '.sortable_button' });
			}
		}

		function setMode(postId) {
			var isEdit = postId > 0;
			var $submit = $modal.find('.mpcrbm-ex-service-modal-submit');

			$('#mpcrbm_ex_service_post_id').val(isEdit ? postId : '');
			$('#mpcrbm_ex_service_modal_title').text(isEdit ? text('editTitle', 'Edit Service Group') : text('addTitle', 'Add Service Group'));
			$modal.find('.mpcrbm-ex-service-modal-eyebrow').text(isEdit ? text('editEyebrow', 'Update Offering') : text('addEyebrow', 'New Offering'));
			$submit.find('span').text(isEdit ? $submit.data('update-text') : $submit.data('create-text'));
		}

		function showAlert(message) {
			$alert.text(message).prop('hidden', false);
		}

		function clearAlert() {
			$alert.empty().prop('hidden', true);
		}

		function setLoading(isLoading) {
			$loading.prop('hidden', !isLoading);
			$modal.find('.mpcrbm-ex-service-modal-settings').attr('aria-busy', isLoading ? 'true' : 'false');
			$modal.find('.mpcrbm-ex-service-modal-table-wrap, .add_item').toggleClass('is-loading', isLoading);
			$modal.find('.mpcrbm-ex-service-modal-submit').prop('disabled', isLoading);
		}

		function resetForAdd() {
			if (activeRequest) {
				activeRequest.abort();
				activeRequest = null;
			}

			$form.get(0).reset();
			setMode(0);
			$('#mpcrbm_ex_service_group_name').val('');
			clearAlert();
			setLoading(false);
			$tbody.html(blankRowHtml);
			refreshRows();
		}

		function loadGroup(postId) {
			var loadSucceeded = false;

			if (activeRequest) {
				activeRequest.abort();
			}

			setMode(postId);
			clearAlert();
			setLoading(true);
			$('#mpcrbm_ex_service_group_name').val('');
			$tbody.empty();

			activeRequest = $.post(window.ajaxurl, {
				action: 'mpcrbm_get_ex_service_group',
				nonce: config.nonce || (window.mpcrbm_admin_nonce && window.mpcrbm_admin_nonce.nonce),
				post_id: postId
			}).done(function (response) {
				if (!response.success || !response.data) {
					showAlert((response.data && response.data.message) || text('loadFailed', 'Unable to load the service group.'));
					return;
				}

				$('#mpcrbm_ex_service_post_id').val(response.data.postId);
				$('#mpcrbm_ex_service_group_name').val(response.data.title);
				$tbody.html(response.data.rowsHtml || blankRowHtml);
				loadSucceeded = true;
				refreshRows();
			}).fail(function (xhr, status) {
				if (status !== 'abort') {
					var response = xhr.responseJSON;
					showAlert((response && response.data && response.data.message) || text('networkError', 'Network error. Please try again.'));
				}
			}).always(function () {
				activeRequest = null;
				setLoading(false);
				if (!loadSucceeded) {
					$modal.find('.mpcrbm-ex-service-modal-submit').prop('disabled', true);
				}
			});
		}

		function openModal(event, postId, useServerContent) {
			if (event) {
				event.preventDefault();
				lastFocused = event.currentTarget;
				postId = parseInt($(event.currentTarget).data('post-id'), 10) || 0;
			}

			$modal.addClass('is-open').attr('aria-hidden', 'false');
			$('body').addClass('mpcrbm-ex-service-modal-open');

			if (!useServerContent) {
				if (postId > 0) {
					loadGroup(postId);
				} else {
					resetForAdd();
				}
			} else {
				setMode(postId || 0);
				refreshRows();
			}

			window.setTimeout(function () {
				$('#mpcrbm_ex_service_group_name').trigger('focus');
			}, 40);
		}

		function closeModal() {
			if (activeRequest) {
				activeRequest.abort();
				activeRequest = null;
			}

			$modal.removeClass('is-open').attr('aria-hidden', 'true');
			$('body').removeClass('mpcrbm-ex-service-modal-open');
			setLoading(false);

			if (window.history && window.history.replaceState) {
				var url = new window.URL(window.location.href);
				if (url.searchParams.get('action') === 'new' || url.searchParams.get('action') === 'edit') {
					url.searchParams.delete('action');
					url.searchParams.delete('id');
					window.history.replaceState({}, document.title, url.toString());
				}
			}

			if (lastFocused) {
				$(lastFocused).trigger('focus');
			} else {
				$('.mpcrbm-ex-services-add-btn').trigger('focus');
			}
		}

		if ($modal.length) {
			$(document).on('click', '.mpcrbm-ex-service-open-modal', function (event) {
				openModal(event);
			});

			$modal.on('click', '.mpcrbm-ex-service-modal-close, .mpcrbm-ex-service-modal-cancel', closeModal);
			$modal.on('mousedown', function (event) {
				if (event.target === this) {
					closeModal();
				}
			});

			$(document).on('keydown.mpcrbmExtraServiceModal', function (event) {
				if (!$modal.hasClass('is-open')) {
					return;
				}

				if (event.key === 'Escape') {
					event.preventDefault();
					closeModal();
					return;
				}

				if (event.key !== 'Tab') {
					return;
				}

				var $focusable = getFocusableElements();
				if (!$focusable.length) {
					event.preventDefault();
					return;
				}

				var first = $focusable.get(0);
				var last = $focusable.get($focusable.length - 1);
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			});

			$form.on('submit', function () {
				if (this.checkValidity && !this.checkValidity()) {
					return;
				}

				var $submit = $(this).find('.mpcrbm-ex-service-modal-submit');
				$submit.prop('disabled', true).attr('aria-busy', 'true');
				$submit.find('span').text($submit.data('loading-text'));
			});

			if ($modal.data('auto-open') === 1 || $modal.hasClass('is-open')) {
				openModal(null, parseInt($modal.data('initial-post-id'), 10) || 0, true);
			}
		}

		$(document).on('click', '.mpcrbm-ex-service-delete-btn', function () {
			var $card = $(this).closest('.mpcrbm-ex-service-card');
			var postId = $(this).data('post-id');

			if (!window.confirm(text('deleteConfirm', 'Are you sure you want to delete this service group?'))) {
				return;
			}

			$.post(window.ajaxurl, {
				action: 'mpcrbm_delete_ex_service_group',
				nonce: config.nonce || (window.mpcrbm_admin_nonce && window.mpcrbm_admin_nonce.nonce),
				post_id: postId
			}, function (response) {
				if (response.success) {
					$card.fadeOut(200, function () { $(this).remove(); });
				} else {
					window.alert((response.data && response.data.message) || text('deleteFailed', 'Failed to delete service group.'));
				}
			}).fail(function () {
				window.alert(text('networkError', 'Network error. Please try again.'));
			});
		});

		$(document).on('click', '.mpcrbm-ex-service-view-btn', function () {
			var $button = $(this);
			var $card = $button.closest('.mpcrbm-ex-service-card');
			var expanded = $card.toggleClass('is-expanded').hasClass('is-expanded');

			$button.find('i').toggleClass('mi-eye', !expanded).toggleClass('mi-eye-crossed', expanded);
			$button.attr('title', expanded ? text('hideItems', 'Hide extra items') : text('showItems', 'Show all items'));
		});
	});
})(jQuery);
