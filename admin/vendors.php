<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Vendors';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  $id=(int)($_POST['id']??0);
  $action=$_POST['action']??'';
  if($id&&in_array($action,['approved','suspended','rejected','pending'])){
    $extra=$action==='approved'?', approved_at=NOW()':'';
    $pdo->prepare("UPDATE vendors SET status=? $extra WHERE id=?")->execute([$action,$id]);
    flash('success','Vendor status updated');
    redirect(BASE_URL.'/admin/vendors.php');
  }
}
$status=$_GET['status']??'';
$where=$status?"WHERE status=?":'';
$params=$status?[$status]:[];
$st=$pdo->prepare("SELECT v.*, u.email as user_email FROM vendors v JOIN users u ON u.id=v.user_id $where ORDER BY v.id DESC");
$st->execute($params);$vendors=$st->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Vendors</h2>
<div class="mb-3 d-flex flex-wrap gap-1">
  <a href="?" class="btn btn-sm <?=!$status?'btn-primary':'btn-outline-secondary'?>">All</a>
  <?php foreach(['pending','approved','suspended','rejected'] as $s):?>
  <a href="?status=<?=$s?>" class="btn btn-sm <?=$status===$s?'btn-primary':'btn-outline-secondary'?>"><?=ucfirst($s)?></a>
  <?php endforeach;?>
</div>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Business</th><th>Owner</th><th>Email</th><th>Phone</th><th>Status</th><th>Sales</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($vendors as $v):?>
<tr>
  <td class="fw-semibold"><?=e($v['business_name'])?></td>
  <td><?=e($v['owner_name'])?></td>
  <td class="small"><?=e($v['email'])?></td>
  <td class="small"><?=e($v['phone'])?></td>
  <td><?=status_badge($v['status'])?></td>
  <td><?=money($v['total_sales'])?></td>
  <td>
    <form method="post" class="d-inline-flex gap-1 flex-wrap"><?=csrf_field()?><input type="hidden" name="id" value="<?=$v['id']?>">
      <?php if($v['status']!=='approved'):?><button name="action" value="approved" class="btn btn-sm btn-success">Approve</button><?php endif;?>
      <?php if($v['status']!=='suspended'):?><button name="action" value="suspended" class="btn btn-sm btn-warning">Suspend</button><?php endif;?>
      <?php if($v['status']==='pending'):?><button name="action" value="rejected" class="btn btn-sm btn-danger">Reject</button><?php endif;?>
    </form>
  </td>
</tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
