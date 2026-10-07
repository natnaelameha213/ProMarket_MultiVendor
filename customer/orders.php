<?php
require_once __DIR__.'/../config/config.php';
require_role('customer');
$pageTitle='My Orders';
$cid=get_customer_id();
$orders=$pdo->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC");
$orders->execute([$cid]);$orders=$orders->fetchAll();
require __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
  <h2 class="fw-bold mb-4">My Orders</h2>
  <div class="table-card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Order #</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach($orders as $o):?>
    <tr>
      <td class="fw-semibold"><?=e($o['order_number'])?></td>
      <td><?=money($o['total'])?></td>
      <td><?=status_badge($o['payment_status'])?></td>
      <td><?=status_badge($o['status'])?></td>
      <td class="small"><?=format_date($o['created_at'],'M d, Y H:i')?></td>
      <td><a href="<?=BASE_URL?>/customer/order-view.php?id=<?=$o['id']?>" class="btn btn-sm btn-outline-primary">Details</a></td>
    </tr>
    <?php endforeach; if(!$orders):?><tr><td colspan="6" class="text-center text-muted py-4">No orders yet. <a href="<?=BASE_URL?>/shop.php">Start shopping</a></td></tr><?php endif;?>
    </tbody>
  </table></div></div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
