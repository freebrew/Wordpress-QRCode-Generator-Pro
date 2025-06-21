<?php
/**
 * WordPress QR Code Generator Pro - Admin Class
 * 
 * Handles all administrative functionality including menu creation, 
 * page rendering, AJAX handlers, and backend operations.
 *
 * @package WP_QR_Generator_Pro
 * @subpackage Admin
 * @author Bruno Brottes <contact@brunobrottes.com>
 * @copyright 2024 Bruno Brottes
 * @license CodeCanyon Regular License
 * @version 1.0.0
 * @since 1.0.0
 * 
 * Features:
 * - WordPress admin menu integration
 * - Dashboard with integrated generate form and performance table
 * - AJAX handlers for QR code generation and management
 * - WooCommerce product integration
 * - Analytics and system status pages
 * - Professional WordPress coding standards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * WordPress QR Code Generator Pro Admin Class
 * 
 * This class manages all administrative functions of the plugin including:
 * - Admin menu creation and page routing
 * - Dashboard functionality with statistics and management
 * - AJAX endpoints for QR code operations
 * - WooCommerce integration for product selection
 * - System status and debugging capabilities
 * 
 * @since 1.0.0
 * @author Bruno Brottes
 */
class WP_QR_Generator_Admin {

    /**
     * Class Constructor
     * 
     * Initializes the admin class by hooking into WordPress actions.
     * Sets up admin menu, scripts, and AJAX handlers for all plugin functionality.
     * 
     * WordPress Actions Registered:
     * - admin_menu: Creates plugin admin menu structure
     * - admin_enqueue_scripts: Loads CSS/JS for admin pages
     * - wp_ajax_*: AJAX endpoints for various plugin operations
     * 
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function __construct() {
        // Admin menu and assets
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Core AJAX handlers for QR code operations
        add_action('wp_ajax_generate_qr_code', array($this, 'ajax_generate_qr_code'));
        add_action('wp_ajax_bulk_generate_qr_codes', array($this, 'ajax_bulk_generate_qr_codes'));
        add_action('wp_ajax_download_qr_code', array($this, 'ajax_download_qr_code'));
        
        // Data and system AJAX handlers
        add_action('wp_ajax_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_get_product_url', array($this, 'ajax_get_product_url'));
        add_action('wp_ajax_check_system_status', array($this, 'ajax_check_system_status'));
        
        // Library management AJAX handler
        add_action('wp_ajax_install_qr_library', array($this, 'ajax_install_qr_library'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('QR Code Generator', 'wp-qr-generator'),
            __('QR Codes', 'wp-qr-generator'),
            'manage_options',
            'wp-qr-generator',
            array($this, 'admin_page'),
            'dashicons-camera',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'wp-qr-generator',
            __('Dashboard', 'wp-qr-generator'),
            __('Dashboard', 'wp-qr-generator'),
            'manage_options',
            'wp-qr-generator',
            array($this, 'admin_page')
        );

        // Generate and Analytics removed - now integrated into Dashboard

        // System Status submenu
        add_submenu_page(
            'wp-qr-generator',
            __('System Status', 'wp-qr-generator'),
            __('System Status', 'wp-qr-generator'),
            'manage_options',
            'wp-qr-generator-system',
            array($this, 'system_status_page')
        );

        // Settings submenu
        add_submenu_page(
            'wp-qr-generator',
            __('Settings', 'wp-qr-generator'),
            __('Settings', 'wp-qr-generator'),
            'manage_options',
            'wp-qr-generator-settings',
            array($this, 'settings_page')
        );

        // Debug submenu (only for admins)
        add_submenu_page(
            'wp-qr-generator',
            __('Debug Info', 'wp-qr-generator'),
            __('Debug', 'wp-qr-generator'),
            'manage_options',
            'wp-qr-generator-debug',
            array($this, 'debug_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-qr-generator') === false) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'wp-qr-generator-admin',
            WP_QR_GENERATOR_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            WP_QR_GENERATOR_VERSION
        );

        // Include WordPress list table styles for proper formatting
        wp_enqueue_style('list-tables');

        // Enqueue admin JS
        wp_enqueue_script(
            'wp-qr-generator-admin',
            WP_QR_GENERATOR_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            WP_QR_GENERATOR_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wp-qr-generator-admin', 'wpQRGenerator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_qr_generator_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this QR code?', 'wp-qr-generator'),
                'select_product' => __('Please select a product.', 'wp-qr-generator'),
                'enter_data' => __('Please enter QR code data.', 'wp-qr-generator'),
                'generating' => __('Generating QR code...', 'wp-qr-generator'),
                'success' => __('QR code generated successfully!', 'wp-qr-generator'),
                'error' => __('Error generating QR code.', 'wp-qr-generator'),
                'installing_library' => __('Installing QR library...', 'wp-qr-generator'),
                'library_installed' => __('QR library installed successfully!', 'wp-qr-generator'),
                'library_error' => __('Error installing QR library.', 'wp-qr-generator')
            )
        ));
    }

    /**
     * Main admin dashboard page
     */
    public function admin_page() {
        $view_path = WP_QR_GENERATOR_PLUGIN_PATH . 'admin/views/dashboard.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Error', 'wp-qr-generator') . '</h1><p>' . esc_html__('Dashboard view file is missing.', 'wp-qr-generator') . '</p></div>';
        }
    }

    // Generate and Analytics pages removed - functionality integrated into Dashboard

    /**
     * System Status page
     */
    public function system_status_page() {
        $this->render_system_status();
    }

    /**
     * Settings page
     */
    public function settings_page() {
        $this->render_simple_settings();
    }

    /**
     * Debug page - shows tracking information for troubleshooting
     */
    public function debug_page() {
        global $wpdb;
        
        // Start session to check session data
        if (!session_id()) {
            session_start();
        }
        
        echo '<div class="wrap">';
        echo '<h1>QR Code Generator Debug Information</h1>';
        
        // Database Tables Status
        echo '<h2>Database Tables</h2>';
        $tables = ['qr_codes', 'qr_scans', 'qr_conversions'];
        echo '<table class="widefat">';
        echo '<thead><tr><th>Table</th><th>Exists</th><th>Row Count</th></tr></thead><tbody>';
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
            echo '<tr><td>' . $table . '</td><td>' . ($exists ? 'Yes' : 'No') . '</td><td>' . $count . '</td></tr>';
        }
        echo '</tbody></table>';
        
        // WooCommerce Analytics Tables
        echo '<h2>WooCommerce Analytics Tables</h2>';
        $wc_tables = [
            'wc_orders' => 'HPOS Orders',
            'wc_order_stats' => 'Order Statistics', 
            'wc_order_product_lookup' => 'Order Product Lookup',
            'wc_order_tax_lookup' => 'Order Tax Lookup',
            'wc_order_coupon_lookup' => 'Order Coupon Lookup',
            'wc_customer_lookup' => 'Customer Lookup'
        ];
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>Table</th><th>Description</th><th>Exists</th><th>Row Count</th></tr></thead><tbody>';
        foreach ($wc_tables as $table => $description) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
            echo '<tr><td>' . $table . '</td><td>' . $description . '</td><td>' . ($exists ? 'Yes' : 'No') . '</td><td>' . $count . '</td></tr>';
        }
        echo '</tbody></table>';
        
        // Recent WooCommerce Orders (both legacy and HPOS)
        echo '<h2>Recent WooCommerce Orders (Last 5)</h2>';
        
        // Check for HPOS orders first
        $hpos_table = $wpdb->prefix . 'wc_orders';
        $hpos_exists = $wpdb->get_var("SHOW TABLES LIKE '$hpos_table'") == $hpos_table;
        
        if ($hpos_exists) {
            echo '<h3>HPOS Orders (wc_orders table)</h3>';
            $hpos_orders = $wpdb->get_results("
                SELECT id, status, currency, total_amount, date_created_gmt 
                FROM {$hpos_table} 
                ORDER BY date_created_gmt DESC 
                LIMIT 5
            ");
            
            if ($hpos_orders) {
                echo '<table class="widefat"><thead><tr><th>Order ID</th><th>Status</th><th>Total</th><th>Currency</th><th>Date</th></tr></thead><tbody>';
                foreach ($hpos_orders as $order) {
                    echo '<tr>';
                    echo '<td>' . $order->id . '</td>';
                    echo '<td>' . $order->status . '</td>';
                    echo '<td>' . $order->total_amount . '</td>';
                    echo '<td>' . $order->currency . '</td>';
                    echo '<td>' . $order->date_created_gmt . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No HPOS orders found.</p>';
            }
        }
        
        // Legacy orders
        echo '<h3>Legacy Orders (posts table)</h3>';
        $legacy_orders = $wpdb->get_results("
            SELECT ID, post_status, post_date 
            FROM {$wpdb->posts} 
            WHERE post_type = 'shop_order' 
            ORDER BY post_date DESC 
            LIMIT 5
        ");
        
        if ($legacy_orders) {
            echo '<table class="widefat"><thead><tr><th>Order ID</th><th>Status</th><th>Date</th></tr></thead><tbody>';
            foreach ($legacy_orders as $order) {
                echo '<tr>';
                echo '<td>' . $order->ID . '</td>';
                echo '<td>' . $order->post_status . '</td>';
                echo '<td>' . $order->post_date . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No legacy orders found.</p>';
        }
        
        // Session Data
        echo '<h2>Current Session Data</h2>';
        echo '<pre>';
        echo 'Session ID: ' . session_id() . "\n";
        echo 'QR Scan ID: ' . (isset($_SESSION['wp_qr_scan_id']) ? $_SESSION['wp_qr_scan_id'] : 'Not set') . "\n";
        echo 'QR Product ID: ' . (isset($_SESSION['wp_qr_product_id']) ? $_SESSION['wp_qr_product_id'] : 'Not set') . "\n";
        echo '</pre>';
        
        // Transient Tracking Data
        echo '<h2>Current Tracking Data (Transient)</h2>';
        $user_key = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $tracking_data = get_transient('qr_tracking_' . $user_key);
        echo '<pre>';
        echo 'User Key: ' . $user_key . "\n";
        echo 'IP Address: ' . $_SERVER['REMOTE_ADDR'] . "\n";
        echo 'User Agent: ' . substr($_SERVER['HTTP_USER_AGENT'], 0, 50) . "...\n";
        if ($tracking_data) {
            echo 'Scan ID: ' . $tracking_data['scan_id'] . "\n";
            echo 'Product ID: ' . $tracking_data['product_id'] . "\n";
            echo 'QR ID: ' . $tracking_data['qr_id'] . "\n";
            echo 'Timestamp: ' . date('Y-m-d H:i:s', $tracking_data['timestamp']) . "\n";
        } else {
            echo 'No tracking data found for this user' . "\n";
        }
        echo '</pre>';
        
        // QR Codes Table Debug
        echo '<h2>QR Codes Table (Recent 10)</h2>';
        $qr_codes_debug = $wpdb->get_results("
            SELECT id, product_id, qr_code_data, file_path, created_at 
            FROM {$wpdb->prefix}qr_codes 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        if ($qr_codes_debug) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>QR ID</th><th>Product ID</th><th>QR Data</th><th>File Path</th><th>Created</th></tr></thead><tbody>';
            foreach ($qr_codes_debug as $qr) {
                echo '<tr>';
                echo '<td>' . $qr->id . '</td>';
                echo '<td>' . ($qr->product_id ?: '<em>NULL</em>') . '</td>';
                echo '<td>' . esc_html(substr($qr->qr_code_data, 0, 50)) . '...</td>';
                echo '<td>' . ($qr->file_path ?: '<em>NULL</em>') . '</td>';
                echo '<td>' . $qr->created_at . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No QR codes found in database.</p>';
        }
        
        // Recent Scans
        echo '<h2>Recent QR Scans (Last 10)</h2>';
        $recent_scans = $wpdb->get_results("
            SELECT s.*, q.product_id, q.qr_code_data 
            FROM {$wpdb->prefix}qr_scans s 
            LEFT JOIN {$wpdb->prefix}qr_codes q ON s.qr_code_id = q.id 
            ORDER BY s.scan_time DESC 
            LIMIT 10
        ");
        
        if ($recent_scans) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>Scan ID</th><th>QR Code ID</th><th>Product ID</th><th>IP Address</th><th>Scan Time</th></tr></thead><tbody>';
            foreach ($recent_scans as $scan) {
                echo '<tr>';
                echo '<td>' . $scan->id . '</td>';
                echo '<td>' . $scan->qr_code_id . '</td>';
                echo '<td>' . $scan->product_id . '</td>';
                echo '<td>' . $scan->ip_address . '</td>';
                echo '<td>' . $scan->scan_time . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No scans recorded yet.</p>';
        }
        
        // Recent Conversions
        echo '<h2>Recent Conversions (Last 10)</h2>';
        $recent_conversions = $wpdb->get_results("
            SELECT c.*, s.qr_code_id, q.product_id 
            FROM {$wpdb->prefix}qr_conversions c 
            LEFT JOIN {$wpdb->prefix}qr_scans s ON c.scan_id = s.id 
            LEFT JOIN {$wpdb->prefix}qr_codes q ON s.qr_code_id = q.id 
            ORDER BY c.conversion_time DESC 
            LIMIT 10
        ");
        
        if ($recent_conversions) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>Conversion ID</th><th>Scan ID</th><th>Order ID</th><th>QR Code ID</th><th>Product ID</th><th>Revenue</th><th>Time</th></tr></thead><tbody>';
            foreach ($recent_conversions as $conversion) {
                echo '<tr>';
                echo '<td>' . $conversion->id . '</td>';
                echo '<td>' . $conversion->scan_id . '</td>';
                echo '<td>' . $conversion->order_id . '</td>';
                echo '<td>' . $conversion->qr_code_id . '</td>';
                echo '<td>' . $conversion->product_id . '</td>';
                echo '<td>$' . $conversion->revenue . '</td>';
                echo '<td>' . $conversion->conversion_time . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No conversions recorded yet.</p>';
        }
        
        // Clear Session Button
        echo '<h2>Debug Actions</h2>';
        if (isset($_POST['clear_session'])) {
            unset($_SESSION['wp_qr_scan_id']);
            unset($_SESSION['wp_qr_product_id']);
            echo '<div class="notice notice-success"><p>Session data cleared!</p></div>';
        }
        
        if (isset($_POST['clear_transient'])) {
            $user_key = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
            delete_transient('qr_tracking_' . $user_key);
            echo '<div class="notice notice-success"><p>Transient tracking data cleared!</p></div>';
        }
        
        echo '<form method="post">';
        echo '<input type="submit" name="clear_session" value="Clear Session Data" class="button" />';
        echo '<input type="submit" name="clear_transient" value="Clear Tracking Data" class="button button-primary" style="margin-left: 10px;" />';
        echo '</form>';
        
        echo '</div>';
    }

    /**
     * Get WooCommerce Products for QR Code Generation
     * 
     * Retrieves a filtered list of WooCommerce products that are available 
     * for QR code generation. Excludes products that already have QR codes
     * to prevent duplicates.
     * 
     * Smart Duplicate Prevention:
     * - Queries existing QR codes table to get products with QR codes
     * - Filters out these products from the available options
     * - Provides clear messaging when all products have QR codes
     * 
     * Product Data Structure:
     * - id: Product ID for form submission
     * - name: Product name with price for user identification
     * - sku: Product SKU for reference
     * - price: Formatted price HTML from WooCommerce
     * - url: Product permalink for verification
     * 
     * @since 1.0.0
     * @access public
     * @global wpdb $wpdb WordPress database abstraction object
     * 
     * @return array Array of available products or empty array if none available
     *               Each product contains: id, name, sku, price, url
     * 
     * @example
     * $products = $admin->get_woocommerce_products();
     * foreach ($products as $product) {
     *     echo $product['name']; // "Product Name ($19.99)"
     * }
     */
    public function get_woocommerce_products() {
        global $wpdb;
        
        // Verify WooCommerce is active and available
        if (!class_exists('WooCommerce') || !function_exists('wc_get_products')) {
            error_log('[WP_QR_Generator] ERROR: WooCommerce not active or wc_get_products() not found.');
            return array();
        }

        // Query existing QR codes to prevent duplicates
        // This is our smart duplicate prevention system
        $existing_qr_products = $wpdb->get_col("
            SELECT DISTINCT product_id 
            FROM {$wpdb->prefix}qr_codes 
            WHERE product_id IS NOT NULL AND product_id > 0
        ");

        // Get published WooCommerce products
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => 200, // Reasonable limit for performance
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $product_list = array();
        
        // Process each product and filter out existing QR codes
        foreach ($products as $product) {
            $product_id = $product->get_id();
            
            // Skip products that already have QR codes (duplicate prevention)
            if (in_array($product_id, $existing_qr_products)) {
                continue;
            }
            
            // Build product data array for dropdown/selection
            $product_list[] = array(
                'id' => $product_id,
                'name' => $product->get_name() . ' (' . strip_tags($product->get_price_html()) . ')',
                'sku' => $product->get_sku(),
                'price' => $product->get_price_html(),
                'url' => get_permalink($product_id)
            );
        }

        return $product_list;
    }

    /**
     * Get summary stats for the dashboard widgets.
     *
     * @return array An array of summary statistics.
     */
    public function get_dashboard_summary_stats() {
        global $wpdb;
        $scans_table = $wpdb->prefix . 'qr_scans';
        $conversions_table = $wpdb->prefix . 'qr_conversions';
        
        $stats = [
            'total_scans'       => 0,
            'unique_visitors'   => 0,
            'total_conversions' => 0,
            'total_revenue'     => 0,
            'conversion_rate'   => 0,
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '{$scans_table}'") != $scans_table) {
            return $stats;
        }

        $stats['total_scans'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$scans_table}");
        $stats['unique_visitors'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT ip_address) FROM {$scans_table}");
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$conversions_table}'") == $conversions_table) {
            $conversion_stats = $wpdb->get_row("
                SELECT COUNT(*) as total_conversions, COALESCE(SUM(revenue), 0) as total_revenue
                FROM {$conversions_table}"
            );
            $stats['total_conversions'] = (int) $conversion_stats->total_conversions;
            $stats['total_revenue'] = (float) $conversion_stats->total_revenue;
        }
        
        if ($stats['total_scans'] > 0) {
            $stats['conversion_rate'] = round(($stats['total_conversions'] / $stats['total_scans']) * 100, 2);
        }

        return $stats;
    }

    /**
     * Get detailed statistics for all QR codes.
     *
     * @return array An array of detailed QR code statistics.
     */
    public function get_qr_code_stats() {
        global $wpdb;
        $qrcodes_table = $wpdb->prefix . 'qr_codes';
        $scans_table = $wpdb->prefix . 'qr_scans';
        $conversions_table = $wpdb->prefix . 'qr_conversions';
        $posts_table = $wpdb->posts;

        if ($wpdb->get_var("SHOW TABLES LIKE '{$qrcodes_table}'") != $qrcodes_table) {
            return [];
        }

        $query = "
            SELECT
                q.id,
                q.product_id,
                q.qr_code_data,
                p.post_title AS product_name,
                (SELECT COUNT(*) FROM {$scans_table} s WHERE s.qr_code_id = q.id) AS scans,
                (SELECT COUNT(DISTINCT s.ip_address) FROM {$scans_table} s WHERE s.qr_code_id = q.id) AS visitors,
                (SELECT COUNT(*) FROM {$conversions_table} c WHERE c.scan_id IN (SELECT id FROM {$scans_table} s WHERE s.qr_code_id = q.id)) AS conversions,
                COALESCE((SELECT SUM(c.revenue) FROM {$conversions_table} c WHERE c.scan_id IN (SELECT id FROM {$scans_table} s WHERE s.qr_code_id = q.id)), 0) AS revenue
            FROM {$qrcodes_table} q
            LEFT JOIN {$posts_table} p ON q.product_id = p.ID
            GROUP BY q.id
            ORDER BY scans DESC
        ";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * System Status Page
     */
    private function render_system_status() {
        global $wpdb;

        // Check for chillerlan/php-qrcode
        $autoload = WP_QR_GENERATOR_PLUGIN_PATH . 'vendor/autoload.php';
        $php_qr_installed = false;
        $php_qr_version = '-';
        if (file_exists($autoload)) {
            require_once $autoload;
            if (class_exists('chillerlan\\QRCode\\QRCode')) {
                $php_qr_installed = true;
                // Try to get version from composer.lock or constant
                $composer_lock = WP_QR_GENERATOR_PLUGIN_PATH . 'composer.lock';
                if (file_exists($composer_lock)) {
                    $lock = json_decode(file_get_contents($composer_lock), true);
                    if (!empty($lock['packages'])) {
                        foreach ($lock['packages'] as $pkg) {
                            if ($pkg['name'] === 'chillerlan/php-qrcode') {
                                $php_qr_version = $pkg['version'];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $local_js_path = WP_QR_GENERATOR_PLUGIN_PATH . 'public/js/qrcode.min.js';
        $local_js_exists = file_exists($local_js_path);

        ?>
        <div class="wrap">
            <h1><?php _e('System Status', 'wp-qr-generator'); ?></h1>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                <h2><?php _e('QR Code Libraries', 'wp-qr-generator'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Component', 'wp-qr-generator'); ?></th>
                            <th><?php _e('Status', 'wp-qr-generator'); ?></th>
                            <th><?php _e('Version', 'wp-qr-generator'); ?></th>
                            <th><?php _e('Action', 'wp-qr-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('PHP QR Code (chillerlan/php-qrcode)', 'wp-qr-generator'); ?></strong></td>
                            <td>
                                <?php if ($php_qr_installed): ?>
                                    <span class="status-indicator status-success">✓ <?php _e('Installed', 'wp-qr-generator'); ?></span>
                                <?php else: ?>
                                    <span class="status-indicator status-error">✗ <?php _e('Not Installed', 'wp-qr-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($php_qr_version); ?></td>
                            <td>
                                <a href="https://github.com/chillerlan/php-qrcode" target="_blank" class="button"><?php _e('View on GitHub', 'wp-qr-generator'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('JavaScript QRCode.js (CDN)', 'wp-qr-generator'); ?></strong></td>
                            <td id="js-cdn-status"><span class="status-indicator status-warning"><?php _e('Checking...', 'wp-qr-generator'); ?></span></td>
                            <td id="js-cdn-version">-</td>
                            <td>
                                <a href="https://cdnjs.com/libraries/qrcodejs" target="_blank" class="button"><?php _e('View CDN', 'wp-qr-generator'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('JavaScript QRCode.js (Local)', 'wp-qr-generator'); ?></strong></td>
                            <td>
                                <?php if ($local_js_exists): ?>
                                    <span class="status-indicator status-success">✓ <?php _e('Installed', 'wp-qr-generator'); ?></span>
                                <?php else: ?>
                                    <span class="status-indicator status-error">✗ <?php _e('Not Installed', 'wp-qr-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td id="js-local-version">-</td>
                            <td>
                                <?php if (!$local_js_exists): ?>
                                    <a href="https://github.com/davidshimjs/qrcodejs" target="_blank" class="button"><?php _e('Download', 'wp-qr-generator'); ?></a>
                                <?php else: ?>
                                    <span class="description"><?php _e('Local file detected', 'wp-qr-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div id="library-install-result" style="margin-top: 20px; display: none;"></div>
            </div>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                <h2><?php _e('Database Tables', 'wp-qr-generator'); ?></h2>
                <?php
                $tables = array(
                    'qr_codes' => __('QR Codes', 'wp-qr-generator'),
                    'qr_scans' => __('QR Scans', 'wp-qr-generator'),
                    'qr_conversions' => __('QR Conversions', 'wp-qr-generator'),
                    'qr_errors' => __('Error Logs', 'wp-qr-generator')
                );
                
                echo '<table class="widefat">';
                echo '<thead><tr><th>' . __('Table', 'wp-qr-generator') . '</th><th>' . __('Status', 'wp-qr-generator') . '</th><th>' . __('Records', 'wp-qr-generator') . '</th></tr></thead>';
                echo '<tbody>';
                foreach ($tables as $table_suffix => $table_name) {
                    $table_name_full = $wpdb->prefix . $table_suffix;
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_full'");
                    $record_count = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name_full") : 0;
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($table_name) . '</strong></td>';
                    echo '<td>';
                    if ($table_exists) {
                        echo '<span class="status-indicator status-success">✓ ' . __('Exists', 'wp-qr-generator') . '</span>';
                    } else {
                        echo '<span class="status-indicator status-error">✗ ' . __('Missing', 'wp-qr-generator') . '</span>';
                    }
                    echo '</td>';
                    echo '<td>' . number_format($record_count) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                ?>
            </div>
        </div>

        <script>
        // Check CDN version and status
        (function() {
            var script = document.createElement('script');
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js";
            script.onload = function() {
                document.getElementById('js-cdn-status').innerHTML = '<span class="status-indicator status-success">✓ <?php echo esc_js(__('Loaded', 'wp-qr-generator')); ?></span>';
                // Try to get version (QRCode.js does not expose a version, so we hardcode or check window.QRCode)
                document.getElementById('js-cdn-version').textContent = "1.0.0";
            };
            script.onerror = function() {
                document.getElementById('js-cdn-status').innerHTML = '<span class="status-indicator status-error">✗ <?php echo esc_js(__('Not Loaded', 'wp-qr-generator')); ?></span>';
            };
            document.head.appendChild(script);

            // Check local version if local file is present
            <?php if ($local_js_exists): ?>
            var localScript = document.createElement('script');
            localScript.src = "<?php echo esc_js(WP_QR_GENERATOR_PLUGIN_URL . 'public/js/qrcode.min.js'); ?>";
            localScript.onload = function() {
                document.getElementById('js-local-version').textContent = "1.0.0";
            };
            localScript.onerror = function() {
                document.getElementById('js-local-version').textContent = "<?php echo esc_js(__('Error loading', 'wp-qr-generator')); ?>";
            };
            document.head.appendChild(localScript);
            <?php endif; ?>
        })();
        </script>

        <style>
        .status-indicator {
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: 500;
        }
        .status-success { background: #d1e7dd; color: #0f5132; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }

    /**
     * AJAX handler for installing QR library
     */
    public function ajax_install_qr_library() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        // This function is deprecated as we now use Composer.
        // Kept for backwards compatibility if needed, but should not be called.
        wp_send_json_error(array(
            'message' => __('This function is deprecated. Please use Composer to install libraries.', 'wp-qr-generator')
        ));
    }

    /**
     * Render simple analytics if view file doesn't exist
     */
    private function render_simple_analytics() {
        // Keep existing analytics method
        ?>
        <div class="wrap">
            <h1><?php _e('QR Code Analytics', 'wp-qr-generator'); ?></h1>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                <h2><?php _e('Analytics Overview', 'wp-qr-generator'); ?></h2>
                
                <?php
                global $wpdb;
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}qr_scans'");
                
                if ($table_exists) {
                    $total_scans = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}qr_scans");
                    $total_qr_codes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}qr_codes");
                    $total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}qr_conversions");
                    
                    echo '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">';
                    
                    echo '<div style="background: #f0f0f1; padding: 20px; text-align: center; border-radius: 4px;">';
                    echo '<h3 style="margin: 0; font-size: 32px; color: #1d2327;">' . intval($total_qr_codes) . '</h3>';
                    echo '<p style="margin: 5px 0 0 0; color: #646970;">' . __('Total QR Codes', 'wp-qr-generator') . '</p>';
                    echo '</div>';
                    
                    echo '<div style="background: #f0f0f1; padding: 20px; text-align: center; border-radius: 4px;">';
                    echo '<h3 style="margin: 0; font-size: 32px; color: #1d2327;">' . intval($total_scans) . '</h3>';
                    echo '<p style="margin: 5px 0 0 0; color: #646970;">' . __('Total Scans', 'wp-qr-generator') . '</p>';
                    echo '</div>';
                    
                    echo '<div style="background: #f0f0f1; padding: 20px; text-align: center; border-radius: 4px;">';
                    echo '<h3 style="margin: 0; font-size: 32px; color: #1d2327;">' . intval($total_conversions) . '</h3>';
                    echo '<p style="margin: 5px 0 0 0; color: #646970;">' . __('Total Conversions', 'wp-qr-generator') . '</p>';
                    echo '</div>';
                    
                    echo '</div>';
                } else {
                    echo '<p>' . __('No analytics data available. Database tables not found.', 'wp-qr-generator') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render simple settings if view file doesn't exist
     */
    private function render_simple_settings() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['qr_settings_nonce'], 'qr_settings_save')) {
            $settings = array(
                'default_size' => intval($_POST['default_size']),
                'default_quality' => sanitize_text_field($_POST['default_quality']),
                'enable_tracking' => isset($_POST['enable_tracking']) ? 1 : 0,
                'enable_analytics' => isset($_POST['enable_analytics']) ? 1 : 0,
            );
            
            update_option('wp_qr_generator_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wp-qr-generator') . '</p></div>';
        }

        $settings = get_option('wp_qr_generator_settings', array(
            'default_size' => 300,
            'default_quality' => 'H',
            'enable_tracking' => true,
            'enable_analytics' => true,
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('QR Code Generator Settings', 'wp-qr-generator'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('qr_settings_save', 'qr_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default QR Code Size', 'wp-qr-generator'); ?></th>
                        <td>
                            <select name="default_size">
                                <option value="200" <?php selected($settings['default_size'], 200); ?>>200x200</option>
                                <option value="300" <?php selected($settings['default_size'], 300); ?>>300x300</option>
                                <option value="400" <?php selected($settings['default_size'], 400); ?>>400x400</option>
                                <option value="500" <?php selected($settings['default_size'], 500); ?>>500x500</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Error Correction Level', 'wp-qr-generator'); ?></th>
                        <td>
                            <select name="default_quality">
                                <option value="L" <?php selected($settings['default_quality'], 'L'); ?>><?php _e('Low (7%)', 'wp-qr-generator'); ?></option>
                                <option value="M" <?php selected($settings['default_quality'], 'M'); ?>><?php _e('Medium (15%)', 'wp-qr-generator'); ?></option>
                                <option value="Q" <?php selected($settings['default_quality'], 'Q'); ?>><?php _e('Quartile (25%)', 'wp-qr-generator'); ?></option>
                                <option value="H" <?php selected($settings['default_quality'], 'H'); ?>><?php _e('High (30%)', 'wp-qr-generator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Tracking', 'wp-qr-generator'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_tracking" value="1" <?php checked($settings['enable_tracking'], 1); ?> />
                            <label><?php _e('Track QR code scans and user interactions', 'wp-qr-generator'); ?></label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Analytics', 'wp-qr-generator'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_analytics" value="1" <?php checked($settings['enable_analytics'], 1); ?> />
                            <label><?php _e('Generate analytics reports and charts', 'wp-qr-generator'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX handler for enhanced QR code generation with template options
     */
    public function ajax_generate_qr_code() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        $qr_type = isset($_POST['qr_type']) ? sanitize_text_field($_POST['qr_type']) : 'product';
        
        // Collect template options
        $template_options = array(
            'include_header' => isset($_POST['include_header']) && $_POST['include_header'] === '1',
            'include_footer' => isset($_POST['include_footer']) && $_POST['include_footer'] === '1',
            'include_navigation' => isset($_POST['include_navigation']) && $_POST['include_navigation'] === '1',
            'include_sidebar' => isset($_POST['include_sidebar']) && $_POST['include_sidebar'] === '1',
            'qr_position' => isset($_POST['qr_position']) ? sanitize_text_field($_POST['qr_position']) : 'center',
            'qr_size' => isset($_POST['qr_size']) ? intval($_POST['qr_size']) : 300
        );
        
        if (!class_exists('WP_QR_Generator_QR_Generator')) {
            require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-qr-generator.php';
        }
        $qr_generator = new WP_QR_Generator_QR_Generator();
        
        // Determine target URL and data based on QR type
        $target_url = '';
        $qr_data = '';
        $product_id = 0;
        
        switch ($qr_type) {
            case 'product':
                $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
                if (empty($product_id)) {
                    wp_send_json_error(['message' => 'Please select a product.']);
                    return;
                }
                $target_url = get_permalink($product_id);
                $qr_data = $target_url;
                break;
                
            case 'category':
                $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
                if (empty($category_id)) {
                    wp_send_json_error(['message' => 'Please select a category.']);
                    return;
                }
                $target_url = get_term_link($category_id, 'product_cat');
                if (is_wp_error($target_url)) {
                    wp_send_json_error(['message' => 'Invalid category selected.']);
                    return;
                }
                $qr_data = $target_url;
                break;
                
            case 'shop':
                if (function_exists('wc_get_page_id')) {
                    $shop_page_id = wc_get_page_id('shop');
                    $target_url = get_permalink($shop_page_id);
                } else {
                    $target_url = home_url('/shop/');
                }
                $qr_data = $target_url;
                break;
                
            case 'custom':
                $custom_data = isset($_POST['custom_data']) ? sanitize_textarea_field($_POST['custom_data']) : '';
                if (empty($custom_data)) {
                    wp_send_json_error(['message' => 'Please enter custom data.']);
                    return;
                }
                $qr_data = $custom_data;
                $target_url = $custom_data;
                break;
                
            default:
                wp_send_json_error(['message' => 'Invalid QR code type.']);
                return;
        }
        
        // Generate the template-enhanced QR code
        $result = $this->generate_template_qr_code($qr_data, $target_url, $product_id, $template_options, $qr_type);

        if ($result && !empty($result['success'])) {
            wp_send_json_success([
                'file_url' => $result['file_url'],
                'url' => $result['tracking_url'],
                'template_url' => isset($result['template_url']) ? $result['template_url'] : null,
                'pdf_url' => isset($result['pdf_url']) ? $result['pdf_url'] : null,
                'qr_id' => $result['qr_id']
            ]);
        } else {
            $error = $result['error'] ?? 'An unknown error occurred.';
            wp_send_json_error(['message' => $error]);
        }
    }
    
    /**
     * Generate QR code with template integration
     */
    private function generate_template_qr_code($qr_data, $target_url, $product_id, $template_options, $qr_type) {
        if (!class_exists('WP_QR_Generator_Template_Generator')) {
            require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-template-generator.php';
        }
        
        $template_generator = new WP_QR_Generator_Template_Generator();
        return $template_generator->generate_template_qr_code($qr_data, $target_url, $product_id, $template_options, $qr_type);
    }

    /**
     * AJAX handler for bulk QR code generation
     */
    public function ajax_bulk_generate_qr_codes() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        wp_send_json_success(array('message' => 'Bulk generation not fully implemented yet.'));
    }

    /**
     * AJAX handler for analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        wp_send_json_success(array('message' => 'Analytics not fully implemented yet.'));
    }

    /**
     * AJAX handler to get product URL
     */
    public function ajax_get_product_url() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        $product_id = intval($_POST['product_id']);
        
        if ($product_id && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_url = get_permalink($product_id);
                $tracking_url = add_query_arg(array(
                    'qr_source' => 'admin',
                    'qr_id' => $product_id,
                    'qr_timestamp' => time()
                ), $product_url);

                wp_send_json_success(array(
                    'product_url' => $product_url,
                    'tracking_url' => $tracking_url,
                    'product_name' => $product->get_name(),
                    'product_price' => $product->get_price_html()
                ));
            }
        }

        wp_send_json_error(__('Product not found', 'wp-qr-generator'));
    }

    /**
     * AJAX handler for checking system status
     */
    public function ajax_check_system_status() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-qr-generator'));
        }

        // Call the system status function from main plugin file
        $result = wp_qr_generator_check_system_status();

        if ($result) {
            wp_send_json_success(array(
                'message' => __('System status checked successfully!', 'wp-qr-generator')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to check system status.', 'wp-qr-generator')
            ));
        }
    }

    /**
     * AJAX handler for downloading QR code
     */
    public function ajax_download_qr_code() {
        check_ajax_referer('wp_qr_generator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-qr-generator')));
        }

        $qr_lib_path = WP_QR_GENERATOR_PLUGIN_PATH . 'vendor/phpqrcode/qrlib.php';
        if (!file_exists($qr_lib_path)) {
            wp_send_json_error(array('message' => __('QR code library not installed. Please install it from System Status.', 'wp-qr-generator')));
        }

        // ... proceed with download logic ...
    }
} 