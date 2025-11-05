<?php

/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Term_Condition_Setting' ) ) {

    class MPCRBM_Term_Condition_Setting{

        private $term_option_key = 'mpcrbm_term_condition_list';
        public function __construct() {
            add_action( 'mpcrbm_settings_tab_content', [ $this, 'term_tab_content' ], 10, 1 );
            add_action('wp_ajax_mpcrbm_save_added_term_condition', [ $this, 'mpcrbm_save_added_term_condition' ] );
        }
        public function term_tab_content( $post_id ){

            $terms = get_option( $this->term_option_key, [] );
            $added_terms = get_post_meta( $post_id, $this->term_option_key, true );
            $selected_terms_data = [];
            if (!empty($added_terms) && !empty( $terms ) ) {
                foreach ($added_terms as $faq_key) {
                    if (isset($terms[$faq_key])) {
                        $selected_terms_data[$faq_key] = $terms[$faq_key];
                    }
                }
            }


            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_term_and_condition">
                <h2><?php esc_html_e( 'Term Condition', 'car-rental-manager' ); ?></h2>
                <p><?php esc_html_e( 'Manage Term Condition settings.', 'car-rental-manager' ); ?></p>

                <?php wp_nonce_field( 'manage_faq_settings', 'faq_settings_nonce' ); ?>
                <section class="bg-light">
                    <h6><?php esc_html_e( 'Manage Term Condition', 'car-rental-manager' ); ?></h6>
                    <span><?php esc_html_e( 'Configure and manage term condition', 'car-rental-manager' ); ?></span>
                </section>

                <section class="mpcrbm_faq_question_holder">
                    <div class="mpcrbm_faq_all_question_box">
                        <h3><?php esc_html_e( 'Available Term & Condition', 'car-rental-manager' ); ?></h3>
                        <div class="mpcrbm_faq_all_question">
                            <?php if (!empty($terms)) : ?>
                                <?php foreach ($terms as $key => $faq) :
                                    if ( isset( $selected_terms_data[$key] ) ) continue;
                                    ?>
                                    <div class="mpcrbm_faq_item"
                                         data-key="<?php echo esc_attr($key); ?>"
                                         data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                    >
                                        <div class="mpcrbm_faq_title"><?php echo esc_html($faq['title']); ?></div>
                                        <button type="button" class="button button-small mpcrbm_add_term_condition"><?php esc_html_e( 'Add', 'car-rental-manager' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'No Term & Condition available.', 'car-rental-manager' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mpcrbm_selected_faq_question_box">
                        <h3><?php esc_html_e( 'Added Term & Condition', 'car-rental-manager' ); ?></h3>
                        <div class="mpcrbm_selected_faq_question">
                            <?php if (!empty($selected_terms_data)) : ?>
                                <?php foreach ($selected_terms_data as $key => $faq) : ?>
                                    <div class="mpcrbm_selected_item"
                                         data-key="<?php echo esc_attr($key); ?>"
                                         data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                    >
                                        <div class="mpcrbm_faq_title"><?php echo esc_html($faq['title']); ?></div>
                                        <button type="button" class="button button-small mpcrbm_remove_term_condition"><?php esc_html_e( 'Remove', 'car-rental-manager' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'No Term & Condition added yet.', 'car-rental-manager' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" id="mpcrbm_added_term_condition_input" name="mpcrbm_added_term_condition" value="<?php echo esc_attr(json_encode($selected_terms_data)); ?>">
                </section>


            </div>

        <?php }

        function mpcrbm_save_added_term_condition() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id =  isset( $_POST['mpcrbm_added_term']) ?  intval( $_POST['post_id']) : '';
            $data = isset( $_POST['mpcrbm_added_term']) ? json_decode(stripslashes( $_POST[ 'mpcrbm_added_term' ] ), true) : [];

            if (!current_user_can('edit_post', $post_id ) ) {
                wp_send_json_error(['message' => 'You do not have permission to edit this post.']);
            }
            if ( $post_id && is_array( $data ) ) {
                update_post_meta( $post_id, $this->term_option_key, $data );

                wp_send_json_success(['message' => 'FAQ saved successfully!', 'data' => $data]);
            } else {
                wp_send_json_error(['message' => 'Invalid data']);
            }
        }

    }

    new MPCRBM_Term_Condition_Setting();
}