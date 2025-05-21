<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly

if (!class_exists('MPTBM_Enqueue')) {
    class MPTBM_Enqueue {
        public function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'mptbm_enqueue_scripts'));
        }
        
        public function mptbm_enqueue_scripts() {
            // ... existing enqueues ...
        }
    }
    
    new MPTBM_Enqueue();
} 