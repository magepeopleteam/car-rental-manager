<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Dependencies')) {
    class MPTBM_Dependencies
    {
        public function __construct()
        {
            add_action('init', array($this, 'language_load'));
            $this->load_file();
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
            add_action('admin_head', array($this, 'js_constant'), 5);
            add_action('wp_head', array($this, 'js_constant'), 5);
        }
        public function language_load(): void
        {
            $plugin_dir = basename(dirname(__DIR__)) . "/languages/";
            load_plugin_textdomain('car-rental-manager', false, $plugin_dir);
        }
        private function load_file(): void
        {
            require_once MPTBM_PLUGIN_DIR . '/inc/MPTBM_Function.php';
            require_once MPTBM_PLUGIN_DIR . '/inc/MPTBM_Query.php';
            require_once MPTBM_PLUGIN_DIR . '/inc/MPTBM_Layout.php';
            require_once MPTBM_PLUGIN_DIR . '/Admin/MPTBM_Admin.php';
            require_once MPTBM_PLUGIN_DIR . '/Frontend/MPTBM_Frontend.php';
        }
        public function global_enqueue()
        {
             
            do_action('add_mptbm_common_script');
        }

        public function admin_enqueue()
        {
            $this->global_enqueue();
            // custom
            wp_enqueue_style('mptbm_admin', MPTBM_PLUGIN_URL . '/assets/admin/mptbm_admin.css', array(), time());
            wp_enqueue_style('admin_style', MPTBM_PLUGIN_URL . '/assets/admin/admin_style.css', array(), time());
            wp_enqueue_script('mptbm_admin', MPTBM_PLUGIN_URL . '/assets/admin/mptbm_admin.js', array('jquery'), time(), true);
           
            // Trigger the action hook to add additional scripts if needed
            do_action('add_mptbm_admin_script');
        }

        public function frontend_enqueue()
        {


            $this->global_enqueue();
            wp_enqueue_script('wc-checkout');
            //
            wp_enqueue_style('mptbm_style', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_style.css', array(), time());
            wp_enqueue_script('mptbm_script', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_script.js', array('jquery'), time(), true);
            wp_enqueue_script('mptbm_registration', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.js', array('jquery'), time(), true);
            wp_enqueue_style('mptbm_registration', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.css', array(), time());
            do_action('add_mptbm_frontend_script');
        }
        public function js_constant()
        {
?>
            <script type="text/javascript">
                let mp_lat_lng = {
                    lat: <?php echo esc_js(MP_Global_Function::get_settings('mptbm_map_api_settings', 'mp_latitude', '23.81234828905659')); ?>,
                    lng: <?php echo esc_js(MP_Global_Function::get_settings('mptbm_map_api_settings', 'mp_longitude', '90.41069652669002')); ?>
                };
                const mp_map_options = {
                    componentRestrictions: {
                        country: "<?php echo esc_js(MP_Global_Function::get_settings('mptbm_map_api_settings', 'mp_country', 'BD')); ?>"
                    },
                    fields: ["address_components", "geometry"],
                    types: ["address"],
                }
            </script>
            <?php
        }
        
    }
    new MPTBM_Dependencies();
}
