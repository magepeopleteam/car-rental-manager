<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Layout')) {
		class MPCRBM_Layout {
			public function __construct() {}
			public static function post_select() {
				$label = MPCRBM_Function::get_name();
				?>
				<label class="min_400 mpcrbm_post_id">
					<select name="mpcrbm_id" class="formControl mpcrbm_select2" id="mpcrbm_post_id" required>
						<option value="" selected><?php esc_html_e('Select', 'car-rental-manager') . ' ' . esc_html($label); ?></option>
						<?php
							$post_query = MPCRBM_Global_Function::query_post_type(MPCRBM_Function::get_cpt());
							$all_posts = $post_query->posts;
							foreach ($all_posts as $post) {
								$post_id = $post->ID;
								$mpcrbm_id = MPCRBM_Function::post_id_multi_language($post_id);
								if ($post_id == $mpcrbm_id) {
									//$price_based = MPCRBM_Global_Function::get_post_info($post_id, 'mpcrbm_price_based');
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
		new MPCRBM_Layout();
	}