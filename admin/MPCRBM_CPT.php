<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_CPT' ) ) {
		class MPCRBM_CPT {
			public function __construct() {
				add_action( 'init', [ $this, 'cpt' ] );
				add_filter( 'manage_mpcrbm_rent_posts_columns', array( $this, 'rent_columns' ) );
				add_action( 'manage_mpcrbm_rent_posts_custom_column', array( $this, 'rent_custom_column' ), 10, 2 );
				add_filter( 'manage_edit-mpcrbm_rent_sortable_columns', array( $this, 'rent_sortable_columns' ) );

                add_action('save_post', array( $this, 'pa_save_car_taxonomies_names_as_meta'), 10, 3);
			}

            public function pa_save_car_taxonomies_names_as_meta( $post_id, $post, $update ) {
                $cpt = MPCRBM_Function::get_cpt();

                if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
                if ( wp_is_post_revision( $post_id ) ) return;
                if ( $post->post_type !== $cpt ) return;

                $taxonomies = array(
                    'mpcrbm_car_type',
                    'mpcrbm_fuel_type',
                    'mpcrbm_seating_capacity',
                    'mpcrbm_car_brand',
                    'mpcrbm_make_year'
                );

                foreach ($taxonomies as $taxonomy) {

                    $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));

                    if (!empty($terms) && !is_wp_error($terms)) {

                        $tax_obj = get_taxonomy($taxonomy);
                        if ($tax_obj && $tax_obj->hierarchical) {
                            $term_names = array();
                            foreach ($terms as $term_name) {
                                $term_obj = get_term_by('name', $term_name, $taxonomy);
                                if ($term_obj) {
                                    $term_names[] = $term_obj->name; // শুধু নাম
                                }
                            }
                            update_post_meta($post_id, $taxonomy, $term_names);
                        } else {
                            update_post_meta($post_id, $taxonomy, $terms);
                        }

                    } else {
                        delete_post_meta($post_id, $taxonomy);
                    }
                }
            }


            public function rent_custom_column( $columns, $post_id ) {
				switch ( $columns ) {
					case 'mpcrbm_km_price':
						$mpcrbm_km_price = get_post_meta( $post_id, 'mpcrbm_km_price', true );
						echo esc_html( $mpcrbm_km_price ? $mpcrbm_km_price : '' );
						break;
					case 'mpcrbm_hour_price':
						$mpcrbm_hour_price = get_post_meta( $post_id, 'mpcrbm_hour_price', true );
						echo esc_html( $mpcrbm_hour_price ? $mpcrbm_hour_price : '' );
						break;
					case 'mpcrbm_waiting_price':
						$mpcrbm_waiting_price = get_post_meta( $post_id, 'mpcrbm_waiting_price', true );
						echo esc_html( $mpcrbm_waiting_price ? $mpcrbm_waiting_price : '' );
						break;
					case 'mpcrbm_day_price':
						$mpcrbm_day_price = get_post_meta( $post_id, 'mpcrbm_day_price', true );
						echo esc_html( $mpcrbm_day_price ? $mpcrbm_day_price : '' );
				}
			}

			public function rent_columns( $columns ) {
				unset( $columns['date'] );
				$columns['author']           = esc_html__( 'Author', 'car-rental-manager' );
				$columns['date']             = esc_html__( 'Date', 'car-rental-manager' );
				$columns['mpcrbm_day_price'] = esc_html__( 'Day Price', 'car-rental-manager' );

				return $columns;
			}

			public function rent_sortable_columns( $columns ) {
				$columns['mpcrbm_day_price'] = 'mpcrbm_day_price';
				$columns['author']           = 'author';

				return $columns;
			}

            function pa_register_car_taxonomies( $cpt ) {
                $taxonomies = array(
                    'mpcrbm_car_type' => array('label' => 'Car Type', 'hierarchical' => true),
                    'mpcrbm_fuel_type' => array('label' => 'Fuel Type', 'hierarchical' => true),
                    'mpcrbm_seating_capacity' => array('label' => 'Seating Capacity', 'hierarchical' => true),
                    'mpcrbm_car_brand' => array('label' => 'Car Brand', 'hierarchical' => true),
                    'mpcrbm_make_year' => array('label' => 'Make Year', 'hierarchical' => true),
                    'mpcrbm_car_feature' => array('label' => 'Make Year', 'hierarchical' => true),
                );

                foreach ( $taxonomies as $slug => $props ) {

                    $labels = array(
                        'name'          => _x( $props['label'] . 's', 'taxonomy general name', 'pa-car' ),
                        'singular_name' => _x( $props['label'], 'taxonomy singular name', 'pa-car' ),
                        'search_items'  => __( 'Search ' . $props['label'] . 's', 'pa-car' ),
                        'all_items'     => __( 'All ' . $props['label'] . 's', 'pa-car' ),
                        'edit_item'     => __( 'Edit ' . $props['label'], 'pa-car' ),
                        'add_new_item'  => __( 'Add New ' . $props['label'], 'pa-car' ),
                        'menu_name'     => __( $props['label'] . 's', 'pa-car' ),
                    );

                    $args = array(
                        'hierarchical'      => $props['hierarchical'],
                        'labels'            => $labels,
                        'show_ui'           => true,             // show meta box on post edit screen
                        'show_admin_column' => true,             // show column in CPT list table
                        'query_var'         => true,
                        'show_in_rest'      => true,             // enable Gutenberg / REST API
                        'rewrite'           => array( 'slug' => $slug ),
                        'show_in_menu'      => false,            // ❌ hide from admin menu (left side)
                    );

                    // register taxonomy for your CPT, e.g., 'car'
                    register_taxonomy( $slug, $cpt, $args );
                }


            }

			public function cpt(): void {
				$cpt    = MPCRBM_Function::get_cpt();
				$label  = MPCRBM_Function::get_name();
				$slug   = MPCRBM_Function::get_slug();
				$icon   = MPCRBM_Function::get_icon();
				$labels = [
					'name'                  => $label,
					'singular_name'         => $label,
					'menu_name'             => $label,
					'name_admin_bar'        => $label,
					'archives'              => $label . ' ' . esc_html__( ' List', 'car-rental-manager' ),
					'attributes'            => $label . ' ' . esc_html__( ' List', 'car-rental-manager' ),
					'parent_item_colon'     => $label . ' ' . esc_html__( ' Item:', 'car-rental-manager' ),
					'all_items'             => esc_html__( 'All ', 'car-rental-manager' ) . ' ' . $label,
					'add_new_item'          => esc_html__( 'Add New ', 'car-rental-manager' ) . ' ' . $label,
					'add_new'               => esc_html__( 'Add New ', 'car-rental-manager' ) . ' ' . $label,
					'new_item'              => esc_html__( 'New ', 'car-rental-manager' ) . ' ' . $label,
					'edit_item'             => esc_html__( 'Edit ', 'car-rental-manager' ) . ' ' . $label,
					'update_item'           => esc_html__( 'Update ', 'car-rental-manager' ) . ' ' . $label,
					'view_item'             => esc_html__( 'View ', 'car-rental-manager' ) . ' ' . $label,
					'view_items'            => esc_html__( 'View ', 'car-rental-manager' ) . ' ' . $label,
					'search_items'          => esc_html__( 'Search ', 'car-rental-manager' ) . ' ' . $label,
					'not_found'             => $label . ' ' . esc_html__( ' Not found', 'car-rental-manager' ),
					'not_found_in_trash'    => $label . ' ' . esc_html__( ' Not found in Trash', 'car-rental-manager' ),
					'featured_image'        => $label . ' ' . esc_html__( ' Feature Image', 'car-rental-manager' ),
					'set_featured_image'    => esc_html__( 'Set ', 'car-rental-manager' ) . ' ' . $label . ' ' . esc_html__( ' featured image', 'car-rental-manager' ),
					'remove_featured_image' => esc_html__( 'Remove ', 'car-rental-manager' ) . ' ' . $label . ' ' . esc_html__( ' featured image', 'car-rental-manager' ),
					'use_featured_image'    => esc_html__( 'Use as featured image', 'car-rental-manager' ) . ' ' . $label . ' ' . esc_html__( ' featured image', 'car-rental-manager' ),
					'insert_into_item'      => esc_html__( 'Insert into ', 'car-rental-manager' ) . ' ' . $label,
					'uploaded_to_this_item' => esc_html__( 'Uploaded to this ', 'car-rental-manager' ) . ' ' . $label,
					'items_list'            => $label . ' ' . esc_html__( ' list', 'car-rental-manager' ),
					'items_list_navigation' => $label . ' ' . esc_html__( ' list navigation', 'car-rental-manager' ),
					'filter_items_list'     => esc_html__( 'Filter ', 'car-rental-manager' ) . ' ' . $label . ' ' . esc_html__( ' list', 'car-rental-manager' )
				];
				$args   = [
					'public'              => false,
					'labels'              => $labels,
					'menu_icon'           => $icon,
					'supports'            => [ 'title', 'thumbnail' ],
					'show_in_rest'        => true,
					'capability_type'     => 'post',
					'publicly_queryable'  => true,  // you should be able to query it
					'show_ui'             => true,  // you should be able to edit it in wp-admin
					'exclude_from_search' => true,  // you should exclude it from search results
					'show_in_nav_menus'   => false,  // you shouldn't be able to add it to menus
					'has_archive'         => false,  // it shouldn't have archive page
					'rewrite'             => [ 'slug' => $slug ],
				];
				register_post_type( $cpt, $args );
				$ex_args = array(
					'public'              => false,
					'label'               => esc_html__( 'Extra Services', 'car-rental-manager' ),
					'supports'            => array( 'title' ),
					'show_in_menu'        => 'edit.php?post_type=' . $cpt,
					'capability_type'     => 'post',
					'publicly_queryable'  => true,  // you should be able to query it
					'show_ui'             => true,  // you should be able to edit it in wp-admin
					'exclude_from_search' => true,  // you should exclude it from search results
					'show_in_nav_menus'   => false,  // you shouldn't be able to add it to menus
					'has_archive'         => false,  // it shouldn't have archive page
					'rewrite'             => false,
				);
				$dx_args = array(
					'public'              => false,
					'label'               => esc_html__( 'Operation Areas', 'car-rental-manager' ),
					'supports'            => array( 'title' ),
					'show_in_menu'        => 'edit.php?post_type=' . $cpt,
					'capability_type'     => 'post',
					'publicly_queryable'  => true,  // you should be able to query it
					'show_ui'             => true,  // you should be able to edit it in wp-admin
					'exclude_from_search' => true,  // you should exclude it from search results
					'show_in_nav_menus'   => false,  // you shouldn't be able to add it to menus
					'has_archive'         => false,  // it shouldn't have archive page
					'rewrite'             => false,
				);
				$taxonomy_labels = array(
					'name'          => esc_html__( 'Locations', 'car-rental-manager' ),
					'singular_name' => esc_html__( 'Location', 'car-rental-manager' ),
					'menu_name'     => esc_html__( 'Locations', 'car-rental-manager' ),
					'all_items'     => esc_html__( 'All Locations', 'car-rental-manager' ),
					'edit_item'     => esc_html__( 'Edit Location', 'car-rental-manager' ),
					'view_item'     => esc_html__( 'View Location', 'car-rental-manager' ),
					'update_item'   => esc_html__( 'Update Location', 'car-rental-manager' ),
					'add_new_item'  => esc_html__( 'Add New Location', 'car-rental-manager' ),
					'new_item_name' => esc_html__( 'New Location Name', 'car-rental-manager' ),
					'search_items'  => esc_html__( 'Search Locations', 'car-rental-manager' ),
				);
				$taxonomy_args = array(
					'hierarchical' => false,
					'labels'       => $taxonomy_labels,
					'show_ui'      => true,
					'show_in_rest' => true,
					'query_var'    => true,
					'rewrite'      => array( 'slug' => 'mpcrbm_locations' ),  // Updated slug
					'meta_box_cb'  => false,
				);
				register_taxonomy( 'mpcrbm_locations', $cpt, $taxonomy_args );  // Updated taxonomy name
//				register_post_type( 'crm_extra_services', $ex_args );
				register_post_type( 'mpcrbm_ex_services', $ex_args );
				if ( class_exists( 'MPCRBM_Plugin_Pro' ) ) {
					register_post_type( 'mpcrbm_operate_areas', $dx_args );
				}

                $this->pa_register_car_taxonomies( $cpt );


            }
		}
		new MPCRBM_CPT();
	}
