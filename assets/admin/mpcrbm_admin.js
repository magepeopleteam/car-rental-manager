(function ($) {
	"use strict";
	$(document).on('change', '.mpcrbm_extra_services_setting [name="mpcrbm_extra_services_id"]', function () {
		let ex_id = $(this).val();
		let parent = $(this).closest('.mpcrbm_extra_services_setting');
		let target = parent.find('.mpcrbm_extra_service_area');
		let post_id = $('[name="mpcrbm_post_id"]').val();
		
		if (ex_id && post_id) {
			$.ajax({
				type: 'POST',
				url: mpcrbm_ajax_url,
				data: {
					action: 'get_mpcrbm_ex_service',
					ex_id: ex_id,
					post_id: post_id,
					nonce: mpcrbm_admin_nonce.nonce
				},
				beforeSend: function () {
					target.html('<div class="mp_loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
				},
				success: function (response) {
					if (response.success) {
						target.html(response.data.html);
						// Show success message
						if (response.data.message) {
							let message = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
							parent.prepend(message);
							setTimeout(function() {
								message.fadeOut(500, function() {
									$(this).remove();
								});
							}, 3000);
						}
					} else {
						target.html('<div class="notice notice-error"><p>' + (response.data.message || 'Error loading extra services') + '</p></div>');
					}
				},
				error: function () {
					target.html('<div class="notice notice-error"><p>Error loading extra services. Please try again.</p></div>');
				}
			});
		} else {
			target.html('');
		}
	});

	// Add handler for form submission
	$(document).on('submit', '#post', function(e) {
		let extraServiceSelect = $('#mpcrbm_extra_services_select');
		if (extraServiceSelect.length) {
			let selectedValue = extraServiceSelect.val();
			if (!selectedValue) {
				e.preventDefault();
				alert('Please select an extra service option or choose "Custom"');
				extraServiceSelect.focus();
				return false;
			}
		}
	});
	$(document).ready(function () {
		$('#operation_area_select').on('change', function () {
			// Loop through all options
			$(this).find('option').each(function () {
				// Add or remove the 'selected' class based on the selected state
				if ($(this).is(':selected')) {
					$(this).addClass('operation_area_selected');
				} else {
					$(this).removeClass('operation_area_selected');
				}
			});
		});

		// Trigger change on page load to apply the correct styles for pre-selected options
		$('#operation_area_select').trigger('change');
	});
}(jQuery));
(function($) {
	"use strict";
	$(document).ready(function() {
		// Check if the target element exists (Change selector to match your page's structure)
		if ($('#mpcrbm-quick-setup').length > 0) {
			let mpcrbm_admin_location = window.location.href;

			// Prevent infinite loop by checking if redirection is needed
			if (!mpcrbm_admin_location.includes('edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup')) {
				mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');
				mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?page=mpcrbm_rent', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');
				mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?page=mpcrbm_quick_setup', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');

				window.location.href = mpcrbm_admin_location; // Redirect only if needed
			}
		}
	});
})(jQuery);
