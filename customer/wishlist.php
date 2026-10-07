<?php
require_once __DIR__.'/../config/config.php';
require_role('customer');
$pageTitle='Wishlist';
$uid=current_user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['remove'])){
    $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$uid,(int)$_POST['remove']]);
    flash('success','Removed from wishlist');
    redirect(BASE_URL.'/customer/wishlist.php');
  }
  if(isset($_POST['to_cart'])){
    $pid=(int)$_POST['to_cart'];
    $chk=$pdo->prepare("SELECT id,quantity FROM cart WHERE user_id=? AND product_id=?");$chk->execute([$uid,$pid]);$ex=$chk->fetch();
    if($ex) $pdo->prepare("UPDATE cart SET quantity=quantity+1 WHERE id=?")->execute([$ex['id']]);
    else $pdo->prepare("INSERT INTO cart (user_id,product_id,quantity) VALUES (?,?,1)")->execute([$uid,$pid]);
    $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$uid,$pid]);
    flash('success','Moved to cart');
    redirect(BASE_URL.'/customer/wishlist.php');
  }
}
$items=$pdo->prepare("SELECT w.*, p.name, p.price, p.discount_price, p.stock, pi.image_path FROM wishlist w JOIN products p ON p.id=w.product_id LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1 WHERE w.user_id=? AND p.status='active'");
$items->execute([$uid]);$items=$items->fetchAll();
require __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
  <h2 class="fw-bold mb-4">My Wishlist</h2>
  <?php if(!$items):?><div class="empty-state"><i class="bi bi-heart"></i><p>Your wishlist is empty</p><a href="<?=BASE_URL?>/shop.php" class="btn btn-primary">Browse Products</a></div>
  <?php else:?>
  <div class="row g-3">
    <?php foreach($items as $it): $pr=current_price($it);?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="product-card">
        <a href="<?=BASE_URL?>/product.php?id=<?=$it['product_id']?>"><div class="img-wrap"><img src="<?=e(product_image($it['image_path']))?>" alt=""></div></a>
        <div class="card-body">
          <a href="<?=BASE_URL?>/product.php?id=<?=$it['product_id']?>" class="product-title"><?=e($it['name'])?></a>
          <div class="price my-1"><?=money($pr)?></div>
          <div class="d-flex gap-1">
            <form method="post"><?=csrf_field()?><button name="to_cart" value="<?=$it['product_id']?>" class="btn btn-sm btn-primary">To Cart</button></form>
            <form method="post"><?=csrf_field()?><button name="remove" value="<?=$it['product_id']?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <?php endif;?>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
