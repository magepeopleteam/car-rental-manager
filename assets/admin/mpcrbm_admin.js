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
		// City toggle-pills — a set of buttons built from #operation_area_select's
		// own <option> elements, one per city. The <select multiple> itself is
		// left completely untouched (still has the same name/id/<option>
		// values) and just hidden via CSS; clicking a pill only flips that
		// option's "selected" property. This means the exact same
		// $_POST['mpcrbm_terms_start_location'] shape reaches
		// MPCRBM_Operation_Area_Settings::save_operation_area_settings() as
		// before — existing saved values keep working across the redesign,
		// nothing about the underlying form field changed, only how it's
		// presented.
		var $operationAreaSelect = $('#operation_area_select');
		if ($operationAreaSelect.length) {
			var $pillGroup = $('<div class="mpcrbm-city-toggle-group"></div>');

			$operationAreaSelect.find('option').each(function () {
				var $option = $(this);
				var $pill = $('<button type="button" class="mpcrbm-city-pill"></button>')
					.attr('data-value', $option.val())
					.toggleClass('is-selected', $option.prop('selected'))
					.append($('<span class="mpcrbm-city-pill-label"></span>').text($.trim($option.text())))
					.append('<i class="fas fa-check-circle"></i>');

				$pill.on('click', function () {
					var isSelected = !$option.prop('selected');
					$option.prop('selected', isSelected);
					$pill.toggleClass('is-selected', isSelected);
				});

				$pillGroup.append($pill);
			});

			$operationAreaSelect.hide().after($pillGroup);
		}

		// Search/filter box for the FAQ + Term & Condition "available"/"added"
		// list-picker panels (MPCRBM_Faq_Settings.php and
		// MPCRBM_Term_Condition_Setting.php render the same .mpcrbm_faq_item/
		// .mpcrbm_selected_item row markup, just with different Add/Remove
		// button classes) — these lists have no pagination and FAQ questions
		// in particular run long, so a plain client-side text filter goes a
		// long way for finding one quickly. Purely visual (toggles .hide()),
		// doesn't touch the existing Add/Remove click handlers or the hidden
		// mpcrbm_added_faq/mpcrbm_added_term_condition inputs at all.
		$('.mpcrbm_faq_all_question_box, .mpcrbm_selected_faq_question_box, .mpcrbm_all_term_condition').each(function () {
			var $box = $(this);
			var $list = $box.children('div').first();
			if (!$list.length) {
				return;
			}

			var $search = $('<input type="text" class="formControl mpcrbm-list-search" placeholder="Search…">');
			$box.find('> h3').after($search);

			$search.on('keyup', function () {
				var term = $.trim($(this).val()).toLowerCase();
				$list.find('.mpcrbm_faq_item, .mpcrbm_selected_item').each(function () {
					var title = ($(this).data('title') || '').toString().toLowerCase();
					$(this).toggle(title.indexOf(term) !== -1);
				});
			});
		});
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
			let $checkbox = $(this);
			let checked = $checkbox.is(':checked') ? 1 : 0;
			let post_id = $('[name="mpcrbm_post_id"]').val();
			let metaKey  = $checkbox.attr('id');
			let containerId =metaKey+'_holder';

			var heading = $checkbox.closest('.mpcrbm-section').find('.mpcrbm-heading');

			// Visible loading state on the switch itself while the AJAX call is
			// in flight, so there's feedback right away instead of the card
			// body just silently sitting there until the request finishes —
			// it's this callback's success handler (below), not the click
			// itself, that actually reveals/hides #<metaKey>_holder.
			let $switchLabel = $checkbox.closest('.roundSwitchLabel');
			$checkbox.prop('disabled', true);
			$switchLabel.addClass('mpcrbm-switch-loading');

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
				},
				error: function() {
					// Request failed outright — revert the switch rather than
					// leaving it checked/unchecked out of sync with the saved
					// (unsaved) meta value.
					$checkbox.prop('checked', checked !== 1);
					alert('Something went wrong while saving this setting. Please try again.');
				},
				complete: function() {
					$checkbox.prop('disabled', false);
					$switchLabel.removeClass('mpcrbm-switch-loading');
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

	jQuery(document).on('change', '#mpcrbm_enable_gallery', function () {
		if ($(this).is(':checked')) {
			$("#mpcrbm_gallery_images_wrapper").fadeIn();
		}else{
			$("#mpcrbm_gallery_images_wrapper").fadeOut();
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

	/*let timer;
	$("#mpcrbm_set_pickup_location").on("input", function () {
		clearTimeout(timer);
		let query = $(this).val().trim();
		if (query.length < 3) {
			$("#mpcrbm_text_suggestions").hide();
			return;
		}
		timer = setTimeout(function () {

			$.ajax({
				url: "https://nominatim.openstreetmap.org/search",
				data: {
					format: "jsonv2",
					q: query,
					limit: 5
				},
				success: function (data) {

					let box = $("#mpcrbm_text_suggestions");
					box.empty();

					if (data.length === 0) {
						box.hide();
						return;
					}

					$.each(data, function (i, place) {
						let item = $("<div class='mpcrbm_text_item'></div>");
						console.log( place.display_name );

						item.text(place.display_name);

						item.on("click", function () {
							$("#mpcrbm_set_pickup_location").val(place.display_name);
							box.hide();
						});

						box.append(item);
					});

					box.show();
				},
				error: function () {
					console.log("Error fetching locations");
				}
			});

		}, 300);

	});
	$(document).on("click", function (e) {
		if (!$(e.target).closest(".container").length) {
			$("#mpcrbm_text_suggestions").hide();
		}
	});*/

})(jQuery);
