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

            // Edit/delete are privileged actions. They must NOT be exposed to
            // unauthenticated visitors: a guest has get_current_user_id() === 0
            // and guest reviews are stored with user_id === 0, so a "nopriv"
            // handler would let anyone edit/delete guest reviews with only the
            // public frontend nonce (CSRF token, not an authorization check).
            add_action('wp_ajax_mpcrbm_review_delete', [ $this, 'mpcrbm_review_delete_callback' ] );

            add_action('wp_ajax_mpcrbm_review_edit', [ $this, 'mpcrbm_review_edit_callback' ] );
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

            // Record the author's user ID (0 for guests). This is what the
            // edit/delete ownership check relies on, so a logged-in author can
            // later manage their own review while guests remain non-owners.
            $current_user_id = get_current_user_id();

            $commentdata = [
                'comment_post_ID' => $post_id,
                'comment_author'  => isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '',
                'comment_author_email' =>  isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
                'comment_content' =>  isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
                'user_id'         => $current_user_id,
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

            if( ! $this->mpcrbm_user_can_manage_review( $comment ) ){
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
            if( ! $this->mpcrbm_user_can_manage_review( $comment ) ){
                wp_send_json_error('You are not allowed to delete this review');
            }

            wp_delete_comment( $comment_id, true );

            wp_send_json_success('Review deleted');
        }

        /**
         * Authorization gate for editing/deleting a review comment.
         *
         * Returns true only when the current request comes from:
         *  - a comment moderator/administrator (moderate_comments capability), or
         *  - the logged-in author who actually owns the comment.
         *
         * Guests (user_id === 0) can never be owners: a logged-out visitor has
         * get_current_user_id() === 0, which must NOT be treated as owning the
         * many guest reviews that are also stored with user_id === 0. The public
         * frontend nonce only proves the request is same-origin, not who sent it.
         *
         * @param WP_Comment $comment Comment object being acted upon.
         * @return bool
         */
        private function mpcrbm_user_can_manage_review( $comment ) {

            // Moderators/admins may always manage reviews.
            if ( current_user_can( 'moderate_comments' ) ) {
                return true;
            }

            $current_user_id = get_current_user_id();
            $comment_user_id = isset( $comment->user_id ) ? intval( $comment->user_id ) : 0;

            // Must be a logged-in user who owns a comment that has a real owner.
            return $current_user_id > 0 && $comment_user_id > 0 && $current_user_id === $comment_user_id;
        }

    }

    new MPCRBM_Manage_Review();
}