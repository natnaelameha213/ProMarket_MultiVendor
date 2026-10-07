<?php
require_once __DIR__.'/../config/config.php';
$pageTitle='Categories';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf()){
  if(isset($_POST['add'])){
    $name=trim($_POST['name']??'');
    if($name){
      $slug=slugify($name);
      try{$pdo->prepare("INSERT INTO categories (name,slug,description,status) VALUES (?,?,?,?)")->execute([$name,$slug,trim($_POST['description']??'')?:null,'active']);flash('success','Category added');}
      catch(Exception $e){$error='Category already exists or error occurred';}
    }
  }
  if(isset($_POST['delete'])){
    $id=(int)$_POST['delete'];
    $used=(int)db_q("SELECT COUNT(*) FROM products WHERE category_id=?",[$id])->fetchColumn();
    if($used){flash('danger',"Cannot delete: $used products use this category");}
    else{$pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);flash('success','Category deleted');}
    redirect(BASE_URL.'/admin/categories.php');
  }
  if(isset($_POST['toggle'])){
    $pdo->prepare("UPDATE categories SET status=IF(status='active','inactive','active') WHERE id=?")->execute([(int)$_POST['toggle']]);
    flash('success','Status updated');redirect(BASE_URL.'/admin/categories.php');
  }
}
$cats=$pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) as product_count FROM categories c ORDER BY c.sort_order,c.name")->fetchAll();
require __DIR__.'/../includes/admin_header.php';
?>
<h2 class="fw-bold mb-4">Categories</h2>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="form-card">
      <h5 class="fw-bold mb-3">Add Category</h5>
      <form method="post"><?=csrf_field()?>
        <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <button name="add" value="1" class="btn btn-primary w-100">Add Category</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="table-card"><div class="table-responsive"><table class="table">
      <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($cats as $c):?>
      <tr>
        <td class="fw-semibold"><?=e($c['name'])?></td>
        <td class="small text-muted"><?=e($c['slug'])?></td>
        <td><?=$c['product_count']?></td>
        <td><?=status_badge($c['status'])?></td>
        <td class="d-flex gap-1">
          <form method="post"><?=csrf_field()?><button name="toggle" value="<?=$c['id']?>" class="btn btn-sm btn-outline-secondary"><?=$c['status']==='active'?'Deactivate':'Activate'?></button></form>
          <form method="post" onsubmit="return confirm('Delete?')"><?=csrf_field()?><button name="delete" value="<?=$c['id']?>" class="btn btn-sm btn-outline-danger">Delete</button></form>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div></div>
  </div>
</div>
<?php require __DIR__.'/../includes/admin_footer.php';?>
