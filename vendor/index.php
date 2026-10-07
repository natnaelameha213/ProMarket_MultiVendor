<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Vendor Dashboard';
$vid=get_vendor_id();
$stats=[
  'products'=>(int)db_q("SELECT COUNT(*) FROM products WHERE vendor_id=?",[$vid])->fetchColumn(),
  'orders'=>(int)db_q("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE vendor_id=?",[$vid])->fetchColumn(),
  'pending'=>(int)db_q("SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.vendor_id=? AND o.status='pending'",[$vid])->fetchColumn(),
  'sales'=>(float)db_q("SELECT COALESCE(SUM(oi.total_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.vendor_id=? AND o.status NOT IN ('cancelled')",[$vid])->fetchColumn(),
  'low_stock'=>(int)db_q("SELECT COUNT(*) FROM products WHERE vendor_id=? AND stock<=low_stock_threshold AND status='active'",[$vid])->fetchColumn(),
];
$recent=db_q("SELECT o.order_number,o.status,o.created_at,oi.product_name,oi.quantity,oi.total_price FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.vendor_id=? ORDER BY o.id DESC LIMIT 8",[$vid])->fetchAll();
require __DIR__.'/../includes/vendor_header.php';
?>
<h2 class="fw-bold mb-4">Dashboard</h2>
<div class="row g-3 mb-4">
  <?php
  $cards=[['Products',$stats['products'],'bi-box-seam','primary'],['Orders',$stats['orders'],'bi-bag','success'],['Pending',$stats['pending'],'bi-hourglass','warning'],['Sales',money($stats['sales']),'bi-cash','info'],['Low Stock',$stats['low_stock'],'bi-exclamation-triangle','danger']];
  foreach($cards as $c):?>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card"><div class="stat-value"><?=$c[1]?></div><div class="stat-label"><i class="bi <?=$c[2]?>"></i> <?=$c[0]?></div></div>
  </div>
  <?php endforeach;?>
</div>
<div class="table-card">
  <div class="p-3 border-bottom fw-bold">Recent Orders</div>
  <div class="table-responsive"><table class="table">
    <thead><tr><th>Order</th><th>Product</th><th>Qty</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($recent as $r):?>
    <tr><td><?=e($r['order_number'])?></td><td><?=e($r['product_name'])?></td><td><?=$r['quantity']?></td><td><?=money($r['total_price'])?></td><td><?=status_badge($r['status'])?></td><td class="small"><?=format_date($r['created_at'])?></td></tr>
    <?php endforeach; if(!$recent):?><tr><td colspan="6" class="text-center text-muted py-4">No orders yet</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>
<?php require __DIR__.'/../includes/vendor_footer.php';?>
