<?php
require_once __DIR__.'/../config/config.php';
require_role('customer');
$pageTitle='My Account';
$cid=get_customer_id();
$uid=current_user()['id'];
$orderCount=(int)db_q("SELECT COUNT(*) FROM orders WHERE customer_id=?",[$cid])->fetchColumn();
$wishCount=wishlist_count();
$recent=$pdo->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 5");
$recent->execute([$cid]);$recent=$recent->fetchAll();
require __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
  <h2 class="fw-bold mb-4">My Account</h2>
  <div class="row g-4">
    <div class="col-md-3">
      <div class="list-group shadow-sm rounded-3">
        <a href="<?=BASE_URL?>/customer/dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
        <a href="<?=BASE_URL?>/customer/orders.php" class="list-group-item list-group-item-action">Orders</a>
        <a href="<?=BASE_URL?>/customer/wishlist.php" class="list-group-item list-group-item-action">Wishlist</a>
        <a href="<?=BASE_URL?>/customer/profile.php" class="list-group-item list-group-item-action">Profile</a>
        <a href="<?=BASE_URL?>/auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
      </div>
    </div>
    <div class="col-md-9">
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-value"><?=$orderCount?></div><div class="stat-label">Orders</div></div></div>
        <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-value"><?=$wishCount?></div><div class="stat-label">Wishlist</div></div></div>
        <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-value"><?=cart_count()?></div><div class="stat-label">Cart Items</div></div></div>
      </div>
      <div class="table-card">
        <div class="p-3 border-bottom fw-bold">Recent Orders</div>
        <div class="table-responsive"><table class="table">
          <thead><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach($recent as $o):?>
          <tr><td><?=e($o['order_number'])?></td><td><?=money($o['total'])?></td><td><?=status_badge($o['status'])?></td><td class="small"><?=format_date($o['created_at'])?></td>
          <td><a href="<?=BASE_URL?>/customer/order-view.php?id=<?=$o['id']?>" class="btn btn-sm btn-outline-primary">View</a></td></tr>
          <?php endforeach; if(!$recent):?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
