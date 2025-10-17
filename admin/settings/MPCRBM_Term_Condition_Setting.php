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
            add_action( 'mpcrbm_settings_tab_content', [ $this, 'term_tab_content' ] );
            add_action('wp_ajax_mpcrbm_save_added_faq', [ $this, 'mpcrbm_save_added_faq' ] );
        }

        public function term_tab_content(){

            $faqs = get_option( $this->term_option_key, [] );
            $added_faqs = get_post_meta( 66, 'mpcrbm_added_term_condition', true);
            $selected_faqs_data = [];
            if (!empty($added_faqs) && !empty( $faqs ) ) {
                foreach ($added_faqs as $faq_key) {
                    if (isset($faqs[$faq_key])) {
                        $selected_faqs_data[$faq_key] = $faqs[$faq_key];
                    }
                }
            }


            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_term_and_condition">
                <h3><?php esc_html_e( 'Term Condition', 'car-rental-manager' ); ?></h3>
                <p><?php esc_html_e( 'Manage Term Condition settings.', 'car-rental-manager' ); ?></p>

                <?php wp_nonce_field( 'manage_faq_settings', 'faq_settings_nonce' ); ?>
                <section class="bg-light">
                    <h6><?php esc_html_e( 'Manage Term Condition', 'car-rental-manager' ); ?></h6>
                    <span><?php esc_html_e( 'Configure and manage term condition', 'car-rental-manager' ); ?></span>
                </section>

                <div class="mpcrbm_faq_question_holder">
                    <div class="mpcrbm_faq_all_question_box">
                        <h3>Available Term & Condition</h3>
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
                                        <button type="button" class="button button-small mpcrbm_add_faq">Add</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No Term & Condition available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mpcrbm_selected_faq_question_box">
                        <h3>Added Term & Condition</h3>
                        <div class="mpcrbm_selected_faq_question">
                            <?php if (!empty($selected_faqs_data)) : ?>
                                <?php foreach ($selected_faqs_data as $key => $faq) : ?>
                                    <div class="mpcrbm_selected_item"
                                         data-key="<?php echo esc_attr($key); ?>"
                                         data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                    >
                                        <div class="mpcrbm_faq_title"><?php echo esc_html($faq['title']); ?></div>
                                        <button type="button" class="button button-small mpcrbm_remove_faq">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No Term & Condition added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" id="mpcrbm_added_term_condition_input" name="mpcrbm_added_term_condition" value="<?php echo esc_attr(json_encode($selected_faqs_data)); ?>">
                </div>


            </div>

        <?php }


    }

    new MPCRBM_Term_Condition_Setting();
}