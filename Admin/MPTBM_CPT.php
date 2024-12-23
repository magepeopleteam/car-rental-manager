<?php
/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_CPT')) {
	class MPTBM_CPT
	{
		public function __construct()
		{
			add_action('init', [$this, 'add_cpt']);
			add_filter('manage_mptbm_rent_posts_columns', array($this, 'mptbm_rent_columns'));
			add_action('manage_mptbm_rent_posts_custom_column', array($this, 'mptbm_rent_custom_column'), 10, 2);
			add_filter('manage_edit-mptbm_rent_sortable_columns', array($this, 'mptbm_rent_sortable_columns'));
		}

		public function mptbm_rent_custom_column($columns,$post_id){
			switch($columns){
				case 'mptbm_price_based':
					$mptbm_price_based = esc_html__(get_post_meta($post_id,'mptbm_price_based',true));
				
					$item_price_based = [
						'inclusive' => 'Inclusive',
						'distance' => 'Distance as google map',
						'duration' => 'Duration/Time as google map',
						'distance_duration' => 'Distance + Duration as google map',
						'manual' => 'Manual as fixed Location',
						'fixed_hourly' => 'Fixed Hourly',
					];
					foreach($item_price_based as $kay => $value):
						echo esc_html(($kay==$mptbm_price_based)?$value:'');
					endforeach;
				break;
				case 'mptbm_km_price':
					$mptbm_km_price = get_post_meta($post_id,'mptbm_km_price',true);
					echo esc_html($mptbm_km_price?$mptbm_km_price:'');
				break;
				case 'mptbm_hour_price':
					$mptbm_hour_price = get_post_meta($post_id,'mptbm_hour_price',true);
					echo esc_html($mptbm_hour_price?$mptbm_hour_price:'');
				break;
				case 'mptbm_waiting_price':
					$mptbm_waiting_price = get_post_meta($post_id,'mptbm_waiting_price',true);
					echo esc_html($mptbm_waiting_price?$mptbm_waiting_price:'');
				break;
			}
		}

		public function mptbm_rent_columns($columns)
		{
			unset($columns['date']);
			$columns['mptbm_price_based'] = esc_html__('Price based', 'booking-and-rental-manager-for-woocommerce');
			$columns['mptbm_km_price']      =  esc_html__('Kilometer price', 'booking-and-rental-manager-for-woocommerce');
			$columns['mptbm_hour_price']      =  esc_html__('Hourly price', 'booking-and-rental-manager-for-woocommerce');
			$columns['mptbm_waiting_price']      =  esc_html__('Waiting price', 'booking-and-rental-manager-for-woocommerce');
			$columns['author']      =  esc_html__('Author', 'booking-and-rental-manager-for-woocommerce');
			$columns['date']        = esc_html__('Date', 'booking-and-rental-manager-for-woocommerce');
			return $columns;
		}

		

		public function mptbm_rent_sortable_columns($columns)
		{
			$columns['mptbm_price_based'] = 'mptbm_price_based';
			$columns['mptbm_km_price'] = 'mptbm_km_price';
			$columns['mptbm_hour_price'] = 'mptbm_hour_price';
			$columns['mptbm_waiting_price'] = 'mptbm_waiting_price';
			$columns['author'] = 'author';
			return $columns;
		}


		public function add_cpt(): void
		{
			$cpt = MPTBM_Function::get_cpt();
			$label = MPTBM_Function::get_name();
			$slug = MPTBM_Function::get_slug();
			$icon = MPTBM_Function::get_icon();
			$labels = [
				'name' => $label,
				'singular_name' => $label,
				'menu_name' => $label,
				'name_admin_bar' => $label,
				'archives' => $label . ' ' . esc_html__(' List', 'wpcarrently-car-rental-manager'),
				'attributes' => $label . ' ' . esc_html__(' List', 'wpcarrently-car-rental-manager'),
				'parent_item_colon' => $label . ' ' . esc_html__(' Item:', 'wpcarrently-car-rental-manager'),
				'all_items' => esc_html__('All ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'add_new_item' => esc_html__('Add New ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'add_new' => esc_html__('Add New ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'new_item' => esc_html__('New ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'edit_item' => esc_html__('Edit ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'update_item' => esc_html__('Update ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'view_item' => esc_html__('View ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'view_items' => esc_html__('View ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'search_items' => esc_html__('Search ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'not_found' => $label . ' ' . esc_html__(' Not found', 'wpcarrently-car-rental-manager'),
				'not_found_in_trash' => $label . ' ' . esc_html__(' Not found in Trash', 'wpcarrently-car-rental-manager'),
				'featured_image' => $label . ' ' . esc_html__(' Feature Image', 'wpcarrently-car-rental-manager'),
				'set_featured_image' => esc_html__('Set ', 'wpcarrently-car-rental-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently-car-rental-manager'),
				'remove_featured_image' => esc_html__('Remove ', 'wpcarrently-car-rental-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently-car-rental-manager'),
				'use_featured_image' => esc_html__('Use as featured image', 'wpcarrently-car-rental-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently-car-rental-manager'),
				'insert_into_item' => esc_html__('Insert into ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'uploaded_to_this_item' => esc_html__('Uploaded to this ', 'wpcarrently-car-rental-manager') . ' ' . $label,
				'items_list' => $label . ' ' . esc_html__(' list', 'wpcarrently-car-rental-manager'),
				'items_list_navigation' => $label . ' ' . esc_html__(' list navigation', 'wpcarrently-car-rental-manager'),
				'filter_items_list' => esc_html__('Filter ', 'wpcarrently-car-rental-manager') . ' ' . $label . ' ' . esc_html__(' list', 'wpcarrently-car-rental-manager')
			];
			$args = [
				'public' => false,
				'labels' => $labels,
				'menu_icon' => $icon,
				'supports' => ['title', 'thumbnail'],
				'show_in_rest' => true,
				'capability_type' => 'post',
				'publicly_queryable' => true,  // you should be able to query it
				'show_ui' => true,  // you should be able to edit it in wp-admin
				'exclude_from_search' => true,  // you should exclude it from search results
				'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
				'has_archive' => false,  // it shouldn't have archive page
				'rewrite' => ['slug' => $slug],
			];
			register_post_type($cpt, $args);
			$ex_args = array(
				'public' => false,
				'label' => esc_html__('Extra Services', 'wpcarrently-car-rental-manager'),
				'supports' => array('title'),
				'show_in_menu' => 'edit.php?post_type=' . $cpt,
				'capability_type' => 'post',
				'publicly_queryable' => true,  // you should be able to query it
				'show_ui' => true,  // you should be able to edit it in wp-admin
				'exclude_from_search' => true,  // you should exclude it from search results
				'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
				'has_archive' => false,  // it shouldn't have archive page
				'rewrite' => false,
			);

			$dx_args = array(
				'public' => false,
				'label' => esc_html__('Operation Areas', 'wpcarrently-car-rental-manager'),
				'supports' => array('title'),
				'show_in_menu' => 'edit.php?post_type=' . $cpt,
				'capability_type' => 'post',
				'publicly_queryable' => true,  // you should be able to query it
				'show_ui' => true,  // you should be able to edit it in wp-admin
				'exclude_from_search' => true,  // you should exclude it from search results
				'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
				'has_archive' => false,  // it shouldn't have archive page
				'rewrite' => false,
			);

			$taxonomy_labels = array(
				'name' => esc_html__('Locations', 'wpcarrently-car-rental-manager'),
				'singular_name' => esc_html__('Location', 'wpcarrently-car-rental-manager'),
				'menu_name' => esc_html__('Locations', 'wpcarrently-car-rental-manager'),
				'all_items' => esc_html__('All Locations', 'wpcarrently-car-rental-manager'),
				'edit_item' => esc_html__('Edit Location', 'wpcarrently-car-rental-manager'),
				'view_item' => esc_html__('View Location', 'wpcarrently-car-rental-manager'),
				'update_item' => esc_html__('Update Location', 'wpcarrently-car-rental-manager'),
				'add_new_item' => esc_html__('Add New Location', 'wpcarrently-car-rental-manager'),
				'new_item_name' => esc_html__('New Location Name', 'wpcarrently-car-rental-manager'),
				'search_items' => esc_html__('Search Locations', 'wpcarrently-car-rental-manager'),
			);

			$taxonomy_args = array(
				'hierarchical' => false,
				'labels' => $taxonomy_labels,
				'show_ui' => true,
				'show_in_rest' => true,
				'query_var' => true,
				'rewrite' => array('slug' => 'locations'),  // Adjust the slug as needed
				'meta_box_cb' => false,
			);

			
			register_taxonomy('locations', $cpt, $taxonomy_args);
			register_post_type('mptbm_extra_services', $ex_args);
			if (class_exists('MPTBM_Plugin_Pro')) {
				register_post_type('mptbm_operate_areas', $dx_args);
			}
		}
	}
	new MPTBM_CPT();
}
