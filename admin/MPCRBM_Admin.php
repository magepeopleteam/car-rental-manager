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
				// MPCRBM_CPT registers the mpcrbm_rent CPT and the mpcrbm_car_type /
				// mpcrbm_fuel_type / mpcrbm_car_brand / mpcrbm_seating_capacity /
				// mpcrbm_make_year taxonomies (hooked to 'init', which fires on every
				// request) — loading it only inside the is_admin() block below meant
				// these taxonomies were never registered on the frontend at all, so
				// any frontend template querying them (e.g. templates/registration/
				// vehicle_item.php's car type/fuel type/brand/year/seating spec grid,
				// via wp_get_post_terms()) always got "Invalid taxonomy" and rendered
				// every car's specs as blank dashes — regardless of whether the car
				// actually has terms assigned.
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_CPT.php';

				if ( is_admin() ) {
					$this->load_file();
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
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Admin_Shell.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Status.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Guideline.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_License.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Taxonomies.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Manage_Faq.php';
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
				
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Faq_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Manage_Feature.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Term_Condition_Setting.php';
                require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Security_Deposit_Setting.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_User_Branch_Manager.php';
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