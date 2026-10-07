<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Order Details';
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM orders WHERE id=?");$st->execute([$id]);$order=$st->fetch();
if(!$order){flash('danger','Order not found');redirect(BASE_URL.'/admin/orders.php');}
$items=$pdo->prepare("SELECT oi.*, v.business_name FROM order_items oi JOIN vendors v ON v.id=oi.vendor_id WHERE oi.order_id=?");
$items->execute([$id]);$items=$items->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  $newStatus=$_POST['status']??'';
  $allowed=['pending','confirmed','processing','shipped','delivered','cancelled'];
  if(in_array($newStatus,$allowed)){
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus,$id]);
    $pdo->prepare("UPDATE order_items SET status=? WHERE order_id=?")->execute([$newStatus,$id]);
    // notify customer
    $cu=$pdo->prepare("SELECT user_id FROM customers WHERE id=?");$cu->execute([$order['customer_id']]);
    $cuid=$cu->fetchColumn();
    if($cuid) notify((int)$cuid,'order','Order Status Updated','Your order '.$order['order_number'].' is now '.$newStatus, BASE_URL.'/customer/orders.php');
    flash('success','Order status updated');
    redirect(BASE_URL.'/admin/order-view.php?id='.$id);
  }
}
require __DIR__.'/../includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">Order <?=e($order['order_number'])?></h2>
  <a href="<?=BASE_URL?>/admin/orders.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="table-card mb-4">
      <div class="p-3 border-bottom fw-bold">Items</div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Product</th><th>Vendor</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach($items as $it):?>
        <tr><td><?=e($it['product_name'])?><div class="small text-muted"><?=e($it['product_sku'])?></div></td>
        <td><?=e($it['business_name'])?></td><td><?=$it['quantity']?></td><td><?=money($it['unit_price'])?></td><td><?=money($it['total_price'])?></td></tr>
        <?php endforeach;?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="form-card mb-3">
      <h6 class="fw-bold">Customer</h6>
      <p class="mb-1"><?=e($order['customer_name'])?></p>
      <p class="mb-1 small"><?=e($order['customer_email'])?></p>
      <p class="mb-1 small"><?=e($order['customer_phone'])?></p>
      <p class="mb-0 small"><?=e($order['shipping_address'])?>, <?=e($order['shipping_city'])?></p>
    </div>
    <div class="form-card mb-3">
      <h6 class="fw-bold">Summary</h6>
      <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><span><?=money($order['subtotal'])?></span></div>
      <div class="d-flex justify-content-between small mb-1"><span>Discount</span><span>-<?=money($order['discount_amount'])?></span></div>
      <div class="d-flex justify-content-between small mb-1"><span>Shipping</span><span><?=money($order['shipping_amount'])?></span></div>
      <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?=money($order['total'])?></span></div>
      <hr>
      <div class="small">Payment: <?=e($order['payment_method'])?> · <?=status_badge($order['payment_status'])?></div>
      <div class="small mt-1">Status: <?=status_badge($order['status'])?></div>
    </div>
    <div class="form-card">
      <h6 class="fw-bold">Update Status</h6>
      <form method="post"><?=csrf_field()?>
        <select name="status" class="form-select mb-2">
          <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s):?>
          <option value="<?=$s?>" <?=$order['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
          <?php endforeach;?>
        </select>
        <button class="btn btn-primary w-100">Update</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
