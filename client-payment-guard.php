<?php
/**
 * Plugin Name: Client Access Control
 * Description: Control site availability based on database status.
 * Author: NafezTech
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1️⃣ Create table on activation
register_activation_hook(__FILE__, 'cac_create_table');
function cac_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'site_status';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        status varchar(20) NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Insert default status Active
    $wpdb->insert($table_name, ['status' => 'active']);
}

// 2️⃣ Update status from GitHub (JSON or text)
function cac_update_status_from_github() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'site_status';

    $url = "https://raw.githubusercontent.com/nafez-tech/client-payment-guard/unitedTeba-8-25025/status.txt";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['status'])) {
        $wpdb->update($table_name, ['status' => sanitize_text_field($data['status'])], ['id' => 1]);
    }
}

// 3️⃣ Check site status on each visit
add_action('init', 'cac_check_site_status');
function cac_check_site_status() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'site_status';

    // Update from GitHub
    cac_update_status_from_github();

    // Read current status
    $status = $wpdb->get_var("SELECT status FROM $table_name WHERE id = 1");

    if ($status === 'closed') {
        wp_die(
            '<div style="text-align:center; padding:50px; font-family:sans-serif;">
                <h1 style="font-size:32px; margin-bottom:20px;">Website is currently unavailable</h1>
                <p style="font-size:18px; margin-bottom:30px;">Please complete your payment to restore access.</p>
                <a href="https://t.me/YourTelegramUserName?text=' . urlencode("My website has been suspended: " . home_url()) . '" 
                   style="display:inline-block; background-color:#0088cc; color:#fff; 
                          padding:12px 25px; border-radius:8px; text-decoration:none; 
                          font-size:18px; font-weight:bold; transition:0.3s;">
                   Contact Support
                </a>
            </div>',
            'Website Suspended'
        );
    }
}
