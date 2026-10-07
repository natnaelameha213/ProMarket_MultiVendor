<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Coupons';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['add'])){
    $pdo->prepare("INSERT INTO coupons (code,description,discount_type,discount_value,min_order_amount,max_discount,usage_limit,start_date,expiry_date,status) VALUES (?,?,?,?,?,?,?,?,?,?)")
      ->execute([strtoupper(trim($_POST['code'])),trim($_POST['description']??''),$_POST['discount_type'],(float)$_POST['discount_value'],(float)($_POST['min_order']??0),$_POST['max_discount']!==''?(float)$_POST['max_discount']:null,(int)($_POST['usage_limit']??0)?:null,$_POST['start_date'],$_POST['expiry_date'],'active']);
    flash('success','Coupon created');redirect(BASE_URL.'/admin/coupons.php');
  }
  if(isset($_POST['delete'])){
    $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_POST['delete']]);
    flash('success','Coupon deleted');redirect(BASE_URL.'/admin/coupons.php');
  }
}
$coupons=$pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Coupons</h2>
<div class="row g-4">
  <div class="col-md-4">
    <div class="form-card">
      <h5 class="fw-bold mb-3">Create Coupon</h5>
      <form method="post"><?=csrf_field()?>
        <div class="mb-2"><input type="text" name="code" class="form-control form-control-sm" placeholder="Code" required></div>
        <div class="mb-2"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description"></div>
        <div class="mb-2"><select name="discount_type" class="form-select form-select-sm"><option value="percentage">Percentage</option><option value="fixed">Fixed Amount</option></select></div>
        <div class="mb-2"><input type="number" step="0.01" name="discount_value" class="form-control form-control-sm" placeholder="Value" required></div>
        <div class="mb-2"><input type="number" name="min_order" class="form-control form-control-sm" placeholder="Min order"></div>
        <div class="mb-2"><input type="number" name="max_discount" class="form-control form-control-sm" placeholder="Max discount"></div>
        <div class="mb-2"><input type="number" name="usage_limit" class="form-control form-control-sm" placeholder="Usage limit"></div>
        <div class="mb-2"><input type="date" name="start_date" class="form-control form-control-sm" required value="<?=date('Y-m-d')?>"></div>
        <div class="mb-2"><input type="date" name="expiry_date" class="form-control form-control-sm" required value="<?=date('Y-m-d',strtotime('+3 months'))?>"></div>
        <button name="add" value="1" class="btn btn-primary btn-sm w-100">Create</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="table-card"><div class="table-responsive"><table class="table">
      <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($coupons as $c):?>
      <tr>
        <td class="fw-semibold"><?=e($c['code'])?></td>
        <td><?=e($c['discount_type'])?></td>
        <td><?=$c['discount_type']==='percentage'?$c['discount_value'].'%':money($c['discount_value'])?></td>
        <td><?=$c['used_count']?><?=$c['usage_limit']?'/'.$c['usage_limit']:''?></td>
        <td class="small"><?=e($c['expiry_date'])?></td>
        <td><?=status_badge($c['status'])?></td>
        <td><form method="post" onsubmit="return confirm('Delete?')"><?=csrf_field()?><button name="delete" value="<?=$c['id']?>" class="btn btn-sm btn-outline-danger">Del</button></form></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div></div>
  </div>
</div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
