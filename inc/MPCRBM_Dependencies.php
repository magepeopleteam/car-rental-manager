<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPCRBM_Dependencies')) {
    class MPCRBM_Dependencies
    {
        public function __construct()
        {
            add_action('init', array($this, 'language_load'));
            $this->load_file();
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
            
        }
        public function language_load(): void
        {
            $plugin_dir = basename(dirname(__DIR__)) . "/languages/";
            load_plugin_textdomain('car-rental-manager', false, $plugin_dir);
        }
        private function load_file(): void
        {
            require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Function.php';
            require_once MPCRBM_PLUGIN_DIR . '/inc/MPTBM_Query.php';
            require_once MPCRBM_PLUGIN_DIR . '/inc/MPTBM_Layout.php';
            require_once MPCRBM_PLUGIN_DIR . '/Admin/MPTBM_Admin.php';
            require_once MPCRBM_PLUGIN_DIR . '/Frontend/MPTBM_Frontend.php';
        }
        public function global_enqueue()
        {
             
            do_action('add_mptbm_common_script');
        }

        public function admin_enqueue()
        {
            $this->global_enqueue();
            // custom
            wp_enqueue_style('mptbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mptbm_admin.css', array(), time());
            wp_enqueue_style('mpcrm_admin_style', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrm_admin_style.css', array(), time());
            wp_enqueue_script('mptbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mptbm_admin.js', array('jquery'), time(), true);
            wp_enqueue_script('mptbm_admin_quick_setup', MPCRBM_PLUGIN_URL . '/assets/admin/mptbm_admin_quick_setup.js', array('jquery'), time(), true);
            $nonce = wp_create_nonce('mptbm_extra_service');
            wp_localize_script('mptbm_admin', 'mptbmAdmin', array(
                'nonce' => $nonce
            ));
            // Trigger the action hook to add additional scripts if needed
            do_action('add_mptbm_admin_script');
        }

        public function frontend_enqueue()
        {
            $this->global_enqueue();

            
            // Enqueue styles
            wp_enqueue_style('mptbm_style', MPCRBM_PLUGIN_URL . '/assets/frontend/mptbm_style.css', array(), time());
            wp_enqueue_style('mptbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.css', array(), time());
            
            // Enqueue scripts
            wp_enqueue_script('mptbm_script', MPCRBM_PLUGIN_URL . '/assets/frontend/mptbm_script.js', array('jquery'), time(), true);
            wp_enqueue_script('mptbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.js', array('jquery'), time(), true);
            
            // Localize scripts
            wp_localize_script('mptbm_registration', 'mptbm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mptbm_transportation_type_nonce')
            ));
            
            wp_localize_script('mptbm_registration', 'mptbmL10n', array(
                'nameLabel' => __('Name : ', 'car-rental-manager'),
                'qtyLabel' => __('Quantity : ', 'car-rental-manager'),
                'priceLabel' => __('Price : ', 'car-rental-manager')
            ));

            do_action('add_mptbm_frontend_script');
        }
        
        
    }
    new MPCRBM_Dependencies();
}
