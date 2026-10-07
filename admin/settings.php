<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Settings';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  $keys=['marketplace_name','marketplace_email','marketplace_phone','marketplace_address','shipping_flat_rate','shipping_free_threshold','currency_symbol'];
  foreach($keys as $k){
    if(isset($_POST[$k])){
      $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$k,$_POST[$k],$_POST[$k]]);
    }
  }
  flash('success','Settings saved');
  redirect(BASE_URL.'/admin/settings.php');
}
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Settings</h2>
<div class="form-card" style="max-width:600px">
<form method="post"><?=csrf_field()?>
  <div class="mb-3"><label class="form-label">Marketplace Name</label><input type="text" name="marketplace_name" class="form-control" value="<?=e(setting('marketplace_name'))?>"></div>
  <div class="mb-3"><label class="form-label">Email</label><input type="email" name="marketplace_email" class="form-control" value="<?=e(setting('marketplace_email'))?>"></div>
  <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="marketplace_phone" class="form-control" value="<?=e(setting('marketplace_phone'))?>"></div>
  <div class="mb-3"><label class="form-label">Address</label><input type="text" name="marketplace_address" class="form-control" value="<?=e(setting('marketplace_address'))?>"></div>
  <div class="mb-3"><label class="form-label">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?=e(setting('currency_symbol','ETB'))?>"></div>
  <div class="mb-3"><label class="form-label">Flat Shipping Rate ($)</label><input type="number" name="shipping_flat_rate" class="form-control" value="<?=e(setting('shipping_flat_rate','150'))?>"></div>
  <div class="mb-3"><label class="form-label">Free Shipping Threshold ($)</label><input type="number" name="shipping_free_threshold" class="form-control" value="<?=e(setting('shipping_free_threshold','5000'))?>"></div>
  <button class="btn btn-primary">Save Settings</button>
</form>
</div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
