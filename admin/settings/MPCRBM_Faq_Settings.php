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
            add_action('wp_ajax_mpcrbm_save_faq_display_toggle', [ $this, 'ajax_save_faq_display_toggle' ] );
        }

        // Per-car "Show FAQ Section on Frontend" switch — stored as post meta
        // (mpcrbm_display_faq) so each car can be turned on/off independently,
        // instead of the old single site-wide switch. Meta value 'no' is the
        // only thing that disables it; anything else (including the meta never
        // having been set at all) reads as enabled, so cars that already had
        // FAQs showing before this per-car switch existed keep showing them —
        // nobody's existing setup silently goes dark on upgrade.
        public function ajax_save_faq_display_toggle() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
                wp_send_json_error( [ 'message' => 'You do not have permission to edit this post.' ] );
            }

            $enabled = ( isset( $_POST['enabled'] ) && sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) === 'yes' ) ? 'yes' : 'no';
            update_post_meta( $post_id, 'mpcrbm_display_faq', $enabled );

            wp_send_json_success( [ 'enabled' => $enabled ] );
        }

        function mpcrbm_save_added_faq() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) : '';

            // 3. Process the JSON data using wp_unslash()
            $faq_json = isset( $_POST['mpcrbm_added_faq'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_added_faq'] ) ) : '';
            $data     = ! empty( $faq_json ) ? json_decode( $faq_json, true ) : [];

            // 4. Sanitize the resulting array (Crucial for security)
            if ( ! empty( $data ) && is_array( $data ) ) {
                $data = map_deep( $data, 'sanitize_text_field' );
            }

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

            // Backward-compatible default: only an explicit 'no' disables the
            // section — a car whose meta was never touched (every car that
            // existed before this per-car switch was added) reads as enabled,
            // same as its old always-on behavior.
            $faq_display_meta   = get_post_meta( $post_id, 'mpcrbm_display_faq', true );
            $faq_enabled_for_car = ( $faq_display_meta !== 'no' );

            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_setting_manage_faq">
                <?php wp_nonce_field( 'manage_faq_settings', 'faq_settings_nonce' ); ?>
                <div class="mpcrbm-info-card">
                    <div class="mpcrbm-info-card-body">
                <section class="mpcrbm-faq-single-list">
                    <div class="mpcrbm-faq-toolbar">
                        <div class="mpcrbm-faq-toolbar-text">
                            <div class="mpcrbm-faq-eyebrow">
                                <span class="mpcrbm-faq-eyebrow-dot"></span>
                                <?php esc_html_e( 'Content Management', 'car-rental-manager' ); ?>
                            </div>
                            <h3><?php esc_html_e( 'Manage FAQs', 'car-rental-manager' ); ?></h3>
                            <span class="desc"><?php esc_html_e( 'Choose the frequently asked questions to appear during the booking journey. Only the selected FAQs from the list below will be displayed on the frontend.', 'car-rental-manager' ); ?></span>
                        </div>
                        <div class="mpcrbm-faq-toolbar-actions">
                            <label class="mpcrbm-faq-master-toggle">
                                <input type="checkbox" id="mpcrbm_faq_display_toggle" <?php checked( $faq_enabled_for_car ); ?>>
                                <span class="mpcrbm-faq-master-toggle-slider"></span>
                                <span class="mpcrbm-faq-master-toggle-text"><?php esc_html_e( 'Show FAQ Section on Frontend', 'car-rental-manager' ); ?></span>
                            </label>
                            <?php if ( ! empty( $faqs ) ) : ?>
                                <div class="mpcrbm-faq-live-pill">
                                    <span id="mpcrbm_faq_status_badge" class="mpcrbm-faq-status-badge<?php echo $faq_enabled_for_car ? ' is-on' : ' is-off'; ?>">
                                        <?php echo $faq_enabled_for_car ? esc_html__( 'FAQ Activated', 'car-rental-manager' ) : esc_html__( 'FAQ Deactivated', 'car-rental-manager' ); ?>
                                    </span>
                                    <span>
                                        <span id="mpcrbm_faq_selected_count"><?php echo esc_html( count( $selected_faqs_data ) ); ?></span>
                                        <?php esc_html_e( 'of', 'car-rental-manager' ); ?>
                                        <span id="mpcrbm_faq_total_count"><?php echo esc_html( count( $faqs ) ); ?></span>
                                        <?php esc_html_e( 'Added to this Car', 'car-rental-manager' ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="mpcrbm-faq-add-new-toggle" id="mpcrbm_toggle_new_faq">
                                <i class="mi mi-plus"></i> <?php esc_html_e( 'Add New FAQ', 'car-rental-manager' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="mpcrbm_faq_modal mpcrbm-faq-add-new-modal" id="mpcrbm_new_faq_panel">
                        <div class="mpcrbm_modal_content">
                            <h3><?php esc_html_e( 'Add New FAQ', 'car-rental-manager' ); ?></h3>
                            <label class="mpcrbm-faq-add-new-field">
                                <span><?php esc_html_e( 'Question', 'car-rental-manager' ); ?></span>
                                <input type="text" id="mpcrbm_new_faq_title" class="formControl" placeholder="<?php esc_attr_e( 'e.g. Do you offer weekend discounts?', 'car-rental-manager' ); ?>">
                            </label>
                            <label class="mpcrbm-faq-add-new-field">
                                <span><?php esc_html_e( 'Answer', 'car-rental-manager' ); ?></span>
                                <?php
                                wp_editor( '', 'mpcrbm_new_faq_answer_editor', [
                                    'textarea_name' => 'mpcrbm_new_faq_answer',
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
                                <button type="button" class="_themeButton_xs_mT_xs mpcrbm-price-add-btn" id="mpcrbm_save_new_faq_btn"><?php esc_html_e( 'Save FAQ', 'car-rental-manager' ); ?></button>
                                <button type="button" class="button" id="mpcrbm_cancel_new_faq_btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="mpcrbm-faq-list">
                        <?php if ( ! empty( $faqs ) ) : ?>
                            <?php foreach ( $faqs as $key => $faq ) :
                                $is_selected = isset( $selected_faqs_data[ $key ] );
                                ?>
                                <div class="mpcrbm_faq_item mpcrbm-faq-toggle-item<?php echo $is_selected ? ' is-selected' : ''; ?>"
                                     data-key="<?php echo esc_attr( $key ); ?>"
                                     data-title="<?php echo esc_attr( $faq['title'] ); ?>"
                                >
                                    <span class="mpcrbm-faq-toggle-check"><i class="fas fa-check"></i></span>
                                    <div class="mpcrbm_faq_title"><?php echo esc_html( $faq['title'] ); ?></div>
                                    <span class="mpcrbm-faq-toggle-status"><?php esc_html_e( 'Selected', 'car-rental-manager' ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p><?php esc_html_e( 'No FAQs available yet — add some from the global FAQ list first.', 'car-rental-manager' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" id="mpcrbm_added_faq_input" name="mpcrbm_added_faq" value="<?php echo esc_attr( wp_json_encode( array_keys( $selected_faqs_data ) ) ); ?>">
                </section>
                    </div>
                </div>

            </div>

        <?php }

    }

    new MPCRBM_Faq_Settings();
}