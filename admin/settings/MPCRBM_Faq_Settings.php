<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.
if ( ! class_exists( 'MPCRBM_Faq_Settings' ) ) {
    class MPCRBM_Faq_Settings
    {

        private $option_key = 'mpcrbm_faq_list';
        public function __construct() {
            add_action( 'mpcrbm_settings_tab_content', [ $this, 'faq_tab_content' ], 10, 1 );
            add_action('wp_ajax_mpcrbm_save_added_faq', [ $this, 'mpcrbm_save_added_faq' ] );
        }

        function mpcrbm_save_added_faq() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id =  isset( $_POST['mpcrbm_added_faq']) ?  intval( $_POST['post_id']) : '';
            $data = isset( $_POST['mpcrbm_added_faq']) ? json_decode(stripslashes($_POST['mpcrbm_added_faq']), true) : [];

            if (!current_user_can('edit_post', $post_id ) ) {
                wp_send_json_error(['message' => 'You do not have permission to edit this post.']);
            }
            if ( $post_id && is_array( $data ) ) {
                update_post_meta($post_id, 'mpcrbm_added_faq', $data);
                wp_send_json_success(['message' => 'FAQ saved successfully!', 'data' => $data]);
            } else {
                wp_send_json_error(['message' => 'Invalid data']);
            }
        }

        public function faq_tab_content( $post_id ){

            $faqs = get_option( $this->option_key, [] );
            $added_faqs = get_post_meta( $post_id, 'mpcrbm_added_faq', true );
            $selected_faqs_data = [];
            if (!empty($added_faqs) && !empty( $faqs ) ) {
                foreach ($added_faqs as $faq_key) {
                    if (isset($faqs[$faq_key])) {
                        $selected_faqs_data[$faq_key] = $faqs[$faq_key];
                    }
                }
            }


            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_setting_manage_faq">
                <h2><?php esc_html_e( 'FAQ', 'car-rental-manager' ); ?></h2>
                <p><?php esc_html_e( 'Manage FAQ settings.', 'car-rental-manager' ); ?></p>

                <?php wp_nonce_field( 'manage_faq_settings', 'faq_settings_nonce' ); ?>
                <section class="bg-light">
                    <h6><?php esc_html_e( 'Manage FAQ', 'car-rental-manager' ); ?></h6>
                    <span><?php esc_html_e( 'Configure and manage faq', 'car-rental-manager' ); ?></span>
                </section>

                <div class="mpcrbm_faq_question_holder">
                    <div class="mpcrbm_faq_all_question_box">
                        <h3><?php esc_html_e( 'Available FAQs', 'car-rental-manager' ); ?></h3>
                        <div class="mpcrbm_faq_all_question">
                            <?php if (!empty($faqs)) : ?>
                                <?php foreach ($faqs as $key => $faq) :
                                    if ( isset( $selected_faqs_data[$key] ) ) continue;
                                    ?>
                                    <div class="mpcrbm_faq_item"
                                         data-key="<?php echo esc_attr($key); ?>"
                                         data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                    >
                                        <div class="mpcrbm_faq_title"><?php echo esc_html($faq['title']); ?></div>
                                        <button type="button" class="button button-small mpcrbm_add_faq"><?php esc_html_e( 'Add', 'car-rental-manager' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'No FAQs available.', 'car-rental-manager' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mpcrbm_selected_faq_question_box">
                        <h3><?php esc_html_e( 'Added FAQs', 'car-rental-manager' ); ?></h3>
                        <div class="mpcrbm_selected_faq_question">
                            <?php if (!empty($selected_faqs_data)) : ?>
                                <?php foreach ($selected_faqs_data as $key => $faq) : ?>
                                    <div class="mpcrbm_selected_item"
                                         data-key="<?php echo esc_attr($key); ?>"
                                         data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                    >
                                        <div class="mpcrbm_faq_title"><?php echo esc_html($faq['title']); ?></div>
                                        <button type="button" class="button button-small mpcrbm_remove_faq"><?php esc_html_e( 'Remove', 'car-rental-manager' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'No FAQs added yet.', 'car-rental-manager' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" id="mpcrbm_added_faq_input" name="mpcrbm_added_faq" value="<?php echo esc_attr(json_encode($selected_faqs_data)); ?>">
                </div>


            </div>

        <?php }

    }

    new MPCRBM_Faq_Settings();
}