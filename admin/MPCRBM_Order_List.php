<?php

if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('MPCRBM_Order_List')) {
    class MPCRBM_Order_List
    {
        public function __construct() {
            add_action('admin_menu', array($this, 'order_menu'));
        }

        public function order_menu() {
            add_submenu_page('edit.php?post_type=mpcrbm_rent', __('Order List', 'car-rental-manager'), __('Order List', 'car-rental-manager'), 'manage_options', 'mpcrbm_order_list', array($this, 'order_list'));
        }
        public function filter_selection() {
            $tour_label = MPCRBM_Function::get_name();
            ?>
            <div class="ttbm_order_filter_area">
                <div class="_dLayout_pRelative placeholder_area">
                    <h4 class="title_on_border"><?php echo esc_html($tour_label) . ' ' . esc_html__(' Order List', 'ttbm-pro'); ?></h4>
                    <div class="justifyCenter _mb attendee_filter_list" data-placeholder>
                        <div class="dFlex">
                            <h5><?php esc_html_e('Filter List By:', 'ttbm-pro'); ?></h5>
                            <label class="customRadioLabel"><input type="radio" name='attendee_filter' value='tour' checked/> <span class="customRadio"><?php echo esc_html($tour_label); ?></span></label>
                            <label class="customRadioLabel" data-placeholder="<?php _e('Please Enter Ticket No..', 'ttbm-pro') ?>"><input type="radio" name='attendee_filter' value='ttbm_pin'/> <span class="customRadio"><?php _e('Ticket No', 'ttbm-pro'); ?></span></label>
                            <label class="customRadioLabel" data-placeholder="<?php _e('Please Enter Order ID..', 'ttbm-pro') ?>"><input type="radio" name='attendee_filter' value='ttbm_order_id'/> <span class="customRadio"><?php _e('Order ID', 'ttbm-pro'); ?></span></label>
                        </div>
                    </div>
                    <div class="justifyCenter" data-placeholder>
                        <div class="dFlex min_400">
<!--                            --><?php //TTBM_Layout::tour_list_in_select(); ?>
                            <label class="dNone max_400 attendee_text_select"><input type="text" name='filter_key' placeholder="" value='' class="formControl"></label>
                            <div class="buttonGroup max_400">
                                <button class="_themeButton" type="button" id="get_ttbm_order_result"><?php _e('Filter', 'ttbm-pro'); ?></button>
                                <button class="_warningButton" id="ttbm_order_filter_reset" type="button"><?php _e('Reset', 'ttbm-pro'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }


        public static function mpcrbm_display_orders_with_specific_meta() {
            $args = array(
                'post_type'      => 'mpcrbm_booking',
                'post_status'    => 'publish',
                'posts_per_page' => -1
            );

            $orders = get_posts($args);

            if (empty($orders)) {
                return '<div class="mpcrbm_order_list_wrapper">No bookings found.</div>';
            }

            ob_start();
            ?>
            <div class="mpcrbm_order_list_wrapper">
                <h2 class="mpcrbm_order_list_title">All Booking Orders</h2>
                <div class="mpcrbm_order_list_table_wrap">
                    <table class="mpcrbm_order_list_table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Order No</th>
                            <th>Pickup Date Time</th>
                            <th>Return Date Time</th>
                            <th>Start Place</th>
                            <th>End Place</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total Price</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order):
                            $order_id = $order->ID;
                            $date_raw = get_post_meta($order_id, 'mpcrbm_date', true);
                            $return_raw = get_post_meta($order_id, 'return_date_time', true);

                            // Format them
                            $mpcrbm_date = $date_raw ? date('j M Y, g:ia', strtotime($date_raw)) : '';
                            $return_date_time = $return_raw ? date('j M Y, g:ia', strtotime($return_raw)) : '';
                            $post_id = get_post_meta($order_id, 'mpcrbm_id', true);
                            $name = get_the_title( $post_id );


                            $row = array(
                                'name'                      => $name,
                                'mpcrbm_id'                 => get_post_meta($order_id, 'mpcrbm_id', true),
                                'mpcrbm_order_id'           => get_post_meta($order_id, 'mpcrbm_order_id', true),
                                'mpcrbm_date'               => $mpcrbm_date,
                                'return_date_time'          => $return_date_time,
                                'mpcrbm_start_place'        => get_post_meta($order_id, 'mpcrbm_start_place', true),
                                'mpcrbm_end_place'          => get_post_meta($order_id, 'mpcrbm_end_place', true),
                                'mpcrbm_base_price'         => get_post_meta($order_id, 'mpcrbm_base_price', true),
                                'mpcrbm_order_status'       => get_post_meta($order_id, 'mpcrbm_order_status', true),
                                'mpcrbm_payment_method'     => get_post_meta($order_id, 'mpcrbm_payment_method', true),
                                'mpcrbm_tp'                 => get_post_meta($order_id, 'mpcrbm_tp', true),
                            );
                            ?>
                            <tr
                                    data-filtar-name="<?php echo esc_attr( $name )?>"
                                    data-filtar-pickup-date="<?php echo esc_attr( $name )?>"
                            >
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }




        public function order_result() {
            echo self::mpcrbm_display_orders_with_specific_meta();
        }

        public function order_list() {
            ?>
            <div class="wrap">
                <div class="ttbm_style">
<!--                    --><?php //$this->filter_selection(); ?>
                    <div id="ttbm_order_list_result">
                        <?php $this->order_result(); ?>
                    </div>
                </div>
            </div>
            <?php
        }

    }

    new MPCRBM_Order_List();
}