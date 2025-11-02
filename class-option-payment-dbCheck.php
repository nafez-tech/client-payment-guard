<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'cg_check_status_before_load');

function cg_check_status_before_load() {
    $status = get_option('wp_wooPaymentStatus', 'active');
    if ($status === 'close') {
        cg_display_suspended_page();
        exit;
    }
}

function cg_display_suspended_page() {
    $file = __DIR__ . '/status-page.php';
    if (file_exists($file)) {
        include $file;
    } else {
        wp_die('Site suspended. Please contact support.');
    }
}
