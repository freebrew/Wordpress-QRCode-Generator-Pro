<?php
/**
 * Admin Settings Class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Settings Class
 */
class WP_QR_Generator_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_save_qr_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_clear_qr_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_export_analytics', array($this, 'ajax_export_analytics'));
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('wp_qr_generator_settings', 'wp_qr_generator_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        // General Settings Section
        add_settings_section(
            'wp_qr_generator_general',
            __('General Settings', 'wp-qr-generator'),
            array($this, 'general_section_callback'),
            'wp_qr_generator_settings'
        );

        // Performance Settings Section
        add_settings_section(
            'wp_qr_generator_performance',
            __('Performance Settings', 'wp-qr-generator'),
            array($this, 'performance_section_callback'),
            'wp_qr_generator_settings'
        );

        // Security Settings Section
        add_settings_section(
            'wp_qr_generator_security',
            __('Security Settings', 'wp-qr-generator'),
            array($this, 'security_section_callback'),
            'wp_qr_generator_settings'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // General settings
        $sanitized['default_size'] = isset($input['default_size']) ? intval($input['default_size']) : 300;
        $sanitized['default_size'] = max(100, min(1000, $sanitized['default_size']));

        $allowed_qualities = array('L', 'M', 'Q', 'H');
        $sanitized['default_quality'] = isset($input['default_quality']) && in_array($input['default_quality'], $allowed_qualities) 
            ? $input['default_quality'] : 'H';

        $sanitized['enable_tracking'] = isset($input['enable_tracking']) ? 1 : 0;
        $sanitized['enable_analytics'] = isset($input['enable_analytics']) ? 1 : 0;
        $sanitized['logo_upload'] = isset($input['logo_upload']) ? 1 : 0;

        // Appearance settings
        $sanitized['default_color_dark'] = isset($input['default_color_dark']) 
            ? sanitize_hex_color($input['default_color_dark']) : '#000000';
        $sanitized['default_color_light'] = isset($input['default_color_light']) 
            ? sanitize_hex_color($input['default_color_light']) : '#ffffff';

        // Performance settings
        $sanitized['cache_duration'] = isset($input['cache_duration']) ? intval($input['cache_duration']) : 3600;
        $sanitized['cache_duration'] = max(300, min(86400, $sanitized['cache_duration']));

        $sanitized['auto_cleanup_days'] = isset($input['auto_cleanup_days']) ? intval($input['auto_cleanup_days']) : 30;
        $sanitized['auto_cleanup_days'] = max(1, min(365, $sanitized['auto_cleanup_days']));

        // Security settings
        $sanitized['rate_limit'] = isset($input['rate_limit']) ? intval($input['rate_limit']) : 100;
        $sanitized['rate_limit'] = max(10, min(1000, $sanitized['rate_limit']));

        // Privacy settings
        $sanitized['gdpr_compliance'] = isset($input['gdpr_compliance']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general QR code generation settings.', 'wp-qr-generator') . '</p>';
    }

    /**
     * Performance section callback
     */
    public function performance_section_callback() {
        echo '<p>' . __('Optimize performance and caching settings.', 'wp-qr-generator') . '</p>';
    }

    /**
     * Security section callback
     */
    public function security_section_callback() {
        echo '<p>' . __('Configure security and privacy settings.', 'wp-qr-generator') . '</p>';
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        parse_str($_POST['settings'], $settings);
        $sanitized_settings = $this->sanitize_settings($settings);
        
        update_option('wp_qr_generator_settings', $sanitized_settings);

        wp_send_json_success(__('Settings saved successfully!', 'wp-qr-generator'));
    }

    /**
     * AJAX clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        $cache = new WP_QR_Generator_Cache();
        $result = $cache->clear_all_cache();

        if ($result) {
            wp_send_json_success(__('Cache cleared successfully!', 'wp-qr-generator'));
        } else {
            wp_send_json_error(__('Failed to clear cache.', 'wp-qr-generator'));
        }
    }

    /**
     * AJAX export analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to = sanitize_text_field($_GET['date_to'] ?? '');

        $analytics = new WP_QR_Generator_Analytics();
        $file_path = $analytics->export_analytics_data($format, $date_from, $date_to);

        if ($file_path && file_exists($file_path)) {
            $file_url = str_replace(WP_QR_GENERATOR_UPLOADS_DIR, WP_QR_GENERATOR_UPLOADS_URL, $file_path);
            wp_redirect($file_url);
            exit;
        } else {
            wp_die(__('Failed to generate export file.', 'wp-qr-generator'));
        }
    }

    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'default_size' => 300,
            'default_quality' => 'H',
            'enable_tracking' => true,
            'enable_analytics' => true,
            'cache_duration' => 3600,
            'logo_upload' => false,
            'rate_limit' => 100,
            'gdpr_compliance' => true,
            'default_color_dark' => '#000000',
            'default_color_light' => '#ffffff',
            'auto_cleanup_days' => 30
        );
    }

    /**
     * Get current settings
     */
    public static function get_settings() {
        $defaults = self::get_default_settings();
        $settings = get_option('wp_qr_generator_settings', array());
        return wp_parse_args($settings, $defaults);
    }
} 