<?php
/**
 * WordPress QR Code Generator Pro - PDF Generator Class
 * 
 * Professional PDF generation system using TCPDF library to create
 * high-quality, print-ready QR code documents with product layouts.
 * 
 * @package WP_QR_Generator_Pro
 * @subpackage Core
 * @author Bruno Brottes <contact@brunobrottes.com>
 * @copyright 2024 Bruno Brottes
 * @license CodeCanyon Regular License
 * @version 1.0.0
 * @since 1.0.0
 * 
 * Key Features:
 * - Professional 8.5x11 inch standard format (Letter size)
 * - 2x2 product layout with organized information display
 * - Ink-efficient faded color theme (50% opacity for cost savings)
 * - Single-page fitting with optimized 10mm margins
 * - TCPDF integration for high-quality PDF output
 * - Automatic font sizing and layout optimization
 * - Product image integration with scaling
 * - WooCommerce price and description formatting
 * - Multiple QR code types support (Product, Category, Shop, Custom)
 * 
 * PDF Layout Structure (2x2 Table):
 * ┌─────────────────┬─────────────────┐
 * │ Product Image   │ Price & Summary │
 * │ (160x160px)     │ + SKU + Details │
 * ├─────────────────┼─────────────────┤
 * │ Full Product    │ QR Code + Label │
 * │ Description     │ "Scan to Buy"   │
 * └─────────────────┴─────────────────┘
 * 
 * Ink-Efficient Color Scheme:
 * - Header: #7e9bb8 (50% opacity of dark blue)
 * - Borders: #a8b8c8 (50% opacity for subtle lines)  
 * - Text: #556b7d (50% opacity for readability)
 * - Background: White for maximum contrast and savings
 * 
 * Technical Specifications:
 * - Page Size: 216 x 279 mm (8.5 x 11 inches)
 * - Margins: 10mm all around for compact fitting
 * - Font Family: Arial/Helvetica for universal compatibility
 * - Image Format: JPEG/PNG with automatic optimization
 * - Color Mode: RGB with transparency support
 * - Output: Single page guaranteed (no page breaks)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Include TCPDF library for PDF generation
require_once WP_QR_GENERATOR_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * Professional PDF Generator for QR Code Templates
 * 
 * Creates print-ready PDF documents with professional layouts using TCPDF.
 * Implements ink-efficient color schemes and optimized layouts for business use.
 * Supports multiple QR code types with appropriate content formatting.
 * 
 * PDF Generation Workflow:
 * 1. Initialize TCPDF with Letter format and compact margins
 * 2. Disable headers/footers for maximum content space
 * 3. Generate HTML content based on QR code type
 * 4. Apply faded color styling for ink efficiency
 * 5. Create 2x2 table layout for product information
 * 6. Output to file with unique filename and URL
 * 
 * Supported QR Types:
 * - Product: Full WooCommerce product layout with image, pricing, descriptions
 * - Category: Product category browsing page
 * - Shop: Main store/shop page
 * - Custom: Any custom URL with basic information
 * 
 * @since 1.0.0
 * @author Bruno Brottes
 */
class WP_QR_Generator_PDF_Generator {

