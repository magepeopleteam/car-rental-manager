<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCR_Global_File_Load')) {
		class MPCR_Global_File_Load {
			public function __construct() {
				$this->define_constants();
				$this->load_global_file();
				
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
				add_action('transporter_panel_admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
				add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
				add_action('admin_head', array($this, 'add_admin_head'), 5);
				add_action('wp_head', array($this, 'add_frontend_head'), 5);
			}
			public function define_constants() {
				if (!defined('MPCR_GLOBAL_PLUGIN_DIR')) {
					define('MPCR_GLOBAL_PLUGIN_DIR', dirname(__FILE__));
				}
				if (!defined('MPCR_GLOBAL_PLUGIN_URL')) {
					define('MPCR_GLOBAL_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));
				}
			}
			public function load_global_file() {
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Global_Function.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Global_Style.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Custom_Layout.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Custom_Slider.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Select_Icon_image.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MAGE_Setting_API.php';
				require_once MPCR_GLOBAL_PLUGIN_DIR . '/class/MPCR_Settings_Global.php';
			}
			public function global_enqueue() {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_style('mp_jquery_ui', MPCR_GLOBAL_PLUGIN_URL . '/assets/jquery-ui.min.css', array(), '1.13.2');
				wp_enqueue_style('mp_font_awesome', MPCR_GLOBAL_PLUGIN_URL . '/assets/font_awesome/css/all.min.css', array(), '5.15.4'); 
				wp_enqueue_style('mp_select_2', MPCR_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.css', array(), '4.0.13');
				wp_enqueue_script('mp_select_2', MPCR_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13');
				wp_enqueue_style('mp_owl_carousel', MPCR_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.css', array(), '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPCR_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.js', array(), '2.3.4');
				wp_enqueue_style('mp_plugin_global', MPCR_GLOBAL_PLUGIN_URL . '/assets/mp_style/mp_style.css', array(), time());
				wp_enqueue_script('mp_plugin_global', MPCR_GLOBAL_PLUGIN_URL . '/assets/mp_style/mp_script.js', array('jquery'), time(), true);
				do_action('add_mp_global_enqueue');
			}
			public function admin_enqueue() {
				$this->global_enqueue();
				wp_enqueue_editor();
				wp_enqueue_media();
				//admin script
				wp_enqueue_script('jquery-ui-sortable');
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_script('wp-color-picker');
				wp_enqueue_style('wp-codemirror');
				wp_enqueue_script('wp-codemirror');
				//wp_enqueue_script('jquery-ui-accordion');
				//loading Time picker
				wp_enqueue_style('jquery.timepicker.min', MPCR_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.css', array(), time());
				wp_enqueue_script('jquery-timepicker', MPCR_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.js', array('jquery'), time(), true);
				//=====================//
				wp_enqueue_script('form-field-dependency', MPCR_GLOBAL_PLUGIN_URL . '/assets/admin/form-field-dependency.js', array('jquery'), null, false);
				// admin setting global
				wp_enqueue_script('mp_admin_settings', MPCR_GLOBAL_PLUGIN_URL . '/assets/admin/mp_admin_settings.js', array('jquery'), time(), true);
				wp_enqueue_style('mp_admin_settings', MPCR_GLOBAL_PLUGIN_URL . '/assets/admin/mp_admin_settings.css', array(), time());
				do_action('add_mp_admin_enqueue');
			}
			public function frontend_enqueue() {
				$this->global_enqueue();
				do_action('add_mp_frontend_enqueue');
			}
			public function add_admin_head() {
				$this->js_constant();
			}
			public function add_frontend_head() {
				$this->js_constant();
				$this->custom_css();
			}
			public function js_constant() {
				// Register and enqueue your JavaScript file (if you have one)
				wp_register_script('mpcr-custom-js-constant', plugin_dir_url(__FILE__) . 'assets/mpcr-custom-js-constant.js', array('jquery'), '1.0', true);
				wp_enqueue_script('mpcr-custom-js-constant');
			
				// Prepare inline JavaScript
				$mp_js_constants = '
					let mp_currency_symbol = "";
					let mp_currency_position = "";
					let mp_currency_decimal = "";
					let mp_currency_thousands_separator = "";
					let mp_num_of_decimal = "";
					let mp_ajax_url = "' . esc_url(admin_url('admin-ajax.php')) . '";
					let mp_empty_image_url = "' . esc_attr(MPCR_GLOBAL_PLUGIN_URL . '/assets/images/no_image.png') . '";
					let mp_date_format = "' . esc_attr(MPCR_Global_Function::get_settings('mp_global_settings', 'date_format', 'D d M , yy')) . '";
					let mp_date_format_without_year = "' . esc_attr(MPCR_Global_Function::get_settings('mp_global_settings', 'date_format_without_year', 'D d M')) . '";
				';
			
				// Check if WooCommerce is active
				if (MPCR_Global_Function::check_woocommerce() == 1) {
					$mp_js_constants .= '
						mp_currency_symbol = "' . esc_js(get_woocommerce_currency_symbol()) . '";
						mp_currency_position = "' . esc_attr(get_option("woocommerce_currency_pos")) . '";
						mp_currency_decimal = "' . esc_html(wc_get_price_decimal_separator()) . '";
						mp_currency_thousands_separator = "' . esc_js(wc_get_price_thousand_separator()) . '";
						mp_num_of_decimal = "' . absint(get_option("woocommerce_price_num_decimals", 2)) . '";
					';
				}
			
				// Add inline script after enqueuing main script
				wp_add_inline_script('mpcr-custom-js-constant', $mp_js_constants, 'before');
			}
			
			
			public function custom_css() {
				$custom_css = MPCR_Global_Function::get_settings('mp_add_custom_css', 'custom_css');
				ob_start();
				?>
				<style>
					<?php echo esc_html( $custom_css ); ?>
				</style>
				<?php
				echo wp_kses_post( ob_get_clean() );
			}
		}
		new MPCR_Global_File_Load();
	}