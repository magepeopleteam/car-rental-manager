<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly

if (!isset($_POST['mptbm_transportation_type_nonce'])) {
	return;
}

// Unslash and verify the nonce
$nonce = wp_unslash($_POST['mptbm_transportation_type_nonce']);
if (!wp_verify_nonce($nonce, 'mptbm_transportation_type_nonce')) {
	return;
}
$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
if ($post_id && $post_id > 0) {
	$link_wc_product = MPCR_Global_Function::get_post_info($post_id, 'link_wc_product');
	$display_extra_services = MPCR_Global_Function::get_post_info($post_id, 'display_mptbm_extra_services', 'on');
	$service_id = MPCR_Global_Function::get_post_info($post_id, 'mptbm_extra_services_id', $post_id);
	$extra_services = MPCR_Global_Function::get_post_info($service_id, 'mptbm_extra_service_infos', []);
	if ($display_extra_services == 'on' && is_array($extra_services) && sizeof($extra_services) > 0) {
?>
		<div class="dLayout">
			<h3><?php esc_html_e('Choose Extra Features (Optional)', 'car-rental-manager'); ?></h3>
			<div class="divider"></div>
			<?php foreach ($extra_services as $service) { ?>
				<?php
				$service_icon = array_key_exists('service_icon', $service) ? $service['service_icon'] : '';
				$service_image = array_key_exists('service_image', $service) ? $service['service_image'] : '';
				$service_name = array_key_exists('service_name', $service) ? $service['service_name'] : '';
				$service_price = array_key_exists('service_price', $service) ? $service['service_price'] : 0;
				$wc_price = MPCR_Global_Function::wc_price($post_id, $service_price);
				$service_price = MPCR_Global_Function::price_convert_raw($wc_price);
				$description = array_key_exists('extra_service_description', $service) ? $service['extra_service_description'] : '';
				$ex_unique_id = '#ex_service_' . uniqid();
				?>
				<?php if ($service_name) { ?>
					<div class="dFlex mptbm_extra_service_item">
						<?php if ($service_image) { ?>
							<div class="service_img_area alignCenter">
								<div class="bg_image_area">
									<div data-bg-image="<?php echo esc_attr(MPCR_Global_Function::get_image_url('', $service_image, 'medium')); ?>"></div>
								</div>
							</div>
						<?php } ?>
						<div class="fdColumn _fullWidth">
							<h4>
								<?php if ($service_icon) { ?>
									<span class="<?php echo esc_attr($service_icon); ?>"></span>
								<?php } ?>
								<?php echo esc_html($service_name); ?>
								<sub class="textTheme"> &nbsp;&nbsp;<?php echo wp_kses_post(wc_price($service_price)); ?></sub>
							</h4>
							<div class="_equalChild">
								<div class="_mR_xs">
									<?php MPCR_Custom_Layout::load_more_text($description, 100); ?>
								</div>
								<div>
									<div class="justifyEnd">
										<div class="_mR_min_100" data-collapse="<?php echo esc_attr($ex_unique_id); ?>">
											<?php MPCR_Custom_Layout::qty_input('mptbm_extra_service_qty[]', $service_price, 100, 1, 0); ?>
										</div>
										<button type="button" class="_mpBtn_dBR_min_150 mptbm_price_calculation" data-extra-item data-collapse-target="<?php echo esc_attr($ex_unique_id); ?>" data-open-icon="far fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e('Select', 'car-rental-manager'); ?>" data-close-text="<?php esc_attr_e('Selected', 'car-rental-manager'); ?>" data-add-class="mActive">
											<input type="hidden" name="mptbm_extra_service[]" data-value="<?php echo esc_attr($service_name); ?>" value="" />
											<span data-text><?php esc_html_e('Select', 'car-rental-manager'); ?></span>
											<span data-icon class="mL_xs"></span>
										</button>
									</div>
								</div>

							</div>
						</div>
					</div>
					<div class="divider"></div>
				<?php } ?>
			<?php } ?>
		</div>
	<?php } ?>
	<div class="divider"></div>
	<div class="justifyBetween">
		<div></div>
		<button class="_successButton_min_200 mptbm_book_now" style="display:none;" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>">
			<span class="fas fa-cart-plus _mR_xs"></span>
			<?php esc_html_e('Book Now', 'car-rental-manager'); ?>
		</button>
	</div>
<?php
}
