<?php
/**
 * Error handling class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Error Handler Class
 */
class WP_QR_Generator_Error_Handler {

    /**
     * Error log file
     *
     * @var string
     */
    private static $log_file;

    /**
     * Initialize error handler
     */
    public static function init() {
        self::$log_file = WP_QR_GENERATOR_PLUGIN_PATH . 'logs/error.log';
        
        // Create logs directory if it doesn't exist
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }

    /**
     * Handle QR code generation error
     *
     * @param Exception $error Error object
     * @return string Fallback HTML
     */
    public static function handle_generation_error($error) {
        $error_message = sprintf(
            '[%s] QR Code Generation Error: %s in %s on line %d',
            current_time('mysql'),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
        
        self::log_error($error_message);
        
        // Return fallback to client-side generation
        return self::fallback_to_client_side();
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     */
    public static function log_error($message) {
        // Log to WordPress error log
        error_log($message);
        
        // Log to plugin-specific file
        if (self::$log_file && is_writable(dirname(self::$log_file))) {
            file_put_contents(self::$log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        
        // Store in database for admin viewing
        self::store_error_in_db($message);
    }

    /**
     * Store error in database
     *
     * @param string $message Error message
     */
    private static function store_error_in_db($message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'qr_error_logs';
        
        // Create table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                error_message text NOT NULL,
                error_time datetime DEFAULT CURRENT_TIMESTAMP,
                user_id bigint(20) unsigned DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY error_time (error_time)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'error_message' => $message,
                'user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%s', '%d', '%s', '%s')
        );
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Fallback to client-side generation
     *
     * @return string Fallback HTML
     */
    private static function fallback_to_client_side() {
        return '<div id="qrcode-fallback" class="qr-fallback" data-error="server-generation-failed"></div>';
    }

    /**
     * Get recent errors
     *
     * @param int $limit Number of errors to retrieve
     * @return array Recent errors
     */
    public static function get_recent_errors($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'qr_error_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY error_time DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Clear old error logs
     *
     * @param int $days Number of days to keep
     */
    public static function cleanup_old_errors($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'qr_error_logs';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE error_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}

// Initialize error handler
WP_QR_Generator_Error_Handler::init(); 