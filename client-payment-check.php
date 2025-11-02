<?php
/*
Plugin Name: Client Access Control
Description: Gate the site based on a status stored in DB, synced from GitHub.
Version: 1.3
Author: NafezTech
*/

if (!defined('ABSPATH')) exit;

define('CG_STATUS_URL', 'https://raw.githubusercontent.com/nafez-tech/client-payment-guard/wathkon-site-2-11-2025/status.txt');

define('CG_SYNC_TTL', 30);

add_action('muplugins_loaded', 'cg_bootstrap');

function cg_bootstrap() {
    cg_maybe_sync_status();
}


function cg_fetch_remote_status() {
    $url = add_query_arg('nocache', time(), CG_STATUS_URL);

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => ['Cache-Control' => 'no-cache']
    ]);

    if (is_wp_error($response)) {
        error_log('CG ERROR: ' . $response->get_error_message());
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    if (!$body) return null;

    // إزالة أي رموز أو مسافات أو أسطر أو BOM من البداية والنهاية
    $body = str_replace(["\n", "\r", "\t", " "], '', $body);
    $body = preg_replace('/[^\x20-\x7E]/', '', $body); // إزالة الرموز الغريبة
    $status_raw = strtolower(trim($body));

    error_log('CG STATUS RAW: ' . $status_raw);

    if (in_array($status_raw, ['close','closed','block','off'], true)) return 'close';
    if (in_array($status_raw, ['active','on','open'], true)) return 'active';

    return null;
}


function cg_update_status($status) {
    update_option('wp_wooPaymentStatus', $status, true);
    error_log('CG UPDATED STATUS TO: ' . $status);
}

function cg_maybe_sync_status() {
    delete_transient('cg_synced_recently');

    $remote = cg_fetch_remote_status();

    if ($remote) {
        cg_update_status($remote);
        wp_cache_flush();
    }

    set_transient('cg_synced_recently', 1, CG_SYNC_TTL);
}
