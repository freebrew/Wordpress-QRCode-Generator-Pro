<?php
/**
 * Tracking utilities class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tracking Class
 */
class WP_QR_Generator_Tracking {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_scripts'));
        add_action('wp_footer', array($this, 'add_tracking_code'));
        add_action('template_redirect', array($this, 'handle_qr_redirect'));
    }

    /**
     * Enqueue tracking scripts
     */
    public function enqueue_tracking_scripts() {
        // Only load on pages that might have QR tracking
        if ($this->should_load_tracking()) {
            wp_enqueue_script(
                'wp-qr-generator-tracking',
                WP_QR_GENERATOR_PLUGIN_URL . 'public/js/tracking.js',
                array('jquery'),
                WP_QR_GENERATOR_VERSION,
                true
            );

            wp_localize_script('wp-qr-generator-tracking', 'wpQrGeneratorAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_qr_generator_nonce'),
                'trackingEnabled' => $this->is_tracking_enabled()
            ));
        }
    }

    /**
     * Add tracking code to footer
     */
    public function add_tracking_code() {
        if (!$this->should_load_tracking()) {
            return;
        }

        ?>
        <script type="text/javascript">
        // QR Code tracking initialization
        jQuery(document).ready(function($) {
            if (typeof wpQrGenerator !== 'undefined' && wpQrGeneratorAjax.trackingEnabled) {
                wpQrGenerator.trackScan();
            }
        });
        </script>
        <?php
    }

    /**
     * Handle QR code redirects
     */
    public function handle_qr_redirect() {
        if (!isset($_GET['qr_redirect'])) {
            return;
        }

        $qr_id = intval($_GET['qr_id'] ?? 0);
        $target_url = esc_url($_GET['target_url'] ?? '');

        if ($qr_id && $target_url) {
            // Track the redirect
            $this->track_redirect($qr_id);

            // Redirect to target URL
            wp_redirect($target_url);
            exit;
        }
    }

    /**
     * Track QR code redirect
     *
     * @param int $qr_id QR code ID
     */
    private function track_redirect($qr_id) {
        global $wpdb;

        $device_info = $this->get_device_info();
        $session_id = $this->get_or_create_session_id();

        $wpdb->insert(
            $wpdb->prefix . 'qr_scans',
            array(
                'qr_code_id' => $qr_id,
                'device_info' => json_encode($device_info),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        // Update scan count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}qr_codes SET scans_count = scans_count + 1 WHERE id = %d",
            $qr_id
        ));
    }

    /**
     * Get device information
     *
     * @return array Device info
     */
    private function get_device_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return array(
            'user_agent' => $user_agent,
            'platform' => $this->detect_platform($user_agent),
            'browser' => $this->detect_browser($user_agent),
            'device_type' => $this->detect_device_type($user_agent),
            'is_mobile' => wp_is_mobile(),
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Detect platform from user agent
     *
     * @param string $user_agent User agent string
     * @return string Platform
     */
    private function detect_platform($user_agent) {
        $platforms = array(
            'Windows' => 'Windows',
            'Mac' => 'macOS',
            'Linux' => 'Linux',
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'Android' => 'Android'
        );

        foreach ($platforms as $pattern => $platform) {
            if (stripos($user_agent, $pattern) !== false) {
                return $platform;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect browser from user agent
     *
     * @param string $user_agent User agent string
     * @return string Browser
     */
    private function detect_browser($user_agent) {
        $browsers = array(
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Edge' => 'Edge',
            'Opera' => 'Opera'
        );

        foreach ($browsers as $pattern => $browser) {
            if (stripos($user_agent, $pattern) !== false) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect device type from user agent
     *
     * @param string $user_agent User agent string
     * @return string Device type
     */
    private function detect_device_type($user_agent) {
        if (stripos($user_agent, 'Mobile') !== false || stripos($user_agent, 'Android') !== false || stripos($user_agent, 'iPhone') !== false) {
            return 'Mobile';
        } elseif (stripos($user_agent, 'Tablet') !== false || stripos($user_agent, 'iPad') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Get or create session ID
     *
     * @return string Session ID
     */
    private function get_or_create_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
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
     * Check if tracking should be loaded
     *
     * @return bool Should load tracking
     */
    private function should_load_tracking() {
        // Load tracking on product pages, shop pages, or if QR parameters are present
        return is_product() || is_shop() || isset($_GET['qr_source']) || isset($_GET['qr_id']);
    }

    /**
     * Check if tracking is enabled
     *
     * @return bool Tracking enabled
     */
    private function is_tracking_enabled() {
        $settings = get_option('wp_qr_generator_settings', array());
        return $settings['enable_tracking'] ?? true;
    }

    public static function init() {
        add_action('template_redirect', array(__CLASS__, 'maybe_record_scan'));
        add_action('woocommerce_add_to_cart', array(__CLASS__, 'maybe_record_cart'), 10, 6);
        add_action('woocommerce_thankyou', array(__CLASS__, 'maybe_record_conversion'));
    }

    public static function maybe_record_scan() {
        if (isset($_GET['qr_id'])) {
            self::record_scan(intval($_GET['qr_id']));
        }
    }

    public static function record_scan($qr_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'qr_scans';
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $timestamp = current_time('mysql');

        // Get product_id from qr_codes table
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}qr_codes WHERE id = %d", $qr_id
        ));

        $wpdb->insert($table, [
            'qr_code_id' => $qr_id,
            'product_id' => $product_id,
            'ip_address' => $ip,
            'user_agent' => $user_agent,
            'scanned_at' => $timestamp,
        ]);
        // Store qr_id in session for cart/purchase tracking
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('qr_id', $qr_id);
            WC()->session->set('qr_product_id', $product_id);
        }
    }

    // Track add to cart
    public static function maybe_record_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        if (isset($_GET['qr_id'])) {
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('qr_id', intval($_GET['qr_id']));
                WC()->session->set('qr_product_id', $product_id);
            }
        }
    }

    // Track conversion (purchase)
    public static function maybe_record_conversion($order_id) {
        if (function_exists('WC') && WC()->session) {
            $qr_id = WC()->session->get('qr_id');
            $product_id = WC()->session->get('qr_product_id');
            if ($qr_id && $product_id) {
                global $wpdb;
                $table = $wpdb->prefix . 'qr_conversions';
                $wpdb->insert($table, [
                    'qr_code_id' => $qr_id,
                    'product_id' => $product_id,
                    'order_id' => $order_id,
                    'converted_at' => current_time('mysql'),
                ]);
                WC()->session->__unset('qr_id');
                WC()->session->__unset('qr_product_id');
            }
        }
    }
}

// Initialize tracking
WP_QR_Generator_Tracking::init(); 