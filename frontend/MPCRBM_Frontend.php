<?php
	/*
	* @Author 		magePeople
	* Copyright: 	mage-people.com
	*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Frontend')) {
		class MPCRBM_Frontend {
			public function __construct() {
				$this->load_file();
				add_filter('single_template', array($this, 'load_single_template'));

                add_filter('the_content', array($this, 'mpcrbm_display_search_result'));

                add_action( 'wp_ajax_mpcrbm_get_total_count_price_selected_car', [ $this, 'mpcrbm_get_total_count_price_selected_car' ] );
                add_action( 'wp_ajax_nopriv_mpcrbm_get_total_count_price_selected_car', [ $this, 'mpcrbm_get_total_count_price_selected_car' ] );

                add_action( 'wp_ajax_mpcrbm_get_car_qty_by_date', [ $this, 'mpcrbm_get_car_qty_by_date' ] );
                add_action( 'wp_ajax_nopriv_mpcrbm_get_car_qty_by_date', [ $this, 'mpcrbm_get_car_qty_by_date' ] );

			}
			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Shortcodes.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Transport_Search.php';
				// Holds mpcrbm_add_to_cart(), the single entry point for "Book Now" in
				// BOTH booking modes, so it loads whether or not WooCommerce is active.
				// Its WooCommerce hooks simply never fire without WooCommerce.
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Woocommerce.php';
				// Free standalone checkout for the built-in Offline method. Stands down on
				// its own when the Pro plugin (MPCRBM_Native_Checkout) is active.
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Offline_Checkout.php';
				// admin/MPCRBM_Customers.php is mostly the wp-admin "Customers" screen,
				// but it ALSO registers the customer-blocklist checkout enforcement
				// (both WooCommerce checkout UIs + this Custom Payment flow) and the
				// discount coupon's "valid from" date check. MPCRBM_Admin only requires
				// this file behind is_admin() — true for wp-admin and admin-ajax.php
				// requests, but FALSE for the customer's own checkout page and the
				// Store API's REST request, which is exactly where this enforcement
				// needs to run. Requiring it here too (require_once is idempotent) is
				// what actually makes blocking take effect instead of silently
				// registering hooks that only ever fire in the admin screen that set
				// them up. The admin-only parts of the file (menu registration, page
				// rendering, its own wp_ajax_* handlers) are harmless dead weight on
				// the frontend — the WP hooks they're bound to just never fire here.
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Admin_Shell.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Booking_List_Free.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Customers.php';
			}
			public function load_single_template($template): string {
				global $post;
				if ($post->post_type && $post->post_type == MPCRBM_Function::get_cpt()) {
					$template = MPCRBM_Function::template_path('single_page/mpcrbm_details.php');
				}
				if ($post->post_type && $post->post_type == 'transport_booking') {
					$template = MPCRBM_Function::template_path('single_page/transport_booking.php');
				}
				return $template;
			}

            public static function mpcrbm_get_conyent_shortcode( $content ) {
                $atts = [];
                if ( has_shortcode( $content, 'mpcrbm_booking' ) ) {
                    $pattern = get_shortcode_regex( array( 'mpcrbm_booking' ) );
                    if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
                        foreach ( $matches[0] as $key => $shortcode ) {
                            $atts = shortcode_parse_atts( $matches[3][ $key ] );
                        }
                    }
                }

                return $atts;
            }

            public function mpcrbm_display_search_result( $content ) {

                $search_shortcode_code = self::mpcrbm_get_conyent_shortcode( $content );

                $search_page_slug = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');

                if ( ! empty( $search_page_slug ) && is_page( $search_page_slug ) ) {
                    $search_form_with_result = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'search_form_with_search_result', 'on' );
                    if ( session_status() === PHP_SESSION_NONE ) {
                        session_start();
                    }
                    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $result_data = isset($_SESSION['custom_content']) ? $_SESSION['custom_content'] : '';
                    $progress_bar = isset($_SESSION['progress_bar']) ? $_SESSION['progress_bar'] : '';
                    $search_date = isset($_SESSION['search_date']) ? $_SESSION['search_date'] : '';
                    // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                    if ( isset($_SESSION['custom_content'] ) ) {
                        unset($_SESSION['custom_content']);
                        unset($_SESSION['progress_bar']);
                        unset($_SESSION['search_date']);
                    }
                    session_write_close();

                    /*$user_id = MPCRBM_Transport_Search::get_guest_unique_id();
                    $result_data = get_transient('wtbm_custom_content_' . $user_id);
                    $progress_bar = get_transient('wtbm_progress_bar_' . $user_id);
                    $search_date = get_transient('wtbm_search_date_' . $user_id);

                    delete_transient('wtbm_custom_content_' . $user_id);
                    delete_transient('wtbm_progress_bar_' . $user_id);
                    delete_transient('wtbm_search_date_' . $user_id);*/

                    $content = '';
                    if( $result_data ){
                        $search_result = 'no';
                    }else{
                        $search_result = isset( $search_shortcode_code['search_result'] ) ? $search_shortcode_code['search_result'] : 'no';
                    }
                    $title        = isset( $search_shortcode_code['title'] ) ? $search_shortcode_code['title'] : 'no';
                    $ajax_search  = isset( $search_shortcode_code['ajax_search'] ) ? $search_shortcode_code['ajax_search'] : 'no';
                    $progressbar  = isset( $search_shortcode_code['progressbar'] ) ? $search_shortcode_code['progressbar'] : 'no';

                    $search_attribute = [
                        'form'=>'inline',
                        'title'=>$title,
                        'ajax_search' => $ajax_search,
                        'progressbar' => $progressbar,
                        'search_result' => $search_result,
                    ];


                    $search_defaults = MPCRBM_Shortcodes::default_attribute();
                    $params = shortcode_atts( $search_defaults, $search_attribute );
                    ob_start();
                    do_action( 'mpcrbm_transport_search', $params, $search_date );
                    $action_output = ob_get_clean();
                   // }




                    if ( !empty( $result_data) ) {
                        $progressbar_class = '';

                        $content = '<main id="maincontent" class="transport-result-page" style=" max-width: 1200px; margin:auto">';
                        $content .= '<div class="mpcrbm mpcrbm_transport_search_area" style="margin: auto; width: 100%">';
                        $content .= '<div class="mpcrbm_tab_next _mT">';

                        if( $progress_bar === 'no' ){
                            $progressbar_class = 'dNone';
                        }else{
                            $progressbar_class = '';
                        }
                        $content .= '<div class="tabListsNext ' . esc_attr($progressbar_class) . '" id="mpcrbm_progress_bar_holder">';
                        $content .= '<div data-tabs-target-next="#mpcrbm_pick_up_details" class="tabItemNext active" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                    <h4 class="circleIcon" data-class>
                                        <span class="mp_zero" data-icon></span>
                                        <span class="mp_zero" data-text>1</span>
                                    </h4>
                                    <h6 class="circleTitle" data-class>' . esc_html__('Enter Ride Details', 'car-rental-manager') . '</h6>
                                </div>';

                        $content .= '<div data-tabs-target-next="#mpcrbm_search_result" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                    <h4 class="circleIcon" data-class>
                                        <span class="mp_zero" data-icon></span>
                                        <span class="mp_zero" data-text>2</span>
                                    </h4>
                                    <h6 class="circleTitle" data-class>' . esc_html__('Choose a vehicle', 'car-rental-manager') . '</h6>
                                </div>';

                        $content .= '<div data-tabs-target-next="#mpcrbm_order_summary" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                    <h4 class="circleIcon" data-class>
                                        <span class="mp_zero" data-icon></span>
                                        <span class="mp_zero" data-text>3</span>
                                    </h4>
                                    <h6 class="circleTitle" data-class>' . esc_html__('Place Order', 'car-rental-manager') . '</h6>
                                </div>';

                        $content .= '</div>';

                        $content .= '<div class="tabsContentNext">';
                        $content .= '<div data-tabs-next="#mpcrbm_pick_up_details" class="active mpcrbm_pick_up_details">';
                        $content .= $action_output;
                        $content .= $result_data;
                        $content .= '</div>';
                        $content .= '</div>';

                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</main>';

                    }else{
                        $content .= $action_output;
                        if( empty( $action_output ) ){
                            $content .= '<p class="mpcrbm_empty_result" id="mpcrbm_empty_result">No search results found.</p>';
                        }

                    }
                }

                return $content;
            }


            /**
             * Get all booking dates between mpcrbm_date and return_date_time
             * from all mpcrbm_booking posts
             *
             * @return array
             */
            public static function mpcrbm_get_all_booking_dates_between_start_end( $post_id ) {

                $args = [
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'relation' => 'AND',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => [
                        [
                            'key'     => 'mpcrbm_id',
                            'value'   => $post_id,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ],
                        [
                            'key'     => 'mpcrbm_order_status',
                            'value'   => ['cancelled', 'refunded', 'failed'],
                            'compare' => 'NOT IN',
                        ],
                    ]
                ];

                $query = new WP_Query( $args );

                $all_dates = [];

                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();

                        $start_datetime = get_post_meta( get_the_ID(), 'mpcrbm_date', true );
                        $end_datetime   = get_post_meta( get_the_ID(), 'return_date_time', true );

                        if ( empty( $start_datetime ) || empty( $end_datetime ) ) {
                            continue;
                        }

                        $start = new DateTime( $start_datetime );
                        $end   = new DateTime( $end_datetime );

                        $start->setTime( 0, 0, 0 );
                        $end->setTime( 0, 0, 0 );

                        $end->modify( '+1 day' );

                        $interval = new DateInterval( 'P1D' );
                        $period   = new DatePeriod( $start, $interval, $end );

                        foreach ( $period as $date ) {
                            $all_dates[] = $date->format( 'Y-m-d' );
                        }
                    }

                    wp_reset_postdata();
                }

                $all_dates = array_values( array_unique( $all_dates ) );
                sort( $all_dates );

                return $all_dates;
            }
            public static function mpcrbm_get_unavailable_dates_by_stock( $post_id, $exclude_booking_id = 0 ) {

                $stock = (int) MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_car_stock', 1 );

                if ( $stock <= 0 ) {
                    return [];
                }

                // 🔹 Step 2: Get all active bookings for this car
                $args = [
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => [
                        'relation' => 'AND',
                        [
                            'key'     => 'mpcrbm_id',
                            'value'   => $post_id,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ],
                        [
                            'key'     => 'mpcrbm_order_status',
                            'value'   => ['cancelled', 'refunded', 'failed'],
                            'compare' => 'NOT IN',
                        ],
                    ]
                ];

                $query = new WP_Query( $args );

                $daily_bookings = [];

                if ( ! empty( $query->posts ) ) {

                    foreach ( $query->posts as $booking_id ) {

                        if ( $exclude_booking_id && (int) $booking_id === (int) $exclude_booking_id ) {
                            continue;
                        }

                        $start_datetime = get_post_meta( $booking_id, 'mpcrbm_date', true );
                        $end_datetime   = get_post_meta( $booking_id, 'return_date_time', true );
                        $qty            = (int) get_post_meta( $booking_id, 'mpcrbm_car_quantity', true );

                        if ( empty( $start_datetime ) || empty( $end_datetime ) ) {
                            continue;
                        }

                        $start = new DateTime( $start_datetime );
                        $end   = new DateTime( $end_datetime );

                        $start->setTime( 0, 0, 0 );
                        $end->setTime( 0, 0, 0 );
                        $end->modify( '+1 day' );

                        $interval = new DateInterval( 'P1D' );
                        $period   = new DatePeriod( $start, $interval, $end );

                        foreach ( $period as $date ) {

                            $formatted = $date->format( 'Y-m-d' );

                            if ( ! isset( $daily_bookings[$formatted] ) ) {
                                $daily_bookings[$formatted] = 0;
                            }

                            $daily_bookings[$formatted] += $qty;
                        }
                    }
                }

                // 🔹 Step 3: Find fully booked dates
                $unavailable_dates = [];

                foreach ( $daily_bookings as $date => $booked_qty ) {

                    if ( $booked_qty >= $stock ) {
                        $unavailable_dates[] = $date;
                    }
                }

                sort( $unavailable_dates );

                return $unavailable_dates;
            }


            public function mpcrbm_get_total_count_price_selected_car() {
                $nonce = sanitize_text_field( wp_unslash( $_POST['_nonce'] ?? '' ) );
                if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
                    wp_send_json_error( array(
                        'message' => __( 'Security check failed', 'car-rental-manager' ),
                    ) );
                }

                $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
                $start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '';

                // decimal_time_to_hi() understands the plugin's decimal clock notation
                // ("10.3" = 10:30). The previous sprintf( '%02d:%02d', $t, $t ) fed the
                // SAME number in as both hours and minutes, so 10.30 became "10:10" and
                // 0.5 became "00:00" — a wrong hour to test seasonal date ranges against.
                $start_date_time = gmdate(
                    'Y-m-d H:i',
                    strtotime( $start_date . ' ' . MPCRBM_Function::decimal_time_to_hi( $start_time ) )
                );


                // Get posted values
                $car_id = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
                $days  = isset( $_POST['total_days'] ) ? absint( wp_unslash( $_POST['total_days'] ) ) : 1;
                // floatval, NOT absint: this is the undiscounted base for the whole stay,
                // and absint() truncated every fractional rate — a 59.50/day car over 3
                // days came in as 178 instead of 178.50, so the price shown on the car
                // page never quite matched the one the cart charged.
                $total_price  = isset( $_POST['total_price'] ) ? (float) wp_unslash( $_POST['total_price'] ) : 0;
                $total_price  = max( 0, $total_price );

                $calculated_price = 0;
                if ( $car_id && $days > 0 ) {
                    $calculated_price = MPCRBM_Function::mpcrbm_calculate_price( $car_id, $start_date_time, $days, $total_price );
                }

                wp_send_json_success( array(
                    'calculated_price' => $calculated_price,
                ) );
            }

            public static function mpcrbm_get_available_stock_by_date( $car_id, $date ) {

                $total_stock = (int) MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_car_stock', 1 );

                if ( $total_stock <= 0 ) {
                    return 0;
                }

                $start_datetime = $date . ' 00:00:00';
                $end_datetime   = $date . ' 23:59:59';

                $args = [
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => [
                        'relation' => 'AND',
                        [
                            'key'     => 'mpcrbm_id',
                            'value'   => $car_id,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ],
                        [
                            'key'     => 'mpcrbm_date',
                            'value'   => $end_datetime,
                            'compare' => '<=',
                            'type'    => 'DATETIME',
                        ],
                        [
                            'key'     => 'return_date_time',
                            'value'   => $start_datetime,
                            'compare' => '>=',
                            'type'    => 'DATETIME',
                        ],
                        [
                            'key'     => 'mpcrbm_order_status',
                            'value'   => ['cancelled', 'refunded', 'failed'],
                            'compare' => 'NOT IN',
                        ],
                    ]
                ];

                $query = new WP_Query( $args );

                $total_booked = 0;

                if ( ! empty( $query->posts ) ) {

                    foreach ( $query->posts as $booking_id ) {

                        $qty = (int) get_post_meta( $booking_id, 'mpcrbm_car_quantity', true );
                        $total_booked += $qty;
                    }
                }

                // 🔹 Step 3: Calculate available stock
                $available_stock = $total_stock - $total_booked;

                return max( 0, $available_stock );
            }

            public function mpcrbm_get_car_qty_by_date() {
                $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
                $qty_html = '';
                if ( wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
                    $car_id     = isset( $_POST['car_id'] ) ? sanitize_text_field( wp_unslash( $_POST['car_id'] ) ) : '';
                    $date       = isset( $_POST['startDate'] ) ? sanitize_text_field( wp_unslash( $_POST['startDate'] ) ) : '';
                    $day_price  = isset( $_POST['day_wise_price'] ) ? sanitize_text_field( wp_unslash( $_POST['day_wise_price'] ) ) : '';
                    if( $car_id && $date ){
                        ob_start();
                        $available_stock = MPCRBM_Frontend::mpcrbm_get_available_stock_by_date( $car_id, $date );
                        if( $available_stock > 1 ){
                        ?>
                            <div class="mpcrbm_car_quantity_title"><?php esc_html_e('Car Quantity', 'car-rental-manager') ?></div>
                            <?php
                            MPCRBM_Custom_Layout::qty_input('mpcrbm_get_car_qty', $day_price, $available_stock, 1, 0);
                            ?>
                        <?php
                        }
//                        MPCRBM_Custom_Layout::qty_input('mpcrbm_get_car_qty', $day_price, $available_stock, 1, 0);
                        $qty_html = ob_get_clean();
                    }

                }

               wp_send_json_success( $qty_html );

            }


        }
		new MPCRBM_Frontend();
	}