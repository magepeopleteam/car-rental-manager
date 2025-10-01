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
            public function default_attribute() {
                return array(
                    "cat" => "0",
                    "org" => "0",
                    "style" => 'list',
                    "show" => '9',
                    "pagination" => "yes",
                    "city" => "",
                    "country" => "",
                    'sort' => 'ASC',
                    'status' => '',
                    "pagination-style" => "load_more",
                    "column" => 3,
                    "price_based" => 'manual',
                    'progressbar'=>'yes',
                    'map'=>'yes',
                    'form'=>'horizontal',
                    'title'=>'yes',
                );
            }


            public function mpcrbm_display_search_result( $content ) {

                $search_page_slug = MPCRBM_Global_Function::get_settings('mpcrbm_general_settings', 'enable_view_search_result_page');

                if ( ! empty( $search_page_slug ) && is_page( $search_page_slug ) ) {

                    if ( session_status() === PHP_SESSION_NONE ) {
                        session_start();
                    }
                    $result_data = isset($_SESSION['custom_content']) ? $_SESSION['custom_content'] : '';
                    $progress_bar = isset($_SESSION['progress_bar']) ? $_SESSION['progress_bar'] : '';
                    if ( isset($_SESSION['custom_content'] ) ) {
                        unset($_SESSION['custom_content']);
                        unset($_SESSION['progress_bar']);
                    }
                    session_write_close();

                    $content = '';
                    $search_attribute = [ 'form'=>'inline', 'title'=>'no', 'ajax_search' => 'yes', 'progressbar' => 'no' ];
                    $search_defaults = MPCRBM_Shortcodes::default_attribute();
                    $params = shortcode_atts( $search_defaults, $search_attribute );

                    ob_start();
                    do_action( 'mpcrbm_transport_search', $params );
                    $action_output = ob_get_clean();



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
		}
		new MPCRBM_Frontend();
	}