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
                    'posts_per_page' => intval( $atts['per_page'] ),
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
                                    <?php foreach ( $cars as $car ) : ?>
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
                                                    <div class="mpcrbm_car_list_content_holder">
                                                        <p class="mpcrbm_car_list_content"><?php echo strip_tags (wp_kses_post( $car['content'] ) ); ?></p>
                                                    </div>
                                                    <div class="mpcrbm_car_specs_lists">
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-car"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Car Type ','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html($car['type']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-gas-pump-alt"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Fuel Type ','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html( $car['fuel']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-bonus"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Brands','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html($car['brand']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-time-quarter-to"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Make Year','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html($car['year']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-person-seat"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Seat Capacity','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html($car['seating']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="mpcrbm_car_spec">
                                                            <i class="mi mi-person-luggage"></i>
                                                            <div>
                                                                <div class="spec-label"><?php echo esc_html__('Maximum Bags','car-rental-manager'); ?></div>
                                                                <div class="spec-value"><?php echo esc_html($car['bag_count']); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mpcrbm_car_list_price_display">
                                                        <div class="mpcrbm_car_list_price">
                                                            <h3>
                                                                <span class="woocommerce-Price-amount amount">
                                                                    <?php echo wc_price( $car['day_price'] );?>
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

                ob_start();
                do_shortcode( '[shop_messages]' );
                echo wp_kses_post( ob_get_clean() );
                //echo '<pre>';print_r($params);echo '</pre>';
                include( MPCRBM_Function::template_path( 'registration/registration_layout.php' ) );
				return ob_get_clean();
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
				);
			}
		}
		new MPCRBM_Shortcodes();
	}