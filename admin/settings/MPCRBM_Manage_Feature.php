<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.
if ( ! class_exists( 'MPCRBM_Manage_Feature' ) ) {
    class MPCRBM_Manage_Feature
    {
        public function __construct() {
            add_action( 'mpcrbm_settings_tab_content', [ $this, 'feature_tab_content' ], 10, 1 );
            add_action('wp_ajax_mpcrbm_update_feature_meta', [ $this, 'mpcrbm_update_feature_meta' ] );
            add_action('wp_ajax_mpcrbm_save_new_feature', [ $this, 'mpcrbm_save_new_feature' ] );
        }

        // "Add New Feature" panel (mirrors MPCRBM_Faq_Settings.php's "Add New
        // FAQ" panel) — creates a brand new mpcrbm_car_feature term on the fly
        // and immediately includes it on the current car, same as ticking one
        // of the existing "Include Features" checkboxes.
        function mpcrbm_save_new_feature() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
            $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
                wp_send_json_error( [ 'message' => 'Permission denied' ] );
            }
            if ( '' === $name ) {
                wp_send_json_error( [ 'message' => 'Feature name is required.' ] );
            }

            $existing = get_term_by( 'name', $name, 'mpcrbm_car_feature' );
            if ( $existing ) {
                $term_id = $existing->term_id;
            } else {
                $result = wp_insert_term( $name, 'mpcrbm_car_feature' );
                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( [ 'message' => $result->get_error_message() ] );
                }
                $term_id = $result['term_id'];
            }

            $included = get_post_meta( $post_id, 'mpcrbm_include_features', true );
            if ( ! is_array( $included ) ) {
                $included = [];
            }
            if ( ! in_array( $term_id, $included, true ) ) {
                $included[] = $term_id;
                update_post_meta( $post_id, 'mpcrbm_include_features', $included );
            }

            wp_send_json_success( [ 'term_id' => $term_id, 'name' => $name ] );
        }

        function mpcrbm_update_feature_meta() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
            $term_id = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
            $feature_type = isset( $_POST['feature_type'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_type'] ) ) : '';
            $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

            // Authorization: only users who can edit this post may change its feature meta.
            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
                wp_send_json_error( [ 'message' => 'Permission denied' ] );
            }
            // Validate the term actually belongs to the car-feature taxonomy.
            if ( ! $term_id || ! term_exists( $term_id, 'mpcrbm_car_feature' ) ) {
                wp_send_json_error( [ 'message' => 'Invalid feature' ] );
            }

            $meta_key = ($feature_type === 'include') ? 'mpcrbm_include_features' : 'mpcrbm_exclude_features';
            $current = get_post_meta( $post_id, $meta_key, true );
            if ( !is_array( $current ) ) $current = [];

            if ( $action_type === 'add' ) {
                if ( !in_array( $term_id, $current ) ) {
                    $current[] = $term_id;
                }
            } elseif ($action_type === 'remove') {
                $current = array_diff($current, [$term_id]);
            }

            update_post_meta( $post_id, $meta_key, array_values( $current ) );

            wp_send_json_success( ['saved' => $current] );
        }

        public function feature_tab_content( $post_id ){ ?>

            <div class="tabsItem" data-tabs="#mpcrbm_setting_feature">
                <?php wp_nonce_field( 'manage_car_feature_settings', 'faq_settings_nonce' ); ?>
                <div class="mpcrbm-info-card">
                    <div class="mpcrbm-info-card-body">
                <?php

                $taxonomy = 'mpcrbm_car_feature';
                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]);

                $included = get_post_meta($post_id, 'mpcrbm_include_features', true);
                $excluded = get_post_meta($post_id, 'mpcrbm_exclude_features', true);

                if (!is_array($included)) $included = [];
                if (!is_array($excluded)) $excluded = [];

                ?>
                <div class="mpcrbm-faq-toolbar">
                    <div class="mpcrbm-faq-toolbar-text">
                        <div class="mpcrbm-faq-eyebrow">
                            <span class="mpcrbm-faq-eyebrow-dot"></span>
                            <?php esc_html_e( 'Content Management', 'car-rental-manager' ); ?>
                        </div>
                        <h3><?php esc_html_e( 'Manage Car Feature', 'car-rental-manager' ); ?></h3>
                        <span class="desc"><?php esc_html_e( 'Highlight the specs and amenities this car offers. Ticked features under "Include" show on the car page; anything under "Exclude" is called out as not included.', 'car-rental-manager' ); ?></span>
                    </div>
                    <div class="mpcrbm-faq-toolbar-actions">
                        <button type="button" class="mpcrbm-faq-add-new-toggle" id="mpcrbm_toggle_new_feature">
                            <i class="mi mi-plus"></i> <?php esc_html_e( 'Add New Feature', 'car-rental-manager' ); ?>
                        </button>
                    </div>
                </div>

                <div class="mpcrbm_faq_modal mpcrbm-faq-add-new-modal" id="mpcrbm_new_feature_panel">
                    <div class="mpcrbm_modal_content">
                        <h3><?php esc_html_e( 'Add New Feature', 'car-rental-manager' ); ?></h3>
                        <label class="mpcrbm-faq-add-new-field">
                            <span><?php esc_html_e( 'Feature Name', 'car-rental-manager' ); ?></span>
                            <input type="text" id="mpcrbm_new_feature_name" class="formControl" placeholder="<?php esc_attr_e( 'e.g. Bluetooth', 'car-rental-manager' ); ?>">
                        </label>
                        <div class="mpcrbm-faq-add-new-actions">
                            <button type="button" class="_themeButton_xs_mT_xs mpcrbm-price-add-btn" id="mpcrbm_save_new_feature_btn"><?php esc_html_e( 'Save Feature', 'car-rental-manager' ); ?></button>
                            <button type="button" class="button" id="mpcrbm_cancel_new_feature_btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
                        </div>
                    </div>
                </div>

                <section class="mpcrbm_faq_question_holder">
                    <div class="mpcrbm_faq_all_question_box">
                        <h3>Include Features</h3>
                        <div class="mpcrbm_include_feature">
                            <input type="hidden" class="mpcrbm_include_feature_term_id" name="mpcrbm_include_feature_term_id" value="<?php echo esc_attr(implode(',', $included)); ?>">
                            <?php foreach ($terms as $term) : ?>
                                <label>
                                    <input type="checkbox" class="mpcrbm_include_checkbox" value="<?php echo esc_attr($term->term_id); ?>"
                                        <?php checked( in_array($term->term_id, $included ) ); ?>>
                                    <?php echo esc_html( $term->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mpcrbm_selected_faq_question_box">
                        <h3>Exclude Features</h3>
                        <div class="mpcrbm_exclude_feature">
                            <input type="hidden" class="mpcrbm_exclude_feature_term_id" name="mpcrbm_exclude_feature_term_id" value="<?php echo esc_attr(implode(',', $excluded)); ?>">
                            <?php foreach ($terms as $term) : ?>
                                <label>
                                    <input type="checkbox" class="mpcrbm_exclude_checkbox" value="<?php echo esc_attr($term->term_id); ?>"
                                        <?php checked(in_array($term->term_id, $excluded)); ?>>
                                    <?php echo esc_html($term->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                    </div>
                </div>
            </div>
        <?php }

    }

    new MPCRBM_Manage_Feature();
}