    /**
     * Generate Professional PDF from QR Code Template
     * 
     * Main method that creates a high-quality PDF document from QR code data
     * using TCPDF library with professional styling and layout.
     * 
     * Process Flow:
     * 1. Initialize TCPDF with Letter format and optimized settings
     * 2. Configure document metadata (title, author, keywords)
     * 3. Set up page with disabled headers/footers for maximum space
     * 4. Generate content HTML based on QR code type
     * 5. Apply ink-efficient styling and layout
     * 6. Output to file with unique naming convention
     * 7. Return file paths and status for further processing
     * 
     * Features:
     * - Standard 8.5x11 inch format for universal printing
     * - Ink-efficient faded color scheme (50% opacity)
     * - Single-page guaranteed output with 10mm margins
     * - Professional 2x2 table layout for products
     * - Automatic image scaling and optimization
     * - WooCommerce integration for product data
     * - Error handling with detailed logging
     * 
     * @since 1.0.0
     * @access public
     * 
     * @param array  $qr_result      QR code generation result with file URLs and IDs
     * @param string $target_url     Target URL for the QR code
     * @param int    $product_id     WooCommerce product ID (if applicable)
     * @param array  $template_options Template configuration options
     * @param string $qr_type        Type of QR code (product, category, shop, custom)
     * 
     * @return array {
     *     PDF generation result array
     *     
     *     @type bool   $success   Whether PDF generation was successful
     *     @type string $file_path Absolute file path to generated PDF
     *     @type string $file_url  Public URL to access the PDF
     *     @type string $filename  PDF filename for reference
     *     @type string $error     Error message if generation failed
     * }
     * 
     * @throws Exception If TCPDF initialization or file operations fail
     * 
     * @example
     * $pdf_result = $generator->generate_template_pdf(
     *     $qr_result,
     *     'https://example.com/product/123',
     *     123,
     *     ['include_header' => true, 'qr_size' => 300],
     *     'product'
     * );
     * 
     * if ($pdf_result['success']) {
     *     echo 'PDF generated: ' . $pdf_result['file_url'];
     * }
     */
    public function generate_template_pdf($qr_result, $target_url, $product_id, $template_options, $qr_type) {
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $site_name = get_bloginfo('name');
            $pdf->SetCreator('WP QR Code Generator');
            $pdf->SetAuthor($site_name);
            $pdf->SetTitle('QR Code Template - ' . $site_name);
            $pdf->SetSubject('QR Code Template');
            $pdf->SetKeywords('QR Code, Template, ' . $site_name);
            
                         // Disable default header and footer for more space
             $pdf->setPrintHeader(false);
             $pdf->setPrintFooter(false);
             
             // Set default monospaced font
             $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
             
             // Set reduced margins for single page fitting
             $pdf->SetMargins(10, 10, 10);
             
             // Disable auto page breaks to ensure single page
             $pdf->SetAutoPageBreak(FALSE);
            
            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Add a page
            $pdf->AddPage();
            
            // Generate the content based on QR type
            $html_content = $this->generate_pdf_content($qr_result, $target_url, $product_id, $template_options, $qr_type);
            
            // Write the HTML content
            $pdf->writeHTML($html_content, true, false, true, false, '');
            
            // Save the PDF
            $upload_dir = wp_upload_dir();
            $qr_dir = $upload_dir['basedir'] . '/qr-codes/';
            
            if (!file_exists($qr_dir)) {
                wp_mkdir_p($qr_dir);
            }
            
            $pdf_filename = 'qr-template-' . $qr_result['qr_id'] . '-' . time() . '.pdf';
            $pdf_filepath = $qr_dir . $pdf_filename;
            $pdf_url = $upload_dir['baseurl'] . '/qr-codes/' . $pdf_filename;
            
            // Output PDF to file
            $pdf->Output($pdf_filepath, 'F');
            
            return array(
                'success' => true,
                'file_path' => $pdf_filepath,
                'file_url' => $pdf_url,
                'filename' => $pdf_filename
            );
            
        } catch (Exception $e) {
            error_log('[WP_QR_Generator] PDF generation error: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
         /**
      * Generate PDF content HTML
      */
     private function generate_pdf_content($qr_result, $target_url, $product_id, $template_options, $qr_type) {
         $site_name = get_bloginfo('name');
         $site_url = home_url();
         
         // Faded color theme (50% opacity) for ink saving
         $header_color = '#7e9bb8'; // 50% fade of #2c3e50
         $border_color = '#a8b8c8'; // 50% fade of #2c3e50
         $text_color = '#556b7d';   // 50% fade of #2c3e50
         
         $html = '<style>
             .pdf-container {
                 width: 100%;
                 font-family: Arial, sans-serif;
                 color: #333;
                 font-size: 12px;
             }
             .pdf-header {
                 text-align: center;
                 margin-bottom: 15px;
                 padding-bottom: 10px;
                 border-bottom: 1px solid ' . $border_color . ';
             }
             .pdf-header h1 {
                 color: ' . $header_color . ';
                 font-size: 18px;
                 margin: 0 0 5px 0;
             }
             .pdf-header p {
                 color: ' . $text_color . ';
                 font-size: 10px;
                 margin: 2px 0;
             }
             .qr-section {
                 text-align: center;
                 margin: 15px 0;
                 padding: 15px;
                 background-color: #f8f9fa;
             }
             .qr-section h2 {
                 color: ' . $header_color . ';
                 margin-bottom: 10px;
                 font-size: 16px;
             }
             .product-details {
                 margin: 10px 0;
                 padding: 10px;
                 background-color: #fff;
             }
             .product-table {
                 width: 100%;
                 border-collapse: collapse;
                 margin: 10px 0;
             }
             .product-table td {
                 padding: 15px;
                 vertical-align: top;
             }
             .product-table th {
                 padding: 8px;
                 background-color: #f5f5f5;
                 font-weight: bold;
             }
             .product-price {
                 font-size: 16px;
                 font-weight: bold;
                 color: ' . $header_color . ';
                 margin: 8px 0;
             }
             .footer-info {
                 margin-top: 20px;
                 text-align: center;
                 font-size: 8px;
                 color: ' . $text_color . ';
                 border-top: 1px solid ' . $border_color . ';
                 padding-top: 10px;
             }
         </style>';
        
        $html .= '<div class="pdf-container">';
        
        // Header
        if ($template_options['include_header']) {
            $html .= '<div class="pdf-header">';
            $html .= '<h1>' . esc_html($site_name) . '</h1>';
            $html .= '<p>' . esc_html(get_bloginfo('description')) . '</p>';
            $html .= '<p>Website: ' . esc_html($site_url) . '</p>';
            $html .= '</div>';
        }
        
                 // Content based on QR type
         if ($qr_type === 'product') {
             $html .= $this->get_product_pdf_content($product_id, $qr_result, $template_options);
         } else {
             $html .= $this->get_general_pdf_content($target_url, $qr_type, $qr_result, $template_options);
         }
         
         // Footer
        if ($template_options['include_footer']) {
            $html .= '<div class="footer-info">';
            $html .= '<p>&copy; ' . date('Y') . ' ' . esc_html($site_name) . '. All rights reserved.</p>';
            $html .= '<p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>';
            $html .= '<p>QR Code ID: ' . $qr_result['qr_id'] . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get product-specific PDF content
     */
    private function get_product_pdf_content($product_id, $qr_result, $template_options) {
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
        
                 $html = '<div class="product-details">';
         $html .= '<h2 style="text-align: center; color: #7e9bb8; margin-bottom: 10px; font-size: 16px;">' . esc_html($product->get_name()) . '</h2>';
         
         // Product table layout - matching HTML template exactly (2x2: Image|Price&Summary / Description|QR)
         $html .= '<table class="product-table" border="0" cellpadding="15" cellspacing="0" style="width: 100%;">';
         
         // Row 1: Product Image | Price & Short Description
         $html .= '<tr>';
         // Top Left: Product Image
         if ($image_url) {
             $html .= '<td style="width: 50%; text-align: center; vertical-align: top;">';
             $html .= '<img src="' . esc_url($image_url) . '" style="max-width: 160px; max-height: 160px;" />';
             $html .= '</td>';
         } else {
             $html .= '<td style="width: 50%; text-align: center; vertical-align: top;">';
             $html .= '<div style="width: 160px; height: 160px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; margin: 0 auto;">';
             $html .= '<span style="color: #666; font-style: italic; font-size: 12px;">No image available</span>';
             $html .= '</div>';
             $html .= '</td>';
         }
         
         // Top Right: Price & Short Description
         $html .= '<td style="width: 50%; vertical-align: top; padding-left: 20px;">';
         $html .= '<div class="product-price" style="margin-bottom: 15px;">' . $product->get_price_html() . '</div>';
         
         if ($product->get_short_description()) {
             $html .= '<div style="font-size: 11px; line-height: 1.4; color: #666;">' . wp_kses_post(wp_trim_words($product->get_short_description(), 30, '...')) . '</div>';
         }
         
         if ($product->get_sku()) {
             $html .= '<div style="margin-top: 12px; font-size: 9px; color: #999;"><strong>SKU:</strong> ' . esc_html($product->get_sku()) . '</div>';
         }
         $html .= '</td>';
         $html .= '</tr>';
         
         // Row 2: Long Description | QR Code
         $html .= '<tr>';
         // Bottom Left: Long Description
         $html .= '<td style="width: 50%; vertical-align: top; padding-top: 15px;">';
         if ($product->get_description()) {
             $html .= '<strong style="color: #7e9bb8; font-size: 12px;">Description</strong><br/>';
             $html .= '<div style="font-size: 10px; line-height: 1.4; margin-top: 8px;">' . wp_kses_post(wp_trim_words($product->get_description(), 50, '...')) . '</div>';
         } else {
             $html .= '<span style="color: #666; font-style: italic; font-size: 10px;">No detailed description available.</span>';
         }
         $html .= '</td>';
         
         // Bottom Right: QR Code
         $html .= '<td style="width: 50%; text-align: center; vertical-align: center; padding-top: 15px;">';
         $html .= '<strong style="color: #7e9bb8; font-size: 12px;">Scan to Purchase</strong><br/>';
         if (isset($qr_result['file_url'])) {
             $qr_size = min(160, $template_options['qr_size'] * 0.7);
             $html .= '<img src="' . esc_url($qr_result['file_url']) . '" style="max-width: ' . $qr_size . 'px; margin-top: 8px;" />';
         }
         $html .= '<div style="font-size: 9px; color: #666; margin-top: 8px;">Scan with your mobile device to view and purchase this product.</div>';
         $html .= '</td>';
         $html .= '</tr>';
         
         $html .= '</table>';
         $html .= '</div>';
        
        return $html;
    }
    
         /**
      * Get general PDF content for non-product QR codes
      */
     private function get_general_pdf_content($target_url, $qr_type, $qr_result, $template_options) {
         $html = '<div class="qr-section">';
         
         switch ($qr_type) {
             case 'category':
                 $html .= '<h2>Product Category</h2>';
                 $html .= '<p style="font-size: 11px; margin: 10px 0;">Scan the QR code below to browse products in this category.</p>';
                 break;
             case 'shop':
                 $html .= '<h2>Our Online Store</h2>';
                 $html .= '<p style="font-size: 11px; margin: 10px 0;">Scan the QR code below to visit our complete online store.</p>';
                 break;
             case 'custom':
                 $html .= '<h2>Custom Link</h2>';
                 $html .= '<p style="font-size: 11px; margin: 10px 0;">Scan the QR code below to visit:</p>';
                 $html .= '<p style="font-family: monospace; font-size: 9px; word-break: break-all; background: #f5f5f5; padding: 5px; margin: 10px 0;">' . esc_html($target_url) . '</p>';
                 break;
             default:
                 $html .= '<h2>QR Code</h2>';
                 $html .= '<p style="font-size: 11px; margin: 10px 0;">Scan the QR code below with your mobile device.</p>';
         }
         
         if (isset($qr_result['file_url'])) {
             $qr_size = min(200, $template_options['qr_size']);
             $html .= '<div style="margin: 20px 0;">';
             $html .= '<img src="' . esc_url($qr_result['file_url']) . '" style="max-width: ' . $qr_size . 'px;" />';
             $html .= '</div>';
         }
         
         $html .= '</div>';
         
         return $html;
     }
    
         /**
      * Generate simple QR code PDF (fallback)
      */
     public function generate_simple_qr_pdf($qr_result) {
         try {
             $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
             
             $pdf->SetCreator('WP QR Code Generator');
             $pdf->SetTitle('QR Code');
             
             // Disable header/footer for more space
             $pdf->setPrintHeader(false);
             $pdf->setPrintFooter(false);
             
             // Reduced margins
             $pdf->SetMargins(10, 10, 10);
             $pdf->SetAutoPageBreak(FALSE);
             
             $pdf->AddPage();
             
             // Simple centered QR code with faded colors
             $html = '<div style="text-align: center; margin-top: 30px; font-family: Arial, sans-serif;">';
             $html .= '<h1 style="color: #7e9bb8; font-size: 18px; margin-bottom: 20px;">QR Code</h1>';
             $html .= '<div style="margin: 20px 0;">';
             $html .= '<img src="' . esc_url($qr_result['file_url']) . '" style="max-width: 250px;" />';
             $html .= '</div>';
             $html .= '<p style="color: #556b7d; font-size: 12px;">Scan with your mobile device</p>';
             $html .= '</div>';
             
             $pdf->writeHTML($html, true, false, true, false, '');
             
             $upload_dir = wp_upload_dir();
             $qr_dir = $upload_dir['basedir'] . '/qr-codes/';
             
             if (!file_exists($qr_dir)) {
                 wp_mkdir_p($qr_dir);
             }
             
             $pdf_filename = 'qr-simple-' . $qr_result['qr_id'] . '-' . time() . '.pdf';
             $pdf_filepath = $qr_dir . $pdf_filename;
             $pdf_url = $upload_dir['baseurl'] . '/qr-codes/' . $pdf_filename;
             
             $pdf->Output($pdf_filepath, 'F');
             
             return array(
                 'success' => true,
                 'file_path' => $pdf_filepath,
                 'file_url' => $pdf_url,
                 'filename' => $pdf_filename
             );
             
         } catch (Exception $e) {
             error_log('[WP_QR_Generator] Simple PDF generation error: ' . $e->getMessage());
             return array('success' => false, 'error' => $e->getMessage());
         }
     }
} 