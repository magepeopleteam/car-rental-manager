<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action('mptbm_settings_sec_fields', array($this, 'settings_sec_fields'), 10, 1);
do_action('mptbm_settings_sec_fields');
