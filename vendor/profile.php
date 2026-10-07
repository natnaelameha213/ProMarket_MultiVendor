<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Profile';
$vid=get_vendor_id();
$v=$pdo->prepare("SELECT * FROM vendors WHERE id=?");$v->execute([$vid]);$v=$v->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  $pdo->prepare("UPDATE vendors SET business_name=?,phone=?,address=?,city=?,description=? WHERE id=?")
    ->execute([trim($_POST['business_name']),trim($_POST['phone']??''),trim($_POST['address']??''),trim($_POST['city']??''),trim($_POST['description']??''),$vid]);
  flash('success','Profile updated');redirect(BASE_URL.'/vendor/profile.php');
}
require __DIR__.'/../includes/vendor_header.php';
?>
<h2 class="fw-bold mb-4">Vendor Profile</h2>
<div class="form-card" style="max-width:600px">
<form method="post"><?=csrf_field()?>
  <div class="mb-3"><label class="form-label">Business Name</label><input type="text" name="business_name" class="form-control" value="<?=e($v['business_name'])?>" required></div>
  <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?=e($v['phone'])?>"></div>
  <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?=e($v['address'])?>"></div>
  <div class="mb-3"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?=e($v['city'])?>"></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?=e($v['description'])?></textarea></div>
  <div class="mb-3"><label class="form-label">Status</label><div><?=status_badge($v['status'])?></div></div>
  <button class="btn btn-primary">Save</button>
</form>
</div>
<?php require __DIR__.'/../includes/vendor_footer.php';?>
