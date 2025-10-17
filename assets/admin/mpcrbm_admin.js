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
					action: 'mpcrbm_get_ex_service',
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

		$('#mpcrbm-add-tier').on('click', function(){
			$('#mpcrbm-tiered-rows').append(
				'<div class="mpcrbm-item mpcrbm-price-discount-tier">\
					<input type="number" name="mpcrbm_tiered_discounts[min][]" class="mpcrbm-input" placeholder="Min Days">\
					<span class="separator">–</span>\
					<input type="number" name="mpcrbm_tiered_discounts[max][]" class="mpcrbm-input" placeholder="Max Days">\
					 <span>days</span>\
					<input type="number" step="0.01" name="mpcrbm_tiered_discounts[percent][]" class="mpcrbm-input" placeholder="% Discount">\
					<span>% discount</span>\
					<button type="button" class="button mpcrbm-remove-row mpcrbm-remove-btn">Remove</button>\
				</div>'
			);
		});

		$('#mpcrbm-add-season').on('click', function(){
			$('#mpcrbm-season-rows').append(
				'<div class="mpcrbm-item mpcrbm-season-row">\
					<input type="text" name="mpcrbm_seasonal_pricing[name][]" placeholder="Name">\
					<input type="date" name="mpcrbm_seasonal_pricing[start][]">\
					<input type="date" name="mpcrbm_seasonal_pricing[end][]">\
						<select name="mpcrbm_seasonal_pricing[type][]">\
						<option value="percentage_increase">% Increase</option>\
						<option value="percentage_decrease">% Decrease</option>\
						<option value="fixed">Fixed</option>\
					</select>\
					<input type="number" step="0.01" name="mpcrbm_seasonal_pricing[value][]" placeholder="Value">\
					<button type="button" class="button mpcrbm-remove-row mpcrbm-remove-btn">Remove</button>\
				</div>'
			);
		});

		$('#mpcrbm-add-fee').on('click', function(){
			$('#mpcrbm-fees-rows').append(
				'<div class="mpcrbm-item mpcrbm-fee-row">\
					<input type="text" name="mpcrbm_additional_fees[name][]" class="mpcrbm-input" placeholder="Fee Name">\
					<select name="mpcrbm_additional_fees[type][]">\
						<option value="fixed">Fixed</option>\
						<option value="percentage">Percentage</option>\
					</select>\
					<input type="number" step="0.01" name="mpcrbm_additional_fees[value][]" class="mpcrbm-input" placeholder="Value">\
					<select name="mpcrbm_additional_fees[apply][]">\
						<option value="per_booking">Per Booking</option>\
						<option value="per_day">Per Day</option>\
					</select>\
					<button type="button" class="button mpcrbm-remove-row mpcrbm-remove-btn">Remove</button>\
				</div>'
			);
		});

		$(document).on('click', '.mpcrbm-remove-row', function(){
			$(this).closest('.mpcrbm-item').remove();
		});

		$(document).on('click', '.mpcrbm_toggle_class', function(){
			// $('.mpcrbm-price-content-container').slideUp();
			$(this).siblings().slideToggle(300);
		});
		$(document).on('click', '.mpcrbm_switch_checkbox', function() {
			let checked = $(this).is(':checked') ? 1 : 0;
			let post_id = $('[name="mpcrbm_post_id"]').val();
			let metaKey  = $(this).attr('id');
			let containerId =metaKey+'_holder';

			var heading = $(this).closest('.mpcrbm-section').find('.mpcrbm-heading');
			$.ajax({
				type: 'POST',
				url: mpcrbm_ajax_url,
				data: {
					action: 'mpcrbm_add_price_discount_rules',
					post_id: post_id,
					metaKey: metaKey,
					enable: checked,
					nonce: mpcrbm_admin_nonce.nonce
				},
				beforeSend: function() {
					$('#mpcrbm_message').text('Saving...');
				},
				success: function(response) {

					if( response.data.message ){
						if( checked === 1 ){
							$("#"+containerId).slideDown(300);
							heading.addClass('mpcrbm_toggle_class');
						}else{
							$("#"+containerId).slideUp(300);
							heading.removeClass('mpcrbm_toggle_class');
						}
					}else{
						alert( response.data.message );
					}
				}
			});
		});


	});
})(jQuery);
