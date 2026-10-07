<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Orders';
$vid=get_vendor_id();
$orders=db_q("SELECT DISTINCT o.*, oi.product_name, oi.quantity, oi.total_price FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE oi.vendor_id=? ORDER BY o.id DESC LIMIT 50",[$vid])->fetchAll();
require __DIR__.'/../includes/vendor_header.php';
?>
<h2 class="fw-bold mb-4">Orders</h2>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Order</th><th>Product</th><th>Qty</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
<tbody>
<?php foreach($orders as $o):?>
<tr><td><?=e($o['order_number'])?></td><td><?=e($o['product_name'])?></td><td><?=$o['quantity']?></td><td><?=money($o['total_price'])?></td><td><?=status_badge($o['status'])?></td><td class="small"><?=format_date($o['created_at'])?></td></tr>
<?php endforeach; if(!$orders):?><tr><td colspan="6" class="text-center text-muted py-4">No orders yet</td></tr><?php endif;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/vendor_footer.php';?>
