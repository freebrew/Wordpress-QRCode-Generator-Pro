<?php
/**
 * Database operations class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Class
 */
class WP_QR_Generator_Database {

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // QR Codes table
        $table_qr_codes = $wpdb->prefix . 'qr_codes';
        $sql_qr_codes = "CREATE TABLE $table_qr_codes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned DEFAULT NULL,
            qr_code_url varchar(255) NOT NULL,
            qr_code_data text NOT NULL,
            file_path varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL,
            status varchar(20) DEFAULT 'active',
            settings longtext DEFAULT NULL,
            scans_count bigint(20) unsigned DEFAULT 0,
            conversions_count bigint(20) unsigned DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY created_by (created_by),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Scans table
        $table_qr_scans = $wpdb->prefix . 'qr_scans';
        $sql_scans = "CREATE TABLE $table_qr_scans (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            qr_code_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            scanned_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY qr_code_id (qr_code_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // Conversions table
        $table_qr_conversions = $wpdb->prefix . 'qr_conversions';
        $sql_conversions = "CREATE TABLE $table_qr_conversions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            qr_code_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED DEFAULT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            converted_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY qr_code_id (qr_code_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_qr_codes);
        dbDelta($sql_scans);
        dbDelta($sql_conversions);

        // Update database version
        update_option('wp_qr_generator_db_version', '1.0.0');
    }

    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'qr_conversions',
            $wpdb->prefix . 'qr_scans',
            $wpdb->prefix . 'qr_codes'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('wp_qr_generator_db_version');
    }
} 