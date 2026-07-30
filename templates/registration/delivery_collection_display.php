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
        'label'       => esc_html__( 'Deliver this vehicle to my address', 'car-rental-manager' ),
        'placeholder' => esc_html__( 'Enter the delivery address…', 'car-rental-manager' ),
        'row_label'   => esc_html__( 'Delivery Fee:', 'car-rental-manager' ),
    ),
    'collection' => array(
        'label'       => esc_html__( 'Collect this vehicle from my address at return time', 'car-rental-manager' ),
        'placeholder' => esc_html__( 'Enter the collection address…', 'car-rental-manager' ),
        'row_label'   => esc_html__( 'Collection Fee:', 'car-rental-manager' ),
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
    <div class="mpcrbm_delivery_collection_layout_details">
        <h3><?php esc_html_e( 'Delivery & Collection (Optional)', 'car-rental-manager' ); ?></h3>
        <div class="divider"></div>
        <?php foreach ( $mpcrbm_dc_kinds as $mpcrbm_dc_kind => $mpcrbm_dc_cfg ) :
            if ( ! MPCRBM_Delivery_Collection_Settings::is_enabled( $post_id, $mpcrbm_dc_kind ) ) {
                continue;
            }
            $mpcrbm_dc_fee_type = get_post_meta( $post_id, "mpcrbm_{$mpcrbm_dc_kind}_fee_type", true );
            $mpcrbm_dc_fee_val  = floatval( get_post_meta( $post_id, "mpcrbm_{$mpcrbm_dc_kind}_fee", true ) );
            $mpcrbm_dc_fee_note = ( $mpcrbm_dc_fee_type === 'percentage' )
                ? sprintf( /* translators: %s: percentage number */ esc_html__( '+%s%% of total', 'car-rental-manager' ), $mpcrbm_dc_fee_val )
                : wp_kses_post( wc_price( $mpcrbm_dc_fee_val ) );
            ?>
            <div class="mpcrbm_dc_item">
                <label class="mpcrbm_dc_checkbox_label">
                    <input type="checkbox" name="mpcrbm_<?php echo esc_attr( $mpcrbm_dc_kind ); ?>_requested" value="1"
                           class="mpcrbm_dc_checkbox" data-kind="<?php echo esc_attr( $mpcrbm_dc_kind ); ?>"
                           data-fee="<?php echo esc_attr( $mpcrbm_dc_fee_val ); ?>"
                           data-fee-type="<?php echo esc_attr( $mpcrbm_dc_fee_type ); ?>">
                    <span><?php echo esc_html( $mpcrbm_dc_cfg['label'] ); ?></span>
                    <span class="mpcrbm_dc_fee_note _textTheme">(<?php echo wp_kses_post( $mpcrbm_dc_fee_note ); ?>)</span>
                </label>
                <textarea name="mpcrbm_<?php echo esc_attr( $mpcrbm_dc_kind ); ?>_address"
                          class="mpcrbm_dc_address" rows="2" style="display:none;"
                          placeholder="<?php echo esc_attr( $mpcrbm_dc_cfg['placeholder'] ); ?>"></textarea>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function($){
        // Only handles the address textarea slide — total/row recalculation is wired
        // in mpcrbm_registration.js (mpcrbm_price_calculation_car_details_page() for
        // the single car-details page, mpcrbm_price_calculation() for the search-result
        // "choose vehicle" flow), since those functions live inside that file's own
        // jQuery(document).ready() closure and aren't reachable from here. Not scoped
        // to a specific ancestor container since this template renders in both flows.
        $(document).on('change', '.mpcrbm_dc_checkbox', function(){
            var $box = $(this);
            var $addr = $box.closest('.mpcrbm_dc_item').find('.mpcrbm_dc_address');

            if ( $box.is(':checked') ) {
                $addr.slideDown(150);
            } else {
                $addr.slideUp(150).val('');
            }
        });
    })(jQuery);
    </script>
<?php endif; ?>
