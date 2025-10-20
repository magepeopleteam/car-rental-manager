<?php
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Dependencies' ) ) {
		class MPCRBM_Dependencies {
			public function __construct() {
				add_action( 'init', array( $this, 'language_load' ) );
				$this->load_file();
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 80 );
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ), 80 );
			}

			public function language_load(): void {
				$plugin_dir = basename( dirname( __DIR__ ) ) . "/languages/";
				load_plugin_textdomain( 'car-rental-manager', false, $plugin_dir );
			}

			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Function.php';
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Query.php';
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Layout.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Admin.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Frontend.php';
			}

			public function global_enqueue() {
				do_action( 'mpcrbm_common_script' );
				wp_enqueue_style('mage-icons', MPCRBM_PLUGIN_URL . '/assets/mage-icon/css/mage-icon.css', array(), time());
			}

			public function admin_enqueue() {
				$this->global_enqueue();
				// custom
				wp_enqueue_style( 'mpcrbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_admin.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_price_set', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_price_set.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_order_list', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_order_list.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_manage_taxonomy', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_manage_taxonomy.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_admin.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mpcrbm_order_lists', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_order_lists.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mpcrbm_manage_taxonomy', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_manage_taxonomy.js', array( 'jquery' ), time(), true );
				$nonce = wp_create_nonce( 'mpcrbm_extra_service' );
				wp_localize_script( 'mpcrbm_admin', 'mpcrbm_admin_nonce', array(
					'nonce' => $nonce
				) );
				// Trigger the action hook to add additional scripts if needed
				do_action( 'mpcrbm_admin_script' );
			}

			public function frontend_enqueue() {
				$this->global_enqueue();
				wp_enqueue_style( 'mpcrbm_frontend', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_frontend.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_search_shortcode', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_search_shortcode.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_frontend', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_frontend.js', array( 'jquery' ), time(), true );
				wp_enqueue_style( 'mpcrbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_registration.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_registration.js', array( 'jquery' ), time(), true );
				// Localize scripts
				wp_localize_script( 'mpcrbm_registration', 'mpcrbm_ajax', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'mpcrbm_transportation_type_nonce' )
				) );
				wp_localize_script( 'mpcrbm_registration', 'mpcrbmL10n', array(
					'nameLabel'  => __( 'Name : ', 'car-rental-manager' ),
					'qtyLabel'   => __( 'Quantity : ', 'car-rental-manager' ),
					'priceLabel' => __( 'Price : ', 'car-rental-manager' )
				) );
				do_action( 'mpcrbm_frontend_script' );
			}
		}
		new MPCRBM_Dependencies();
	}
