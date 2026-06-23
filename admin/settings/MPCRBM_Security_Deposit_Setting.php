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
            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_security_deposit">
                <h2><?php esc_html_e( 'Security Deposit', 'car-rental-manager' ); ?></h2>
                <p><?php esc_html_e( 'Configure security deposit settings for this vehicle.', 'car-rental-manager' ); ?></p>

                <section>
                    <div class="label">
                        <div>
                            <h6><?php esc_html_e( 'Enable Security Deposit', 'car-rental-manager' ); ?></h6>
                            <span class="desc"><?php esc_html_e( 'By default security deposit is OFF. Enable to require a deposit at booking.', 'car-rental-manager' ); ?></span>
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
                </section>

                <section id="mpcrbm_security_deposit_enable_holder" style="display: <?php echo esc_attr( $section_display ); ?>">
                    <div class="label">
                        <div>
                            <h6><?php esc_html_e( 'Deposit Type', 'car-rental-manager' ); ?></h6>
                            <span class="desc"><?php esc_html_e( 'Choose whether the deposit is a fixed amount or a percentage of the booking price.', 'car-rental-manager' ); ?></span>
                        </div>
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio"
                                       name="mpcrbm_security_deposit_type"
                                       value="fixed"
                                       id="mpcrbm_deposit_type_fixed"
                                    <?php checked( $type, 'fixed' ); ?> />
                                <?php esc_html_e( 'Fixed Amount', 'car-rental-manager' ); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio"
                                       name="mpcrbm_security_deposit_type"
                                       value="percentage"
                                       id="mpcrbm_deposit_type_percentage"
                                    <?php checked( $type, 'percentage' ); ?> />
                                <?php esc_html_e( 'Percentage of Booking Price', 'car-rental-manager' ); ?>
                            </label>
                        </div>
                    </div>

                    <label class="label">
                        <div>
                            <h6><?php esc_html_e( 'Security Deposit Value', 'car-rental-manager' ); ?></h6>
                            <span class="desc" id="mpcrbm_deposit_desc_fixed" style="display: <?php echo $type === 'fixed' ? 'inline' : 'none'; ?>">
                                <?php esc_html_e( 'Enter the fixed deposit amount required per booking.', 'car-rental-manager' ); ?>
                            </span>
                            <span class="desc" id="mpcrbm_deposit_desc_percentage" style="display: <?php echo $type === 'percentage' ? 'inline' : 'none'; ?>">
                                <?php esc_html_e( 'Enter the deposit as a percentage of the booking price (e.g. 10 for 10%).', 'car-rental-manager' ); ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <input type="number"
                                   class="formControl"
                                   id="mpcrbm_security_deposit"
                                   name="mpcrbm_security_deposit"
                                   value="<?php echo esc_attr( $amount ); ?>"
                                   min="0"
                                   step="0.01"
                                   placeholder="<?php esc_attr_e( 'e.g. 200', 'car-rental-manager' ); ?>"
                                   style="max-width: 160px;" />
                            <span id="mpcrbm_deposit_unit" style="font-weight: 600; font-size: 1em;">
                                <?php echo $type === 'percentage' ? '%' : esc_html( get_woocommerce_currency_symbol() ); ?>
                            </span>
                        </div>
                    </label>
                </section>
            </div>
            <script>
                (function($){
                    var currencySymbol = '<?php echo esc_js( get_woocommerce_currency_symbol() ); ?>';
                    $('[name="mpcrbm_security_deposit_type"]').on('change', function(){
                        var val = $(this).val();
                        if ( val === 'percentage' ) {
                            $('#mpcrbm_deposit_desc_fixed').hide();
                            $('#mpcrbm_deposit_desc_percentage').show();
                            $('#mpcrbm_deposit_unit').text('%');
                            $('#mpcrbm_security_deposit').attr('placeholder', 'e.g. 10');
                        } else {
                            $('#mpcrbm_deposit_desc_fixed').show();
                            $('#mpcrbm_deposit_desc_percentage').hide();
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
