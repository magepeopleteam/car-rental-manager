<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRM_Global_File_Load')) {
		class MPCRM_Global_File_Load {
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
				if (!defined('MPCRM_GLOBAL_PLUGIN_DIR')) {
					define('MPCRM_GLOBAL_PLUGIN_DIR', dirname(__FILE__));
				}
				if (!defined('MPCRM_GLOBAL_PLUGIN_URL')) {
					define('MPCRM_GLOBAL_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));
				}
			}
			public function load_global_file() {
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Global_Function.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Global_Style.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Custom_Layout.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Custom_Slider.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Select_Icon_image.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MAGE_Setting_API.php';
				require_once MPCRM_GLOBAL_PLUGIN_DIR . '/class/MPCRM_Settings_Global.php';
			}
			public function global_enqueue() {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_style('mp_jquery_ui', MPCRM_GLOBAL_PLUGIN_URL . '/assets/jquery-ui.min.css', array(), '1.13.2');
				wp_enqueue_style('mp_font_awesome', MPCRM_GLOBAL_PLUGIN_URL . '/assets/font_awesome/css/all.min.css', array(), '5.15.4'); 
				wp_enqueue_style('mp_select_2', MPCRM_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.css', array(), '4.0.13');
				wp_enqueue_script('mp_select_2', MPCRM_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13');
				wp_enqueue_style('mp_owl_carousel', MPCRM_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.css', array(), '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPCRM_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.js', array(), '2.3.4');
				wp_enqueue_style('mp_plugin_global', MPCRM_GLOBAL_PLUGIN_URL . '/assets/mp_style/mp_style.css', array(), time());
				wp_enqueue_script('mp_plugin_global', MPCRM_GLOBAL_PLUGIN_URL . '/assets/mp_style/mp_script.js', array('jquery'), time(), true);
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
				wp_enqueue_style('jquery.timepicker.min', MPCRM_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.css', array(), time());
				wp_enqueue_script('jquery-timepicker', MPCRM_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.js', array('jquery'), time(), true);
				//=====================//
				wp_enqueue_script('form-field-dependency', MPCRM_GLOBAL_PLUGIN_URL . '/assets/admin/form-field-dependency.js', array('jquery'), null, false);
				// admin setting global
				wp_enqueue_script('mp_admin_settings', MPCRM_GLOBAL_PLUGIN_URL . '/assets/admin/mp_admin_settings.js', array('jquery'), time(), true);
				wp_enqueue_style('mp_admin_settings', MPCRM_GLOBAL_PLUGIN_URL . '/assets/admin/mp_admin_settings.css', array(), time());
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
				?>
				<script type="text/javascript">
					let mp_currency_symbol = "";
					let mp_currency_position = "";
					let mp_currency_decimal = "";
					let mp_currency_thousands_separator = "";
					let mp_num_of_decimal = "";
					let mp_ajax_url = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
					let mp_empty_image_url = "<?php echo esc_attr(MPCRM_GLOBAL_PLUGIN_URL . '/assets/images/no_image.png'); ?>";
					let mp_date_format = "<?php echo esc_attr(MPCRM_Global_Function::get_settings('mp_global_settings', 'date_format', 'D d M , yy')); ?>";
					let mp_date_format_without_year = "<?php echo esc_attr(MPCRM_Global_Function::get_settings('mp_global_settings', 'date_format_without_year', 'D d M')); ?>";
				</script>
				<?php
				if (MPCRM_Global_Function::check_woocommerce() == 1) {
					?>
					<script type="text/javascript">
						mp_currency_symbol = "<?php echo esc_js( get_woocommerce_currency_symbol() ); ?>";
						mp_currency_position = "<?php echo esc_attr( get_option('woocommerce_currency_pos') ); ?>";
						mp_currency_decimal = "<?php echo esc_html( wc_get_price_decimal_separator() ); ?>";
						mp_currency_thousands_separator = "<?php echo esc_js( wc_get_price_thousand_separator() ); ?>";
						mp_num_of_decimal = "<?php echo absint( get_option('woocommerce_price_num_decimals', 2) ); ?>";
					</script>
					<?php
				}
			}
			public function custom_css() {
				$custom_css = MPCRM_Global_Function::get_settings('mp_add_custom_css', 'custom_css');
				ob_start();
				?>
				<style>
					<?php echo esc_html( $custom_css ); ?>
				</style>
				<?php
				echo wp_kses_post( ob_get_clean() );
			}
		}
		new MPCRM_Global_File_Load();
	}