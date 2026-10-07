<?php
require_once __DIR__.'/../config/config.php';
require_role('customer');
$pageTitle='Profile';
$user=current_user();
$error=$success='';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['update_profile'])){
    $name=trim($_POST['name']??'');$phone=trim($_POST['phone']??'');
    if($name){
      $pdo->prepare("UPDATE users SET name=?,phone=? WHERE id=?")->execute([$name,$phone?:null,$user['id']]);
      $pdo->prepare("UPDATE customers SET full_name=?,phone=? WHERE user_id=?")->execute([$name,$phone?:null,$user['id']]);
      $_SESSION['user']['name']=$name;$_SESSION['user']['phone']=$phone;
      $success='Profile updated';
    }
  }
  if(isset($_POST['change_password'])){
    $cur=$_POST['current_password']??'';$new=$_POST['new_password']??'';$conf=$_POST['confirm_password']??'';
    $st=$pdo->prepare("SELECT password FROM users WHERE id=?");$st->execute([$user['id']]);$hash=$st->fetchColumn();
    if(!password_verify($cur,$hash)) $error='Current password is incorrect';
    elseif(strlen($new)<6) $error='New password must be at least 6 characters';
    elseif($new!==$conf) $error='Passwords do not match';
    else{
      $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$user['id']]);
      $success='Password changed';
    }
  }
}
require __DIR__.'/../includes/header.php';
?>
<div class="container py-4" style="max-width:600px">
  <h2 class="fw-bold mb-4">My Profile</h2>
  <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
  <?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
  <div class="form-card mb-4">
    <h5 class="fw-bold mb-3">Personal Info</h5>
    <form method="post"><?=csrf_field()?>
      <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?=e($user['name'])?>" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?=e($user['email'])?>" disabled></div>
      <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?=e($user['phone']??'')?>"></div>
      <button name="update_profile" value="1" class="btn btn-primary">Save</button>
    </form>
  </div>
  <div class="form-card">
    <h5 class="fw-bold mb-3">Change Password</h5>
    <form method="post"><?=csrf_field()?>
      <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
      <button name="change_password" value="1" class="btn btn-primary">Change Password</button>
    </form>
  </div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
