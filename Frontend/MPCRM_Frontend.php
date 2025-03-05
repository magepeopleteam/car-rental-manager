<?php
	/*
	* @Author 		magePeople
	* Copyright: 	mage-people.com
	*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRM_Frontend')) {
		class MPCRM_Frontend {
			public function __construct() {
				$this->load_file();
				add_filter('single_template', array($this, 'load_single_template'));
			}
			private function load_file(): void {
				require_once MPCRM_PLUGIN_DIR . '/Frontend/MPCRM_Shortcodes.php';
				require_once MPCRM_PLUGIN_DIR . '/Frontend/MPCRM_Transport_Search.php';
				require_once MPCRM_PLUGIN_DIR . '/Frontend/MPCRM_Woocommerce.php';
				require_once MPCRM_PLUGIN_DIR . '/Frontend/MPCRM_Wc_Checkout_Fields_Helper.php';
			}
			public function load_single_template($template): string {
				global $post;
				if ($post->post_type && $post->post_type == MPCRM_Function::get_cpt()) {
					$template = MPCRM_Function::template_path('single_page/mptbm_details.php');
				}
				if ($post->post_type && $post->post_type == 'transport_booking') {
					$template = MPCRM_Function::template_path('single_page/transport_booking.php');
				}
				return $template;
			}
		}
		new MPCRM_Frontend();
	}