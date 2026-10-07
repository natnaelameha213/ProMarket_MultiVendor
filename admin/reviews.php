<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Reviews';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['approve'])){$pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([(int)$_POST['approve']]);flash('success','Approved');redirect(BASE_URL.'/admin/reviews.php');}
  if(isset($_POST['reject'])){$pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([(int)$_POST['reject']]);flash('success','Rejected');redirect(BASE_URL.'/admin/reviews.php');}
  if(isset($_POST['delete'])){$pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([(int)$_POST['delete']]);flash('success','Deleted');redirect(BASE_URL.'/admin/reviews.php');}
}
$reviews=$pdo->query("SELECT r.*, p.name as product_name, cu.full_name FROM reviews r JOIN products p ON p.id=r.product_id JOIN customers cu ON cu.id=r.customer_id ORDER BY r.id DESC")->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Reviews</h2>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($reviews as $r):?>
<tr>
  <td><?=e($r['product_name'])?></td><td><?=e($r['full_name'])?></td><td><?=stars((float)$r['rating'])?></td>
  <td class="small"><?=e(mb_substr($r['comment']??'',0,80))?></td><td><?=status_badge($r['status'])?></td>
  <td class="d-flex gap-1">
    <?php if($r['status']!=='approved'):?><form method="post"><?=csrf_field()?><button name="approve" value="<?=$r['id']?>" class="btn btn-sm btn-success">Approve</button></form><?php endif;?>
    <form method="post"><?=csrf_field()?><button name="reject" value="<?=$r['id']?>" class="btn btn-sm btn-warning">Reject</button></form>
    <form method="post" onsubmit="return confirm('Delete?')"><?=csrf_field()?><button name="delete" value="<?=$r['id']?>" class="btn btn-sm btn-danger">Del</button></form>
  </td>
</tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
