<?php
require_once __DIR__.'/config/config.php';
require_login();
if (user_role() !== 'customer') { flash('warning','Only customers have a cart'); redirect(BASE_URL.'/index.php'); }
$pageTitle = 'Shopping Cart';
$uid = current_user()['id'];

// Update / remove
if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf()) {
  if (isset($_POST['update'])) {
    foreach (($_POST['qty'] ?? []) as $cartId => $qty) {
      $qty = max(0, (int)$qty);
      if ($qty === 0) {
        $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?")->execute([(int)$cartId, $uid]);
      } else {
        // stock check
        $st = $pdo->prepare("SELECT c.id, p.stock FROM cart c JOIN products p ON p.id=c.product_id WHERE c.id=? AND c.user_id=?");
        $st->execute([(int)$cartId, $uid]);
        $row = $st->fetch();
        if ($row) {
          $qty = min($qty, (int)$row['stock']);
          $pdo->prepare("UPDATE cart SET quantity=? WHERE id=?")->execute([$qty, $row['id']]);
        }
      }
    }
    flash('success','Cart updated');
    redirect(BASE_URL.'/cart.php');
  }
  if (isset($_POST['remove'])) {
    $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?")->execute([(int)$_POST['remove'], $uid]);
    flash('success','Item removed');
    redirect(BASE_URL.'/cart.php');
  }
}

$items = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.stock, p.sku, pi.image_path, v.business_name
  FROM cart c JOIN products p ON p.id=c.product_id JOIN vendors v ON v.id=p.vendor_id
  LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
  WHERE c.user_id=? AND p.status='active'");
$items->execute([$uid]); $items = $items->fetchAll();

$subtotal = 0;
foreach ($items as &$it) {
  $it['unit'] = current_price($it);
  $it['line'] = $it['unit'] * $it['quantity'];
  $subtotal += $it['line'];
}
unset($it);
$shipping = $subtotal >= (float)setting('shipping_free_threshold','5000') ? 0 : (float)setting('shipping_flat_rate','150');
$total = $subtotal + $shipping;

require __DIR__.'/includes/header.php';
?>
<div class="container py-4">
  <h2 class="fw-bold mb-4">Shopping Cart</h2>
  <?php if(!$items):?>
  <div class="empty-state"><i class="bi bi-cart-x"></i><p>Your cart is empty</p><a href="<?=BASE_URL?>/shop.php" class="btn btn-primary">Continue Shopping</a></div>
  <?php else:?>
  <form method="post">
    <?=csrf_field()?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>
              <tbody>
              <?php foreach($items as $it):?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <img src="<?=e(product_image($it['image_path']))?>" width="56" height="56" class="rounded object-fit-cover" style="object-fit:cover">
                    <div>
                      <a href="<?=BASE_URL?>/product.php?id=<?=$it['product_id']?>" class="fw-semibold text-dark"><?=e($it['name'])?></a>
                      <div class="small text-muted"><?=e($it['business_name'])?></div>
                      <?php if($it['quantity']>$it['stock']):?><div class="small text-danger">Only <?=$it['stock']?> left</div><?php endif;?>
                    </div>
                  </div>
                </td>
                <td><?=money($it['unit'])?></td>
                <td><input type="number" name="qty[<?=$it['id']?>]" value="<?=$it['quantity']?>" min="0" max="<?=$it['stock']?>" class="form-control form-control-sm" style="width:70px"></td>
                <td class="fw-semibold"><?=money($it['line'])?></td>
                <td><button type="submit" name="remove" value="<?=$it['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')"><i class="bi bi-trash"></i></button></td>
              </tr>
              <?php endforeach;?>
              </tbody>
            </table>
          </div>
        </div>
        <button type="submit" name="update" value="1" class="btn btn-outline-primary mt-3">Update Cart</button>
      </div>
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-4">
          <h5 class="fw-bold mb-3">Order Summary</h5>
          <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span><?=money($subtotal)?></span></div>
          <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?=$shipping?money($shipping):'Free'?></span></div>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>Total</span><span><?=money($total)?></span></div>
          <a href="<?=BASE_URL?>/checkout.php" class="btn btn-primary btn-lg w-100">Proceed to Checkout</a>
        </div>
      </div>
    </div>
  </form>
  <?php endif;?>
</div>
<?php require __DIR__.'/includes/footer.php';?>
