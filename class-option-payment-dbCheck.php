<?php
// class-option-payment-dbCheck.php
// Must be required from wp-config.php or loaded early.

if (!defined('ABSPATH')) {
    // ABSPATH قد لا يكون معرفًا لو الملف استدعي بطريقة غريبة، لكن بما إنك استدعيت الملف من wp-config.php فمن المفترض أنه موجود.
    // إذا لم يكن معرفًا سنحاول تعريفه من مكاننا (fallback).
    if (defined('WP_CONTENT_DIR')) {
        define('ABSPATH', dirname(dirname(__FILE__)) . '/');
    } else {
        // إذا لم نتمكن من تعيين ABSPATH نوقف التنفيذ لمنع أخطاء غير متوقعة
        error_log('PaymentCheck: ABSPATH not defined and cannot be inferred.');
        return;
    }
}

// Path to the suspended page file (تأكد أن المسار صحيح)
$suspended_file = __DIR__ . '/suspended-page.php';

// Function to display the suspended page and exit
function ppc_display_suspended_and_exit($file_path) {
    if (file_exists($file_path)) {
        // Send correct headers
        if (!headers_sent()) {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Retry-After: 3600'); // clients may retry after 1 hour
            header('Content-Type: text/html; charset=UTF-8');
        }
        include $file_path;
        exit;
    } else {
        // fallback message
        if (!function_exists('wp_die')) {
            echo '<h1>Site suspended</h1><p>Please contact support.</p>';
            exit;
        } else {
            wp_die('Site suspended. Please contact support.');
        }
    }
}

// Function to read option safely: try WP get_option, otherwise direct DB read
function ppc_get_site_status() {
    // Prefer WP API if available
    if (function_exists('get_option')) {
        $status = get_option('wp_wooPaymentStatus', 'active');
        error_log('PaymentCheck: get_option returned: ' . var_export($status, true));
        return $status;
    }

    // Fallback: direct DB read using $wpdb if available
    global $wpdb;
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        $table = $wpdb->prefix . 'options';
        $val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $table WHERE option_name = %s LIMIT 1", 'wp_wooPaymentStatus' ) );
        $val = is_null($val) ? 'active' : maybe_unserialize($val);
        error_log('PaymentCheck: $wpdb fetched: ' . var_export($val, true));
        return $val;
    }

    // Final fallback: direct mysqli using WP constants from wp-config.php
    if (defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_HOST')) {
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_error) {
            error_log('PaymentCheck: mysqli connection error: ' . $mysqli->connect_error);
            return 'active';
        }
        // Determine the WP table prefix: try to read $table_prefix constant if defined
        $prefix = defined('table_prefix') ? table_prefix : 'wp_';
        // if not defined, try default 'wp_'
        $opt_name = 'wp_wooPaymentStatus';
        $stmt = $mysqli->prepare("SELECT option_value FROM {$prefix}options WHERE option_name = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $opt_name);
            $stmt->execute();
            $stmt->bind_result($opt_value);
            if ($stmt->fetch()) {
                $stmt->close();
                $mysqli->close();
                $opt_value = maybe_unserialize($opt_value);
                error_log('PaymentCheck: mysqli fetched: ' . var_export($opt_value, true));
                return $opt_value;
            }
            $stmt->close();
        } else {
            error_log('PaymentCheck: mysqli prepare failed.');
        }
        $mysqli->close();
    }

    // Default
    return 'active';
}

// Immediate check: this ensures that if the file is included from wp-config.php we still enforce quickly
$status_now = ppc_get_site_status();
if ($status_now === 'close') {
    error_log('PaymentCheck: Status is close — showing suspended page immediately.');
    ppc_display_suspended_and_exit($suspended_file);
}

// Also register a very early hook as fallback when WP is loaded (for normal plugin flow)
if (function_exists('add_action')) {
    add_action('muplugins_loaded', function() use ($suspended_file) {
        $status = ppc_get_site_status();
        if ($status === 'close') {
            error_log('PaymentCheck: (hook) Status is close — showing suspended page.');
            ppc_display_suspended_and_exit($suspended_file);
        }
    }, 1);
}
