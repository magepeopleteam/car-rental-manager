<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Price_Settings' ) ) {
		class MPCRBM_Price_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'price_settings' ] );
//				add_action( 'mpcrbm_settings_tab_content', [ $this, 'price_settings' ] );
				add_action( 'save_post', [ $this, 'save_price_settings' ] );
				add_action( 'mpcrbm_settings_sec_fields', array( $this, 'settings_sec_fields' ), 10, 1 );


				add_action( 'wp_ajax_mpcrbm_add_price_discount_rules', array( $this, 'mpcrbm_add_price_discount_rules' ), 10, 1 );
			}

            public function mpcrbm_add_price_discount_rules(){
                if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'mpcrbm_extra_service' ) ) {
                    wp_send_json_error([ 'message' => 'Security check failed' ]);
                }
                $success =false;
                $message = 'Update Failed';

                $post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : '';
                $enable  =  isset( $_POST['enable'] ) ? intval( wp_unslash( $_POST['enable'] ) ) : '';
                $metaKey  =  isset( $_POST['metaKey'] ) ? sanitize_text_field( wp_unslash( $_POST['metaKey'] ) ) : '';

                if( $post_id && $metaKey ){
                    update_post_meta( $post_id, $metaKey, $enable );
                    $success = true;
                    $message = 'Discount setting successfully saved.';
                }

                wp_send_json_success([ 'success' => $success ,'message' => $message ]);
            }

            public function set_price_meta_box( $post_id ) {
                wp_nonce_field( 'mpcrbm_set_price_save', 'mpcrbm_set_price_nonce' );

//                $base_price = get_post_meta( $post_id, 'mpcrbm_base_daily_price', true );
                $daywise    = (array) get_post_meta( $post_id, 'mpcrbm_daywise_pricing', true );
                $tiered     = (array) get_post_meta( $post_id, 'mpcrbm_tiered_discounts', true );
                $seasonal   = (array) get_post_meta( $post_id, 'mpcrbm_seasonal_pricing', true );

                $enable_tired       =  (int)get_post_meta( $post_id, 'mpcrbm_enable_tired_discount', true );
                $enable_day_wise    = (int)get_post_meta( $post_id, 'mpcrbm_enable_day_wise_discount', true );
                $enable_seasonal    = (int)get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );



                if( $enable_tired === 1 ) {
                    $tired_display = 'block';
                    $tired_checked = 'checked';
                }else{
                    $tired_display = 'none';
                    $tired_checked = '';
                }

                if( $enable_day_wise === 1 ) {
                    $day_wise_display = 'block';
                    $day_wise_checked = 'checked';
                }else{
                    $day_wise_display = 'none';
                    $day_wise_checked = '';
                }

                if( $enable_seasonal === 1 ) {
                    $seasonal_display = 'block';
                    $seasonal_checked = 'checked';
                }else{
                    $seasonal_display = 'none';
                    $seasonal_checked = '';
                }


                ?>

                <!--<div class="mpcrbm-section">
                    <div class="mpcrbm-heading"><?php /*esc_html_e('Base Daily Price', 'mpcrbm'); */?></div>
                    <div class="mpcrbm-price-content-container">
                        <input type="number" name="mpcrbm_base_daily_price" step="0.01" value="<?php /*echo esc_attr($base_price); */?>" />
                    </div>
                </div>-->

                <div class="mpcrbm-section">
                    <div class="mpcrbm-heading"><?php esc_html_e('Tiered Discount Rules', 'car-rental-manager'); ?></div>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e('Enable Tiered Discount Rules', 'car-rental-manager'); ?></h6>
                                <span class="desc"><?php esc_html_e('By default tired discount rules is OFF but you can keep it on by switching this option', 'car-rental-manager'); ?></span>
                            </div>
                            <?php MPCRBM_Custom_Layout::switch_checkbox_button( 'mpcrbm_enable_tired_discount', $tired_checked ); ?>
                        </label>
                    </section>
                    <div class="mpcrbm-price-content-container" id="mpcrbm_enable_tired_discount_holder" style="display: <?php echo esc_attr( $tired_display )?>">
                            <div id="mpcrbm-tiered-rows" class="mpcrbm-list">
                                <?php if ( isset( $tiered[0] ) && is_array( $tiered[0] ) && ! empty( $tiered[0] ) ) :
                                    foreach ( $tiered as $t ) : ?>
                                        <div class="mpcrbm-item mpcrbm-price-discount-tier">
                                            <input type="number" name="mpcrbm_tiered_discounts[min][]" value="<?php echo esc_attr($t['min']); ?>" class="mpcrbm-input" placeholder="<?php esc_html_e( 'Min Days', 'car-rental-manager' ); ?>">
                                            <span class="separator">–</span>
                                            <input type="number" name="mpcrbm_tiered_discounts[max][]" value="<?php echo esc_attr($t['max']); ?>" class="mpcrbm-input" placeholder="<?php esc_html_e( 'Max Days', 'car-rental-manager' ); ?>">
                                            <span>days</span>
                                            <input type="number" step="0.01" name="mpcrbm_tiered_discounts[percent][]" value="<?php echo esc_attr($t['percent']); ?>" class="mpcrbm-input" placeholder="<?php esc_html_e( '% Discount', 'car-rental-manager' ); ?>">
                                            <span>% discount</span>
                                            <button type="button" class="button mpcrbm-remove-row mpcrbm-remove-btn"><?php esc_html_e( 'Remove', 'car-rental-manager' ); ?></button>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" id="mpcrbm-add-tier" class="mpcrbm-price-add-btn">+ <?php esc_html_e( 'Add Tier', 'car-rental-manager' ); ?></button>
                            <p class="mpcrbm-price-info-text"><?php esc_html_e( 'Set discount percentages based on rental duration. Longer rentals get better rates.', 'car-rental-manager' ); ?></p>
                        </div>
                </div>

                <div class="mpcrbm-section">
                    <div class="mpcrbm-heading"><?php esc_html_e('Day-wise Pricing', 'car-rental-manager'); ?></div>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e('Enable Day Wise Discount Rules', 'car-rental-manager'); ?></h6>
                                <span class="desc"><?php esc_html_e('By default day wise discount rules is OFF but you can keep it on by switching this option', 'car-rental-manager'); ?></span>
                            </div>
                            <?php MPCRBM_Custom_Layout::switch_checkbox_button( 'mpcrbm_enable_day_wise_discount', $day_wise_checked ); ?>
                        </label>
                    </section>
                    <div class="mpcrbm-price-content-container" id="mpcrbm_enable_day_wise_discount_holder" style="display: <?php echo esc_attr( $day_wise_display )?>">

                        <div class="mpcrbm-price-info-banner ">
                            <svg class="mpcrbm-price-icon" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 8px;">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <?php esc_html_e( 'Set specific rates for each day of the week. Leave empty to use base daily price.', 'car-rental-manager' ); ?>
                        </div>
                        <div class="mpcrbm-grid">
                            <?php
                            $days = [
                                'mon' => 'Monday',
                                'tue' => 'Tuesday',
                                'wed' => 'Wednesday',
                                'thu' => 'Thursday',
                                'fri' => 'Friday',
                                'sat' => 'Saturday',
                                'sun' => 'Sunday'
                            ];
                            foreach ( $days as $k => $label ) :
                                $val = isset($daywise[$k]) ? $daywise[$k] : '';

                                if( $k === 'fri' || $k === 'sat' || $k === 'sun' ){
                                    $weekend_class = 'weekend';
                                }else{
                                    $weekend_class = '';
                                }
                                ?>
                                <div class="mpcrbm-grid-item mpcrbm-price-day-card <?php echo esc_attr( $weekend_class );?>">
                                    <span class="mpcrbm-price-day-label"><?php echo $label; ?></span>
                                    <input type="number" name="mpcrbm_daywise_pricing[<?php echo $k; ?>]" step="0.01" value="<?php echo esc_attr($val); ?>" class="mpcrbm-input">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="mpcrbm-price-info-text"><?php esc_html_e( 'Override the base daily price for specific days of the week. Weekend rates are highlighted.', 'car-rental-manager' ); ?></p>
                    </div>
                </div>

                <div class="mpcrbm-section">
                     <div class="mpcrbm-heading"><?php esc_html_e('Seasonal Pricing', 'car-rental-manager'); ?></div>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e('Enable Seasonal Discount Rules', 'car-rental-manager'); ?></h6>
                                <span class="desc"><?php esc_html_e('By default seasonal discount rules is OFF but you can keep it on by switching this option', 'car-rental-manager'); ?></span>
                            </div>
                            <?php MPCRBM_Custom_Layout::switch_checkbox_button( 'mpcrbm_enable_seasonal_discount', $seasonal_checked ); ?>
                        </label>
                    </section>
                     <div class="mpcrbm-price-content-container" id="mpcrbm_enable_seasonal_discount_holder" style="display: <?php echo esc_attr( $seasonal_display )?>">
                        <div class="mpcrbm-warning-banner">
                            <svg class="mpcrbm-price-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <?php esc_html_e( 'Set special pricing for holidays, peak seasons, and special events throughout the year.', 'car-rental-manager' ); ?>
                        </div>
                        <div id="mpcrbm-season-rows" class="mpcrbm-list">
                            <?php if ( isset( $tiered[0] ) && is_array( $seasonal[0] ) && ! empty( $seasonal[0] ) ) :
                                foreach ( $seasonal as $s ) : ?>
                                    <div class="mpcrbm-item mpcrbm-season-row">
                                        <input type="text" name="mpcrbm_seasonal_pricing[name][]" value="<?php echo esc_attr($s['name']); ?>" placeholder="<?php esc_html_e('Name', 'car-rental-manager'); ?>">
                                        <input type="date" name="mpcrbm_seasonal_pricing[start][]" value="<?php echo esc_attr($s['start']); ?>">
                                        <input type="date" name="mpcrbm_seasonal_pricing[end][]" value="<?php echo esc_attr($s['end']); ?>">
                                        <select name="mpcrbm_seasonal_pricing[type][]">
                                            <option value="percentage_increase" <?php selected($s['type'], 'percentage_increase'); ?>><?php esc_html_e('% Increase', 'car-rental-manager'); ?></option>
                                            <option value="percentage_decrease" <?php selected($s['type'], 'percentage_decrease'); ?>><?php esc_html_e('% Decrease', 'car-rental-manager'); ?></option>
                                            <option value="fixed_increase" <?php selected($s['type'], 'fixed_increase'); ?>><?php esc_html_e('Fixed Increase', 'car-rental-manager'); ?></option>
                                            <option value="fixed_decrease" <?php selected($s['type'], 'fixed_decrease'); ?>><?php esc_html_e('Fixed Decrease', 'car-rental-manager'); ?></option>
                                        </select>
                                        <input type="number" step="0.01" name="mpcrbm_seasonal_pricing[value][]" value="<?php echo esc_attr($s['value']); ?>" placeholder="<?php esc_html_e('Value', 'car-rental-manager'); ?>">
                                        <button type="button" class="button mpcrbm-remove-row mpcrbm-remove-btn"><?php esc_html_e('Remove', 'car-rental-manager'); ?></button>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <button type="button" id="mpcrbm-add-season" class="mpcrbm-price-add-btn"><?php esc_html_e('+ Add Season', 'car-rental-manager'); ?></button>
                        <p class="mpcrbm-price-info-text"><?php esc_html_e('Create seasonal pricing rules that override base rates during specific date ranges. Choose between fixed prices or percentage adjustments.', 'car-rental-manager'); ?></p>
                    </div>
                </div>

                <?php
            }



            public function price_settings( $post_id ) {
				$time_price            = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price' );
				$manual_prices         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
				$terms_location_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_price_info', [] );
				$location_terms        = get_terms( array( 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ) );
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_settings_pricing">
                    <h2><?php esc_html_e( 'Price Settings', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'here you can set initial price, Waiting Time price, price calculation model', 'car-rental-manager' ); ?></p>
                    <section class="bg-light">
                        <h6><?php esc_html_e( 'Price Settings', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Here you can set price', 'car-rental-manager' ); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Price/Day', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php MPCRBM_Settings::info_text( 'mpcrbm_day_price' ); ?></span>
                            </div>
                            <input class="formControl price_validation" name="mpcrbm_day_price" value="<?php echo esc_attr( $time_price ); ?>" type="text" placeholder="<?php esc_html_e( 'EX:10', 'car-rental-manager' ); ?>"/>
                        </label>
                    </section>
                    <!-- Manual price -->
                    <section class="bg-light" style="margin-top: 20px;" data-collapse="#mp_manual">
                        <h6><?php esc_html_e( 'Manual Price Settings', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Manual Price Settings', 'car-rental-manager' ); ?></span>
                    </section>

                    <?php echo $this->set_price_meta_box( $post_id )?>
                </div>
				<?php
			}

			public function save_price_settings( $post_id ) {
				if (
					! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) ), 'mpcrbm_transportation_type_nonce' ) ||
					( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
					! current_user_can( 'edit_post', $post_id )
				) {
					return;
				}
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					$price_based = "manual";
					update_post_meta( $post_id, 'mpcrbm_price_based', $price_based );
					$hour_price = isset( $_POST['mpcrbm_day_price'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_day_price'] ) ) : 0;
					update_post_meta( $post_id, 'mpcrbm_day_price', $hour_price );



                    if ( ! isset( $_POST['mpcrbm_set_price_nonce'] ) || ! wp_verify_nonce( $_POST['mpcrbm_set_price_nonce'], 'mpcrbm_set_price_save' ) ) return;
                    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
                    if ( get_post_type( $post_id ) !== MPCRBM_Function::get_cpt() ) return;

                    // Base price
                    if ( isset( $_POST['mpcrbm_base_daily_price'] ) ) {
                        update_post_meta( $post_id, 'mpcrbm_base_daily_price', floatval($_POST['mpcrbm_base_daily_price']) );
                    }

                    // Day-wise pricing
                    if ( isset( $_POST['mpcrbm_daywise_pricing'] ) && is_array($_POST['mpcrbm_daywise_pricing']) ) {
                        $clean = [];
                        foreach ($_POST['mpcrbm_daywise_pricing'] as $day=>$val){
                            $clean[$day] = floatval($val);
                        }
                        update_post_meta( $post_id, 'mpcrbm_daywise_pricing', $clean );
                    }

                    // Tiered Discounts
                    if ( isset($_POST['mpcrbm_tiered_discounts']) && is_array($_POST['mpcrbm_tiered_discounts']) ) {
                        $tiers = [];
                        $mins = $_POST['mpcrbm_tiered_discounts']['min'];
                        $maxs = $_POST['mpcrbm_tiered_discounts']['max'];
                        $perc = $_POST['mpcrbm_tiered_discounts']['percent'];
                        for ($i=0; $i < count($mins); $i++){
                            if ($mins[$i] && $maxs[$i] && $perc[$i] !== ''){
                                $tiers[] = [
                                    'min'=>intval($mins[$i]),
                                    'max'=>intval($maxs[$i]),
                                    'percent'=>floatval($perc[$i])
                                ];
                            }
                        }
                        update_post_meta( $post_id, 'mpcrbm_tiered_discounts', $tiers );
                    }

                    // Seasonal Pricing
                    if ( isset($_POST['mpcrbm_seasonal_pricing']) && is_array($_POST['mpcrbm_seasonal_pricing']) ) {
                        $seasons = [];
                        $names = $_POST['mpcrbm_seasonal_pricing']['name'];
                        $starts = $_POST['mpcrbm_seasonal_pricing']['start'];
                        $ends   = $_POST['mpcrbm_seasonal_pricing']['end'];
                        $types  = $_POST['mpcrbm_seasonal_pricing']['type'];
                        $values = $_POST['mpcrbm_seasonal_pricing']['value'];

                        for ($i=0; $i < count($names); $i++){
                            if ($names[$i] && $starts[$i] && $ends[$i]){
                                $seasons[] = [
                                    'name'=>sanitize_text_field($names[$i]),
                                    'start'=>$starts[$i],
                                    'end'=>$ends[$i],
                                    'type'=>$types[$i],
                                    'value'=>floatval($values[$i])
                                ];
                            }
                        }
                        update_post_meta( $post_id, 'mpcrbm_seasonal_pricing', $seasons );
                    }

				}
			}

			public function settings_sec_fields( $default_fields ): array {
				// Ensure $default_fields is an array
				$default_fields = is_array( $default_fields ) ? $default_fields : array();
				$settings_fields = array(
					'mpcrbm_price_settings' => array(
						array(
							'name'    => 'mpcrbm_day_price',
							'label'   => esc_html__( 'Price/Day', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set the daily price for the car rental', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '0'
						),
						array(
							'name'    => 'mpcrbm_manual_price_info',
							'label'   => esc_html__( 'Manual Price Settings', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Configure manual pricing options', 'car-rental-manager' ),
							'type'    => 'array',
							'default' => array()
						),
						array(
							'name'    => 'mpcrbm_terms_price_info',
							'label'   => esc_html__( 'Location Based Pricing', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set prices based on locations', 'car-rental-manager' ),
							'type'    => 'array',
							'default' => array()
						)
					)
				);

				return array_merge( $default_fields, $settings_fields );
			}
		}
		new MPCRBM_Price_Settings();
	}