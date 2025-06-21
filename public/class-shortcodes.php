<?php
/**
 * Shortcodes class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcodes Class
 */
class WP_QR_Generator_Shortcodes {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('qr_code', array($this, 'qr_code_shortcode'));
        add_shortcode('qr_product', array($this, 'qr_product_shortcode'));
        add_shortcode('qr_analytics', array($this, 'qr_analytics_shortcode'));
    }

    /**
     * QR Code shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function qr_code_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => '',
            'size' => 300,
            'color_dark' => '#000000',
            'color_light' => '#ffffff',
            'error_level' => 'H',
            'class' => '',
            'id' => ''
        ), $atts, 'qr_code');

        if (empty($atts['data'])) {
            return '<p>' . __('QR Code data is required.', 'wp-qr-generator') . '</p>';
        }

        $unique_id = $atts['id'] ?: 'qr-code-' . uniqid();
        $class = 'qr-code-container ' . $atts['class'];

        $output = '<div class="' . esc_attr($class) . '" id="' . esc_attr($unique_id) . '" ';
        $output .= 'data-qr-data="' . esc_attr($atts['data']) . '" ';
        $output .= 'data-qr-size="' . esc_attr($atts['size']) . '" ';
        $output .= 'data-qr-color-dark="' . esc_attr($atts['color_dark']) . '" ';
        $output .= 'data-qr-color-light="' . esc_attr($atts['color_light']) . '" ';
        $output .= 'data-qr-error-level="' . esc_attr($atts['error_level']) . '">';
        $output .= '</div>';

        // Add initialization script
        $output .= '<script type="text/javascript">';
        $output .= 'jQuery(document).ready(function($) {';
        $output .= 'if (typeof QRCode !== "undefined") {';
        $output .= 'new QRCode(document.getElementById("' . esc_js($unique_id) . '"), {';
        $output .= 'text: "' . esc_js($atts['data']) . '",';
        $output .= 'width: ' . intval($atts['size']) . ',';
        $output .= 'height: ' . intval($atts['size']) . ',';
        $output .= 'colorDark: "' . esc_js($atts['color_dark']) . '",';
        $output .= 'colorLight: "' . esc_js($atts['color_light']) . '",';
        $output .= 'correctLevel: QRCode.CorrectLevel.' . esc_js($atts['error_level']);
        $output .= '});';
        $output .= '}';
        $output .= '});';
        $output .= '</script>';

        return $output;
    }

    /**
     * QR Product shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function qr_product_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'size' => 300,
            'show_product_info' => 'false',
            'class' => ''
        ), $atts, 'qr_product');

        $product_id = intval($atts['id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            return '<p>' . __('Product not found.', 'wp-qr-generator') . '</p>';
        }

        // Generate tracking URL
        $product_url = get_permalink($product_id);
        $tracking_params = array(
            'qr_source' => 'shortcode',
            'qr_id' => $product_id,
            'qr_timestamp' => time()
        );
        $tracked_url = add_query_arg($tracking_params, $product_url);

        $output = '';

        // Show product info if requested
        if ($atts['show_product_info'] === 'true') {
            $output .= '<div class="qr-product-info">';
            $output .= '<h4>' . esc_html($product->get_name()) . '</h4>';
            $output .= '<p class="price">' . $product->get_price_html() . '</p>';
            $output .= '</div>';
        }

        // Generate QR code
        $qr_atts = array(
            'data' => $tracked_url,
            'size' => $atts['size'],
            'class' => 'qr-product-code ' . $atts['class'],
            'id' => 'qr-product-' . $product_id
        );

        $output .= $this->qr_code_shortcode($qr_atts);

        return '<div class="qr-product-container">' . $output . '</div>';
    }

    /**
     * QR Analytics shortcode (for displaying basic stats)
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function qr_analytics_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p>' . __('Insufficient permissions to view analytics.', 'wp-qr-generator') . '</p>';
        }

        $atts = shortcode_atts(array(
            'days' => 30,
            'show' => 'summary', // summary, chart, table
            'qr_id' => ''
        ), $atts, 'qr_analytics');

        $analytics = new WP_QR_Generator_Analytics();
        $date_from = date('Y-m-d', strtotime('-' . intval($atts['days']) . ' days'));
        $date_to = date('Y-m-d');

        $data = $analytics->get_analytics_data($date_from, $date_to);

        $output = '<div class="qr-analytics-container">';

        switch ($atts['show']) {
            case 'summary':
                $output .= $this->render_analytics_summary($data['summary']);
                break;
            case 'chart':
                $output .= $this->render_analytics_chart($data['daily_scans']);
                break;
            case 'table':
                $output .= $this->render_analytics_table($data['top_qr_codes']);
                break;
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render analytics summary
     *
     * @param array $summary Summary data
     * @return string HTML output
     */
    private function render_analytics_summary($summary) {
        $output = '<div class="qr-analytics-summary">';
        $output .= '<div class="qr-stat-grid">';

        $stats = array(
            'total_scans' => __('Total Scans', 'wp-qr-generator'),
            'unique_scans' => __('Unique Scans', 'wp-qr-generator'),
            'total_conversions' => __('Conversions', 'wp-qr-generator'),
            'conversion_rate' => __('Conversion Rate', 'wp-qr-generator')
        );

        foreach ($stats as $key => $label) {
            $value = $summary[$key] ?? 0;
            if ($key === 'conversion_rate') {
                $value .= '%';
            } elseif ($key === 'total_revenue') {
                $value = wc_price($value);
            }

            $output .= '<div class="qr-stat-item">';
            $output .= '<div class="qr-stat-value">' . esc_html($value) . '</div>';
            $output .= '<div class="qr-stat-label">' . esc_html($label) . '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render analytics chart (simplified)
     *
     * @param array $daily_scans Daily scan data
     * @return string HTML output
     */
    private function render_analytics_chart($daily_scans) {
        $output = '<div class="qr-analytics-chart">';
        $output .= '<h4>' . __('Daily Scans', 'wp-qr-generator') . '</h4>';
        $output .= '<div class="qr-chart-container" id="qr-chart-' . uniqid() . '">';

        // Simple bar chart using CSS
        if (!empty($daily_scans)) {
            $max_scans = max(array_column($daily_scans, 'scans'));
            
            foreach ($daily_scans as $day) {
                $height = $max_scans > 0 ? ($day->scans / $max_scans) * 100 : 0;
                $output .= '<div class="qr-chart-bar" style="height: ' . $height . '%;" title="' . esc_attr($day->date . ': ' . $day->scans . ' scans') . '"></div>';
            }
        } else {
            $output .= '<p>' . __('No scan data available.', 'wp-qr-generator') . '</p>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render analytics table
     *
     * @param array $qr_codes QR code data
     * @return string HTML output
     */
    private function render_analytics_table($qr_codes) {
        $output = '<div class="qr-analytics-table">';
        $output .= '<h4>' . __('Top Performing QR Codes', 'wp-qr-generator') . '</h4>';
        $output .= '<table class="qr-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>' . __('QR Code', 'wp-qr-generator') . '</th>';
        $output .= '<th>' . __('Scans', 'wp-qr-generator') . '</th>';
        $output .= '<th>' . __('Conversions', 'wp-qr-generator') . '</th>';
        $output .= '<th>' . __('Rate', 'wp-qr-generator') . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        if (!empty($qr_codes)) {
            foreach ($qr_codes as $qr) {
                $conversion_rate = $qr->scans > 0 ? round(($qr->conversions / $qr->scans) * 100, 1) : 0;
                $output .= '<tr>';
                $output .= '<td>' . esc_html(substr($qr->qr_code_data, 0, 50)) . '...</td>';
                $output .= '<td>' . intval($qr->scans) . '</td>';
                $output .= '<td>' . intval($qr->conversions) . '</td>';
                $output .= '<td>' . $conversion_rate . '%</td>';
                $output .= '</tr>';
            }
        } else {
            $output .= '<tr><td colspan="4">' . __('No data found.', 'wp-qr-generator') . '</td></tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        return $output;
    }
} 