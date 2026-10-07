<?php
require_once __DIR__.'/config/config.php';
$pageTitle = 'Shop';

$q = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$vendor = (int)($_GET['vendor'] ?? 0);
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$rating = (int)($_GET['rating'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';
$inStock = isset($_GET['in_stock']);
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["p.status='active'"];
$params = [];

if ($q !== '') {
  $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($category) { $where[] = "p.category_id=?"; $params[] = $category; }
if ($vendor) { $where[] = "p.vendor_id=?"; $params[] = $vendor; }
if ($minPrice !== '' && is_numeric($minPrice)) { $where[] = "COALESCE(p.discount_price,p.price) >= ?"; $params[] = (float)$minPrice; }
if ($maxPrice !== '' && is_numeric($maxPrice)) { $where[] = "COALESCE(p.discount_price,p.price) <= ?"; $params[] = (float)$maxPrice; }
if ($rating) { $where[] = "p.avg_rating >= ?"; $params[] = $rating; }
if ($inStock) { $where[] = "p.stock > 0"; }

$orderMap = [
  'newest' => 'p.created_at DESC',
  'price_asc' => 'COALESCE(p.discount_price,p.price) ASC',
  'price_desc' => 'COALESCE(p.discount_price,p.price) DESC',
  'rating' => 'p.avg_rating DESC',
  'popular' => 'p.sales_count DESC',
];
$order = $orderMap[$sort] ?? $orderMap['newest'];
$whereSql = implode(' AND ', $where);

$countSt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$pag = paginate($total, $page);

$sql = "SELECT p.*, v.business_name as vendor_name, pi.image_path 
  FROM products p 
  JOIN vendors v ON v.id=p.vendor_id 
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE $whereSql ORDER BY $order LIMIT {$pag['per_page']} OFFSET {$pag['offset']}";
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$categories = $pdo->query("SELECT id,name FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$vendors = $pdo->query("SELECT id,business_name FROM vendors WHERE status='approved' ORDER BY business_name")->fetchAll();

$qs = $_GET; unset($qs['page']);
$baseUrl = BASE_URL.'/shop.php?'.http_build_query($qs).'&';

require __DIR__.'/includes/header.php';
?>
<div class="container py-4">
  <div class="row g-4">
    <!-- FILTERS -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top:90px">
        <h5 class="fw-bold mb-3">Filters</h5>
        <form method="get">
          <?php if ($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Category</label>
            <select name="category" class="form-select form-select-sm">
              <option value="">All Categories</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $category == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Vendor</label>
            <select name="vendor" class="form-select form-select-sm">
              <option value="">All Vendors</option>
              <?php foreach ($vendors as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $vendor == $v['id'] ? 'selected' : '' ?>><?= e($v['business_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Price Range ($)</label>
            <div class="d-flex gap-2">
              <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= e($minPrice) ?>">
              <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= e($maxPrice) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Min Rating</label>
            <select name="rating" class="form-select form-select-sm">
              <option value="">Any</option>
              <?php for ($i = 4; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>><?= $i ?>+ stars</option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" name="in_stock" class="form-check-input" id="inStock" <?= $inStock ? 'checked' : '' ?>>
            <label class="form-check-label small" for="inStock">In stock only</label>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Sort By</label>
            <select name="sort" class="form-select form-select-sm">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
              <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
            </select>
          </div>

          <button class="btn btn-primary btn-sm w-100 rounded-pill">Apply Filters</button>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-secondary btn-sm w-100 mt-2 rounded-pill">Clear All</a>
        </form>
      </div>
    </div>

    <!-- PRODUCTS -->
    <div class="col-lg-9">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
          <h4 class="mb-0 fw-bold"><?= $q ? 'Results for “'.e($q).'”' : 'All Products' ?></h4>
          <span class="text-muted small"><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> found</span>
        </div>
      </div>

      <div class="row g-3">
        <?php if (!$products): ?>
        <div class="col-12">
          <div class="empty-state">
            <i class="bi bi-search"></i>
            <p class="mb-3">No products match your filters</p>
            <a href="<?= BASE_URL ?>/shop.php" class="btn btn-primary rounded-pill px-4">Clear Filters</a>
          </div>
        </div>
        <?php else: foreach ($products as $p):
          $img = product_image($p['image_path'] ?? null);
          $price = current_price($p);
          $disc = has_discount($p);
        ?>
        <div class="col-6 col-md-4">
          <div class="product-card">
            <a href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>">
              <div class="img-wrap">
                <?php if ($disc):
                  $pct = round((1 - $price / $p['price']) * 100);
                ?>
                <span class="badge bg-danger badge-discount">-<?= $pct ?>%</span>
                <?php endif; ?>
                <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
              </div>
            </a>
            <div class="card-body">
              <div class="vendor-name mb-1"><?= e($p['vendor_name']) ?></div>
              <a href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>" class="product-title mb-2"><?= e($p['name']) ?></a>
              <div class="mt-auto">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="price"><?= money($price) ?></span>
                  <?php if ($disc): ?><span class="price-old"><?= money($p['price']) ?></span><?php endif; ?>
                </div>
                <div class="small"><?= stars((float)$p['avg_rating']) ?> <span class="text-muted">(<?= $p['review_count'] ?>)</span></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="mt-4"><?= pagination_html($pag, $baseUrl) ?></div>
    </div>
  </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>