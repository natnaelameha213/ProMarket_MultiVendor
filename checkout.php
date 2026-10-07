<?php
require_once __DIR__.'/config/config.php';
require_login();
if (user_role() !== 'customer') { flash('warning','Customers only'); redirect(BASE_URL.'/index.php'); }
$pageTitle = 'Checkout';
$uid = current_user()['id'];
$cid = get_customer_id();

$items = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.stock, p.sku, p.vendor_id, v.business_name
  FROM cart c JOIN products p ON p.id=c.product_id JOIN vendors v ON v.id=p.vendor_id
  WHERE c.user_id=? AND p.status='active'");
$items->execute([$uid]); $items = $items->fetchAll();
if (!$items) { flash('warning','Cart is empty'); redirect(BASE_URL.'/cart.php'); }

$subtotal = 0;
foreach ($items as &$it) {
  if ($it['quantity'] > $it['stock']) { flash('danger',$it['name'].' has insufficient stock'); redirect(BASE_URL.'/cart.php'); }
  $it['unit'] = current_price($it);
  $it['line'] = $it['unit'] * $it['quantity'];
  $subtotal += $it['line'];
}
unset($it);

$shipping = $subtotal >= (float)setting('shipping_free_threshold','5000') ? 0 : (float)setting('shipping_flat_rate','150');
$discount = 0;
$couponCode = '';

// Apply coupon
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['apply_coupon']) && verify_csrf()) {
  $code = strtoupper(trim($_POST['coupon_code']??''));
  $cst = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND status='active' AND start_date<=CURDATE() AND expiry_date>=CURDATE()");
  $cst->execute([$code]); $coupon = $cst->fetch();
  if (!$coupon) { flash('danger','Invalid or expired coupon'); }
  elseif ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) { flash('danger','Coupon usage limit reached'); }
  elseif ($subtotal < $coupon['min_order_amount']) { flash('danger','Minimum order amount not met'); }
  else {
    if ($coupon['discount_type']==='percentage') {
      $discount = $subtotal * ($coupon['discount_value']/100);
      if ($coupon['max_discount']) $discount = min($discount, (float)$coupon['max_discount']);
    } else {
      $discount = min((float)$coupon['discount_value'], $subtotal);
    }
    $_SESSION['coupon'] = ['code'=>$code,'discount'=>$discount,'id'=>$coupon['id']];
    flash('success','Coupon applied!');
  }
  redirect(BASE_URL.'/checkout.php');
}

if (!empty($_SESSION['coupon'])) {
  $couponCode = $_SESSION['coupon']['code'];
  $discount = $_SESSION['coupon']['discount'];
}
$total = max(0, $subtotal - $discount + $shipping);

$user = current_user();
$addr = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? AND is_default=1 LIMIT 1");
$addr->execute([$uid]); $defaultAddr = $addr->fetch();

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order']) && verify_csrf()) {
  $name = trim($_POST['full_name']??'');
  $email = trim($_POST['email']??'');
  $phone = trim($_POST['phone']??'');
  $address = trim($_POST['address']??'');
  $city = trim($_POST['city']??'');
  $country = trim($_POST['country']??'Ethiopia');
  $payment = $_POST['payment_method']??'cod';
  $notes = trim($_POST['notes']??'');

  if (!$name||!$email||!$phone||!$address||!$city) {
    $error = 'Please fill all required fields.';
  } else {
    try {
      $pdo->beginTransaction();
      $orderNum = generate_order_number();
      $payStatus = ($payment==='cod') ? 'pending' : 'pending';
      $st = $pdo->prepare("INSERT INTO orders (order_number,customer_id,customer_name,customer_email,customer_phone,shipping_address,shipping_city,shipping_country,subtotal,discount_amount,shipping_amount,total,coupon_code,payment_method,payment_status,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$orderNum,$cid,$name,$email,$phone,$address,$city,$country,$subtotal,$discount,$shipping,$total,$couponCode?:null,$payment,$payStatus,'pending',$notes?:null]);
      $orderId = (int)$pdo->lastInsertId();

      $itemSt = $pdo->prepare("INSERT INTO order_items (order_id,product_id,vendor_id,product_name,product_sku,quantity,unit_price,total_price,status) VALUES (?,?,?,?,?,?,?,?,?)");
      $stockSt = $pdo->prepare("UPDATE products SET stock=stock-?, sales_count=sales_count+? WHERE id=? AND stock>=?");
      $logSt = $pdo->prepare("INSERT INTO inventory_log (product_id,vendor_id,change_type,quantity_change,quantity_before,quantity_after,reference_type,reference_id) VALUES (?,?,?,?,?,?,?,?)");

      foreach ($items as $it) {
        $itemSt->execute([$orderId,$it['product_id'],$it['vendor_id'],$it['name'],$it['sku'],$it['quantity'],$it['unit'],$it['line'],'pending']);
        // Reduce stock
        $before = (int)$it['stock'];
        $stockSt->execute([$it['quantity'],$it['quantity'],$it['product_id'],$it['quantity']]);
        if ($stockSt->rowCount()===0) throw new Exception('Stock update failed for '.$it['name']);
        $logSt->execute([$it['product_id'],$it['vendor_id'],'sale',-$it['quantity'],$before,$before-$it['quantity'],'order',$orderId]);
      }

      // Coupon usage
      if (!empty($_SESSION['coupon']['id'])) {
        $pdo->prepare("UPDATE coupons SET used_count=used_count+1 WHERE id=?")->execute([$_SESSION['coupon']['id']]);
      }

      // Clear cart
      $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$uid]);
      unset($_SESSION['coupon']);

      // Notifications
      $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
      if ($adminId) notify((int)$adminId,'order','New Order',$orderNum.' placed by '.$name, BASE_URL.'/admin/orders.php?id='.$orderId);
      notify($uid,'order','Order Confirmed','Your order '.$orderNum.' has been received.', BASE_URL.'/customer/orders.php');

      // Vendor notifications
      $vendorIds = array_unique(array_column($items,'vendor_id'));
      foreach ($vendorIds as $vid) {
        $vu = $pdo->prepare("SELECT user_id FROM vendors WHERE id=?"); $vu->execute([$vid]);
        $vuid = $vu->fetchColumn();
        if ($vuid) notify((int)$vuid,'order','New Order','You have a new order: '.$orderNum, BASE_URL.'/vendor/orders.php');
      }

      $pdo->commit();
      flash('success','Order placed successfully! Order #'.$orderNum);
      redirect(BASE_URL.'/customer/order-view.php?id='.$orderId);
    } catch (Exception $e) {
      $pdo->rollBack();
      $error = 'Order failed: '.$e->getMessage();
    }
  }
}

