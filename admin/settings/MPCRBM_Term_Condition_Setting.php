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
            add_action('wp_ajax_mpcrbm_save_term_display_toggle', [ $this, 'ajax_save_term_display_toggle' ] );
        }

        // Per-car "Show Terms & Conditions Section on Frontend" switch — same
        // pattern as MPCRBM_Faq_Settings.php's FAQ toggle. Only an explicit 'no'
        // disables it, so a car whose meta was never touched (every car that
        // existed before this switch was added) keeps its old always-on
        // behavior, and nobody's existing terms display disappears on upgrade.
        public function ajax_save_term_display_toggle() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
                wp_send_json_error( [ 'message' => 'You do not have permission to edit this post.' ] );
            }

            $enabled = ( isset( $_POST['enabled'] ) && sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) === 'yes' ) ? 'yes' : 'no';
            update_post_meta( $post_id, 'mpcrbm_display_term_condition', $enabled );

            wp_send_json_success( [ 'enabled' => $enabled ] );
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

            $term_display_meta   = get_post_meta( $post_id, 'mpcrbm_display_term_condition', true );
            $term_enabled_for_car = ( $term_display_meta !== 'no' );

            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_term_and_condition">
                <?php wp_nonce_field( 'manage_faq_settings', 'faq_settings_nonce' ); ?>
                <div class="mpcrbm-info-card">
                    <div class="mpcrbm-info-card-body">
                <section class="mpcrbm-faq-single-list">
                    <div class="mpcrbm-faq-toolbar">
                        <div class="mpcrbm-faq-toolbar-text">
                            <div class="mpcrbm-faq-eyebrow">
                                <span class="mpcrbm-faq-eyebrow-dot"></span>
                                <?php esc_html_e( 'Legal & Policies', 'car-rental-manager' ); ?>
                            </div>
                            <h3><?php esc_html_e( 'Manage Terms & Conditions', 'car-rental-manager' ); ?></h3>
                            <span class="desc"><?php esc_html_e( 'Choose the terms and conditions to appear during the booking journey. Only the selected terms from the list below will be displayed on the frontend.', 'car-rental-manager' ); ?></span>
                        </div>
                        <div class="mpcrbm-faq-toolbar-actions">
                            <label class="mpcrbm-faq-master-toggle">
                                <input type="checkbox" id="mpcrbm_term_display_toggle" <?php checked( $term_enabled_for_car ); ?>>
                                <span class="mpcrbm-faq-master-toggle-slider"></span>
                                <span class="mpcrbm-faq-master-toggle-text"><?php esc_html_e( 'Show Terms & Conditions Section on Frontend', 'car-rental-manager' ); ?></span>
                            </label>
                            <?php if ( ! empty( $terms ) ) : ?>
                                <div class="mpcrbm-faq-live-pill">
                                    <span id="mpcrbm_term_status_badge" class="mpcrbm-faq-status-badge<?php echo $term_enabled_for_car ? ' is-on' : ' is-off'; ?>">
                                        <?php echo $term_enabled_for_car ? esc_html__( 'Terms & Conditions Activated', 'car-rental-manager' ) : esc_html__( 'Terms & Conditions Deactivated', 'car-rental-manager' ); ?>
                                    </span>
                                    <span>
                                        <span id="mpcrbm_term_selected_count"><?php echo esc_html( count( $selected_terms_data ) ); ?></span>
                                        <?php esc_html_e( 'of', 'car-rental-manager' ); ?>
                                        <span id="mpcrbm_term_total_count"><?php echo esc_html( count( $terms ) ); ?></span>
                                        <?php esc_html_e( 'Added to this Car', 'car-rental-manager' ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="mpcrbm-faq-add-new-toggle" id="mpcrbm_toggle_new_term">
                                <i class="mi mi-plus"></i> <?php esc_html_e( 'Add New Term & Condition', 'car-rental-manager' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="mpcrbm_faq_modal mpcrbm-faq-add-new-modal" id="mpcrbm_new_term_panel">
                        <div class="mpcrbm_modal_content">
                            <h3><?php esc_html_e( 'Add New Term & Condition', 'car-rental-manager' ); ?></h3>
                            <label class="mpcrbm-faq-add-new-field">
                                <span><?php esc_html_e( 'Title', 'car-rental-manager' ); ?></span>
                                <input type="text" id="mpcrbm_new_term_title" class="formControl" placeholder="<?php esc_attr_e( 'e.g. Cancellation Policy', 'car-rental-manager' ); ?>">
                            </label>
                            <label class="mpcrbm-faq-add-new-field">
                                <span><?php esc_html_e( 'Description', 'car-rental-manager' ); ?></span>
                                <?php
                                wp_editor( '', 'mpcrbm_new_term_answer_editor', [
                                    'textarea_name' => 'mpcrbm_new_term_answer',
                                    'textarea_rows' => 6,
                                    'media_buttons' => false,
                                    'editor_height' => 180,
                                    'tinymce'       => [
                                        'toolbar1' => 'bold italic underline | bullist numlist | link unlink | undo redo',
                                    ],
                                ] );
                                ?>
                            </label>
                            <div class="mpcrbm-faq-add-new-actions">
                                <button type="button" class="_themeButton_xs_mT_xs mpcrbm-price-add-btn" id="mpcrbm_save_new_term_btn"><?php esc_html_e( 'Save Term & Condition', 'car-rental-manager' ); ?></button>
                                <button type="button" class="button" id="mpcrbm_cancel_new_term_btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="mpcrbm-term-list">
                        <?php if ( ! empty( $terms ) ) : ?>
                            <?php foreach ( $terms as $key => $term ) :
                                $is_selected = isset( $selected_terms_data[ $key ] );
                                ?>
                                <div class="mpcrbm_faq_item mpcrbm-term-toggle-item<?php echo $is_selected ? ' is-selected' : ''; ?>"
                                     data-key="<?php echo esc_attr( $key ); ?>"
                                     data-title="<?php echo esc_attr( $term['title'] ); ?>"
                                >
                                    <span class="mpcrbm-faq-toggle-check"><i class="fas fa-check"></i></span>
                                    <div class="mpcrbm_faq_title"><?php echo esc_html( $term['title'] ); ?></div>
                                    <span class="mpcrbm-faq-toggle-status"><?php esc_html_e( 'Selected', 'car-rental-manager' ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p><?php esc_html_e( 'No terms available yet — add some from the global list first.', 'car-rental-manager' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" id="mpcrbm_added_term_condition_input" name="mpcrbm_added_term_condition" value="<?php echo esc_attr( wp_json_encode( array_keys( $selected_terms_data ) ) ); ?>">
                </section>
                    </div>
                </div>

            </div>

        <?php }

        function mpcrbm_save_added_term_condition() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : '';
            $added_term = isset( $_POST['mpcrbm_added_term'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_added_term'] ) ) : '';
            $data       = ! empty( $added_term ) ? json_decode( $added_term, true ) : [];
            // 4. Sanitize the decoded data (Crucial for security)
            if ( ! empty( $data ) && is_array( $data ) ) {
                $data = map_deep( $data, 'sanitize_text_field' );
            }

            if (!current_user_can('edit_post', $post_id ) ) {
                wp_send_json_error(['message' => 'You do not have permission to edit this post.']);
            }
            if ( $post_id && is_array( $data ) ) {
                update_post_meta( $post_id, $this->term_option_key, $data );

                wp_send_json_success(['message' => 'Term & Condition saved successfully!', 'data' => $data]);
            } else {
                wp_send_json_error(['message' => 'Invalid data']);
            }
        }

    }

    new MPCRBM_Term_Condition_Setting();
}