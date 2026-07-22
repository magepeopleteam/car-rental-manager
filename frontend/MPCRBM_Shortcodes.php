<?php
	/*
	* @Author 		magePeople
	* Copyright: 	mage-people.com
	*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Shortcodes')) {
		class MPCRBM_Shortcodes {
			public function __construct() {
				add_shortcode('mpcrbm_booking', array($this, 'mpcrbm_booking'));

                add_shortcode( 'mpcrbm_car_list', [ $this, 'mpcrbm_car_list_shortcode'] );

                add_shortcode( 'mpcrbm_my_bookings', [ $this, 'mpcrbm_my_bookings_shortcode' ] );
                add_action( 'wp_ajax_mpcrbm_mb_load',       [ $this, 'mpcrbm_mb_load' ] );
                add_action( 'wp_ajax_mpcrbm_mb_detail',     [ $this, 'mpcrbm_mb_detail' ] );
                add_action( 'wp_ajax_mpcrbm_mb_mod_request', [ $this, 'mpcrbm_mb_mod_request' ] );

			}

            public static function mpcrbm_get_car_data( $atts ) {

                $meta_query = [ 'relation' => 'AND' ];

                if ( ! empty( $atts['car_type'] ) ) {
                    $car_types = (array) $atts['car_type'];
                    foreach ( $car_types as $car_type ) {
                        $meta_query[] = [
                            'key'     => 'mpcrbm_car_type',
                            'value'   => '"' . sanitize_text_field( $car_type ) . '"',
                            'compare' => 'LIKE',
                        ];
                    }
                }

                if ( ! empty( $atts['fuel_type'] ) ) {
                    $fuel_types = (array) $atts['fuel_type'];
                    foreach ( $fuel_types as $fuel_type ) {
                        $meta_query[] = [
                            'key'     => 'mpcrbm_fuel_type',
                            'value'   => '"' . sanitize_text_field( $fuel_type ) . '"',
                            'compare' => 'LIKE',
                        ];
                    }
                }

                if ( ! empty( $atts['brand'] ) ) {
                    $brands = (array) $atts['brand'];
                    foreach ( $brands as $brand ) {
                        $meta_query[] = [
                            'key'     => 'mpcrbm_car_brand',
                            'value'   => '"' . sanitize_text_field( $brand ) . '"',
                            'compare' => 'LIKE',
                        ];
                    }
                }

                $args = [
                    'post_type'      => 'mpcrbm_rent',
//                    'posts_per_page' => intval( $atts['per_page'] ),
                    'posts_per_page' => intval( $atts['show'] ),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => $meta_query,
                ];

                $query = new WP_Query( $args );
                $cars = $post_ids = [];

                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        $car_id                 = get_the_ID();
                        $type                   = get_post_meta( $car_id, 'mpcrbm_car_type', true );
                        $fuel                   = get_post_meta( $car_id, 'mpcrbm_fuel_type', true );
                        $brand                  = get_post_meta( $car_id, 'mpcrbm_car_brand', true );
                        $car_year               = MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_make_year' );
                        $car_seating_capacity = MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_seating_capacity');

                        $all_filters = [
                            $type,
                            $fuel,
                            $car_seating_capacity,
                            $brand,
                            $car_year
                        ];

                        $merged_values = [];
                        foreach ($all_filters as $filter) {
                            if (is_array($filter) && !empty($filter)) {
                                $merged_values = array_merge($merged_values, $filter);
                            }
                        }
                        $final_filter_string = !empty($merged_values) ? implode(', ', $merged_values) : '';


                        $cars[] = [
                            'id'         => $car_id,
                            'title'      => get_the_title(),
                            'content'    => get_the_content(),
                            'image'      => get_the_post_thumbnail_url( $car_id, 'medium' ),
                            'brand'      => isset( $brand[0] ) ? $brand[0] : '',
                            'type'       => isset( $type[0] ) ? $type[0] : '',
                            'fuel'       => isset( $fuel[0] ) ? $fuel[0] : '',
                            'car_year'   => isset( $car_year[0] ) ? $car_year[0] : '',
                            'seating_capacity'   => isset( $car_seating_capacity[0] ) ? $car_seating_capacity[0] : '',
                            'bag'        => get_post_meta( $car_id, 'mpcrbm_maximum_bag', true ),
                            'passenger'  => get_post_meta( $car_id, 'mpcrbm_maximum_passenger', true ),
                            'day_price'  => get_post_meta( $car_id, 'mpcrbm_day_price', true ),
                            'filter_string'  => $final_filter_string,
                        ];

                        $post_ids[] = $car_id;
                    }

                    wp_reset_postdata();
                }

                return array(
                        'cars' => $cars,
                        'car_ids' => $post_ids,
                );
            }


            function mpcrbm_car_list_shortcode( $atts ) {
                $atts = shortcode_atts( [
                    'car_type'      => '',
                    'fuel_type'     => '',
                    'brand'         => '',
                    'per_page'      => 20,
                    'show'          => 20,
                    'column'        => 3,
                    'style'         => 'grid',
                    'mpcrbm_left_filter'   => 'no',
                ], $atts, 'mpcrbm_car_list' );

                $left_side_filter = [];
                $car_data = self::mpcrbm_get_car_data( $atts );
                $cars = $car_data['cars'];
                $car_ids = $car_data['car_ids'];

                if( count( $car_ids ) > 0 ){
                    $left_side_filter = MPCRBM_Global_Function::get_meta_key( $car_ids );
                }

                $column = max( 1, min( 6, intval( $atts['column'] ) ) );
                $left_filter =  sanitize_text_field( $atts['mpcrbm_left_filter'] );

                $car_style = $atts['style'];
                if( $car_style === 'list' ){
                    $list_grid_class = 'mpcrbm_car_list_lists mpcrbm_car_list_list_view';
                    $grid_active = '';
                    $list_active = 'active';
                }else{
                    $list_grid_class = 'mpcrbm_car_list_grid mpcrbm_car_list_grid_view';
                    $grid_active = 'active';
                    $list_active = '';
                }

                ob_start(); ?>

                <div class=" mpcrbm mpcrbm_transport_search_area ">
                    <div class="mpcrbm_car_list_grid_wrapper">
                        <div class="mpcrbm_car_list_grid_toggle">
                            <button class="mpcrbm_car_list_grid_btn <?php echo esc_html( $grid_active );?>" data-view="grid">Grid</button>
                            <button class="mpcrbm_car_list_list_btn <?php echo esc_html( $list_active );?>" data-view="list">List</button>
                        </div>
                        <div class="mpcrbm_car_list_container ">
                            <?php if( $left_filter === 'yes' && count( $left_side_filter ) > 0 ){?>
                                <div class="mpcrbm_left_filter">
                                    <?php do_action( 'mpcrbm_left_side_car_filter', $left_side_filter );?>
                                </div>
                            <?php }?>
                            <div id="mpcrbm_car_list_grid" class="<?php echo esc_html( $list_grid_class )?> mpcrbm_car_list_grid_<?php echo esc_html($column) ;?>">
                                <?php if ( ! empty( $cars ) ) : ?>
                                    <?php foreach ( $cars as $car ) :?>
                                <a href="<?php echo esc_url( get_permalink( $car['id'] ) ); ?>">
                                        <div class="mpcrbm_car_list_grid_item mpcrbm_booking_item "
                                             data-car-type="<?php echo esc_attr( $car['type'])?>"
                                             data-fuel-type="<?php echo esc_attr( $car['fuel'])?>"
                                             data-seating-capacity="<?php echo esc_attr( $car['seating_capacity'] )?>"
                                             data-car-brand="<?php echo esc_attr( $car['brand'] )?>"
                                             data-car-year="<?php echo esc_attr( $car['car_year'])?>"
                                             data-filter-category-items="<?php echo esc_attr( $car['filter_string'])?>"
                                        >
                                            
                                                <div class="mpcrbm_car_list_grid_image">
                                                    <?php if ( $car['image'] ) : ?>
                                                        <img src="<?php echo esc_url( $car['image'] ); ?>" alt="<?php echo esc_attr( $car['title'] ); ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mpcrbm_car_list_grid_content">
                                                    <h3 class="mpcrbm_car_list_grid_title"><?php echo esc_html( $car['title'] ); ?></h3>
                                                    <div class="mpcrbm_car_specs_lists">
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-car"></i>
                                                            <div title="<?php echo esc_html_e('Car Type: ','car-rental-manager').esc_attr($car['type']); ?>">
                                                                <div class="spec-value"><?php echo esc_html($car['type']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-gas-pump-alt"></i>
                                                            <div title="<?php echo esc_html_e('Fuel Type: ','car-rental-manager').esc_attr($car['fuel']); ?>">
                                                                <div class="spec-value"><?php echo esc_html( $car['fuel']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-bonus"></i>
                                                            <div title="<?php echo esc_html_e('Brands: ','car-rental-manager').esc_attr($car['brand']); ?>">
                                                                <div class="spec-value"><?php echo esc_html($car['brand']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-time-quarter-to"></i>
                                                            <div title="<?php echo esc_html_e('Making Year: ','car-rental-manager').esc_attr($car['car_year']); ?>">
                                                                <div class="spec-value"><?php echo esc_html($car['car_year']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-person-seat"></i>
                                                            <div title="<?php echo esc_html_e('Seating Capacity: ','car-rental-manager').esc_attr($car['seating_capacity']); ?>">
                                                                <div class="spec-value"><?php echo esc_html($car['seating_capacity']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-person-luggage"></i>
                                                            <div title="<?php echo esc_html_e('Maximum Bags: ','car-rental-manager').esc_attr($car['bag']); ?>">
                                                                <div class="spec-value"><?php echo esc_html($car['bag']).esc_html_e(' Bags','car-rental-manager'); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mpcrbm_car_list_price_display">
                                                        <div class="mpcrbm_car_list_price">
                                                            <h3>
                                                                <span class="woocommerce-Price-amount amount">
                                                                    <?php echo wp_kses_post( wc_price( $car['day_price'] ) );?>
                                                                </span>
                                                                <small>/ day</small>
                                                            </h3>
                                                        </div>
                                                    </div>
                                                </div>

                                        </div>
                                </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p>No cars found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <?php
                return ob_get_clean();
            }

            function mpcrbm_car_list_shortcode1( $atts ) {

                $atts = shortcode_atts( [
                    'car_type'      => '',
                    'fuel_type'     => '',
                    'brand'         => '',
                    'per_page'      => 2,
                    'column'        => 3,
                ], $atts, 'mpcrbm_car_list' );

                $meta_query = [ 'relation' => 'AND' ];

                if ( ! empty( $atts['car_type'] ) ) {
                    $meta_query[] = [
                        'key'     => 'mpcrbm_car_type',
                        'value'   => sanitize_text_field( $atts['car_type'] ),
                        'compare' => '=',
                    ];
                }

                if ( ! empty( $atts['fuel_type'] ) ) {
                    $meta_query[] = [
                        'key'     => 'mpcrbm_fuel_type',
                        'value'   => sanitize_text_field( $atts['fuel_type'] ),
                        'compare' => '=',
                    ];
                }

                if ( ! empty( $atts['brand'] ) ) {
                    $meta_query[] = [
                        'key'     => 'mpcrbm_car_brand',
                        'value'   => sanitize_text_field( $atts['brand'] ),
                        'compare' => '=',
                    ];
                }

                $args = [
                    'post_type'      => 'mpcrbm_rent',
                    'posts_per_page' => intval( $atts['per_page'] ),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => $meta_query,
                ];

                $query = new WP_Query( $args );
                ob_start();

                $column = $atts['column'];
                if ( $column < 1 ) $column = 1;
                if ( $column > 6 ) $column = 6;
                ?>
                <div class="mpcrbm_car_list_grid_wrapper mpcrbm mpcrbm_transport_search_area">
                    <div class="mpcrbm_car_list_grid_toggle">
                        <button class="mpcrbm_car_list_grid_btn active" data-view="grid">Grid</button>
                        <button class="mpcrbm_car_list_list_btn" data-view="list">List</button>
                    </div>
                    <div id="mpcrbm_car_list_grid" class="mpcrbm_car_list_grid mpcrbm_car_list_grid_view"
                         style="grid-template-columns: repeat(<?php echo esc_attr( $column ); ?>, 1fr);">
                        <?php if ( $query->have_posts() ) : ?>
                            <?php while ( $query->have_posts() ) : $query->the_post();
                                $car_id = get_the_ID();
                                $car_img = get_the_post_thumbnail_url( $car_id, 'medium' );
                                $brand = get_post_meta( $car_id, 'mpcrbm_car_brand', true );
                                $type = get_post_meta( $car_id, 'mpcrbm_car_type', true );
                                $fuel = get_post_meta( $car_id, 'mpcrbm_fuel_type', true );
                                $bag = get_post_meta( $car_id, 'mpcrbm_maximum_bag', true );
                                $passenger = get_post_meta( $car_id, 'mpcrbm_maximum_passenger', true );
                                $day_price = get_post_meta( $car_id, 'mpcrbm_day_price', true );

                                ?>
                                <div class="mpcrbm_car_list_grid_item">
                                    <div class="mpcrbm_car_list_grid_image">
                                        <?php if ( $car_img ) : ?>
                                            <img src="<?php echo esc_url( $car_img ); ?>" alt="<?php the_title_attribute(); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="mpcrbm_car_list_grid_content">
                                        <h3 class="mpcrbm_car_list_grid_title"><?php the_title(); ?></h3>
                                        <ul class="mpcrbm_car_list_grid_meta">
                                            <li><strong>Brand:</strong> <?php echo isset( $brand[0] ) ?  esc_html( $brand[0] ) : ''; ?></li>
                                            <li><strong>Type:</strong> <?php echo isset(  $type[0] ) ? esc_html( $type[0] ) : ''; ?></li>
                                            <li><strong>Fuel:</strong> <?php echo isset( $fuel[0] ) ? esc_html( $fuel[0] ) : ''; ?></li>
                                            <li><strong>Passenger:</strong> <?php echo esc_html( $passenger ); ?></li>
                                            <li><strong>Bag:</strong> <?php echo esc_html( $bag ); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        <?php else : ?>
                            <p>No cars found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }


			public function mpcrbm_booking($attribute) {
				$defaults = self::default_attribute();
				$params = shortcode_atts($defaults, $attribute);
				ob_start();
				do_action('mpcrbm_transport_search', $params);
				return ob_get_clean();
			}

			public function mpcrbm_single_page_car_booking($attribute, $post_id,  $search_date = [] ) {
				$defaults = self::default_attribute();
				$params = shortcode_atts($defaults, $attribute);
				ob_start();
//				do_action('mpcrbm_transport_search', $params);
                $display_map = MPCRBM_Global_Function::get_settings( 'mpcrbm_map_api_settings', 'display_map', 'enable' );
                $price_based = $params['price_based'] ?: 'dynamic';
                $price_based = $display_map == 'disable' ? 'manual' : $price_based;
                $progressbar = $params['progressbar'] ?: 'yes';
                $form_style  = $params['form'] ?: 'horizontal';
                $map         = $params['map'] ?: 'yes';
                $map         = $display_map == 'disable' ? 'no' : $map;

                $is_title    = $params['title'] ?: 'no';
                $ajax_search    = $params['ajax_search'] ?: 'no';
                $search_result_show    = $params['search_result'] ?: 'no';
                $search_result_same_page    = $params['search_result_same_page'] ?: 'no';

//                ob_start();
                do_shortcode( '[shop_messages]' );
                echo wp_kses_post( ob_get_clean() );
                //echo '<pre>';print_r($params);echo '</pre>';
                include( MPCRBM_Function::template_path( 'registration/registration_layout.php' ) );
//				return ob_get_clean();
			}
			public static function default_attribute() {
				return array(
					"cat" => "0",
					"org" => "0",
					"style" => 'list',
					"show" => '9',
					"pagination" => "yes",
					"city" => "",
					"country" => "",
					'sort' => 'ASC',
					'status' => '',
					"pagination-style" => "load_more",
					"column" => 3,
					"price_based" => 'manual',
					'progressbar'=>'yes',
					'map'=>'yes',
					'form'=>'horizontal',
					'title'=>'yes',
					'ajax_search' => 'no',
					'single_page' => 'no',
					'pickup_location' => '',
					'search_result' => 'no',
					'search_result_same_page' => 'no',
				);
			}

            public function mpcrbm_my_bookings_shortcode( $atts ) {
                if ( ! is_user_logged_in() ) {
                    ob_start(); ?>
                    <div class="mpcrbm mpcrbm-mb-wrap">
                        <div class="mpcrbm-mb-login-notice">
                            <i class="mi mi-lock-alt"></i>
                            <p><?php esc_html_e( 'Please log in to view your bookings.', 'car-rental-manager' ); ?></p>
                            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="mpcrbm-mb-login-btn">
                                <?php esc_html_e( 'Login', 'car-rental-manager' ); ?>
                            </a>
                        </div>
                    </div>
                    <?php return ob_get_clean();
                }

                $nonce    = wp_create_nonce( 'mpcrbm_my_bookings' );
                $per_page = 20;
                $user_id  = get_current_user_id();

                $order_ids = wc_get_orders( [
                    'customer_id' => $user_id,
                    'limit'       => -1,
                    'return'      => 'ids',
                ] );

                $has_more    = false;
                $cards_html  = '';

                if ( ! empty( $order_ids ) ) {
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    $meta_q = [ [ 'key' => 'mpcrbm_order_id', 'value' => $order_ids, 'compare' => 'IN' ] ];
                    $query  = new WP_Query( [
                        'post_type'      => 'mpcrbm_booking',
                        'posts_per_page' => $per_page,
                        'paged'          => 1,
                        'post_status'    => 'any',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'no_found_rows'  => false,
                        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        'meta_query'     => $meta_q,
                    ] );
                    $has_more = $query->found_posts > $per_page;
                    if ( $query->have_posts() ) {
                        ob_start();
                        while ( $query->have_posts() ) {
                            $query->the_post();
                            echo wp_kses_post( $this->mpcrbm_mb_render_card( get_post() ) );
                        }
                        wp_reset_postdata();
                        $cards_html = ob_get_clean();
                    }
                }

                ob_start(); ?>
                <div class="mpcrbm mpcrbm-mb-wrap"
                     data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
                     data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <div class="mpcrbm-mb-grid" id="mpcrbm-mb-grid">
                        <?php if ( $cards_html ) : ?>
                            <?php echo $cards_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <?php else : ?>
                            <div class="mpcrbm-mb-empty">
                                <i class="mi mi-car"></i>
                                <p><?php esc_html_e( 'You have no bookings yet.', 'car-rental-manager' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ( $has_more ) : ?>
                    <div class="mpcrbm-mb-loadmore-wrap" id="mpcrbm-mb-loadmore-wrap">
                        <button class="mpcrbm-mb-loadmore" id="mpcrbm-mb-loadmore" data-page="2">
                            <span class="mpcrbm-mb-loadmore-text"><?php esc_html_e( 'Load More', 'car-rental-manager' ); ?></span>
                            <span class="mpcrbm-mb-loadmore-spinner" style="display:none"><div class="mpcrbm-mb-spinner mpcrbm-mb-spinner-sm"></div></span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mpcrbm-mb-modal" id="mpcrbm-mb-modal" aria-hidden="true">
                    <div class="mpcrbm-mb-modal-backdrop" id="mpcrbm-mb-modal-backdrop"></div>
                    <div class="mpcrbm-mb-modal-dialog">
                        <button class="mpcrbm-mb-modal-close" id="mpcrbm-mb-modal-close" aria-label="Close">
                            <i class="mi mi-close"></i>
                        </button>
                        <div class="mpcrbm-mb-modal-body" id="mpcrbm-mb-modal-body">
                            <div class="mpcrbm-mb-loading"><div class="mpcrbm-mb-spinner"></div></div>
                        </div>
                    </div>
                </div>
                <?php return ob_get_clean();
            }

            public function mpcrbm_mb_load() {
                check_ajax_referer( 'mpcrbm_my_bookings', 'nonce' );
                if ( ! is_user_logged_in() ) {
                    wp_send_json_error();
                }

                $per_page  = 20;
                $page      = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
                $user_id   = get_current_user_id();

                $order_ids = wc_get_orders( [
                    'customer_id' => $user_id,
                    'limit'       => -1,
                    'return'      => 'ids',
                ] );

                if ( empty( $order_ids ) ) {
                    wp_send_json_success( [ 'html' => '', 'has_more' => false, 'total' => 0 ] );
                }

                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                $meta_q = [ [ 'key' => 'mpcrbm_order_id', 'value' => $order_ids, 'compare' => 'IN' ] ];

                $query = new WP_Query( [
                    'post_type'      => 'mpcrbm_booking',
                    'posts_per_page' => $per_page,
                    'paged'          => $page,
                    'post_status'    => 'any',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'no_found_rows'  => false,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    'meta_query'     => $meta_q,
                ] );

                $total    = (int) $query->found_posts;
                $has_more = ( $page * $per_page ) < $total;

                ob_start();
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        echo wp_kses_post( $this->mpcrbm_mb_render_card( get_post() ) );
                    }
                    wp_reset_postdata();
                }
                $html = ob_get_clean();

                wp_send_json_success( [ 'html' => $html, 'has_more' => $has_more, 'total' => $total, 'page' => $page ] );
            }

            public function mpcrbm_mb_detail() {
                check_ajax_referer( 'mpcrbm_my_bookings', 'nonce' );
                if ( ! is_user_logged_in() ) {
                    wp_send_json_error();
                }

                $booking_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
                $booking    = $booking_id ? get_post( $booking_id ) : null;

                if ( ! $booking || $booking->post_type !== 'mpcrbm_booking' ) {
                    wp_send_json_error( [ 'message' => __( 'Booking not found.', 'car-rental-manager' ) ] );
                }

                $order_id  = (int) get_post_meta( $booking_id, 'mpcrbm_order_id', true );
                $order_obj = $order_id ? wc_get_order( $order_id ) : null;
                if ( $order_obj && (int) $order_obj->get_customer_id() !== get_current_user_id() ) {
                    wp_send_json_error( [ 'message' => __( 'Access denied.', 'car-rental-manager' ) ] );
                }

                wp_send_json_success( [ 'html' => $this->mpcrbm_mb_render_detail( $booking_id, $order_obj ) ] );
            }

            private function mpcrbm_mb_render_card( $booking ) {
                $id          = $booking->ID;
                $car_id      = get_post_meta( $id, 'mpcrbm_id', true );
                $pickup_dt   = get_post_meta( $id, 'mpcrbm_date', true );
                $return_dt   = get_post_meta( $id, 'return_date_time', true );
                $start_place = get_post_meta( $id, 'mpcrbm_start_place', true );
                $end_place   = get_post_meta( $id, 'mpcrbm_end_place', true );
                $order_id    = get_post_meta( $id, 'mpcrbm_order_id', true );
                $status      = (string) get_post_meta( $id, 'mpcrbm_order_status', true );
                $total       = get_post_meta( $id, 'mpcrbm_tp', true );
                $car_img     = $car_id ? get_the_post_thumbnail_url( (int) $car_id, 'medium' ) : '';
                $car_title   = $car_id ? get_the_title( (int) $car_id ) : __( 'Car Rental', 'car-rental-manager' );
                $pickup_date = $pickup_dt ? MPCRBM_Global_Function::date_format( $pickup_dt ) : '—';
                $pickup_time = $pickup_dt ? MPCRBM_Global_Function::date_format( $pickup_dt, 'time' ) : '';
                $return_date = $return_dt ? MPCRBM_Global_Function::date_format( $return_dt ) : '—';
                $return_time = $return_dt ? MPCRBM_Global_Function::date_format( $return_dt, 'time' ) : '';

                ob_start(); ?>
                <div class="mpcrbm-mb-card" data-id="<?php echo esc_attr( $id ); ?>">
                    <div class="mpcrbm-mb-card-thumb">
                        <?php if ( $car_img ) : ?>
                            <img src="<?php echo esc_url( $car_img ); ?>" alt="<?php echo esc_attr( $car_title ); ?>" class="mpcrbm-mb-card-img">
                        <?php else : ?>
                            <div class="mpcrbm-mb-card-img-placeholder"><i class="mi mi-car"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="mpcrbm-mb-card-body">
                        <div class="mpcrbm-mb-card-info">
                            <h3 class="mpcrbm-mb-card-title"><?php echo esc_html( $car_title ); ?></h3>
                            <span class="mpcrbm-mb-card-num">#<?php echo esc_html( $order_id ?: $id ); ?></span>
                        </div>
                        <div class="mpcrbm-mb-card-dates">
                            <div class="mpcrbm-mb-card-date">
                                <span class="mpcrbm-mb-card-date-label"><?php esc_html_e( 'Pickup', 'car-rental-manager' ); ?></span>
                                <span class="mpcrbm-mb-card-date-val"><?php echo esc_html( $pickup_date ); ?></span>
                                <?php if ( $pickup_time ) : ?><span class="mpcrbm-mb-card-date-time"><?php echo esc_html( $pickup_time ); ?></span><?php endif; ?>
                            </div>
                            <div class="mpcrbm-mb-card-date-arrow"><i class="mi mi-arrow-right-long"></i></div>
                            <div class="mpcrbm-mb-card-date">
                                <span class="mpcrbm-mb-card-date-label"><?php esc_html_e( 'Return', 'car-rental-manager' ); ?></span>
                                <span class="mpcrbm-mb-card-date-val"><?php echo esc_html( $return_date ); ?></span>
                                <?php if ( $return_time ) : ?><span class="mpcrbm-mb-card-date-time"><?php echo esc_html( $return_time ); ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php if ( $start_place ) : ?>
                        <div class="mpcrbm-mb-card-route">
                            <i class="mi mi-map-pin-alt"></i>
                            <span><?php echo esc_html( $start_place ); ?></span>
                            <?php if ( $end_place && $end_place !== $start_place ) : ?>
                                <i class="mi mi-arrow-right"></i>
                                <span><?php echo esc_html( $end_place ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="mpcrbm-mb-card-footer">
                            <span class="mpcrbm-mb-badge mpcrbm-mb-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
                            <span class="mpcrbm-mb-card-price"><?php echo $total ? wp_kses_post( wc_price( (float) $total ) ) : ''; ?></span>
                            <button class="mpcrbm-mb-view-btn js-mpcrbm-mb-view" data-id="<?php echo esc_attr( $id ); ?>">
                                <?php esc_html_e( 'View Details', 'car-rental-manager' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php return ob_get_clean();
            }

            private function mpcrbm_mb_render_detail( $booking_id, $order_obj ) {
                $car_id     = get_post_meta( $booking_id, 'mpcrbm_id', true );
                $pickup_dt  = get_post_meta( $booking_id, 'mpcrbm_date', true );
                $return_dt  = get_post_meta( $booking_id, 'return_date_time', true );
                $from       = (string) get_post_meta( $booking_id, 'mpcrbm_start_place', true );
                $to         = (string) get_post_meta( $booking_id, 'mpcrbm_end_place', true );
                $order_id   = get_post_meta( $booking_id, 'mpcrbm_order_id', true );
                $status     = (string) get_post_meta( $booking_id, 'mpcrbm_order_status', true );
                $total      = (float) get_post_meta( $booking_id, 'mpcrbm_tp', true );
                $base_price = (float) get_post_meta( $booking_id, 'mpcrbm_base_price', true );
                $quantity   = (int) get_post_meta( $booking_id, 'mpcrbm_car_quantity', true ) ?: 1;
                $deposit    = (float) get_post_meta( $booking_id, 'mpcrbm_security_deposit_amount', true );
                $one_way    = (float) get_post_meta( $booking_id, 'mpcrbm_branch_one_way_fee', true );
                $payment    = (string) get_post_meta( $booking_id, 'mpcrbm_payment_method', true );
                $bill_name  = (string) get_post_meta( $booking_id, 'mpcrbm_billing_name', true );
                $bill_email = (string) get_post_meta( $booking_id, 'mpcrbm_billing_email', true );
                $bill_phone = (string) get_post_meta( $booking_id, 'mpcrbm_billing_phone', true );
                $car_img    = $car_id ? get_the_post_thumbnail_url( (int) $car_id, 'medium' ) : '';
                $car_title  = $car_id ? get_the_title( (int) $car_id ) : __( 'Car Rental', 'car-rental-manager' );

                $pickup_date = $pickup_dt ? MPCRBM_Global_Function::date_format( $pickup_dt ) : '—';
                $pickup_time = $pickup_dt ? MPCRBM_Global_Function::date_format( $pickup_dt, 'time' ) : '';
                $return_date = $return_dt ? MPCRBM_Global_Function::date_format( $return_dt ) : '—';
                $return_time = $return_dt ? MPCRBM_Global_Function::date_format( $return_dt, 'time' ) : '';

                $duration = '';
                if ( $pickup_dt && $return_dt ) {
                    $diff     = abs( strtotime( $return_dt ) - strtotime( $pickup_dt ) );
                    $days     = max( 1, (int) ceil( $diff / DAY_IN_SECONDS ) );
                    $duration = $days . ' ' . _n( 'day', 'days', $days, 'car-rental-manager' );
                }

                ob_start(); ?>
                <div class="mpcrbm-mb-detail">
                    <div class="mpcrbm-mb-detail-hero">
                        <?php if ( $car_img ) : ?>
                            <img src="<?php echo esc_url( $car_img ); ?>" alt="<?php echo esc_attr( $car_title ); ?>" class="mpcrbm-mb-detail-car-img">
                        <?php else : ?>
                            <div class="mpcrbm-mb-detail-car-img-placeholder"><i class="mi mi-car"></i></div>
                        <?php endif; ?>
                        <div class="mpcrbm-mb-detail-hero-info">
                            <h2 class="mpcrbm-mb-detail-car-title"><?php echo esc_html( $car_title ); ?></h2>
                            <div class="mpcrbm-mb-detail-meta">
                                <span class="mpcrbm-mb-badge mpcrbm-mb-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
                                <span class="mpcrbm-mb-detail-order-num"><?php echo esc_html__( 'Order', 'car-rental-manager' ); ?> #<?php echo esc_html( $order_id ?: $booking_id ); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mpcrbm-mb-detail-sections">
                        <div class="mpcrbm-mb-detail-section">
                            <h4 class="mpcrbm-mb-detail-section-title"><i class="mi mi-calendar-check"></i> <?php esc_html_e( 'Rental Period', 'car-rental-manager' ); ?></h4>
                            <div class="mpcrbm-mb-detail-dates">
                                <div class="mpcrbm-mb-detail-date-block">
                                    <span class="mpcrbm-mb-detail-date-label"><?php esc_html_e( 'Pickup', 'car-rental-manager' ); ?></span>
                                    <span class="mpcrbm-mb-detail-date-val"><?php echo esc_html( $pickup_date ); ?></span>
                                    <?php if ( $pickup_time ) : ?><span class="mpcrbm-mb-detail-date-time"><?php echo esc_html( $pickup_time ); ?></span><?php endif; ?>
                                </div>
                                <?php if ( $duration ) : ?>
                                <div class="mpcrbm-mb-detail-duration"><i class="mi mi-time-quarter-to"></i> <?php echo esc_html( $duration ); ?></div>
                                <?php endif; ?>
                                <div class="mpcrbm-mb-detail-date-block">
                                    <span class="mpcrbm-mb-detail-date-label"><?php esc_html_e( 'Return', 'car-rental-manager' ); ?></span>
                                    <span class="mpcrbm-mb-detail-date-val"><?php echo esc_html( $return_date ); ?></span>
                                    <?php if ( $return_time ) : ?><span class="mpcrbm-mb-detail-date-time"><?php echo esc_html( $return_time ); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ( $from || $to ) : ?>
                        <div class="mpcrbm-mb-detail-section">
                            <h4 class="mpcrbm-mb-detail-section-title"><i class="mi mi-map-pin-alt"></i> <?php esc_html_e( 'Locations', 'car-rental-manager' ); ?></h4>
                            <div class="mpcrbm-mb-detail-locations">
                                <?php if ( $from ) : ?>
                                <div class="mpcrbm-mb-detail-loc">
                                    <span class="mpcrbm-mb-detail-loc-label"><?php esc_html_e( 'From', 'car-rental-manager' ); ?></span>
                                    <span><?php echo esc_html( $from ); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ( $to && $to !== $from ) : ?>
                                <div class="mpcrbm-mb-detail-loc">
                                    <span class="mpcrbm-mb-detail-loc-label"><?php esc_html_e( 'To', 'car-rental-manager' ); ?></span>
                                    <span><?php echo esc_html( $to ); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mpcrbm-mb-detail-section">
                            <h4 class="mpcrbm-mb-detail-section-title"><i class="mi mi-money-bill"></i> <?php esc_html_e( 'Pricing', 'car-rental-manager' ); ?></h4>
                            <div class="mpcrbm-mb-detail-pricing">
                                <?php if ( $base_price > 0 ) : ?>
                                <div class="mpcrbm-mb-detail-price-row">
                                    <span><?php echo esc_html( sprintf( __( 'Base Price × %d', 'car-rental-manager' ), $quantity ) ); ?></span>
                                    <span><?php echo wp_kses_post( wc_price( $base_price * $quantity ) ); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ( $one_way > 0 ) : ?>
                                <div class="mpcrbm-mb-detail-price-row">
                                    <span><?php esc_html_e( 'One-Way Fee', 'car-rental-manager' ); ?></span>
                                    <span><?php echo wp_kses_post( wc_price( $one_way ) ); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ( $deposit > 0 ) : ?>
                                <div class="mpcrbm-mb-detail-price-row">
                                    <span><?php esc_html_e( 'Security Deposit', 'car-rental-manager' ); ?></span>
                                    <span><?php echo wp_kses_post( wc_price( $deposit ) ); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="mpcrbm-mb-detail-price-row mpcrbm-mb-detail-price-total">
                                    <span><?php esc_html_e( 'Total', 'car-rental-manager' ); ?></span>
                                    <span><?php echo wp_kses_post( wc_price( $total ) ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mpcrbm-mb-detail-section mpcrbm-mb-detail-section--row">
                            <?php if ( $bill_name || $bill_email || $bill_phone ) : ?>
                            <div class="mpcrbm-mb-detail-subsection">
                                <h4 class="mpcrbm-mb-detail-section-title"><i class="mi mi-person"></i> <?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></h4>
                                <?php if ( $bill_name ) : ?><p><?php echo esc_html( $bill_name ); ?></p><?php endif; ?>
                                <?php if ( $bill_email ) : ?><p><?php echo esc_html( $bill_email ); ?></p><?php endif; ?>
                                <?php if ( $bill_phone ) : ?><p><?php echo esc_html( $bill_phone ); ?></p><?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ( $payment ) : ?>
                            <div class="mpcrbm-mb-detail-subsection">
                                <h4 class="mpcrbm-mb-detail-section-title"><i class="mi mi-credit-card"></i> <?php esc_html_e( 'Payment', 'car-rental-manager' ); ?></h4>
                                <p><?php echo esc_html( $payment ); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        $modifiable = in_array( $status, [ 'pending', 'processing', 'on-hold' ], true );
                        $mod_reqs   = get_post_meta( $booking_id, 'mpcrbm_mod_requests', true );
                        if ( ! is_array( $mod_reqs ) ) $mod_reqs = [];
                        $pending_req = null;
                        foreach ( array_reverse( $mod_reqs ) as $_r ) {
                            if ( ( $_r['status'] ?? '' ) === 'pending' ) { $pending_req = $_r; break; }
                        }
                        if ( $modifiable ) : ?>
                        <div class="mpcrbm-mb-mod-section" data-booking-id="<?php echo esc_attr( $booking_id ); ?>">
                            <h4 class="mpcrbm-mb-mod-title">
                                <i class="mi mi-edit"></i> <?php esc_html_e( 'Request Modification', 'car-rental-manager' ); ?>
                            </h4>
                            <?php if ( $pending_req ) : ?>
                            <div class="mpcrbm-mb-mod-pending">
                                <i class="mi mi-time-quarter-to"></i>
                                <?php
                                echo esc_html( $pending_req['type'] === 'cancellation'
                                    ? __( 'You have a pending cancellation request. We\'ll respond shortly.', 'car-rental-manager' )
                                    : __( 'You have a pending date change request. We\'ll respond shortly.', 'car-rental-manager' )
                                ); ?>
                            </div>
                            <?php else : ?>
                            <div class="mpcrbm-mb-mod-btns">
                                <button type="button" class="mpcrbm-mb-mod-toggle-btn js-mpcrbm-mod-open" data-target="mpcrbm-mod-cancel-form">
                                    <i class="mi mi-close-circle"></i> <?php esc_html_e( 'Cancel Booking', 'car-rental-manager' ); ?>
                                </button>
                                <button type="button" class="mpcrbm-mb-mod-toggle-btn js-mpcrbm-mod-open" data-target="mpcrbm-mod-date-form">
                                    <i class="mi mi-calendar-edit"></i> <?php esc_html_e( 'Change Dates', 'car-rental-manager' ); ?>
                                </button>
                            </div>
                            <div class="mpcrbm-mb-mod-pending-after" style="display:none;">
                                <i class="mi mi-check-circle"></i>
                                <?php esc_html_e( 'Your request has been submitted. We\'ll respond shortly.', 'car-rental-manager' ); ?>
                            </div>

                            <form class="mpcrbm-mb-mod-form" id="mpcrbm-mod-cancel-form" data-type="cancellation" style="display:none;">
                                <div class="mpcrbm-mb-mod-form-field">
                                    <label><?php esc_html_e( 'Reason (optional)', 'car-rental-manager' ); ?></label>
                                    <textarea name="note" rows="3" placeholder="<?php esc_attr_e( 'Tell us why you want to cancel…', 'car-rental-manager' ); ?>"></textarea>
                                </div>
                                <div class="mpcrbm-mb-mod-form-actions">
                                    <button type="submit" class="mpcrbm-mb-mod-submit-btn mpcrbm-mb-mod-submit-btn--danger">
                                        <?php esc_html_e( 'Submit Cancellation Request', 'car-rental-manager' ); ?>
                                    </button>
                                    <button type="button" class="mpcrbm-mb-mod-dismiss-btn js-mpcrbm-mod-dismiss">
                                        <?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?>
                                    </button>
                                </div>
                                <div class="mpcrbm-mb-mod-result"></div>
                            </form>

                            <form class="mpcrbm-mb-mod-form" id="mpcrbm-mod-date-form" data-type="date_change" style="display:none;">
                                <div class="mpcrbm-mb-mod-date-row">
                                    <div class="mpcrbm-mb-mod-form-field">
                                        <label><?php esc_html_e( 'New Pickup Date & Time', 'car-rental-manager' ); ?></label>
                                        <input type="text" name="new_pickup" class="mpcrbm-mod-datepicker"
                                               value="<?php echo esc_attr( $pickup_dt ); ?>"
                                               data-default="<?php echo esc_attr( $pickup_dt ); ?>"
                                               placeholder="YYYY-MM-DD HH:MM" autocomplete="off" required>
                                    </div>
                                    <div class="mpcrbm-mb-mod-form-field">
                                        <label><?php esc_html_e( 'New Return Date & Time', 'car-rental-manager' ); ?></label>
                                        <input type="text" name="new_return" class="mpcrbm-mod-datepicker"
                                               value="<?php echo esc_attr( $return_dt ); ?>"
                                               data-default="<?php echo esc_attr( $return_dt ); ?>"
                                               placeholder="YYYY-MM-DD HH:MM" autocomplete="off" required>
                                    </div>
                                </div>
                                <div class="mpcrbm-mb-mod-form-field">
                                    <label><?php esc_html_e( 'Note (optional)', 'car-rental-manager' ); ?></label>
                                    <textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'Any additional details…', 'car-rental-manager' ); ?>"></textarea>
                                </div>
                                <div class="mpcrbm-mb-mod-form-actions">
                                    <button type="submit" class="mpcrbm-mb-mod-submit-btn">
                                        <?php esc_html_e( 'Submit Date Change Request', 'car-rental-manager' ); ?>
                                    </button>
                                    <button type="button" class="mpcrbm-mb-mod-dismiss-btn js-mpcrbm-mod-dismiss">
                                        <?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?>
                                    </button>
                                </div>
                                <div class="mpcrbm-mb-mod-result"></div>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php return ob_get_clean();
            }

            public function mpcrbm_mb_mod_request() {
                check_ajax_referer( 'mpcrbm_my_bookings', 'nonce' );
                if ( ! is_user_logged_in() ) { wp_send_json_error(); }

                $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
                $req_type   = isset( $_POST['req_type'] )   ? sanitize_key( wp_unslash( $_POST['req_type'] ) ) : '';
                $note       = isset( $_POST['note'] )       ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
                $new_pickup = isset( $_POST['new_pickup'] ) ? sanitize_text_field( wp_unslash( $_POST['new_pickup'] ) ) : '';
                $new_return = isset( $_POST['new_return'] ) ? sanitize_text_field( wp_unslash( $_POST['new_return'] ) ) : '';

                if ( ! $booking_id || ! in_array( $req_type, [ 'cancellation', 'date_change' ], true ) ) {
                    wp_send_json_error( [ 'message' => __( 'Invalid request.', 'car-rental-manager' ) ] );
                }

                $booking = get_post( $booking_id );
                if ( ! $booking || $booking->post_type !== 'mpcrbm_booking' ) {
                    wp_send_json_error( [ 'message' => __( 'Booking not found.', 'car-rental-manager' ) ] );
                }

                $order_id  = (int) get_post_meta( $booking_id, 'mpcrbm_order_id', true );
                $order_obj = $order_id ? wc_get_order( $order_id ) : null;
                if ( ! $order_obj || (int) $order_obj->get_customer_id() !== get_current_user_id() ) {
                    wp_send_json_error( [ 'message' => __( 'Access denied.', 'car-rental-manager' ) ] );
                }

                $status = (string) get_post_meta( $booking_id, 'mpcrbm_order_status', true );
                if ( ! in_array( $status, [ 'pending', 'processing', 'on-hold' ], true ) ) {
                    wp_send_json_error( [ 'message' => __( 'This booking cannot be modified.', 'car-rental-manager' ) ] );
                }

                $mod_reqs = get_post_meta( $booking_id, 'mpcrbm_mod_requests', true );
                if ( ! is_array( $mod_reqs ) ) $mod_reqs = [];
                foreach ( $mod_reqs as $r ) {
                    if ( ( $r['status'] ?? '' ) === 'pending' ) {
                        wp_send_json_error( [ 'message' => __( 'You already have a pending modification request.', 'car-rental-manager' ) ] );
                    }
                }

                $mod_reqs[] = [
                    'type'       => $req_type,
                    'status'     => 'pending',
                    'submitted'  => time(),
                    'note'       => $note,
                    'new_pickup' => $new_pickup,
                    'new_return' => $new_return,
                ];
                update_post_meta( $booking_id, 'mpcrbm_mod_requests', $mod_reqs );

                $user      = wp_get_current_user();
                $car_id    = get_post_meta( $booking_id, 'mpcrbm_id', true );
                $car_title = $car_id ? get_the_title( (int) $car_id ) : __( 'Car Rental', 'car-rental-manager' );
                $type_label = $req_type === 'cancellation' ? 'Cancellation' : 'Date Change';
                $subject   = sprintf( '[%s] Booking %s Request — #%d', get_bloginfo( 'name' ), $type_label, $booking_id );
                $body  = "A customer has submitted a booking modification request.\n\n";
                $body .= "Car: {$car_title}\n";
                $body .= "Booking ID: #{$booking_id}\n";
                $body .= "Order ID: #{$order_id}\n";
                $body .= "Customer: {$user->display_name} ({$user->user_email})\n";
                $body .= "Request Type: {$type_label}\n";
                if ( $req_type === 'date_change' ) {
                    $body .= "New Pickup: {$new_pickup}\n";
                    $body .= "New Return: {$new_return}\n";
                }
                if ( $note ) { $body .= "Note: {$note}\n"; }
                wp_mail( get_option( 'admin_email' ), $subject, $body );

                wp_send_json_success( [ 'message' => __( 'Your request has been submitted. We\'ll get back to you shortly.', 'car-rental-manager' ) ] );
            }

		}
		new MPCRBM_Shortcodes();
	}