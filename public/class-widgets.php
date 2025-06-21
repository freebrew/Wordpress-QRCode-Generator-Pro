<?php
/**
 * Widget functionality
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * QR Code Widget Class
 */
class WP_QR_Generator_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'wp_qr_generator_widget',
            __('QR Code Generator', 'wp-qr-generator'),
            array(
                'description' => __('Display QR codes for products or custom content', 'wp-qr-generator')
            )
        );
    }

    /**
     * Widget output
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $qr_type = $instance['qr_type'] ?? 'current_page';
        $size = intval($instance['size'] ?? 200);
        $show_download = isset($instance['show_download']) ? (bool)$instance['show_download'] : false;

        $qr_data = '';
        $unique_id = 'qr-widget-' . uniqid();

        switch ($qr_type) {
            case 'current_page':
                $qr_data = get_permalink();
                break;
            case 'current_product':
                if (is_product()) {
                    $product_id = get_the_ID();
                    $tracking_params = array(
                        'qr_source' => 'widget',
                        'qr_id' => $product_id,
                        'qr_timestamp' => time()
                    );
                    $qr_data = add_query_arg($tracking_params, get_permalink($product_id));
                } else {
                    $qr_data = home_url();
                }
                break;
            case 'custom':
                $qr_data = $instance['custom_data'] ?? '';
                break;
            case 'home':
                $qr_data = home_url();
                break;
        }

        if ($qr_data) {
            echo '<div class="qr-widget-container">';
            echo '<div class="qr-code-container" id="' . esc_attr($unique_id) . '" ';
            echo 'data-qr-data="' . esc_attr($qr_data) . '" ';
            echo 'data-qr-size="' . esc_attr($size) . '">';
            echo '</div>';

            if ($show_download) {
                echo '<div class="qr-widget-actions">';
                echo '<button type="button" class="qr-download-btn" onclick="wpQrGeneratorPublic.downloadQRCode.call(this)">';
                echo __('Download QR Code', 'wp-qr-generator');
                echo '</button>';
                echo '</div>';
            }

            echo '</div>';
        }

        echo $args['after_widget'];
    }

    /**
     * Widget settings form
     */
    public function form($instance) {
        $title = $instance['title'] ?? '';
        $qr_type = $instance['qr_type'] ?? 'current_page';
        $custom_data = $instance['custom_data'] ?? '';
        $size = $instance['size'] ?? 200;
        $show_download = isset($instance['show_download']) ? (bool)$instance['show_download'] : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'wp-qr-generator'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('qr_type')); ?>">
                <?php _e('QR Code Type:', 'wp-qr-generator'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('qr_type')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('qr_type')); ?>">
                <option value="current_page" <?php selected($qr_type, 'current_page'); ?>>
                    <?php _e('Current Page', 'wp-qr-generator'); ?>
                </option>
                <option value="current_product" <?php selected($qr_type, 'current_product'); ?>>
                    <?php _e('Current Product (if applicable)', 'wp-qr-generator'); ?>
                </option>
                <option value="home" <?php selected($qr_type, 'home'); ?>>
                    <?php _e('Home Page', 'wp-qr-generator'); ?>
                </option>
                <option value="custom" <?php selected($qr_type, 'custom'); ?>>
                    <?php _e('Custom URL/Text', 'wp-qr-generator'); ?>
                </option>
            </select>
        </p>

        <p class="qr-custom-data" style="<?php echo $qr_type !== 'custom' ? 'display:none;' : ''; ?>">
            <label for="<?php echo esc_attr($this->get_field_id('custom_data')); ?>">
                <?php _e('Custom Data:', 'wp-qr-generator'); ?>
            </label>
            <textarea class="widefat" 
                      id="<?php echo esc_attr($this->get_field_id('custom_data')); ?>" 
                      name="<?php echo esc_attr($this->get_field_name('custom_data')); ?>" 
                      rows="3"><?php echo esc_textarea($custom_data); ?></textarea>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('size')); ?>">
                <?php _e('Size (pixels):', 'wp-qr-generator'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('size')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('size')); ?>" 
                   type="number" 
                   min="100" 
                   max="500" 
                   value="<?php echo esc_attr($size); ?>">
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_download')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_download')); ?>" 
                   value="1" 
                   <?php checked($show_download, true); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_download')); ?>">
                <?php _e('Show download button', 'wp-qr-generator'); ?>
            </label>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($this->get_field_id('qr_type')); ?>').change(function() {
                var customData = $(this).closest('.widget-content').find('.qr-custom-data');
                if ($(this).val() === 'custom') {
                    customData.show();
                } else {
                    customData.hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Update widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['qr_type'] = (!empty($new_instance['qr_type'])) ? sanitize_text_field($new_instance['qr_type']) : 'current_page';
        $instance['custom_data'] = (!empty($new_instance['custom_data'])) ? sanitize_textarea_field($new_instance['custom_data']) : '';
        $instance['size'] = (!empty($new_instance['size'])) ? intval($new_instance['size']) : 200;
        $instance['show_download'] = !empty($new_instance['show_download']);

        return $instance;
    }
}

/**
 * Register widget
 */
function wp_qr_generator_register_widget() {
    register_widget('WP_QR_Generator_Widget');
}
add_action('widgets_init', 'wp_qr_generator_register_widget'); 