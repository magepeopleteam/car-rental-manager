<?php
	/**
	 * @author Sahahdat Hossain <raselsha@gmail.com>
	 * @license mage-people.com
	 * @var 1.0.0
	 */
	defined('ABSPATH') || die;

	if (!class_exists('MPCRBM_Faq_Settings')) {
		class MPCRBM_Faq_Settings {
			public function __construct() {
				add_action('mpcrbm_settings_tab_content', [$this, 'faq_settings']);
				add_action('admin_enqueue_scripts', [$this, 'my_custom_editor_enqueue']);
				// save faq data
				add_action('wp_ajax_mpcrbm_faq_data_save', [$this, 'save_faq_data_settings']);
				add_action('wp_ajax_nopriv_mpcrbm_faq_data_save', [$this, 'save_faq_data_settings']);
				// update faq data
				add_action('wp_ajax_mpcrbm_faq_data_update', [$this, 'faq_data_update']);
				add_action('wp_ajax_nopriv_mpcrbm_faq_data_update', [$this, 'faq_data_update']);
				// mpcrbm_delete_faq_data
				add_action('wp_ajax_mpcrbm_faq_delete_item', [$this, 'faq_delete_item']);
				add_action('wp_ajax_nopriv_mpcrbm_faq_delete_item', [$this, 'faq_delete_item']);
				// FAQ sort_faq
				add_action('wp_ajax_mpcrbm_sort_faq', [$this, 'sort_faq']);

                add_action( 'save_post', [ $this, 'save_general_settings' ] );
			}

            public function save_general_settings( $post_id ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_nonce'] ) ) {
					return;
				};
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_general_settings' ) ) {
					return;
				};
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
                    $faq_active       = isset( $_POST['mpcrbm_faq_active'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_faq_active'] ) ) : 'off';
                    update_post_meta($post_id, 'mpcrbm_faq_active', $faq_active);
                    	
                }
            }

			public function sort_faq() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpcrbm_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? sanitize_text_field(wp_unslash($_POST['postID'])) : '';
				$sorted_ids = isset($_POST['sortedIDs']) ? array_map('intval', $_POST['sortedIDs']) : [];
				$mpcrbm_faq = get_post_meta($post_id, 'mpcrbm_faq', true);;
				$new_ordered = [];
				foreach ($sorted_ids as $id) {
					if (isset($mpcrbm_faq[$id])) {
						$new_ordered[$id] = $mpcrbm_faq[$id];
					}
				}
				update_post_meta($post_id, 'mpcrbm_faq', $new_ordered);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'car-rental-manager');
				$this->show_faq_data($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}

			public function my_custom_editor_enqueue() {
				// Enqueue necessary scripts
				wp_enqueue_script('jquery');
				wp_enqueue_script('editor');
				wp_enqueue_script('media-upload');
				wp_enqueue_script('thickbox');
				wp_enqueue_style('thickbox');
			}
			public function faq_settings($post_id) {
				$mpcrbm_faq_active = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_faq_active', 'off');
				$active_class = $mpcrbm_faq_active == 'on' ? 'mActive' : '';
				$mpcrbm_faq_active_checked = $mpcrbm_faq_active == 'on' ? 'checked' : '';
				?>
                <div class="tabsItem" data-tabs="#mpcrbm_setting_manage_faq">
                    
                    <h2><?php esc_html_e('FAQ Settings', 'car-rental-manager'); ?></h2>
                    <p><?php esc_html_e('FAQ Settings will be here.', 'car-rental-manager'); ?></p>
                    <section class="bg-light">
                        <h6><?php esc_html_e( 'Manage FAQ', 'car-rental-manager' ); ?></h6>
                        <span><?php esc_html_e( 'Configure and manage faq', 'car-rental-manager' ); ?></span>
                    </section>
 
                    <section>
                        <div class="label">
                            <div>
                                <h6><?php esc_html_e('Enable FAQ Section', 'car-rental-manager'); ?></h6>
                                <span><?php esc_html_e('Enable FAQ Section', 'car-rental-manager'); ?></span>
                            </div>
                            <div>
								<?php MPCRBM_Custom_Layout::switch_button('mpcrbm_faq_active', $mpcrbm_faq_active_checked); ?>
                            </div>
                        </div>
                    </section>
                    <section class="mpcrbm-faq-section <?php echo esc_attr($active_class); ?>" data-collapse="#mpcrbm_faq_active">
                        <div class="mpcrbm-faq-items mB">
							<?php $this->show_faq_data($post_id); ?>
                        </div>
                        <button class="button mpcrbm-faq-item-new" data-modal="mpcrbm-faq-item-new" type="button"><?php esc_html_e('Add FAQ', 'car-rental-manager'); ?></button>
                    </section>
                    <!-- sidebar collapse open -->
                    <div class="mpcrbm-modal-container" data-modal-target="mpcrbm-faq-item-new">
                        <div class="mpcrbm-modal-content">
                            <span class="mpcrbm-modal-close"><i class="fas fa-times"></i></span>
                            <div class="title">
                                <h3><?php esc_html_e('Add F.A.Q.', 'car-rental-manager'); ?></h3>
                                <div id="mpcrbm-service-msg"></div>
                            </div>
                            <div class="content">
                                <label>
									<?php esc_html_e('Add Title', 'car-rental-manager'); ?>
                                    <input type="hidden" name="mpcrbm_post_id" value="<?php echo esc_attr($post_id); ?>">
                                    <input type="text" name="mpcrbm_faq_title">
                                    <input type="hidden" name="mpcrbm_faq_item_id">
                                </label>
                                <label>
									<?php esc_html_e('Add Content', 'car-rental-manager'); ?>
                                </label>
								<?php
									$content = '';
									$editor_id = 'mpcrbm_faq_content';
									$settings = array(
										'textarea_name' => 'mpcrbm_faq_content',
										'media_buttons' => true,
										'textarea_rows' => 10,
									);
									wp_editor($content, $editor_id, $settings);
								?>
                                <div class="mT"></div>
                                <div class="mpcrbm_faq_save_buttons">
                                    <p>
                                        <button id="mpcrbm_faq_save" class="button button-primary button-large"><?php esc_html_e('Save', 'car-rental-manager'); ?></button>
                                        <button id="mpcrbm_faq_save_close" class="button button-primary button-large">save close</button>
                                    <p>
                                </div>
                                <div class="mpcrbm_faq_update_buttons" style="display: none;">
                                    <p>
                                        <button id="mpcrbm_faq_update" class="button button-primary button-large"><?php esc_html_e('Update and Close', 'car-rental-manager'); ?></button>
                                    <p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				<?php
			}
			public function show_faq_data($post_id) {
				$mpcrbm_faq = get_post_meta($post_id, 'mpcrbm_faq', true);
				if (!empty($mpcrbm_faq)):
					foreach ($mpcrbm_faq as $key => $value) :
						?>
                        <div class="mpcrbm-faq-item" data-id="<?php echo esc_attr($key); ?>">
                            <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($key); ?>">
                                <label class="label">
                                    <p><?php echo esc_html($value['title']); ?></p>
                                    <div class="faq-action">
                                        <span class=""><i class="fas fa-eye"></i></span>
                                        <span class="mpcrbm-faq-item-edit" data-modal="mpcrbm-faq-item-new"><i class="fas fa-edit"></i></span>
                                        <span class="mpcrbm-faq-item-delete"><i class="fas fa-trash"></i></span>
                                    </div>
                                </label>
                            </section>
                            <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($key); ?>">
								<?php echo wp_kses_post($value['content']); ?>
                            </section>
                        </div>
					<?php
					endforeach;
				endif;
			}
			public function faq_data_update() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpcrbm_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpcrbm_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_postID'])) : '';
				$mpcrbm_faq_title = isset($_POST['mpcrbm_faq_title']) ? sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_title'])) : '';
				$mpcrbm_faq_content = isset($_POST['mpcrbm_faq_content']) ? wp_kses_post(wp_unslash($_POST['mpcrbm_faq_content'])) : '';
				$mpcrbm_faq = get_post_meta($post_id, 'mpcrbm_faq', true);
				$mpcrbm_faq = !empty($mpcrbm_faq) ? $mpcrbm_faq : [];
				$new_data = ['title' => $mpcrbm_faq_title, 'content' => $mpcrbm_faq_content];
				if (!empty($mpcrbm_faq)) {
					if (isset($_POST['mpcrbm_faq_itemID'])) {
						$mpcrbm_faq[sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_itemID']))] = $new_data;
					}
				}
				update_post_meta($post_id, 'mpcrbm_faq', $mpcrbm_faq);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'car-rental-manager');
				$this->show_faq_data($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function save_faq_data_settings() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpcrbm_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpcrbm_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_postID'])) : '';
				update_post_meta($post_id, 'mpcrbm_faq_active', 'on');
				$mpcrbm_faq_title = isset($_POST['mpcrbm_faq_title']) ? sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_title'])) : '';
				$mpcrbm_faq_content = isset($_POST['mpcrbm_faq_content']) ? wp_kses_post(wp_unslash($_POST['mpcrbm_faq_content'])) : '';
				$mpcrbm_faq = get_post_meta($post_id, 'mpcrbm_faq', true);
				$mpcrbm_faq = !empty($mpcrbm_faq) ? $mpcrbm_faq : [];
				$new_data = ['title' => $mpcrbm_faq_title, 'content' => $mpcrbm_faq_content];
				if (isset($post_id)) {
					array_push($mpcrbm_faq, $new_data);
				}
				$result = update_post_meta($post_id, 'mpcrbm_faq', $mpcrbm_faq);
				if ($result) {
					ob_start();
					$resultMessage = esc_html__('Data Added Successfully', 'car-rental-manager');
					$this->show_faq_data($post_id);
					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not inserted',
						'html' => 'error',
					]);
				}
				die;
			}
			public function faq_delete_item() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpcrbm_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpcrbm_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpcrbm_faq_postID'])) : '';
				$mpcrbm_faq = get_post_meta($post_id, 'mpcrbm_faq', true);
				$mpcrbm_faq = !empty($mpcrbm_faq) ? $mpcrbm_faq : [];
				if (!empty($mpcrbm_faq)) {
					if (isset($_POST['itemId'])) {
						unset($mpcrbm_faq[sanitize_text_field(wp_unslash($_POST['itemId']))]);
						$mpcrbm_faq = array_values($mpcrbm_faq);
					}
				}
				$result = update_post_meta($post_id, 'mpcrbm_faq', $mpcrbm_faq);
				if ($result) {
					ob_start();
					$resultMessage = esc_html__('Data Deleted Successfully', 'car-rental-manager');
					$this->show_faq_data($post_id);
					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not inserted',
						'html' => '',
					]);
				}
				die;
			}
		}
		new MPCRBM_Faq_Settings();
	}