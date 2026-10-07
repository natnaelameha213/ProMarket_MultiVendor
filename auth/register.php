<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) redirect(BASE_URL.'/index.php');
$error = $success = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) $error='Invalid request.';
  elseif (($_POST['password']??'') !== ($_POST['password2']??'')) $error='Passwords do not match.';
  else {
    $res = register_customer($_POST);
    if ($res['success']) {
      $st=$pdo->prepare('SELECT * FROM users WHERE id=?');$st->execute([$res['user_id']]);
      login_user($st->fetch());
      flash('success','Welcome to ProMarket!');
      redirect(BASE_URL.'/customer/dashboard.php');
    }
    $error=$res['message'];
  }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register - ProMarket</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>/assets/css/style.css" rel="stylesheet"></head>
<body class="d-flex align-items-center justify-content-center p-3" style="min-height:100vh;background:#f4f6f9">
<div class="card shadow border-0 rounded-4" style="max-width:480px;width:100%">
  <div class="card-body p-4 p-lg-5">
    <h3 class="fw-bold">Create Account</h3>
    <p class="text-muted mb-4">Join ProMarket as a customer</p>
    <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
    <form method="post">
      <?=csrf_field()?>
      <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=e($_POST['name']??'')?>" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=e($_POST['email']??'')?>" required></div>
      <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?=e($_POST['phone']??'')?>"></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="password2" class="form-control" required></div>
      <button class="btn btn-primary w-100 btn-lg">Create Account</button>
    </form>
    <div class="text-center mt-3"><a href="<?=BASE_URL?>/auth/login.php">Already have an account? Sign in</a></div>
  </div>
</div>
</body></html>
