(function($) {
    "use strict";
    $(document).ready(function() {
        // Check if the target element exists (Change selector to match your page's structure)
        if ($('#mpcrm-quick-setup').length > 0) {
            let mptbm_admin_location = window.location.href;

            // Prevent infinite loop by checking if redirection is needed
            if (!mptbm_admin_location.includes('edit.php?post_type=mptbm_rent&page=mptbm_quick_setup')) {
                mptbm_admin_location = mptbm_admin_location.replace('admin.php?post_type=mptbm_rent&page=mptbm_quick_setup', 'edit.php?post_type=mptbm_rent&page=mptbm_quick_setup');
                mptbm_admin_location = mptbm_admin_location.replace('admin.php?page=mptbm_rent', 'edit.php?post_type=mptbm_rent&page=mptbm_quick_setup');
                mptbm_admin_location = mptbm_admin_location.replace('admin.php?page=mptbm_quick_setup', 'edit.php?post_type=mptbm_rent&page=mptbm_quick_setup');
                
                window.location.href = mptbm_admin_location; // Redirect only if needed
            }
        }
    });
})(jQuery);
