<?php
require_role('vendor');
$vendorId = get_vendor_id();
if (!$vendorId) { flash('danger','Vendor profile not found'); redirect(BASE_URL.'/auth/logout.php'); }
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle??'Vendor')?> - ProMarket Vendor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="sidebar-overlay"></div>
<div class="dash-wrapper">
<aside class="dash-sidebar">
  <div class="brand">Pro<span>Market</span> Vendor</div>
  <a href="<?=BASE_URL?>/vendor/index.php" class="nav-link <?=$currentPage==='index'?'active':''?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="<?=BASE_URL?>/vendor/products.php" class="nav-link <?=in_array($currentPage,['products','product-form'])?'active':''?>"><i class="bi bi-box-seam"></i> Products</a>
  <a href="<?=BASE_URL?>/vendor/orders.php" class="nav-link <?=$currentPage==='orders'||$currentPage==='order-view'?'active':''?>"><i class="bi bi-bag-check"></i> Orders</a>
  <a href="<?=BASE_URL?>/vendor/reviews.php" class="nav-link <?=$currentPage==='reviews'?'active':''?>"><i class="bi bi-star"></i> Reviews</a>
  <a href="<?=BASE_URL?>/vendor/profile.php" class="nav-link <?=$currentPage==='profile'?'active':''?>"><i class="bi bi-person"></i> Profile</a>
  <a href="<?=BASE_URL?>/index.php" class="nav-link"><i class="bi bi-globe"></i> View Store</a>
  <a href="<?=BASE_URL?>/auth/logout.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
</aside>
<div class="dash-main">
  <div class="dash-topbar">
    <button class="btn btn-link d-lg-none p-0" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="text-muted small"><?=e(current_user()['name'])?></span>
      <a href="<?=BASE_URL?>/auth/logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
    </div>
  </div>
  <div class="dash-content">
<?php $flash=get_flash(); if($flash):?><div class="alert alert-<?=e($flash['type'])?> alert-dismissible fade show"><?=e($flash['message'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
