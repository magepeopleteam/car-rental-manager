<?php
$http_referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url(sanitize_url($_SERVER['HTTP_REFERER'])) : ''; 

$inline_script = '(function($) {';
$inline_script .= '"use strict";';
$inline_script .= '$(document).ready(function() {';
$inline_script .= 'var httpReferrer = "' . esc_js($http_referrer) . '";';
$inline_script .= 'document.cookie = "httpReferrer=" + httpReferrer + ";path=/";';
$inline_script .= '});';
$inline_script .= '})(jQuery);';

wp_add_inline_script('jquery', $inline_script, 'after'); 