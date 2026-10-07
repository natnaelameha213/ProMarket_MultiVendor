<?php
require_once __DIR__ . '/../config/config.php';
$vid = get_vendor_id();
$id = (int)($_GET['id'] ?? 0);
$product = null;

if ($id) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=? AND vendor_id=?");
    $st->execute([$id, $vid]);
    $product = $st->fetch();
    if (!$product) {
        flash('danger', 'Product not found');
        redirect(BASE_URL . '/vendor/products.php');
    }
}

$pageTitle = $product ? 'Edit Product' : 'Add Product';
$categories = $pdo->query("SELECT id,name FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $name     = trim($_POST['name'] ?? '');
    $sku      = trim($_POST['sku'] ?? '');
    $cat      = (int)($_POST['category_id'] ?? 0);
    $price    = (float)($_POST['price'] ?? 0);
    $discount = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
    $stock    = (int)($_POST['stock'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');
    $specs    = trim($_POST['specifications'] ?? '');
    $status   = $_POST['status'] ?? 'pending';
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (!$name || !$sku || !$cat || $price <= 0) {
        $error = 'Name, SKU, category and valid price are required.';
    } else {
        $slug = slugify($name);
        try {
            if ($product) {
                $pdo->prepare("UPDATE products SET category_id=?, name=?, slug=?, sku=?, description=?, specifications=?, price=?, discount_price=?, stock=?, status=? WHERE id=? AND vendor_id=?")
                    ->execute([$cat, $name, $slug, $sku, $desc ?: null, $specs ?: null, $price, $discount, $stock, $status, $id, $vid]);
                $productId = $id;
            } else {
                $pdo->prepare("INSERT INTO products (vendor_id, category_id, name, slug, sku, description, specifications, price, discount_price, stock, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$vid, $cat, $name, $slug, $sku, $desc ?: null, $specs ?: null, $price, $discount, $stock, $status]);
                $productId = (int)$pdo->lastInsertId();
            }

            $savedImage = false;

            // File upload
            if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($_FILES['image']['tmp_name']);

                if (in_array($mime, ALLOWED_IMAGE_TYPES) && $_FILES['image']['size'] <= MAX_UPLOAD_SIZE) {
                    $ext = match ($mime) {
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif',
                        default      => 'jpg'
                    };
                    $fname = 'products/p' . $productId . '_' . time() . '.' . $ext;
                    $dest  = UPLOAD_PATH . '/' . $fname;

                    if (!is_dir(UPLOAD_PATH . '/products')) {
                        mkdir(UPLOAD_PATH . '/products', 0755, true);
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $pdo->prepare("DELETE FROM product_images WHERE product_id=? AND is_primary=1")->execute([$productId]);
                        $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?,?,1)")
                            ->execute([$productId, $fname]);
                        $savedImage = true;
                    }
                }
            }

            // Image URL (if no file uploaded)
            if (!$savedImage && $imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $pdo->prepare("DELETE FROM product_images WHERE product_id=? AND is_primary=1")->execute([$productId]);
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?,?,1)")
                    ->execute([$productId, $imageUrl]);
            }

            flash('success', $product ? 'Product updated' : 'Product created');
            redirect(BASE_URL . '/vendor/products.php');

        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$currentImage = null;
if ($product) {
    $imgSt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=? AND is_primary=1 LIMIT 1");
    $imgSt->execute([$product['id']]);
    $currentImage = $imgSt->fetchColumn() ?: null;
}

require __DIR__ . '/../includes/vendor_header.php';
?>

<h2 class="fw-bold mb-4"><?= $pageTitle ?></h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:720px">
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Product Name *</label>
      <input type="text" name="name" class="form-control" required
             value="<?= e($_POST['name'] ?? $product['name'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">SKU *</label>
      <input type="text" name="sku" class="form-control" required
             value="<?= e($_POST['sku'] ?? $product['sku'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Category *</label>
      <select name="category_id" class="form-select" required>
        <option value="">Select</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>"
          <?= ($_POST['category_id'] ?? $product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Price ($) *</label>
      <input type="number" step="0.01" name="price" class="form-control" required
             value="<?= e($_POST['price'] ?? $product['price'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Discount Price ($)</label>
      <input type="number" step="0.01" name="discount_price" class="form-control"
             value="<?= e($_POST['discount_price'] ?? $product['discount_price'] ?? '') ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Stock *</label>
      <input type="number" name="stock" class="form-control" required
             value="<?= e($_POST['stock'] ?? $product['stock'] ?? '0') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php foreach (['pending', 'active', 'inactive'] as $s): ?>
        <option value="<?= $s ?>"
          <?= ($_POST['status'] ?? $product['status'] ?? 'pending') === $s ? 'selected' : '' ?>>
          <?= ucfirst($s) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Upload Image</label>
      <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
      <div class="form-text">JPG, PNG, WebP, GIF · max 2 MB</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Or Image URL</label>
      <input type="url" name="image_url" class="form-control"
             placeholder="https://example.com/photo.jpg"
             value="<?= e($_POST['image_url'] ?? '') ?>">
      <div class="form-text">Paste a direct link from any website</div>
    </div>

    <?php if ($currentImage): ?>
    <div class="col-12">
      <label class="form-label">Current Image</label>
      <div>
        <img src="<?= e(product_image($currentImage)) ?>"
             alt="Current"
             style="width:100px;height:100px;object-fit:cover;border-radius:10px;border:1px solid #dee2e6">
      </div>
    </div>
    <?php endif; ?>

    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Specifications</label>
      <textarea name="specifications" class="form-control" rows="3"><?= e($_POST['specifications'] ?? $product['specifications'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">Save Product</button>
    <a href="<?= BASE_URL ?>/vendor/products.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
</div>

<?php require __DIR__ . '/../includes/vendor_footer.php'; ?>