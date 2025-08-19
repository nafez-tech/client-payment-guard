<?php
/*
Plugin Name: Client Access Control (MU)
Description: Gate the site based on a status stored in DB, synced from GitHub.
Version: 1.0
Author: NafezTech
*/

if (!defined('ABSPATH')) exit;

// ====== CONFIG ======
define('CAC_STATUS_URL', 'https://raw.githubusercontent.com/nafez-tech/client-payment-guard/unitedTeba-8-25025/status.txt'); // Change this to client branch URL
define('CAC_TELEGRAM_USER', 'ahetclay'); // Your Telegram username (without @)
define('CAC_SYNC_TTL', 60); // Cache duration in seconds for sync with GitHub
// =====================

// Bootstrap: ensure DB table and sync status
add_action('muplugins_loaded', 'cac_bootstrap');

function cac_bootstrap() {
    cac_ensure_table();
    cac_maybe_sync_status(); // First sync
}

// Create table if not exists
function cac_ensure_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'site_status';

    $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $table) );
    if ($exists !== $table) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id smallint(5) NOT NULL,
            status varchar(20) NOT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    $has_row = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE id = 1");
    if (!$has_row) {
        $wpdb->insert($table, [
            'id'        => 1,
            'status'    => 'active',
            'updated_at'=> current_time('mysql')
        ]);
    }
}

// Fetch status from GitHub (supports plain text or JSON)
function cac_fetch_remote_status() {
    $url = add_query_arg('nocache', time(), CAC_STATUS_URL); // prevent caching
    $response = wp_remote_get($url, [
        'timeout' => 7,
        'headers' => ['Cache-Control' => 'no-cache']
    ]);
    if (is_wp_error($response)) return null;

    $body = wp_remote_retrieve_body($response);
    if (!$body) return null;

    // Try JSON first
    $data = json_decode($body, true);
    if (is_array($data) && isset($data['status'])) {
        $status_raw = strtolower(trim($data['status']));
    } else {
        $status_raw = strtolower(trim($body)); // plain text: active / close
    }

    if (in_array($status_raw, ['close','closed','block','blocked','off'], true)) {
        return 'closed';
    }
    if (in_array($status_raw, ['active','open','on'], true)) {
        return 'active';
    }
    return null;
}

function cac_update_db_status($status) {
    global $wpdb;
    $table = $wpdb->prefix . 'site_status';
    $wpdb->update($table, [
        'status'     => $status,
        'updated_at' => current_time('mysql')
    ], ['id' => 1]);
}

function cac_get_db_status() {
    global $wpdb;
    $table = $wpdb->prefix . 'site_status';
    return (string) $wpdb->get_var("SELECT status FROM $table WHERE id = 1");
}

// Sync with GitHub (using transient to reduce load)
function cac_maybe_sync_status() {
    if (false === get_transient('cac_synced_recently')) {
        $remote = cac_fetch_remote_status();
        if ($remote) {
            cac_update_db_status($remote);
        }
        set_transient('cac_synced_recently', 1, CAC_SYNC_TTL);
    }
}

// Enforce site status
add_action('wp_loaded', 'cac_enforce_status');

function cac_enforce_status() {
    cac_maybe_sync_status();
    $status = cac_get_db_status();

    if ($status === 'closed') {
        $tgUrl = 'https://t.me/' . ltrim(CAC_TELEGRAM_USER, '@') . '?text=' . rawurlencode('My site is suspended: ' . home_url());
        wp_die(
            '<div style="text-align:center; padding:50px; font-family:sans-serif;">
                <h1 style="font-size:32px; margin-bottom:20px;">Site Unavailable</h1>
                <p style="font-size:18px; margin-bottom:30px;">Please contact support to restore access.</p>
                <a href="'. esc_url($tgUrl) .'" 
                   style="display:inline-block; background:#0088cc; color:#fff; padding:12px 25px; border-radius:8px; text-decoration:none; font-size:18px; font-weight:bold;">
                   Contact Support
                </a>
            </div>',
            'Site Closed'
        );
    }
}
