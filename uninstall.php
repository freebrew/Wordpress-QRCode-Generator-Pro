<?php
/**
 * Uninstall script for WordPress QR Code Generator
 *
 * @package WP_QR_Generator
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has the capability to delete plugins
if (!current_user_can('delete_plugins')) {
    exit;
}

// Include the database class
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

// Delete database tables
WP_QR_Generator_Database::drop_tables();

// Delete options
$options = array(
    'wp_qr_generator_version',
    'wp_qr_generator_settings',
    'wp_qr_generator_db_version'
);

foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option); // For multisite
}

// Clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_qrcode_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_qrcode_%'");

// Remove upload directory and files (recursive, safe)
function wp_qr_generator_rrmdir($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object === '.' || $object === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $object;
        if (is_dir($path)) {
            wp_qr_generator_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

$upload_dir = WP_CONTENT_DIR . '/uploads/qrcodes/';
if (is_dir($upload_dir)) {
    wp_qr_generator_rrmdir($upload_dir);
}

// Clear any scheduled events
wp_clear_scheduled_hook('wp_qr_generator_cleanup');

// Clear rewrite rules
flush_rewrite_rules(); 