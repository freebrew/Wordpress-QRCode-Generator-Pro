<?php
/**
 * HPOS-compatible order utilities
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order Utils Class for HPOS compatibility
 */
class WP_QR_Generator_Order_Utils {

    /**
     * Check if HPOS is enabled
     *
     * @return bool
     */
    public static function is_hpos_enabled() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Get order by ID (HPOS compatible)
     *
     * @param int $order_id Order ID
     * @return WC_Order|false Order object or false
     */
    public static function get_order($order_id) {
        if (function_exists('wc_get_order')) {
            return wc_get_order($order_id);
        }
        return false;
    }

    /**
     * Get orders with specific meta (HPOS compatible)
     *
     * @param string $meta_key Meta key
     * @param string $meta_value Meta value
     * @param array $args Additional arguments
     * @return array Order IDs
     */
    public static function get_orders_by_meta($meta_key, $meta_value, $args = array()) {
        $default_args = array(
            'limit' => -1,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'return' => 'ids'
        );

        $args = wp_parse_args($args, $default_args);

        if (function_exists('wc_get_orders')) {
            return wc_get_orders($args);
        }

        // Fallback for older WooCommerce versions
        return array();
    }

    /**
     * Update order meta (HPOS compatible)
     *
     * @param int $order_id Order ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return bool Success status
     */
    public static function update_order_meta($order_id, $meta_key, $meta_value) {
        $order = self::get_order($order_id);
        if ($order) {
            $order->update_meta_data($meta_key, $meta_value);
            $order->save();
            return true;
        }
        return false;
    }

    /**
     * Get order meta (HPOS compatible)
     *
     * @param int $order_id Order ID
     * @param string $meta_key Meta key
     * @param bool $single Return single value
     * @return mixed Meta value
     */
    public static function get_order_meta($order_id, $meta_key, $single = true) {
        $order = self::get_order($order_id);
        if ($order) {
            return $order->get_meta($meta_key, $single);
        }
        return false;
    }

    /**
     * Delete order meta (HPOS compatible)
     *
     * @param int $order_id Order ID
     * @param string $meta_key Meta key
     * @return bool Success status
     */
    public static function delete_order_meta($order_id, $meta_key) {
        $order = self::get_order($order_id);
        if ($order) {
            $order->delete_meta_data($meta_key);
            $order->save();
            return true;
        }
        return false;
    }

    /**
     * Get recent orders (HPOS compatible)
     *
     * @param array $args Query arguments
     * @return array Order objects
     */
    public static function get_recent_orders($args = array()) {
        $default_args = array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array('wc-processing', 'wc-completed')
        );

        $args = wp_parse_args($args, $default_args);

        if (function_exists('wc_get_orders')) {
            return wc_get_orders($args);
        }

        return array();
    }

    /**
     * Get order count by status (HPOS compatible)
     *
     * @param string $status Order status
     * @param array $date_range Date range array with 'start' and 'end'
     * @return int Order count
     */
    public static function get_order_count_by_status($status, $date_range = array()) {
        $args = array(
            'status' => $status,
            'return' => 'ids',
            'limit' => -1
        );

        if (!empty($date_range['start'])) {
            $args['date_created'] = '>=' . $date_range['start'];
        }

        if (!empty($date_range['end'])) {
            $args['date_created'] = '<=' . $date_range['end'];
        }

        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders($args);
            return count($orders);
        }

        return 0;
    }

    /**
     * Get orders with QR tracking data (HPOS compatible)
     *
     * @param array $args Query arguments
     * @return array Orders with QR tracking
     */
    public static function get_qr_tracked_orders($args = array()) {
        $default_args = array(
            'limit' => -1,
            'meta_key' => '_qr_scan_id',
            'meta_compare' => 'EXISTS',
            'return' => 'objects'
        );

        $args = wp_parse_args($args, $default_args);

        if (function_exists('wc_get_orders')) {
            return wc_get_orders($args);
        }

        return array();
    }
} 