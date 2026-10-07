<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Customers';
$customers=$pdo->query("SELECT c.*, u.email, u.status as user_status, u.created_at, (SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) as order_count FROM customers c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC")->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Customers</h2>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Status</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach($customers as $c):?>
<tr><td class="fw-semibold"><?=e($c['full_name'])?></td><td><?=e($c['email'])?></td><td><?=e($c['phone'])?></td><td><?=$c['order_count']?></td><td><?=status_badge($c['user_status'])?></td><td class="small"><?=format_date($c['created_at'])?></td></tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
