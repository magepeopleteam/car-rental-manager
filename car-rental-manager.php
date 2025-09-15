<?php
	/**
	 * Plugin Name:       Car Rental Manager
	 * Plugin URI:        https://wordpress.org/plugins/car-rental-manager
	 * Description:       A complete car rental solution for WordPress by MagePeople. Manage bookings, vehicles, pricing, and availability with ease.
	 * Version:           1.0.1
	 * Requires at least: 5.6
	 * Requires PHP:      7.2
	 * Author:            MagePeople Team
	 * Author URI:        https://www.mage-people.com/
	 * License:           GPL v2 or later
	 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
	 * Text Domain:       car-rental-manager
	 * Domain Path:       /languages
	 * Tested up to:      6.7
	 * Stable tag:        1.0.0
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Plugin' ) ) {
		class MPCRBM_Plugin {
			public function __construct() {
				$this->load_plugin();
				add_filter( 'theme_page_templates', array( $this, 'activation_template_create' ), 10, 3 );
				add_filter( 'template_include', array( $this, 'change_page_template' ), 99 );
				add_action( 'admin_init', array( $this, 'assign_template_to_page' ) );
                add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
			}

			private function load_plugin(): void {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if ( ! defined( 'MPCRBM_PLUGIN_DIR' ) ) {
					define( 'MPCRBM_PLUGIN_DIR', dirname( __FILE__ ) );
				}
				if ( ! defined( 'MPCRBM_PLUGIN_URL' ) ) {
					define( 'MPCRBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
				}
				if ( ! defined( 'MPCRBM_PLUGIN_VERSION' ) ) {
					define( 'MPCRBM_PLUGIN_VERSION', '1.0.0' );
				}
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/MPCRBM_Global_File_Load.php';
				if ( MPCRBM_Global_Function::check_woocommerce() == 1 ) {
					add_action( 'activated_plugin', array( $this, 'activation_redirect' ), 90, 1 );
					self::on_activation_page_create();
					require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Dependencies.php';
				} else {
					require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Quick_Setup.php';
					//add_action('admin_notices', [$this, 'woocommerce_not_active']);
					add_action( 'activated_plugin', array( $this, 'activation_redirect_setup' ), 90, 1 );
				}
			}

			public function activation_redirect( $plugin ) {
				$quick_setup_done = get_option( 'mpcrbm_quick_setup_done' );
				if ( $plugin == plugin_basename( __FILE__ ) && $quick_setup_done != 'yes' ) {
					wp_safe_redirect( admin_url( 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup' ) );
					exit();
				}
			}

			public function activation_redirect_setup( $plugin ) {
				$quick_setup_done = get_option( 'mpcrbm_quick_setup_done' );
				if ( $plugin == plugin_basename( __FILE__ ) && $quick_setup_done != 'yes' ) {
					wp_safe_redirect( admin_url( 'admin.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup' ) );
					exit();
				}
			}

			public static function on_activation_page_create(): void {
				if ( did_action( 'wp_loaded' ) ) {
					self::create_pages();
				} else {
					add_action( 'wp_loaded', array( __CLASS__, 'create_pages' ) );
				}
			}

			public static function create_pages() {
				// Create pages only if they don't exist
				if ( ! MPCRBM_Global_Function::get_page_by_slug( 'mpcrbm-search' ) ) {
					$search_page = array(
						'post_type'    => 'page',
						'post_name'    => 'mpcrbm-search',
						'post_title'   => 'Search Transport',
						'post_content' => '[mpcrbm_booking]',
						'post_status'  => 'publish'
					);
					wp_insert_post( $search_page );
				}

				if ( ! MPCRBM_Global_Function::get_page_by_slug( 'mpcrbm-search-inline' ) ) {
					$search_page = array(
						'post_type'    => 'page',
						'post_name'    => 'mpcrbm-search-inline',
						'post_title'   => 'Search Transport Inline',
						'post_content' => '[mpcrbm_booking form="inline"]',
						'post_status'  => 'publish'
					);
					wp_insert_post( $search_page );
				}

				if ( ! MPCRBM_Global_Function::get_page_by_slug( 'transport-result' ) ) {
					$search_page = array(
						'post_type'    => 'page',
						'post_name'    => 'transport-result',
						'post_title'   => 'Car Search Result',
						'post_status'  => 'publish'
					);
					wp_insert_post( $search_page );
				}
			}

			public function activation_template_create( $templates ) {
				$template_path                    = 'transport_result.php';
				$page_templates[ $template_path ] = 'Car Result';
				foreach ( $page_templates as $tk => $tv ) {
					$templates[ $tk ] = $tv;
				}
				flush_rewrite_rules();

				return $templates;
			}

			public function change_page_template( $template ) {
				$page_temp_slug                   = get_page_template_slug( get_the_ID() );
				$template_path                    = 'transport_result.php';
				$page_templates[ $template_path ] = 'Car Result';
				if ( isset( $page_templates[ $page_temp_slug ] ) ) {
					$template = plugin_dir_path( __FILE__ ) . '/' . $page_temp_slug;
				}

				return $template;
			}

			public function assign_template_to_page() {
				// Check if the page 'transport-result' exists
				$page = get_page_by_path( 'transport-result' );
				if ( $page ) {
					// Update the page meta to assign the template
					update_post_meta( $page->ID, '_wp_page_template', 'transport_result.php' );
				}
			}

            /**
             * Enqueue frontend assets
             */
            public function enqueue_frontend_assets() {
                if (is_page_template('transport-result.php')) {
                    wp_enqueue_style(
                        'mpcrbm-file-upload',
                        MPCRBM_PLUGIN_URL . '/assets/css/file-upload.css',
                        array(),
	                    MPCRBM_PLUGIN_VERSION
                    );
                }
            }

		}
		new MPCRBM_Plugin();
	}
