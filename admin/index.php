<?php
require_once __DIR__.'/../config/config.php';
$pageTitle = 'Dashboard';

$stats = [
  'sales' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn(),
  'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
  'vendors' => (int)$pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn(),
  'customers' => (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
  'pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
  'completed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn(),
  'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= low_stock_threshold AND status='active'")->fetchColumn(),
];

$recentOrders = $pdo->query("SELECT id,order_number,customer_name,total,status,created_at FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
$recentVendors = $pdo->query("SELECT id,business_name,status,created_at FROM vendors ORDER BY id DESC LIMIT 5")->fetchAll();

// Chart data - last 7 days
$chartLabels = []; $chartSales = []; $chartOrders = [];
for ($i=6; $i>=0; $i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $chartLabels[] = date('M d', strtotime($d));
  $s = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=? AND status!='cancelled'");
  $s->execute([$d]); $chartSales[] = (float)$s->fetchColumn();
  $o = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?");
  $o->execute([$d]); $chartOrders[] = (int)$o->fetchColumn();
}

require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Dashboard</h2>
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Total Sales', money($stats['sales']), 'bi-currency-exchange', 'bg-primary'],
    ['Orders', $stats['orders'], 'bi-bag', 'bg-success'],
    ['Products', $stats['products'], 'bi-box-seam', 'bg-info'],
    ['Vendors', $stats['vendors'], 'bi-shop', 'bg-warning'],
    ['Customers', $stats['customers'], 'bi-people', 'bg-secondary'],
    ['Pending Orders', $stats['pending'], 'bi-hourglass-split', 'bg-danger'],
    ['Delivered', $stats['completed'], 'bi-check-circle', 'bg-success'],
    ['Low Stock', $stats['low_stock'], 'bi-exclamation-triangle', 'bg-warning'],
  ];
  foreach ($cards as $c):?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon <?=$c[3]?> bg-opacity-10 text-<?=str_replace('bg-','',$c[3])?>"><i class="bi <?=$c[2]?>"></i></div>
      <div><div class="stat-value"><?=$c[1]?></div><div class="stat-label"><?=$c[0]?></div></div>
    </div>
  </div>
  <?php endforeach;?>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3 p-3">
      <h5 class="fw-bold mb-3">Sales & Orders (Last 7 Days)</h5>
      <canvas id="salesChart" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-3 p-3">
      <h5 class="fw-bold mb-3">Recent Vendors</h5>
      <?php foreach($recentVendors as $v):?>
      <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <div><div class="fw-semibold small"><?=e($v['business_name'])?></div><div class="text-muted" style="font-size:.75rem"><?=format_date($v['created_at'])?></div></div>
        <?=status_badge($v['status'])?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>

<div class="table-card">
  <div class="p-3 border-bottom"><h5 class="fw-bold mb-0">Recent Orders</h5></div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach($recentOrders as $o):?>
      <tr>
        <td class="fw-semibold"><?=e($o['order_number'])?></td>
        <td><?=e($o['customer_name'])?></td>
        <td><?=money($o['total'])?></td>
        <td><?=status_badge($o['status'])?></td>
        <td class="small text-muted"><?=format_date($o['created_at'])?></td>
        <td><a href="<?=BASE_URL?>/admin/order-view.php?id=<?=$o['id']?>" class="btn btn-sm btn-outline-primary">View</a></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>
<script>
new Chart(document.getElementById('salesChart'),{
  type:'line',
  data:{
    labels:<?=json_encode($chartLabels)?>,
    datasets:[
      {label:'Sales (ETB)',data:<?=json_encode($chartSales)?>,borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,.1)',fill:true,tension:.3},
      {label:'Orders',data:<?=json_encode($chartOrders)?>,borderColor:'#198754',backgroundColor:'transparent',tension:.3,yAxisID:'y1'}
    ]
  },
  options:{responsive:true,scales:{y:{beginAtZero:true},y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false}}}}
});
</script>
<?php require __DIR__.'/../includes/admin_footer.php';?>
