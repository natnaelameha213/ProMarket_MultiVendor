<?php
require_once __DIR__.'/config/config.php';
$pageTitle = 'Home';

$featured = $pdo->query("SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p 
  JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE p.status='active' AND p.is_featured=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();

$latest = $pdo->query("SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

$bestsellers = $pdo->query("SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE p.status='active' AND p.is_bestseller=1 ORDER BY p.sales_count DESC LIMIT 8")->fetchAll();

$discounts = $pdo->query("SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE p.status='active' AND p.discount_price IS NOT NULL AND p.discount_price < p.price 
  ORDER BY (p.price-p.discount_price)/p.price DESC LIMIT 8")->fetchAll();

$vendors = $pdo->query("SELECT * FROM vendors WHERE status='approved' ORDER BY total_sales DESC LIMIT 6")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY sort_order,name")->fetchAll();

function render_products($items) {
  if (!$items) {
    echo '<div class="col-12"><div class="empty-state"><i class="bi bi-box"></i><p>No products found</p></div></div>';
    return;
  }
  foreach ($items as $p) {
    $img = product_image($p['image_path'] ?? null);
    $price = current_price($p);
    $disc = has_discount($p);
    echo '<div class="col-6 col-md-4 col-lg-3">
      <div class="product-card">
        <a href="'.BASE_URL.'/product.php?id='.$p['id'].'">
          <div class="img-wrap">';
    if ($disc) {
      $pct = round((1 - $price / $p['price']) * 100);
      echo '<span class="badge bg-danger badge-discount">-'.$pct.'%</span>';
    }
    echo '<img src="'.e($img).'" alt="'.e($p['name']).'" loading="lazy">
          </div>
        </a>
        <div class="card-body">
          <div class="vendor-name mb-1">'.e($p['vendor_name']).'</div>
          <a href="'.BASE_URL.'/product.php?id='.$p['id'].'" class="product-title mb-2">'.e($p['name']).'</a>
          <div class="mt-auto">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="price">'.money($price).'</span>';
    if ($disc) echo '<span class="price-old">'.money($p['price']).'</span>';
    echo '</div>
            <div class="small">'.stars((float)$p['avg_rating']).' <span class="text-muted">('.$p['review_count'].')</span></div>
          </div>
        </div>
      </div>
    </div>';
  }
}

require __DIR__.'/includes/header.php';
?>

<!-- HERO -->
<?php
  $heroImgs = array_slice($featured, 0, 3);
  $heroSrc = fn($i) => product_image($heroImgs[$i]['image_path'] ?? null);
?>
<section class="hero">
  <span class="dots" aria-hidden="true"></span>
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <span class="hero-tag"><i class="bi bi-shop"></i> Marketplace</span>
        <h1>Shop from trusted vendors. <span class="accent">All in one place.</span></h1>
        <p class="lead-sm">Electronics, fashion, home, beauty and food from verified sellers across Ethiopia.</p>
        <form action="<?= BASE_URL ?>/shop.php" method="get" class="d-flex" style="max-width:520px">
          <input type="search" name="q" class="form-control form-control-lg" placeholder="What are you looking for?" aria-label="Search products">
          <button class="btn btn-search btn-lg"><i class="bi bi-search"></i><span class="visually-hidden">Search</span></button>
        </form>
        <ul class="hero-points">
          <li><i class="bi bi-shield-check"></i>Secure payments</li>
          <li><i class="bi bi-patch-check"></i>Verified vendors</li>
          <li><i class="bi bi-truck"></i>Fast delivery</li>
        </ul>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="hero-photos">
          <img class="ph-1" src="<?= e($heroSrc(0)) ?>" alt="<?= e($heroImgs[0]['name'] ?? 'Featured product') ?>">
          <img class="ph-2" src="<?= e($heroSrc(1)) ?>" alt="<?= e($heroImgs[1]['name'] ?? 'Featured product') ?>">
          <img class="ph-3" src="<?= e($heroSrc(2)) ?>" alt="<?= e($heroImgs[2]['name'] ?? 'Featured product') ?>">
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container">

  <!-- CATEGORIES -->
  <div class="d-flex gap-2 overflow-auto pb-2 mb-5" style="scrollbar-width:thin">
    <?php foreach ($categories as $c): ?>
    <a href="<?= BASE_URL ?>/shop.php?category=<?= $c['id'] ?>" class="cat-chip">
      <i class="bi bi-tag-fill"></i> <?= e($c['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- FEATURED -->
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="section-title mb-0">Featured Products</h2>
    <a href="<?= BASE_URL ?>/shop.php?featured=1" class="text-primary fw-semibold small">View all <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3 mb-5"><?php render_products($featured); ?></div>

  <!-- BESTSELLERS -->
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="section-title mb-0">Best Sellers</h2>
    <a href="<?= BASE_URL ?>/shop.php?sort=popular" class="text-primary fw-semibold small">View all <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3 mb-5"><?php render_products($bestsellers); ?></div>

  <!-- PROMO STRIP -->
  <div class="promo-strip text-center">
    <h3 class="mb-2">Deals this week</h3>
    <p class="mb-3 opacity-90">Save on selected electronics, fashion and beauty products.</p>
    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-light btn-lg px-4 fw-semibold">Shop Deals <i class="bi bi-arrow-right"></i></a>
  </div>

  <!-- DISCOUNTS -->
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="section-title mb-0">Hot Deals</h2>
    <a href="<?= BASE_URL ?>/shop.php" class="text-primary fw-semibold small">View all <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3 mb-5"><?php render_products($discounts); ?></div>

  <!-- NEW ARRIVALS -->
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="section-title mb-0">New Arrivals</h2>
    <a href="<?= BASE_URL ?>/shop.php?sort=newest" class="text-primary fw-semibold small">View all <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3 mb-5"><?php render_products($latest); ?></div>

  <!-- VENDORS -->
  <h2 class="section-title">Popular Vendors</h2>
  <div class="row g-3 mb-5">
    <?php foreach ($vendors as $v): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <a href="<?= BASE_URL ?>/shop.php?vendor=<?= $v['id'] ?>" class="vendor-card text-decoration-none">
        <div class="vendor-avatar"><?= e(strtoupper(substr($v['business_name'], 0, 1))) ?></div>
        <div class="fw-semibold text-dark small"><?= e($v['business_name']) ?></div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require __DIR__.'/includes/footer.php'; ?>