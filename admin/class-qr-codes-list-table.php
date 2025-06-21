<?php
/**
 * WordPress QR Code Generator Pro - List Table Class
 * 
 * Professional WordPress-style list table for managing QR codes with 
 * bulk actions, filtering, sorting, and pagination functionality.
 * 
 * @package WP_QR_Generator_Pro
 * @subpackage Admin
 * @author Bruno Brottes <contact@brunobrottes.com>
 * @copyright 2024 Bruno Brottes
 * @license CodeCanyon Regular License
 * @version 1.0.0
 * @since 1.0.0
 * 
 * Features:
 * - WordPress-native bulk actions (Enable, Disable, Delete, Regenerate)
 * - Advanced filtering by category and status
 * - Sortable columns for all key metrics
 * - Pagination for large datasets
 * - Row actions on hover (View QR, Delete)
 * - Professional WordPress admin styling
 * - AJAX-powered bulk operations
 * - Security with nonce verification
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Load WordPress List Table class if not available
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * QR Codes List Table Class
 * 
 * Extends WordPress core WP_List_Table to provide professional QR code
 * management interface with all standard WordPress admin functionality.
 * 
 * Table Features:
 * - Checkbox column for bulk selection
 * - Product name with row actions
 * - QR code preview thumbnails
 * - Performance metrics (scans, visitors, conversions)
 * - Revenue tracking with WooCommerce formatting
 * - Status indicators with visual feedback
 * - Creation date with WordPress formatting
 * - Action buttons for quick operations
 * 
 * Bulk Actions:
 * - Enable: Activate selected QR codes
 * - Disable: Deactivate selected QR codes  
 * - Delete: Remove selected QR codes and related data
 * - Regenerate: Create new QR code files for selected items
 * 
 * Filtering Options:
 * - By WooCommerce product category
 * - By QR code status (Active/Inactive)
 * - Combined filters for precise results
 * 
 * @since 1.0.0
 * @extends WP_List_Table
 * @author Bruno Brottes
 */
