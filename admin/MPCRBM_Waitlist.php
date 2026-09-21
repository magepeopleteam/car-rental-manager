<?php
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 *
	 * "Join Waitlist" — MVP.
	 *
	 * When a customer's chosen car is already booked for their chosen date, the
	 * front end offers a "Join Waitlist" form instead of just an alert. The
	 * request lands on this hidden CPT (mpcrbm_waitlist), and the admin can see
	 * it here and manually notify the customer, or — automatically — get
	 * notified the moment a booking for that same car is cancelled/refunded.
	 *
	 * Deliberately event-driven, not date-matching: "notified" here means "a
	 * booking for this car was just freed up, you may want to check again",
	 * not "the exact dates you asked for are now free". Matching exact dates
	 * would need the waitlist entry to store a date range and re-run one of
	 * the plugin's several (not fully consistent) availability calculators —
	 * out of scope for this first version. See MPCRBM_Waitlist::maybe_notify_for_car().
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MPCRBM_Waitlist' ) ) {
		class MPCRBM_Waitlist {

			const CPT  = 'mpcrbm_waitlist';
			const SLUG = 'mpcrbm_waitlist';

			public function __construct() {
				add_action( 'init', [ $this, 'register_cpt' ] );
				add_action( 'admin_menu', [ $this, 'add_menu' ] );
				add_filter( 'mpcrbm_shell_menu_items', [ $this, 'add_shell_menu_item' ] );
				add_filter( 'mpcrbm_shell_screen_ids', [ $this, 'add_shell_screen_id' ] );

				add_action( 'wp_ajax_mpcrbm_join_waitlist', [ $this, 'ajax_join_waitlist' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_join_waitlist', [ $this, 'ajax_join_waitlist' ] );
				add_action( 'wp_ajax_mpcrbm_notify_waitlist_entry', [ $this, 'ajax_notify_waitlist_entry' ] );
				add_action( 'wp_ajax_mpcrbm_dismiss_waitlist_entry', [ $this, 'ajax_dismiss_waitlist_entry' ] );
				add_action( 'wp_ajax_mpcrbm_mark_waitlist_outcome', [ $this, 'ajax_mark_waitlist_outcome' ] );
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

				// WooCommerce booking mode: a car's line item on an order that just
				// turned cancelled/refunded is our "a slot may have opened up" signal.
				// Kept as a separate listener rather than editing
				// MPCRBM_Woocommerce::order_status_changed() so this feature stays
				// fully additive.
				add_action( 'woocommerce_order_status_changed', [ $this, 'on_woocommerce_order_status_changed' ] );
			}

			public function is_enabled(): bool {
				return class_exists( 'MPCRBM_Global_Function' )
					&& 'yes' === MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', 'car_details_waitlist_button' );
			}

			/**
			 * Only on a car-details page, and only when the admin has turned the
			 * feature on.
			 */
			public function enqueue_frontend_assets() {
				if ( ! $this->is_enabled() ) {
					return;
				}
				if ( ! is_singular( MPCRBM_Function::get_cpt() ) ) {
					return;
				}

				$path = MPCRBM_PLUGIN_DIR . '/assets/frontend/mpcrbm-waitlist.js';
				wp_enqueue_script(
					'mpcrbm-waitlist',
					MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-waitlist.js',
					[ 'jquery' ],
					file_exists( $path ) ? (string) filemtime( $path ) : MPCRBM_PLUGIN_VERSION,
					true
				);
				wp_localize_script( 'mpcrbm-waitlist', 'mpcrbmWaitlist', [
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mpcrbm_join_waitlist' ),
					'i18n'    => [
						'title'          => __( 'This car is booked for that day', 'car-rental-manager' ),
						'intro'          => __( 'Leave your details and we\'ll email you if it becomes available again.', 'car-rental-manager' ),
						'nameLabel'      => __( 'Your Name', 'car-rental-manager' ),
						'emailLabel'     => __( 'Email', 'car-rental-manager' ),
						'phoneLabel'     => __( 'Phone (optional)', 'car-rental-manager' ),
						'send'           => __( 'Join Waitlist', 'car-rental-manager' ),
						'cancel'         => __( 'Cancel', 'car-rental-manager' ),
						'sending'        => __( 'Sending…', 'car-rental-manager' ),
						'enterNameEmail' => __( 'Please enter your name and a valid email address.', 'car-rental-manager' ),
						'error'          => __( 'Something went wrong. Please try again.', 'car-rental-manager' ),
						'success'        => __( 'You\'re on the waitlist! We\'ll email you if this car frees up.', 'car-rental-manager' ),
					],
				] );
			}

			public function register_cpt() {
				register_post_type( self::CPT, [
					'label'               => __( 'Waitlist', 'car-rental-manager' ),
					'public'              => false,
					'show_ui'             => false,
					'show_in_menu'        => false,
					'supports'            => [ 'title' ],
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'exclude_from_search' => true,
					'show_in_nav_menus'   => false,
					'has_archive'         => false,
					'rewrite'             => false,
				] );
			}

			public function add_menu() {
				add_submenu_page(
					'edit.php?post_type=' . MPCRBM_Function::get_cpt(),
					__( 'Waitlist', 'car-rental-manager' ),
					__( 'Waitlist', 'car-rental-manager' ),
					'manage_options',
					self::SLUG,
					[ $this, 'render_page' ]
				);
			}

			public function add_shell_menu_item( $items ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $items;
				}
				$items[] = [
					'slug'  => self::SLUG,
					'label' => __( 'Waitlist', 'car-rental-manager' ),
					'icon'  => 'fas fa-clock',
					'link'  => admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=' . self::SLUG ),
				];

				return $items;
			}

			public function add_shell_screen_id( $ids ) {
				$ids[] = MPCRBM_Function::get_cpt() . '_page_' . self::SLUG;

				return $ids;
			}

			// =========================================================
			// Frontend: customer joins the waitlist from the car-details page
			// =========================================================
			public function ajax_join_waitlist() {
				check_ajax_referer( 'mpcrbm_join_waitlist', 'nonce' );

				$car_id = isset( $_POST['car_id'] ) ? absint( $_POST['car_id'] ) : 0;
				if ( ! $car_id || get_post_type( $car_id ) !== MPCRBM_Function::get_cpt() ) {
					wp_send_json_error( [ 'message' => __( 'We could not find that vehicle. Please try again.', 'car-rental-manager' ) ] );
				}

				$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
				$pickup = isset( $_POST['pickup'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup'] ) ) : '';
				$return = isset( $_POST['return'] ) ? sanitize_text_field( wp_unslash( $_POST['return'] ) ) : '';

				if ( '' === $name || ! is_email( $email ) ) {
					wp_send_json_error( [ 'message' => __( 'Please enter your name and a valid email address.', 'car-rental-manager' ) ] );
				}

				$post_id = wp_insert_post( [
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => sprintf( '%s — %s', get_the_title( $car_id ), $name ),
				], true );

				if ( is_wp_error( $post_id ) || ! $post_id ) {
					wp_send_json_error( [ 'message' => __( 'Could not join the waitlist. Please try again.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $post_id, 'mpcrbm_wl_car_id', $car_id );
				update_post_meta( $post_id, 'mpcrbm_wl_name', $name );
				update_post_meta( $post_id, 'mpcrbm_wl_email', $email );
				update_post_meta( $post_id, 'mpcrbm_wl_phone', $phone );
				update_post_meta( $post_id, 'mpcrbm_wl_pickup', $pickup );
				update_post_meta( $post_id, 'mpcrbm_wl_return', $return );
				update_post_meta( $post_id, 'mpcrbm_wl_status', 'pending' );

				$admin_email = get_option( 'admin_email' );
				if ( $admin_email ) {
					wp_mail(
						$admin_email,
						sprintf( /* translators: %s: site name */ __( '[%s] New waitlist entry', 'car-rental-manager' ), get_bloginfo( 'name' ) ),
						sprintf(
							/* translators: 1: customer name, 2: customer email, 3: car name, 4: admin link */
							__( "%1\$s (%2\$s) joined the waitlist for \"%3\$s\". View it here: %4\$s", 'car-rental-manager' ),
							$name,
							$email,
							get_the_title( $car_id ),
							admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=' . self::SLUG )
						)
					);
				}

				wp_send_json_success( [
					'message' => __( 'You\'re on the waitlist! We\'ll email you if this car frees up.', 'car-rental-manager' ),
				] );
			}

			// =========================================================
			// Automatic trigger: WooCommerce order status change
			// =========================================================
			public function on_woocommerce_order_status_changed( $order_id ) {
				if ( ! function_exists( 'wc_get_order' ) ) {
					return;
				}
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					return;
				}
				if ( ! $order->has_status( 'cancelled' ) && ! $order->has_status( 'refunded' ) ) {
					return;
				}
				foreach ( $order->get_items() as $item_id => $item_values ) {
					$car_id = (int) MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_id' );
					if ( $car_id && get_post_type( $car_id ) === MPCRBM_Function::get_cpt() ) {
						self::maybe_notify_for_car( $car_id );
					}
				}
			}

			/**
			 * Called whenever a booking for $car_id turns cancelled/refunded —
			 * from the WooCommerce listener above, or directly from Custom
			 * Payment status-change code (e.g. Pro's Order List admin dropdown
			 * and customer self-service cancellation), since those mutate a
			 * booking's status via a plain update_post_meta() with no WordPress
			 * action fired for anything else to hook.
			 *
			 * Emails every still-pending waitlist entry for this car that a slot
			 * may have opened up, then marks them notified so the same
			 * cancellation doesn't re-email them. Does not attempt to verify the
			 * specific dates a waitlisted customer wanted are the ones that just
			 * freed up — see this file's top comment.
			 */
			public static function maybe_notify_for_car( int $car_id ) {
				if ( ! $car_id ) {
					return;
				}
				$query = new WP_Query( [
					'post_type'      => self::CPT,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => [
						[ 'key' => 'mpcrbm_wl_car_id', 'value' => $car_id ],
						[ 'key' => 'mpcrbm_wl_status', 'value' => 'pending' ],
					],
				] );

				if ( ! $query->have_posts() ) {
					return;
				}

				$car_name = get_the_title( $car_id );
				$car_link = get_permalink( $car_id );

				foreach ( $query->posts as $entry ) {
					$email  = get_post_meta( $entry->ID, 'mpcrbm_wl_email', true );
					$name   = get_post_meta( $entry->ID, 'mpcrbm_wl_name', true );
					$wanted = self::format_wanted_dates( $entry->ID );
					if ( $email ) {
						wp_mail(
							$email,
							sprintf( /* translators: %s: car name */ __( 'Good news — "%s" may be available again', 'car-rental-manager' ), $car_name ),
							sprintf(
								/* translators: 1: customer name, 2: car name, 3: wanted dates line (may be blank), 4: booking link */
								__( "Hi %1\$s,\n\nA booking for \"%2\$s\" was just freed up.%3\$s If you'd still like to book it, please check availability for your dates here:\n%4\$s\n\nIf someone else books it first, sign up for the waitlist again and we'll let you know next time.", 'car-rental-manager' ),
								$name,
								$car_name,
								$wanted ? ' ' . sprintf( /* translators: %s: the dates the customer originally asked for */ __( 'You had asked about %s.', 'car-rental-manager' ), $wanted ) : '',
								$car_link
							)
						);
					}
					update_post_meta( $entry->ID, 'mpcrbm_wl_status', 'notified' );
				}
			}

			/**
			 * "2026-09-21" / "2026-09-21 → 2026-09-23" for use in admin display
			 * and customer-facing email text — kept in one place so both stay
			 * in sync.
			 */
			private static function format_wanted_dates( int $entry_id ): string {
				$pickup = get_post_meta( $entry_id, 'mpcrbm_wl_pickup', true );
				$return = get_post_meta( $entry_id, 'mpcrbm_wl_return', true );
				if ( ! $pickup && ! $return ) {
					return '';
				}
				return $pickup . ( $return ? ' → ' . $return : '' );
			}

			// =========================================================
			// Admin: manual actions
			// =========================================================
			public function ajax_notify_waitlist_entry() {
				check_ajax_referer( 'mpcrbm_waitlist_admin', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
				}

				$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
				if ( ! $entry_id || get_post_type( $entry_id ) !== self::CPT ) {
					wp_send_json_error( [ 'message' => __( 'Entry not found.', 'car-rental-manager' ) ] );
				}

				$email  = get_post_meta( $entry_id, 'mpcrbm_wl_email', true );
				$name   = get_post_meta( $entry_id, 'mpcrbm_wl_name', true );
				$car_id = (int) get_post_meta( $entry_id, 'mpcrbm_wl_car_id', true );
				$wanted = self::format_wanted_dates( $entry_id );

				if ( $email ) {
					wp_mail(
						$email,
						sprintf( /* translators: %s: car name */ __( 'Good news — "%s" may be available again', 'car-rental-manager' ), get_the_title( $car_id ) ),
						sprintf(
							/* translators: 1: customer name, 2: car name, 3: wanted dates line (may be blank), 4: booking link */
							__( "Hi %1\$s,\n\nGood news — \"%2\$s\" may be available now.%3\$s Please check availability for your dates here:\n%4\$s", 'car-rental-manager' ),
							$name,
							get_the_title( $car_id ),
							$wanted ? ' ' . sprintf( /* translators: %s: the dates the customer originally asked for */ __( 'You had asked about %s.', 'car-rental-manager' ), $wanted ) : '',
							get_permalink( $car_id )
						)
					);
				}
				update_post_meta( $entry_id, 'mpcrbm_wl_status', 'notified' );

				wp_send_json_success( [ 'html' => self::render_list() ] );
			}

			/**
			 * Lets the admin record what actually happened after a "Notified"
			 * email went out, since nothing here can detect that on its own —
			 * see this file's top comment on why this stays event-driven
			 * rather than date-matched.
			 */
			public function ajax_mark_waitlist_outcome() {
				check_ajax_referer( 'mpcrbm_waitlist_admin', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
				}

				$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
				$outcome  = isset( $_POST['outcome'] ) ? sanitize_key( wp_unslash( $_POST['outcome'] ) ) : '';
				if ( ! $entry_id || get_post_type( $entry_id ) !== self::CPT ) {
					wp_send_json_error( [ 'message' => __( 'Entry not found.', 'car-rental-manager' ) ] );
				}
				if ( ! in_array( $outcome, [ 'booked', 'no_response' ], true ) ) {
					wp_send_json_error( [ 'message' => __( 'Unknown outcome.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $entry_id, 'mpcrbm_wl_status', $outcome );

				wp_send_json_success( [ 'html' => self::render_list() ] );
			}

			public function ajax_dismiss_waitlist_entry() {
				check_ajax_referer( 'mpcrbm_waitlist_admin', 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
				}

				$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
				if ( ! $entry_id || get_post_type( $entry_id ) !== self::CPT ) {
					wp_send_json_error( [ 'message' => __( 'Entry not found.', 'car-rental-manager' ) ] );
				}

				update_post_meta( $entry_id, 'mpcrbm_wl_status', 'dismissed' );

				wp_send_json_success( [ 'html' => self::render_list() ] );
			}

			// =========================================================
			// Admin page
			// =========================================================
			private static function status_badge( $status ) {
				$map = [
					'pending'     => [ '#b45309', '#fffbeb', __( 'Pending', 'car-rental-manager' ) ],
					'notified'    => [ '#166534', '#f0fdf4', __( 'Notified', 'car-rental-manager' ) ],
					'booked'      => [ '#1d4ed8', '#eff6ff', __( 'Booked', 'car-rental-manager' ) ],
					'no_response' => [ '#9333ea', '#faf5ff', __( 'No Response', 'car-rental-manager' ) ],
					'dismissed'   => [ '#6b7280', '#f3f4f6', __( 'Dismissed', 'car-rental-manager' ) ],
				];
				[ $color, $bg, $label ] = $map[ $status ] ?? $map['pending'];

				return sprintf(
					'<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;color:%s;background:%s;">%s</span>',
					esc_attr( $color ),
					esc_attr( $bg ),
					esc_html( $label )
				);
			}

			public static function render_list(): string {
				$query = new WP_Query( [
					'post_type'      => self::CPT,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				] );

				ob_start();

				if ( ! $query->have_posts() ) {
					?>
					<div class="mpcrbm-wl-empty">
						<i class="fas fa-clock"></i>
						<p><?php esc_html_e( 'No waitlist entries yet.', 'car-rental-manager' ); ?></p>
						<span><?php esc_html_e( 'When a customer joins the waitlist for a fully booked car, it will show up here.', 'car-rental-manager' ); ?></span>
					</div>
					<?php
					wp_reset_postdata();

					return (string) ob_get_clean();
				}

				echo '<div class="mpcrbm-wl-list">';
				while ( $query->have_posts() ) {
					$query->the_post();
					$id      = get_the_ID();
					$car_id  = (int) get_post_meta( $id, 'mpcrbm_wl_car_id', true );
					$name    = get_post_meta( $id, 'mpcrbm_wl_name', true );
					$email   = get_post_meta( $id, 'mpcrbm_wl_email', true );
					$phone   = get_post_meta( $id, 'mpcrbm_wl_phone', true );
					$pickup  = get_post_meta( $id, 'mpcrbm_wl_pickup', true );
					$return  = get_post_meta( $id, 'mpcrbm_wl_return', true );
					$status  = get_post_meta( $id, 'mpcrbm_wl_status', true ) ?: 'pending';
					$car_name = $car_id ? get_the_title( $car_id ) : __( '(vehicle removed)', 'car-rental-manager' );
					?>
					<div class="mpcrbm-wl-card" data-entry-id="<?php echo esc_attr( $id ); ?>">
						<div class="mpcrbm-wl-card-top">
							<div>
								<strong class="mpcrbm-wl-car"><?php echo esc_html( $car_name ); ?></strong>
								<span class="mpcrbm-wl-date"><?php echo esc_html( get_the_date( 'j M Y, g:ia' ) ); ?></span>
							</div>
							<?php echo wp_kses_post( self::status_badge( $status ) ); ?>
						</div>
						<div class="mpcrbm-wl-card-body">
							<div><strong><?php esc_html_e( 'Customer:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $name ); ?> — <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php echo $phone ? ' — ' . esc_html( $phone ) : ''; ?></div>
							<?php if ( $pickup || $return ) : ?>
								<div><strong><?php esc_html_e( 'Wanted dates:', 'car-rental-manager' ); ?></strong> <?php echo esc_html( $pickup ); ?><?php echo $return ? ' → ' . esc_html( $return ) : ''; ?></div>
							<?php endif; ?>
						</div>
						<?php if ( 'pending' === $status ) : ?>
							<div class="mpcrbm-wl-card-actions">
								<button type="button" class="button button-primary mpcrbm-wl-notify-btn" data-entry-id="<?php echo esc_attr( $id ); ?>">
									<?php esc_html_e( 'Notify Now', 'car-rental-manager' ); ?>
								</button>
								<button type="button" class="button mpcrbm-wl-dismiss-btn" data-entry-id="<?php echo esc_attr( $id ); ?>">
									<?php esc_html_e( 'Dismiss', 'car-rental-manager' ); ?>
								</button>
							</div>
						<?php elseif ( 'notified' === $status ) : ?>
							<div class="mpcrbm-wl-card-actions">
								<span class="mpcrbm-wl-outcome-prompt"><?php esc_html_e( 'Did they book?', 'car-rental-manager' ); ?></span>
								<button type="button" class="button mpcrbm-wl-outcome-btn" data-entry-id="<?php echo esc_attr( $id ); ?>" data-outcome="booked">
									<?php esc_html_e( 'Yes, Booked', 'car-rental-manager' ); ?>
								</button>
								<button type="button" class="button mpcrbm-wl-outcome-btn" data-entry-id="<?php echo esc_attr( $id ); ?>" data-outcome="no_response">
									<?php esc_html_e( 'No Response', 'car-rental-manager' ); ?>
								</button>
							</div>
						<?php endif; ?>
					</div>
					<?php
				}
				echo '</div>';
				wp_reset_postdata();

				return (string) ob_get_clean();
			}

			public function render_page() {
				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Waitlist', 'car-rental-manager' ) );
				$admin_nonce = wp_create_nonce( 'mpcrbm_waitlist_admin' );
				?>
				<style>
				.mpcrbm-wl-head{margin-bottom:20px;}
				.mpcrbm-wl-head h2{margin:0 0 6px;font-size:22px;}
				.mpcrbm-wl-head p{margin:0;color:#6b7280;font-size:13px;}
				.mpcrbm-wl-empty{text-align:center;padding:60px 20px;color:#9ca3af;}
				.mpcrbm-wl-empty i{font-size:32px;margin-bottom:10px;display:block;}
				.mpcrbm-wl-list{display:flex;flex-direction:column;gap:12px;}
				.mpcrbm-wl-card{background:#fff;border:1px solid #e5e9f0;border-radius:10px;padding:16px 18px;}
				.mpcrbm-wl-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
				.mpcrbm-wl-car{font-size:15px;color:#111827;}
				.mpcrbm-wl-date{margin-left:10px;font-size:12px;color:#9ca3af;}
				.mpcrbm-wl-card-body{font-size:13px;color:#374151;display:flex;flex-direction:column;gap:4px;}
				.mpcrbm-wl-card-actions{margin-top:12px;display:flex;gap:8px;align-items:center;}
				.mpcrbm-wl-outcome-prompt{font-size:12px;color:#6b7280;margin-right:2px;}

				.mpcrbm-wl-guide{border:1px solid #e5e9f0;border-radius:12px;background:#fff;margin-bottom:24px;overflow:hidden;}
				.mpcrbm-wl-guide-toggle{width:100%;text-align:left;background:linear-gradient(135deg,#ecfeff,#eff6ff);border:none;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;gap:12px;}
				.mpcrbm-wl-guide-toggle-left{display:flex;align-items:center;gap:12px;}
				.mpcrbm-wl-guide-toggle-left i{font-size:18px;color:#0891b2;width:34px;height:34px;border-radius:10px;background:#cffafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
				.mpcrbm-wl-guide-toggle-left strong{display:block;font-size:14px;color:#164e63;}
				.mpcrbm-wl-guide-toggle-left span{display:block;font-size:12px;color:#0891b2;}
				.mpcrbm-wl-guide-toggle-caret{color:#0891b2;transition:transform .2s;}
				.mpcrbm-wl-guide.is-collapsed .mpcrbm-wl-guide-toggle-caret{transform:rotate(-90deg);}
				.mpcrbm-wl-guide-body{padding:22px 24px;}
				.mpcrbm-wl-guide.is-collapsed .mpcrbm-wl-guide-body{display:none;}
				.mpcrbm-wl-guide-why{display:flex;gap:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-bottom:20px;}
				.mpcrbm-wl-guide-why i{color:#2563eb;font-size:18px;margin-top:2px;}
				.mpcrbm-wl-guide-why strong{display:block;color:#1e3a8a;font-size:13px;margin-bottom:3px;}
				.mpcrbm-wl-guide-why span{color:#1e40af;font-size:13px;line-height:1.6;}
				.mpcrbm-wl-guide-note{display:flex;gap:12px;background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin-bottom:20px;}
				.mpcrbm-wl-guide-note i{color:#b45309;font-size:18px;margin-top:2px;}
				.mpcrbm-wl-guide-note strong{display:block;color:#78350f;font-size:13px;margin-bottom:3px;}
				.mpcrbm-wl-guide-note span{color:#92400e;font-size:13px;line-height:1.6;}
				.mpcrbm-wl-guide-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;margin:0 0 12px;}
				.mpcrbm-wl-howto{list-style:none;margin:0 0 20px;padding:0;display:flex;flex-direction:column;gap:10px;}
				.mpcrbm-wl-howto li{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#374151;line-height:1.6;}
				.mpcrbm-wl-howto li b{background:#111827;color:#fff;border-radius:50%;width:20px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;margin-top:1px;}
				.mpcrbm-wl-howto li code{background:#f3f4f6;padding:1px 6px;border-radius:4px;font-size:12px;}
				.mpcrbm-wl-legend{display:flex;flex-wrap:wrap;gap:18px;background:#f9fafb;border-radius:10px;padding:12px 16px;}
				.mpcrbm-wl-legend-item{display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;}
				</style>

				<div class="mpcrbm-wl-head">
					<h2><?php esc_html_e( 'Waitlist', 'car-rental-manager' ); ?></h2>
					<p><?php esc_html_e( 'Customers who wanted a car that was already booked for their dates, and left their details to be notified if it frees up.', 'car-rental-manager' ); ?></p>
				</div>

				<div class="mpcrbm-wl-guide" id="mpcrbm-wl-guide">
					<button type="button" class="mpcrbm-wl-guide-toggle" id="mpcrbm-wl-guide-toggle">
						<span class="mpcrbm-wl-guide-toggle-left">
							<i class="fas fa-circle-info"></i>
							<span>
								<strong><?php esc_html_e( 'What is this page & how do I use it?', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'Click to read a quick guide', 'car-rental-manager' ); ?></span>
							</span>
						</span>
						<i class="fas fa-chevron-down mpcrbm-wl-guide-toggle-caret"></i>
					</button>
					<div class="mpcrbm-wl-guide-body">
						<div class="mpcrbm-wl-guide-why">
							<i class="fas fa-lightbulb"></i>
							<div>
								<strong><?php esc_html_e( 'Why this exists', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'A car that is fully booked for a customer\'s chosen date used to just show "not available", and the customer left. This lets them leave their contact details instead, so you can win the booking back if a cancellation frees the car up.', 'car-rental-manager' ); ?></span>
							</div>
						</div>
						<div class="mpcrbm-wl-guide-note">
							<i class="fas fa-triangle-exclamation"></i>
							<div>
								<strong><?php esc_html_e( 'Important: this checks "was this car freed up", not "are these exact dates free"', 'car-rental-manager' ); ?></strong>
								<span><?php esc_html_e( 'When any booking for a car is cancelled or refunded, everyone waiting on that car is emailed to check again — it does not automatically confirm their specific dates are the ones that opened up. Always confirm availability before promising the customer the booking.', 'car-rental-manager' ); ?></span>
							</div>
						</div>
						<p class="mpcrbm-wl-guide-section-title"><?php esc_html_e( 'Using this page', 'car-rental-manager' ); ?></p>
						<ul class="mpcrbm-wl-howto">
							<li><b>1</b><span><?php echo wp_kses_post( __( 'First, turn the feature on: go to <code>Settings → General → Enable "Join Waitlist" On Fully Booked Dates</code> and set it to Yes.', 'car-rental-manager' ) ); ?></span></li>
							<li><b>2</b><span><?php esc_html_e( 'When a customer tries to book a car that is already taken for their date, they can join the waitlist instead. New entries show up here and you get an email right away.', 'car-rental-manager' ); ?></span></li>
							<li><b>3</b><span><?php esc_html_e( 'If a booking for that car is cancelled or refunded (WooCommerce orders), everyone on the waitlist for it is emailed automatically and marked "Notified".', 'car-rental-manager' ); ?></span></li>
							<li><b>4</b><span><?php esc_html_e( 'You can also click "Notify Now" any time to email a customer yourself — useful for Custom Payment bookings, which are not auto-detected yet.', 'car-rental-manager' ); ?></span></li>
							<li><b>5</b><span><?php esc_html_e( 'Click "Dismiss" to close an entry without emailing — for example, once you\'ve booked them in some other way.', 'car-rental-manager' ); ?></span></li>
							<li><b>6</b><span><?php esc_html_e( 'Once an entry is "Notified", nothing here can tell whether the customer actually booked — click "Yes, Booked" or "No Response" yourself to record the outcome and keep the list useful.', 'car-rental-manager' ); ?></span></li>
						</ul>
						<p class="mpcrbm-wl-guide-section-title"><?php esc_html_e( 'Status colors', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-wl-legend">
							<div class="mpcrbm-wl-legend-item"><?php echo wp_kses_post( self::status_badge( 'pending' ) ); ?> <?php esc_html_e( 'waiting to be notified', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-wl-legend-item"><?php echo wp_kses_post( self::status_badge( 'notified' ) ); ?> <?php esc_html_e( 'already emailed, outcome not yet recorded', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-wl-legend-item"><?php echo wp_kses_post( self::status_badge( 'booked' ) ); ?> <?php esc_html_e( 'customer booked after being notified', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-wl-legend-item"><?php echo wp_kses_post( self::status_badge( 'no_response' ) ); ?> <?php esc_html_e( 'notified, customer didn\'t book', 'car-rental-manager' ); ?></div>
							<div class="mpcrbm-wl-legend-item"><?php echo wp_kses_post( self::status_badge( 'dismissed' ) ); ?> <?php esc_html_e( 'closed, no email sent', 'car-rental-manager' ); ?></div>
						</div>
					</div>
				</div>

				<script>
				(function($){
					$(document).on('click', '#mpcrbm-wl-guide-toggle', function(){
						$('#mpcrbm-wl-guide').toggleClass('is-collapsed');
						try {
							localStorage.setItem('mpcrbm_wl_guide_collapsed', $('#mpcrbm-wl-guide').hasClass('is-collapsed') ? '1' : '0');
						} catch(e) {}
					});
					try {
						if( localStorage.getItem('mpcrbm_wl_guide_collapsed') === '1' ){
							$('#mpcrbm-wl-guide').addClass('is-collapsed');
						}
					} catch(e) {}
				})(jQuery);
				</script>

				<div id="mpcrbm-wl-list-holder"><?php echo self::render_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html()/esc_attr()'d pieces above. ?></div>

				<script>
				(function($){
					'use strict';
					var ajaxUrl    = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
					var adminNonce = '<?php echo esc_js( $admin_nonce ); ?>';

					$(document).on('click', '.mpcrbm-wl-notify-btn', function(){
						var $btn = $(this);
						var entryId = $btn.data('entry-id');
						$btn.prop('disabled', true);
						$.post(ajaxUrl, { action:'mpcrbm_notify_waitlist_entry', nonce:adminNonce, entry_id:entryId }, function(r){
							if( r.success ){ $('#mpcrbm-wl-list-holder').html(r.data.html); }
							else { $btn.prop('disabled', false); alert( (r.data && r.data.message) || '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'car-rental-manager' ) ); ?>' ); }
						}).fail(function(){
							$btn.prop('disabled', false);
							alert('<?php echo esc_js( __( 'Something went wrong. Please try again.', 'car-rental-manager' ) ); ?>');
						});
					});

					$(document).on('click', '.mpcrbm-wl-dismiss-btn', function(){
						if( !confirm('<?php echo esc_js( __( 'Dismiss this entry without emailing the customer?', 'car-rental-manager' ) ); ?>') ){ return; }
						var entryId = $(this).data('entry-id');
						$.post(ajaxUrl, { action:'mpcrbm_dismiss_waitlist_entry', nonce:adminNonce, entry_id:entryId }, function(r){
							if( r.success ){ $('#mpcrbm-wl-list-holder').html(r.data.html); }
						});
					});

					$(document).on('click', '.mpcrbm-wl-outcome-btn', function(){
						var $btn = $(this);
						var entryId = $btn.data('entry-id');
						var outcome = $btn.data('outcome');
						$btn.closest('.mpcrbm-wl-card-actions').find('button').prop('disabled', true);
						$.post(ajaxUrl, { action:'mpcrbm_mark_waitlist_outcome', nonce:adminNonce, entry_id:entryId, outcome:outcome }, function(r){
							if( r.success ){ $('#mpcrbm-wl-list-holder').html(r.data.html); }
							else { alert( (r.data && r.data.message) || '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'car-rental-manager' ) ); ?>' ); }
						}).fail(function(){
							alert('<?php echo esc_js( __( 'Something went wrong. Please try again.', 'car-rental-manager' ) ); ?>');
						});
					});
				})(jQuery);
				</script>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}
		}

		new MPCRBM_Waitlist();
	}
