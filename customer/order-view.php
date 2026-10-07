<?php
require_once __DIR__.'/../config/config.php';
require_role('customer');
$cid=get_customer_id();
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM orders WHERE id=? AND customer_id=?");$st->execute([$id,$cid]);$order=$st->fetch();
if(!$order){flash('danger','Order not found');redirect(BASE_URL.'/customer/orders.php');}
$items=$pdo->prepare("SELECT * FROM order_items WHERE order_id=?");$items->execute([$id]);$items=$items->fetchAll();
$pageTitle='Order '.$order['order_number'];
require __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0">Order <?=e($order['order_number'])?></h2>
    <div>
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print Invoice</button>
      <a href="<?=BASE_URL?>/customer/orders.php" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="table-card mb-3">
        <div class="p-3 border-bottom fw-bold">Items</div>
        <div class="table-responsive"><table class="table">
          <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach($items as $it):?>
          <tr><td><?=e($it['product_name'])?></td><td><?=$it['quantity']?></td><td><?=money($it['unit_price'])?></td><td><?=money($it['total_price'])?></td></tr>
          <?php endforeach;?>
          </tbody>
        </table></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="form-card">
        <h6 class="fw-bold">Order Summary</h6>
        <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><span><?=money($order['subtotal'])?></span></div>
        <div class="d-flex justify-content-between small mb-1"><span>Discount</span><span>-<?=money($order['discount_amount'])?></span></div>
        <div class="d-flex justify-content-between small mb-1"><span>Shipping</span><span><?=money($order['shipping_amount'])?></span></div>
        <div class="d-flex justify-content-between fw-bold mb-2"><span>Total</span><span><?=money($order['total'])?></span></div>
        <hr>
        <p class="small mb-1">Status: <?=status_badge($order['status'])?></p>
        <p class="small mb-1">Payment: <?=e($order['payment_method'])?> · <?=status_badge($order['payment_status'])?></p>
        <p class="small mb-1">Date: <?=format_date($order['created_at'],'M d, Y H:i')?></p>
        <p class="small mb-0">Ship to: <?=e($order['shipping_address'])?>, <?=e($order['shipping_city'])?></p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
