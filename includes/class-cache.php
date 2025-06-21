<?php
/**
 * Cache management class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache Class
 */
class WP_QR_Generator_Cache {

    /**
     * Cache duration in seconds
     *
     * @var int
     */
    private $cache_time = 3600; // 1 hour

    /**
     * Cache group
     *
     * @var string
     */
    private $cache_group = 'wp_qr_generator';

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('wp_qr_generator_settings', array());
        $this->cache_time = $settings['cache_duration'] ?? 3600;
    }

    /**
     * Get cached QR code
     *
     * @param string $key Cache key
     * @return mixed Cached data or false
     */
    public function get_cached_qr_code($key) {
        $cache_key = 'qrcode_' . $key;
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached === false) {
            $cached = get_transient($cache_key);
        }
        
        return $cached;
    }

    /**
     * Cache QR code data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @return bool Success status
     */
    public function cache_qr_code($key, $data) {
        $cache_key = 'qrcode_' . $key;
        
        // Store in object cache
        wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_time);
        
        // Store in transient as fallback
        return set_transient($cache_key, $data, $this->cache_time);
    }

    /**
     * Delete cached QR code
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete_cached_qr_code($key) {
        $cache_key = 'qrcode_' . $key;
        
        wp_cache_delete($cache_key, $this->cache_group);
        return delete_transient($cache_key);
    }

    /**
     * Clear all QR code cache
     *
     * @return bool Success status
     */
    public function clear_all_cache() {
        global $wpdb;
        
        // Clear object cache group
        wp_cache_flush_group($this->cache_group);
        
        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_qrcode_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_qrcode_%'");
        
        return true;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $transient_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_qrcode_%'"
        );
        
        return array(
            'transient_count' => intval($transient_count),
            'cache_duration' => $this->cache_time,
            'cache_group' => $this->cache_group
        );
    }
} 