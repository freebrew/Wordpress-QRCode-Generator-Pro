<?php
/**
 * QR Code Generator Class
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required classes
require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-security.php';

/**
 * QR Generator Class
 */
class WP_QR_Generator_QR_Generator {

    /**
     * Temp directory for QR codes
     *
     * @var string
     */
    private $temp_dir;

    /**
     * Quality level
     *
     * @var string
     */
    private $quality;

    /**
     * Cache instance
     *
     * @var WP_QR_Generator_Cache
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->temp_dir = WP_QR_GENERATOR_UPLOADS_DIR;
        $this->quality = get_option('wp_qr_generator_settings')['default_quality'] ?? 'H';
        
        if (class_exists('WP_QR_Generator_Cache')) {
            $this->cache = new WP_QR_Generator_Cache();
        }
        
        // Require Composer autoloader for chillerlan/php-qrcode
        $autoload = WP_QR_GENERATOR_PLUGIN_PATH . 'vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
            error_log('Composer autoload not found for chillerlan/php-qrcode');
        }
    }

    /**
     * Generate QR Code and save to database using chillerlan/php-qrcode
     *
     * @param string $data QR code data
     * @param int $product_id Product ID (optional)
     * @param array $options Additional options
     * @return array|false Result array on success, false on failure
     */
    public function generate_and_save_qr_code($data, $product_id = 0, $options = array()) {
        try {
            $data = WP_QR_Generator_Security::validate_input($data);
            $filename = 'qr_' . md5($data . time()) . '.png';
            $filepath = $this->temp_dir . $filename;

            // Debug: Log temp dir and file path
            error_log('[WP_QR_Generator] Temp dir: ' . $this->temp_dir);
            error_log('[WP_QR_Generator] File path: ' . $filepath);

            if (!file_exists($this->temp_dir)) {
                $created = wp_mkdir_p($this->temp_dir);
                error_log('[WP_QR_Generator] Created temp dir: ' . ($created ? 'yes' : 'no'));
            }

            // Use chillerlan/php-qrcode
            if (class_exists('chillerlan\\QRCode\\QRCode')) {
                error_log('[WP_QR_Generator] chillerlan\\QRCode\\QRCode class exists');
                
                // Map the string quality to the library's integer constants
                $ecc_level_char = $this->quality ?: 'H';
                $ecc_level_map = [
                    'L' => \chillerlan\QRCode\QRCode::ECC_L,
                    'M' => \chillerlan\QRCode\QRCode::ECC_M,
                    'Q' => \chillerlan\QRCode\QRCode::ECC_Q,
                    'H' => \chillerlan\QRCode\QRCode::ECC_H,
                ];
                $ecc = $ecc_level_map[$ecc_level_char] ?? \chillerlan\QRCode\QRCode::ECC_H;

                $size = intval($options['size'] ?? 300);
                $qropts = new \chillerlan\QRCode\QROptions([
                    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
                    'eccLevel' => $ecc,
                    'scale' => max(1, intval($size / 40)), // scale factor
                    'imageBase64' => false,
                ]);
                $qr = new \chillerlan\QRCode\QRCode($qropts);
                $imageData = $qr->render($data);
                $write_result = file_put_contents($filepath, $imageData);
                error_log('[WP_QR_Generator] file_put_contents result: ' . var_export($write_result, true));
                if ($write_result !== false) {
                    $qr_id = $this->save_to_database($data, $product_id, $filepath, $options);
                    return [
                        'success' => true,
                        'qr_id' => $qr_id,
                        'file_path' => $filepath,
                        'file_url' => str_replace(WP_QR_GENERATOR_UPLOADS_DIR, WP_QR_GENERATOR_UPLOADS_URL, $filepath),
                        'fallback_js' => false,
                        'data' => $data,
                        'options' => $options,
                    ];
                } else {
                    error_log('[WP_QR_Generator] Failed to write QR code image file: ' . $filepath);
                    return [
                        'success' => false,
                        'error' => 'Failed to write QR code image file: ' . $filepath,
                        'fallback_js' => true,
                        'data' => $data,
                        'options' => $options,
                    ];
                }
            } else {
                error_log('[WP_QR_Generator] chillerlan/php-qrcode library not found');
                return [
                    'success' => false,
                    'error' => 'chillerlan/php-qrcode library not found.',
                    'fallback_js' => true,
                    'data' => $data,
                    'options' => $options,
                ];
            }
        } catch (Exception $e) {
            error_log('[WP_QR_Generator] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_js' => true,
                'data' => $data,
                'options' => $options,
            ];
        }
    }

