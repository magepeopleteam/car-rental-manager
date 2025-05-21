(function ($) {
	"use strict";
	$(document).on('change', '.mptbm_extra_services_setting [name="mptbm_extra_services_id"]', function () {
		let ex_id = $(this).val();
		let parent = $(this).closest('.mptbm_extra_services_setting');
		let target = parent.find('.mptbm_extra_service_area');
		let post_id = $('[name="mptbm_post_id"]').val();
		
		if (ex_id && post_id) {
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					action: 'get_mptbm_ex_service',
					ex_id: ex_id,
					post_id: post_id,
					nonce: mptbmAdmin.nonce
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
		let extraServiceSelect = $('#mptbm_extra_services_select');
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

}(jQuery));

