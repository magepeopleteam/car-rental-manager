<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$mpcrbm_link_wc_product = MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product');
$mpcrbm_display_extra_services = MPCRBM_Global_Function::get_post_info($post_id, 'display_mpcrbm_extra_services', 'on');
$mpcrbm_service_id = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_extra_services_id', $post_id);
//$extra_services = MPCRBM_Global_Function::get_post_info($mpcrbm_service_id, 'mpcrbm_extra_service_infos', []);
$mpcrbm_extra_services = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_extra_service_infos', []);

if ($mpcrbm_display_extra_services == 'on' && is_array( $mpcrbm_extra_services ) && sizeof( $mpcrbm_extra_services ) > 0) { ?>
    <div class="<?php echo esc_attr( $mpcrbm_extra_service_class );?>">
        <h3><?php esc_html_e('Choose Extra Features (Optional)', 'car-rental-manager'); ?></h3>
        <div class="divider"></div>
        <?php foreach ($mpcrbm_extra_services as $mpcrbm_service) {
            // Validate and sanitize service data
            if (!is_array($mpcrbm_service)) {
                continue;
            }

            $mpcrbm_service_icon = isset( $mpcrbm_service['service_icon'] ) ? sanitize_text_field($mpcrbm_service['service_icon'] ) : '';
            $mpcrbm_service_image = isset( $mpcrbm_service['service_image'] ) ? absint($mpcrbm_service['service_image'] ) : 0;
            $mpcrbm_service_name = isset( $mpcrbm_service['service_name'] ) ? sanitize_text_field($mpcrbm_service['service_name'] ) : '';
            $mpcrbm_service_price = isset( $mpcrbm_service['service_price'] ) ? floatval($mpcrbm_service['service_price'] ) : 0;
            $mpcrbm_description = isset( $mpcrbm_service['extra_service_description'] ) ? wp_kses_post($mpcrbm_service['extra_service_description'] ) : '';

            // Skip if required fields are missing
            if (!$mpcrbm_service_name || $mpcrbm_service_price < 0) {
                continue;
            }

            $mpcrbm_wc_price = MPCRBM_Global_Function::wc_price($post_id, $mpcrbm_service_price);
            $mpcrbm_service_price = MPCRBM_Global_Function::price_convert_raw($mpcrbm_wc_price);
            $mpcrbm_ex_unique_id = '#ex_service_' . uniqid();
            ?>
            <div class="dFlex mpcrbm_extra_service_item">
                <?php if ($mpcrbm_service_image) { ?>
                    <div class="service_img_area alignCenter">
                        <div class="bg_image_area">
                            <div data-bg-image="<?php echo esc_attr(MPCRBM_Global_Function::get_image_url('', $mpcrbm_service_image, 'medium')); ?>"></div>
                        </div>
                    </div>
                <?php } ?>
                <div class="fdColumn _fullWidth">
                    <h4 class="mpcrbm_search_title">
                        <?php if ($mpcrbm_service_icon) { ?>
                            <span class="<?php echo esc_attr($mpcrbm_service_icon); ?>"></span>
                        <?php } ?>
                        <span class="mprcbm_text"><?php echo esc_html($mpcrbm_service_name); ?></span>
                    </h4>
                    <div class="mpcrbm-ex-quantity-box">
                        <div class="_mR_xs">
                            <?php MPCRBM_Custom_Layout::load_more_text($mpcrbm_description, 100); ?>
                        </div>
                        <div class="price-quantity-box">
                            <div class="mpcrbm-price"><?php echo wp_kses_post(wc_price($mpcrbm_service_price)); ?></div>
                            <div class="_mR_min_100" data-collapse="<?php echo esc_attr($mpcrbm_ex_unique_id); ?>">
                                <?php MPCRBM_Custom_Layout::qty_input('mpcrbm_extra_service_qty[]', $mpcrbm_service_price, 100, 1, 0); ?>
                            </div>

                            <button type="button" class="mpcrbm_price_calculation" data-extra-item data-collapse-target="<?php echo esc_attr($mpcrbm_ex_unique_id); ?>" data-open-icon="far fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e('Select', 'car-rental-manager'); ?>" data-close-text="<?php esc_attr_e('Selected', 'car-rental-manager'); ?>" data-add-class="mActive">
                                <input type="hidden" name="mpcrbm_extra_service[]" data-value="<?php echo esc_attr($mpcrbm_service_name); ?>" value="" />
                                <span data-text><?php esc_html_e('Select', 'car-rental-manager'); ?></span>
                                <span data-icon></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="divider"></div>
        <?php } ?>
    </div>
<?php }

?>
