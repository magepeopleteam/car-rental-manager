<?php
	/**
	 * Free-plugin "Bookings" list — a limited, upgrade-teaser version of the Pro booking
	 * list (MPCRBM_Order_List in car-rental-manager-pro).
	 *
	 * Purpose: without Pro, admins previously had no way at all to see their bookings in
	 * wp-admin — the only list shipped with Pro. This page shows the `mpcrbm_booking`
	 * records that BOTH flows create (the WooCommerce bridge on checkout, and the
	 * standalone Offline checkout), with Pro-only capabilities visible but locked:
	 *
	 *   - Statistics bar   -> rendered blurred behind a "PRO" overlay (no real figures).
	 *   - Filter panel     -> rendered blurred/disabled behind a "PRO" overlay.
	 *   - Booking detail   -> locked action (PRO badge, no navigation).
	 *   - Change status    -> locked action (PRO badge, status shown read-only).
	 *   - Bulk select      -> removed entirely (no checkboxes).
	 *   - Delete           -> ALLOWED in free (works, cancels a linked WooCommerce order).
	 *
	 * Fully self-contained: depends only on jQuery + dashicons (both always present in
	 * wp-admin) and the free plugin's own helpers, so it can never fatal when Pro is
	 * absent. When Pro IS active it stands down completely (no menu, no AJAX handling) so
	 * the Pro list stays the single source of truth.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! class_exists( 'MPCRBM_Booking_List_Free' ) ) {
		class MPCRBM_Booking_List_Free {

			const PER_PAGE = 20;
			const SLUG     = 'mpcrbm_bookings';

			public function __construct() {
				add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
				add_action( 'wp_ajax_mpcrbm_free_delete_booking', array( $this, 'ajax_delete_booking' ) );
				add_filter( 'mpcrbm_shell_menu_items', array( $this, 'add_shell_menu_item' ) );
				add_filter( 'mpcrbm_shell_screen_ids', array( $this, 'add_shell_screen_id' ) );
			}

			/**
			 * Pro is detected at hook/request time (both plugins are loaded by then), so
			 * the free list reliably stands down when Pro is present — never at include
			 * time, since plugin load order is not guaranteed.
			 */
			private function is_pro_active() {
				return class_exists( 'MPCRBM_Dependencies_Pro' ) || class_exists( 'MPCRBM_Plugin_Pro' );
			}

			private function get_cpt() {
				return class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_cpt() : 'mpcrbm_rent';
			}

			private function base_url() {
				return admin_url( 'edit.php?post_type=' . $this->get_cpt() . '&page=' . self::SLUG );
			}

			/** Filterable upgrade destination shown on every "PRO" lock. */
			private function upgrade_url() {
				return apply_filters( 'mpcrbm_pro_upgrade_url', 'https://mage-people.com/product/car-rental-manager/' );
			}

			public function add_menu() {
				if ( $this->is_pro_active() ) {
					return; // Pro provides the full Bookings page; don't duplicate the menu.
				}
				add_submenu_page(
					'edit.php?post_type=' . $this->get_cpt(),
					__( 'Bookings', 'car-rental-manager' ),
					__( 'Bookings', 'car-rental-manager' ),
					'manage_options',
					self::SLUG,
					array( $this, 'render_page' )
				);
			}

			/** Join the plugin's own admin shell sidebar (same idiom as the Pro list). */
			public function add_shell_menu_item( $items ) {
				if ( $this->is_pro_active() || ! current_user_can( 'manage_options' ) ) {
					return $items;
				}
				$items[] = array(
					'slug'  => self::SLUG,
					'label' => __( 'Bookings', 'car-rental-manager' ),
					'icon'  => 'fas fa-clipboard-list',
					'link'  => $this->base_url(),
				);

				return $items;
			}

			public function add_shell_screen_id( $ids ) {
				$ids[] = $this->get_cpt() . '_page_' . self::SLUG;

				return $ids;
			}

			/* --------------------------------------------------------------
			 * Data
			 * ------------------------------------------------------------ */

			private function format_price( $amount ) {
				return class_exists( 'MPCRBM_Global_Function' )
					? MPCRBM_Global_Function::format_price( (float) $amount )
					: number_format( (float) $amount, 2 );
			}

			/** Unified status label + CSS class (kept in sync with the Pro normalizer). */
			private function status_meta( $key ) {
				$key = strtolower( (string) $key );
				if ( 0 === strpos( $key, 'wc-' ) ) {
					$key = substr( $key, 3 );
				}
				$key = $key ?: 'pending';
				$map = array(
					'pending'    => array( __( 'Pending', 'car-rental-manager' ),    'pending' ),
					'processing' => array( __( 'Processing', 'car-rental-manager' ), 'processing' ),
					'on-hold'    => array( __( 'On Hold', 'car-rental-manager' ),    'on-hold' ),
					'completed'  => array( __( 'Completed', 'car-rental-manager' ),  'completed' ),
					'cancelled'  => array( __( 'Cancelled', 'car-rental-manager' ),  'cancelled' ),
					'refunded'   => array( __( 'Refunded', 'car-rental-manager' ),   'refunded' ),
					'failed'     => array( __( 'Failed', 'car-rental-manager' ),     'failed' ),
				);

				return isset( $map[ $key ] ) ? $map[ $key ] : array( ucfirst( str_replace( '-', ' ', $key ) ), 'pending' );
			}

			/**
			 * One page of bookings (newest first). Only the requested page is queried, so
			 * nothing loads the whole table into memory.
			 *
			 * @param int $paged 1-based page number.
			 *
			 * @return array{0: array<int,array>, 1: int} [ rows, total ]
			 */
			private function fetch_page( $paged ) {
				$q = new WP_Query( array(
					'post_type'      => 'mpcrbm_booking',
					'post_status'    => array( 'publish', 'pending', 'draft' ),
					'posts_per_page' => self::PER_PAGE,
					'paged'          => max( 1, $paged ),
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => false,
				) );

				$rows     = array();
				$wc_ready = function_exists( 'wc_get_order' );
				while ( $q->have_posts() ) {
					$q->the_post();
					$id       = get_the_ID();
					$car      = absint( get_post_meta( $id, 'mpcrbm_id', true ) );
					$order_id = absint( get_post_meta( $id, 'mpcrbm_order_id', true ) );

					// Source + total resolved per rendered row only (max PER_PAGE lookups).
					$wc_order = ( $wc_ready && $order_id && $order_id !== $id ) ? wc_get_order( $order_id ) : false;
					$is_woo   = ( $wc_order instanceof WC_Order );
					$total    = $is_woo ? (float) $wc_order->get_total() : (float) get_post_meta( $id, 'mpcrbm_tp', true );

					// What makes up that total. A bare grand total tells an admin nothing
					// about WHY a booking costs what it does — whether the customer added
					// extras, or the figure is just the rental. Each part is only listed
					// when it is actually charged, so a plain booking stays a single line.
					$quantity  = max( 1, absint( get_post_meta( $id, 'mpcrbm_car_quantity', true ) ) );
					$base      = (float) get_post_meta( $id, 'mpcrbm_base_price', true );
					$deposit   = (float) get_post_meta( $id, 'mpcrbm_security_deposit_amount', true );
					$one_way   = (float) get_post_meta( $id, 'mpcrbm_branch_one_way_fee', true );
					$services  = get_post_meta( $id, 'mpcrbm_service_info', true );
					// The WooCommerce flow nests this one level deeper than the standalone
					// checkout does, so unwrap a wrapper array before reading it.
					if ( is_array( $services ) && isset( $services[0] ) && is_array( $services[0] ) && ! isset( $services[0]['service_name'] ) ) {
						$services = $services[0];
					}
					$services = is_array( $services ) ? $services : array();

					$parts = array();
					if ( $base > 0 ) {
						$parts[] = array(
							'label'  => $quantity > 1
								/* translators: %d: number of vehicles booked. */
								? sprintf( __( 'Rental × %d', 'car-rental-manager' ), $quantity )
								: __( 'Rental', 'car-rental-manager' ),
							'amount' => $base * $quantity,
						);
					}
					foreach ( $services as $service ) {
						if ( ! is_array( $service ) || empty( $service['service_name'] ) ) {
							continue;
						}
						$service_qty = max( 1, absint( $service['service_quantity'] ?? 1 ) );
						$parts[]     = array(
							'label'  => $service['service_name'] . ( $service_qty > 1 ? ' × ' . $service_qty : '' ),
							'amount' => (float) ( $service['service_price'] ?? 0 ) * $service_qty,
							'extra'  => true,
						);
					}
					if ( $one_way > 0 ) {
						$parts[] = array( 'label' => __( 'One-way fee', 'car-rental-manager' ), 'amount' => $one_way * $quantity );
					}
					if ( $deposit > 0 ) {
						$parts[] = array( 'label' => __( 'Deposit', 'car-rental-manager' ), 'amount' => $deposit );
					}

					$rows[] = array(
						'ID'       => $id,
						'is_woo'   => $is_woo,
						'order_id' => $order_id,
						'name'     => get_post_meta( $id, 'mpcrbm_billing_name', true ),
						'email'    => get_post_meta( $id, 'mpcrbm_billing_email', true ),
						'phone'    => get_post_meta( $id, 'mpcrbm_billing_phone', true ),
						'car'      => $car ? get_the_title( $car ) : '—',
						'pickup'   => get_post_meta( $id, 'mpcrbm_date', true ),
						'ret'      => get_post_meta( $id, 'return_date_time', true ),
						'status'   => get_post_meta( $id, 'mpcrbm_order_status', true ) ?: 'pending',
						'payment'  => get_post_meta( $id, 'mpcrbm_payment_method', true ),
						'total'    => $this->format_price( $total ),
						'parts'    => $parts,
					);
				}
				wp_reset_postdata();

				return array( $rows, (int) $q->found_posts );
			}

			/* --------------------------------------------------------------
			 * AJAX: delete (the one action available in free)
			 * ------------------------------------------------------------ */

			public function ajax_delete_booking() {
				check_ajax_referer( 'mpcrbm_free_bookings', 'nonce' );
				if ( $this->is_pro_active() ) {
					// Defense in depth: when Pro is active it owns deletion.
					wp_send_json_error( array( 'message' => __( 'Handled by Pro.', 'car-rental-manager' ) ), 400 );
				}
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized', 'car-rental-manager' ) ), 403 );
				}
				$id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
				if ( ! $id || 'mpcrbm_booking' !== get_post_type( $id ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid booking.', 'car-rental-manager' ) ) );
				}

				$order_id = absint( get_post_meta( $id, 'mpcrbm_order_id', true ) );

				// Cancel the linked WooCommerce order (if any, and if WooCommerce is
				// active). Deleting only the booking record would leave a paid-looking
				// order behind with nothing to fulfil.
				if ( $order_id && $order_id !== $id && function_exists( 'wc_get_order' ) ) {
					$order = wc_get_order( $order_id );
					if ( $order instanceof WC_Order && ! in_array( $order->get_status(), array( 'cancelled', 'refunded' ), true ) ) {
						$order->update_status( 'cancelled', __( 'Order cancelled due to booking deletion.', 'car-rental-manager' ) );
					}
				}

				// Remove the extra-service records tied to this booking.
				$service_q = new WP_Query( array(
					'post_type'      => 'mpcrbm_service_booking',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'     => array(
						'relation' => 'AND',
						array( 'key' => 'mpcrbm_id', 'value' => get_post_meta( $id, 'mpcrbm_id', true ) ),
						array( 'key' => 'mpcrbm_order_id', 'value' => $order_id ),
					),
				) );
				foreach ( $service_q->posts as $service_id ) {
					wp_delete_post( $service_id, true );
				}
				wp_reset_postdata();

				wp_trash_post( $id );

				wp_send_json_success( array( 'message' => __( 'Booking deleted.', 'car-rental-manager' ) ) );
			}

			/* --------------------------------------------------------------
			 * Render
			 * ------------------------------------------------------------ */

			private function pro_badge() {
				return '<span class="mpcrbm-lock-badge"><span class="dashicons dashicons-lock"></span>' . esc_html__( 'PRO', 'car-rental-manager' ) . '</span>';
			}

			private function pro_overlay( $upgrade, $message ) {
				?>
				<div class="mpcrbm-pro-lock-overlay">
					<span class="mpcrbm-pro-lock-icon dashicons dashicons-lock"></span>
					<p><?php echo esc_html( $message ); ?></p>
					<a href="<?php echo esc_url( $upgrade ); ?>" target="_blank" rel="noopener noreferrer" class="mpcrbm-btn mpcrbm-btn-primary">
						<span class="dashicons dashicons-star-filled"></span><?php esc_html_e( 'Upgrade to Pro', 'car-rental-manager' ); ?>
					</a>
				</div>
				<?php
			}

			public function render_page() {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				list( $rows, $total ) = $this->fetch_page( $paged );
				$pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
				if ( $paged > $pages ) {
					// Clamp out-of-range page requests (e.g. after deleting the last row
					// on the final page) instead of showing a blank table.
					$paged = $pages;
					list( $rows, $total ) = $this->fetch_page( $paged );
				}
				$nonce   = wp_create_nonce( 'mpcrbm_free_bookings' );
				$upgrade = $this->upgrade_url();

				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Bookings', 'car-rental-manager' ) );
				$this->render_styles();
				?>
				<div class="mpcrbm-bookings-wrap">
					<div class="mpcrbm-settings-head">
						<span class="mpcrbm-settings-head-eyebrow"><?php esc_html_e( 'Booking management', 'car-rental-manager' ); ?></span>
						<h2><?php esc_html_e( 'Bookings', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-settings-head-subtitle"><?php esc_html_e( 'A read-only overview of every rental booking — from both the WooCommerce checkout and the standalone Custom Payment checkout. Unlock filters, statistics, booking details and status management with Pro.', 'car-rental-manager' ); ?></p>
					</div>

					<?php /* Statistics + Filter — locked together behind a single PRO overlay. */ ?>
					<div class="mpcrbm-pro-lock">
						<div class="mpcrbm-pro-lock-inner" aria-hidden="true">
							<div class="mpcrbm-stats-bar">
								<div class="mpcrbm-stat-card"><span class="mpcrbm-stat-icon dashicons dashicons-cart"></span><div class="mpcrbm-stat-info"><span class="mpcrbm-stat-value">1,248</span><span class="mpcrbm-stat-label"><?php esc_html_e( 'Total Bookings', 'car-rental-manager' ); ?></span></div></div>
								<div class="mpcrbm-stat-card"><span class="mpcrbm-stat-icon dashicons dashicons-money-alt"></span><div class="mpcrbm-stat-info"><span class="mpcrbm-stat-value">$48,920</span><span class="mpcrbm-stat-label"><?php esc_html_e( 'Total Revenue', 'car-rental-manager' ); ?></span></div></div>
								<div class="mpcrbm-stat-card"><span class="mpcrbm-stat-icon dashicons dashicons-clock"></span><div class="mpcrbm-stat-info"><span class="mpcrbm-stat-value">36</span><span class="mpcrbm-stat-label"><?php esc_html_e( 'Pending', 'car-rental-manager' ); ?></span></div></div>
								<div class="mpcrbm-stat-card"><span class="mpcrbm-stat-icon dashicons dashicons-yes-alt"></span><div class="mpcrbm-stat-info"><span class="mpcrbm-stat-value">1,102</span><span class="mpcrbm-stat-label"><?php esc_html_e( 'Completed', 'car-rental-manager' ); ?></span></div></div>
							</div>
							<div class="mpcrbm-filter-panel">
								<div class="mpcrbm-filter-panel-header"><span class="dashicons dashicons-filter"></span><strong><?php esc_html_e( 'Filter Bookings', 'car-rental-manager' ); ?></strong></div>
								<div class="mpcrbm-filter-grid">
									<div class="mpcrbm-filter-field"><label><?php esc_html_e( 'Search', 'car-rental-manager' ); ?></label><input type="text" disabled placeholder="<?php esc_attr_e( 'Name, email or #ID…', 'car-rental-manager' ); ?>"></div>
									<div class="mpcrbm-filter-field"><label><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></label><select disabled><option><?php esc_html_e( 'All Statuses', 'car-rental-manager' ); ?></option></select></div>
									<div class="mpcrbm-filter-field"><label><?php esc_html_e( 'Booking Source', 'car-rental-manager' ); ?></label><select disabled><option><?php esc_html_e( 'All Sources', 'car-rental-manager' ); ?></option></select></div>
									<div class="mpcrbm-filter-field"><label><?php esc_html_e( 'Date From', 'car-rental-manager' ); ?></label><input type="date" disabled></div>
								</div>
							</div>
						</div>
						<?php $this->pro_overlay( $upgrade, __( 'Live statistics, filtering & search are Pro features', 'car-rental-manager' ) ); ?>
					</div>

					<div class="mpcrbm-card mpcrbm-bookings-card">
						<div class="mpcrbm-table-toolbar">
							<span class="mpcrbm-result-count">
								<?php
								/* translators: %d: number of bookings. */
								printf( esc_html( _n( '%d booking', '%d bookings', $total, 'car-rental-manager' ) ), (int) $total );
								?>
							</span>
							<a href="<?php echo esc_url( $upgrade ); ?>" target="_blank" rel="noopener noreferrer" class="mpcrbm-btn mpcrbm-btn-primary">
								<span class="dashicons dashicons-star-filled"></span><?php esc_html_e( 'Upgrade to Pro', 'car-rental-manager' ); ?>
							</a>
						</div>

						<div class="mpcrbm-table-scroll">
							<table class="mpcrbm-bookings-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Booking', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></th>
										<th><?php echo esc_html( class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_name() : __( 'Car', 'car-rental-manager' ) ); ?></th>
										<th><?php esc_html_e( 'Rental period', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Total', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Payment', 'car-rental-manager' ); ?></th>
										<th><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
										<th class="mpcrbm-col-actions"><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
								<?php if ( empty( $rows ) ) : ?>
									<tr class="mpcrbm-empty-row">
										<td colspan="8">
											<span class="dashicons dashicons-clipboard"></span>
											<strong><?php esc_html_e( 'No bookings yet', 'car-rental-manager' ); ?></strong>
											<span><?php esc_html_e( 'Bookings will appear here as soon as your first customer completes a rental.', 'car-rental-manager' ); ?></span>
										</td>
									</tr>
								<?php else : ?>
									<?php foreach ( $rows as $row ) : ?>
										<?php list( $status_label, $status_class ) = $this->status_meta( $row['status'] ); ?>
										<tr data-booking-id="<?php echo esc_attr( $row['ID'] ); ?>">
											<td>
												<strong class="mpcrbm-booking-ref">#<?php echo esc_html( $row['ID'] ); ?></strong>
												<span class="mpcrbm-source-tag mpcrbm-source-<?php echo $row['is_woo'] ? 'woo' : 'native'; ?>">
													<?php echo $row['is_woo'] ? esc_html__( 'WooCommerce', 'car-rental-manager' ) : esc_html__( 'Custom Payment', 'car-rental-manager' ); ?>
												</span>
											</td>
											<td>
												<span class="mpcrbm-cell-strong"><?php echo esc_html( $row['name'] ?: '—' ); ?></span>
												<?php if ( $row['email'] ) : ?><span class="mpcrbm-cell-sub"><?php echo esc_html( $row['email'] ); ?></span><?php endif; ?>
												<?php if ( $row['phone'] ) : ?><span class="mpcrbm-cell-sub"><?php echo esc_html( $row['phone'] ); ?></span><?php endif; ?>
											</td>
											<td><?php echo esc_html( $row['car'] ); ?></td>
											<td>
												<span class="mpcrbm-cell-strong"><?php echo esc_html( $row['pickup'] ?: '—' ); ?></span>
												<?php if ( $row['ret'] ) : ?><span class="mpcrbm-cell-sub"><?php echo esc_html( $row['ret'] ); ?></span><?php endif; ?>
											</td>
											<td>
												<span class="mpcrbm-cell-strong"><?php echo wp_kses_post( $row['total'] ); ?></span>
												<?php if ( ! empty( $row['parts'] ) ) : ?>
													<ul class="mpcrbm-total-parts">
														<?php foreach ( $row['parts'] as $part ) : ?>
															<li<?php echo ! empty( $part['extra'] ) ? ' class="is-extra"' : ''; ?>>
																<span><?php echo esc_html( $part['label'] ); ?></span>
																<em><?php echo wp_kses_post( $this->format_price( $part['amount'] ) ); ?></em>
															</li>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $row['payment'] ?: '—' ); ?></td>
											<td><span class="mpcrbm-status-pill is-<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
											<td class="mpcrbm-col-actions">
												<span class="mpcrbm-action-locked" title="<?php esc_attr_e( 'Booking details are available in Pro', 'car-rental-manager' ); ?>">
													<span class="dashicons dashicons-visibility"></span><?php echo wp_kses_post( $this->pro_badge() ); ?>
												</span>
												<button type="button" class="mpcrbm-action-delete" data-booking-id="<?php echo esc_attr( $row['ID'] ); ?>" title="<?php esc_attr_e( 'Delete booking', 'car-rental-manager' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
								</tbody>
							</table>
						</div>

						<?php if ( $pages > 1 ) : ?>
							<div class="mpcrbm-pagination">
								<?php
								echo wp_kses_post( paginate_links( array(
									'base'      => add_query_arg( 'paged', '%#%', $this->base_url() ),
									'format'    => '',
									'current'   => $paged,
									'total'     => $pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								) ) );
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<script>
				jQuery(function($){
					var nonce = <?php echo wp_json_encode( $nonce ); ?>;
					var i18n = {
						confirm: <?php echo wp_json_encode( __( 'Delete this booking? Any linked WooCommerce order will be cancelled. This cannot be undone.', 'car-rental-manager' ) ); ?>,
						error: <?php echo wp_json_encode( __( 'Could not delete the booking. Please try again.', 'car-rental-manager' ) ); ?>
					};

					$(document).on('click', '.mpcrbm-action-delete', function(){
						var $btn = $(this);
						if (!window.confirm(i18n.confirm)) { return; }
						$btn.prop('disabled', true);

						$.post(ajaxurl, {
							action: 'mpcrbm_free_delete_booking',
							nonce: nonce,
							booking_id: $btn.data('booking-id')
						}).done(function(res){
							if (res && res.success) {
								$btn.closest('tr').fadeOut(200, function(){
									// Reload rather than only removing the row: the result
									// count and pagination are both now wrong, and on the
									// last row of a page the page itself no longer exists.
									window.location.reload();
								});
							} else {
								window.alert((res && res.data && res.data.message) || i18n.error);
								$btn.prop('disabled', false);
							}
						}).fail(function(){
							window.alert(i18n.error);
							$btn.prop('disabled', false);
						});
					});
				});
				</script>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}

			/** Screen CSS, following the car-rental admin shell's tokens. */
			private function render_styles() {
				?>
				<style>
				.mpcrbm-bookings-wrap{--mpcrbm-bk-accent:var(--mpcrbm-shell-primary,#667eea);}
				.mpcrbm-bookings-wrap *{box-sizing:border-box;}

				/* PRO lock (stats + filters) */
				.mpcrbm-pro-lock{position:relative;margin:0 4px 24px;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius,16px);background:#fff;overflow:hidden;box-shadow:var(--mpcrbm-shell-shadow,0 5px 15px -5px rgba(115,125,146,.11));}
				.mpcrbm-pro-lock-inner{padding:24px;filter:blur(3px);opacity:.55;pointer-events:none;user-select:none;}
				.mpcrbm-pro-lock-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;text-align:center;padding:20px;background:rgba(255,255,255,.72);}
				.mpcrbm-pro-lock-icon{width:46px;height:46px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:#eef1fe;color:var(--mpcrbm-bk-accent);font-size:22px;}
				.mpcrbm-pro-lock-overlay p{margin:0;font-size:14px;font-weight:600;color:var(--mpcrbm-shell-text,#1f222b);}

				.mpcrbm-stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:20px;}
				.mpcrbm-stat-card{display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius-sm,12px);background:#fafbfc;}
				.mpcrbm-stat-icon{width:40px;height:40px;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background:#eef1fe;color:var(--mpcrbm-bk-accent);}
				.mpcrbm-stat-info{display:flex;flex-direction:column;min-width:0;}
				.mpcrbm-stat-value{font-size:20px;font-weight:700;color:var(--mpcrbm-shell-text,#1f222b);line-height:1.2;}
				.mpcrbm-stat-label{font-size:12px;color:var(--mpcrbm-shell-text-faded,#788291);}
				.mpcrbm-filter-panel{border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius-sm,12px);overflow:hidden;}
				.mpcrbm-filter-panel-header{display:flex;align-items:center;gap:8px;padding:12px 16px;background:#fafbfc;border-bottom:1px solid var(--mpcrbm-shell-border,#e7e7ea);font-size:14px;}
				.mpcrbm-filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;padding:16px;}
				.mpcrbm-filter-field label{display:block;margin-bottom:5px;font-size:12px;font-weight:600;color:#39445A;}
				.mpcrbm-filter-field input,.mpcrbm-filter-field select{width:100%;padding:8px 11px;border:1px solid #d9dce3;border-radius:8px;font-size:13px;background:#fff;}

				/* Table card */
				.mpcrbm-bookings-card{margin:0 4px 24px;background:#fff;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);border-radius:var(--mpcrbm-shell-radius,16px);box-shadow:var(--mpcrbm-shell-shadow,0 5px 15px -5px rgba(115,125,146,.11));overflow:hidden;}
				.mpcrbm-table-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;border-bottom:1px solid var(--mpcrbm-shell-border,#e7e7ea);}
				.mpcrbm-result-count{font-size:13.5px;font-weight:600;color:var(--mpcrbm-shell-text-faded,#788291);}
				.mpcrbm-btn{display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 16px;border-radius:var(--mpcrbm-shell-radius-xs,8px);font-size:13px;font-weight:600;text-decoration:none;line-height:36px;border:none;cursor:pointer;}
				.mpcrbm-btn-primary{background:var(--mpcrbm-bk-accent);color:#fff !important;box-shadow:0 2px 6px rgba(102,126,234,.28);}
				.mpcrbm-btn-primary:hover{background:#5568d3;color:#fff !important;}
				.mpcrbm-btn .dashicons{font-size:15px;width:15px;height:15px;line-height:1.2;}

				/* Wide table scrolls inside its own rail — the page must never scroll sideways. */
				.mpcrbm-table-scroll{width:100%;overflow-x:auto;}
				.mpcrbm-bookings-table{width:100%;min-width:900px;border-collapse:collapse;}
				.mpcrbm-bookings-table th{padding:13px 18px;text-align:left;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--mpcrbm-shell-text-faded,#788291);background:#fafbfc;border-bottom:1px solid var(--mpcrbm-shell-border,#e7e7ea);white-space:nowrap;}
				.mpcrbm-bookings-table td{padding:15px 18px;font-size:13.5px;color:var(--mpcrbm-shell-text,#1f222b);border-bottom:1px solid #f1f2f6;vertical-align:top;}
				.mpcrbm-bookings-table tbody tr:last-child td{border-bottom:none;}
				.mpcrbm-bookings-table tbody tr:hover{background:#fbfcfe;}
				.mpcrbm-cell-strong{display:block;font-weight:600;}

				/* What makes up the total. Deliberately quiet — the grand total stays the
				   headline and the parts read as supporting detail rather than competing
				   with it. Extras are tinted so an admin can spot at a glance which
				   bookings actually had services added. */
				.mpcrbm-total-parts{list-style:none;margin:6px 0 0;padding:0;border-top:1px dashed #e7e7ea;padding-top:6px;}
				.mpcrbm-total-parts li{display:flex;align-items:baseline;justify-content:space-between;gap:10px;font-size:11.5px;line-height:1.6;color:var(--mpcrbm-shell-text-faded,#788291);white-space:nowrap;}
				.mpcrbm-total-parts li.is-extra{color:#4f5bd5;}
				.mpcrbm-total-parts li span{overflow:hidden;text-overflow:ellipsis;}
				.mpcrbm-total-parts li em{font-style:normal;font-weight:600;flex:0 0 auto;}
				.mpcrbm-cell-sub{display:block;font-size:12px;color:var(--mpcrbm-shell-text-faded,#788291);margin-top:2px;}
				.mpcrbm-booking-ref{display:block;font-size:14px;}
				.mpcrbm-source-tag{display:inline-block;margin-top:5px;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
				.mpcrbm-source-woo{background:#f3ecfa;color:#7f54b3;}
				.mpcrbm-source-native{background:#eef1fe;color:#4f5bd5;}

				.mpcrbm-status-pill{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;background:#f1f2f6;color:#788291;}
				.mpcrbm-status-pill.is-pending{background:#fff7ed;color:#9a3412;}
				.mpcrbm-status-pill.is-processing{background:#eff6ff;color:#1d4ed8;}
				.mpcrbm-status-pill.is-on-hold{background:#fefce8;color:#a16207;}
				.mpcrbm-status-pill.is-completed{background:#dcfce7;color:#15803d;}
				.mpcrbm-status-pill.is-cancelled,.mpcrbm-status-pill.is-failed{background:#fef2f2;color:#b42318;}
				.mpcrbm-status-pill.is-refunded{background:#f3f4f6;color:#4b5563;}

				.mpcrbm-col-actions{white-space:nowrap;}
				.mpcrbm-action-locked{display:inline-flex;align-items:center;gap:5px;margin-right:10px;color:#b9bec9;cursor:not-allowed;}
				.mpcrbm-lock-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff;font-size:9.5px;font-weight:800;letter-spacing:.4px;}
				.mpcrbm-lock-badge .dashicons{font-size:11px;width:11px;height:11px;line-height:1.1;}
				.mpcrbm-action-delete{background:none;border:none;padding:4px;cursor:pointer;color:#b42318;border-radius:6px;line-height:1;}
				.mpcrbm-action-delete:hover:not(:disabled){background:#fef2f2;}
				.mpcrbm-action-delete:disabled{opacity:.5;cursor:progress;}

				.mpcrbm-empty-row td{padding:56px 20px !important;text-align:center;color:var(--mpcrbm-shell-text-faded,#788291);}
				.mpcrbm-empty-row .dashicons{display:block;margin:0 auto 12px;font-size:38px;width:38px;height:38px;color:#c8ccd6;}
				.mpcrbm-empty-row strong{display:block;font-size:15px;color:var(--mpcrbm-shell-text,#1f222b);margin-bottom:5px;}
				.mpcrbm-empty-row span{font-size:13px;}

				.mpcrbm-pagination{display:flex;justify-content:center;gap:6px;padding:18px;border-top:1px solid var(--mpcrbm-shell-border,#e7e7ea);}
				.mpcrbm-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:8px;border:1px solid var(--mpcrbm-shell-border,#e7e7ea);font-size:13px;font-weight:600;color:#39445A;text-decoration:none;}
				.mpcrbm-pagination .page-numbers:hover{border-color:var(--mpcrbm-bk-accent);color:var(--mpcrbm-bk-accent);}
				.mpcrbm-pagination .page-numbers.current{background:var(--mpcrbm-bk-accent);border-color:var(--mpcrbm-bk-accent);color:#fff;}

				@media (max-width:782px){
					.mpcrbm-table-toolbar{flex-direction:column;align-items:stretch;}
					.mpcrbm-table-toolbar .mpcrbm-btn{justify-content:center;}
				}
				</style>
				<?php
			}
		}

		new MPCRBM_Booking_List_Free();
	}
