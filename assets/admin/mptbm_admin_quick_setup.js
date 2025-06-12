(function($) {
    "use strict";
    $(document).ready(function() {
        // Check if the target element exists (Change selector to match your page's structure)
        if ($('#mpcrm-quick-setup').length > 0) {
            let mpcrbm_admin_location = window.location.href;

            // Prevent infinite loop by checking if redirection is needed
            if (!mpcrbm_admin_location.includes('edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup')) {
                mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');
                mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?page=mpcrbm_rent', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');
                mpcrbm_admin_location = mpcrbm_admin_location.replace('admin.php?page=mpcrbm_quick_setup', 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_quick_setup');
                
                window.location.href = mpcrbm_admin_location; // Redirect only if needed
            }
        }
    });
})(jQuery);
