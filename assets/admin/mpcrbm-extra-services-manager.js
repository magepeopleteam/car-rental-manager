/**
 * Extra Services Manager — Admin JS
 *
 * Only the list page's "Delete" button needs JS here — the group editor's
 * repeater table (add row / remove row) is already handled globally by
 * mp_global/assets/admin/mpcrbm_admin_settings.js, which delegates on
 * "div.mpcrbm .add_item" / ".item_remove" — this page's form just needs to
 * be wrapped in the same "div.mpcrbm .settings_area" structure the native
 * mpcrbm_ex_services edit screen uses (see MPCRBM_Extra_Services_Manager::
 * render_editor()) and it works with zero new code.
 */
jQuery(document).ready(function ($) {

	$(document).on('click', '.mpcrbm-ex-service-delete-btn', function () {
		var $card  = $(this).closest('.mpcrbm-ex-service-card');
		var postId = $(this).data('post-id');

		if (!confirm('Are you sure you want to delete this service group?')) {
			return;
		}

		$.post(ajaxurl, {
			action:  'mpcrbm_delete_ex_service_group',
			nonce:   mpcrbm_admin_nonce.nonce,
			post_id: postId,
		}, function (res) {
			if (res.success) {
				$card.fadeOut(200, function () { $(this).remove(); });
			} else {
				alert((res.data && res.data.message) || 'Failed to delete service group.');
			}
		}).fail(function () {
			alert('Network error. Please try again.');
		});
	});

});
