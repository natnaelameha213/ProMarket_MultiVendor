<?php
require_once __DIR__.'/config/config.php';
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT p.*, v.business_name, v.slug as vendor_slug, v.id as vid, c.name as category_name
  FROM products p 
  JOIN vendors v ON v.id=p.vendor_id 
  JOIN categories c ON c.id=p.category_id
  WHERE p.id=? AND p.status='active'");
$st->execute([$id]);
$product = $st->fetch();

if (!$product) {
  flash('danger', 'Product not found');
  redirect(BASE_URL.'/shop.php');
}

$pdo->prepare("UPDATE products SET views=views+1 WHERE id=?")->execute([$id]);

$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order");
$images->execute([$id]);
$images = $images->fetchAll();
if (!$images) $images = [['image_path' => null]];

$reviews = $pdo->prepare("SELECT r.*, cu.full_name FROM reviews r 
  JOIN customers cu ON cu.id=r.customer_id 
  WHERE r.product_id=? AND r.status='approved' 
  ORDER BY r.created_at DESC LIMIT 20");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$related = $pdo->prepare("SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p 
  JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1 
  WHERE p.category_id=? AND p.id!=? AND p.status='active' LIMIT 4");
$related->execute([$product['category_id'], $id]);
$related = $related->fetchAll();

/* Add to cart */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
  require_login(BASE_URL.'/auth/login.php');
  if (user_role() !== 'customer') {
    flash('warning', 'Only customers can add to cart');
    redirect(BASE_URL.'/product.php?id='.$id);
  }
  if (!verify_csrf()) {
    flash('danger', 'Invalid request');
    redirect(BASE_URL.'/product.php?id='.$id);
  }
  $qty = max(1, (int)($_POST['quantity'] ?? 1));
  if ($qty > $product['stock']) {
    flash('danger', 'Not enough stock available');
    redirect(BASE_URL.'/product.php?id='.$id);
  }
  $uid = current_user()['id'];
  $chk = $pdo->prepare("SELECT id,quantity FROM cart WHERE user_id=? AND product_id=?");
  $chk->execute([$uid, $id]);
  $existing = $chk->fetch();
  if ($existing) {
    $newQty = min($existing['quantity'] + $qty, (int)$product['stock']);
    $pdo->prepare("UPDATE cart SET quantity=? WHERE id=?")->execute([$newQty, $existing['id']]);
  } else {
    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$uid, $id, $qty]);
  }
  flash('success', 'Added to cart!');
  redirect(BASE_URL.'/cart.php');
}

/* Wishlist */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_wish'])) {
  require_login(BASE_URL.'/auth/login.php');
  if (!verify_csrf()) {
    flash('danger', 'Invalid request');
    redirect(BASE_URL.'/product.php?id='.$id);
  }
  $uid = current_user()['id'];
  try {
    $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$uid, $id]);
    flash('success', 'Added to wishlist!');
  } catch (Exception $e) {
    flash('info', 'Already in wishlist');
  }
  redirect(BASE_URL.'/product.php?id='.$id);
}

$pageTitle = $product['name'];
$price = current_price($product);
$disc = has_discount($product);

