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



			}
			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Shortcodes.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Transport_Search.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Woocommerce.php';
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

            public function mpcrbm_display_search_result( $content ) {

                $search_page_slug = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');

                if ( ! empty( $search_page_slug ) && is_page( $search_page_slug ) ) {
                    $search_form_with_result = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'search_form_with_search_result', 'on' );
                    if ( session_status() === PHP_SESSION_NONE ) {
                        session_start();
                    }
                    $result_data = isset($_SESSION['custom_content']) ? $_SESSION['custom_content'] : '';
                    $progress_bar = isset($_SESSION['progress_bar']) ? $_SESSION['progress_bar'] : '';
                    $search_date = isset($_SESSION['search_date']) ? $_SESSION['search_date'] : '';
                    if ( isset($_SESSION['custom_content'] ) ) {
                        unset($_SESSION['custom_content']);
                        unset($_SESSION['progress_bar']);
                        unset($_SESSION['search_date']);
                    }
                    session_write_close();

                    $content = '';
                    $search_attribute = [ 'form'=>'inline', 'title'=>'no', 'ajax_search' => 'yes', 'progressbar' => 'no' ];
                    $search_defaults = MPCRBM_Shortcodes::default_attribute();
                    $params = shortcode_atts( $search_defaults, $search_attribute );

                    if( $search_form_with_result === 'yes' ){
                        ob_start();
                        do_action( 'mpcrbm_transport_search', $params, $search_date );
                        $action_output = ob_get_clean();
                    }else{
                        $action_output = '';
                    }




                    if ( !empty( $result_data) ) {
                        $progressbar_class = '';

                        $content = '<main id="maincontent" class="transport-result-page" style=" max-width: 1200px;">';
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
                        $content .= '<p class="mpcrbm_empty_result" id="mpcrbm_empty_result">No search results found.</p>';
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
                    'meta_query'     => [
                        [
                            'key'     => 'mpcrbm_id',
                            'value'   => $post_id,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ]
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

            public function mpcrbm_get_total_count_price_selected_car() {
                $nonce = sanitize_text_field( wp_unslash( $_POST['_nonce'] ?? '' ) );
                if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
                    wp_send_json_error( array(
                        'message' => __( 'Security check failed', 'mpcrbm' ),
                    ) );
                }

                $start_date = sanitize_text_field( wp_unslash( $_POST['start_date'] ) );
                $start_time = sanitize_text_field( wp_unslash( $_POST['start_time'] ) );

                $start_date_time = date(
                    'Y-m-d H:i',
                    strtotime(
                        $start_date . ' ' .
                        sprintf('%02d:%02d', $start_time, $start_time)
                    )
                );


                // Get posted values
                $car_id = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
                $days  = isset( $_POST['total_days'] ) ? absint( wp_unslash( $_POST['total_days'] ) ) : 1;
                $total_price  = isset( $_POST['total_price'] ) ? absint( wp_unslash( $_POST['total_price'] ) ) : 0;

                $calculated_price = 0;
                if ( $car_id && $days > 0 ) {
                    $calculated_price = MPCRBM_Function::mpcrbm_calculate_price( $car_id, $start_date_time, $days, $total_price );
                }

                wp_send_json_success( array(
                    'calculated_price' => $calculated_price,
                ) );
            }

        }
		new MPCRBM_Frontend();
	}