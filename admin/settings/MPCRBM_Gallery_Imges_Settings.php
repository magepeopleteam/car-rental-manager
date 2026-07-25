<?php
/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
if ( ! defined( 'ABSPATH' ) ) {
    die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Gallery_Imges_Settings' ) ) {
    class MPCRBM_Gallery_Imges_Settings
    {
        public function __construct() {
            add_action( 'mpcrbm_settings_tab_content', [$this,'add_tabs_content'] );
            add_action('save_post', array($this, 'settings_save'), 99, 1);
        }

        public function add_tabs_content( $post_id ) {
            wp_nonce_field( 'mpcrbm_save_gallery_image_nonce', 'mpcrbm_gallery_image_nonce' );
            $enable_gallery = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_enable_gallery', 'on' );
            $is_gallery_checked = ( $enable_gallery === 'on' ) ? 'checked' : '';
            ?>
            <div class="tabsItem" data-tabs="#mpcrbm_settings_gallery_images">
                <div class="mpcrbm-gallery-enable-row">
                    <h6><?php esc_html_e( 'Enable/Disable Gallery', 'car-rental-manager' ); ?></h6>
                    <label class="roundSwitchLabel">
                        <input type="checkbox" class="mpcrbm_switch_checkbox" id="mpcrbm_enable_gallery" name="mpcrbm_enable_gallery" <?php echo esc_attr( $is_gallery_checked ); ?>>
                        <span class="roundSwitch"></span>
                    </label>
                </div>
                <div class="mpcrbm-gallery-images-wrapper" id="mpcrbm_gallery_images_wrapper" style="display: <?php echo esc_attr( $enable_gallery === 'on' ? 'block' : 'none' ); ?>">
                    <div class="mpcrbm-gallery-images-label">
                        <h6><?php esc_html_e( 'Gallery Images', 'car-rental-manager' ); ?></h6>
                        <span class="mpcrbm-gallery-images-info" title="<?php esc_attr_e( 'Please upload gallery images in a 4:3 ratio (e.g. 1200x900px), matching the featured image size.', 'car-rental-manager' ); ?>">?</span>
                    </div>
                    <section>
                        <div  id="field-wrapper-<?php echo esc_attr($post_id); ?>" class="<?php if(!empty($depends)) echo 'dependency-field'; ?> field-wrapper field-media-multi-wrapper field-media-multi-wrapper-<?php echo esc_attr($post_id); ?>">
                            <div class='button upload' id='media_upload_<?php echo esc_attr($post_id); ?>'>
                                <?php echo esc_html__('Add Image','car-rental-manager');?>
                            </div>
                            <div class='button clear' id='media_clear_<?php echo esc_attr($post_id); ?>'>
                                <?php echo esc_html__('Clear','car-rental-manager');?>
                            </div>
                            <div class="mpcrbm_gallery-images-lists media-list-<?php echo esc_attr($post_id); ?> ">
                                <?php
                                $gallery_images = get_post_meta($post_id,'mpcrbm_gallery_images',true);
                                $gallery_images = $gallery_images ? $gallery_images : [];

                                if(!empty($gallery_images) && is_array($gallery_images)):
                                    foreach ($gallery_images as $image ):
                                        $media_url	= wp_get_attachment_url( $image );
                                        $media_type	= get_post_mime_type( $image );
                                        $media_title= get_the_title( $image );
                                        ?>
                                        <div class="mpcrbm_gallery-image">
                                            <div class="mpcrbm_gallery_image_remove" onclick="jQuery(this).parent().remove()">X</i></div>

                                            <img class="mpcrbm_gallery-images" id='media_preview_<?php echo esc_attr($post_id); ?>' src='<?php echo esc_attr($media_url); ?>' />
                                            <input type='hidden' name='mpcrbm_gallery_images[]' value='<?php echo esc_attr($image); ?>' />
                                        </div>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    </section>
                </div>
                <script>
                    jQuery(document).ready(function($){
                        $('#media_upload_<?php echo esc_attr($post_id); ?>').click(function() {
                            //var send_attachment_bkp = wp.media.editor.send.attachment;
                            wp.media.editor.send.attachment = function(props, attachment) {
                                attachment_id = attachment.id;
                                attachment_url = attachment.url;
                                html = '<div class=" mpcrbm_gallery-image">';
                                html += '<span class="mpcrbm_gallery_image_remove" onclick="jQuery(this).parent().remove()">X</i></span>';
                                html += '<img src="'+attachment_url+'" class="mpcrbm_gallery-images"/>';
                                html += '<input type="hidden" name="mpcrbm_gallery_images[]" value="'+attachment_id+'" />';
                                html += '</div>';
                                $('.media-list-<?php echo esc_attr($post_id); ?>').append(html);
                                //wp.media.editor.send.attachment = send_attachment_bkp;
                            }
                            wp.media.editor.open($(this));
                            return false;
                        });
                        $('#media_clear_<?php echo esc_attr($post_id); ?>').click(function() {
                            $('.media-list-<?php echo esc_attr($post_id); ?> .gallery-image').remove();
                        })
                    });
                </script>
            </div>
            <?php
        }
        public function settings_save($post_id) {

            if ( ! isset( $_POST['mpcrbm_gallery_image_nonce'] ) ) {
                return;
            }
            if ( ! isset( $_POST['mpcrbm_gallery_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_gallery_image_nonce'] ) ), 'mpcrbm_save_gallery_image_nonce' ) ) {
                return; // or wp_die( esc_html__( 'Security check failed.', 'text-domain' ) );
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {

                $gallery_images = isset( $_POST['mpcrbm_gallery_images'] )
                    ? array_map( 'absint', wp_unslash( $_POST['mpcrbm_gallery_images'] ) )
                    : [];
//                $gallery_images = isset( $_POST['mpcrbm_gallery_images'] ) ? map_deep( sanitize_text_field( wp_unslash( $_POST['mpcrbm_gallery_images'] ) ), 'absint' ) : [];
                update_post_meta($post_id, 'mpcrbm_gallery_images', $gallery_images);

                $enable_gallery = isset( $_POST['mpcrbm_enable_gallery'] ) ? 'on' : 'off';
                update_post_meta( $post_id, 'mpcrbm_enable_gallery', $enable_gallery );

            }
        }

    }

    new MPCRBM_Gallery_Imges_Settings();
}