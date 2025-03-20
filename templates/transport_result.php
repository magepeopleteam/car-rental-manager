<?php
// First sanitize the URL for storage/processing
$raw_url = isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : '';
$sanitized_url = esc_url_raw($raw_url);

// Then escape it for output
$http_referrer = esc_url($sanitized_url);

$inline_script = '(function($) {';
$inline_script .= '"use strict";';
$inline_script .= '$(document).ready(function() {';
$inline_script .= 'var httpReferrer = "' . esc_js($http_referrer) . '";';
$inline_script .= 'document.cookie = "httpReferrer=" + httpReferrer + ";path=/";';
$inline_script .= '});';
$inline_script .= '})(jQuery);';

wp_add_inline_script('jquery', $inline_script, 'after'); 