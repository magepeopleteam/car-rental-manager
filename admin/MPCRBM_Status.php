<?php
	/*
   * @Author 		MagePeople Team
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Status' ) ) {
		class MPCRBM_Status {
			public function __construct() {
				add_action( 'admin_menu', array( $this, 'status_menu' ) );
			}

			public function status_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page( 'edit.php?post_type=' . $cpt, esc_html__( 'Status', 'car-rental-manager' ), '<span style="color:yellow">' . esc_html__( 'Status', 'car-rental-manager' ) . '</span>', 'manage_options', 'mpcrbm_status_page', array( $this, 'status_page' ) );
			}

			public function status_page() {
				$label      = MPCRBM_Function::get_name();
				$wc_i       = MPCRBM_Global_Function::check_woocommerce();
				$wc_i_text  = $wc_i == 1 ? esc_html__( 'Yes', 'car-rental-manager' ) : esc_html__( 'No', 'car-rental-manager' );
				$wp_v       = get_bloginfo( 'version' );
				$wc_v       = WC()->version;
                $is_modern = version_compare( $wc_v, '4.8', '>' );
				$from_name  = get_option( 'woocommerce_email_from_name' );
				$from_email = get_option( 'woocommerce_email_from_address' );
				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Status', 'car-rental-manager' ) );
				?>
				<style>
				/* Scoped to this page's own table wrapper — .textSuccess/.textWarning
				   are shared global text-color utility classes (also used by
				   MPCRBM_License.php and by car-rental-manager-pro's
				   MPCRBM_Admin_Pro::status_table_item_sec(), which appends raw
				   <tr><th>label</th><th class="textSuccess|textWarning">...</th></tr>
				   rows straight into this same <table> via the
				   "mpcrbm_status_table_item_sec" action) — restyled as pill badges
				   only inside .mpcrbm-status-table, so the plain-text look those
				   classes have everywhere else is untouched. The <table>/<tr>/<th>
				   structure itself is kept exactly as before for the same reason:
				   the pro plugin's hook output assumes this exact shape.
				*/
				.mpcrbm-status-hero {
					background: linear-gradient(135deg, #eef4ff, #f5f8ff);
					border: 1px solid #d7e3fb;
					border-radius: var(--mpcrbm-shell-radius, 16px);
					padding: 32px;
					margin-bottom: 24px;
				}
				.mpcrbm-status-eyebrow {
					display: flex !important;
					align-items: center;
					gap: 8px;
					font-size: 12px;
					font-weight: 700;
					letter-spacing: .06em;
					text-transform: uppercase;
					color: var(--mpcrbm-shell-primary, #1d7bff);
					margin-bottom: 10px;
				}
				.mpcrbm-status-eyebrow-dot {
					width: 7px;
					height: 7px;
					border-radius: 50%;
					background: var(--mpcrbm-shell-primary, #1d7bff);
				}
				.mpcrbm-status-hero h1 {
					margin: 0 0 10px;
					font-size: 26px;
					font-weight: 700;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-status-hero p {
					margin: 0;
					max-width: 640px;
					font-size: 14px;
					line-height: 1.6;
					color: var(--mpcrbm-shell-text-faded, #788291);
				}

				.mpcrbm-status-card-header i {
					width: 34px;
					height: 34px;
					border-radius: 10px;
					background: #eff6ff;
					color: var(--mpcrbm-shell-primary, #1d7bff);
					display: flex !important;
					align-items: center;
					justify-content: center;
					font-size: 14px;
					flex-shrink: 0;
				}
				.mpcrbm-status-card-header-text {
					display: flex;
					align-items: center;
					gap: 12px;
				}
				.mpcrbm-shell-body .mpcrbm-card-header.mpcrbm-status-card-header {
					justify-content: flex-start;
				}

				.mpcrbm-status-table {
					width: 100%;
					table-layout: fixed;
					border-collapse: collapse;
				}
				.mpcrbm-status-table tr {
					border-bottom: 1px solid #eef0f3;
				}
				.mpcrbm-status-table tr:last-child {
					border-bottom: none;
				}
				.mpcrbm-status-table th {
					text-align: left;
					font-weight: 400;
					padding: 14px 4px;
					font-size: 13.5px;
					color: var(--mpcrbm-shell-text-faded, #788291);
				}
				.mpcrbm-status-table th:first-child {
					width: 70%;
					font-weight: 600;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-status-table th:last-child {
					width: 30%;
					text-align: right;
				}
				.mpcrbm-status-table .textSuccess,
				.mpcrbm-status-table .textWarning {
					display: inline-flex !important;
					align-items: center;
					gap: 6px;
					padding: 5px 12px;
					border-radius: 100px;
					font-size: 12px;
					font-weight: 600;
				}
				.mpcrbm-status-table .textSuccess {
					background: #dcfce7;
					color: #15803d !important;
				}
				.mpcrbm-status-table .textWarning {
					background: #fef3c7;
					color: #92400e !important;
				}
				</style>

				<div class="mpcrbm-status-hero">
					<div class="mpcrbm-status-eyebrow">
						<span class="mpcrbm-status-eyebrow-dot"></span>
						<?php esc_html_e( 'System Status', 'car-rental-manager' ); ?>
					</div>
					<h1>
						<?php
						/* translators: %s: the plugin's configurable "Car" label */
						echo esc_html( sprintf( __( '%s Environment Status', 'car-rental-manager' ), $label ) );
						?>
					</h1>
					<p><?php esc_html_e( 'A quick health check of the WordPress/WooCommerce environment this plugin depends on — useful before reporting an issue to support.', 'car-rental-manager' ); ?></p>
				</div>

                <div class="mpcrbm-card">
					<div class="mpcrbm-card-header mpcrbm-status-card-header">
						<div class="mpcrbm-status-card-header-text">
							<i class="fas fa-heartbeat"></i>
							<h3><?php esc_html_e( 'Environment Checks', 'car-rental-manager' ); ?></h3>
						</div>
					</div>
                <div class="mpcrbm-card-content">
					<?php do_action( 'mpcrbm_status_notice_sec' ); ?>
                    <table class="mpcrbm-status-table">
                        <tbody>
                        <tr>
                            <th data-export-label="WC Version"><?php esc_html_e( 'WordPress Version', 'car-rental-manager' ); ?></th>
                            <th class="<?php echo esc_attr( $wp_v > 5.5 ? 'textSuccess' : 'textWarning' ); ?>">
                                <span class="<?php echo esc_attr( $wp_v > 5.5 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wp_v ); ?>
                            </th>
                        </tr>
                        <tr>
                            <th data-export-label="WC Version"><?php esc_html_e( 'WooCommerce Installed', 'car-rental-manager' ); ?></th>
                            <th class="<?php echo esc_attr( $wc_i == 1 ? 'textSuccess' : 'textWarning' ); ?>">
                                <span class="<?php echo esc_attr( $wc_i == 1 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wc_i_text ); ?>
                            </th>
                        </tr>
						<?php if ( $wc_i == 1 ) { ?>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e( 'WooCommerce Version', 'car-rental-manager' ); ?></th>
                                <th class="<?php echo esc_attr( $is_modern ? 'textSuccess' : 'textWarning' ); ?>">
                                    <span class="<?php echo esc_attr( $is_modern ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $wc_v ); ?>
                                </th>
                            </tr>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e( 'WooCommerce Email Name', 'car-rental-manager' ); ?></th>
                                <th class="<?php echo esc_attr( $from_name ? 'textSuccess' : 'textWarning' ); ?>">
                                    <span class="<?php echo esc_attr( $from_name ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $from_name ); ?>
                                </th>
                            </tr>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e( 'WooCommerce Email Address', 'car-rental-manager' ); ?></th>
                                <th class="<?php echo esc_attr( $from_email ? 'textSuccess' : 'textWarning' ); ?>">
                                    <span class="<?php echo esc_attr( $from_email ? 'far fa-check-circle' : 'fas fa-exclamation-triangle' ); ?> mR_xs"></span><?php echo esc_html( $from_email ); ?>
                                </th>
                            </tr>
						<?php }
							do_action( 'mpcrbm_status_table_item_sec' ); ?>
                        </tbody>
                    </table>
                </div>
                </div>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}
		}
		new MPCRBM_Status();
	}