class WP_QR_Generator_QR_Codes_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'qr_code',
            'plural'   => 'qr_codes',
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns() {
        return array(
            'cb'           => '<input type="checkbox" />',
            'product_name' => __('Product Name', 'wp-qr-generator'),
            'qr_preview'   => __('QR Code', 'wp-qr-generator'),
            'scans'        => __('Scans', 'wp-qr-generator'),
            'visitors'     => __('Unique Visitors', 'wp-qr-generator'),
            'conversions'  => __('Conversions', 'wp-qr-generator'),
            'revenue'      => __('Revenue', 'wp-qr-generator'),
            'conversion_rate' => __('Conv. Rate', 'wp-qr-generator'),
            'status'       => __('Status', 'wp-qr-generator'),
            'created_at'   => __('Created', 'wp-qr-generator'),
            'actions'      => __('Actions', 'wp-qr-generator')
        );
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return array(
            'product_name' => array('product_name', false),
            'scans'        => array('scans', true),
            'conversions'  => array('conversions', true),
            'revenue'      => array('revenue', true),
            'created_at'   => array('created_at', true)
        );
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'enable'    => __('Enable', 'wp-qr-generator'),
            'disable'   => __('Disable', 'wp-qr-generator'),
            'delete'    => __('Delete', 'wp-qr-generator'),
            'regenerate' => __('Regenerate QR Codes', 'wp-qr-generator')
        );
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        global $wpdb;
        
        $action = $this->current_action();
        $qr_ids = isset($_REQUEST['qr_code']) ? $_REQUEST['qr_code'] : array();
        
        if (!$action || empty($qr_ids)) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
            wp_die(__('Security check failed', 'wp-qr-generator'));
        }
        
        $qr_ids = array_map('intval', $qr_ids);
        $qr_ids_placeholder = implode(',', array_fill(0, count($qr_ids), '%d'));
        
        switch ($action) {
            case 'enable':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}qr_codes SET status = 'active' WHERE id IN ($qr_ids_placeholder)",
                    ...$qr_ids
                ));
                break;
                
            case 'disable':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}qr_codes SET status = 'inactive' WHERE id IN ($qr_ids_placeholder)",
                    ...$qr_ids
                ));
                break;
                
            case 'delete':
                // Delete related scans and conversions first
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}qr_conversions WHERE scan_id IN (
                        SELECT id FROM {$wpdb->prefix}qr_scans WHERE qr_code_id IN ($qr_ids_placeholder)
                    )",
                    ...$qr_ids
                ));
                
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}qr_scans WHERE qr_code_id IN ($qr_ids_placeholder)",
                    ...$qr_ids
                ));
                
                // Delete QR codes
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}qr_codes WHERE id IN ($qr_ids_placeholder)",
                    ...$qr_ids
                ));
                break;
                
            case 'regenerate':
                // Regenerate QR codes for selected items
                foreach ($qr_ids as $qr_id) {
                    $this->regenerate_qr_code($qr_id);
                }
                break;
        }
        
        // Redirect to remove query args
        wp_redirect(remove_query_arg(array('action', 'action2', 'qr_code', '_wpnonce')));
        exit;
    }

    /**
     * Column checkbox
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="qr_code[]" value="%s" />', $item['id']);
    }

    /**
     * Column product name
     */
    public function column_product_name($item) {
        $product_name = $item['product_name'] ?: __('Unknown Product', 'wp-qr-generator');
        
        $actions = array(
            'view'   => sprintf('<a href="%s" target="_blank">%s</a>',
                $item['file_url'], __('View QR', 'wp-qr-generator')),
            'delete' => sprintf('<a href="?page=%s&action=%s&qr_code=%s" onclick="return confirm(\'%s\')">%s</a>',
                $_REQUEST['page'], 'delete', $item['id'], 
                __('Are you sure you want to delete this QR code?', 'wp-qr-generator'),
                __('Delete', 'wp-qr-generator'))
        );
        
        return sprintf('%1$s %2$s', $product_name, $this->row_actions($actions));
    }

    /**
     * Column QR preview
     */
    public function column_qr_preview($item) {
        if ($item['file_url']) {
            return sprintf('<img src="%s" style="width: 50px; height: 50px;" alt="QR Code" />', esc_url($item['file_url']));
        }
        return __('No QR Code', 'wp-qr-generator');
    }

    /**
     * Column status
     */
    public function column_status($item) {
        $status = $item['status'] ?: 'active';
        
        if ($status === 'active') {
            return '<span style="color: green;">●</span> ' . __('Active', 'wp-qr-generator');
        } else {
            return '<span style="color: red;">●</span> ' . __('Inactive', 'wp-qr-generator');
        }
    }

    /**
     * Column conversion rate
     */
    public function column_conversion_rate($item) {
        if ($item['scans'] > 0) {
            $rate = ($item['conversions'] / $item['scans']) * 100;
            return sprintf('%.1f%%', $rate);
        }
        return '0%';
    }

    /**
     * Column revenue
     */
    public function column_revenue($item) {
        return wc_price($item['revenue']);
    }

    /**
     * Column created at
     */
    public function column_created_at($item) {
        return date_i18n(get_option('date_format'), strtotime($item['created_at']));
    }

    /**
     * Column actions
     */
    public function column_actions($item) {
        $actions = array();
        
        $actions[] = sprintf('<a href="%s" class="button button-small" target="_blank">%s</a>',
            $item['file_url'], __('Download', 'wp-qr-generator'));
        
        if ($item['product_id']) {
            $product_url = get_permalink($item['product_id']);
            $actions[] = sprintf('<a href="%s" class="button button-small" target="_blank">%s</a>',
                $product_url, __('View Product', 'wp-qr-generator'));
        }
        
        return implode(' ', $actions);
    }

    /**
     * Default column handler
     */
    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }

    /**
     * Prepare items
     */
    public function prepare_items() {
        global $wpdb;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Process bulk actions
        $this->process_bulk_action();
        
        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Get filter values
        $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        // Build query
        $where_clauses = array();
        $where_values = array();
        
        if ($category_filter) {
            $where_clauses[] = "EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr 
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                WHERE tr.object_id = q.product_id 
                AND tt.taxonomy = 'product_cat' 
                AND tt.term_id = %d
            )";
            $where_values[] = intval($category_filter);
        }
        
        if ($status_filter) {
            $where_clauses[] = "q.status = %s";
            $where_values[] = $status_filter;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Sorting
        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'created_at';
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
        
        // Get data
        $query = "
            SELECT 
                q.id,
                q.product_id,
                q.qr_code_data,
                q.file_url,
                q.status,
                q.created_at,
                p.post_title AS product_name,
                COALESCE(s.scans, 0) AS scans,
                COALESCE(s.visitors, 0) AS visitors,
                COALESCE(c.conversions, 0) AS conversions,
                COALESCE(c.revenue, 0) AS revenue
            FROM {$wpdb->prefix}qr_codes q
            LEFT JOIN {$wpdb->posts} p ON q.product_id = p.ID
            LEFT JOIN (
                SELECT qr_code_id, COUNT(*) AS scans, COUNT(DISTINCT ip_address) AS visitors
                FROM {$wpdb->prefix}qr_scans 
                GROUP BY qr_code_id
            ) s ON q.id = s.qr_code_id
            LEFT JOIN (
                SELECT s.qr_code_id, COUNT(c.id) AS conversions, SUM(c.revenue) AS revenue
                FROM {$wpdb->prefix}qr_scans s
                LEFT JOIN {$wpdb->prefix}qr_conversions c ON s.id = c.scan_id
                GROUP BY s.qr_code_id
            ) c ON q.id = c.qr_code_id
            $where_sql
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        
        // Always use wpdb::prepare since we have LIMIT and OFFSET placeholders
        $items = $wpdb->get_results($wpdb->prepare($query, ...$query_values), ARRAY_A);
        
        // Get total count
        $total_query = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}qr_codes q
            $where_sql
        ";
        
        // Only use wpdb::prepare if we have where values
        if (!empty($where_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($total_query, ...$where_values));
        } else {
            $total_items = $wpdb->get_var($total_query);
        }
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        
        $this->items = $items;
    }

    /**
     * Display filters
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        // Get categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));
        
        ?>
        <div class="alignleft actions">
            <select name="category" id="filter-by-category">
                <option value=""><?php _e('All categories', 'wp-qr-generator'); ?></option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected(isset($_GET['category']) ? $_GET['category'] : '', $category->term_id); ?>>
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" id="filter-by-status">
                <option value=""><?php _e('All statuses', 'wp-qr-generator'); ?></option>
                <option value="active" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'active'); ?>><?php _e('Active', 'wp-qr-generator'); ?></option>
                <option value="inactive" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'inactive'); ?>><?php _e('Inactive', 'wp-qr-generator'); ?></option>
            </select>
            
            <?php submit_button(__('Filter', 'wp-qr-generator'), 'secondary', 'filter_action', false); ?>
        </div>
        <?php
    }

    /**
     * Regenerate QR code
     */
    private function regenerate_qr_code($qr_id) {
        global $wpdb;
        
        $qr_code = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}qr_codes WHERE id = %d",
            $qr_id
        ));
        
        if (!$qr_code) {
            return false;
        }
        
        // Regenerate the QR code
        if (!class_exists('WP_QR_Generator_QR_Generator')) {
            require_once WP_QR_GENERATOR_PLUGIN_PATH . 'includes/class-qr-generator.php';
        }
        
        $qr_generator = new WP_QR_Generator_QR_Generator();
        $result = $qr_generator->generate_product_qr_code($qr_code->product_id);
        
        if ($result && $result['success']) {
            // Update the database with new file URL
            $wpdb->update(
                $wpdb->prefix . 'qr_codes',
                array('file_url' => $result['file_url']),
                array('id' => $qr_id),
                array('%s'),
                array('%d')
            );
        }
        
        return $result;
    }
} 