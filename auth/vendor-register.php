<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) redirect(BASE_URL.'/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) $error='Invalid request.';
  elseif (($_POST['password']??'') !== ($_POST['password2']??'')) $error='Passwords do not match.';
  else {
    $res = register_vendor($_POST);
    if ($res['success']) {
      flash('success','Vendor registration submitted! Please wait for admin approval before logging in.');
      redirect(BASE_URL.'/auth/login.php');
    }
    $error=$res['message'];
  }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vendor Registration - ProMarket</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>/assets/css/style.css" rel="stylesheet"></head>
<body class="py-5" style="background:#f4f6f9">
<div class="container" style="max-width:640px">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-lg-5">
      <h3 class="fw-bold">Become a Vendor</h3>
      <p class="text-muted mb-4">Sell your products on ProMarket</p>
      <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
      <form method="post">
        <?=csrf_field()?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Business Name *</label><input type="text" name="business_name" class="form-control" required value="<?=e($_POST['business_name']??'')?>"></div>
          <div class="col-md-6"><label class="form-label">Owner Name *</label><input type="text" name="owner_name" class="form-control" required value="<?=e($_POST['owner_name']??'')?>"></div>
          <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?=e($_POST['email']??'')?>"></div>
          <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?=e($_POST['phone']??'')?>"></div>
          <div class="col-md-6"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?=e($_POST['city']??'')?>"></div>
          <div class="col-md-6"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?=e($_POST['address']??'')?>"></div>
          <div class="col-12"><label class="form-label">Business Description</label><textarea name="description" class="form-control" rows="3"><?=e($_POST['description']??'')?></textarea></div>
          <div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
          <div class="col-md-6"><label class="form-label">Confirm Password *</label><input type="password" name="password2" class="form-control" required></div>
        </div>
        <button class="btn btn-primary w-100 btn-lg mt-4">Submit Application</button>
      </form>
      <div class="text-center mt-3"><a href="<?=BASE_URL?>/auth/login.php">Already registered? Sign in</a></div>
    </div>
  </div>
</div>
</body></html>
