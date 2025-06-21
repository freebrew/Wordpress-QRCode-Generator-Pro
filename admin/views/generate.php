<?php
// admin/views/generate.php
// Enhanced QR code generator form with template options

$admin = new WP_QR_Generator_Admin();
$products = $admin->get_woocommerce_products();

// Get WooCommerce categories
$categories = array();
if (function_exists('get_terms')) {
    $product_categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    if (!is_wp_error($product_categories)) {
        $categories = $product_categories;
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Generate QR Code', 'wp-qr-generator'); ?></h1>
    
    <form id="qr-generator-form" style="margin-top: 20px;">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="qr-type"><?php esc_html_e('QR Code Type', 'wp-qr-generator'); ?></label></th>
                <td>
                    <select id="qr-type" name="qr_type">
                        <option value="product" selected><?php esc_html_e('WooCommerce Product', 'wp-qr-generator'); ?></option>
                        <option value="category"><?php esc_html_e('WooCommerce Category', 'wp-qr-generator'); ?></option>
                        <option value="shop"><?php esc_html_e('WooCommerce Store (Shop Page)', 'wp-qr-generator'); ?></option>
                        <option value="custom"><?php esc_html_e('Custom URL / Text', 'wp-qr-generator'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Product Selection -->
            <tr id="row-product-id">
                <th scope="row"><label for="product-id"><?php esc_html_e('Select Product', 'wp-qr-generator'); ?></label></th>
                <td>
                    <?php if (!empty($products)) : ?>
                        <select id="product-id" name="product_id" style="width:300px;">
                            <option value=""><?php esc_html_e('-- Select a Product --', 'wp-qr-generator'); ?></option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo esc_attr($product['id']); ?>"><?php echo esc_html($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <p><?php esc_html_e('No WooCommerce products found.', 'wp-qr-generator'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Category Selection -->
            <tr id="row-category-id" style="display:none;">
                <th scope="row"><label for="category-id"><?php esc_html_e('Select Category', 'wp-qr-generator'); ?></label></th>
                <td>
                    <?php if (!empty($categories)) : ?>
                        <select id="category-id" name="category_id" style="width:300px;">
                            <option value=""><?php esc_html_e('-- Select a Category --', 'wp-qr-generator'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?> (<?php echo $category->count; ?> products)</option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <p><?php esc_html_e('No WooCommerce categories found.', 'wp-qr-generator'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Custom Data -->
            <tr id="row-custom-data" style="display:none;">
                <th scope="row"><label for="custom-data"><?php esc_html_e('Custom Data', 'wp-qr-generator'); ?></label></th>
                <td><input type="text" id="custom-data" name="custom_data" class="regular-text" value="https://example.com"></td>
            </tr>
        </table>
        
        <!-- Template Options -->
        <h3><?php esc_html_e('Template Options', 'wp-qr-generator'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Include Elements', 'wp-qr-generator'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" id="include-header" name="include_header" value="1" checked>
                            <?php esc_html_e('Site Header', 'wp-qr-generator'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" id="include-footer" name="include_footer" value="1" checked>
                            <?php esc_html_e('Site Footer', 'wp-qr-generator'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" id="include-navigation" name="include_navigation" value="1">
                            <?php esc_html_e('Navigation Menu', 'wp-qr-generator'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" id="include-sidebar" name="include_sidebar" value="1">
                            <?php esc_html_e('Sidebar (if applicable)', 'wp-qr-generator'); ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e('Select which site elements to include with the QR code content.', 'wp-qr-generator'); ?></p>
                </td>
            </tr>
            

            
            <tr>
                <th scope="row"><?php esc_html_e('QR Code Size', 'wp-qr-generator'); ?></th>
                <td>
                    <select id="qr-size" name="qr_size">
                        <option value="150"><?php esc_html_e('Small (150x150)', 'wp-qr-generator'); ?></option>
                        <option value="200"><?php esc_html_e('Medium (200x200)', 'wp-qr-generator'); ?></option>
                        <option value="300" selected><?php esc_html_e('Large (300x300)', 'wp-qr-generator'); ?></option>
                        <option value="400"><?php esc_html_e('Extra Large (400x400)', 'wp-qr-generator'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Generate QR Code Template', 'wp-qr-generator'); ?></button>
        </p>
    </form>
    
    <!-- UI Feedback and Result Containers -->
    <div id="qr-feedback" style="margin-top:20px;display:none;padding:10px;border:1px solid #c3c4c7;"></div>
    <div id="qr-result" style="margin-top:20px;text-align:center;"></div>
    <div id="qr-actions" style="margin-top:10px;text-align:center;display:none;">
        <a href="#" id="qr-download-link" class="button button-primary" target="_blank">📄 <?php esc_html_e('Download PDF', 'wp-qr-generator'); ?></a>
        <a href="#" id="qr-test-link" class="button" target="_blank">🔗 <?php esc_html_e('Test QR Code Link', 'wp-qr-generator'); ?></a>
        <a href="#" id="qr-preview-link" class="button" target="_blank">👁️ <?php esc_html_e('Preview Template', 'wp-qr-generator'); ?></a>
    </div>
</div> 