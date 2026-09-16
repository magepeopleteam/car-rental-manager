<?php
/*
 * @Author      MagePeople Team
 * Copyright:   mage-people.com
 */
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Delivery_Collection_Settings' ) ) {

    class MPCRBM_Delivery_Collection_Settings {

        public function __construct() {
            // Renders inside the "Fee & Deposit" tab, right after Security Deposit —
            // same extension point (MPCRBM_Multi_Location_Settings::multi_location_settings()).
            add_action( 'mpcrbm_multi_location_tab_after_pricing', [ $this, 'delivery_collection_settings' ], 20, 1 );
            add_action( 'save_post', [ $this, 'save_delivery_collection_settings' ] );
        }

        public function delivery_collection_settings( $post_id ) {
            wp_nonce_field( 'mpcrbm_save_delivery_collection', 'mpcrbm_delivery_collection_nonce' );
            // _text(): this symbol is printed through esc_html() and handed to
            // jQuery .text() below, so it must be a real character. WooCommerce's
            // raw symbol is an HTML entity ("&#36;") and would show up literally,
            // crushed into an unreadable sliver by the narrow prefix box.
            $currency = MPCRBM_Global_Function::currency_symbol_text();

            $this->render_fee_card(
                'delivery',
                __( 'Vehicle Delivery', 'car-rental-manager' ),
                __( 'Deliver this vehicle to the customer\'s address for an extra fee.', 'car-rental-manager' ),
                'fas fa-truck',
                $post_id,
                $currency
            );
            $this->render_fee_card(
                'collection',
                __( 'Vehicle Collection', 'car-rental-manager' ),
                __( 'Collect this vehicle from the customer\'s address at return time for an extra fee.', 'car-rental-manager' ),
                'fas fa-truck-loading',
                $post_id,
                $currency
            );
            ?>
            <style>
            .mpcrbm-dc-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;}
            .mpcrbm-dc-type-opt{position:relative;}
            .mpcrbm-dc-type-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
            .mpcrbm-dc-type-lbl{display:flex;align-items:center;gap:8px;padding:10px 12px;border:2px solid #e5e9f0;border-radius:8px;cursor:pointer;transition:all .2s;background:#fafbfc;font-size:13px;}
            .mpcrbm-dc-type-lbl:hover{border-color:#93c5fd;background:#eff6ff;}
            .mpcrbm-dc-type-opt input[type=radio]:checked + .mpcrbm-dc-type-lbl{border-color:#2563eb;background:#eff6ff;font-weight:600;color:#1d4ed8;}
            /* IMPORTANT — every rule below that styles a <span> is written as
               ".mpcrbm span.<class>" (specificity 0,2,1) on purpose. The shared
               framework sheet mp_global/assets/mp_style/mpcrbm_global.css carries
               ".mpcrbm span {display:inline-block}" (0,1,1), which outranks a plain
               ".mpcrbm-dc-…" class selector (0,1,0) and silently discards
               "display:flex" — taking align-items/justify-content down with it. That
               is what left the currency symbol stuck at the top-left of its box
               instead of centred, and the radio dot's inner dot off-centre. */
            .mpcrbm span.mpcrbm-dc-type-dot{width:16px;height:16px;border-radius:50%;border:2px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .2s;}
            .mpcrbm-dc-type-opt input[type=radio]:checked + .mpcrbm-dc-type-lbl .mpcrbm-dc-type-dot{border-color:#2563eb;background:#2563eb;}
            .mpcrbm-dc-type-dot::after{content:'';width:5px;height:5px;border-radius:50%;background:#fff;display:none;}
            .mpcrbm-dc-type-opt input[type=radio]:checked + .mpcrbm-dc-type-lbl .mpcrbm-dc-type-dot::after{display:block;}
            .mpcrbm-dc-amount-wrap{display:flex;align-items:center;border:2px solid #e5e9f0;border-radius:8px;overflow:hidden;transition:border-color .2s;max-width:220px;}
            .mpcrbm-dc-amount-wrap:focus-within{border-color:#2563eb;}
            /* flex-shrink:0 + nowrap: without them flexbox shrinks this box to fit
               the input, and a symbol wider than one glyph ("CHF", "Rp", "zł" — or
               an un-decoded "&#36;") is clipped to a sliver by the wrapper's
               overflow:hidden. line-height:1 keeps the glyph optically centred at
               any font size. See the ".mpcrbm span" note above for the selector. */
            .mpcrbm span.mpcrbm-dc-amount-prefix{padding:0 12px;background:#f3f4f6;border-right:1px solid #e5e9f0;height:40px;display:flex;align-items:center;justify-content:center;line-height:1;font-weight:700;font-size:14px;color:#374151;min-width:40px;flex:0 0 auto;white-space:nowrap;}
            .mpcrbm-dc-amount-wrap input[type=number]{border:none!important;outline:none!important;box-shadow:none!important;padding:0 12px;height:40px;font-size:14px;font-weight:600;color:#111827;width:100%;background:#fff;}
            </style>
            <script>
            (function($){
                ['delivery','collection'].forEach(function(kind){
                    var currency = '<?php echo esc_js( $currency ); ?>';
                    $('[name="mpcrbm_' + kind + '_fee_type"]').on('change', function(){
                        var isPct = $(this).val() === 'percentage';
                        $('#mpcrbm_' + kind + '_unit').text(isPct ? '%' : currency);
                        $('#mpcrbm_' + kind + '_fee_input').attr('placeholder', isPct ? 'e.g. 10' : 'e.g. 25');
                    });
                });
            })(jQuery);
            </script>
            <?php
        }

        private function render_fee_card( $kind, $title, $desc, $icon, $post_id, $currency ) {
            $enabled  = get_post_meta( $post_id, "mpcrbm_{$kind}_enabled", true );
            $fee_val  = get_post_meta( $post_id, "mpcrbm_{$kind}_fee", true );
            $fee_type = get_post_meta( $post_id, "mpcrbm_{$kind}_fee_type", true );
            $fee_type = ( $fee_type === 'percentage' ) ? 'percentage' : 'fixed';
            $checked  = $enabled ? 'checked' : '';
            $display  = $enabled ? 'block' : 'none';
            $unit     = $fee_type === 'percentage' ? '%' : $currency;
            ?>
            <div class="mpcrbm-info-card">
                <div class="mpcrbm-info-card-header">
                    <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    <div>
                        <h3><?php echo esc_html( $title ); ?></h3>
                        <span class="desc"><?php echo esc_html( $desc ); ?></span>
                    </div>
                    <?php MPCRBM_Custom_Layout::switch_button( "mpcrbm_{$kind}_enabled", $checked ); ?>
                </div>
                <div class="mpcrbm-info-card-body" id="mpcrbm_<?php echo esc_attr( $kind ); ?>_enabled_holder" style="display:<?php echo esc_attr( $display ); ?>;" data-collapse="#mpcrbm_<?php echo esc_attr( $kind ); ?>_enabled">
                    <section>
                        <label class="label" style="margin-bottom:12px;">
                            <div>
                                <h6><?php esc_html_e( 'Fee Type', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php esc_html_e( 'Fixed: flat amount added. Percentage: % of the booking total.', 'car-rental-manager' ); ?></span>
                            </div>
                        </label>
                        <div class="mpcrbm-dc-type-grid">
                            <div class="mpcrbm-dc-type-opt">
                                <input type="radio" name="mpcrbm_<?php echo esc_attr( $kind ); ?>_fee_type" value="fixed"
                                       id="mpcrbm_<?php echo esc_attr( $kind ); ?>_type_fixed" <?php checked( $fee_type, 'fixed' ); ?>>
                                <label class="mpcrbm-dc-type-lbl" for="mpcrbm_<?php echo esc_attr( $kind ); ?>_type_fixed">
                                    <span class="mpcrbm-dc-type-dot"></span>
                                    <?php esc_html_e( 'Fixed Amount', 'car-rental-manager' ); ?>
                                </label>
                            </div>
                            <div class="mpcrbm-dc-type-opt">
                                <input type="radio" name="mpcrbm_<?php echo esc_attr( $kind ); ?>_fee_type" value="percentage"
                                       id="mpcrbm_<?php echo esc_attr( $kind ); ?>_type_pct" <?php checked( $fee_type, 'percentage' ); ?>>
                                <label class="mpcrbm-dc-type-lbl" for="mpcrbm_<?php echo esc_attr( $kind ); ?>_type_pct">
                                    <span class="mpcrbm-dc-type-dot"></span>
                                    <?php esc_html_e( 'Percentage (%)', 'car-rental-manager' ); ?>
                                </label>
                            </div>
                        </div>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Fee Value', 'car-rental-manager' ); ?></h6>
                            </div>
                            <div class="mpcrbm-dc-amount-wrap">
                                <span class="mpcrbm-dc-amount-prefix" id="mpcrbm_<?php echo esc_attr( $kind ); ?>_unit"><?php echo esc_html( $unit ); ?></span>
                                <input type="number" id="mpcrbm_<?php echo esc_attr( $kind ); ?>_fee_input" class="price_validation"
                                       name="mpcrbm_<?php echo esc_attr( $kind ); ?>_fee" value="<?php echo esc_attr( $fee_val ); ?>"
                                       min="0" step="0.01"
                                       placeholder="<?php echo $fee_type === 'percentage' ? esc_attr__( 'e.g. 10', 'car-rental-manager' ) : esc_attr__( 'e.g. 25', 'car-rental-manager' ); ?>"/>
                            </div>
                        </label>
                    </section>
                </div>
            </div>
            <?php
        }

        public function save_delivery_collection_settings( $post_id ) {
            if ( ! isset( $_POST['mpcrbm_delivery_collection_nonce'] ) ) {
                return;
            }
            $nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_delivery_collection_nonce'] ) );
            if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_delivery_collection' ) ) {
                return;
            }
            if ( get_post_type( $post_id ) !== MPCRBM_Function::get_cpt() ) {
                return;
            }

            foreach ( [ 'delivery', 'collection' ] as $kind ) {
                $enabled = isset( $_POST[ "mpcrbm_{$kind}_enabled" ] ) ? '1' : '';
                update_post_meta( $post_id, "mpcrbm_{$kind}_enabled", $enabled );

                $fee_type = ( isset( $_POST[ "mpcrbm_{$kind}_fee_type" ] ) && $_POST[ "mpcrbm_{$kind}_fee_type" ] === 'percentage' ) ? 'percentage' : 'fixed';
                update_post_meta( $post_id, "mpcrbm_{$kind}_fee_type", $fee_type );

                if ( isset( $_POST[ "mpcrbm_{$kind}_fee" ] ) ) {
                    $raw_fee = sanitize_text_field( wp_unslash( $_POST[ "mpcrbm_{$kind}_fee" ] ) );
                    update_post_meta( $post_id, "mpcrbm_{$kind}_fee", is_numeric( $raw_fee ) ? floatval( $raw_fee ) : 0 );
                }
            }
        }

        // =========================================================
        // Read-side helpers — used by checkout (MPCRBM_Woocommerce.php)
        // and anywhere else that needs to know if/how much to charge.
        // =========================================================
        public static function is_enabled( $post_id, $kind ) {
            return (bool) get_post_meta( $post_id, "mpcrbm_{$kind}_enabled", true );
        }

        /** Returns the calculated fee for $kind ('delivery' or 'collection'). */
        public static function get_fee( $post_id, $kind, $base_price = 0 ) {
            if ( ! self::is_enabled( $post_id, $kind ) ) {
                return 0.0;
            }
            $amount = get_post_meta( $post_id, "mpcrbm_{$kind}_fee", true );
            $amount = ( $amount !== '' && $amount !== false ) ? floatval( $amount ) : 0.0;
            if ( $amount <= 0 ) {
                return 0.0;
            }
            $type = get_post_meta( $post_id, "mpcrbm_{$kind}_fee_type", true );
            if ( $type === 'percentage' ) {
                return $base_price > 0 ? round( $base_price * $amount / 100, 2 ) : 0.0;
            }
            return round( $amount, 2 );
        }
    }

    new MPCRBM_Delivery_Collection_Settings();
}
