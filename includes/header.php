<?php
$cartCnt = cart_count();
$wishCnt = wishlist_count();
$cats = $pdo->query("SELECT id,name,slug FROM categories WHERE status='active' ORDER BY sort_order,name LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'ProMarket') ?> - Multi-Vendor Marketplace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-market">
  <div class="container">
    <a class="brand me-lg-4" href="<?= BASE_URL ?>/index.php">Pro<span>Market</span></a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <!-- Search -->
      <form class="search-bar d-flex mx-lg-auto my-3 my-lg-0" action="<?= BASE_URL ?>/shop.php" method="get">
        <input type="search" name="q" class="form-control" placeholder="Search products, brands..." value="<?= e($_GET['q'] ?? '') ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
      </form>

      <ul class="navbar-nav ms-lg-3 align-items-lg-center gap-lg-1">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/shop.php">Shop</a>
        </li>

        <!-- Cart -->
        <li class="nav-item position-relative">
          <a class="nav-link" href="<?= BASE_URL ?>/cart.php" title="Cart">
            <i class="bi bi-cart3 fs-5"></i>
            <?php if ($cartCnt): ?>
              <span class="badge bg-danger rounded-pill cart-badge"><?= $cartCnt ?></span>
            <?php endif; ?>
          </a>
        </li>

        <!-- Wishlist (customers only) -->
        <?php if (is_logged_in() && user_role() === 'customer'): ?>
        <li class="nav-item position-relative">
          <a class="nav-link" href="<?= BASE_URL ?>/customer/wishlist.php" title="Wishlist">
            <i class="bi bi-heart fs-5"></i>
            <?php if ($wishCnt): ?>
              <span class="badge bg-danger rounded-pill cart-badge"><?= $wishCnt ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?php endif; ?>

        <!-- Auth -->
        <?php if (is_logged_in()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-5"></i>
            <span class="d-none d-lg-inline"><?= e(current_user()['name']) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
            <?php if (user_role() === 'admin'): ?>
              <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
            <?php elseif (user_role() === 'vendor'): ?>
              <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/vendor/index.php"><i class="bi bi-shop me-2"></i>Vendor Dashboard</a></li>
            <?php else: ?>
              <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/dashboard.php"><i class="bi bi-person me-2"></i>My Account</a></li>
              <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/orders.php"><i class="bi bi-bag me-2"></i>Orders</a></li>
              <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-primary btn-sm px-3 ms-lg-2 rounded-pill" href="<?= BASE_URL ?>/auth/register.php">Register</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php
$flash = get_flash();
if ($flash):
?>
<div class="container mt-3">
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 rounded-3">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>