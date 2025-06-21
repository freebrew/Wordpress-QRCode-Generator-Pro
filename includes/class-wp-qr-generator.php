<?php
/**
 * Main plugin class (HPOS Compatible)
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main WP_QR_Generator Class
 */
final class WP_QR_Generator {

    /**
     * Plugin instance
     *
     * @var WP_QR_Generator
     */
    private static $instance = null;

    /**
     * QR Generator instance
     *
     * @var WP_QR_Generator_QR_Generator
     */
    public $qr_generator;

    /**
     * Analytics instance
     *
     * @var WP_QR_Generator_Analytics
     */
    public $analytics;

    /**
     * Tracking instance
     *
     * @var WP_QR_Generator_Tracking
     */
    public $tracking;

    /**
     * Security instance
     *
     * @var WP_QR_Generator_Security
     */
    public $security;

    /**
     * Admin instance
     *
     * @var WP_QR_Generator_Admin
     */
    public $admin;

    /**
     * Public instance
     *
     * @var WP_QR_Generator_Public
     */
    public $public;

    /**
     * Get instance
     *
     * @return WP_QR_Generator
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        // Already defined in main file
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        $this->include_file('includes/class-order-utils.php');
        $this->include_file('includes/class-qr-generator.php');
        $this->include_file('includes/class-analytics.php');
        $this->include_file('includes/class-tracking.php');
        $this->include_file('includes/class-security.php');
        $this->include_file('includes/class-database.php');
        $this->include_file('includes/class-cache.php');
        $this->include_file('includes/class-error-handler.php');

        // Admin classes
        if (is_admin()) {
            $this->include_file('admin/class-admin.php');
            $this->include_file('admin/class-admin-settings.php');
        }

        // Public classes
        $this->include_file('public/class-public.php');
        $this->include_file('public/class-shortcodes.php');
        $this->include_file('public/class-widgets.php');
    }

    /**
     * Safely include file
     *
     * @param string $file File path relative to plugin root
     */
    private function include_file($file) {
        $file_path = WP_QR_GENERATOR_PLUGIN_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("WP QR Generator: Missing file - " . $file_path);
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('init', array($this, 'load_textdomain'));
        
        // HPOS compatibility notice
        add_action('admin_notices', array($this, 'hpos_compatibility_notice'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes only if they exist
        if (class_exists('WP_QR_Generator_QR_Generator')) {
            $this->qr_generator = new WP_QR_Generator_QR_Generator();
        }
        
        if (class_exists('WP_QR_Generator_Analytics')) {
            $this->analytics = new WP_QR_Generator_Analytics();
        }
        
        if (class_exists('WP_QR_Generator_Tracking')) {
            $this->tracking = new WP_QR_Generator_Tracking();
        }
        
        if (class_exists('WP_QR_Generator_Security')) {
            $this->security = new WP_QR_Generator_Security();
        }

        // Initialize admin
        if (is_admin() && class_exists('WP_QR_Generator_Admin')) {
            $this->admin = new WP_QR_Generator_Admin();
        }

        // Initialize public
        if (class_exists('WP_QR_Generator_Public')) {
            $this->public = new WP_QR_Generator_Public();
        }

        // Fire action after plugin initialization
        do_action('wp_qr_generator_loaded');
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-qr-generator',
            false,
            dirname(WP_QR_GENERATOR_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * HPOS compatibility notice
     */
    public function hpos_compatibility_notice() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
                $screen = get_current_screen();
                if ($screen && strpos($screen->id, 'wp-qr-generator') !== false) {
                    ?>
                    <div class="notice notice-info">
                        <p>
                            <?php _e('WordPress QR Code Generator is compatible with WooCommerce High-Performance Order Storage (HPOS).', 'wp-qr-generator'); ?>
                        </p>
                    </div>
                    <?php
                }
            }
        }
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        if (class_exists('WP_QR_Generator_Database')) {
            WP_QR_Generator_Database::create_tables();
        }

        // Create upload directory
        if (!file_exists(WP_QR_GENERATOR_UPLOADS_DIR)) {
            wp_mkdir_p(WP_QR_GENERATOR_UPLOADS_DIR);
        }

        // Add .htaccess file for security
        $htaccess_file = WP_QR_GENERATOR_UPLOADS_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, 'Options -Indexes' . PHP_EOL);
        }

        // Set default options
        add_option('wp_qr_generator_version', WP_QR_GENERATOR_VERSION);
        add_option('wp_qr_generator_settings', array(
            'default_size' => 300,
            'default_quality' => 'H',
            'enable_tracking' => true,
            'enable_analytics' => true,
            'cache_duration' => 3600
        ));

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wp_qr_generator_cleanup');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 