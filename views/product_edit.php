<?php
require_role('admin');
$p = $id ? one('SELECT * FROM products WHERE id=?', [$id]) : ['title' => '', 'description' => '', 'category' => 'Task Books', 'price' => '', 'compare_price' => '', 'image' => '', 'type' => 'physical', 'file' => '', 'stock' => '', 'course_id' => (int)get('course') ?: null, 'active' => 1];
if (!$p) exit('Not found');
$title = $id ? 'Edit product' : 'New product'; $back = '?p=products';
$courses = all('SELECT id,title FROM courses ORDER BY title');
$cats = array_unique(array_merge(['Task Books', 'Workbooks', 'Story Books', 'Learning Kits', 'Stationery', 'Worksheets (PDF)', 'Trainer Material'], array_column(all('SELECT DISTINCT category FROM products'), 'category')));
?>
<form method="post" class="card" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="a" value="product_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Product name<input name="title" value="<?= e($p['title']) ?>" required placeholder="e.g. Grade 2 Maths Task Book"></label>
  <label>Photo
    <div class="cover-pick" id="cprev" style="aspect-ratio:1/1;max-width:260px;<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"><span><?= $p['image'] ? 'Tap to change' : '📷 Tap to add photo' ?></span>
    <input type="file" name="image" accept="image/*" onchange="const f=this.files[0];if(f){const c=document.getElementById('cprev');c.style.backgroundImage='url('+URL.createObjectURL(f)+')';c.querySelector('span').textContent='Tap to change'}"></div></label>
  <div class="two"><label>Price (PKR)<input name="price" type="number" min="0" value="<?= e($p['price']) ?>" required></label><label>Original price <small>(optional, shows discount)</small><input name="compare_price" type="number" min="0" value="<?= e($p['compare_price']) ?>"></label></div>
  <div class="two"><label>Category<input name="category" list="pcats" value="<?= e($p['category']) ?>"><datalist id="pcats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach ?></datalist></label>
    <label>Type<select name="type" onchange="document.getElementById('dig').hidden=this.value!=='digital';document.getElementById('stk').hidden=this.value==='digital'"><option value="physical" <?= $p['type'] === 'physical' ? 'selected' : '' ?>>📚 Physical (delivered)</option><option value="digital" <?= $p['type'] === 'digital' ? 'selected' : '' ?>>📄 Digital (PDF download)</option></select></label></div>
  <div id="stk" <?= $p['type'] === 'digital' ? 'hidden' : '' ?>><label>Stock <small>(leave empty = unlimited)</small><input name="stock" type="number" min="0" value="<?= e($p['stock']) ?>"></label></div>
  <div id="dig" <?= $p['type'] !== 'digital' ? 'hidden' : '' ?>><label>PDF file <?= $p['file'] ? '<small>(uploaded ✓ — choose to replace)</small>' : '' ?><input type="file" name="file" accept="application/pdf,image/*"></label></div>
  <label>Required for course <small>(shows on the course page)</small><select name="course_id"><option value="">— Not linked —</option><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $p['course_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label>
  <label>Description<textarea name="description" rows="5"><?= e($p['description']) ?></textarea></label>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>> Show in shop</label>
  <button class="btn block">Save product</button>
</form>
<?php if ($id): ?><a class="btn ghost block" href="?p=product&id=<?= $id ?>">👁 View in shop</a>
<form method="post" onsubmit="return confirm('Hide this product?')"><?= csrf_field() ?><input type="hidden" name="a" value="product_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Remove from shop</button></form><?php endif ?>
