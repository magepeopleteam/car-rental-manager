<?php
/*
	* @Author 		magePeople
	* Copyright: 	mage-people.com
	*/
if (!defined('ABSPATH')) {
    die;
}
if (!class_exists('MPCRBM_Manage_Review')) {
    class MPCRBM_Manage_Review{
        public function __construct() {

            add_action('wp_ajax_mpcrbm_review_save', [ $this, 'mpcrbm_review_save_callback' ] );
            add_action('wp_ajax_nopriv_mpcrbm_review_save', [ $this, 'mpcrbm_review_save_callback' ] );

            add_action('wp_ajax_mpcrbm_review_delete', [ $this, 'mpcrbm_review_delete_callback' ] );
            add_action('wp_ajax_nopriv_mpcrbm_review_delete', [ $this, 'mpcrbm_review_delete_callback' ] );

            add_action('wp_ajax_mpcrbm_review_edit', [ $this, 'mpcrbm_review_edit_callback' ] );
            add_action('wp_ajax_nopriv_mpcrbm_review_edit', [ $this, 'mpcrbm_review_edit_callback' ] );
        }

        function mpcrbm_review_save_callback() {

            // Check nonce
            if( ! isset($_POST['mpcrbm_review_nonce']) ||
                ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_review_nonce'] ) ), 'mpcrbm_review_nonce_action') ) {
                wp_send_json_error('Nonce verification failed.');
                wp_die();
            }

            $post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : '';
            $rating  = isset( $_POST['rating'] ) ? intval( wp_unslash( $_POST['rating'] ) ) : '';

            $commentdata = [
                'comment_post_ID' => $post_id,
                'comment_author'  => isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '',
                'comment_author_email' =>  isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
                'comment_content' =>  isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
                'comment_approved' => 1
            ];

            $comment_id = wp_insert_comment($commentdata);

            if ($comment_id && $rating) {
                add_comment_meta($comment_id, 'mpcrbm_review_rating', $rating);
            }

            echo '<p>'.esc_html_e( 'Review submitted successfully!', 'car-rental-manager' ).'</p>';
            wp_die();
        }

        function mpcrbm_review_edit_callback() {

            if(!isset($_POST['mpcrbm_review_nonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_review_nonce'] ) ), 'mpcrbm_review_nonce_action')){
                wp_send_json_error('Nonce verification failed');
            }

            $comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : '';
            $new_content = isset( $_POST['comment_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment_content'] ) ) : '';
            $comment = get_comment($comment_id);

            if(!$comment) wp_send_json_error('Comment not found');

            $current_user_id = get_current_user_id();
            if($current_user_id !== intval($comment->user_id) && !current_user_can('manage_options')){
                wp_send_json_error('You are not allowed to edit this review');
            }

            wp_update_comment([
                'comment_ID' => $comment_id,
                'comment_content' => $new_content
            ]);

            wp_send_json_success('Review updated');
        }
        function mpcrbm_review_delete_callback() {

            // Check nonce
            if(!isset($_POST['mpcrbm_review_nonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_review_nonce'] ) ), 'mpcrbm_review_nonce_action')){
                wp_send_json_error('Nonce verification failed');
            }

            $comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : '';
            $comment = get_comment($comment_id);

            if(!$comment) wp_send_json_error('Comment not found');

            // Permission check: author or admin
            $current_user_id = get_current_user_id();
            if($current_user_id !== intval($comment->user_id) && !current_user_can('manage_options')){
                wp_send_json_error('You are not allowed to delete this review');
            }

            wp_delete_comment( $comment_id, true );

            wp_send_json_success('Review deleted');
        }

    }

    new MPCRBM_Manage_Review();
}