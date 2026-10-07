<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Reports';
$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$sales=(float)$pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status!='cancelled'")->execute([$from,$to])?0:0;
$st=$pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status!='cancelled'");$st->execute([$from,$to]);$sales=(float)$st->fetchColumn();
$st=$pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");$st->execute([$from,$to]);$orders=(int)$st->fetchColumn();
$topProducts=$pdo->query("SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.total_price) as revenue FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN orders o ON o.id=oi.order_id WHERE o.status!='cancelled' GROUP BY p.id ORDER BY revenue DESC LIMIT 10")->fetchAll();
$topVendors=$pdo->query("SELECT v.business_name, SUM(oi.total_price) as revenue FROM order_items oi JOIN vendors v ON v.id=oi.vendor_id JOIN orders o ON o.id=oi.order_id WHERE o.status!='cancelled' GROUP BY v.id ORDER BY revenue DESC LIMIT 10")->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Reports</h2>
<form class="row g-2 mb-4">
  <div class="col-auto"><input type="date" name="from" class="form-control form-control-sm" value="<?=e($from)?>"></div>
  <div class="col-auto"><input type="date" name="to" class="form-control form-control-sm" value="<?=e($to)?>"></div>
  <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
</form>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?=money($sales)?></div><div class="stat-label">Sales (<?=e($from)?> to <?=e($to)?>)</div></div></div>
  <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?=$orders?></div><div class="stat-label">Orders</div></div></div>
</div>
<div class="row g-4">
  <div class="col-md-6">
    <div class="table-card"><div class="p-3 border-bottom fw-bold">Top Products</div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead><tbody>
    <?php foreach($topProducts as $p):?><tr><td><?=e($p['name'])?></td><td><?=$p['qty']?></td><td><?=money($p['revenue'])?></td></tr><?php endforeach;?>
    </tbody></table></div></div>
  </div>
  <div class="col-md-6">
    <div class="table-card"><div class="p-3 border-bottom fw-bold">Top Vendors</div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Vendor</th><th>Revenue</th></tr></thead><tbody>
    <?php foreach($topVendors as $v):?><tr><td><?=e($v['business_name'])?></td><td><?=money($v['revenue'])?></td></tr><?php endforeach;?>
    </tbody></table></div></div>
  </div>
</div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