require __DIR__.'/includes/header.php';
?>
<div class="container py-4">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop.php?category=<?= $product['category_id'] ?>"><?= e($product['category_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 g-lg-5">
    <!-- Images -->
    <div class="col-md-5">
      <div class="bg-white rounded-4 shadow-sm p-2 mb-3 overflow-hidden">
        <img id="mainImg" src="<?= e(product_image($images[0]['image_path'] ?? null)) ?>" 
             class="w-100 rounded-3" style="aspect-ratio:1;object-fit:cover" alt="<?= e($product['name']) ?>">
      </div>
      <?php if (count($images) > 1): ?>
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($images as $im): ?>
        <img src="<?= e(product_image($im['image_path'])) ?>" 
             class="rounded-3 border" 
             style="width:72px;height:72px;object-fit:cover;cursor:pointer;transition:all .2s"
             onmouseover="this.style.borderColor='var(--primary)'"
             onmouseout="this.style.borderColor=''"
             onclick="document.getElementById('mainImg').src=this.src"
             alt="">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="col-md-7">
      <div class="text-muted small mb-2">
        <a href="<?= BASE_URL ?>/shop.php?vendor=<?= $product['vid'] ?>" class="text-muted"><?= e($product['business_name']) ?></a>
        · SKU: <?= e($product['sku']) ?>
      </div>

      <h1 class="h3 fw-bold mb-2" style="letter-spacing:-0.02em"><?= e($product['name']) ?></h1>

      <div class="mb-3">
        <?= stars((float)$product['avg_rating']) ?>
        <span class="text-muted small ms-1">(<?= $product['review_count'] ?> reviews) · <?= number_format($product['views']) ?> views</span>
      </div>

      <div class="mb-3">
        <span class="fs-2 fw-bold text-primary"><?= money($price) ?></span>
        <?php if ($disc): ?>
        <span class="text-muted text-decoration-line-through ms-2 fs-5"><?= money($product['price']) ?></span>
        <span class="badge bg-danger ms-2 rounded-pill">Save <?= round((1 - $price / $product['price']) * 100) ?>%</span>
        <?php endif; ?>
      </div>

      <p class="mb-4">
        <?php if ($product['stock'] > 0): ?>
          <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
            <i class="bi bi-check-circle me-1"></i>In Stock (<?= $product['stock'] ?>)
          </span>
        <?php else: ?>
          <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2">Out of Stock</span>
        <?php endif; ?>
      </p>

      <?php if ($product['stock'] > 0): ?>
      <form method="post" class="d-flex flex-wrap gap-2 align-items-end mb-4">
        <?= csrf_field() ?>
        <div>
          <label class="form-label small fw-semibold">Quantity</label>
          <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= $product['stock'] ?>" style="width:100px">
        </div>
        <button type="submit" name="add_cart" value="1" class="btn btn-primary btn-lg px-4 rounded-pill">
          <i class="bi bi-cart-plus me-1"></i> Add to Cart
        </button>
        <button type="submit" name="add_wish" value="1" class="btn btn-outline-secondary btn-lg rounded-pill" title="Add to Wishlist">
          <i class="bi bi-heart"></i>
        </button>
      </form>
      <?php endif; ?>

      <div class="card border-0 bg-light rounded-4 p-3 mb-3">
        <h6 class="fw-bold mb-2">Description</h6>
        <p class="mb-0 small" style="line-height:1.7"><?= nl2br(e($product['description'] ?? 'No description available.')) ?></p>
      </div>

      <?php if ($product['specifications']): ?>
      <div class="card border-0 bg-light rounded-4 p-3">
        <h6 class="fw-bold mb-2">Specifications</h6>
        <pre class="mb-0 small" style="white-space:pre-wrap;font-family:inherit;line-height:1.7"><?= e($product['specifications']) ?></pre>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reviews -->
  <div class="mt-5">
    <h4 class="fw-bold mb-3">Customer Reviews</h4>
    <?php if (!$reviews): ?>
      <p class="text-muted">No reviews yet. Be the first to review this product after purchase.</p>
    <?php else: foreach ($reviews as $r): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3 p-3">
      <div class="d-flex justify-content-between align-items-start">
        <strong><?= e($r['full_name']) ?></strong>
        <span class="small text-muted"><?= format_date($r['created_at']) ?></span>
      </div>
      <div class="my-1"><?= stars((float)$r['rating']) ?></div>
      <?php if ($r['title']): ?><div class="fw-semibold mb-1"><?= e($r['title']) ?></div><?php endif; ?>
      <p class="mb-0 small text-muted"><?= e($r['comment']) ?></p>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Related -->
  <?php if ($related): ?>
  <div class="mt-5">
    <h4 class="fw-bold mb-3">Related Products</h4>
    <div class="row g-3">
      <?php foreach ($related as $p):
        $img = product_image($p['image_path'] ?? null);
        $pr = current_price($p);
      ?>
      <div class="col-6 col-md-3">
        <div class="product-card">
          <a href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>">
            <div class="img-wrap"><img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>"></div>
          </a>
          <div class="card-body">
            <a href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>" class="product-title"><?= e($p['name']) ?></a>
            <div class="price mt-1"><?= money($pr) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>