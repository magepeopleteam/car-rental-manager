<?php

/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Manage_Faq' ) ) {
        class MPCRBM_Manage_Faq{
        private $option_key = 'mpcrbm_faq_list';
        private $menu_slug  = 'mpcrbm_manage_faq';

        public function __construct() {
            add_action('admin_menu', [ $this, 'register_menu' ]);
            add_action('wp_ajax_mpcrbm_save_faq', [ $this, 'ajax_save_faq' ]);
            add_action('wp_ajax_mpcrbm_delete_faq', [ $this, 'ajax_delete_faq' ]);
        }

        /**
         * Register submenu page
         */
        public function register_menu() {
            add_submenu_page(
                'edit.php?post_type='.MPCRBM_Function::get_cpt(),
                __('Manage FAQ, Term & Condition', 'car-rental'),
                __('anage FAQ, Term & Condition'),
                'manage_options',
                $this->menu_slug,
                [ $this, 'render_page' ]
            );
        }

        /**
         * Render FAQ management page
         */
        public function render_page() {
            $faqs = get_option( $this->option_key, [] );
            ?>
            <div class="mpcrbm_faq_container">
                <h2><?php esc_attr_e( 'Manage FAQs', 'car-rental-manager' );?></h2>
                <button id="mpcrbm_add_faq_btn" class="button button-primary">+ <?php esc_attr_e( 'Add FAQ', 'car-rental-manager' );?></button>

                <table class="widefat mpcrbm_faq_table">
                    <thead>
                    <tr>
                        <th><?php esc_attr_e( 'Title', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Answer', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Action', 'car-rental-manager' );?></th>
                    </tr>
                    </thead>
                    <tbody id="mpcrbm_faq_list">
                    <?php if ( ! empty( $faqs ) ) : ?>
                        <?php foreach ( $faqs as $key => $faq ) : ?>
                            <tr
                                    data-key="<?php echo esc_attr( $key ); ?>"
                                    data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                            >
                                <td class="faq-title"><?php echo esc_html( $faq['title'] ); ?></td>
                                <td class="faq-answer"><?php echo wp_kses_post( wp_trim_words( $faq['answer'], 15 ) ); ?></td>
                                <td>
                                    <button class="button edit-faq"><?php esc_attr_e( 'Edit', 'car-rental-manager' );?></button>
                                    <button class="button delete-faq"><?php esc_attr_e( 'Delete', 'car-rental-manager' );?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3"><?php esc_attr_e( 'No FAQs found.', 'car-rental-manager' );?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Popup Modal -->
                <div id="mpcrbm_faq_modal" class="mpcrbm_faq_modal">
                    <div class="mpcrbm_modal_content">
                        <h3 id="mpcrbm_modal_title"><?php esc_attr_e( 'Add FAQ', 'car-rental-manager' );?></h3>
                        <input type="hidden" id="mpcrbm_faq_key" value="">
                        <label><?php esc_attr_e( 'Question', 'car-rental-manager' );?>:</label>
                        <input type="text" id="mpcrbm_faq_title" class=" mpcrbm_faq_title regular-text"><br><br>

                        <label>Answer:</label>
                        <div id="mpcrbm_faq_editor_container">
                            <?php
                            wp_editor( '', 'mpcrbm_faq_answer_editor', [
                                'textarea_name' => 'mpcrbm_faq_answer',
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'editor_height' => 400,
                                'tinymce' => [
                                    'toolbar1' => 'bold italic underline | bullist numlist | link unlink | undo redo | formatselect',
                                ],
                            ] );
                            ?>
                        </div>

                        <br>
                        <button id="mpcrbm_save_faq_btn" class="button button-primary">Save</button>
                        <button id="mpcrbm_cancel_faq_btn" class="button">Cancel</button>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Save FAQ (AJAX)
         */
            public function ajax_save_faq() {
                check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

                $title  = sanitize_text_field( $_POST['title'] ?? '' );
                $answer = wp_kses_post( $_POST['answer'] ?? '' );
                $key    = sanitize_text_field( $_POST['key'] ?? '' );

                if ( empty( $title ) || empty( $answer ) ) {
                    wp_send_json_error( 'Title and Answer are required.' );
                }

                $faqs = get_option( $this->option_key, [] );
                if ( $key === '' ) {
                    $key = uniqid( 'faq_' );
                }

                $faqs[$key] = [
                    'title'  => $title,
                    'answer' => $answer,
                ];

                update_option( $this->option_key, $faqs );
                wp_send_json_success( 'Saved successfully.' );
            }

            /**
             * Delete FAQ (AJAX)
             */
            public function ajax_delete_faq() {
                check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

                $key = sanitize_text_field( $_POST['key'] ?? '' );
                $faqs = get_option( $this->option_key, [] );

                if ( isset( $faqs[$key] ) ) {
                    unset( $faqs[$key] );
                    update_option( $this->option_key, $faqs );
                    wp_send_json_success( 'FAQ deleted.' );
                } else {
                    wp_send_json_error( 'FAQ not found.' );
                }
            }
    }

    new MPCRBM_Manage_Faq();

}