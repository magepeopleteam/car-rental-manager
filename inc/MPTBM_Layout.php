<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPTBM_Layout')) {
		class MPTBM_Layout {
			public function __construct() {}
			public static function post_select() {
				$label = MPTBM_Function::mpcrm_get_name();
				?>
				<label class="min_400 mptbm_post_id">
					<select name="mptbm_id" class="formControl mp_select2" id="mptbm_post_id" required>
						<option value="" selected><?php esc_html_e('Select', 'car-rental-manager') . ' ' . esc_html($label); ?></option>
						<?php
							$post_query = MPCRM_Global_Function::query_post_type(MPTBM_Function::mpcrm_get_cpt());
							$all_posts = $post_query->posts;
							foreach ($all_posts as $post) {
								$post_id = $post->ID;
								$mptbm_id = MPTBM_Function::post_id_multi_language($post_id);
								if ($post_id == $mptbm_id) {
									//$price_based = MPCRM_Global_Function::mpcrm_get_post_info($post_id, 'mptbm_price_based');
									//$price_based_text = $price_based == 'manual' ? esc_html__('Manual', 'car-rental-manager') : esc_html__('Dynamic', 'car-rental-manager');
									?>
									<option value="<?php echo esc_attr($post_id); ?>">
										<?php echo esc_html(get_the_title($post_id)); ?>
										<?php //echo esc_html($price_based_text) ?>
									</option>
									<?php
								}
							}
							wp_reset_postdata();
						?>
					</select>
				</label>
				<?php
			}
			public static function msg($msg, $class = '') {
				?>
                <div class="_mZero_textCenter <?php echo esc_attr($class); ?>">
                    <label class="_textTheme"><?php echo esc_html($msg); ?></label>
                </div>
				<?php
			}
		}
		new MPTBM_Layout();
	}