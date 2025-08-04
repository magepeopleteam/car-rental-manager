<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Extra_Service' ) ) {
		class MPCRBM_Extra_Service {
			public function __construct() {
				add_action( 'add_meta_boxes', array( $this, 'extra_service_meta' ) );
				add_action( 'save_post', array( $this, 'save_ex_service_settings' ) );
				//********************//
				add_action( 'mpcrbm_extra_service_item', array( $this, 'extra_service_item' ) );
				//****************************//
				add_action( 'mpcrbm_settings_tab_content', [ $this, 'ex_service_settings' ] );
				//*******************//
				add_action( 'wp_ajax_mpcrbm_get_ex_service', array( $this, 'mpcrbm_get_ex_service' ) );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_ex_service', array( $this, 'mpcrbm_get_ex_service' ) );
			}

			public function extra_service_meta() {
				$label = MPCRBM_Function::get_name();
				$extra_services_label = sprintf(
				// translators: %s represents the plugin version.
					__( '> Extra Services Settings <span class="version"> V%s</span>', 'car-rental-manager' ),
					MPCRBM_PLUGIN_VERSION
				);
				add_meta_box(
					'mpcrbm_meta_box_panel',
					$label . ' ' . $extra_services_label,
					array( $this, 'mpcrbm_extra_service' ),
					'mpcrbm_extra_services',
					'normal',
					'high'
				);
			}

			public function mpcrbm_extra_service() {
				$post_id        = get_the_id();
				$extra_services = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_extra_service_infos', array() );
				wp_nonce_field( 'mpcrbm_save_extra_service_nonce', 'mpcrbm_ex_service_nonce' );
				?>
                <div class="mpcrbm mpcrbm_settings">
                    <div class="tabsContent" style="width: 100%;">
                        <div class="mpcrbm_extra_service_settings tabsItem">
                            <h5><?php esc_html_e( 'Global Extra Service Settings', 'car-rental-manager' ); ?></h5>
                            <p><?php MPCRBM_Settings::info_text( 'mpcrbm_extra_services_global' ); ?></p>
                            <section class="bg-light">
                                <h6><?php esc_html_e( 'Extra Service Settings', 'car-rental-manager' ); ?></h6>
                                <span><?php esc_html_e( 'Here you can set price', 'car-rental-manager' ); ?></span>
                            </section>
                            <section>
                                <div class="settings_area">
                                    <form method="post" id="mpcrbm_extra_service_form">
                                        <div class="_ovAuto_mT_xs">
                                            <table>
                                                <thead>
                                                <tr>
                                                    <th><span><?php esc_html_e( 'Service Icon', 'car-rental-manager' ); ?></span></th>
                                                    <th><span><?php esc_html_e( 'Service Name', 'car-rental-manager' ); ?></span></th>
                                                    <th><span><?php esc_html_e( 'Short description', 'car-rental-manager' ); ?></span></th>
                                                    <th><span><?php esc_html_e( 'Service Price', 'car-rental-manager' ); ?></span></th>
                                                    <th><span><?php esc_html_e( 'Qty Box Type', 'car-rental-manager' ); ?></span></th>
                                                    <th><span><?php esc_html_e( 'Action', 'car-rental-manager' ); ?></span></th>
                                                </tr>
                                                </thead>
                                                <tbody class="sortable_area item_insert">
												<?php
													if ( $extra_services && is_array( $extra_services ) && sizeof( $extra_services ) > 0 ) {
														foreach ( $extra_services as $extra_service ) {
															$this->extra_service_item( $extra_service );
														}
													}
												?>
                                                </tbody>
                                            </table>
                                        </div>
										<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add Extra New Service', 'car-rental-manager' ) ); ?>
										<?php do_action( 'mpcrbm_hidden_table', 'mpcrbm_extra_service_item' ); ?>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
				<?php
			}

			public function extra_service_item( $field = array() ) {
				$field         = $field ?: array();
				$service_icon  = array_key_exists( 'service_icon', $field ) ? $field['service_icon'] : '';
				$service_name  = array_key_exists( 'service_name', $field ) ? $field['service_name'] : '';
				$service_price = array_key_exists( 'service_price', $field ) ? $field['service_price'] : '';
				$input_type    = array_key_exists( 'service_qty_type', $field ) ? $field['service_qty_type'] : 'inputbox';
				$description   = array_key_exists( 'extra_service_description', $field ) ? $field['extra_service_description'] : '';
				$icon          = $image = "";
				if ( $service_icon ) {
					if ( preg_match( '/\s/', $service_icon ) ) {
						$icon = $service_icon;
					} else {
						$image = $service_icon;
					}
				}
				?>
                <tr class="remove_area">
                    <td>
						<?php do_action( 'mpcrbm_add_icon_image', 'service_icon[]', $icon, $image ); ?>
                    </td>
                    <td class="text-center">
                        <input type="text" class="small name_validation" name="service_name[]" placeholder="<?php esc_attr_e( 'EX: Driver', 'car-rental-manager' ); ?>" value="<?php echo esc_attr( $service_name ); ?>"/>
                    </td>
                    <td>
                        <label>
                            <textarea rows="1" cols="5" class="" name="extra_service_description[]" placeholder="<?php esc_attr_e( 'EX: Description', 'car-rental-manager' ); ?>"><?php echo esc_html( $description ); ?></textarea>
                        </label>
                    </td>
                    <td class="text-center">
                        <input type="number" pattern="[0-9]*" step="0.01" class="small price_validation" name="service_price[]" placeholder="<?php esc_attr_e( 'EX: 10', 'car-rental-manager' ); ?>" value="<?php echo esc_attr( $service_price ); ?>"/>
                    </td>
                    <td>
                        <select name="service_qty_type[]" class='mideum'>
                            <option value="inputbox" <?php echo esc_attr( $input_type == 'inputbox' ? 'selected' : '' ); ?>><?php esc_html_e( 'Input Box', 'car-rental-manager' ); ?></option>
                            <option value="dropdown" <?php echo esc_attr( $input_type == 'dropdown' ? 'selected' : '' ); ?>><?php esc_html_e( 'Dropdown List', 'car-rental-manager' ); ?></option>
                        </select>
                    </td>
                    <td><?php MPCRBM_Custom_Layout::move_remove_button(); ?></td>
                </tr>
				<?php
			}

			public function save_ex_service_settings( $post_id ) {
				// Verify post_id is valid
				if ( ! $post_id || ! is_numeric( $post_id ) ) {
					return;
				}
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_ex_service_nonce'] ) ) {
					return;
				}
				// Unslash and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_ex_service_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_extra_service_nonce' ) ) {
					return;
				}
				// Verify user has permission to save
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
				// Handle both custom post type and regular post type
