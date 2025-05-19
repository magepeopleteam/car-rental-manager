<?php
/**
 * Plugin Name: Car Rental Manager
 * Plugin URI: http://mage-people.com
 * Description: A Complete Car rental Solution for WordPress by MagePeople.
 * Version: 1.0.0
 * Author: MagePeople Team
 * Author URI: http://www.mage-people.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: car-rental-manager
 * Domain Path: /languages/
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Plugin')) {
    class MPTBM_Plugin
    {
        public function __construct()
        {
            $this->load_plugin();

            add_filter('theme_page_templates', array($this, 'mpcrm_on_activation_template_create'), 10, 3);
            add_filter('template_include', array($this, 'mpcrm_change_page_template'), 99);
            add_action('admin_init', array($this, 'wptbm_assign_template_to_page'));
        }

        private function load_plugin(): void
		{
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			if (!defined('MPTBM_PLUGIN_DIR')) {
				define('MPTBM_PLUGIN_DIR', dirname(__FILE__));
			}
			if (!defined('MPTBM_PLUGIN_URL')) {
				define('MPTBM_PLUGIN_URL', plugin_dir_url(__FILE__));
			}
			if (!defined('MPTBM_PLUGIN_DATA')) {
				// define('MPTBM_PLUGIN_DATA', get_plugin_data(__FILE__));
			}
			if (!defined('MPTBM_PLUGIN_VERSION')) {
				define('MPTBM_PLUGIN_VERSION', '1.0.7');
			}
			require_once MPTBM_PLUGIN_DIR . '/mp_global/MPCRM_Global_File_Load.php';
			if (MPCRM_Global_Function::check_woocommerce() == 1) {
				add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
				self::on_activation_page_create();
				require_once MPTBM_PLUGIN_DIR . '/inc/MPTBM_Dependencies.php';
			} else {
				require_once MPTBM_PLUGIN_DIR . '/Admin/MPTBM_Quick_Setup.php';
				//add_action('admin_notices', [$this, 'woocommerce_not_active']);
				add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
			}
		}

        public function activation_redirect($plugin)
        {
            $mptbm_quick_setup_done = get_option('mptbm_quick_setup_done');
            if ($plugin == plugin_basename(__FILE__) && $mptbm_quick_setup_done != 'yes') {
                wp_safe_redirect(admin_url('edit.php?post_type=mptbm_rent&page=mptbm_quick_setup'));
                exit();
            }
        }

        public function activation_redirect_setup($plugin)
        {
            $mptbm_quick_setup_done = get_option('mptbm_quick_setup_done');
            if ($plugin == plugin_basename(__FILE__) && $mptbm_quick_setup_done != 'yes') {
                wp_safe_redirect(admin_url('admin.php?post_type=mptbm_rent&page=mptbm_quick_setup'));
                exit();
            }
        }

        public static function on_activation_page_create(): void
        {
            if (did_action('wp_loaded')) {
                self::create_pages();
            } else {
                add_action('wp_loaded', array(__CLASS__, 'create_pages'));
            }
        }

        public static function create_pages()
        {
            // Create pages only if they don't exist
            if (!MPCRM_Global_Function::get_page_by_slug('mptbm-search')) {
                $search_page = array(
                    'post_type' => 'page',
                    'post_name' => 'mptbm-search',
                    'post_title' => 'Search Transport',
                    'post_content' => '',
                    'post_status' => 'publish'
                );
                wp_insert_post($search_page);
            }
        }

        public function mpcrm_on_activation_template_create($templates)
        {
            $template_path = 'transport_result.php';
            $page_templates[$template_path] = 'Transport Result';
            foreach ($page_templates as $tk => $tv) {
                $templates[$tk] = $tv;
            }
            flush_rewrite_rules();
            return $templates;
        }

        public function mpcrm_change_page_template($template)
        {
            global $wp_query, $wpdb;
            $page_temp_slug = get_page_template_slug(get_the_ID());
            $template_path = 'transport_result.php';
            $page_templates[$template_path] = 'Transport Result';
            if (isset($page_templates[$page_temp_slug])) {
                $template = plugin_dir_path(__FILE__) . '/' . $page_temp_slug;
            }

            return $template;
        }

        public function wptbm_assign_template_to_page()
        {
            // Check if the page 'transport-result' exists
            $page = get_page_by_path('transport-result');
            if ($page) {
                // Update the page meta to assign the template
                update_post_meta($page->ID, '_wp_page_template', 'transport_result.php');
            }
        }

        public function load_template($template)
        {
            global $post;
            if ($post) {
                if (is_page('mptbm-search')) {
                    $template_path = 'search_page.php';
                    $template_file = MPTBM_PLUGIN_DIR . '/templates/' . $template_path;
                    if (file_exists($template_file)) {
                        return $template_file;
                    }
                }
            }
            return $template;
        }
    }

    new MPTBM_Plugin();
}
