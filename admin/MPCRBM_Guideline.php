<?php
	/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Guideline')) {
		class MPCRBM_Guideline {
			public function __construct() {
				add_action('admin_menu', array($this, 'guideline_menu'));
			}
			public function guideline_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Guideline', 'car-rental-manager'), '<span>' . esc_html__('Guideline', 'car-rental-manager') . '</span>', 'manage_options', 'mpcrbm_guideline_page', array($this, 'guideline_page'));
			}

			/**
			 * One entry in the quick-nav / card list: icon, title, and the
			 * anchor id its card uses, so both stay in sync from one place.
			 */
			private function sections() {
				return array(
					'overview'      => array('icon' => 'fas fa-compass', 'title' => esc_html__('Getting Started', 'car-rental-manager')),
					'shortcode'     => array('icon' => 'fas fa-code', 'title' => esc_html__('Shortcodes', 'car-rental-manager')),
					'cars'          => array('icon' => 'fas fa-car-side', 'title' => esc_html__('Adding & Managing Cars', 'car-rental-manager')),
					'pricing'       => array('icon' => 'fas fa-money-bill-wave', 'title' => esc_html__('Pricing & Discounts', 'car-rental-manager')),
					'schedule'      => array('icon' => 'fas fa-calendar-check', 'title' => esc_html__('Availability & Schedule', 'car-rental-manager')),
					'locations'     => array('icon' => 'fas fa-map-pin', 'title' => esc_html__('Multi-Location & Fees', 'car-rental-manager')),
					'branches'      => array('icon' => 'fas fa-code-branch', 'title' => esc_html__('Drivers & Branches', 'car-rental-manager')),
					'faq'           => array('icon' => 'fas fa-circle-question', 'title' => esc_html__('FAQs & Terms and Conditions', 'car-rental-manager')),
					'services'      => array('icon' => 'fas fa-shopping-basket', 'title' => esc_html__('Extra Services & Features', 'car-rental-manager')),
					'securehold'    => array('icon' => 'fas fa-lock', 'title' => esc_html__('SecureHold Deposit Holds', 'car-rental-manager')),
					'help'          => array('icon' => 'fas fa-life-ring', 'title' => esc_html__('Need Help?', 'car-rental-manager')),
				);
			}

			public function guideline_page() {
				$label   = MPCRBM_Function::get_name();
				$cpt     = MPCRBM_Function::get_cpt();
				$new_url = admin_url( 'post-new.php?post_type=' . $cpt );
				$dash_url = admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_car_rental' );
				$loc_url  = admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_car_rental&mpcrbm_tab=mpcrbm_branch_manager' );
				$ex_url   = admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_ex_services_manager' );
				$bm_url   = admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_branch_managers' );
				$sections = $this->sections();

				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Guideline', 'car-rental-manager' ) );
				?>
				<style>
				.mpcrbm-guide-hero {
					background: linear-gradient(135deg, #eef4ff, #f5f8ff);
					border: 1px solid #d7e3fb;
					border-radius: var(--mpcrbm-shell-radius, 16px);
					padding: 32px;
					margin-bottom: 24px;
				}
				.mpcrbm-guide-eyebrow {
					display: flex;
					align-items: center;
					gap: 8px;
					font-size: 12px;
					font-weight: 700;
					letter-spacing: .06em;
					text-transform: uppercase;
					color: var(--mpcrbm-shell-primary, #1d7bff);
					margin-bottom: 10px;
				}
				.mpcrbm-guide-eyebrow-dot {
					width: 7px;
					height: 7px;
					border-radius: 50%;
					background: var(--mpcrbm-shell-primary, #1d7bff);
				}
				.mpcrbm-guide-hero h1 {
					margin: 0 0 10px;
					font-size: 28px;
					font-weight: 700;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-hero p {
					margin: 0 0 22px;
					font-size: 14px !important;
					max-width: 80%;
					margin-bottom: 20px !important;
					font-size: 14px;
					line-height: 1.6;
					color: var(--mpcrbm-shell-text-faded, #788291);
				}
				.mpcrbm-guide-nav {
					display: flex;
					flex-wrap: wrap;
					gap: 8px;
				}
				.mpcrbm-guide-nav a {
					display: inline-flex;
					align-items: center;
					gap: 7px;
					padding: 8px 14px;
					background: #fff;
					border: 1px solid #d7e3fb;
					border-radius: 100px;
					font-size: 12px;
					font-weight: 600;
					color: var(--mpcrbm-shell-text, #1f222b);
					text-decoration: none;
					transition: border-color .15s ease, background-color .15s ease;
				}
				.mpcrbm-guide-nav a:hover {
					border-color: var(--mpcrbm-shell-primary, #1d7bff);
					background: #eff6ff;
					color: var(--mpcrbm-shell-primary, #1d7bff);
				}
				.mpcrbm-guide-nav a i {
					font-size: 11px;
					color: var(--mpcrbm-shell-primary, #1d7bff);
				}

				.mpcrbm-guide-card-header i {
					width: 34px;
					height: 34px;
					border-radius: 10px;
					background: #eff6ff;
					color: var(--mpcrbm-shell-primary, #1d7bff);
					display: flex;
					align-items: center;
					justify-content: center;
					font-size: 14px;
					flex-shrink: 0;
				}
				.mpcrbm-guide-card-header-text {
					display: flex;
					align-items: center;
					gap: 12px;
				}
				.mpcrbm-shell-body .mpcrbm-card-header.mpcrbm-guide-card-header {
					justify-content: flex-start;
				}

				.mpcrbm-guide-intro {
					margin: 0 0 20px;
					font-size: 12px;
					line-height: 1.65;
					color: var(--mpcrbm-shell-text-faded, #788291);
				}
				.mpcrbm-guide-grid {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 16px;
					align-items: stretch;
					margin-top: 8px;
				}
				@media (max-width: 900px) {
					.mpcrbm-guide-grid {
						grid-template-columns: 1fr;
					}
				}
				.mpcrbm-guide-block {
					background: var(--mpcrbm-shell-bg, #f8fafc);
					border: 1px solid #eef0f3;
					border-radius: 10px;
					padding: 20px 22px;
				}
				.mpcrbm-guide-block h4 {
					display: flex;
					align-items: center;
					gap: 10px;
					margin-top: 0;
					margin-bottom: 10px !important;
					font-size: 16px !important;
					font-weight: 700;
					line-height: 1.4;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-block h4 i {
					width: 26px;
					height: 26px;
					border-radius: 8px;
					background: #eff6ff;
					color: var(--mpcrbm-shell-primary, #1d7bff);
					display: flex !important;
					align-items: center;
					justify-content: center;
					font-size: 12px;
					flex-shrink: 0;
				}
				.mpcrbm-guide-block ul {
					list-style: none;
					margin: 0;
					padding-left: 0;
				}
				.mpcrbm-guide-block li {
					position: relative;
					/* !important: ".mpcrbm ul li { padding: 0 }" (mp_global/
					   assets/mp_style/mpcrbm_global.css) has higher specificity
					   (class + 2 elements vs. class + 1 element here) and was
					   silently canceling this padding, collapsing the text back
					   under the dot marker below. */
					/* 36px = h4 icon badge (26px) + its gap (10px), so this text
					   lines up under the h4 title text, not the icon badge. */
					padding-left: 36px !important;
					font-size: 13.5px;
					line-height: 1.75;
					color: #4b5563;
				}
				.mpcrbm-guide-block li::before {
					content: "";
					position: absolute;
					left: 13px;
					top: 50%;
					transform: translateY(-50%);
					width: 5px;
					height: 5px;
					border-radius: 50%;
					background: var(--mpcrbm-shell-primary, #1d7bff);
				}
				.mpcrbm-guide-block li + li {
					margin-top: 9px;
				}
				.mpcrbm-guide-block li strong {
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-where {
					display: inline-flex;
					align-items: center;
					gap: 6px;
					margin-top: 14px;
					padding: 8px 14px;
					background: var(--mpcrbm-shell-bg, #f5f6fa);
					border-radius: 8px;
					font-size: 12px;
					color: #6b7280;
				}
				.mpcrbm-guide-where b {
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-link-btn {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					margin-top: 20px;
					padding: 10px 18px;
					background: var(--mpcrbm-shell-primary, #1d7bff);
					color: #fff !important;
					border-radius: 8px;
					font-size: 13px;
					font-weight: 600;
					text-decoration: none;
				}
				/* SecureHold section: numbered setup steps and screenshots. */
				.mpcrbm-guide-steps {
					margin: 0 0 4px;
					padding-left: 20px !important;
				}
				.mpcrbm-guide-steps li {
					margin: 0 0 8px;
					padding: 0 !important;
					font-size: 13.5px;
					line-height: 1.7;
					color: #4b5563;
					list-style: decimal;
				}
				.mpcrbm-guide-steps li strong {
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-shots {
					display: grid;
					grid-template-columns: repeat(2, minmax(0, 1fr));
					gap: 16px;
					margin: 16px 0 4px;
				}
				.mpcrbm-guide-shots.is-single {
					grid-template-columns: minmax(0, 1fr);
				}
				@media (max-width: 900px) {
					.mpcrbm-guide-shots {
						grid-template-columns: minmax(0, 1fr);
					}
				}
				.mpcrbm-guide-shot {
					margin: 0;
					padding: 12px;
					background: var(--mpcrbm-shell-bg, #f8fafc);
					border: 1px solid #eef0f3;
					border-radius: 10px;
				}
				.mpcrbm-guide-shot a {
					display: block;
				}
				/* Scoped: a global image rule stretches every img to the column width,
				   which blurs small screenshots. Show each at its natural size. */
				.mpcrbm-shell-body .mpcrbm-guide-shot img {
					display: block;
					width: auto;
					max-width: 100%;
					height: auto;
					margin: 0 auto;
					border: 1px solid #e5e7eb;
					border-radius: 8px;
					background: #fff;
				}
				.mpcrbm-guide-shot figcaption {
					margin-top: 10px;
					font-size: 12.5px;
					line-height: 1.55;
					color: #6b7280;
				}
				.mpcrbm-guide-shot figcaption b {
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-link-btn:hover {
					background: var(--mpcrbm-shell-primary-dark, #1465d6);
					color: #fff;
				}
				.mpcrbm-guide-shortcode-box {
					background: #f8fafc;
					border: 1px solid #e2e8f0;
					border-radius: 10px;
					padding: 16px 18px;
					margin-bottom: 16px;
				}
				.mpcrbm-guide-shortcode-row {
					display: flex;
					align-items: center;
					justify-content: space-between;
					gap: 10px;
				}
				.mpcrbm-guide-shortcode-box code {
					font-family: Consolas, Monaco, "Courier New", monospace;
					font-size: 12.5px;
					color: #1d4ed8;
					background: #eef2ff;
					padding: 5px 10px;
					border-radius: 6px;
					word-break: break-all;
				}
				.mpcrbm-guide-copy-btn {
					flex-shrink: 0;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					width: 30px;
					height: 30px;
					padding: 0;
					background: #fff;
					border: 1px solid #e2e8f0;
					border-radius: 7px;
					color: #6b7280;
					font-size: 12px;
					cursor: pointer;
					transition: background .15s ease, color .15s ease, border-color .15s ease;
				}
				.mpcrbm-guide-copy-btn:hover {
					background: #eff6ff;
					border-color: var(--mpcrbm-shell-primary, #1d7bff);
					color: var(--mpcrbm-shell-primary, #1d7bff);
				}
				.mpcrbm-guide-copy-btn.is-copied {
					background: #dcfce7;
					border-color: #86efac;
					color: #15803d;
				}
				.mpcrbm-guide-shortcode-box p {
					margin: 8px 0 0;
					font-size: 12.5px;
					line-height: 1.6;
					color: #6b7280;
				}
				.mpcrbm-guide-subheading {
					margin: 0 0 10px;
					font-size: 13px;
					font-weight: 700;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-subheading:not(:first-child) {
					margin-top: 26px;
				}
				.mpcrbm-guide-param-table + .mpcrbm-guide-subheading {
					margin-top: 26px;
				}
				h4.mpcrbm-guide-subheading {
					margin-bottom: 20px !important;
					font-size: 16px !important;
				}
				.mpcrbm-guide-param-table {
					width: 100%;
					border-collapse: collapse;
				}
				.mpcrbm-guide-param-table th,
				.mpcrbm-guide-param-table td {
					text-align: left;
					padding: 10px 12px;
					font-size: 13px;
					border-bottom: 1px solid #eef0f3;
					vertical-align: top;
				}
				.mpcrbm-guide-param-table th {
					font-size: 11px;
					font-weight: 700;
					letter-spacing: .04em;
					text-transform: uppercase;
					color: #9ca3af;
				}
				.mpcrbm-guide-param-table code {
					color: #1d4ed8;
				}
				.mpcrbm-guide-help-grid {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 16px;
				}
				@media (max-width: 900px) {
					.mpcrbm-guide-help-grid {
						grid-template-columns: 1fr;
					}
				}
				.mpcrbm-guide-help-item {
					padding: 18px;
					background: var(--mpcrbm-shell-bg, #f5f6fa);
					border-radius: 10px;
				}
				.mpcrbm-guide-help-item i {
					color: var(--mpcrbm-shell-primary, #1d7bff);
					font-size: 18px;
					margin-bottom: 10px;
					display: block;
				}
				.mpcrbm-guide-help-item h4 {
					margin: 0 0 6px;
					font-size: 13px;
					font-weight: 700;
					color: var(--mpcrbm-shell-text, #1f222b);
				}
				.mpcrbm-guide-help-item p {
					margin: 0;
					font-size: 12px;
					line-height: 1.6;
					color: #6b7280;
				}
				</style>

				<div class="mpcrbm-guide-hero">
					<div class="mpcrbm-guide-eyebrow">
						<span class="mpcrbm-guide-eyebrow-dot"></span>
						<?php esc_html_e( 'Documentation', 'car-rental-manager' ); ?>
					</div>
					<h1>
						<?php
						/* translators: %s: the plugin's configurable "Car" label */
						echo esc_html( sprintf( __( '%s User Guide', 'car-rental-manager' ), $label ) );
						?>
					</h1>
					<p>
						<?php
						/* translators: %s: the plugin's configurable "Car" label, lowercased in this sentence */
						echo esc_html( sprintf( __( 'Everything you need to set up %s, pricing, availability, branches, and the booking form on your site.', 'car-rental-manager' ), strtolower( $label ) ) );
						?>
					</p>
					<div class="mpcrbm-guide-nav">
						<?php foreach ( $sections as $anchor => $section ) : ?>
							<a href="#mpcrbm-guide-<?php echo esc_attr( $anchor ); ?>"><i class="<?php echo esc_attr( $section['icon'] ); ?>"></i> <?php echo esc_html( $section['title'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Getting Started -->
				<div class="mpcrbm-card" id="mpcrbm-guide-overview">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['overview']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['overview']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro">
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo esc_html( sprintf( __( 'This plugin turns %s into a bookable fleet with pricing, availability, branches, and a WooCommerce-powered checkout. A typical setup goes in this order:', 'car-rental-manager' ), strtolower( $label ) . 's' ) );
							?>
						</p>
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-flag-checkered"></i> <?php esc_html_e( 'Add your fleet', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php
									/* translators: %s: the plugin's configurable "Car" label */
									echo esc_html( sprintf( __( 'Create each %s and fill in its details, pricing, and photos', 'car-rental-manager' ), strtolower( $label ) ) );
									?></li>
									<li><?php esc_html_e( 'Set weekly availability and mark any recurring off days', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-gears"></i> <?php esc_html_e( 'Configure the business side', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Add branch locations if customers can pick up/drop off in different places', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Set up FAQs, terms and conditions, and any extra services', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<a class="mpcrbm-guide-link-btn" href="<?php echo esc_url( $new_url ); ?>">
							<i class="fas fa-plus"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo esc_html( sprintf( __( 'Add Your First %s', 'car-rental-manager' ), $label ) );
							?>
						</a>
					</div>
				</div>

				<!-- Shortcode -->
				<div class="mpcrbm-card" id="mpcrbm-guide-shortcode">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['shortcode']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['shortcode']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro"><?php esc_html_e( 'Two shortcodes cover everything: the booking search form, and a standalone car listing — drop either into any page or post.', 'car-rental-manager' ); ?></p>

						<h4 class="mpcrbm-guide-subheading"><?php esc_html_e( 'Booking Search Form — [mpcrbm_booking]', 'car-rental-manager' ); ?></h4>

						<div class="mpcrbm-guide-shortcode-box">
							<div class="mpcrbm-guide-shortcode-row">
								<code>[mpcrbm_booking form='inline' progressbar='no']</code>
								<button type="button" class="mpcrbm-guide-copy-btn" data-copy="<?php echo esc_attr( "[mpcrbm_booking form='inline' progressbar='no']" ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'car-rental-manager' ); ?>"><i class="fas fa-copy"></i></button>
							</div>
							<p><?php esc_html_e( 'A simple shortcode that displays only the search form, the same as the homepage.', 'car-rental-manager' ); ?></p>
						</div>
						<div class="mpcrbm-guide-shortcode-box">
							<div class="mpcrbm-guide-shortcode-row">
								<code>[mpcrbm_booking form='inline' title='yes' progressbar='no' search_result='yes' ajax_search='yes']</code>
								<button type="button" class="mpcrbm-guide-copy-btn" data-copy="<?php echo esc_attr( "[mpcrbm_booking form='inline' title='yes' progressbar='no' search_result='yes' ajax_search='yes']" ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'car-rental-manager' ); ?>"><i class="fas fa-copy"></i></button>
							</div>
							<p><?php esc_html_e( 'Shows the title bar above the form (title=\'yes\'), displays the default search results together with the form (search_result=\'yes\'), and loads those results via AJAX instead of redirecting to a results page (ajax_search=\'yes\').', 'car-rental-manager' ); ?></p>
						</div>
						<div class="mpcrbm-guide-shortcode-box">
							<div class="mpcrbm-guide-shortcode-row">
								<code>[mpcrbm_booking form='horizontal' progressbar='no']</code>
								<button type="button" class="mpcrbm-guide-copy-btn" data-copy="<?php echo esc_attr( "[mpcrbm_booking form='horizontal' progressbar='no']" ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'car-rental-manager' ); ?>"><i class="fas fa-copy"></i></button>
							</div>
							<p><?php esc_html_e( 'The same form laid out horizontally instead of inline — handy for a full-width header or hero section.', 'car-rental-manager' ); ?></p>
						</div>

						<table class="mpcrbm-guide-param-table">
							<thead>
							<tr>
								<th><?php esc_html_e( 'Parameter', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Values', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'What it does', 'car-rental-manager' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<tr>
								<td><code>form</code></td>
								<td><strong>horizontal</strong> / inline</td>
								<td><?php esc_html_e( 'Layout of the search form — a full row, or a compact single line.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>title</code></td>
								<td><strong>yes</strong> / no</td>
								<td><?php esc_html_e( 'Show or hide the title bar above the search form.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>progressbar</code></td>
								<td><strong>yes</strong> / no</td>
								<td><?php esc_html_e( 'Show or hide the booking progress bar.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>search_result</code></td>
								<td>yes / <strong>no</strong></td>
								<td><?php esc_html_e( 'Show the default search results together with the form.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>ajax_search</code></td>
								<td>yes / <strong>no</strong></td>
								<td><?php esc_html_e( 'Load search results via AJAX in place, instead of redirecting to a results page (requires search_result=\'yes\').', 'car-rental-manager' ); ?></td>
							</tr>
							</tbody>
						</table>

						<h4 class="mpcrbm-guide-subheading"><?php esc_html_e( 'Car Listing — [mpcrbm_car_list]', 'car-rental-manager' ); ?></h4>

						<div class="mpcrbm-guide-shortcode-box">
							<div class="mpcrbm-guide-shortcode-row">
								<code>[mpcrbm_car_list mpcrbm_left_filter='yes' style='grid' per_page='6']</code>
								<button type="button" class="mpcrbm-guide-copy-btn" data-copy="<?php echo esc_attr( "[mpcrbm_car_list mpcrbm_left_filter='yes' style='grid' per_page='6']" ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'car-rental-manager' ); ?>"><i class="fas fa-copy"></i></button>
							</div>
							<p><?php esc_html_e( 'Displays a car list with a left-hand filter sidebar (by car type, fuel type, brand, and more), showing 6 cars per page in a grid layout with a Grid/List switcher and numbered pagination.', 'car-rental-manager' ); ?></p>
						</div>

						<div class="mpcrbm-guide-shortcode-box">
							<div class="mpcrbm-guide-shortcode-row">
								<code>[mpcrbm_car_list style='carousel' per_page='8' column='4']</code>
								<button type="button" class="mpcrbm-guide-copy-btn" data-copy="<?php echo esc_attr( "[mpcrbm_car_list style='carousel' per_page='8' column='4']" ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'car-rental-manager' ); ?>"><i class="fas fa-copy"></i></button>
							</div>
							<p><?php esc_html_e( 'Instead of a grid/list with pagination, shows the cars as a single sliding Owl Carousel with arrow navigation — no Grid/List switcher, no pagination.', 'car-rental-manager' ); ?></p>
						</div>

						<table class="mpcrbm-guide-param-table">
							<thead>
							<tr>
								<th><?php esc_html_e( 'Parameter', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Values', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'What it does', 'car-rental-manager' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<tr>
								<td><code>style</code></td>
								<td><strong>grid</strong> / list / carousel</td>
								<td><?php esc_html_e( 'Cards in a grid, a vertical list, or a single sliding Owl Carousel. Grid and list share a client-side Grid/List switcher and AJAX pagination; carousel shows one sliding row instead, with neither.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>mpcrbm_left_filter</code></td>
								<td>yes / <strong>no</strong></td>
								<td><?php esc_html_e( 'Show a sidebar for visitors to filter the list by car type, fuel type, brand, and more. Grid/list style only.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>per_page</code></td>
								<td><?php esc_html_e( 'number, default 9', 'car-rental-manager' ); ?></td>
								<td><?php esc_html_e( 'Grid/list: cars per page — additional cars are reachable via pagination. Carousel: total cars loaded into the slider.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>show</code></td>
								<td><?php esc_html_e( 'number, default 20', 'car-rental-manager' ); ?></td>
								<td><?php esc_html_e( 'Older alias for per_page, kept for shortcodes placed before pagination existed. Ignored if per_page is also set.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>column</code></td>
								<td><?php esc_html_e( '1–6, default 3', 'car-rental-manager' ); ?></td>
								<td><?php esc_html_e( 'Number of columns in grid style, or visible slides at desktop width in carousel style.', 'car-rental-manager' ); ?></td>
							</tr>
							<tr>
								<td><code>car_type</code> / <code>fuel_type</code> / <code>brand</code></td>
								<td><?php esc_html_e( 'optional', 'car-rental-manager' ); ?></td>
								<td><?php esc_html_e( 'Only show cars matching these taxonomies.', 'car-rental-manager' ); ?></td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Adding & Managing Cars -->
				<div class="mpcrbm-card" id="mpcrbm-guide-cars">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['cars']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['cars']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro"><?php esc_html_e( 'Every vehicle is its own post, edited through a tabbed panel. The "General Info" tab covers the essentials:', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-car-side"></i> <?php esc_html_e( 'Vehicle Details', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Car Type, Fuel Type, Seating Capacity, Brand, and Make Year', 'car-rental-manager' ); ?></li>
									<li><strong><?php esc_html_e( 'Capacity & Stock', 'car-rental-manager' ); ?></strong> — <?php esc_html_e( 'maximum passengers/bags and how many units you have', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-location-dot"></i> <?php esc_html_e( 'Pickup & Booking', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Pickup address shown to customers', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Minimum Booking Day, if you require advance notice (Pro)', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>General Info</b> tab. Gallery images have their own tab, off by default until you enable it.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
					</div>
				</div>

				<!-- Pricing & Discounts -->
				<div class="mpcrbm-card" id="mpcrbm-guide-pricing">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['pricing']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['pricing']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro"><?php esc_html_e( 'The Pricing tab starts with a base Price/Day, then runs the optional rules in a fixed order. Each rule works on the result of the one before it, so when several are switched on at once the LAST one to run has the final say:', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-calendar-week"></i> <?php esc_html_e( '1. Day-wise Pricing', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Set a specific rate for any day of the week (e.g. pricier weekends)', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Replaces the base total — every date of the stay is charged at its own weekday rate', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Leave a day blank (or at 0) to keep using the base price for that weekday', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-umbrella-beach"></i> <?php esc_html_e( '2. Seasonal Pricing', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Override rates for specific date ranges — holidays, peak season, events', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Increase or decrease the price, by a fixed amount or a percentage', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Matched on the pick-up date only, and only the first matching season is used', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-layer-group"></i> <?php esc_html_e( '3. Tiered Discount Rules', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Reward longer rentals — e.g. 15% off for 7–14 days', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Runs last, on whatever the two rules above produced', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Choose percentage, fixed discount, fixed total price, or a day-wise rate per tier', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Fixed total price and price per day replace the total outright, discarding steps 1 and 2', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-route"></i> <?php esc_html_e( 'One-Way Fee', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Extra charge when pickup and drop-off locations differ', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Set as a flat amount or a percentage of the total', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Not part of the day-rate chain above — it is added to the final total, alongside extra services and the security deposit', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>Pricing</b> tab.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
					</div>
				</div>

				<!-- Availability & Schedule -->
				<div class="mpcrbm-card" id="mpcrbm-guide-schedule">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['schedule']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['schedule']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro"><?php esc_html_e( 'Control what hours a vehicle is actually bookable, and block off days it is not available at all.', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-clock"></i> <?php esc_html_e( 'Operation Schedule', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Set a Default Schedule once, then use "Apply to All Days" to copy it to every weekday', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Or fine-tune Shift Start/End for individual days', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-calendar-xmark"></i> <?php esc_html_e( 'Off Days & Off Dates', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Tick "Off Day" next to any weekday to close it every week', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Add specific Off Dates for one-off closures (maintenance, holidays)', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>Operation Area & Date Time</b> tab.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
					</div>
				</div>

				<!-- Multi-Location & Fees -->
				<div class="mpcrbm-card" id="mpcrbm-guide-locations">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['locations']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['locations']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-route"></i> <?php esc_html_e( 'Multi-Location Pricing', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Turn this on to charge different transfer fees per pickup/drop-off route', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Daily rates still come from the Pricing tab — this only adds route-based fees', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-shield-halved"></i> <?php esc_html_e( 'Security Deposit', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Require a refundable deposit at booking time', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Charge a fixed amount, or a percentage of the booking total', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>Fee & Deposit</b> tab. Manage the list of locations themselves from the link below.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
						<a class="mpcrbm-guide-link-btn" href="<?php echo esc_url( $loc_url ); ?>">
							<i class="fas fa-map-pin"></i> <?php esc_html_e( 'Manage Locations', 'car-rental-manager' ); ?>
						</a>
					</div>
				</div>

				<!-- Drivers & Branches -->
				<div class="mpcrbm-card" id="mpcrbm-guide-branches">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['branches']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['branches']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-id-card"></i> <?php esc_html_e( 'Driver Information', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Optionally show a driver\'s name, phone, email, and age on the car page', 'car-rental-manager' ); ?></li>
								</ul>
								<h4 style="margin-top:14px;"><i class="fas fa-map-location-dot"></i> <?php esc_html_e( 'Branch Assignment', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Home Branch: where this vehicle is registered/normally returns to', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Current Branch: where it physically is right now — changes are logged', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-building"></i> <?php esc_html_e( 'Branch Manager Dashboard', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Set each branch\'s address, phone, price multiplier, and one-way fee', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Review the transfer history log for every ownership change', 'car-rental-manager' ); ?></li>
								</ul>
								<h4 style="margin-top:14px;"><i class="fas fa-users"></i> <?php esc_html_e( 'Branch Manager Users', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Assign staff accounts to one or more branches so they only see and manage their own vehicles/bookings', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>Driver and Branch Assignment</b> tab.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
						<a class="mpcrbm-guide-link-btn" href="<?php echo esc_url( $bm_url ); ?>">
							<i class="fas fa-users"></i> <?php esc_html_e( 'Manage Branch Manager Users', 'car-rental-manager' ); ?>
						</a>
					</div>
				</div>

				<!-- FAQs & Terms and Conditions -->
				<div class="mpcrbm-card" id="mpcrbm-guide-faq">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['faq']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['faq']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro"><?php esc_html_e( 'FAQs and Terms & Conditions are each a single shared library — you write a question/term once, then pick which ones apply to each car.', 'car-rental-manager' ); ?></p>
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-circle-question"></i> <?php esc_html_e( 'Manage FAQ section', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Click a question in the list to add it to this car — click again to remove it', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( '"Add New FAQ" writes a brand new question straight from this screen', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-file-contract"></i> <?php esc_html_e( 'Term & Condition section', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Works the same way — click to select which terms show for this car', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Selections are saved instantly; no need to hit Update', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; <b>Content &amp; Policies</b> tab.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
					</div>
				</div>

				<!-- Extra Services & Features -->
				<div class="mpcrbm-card" id="mpcrbm-guide-services">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['services']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['services']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-shopping-basket"></i> <?php esc_html_e( 'Extra Service Options', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Add optional paid extras — child seat, GPS, insurance, and so on', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Price them flat or per day, and choose an input box or dropdown for quantity', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-list-check"></i> <?php esc_html_e( 'Car Feature', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Highlight specs like A/C, Bluetooth, or sunroof on the car page', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="mpcrbm-guide-where">
							<i class="fas fa-location-arrow"></i>
							<?php
							/* translators: %s: the plugin's configurable "Car" label */
							echo wp_kses_post( sprintf( __( 'Find it: editing a %s &rarr; the matching tab, or manage the shared Extra Services list from the link below.', 'car-rental-manager' ), strtolower( $label ) ) );
							?>
						</div>
						<a class="mpcrbm-guide-link-btn" href="<?php echo esc_url( $ex_url ); ?>">
							<i class="fas fa-shopping-basket"></i> <?php esc_html_e( 'Manage Extra Services', 'car-rental-manager' ); ?>
						</a>
					</div>
				</div>

				<!-- SecureHold Deposit Holds -->
				<?php
				$sh_settings_url = admin_url( 'edit.php?post_type=' . $cpt . '&page=mpcrbm_settings_page' );
				$sh_img          = function ( $file, $alt, $caption ) {
					$src = MPCRBM_PLUGIN_URL . '/assets/admin/images/guideline/' . $file;
					?>
					<figure class="mpcrbm-guide-shot">
						<a href="<?php echo esc_url( $src ); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy"></a>
						<figcaption><?php echo wp_kses_post( $caption ); ?></figcaption>
					</figure>
					<?php
				};
				?>
				<div class="mpcrbm-card" id="mpcrbm-guide-securehold">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['securehold']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['securehold']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<p class="mpcrbm-guide-intro">
							<?php esc_html_e( 'With the free SecureHold WP plugin, a car’s security deposit is held on the customer’s card (a Stripe authorization) instead of being charged. The customer pays only the rental. After the rental the hold is released automatically, so there is nothing to refund, or you capture part or all of it for damage.', 'car-rental-manager' ); ?>
						</p>

						<div class="mpcrbm-guide-grid">
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-tasks"></i> <?php esc_html_e( 'Requirements', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php echo wp_kses_post( __( 'Booking Mode set to <strong>WooCommerce</strong> (Global Settings &rarr; Payments). SecureHold works only with WooCommerce orders.', 'car-rental-manager' ) ); ?></li>
									<li><?php echo wp_kses_post( __( 'The <strong>WooCommerce Stripe Gateway</strong> plugin, enabled and connected. Holds are placed on the card the customer pays with.', 'car-rental-manager' ) ); ?></li>
									<li><?php echo wp_kses_post( __( '<strong>SecureHold WP 3.4.11 or later</strong>, with Stripe API keys in the same mode (test or live) as the Stripe gateway.', 'car-rental-manager' ) ); ?></li>
									<li><?php echo wp_kses_post( __( 'A <strong>Fixed Amount</strong> security deposit on the car (Fee & Deposit tab). Percentage deposits are always charged.', 'car-rental-manager' ) ); ?></li>
								</ul>
							</div>
							<div class="mpcrbm-guide-block">
								<h4><i class="fas fa-shield-alt"></i> <?php esc_html_e( 'When the deposit is still charged', 'car-rental-manager' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Custom Payment checkout (no WooCommerce order, so nothing to hold on)', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Percentage deposits, and cars excluded in SecureHold', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Orders below SecureHold’s Minimum Cart Amount', 'car-rental-manager' ); ?></li>
									<li><?php esc_html_e( 'Stripe not connected yet. The deposit is then charged with the booking as before, so it is never lost.', 'car-rental-manager' ); ?></li>
								</ul>
							</div>
						</div>

						<h4 class="mpcrbm-guide-subheading"><?php esc_html_e( 'Set it up', 'car-rental-manager' ); ?></h4>
						<ol class="mpcrbm-guide-steps">
							<li><?php echo wp_kses_post( __( 'Open <strong>Global Settings &rarr; Integrations</strong>. The SecureHold card checks everything listed under Requirements.', 'car-rental-manager' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Work down every orange row with its button: <strong>Install</strong> / <strong>Activate</strong> the Stripe gateway and SecureHold, <strong>Connect Stripe in SecureHold</strong>, then <strong>Enable compatibility</strong> (turns on SecureHold’s “Use MagePeople security deposit amounts”).', 'car-rental-manager' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Recommended: click <strong>Hold only car deposits</strong>. Otherwise SecureHold also holds its own Default Hold Amount on orders without a fixed car deposit, including bookings whose percentage deposit is already charged.', 'car-rental-manager' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'On each car: <strong>Fee & Deposit</strong> tab &rarr; <strong>Security Deposit</strong> &rarr; <strong>Fixed Amount</strong>, then update the car.', 'car-rental-manager' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Test in Stripe test mode with card <strong>4242 4242 4242 4242</strong>. The booking total drops by the deposit and the order notes say “SecureHold WP: Security deposit of … authorized”.', 'car-rental-manager' ) ); ?></li>
						</ol>
						<div class="mpcrbm-guide-shots is-single">
							<?php
							$sh_img( 'securehold-setup-needed.png', __( 'SecureHold integration card with setup steps still open', 'car-rental-manager' ), __( '<b>Setup needed:</b> each open step has its own button.', 'car-rental-manager' ) );
							$sh_img( 'securehold-integrations-card.png', __( 'SecureHold integration card, ready', 'car-rental-manager' ), __( '<b>Ready:</b> every row is green, and fixed car deposits are now held on the card.', 'car-rental-manager' ) );
							?>
						</div>

						<h4 class="mpcrbm-guide-subheading"><?php esc_html_e( 'What the customer sees', 'car-rental-manager' ); ?></h4>
						<p class="mpcrbm-guide-intro">
							<?php esc_html_e( 'Why the deposit is not in the total: a held deposit is never paid, only reserved on the card. For a 2-day rental at $60/day with a $200 deposit, the customer is charged $120 and their card shows a separate $200 pending authorization, which disappears when the hold is released.', 'car-rental-manager' ); ?>
						</p>
						<div class="mpcrbm-guide-shots">
							<?php
							$sh_img( 'securehold-car-summary.png', __( 'Car booking summary with the deposit held on card', 'car-rental-manager' ), __( '<b>Car page and search results:</b> the deposit is labelled “held on card” and left out of the Total.', 'car-rental-manager' ) );
							$sh_img( 'securehold-checkout.png', __( 'Checkout order summary with the deposit hold notice', 'car-rental-manager' ), __( '<b>Cart and checkout:</b> no deposit fee in the total, and a notice of the amount that will be held on the card.', 'car-rental-manager' ) );
							?>
						</div>

						<h4 class="mpcrbm-guide-subheading"><?php esc_html_e( 'Manage the hold', 'car-rental-manager' ); ?></h4>
						<div class="mpcrbm-guide-block">
							<ul>
								<li><?php echo wp_kses_post( __( '<strong>Bookings</strong> shows each hold under the booking total: amount, status (Held, Captured, Released, Hold failed) and the automatic release date, with a <strong>Manage in SecureHold</strong> link.', 'car-rental-manager' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'To charge for damage, open the hold in SecureHold and <strong>capture</strong> all or part of it. Anything not captured is released. With Pro, record the damage in the booking’s <strong>Damage Charge</strong> tab first; it reminds you that the deposit is held in SecureHold.', 'car-rental-manager' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'Stripe cancels an uncaptured hold after about 7 days. For longer rentals, set SecureHold’s capture timing so the hold is placed closer to the return date.', 'car-rental-manager' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'A booking paid with another method (for example Cash on Delivery) gets no hold and no deposit charge. It is marked <strong>Not secured</strong>, so collect that deposit before the rental.', 'car-rental-manager' ) ); ?></li>
							</ul>
						</div>
						<div class="mpcrbm-guide-shots is-single">
							<?php
							$sh_img( 'securehold-booking-list.png', __( 'Bookings list with a deposit hold under the booking total', 'car-rental-manager' ), __( '<b>Bookings list:</b> the hold appears under the booking’s total.', 'car-rental-manager' ) );
							$sh_img( 'securehold-order-view.png', __( 'Booking details with the security deposit hold', 'car-rental-manager' ), __( '<b>Booking details (Pro):</b> the customer paid the $120 rental; the $200 deposit is held, not charged.', 'car-rental-manager' ) );
							?>
						</div>

						<a class="mpcrbm-guide-link-btn" href="<?php echo esc_url( $sh_settings_url ); ?>">
							<i class="fas fa-plug"></i> <?php esc_html_e( 'Open Integrations Settings', 'car-rental-manager' ); ?>
						</a>
					</div>
				</div>

				<!-- Need Help? -->
				<div class="mpcrbm-card" id="mpcrbm-guide-help">
					<div class="mpcrbm-card-header mpcrbm-guide-card-header">
						<div class="mpcrbm-guide-card-header-text">
							<i class="<?php echo esc_attr( $sections['help']['icon'] ); ?>"></i>
							<h3><?php echo esc_html( $sections['help']['title'] ); ?></h3>
						</div>
					</div>
					<div class="mpcrbm-card-content">
						<div class="mpcrbm-guide-help-grid">
							<div class="mpcrbm-guide-help-item">
								<i class="fas fa-heartbeat"></i>
								<h4><?php esc_html_e( 'Environment Status', 'car-rental-manager' ); ?></h4>
								<p><?php esc_html_e( 'Check WordPress/WooCommerce versions and required settings before reporting an issue.', 'car-rental-manager' ); ?></p>
							</div>
							<div class="mpcrbm-guide-help-item">
								<i class="fas fa-sliders-h"></i>
								<h4><?php esc_html_e( 'Global Settings', 'car-rental-manager' ); ?></h4>
								<p><?php esc_html_e( 'Site-wide defaults that apply across every car, unless overridden per vehicle.', 'car-rental-manager' ); ?></p>
							</div>
							<div class="mpcrbm-guide-help-item">
								<i class="fas fa-envelope"></i>
								<h4><?php esc_html_e( 'Still stuck?', 'car-rental-manager' ); ?></h4>
								<p><?php esc_html_e( 'Reach out to MagePeople support with your site URL and a description of what you\'re trying to do.', 'car-rental-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<script>
				(function () {
					document.addEventListener('click', function (e) {
						var btn = e.target.closest('.mpcrbm-guide-copy-btn');
						if (!btn) {
							return;
						}
						var text = btn.getAttribute('data-copy') || '';
						var icon = btn.querySelector('i');
						var showCopied = function () {
							btn.classList.add('is-copied');
							if (icon) {
								icon.className = 'fas fa-check';
							}
							setTimeout(function () {
								btn.classList.remove('is-copied');
								if (icon) {
									icon.className = 'fas fa-copy';
								}
							}, 1500);
						};
						if (navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(text).then(showCopied);
						} else {
							var ta = document.createElement('textarea');
							ta.value = text;
							ta.style.position = 'fixed';
							ta.style.opacity = '0';
							document.body.appendChild(ta);
							ta.select();
							try {
								document.execCommand('copy');
							} catch (err) {}
							document.body.removeChild(ta);
							showCopied();
						}
					});
				})();
				</script>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}
		}
		new MPCRBM_Guideline();
	}
