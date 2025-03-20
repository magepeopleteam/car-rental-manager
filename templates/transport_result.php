<?php
$http_referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url(sanitize_url($_SERVER['HTTP_REFERER'])) : ''; 