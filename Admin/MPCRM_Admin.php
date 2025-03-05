<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRM_Admin')) {
		class MPCRM_Admin {
			public function __construct() {
				if (is_admin()) {
					$this->load_file();
					add_action('init', [$this, 'add_dummy_data']);
					add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2);
					add_filter('wp_mail_content_type', array($this, 'email_content_type'));
					add_action('upgrader_process_complete', [$this, 'flush_rewrite'], 0);
				}
			}
			public function flush_rewrite() {
				flush_rewrite_rules();
			}
			private function load_file(): void {
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Dummy_Import.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Hidden_Product.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_CPT.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Quick_Setup.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Status.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Guideline.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_License.php';
				//****************Global settings************************//
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Settings_Global.php';
				//****************Taxi settings************************//
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_General_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_Price_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_Extra_Service.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_Date_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_Tax_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/settings/MPCRM_Operation_Area_Settings.php';
				//****************Woocommerce Checkout*********************** */
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Wc_Checkout_Settings.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Wc_Checkout_Fields.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Wc_Checkout_Billing.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Wc_Checkout_Shipping.php';
				require_once MPCRM_PLUGIN_DIR . '/Admin/MPCRM_Wc_Checkout_Order.php';
			}
			public function add_dummy_data() {
				new MPCRM_Dummy_Import();
			}
			//************Disable Gutenberg************************//
			public function disable_gutenberg($current_status, $post_type) {
				$user_status = MPCRM_Global_Function::get_settings('mp_global_settings', 'disable_block_editor', 'yes');
				if ($post_type === MPCRM_Function::get_cpt() && $user_status == 'yes') {
					return false;
				}
				return $current_status;
			}
			//*************************//
			public function email_content_type() {
				return "text/html";
			}
		}
		new MPCRM_Admin();
	}