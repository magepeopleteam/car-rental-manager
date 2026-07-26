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
 * "div.mpcrbm .add_item" / ".item_remove") works here with no new JS, and
 * both surfaces always render identical rows.
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
			add_action( 'wp_ajax_mpcrbm_delete_ex_service_group', [ $this, 'ajax_delete' ] );
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

			MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Extra Services', 'car-rental-manager' ) );

			if ( 'edit' === $action || 'new' === $action ) {
				$this->render_editor();
			} else {
				$this->render_list();
			}

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

		private function render_list() {
			$groups = get_posts( [
				'post_type'      => self::POST_TYPE,
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect flag, not a state-changing request.
			$notice = isset( $_GET['mpcrbm_notice'] ) ? sanitize_key( wp_unslash( $_GET['mpcrbm_notice'] ) ) : '';
			?>
			<div class="mpcrbm-ex-services-manager">

				<?php if ( $notice ) : ?>
					<div class="mpcrbm-ex-services-notice mpcrbm-ex-services-notice-<?php echo esc_attr( $notice ); ?>"><?php echo esc_html( $this->notice_text( $notice ) ); ?></div>
				<?php endif; ?>

				<div class="mpcrbm-ex-services-head">
					<div class="mpcrbm-ex-services-head-text">
						<h2><?php esc_html_e( 'Extra Services', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-ex-services-head-subtitle"><?php esc_html_e( 'Define add-on services — GPS, child seat, insurance, and more — grouped into sets you can attach to any vehicle.', 'car-rental-manager' ); ?></p>
					</div>
					<a href="<?php echo esc_url( $this->edit_url() ); ?>" class="mpcrbm-ex-services-add-btn">
						<i class="mi mi-plus"></i> <?php esc_html_e( 'Add Service Group', 'car-rental-manager' ); ?>
					</a>
				</div>

				<?php if ( empty( $groups ) ) : ?>
					<div class="mpcrbm-ex-services-empty">
						<i class="mi mi-list-timeline"></i>
						<p><?php esc_html_e( 'No extra service groups yet', 'car-rental-manager' ); ?></p>
						<span><?php esc_html_e( 'Click "Add Service Group" to define your first set of add-on services.', 'car-rental-manager' ); ?></span>
					</div>
				<?php else : ?>
					<div class="mpcrbm-ex-services-grid">
						<?php foreach ( $groups as $group ) :
							$rows = get_post_meta( $group->ID, 'mpcrbm_extra_service_infos', true );
							$rows = is_array( $rows ) ? $rows : [];
							$preview_names = wp_list_pluck( array_slice( $rows, 0, 3 ), 'service_name' );
							?>
							<div class="mpcrbm-ex-service-card" data-post-id="<?php echo esc_attr( $group->ID ); ?>">
								<div class="mpcrbm-ex-service-card-top">
									<span class="mpcrbm-ex-service-avatar"><i class="mi mi-list-timeline"></i></span>
									<span class="mpcrbm-ex-service-count-badge"><?php echo esc_html( count( $rows ) ); ?> <?php esc_html_e( 'Items', 'car-rental-manager' ); ?></span>
								</div>
								<strong class="mpcrbm-ex-service-name"><?php echo esc_html( get_the_title( $group ) ); ?></strong>
								<?php if ( ! empty( $preview_names ) ) : ?>
									<small class="mpcrbm-ex-service-desc"><?php echo esc_html( implode( ', ', $preview_names ) ); ?></small>
								<?php endif; ?>
								<div class="mpcrbm-ex-service-actions">
									<a href="<?php echo esc_url( $this->edit_url( $group->ID ) ); ?>" class="mpcrbm-ex-service-edit-btn"><i class="mi mi-pencil"></i> <?php esc_html_e( 'Edit', 'car-rental-manager' ); ?></a>
									<button type="button" class="mpcrbm-ex-service-delete-btn" data-post-id="<?php echo esc_attr( $group->ID ); ?>"><i class="mi mi-trash"></i></button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		private function render_editor() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view routing.
			$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			$post    = $post_id ? get_post( $post_id ) : null;
			$is_edit = $post && self::POST_TYPE === $post->post_type;
			$title   = $is_edit ? $post->post_title : '';
			$rows    = $is_edit ? get_post_meta( $post_id, 'mpcrbm_extra_service_infos', true ) : [];
			$rows    = is_array( $rows ) ? $rows : [];
			?>
			<div class="mpcrbm-ex-services-manager">
				<div class="mpcrbm-ex-services-head mpcrbm-ex-services-head--editor">
					<div class="mpcrbm-ex-services-head-text">
						<h2><?php echo $is_edit ? esc_html__( 'Edit Service Group', 'car-rental-manager' ) : esc_html__( 'Add Service Group', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-ex-services-head-subtitle"><?php esc_html_e( 'Each row represents a bespoke add-on available during the client checkout process. Define the visual identity, fiscal value, and quantity mechanics for each premium offering.', 'car-rental-manager' ); ?></p>
					</div>
					<a href="<?php echo esc_url( $this->list_url() ); ?>" class="mpcrbm-ex-services-back-btn">
						<i class="mi mi-arrow-left"></i> <?php esc_html_e( 'Back to List', 'car-rental-manager' ); ?>
					</a>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mpcrbm mpcrbm_settings mpcrbm-ex-service-form">
					<input type="hidden" name="action" value="mpcrbm_save_ex_service_group">
					<?php if ( $is_edit ) : ?>
						<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
					<?php endif; ?>
					<?php wp_nonce_field( 'mpcrbm_save_extra_service_nonce', 'mpcrbm_ex_service_nonce' ); ?>

					<div class="mpcrbm-ex-service-card-form">
						<label for="mpcrbm_ex_service_group_name"><?php esc_html_e( 'Group Identity', 'car-rental-manager' ); ?></label>
						<input type="text" id="mpcrbm_ex_service_group_name" name="mpcrbm_ex_service_group_name" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g. Standard Add-ons', 'car-rental-manager' ); ?>" required>
					</div>

					<div class="settings_area">
						<div class="_ovAuto_mT_xs">
							<table class="mpcrbm-ex-service-table widefat">
								<thead>
								<tr>
									<th><span><?php esc_html_e( 'Icon', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Service Name', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Description', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Price ($)', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Pricing Type', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Qty Control', 'car-rental-manager' ); ?></span></th>
									<th><span><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></span></th>
								</tr>
								</thead>
								<tbody class="sortable_area item_insert">
									<?php
									if ( ! empty( $rows ) ) {
										foreach ( $rows as $row ) {
											MPCRBM_Extra_Service::extra_service_item( $row );
										}
									}
									?>
								</tbody>
							</table>
						</div>
						<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add Additional Service Row', 'car-rental-manager' ) ); ?>
						<?php do_action( 'mpcrbm_hidden_table', 'mpcrbm_extra_service_item' ); ?>
					</div>

					<div class="mpcrbm-ex-service-form-actions">
						<button type="submit" class="mpcrbm-ex-service-save-btn"><?php esc_html_e( 'Save Service Group', 'car-rental-manager' ); ?></button>
						<a href="<?php echo esc_url( $this->list_url() ); ?>" class="mpcrbm-ex-service-cancel-btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></a>
					</div>
				</form>
			</div>
			<?php
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
