<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) {
    $r = user_role();
    redirect(match($r){'admin'=>BASE_URL.'/admin/index.php','vendor'=>BASE_URL.'/vendor/index.php','customer'=>BASE_URL.'/customer/dashboard.php',default=>BASE_URL.'/index.php'});
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $error = 'Invalid request.'; }
    else {
        $res = attempt_login(trim($_POST['email']??''), $_POST['password']??'');
        if ($res['success']) {
            $r = $res['user']['role'];
            redirect(match($r){'admin'=>BASE_URL.'/admin/index.php','vendor'=>BASE_URL.'/vendor/index.php','customer'=>BASE_URL.'/customer/dashboard.php',default=>BASE_URL.'/index.php'});
        }
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - ProMarket</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center p-3" style="min-height:100vh;background:linear-gradient(135deg,#0d6efd22,#0a58ca11)">
<div class="row w-100 g-0" style="max-width:960px">
  <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center rounded-start-4" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#fff;padding:3rem">
    <div>
      <div class="mb-4" style="width:56px;height:56px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800">P</div>
      <h1 class="fw-bold display-5">ProMarket</h1>
      <p class="fs-5 opacity-90">Your trusted multi-vendor marketplace for everything you need.</p>
      <div class="d-flex flex-wrap gap-2 mt-4">
        <span class="badge bg-white bg-opacity-25 p-2">Secure Payments</span>
        <span class="badge bg-white bg-opacity-25 p-2">Verified Vendors</span>
        <span class="badge bg-white bg-opacity-25 p-2">Fast Delivery</span>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-lg rounded-4 rounded-lg-start-0 h-100">
      <div class="card-body p-4 p-lg-5">
        <h3 class="fw-bold mb-1">Welcome back</h3>
        <p class="text-muted mb-4">Sign in to your account</p>
        <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
        <form method="post">
          <?=csrf_field()?>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control form-control-lg" value="<?=e($_POST['email']??'admin@promarket.local')?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
          </div>
          <button class="btn btn-primary btn-lg w-100 mb-3">Sign In <i class="bi bi-arrow-right"></i></button>
        </form>
        <div class="text-center small text-muted">
          <p class="mb-1">Demo accounts (password: <strong>Admin123!</strong>)</p>
          <p class="mb-0">Admin: admin@promarket.local · Vendor: abebe@techhub.et · Customer: customer1@email.com</p>
        </div>
        <hr>
        <div class="text-center">
          <a href="<?=BASE_URL?>/auth/register.php">Create customer account</a> ·
          <a href="<?=BASE_URL?>/auth/vendor-register.php">Become a vendor</a>
        </div>
        <div class="text-center mt-3"><a href="<?=BASE_URL?>/index.php" class="text-muted small"><i class="bi bi-arrow-left"></i> Back to store</a></div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
