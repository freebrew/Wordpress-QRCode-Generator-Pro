<?php
/**
 * Template Generator Class
 * 
 * Handles the generation of QR codes with integrated site templates
 * including headers, footers, navigation, and content areas.
 *
 * @package WP_QR_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template Generator Class
 */
class WP_QR_Generator_Template_Generator {

    /**
     * Generate QR code with template integration
     */
    public function generate_template_qr_code($qr_data, $target_url, $product_id, $template_options, $qr_type) {
        try {
            // First, generate the basic QR code
            if (!class_exists('WP_QR_Generator_QR_Generator')) {
                require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-qr-generator.php';
            }
            
            $qr_generator = new WP_QR_Generator_QR_Generator();
            
            // Create tracking URL
            $tracking_url = $this->create_tracking_url($target_url, $product_id, $qr_type);
            
            // Generate basic QR code with tracking
            $qr_options = array(
                'size' => $template_options['qr_size'],
                'quality' => 'H'
            );
            
            $qr_result = $qr_generator->generate_and_save_qr_code($tracking_url, $product_id, $qr_options);
            
            if (!$qr_result || !$qr_result['success']) {
                return array('success' => false, 'error' => 'Failed to generate base QR code');
            }
            
                         // Generate the template-enhanced version
             $template_result = $this->create_template_image($qr_result, $target_url, $product_id, $template_options, $qr_type);
             
             // Also generate PDF version
             $pdf_result = $this->create_template_pdf($qr_result, $target_url, $product_id, $template_options, $qr_type);
             
             if ($template_result['success']) {
                 return array(
                     'success' => true,
                     'file_url' => $pdf_result['success'] ? $pdf_result['file_url'] : $template_result['file_url'],
                     'tracking_url' => $tracking_url,
                     'template_url' => isset($template_result['template_url']) ? $template_result['template_url'] : null,
                     'pdf_url' => $pdf_result['success'] ? $pdf_result['file_url'] : null,
                     'qr_id' => $qr_result['qr_id']
                 );
             } else {
                 // Fallback to basic QR code if template generation fails
                 return array(
                     'success' => true,
                     'file_url' => $pdf_result['success'] ? $pdf_result['file_url'] : $qr_result['file_url'],
                     'tracking_url' => $tracking_url,
                     'pdf_url' => $pdf_result['success'] ? $pdf_result['file_url'] : null,
                     'qr_id' => $qr_result['qr_id']
                 );
             }
            
        } catch (Exception $e) {
            error_log('[WP_QR_Generator] Template generation error: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Create tracking URL with proper parameters
     */
    private function create_tracking_url($target_url, $product_id, $qr_type) {
        $tracking_params = array(
            'qr_source' => 'template',
            'qr_type' => $qr_type,
            'qr_timestamp' => time()
        );
        
        if ($product_id > 0) {
            $tracking_params['qr_product'] = $product_id;
        }
        
        return add_query_arg($tracking_params, $target_url);
    }
    
    /**
     * Create template-enhanced image combining QR code with site elements
     */
    private function create_template_image($qr_result, $target_url, $product_id, $template_options, $qr_type) {
        try {
            // Check if we have any template elements to include
            $has_template_elements = $template_options['include_header'] || 
                                   $template_options['include_footer'] || 
                                   $template_options['include_navigation'] || 
                                   $template_options['include_sidebar'];
            
            if (!$has_template_elements) {
                // No template elements, return original QR code
                return array(
                    'success' => true,
                    'file_url' => $qr_result['file_url']
                );
            }
            
            // Generate HTML template
            $template_html = $this->generate_template_html($target_url, $product_id, $template_options, $qr_type, $qr_result);
            
            // Convert HTML to image (requires additional setup)
            $template_image_result = $this->html_to_image($template_html, $qr_result['qr_id']);
            
            if ($template_image_result['success']) {
                return array(
                    'success' => true,
                    'file_url' => $template_image_result['file_url'],
                    'template_url' => $template_image_result['template_url']
                );
            } else {
                // Fallback to composite image approach
                return $this->create_composite_image($qr_result, $template_options);
            }
            
        } catch (Exception $e) {
            error_log('[WP_QR_Generator] Template image creation error: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Generate HTML template with site elements and QR code
     */
    private function generate_template_html($target_url, $product_id, $template_options, $qr_type, $qr_result) {
        ob_start();
        
        // Get site information
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $site_url = home_url();
        
                 // Get content based on QR type
         $content_html = $this->get_content_html($target_url, $product_id, $qr_type, $qr_result, $template_options);
        
        // Start HTML template
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($site_name); ?> - QR Code Template</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #fff;
                    width: 800px;
                    max-width: 800px;
                }
                .template-container {
                    width: 100%;
                    max-width: 800px;
                    margin: 0 auto;
                    background: #fff;
                }
                .site-header {
                    background: #2c3e50;
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                }
                .site-header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .site-header p {
                    margin: 5px 0 0 0;
                    opacity: 0.8;
                    font-size: 14px;
                }
                .navigation {
                    background: #34495e;
                    padding: 10px 20px;
                    text-align: center;
                }
                .navigation a {
                    color: #fff;
                    text-decoration: none;
                    margin: 0 15px;
                    font-size: 14px;
                }
                .main-content {
                    display: flex;
                    min-height: 400px;
                }
                .content-area {
                    flex: 1;
                    padding: 30px;
                    position: relative;
                }
                .sidebar {
                    width: 200px;
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 1px solid #dee2e6;
                }
                .qr-code-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .qr-code-overlay {
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    background: rgba(255,255,255,0.95);
                    padding: 10px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .site-footer {
                    background: #2c3e50;
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                }
                .product-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .qr-code img {
                    max-width: 100%;
                    height: auto;
                }
            </style>
        </head>
        <body>
            <div class="template-container">
                
                <?php if ($template_options['include_header']): ?>
                <header class="site-header">
                    <h1><?php echo esc_html($site_name); ?></h1>
                    <?php if ($site_description): ?>
                        <p><?php echo esc_html($site_description); ?></p>
                    <?php endif; ?>
                </header>
                <?php endif; ?>
                
                <?php if ($template_options['include_navigation']): ?>
                <nav class="navigation">
                    <a href="<?php echo esc_url($site_url); ?>">Home</a>
                    <a href="<?php echo esc_url($site_url . '/shop/'); ?>">Shop</a>
                    <a href="<?php echo esc_url($site_url . '/about/'); ?>">About</a>
                    <a href="<?php echo esc_url($site_url . '/contact/'); ?>">Contact</a>
                </nav>
                <?php endif; ?>
                
                <main class="main-content">
                    <div class="content-area">
                        
                        <?php if ($template_options['qr_position'] === 'overlay'): ?>
                        <div class="qr-code-overlay">
                            <img src="<?php echo esc_url($qr_result['file_url']); ?>" alt="QR Code" width="<?php echo intval($template_options['qr_size'] * 0.6); ?>" />
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($template_options['qr_position'] === 'top'): ?>
                        <div class="qr-code-container">
                            <img src="<?php echo esc_url($qr_result['file_url']); ?>" alt="QR Code" width="<?php echo intval($template_options['qr_size']); ?>" />
                        </div>
                        <?php endif; ?>
                        
                                                 <?php echo $content_html; ?>
                         
                         <?php if ($qr_type !== 'product'): ?>
                         <?php if ($template_options['qr_position'] === 'center'): ?>
                         <div class="qr-code-container">
                             <img src="<?php echo esc_url($qr_result['file_url']); ?>" alt="QR Code" width="<?php echo intval($template_options['qr_size']); ?>" />
                         </div>
                         <?php endif; ?>
                         
                         <?php if ($template_options['qr_position'] === 'bottom'): ?>
                         <div class="qr-code-container">
                             <img src="<?php echo esc_url($qr_result['file_url']); ?>" alt="QR Code" width="<?php echo intval($template_options['qr_size']); ?>" />
                         </div>
                         <?php endif; ?>
                         <?php endif; ?>
                        
                    </div>
                    
                    <?php if ($template_options['include_sidebar']): ?>
                    <aside class="sidebar">
                        <h3>Quick Links</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin: 10px 0;"><a href="<?php echo esc_url($site_url); ?>" style="color: #007cba;">Home</a></li>
                            <li style="margin: 10px 0;"><a href="<?php echo esc_url($site_url . '/shop/'); ?>" style="color: #007cba;">Shop</a></li>
                            <li style="margin: 10px 0;"><a href="<?php echo esc_url($site_url . '/cart/'); ?>" style="color: #007cba;">Cart</a></li>
                            <li style="margin: 10px 0;"><a href="<?php echo esc_url($site_url . '/my-account/'); ?>" style="color: #007cba;">Account</a></li>
                        </ul>
                        
                        <h3>Contact Info</h3>
                        <p style="font-size: 12px; color: #666;">
                            Visit our website:<br>
                            <a href="<?php echo esc_url($site_url); ?>" style="color: #007cba;"><?php echo esc_html($site_name); ?></a>
                        </p>
                    </aside>
                    <?php endif; ?>
                </main>
                
                <?php if ($template_options['include_footer']): ?>
                <footer class="site-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
                    <p>Scan the QR code to visit this page on your mobile device.</p>
                </footer>
                <?php endif; ?>
                
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
         /**
      * Get content HTML based on QR type
      */
     private function get_content_html($target_url, $product_id, $qr_type, $qr_result, $template_options) {
         switch ($qr_type) {
             case 'product':
                 return $this->get_product_content_html($product_id, $qr_result, $template_options);
             case 'category':
                 return $this->get_category_content_html($target_url);
             case 'shop':
                 return $this->get_shop_content_html();
             case 'custom':
                 return $this->get_custom_content_html($target_url);
             default:
                 return '<p>Content not available.</p>';
         }
     }
    
         /**
      * Get product-specific content HTML with 2x2 layout
      */
     private function get_product_content_html($product_id, $qr_result = null, $template_options = null) {
        if (!$product_id || !function_exists('wc_get_product')) {
            return '<p>Product information not available.</p>';
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return '<p>Product not found.</p>';
        }
        
        // Get product image
        $image_id = $product->get_image_id();
        $image_url = '';
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
        }
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('medium');
        }
        
        $html = '<div class="product-layout">';
        $html .= '<h2 style="text-align: center; margin-bottom: 30px;">' . esc_html($product->get_name()) . '</h2>';
        
        // 2x2 Table Layout
        $html .= '<table class="product-table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        
        // Row 1: Product Image | Price & Snippet
        $html .= '<tr>';
        $html .= '<td style="width: 50%; padding: 20px; vertical-align: top; border: 1px solid #ddd;">';
        $html .= '<div class="product-image" style="text-align: center;">';
        $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product->get_name()) . '" style="max-width: 100%; height: auto; max-height: 250px; border-radius: 8px;" />';
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '<td style="width: 50%; padding: 20px; vertical-align: top; border: 1px solid #ddd;">';
        $html .= '<div class="product-price-info">';
        $html .= '<div class="product-price" style="font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 15px;">';
        $html .= $product->get_price_html();
        $html .= '</div>';
        
        if ($product->get_short_description()) {
            $html .= '<div class="product-snippet" style="font-size: 14px; line-height: 1.6; color: #666;">';
            $html .= wp_kses_post($product->get_short_description());
            $html .= '</div>';
        }
        
        if ($product->get_sku()) {
            $html .= '<p style="margin-top: 15px; font-size: 12px; color: #999;"><strong>SKU:</strong> ' . esc_html($product->get_sku()) . '</p>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        
        // Row 2: Product Description | QR Code (this will be handled by positioning)
        $html .= '<tr>';
        $html .= '<td style="width: 50%; padding: 20px; vertical-align: top; border: 1px solid #ddd;">';
        $html .= '<div class="product-description">';
        $html .= '<h3 style="margin-top: 0; color: #2c3e50;">Description</h3>';
        if ($product->get_description()) {
            $html .= '<div style="font-size: 14px; line-height: 1.6;">';
            $html .= wp_kses_post(wp_trim_words($product->get_description(), 50, '...'));
            $html .= '</div>';
        } else {
            $html .= '<p style="color: #666; font-style: italic;">No detailed description available.</p>';
        }
        $html .= '</div>';
        $html .= '</td>';
        
                 $html .= '<td id="qr-code-cell" style="width: 50%; padding: 20px; vertical-align: center; border: 1px solid #ddd; text-align: center;">';
         $html .= '<div class="qr-code-section">';
         $html .= '<h3 style="margin-top: 0; color: #2c3e50;">Scan to Purchase</h3>';
         $html .= '<div id="qr-code-container" style="min-height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column;">';
         
         if ($qr_result && isset($qr_result['file_url'])) {
             $qr_size = $template_options ? intval($template_options['qr_size'] * 0.8) : 240;
             $html .= '<img src="' . esc_url($qr_result['file_url']) . '" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px;" width="' . $qr_size . '" />';
         } else {
             $html .= '<p style="color: #666; font-style: italic; margin: 20px 0;">QR Code will appear here</p>';
         }
         
         $html .= '</div>';
         $html .= '<p style="font-size: 12px; color: #666; margin-top: 10px;"><em>Scan with your mobile device to view and purchase this product.</em></p>';
         $html .= '</div>';
         $html .= '</td>';
        $html .= '</tr>';
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get category content HTML
     */
    private function get_category_content_html($category_url) {
        // Extract category ID from URL if possible
        $category_id = url_to_postid($category_url);
        
        $html = '<div class="category-info">';
        $html .= '<h2>Product Category</h2>';
        $html .= '<p>Browse our selection of products in this category.</p>';
        $html .= '<p><em>Scan the QR code to view this category on your mobile device.</em></p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get shop page content HTML
     */
    private function get_shop_content_html() {
        $html = '<div class="shop-info">';
        $html .= '<h2>Our Store</h2>';
        $html .= '<p>Welcome to our online store! Discover our full range of products.</p>';
        $html .= '<p><em>Scan the QR code to visit our store on your mobile device.</em></p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get custom content HTML
     */
    private function get_custom_content_html($custom_url) {
        $html = '<div class="custom-info">';
        $html .= '<h2>Custom Link</h2>';
        $html .= '<p>This QR code links to: <br><code>' . esc_html($custom_url) . '</code></p>';
        $html .= '<p><em>Scan the QR code to visit this link on your mobile device.</em></p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Convert HTML to image (simplified approach)
     */
    private function html_to_image($html, $qr_id) {
        // For now, we'll create a simple text-based approach
        // In a production environment, you might use libraries like:
        // - wkhtmltopdf/wkhtmltoimage
        // - Puppeteer (via Node.js)
        // - PhantomJS
        // - Chrome headless
        
        // Create a simple HTML file that can be viewed
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/qr-codes/';
        
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }
        
        $html_filename = 'qr-template-' . $qr_id . '.html';
        $html_filepath = $qr_dir . $html_filename;
        $html_url = $upload_dir['baseurl'] . '/qr-codes/' . $html_filename;
        
        // Save HTML file
        if (file_put_contents($html_filepath, $html)) {
            return array(
                'success' => true,
                'file_url' => $html_url, // For now, return HTML URL
                'template_url' => $html_url
            );
        }
        
        return array('success' => false, 'error' => 'Failed to save template HTML');
    }
    
         /**
      * Create composite image (fallback approach)
      */
     private function create_composite_image($qr_result, $template_options) {
         // Simple composite approach - just return the original QR code
         // This could be enhanced to overlay text or simple graphics
         
         return array(
             'success' => true,
             'file_url' => $qr_result['file_url']
         );
     }
     
     /**
      * Create template PDF
      */
     private function create_template_pdf($qr_result, $target_url, $product_id, $template_options, $qr_type) {
         try {
             if (!class_exists('WP_QR_Generator_PDF_Generator')) {
                 require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-pdf-generator.php';
             }
             
             $pdf_generator = new WP_QR_Generator_PDF_Generator();
             return $pdf_generator->generate_template_pdf($qr_result, $target_url, $product_id, $template_options, $qr_type);
             
         } catch (Exception $e) {
             error_log('[WP_QR_Generator] PDF creation error: ' . $e->getMessage());
             return array('success' => false, 'error' => $e->getMessage());
         }
     }
 } 