    /**
     * Save QR code to database
     *
     * @param string $data QR code data
     * @param int $product_id Product ID
     * @param string $filepath File path
     * @param array $options Options
     * @return int|false QR code ID on success, false on failure
     */
    private function save_to_database($data, $product_id, $filepath, $options) {
        global $wpdb;
        
        $file_url = str_replace(WP_QR_GENERATOR_UPLOADS_DIR, WP_QR_GENERATOR_UPLOADS_URL, $filepath);
        
        error_log('[WP_QR_Generator] Saving to database: product_id=' . $product_id . ', data=' . substr($data, 0, 50) . '...');
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'qr_codes',
            array(
                'product_id' => $product_id ?: null,
                'qr_code_data' => $data,
                'file_path' => $filepath,
                'file_url' => $file_url,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $qr_id = $wpdb->insert_id;
            error_log('[WP_QR_Generator] QR code saved with ID: ' . $qr_id);
            return $qr_id;
        } else {
            error_log('[WP_QR_Generator] Database insert failed: ' . $wpdb->last_error);
            return false;
        }
    }

    /**
     * Create placeholder QR code image
     *
     * @param string $data QR code data
     * @param string $filepath File path
     * @param int $size Image size
     * @return string|false File path on success, false on failure
     */
    private function create_placeholder_qr($data, $filepath, $size) {
        if (!extension_loaded('gd')) {
            return false;
        }

        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 128, 128, 128);
        
        // Fill with white background
        imagefill($image, 0, 0, $white);
        
        // Create a simple pattern based on data hash
        $hash = md5($data);
        $pattern_size = max(10, intval($size / 20));
        
        for ($x = 0; $x < $size; $x += $pattern_size) {
            for ($y = 0; $y < $size; $y += $pattern_size) {
                $index = intval(($x + $y) / $pattern_size) % strlen($hash);
                $char = $hash[$index];
                
                if (hexdec($char) % 2 == 0) {
                    imagefilledrectangle($image, $x, $y, $x + $pattern_size - 1, $y + $pattern_size - 1, $black);
                }
            }
        }
        
        // Add corner squares (QR code style)
        $corner_size = intval($size / 7);
        $this->draw_corner_square($image, $black, $white, 0, 0, $corner_size);
        $this->draw_corner_square($image, $black, $white, $size - $corner_size, 0, $corner_size);
        $this->draw_corner_square($image, $black, $white, 0, $size - $corner_size, $corner_size);
        
        // Add border
        imagerectangle($image, 0, 0, $size-1, $size-1, $black);
        
        // Save image
        $result = imagepng($image, $filepath);
        imagedestroy($image);
        
