<?php
/**
 * Public-facing functionality
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public Class
 */
class WP_QR_Generator_Public {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'init_shortcodes'));
        add_action('wp_head', array($this, 'add_meta_tags'));
    }

    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'wp-qr-generator-public',
            WP_QR_GENERATOR_PLUGIN_URL . 'public/css/public.css',
            array(),
            WP_QR_GENERATOR_VERSION,
            'all'
        );
    }

    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'qrcode-js',
            WP_QR_GENERATOR_PLUGIN_URL . 'public/js/qrcode.min.js',
            array(),
            '1.4.4',
            true
        );

        wp_enqueue_script(
            'wp-qr-generator-public',
            WP_QR_GENERATOR_PLUGIN_URL . 'public/js/public.js',
            array('jquery', 'qrcode-js'),
            WP_QR_GENERATOR_VERSION,
            true
        );

        wp_localize_script('wp-qr-generator-public', 'wpQrGeneratorPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_qr_generator_public_nonce'),
            'settings' => array(
                'trackingEnabled' => get_option('wp_qr_generator_settings')['enable_tracking'] ?? true
            )
        ));
    }

    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        require_once WP_QR_GENERATOR_PLUGIN_PATH . 'public/class-shortcodes.php';
        new WP_QR_Generator_Shortcodes();
    }

    /**
     * Add meta tags for QR code pages
     */
    public function add_meta_tags() {
        if (is_product() && isset($_GET['qr_source'])) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
            echo '<meta name="qr-source" content="' . esc_attr($_GET['qr_source']) . '">' . "\n";
        }
    }
} 