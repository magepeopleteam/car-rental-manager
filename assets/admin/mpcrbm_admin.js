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

		$(document).ready(function($){
			$('.mpcrbm-discount-type').trigger('change');
		});

		$(document).on('change', '.mpcrbm-discount-type', function(){

			var container = $(this).closest('.mpcrbm-price-discount-tier');
			var type = $(this).val();

			container.find('.mpcrbm-field').hide();

			if(type === 'percent'){
				container.find('.mpcrbm-percent').show();
			}
			else if(type === 'fixed_discount'){
				container.find('.mpcrbm-fixed-discount').show();
			}
			else if(type === 'fixed_price'){
				container.find('.mpcrbm-fixed-price').show();
			}
			else if(type === 'day_price'){
				container.find('.mpcrbm-day-price').show();
			}

		});

		$(document).on( 'click','#mpcrbm-add-tier_old', function(){
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
		$(document).on('click', '#mpcrbm-add-tier', function(){

			$('#mpcrbm-tiered-rows').append(
				'<div class="mpcrbm-item mpcrbm-price-discount-tier">\
                    <input type="number" name="mpcrbm_tiered_discounts[min][]" class="mpcrbm-input" placeholder="Min Days">\
                    <span class="separator">–</span>\
                    <input type="number" name="mpcrbm_tiered_discounts[max][]" class="mpcrbm-input" placeholder="Max Days">\
                    <span>days</span>\
					<select name="mpcrbm_tiered_discounts[type][]" class="mpcrbm-input mpcrbm-discount-type">\
						<option value="percent">Percentage (%)</option>\
						<option value="fixed_discount">Fixed Discount</option>\
						<option value="fixed_price">Fixed Total Price</option>\
						<option value="day_price">Day-wise Price</option>\
					</select>\
					<input type="number" step="0.01" \
						name="mpcrbm_tiered_discounts[percent][]" \
						class="mpcrbm-input mpcrbm-field mpcrbm-percent" \
						placeholder="% Discount">\
					<input type="number" step="0.01" \
						name="mpcrbm_tiered_discounts[fixed_discount][]" \
						class="mpcrbm-input mpcrbm-field mpcrbm-fixed-discount" \
						placeholder="Discount Amount" style="display:none;">\
					<input type="number" step="0.01" \
						name="mpcrbm_tiered_discounts[fixed_price][]" \
						class="mpcrbm-input mpcrbm-field mpcrbm-fixed-price" \
						placeholder="Fixed Total Price" style="display:none;">\
					<input type="number" step="0.01" \
						name="mpcrbm_tiered_discounts[day_price][]" \
						class="mpcrbm-input mpcrbm-field mpcrbm-day-price" \
						placeholder="Price Per Day" style="display:none;">\
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
						<option value="fixed_increase">Fixed Increase</option>\
						<option value="fixed_decrease">Fixed Decrease</option>\
					</select>\
					<input type="number" step="0.01" name="mpcrbm_seasonal_pricing[value][]" placeholder="Value">\
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

	jQuery(document).on('change', '#mpcrbm_enable_driver_information', function () {
		if ($('input[name="mpcrbm_enable_driver_information"]').is(':checked')) {
			console.log('Checkbox is checked');
			$("#mpcrbm_get_driver_info").fadeIn();
		}else{
			$("#mpcrbm_get_driver_info").fadeOut();
		}
	});

	jQuery(document).on('change', '.mpcrbm_order_list__select', function () {
		let status = jQuery(this).val();
		let order_id = $(this).attr('data-order-id').trim();
		let order_post_id = $(this).attr('data-order-post-id').trim();
		console.log( status, order_id );

		jQuery.ajax({
			url: mpcrbm_ajax_url, // WordPress AJAX endpoint
			type: 'POST',
			data: {
				action: 'mpcrbm_update_order_status',
				order_id: order_id,
				order_post_id: order_post_id,
				status: status,
				nonce:  mpcrbm_admin_nonce.nonce
			},
			success: function(response) {
				if(response.success) {
					alert('Order status updated successfully!');
				} else {
					alert('Failed to update order status: ' + response.data);
				}
			},
			error: function(xhr, status, error) {
				console.log('AJAX Error:', error);
			}
		});

	});

})(jQuery);
