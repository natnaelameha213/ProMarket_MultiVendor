<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Product Images';
require_role('admin');

$dir = UPLOAD_PATH . '/products';
if (!is_dir($dir)) mkdir($dir, 0775, true);

/** True when the product has no real photo yet (empty, SVG placeholder, or missing file). */
function needs_photo(?string $path): bool {
    if (!$path) return true;
    if (preg_match('/\.svg$/i', $path)) return true;
    if (preg_match('#^https?://#i', $path)) return false;
    return !file_exists(UPLOAD_PATH . '/' . $path);
}

function save_primary(PDO $pdo, int $id, string $path): void {
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?,?,1,0)')
        ->execute([$id, $path]);
}

$filter = $_GET['show'] ?? 'missing';           // missing | all
$vendor = (int)($_GET['vendor'] ?? 0);
$back   = BASE_URL . '/admin/product-images.php?show=' . urlencode($filter) . ($vendor ? '&vendor=' . $vendor : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { flash('danger', 'Invalid form token. Reload and try again.'); redirect($back); }
    $id = (int)($_POST['product_id'] ?? 0);
    $exists = $pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ?');
    $exists->execute([$id]);
    if (!$id || !$exists->fetchColumn()) { flash('danger', 'Product not found.'); redirect($back); }

    $old = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1');
    $old->execute([$id]);
    $oldPath = (string)$old->fetchColumn();

    // Option A: uploaded file
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['photo'];
        if ($f['size'] > MAX_UPLOAD_SIZE) {
            flash('danger', 'File is larger than ' . (MAX_UPLOAD_SIZE / 1048576) . ' MB.'); redirect($back);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
        if (!$ext || !@getimagesize($f['tmp_name'])) {
            flash('danger', 'Only JPG, PNG, WEBP or GIF images are allowed.'); redirect($back);
        }
        $name = "p{$id}_" . time() . ".$ext";
        if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) {
            flash('danger', 'Could not save the file. Check folder permissions.'); redirect($back);
        }
        save_primary($pdo, $id, "products/$name");
        flash('success', "Photo saved for product #$id.");
    }
    // Option B: image URL
    elseif (!empty(trim($_POST['photo_url'] ?? ''))) {
        $url = trim($_POST['photo_url']);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            flash('danger', 'Please enter a valid http(s) image URL.'); redirect($back);
        }
        save_primary($pdo, $id, $url);
        flash('success', "Image URL saved for product #$id.");
    }
    // Option C: reset to Orange+Navy image
    elseif (isset($_POST['reset'])) {
        $svg = "products/p{$id}.svg";
        if (file_exists(UPLOAD_PATH . '/' . $svg)) save_primary($pdo, $id, $svg);
        else $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        flash('success', "Product #$id reset to the placeholder image.");
    } else {
        flash('warning', 'Choose a file or paste an image URL first.'); redirect($back);
    }

    // remove the previous local photo (never the SVG placeholder)
    if ($oldPath && !preg_match('#^https?://#i', $oldPath) && !preg_match('/\.svg$/i', $oldPath)
        && strpos($oldPath, 'products/p' . $id) === 0 && file_exists(UPLOAD_PATH . '/' . $oldPath)) {
        $newRow = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1');
        $newRow->execute([$id]);
        if ($newRow->fetchColumn() !== $oldPath) @unlink(UPLOAD_PATH . '/' . $oldPath);
    }
    redirect($back . '#p' . $id);
}

$sql = "SELECT p.id, p.name, v.business_name AS vendor_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
          FROM products p JOIN vendors v ON v.id = p.vendor_id";
$params = [];
if ($vendor) { $sql .= ' WHERE p.vendor_id = ?'; $params[] = $vendor; }
$sql .= ' ORDER BY p.id';
$st = $pdo->prepare($sql);
$st->execute($params);
$all = $st->fetchAll();

$total = count($all);
$missing = count(array_filter($all, fn($r) => needs_photo($r['img'])));
$rows = $filter === 'all' ? $all : array_values(array_filter($all, fn($r) => needs_photo($r['img'])));
$vendors = $pdo->query('SELECT id, business_name FROM vendors ORDER BY business_name')->fetchAll();

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h2 class="fw-bold mb-0">Product Images</h2>
    <div class="text-muted small"><?= $missing ?> of <?= $total ?> products still need a real photo</div>
  </div>
  <form method="get" class="d-flex gap-2">
    <select name="show" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="missing"<?= $filter === 'missing' ? ' selected' : '' ?>>Only without photo</option>
      <option value="all"<?= $filter === 'all' ? ' selected' : '' ?>>All products</option>
    </select>
    <select name="vendor" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="0">All vendors</option>
      <?php foreach ($vendors as $v): ?>
        <option value="<?= $v['id'] ?>"<?= $vendor === (int)$v['id'] ? ' selected' : '' ?>><?= e($v['business_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (!$rows): ?>
  <div class="empty-state"><i class="bi bi-check2-circle"></i><p>All products in this view have photos.</p></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($rows as $r):
    $isMissing = needs_photo($r['img']); ?>
  <div class="col-6 col-md-4 col-xl-3" id="p<?= (int)$r['id'] ?>">
    <div class="product-card">
      <div class="img-wrap">
        <span class="badge <?= $isMissing ? 'bg-warning text-dark' : 'bg-success' ?>" style="position:absolute;top:10px;left:10px;z-index:2">
          <?= $isMissing ? 'No photo' : 'Has photo' ?>
        </span>
        <img src="<?= e(product_image($r['img'])) ?>" alt="<?= e($r['name']) ?>" loading="lazy">
      </div>
      <div class="card-body">
        <div class="vendor-name mb-1"><?= e($r['vendor_name']) ?></div>
        <div class="fw-semibold small mb-2">#<?= (int)$r['id'] ?> <?= e($r['name']) ?></div>
        <form method="post" enctype="multipart/form-data" class="mt-auto">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$r['id'] ?>">
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                 class="form-control form-control-sm mb-2" onchange="this.form.submit()">
          <div class="input-group input-group-sm mb-2">
            <input type="url" name="photo_url" class="form-control" placeholder="or paste image URL">
            <button class="btn btn-primary" type="submit">Save</button>
          </div>
          <?php if (!$isMissing): ?>
            <button name="reset" value="1" class="btn btn-sm btn-outline-secondary w-100"
                    onclick="return confirm('Remove this photo?')">Remove photo</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>