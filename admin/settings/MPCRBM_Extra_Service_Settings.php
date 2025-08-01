<?php
/*
 * @Author 		MagePeople Team
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPCRBM_Extra_Service_Settings')) {
    class MPCRBM_Extra_Service_Settings {
        public function __construct() {
            add_action('mpcrbm_settings_sec_fields', array($this, 'settings_sec_fields'), 10, 1);
            add_action('mpcrbm_settings_tab_content', array($this, 'extra_service_settings_tab'), 10, 1);
            add_action('save_post', array($this, 'save_extra_service_settings'), 10, 1);
        }

        public function settings_sec_fields($default_fields) {
            // Ensure $default_fields is an array
            $default_fields = is_array($default_fields) ? $default_fields : array();
            
            $settings_fields = array(
                'mpcrbm_extra_service_settings' => array(
                    array(
                        'name' => 'display_mpcrbm_extra_services',
                        'label' => esc_html__('Display Extra Services', 'car-rental-manager'),
                        'desc' => esc_html__('Enable/Disable extra services display', 'car-rental-manager'),
                        'type' => 'select',
                        'default' => 'on',
                        'options' => array(
                            'on' => esc_html__('On', 'car-rental-manager'),
                            'off' => esc_html__('Off', 'car-rental-manager')
                        )
                    ),
                    array(
                        'name' => 'mpcrbm_extra_services_id',
                        'label' => esc_html__('Extra Services', 'car-rental-manager'),
                        'desc' => esc_html__('Select predefined extra services or create custom ones', 'car-rental-manager'),
                        'type' => 'select',
                        'default' => '',
                        'options' => $this->get_extra_services_options()
                    )
                )
            );
            
            return array_merge($default_fields, $settings_fields);
        }

        private function get_extra_services_options() {
            $options = array(
                '' => esc_html__('Select extra services', 'car-rental-manager')
            );
            
            $extra_services = get_posts(array(
                'post_type' => 'crm_extra_services',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            foreach ($extra_services as $service) {
                $options[$service->ID] = $service->post_title;
            }

            return $options;
        }

        public function extra_service_settings_tab($post_id) {
            if (get_post_type($post_id) !== MPCRBM_Function::get_cpt()) {
                return;
            }

            wp_nonce_field('mpcrbm_save_extra_service_settings', 'mpcrbm_extra_service_nonce');
            
            $display = get_post_meta($post_id, 'display_mpcrbm_extra_services', true) ?: 'on';
            $service_id = get_post_meta($post_id, 'mpcrbm_extra_services_id', true) ?: '';
            $active = $display === 'off' ? '' : 'mActive';
            $checked = $display === 'off' ? '' : 'checked';
            ?>
            <div class="tabsItem mpcrbm_extra_services_setting" data-tabs="#mpcrbm_settings_ex_service">
                <h2><?php esc_html_e('Extra Service Settings', 'car-rental-manager'); ?></h2>
                <p><?php esc_html_e('Configure extra services for this vehicle', 'car-rental-manager'); ?></p>

                <section class="bg-light">
                    <h6><?php esc_html_e('Extra Service Options', 'car-rental-manager'); ?></h6>
                    <span><?php esc_html_e('Enable or disable extra services and select predefined services', 'car-rental-manager'); ?></span>
                </section>

                <section>
                    <label class="label">
                        <div>
                            <h6><?php esc_html_e('Display Extra Services', 'car-rental-manager'); ?></h6>
                            <span class="desc"><?php esc_html_e('Enable or disable extra services for this vehicle', 'car-rental-manager'); ?></span>
                        </div>
                        <?php MPCRBM_Custom_Layout::switch_button('display_mpcrbm_extra_services', $checked); ?>
                    </label>
                </section>

                <div data-collapse="#display_mpcrbm_extra_services" class="settings_area <?php echo esc_attr($active); ?>">
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e('Select Extra Services', 'car-rental-manager'); ?></h6>
                                <span class="desc"><?php esc_html_e('Choose from predefined extra services or create custom ones', 'car-rental-manager'); ?></span>
                            </div>
                            <select class="formControl" name="mpcrbm_extra_services_id">
                                <?php foreach ($this->get_extra_services_options() as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($service_id, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </div>
            </div>
            <?php
        }

        public function save_extra_service_settings($post_id) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            if (get_post_type($post_id) !== MPCRBM_Function::get_cpt()) {
                return;
            }

            if (!isset($_POST['mpcrbm_extra_service_nonce']) ||
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpcrbm_extra_service_nonce'])), 'mpcrbm_save_extra_service_settings')) {
                return;
            }

            $display = isset($_POST['display_mpcrbm_extra_services']) ? 'on' : 'off';
            update_post_meta($post_id, 'display_mpcrbm_extra_services', $display);

            if (isset($_POST['mpcrbm_extra_services_id'])) {
                $service_id = sanitize_text_field(wp_unslash($_POST['mpcrbm_extra_services_id']));
                update_post_meta($post_id, 'mpcrbm_extra_services_id', $service_id);
            }
        }
    }
    new MPCRBM_Extra_Service_Settings();
}
?>
<?php do_action('mpcrbm_settings_sec_fields'); ?> 