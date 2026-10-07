<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5 class="brand mb-3" style="font-size:1.3rem">Pro<span style="color:#FF6B00">Market</span></h5>
        <p class="small mb-3" style="max-width:280px;line-height:1.7">
          Your trusted multi-vendor marketplace. Shop from verified vendors across Ethiopia with secure payments and fast delivery.
        </p>
        <div class="d-flex gap-2">
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-facebook"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-instagram"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <div class="col-6 col-md-2">
        <h5>Shop</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>/shop.php">All Products</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/shop.php?sort=popular">Best Sellers</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/shop.php?sort=newest">New Arrivals</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/shop.php">Deals</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-2">
        <h5>Account</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/auth/vendor-register.php">Sell on ProMarket</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/customer/orders.php">Track Order</a></li>
        </ul>
      </div>

      <div class="col-md-4">
        <h5>Contact</h5>
        <p class="small mb-2"><i class="bi bi-envelope me-2"></i><?= e(setting('marketplace_email', 'support@promarket.local')) ?></p>
        <p class="small mb-2"><i class="bi bi-telephone me-2"></i><?= e(setting('marketplace_phone', '+251 11 123 4567')) ?></p>
        <p class="small mb-3"><i class="bi bi-geo-alt me-2"></i><?= e(setting('marketplace_address', 'Addis Ababa, Ethiopia')) ?></p>
        <p class="small text-white-50 mb-0">Mon – Sat · 8:00 AM – 8:00 PM</p>
      </div>
    </div>

    <div class="bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
      <span>&copy; <?= date('Y') ?> ProMarket. All rights reserved.</span>
      <span class="small">Built with PHP · MySQL · Bootstrap 5</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>