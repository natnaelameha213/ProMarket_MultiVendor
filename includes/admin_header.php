<?php
require_role('admin');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle??'Admin')?> - ProMarket Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>/assets/css/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="sidebar-overlay"></div>
<div class="dash-wrapper">
<aside class="dash-sidebar">
  <div class="brand">Pro<span>Market</span> Admin</div>
  <div class="nav-section">Main</div>
  <a href="<?=BASE_URL?>/admin/index.php" class="nav-link <?=$currentPage==='index'?'active':''?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="<?=BASE_URL?>/admin/orders.php" class="nav-link <?=$currentPage==='orders'||$currentPage==='order-view'?'active':''?>"><i class="bi bi-bag-check"></i> Orders</a>
  <a href="<?=BASE_URL?>/admin/products.php" class="nav-link <?=$currentPage==='products'||$currentPage==='product-form'?'active':''?>"><i class="bi bi-box-seam"></i> Products</a>
  <a href="<?=BASE_URL?>/admin/product-images.php" class="nav-link <?=$currentPage==='product-images'?'active':''?>"><i class="bi bi-images"></i> Product Images</a>
  <a href="<?=BASE_URL?>/admin/categories.php" class="nav-link <?=$currentPage==='categories'?'active':''?>"><i class="bi bi-tags"></i> Categories</a>
  <div class="nav-section">Users</div>
  <a href="<?=BASE_URL?>/admin/vendors.php" class="nav-link <?=$currentPage==='vendors'?'active':''?>"><i class="bi bi-shop"></i> Vendors</a>
  <a href="<?=BASE_URL?>/admin/customers.php" class="nav-link <?=$currentPage==='customers'?'active':''?>"><i class="bi bi-people"></i> Customers</a>
  <div class="nav-section">Marketing</div>
  <a href="<?=BASE_URL?>/admin/coupons.php" class="nav-link <?=$currentPage==='coupons'?'active':''?>"><i class="bi bi-ticket-perforated"></i> Coupons</a>
  <a href="<?=BASE_URL?>/admin/reviews.php" class="nav-link <?=$currentPage==='reviews'?'active':''?>"><i class="bi bi-star"></i> Reviews</a>
  <div class="nav-section">System</div>
  <a href="<?=BASE_URL?>/admin/reports.php" class="nav-link <?=$currentPage==='reports'?'active':''?>"><i class="bi bi-bar-chart"></i> Reports</a>
  <a href="<?=BASE_URL?>/admin/settings.php" class="nav-link <?=$currentPage==='settings'?'active':''?>"><i class="bi bi-gear"></i> Settings</a>
  <a href="<?=BASE_URL?>/index.php" class="nav-link"><i class="bi bi-globe"></i> View Store</a>
  <a href="<?=BASE_URL?>/auth/logout.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
</aside>
<div class="dash-main">
  <div class="dash-topbar">
    <button class="btn btn-link d-lg-none p-0" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-inline"><?=e(current_user()['name'])?></span>
      <a href="<?=BASE_URL?>/auth/logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
    </div>
  </div>
  <div class="dash-content">
<?php $flash=get_flash(); if($flash):?><div class="alert alert-<?=e($flash['type'])?> alert-dismissible fade show"><?=e($flash['message'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
