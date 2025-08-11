<?php
/*
Template Name: Transport Result
*/

// ✅ Start session in functions.php (recommended), not in template
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get stored content
$content = $_SESSION['custom_content'] ?? '';

// Redirect to homepage if empty
if (empty($content)) {
    wp_redirect(home_url());
    exit;
}
$progressbar = $progressbar ?? 'yes';
$progressbar_class = $progressbar == 'yes' ? '' : 'dNone';
// Remove content from session after use
unset($_SESSION['custom_content']);

get_header();
?>

    <!-- Pass HTTP referrer to cookie -->
    <script type="text/javascript">
        (function() {
            var httpReferrer = "<?php echo esc_js($_SERVER['HTTP_REFERER'] ?? ''); ?>";
            document.cookie = "httpReferrer=" + httpReferrer + ";path=/";
        })();
    </script>

    <main id="maincontent" class="transport-result-page">
        <div class="mpcrbm mpcrbm_transport_search_area" style="margin: auto">
            <div class="mpcrbm_tab_next _mT">
                <div class="tabListsNext <?php echo esc_attr($progressbar_class); ?>" id="mpcrbm_progress_bar_holder" style="display: flex">
                    <div data-tabs-target-next="#mpcrbm_pick_up_details" class="tabItemNext active" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>1</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Enter Ride Details', 'car-rental-manager'); ?></h6>
                    </div>
                    <div data-tabs-target-next="#mpcrbm_search_result" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>2</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Choose a vehicle', 'car-rental-manager'); ?></h6>
                    </div>
                    <div data-tabs-target-next="#mpcrbm_order_summary" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>3</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Place Order', 'car-rental-manager'); ?></h6>
                    </div>
                </div>
                <div class="tabsContentNext">
                    <div data-tabs-next="#mpcrbm_pick_up_details" class="active mpcrbm_pick_up_details">
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php
get_footer();
