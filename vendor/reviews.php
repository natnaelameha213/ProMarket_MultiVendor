<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Reviews';
$vid=get_vendor_id();
$reviews=db_q("SELECT r.*, p.name as product_name, cu.full_name FROM reviews r JOIN products p ON p.id=r.product_id JOIN customers cu ON cu.id=r.customer_id WHERE p.vendor_id=? AND r.status='approved' ORDER BY r.id DESC",[$vid])->fetchAll();
require __DIR__.'/../includes/vendor_header.php';
?>
<h2 class="fw-bold mb-4">Product Reviews</h2>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
<tbody>
<?php foreach($reviews as $r):?>
<tr><td><?=e($r['product_name'])?></td><td><?=e($r['full_name'])?></td><td><?=stars((float)$r['rating'])?></td><td class="small"><?=e($r['comment'])?></td><td class="small"><?=format_date($r['created_at'])?></td></tr>
<?php endforeach; if(!$reviews):?><tr><td colspan="5" class="text-center text-muted py-4">No reviews yet</td></tr><?php endif;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/vendor_footer.php';?>
