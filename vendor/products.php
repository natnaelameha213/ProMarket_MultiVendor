<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='My Products';
$vid=get_vendor_id();
if(isset($_GET['delete'])&&verify_csrf()){
  // simple delete via GET with token would need CSRF in query; use POST instead - skip for brevity
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['delete'])&&verify_csrf()){
  $pid=(int)$_POST['delete'];
  $pdo->prepare("DELETE FROM products WHERE id=? AND vendor_id=?")->execute([$pid,$vid]);
  flash('success','Product deleted');
  redirect(BASE_URL.'/vendor/products.php');
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['toggle'])&&verify_csrf()){
  $pid=(int)$_POST['toggle'];
  $pdo->prepare("UPDATE products SET status=IF(status='active','inactive','active') WHERE id=? AND vendor_id=?")->execute([$pid,$vid]);
  flash('success','Status updated');
  redirect(BASE_URL.'/vendor/products.php');
}
$products=$pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.vendor_id=? ORDER BY p.id DESC");
$products->execute([$vid]);$products=$products->fetchAll();
require __DIR__.'/../includes/vendor_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">My Products</h2>
  <a href="<?=BASE_URL?>/vendor/product-form.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add Product</a>
</div>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($products as $p):?>
<tr>
  <td class="fw-semibold"><?=e($p['name'])?></td>
  <td class="small"><?=e($p['sku'])?></td>
  <td><?=e($p['category_name'])?></td>
  <td><?=money(current_price($p))?></td>
  <td><?=$p['stock']?><?php if($p['stock']<=$p['low_stock_threshold']):?> <span class="badge bg-warning">Low</span><?php endif;?></td>
  <td><?=status_badge($p['status'])?></td>
  <td class="d-flex gap-1">
    <a href="<?=BASE_URL?>/vendor/product-form.php?id=<?=$p['id']?>" class="btn btn-sm btn-outline-primary">Edit</a>
    <form method="post" class="d-inline"><?=csrf_field()?><button name="toggle" value="<?=$p['id']?>" class="btn btn-sm btn-outline-secondary"><?=$p['status']==='active'?'Deactivate':'Activate'?></button></form>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?')"><?=csrf_field()?><button name="delete" value="<?=$p['id']?>" class="btn btn-sm btn-outline-danger">Delete</button></form>
  </td>
</tr>
<?php endforeach; if(!$products):?><tr><td colspan="7" class="text-center text-muted py-4">No products yet. <a href="<?=BASE_URL?>/vendor/product-form.php">Add your first product</a></td></tr><?php endif;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/vendor_footer.php';?>
