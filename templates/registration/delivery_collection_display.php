<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// MPCRBM_Delivery_Collection_Settings only loads admin-side (see MPCRBM_Admin::load_file(),
// gated by is_admin()) — bail out on the frontend if it somehow isn't available instead
// of fataling the whole car details page.
if ( ! class_exists( 'MPCRBM_Delivery_Collection_Settings' ) ) {
    return;
}
$post_id            = $mpcrbm_post_id ?? $post_id ?? get_the_id();
$mpcrbm_dc_currency = get_woocommerce_currency_symbol();

$mpcrbm_dc_kinds = array(
    'delivery'   => array(
        'icon'        => 'fas fa-truck',
        'label'       => esc_html__( 'Home Delivery', 'car-rental-manager' ),
        'desc'        => esc_html__( 'Deliver this vehicle to my address', 'car-rental-manager' ),
        'placeholder' => esc_html__( 'Enter the delivery address…', 'car-rental-manager' ),
    ),
    'collection' => array(
        'icon'        => 'fas fa-dolly',
        'label'       => esc_html__( 'Home Collection', 'car-rental-manager' ),
        'desc'        => esc_html__( 'Collect this vehicle from my address at return time', 'car-rental-manager' ),
        'placeholder' => esc_html__( 'Enter the collection address…', 'car-rental-manager' ),
    ),
);

$mpcrbm_dc_any_enabled = false;
foreach ( $mpcrbm_dc_kinds as $mpcrbm_dc_kind => $mpcrbm_dc_cfg ) {
    if ( MPCRBM_Delivery_Collection_Settings::is_enabled( $post_id, $mpcrbm_dc_kind ) ) {
        $mpcrbm_dc_any_enabled = true;
        break;
    }
}

if ( $mpcrbm_dc_any_enabled ) : ?>
    <div class="mpcrbm_extra_service_layout_details mpcrbm_delivery_collection_layout_details">
        <h3><?php esc_html_e( 'Delivery & Collection (Optional)', 'car-rental-manager' ); ?></h3>
        <div class="divider"></div>
        <?php foreach ( $mpcrbm_dc_kinds as $mpcrbm_dc_kind => $mpcrbm_dc_cfg ) :
            if ( ! MPCRBM_Delivery_Collection_Settings::is_enabled( $post_id, $mpcrbm_dc_kind ) ) {
                continue;
            }
            $mpcrbm_dc_fee_type = get_post_meta( $post_id, "mpcrbm_{$mpcrbm_dc_kind}_fee_type", true );
            $mpcrbm_dc_fee_val  = floatval( get_post_meta( $post_id, "mpcrbm_{$mpcrbm_dc_kind}_fee", true ) );
            $mpcrbm_dc_fee_note = ( $mpcrbm_dc_fee_type === 'percentage' )
                ? sprintf( /* translators: %s: percentage number */ esc_html__( '+%s%%', 'car-rental-manager' ), $mpcrbm_dc_fee_val )
                : wp_kses_post( wc_price( $mpcrbm_dc_fee_val ) );
            ?>
            <div class="mpcrbm_dc_item">
                <div class="dFlex mpcrbm_extra_service_item mpcrbm_dc_row">
                    <div class="mpcrbm_dc_icon"><i class="<?php echo esc_attr( $mpcrbm_dc_cfg['icon'] ); ?>"></i></div>
                    <div class="fdColumn _fullWidth">
                        <h4 class="mpcrbm_search_title">
                            <span class="mprcbm_text"><?php echo esc_html( $mpcrbm_dc_cfg['label'] ); ?></span>
                        </h4>
                        <div class="mpcrbm-ex-quantity-box">
                            <div class="price-quantity-box">
                                <div class="mpcrbm-price"><?php echo wp_kses_post( $mpcrbm_dc_fee_note ); ?></div>
                                <button type="button" class="mpcrbm_dc_toggle_btn" data-open-text="<?php esc_attr_e( 'Select', 'car-rental-manager' ); ?>" data-close-text="<?php esc_attr_e( 'Selected', 'car-rental-manager' ); ?>">
                                    <input type="checkbox" name="mpcrbm_<?php echo esc_attr( $mpcrbm_dc_kind ); ?>_requested" value="1"
                                           class="mpcrbm_dc_checkbox" data-kind="<?php echo esc_attr( $mpcrbm_dc_kind ); ?>"
                                           data-fee="<?php echo esc_attr( $mpcrbm_dc_fee_val ); ?>"
                                           data-fee-type="<?php echo esc_attr( $mpcrbm_dc_fee_type ); ?>">
                                    <span data-text><?php esc_html_e( 'Select', 'car-rental-manager' ); ?></span>
                                    <span data-icon><i class="far fa-check-circle"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mpcrbm_dc_desc"><?php echo esc_html( $mpcrbm_dc_cfg['desc'] ); ?></div>
                <textarea name="mpcrbm_<?php echo esc_attr( $mpcrbm_dc_kind ); ?>_address"
                          class="mpcrbm_dc_address" rows="2" style="display:none;"
                          placeholder="<?php echo esc_attr( $mpcrbm_dc_cfg['placeholder'] ); ?>"></textarea>
            </div>
            <div class="divider"></div>
        <?php endforeach; ?>
    </div>

    <style>
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_item { margin-bottom: 4px; }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_row { align-items: center; }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_icon {
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--color_theme, #F12971) 10%, transparent);
        color: var(--color_theme, #F12971);
        font-size: 14px;
        margin-right: 10px;
    }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_desc {
        font-size: 12px;
        color: #6b7280;
        margin: 4px 0 0 44px;
        line-height: 1.4;
    }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_toggle_btn {
        position: relative;
        overflow: hidden;
    }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_checkbox {
        position: absolute;
        inset: 0;
        opacity: 0;
        margin: 0;
        cursor: pointer;
    }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_toggle_btn[data-icon-show] [data-text] { display: none; }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_toggle_btn:not([data-icon-show]) [data-icon] { display: none; }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_address {
        width: 100%;
        box-sizing: border-box;
        margin-top: 10px;
        border: 1.5px solid var(--mpcrbm-cd-border, #e5e7eb);
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        font-family: inherit;
        resize: vertical;
        transition: border-color .18s;
    }
    .mpcrbm_delivery_collection_layout_details .mpcrbm_dc_address:focus {
        outline: none;
        border-color: var(--color_theme, #F12971);
    }
    </style>

    <script>
    (function($){
        // Mirrors the Extra Services "Select"/"Selected" pill button behaviour
        // (assets/frontend/mpcrbm_registration.js data-extra-item handler) so the
        // two optional-add-on sections look and feel consistent. Total/row
        // recalculation itself is wired in mpcrbm_registration.js — those
        // functions live inside that file's own jQuery(document).ready() closure
        // and aren't reachable from here.
        $(document).on('change', '.mpcrbm_dc_checkbox', function(){
            var $box = $(this);
            var $btn = $box.closest('.mpcrbm_dc_toggle_btn');
            var $addr = $box.closest('.mpcrbm_dc_item').find('.mpcrbm_dc_address');
            var checked = $box.is(':checked');

            $btn.toggleClass('mActive', checked);
            $btn.find('[data-text]').text( checked ? $btn.data('close-text') : $btn.data('open-text') );
            $btn.attr('data-icon-show', checked ? '1' : null);

            if ( checked ) {
                $addr.slideDown(150);
            } else {
                $addr.slideUp(150).val('');
            }
        });
    })(jQuery);
    </script>
<?php endif; ?>
