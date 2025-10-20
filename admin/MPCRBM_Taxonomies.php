<?php

/*
	* @Author 		MagePeople Team
	* Copyright: 	mage-people.com
	*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPCRBM_Taxonomies')) {


    class MPCRBM_Taxonomies {

        public function __construct() {
            // Hook into admin_menu
            add_action('admin_menu', array($this, 'mpcrbm_register_submenu'));

            add_action( 'wp_ajax_mpcrbm_load_taxonomies', array( $this, 'ajax_load_taxonomies' ) );
            add_action( 'wp_ajax_mpcrbm_save_taxonomy', array( $this, 'ajax_save_taxonomy' ) );

            add_action('wp_ajax_mpcrbm_update_taxonomy', [$this, 'ajax_update_taxonomy']);
            add_action('wp_ajax_mpcrbm_delete_taxonomy', [$this, 'ajax_delete_taxonomy']);

            add_action('admin_action_mpcrbm_duplicate_car', [$this, 'mpcrbm_duplicate_car']);

            add_action('wp_ajax_mpcrbm_delete_multiple_cars', [$this, 'mpcrbm_delete_multiple_cars']);
        }

        function mpcrbm_delete_multiple_cars() {
            check_ajax_referer('mpcrbm_extra_service', '_wpnonce');

            $ids = isset($_POST['ids']) ? explode(',', sanitize_text_field($_POST['ids'])) : [];

            if (!empty($ids)) {
                foreach ($ids as $id) {
                    wp_trash_post((int)$id);
                }
                wp_send_json_success(['trashed' => $ids]);
            } else {
                wp_send_json_error(['message' => 'No IDs provided']);
            }
        }


        public function mpcrbm_duplicate_car(){
            if ( ! isset( $_GET['post'] ) || ! isset( $_GET['_wpnonce'] ) ) {
                wp_die( 'Invalid request.' );
            }

            $post_id = intval( $_GET['post'] );
            if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'mpcrbm_duplicate_car_' . $post_id ) ) {
                wp_die( 'Security check failed.' );
            }

            $post = get_post( $post_id );
            if ( ! $post ) wp_die( 'Post not found.' );

            $new_post = array(
                'post_title'   => $post->post_title . ' (Copy)',
                'post_content' => $post->post_content,
                'post_status'  => 'draft',
                'post_type'    => $post->post_type,
            );

            $new_post_id = wp_insert_post( $new_post );

            // Copy meta
            $metas = get_post_meta( $post_id );
            foreach ( $metas as $key => $values ) {
                foreach ( $values as $value ) {
                    update_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
                }
            }
            wp_redirect( get_edit_post_link( $new_post_id, 'url' ) );
            exit;
        }


        public function ajax_load_taxonomies() {
//            check_ajax_referer('mpcrbm_admin_nonce', 'security');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }

            $type = sanitize_text_field($_POST['taxonomy_type']);

            $terms = [];

            if( $type === 'mpcrbm_car_list' ){
                $terms = [];
            }else{
                $terms = get_terms(array('taxonomy' => $type, 'hide_empty' => false));
            }


            ob_start();
            ?>
            <div class="mpcrbm_taxonomoy_data_holder">
                <?php  if ( !empty( $terms ) && $type !== 'mpcrbm_car_list' ) {
                    if( $type === 'mpcrbm_car_type' ){
                        $type_title = 'Car Types';
                    }elseif( $type === 'mpcrbm_fuel_type' ){
                        $type_title = 'Fuel Types';
                    }else if( $type === 'mpcrbm_seating_capacity' ){
                        $type_title = 'Seating Capacity';
                    }else if( $type === 'mpcrbm_car_brand' ){
                        $type_title = 'Car Brand';
                    }else if( $type === 'mpcrbm_make_year' ){
                        $type_title = 'Make Year';
                    }else{
                        $type_title = 'Car List';
                    }
                    ?>
                    <h2><?php echo esc_html( $type_title );?></h2>
                    <div class="mpcrbm_taxonomies_toolbar">
                        <button class="mpcrbm_taxonomies_add_btn"><i class="mi mi-plus"></i> <?php esc_attr_e( 'Add New', 'car-rental-manager' );?></button>
                        <input type="text" class="mpcrbm_taxonomies_search" placeholder="<?php esc_attr_e( 'Search taxonomy...', 'car-rental-manager' );?>">
                    </div>
                <?php }else{?>
                <?php }?>
                <?php
                if ( !empty( $terms ) && $type !== 'mpcrbm_car_list' ) {
                    echo '<div class="mpcrbm_taxonomies_list">';
                    foreach ($terms as $term) {
                        $description = '';
                        if( !empty( $term->description ) ){
                            $description = $term->description;
                        }
                        ?>
                        <div class="mpcrbm_taxonomy_item"
                             data-term-id="<?php echo esc_attr( $term->term_id); ?>"
                             data-type="<?php echo esc_attr($type); ?>"
                             data-term-name="<?php echo esc_attr($term->name); ?>"
                             data-term-slug="<?php echo esc_attr($term->slug); ?>"
                             data-term-desc="<?php echo esc_attr($term->description); ?>"
                        >
                            <div class="mpcrbm_taxonomy_content">
                                <strong><?php echo esc_html($term->name); ?> (<?php echo esc_html($term->count); ?>) </strong><br>
                                <small><?php echo esc_html( $description ); ?></small>
                            </div>

                            <div class="mpcrbm_taxonomy_actions">
                                <button class="mpcrbm_action_btn view mpcrbm_edit_taxonomy" title="<?php esc_attr_e( 'Edit', 'car-rental-manager' ); ?>"><i class="mi mi-pencil"></i></button>
                                <button class="mpcrbm_action_btn delete mpcrbm_delete_taxonomy" title="<?php esc_attr_e( 'Delete', 'car-rental-manager' ); ?>"><i class="mi mi-trash"></i></button>
                            </div>

                        </div>
                        <?php
                    }
                    echo '</div>';
                }else if ( empty( $terms ) && $type === 'mpcrbm_car_list'){
                    include( MPCRBM_Function::template_path( 'car_list/car_lists.php' ) );
                }
                else {
                    echo '<p> '.esc_attr_e( 'Search taxonomy...', 'car-rental-manager' ) .' '. esc_html($type) . '</p>';
                }
                ?>
            </div>
            <?php

            wp_send_json_success(['html' => ob_get_clean()]);
        }

        public static function load_taxonomies( $type ) {

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }

            $terms = [];

            if( $type === 'mpcrbm_car_list' ){
                $terms = [];
            }else{
                $terms = get_terms(array('taxonomy' => $type, 'hide_empty' => false));
            }


            ob_start();
            ?>
            <div class="mpcrbm_taxonomoy_data_holder">
                <?php  if ( $type !== 'mpcrbm_car_list' ) {
                    if( $type === 'mpcrbm_car_type' ){
                        $type_title = 'Car Types';
                    }elseif( $type === 'mpcrbm_fuel_type' ){
                        $type_title = 'Fuel Types';
                    }else if( $type === 'mpcrbm_seating_capacity' ){
                        $type_title = 'Seating Capacity';
                    }else if( $type === 'mpcrbm_car_brand' ){
                        $type_title = 'Car Brand';
                    }else if( $type === 'mpcrbm_make_year' ){
                        $type_title = 'Make Year';
                    }else if( $type === 'mpcrbm_car_feature' ){
                        $type_title = 'Car Feature';
                    }else{
                        $type_title = 'Car List';
                    }
                    ?>
                    <h2><?php echo esc_html( $type_title );?></h2>
                    <div class="mpcrbm_taxonomies_toolbar">
                        <button class="mpcrbm_taxonomies_add_btn"><i class="mi mi-plus"></i> <?php esc_attr_e( 'Add New', 'car-rental-manager' );?></button>
                        <input type="text" class="mpcrbm_taxonomies_search" placeholder="<?php esc_attr_e( 'Search taxonomy...', 'car-rental-manager' );?>">
                    </div>
                <?php }else{?>
                <?php }?>
                <?php
                if ( !empty( $terms ) && $type !== 'mpcrbm_car_list' ) {
                    echo '<div class="mpcrbm_taxonomies_list">';
                    foreach ($terms as $term) {
                        $description = '';
                        if( !empty( $term->description ) ){
                            $description = $term->description;
                        }
                        ?>
                        <div class="mpcrbm_taxonomy_item"
                             data-term-id="<?php echo esc_attr( $term->term_id); ?>"
                             data-type="<?php echo esc_attr($type); ?>"
                             data-term-name="<?php echo esc_attr($term->name); ?>"
                             data-term-slug="<?php echo esc_attr($term->slug); ?>"
                             data-term-desc="<?php echo esc_attr($term->description); ?>"
                        >
                            <div class="mpcrbm_taxonomy_content">
                                <strong><?php echo esc_html($term->name); ?> (<?php echo esc_html($term->count); ?>) </strong><br>
                                <small><?php echo esc_html( $description ); ?></small>
                            </div>

                            <div class="mpcrbm_taxonomy_actions">
                                <button class="mpcrbm_action_btn view mpcrbm_edit_taxonomy" title="<?php esc_attr_e( 'Edit', 'car-rental-manager' ); ?>"><i class="mi mi-pencil"></i></button>
                                <button class="mpcrbm_action_btn delete mpcrbm_delete_taxonomy" title="<?php esc_attr_e( 'Delete', 'car-rental-manager' ); ?>"><i class="mi mi-trash"></i></button>
                            </div>

                        </div>
                        <?php
                    }
                    echo '</div>';
                }else if ( empty( $terms ) && $type === 'mpcrbm_car_list'){
                    include( MPCRBM_Function::template_path( 'car_list/car_lists.php' ) );
                }
                else {
                    echo '<p> '.esc_attr_e( 'Search taxonomy...', 'car-rental-manager' ) .' '. esc_html($type) . '</p>';
                }
                ?>
            </div>
            <?php

            return ob_get_clean();
        }

        public function ajax_save_taxonomy() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => __( 'You do not have permission to perform this action.', 'car-rental-manager' )
                ), 403 );
            }

            $type = isset($_POST['taxonomy_type']) ? sanitize_key($_POST['taxonomy_type']) : '';
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
            $desc = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

            if ( empty( $type ) || empty( $name ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Taxonomy type and name are required.', 'car-rental-manager' )
                ), 400 );
            }

            if ( ! taxonomy_exists( $type ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Invalid taxonomy type specified.', 'car-rental-manager' )
                ), 400 );
            }

            $result = wp_insert_term(
                $name,
                $type,
                array(
                    'slug'        => $slug,
                    'description' => $desc,
                )
            );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array(
                    'message' => esc_html( $result->get_error_message() ),
                ), 400 );
            }

            wp_send_json_success( array(
                'message' => __( 'Taxonomy added successfully!', 'car-rental-manager' ),
                'term_id' => absint( $result['term_id'] ),
            ), 200 );
        }


        // Register submenu
        public function mpcrbm_register_submenu() {
            $cpt = MPCRBM_Function::get_cpt();

            add_submenu_page(
                'edit.php?post_type=' . $cpt,
                esc_html__('Car Rental', 'car-rental-manager'),
                esc_html__('Car Rental', 'car-rental-manager'),
                'manage_options',
                'mpcrbm_car_rental',
                array($this, 'mpcrbm_taxonomies_setup')
            );


        }

        // Callback to render page content
        public function mpcrbm_taxonomies_setup() {
            ?>
            <div class="mpcrbm_taxonomies_wrap">



                <div class="mpcrbm_left_sidebar">
                    <div class="mpcrbm_car_rental_title">
                        <h2><?php esc_html_e( 'Car Rental', 'car-rental-manager' );?> </h2>
                        <p><?php esc_html_e( 'Management System', 'car-rental-manager' );?></p>
                    </div>

                    <div class="mpcrbm_taxonomies_tabs">
                        <button class="mpcrbm_car_list_tab mpcrbm_taxonomies_tab active" data-target="mpcrbm_car_list"><i class="mi mi-cars"></i> <?php esc_attr_e( 'Car List', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_car_type"><i class="mi mi-tachometer-fast"></i> <?php esc_attr_e( 'Car Type', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_fuel_type"><i class="mi mi-gas-pump-alt"></i> <?php esc_attr_e( 'Fuel Type', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_seating_capacity"><i class="mi mi-person-seat"></i> <?php esc_attr_e( 'Seating Capacity', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_car_brand"><i class="mi mi-bonus"></i> <?php esc_attr_e( 'Car Brand', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_make_year"><i class="mi mi-time-quarter-to"></i> <?php esc_attr_e( 'Make Year', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_car_feature"><i class="mi mi-list-timeline"></i> <?php esc_attr_e( 'Car Feature', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_manage_faq"><i class="mi mi-messages-question"></i> <?php esc_attr_e( 'Manage Faq', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_manage_term_condition"><i class="mi mi-blog-text"></i> <?php esc_attr_e( 'Manage Term & Condition', 'car-rental-manager' );?></button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_manage_car_info"><i class="mi mi-blog-text"></i> <?php esc_attr_e( 'Manage Term & Condition', 'car-rental-manager' );?></button>
                    </div>
                </div>
                <div class="mpcrbm_left_main_content">

                    <div class="mpcrbm_analytics">
                        <div class="mpcrbm_stat-card total">
                            <div class="mpcrbm_stat-left">
                                <i class="mi mi-cars"></i>
                                <div>
                                    <div class="mpcrbm_stat-label"><?php esc_attr_e( 'Total Cars', 'car-rental-manager' );?></div>
                                    <div class="mpcrbm_stat-value">56</div>
                                </div>
                            </div>
                            <div class="mpcrbm_stat-change positive">↑ 2 <?php esc_attr_e( 'new this month', 'car-rental-manager' );?></div>
                        </div>

                        <div class="mpcrbm_stat-card available">
                            <div class="mpcrbm_stat-left">
                                <i class="mi mi-car"></i>
                                <div>
                                    <div class="mpcrbm_stat-label"><?php esc_attr_e( 'Available', 'car-rental-manager' );?></div>
                                    <div class="mpcrbm_stat-value">7</div>
                                </div>
                            </div>
                            <div class="mpcrbm_stat-change positive">100% <?php esc_attr_e( 'availability', 'car-rental-manager' );?></div>
                        </div>

                        <div class="mpcrbm_stat-card rented">
                            <div class="mpcrbm_stat-left">
                                <i class="mi mi-car-journey"></i>
                                <div>
                                    <div class="mpcrbm_stat-label"><?php esc_attr_e( 'Currently Rented', 'car-rental-manager' );?></div>
                                    <div class="mpcrbm_stat-value">0</div>
                                </div>
                            </div>
                            <div class="mpcrbm_stat-change positive"><?php esc_attr_e( 'Ready to rent', 'car-rental-manager' );?></div>
                        </div>

                        <div class="mpcrbm_stat-card revenue">
                            <div class="mpcrbm_stat-left">
                                <i class="mi mi-coins"></i>
                                <div>
                                    <div class="mpcrbm_stat-label"><?php esc_attr_e( 'Daily Revenue', 'car-rental-manager' );?></div>
                                    <div class="mpcrbm_stat-value">$70</div>
                                </div>
                            </div>
                            <div class="mpcrbm_stat-change positive">↑ $10/day <?php esc_attr_e( 'avg', 'car-rental-manager' );?></div>
                        </div>
                    </div>

                    <div class="mpcrbm_taxonomies_content">
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_car_list_holder">
                            <?php
                                include( MPCRBM_Function::template_path( 'car_list/car_lists.php' ) );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_car_type_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_car_type' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_fuel_type_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_fuel_type' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_seating_capacity_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_seating_capacity' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_car_brand_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_car_brand' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_make_year_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_make_year' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_car_feature_holder" style="display: none">
                            <?php
                                echo self::load_taxonomies( 'mpcrbm_car_feature' );
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_manage_faq_holder" style="display: none">
                            <?php
                                MPCRBM_Manage_Faq::faq_display();
                            ?>
                        </div>
                        <div class="mpcrbm_taxonomies_content_holder" id="mpcrbm_manage_term_condition_holder" style="display: none">
                            <?php
                                MPCRBM_Manage_Faq::term_and_condition_display();
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Popup Form -->
                <div class="mpcrbm_taxonomies_popup_overlay">
                    <div class="mpcrbm_taxonomies_popup">
                        <h3><?php esc_attr_e( 'Add New Taxonomy', 'car-rental-manager' );?></h3>
                        <label><?php esc_attr_e( 'Name', 'car-rental-manager' );?>:</label>
                        <input type="text" id="mpcrbm_taxonomies_name" placeholder="<?php esc_attr_e( 'Enter name', 'car-rental-manager' );?>">
                        <label><?php esc_attr_e( 'Slug', 'car-rental-manager' );?>:</label>
                        <input type="text" id="mpcrbm_taxonomies_slug" placeholder="<?php esc_attr_e( 'Optional slug', 'car-rental-manager' );?>">
                        <label><?php esc_attr_e( 'Description', 'car-rental-manager' );?>:</label>
                        <textarea id="mpcrbm_taxonomies_desc" placeholder="<?php esc_attr_e( 'Short description', 'car-rental-manager' );?>"></textarea>

                        <div class="mpcrbm_taxonomies_popup_actions">
                            <button class="mpcrbm_taxonomies_save_btn"><?php esc_attr_e( 'Save', 'car-rental-manager' );?></button>
                            <button class="mpcrbm_taxonomies_cancel_btn"><?php esc_attr_e( 'Cancel', 'car-rental-manager' );?></button>
                        </div>
                    </div>
                </div>
            </div>

            <?php
        }


        public function ajax_update_taxonomy() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }

            $term_id = intval($_POST['term_id']);
            $type = sanitize_text_field($_POST['taxonomy_type']);
            $name = sanitize_text_field($_POST['name']);
            $slug = sanitize_title($_POST['slug']);
            $desc = sanitize_textarea_field($_POST['description']);

            $result = wp_update_term($term_id, $type, [
                'name' => $name,
                'slug' => $slug,
                'description' => $desc,
            ]);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            wp_send_json_success(['message' => 'Taxonomy updated successfully!']);
        }

        public function ajax_delete_taxonomy() {
            check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }

            $term_id = intval($_POST['term_id']);
            $type = sanitize_text_field($_POST['taxonomy_type']);

            $result = wp_delete_term($term_id, $type);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            wp_send_json_success(['message' => 'Taxonomy deleted successfully!']);
        }


    }

    new MPCRBM_Taxonomies();

}