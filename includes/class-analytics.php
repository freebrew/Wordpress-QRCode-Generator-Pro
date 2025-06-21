<?php
/**
 * Analytics class (HPOS Compatible)
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Class
 */
class WP_QR_Generator_Analytics {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_track_qr_scan', array($this, 'track_scan'));
        add_action('wp_ajax_nopriv_track_qr_scan', array($this, 'track_scan'));
        add_action('woocommerce_thankyou', array($this, 'track_conversion'), 10, 1);
        
        // Include order utils for HPOS compatibility
        require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-order-utils.php';
    }

    /**
     * Track QR code scan
     */
    public function track_scan() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');
        
        global $wpdb;
        
        $qr_source = sanitize_text_field($_POST['qr_source'] ?? '');
        $qr_id = intval($_POST['qr_id'] ?? 0);
        $device_info = sanitize_textarea_field($_POST['device_info'] ?? '');
        $referrer = esc_url($_POST['referrer'] ?? '');
        
        // Get or create session ID
        if (!session_id()) {
            session_start();
        }
        $session_id = session_id();
        
        // Insert scan record
        $scan_id = $wpdb->insert(
            $wpdb->prefix . 'qr_scans',
            array(
                'qr_code_id' => $qr_id,
                'device_info' => $device_info,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $referrer,
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($scan_id) {
            // Update scan count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}qr_codes SET scans_count = scans_count + 1 WHERE id = %d",
                $qr_id
            ));
            
            // Store session data for conversion tracking
            $_SESSION['qr_scan_id'] = $wpdb->insert_id;
            $_SESSION['qr_scan_time'] = time();
        }
        
        wp_send_json_success();
    }

    /**
     * Track conversion (HPOS Compatible)
     *
     * @param int $order_id Order ID
     */
    public function track_conversion($order_id) {
        if (!session_id()) {
            session_start();
        }
        
        $scan_id = $_SESSION['qr_scan_id'] ?? null;
        $scan_time = $_SESSION['qr_scan_time'] ?? null;
        
        if (!$scan_id || !$scan_time) {
            return;
        }
        
        // Check if conversion is within reasonable timeframe (24 hours)
        if (time() - $scan_time > 86400) {
            return;
        }
        
        global $wpdb;
        
        // Use HPOS-compatible method to get order
        $order = WP_QR_Generator_Order_Utils::get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Insert conversion record
        $wpdb->insert(
            $wpdb->prefix . 'qr_conversions',
            array(
                'scan_id' => $scan_id,
                'order_id' => $order_id,
                'revenue' => $order->get_total(),
                'status' => $order->get_status()
            ),
            array('%d', '%d', '%f', '%s')
        );
        
        // Add QR tracking meta to order (HPOS compatible)
        WP_QR_Generator_Order_Utils::update_order_meta($order_id, '_qr_scan_id', $scan_id);
        WP_QR_Generator_Order_Utils::update_order_meta($order_id, '_qr_conversion_time', current_time('mysql'));
        
        // Update conversion count
        $qr_code_id = $wpdb->get_var($wpdb->prepare(
            "SELECT qr_code_id FROM {$wpdb->prefix}qr_scans WHERE id = %d",
            $scan_id
        ));
        
        if ($qr_code_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}qr_codes SET conversions_count = conversions_count + 1 WHERE id = %d",
                $qr_code_id
            ));
        }
        
        // Mark scan as converted
        $wpdb->update(
            $wpdb->prefix . 'qr_scans',
            array('converted' => 1),
            array('id' => $scan_id),
            array('%d'),
            array('%d')
        );
        
        // Clean up session
        unset($_SESSION['qr_scan_id'], $_SESSION['qr_scan_time']);
    }

    /**
     * Get analytics data
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Analytics data
     */
    public function get_analytics_data($date_from = '', $date_to = '') {
        global $wpdb;
        
        // Default to last 30 days if no dates provided
        if (empty($date_from)) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($date_to)) {
            $date_to = date('Y-m-d');
        }
        
        // Get total scans
        $total_scans = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}qr_scans WHERE DATE(scan_time) BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Get unique scans
        $unique_scans = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}qr_scans WHERE DATE(scan_time) BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Get total conversions
        $total_conversions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}qr_conversions c 
             JOIN {$wpdb->prefix}qr_scans s ON c.scan_id = s.id 
             WHERE DATE(s.scan_time) BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Get total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(c.revenue) FROM {$wpdb->prefix}qr_conversions c 
             JOIN {$wpdb->prefix}qr_scans s ON c.scan_id = s.id 
             WHERE DATE(s.scan_time) BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Get conversion rate
        $conversion_rate = $total_scans > 0 ? round(($total_conversions / $total_scans) * 100, 2) : 0;
        
        // Get daily scan data
        $daily_scans = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(scan_time) as date, COUNT(*) as scans 
             FROM {$wpdb->prefix}qr_scans 
             WHERE DATE(scan_time) BETWEEN %s AND %s 
             GROUP BY DATE(scan_time) 
             ORDER BY date",
            $date_from,
            $date_to
        ));
        
        // Get top performing QR codes
        $top_qr_codes = $wpdb->get_results($wpdb->prepare(
            "SELECT qc.id, qc.qr_code_data, COUNT(s.id) as scans, 
                    COUNT(CASE WHEN s.converted = 1 THEN 1 END) as conversions
             FROM {$wpdb->prefix}qr_codes qc
             LEFT JOIN {$wpdb->prefix}qr_scans s ON qc.id = s.qr_code_id 
             WHERE DATE(s.scan_time) BETWEEN %s AND %s OR s.scan_time IS NULL
             GROUP BY qc.id 
             ORDER BY scans DESC 
             LIMIT 10",
            $date_from,
            $date_to
        ));
        
        // Get device breakdown
        $device_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN device_info LIKE '%Mobile%' OR device_info LIKE '%Android%' OR device_info LIKE '%iPhone%' THEN 'Mobile'
                    WHEN device_info LIKE '%Tablet%' OR device_info LIKE '%iPad%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type,
                COUNT(*) as count
             FROM {$wpdb->prefix}qr_scans 
             WHERE DATE(scan_time) BETWEEN %s AND %s 
             GROUP BY device_type",
            $date_from,
            $date_to
        ));
        
        return array(
            'summary' => array(
                'total_scans' => intval($total_scans),
                'unique_scans' => intval($unique_scans),
                'total_conversions' => intval($total_conversions),
                'total_revenue' => floatval($total_revenue),
                'conversion_rate' => $conversion_rate
            ),
            'daily_scans' => $daily_scans,
            'top_qr_codes' => $top_qr_codes,
            'device_stats' => $device_stats,
            'date_range' => array(
                'from' => $date_from,
                'to' => $date_to
            )
        );
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
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
     * Export analytics data
     *
     * @param string $format Export format (csv, json, excel)
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return string File path
     */
    public function export_analytics_data($format = 'csv', $date_from = '', $date_to = '') {
        $data = $this->get_analytics_data($date_from, $date_to);
        
        $filename = 'qr_analytics_' . date('Y-m-d_H-i-s') . '.' . $format;
        $filepath = WP_QR_GENERATOR_UPLOADS_DIR . $filename;
        
        switch ($format) {
            case 'csv':
                $this->export_to_csv($data, $filepath);
                break;
            case 'json':
                $this->export_to_json($data, $filepath);
                break;
            case 'excel':
                $this->export_to_excel($data, $filepath);
                break;
        }
        
        return $filepath;
    }

    /**
     * Export data to CSV
     *
     * @param array $data Analytics data
     * @param string $filepath File path
     */
    private function export_to_csv($data, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($file, array('Metric', 'Value'));
        
        // Write summary data
        foreach ($data['summary'] as $key => $value) {
            fputcsv($file, array(ucfirst(str_replace('_', ' ', $key)), $value));
        }
        
        fclose($file);
    }

    /**
     * Export data to JSON
     *
     * @param array $data Analytics data
     * @param string $filepath File path
     */
    private function export_to_json($data, $filepath) {
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Export data to Excel (simplified CSV with .xlsx extension)
     *
     * @param array $data Analytics data
     * @param string $filepath File path
     */
    private function export_to_excel($data, $filepath) {
        // For now, export as CSV with xlsx extension
        // In a full implementation, you'd use a library like PhpSpreadsheet
        $this->export_to_csv($data, $filepath);
    }
} 