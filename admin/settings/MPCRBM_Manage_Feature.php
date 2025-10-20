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
        }

        function mpcrbm_update_feature_meta() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            $post_id = intval( $_POST['post_id'] );
            $term_id = intval( $_POST['term_id'] );
            $feature_type = sanitize_text_field( $_POST['feature_type'] );
            $action_type = sanitize_text_field( $_POST['action_type'] );
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
                <h3><?php esc_html_e( 'Car Feature', 'car-rental-manager' ); ?></h3>
                <p><?php esc_html_e( 'Car Feature settings.', 'car-rental-manager' ); ?></p>

                <?php wp_nonce_field( 'manage_car_feature_settings', 'faq_settings_nonce' ); ?>
                <section class="bg-light">
                    <h6><?php esc_html_e( 'Manage Car Feature', 'car-rental-manager' ); ?></h6>
                    <span><?php esc_html_e( 'Configure and manage Car Feature', 'car-rental-manager' ); ?></span>
                </section>
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
                <div class="mpcrbm_faq_all_question_box">
                    <h3>Include Features</h3>
                    <div class="mpcrbm_include_feature">
                        <input type="hidden" class="mpcrbm_include_feature_term_id" name="mpcrbm_include_feature_term_id" value="<?php echo esc_attr(implode(',', $included)); ?>">
                        <?php foreach ($terms as $term) : ?>
                            <label>
                                <input type="checkbox" class="mpcrbm_include_checkbox" value="<?php echo esc_attr($term->term_id); ?>"
                                    <?php checked( in_array($term->term_id, $included ) ); ?>>
                                <?php echo esc_html( $term->name ); ?>
                            </label><br>
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
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
            </div>
        <?php }

    }

    new MPCRBM_Manage_Feature();
}