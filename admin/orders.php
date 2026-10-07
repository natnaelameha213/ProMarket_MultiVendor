<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Orders';
$status=$_GET['status']??'';
$search=trim($_GET['q']??'');
$page=max(1,(int)($_GET['page']??1));
$where=['1=1'];$params=[];
if($status){$where[]='status=?';$params[]=$status;}
if($search){$where[]='(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)';$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
$ws=implode(' AND ',$where);
$cnt=$pdo->prepare("SELECT COUNT(*) FROM orders WHERE $ws");$cnt->execute($params);$total=(int)$cnt->fetchColumn();
$pag=paginate($total,$page,15);
$st=$pdo->prepare("SELECT * FROM orders WHERE $ws ORDER BY id DESC LIMIT {$pag['per_page']} OFFSET {$pag['offset']}");
$st->execute($params);$orders=$st->fetchAll();
$baseUrl=BASE_URL.'/admin/orders.php?'.http_build_query(array_filter(['status'=>$status,'q'=>$search])).'&';
require __DIR__.'/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <h2 class="fw-bold mb-0">Orders</h2>
  <form class="d-flex gap-2"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search..." value="<?=e($search)?>"><?php if($status):?><input type="hidden" name="status" value="<?=e($status)?>"><?php endif;?><button class="btn btn-sm btn-primary">Search</button></form>
</div>
<div class="mb-3 d-flex flex-wrap gap-1">
  <a href="? " class="btn btn-sm <?=!$status?'btn-primary':'btn-outline-secondary'?>">All</a>
  <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s):?>
  <a href="?status=<?=$s?>" class="btn btn-sm <?=$status===$s?'btn-primary':'btn-outline-secondary'?>"><?=ucfirst($s)?></a>
  <?php endforeach;?>
</div>
<div class="table-card"><div class="table-responsive"><table class="table">
<thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
<tbody>
<?php foreach($orders as $o):?>
<tr>
  <td class="fw-semibold"><?=e($o['order_number'])?></td>
  <td><?=e($o['customer_name'])?><div class="small text-muted"><?=e($o['customer_email'])?></div></td>
  <td><?=money($o['total'])?></td>
  <td><?=status_badge($o['payment_status'])?> <span class="small text-muted"><?=e($o['payment_method'])?></span></td>
  <td><?=status_badge($o['status'])?></td>
  <td class="small"><?=format_date($o['created_at'],'M d, Y H:i')?></td>
  <td><a href="<?=BASE_URL?>/admin/order-view.php?id=<?=$o['id']?>" class="btn btn-sm btn-outline-primary">View</a></td>
</tr>
<?php endforeach; if(!$orders):?><tr><td colspan="7" class="text-center text-muted py-4">No orders found</td></tr><?php endif;?>
</tbody></table></div></div>
<div class="mt-3"><?=pagination_html($pag,$baseUrl)?></div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
