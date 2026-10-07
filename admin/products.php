<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Products';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['toggle'])){
    $pdo->prepare("UPDATE products SET status=IF(status='active','inactive','active') WHERE id=?")->execute([(int)$_POST['toggle']]);
    flash('success','Status updated');redirect(BASE_URL.'/admin/products.php');
  }
  if(isset($_POST['delete'])){
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['delete']]);
    flash('success','Product deleted');redirect(BASE_URL.'/admin/products.php');
  }
}
$q=trim($_GET['q']??'');
$where=$q?"WHERE p.name LIKE ? OR p.sku LIKE ?":'';
$params=$q?["%$q%","%$q%"]:[];
$st=$pdo->prepare("SELECT p.*, v.business_name, c.name as cat_name FROM products p JOIN vendors v ON v.id=p.vendor_id JOIN categories c ON c.id=p.category_id $where ORDER BY p.id DESC LIMIT 50");
$st->execute($params);$products=$st->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">Products</h2>
  <form class="d-flex gap-2"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search..." value="<?=e($q)?>"><button class="btn btn-sm btn-primary">Search</button></form>
</div>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Product</th><th>Vendor</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($products as $p):?>
<tr>
  <td class="fw-semibold"><?=e($p['name'])?><div class="small text-muted"><?=e($p['sku'])?></div></td>
  <td><?=e($p['business_name'])?></td>
  <td><?=e($p['cat_name'])?></td>
  <td><?=money(current_price($p))?></td>
  <td><?=$p['stock']?></td>
  <td><?=status_badge($p['status'])?></td>
  <td class="d-flex gap-1">
    <form method="post"><?=csrf_field()?><button name="toggle" value="<?=$p['id']?>" class="btn btn-sm btn-outline-secondary"><?=$p['status']==='active'?'Off':'On'?></button></form>
    <form method="post" onsubmit="return confirm('Delete?')"><?=csrf_field()?><button name="delete" value="<?=$p['id']?>" class="btn btn-sm btn-outline-danger">Del</button></form>
  </td>
</tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
