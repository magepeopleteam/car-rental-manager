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
            wp_enqueue_script('mptbm_admin_quick_setup', MPTBM_PLUGIN_URL . '/assets/admin/mptbm_admin_quick_setup.js', array('jquery'), time(), true);
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
            wp_enqueue_script('wc-checkout');
            //
            wp_enqueue_style('mptbm_style', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_style.css', array(), time());
            wp_enqueue_script('mptbm_script', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_script.js', array('jquery'), time(), true);
            wp_enqueue_script('mptbm_registration', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.js', array('jquery'), time(), true);
            wp_enqueue_style('mptbm_registration', MPTBM_PLUGIN_URL . '/assets/frontend/mptbm_registration.css', array(), time());
            
            // Localize the mptbm_registration script with nonce
            wp_localize_script('mptbm_registration', 'mptbm_ajax', array(
                'nonce'    => wp_create_nonce('mptbm_transportation_type_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
            ));

            do_action('add_mptbm_frontend_script');
        }
        
        
    }
    new MPTBM_Dependencies();
}
