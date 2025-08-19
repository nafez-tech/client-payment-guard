<?php
/*
Plugin Name: Client Payment Guard (MU)
Description: Lock site if payment not received. Checks remote status from GitHub.
Version: 1.2
Author: nafez-tech
*/

defined('ABSPATH') or die('No script kiddies please!');

// ====== CONFIGURATION ======
define('CPG_STATUS_URL', 'https://raw.githubusercontent.com/nafez-tech/client-payment-guard/unitedTeba-8-25025/status.txt');
define('CPG_THEME_SLUG', 'hello-elementor'); // ← غيّر دي لاسم مجلد الثيم بتاعك
// ===========================

// 🔒 فحص الحالة من GitHub (دالة عامة)
function cpg_check_status() {
    $response = wp_remote_get(CPG_STATUS_URL);
    if (is_wp_error($response)) return;

    $status = strtolower(trim(wp_remote_retrieve_body($response)));

    if ($status === 'close') {
        wp_die(
            '<div style="text-align:center; padding:50px; font-family:sans-serif;">
                <h1 style="font-size:32px;">الموقع غير متاح حالياً</h1>
                <p style="font-size:18px;">يرجى سداد المستحقات لإعادة تشغيل الموقع.</p>
            </div>',
            'الموقع مغلق'
        );
    }
}

// ✅ شغّل الفحص على كل الصفحات (frontend + backend)
add_action('init', 'cpg_check_status');

// 🔒 تأكد إن الثيم المطلوب مفعّل
add_action('init', function () {
    $current_theme = wp_get_theme();
    if ($current_theme->get_stylesheet() !== CPG_THEME_SLUG) {
        wp_die('<h1>تعذر تحميل الثيم.</h1><p>يرجى التحقق من ملفات الموقع.</p>');
    }
});

// 🔒 إخفاء شريط الأدمن
add_filter('show_admin_bar', '__return_false');
