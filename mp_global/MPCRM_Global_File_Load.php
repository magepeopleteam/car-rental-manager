<?php
/*
* @Author       engr.sumonazma@gmail.com
* Copyright:    mage-people.com
*/

if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPCRM_Global_File_Load')) {
    class MPCRM_Global_File_Load {
        
        public function __construct() {
            $this->define_constants();
            $this->load_global_file();

            add_action('admin_enqueue_scripts', array($this, 'custom_css'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
            add_action('transporter_panel_admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
            add_action('admin_head', array($this, 'mpcrm_admin_head'), 5);
            add_action('wp_head', array($this, 'mpcrm_frontend_head'), 5);
        }

        public function define_constants() {
            if (!defined('MPCRM_GLOBAL_PLUGIN_DIR')) {
                define('MPCRM_GLOBAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
            }
            if (!defined('MPCRM_GLOBAL_PLUGIN_URL')) {
                define('MPCRM_GLOBAL_PLUGIN_URL', plugin_dir_url(__FILE__)); 
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

        public function custom_css() {
            $custom_css = MPCRM_Global_Function::get_settings('mp_add_custom_css', 'custom_css');

            if (!empty($custom_css)) {
                // Minify the CSS
                $custom_css = trim(preg_replace('/\s+/', ' ', $custom_css));

                // Ensure a base stylesheet is registered before adding inline styles
                wp_register_style('mpcrm-custom-style', false);
                wp_enqueue_style('mpcrm-custom-style');

                // Add the inline CSS
                wp_add_inline_style('mpcrm-custom-style', $custom_css);
            }
        }

        public function global_enqueue() {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-datepicker');
        
            wp_enqueue_style('mp_jquery_ui', plugins_url('assets/jquery-ui.min.css', __FILE__), array(), '1.13.2');
            wp_enqueue_style('mp_font_awesome', plugins_url('assets/font_awesome/css/all.min.css', __FILE__), array(), '5.15.4');
            wp_enqueue_style('mp_select_2', plugins_url('assets/select_2/select2.min.css', __FILE__), array(), '4.0.13');
            wp_enqueue_script('mp_select_2', plugins_url('assets/select_2/select2.min.js', __FILE__), array('jquery'), '4.0.13', true);
        
            wp_enqueue_style('mp_owl_carousel', plugins_url('assets/owl_carousel/owl.carousel.min.css', __FILE__), array(), '2.3.4');
            wp_enqueue_script('mp_owl_carousel', plugins_url('assets/owl_carousel/owl.carousel.min.js', __FILE__), array('jquery'), '2.3.4', true);
        
            // Cache busting using file modification time
            wp_enqueue_style('mp_plugin_global', plugins_url('assets/mp_style/mp_style.css', __FILE__), array(), filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/mp_style/mp_style.css'));
            wp_enqueue_script('mp_plugin_global', plugins_url('assets/mp_style/mp_script.js', __FILE__), array('jquery'), filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/mp_style/mp_script.js'), true);
        
            do_action('add_mp_global_enqueue');
        }

        public function admin_enqueue() {
            $this->global_enqueue();
            
            wp_enqueue_editor();
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-codemirror');
            wp_enqueue_script('wp-codemirror');
        
            // Admin-specific styles and scripts
            wp_enqueue_style(
                'jquery-timepicker',
                plugins_url('assets/admin/jquery.timepicker.min.css', __FILE__),
                array(),
                filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/admin/jquery.timepicker.min.css')
            );
        
            wp_enqueue_script(
                'jquery-timepicker',
                plugins_url('assets/admin/jquery.timepicker.min.js', __FILE__),
                array('jquery'),
                filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/admin/jquery.timepicker.min.js'),
                true
            );
        
            wp_enqueue_script(
                'form-field-dependency',
                plugins_url('assets/admin/form-field-dependency.js', __FILE__),
                array('jquery'),
                null,
                false
            );
        
            wp_enqueue_script(
                'mp_admin_settings',
                plugins_url('assets/admin/mp_admin_settings.js', __FILE__),
                array('jquery'),
                filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/admin/mp_admin_settings.js'),
                true
            );
        
            wp_enqueue_style(
                'mp_admin_settings',
                plugins_url('assets/admin/mp_admin_settings.css', __FILE__),
                array(),
                filemtime(MPCRM_GLOBAL_PLUGIN_DIR . 'assets/admin/mp_admin_settings.css')
            );
        
            do_action('add_mp_admin_enqueue');
        }

        public function frontend_enqueue() {
            $this->global_enqueue();
            do_action('add_mp_frontend_enqueue');
        }

        public function mpcrm_admin_head() {
            $this->js_constant();
        }

        public function mpcrm_frontend_head() {
            $this->js_constant();
            $this->custom_css();
        }

        public function js_constant() {
            // Register and enqueue your JavaScript file (if you have one)
            wp_register_script('mpcrm-custom-js-constant', MPCRM_GLOBAL_PLUGIN_URL . 'assets/mpcrm-custom-js-constant.js', array('jquery'), '1.0', true);
            wp_enqueue_script('mpcrm-custom-js-constant');

            // Prepare inline JavaScript
            $mp_js_constants = '
                let mp_currency_symbol = "";
                let mp_currency_position = "";
                let mp_currency_decimal = "";
                let mp_currency_thousands_separator = "";
                let mp_num_of_decimal = "";
                let mp_ajax_url = "' . esc_url(admin_url('admin-ajax.php')) . '";
                let mp_empty_image_url = "' . esc_attr(MPCRM_GLOBAL_PLUGIN_URL . 'assets/images/no_image.png') . '";
                let mp_date_format = "' . esc_attr(MPCRM_Global_Function::get_settings('mp_global_settings', 'date_format', 'D d M , yy')) . '";
                let mp_date_format_without_year = "' . esc_attr(MPCRM_Global_Function::get_settings('mp_global_settings', 'date_format_without_year', 'D d M')) . '";
            ';

            // Check if WooCommerce is active
            if (MPCRM_Global_Function::check_woocommerce() == 1) {
                $mp_js_constants .= '
                    mp_currency_symbol = "' . esc_js(get_woocommerce_currency_symbol()) . '";
                    mp_currency_position = "' . esc_attr(get_option("woocommerce_currency_pos")) . '";
                    mp_currency_decimal = "' . esc_html(wc_get_price_decimal_separator()) . '";
                    mp_currency_thousands_separator = "' . esc_js(wc_get_price_thousand_separator()) . '";
                    mp_num_of_decimal = "' . absint(get_option("woocommerce_price_num_decimals", 2)) . '";
                ';
            }

            // Add inline script after enqueuing main script
            wp_add_inline_script('mpcrm-custom-js-constant', $mp_js_constants, 'before');
        }
    }
    new MPCRM_Global_File_Load();
}