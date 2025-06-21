<?php
global $wpdb;
$scans = $wpdb->get_results("
    SELECT s.*, p.post_title AS product_name, c.order_id, c.converted_at
    FROM {$wpdb->prefix}qr_scans s
    LEFT JOIN {$wpdb->posts} p ON s.product_id = p.ID
    LEFT JOIN {$wpdb->prefix}qr_conversions c ON s.qr_code_id = c.qr_code_id
    ORDER BY s.scanned_at DESC
    LIMIT 100
");
?>
<div class="wrap">
    <h1><?php _e('QR Code Analytics', 'wp-qr-generator'); ?></h1>
    <table class="widefat">
        <thead>
            <tr>
                <th>Product</th>
                <th>Scanned At</th>
                <th>Order ID</th>
                <th>Purchased At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scans as $scan): ?>
            <tr>
                <td><?php echo esc_html($scan->product_name); ?></td>
                <td><?php echo esc_html($scan->scanned_at); ?></td>
                <td><?php echo esc_html($scan->order_id); ?></td>
                <td><?php echo esc_html($scan->converted_at); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 