require __DIR__.'/includes/header.php';
?>
<div class="container py-4">
  <h2 class="fw-bold mb-4">Checkout</h2>
  <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
  <form method="post">
    <?=csrf_field()?>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="form-card mb-4">
          <h5 class="fw-bold mb-3">Shipping Information</h5>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?=e($_POST['full_name']??$defaultAddr['full_name']??$user['name'])?>"></div>
            <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required value="<?=e($_POST['phone']??$defaultAddr['phone']??$user['phone']??'')?>"></div>
            <div class="col-12"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?=e($_POST['email']??$user['email'])?>"></div>
            <div class="col-12"><label class="form-label">Address *</label><input type="text" name="address" class="form-control" required value="<?=e($_POST['address']??$defaultAddr['address_line1']??'')?>"></div>
            <div class="col-md-6"><label class="form-label">City *</label><input type="text" name="city" class="form-control" required value="<?=e($_POST['city']??$defaultAddr['city']??'Addis Ababa')?>"></div>
            <div class="col-md-6"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="<?=e($_POST['country']??'Ethiopia')?>"></div>
            <div class="col-12"><label class="form-label">Order Notes</label><textarea name="notes" class="form-control" rows="2"><?=e($_POST['notes']??'')?></textarea></div>
          </div>
        </div>
        <div class="form-card">
          <h5 class="fw-bold mb-3">Payment Method</h5>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
            <label class="form-check-label" for="cod"><strong>Cash on Delivery</strong> — Pay when you receive</label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank_transfer">
            <label class="form-check-label" for="bank"><strong>Bank Transfer</strong> — Transfer to our account</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="mobile" value="mobile_money">
            <label class="form-check-label" for="mobile"><strong>Mobile Money</strong> — Telebirr / CBE Birr</label>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 p-4 sticky-top" style="top:80px">
          <h5 class="fw-bold mb-3">Order Summary</h5>
          <?php foreach($items as $it):?>
          <div class="d-flex justify-content-between small mb-2">
            <span><?=e($it['name'])?> × <?=$it['quantity']?></span>
            <span><?=money($it['line'])?></span>
          </div>
          <?php endforeach;?>
          <hr>
          <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span><?=money($subtotal)?></span></div>
          <?php if($discount>0):?><div class="d-flex justify-content-between mb-1 text-success"><span>Discount (<?=e($couponCode)?>)</span><span>-<?=money($discount)?></span></div><?php endif;?>
          <div class="d-flex justify-content-between mb-1"><span>Shipping</span><span><?=$shipping?money($shipping):'Free'?></span></div>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>Total</span><span><?=money($total)?></span></div>
          <div class="input-group mb-3">
            <input type="text" name="coupon_code" class="form-control" placeholder="Coupon code" value="<?=e($couponCode)?>">
            <button type="submit" name="apply_coupon" value="1" class="btn btn-outline-secondary">Apply</button>
          </div>
          <button type="submit" name="place_order" value="1" class="btn btn-primary btn-lg w-100">Place Order</button>
        </div>
      </div>
    </div>
  </form>
</div>
<?php require __DIR__.'/includes/footer.php';?>
