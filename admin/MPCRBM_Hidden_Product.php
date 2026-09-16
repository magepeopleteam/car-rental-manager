<?php
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.

	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if (!class_exists('MPCRBM_Hidden_Product')) {
		class MPCRBM_Hidden_Product {
			public function __construct() {
				// Every hook below mirrors a car into a hidden WooCommerce `product` post.
				// With WooCommerce inactive (Custom Payment booking mode) those products
				// are meaningless and would only litter the DB with orphan posts, so the
				// mirror stands down entirely. MPCRBM_Woo_Installer's post-activation
				// backfill re-links any car created while WooCommerce was off.
				if ( ! MPCRBM_Function::is_wc_active() ) {
					return;
				}
				add_action('wp_insert_post', array($this, 'create_hidden_wc_product_on_publish'), 10, 3);
				add_action('save_post', array($this, 'run_link_product_on_save'), 99, 1);
				add_action('parse_query', array($this, 'hide_wc_hidden_product_from_product_list'));
				add_action('wp', array($this, 'hide_hidden_wc_product_from_frontend'));
				add_action('before_delete_post', array($this, 'delete_hidden_wc_product'));
				add_action('wp_trash_post', array($this, 'trash_hidden_wc_product'));
			}
			public function create_hidden_wc_product_on_publish($post_id, $post) {
				if ($post->post_type == MPCRBM_Function::get_cpt() && $post->post_status == 'publish' && empty(MPCRBM_Global_Function::get_post_info($post_id, 'check_if_run_once'))) {
					$new_post = array(
						'post_title' => $post->post_title,
						'post_content' => '',
						'post_name' => uniqid(),
						'post_category' => array(),  // Usable for custom taxonomies too
						'tags_input' => array(),
						'post_status' => 'publish', // Choose: publish, preview, future, draft, etc.
						'post_type' => 'product'  //'post',page' or use a custom post type if you want to
					);
					$pid = wp_insert_post($new_post);
					$product_type = 'yes';
					update_post_meta($post_id, 'link_wc_product', $pid);
					update_post_meta($pid, 'link_mpcrbm_id', $post_id);
					update_post_meta($pid, '_price', 0.01);
					update_post_meta($pid, '_sold_individually', 'yes');
					update_post_meta($pid, '_virtual', $product_type);
					$terms = array('exclude-from-catalog', 'exclude-from-search');
					wp_set_object_terms($pid, $terms, 'product_visibility');
					update_post_meta($post_id, 'check_if_run_once', true);
				}
			}
			public function run_link_product_on_save($post_id) {
				if (!isset($_POST['tax_settings_nonce']) || 
					!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tax_settings_nonce'])), 'save_tax_settings')) {
					return; // Stop execution if nonce is invalid
				}
				if (get_post_type($post_id) == MPCRBM_Function::get_cpt()) {
					$title = get_the_title($post_id);
					if ($this->count_hidden_wc_product($post_id) == 0 || empty(MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product'))) {
						$this->create_hidden_wc_product($post_id, $title);
					}
					$product_id = MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product', $post_id);
					set_post_thumbnail($product_id, get_post_thumbnail_id($post_id));
					wp_publish_post($product_id);
					$product_type = 'yes';
					$_tax_status = isset($_POST['_tax_status']) ? sanitize_text_field(wp_unslash($_POST['_tax_status'])) : 'none';
					$_tax_class = isset($_POST['_tax_class']) ? sanitize_text_field(wp_unslash($_POST['_tax_class'])) : '';
					update_post_meta($product_id, '_tax_status', $_tax_status);
					update_post_meta($product_id, '_tax_class', $_tax_class);
					update_post_meta($product_id, '_stock_status', 'instock');
					update_post_meta($product_id, '_manage_stock', 'no');
					update_post_meta($product_id, '_virtual', $product_type);
					update_post_meta($product_id, '_sold_individually', 'yes');
					$my_post = array(
						'ID' => $product_id,
						'post_title' => $title,
						'post_name' => uniqid()
					);
					remove_action('save_post', 'run_link_product_on_save');
					wp_update_post($my_post);
					add_action('save_post', 'run_link_product_on_save');
				}
			}
			public function hide_wc_hidden_product_from_product_list($query) {
				global $pagenow;
				$q_vars = &$query->query_vars;
				if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == 'product') {
					$tax_query = array(
						[
							'taxonomy' => 'product_visibility',
							'field' => 'slug',
							'terms' => 'exclude-from-catalog',
							'operator' => 'NOT IN',
						]
					);
					$query->set('tax_query', $tax_query);
				}
			}
			public function hide_hidden_wc_product_from_frontend() {
				global $post, $wp_query;
				if (is_product()) {
					$post_id = $post->ID;
					$visibility = get_the_terms($post_id, 'product_visibility');
					if (is_object($visibility)) {
						if ($visibility[0]->name == 'exclude-from-catalog') {
							$check_event_hidden = MPCRBM_Global_Function::get_post_info($post_id, 'link_mpcrbm_id', 0);
							if ($check_event_hidden > 0) {
								$wp_query->set_404();
								status_header(404);
								get_template_part(404);
								exit();
							}
						}
					}
				}
			}
			/**********************/
			public function create_hidden_wc_product($post_id, $title) {
				$new_post = array(
					'post_title' => $title,
					'post_content' => '',
					'post_name' => uniqid(),
					'post_category' => array(),
					'tags_input' => array(),
					'post_status' => 'publish',
					'post_type' => 'product'
				);
				$pid = wp_insert_post($new_post);
				update_post_meta($post_id, 'link_wc_product', $pid);
				update_post_meta($pid, 'link_mpcrbm_id', $post_id);
				update_post_meta($pid, '_price', 0.01);
				update_post_meta($pid, '_sold_individually', 'yes');
				update_post_meta($pid, '_virtual', 'yes');
				$terms = array('exclude-from-catalog', 'exclude-from-search');
				wp_set_object_terms($pid, $terms, 'product_visibility');
				update_post_meta($post_id, 'check_if_run_once', true);
			}
			public function delete_hidden_wc_product($post_id) {
				$post = get_post($post_id);
				if ($post && $post->post_type == MPCRBM_Function::get_cpt()) {
					$product_id = MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product', 0);
					if ($product_id > 0) {
						wp_delete_post($product_id, true);
					}
				}
			}
			public function trash_hidden_wc_product($post_id) {
				$post = get_post($post_id);
				if ($post && $post->post_type == MPCRBM_Function::get_cpt()) {
					$product_id = MPCRBM_Global_Function::get_post_info($post_id, 'link_wc_product', 0);
					if ($product_id > 0) {
						wp_trash_post($product_id);
					}
				}
			}
			/**
			 * Make sure ONE car has a usable hidden WooCommerce product, creating or
			 * re-linking it if needed. Returns the product id, or 0 when it can't.
			 *
			 * Cars published while WooCommerce was inactive (Custom Payment booking mode)
			 * never got a mirror product, because the whole mirror stands down in that
			 * mode — see __construct(). The moment the site switches to WooCommerce mode
			 * those cars would fail "Book Now" with a cart error, so the add-to-cart path
			 * self-heals through this method instead of dying.
			 *
			 * A stale link (product trashed/deleted by hand) is treated the same as a
			 * missing one, since both leave add_to_cart() with nothing to add.
			 */
			public static function ensure_hidden_product( $post_id ) {
				$post_id = absint( $post_id );
				if ( ! $post_id || ! MPCRBM_Function::is_wc_active() ) {
					return 0;
				}
				if ( get_post_type( $post_id ) !== MPCRBM_Function::get_cpt() ) {
					return 0;
				}

				$product_id = absint( MPCRBM_Global_Function::get_post_info( $post_id, 'link_wc_product', 0 ) );
				if ( $product_id && 'product' === get_post_type( $product_id ) && 'publish' === get_post_status( $product_id ) ) {
					return $product_id;
				}

				$self = new self();
				$self->create_hidden_wc_product( $post_id, get_the_title( $post_id ) );

				return absint( MPCRBM_Global_Function::get_post_info( $post_id, 'link_wc_product', 0 ) );
			}

			/**
			 * Bulk counterpart of ensure_hidden_product() — repairs every published car.
			 * Run once right after WooCommerce is activated so a fleet built up in Custom
			 * Payment mode becomes bookable through the cart without touching each car.
			 *
			 * @return int Number of cars that were repaired.
			 */
			public static function repair_all_hidden_products(): int {
				if ( ! MPCRBM_Function::is_wc_active() ) {
					return 0;
				}
				$car_ids = get_posts( array(
					'post_type'        => MPCRBM_Function::get_cpt(),
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'fields'           => 'ids',
					'suppress_filters' => true,
				) );

				$repaired = 0;
				foreach ( $car_ids as $car_id ) {
					$existing = absint( MPCRBM_Global_Function::get_post_info( $car_id, 'link_wc_product', 0 ) );
					if ( $existing && 'product' === get_post_type( $existing ) && 'publish' === get_post_status( $existing ) ) {
						continue;
					}
					if ( self::ensure_hidden_product( $car_id ) ) {
						$repaired++;
					}
				}

				return $repaired;
			}

			public function count_hidden_wc_product( $post_id ): int {
				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => -1,
					'fields'         => 'ids', // Performance: Only fetch IDs, not full objects
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
					'meta_query'     => array(
						array(
							'key'     => 'link_mpcrbm_id',
							'value'   => $post_id,
							'compare' => '=',
						),
					),
				);
				
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$product_ids = get_posts( $args );
				
				return count( $product_ids );
			}
		}
		new MPCRBM_Hidden_Product();
	}