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
                        <button class="mpcrbm_taxonomies_add_btn">+ Add New</button>
                        <input type="text" class="mpcrbm_taxonomies_search" placeholder="Search taxonomy...">
                    </div>
                <?php }else{?>
                <?php }?>
                <?php
                if ( !empty( $terms ) && $type !== 'mpcrbm_car_list' ) {
                    echo '<div class="mpcrbm_taxonomies_list">';
                    foreach ($terms as $term) {
                        ?>
                        <div class="mpcrbm_taxonomy_item" data-term-id="<?php echo esc_attr($term->term_id); ?>" data-type="<?php echo esc_attr($type); ?>">
                            <div class="mpcrbm_taxonomy_content">
                                <strong><?php echo esc_html($term->name); ?></strong><br>
                                <small><?php echo esc_html($term->description); ?></small>
                            </div>

                            <div class="mpcrbm_taxonomy_actions">
                                <button class="button button-small mpcrbm_edit_taxonomy">Edit</button>
                                <button class="button button-small button-danger mpcrbm_delete_taxonomy">Delete</button>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }else if ( empty( $terms ) && $type === 'mpcrbm_car_list'){
                    include( MPCRBM_Function::template_path( 'car_list/car_lists.php' ) );
                }
                else {
                    echo '<p>No taxonomies found for ' . esc_html($type) . '</p>';
                }
                ?>
            </div>
            <?php

            wp_send_json_success(['html' => ob_get_clean()]);
        }

        public function ajax_save_taxonomy() {
//            check_ajax_referer( 'mpcrbm_taxonomy_nonce', 'security' );

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
                        <button class="mpcrbm_car_list_tab mpcrbm_taxonomies_tab active" data-target="mpcrbm_car_list">🚗 Car List</button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_car_type">📋 Car Type</button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_fuel_type">⛽ Fuel Type</button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_seating_capacity">💺 Seating Capacity</button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_car_brand">🏷️ Car Brand</button>
                        <button class="mpcrbm_taxonomies_tab" data-target="mpcrbm_make_year">📅 Make Year</button>
                    </div>
                </div>
                <div class="mpcrbm_left_main_content">

                    <div class="mpcrbm_analytics">
                        <div class="mpcrbm_stat-card total">
                            <div class="mpcrbm_stat-left">
                                <div class="mpcrbm_stat-label">Total Cars</div>
                                <div class="mpcrbm_stat-value">7</div>
                            </div>
                            <div class="mpcrbm_stat-change positive">↑ 2 new this month</div>
                        </div>

                        <div class="mpcrbm_stat-card available">
                            <div class="mpcrbm_stat-left">
                                <div class="mpcrbm_stat-label">Available</div>
                                <div class="mpcrbm_stat-value">7</div>
                            </div>
                            <div class="mpcrbm_stat-change positive">100% availability</div>
                        </div>

                        <div class="mpcrbm_stat-card rented">
                            <div class="mpcrbm_stat-left">
                                <div class="mpcrbm_stat-label">Currently Rented</div>
                                <div class="mpcrbm_stat-value">0</div>
                            </div>
                            <div class="mpcrbm_stat-change">Ready to rent</div>
                        </div>

                        <div class="mpcrbm_stat-card revenue">
                            <div class="mpcrbm_stat-left">
                                <div class="mpcrbm_stat-label">Daily Revenue</div>
                                <div class="mpcrbm_stat-value">$70</div>
                            </div>
                            <div class="mpcrbm_stat-change positive">↑ $10/day avg</div>
                        </div>
                    </div>

                    <div class="mpcrbm_taxonomies_content">
                        <div id="mpcrbm_taxonomies_holder"></div>
                    </div>
                </div>

                <!-- Popup Form -->
                <div class="mpcrbm_taxonomies_popup_overlay">
                    <div class="mpcrbm_taxonomies_popup">
                        <h3>Add New Taxonomy</h3>
                        <label>Name:</label>
                        <input type="text" id="mpcrbm_taxonomies_name" placeholder="Enter name">
                        <label>Slug:</label>
                        <input type="text" id="mpcrbm_taxonomies_slug" placeholder="Optional slug">
                        <label>Description:</label>
                        <textarea id="mpcrbm_taxonomies_desc" placeholder="Short description"></textarea>

                        <div class="mpcrbm_taxonomies_popup_actions">
                            <button class="mpcrbm_taxonomies_save_btn">Save</button>
                            <button class="mpcrbm_taxonomies_cancel_btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php
        }


        public function ajax_update_taxonomy() {
//            check_ajax_referer('mpcrbm_admin_nonce', 'security');

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
//            check_ajax_referer('mpcrbm_admin_nonce', 'security');

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