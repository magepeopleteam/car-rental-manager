<?php

if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('MPCRBM_Order_List')) {
    class MPCRBM_Order_List
    {
        public static function get_unique_places_and_car_names() {
            $places = [];
            $car_ids = [];
            $car_names = [];

            $booking_posts = get_posts([
                'post_type'      => 'mpcrbm_booking',
                'post_status'    => 'publish',
                'numberposts'    => -1,
                'fields'         => 'ids',
            ]);

            foreach ($booking_posts as $post_id) {
                $start_place = get_post_meta($post_id, 'mpcrbm_start_place', true);
                $car_id = get_post_meta($post_id, 'mpcrbm_id', true);

                if (!empty($start_place) && !in_array($start_place, $places)) {
                    $places[] = $start_place;
                }

                if (!empty($car_id) && !in_array($car_id, $car_ids)) {
                    $car_ids[] = $car_id;
                }
            }

            foreach ($car_ids as $car_post_id) {
                $car_post = get_post($car_post_id);

                if ($car_post && $car_post->post_type === 'mpcrbm_rent') {
                    $car_names[] = $car_post->post_title;
                }
            }

            return [
                'places'    => $places,
                'car_names' => $car_names,
            ];
        }

        public function __construct() {
            if ( is_plugin_active( 'wpcarrentpro/MPCRM_Plugin_Pro.php' ) ) {
                add_action('admin_menu', array($this, 'order_menu'));
            }
        }

        public function order_menu() {
            add_submenu_page('edit.php?post_type=mpcrbm_rent', __('Order List', 'car-rental-manager'), __('Order List', 'car-rental-manager'), 'manage_options', 'mpcrbm_order_list', array($this, 'order_list'));
        }
        public function filter_selection1() {
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
                return '<div class="mpcrbm_order_list_wrapper">'.esc_attr_e( 'No bookings found.', 'car-rental-manager' ).'</div>';
            }

            ob_start();
            ?>

                <div class="mpcrbm_order_list_table_wrap">
                    <table class="mpcrbm_order_list_table">
                        <thead>
                        <tr>
                            <th><?php esc_attr_e( 'Booked Car Details', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'ID', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Order No', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Order User name', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Order User Email', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Order User Phone', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Pickup Date Time', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Return Date Time', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Start Place', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'End Place', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Base Price', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Status', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Payment', 'car-rental-manager' );?></th>
                            <th><?php esc_attr_e( 'Total Price', 'car-rental-manager' );?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order):
                            $order_id = $order->ID;
                            $pickup_date_raw = get_post_meta($order_id, 'mpcrbm_date', true);
                            $pickup_date_only = date('Y-m-d', strtotime($pickup_date_raw));

                            $return_raw = get_post_meta($order_id, 'return_date_time', true);

                            // Format them
                            $mpcrbm_date = $pickup_date_raw ? date('j M Y, g:ia', strtotime($pickup_date_raw)) : '';
                            $return_date_time = $return_raw ? date('j M Y, g:ia', strtotime($return_raw)) : '';
                            $post_id = get_post_meta($order_id, 'mpcrbm_id', true);
                            $name = get_the_title( $post_id );

                            $pickup_place = get_post_meta($order_id, 'mpcrbm_start_place', true);
                            $billing_name = get_post_meta($order_id, 'mpcrbm_billing_name', true);
                            $billing_email = get_post_meta($order_id, 'mpcrbm_billing_email', true);
                            $billing_phone = get_post_meta($order_id, 'mpcrbm_billing_phone', true);

                            $order_status = get_post_meta($order_id, 'mpcrbm_order_status', true);
                            $mpcrbm_order_id = get_post_meta($order_id, 'mpcrbm_order_id', true);

                            $extra_service_info = get_post_meta($order_id, 'mpcrbm_service_info', array());

                            $is_extra_service = false;
                            if( is_array( $extra_service_info[0] ) && !empty( $extra_service_info[0] ) ){
                                $is_extra_service = true;
                            }


                            $row = array(
                                'mpcrbm_car_name'                          => $name,
                                'mpcrbm_id'                     => get_post_meta($order_id, 'mpcrbm_id', true),
                                'mpcrbm_order_id'               => get_post_meta($order_id, 'mpcrbm_order_id', true),
                                'mpcrbm_billing_name'           => get_post_meta($order_id, 'mpcrbm_billing_name', true),
                                'mpcrbm_billing_email'          => get_post_meta($order_id, 'mpcrbm_billing_email', true),
                                'mpcrbm_billing_phone'          => get_post_meta($order_id, 'mpcrbm_billing_phone', true),
                                'mpcrbm_date'                   => $mpcrbm_date,
                                'return_date_time'              => $return_date_time,
                                'mpcrbm_start_place'            => $pickup_place,
                                'mpcrbm_end_place'              => get_post_meta($order_id, 'mpcrbm_end_place', true),
                                'mpcrbm_base_price'             => get_post_meta($order_id, 'mpcrbm_base_price', true),
                                'mpcrbm_order_status'           => $order_status,
                                'mpcrbm_payment_method'         => get_post_meta($order_id, 'mpcrbm_payment_method', true),
                                'mpcrbm_tp'                     => get_post_meta($order_id, 'mpcrbm_tp', true),
                            );
                            ?>
                            <tr class="mpcrbm_order_list_display"
                                    data-filtar-post-name="<?php echo esc_attr( $name )?>"
                                    data-filtar-post-id="<?php echo esc_attr( $post_id )?>"
                                    data-filtar-order-id="<?php echo esc_attr( $mpcrbm_order_id )?>"
                                    data-filtar-pickup-date="<?php echo esc_attr( $pickup_date_only )?>"
                                    data-filtar-pickup-place="<?php echo esc_attr( $pickup_place )?>"
                                    data-filtar-user-name="<?php echo esc_attr( $billing_name )?>"
                                    data-filtar-user-email="<?php echo esc_attr( $billing_email )?>"
                                    data-filtar-user-phone="<?php echo esc_attr( $billing_phone )?>"
                                    data-filtar-order-status="<?php echo esc_attr( $order_status )?>"
                            >
                                <?php foreach ( $row as $key => $value):
                                    if( $key === 'mpcrbm_order_status' ){ ?>
                                        <td>
                                        <select id="mpcrbm_order_status" class="mpcrbm_order_list__select" >
                                            <?php
                                            $order_statuses = wc_get_order_statuses();
                                            foreach ( $order_statuses as $slug => $label ) {
                                                $is_selected = ( strtolower( $label )=== $order_status ) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo esc_attr( $slug );?> " <?php echo esc_attr( $is_selected );?>><?php echo esc_attr( $label );?></option>
                                                <?php
                                            } ?>
                                        </select>
                                        </td>
                                    <?php } else if( $key === 'mpcrbm_car_name' ) {
                                        ?>
                                            <td>
                                                <div class="mpcrbm_car_oder_details">
                                                    <div class="mpcrbm_car_name"><?php echo esc_html($value); ?></div>
                                                    <?php if( $is_extra_service ){?>
                                                        <div class="mpcrbm_order_extra_btn_details">
                                                            <div class="mpcrbm_order_extra_service_btn_holder"><span class="pcrbm_order_extra_service_btn" >Extra Service</span></div>
                                                            <div class="mpcrbm_order_extra_service_holder" style="display: none">
                                                                <?php
                                                                $i = 1;
                                                                foreach ( $extra_service_info[0] as $val ){
                                                                    $data = $i.'. '. $val['service_name'].'('.$val['service_quantity'].'X'.$val['service_price'].') ';
                                                                    ?>
                                                                    <span class=""><?php echo esc_attr( $data )?></span>
                                                                    <?php
                                                                    $i++;
                                                                }?>
                                                            </div>
                                                        </div>

                                                    <?php }?>
                                                </div>

                                            </td>
                                   <?php } else{
                                    ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php } endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
            return ob_get_clean();
        }




        public function order_result() { ?>
             <div class="mpcrbm_order_list_wrapper">
             <h2 class="mpcrbm_order_list_title"><?php esc_attr_e( 'All Booking Orders', 'car-rental-manager' );?></h2>
             <?php
                echo self::filter_selection();
                echo self::mpcrbm_display_orders_with_specific_meta(); ?>
             </div>
            <?php
        }

        public static function filter_Selection(){

            $data = self::get_unique_places_and_car_names();
            ?>
            <div class="mpcrbm_order_list__filter-wrapper">

                <h2 class="mpcrbm_order_list__filter-title"><?php esc_attr_e( 'Filter', 'car-rental-manager' );?></h2>
                <div class="mpcrbm_filter_by_date">
                    <div class="mpcrbm_filter_date mpcrbm_data_selected" id="all"><?php esc_attr_e( 'All', 'car-rental-manager' );?></div>
                    <div class="mpcrbm_filter_date" id="today"><?php esc_attr_e( 'Today', 'car-rental-manager' );?></div>
                    <div class="mpcrbm_filter_date" id="week"><?php esc_attr_e( 'This Week', 'car-rental-manager' );?></div>
                    <div class="mpcrbm_filter_date" id="month"><?php esc_attr_e( 'This Month', 'car-rental-manager' );?></div>
                </div>

                <div class="mpcrbm_order_list__filter-row">

                    <div class="mpcrbm_order_list__filter-item">
                        <label for="mpcrbm_order_list__pickup_place"><?php esc_attr_e( 'Pickup Place', 'car-rental-manager' );?></label>
                        <select id="mpcrbm_order_list__pickup_place" class="mpcrbm_order_list__select" >
                            <option value="all"><?php esc_attr_e( 'All', 'car-rental-manager' );?></option>
                            <?php if( is_array( $data['places'] ) && !empty( $data['places'] ) ){
                                foreach ( $data['places'] as $place ){
                                ?>
                                <option value="<?php echo esc_attr( strtolower( $place ) );?>"><?php echo esc_attr( $place );?></option>
                            <?php } }?>
                        </select>
                    </div>

                    <div class="mpcrbm_order_list__filter-item">
                        <label for="mpcrbm_order_list__post_name"><?php esc_attr_e( 'Filter by Post Name', 'car-rental-manager' );?></label>
                        <select id="mpcrbm_order_list__post_name" class="mpcrbm_order_list__select" >
                            <option value="all"><?php esc_attr_e( 'All', 'car-rental-manager' );?></option>
                            <?php if( is_array( $data['car_names'] ) && !empty( $data['car_names'] ) ){
                                foreach ( $data['car_names'] as $car_name ){
                                    ?>
                                    <option value="<?php echo esc_attr( $car_name );?>"><?php echo esc_attr( $car_name );?></option>
                                <?php } }?>
                        </select>
                    </div>

                    <div class="mpcrbm_order_list__filter-item">
                        <label for="mpcrbm_order_list__post_name"><?php esc_attr_e( 'Filter by Order Status', 'car-rental-manager' );?></label>
                        <select id="mpcrbm_order_list__order_status" class="mpcrbm_order_list__select" >
                            <option value="all"><?php esc_attr_e( 'All', 'car-rental-manager' );?></option>
                            <?php
                            $order_statuses = wc_get_order_statuses();
                            foreach ( $order_statuses as $slug => $label ) { ?>

                                <option value="<?php echo esc_attr( strtolower( $label ) );?>"><?php echo esc_attr( $label );?></option>
                                <?php
                            } ?>
                        </select>
                    </div>

                    <div class="mpcrbm_order_list__filter-item">
                        <label for="mpcrbm_order_list__start_date"><?php esc_attr_e( 'Pickup Date', 'car-rental-manager' );?></label>
                        <input type="date" id="mpcrbm_order_list__start_date" class="mpcrbm_order_list__input">
                    </div>

                      <div class="mpcrbm_order_list_name_filter-item">
                        <label for="mpcrbm_order_list__post_name"><?php esc_attr_e( 'Filter by User Info', 'car-rental-manager' );?></label>
                        <select name="mpcrbm_user_info_filter_by" id="mpcrbm_user_info_filter_by" class="mpcrbm_order_list_user_info_filter">
                            <option value="all"><?php esc_attr_e( 'All', 'car-rental-manager' );?></option>
                            <option value="name"><?php esc_attr_e( 'Name', 'car-rental-manager' );?></option>
                            <option value="email"><?php esc_attr_e( 'Email', 'car-rental-manager' );?></option>
                            <option value="phone"><?php esc_attr_e( 'Phone', 'car-rental-manager' );?></option>
                        </select>
                      </div>

                    <div class="mpcrbm_order_list__filter-item" id="mpcrbm_order_list__user_input_container" style="margin-top: 22px">
                        <input type="text" placeholder="Enter name" id="mpcrbm_user_info_value" class="mpcrbm_order_list__input">
                    </div>

                </div>
            </div>

        <?php }

        public function order_list() {
            ?>
            <div class="wrap">
                <div class="ttbm_style">

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