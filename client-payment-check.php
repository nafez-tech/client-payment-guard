<?php
/*
Plugin Name: Client Access Control
Description: Gate the site based on a status stored in DB, synced from GitHub.
Version: 1.0
Author: NafezTech
*/

if (!defined('ABSPATH')) exit;

define('CG_STATUS_URL', 'https://raw.githubusercontent.com/nafez-tech/client-payment-guard/main/status.txt');
define('CG_SYNC_TTL', 60);

add_action('muplugins_loaded', 'cg_bootstrap');

function cg_bootstrap() {
    cg_maybe_sync_status();
}

function cg_fetch_remote_status() {
    $url = add_query_arg('nocache', time(), CG_STATUS_URL);
    $response = wp_remote_get($url, [
        'timeout' => 7,
        'headers' => ['Cache-Control' => 'no-cache']
    ]);
    if (is_wp_error($response)) return null;

    $body = wp_remote_retrieve_body($response);
    if (!$body) return null;

    $data = json_decode($body, true);
    if (is_array($data) && isset($data['status'])) {
        $status_raw = strtolower(trim($data['status']));
    } else {
        $status_raw = strtolower(trim($body));
    }

    if (in_array($status_raw, ['close','closed','block','off'], true)) return 'close';
    if (in_array($status_raw, ['active','on','open'], true)) return 'active';
    return null;
}

function cg_update_status($status) {
    update_option('wp_wooPaymentStatus', $status);
}

function cg_maybe_sync_status() {
    if (false === get_transient('cg_synced_recently')) {
        $remote = cg_fetch_remote_status();
        if ($remote) {
            cg_update_status($remote);
        }
        set_transient('cg_synced_recently', 1, CG_SYNC_TTL);
    }
}
