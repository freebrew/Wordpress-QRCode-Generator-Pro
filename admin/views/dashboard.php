<?php
/**
 * Enhanced Dashboard View with Generate Form and WordPress-style QR Codes List
 *
 * @package WP_QR_Generator/Admin/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the list table class
if (!class_exists('WP_QR_Generator_QR_Codes_List_Table')) {
    require_once WP_QR_GENERATOR_PLUGIN_PATH . 'admin/class-qr-codes-list-table.php';
}

// Fetch data using the new methods from the Admin class instance
$summary_stats = $this->get_dashboard_summary_stats();

// Initialize the list table
$qr_codes_table = new WP_QR_Generator_QR_Codes_List_Table();
$qr_codes_table->prepare_items();

// Get available products for the generate form
$products = $this->get_woocommerce_products();

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
    <h1><?php esc_html_e('QR Code Dashboard', 'wp-qr-generator'); ?></h1>

    <div class="notice notice-info">
        <p>
            <?php esc_html_e('Welcome to WordPress QR Code Generator!', 'wp-qr-generator'); ?>
            <a href="<?php echo admin_url('admin.php?page=wp-qr-generator-system'); ?>" style="margin-left: 15px;">
                <?php esc_html_e('Check System Status', 'wp-qr-generator'); ?>
            </a>
        </p>
    </div>

    <!-- Quick Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="qr-stat-card">
            <h3><?php echo number_format($summary_stats['total_scans']); ?></h3>
            <p><?php esc_html_e('Total Scans', 'wp-qr-generator'); ?></p>
        </div>
        <div class="qr-stat-card">
            <h3><?php echo number_format($summary_stats['unique_visitors']); ?></h3>
            <p><?php esc_html_e('Unique Visitors', 'wp-qr-generator'); ?></p>
        </div>
        <div class="qr-stat-card">
            <h3><?php echo number_format($summary_stats['total_conversions']); ?></h3>
            <p><?php esc_html_e('Conversions', 'wp-qr-generator'); ?></p>
        </div>
        <div class="qr-stat-card">
            <h3><?php echo wc_price($summary_stats['total_revenue']); ?></h3>
            <p><?php esc_html_e('Revenue Generated', 'wp-qr-generator'); ?></p>
        </div>
        <div class="qr-stat-card">
            <h3><?php echo $summary_stats['conversion_rate']; ?>%</h3>
            <p><?php esc_html_e('Conversion Rate', 'wp-qr-generator'); ?></p>
        </div>
    </div>

    <!-- Generate QR Code Form (Moved to Top) -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header">
            <h2 class="hndle"><?php esc_html_e('🚀 Generate New QR Code', 'wp-qr-generator'); ?></h2>
        </div>
        <div class="inside">
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
                                <p class="description"><?php echo sprintf(__('Only products without existing QR codes are shown. %d products available.', 'wp-qr-generator'), count($products)); ?></p>
                            <?php else : ?>
                                <p style="color: #d63638;"><?php esc_html_e('All products already have QR codes! Manage existing QR codes below.', 'wp-qr-generator'); ?></p>
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
    </div>

    <!-- QR Codes List Table -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header">
            <h2 class="hndle"><?php esc_html_e('📊 QR Code Performance & Management', 'wp-qr-generator'); ?></h2>
        </div>
        <div class="inside">
            <form method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                <?php
                $qr_codes_table->display();
                ?>
            </form>
        </div>
    </div>
</div>

<style>
.qr-stat-card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.qr-stat-card h3 { margin: 0 0 10px 0; font-size: 28px; font-weight: 600; color: #1d2327; }
.qr-stat-card p { margin: 0; color: #646970; font-size: 14px; }
.qr-conversion-rate.high { color: #00a32a; font-weight: 600; }
.qr-conversion-rate.medium { color: #dba617; font-weight: 600; }
.qr-conversion-rate.low { color: #d63638; }

/* Enhanced styling for the new layout */
.postbox { background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
.postbox-header { border-bottom: 1px solid #c3c4c7; }
.postbox-header .hndle { padding: 12px; font-size: 14px; line-height: 1.4; margin: 0; }
.inside { padding: 12px; }
</style> 