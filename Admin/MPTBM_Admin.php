<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPTBM_Admin' ) ) {
		class MPTBM_Admin {
			public function __construct() {
				if ( is_admin() ) {
					$this->load_file();
					add_action( 'init', [ $this, 'mpcrm_add_dummy_data' ] );
					add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
					add_filter( 'wp_mail_content_type', array( $this, 'email_content_type' ) );
					add_action( 'upgrader_process_complete', [ $this, 'flush_rewrite' ], 0 );
				}
			}

			public function flush_rewrite() {
				flush_rewrite_rules();
			}

			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_Dummy_Import.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_Hidden_Product.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_CPT.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Quick_Setup.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_Status.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_Guideline.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_License.php';
				//****************Global settings************************//
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Settings_Global.php';
				//****************Taxi settings************************//
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPTBM_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_General_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_Price_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_Extra_Service.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_Date_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_Tax_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPTBM_Operation_Area_Settings.php';
			}

			public function mpcrm_add_dummy_data() {
				new MPTBM_Dummy_Import();
			}

			//************Disable Gutenberg************************//
			public function disable_gutenberg( $current_status, $post_type ) {
				$user_status = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'disable_block_editor', 'yes' );
				if ( $post_type === MPCRBM_Function::get_cpt() && $user_status == 'yes' ) {
					return false;
				}

				return $current_status;
			}

			//*************************//
			public function email_content_type() {
				return "text/html";
			}
		}
		new MPTBM_Admin();
	}