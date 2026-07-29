<?php
	/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Tax_Settings' ) ) {
		// Despite the class name (kept as-is to avoid touching the require in
		// MPCRBM_Admin.php and other references), this now owns the "Advanced"
		// tab as a whole — Tax Settings is one card in it, "Related Rental" is
		// the other. The tab used to be its own top-level "Tax Configure" item;
		// per explicit request it's now folded into "Advanced", placed after
		// the "Driver and Branch Assignment" tab (MPCRBM_Branch_Manager.php)
		// via a higher hook priority (20 vs that tab's default 10 — both hook
		// into the same 'mpcrbm_settings_tab_navigation'/'mpcrbm_settings_tab_content'
		// actions that MPCRBM_Settings::settings() fires after its own
		// hardcoded tabs).
		class MPCRBM_Tax_Settings {
			public function __construct() {
				add_action( 'mpcrbm_settings_tab_navigation', [ $this, 'advanced_tab_nav' ], 20 );
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'tab_content' ], 20 );
				add_action( 'save_post', [ $this, 'settings_save' ] );
			}

			public function advanced_tab_nav() {
				?>
                <li data-tabs-target="#mpcrbm_advanced">
                    <span class="mi mi-settings"></span><?php esc_html_e( 'Advanced', 'car-rental-manager' ); ?>
                </li>
				<?php
			}

			public function tab_content( $post_id ) {
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_advanced">
					<?php
						$tax_status    = MPCRBM_Global_Function::get_post_info( $post_id, '_tax_status' );
						$tax_class     = MPCRBM_Global_Function::get_post_info( $post_id, '_tax_class' );
						$all_tax_class = MPCRBM_Global_Function::all_tax_list();
					?>
					<?php wp_nonce_field( 'save_tax_settings', 'tax_settings_nonce' ); ?>
                    <div class="mpcrbm-info-card">
                        <div class="mpcrbm-info-card-header">
                            <i class="fas fa-receipt"></i>
                            <h3><?php esc_html_e( 'Tax Settings Information', 'car-rental-manager' ); ?></h3>
                        </div>
                        <div class="mpcrbm-info-card-body">
					<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) { ?>
                        <div class="mpcrbm-info-grid">
                            <section>
                                <label class="label">
                                    <div>
                                        <h6><?php esc_html_e( 'Tax status', 'car-rental-manager' ); ?></h6>
                                        <span class="desc"><?php esc_html_e( 'Select tax status type.', 'car-rental-manager' ); ?></span>
                                    </div>
                                    <select class="formControl max_300" name="_tax_status">
                                        <option disabled selected><?php esc_html_e( 'Please Select', 'car-rental-manager' ); ?></option>
                                        <option value="taxable" <?php echo esc_attr( $tax_status == 'taxable' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Taxable', 'car-rental-manager' ); ?>
                                        </option>
                                        <option value="shipping" <?php echo esc_attr( $tax_status == 'shipping' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Shipping only', 'car-rental-manager' ); ?>
                                        </option>
                                        <option value="none" <?php echo esc_attr( $tax_status == 'none' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'None', 'car-rental-manager' ); ?>
                                        </option>
                                    </select>
                                </label>
                            </section>
                            <section>
                                <label class="label">
                                    <div>
                                        <h6><?php esc_html_e( 'Tax class', 'car-rental-manager' ); ?></h6>
                                        <span class="desc"><?php esc_html_e( 'Select tax class.', 'car-rental-manager' ); ?></span>
                                    </div>
                                    <select class="formControl max_300" name="_tax_class">
                                        <option disabled selected><?php esc_html_e( 'Please Select', 'car-rental-manager' ); ?></option>
                                        <option value="standard" <?php echo esc_attr( $tax_class == 'standard' ? 'selected' : '' ); ?>>
											<?php esc_html_e( 'Standard', 'car-rental-manager' ); ?>
                                        </option>
										<?php if ( sizeof( $all_tax_class ) > 0 ) { ?>
											<?php foreach ( $all_tax_class as $key => $class ) { ?>
                                                <option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $tax_class == $key ? 'selected' : '' ); ?>>
													<?php echo esc_html( $class ); ?>
                                                </option>
											<?php } ?>
										<?php } ?>
                                    </select>
                                </label>
                            </section>
                        </div>
					<?php } else { ?>
                        <div class="_dLayout_dFlex_justifyCenter">
							<?php MPCRBM_Layout::msg( esc_html__( 'Tax not active. Please add Tax settings from woocommerce.', 'car-rental-manager' ) ); ?>
                        </div>
					<?php } ?>
                        </div>
                    </div>
					<?php $this->related_rentals_card( $post_id ); ?>
                </div>
				<?php
			}

			// "Related Rental" — lets the admin hand-pick which other cars this
			// one should be shown alongside as similar/related on the frontend
			// (the "Similar Rentals" section on the single car page had no admin
			// control at all before this — this is that control).
			private function related_rentals_card( $post_id ) {
				$cpt      = MPCRBM_Function::get_cpt();
				$selected = get_post_meta( $post_id, 'mpcrbm_related_rentals', true );
				$selected = is_array( $selected ) ? array_map( 'absint', $selected ) : [];

				$other_cars = get_posts( [
					'post_type'      => $cpt,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'exclude'        => [ $post_id ],
					'orderby'        => 'title',
					'order'          => 'ASC',
				] );
				$selected_count = count( $selected );
				$total_count    = count( $other_cars );
				?>
                <div class="mpcrbm-info-card">
                    <div class="mpcrbm-info-card-body">
                        <div class="mpcrbm-faq-toolbar">
                            <div class="mpcrbm-faq-toolbar-text">
                                <div class="mpcrbm-faq-eyebrow">
                                    <span class="mpcrbm-faq-eyebrow-dot"></span>
									<?php esc_html_e( 'Similar Vehicles', 'car-rental-manager' ); ?>
                                </div>
                                <h3><?php esc_html_e( 'Related Rental', 'car-rental-manager' ); ?></h3>
                                <span class="desc"><?php esc_html_e( 'Select one or more cars to show as "Similar Rentals" on this car\'s details page.', 'car-rental-manager' ); ?></span>
                            </div>
							<?php if ( $total_count > 0 ) : ?>
                                <div class="mpcrbm-faq-live-pill">
                                    <span class="mpcrbm-faq-live-switch"></span>
                                    <span>
                                        <span id="mpcrbm_rr_selected_count"><?php echo esc_html( $selected_count ); ?></span>
										<?php esc_html_e( 'of', 'car-rental-manager' ); ?>
                                        <span id="mpcrbm_rr_total_count"><?php echo esc_html( $total_count ); ?></span>
										<?php esc_html_e( 'Selected', 'car-rental-manager' ); ?>
                                    </span>
                                </div>
							<?php endif; ?>
                        </div>

						<?php wp_nonce_field( 'mpcrbm_save_related_rentals', 'mpcrbm_related_rentals_nonce' ); ?>

						<?php if ( $total_count === 0 ) : ?>
                            <p class="desc"><?php esc_html_e( 'No other cars available yet.', 'car-rental-manager' ); ?></p>
						<?php else : ?>
                            <div class="mpcrbm-rr-grid" id="mpcrbm_rr_grid">
								<?php foreach ( $other_cars as $car ) :
									$is_checked = in_array( $car->ID, $selected, true );
									$thumb_url  = get_the_post_thumbnail_url( $car, 'medium' );
									?>
                                    <label class="mpcrbm-rr-item<?php echo $is_checked ? ' is-selected' : ''; ?>">
                                        <input type="checkbox" class="mpcrbm-rr-check-input" name="mpcrbm_related_rentals[]" value="<?php echo esc_attr( $car->ID ); ?>" <?php checked( $is_checked ); ?>>
                                        <span class="mpcrbm-rr-thumb">
											<?php if ( $thumb_url ) : ?>
                                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
											<?php else : ?>
                                                <i class="fas fa-car-side"></i>
											<?php endif; ?>
                                            <span class="mpcrbm-rr-check-badge"><i class="fas fa-check"></i></span>
                                        </span>
                                        <span class="mpcrbm-rr-title"><?php echo esc_html( get_the_title( $car ) ); ?></span>
                                    </label>
								<?php endforeach; ?>
                            </div>
                            <script>
                            jQuery(function ($) {
                                var $grid = $('#mpcrbm_rr_grid');
                                $grid.on('change', '.mpcrbm-rr-check-input', function () {
                                    $(this).closest('.mpcrbm-rr-item').toggleClass('is-selected', this.checked);
                                    $('#mpcrbm_rr_selected_count').text($grid.find('.mpcrbm-rr-check-input:checked').length);
                                });
                            });
                            </script>
						<?php endif; ?>
                    </div>
                </div>
				<?php
			}

			public function settings_save( $post_id ) {
				if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
				if ( get_post_type( $post_id ) != MPCRBM_Function::get_cpt() ) {
					return;
				}

				if (
					isset( $_POST['tax_settings_nonce'] ) &&
					wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tax_settings_nonce'] ) ), 'save_tax_settings' )
				) {
					// Fixed by Shahnur — 2026-04-28 11:56 AM (Asia/Dhaka)
					$allowed_tax_status = array( 'taxable', 'shipping', 'none' );
					$tax_status         = isset( $_POST['_tax_status'] ) ? sanitize_text_field( wp_unslash( $_POST['_tax_status'] ) ) : 'none';
					$tax_status         = in_array( $tax_status, $allowed_tax_status, true ) ? $tax_status : 'none';
					$tax_class          = isset( $_POST['_tax_class'] ) ? sanitize_title( wp_unslash( $_POST['_tax_class'] ) ) : '';
					$allowed_tax_class  = array_keys( MPCRBM_Global_Function::all_tax_list() );
					$tax_class          = in_array( $tax_class, $allowed_tax_class, true ) ? $tax_class : '';
					update_post_meta( $post_id, '_tax_status', $tax_status );
					update_post_meta( $post_id, '_tax_class', $tax_class );
				}

				if (
					isset( $_POST['mpcrbm_related_rentals_nonce'] ) &&
					wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_related_rentals_nonce'] ) ), 'mpcrbm_save_related_rentals' )
				) {
					$cpt      = MPCRBM_Function::get_cpt();
					$posted   = isset( $_POST['mpcrbm_related_rentals'] ) && is_array( $_POST['mpcrbm_related_rentals'] )
						? array_map( 'absint', wp_unslash( $_POST['mpcrbm_related_rentals'] ) )
						: [];
					$related  = array_values( array_filter( $posted, function ( $id ) use ( $cpt, $post_id ) {
						return $id !== (int) $post_id && get_post_type( $id ) === $cpt;
					} ) );
					update_post_meta( $post_id, 'mpcrbm_related_rentals', $related );
				}
			}

		}
		new MPCRBM_Tax_Settings();
	}
