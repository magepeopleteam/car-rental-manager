<?php
/*
 * @Author      MagePeople Team
 * Copyright:   mage-people.com
 */
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Security_Deposit_Setting' ) ) {

    class MPCRBM_Security_Deposit_Setting {

        public function __construct() {
            add_action( 'mpcrbm_settings_tab_content', [ $this, 'security_deposit_settings' ], 10, 1 );
            add_action( 'save_post', [ $this, 'save_security_deposit_settings' ] );
        }

        public function security_deposit_settings( $post_id ) {
            $enable = get_post_meta( $post_id, 'mpcrbm_security_deposit_enable', true );
            $amount = get_post_meta( $post_id, 'mpcrbm_security_deposit', true );
            $type   = get_post_meta( $post_id, 'mpcrbm_security_deposit_type', true );
            $type   = ( $type === 'percentage' ) ? 'percentage' : 'fixed';

            $is_checked      = ( $enable === 'on' ) ? 'checked' : '';
            $section_display = ( $enable === 'on' ) ? 'block' : 'none';
            $amount          = ( $amount !== '' && $amount !== false ) ? floatval( $amount ) : '';

            wp_nonce_field( 'mpcrbm_save_security_deposit', 'mpcrbm_security_deposit_nonce' );
            $currency = get_woocommerce_currency_symbol();
            $unit     = $type === 'percentage' ? '%' : $currency;
            ?>
            <style>
            .mpcrbm-sd-card{background:#fff;border:1px solid #e5e9f0;border-radius:12px;overflow:hidden;margin-bottom:14px;}
            .mpcrbm-sd-card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f0f2f5;}
            .mpcrbm-sd-card-header-info{display:flex;align-items:center;gap:12px;}
            .mpcrbm-sd-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;}
            .mpcrbm-sd-icon-blue{background:#eff6ff;color:#2563eb;}
            .mpcrbm-sd-icon-green{background:#f0fdf4;color:#16a34a;}
            .mpcrbm-sd-icon-orange{background:#fff7ed;color:#ea580c;}
            .mpcrbm-sd-label{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
            .mpcrbm-sd-desc{font-size:12px;color:#6b7280;margin:0;}
            .mpcrbm-sd-body{padding:20px;}
            .mpcrbm-sd-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;}
            .mpcrbm-sd-type-option{position:relative;}
            .mpcrbm-sd-type-option input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
            .mpcrbm-sd-type-label{display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e5e9f0;border-radius:10px;cursor:pointer;transition:all .2s;background:#fafbfc;}
            .mpcrbm-sd-type-label:hover{border-color:#93c5fd;background:#eff6ff;}
            .mpcrbm-sd-type-option input[type=radio]:checked + .mpcrbm-sd-type-label{border-color:#2563eb;background:#eff6ff;}
            .mpcrbm-sd-type-dot{width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;}
            .mpcrbm-sd-type-option input[type=radio]:checked + .mpcrbm-sd-type-label .mpcrbm-sd-type-dot{border-color:#2563eb;background:#2563eb;}
            .mpcrbm-sd-type-dot::after{content:'';width:6px;height:6px;border-radius:50%;background:#fff;display:none;}
            .mpcrbm-sd-type-option input[type=radio]:checked + .mpcrbm-sd-type-label .mpcrbm-sd-type-dot::after{display:block;}
            .mpcrbm-sd-type-text strong{display:block;font-size:13px;font-weight:600;color:#111827;}
            .mpcrbm-sd-type-text span{font-size:11px;color:#6b7280;}
            .mpcrbm-sd-amount-label{font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;display:block;}
            .mpcrbm-sd-amount-wrap{display:flex;align-items:center;border:2px solid #e5e9f0;border-radius:10px;overflow:hidden;transition:border-color .2s;max-width:280px;}
            .mpcrbm-sd-amount-wrap:focus-within{border-color:#2563eb;}
            .mpcrbm-sd-amount-prefix{padding:10px 14px 0px 14px;background:#f3f4f6;border-right:1px solid #e5e9f0;height:44px;display:flex;align-items:center;font-weight:700;font-size:15px;color:#374151;min-width:44px;justify-content:center;}
            #mpcrbm_security_deposit{border:none!important;outline:none!important;box-shadow:none!important;padding:0 14px;height:44px;font-size:15px;font-weight:600;color:#111827;width:100%;background:#fff;}
            .mpcrbm-sd-hint{font-size:12px;color:#9ca3af;margin-top:6px;}
            .mpcrbm-sd-hint b{color:#6b7280;}
            .mpcrbm-sd-type-text{
                display: flex !important;
                gap: 10px;
            }
            </style>

            <div class="tabsItem" data-tabs="#mpcrbm_security_deposit">
                <h2><?php esc_html_e( 'Security Deposit', 'car-rental-manager' ); ?></h2>
                <p><?php esc_html_e( 'Configure security deposit settings for this vehicle.', 'car-rental-manager' ); ?></p>

                <!-- Enable toggle card -->
                <div class="mpcrbm-sd-card">
                    <div class="mpcrbm-sd-card-header">
                        <div class="mpcrbm-sd-card-header-info">
                            <div class="mpcrbm-sd-icon mpcrbm-sd-icon-blue">
                                <span class="dashicons dashicons-shield"></span>
                            </div>
                            <div>
                                <p class="mpcrbm-sd-label"><?php esc_html_e( 'Enable Security Deposit', 'car-rental-manager' ); ?></p>
                                <p class="mpcrbm-sd-desc"><?php esc_html_e( 'Require a refundable deposit at the time of booking.', 'car-rental-manager' ); ?></p>
                            </div>
                        </div>
                        <label class="roundSwitchLabel">
                            <input type="checkbox"
                                   class="mpcrbm_switch_checkbox"
                                   id="mpcrbm_security_deposit_enable"
                                   name="mpcrbm_security_deposit_enable"
                                <?php echo esc_attr( $is_checked ); ?>>
                            <span class="roundSwitch" data-collapse-target="#mpcrbm_security_deposit_enable"></span>
                        </label>
                    </div>
                </div>

                <!-- Configuration card (shown when enabled) -->
                <div class="mpcrbm-sd-card" id="mpcrbm_security_deposit_enable_holder" style="display:<?php echo esc_attr( $section_display ); ?>">

                    <!-- Deposit type -->
                    <div class="mpcrbm-sd-card-header">
                        <div class="mpcrbm-sd-card-header-info">
                            <div class="mpcrbm-sd-icon mpcrbm-sd-icon-green">
                                <span class="dashicons dashicons-tag"></span>
                            </div>
                            <div>
                                <p class="mpcrbm-sd-label"><?php esc_html_e( 'Deposit Type', 'car-rental-manager' ); ?></p>
                                <p class="mpcrbm-sd-desc"><?php esc_html_e( 'Choose how the deposit amount is calculated.', 'car-rental-manager' ); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mpcrbm-sd-body">
                        <div class="mpcrbm-sd-type-grid">
                            <div class="mpcrbm-sd-type-option">
                                <input type="radio" name="mpcrbm_security_deposit_type" value="fixed"
                                       id="mpcrbm_deposit_type_fixed" <?php checked( $type, 'fixed' ); ?>>
                                <label class="mpcrbm-sd-type-label" for="mpcrbm_deposit_type_fixed">
                                    <span class="mpcrbm-sd-type-dot"></span>
                                    <span class="mpcrbm-sd-type-text">
                                        <strong><?php esc_html_e( 'Fixed Amount', 'car-rental-manager' ); ?></strong>
                                        <span><?php esc_html_e( 'e.g. $200 per booking', 'car-rental-manager' ); ?></span>
                                    </span>
                                </label>
                            </div>
                            <div class="mpcrbm-sd-type-option">
                                <input type="radio" name="mpcrbm_security_deposit_type" value="percentage"
                                       id="mpcrbm_deposit_type_percentage" <?php checked( $type, 'percentage' ); ?>>
                                <label class="mpcrbm-sd-type-label" for="mpcrbm_deposit_type_percentage">
                                    <span class="mpcrbm-sd-type-dot"></span>
                                    <span class="mpcrbm-sd-type-text">
                                        <strong><?php esc_html_e( 'Percentage', 'car-rental-manager' ); ?></strong>
                                        <span><?php esc_html_e( 'e.g. 10% of booking', 'car-rental-manager' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Amount input -->
                        <label class="mpcrbm-sd-amount-label" for="mpcrbm_security_deposit">
                            <span id="mpcrbm_deposit_desc_fixed" style="display:<?php echo $type === 'fixed' ? 'inline' : 'none'; ?>"><?php esc_html_e( 'Deposit Amount', 'car-rental-manager' ); ?></span>
                            <span id="mpcrbm_deposit_desc_percentage" style="display:<?php echo $type === 'percentage' ? 'inline' : 'none'; ?>"><?php esc_html_e( 'Deposit Percentage', 'car-rental-manager' ); ?></span>
                        </label>
                        <div class="mpcrbm-sd-amount-wrap">
                            <span class="mpcrbm-sd-amount-prefix" id="mpcrbm_deposit_unit"><?php echo esc_html( $unit ); ?></span>
                            <input type="number"
                                   id="mpcrbm_security_deposit"
                                   name="mpcrbm_security_deposit"
                                   value="<?php echo esc_attr( $amount ); ?>"
                                   min="0" step="0.01"
                                   placeholder="<?php echo $type === 'percentage' ? esc_attr__( 'e.g. 10', 'car-rental-manager' ) : esc_attr__( 'e.g. 200', 'car-rental-manager' ); ?>" />
                        </div>
                        <p class="mpcrbm-sd-hint">
                            <span id="mpcrbm_deposit_hint_fixed" style="display:<?php echo $type === 'fixed' ? 'inline' : 'none'; ?>">
                                <?php esc_html_e( 'A flat fee collected at booking and refunded after the rental.', 'car-rental-manager' ); ?>
                            </span>
                            <span id="mpcrbm_deposit_hint_percentage" style="display:<?php echo $type === 'percentage' ? 'inline' : 'none'; ?>">
                                <?php esc_html_e( 'Calculated as a % of the total booking price at checkout.', 'car-rental-manager' ); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <script>
            (function($){
                var currencySymbol = '<?php echo esc_js( $currency ); ?>';
                $('[name="mpcrbm_security_deposit_type"]').on('change', function(){
                    var val = $(this).val();
                    if ( val === 'percentage' ) {
                        $('#mpcrbm_deposit_desc_fixed, #mpcrbm_deposit_hint_fixed').hide();
                        $('#mpcrbm_deposit_desc_percentage, #mpcrbm_deposit_hint_percentage').show();
                        $('#mpcrbm_deposit_unit').text('%');
                        $('#mpcrbm_security_deposit').attr('placeholder', 'e.g. 10');
                    } else {
                        $('#mpcrbm_deposit_desc_fixed, #mpcrbm_deposit_hint_fixed').show();
                        $('#mpcrbm_deposit_desc_percentage, #mpcrbm_deposit_hint_percentage').hide();
                        $('#mpcrbm_deposit_unit').text(currencySymbol);
                        $('#mpcrbm_security_deposit').attr('placeholder', 'e.g. 200');
                    }
                });
            })(jQuery);
            </script>
            <?php
        }

        public function save_security_deposit_settings( $post_id ) {
            if ( ! isset( $_POST['mpcrbm_security_deposit_nonce'] ) ) {
                return;
            }
            $nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_security_deposit_nonce'] ) );
            if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_security_deposit' ) ) {
                return;
            }
            if ( get_post_type( $post_id ) !== MPCRBM_Function::get_cpt() ) {
                return;
            }

            $enable = isset( $_POST['mpcrbm_security_deposit_enable'] ) ? 'on' : 'off';
            update_post_meta( $post_id, 'mpcrbm_security_deposit_enable', $enable );

            if ( $enable === 'on' ) {
                $amount = isset( $_POST['mpcrbm_security_deposit'] ) ? floatval( wp_unslash( $_POST['mpcrbm_security_deposit'] ) ) : 0;
                $amount = max( 0, $amount );
                $type   = ( isset( $_POST['mpcrbm_security_deposit_type'] ) && $_POST['mpcrbm_security_deposit_type'] === 'percentage' ) ? 'percentage' : 'fixed';
                update_post_meta( $post_id, 'mpcrbm_security_deposit', $amount );
                update_post_meta( $post_id, 'mpcrbm_security_deposit_type', $type );
            }
        }

        /**
         * Returns the calculated security deposit amount.
         * For fixed type: returns the stored amount.
         * For percentage type: returns base_price * percentage / 100.
         * Pass $base_price when the deposit type may be percentage.
         */
        public static function get_security_deposit( $post_id, $base_price = 0 ) {
            $enable = get_post_meta( $post_id, 'mpcrbm_security_deposit_enable', true );
            if ( $enable !== 'on' ) {
                return 0;
            }
            $amount = get_post_meta( $post_id, 'mpcrbm_security_deposit', true );
            $amount = ( $amount !== '' && $amount !== false ) ? floatval( $amount ) : 0;
            if ( $amount <= 0 ) {
                return 0;
            }
            $type = get_post_meta( $post_id, 'mpcrbm_security_deposit_type', true );
            if ( $type === 'percentage' ) {
                return $base_price > 0 ? round( $base_price * $amount / 100, 2 ) : 0;
            }
            return $amount;
        }

        /** Returns 'fixed' or 'percentage'. */
        public static function get_security_deposit_type( $post_id ) {
            $enable = get_post_meta( $post_id, 'mpcrbm_security_deposit_enable', true );
            if ( $enable !== 'on' ) {
                return 'fixed';
            }
            $type = get_post_meta( $post_id, 'mpcrbm_security_deposit_type', true );
            return ( $type === 'percentage' ) ? 'percentage' : 'fixed';
        }

        /** Returns the raw stored value (dollar amount for fixed, percentage number for percentage). */
        public static function get_security_deposit_raw_value( $post_id ) {
            $enable = get_post_meta( $post_id, 'mpcrbm_security_deposit_enable', true );
            if ( $enable !== 'on' ) {
                return 0;
            }
            $amount = get_post_meta( $post_id, 'mpcrbm_security_deposit', true );
            return ( $amount !== '' && $amount !== false ) ? floatval( $amount ) : 0;
        }
    }

    new MPCRBM_Security_Deposit_Setting();
}
