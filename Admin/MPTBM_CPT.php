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
				case 'mptbm_day_price':
					$mptbm_day_price = get_post_meta($post_id, 'mptbm_day_price', true);
					echo esc_html($mptbm_day_price?$mptbm_day_price:'');
			}
		}

		public function mptbm_rent_columns($columns)
		{
			unset($columns['date']);
			$columns['author']      =  esc_html__('Author', 'wpcarrently');
			$columns['date']        = esc_html__('Date', 'wpcarrently');
			$columns['mptbm_day_price'] = esc_html__('Day Price', 'wpcarrently');
			return $columns;
		}

		

		public function mptbm_rent_sortable_columns($columns)
		{
			$columns['mptbm_day_price'] = 'mptbm_day_price';
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
				'archives' => $label . ' ' . esc_html__(' List', 'wpcarrently'),
				'attributes' => $label . ' ' . esc_html__(' List', 'wpcarrently'),
				'parent_item_colon' => $label . ' ' . esc_html__(' Item:', 'wpcarrently'),
				'all_items' => esc_html__('All ', 'wpcarrently') . ' ' . $label,
				'add_new_item' => esc_html__('Add New ', 'wpcarrently') . ' ' . $label,
				'add_new' => esc_html__('Add New ', 'wpcarrently') . ' ' . $label,
				'new_item' => esc_html__('New ', 'wpcarrently') . ' ' . $label,
				'edit_item' => esc_html__('Edit ', 'wpcarrently') . ' ' . $label,
				'update_item' => esc_html__('Update ', 'wpcarrently') . ' ' . $label,
				'view_item' => esc_html__('View ', 'wpcarrently') . ' ' . $label,
				'view_items' => esc_html__('View ', 'wpcarrently') . ' ' . $label,
				'search_items' => esc_html__('Search ', 'wpcarrently') . ' ' . $label,
				'not_found' => $label . ' ' . esc_html__(' Not found', 'wpcarrently'),
				'not_found_in_trash' => $label . ' ' . esc_html__(' Not found in Trash', 'wpcarrently'),
				'featured_image' => $label . ' ' . esc_html__(' Feature Image', 'wpcarrently'),
				'set_featured_image' => esc_html__('Set ', 'wpcarrently') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently'),
				'remove_featured_image' => esc_html__('Remove ', 'wpcarrently') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently'),
				'use_featured_image' => esc_html__('Use as featured image', 'wpcarrently') . ' ' . $label . ' ' . esc_html__(' featured image', 'wpcarrently'),
				'insert_into_item' => esc_html__('Insert into ', 'wpcarrently') . ' ' . $label,
				'uploaded_to_this_item' => esc_html__('Uploaded to this ', 'wpcarrently') . ' ' . $label,
				'items_list' => $label . ' ' . esc_html__(' list', 'wpcarrently'),
				'items_list_navigation' => $label . ' ' . esc_html__(' list navigation', 'wpcarrently'),
				'filter_items_list' => esc_html__('Filter ', 'wpcarrently') . ' ' . $label . ' ' . esc_html__(' list', 'wpcarrently')
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
				'label' => esc_html__('Extra Services', 'wpcarrently'),
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
				'label' => esc_html__('Operation Areas', 'wpcarrently'),
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
				'name' => esc_html__('Locations', 'wpcarrently'),
				'singular_name' => esc_html__('Location', 'wpcarrently'),
				'menu_name' => esc_html__('Locations', 'wpcarrently'),
				'all_items' => esc_html__('All Locations', 'wpcarrently'),
				'edit_item' => esc_html__('Edit Location', 'wpcarrently'),
				'view_item' => esc_html__('View Location', 'wpcarrently'),
				'update_item' => esc_html__('Update Location', 'wpcarrently'),
				'add_new_item' => esc_html__('Add New Location', 'wpcarrently'),
				'new_item_name' => esc_html__('New Location Name', 'wpcarrently'),
				'search_items' => esc_html__('Search Locations', 'wpcarrently'),
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
