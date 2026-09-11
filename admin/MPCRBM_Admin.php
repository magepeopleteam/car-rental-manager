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
					add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], PHP_INT_MAX, 2 );
					add_filter( 'use_block_editor_for_post', [ $this, 'disable_gutenberg_for_car' ], PHP_INT_MAX, 2 );
					add_action( 'do_meta_boxes', [ $this, 'limit_car_metaboxes' ], PHP_INT_MAX, 2 );
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
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_CPT.php';
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
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Damage_Management_Setting.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_User_Branch_Manager.php';
				//****************Payments (WooCommerce vs Custom Payment)****************//
				// Loaded whether or not WooCommerce is active — the Payments tab is exactly
				// where an admin goes to install it, or to choose Custom Payment instead.
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_WC_Payment_Manager.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/settings/MPCRBM_Payment_Settings.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Payment_Notices.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Booking_List_Free.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Customers.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Quote_Requests.php';
			}

			//************Disable Gutenberg************************//
			public function disable_gutenberg( $current_status, $post_type ) {
				if ( $post_type === MPCRBM_Function::get_cpt() ) {
					return false;
				}

				return $current_status;
			}

			/**
			 * Prevent a post-specific editor filter from re-enabling Gutenberg.
			 *
			 * @param bool    $current_status Whether the block editor is enabled.
			 * @param WP_Post $post           Post being edited.
			 * @return bool
			 */
			public function disable_gutenberg_for_car( $current_status, $post ) {
				if ( $post instanceof WP_Post && $post->post_type === MPCRBM_Function::get_cpt() ) {
					return false;
				}

				return $current_status;
			}

			/**
			 * Keep the Add/Edit Car screen focused on the plugin settings wizard.
			 *
			 * The publish box remains in the DOM because the custom top-bar buttons
			 * proxy its native controls. The featured-image box is moved into the
			 * General Info tab by mpcrbm-shell.js. Both are hidden from their native
			 * sidebar positions by the shell stylesheet.
			 *
			 * @param string $post_type Current post type.
			 * @param string $context   Current meta-box context.
			 */
			public function limit_car_metaboxes( $post_type, $context ): void {
				if ( $post_type !== MPCRBM_Function::get_cpt() ) {
					return;
				}

				global $wp_meta_boxes;

				if ( empty( $wp_meta_boxes[ $post_type ][ $context ] ) ) {
					return;
				}

				$allowed_metaboxes = [
					'mpcrbm_meta_box_panel',
					'postimagediv',
					'submitdiv',
				];

				foreach ( $wp_meta_boxes[ $post_type ][ $context ] as $priority => $metaboxes ) {
					foreach ( array_keys( $metaboxes ) as $metabox_id ) {
						if ( ! in_array( $metabox_id, $allowed_metaboxes, true ) ) {
							unset( $wp_meta_boxes[ $post_type ][ $context ][ $priority ][ $metabox_id ] );
						}
					}
				}
			}

			//*************************//
			public function email_content_type() {
				return "text/html";
			}
		}
		new MPCRBM_Admin();
	}