//				if ( get_post_type( $post_id ) == 'crm_extra_services' || get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
				if ( get_post_type( $post_id ) == 'mpcrbm_ex_services' || get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					// Save display setting
					$display = isset( $_POST['display_mpcrbm_extra_services'] ) ? 'on' : 'off';
					update_post_meta( $post_id, 'display_mpcrbm_extra_services', $display );
					// Save selected extra service
					if ( isset( $_POST['mpcrbm_extra_services_id'] ) ) {
						$service_id = sanitize_text_field( wp_unslash( $_POST['mpcrbm_extra_services_id'] ) );
						update_post_meta( $post_id, 'mpcrbm_extra_services_id', $service_id );
					}
					// Save extra service data if this is a custom service
//					if ( get_post_type( $post_id ) == 'crm_extra_services' || 1 ) {
					if ( get_post_type( $post_id ) == 'mpcrbm_ex_services' || 1 ) {
						$extra_service_data = $this->ex_service_data( $post_id );
						if ( ! empty( $extra_service_data ) ) {
							update_post_meta( $post_id, 'mpcrbm_extra_service_infos', $extra_service_data );
						}
					}
				}
			}

			//**************************************//
			public function ex_service_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_extra_service_nonce', 'mpcrbm_ex_service_nonce' );
				$display            = MPCRBM_Global_Function::get_post_info( $post_id, 'display_mpcrbm_extra_services', 'on' );
				$service_id         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_extra_services_id', $post_id );
				$active             = $display == 'off' ? '' : 'mActive';
				$checked            = $display == 'off' ? '' : 'checked';
				$all_ex_services_id = MPCRBM_Query::query_post_id( 'mpcrbm_ex_services' );
				?>
                <div class="tabsItem mpcrbm_extra_services_setting" data-tabs="#mpcrbm_settings_ex_service">
                    <h2><?php esc_html_e( 'On/Off Extra Service Settings', 'car-rental-manager' ); ?></h2>
                    <p><?php esc_html_e( 'Configure extra services for this vehicle', 'car-rental-manager' ); ?></p>
                    <section class="bg-light">
                        <h6><?php esc_html_e( 'Extra Service Options', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Enable or disable extra services and select predefined services', 'car-rental-manager' ); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Display Extra Services', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_extra_services' ); ?></span>
                            </div>
							<?php MPCRBM_Custom_Layout::switch_button( 'display_mpcrbm_extra_services', $checked ); ?>
                        </label>
                    </section>
                    <div data-collapse="#display_mpcrbm_extra_services" class="settings_area <?php echo esc_attr( $active ); ?>">
                        <section>
                            <label class="label">
                                <div>
                                    <h6><?php esc_html_e( 'Select extra option :', 'car-rental-manager' ); ?></h6>
                                    <span class="desc"><?php MPCRBM_Settings::info_text( 'mpcrbm_extra_services_id' ); ?></span>
                                </div>
                                <select class="formControl" name="mpcrbm_extra_services_id" id="mpcrbm_extra_services_select">
                                    <option value=""><?php esc_html_e( 'Select extra option', 'car-rental-manager' ); ?></option>
                                    <option value="<?php echo esc_attr( $post_id ); ?>" <?php selected( $service_id, $post_id ); ?>><?php esc_html_e( 'Custom', 'car-rental-manager' ); ?></option>
									<?php if ( sizeof( $all_ex_services_id ) > 0 ) { ?>
										<?php foreach ( $all_ex_services_id as $ex_services_id ) { ?>
                                            <option value="<?php echo esc_attr( $ex_services_id ); ?>" <?php selected( $service_id, $ex_services_id ); ?>><?php echo esc_html( get_the_title( $ex_services_id ) ); ?></option>
										<?php } ?>
									<?php } ?>
                                </select>
                            </label>
                        </section>
                        <div class="mpcrbm_extra_service_area">
							<?php $this->ex_service_table( $service_id, $post_id ); ?>
                        </div>
                    </div>
                </div>
				<?php
			}

			public function ex_service_table( $service_id, $post_id ) {
				if ( $service_id && $post_id ) {
					$extra_services = MPCRBM_Global_Function::get_post_info( $service_id, 'mpcrbm_extra_service_infos', [] );
					?>
                    <section>
                        <div>
                            <table class="mb-1">
                                <thead>
                                <tr>
                                    <th><span><?php esc_html_e( 'Icon', 'car-rental-manager' ); ?></span></th>
                                    <th><span><?php esc_html_e( 'Name', 'car-rental-manager' ); ?></span></th>
                                    <th><span><?php esc_html_e( 'Description', 'car-rental-manager' ); ?></span></th>
                                    <th><span><?php esc_html_e( 'Price', 'car-rental-manager' ); ?></span></th>
                                    <th><span><?php esc_html_e( 'Qty Box Type', 'car-rental-manager' ); ?></span></th>
                                    <th><span><?php esc_html_e( 'Action', 'car-rental-manager' ); ?></span></th>
                                </tr>
                                </thead>
                                <tbody class="sortable_area item_insert">
								<?php
									if ( sizeof( $extra_services ) > 0 ) {
										foreach ( $extra_services as $extra_service ) {
											$this->extra_service_item( $extra_service );
										}
									}
								?>
                                </tbody>
                            </table>
							<?php
								if ( $service_id == $post_id ) {
									MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add Extra New Service', 'car-rental-manager' ) );
									do_action( 'mpcrbm_hidden_table', 'mpcrbm_extra_service_item' );
								} ?>
                        </div>
                    </section>
					<?php
				}
			}

			public function ex_service_data( $post_id ) {
				// Verify post_id is valid
				if ( ! $post_id || ! is_numeric( $post_id ) ) {
					return array();
				}
				// Check if nonce is set and valid
				if ( ! isset( $_POST['mpcrbm_ex_service_nonce'] ) ||
				     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_ex_service_nonce'] ) ), 'mpcrbm_save_extra_service_nonce' ) ) {
					return array();
				}
				// Verify user has permission
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return array();
				}
				$new_extra_service = array();
				// Sanitize and validate all input arrays
				$extra_icon = isset( $_POST['service_icon'] ) ?
					array_map( 'sanitize_text_field', wp_unslash( $_POST['service_icon'] ) ) :
					array();
				$extra_names = isset( $_POST['service_name'] ) ?
					array_map( 'sanitize_text_field', wp_unslash( $_POST['service_name'] ) ) :
					array();
				$raw_prices = isset( $_POST['service_price'] ) ?
					array_map( 'sanitize_text_field', wp_unslash( $_POST['service_price'] ) ) :
					array();
				$extra_price = is_array( $raw_prices ) ? array_map( function ( $price ) {
					return is_numeric( $price ) ? abs( floatval( $price ) ) : 0;
				}, $raw_prices ) : array();
				$raw_qty_types = isset( $_POST['service_qty_type'] ) ?
					array_map( 'sanitize_text_field', wp_unslash( $_POST['service_qty_type'] ) ) :
					array();
				$extra_qty_type = is_array( $raw_qty_types ) ? array_map( function ( $type ) {
					return in_array( $type, array( 'inputbox', 'dropdown' ) ) ? $type : 'inputbox';
				}, $raw_qty_types ) : array();
				$extra_service_description = isset( $_POST['extra_service_description'] ) ?
					array_map( 'sanitize_textarea_field', wp_unslash( $_POST['extra_service_description'] ) ) :
					array();
				$extra_count = count( $extra_names );
				// Build sanitized array of services
				for ( $i = 0; $i < $extra_count; $i ++ ) {
					if ( ! empty( $extra_names[ $i ] ) ) {
						$new_extra_service[ $i ] = array(
							'service_icon'              => isset( $extra_icon[ $i ] ) ? $extra_icon[ $i ] : '',
							'service_name'              => $extra_names[ $i ],
							'service_price'             => isset( $extra_price[ $i ] ) ? $extra_price[ $i ] : 0,
							'service_qty_type'          => isset( $extra_qty_type[ $i ] ) ? $extra_qty_type[ $i ] : 'inputbox',
							'extra_service_description' => isset( $extra_service_description[ $i ] ) ? $extra_service_description[ $i ] : ''
						);
					}
				}

				return apply_filters( 'mpcrbm_filter_extra_service_data', $new_extra_service, $post_id );
			}

			public function mpcrbm_get_ex_service() {
				// Verify nonce
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mpcrbm_extra_service' ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Security check failed', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Validate and sanitize post_id
				$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
				if ( ! $post_id ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Verify user has permission
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Validate and sanitize service_id
				$service_id = isset( $_REQUEST['ex_id'] ) ? absint( $_REQUEST['ex_id'] ) : 0;
				if ( ! $service_id ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Invalid service ID', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Verify the service exists and is of correct type
				if ( ! get_post( $service_id ) || get_post_type( $service_id ) !== 'mpcrbm_ex_services' ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Invalid service', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Update the selected service ID
				update_post_meta( $post_id, 'mpcrbm_extra_services_id', $service_id );
				ob_start();
				$this->ex_service_table( $service_id, $post_id );
				$html = ob_get_clean();
				wp_send_json_success( array(
					'html'    => $html,
					'message' => esc_html__( 'Extra service updated successfully', 'car-rental-manager' )
				) );
				wp_die();
			}

		}
		new MPCRBM_Extra_Service();
	}
