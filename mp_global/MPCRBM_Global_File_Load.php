<?php
	/*
	* @Author       MagePeople Team
	* Copyright:    mage-people.com
	*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Global_File_Load' ) ) {
		class MPCRBM_Global_File_Load {
			public function __construct() {
				$this->load_global_file();
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 80 );
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ), 80 );
				add_action( 'admin_head', array( $this, 'mpcrbm_admin_head' ), 5 );
				add_action( 'wp_head', array( $this, 'mpcrbm_frontend_head' ), 5 );
			}
			public function load_global_file() {
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Global_Function.php';
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Global_Style.php';
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Custom_Layout.php';
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Custom_Slider.php';
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Select_Icon_image.php';
				require_once MPCRBM_PLUGIN_DIR . '/mp_global/class/MPCRBM_Setting_API.php';
			}

			public function global_enqueue() {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-datepicker' );				
				wp_enqueue_style('mp_jquery_ui', MPCRBM_PLUGIN_URL . '/mp_global/assets/jquery-ui.min.css', array(), '1.13.2');
				wp_enqueue_style('mp_font_awesome', MPCRBM_PLUGIN_URL . '/mp_global/assets/font_awesome/css/all.min.css', array(), '5.15.4');
				wp_enqueue_style('mp_select_2', MPCRBM_PLUGIN_URL . '/mp_global/assets/select_2/select2.min.css', array(), '4.0.13');
				wp_enqueue_script('mp_select_2', MPCRBM_PLUGIN_URL . '/mp_global/assets/select_2/select2.min.js', array(), '4.0.13');
				wp_enqueue_style('mp_owl_carousel', MPCRBM_PLUGIN_URL . '/mp_global/assets/owl_carousel/owl.carousel.min.css', array(), '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPCRBM_PLUGIN_URL . '/mp_global/assets/owl_carousel/owl.carousel.min.js', array(), '2.3.4');
				// Cache busting using file modification time
				wp_enqueue_style('mpcrbm_global', MPCRBM_PLUGIN_URL . 'mp_global/assets/mp_style/mpcrbm_global.css', array(), filemtime( __DIR__ . '/assets/mp_style/mpcrbm_global.css'));
				wp_enqueue_script('mpcrbm_global', MPCRBM_PLUGIN_URL . 'mp_global/assets/mp_style/mpcrbm_global.js', array(), filemtime( __DIR__ . '/assets/mp_style/mpcrbm_global.js'));
				do_action( 'mpcrbm_global_enqueue' );
			}

			public function admin_enqueue() {
				$this->global_enqueue();
				wp_enqueue_editor();
				wp_enqueue_media();
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp-codemirror' );
				wp_enqueue_script( 'wp-codemirror' );
				// Admin-specific styles and scripts
				wp_enqueue_style('jquery-timepicker', MPCRBM_PLUGIN_URL . '/mp_global/assets/admin/jquery.timepicker.min.css', array(), filemtime(__DIR__ . '/assets/admin/jquery.timepicker.min.css'));
				wp_enqueue_style('jquery-timepicker', MPCRBM_PLUGIN_URL . '/mp_global/assets/admin/jquery.timepicker.min.js', array(), filemtime(__DIR__ . '/assets/admin/jquery.timepicker.min.js'));
				//=====================//
				wp_enqueue_script('form-field-dependency', MPCRBM_PLUGIN_URL . '/mp_global/assets/admin/form-field-dependency.js', array('jquery'), null, false);
				// admin setting global
				wp_enqueue_script('mpcrbm_admin_settings', MPCRBM_PLUGIN_URL . '/mp_global/assets/admin/mpcrbm_admin_settings.js', array('jquery'), filemtime(__DIR__ . '/assets/admin/mpcrbm_admin_settings.js'), true);
				wp_enqueue_style('mpcrbm_admin_settings', MPCRBM_PLUGIN_URL . '/mp_global/assets/admin/mpcrbm_admin_settings.css', array(), filemtime(__DIR__ . '/assets/admin/mpcrbm_admin_settings.css'));
				
				// Multi-location support assets
				wp_enqueue_style('mpcrbm_multi_location', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_multi_location.css', array(), MPCRBM_PLUGIN_VERSION);
				wp_enqueue_script('mpcrbm_multi_location', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_multi_location.js', array('jquery'), MPCRBM_PLUGIN_VERSION, true);
				do_action( 'mpcrbm_admin_enqueue' );
			}

			public function frontend_enqueue() {
				$this->global_enqueue();
				do_action( 'mpcrbm_frontend_enqueue' );
			}

			public function mpcrbm_admin_head() {
				$this->js_constant();
			}

			public function mpcrbm_frontend_head() {
				$this->js_constant();
			}

			public function js_constant() {
				// Register and enqueue your JavaScript file (if you have one)
				wp_register_script( 'mpcrbm-custom-js-constant', MPCRBM_PLUGIN_URL . '/mp_global/assets/mpcr-custom-js-constant.js', array( 'jquery' ), '1.0', true );
				wp_enqueue_script( 'mpcrbm-custom-js-constant' );
				// Prepare inline JavaScript
				$mp_js_constants = '
                let mpcrbm_currency_symbol = "";
                let mpcrbm_currency_position = "";
                let mpcrbm_currency_decimal = "";
                let mpcrbm_currency_thousands_separator = "";
                let mpcrbm_num_of_decimal = "";
                let mpcrbm_ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
                let mpcrbm_site_url = "' . esc_url( site_url() ) . '";
                let mpcrbm_empty_image_url = "' . esc_attr( MPCRBM_PLUGIN_URL . '/mp_global/assets/images/no_image.png' ) . '";
                let mpcrbm_date_format = "' . esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'date_format', 'D d M , yy' ) ) . '";
                let mpcrbm_date_format_without_year = "' . esc_attr( MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'date_format_without_year', 'D d M' ) ) . '";
            ';
				// Check if WooCommerce is active
				if ( MPCRBM_Global_Function::check_woocommerce() == 1 ) {
					$mp_js_constants .= '
                    mpcrbm_currency_symbol = "' . esc_js( get_woocommerce_currency_symbol() ) . '";
                    mpcrbm_currency_position = "' . esc_attr( get_option( "woocommerce_currency_pos" ) ) . '";
                    mpcrbm_currency_decimal = "' . esc_html( wc_get_price_decimal_separator() ) . '";
                    mpcrbm_currency_thousands_separator = "' . esc_js( wc_get_price_thousand_separator() ) . '";
                    mpcrbm_num_of_decimal = "' . absint( get_option( "woocommerce_price_num_decimals", 2 ) ) . '";
                ';
				}
				// Add inline script after enqueuing main script
				wp_add_inline_script( 'mpcrbm-custom-js-constant', $mp_js_constants, 'before' );
			}
		}
		new MPCRBM_Global_File_Load();
	}