        return $result ? $filepath : false;
    }

    /**
     * Draw corner square for QR code
     */
    private function draw_corner_square($image, $black, $white, $x, $y, $size) {
        // Outer square
        imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $black);
        
        // Inner white square
        $margin = intval($size / 7);
        imagefilledrectangle($image, $x + $margin, $y + $margin, $x + $size - $margin, $y + $size - $margin, $white);
        
        // Center black square
        $center_margin = intval($size / 3);
        imagefilledrectangle($image, $x + $center_margin, $y + $center_margin, $x + $size - $center_margin, $y + $size - $center_margin, $black);
    }

    /**
     * Generate QR code image without saving to database
     *
     * @param string $data QR code data
     * @param array $options Additional options
     * @return array|false Result array on success, false on failure
     */
    private function generate_qr_image($data, $options = array()) {
        try {
            $data = WP_QR_Generator_Security::validate_input($data);
            $filename = 'qr_' . md5($data . time()) . '.png';
            $filepath = $this->temp_dir . $filename;

            if (!file_exists($this->temp_dir)) {
                wp_mkdir_p($this->temp_dir);
            }

            // Use chillerlan/php-qrcode
            if (class_exists('chillerlan\\QRCode\\QRCode')) {
                // Map the string quality to the library's integer constants
                $ecc_level_char = $this->quality ?: 'H';
                $ecc_level_map = [
                    'L' => \chillerlan\QRCode\QRCode::ECC_L,
                    'M' => \chillerlan\QRCode\QRCode::ECC_M,
                    'Q' => \chillerlan\QRCode\QRCode::ECC_Q,
                    'H' => \chillerlan\QRCode\QRCode::ECC_H,
                ];
                $ecc = $ecc_level_map[$ecc_level_char] ?? \chillerlan\QRCode\QRCode::ECC_H;

                $size = intval($options['size'] ?? 300);
                $qropts = new \chillerlan\QRCode\QROptions([
                    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
                    'eccLevel' => $ecc,
                    'scale' => max(1, intval($size / 40)),
                    'imageBase64' => false,
                ]);
                $qr = new \chillerlan\QRCode\QRCode($qropts);
                $imageData = $qr->render($data);
                $write_result = file_put_contents($filepath, $imageData);
                
                if ($write_result !== false) {
                    return [
                        'success' => true,
                        'file_path' => $filepath,
                        'file_url' => str_replace(WP_QR_GENERATOR_UPLOADS_DIR, WP_QR_GENERATOR_UPLOADS_URL, $filepath),
                        'data' => $data,
                    ];
                } else {
                    error_log('[WP_QR_Generator] Failed to write QR code image file: ' . $filepath);
                    return [
                        'success' => false,
                        'error' => 'Failed to write QR code image file: ' . $filepath,
                    ];
                }
            } else {
                error_log('[WP_QR_Generator] chillerlan/php-qrcode library not found');
                return [
                    'success' => false,
                    'error' => 'chillerlan/php-qrcode library not found.',
                ];
            }
        } catch (Exception $e) {
            error_log('[WP_QR_Generator] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate QR code for WooCommerce product
     *
     * @param int $product_id Product ID
     * @param array $options Generation options
     * @return array|false QR code data on success, false on failure
     */
    public function generate_product_qr_code($product_id, $options = array()) {
        if (!$product_id) {
            return array('success' => false, 'error' => 'No product selected.');
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('[WP_QR_Generator] Failed to retrieve product for ID: ' . $product_id);
            return array('success' => false, 'error' => 'Could not find product with ID: ' . $product_id);
        }

        $options = get_option('wp_qr_generator_settings', []);
        $product_url = $product->get_permalink();

        if (!$product_url || is_wp_error($product_url)) {
            $error_message = 'Could not retrieve permalink for product ID: ' . $product_id;
            if (is_wp_error($product_url)) {
                $error_message .= ' - ' . $product_url->get_error_message();
            }
            error_log('[WP_QR_Generator] ' . $error_message);
            return array('success' => false, 'error' => $error_message);
        }

        // First, save a temporary QR code to get the ID
        global $wpdb;
        $temp_result = $wpdb->insert(
            $wpdb->prefix . 'qr_codes',
            array(
                'product_id' => $product_id,
                'qr_code_data' => $product_url, // temporary, will be updated
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );

        if (!$temp_result) {
            error_log('[WP_QR_Generator] Failed to create QR code record: ' . $wpdb->last_error);
            return array('success' => false, 'error' => 'Failed to create QR code record');
        }

        $qr_id = $wpdb->insert_id;
        error_log('[WP_QR_Generator] Created QR code record with ID: ' . $qr_id);

        // Now generate the tracking URL with the correct QR ID
        $enable_tracking = $options['enable_tracking'] ?? true;
        if ($enable_tracking) {
            $tracking_params = array(
                'qr_source' => 'product',
                'qr_id' => $qr_id, // Use the actual QR code ID
                'qr_timestamp' => time()
            );
            $product_url = add_query_arg($tracking_params, $product_url);
            error_log('[WP_QR_Generator] Final tracking URL: ' . $product_url);
        }

        // Generate the QR code image
        $image_result = $this->generate_qr_image($product_url, $options);
        
        if ($image_result && $image_result['success']) {
            // Update the database record with the final data
            $update_result = $wpdb->update(
                $wpdb->prefix . 'qr_codes',
                array(
                    'qr_code_data' => $product_url,
                    'file_path' => $image_result['file_path'],
                    'file_url' => $image_result['file_url']
                ),
                array('id' => $qr_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            if ($update_result !== false) {
                error_log('[WP_QR_Generator] Updated QR code record successfully');
                
                // Return the complete result
                $result = array(
                    'success' => true,
                    'qr_id' => $qr_id,
                    'file_path' => $image_result['file_path'],
                    'file_url' => $image_result['file_url'],
                    'data' => $product_url,
                    'options' => $options,
                );
                
                // Add the tracking url to the result so the JS can use it
                if ($enable_tracking) {
                    $result['tracking_url'] = $product_url;
                }
                
                return $result;
            } else {
                error_log('[WP_QR_Generator] Failed to update QR code record: ' . $wpdb->last_error);
                // Clean up the temporary record and file
                $wpdb->delete($wpdb->prefix . 'qr_codes', array('id' => $qr_id), array('%d'));
                if (file_exists($image_result['file_path'])) {
                    unlink($image_result['file_path']);
                }
                return array('success' => false, 'error' => 'Failed to update QR code record');
            }
        } else {
            // Clean up the temporary record if image generation failed
            $wpdb->delete($wpdb->prefix . 'qr_codes', array('id' => $qr_id), array('%d'));
            return $image_result; // Return the error from image generation
        }
    }

    /**
     * Get QR code by ID
     *
     * @param int $qr_id QR code ID
     * @return object|false QR code data or false
     */
    public function get_qr_code($qr_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}qr_codes WHERE id = %d",
            $qr_id
        ));
    }

    /**
     * Delete QR code
     *
     * @param int $qr_id QR code ID
     * @return bool Success status
     */
    public function delete_qr_code($qr_id) {
        global $wpdb;
        
        // Get QR code data
        $qr_code = $this->get_qr_code($qr_id);
        if (!$qr_code) {
            return false;
        }
        
        // Delete file
        if (file_exists($qr_code->file_path)) {
            unlink($qr_code->file_path);
        }
        
        // Delete from database
        $result = $wpdb->delete(
            $wpdb->prefix . 'qr_codes',
            array('id' => $qr_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax_requests() {
        if (!isset($_POST['action'])) {
            wp_send_json_error(['message' => 'No action specified']);
            return;
        }

        $action = $_POST['action'];
        $options = $_POST['options'] ?? [];

        switch ($action) {
            case 'product':
                $product_id = intval($_POST['product_id']);
                if ($product_id > 0) {
                    $result = $this->generate_product_qr_code($product_id);
                } else {
                    $result = ['success' => false, 'error' => 'Invalid product ID.'];
                }
                break;
            case 'url':
                $url = esc_url_raw($_POST['url']);
                if (!empty($url)) {
                    $result = $this->generate_and_save_qr_code($url, 'custom_url', $options);
                } else {
                    $result = ['success' => false, 'error' => 'URL is empty.'];
                }
                break;
            default:
                $result = ['success' => false, 'error' => 'Unknown action'];
        }

        if (isset($result['success']) && $result['success']) {
            // Ensure the URL used for the QR code is in the response
            $response_data = [
                'file_url' => $result['file_url'],
                'url' => $result['data']
            ];
            wp_send_json_success($response_data);
        } else {
            $error_message = $result['error'] ?? 'An unknown error occurred.';
            error_log('[WP_QR_Generator] AJAX Error: ' . $error_message);
            wp_send_json_error(['message' => $error_message]);
        }
    }
} 