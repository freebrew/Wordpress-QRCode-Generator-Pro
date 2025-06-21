<?php
/**
 * Security utilities class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Class
 */
class WP_QR_Generator_Security {

    /**
     * Validate input data
     *
     * @param string $data Input data
     * @return string Sanitized data
     */
    public static function validate_input($data) {
        // Sanitize input
        $data = sanitize_text_field($data);
        
        // Validate URL if present
        if (filter_var($data, FILTER_VALIDATE_URL)) {
            return esc_url($data);
        }
        
        return $data;
    }

    /**
     * Secure file path
     *
     * @param string $filename Filename
     * @return string Secured filename
     * @throws Exception If path is invalid
     */
    public static function secure_file_path($filename) {
        // Sanitize filename
        $filename = sanitize_file_name($filename);
        
        // Ensure file is within allowed directory
        $temp_dir = WP_QR_GENERATOR_UPLOADS_DIR;
        $path = wp_normalize_path($temp_dir . $filename);
        
        if (strpos($path, wp_normalize_path($temp_dir)) !== 0) {
            throw new Exception('Invalid file path');
        }
        
        return $filename;
    }

    /**
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Action name
     * @return bool Verification result
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capabilities
     *
     * @param string $capability Required capability
     * @return bool Capability check result
     */
    public static function check_capability($capability) {
        return current_user_can($capability);
    }

    /**
     * Rate limit check
     *
     * @param string $key Rate limit key
     * @param int $limit Request limit
     * @param int $window Time window in seconds
     * @return bool Rate limit status
     */
    public static function check_rate_limit($key, $limit = 100, $window = 3600) {
        $transient_key = 'wp_qr_rate_limit_' . md5($key);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            set_transient($transient_key, 1, $window);
            return true;
        }
        
        if ($requests >= $limit) {
            return false;
        }
        
        set_transient($transient_key, $requests + 1, $window);
        return true;
    }

    /**
     * Sanitize QR code settings
     *
     * @param array $settings Settings array
     * @return array Sanitized settings
     */
    public static function sanitize_qr_settings($settings) {
        $sanitized = array();
        
        if (isset($settings['size'])) {
            $sanitized['size'] = absint($settings['size']);
            $sanitized['size'] = max(100, min(1000, $sanitized['size']));
        }
        
        if (isset($settings['quality'])) {
            $allowed_qualities = array('L', 'M', 'Q', 'H');
            $sanitized['quality'] = in_array($settings['quality'], $allowed_qualities) ? $settings['quality'] : 'H';
        }
        
        if (isset($settings['color_dark'])) {
            $sanitized['color_dark'] = sanitize_hex_color($settings['color_dark']);
        }
        
        if (isset($settings['color_light'])) {
            $sanitized['color_light'] = sanitize_hex_color($settings['color_light']);
        }
        
        return $sanitized;
    }
} 