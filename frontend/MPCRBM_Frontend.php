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
		}
		new MPCRBM_Frontend();
	}