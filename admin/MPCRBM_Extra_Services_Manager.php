<?php
/*
 * @Author      MagePeople Team
 * Copyright:   mage-people.com
 *
 * In-shell "Extra Services" page — full custom list + repeater-table editor
 * for mpcrbm_ex_services posts, rendered inside MPCRBM_Admin_Shell instead
 * of linking out to the native post list/editor.
 *
 * The repeater table itself (icon/name/description/price/pricing type/qty
 * type per row, plus add-row/remove-row) is NOT reimplemented — it reuses
 * MPCRBM_Extra_Service::extra_service_item() for each row and
 * MPCRBM_Custom_Layout::add_new_button()/the "mpcrbm_hidden_table" action
 * for the add-row affordance, wrapped in the same "div.mpcrbm .settings_area
 * .item_insert" structure the native mpcrbm_ex_services edit screen uses —
 * so the existing global add-row/remove-row JS
 * (mp_global/assets/admin/mpcrbm_admin_settings.js, delegated on
 * "div.mpcrbm .add_item" / ".item_remove") continues to power the rows
 * inside the custom modal, and both surfaces always render identical fields.
 *
 * Saving goes through wp_insert_post()/wp_update_post() for the
 * mpcrbm_ex_services CPT, which fires save_post synchronously —
 * MPCRBM_Extra_Service::save_ex_service_settings() (already hooked to
 * save_post, and already checks the same mpcrbm_ex_service_nonce this page
 * also renders via wp_nonce_field()) reads service_name[]/service_price[]/
 * etc. straight from the same $_POST and saves mpcrbm_extra_service_infos
 * with no new meta-saving code needed here.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Extra_Services_Manager' ) ) {
	class MPCRBM_Extra_Services_Manager {

		const POST_TYPE = 'mpcrbm_ex_services';

		public function __construct() {
			add_action( 'admin_menu', [ $this, 'register_menu' ] );
			add_action( 'admin_post_mpcrbm_save_ex_service_group', [ $this, 'handle_save' ] );
			add_action( 'wp_ajax_mpcrbm_get_ex_service_group', [ $this, 'ajax_get_group' ] );
			add_action( 'wp_ajax_mpcrbm_delete_ex_service_group', [ $this, 'ajax_delete' ] );
			add_action( 'admin_post_mpcrbm_export_ex_services', [ $this, 'handle_export' ] );
		}

		public function register_menu() {
			$cpt = MPCRBM_Function::get_cpt();
			add_submenu_page(
				'edit.php?post_type=' . $cpt,
				esc_html__( 'Extra Services', 'car-rental-manager' ),
				esc_html__( 'Extra Services', 'car-rental-manager' ),
				'manage_options',
				'mpcrbm_ex_services_manager',
				[ $this, 'render_page' ]
			);
		}

		private function list_url(): string {
			return admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_ex_services_manager' );
		}

		private function edit_url( int $post_id = 0 ): string {
			$url = $this->list_url() . '&action=' . ( $post_id ? 'edit' : 'new' );
			return $post_id ? $url . '&id=' . $post_id : $url;
		}

		// ═══════════════════════════════════════════════════════════════════
		// PAGE
		// ═══════════════════════════════════════════════════════════════════

		public function render_page() {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view routing.
			$open_editor = in_array( $action, [ 'edit', 'new' ], true );
			$editor_id   = 'edit' === $action && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view routing.

			MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Extra Services', 'car-rental-manager' ) );
			$this->render_list( $open_editor, $editor_id );

			MPCRBM_Admin_Shell::render_shell_close();
		}

		private function notice_text( string $notice ): string {
			switch ( $notice ) {
				case 'created':
					return __( 'Service group created successfully!', 'car-rental-manager' );
				case 'updated':
					return __( 'Service group updated successfully!', 'car-rental-manager' );
				default:
					return __( 'Something went wrong. Please try again.', 'car-rental-manager' );
			}
		}

		/**
		 * Only counts rows that actually have a name — matches the guard
		 * MPCRBM_Extra_Service::ex_service_data() already applies on save
		 * (empty-named rows are dropped there), so a stray blank row left in
		 * older data doesn't inflate the "Items" counts shown here.
		 */
		private function named_rows( int $group_id ): array {
			$rows = get_post_meta( $group_id, 'mpcrbm_extra_service_infos', true );
			$rows = is_array( $rows ) ? $rows : [];
			return array_values( array_filter( $rows, static function ( $row ) {
				return ! empty( $row['service_name'] );
			} ) );
		}

		/**
		 * "Utilization" reads mpcrbm_extra_services_id on the vehicle CPT —
		 * the same meta MPCRBM_Extra_Service_Settings/ex_service_settings()
		 * save from the vehicle's own "Select extra option" dropdown — so it
		 * reflects the share of the published fleet that actually has an
		 * extra-service group assigned, not just how many groups exist.
		 */
		private function compute_stats( array $groups ): array {
			$active = 0;
			$items  = 0;

			foreach ( $groups as $group ) {
				if ( 'publish' === $group->post_status ) {
					$active++;
				}
				$items += count( $this->named_rows( $group->ID ) );
			}

			$car_cpt        = MPCRBM_Function::get_cpt();
			$total_vehicles = (int) ( wp_count_posts( $car_cpt )->publish ?? 0 );
			$enabled_count  = 0;

			if ( $total_vehicles > 0 ) {
				$enabled_ids   = get_posts( [
					'post_type'      => $car_cpt,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small admin-only fleet count, not a frontend query.
						[ 'key' => 'mpcrbm_extra_services_id', 'value' => '', 'compare' => '!=' ],
					],
				] );
				$enabled_count = count( $enabled_ids );
			}

			return [
				'active'      => $active,
				'items'       => $items,
				'utilization' => $total_vehicles > 0 ? (int) round( $enabled_count / $total_vehicles * 100 ) : 0,
			];
		}

		private function render_list( bool $open_editor = false, int $editor_id = 0 ) {
			$groups = get_posts( [
				'post_type'      => self::POST_TYPE,
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect flag, not a state-changing request.
			$notice = isset( $_GET['mpcrbm_notice'] ) ? sanitize_key( wp_unslash( $_GET['mpcrbm_notice'] ) ) : '';
			$stats  = $this->compute_stats( $groups );
			?>
			<div class="mpcrbm-ex-services-manager">

				<?php if ( $notice ) : ?>
					<div class="mpcrbm-ex-services-notice mpcrbm-ex-services-notice-<?php echo esc_attr( $notice ); ?>"><?php echo esc_html( $this->notice_text( $notice ) ); ?></div>
				<?php endif; ?>

				<div class="mpcrbm-ex-services-head">
					<div class="mpcrbm-ex-services-head-text">
						<span class="mpcrbm-ex-services-head-eyebrow"><?php esc_html_e( 'Configuration', 'car-rental-manager' ); ?></span>
						<h2><?php esc_html_e( 'Extra Services', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-ex-services-head-subtitle"><?php esc_html_e( 'Manage premium add-ons and complimentary offerings for your fleet. Define GPS units, seasonal equipment, and hospitality items to enhance the rental experience.', 'car-rental-manager' ); ?></p>
					</div>
					<div class="mpcrbm-ex-services-head-actions">
						<a href="<?php echo esc_url( $this->edit_url() ); ?>" class="mpcrbm-ex-services-add-btn mpcrbm-ex-service-open-modal" data-post-id="0">
							<i class="mi mi-plus"></i> <?php esc_html_e( 'Add Service Group', 'car-rental-manager' ); ?>
						</a>
					</div>
				</div>

				<?php if ( ! empty( $groups ) ) : ?>
					<div class="mpcrbm_analytics mpcrbm-ex-services-stats">
						<div class="mpcrbm_stat-card mpcrbm-ex-services-stat groups">
							<div class="mpcrbm_stat-left">
								<i class="mi mi-archive"></i>
								<div>
									<div class="mpcrbm_stat-label"><?php esc_html_e( 'Active Groups', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm_stat-value"><?php echo esc_html( $stats['active'] ); ?></div>
								</div>
							</div>
						</div>

						<div class="mpcrbm_stat-card mpcrbm-ex-services-stat items">
							<div class="mpcrbm_stat-left">
								<i class="mi mi-check-circle"></i>
								<div>
									<div class="mpcrbm_stat-label"><?php esc_html_e( 'Total Items', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm_stat-value"><?php echo esc_html( $stats['items'] ); ?></div>
								</div>
							</div>
						</div>

						<div class="mpcrbm_stat-card mpcrbm-ex-services-stat mpcrbm-ex-stat-utilization">
							<div class="mpcrbm_stat-left">
								<i class="mi mi-chart-pie"></i>
								<div>
									<div class="mpcrbm_stat-label"><?php esc_html_e( 'Utilization', 'car-rental-manager' ); ?></div>
									<div class="mpcrbm_stat-value"><?php echo esc_html( $stats['utilization'] ); ?>%</div>
								</div>
							</div>
							<span class="mpcrbm_stat-change mpcrbm-ex-stat-ring" style="--pct: <?php echo (int) $stats['utilization']; ?>;" title="<?php esc_attr_e( 'Share of published vehicles with an extra-service group assigned', 'car-rental-manager' ); ?>" aria-hidden="true"></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( empty( $groups ) ) : ?>
					<div class="mpcrbm-ex-services-empty">
						<i class="mi mi-list-timeline"></i>
						<p><?php esc_html_e( 'No extra service groups yet', 'car-rental-manager' ); ?></p>
						<span><?php esc_html_e( 'Click "Add Service Group" to define your first set of add-on services.', 'car-rental-manager' ); ?></span>
					</div>
				<?php else : ?>
					<div class="mpcrbm-ex-services-grid">
						<?php foreach ( $groups as $group ) :
							$rows          = $this->named_rows( $group->ID );
							$preview_names = wp_list_pluck( array_slice( $rows, 0, 3 ), 'service_name' );
							$extra_count   = max( 0, count( $rows ) - 3 );
							$is_publish    = 'publish' === $group->post_status;
							?>
							<div class="mpcrbm-ex-service-card" data-post-id="<?php echo esc_attr( $group->ID ); ?>">
								<div class="mpcrbm-ex-service-card-top">
									<span class="mpcrbm-ex-service-avatar">
										<i class="mi mi-list-timeline"></i>
										<span class="mpcrbm-ex-service-status-dot <?php echo $is_publish ? 'is-active' : 'is-draft'; ?>" title="<?php echo esc_attr( $is_publish ? __( 'Published', 'car-rental-manager' ) : __( 'Draft', 'car-rental-manager' ) ); ?>"></span>
									</span>
									<span class="mpcrbm-ex-service-count-badge"><?php echo esc_html( count( $rows ) ); ?> <?php esc_html_e( 'Items', 'car-rental-manager' ); ?></span>
								</div>
								<strong class="mpcrbm-ex-service-name"><?php echo esc_html( get_the_title( $group ) ); ?></strong>
								<?php if ( ! empty( $preview_names ) ) : ?>
									<small class="mpcrbm-ex-service-desc"><?php
										/* translators: %s: comma-separated list of item names in this group */
										printf( esc_html__( 'Includes %s', 'car-rental-manager' ), esc_html( implode( ', ', $preview_names ) ) );
									?></small>
									<div class="mpcrbm-ex-service-tags">
										<?php foreach ( $rows as $i => $row ) : ?>
											<span class="mpcrbm-ex-service-tag<?php echo $i >= 3 ? ' mpcrbm-ex-service-tag--extra' : ''; ?>"><?php echo esc_html( $row['service_name'] ); ?></span>
										<?php endforeach; ?>
										<?php if ( $extra_count > 0 ) : ?>
											<span class="mpcrbm-ex-service-tag mpcrbm-ex-service-tag--more">+<?php echo esc_html( $extra_count ); ?> <?php esc_html_e( 'more', 'car-rental-manager' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<div class="mpcrbm-ex-service-actions">
									<?php if ( $extra_count > 0 ) : ?>
										<button type="button" class="mpcrbm-ex-service-view-btn" title="<?php esc_attr_e( 'Show all items', 'car-rental-manager' ); ?>"><i class="mi mi-eye"></i></button>
									<?php endif; ?>
									<a href="<?php echo esc_url( $this->edit_url( $group->ID ) ); ?>" class="mpcrbm-ex-service-edit-btn mpcrbm-ex-service-open-modal" data-post-id="<?php echo esc_attr( $group->ID ); ?>"><i class="mi mi-pencil"></i> <?php esc_html_e( 'Edit Group', 'car-rental-manager' ); ?></a>
									<button type="button" class="mpcrbm-ex-service-delete-btn" data-post-id="<?php echo esc_attr( $group->ID ); ?>"><i class="mi mi-trash"></i></button>
								</div>
							</div>
						<?php endforeach; ?>

						<a href="<?php echo esc_url( $this->edit_url() ); ?>" class="mpcrbm-ex-service-card-add mpcrbm-ex-service-open-modal" data-post-id="0">
							<span class="mpcrbm-ex-service-card-add-icon"><i class="mi mi-plus"></i></span>
							<?php esc_html_e( 'Create New Category', 'car-rental-manager' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<?php $this->render_editor_modal( $open_editor, $editor_id ); ?>
			</div>
			<?php
		}

		private function render_editor_modal( bool $open_by_default = false, int $post_id = 0 ) {
			$post    = $post_id ? get_post( $post_id ) : null;
			$is_edit = $post && self::POST_TYPE === $post->post_type;
			$post_id = $is_edit ? $post_id : 0;
			$title   = $is_edit ? $post->post_title : '';
			$rows    = $is_edit ? get_post_meta( $post_id, 'mpcrbm_extra_service_infos', true ) : [];
			$rows    = is_array( $rows ) && ! empty( $rows ) ? $rows : [ [] ];
			?>
			<div class="mpcrbm-ex-service-modal-overlay<?php echo $open_by_default ? ' is-open' : ''; ?>"
				 id="mpcrbm_ex_service_modal"
				 data-auto-open="<?php echo $open_by_default ? '1' : '0'; ?>"
				 data-initial-post-id="<?php echo esc_attr( $post_id ); ?>"
				 aria-hidden="<?php echo $open_by_default ? 'false' : 'true'; ?>">
				<div class="mpcrbm-ex-service-modal" role="dialog" aria-modal="true" aria-labelledby="mpcrbm_ex_service_modal_title">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mpcrbm mpcrbm_settings mpcrbm-ex-service-modal-form" id="mpcrbm_ex_service_modal_form">
						<input type="hidden" name="action" value="mpcrbm_save_ex_service_group">
						<input type="hidden" name="post_id" id="mpcrbm_ex_service_post_id" value="<?php echo esc_attr( $post_id ); ?>">
						<?php wp_nonce_field( 'mpcrbm_save_extra_service_nonce', 'mpcrbm_ex_service_nonce' ); ?>

						<div class="mpcrbm-ex-service-modal-header">
							<div class="mpcrbm-ex-service-modal-heading">
								<span class="mpcrbm-ex-service-modal-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span>
								<div>
									<span class="mpcrbm-ex-service-modal-eyebrow"><?php echo $is_edit ? esc_html__( 'Update Offering', 'car-rental-manager' ) : esc_html__( 'New Offering', 'car-rental-manager' ); ?></span>
									<h2 id="mpcrbm_ex_service_modal_title"><?php echo $is_edit ? esc_html__( 'Edit Service Group', 'car-rental-manager' ) : esc_html__( 'Add Service Group', 'car-rental-manager' ); ?></h2>
									<p><?php esc_html_e( 'Build a reusable collection of optional services for your rental fleet.', 'car-rental-manager' ); ?></p>
								</div>
							</div>
							<button type="button" class="mpcrbm-ex-service-modal-close" aria-label="<?php esc_attr_e( 'Close service group form', 'car-rental-manager' ); ?>">&times;</button>
						</div>

						<div class="mpcrbm-ex-service-modal-body">
							<div class="mpcrbm-ex-service-modal-alert" role="alert" hidden></div>

							<section class="mpcrbm-ex-service-modal-section" aria-labelledby="mpcrbm_ex_service_identity_heading">
								<div class="mpcrbm-ex-service-modal-section-heading">
									<i class="fas fa-tag" aria-hidden="true"></i>
									<div>
										<h3 id="mpcrbm_ex_service_identity_heading"><?php esc_html_e( 'Group Identity', 'car-rental-manager' ); ?></h3>
										<p><?php esc_html_e( 'Give this service collection a clear, recognizable name.', 'car-rental-manager' ); ?></p>
									</div>
								</div>
								<div class="mpcrbm-ex-service-modal-field">
									<label for="mpcrbm_ex_service_group_name"><?php esc_html_e( 'Service Group Name', 'car-rental-manager' ); ?> <span>*</span></label>
									<input type="text" id="mpcrbm_ex_service_group_name" name="mpcrbm_ex_service_group_name" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g. Standard Add-ons', 'car-rental-manager' ); ?>" required>
								</div>
							</section>

							<section class="mpcrbm-ex-service-modal-section" aria-labelledby="mpcrbm_ex_service_items_heading">
								<div class="mpcrbm-ex-service-modal-section-heading">
									<i class="fas fa-list-ul" aria-hidden="true"></i>
									<div>
										<h3 id="mpcrbm_ex_service_items_heading"><?php esc_html_e( 'Service Items', 'car-rental-manager' ); ?></h3>
										<p><?php esc_html_e( 'Set each item’s icon, price, pricing basis, and quantity control.', 'car-rental-manager' ); ?></p>
									</div>
								</div>

								<div class="settings_area mpcrbm-ex-service-modal-settings">
									<div class="mpcrbm-ex-service-modal-loading" role="status" aria-live="polite" hidden><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <span><?php esc_html_e( 'Loading service group…', 'car-rental-manager' ); ?></span></div>
									<div class="_ovAuto_mT_xs mpcrbm-ex-service-modal-table-wrap">
										<table class="mpcrbm-ex-service-table widefat">
											<thead>
											<tr>
												<th><span><?php esc_html_e( 'Icon', 'car-rental-manager' ); ?></span></th>
												<th><span><?php esc_html_e( 'Service Name', 'car-rental-manager' ); ?></span></th>
												<th><span><?php esc_html_e( 'Price ($)', 'car-rental-manager' ); ?></span></th>
												<th><span><?php esc_html_e( 'Pricing Type', 'car-rental-manager' ); ?></span></th>
												<th><span><?php esc_html_e( 'Qty Control', 'car-rental-manager' ); ?></span></th>
												<th><span><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></span></th>
											</tr>
											</thead>
											<tbody class="sortable_area item_insert">
												<?php foreach ( $rows as $row ) : ?>
													<?php MPCRBM_Extra_Service::extra_service_item( $row ); ?>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
									<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add Additional Service Row', 'car-rental-manager' ) ); ?>
									<?php do_action( 'mpcrbm_hidden_table', 'mpcrbm_extra_service_item' ); ?>
								</div>
							</section>
						</div>

						<div class="mpcrbm-ex-service-modal-footer">
							<button type="button" class="mpcrbm-ex-service-modal-cancel"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
							<button type="submit" class="mpcrbm-ex-service-modal-submit" data-create-text="<?php esc_attr_e( 'Create Service Group', 'car-rental-manager' ); ?>" data-update-text="<?php esc_attr_e( 'Update Service Group', 'car-rental-manager' ); ?>" data-loading-text="<?php esc_attr_e( 'Saving…', 'car-rental-manager' ); ?>">
								<i class="fas fa-save" aria-hidden="true"></i>
								<span><?php echo $is_edit ? esc_html__( 'Update Service Group', 'car-rental-manager' ) : esc_html__( 'Create Service Group', 'car-rental-manager' ); ?></span>
							</button>
						</div>
					</form>
				</div>
			</div>
			<?php
		}

		public function ajax_get_group() {
			check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$post    = $post_id ? get_post( $post_id ) : null;
			if ( ! $post || self::POST_TYPE !== $post->post_type ) {
				wp_send_json_error( [ 'message' => __( 'Invalid service group.', 'car-rental-manager' ) ], 400 );
			}

			$rows = get_post_meta( $post_id, 'mpcrbm_extra_service_infos', true );
			$rows = is_array( $rows ) && ! empty( $rows ) ? $rows : [ [] ];

			ob_start();
			foreach ( $rows as $row ) {
				MPCRBM_Extra_Service::extra_service_item( $row );
			}

			wp_send_json_success( [
				'postId'   => $post_id,
				'title'    => $post->post_title,
				'rowsHtml' => ob_get_clean(),
			] );
		}

		// ═══════════════════════════════════════════════════════════════════
		// SAVE / DELETE
		// ═══════════════════════════════════════════════════════════════════

		public function handle_save() {
			check_admin_referer( 'mpcrbm_save_extra_service_nonce', 'mpcrbm_ex_service_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'car-rental-manager' ) );
			}

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$name    = isset( $_POST['mpcrbm_ex_service_group_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_ex_service_group_name'] ) ) : '';

			if ( '' === $name ) {
				$name = __( 'Untitled Service Group', 'car-rental-manager' );
			}

			$postarr = [
				'post_type'   => self::POST_TYPE,
				'post_title'  => $name,
				'post_status' => 'publish',
			];

			$is_update = $post_id && get_post_type( $post_id ) === self::POST_TYPE;
			if ( $is_update ) {
				$postarr['ID'] = $post_id;
				$result_id     = wp_update_post( $postarr, true );
			} else {
				$result_id = wp_insert_post( $postarr, true );
			}

			$redirect_args = [
				'post_type' => MPCRBM_Function::get_cpt(),
				'page'      => 'mpcrbm_ex_services_manager',
			];

			// wp_insert_post()/wp_update_post() above already fired save_post
			// synchronously — MPCRBM_Extra_Service::save_ex_service_settings()
			// has already saved mpcrbm_extra_service_infos from this same
			// $_POST by the time we get here (see class docblock).
			$redirect_args['mpcrbm_notice'] = is_wp_error( $result_id ) ? 'error' : ( $is_update ? 'updated' : 'created' );

			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
			exit;
		}

		public function handle_export() {
			check_admin_referer( 'mpcrbm_export_ex_services' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'car-rental-manager' ) );
			}

			$groups = get_posts( [
				'post_type'      => self::POST_TYPE,
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="extra-services-' . gmdate( 'Y-m-d' ) . '.csv"' );

			$out = fopen( 'php://output', 'w' );
			fputcsv( $out, [ 'Service Group', 'Status', 'Item Name', 'Description', 'Price', 'Pricing Type', 'Qty Control' ] );

			foreach ( $groups as $group ) {
				$rows = $this->named_rows( $group->ID );

				if ( empty( $rows ) ) {
					fputcsv( $out, [ $group->post_title, $group->post_status, '', '', '', '', '' ] );
					continue;
				}

				foreach ( $rows as $row ) {
					fputcsv( $out, [
						$group->post_title,
						$group->post_status,
						$row['service_name'] ?? '',
						$row['extra_service_description'] ?? '',
						$row['service_price'] ?? '',
						$row['service_price_type'] ?? '',
						$row['service_qty_type'] ?? '',
					] );
				}
			}

			fclose( $out );
			exit;
		}

		public function ajax_delete() {
			check_ajax_referer( 'mpcrbm_extra_service', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			if ( ! $post_id || get_post_type( $post_id ) !== self::POST_TYPE ) {
				wp_send_json_error( [ 'message' => __( 'Invalid service group.', 'car-rental-manager' ) ], 400 );
			}

			$result = wp_trash_post( $post_id );
			if ( ! $result ) {
				wp_send_json_error( [ 'message' => __( 'Unable to delete service group.', 'car-rental-manager' ) ], 400 );
			}

			wp_send_json_success( [ 'message' => __( 'Service group deleted.', 'car-rental-manager' ) ] );
		}
	}

	new MPCRBM_Extra_Services_Manager();
}
