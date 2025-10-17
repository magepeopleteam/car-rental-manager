<?php
	/*
   * @Author 		MagePeople Team
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Admin' ) ) {
		class MPCRBM_Admin {
			public function __construct() {
				if ( is_admin() ) {
					$this->load_file();
					add_action( 'init', [ $this, 'add_dummy_data' ] );
					add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
					add_filter( 'wp_mail_content_type', array( $this, 'email_content_type' ) );
					add_action( 'upgrader_process_complete', [ $this, 'flush_rewrite' ], 0 );
				}
			}

			public function flush_rewrite() {
				flush_rewrite_rules();
			}

			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Dummy_Import.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Hidden_Product.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_CPT.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Quick_Setup.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Status.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Guideline.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_License.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Taxonomies.php';
				//****************Global settings************************//
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Settings_Global.php';
				//****************Taxi settings************************//
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_General_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Price_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Extra_Service.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Date_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Tax_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Operation_Area_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Multi_Location_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Gallery_Imges_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Manage_Faq.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Faq_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Manage_Feature.php';
			}

			public function add_dummy_data() {
				new MPCRBM_Dummy_Import();
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
		new MPCRBM_Admin();
	}