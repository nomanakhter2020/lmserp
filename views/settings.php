<?php
require_role('admin');
$title = 'Settings'; $back = '?p=more';
$cats = all('SELECT c.*,(SELECT COUNT(*) FROM courses WHERE category_id=c.id) n FROM categories c ORDER BY name');
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="settings_save">
  <label>Institute name<input name="institute" value="<?= e(setting('institute')) ?>" required></label>
  <label>Contact phone<input name="phone" value="<?= e(setting('phone')) ?>"></label>
  <input type="hidden" name="allow_register" value="0"><label class="check"><input type="checkbox" name="allow_register" value="1" <?= setting('allow_register', '1') === '1' ? 'checked' : '' ?>> Students can sign up themselves</label>
  <input type="hidden" name="paid_needs_approval" value="0"><label class="check"><input type="checkbox" name="paid_needs_approval" value="1" <?= setting('paid_needs_approval', '1') === '1' ? 'checked' : '' ?>> Paid courses need fee approval before access</label>
  <button class="btn block">Save settings</button></form>
<h2>Categories</h2>
<div class="list"><?php foreach ($cats as $c): ?>
  <div class="row"><b class="grow"><?= e($c['name']) ?></b><small><?= $c['n'] ?> courses</small>
  <form method="post" onsubmit="return confirm('Delete category?')"><?= csrf_field() ?><input type="hidden" name="a" value="category_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach ?></div>
<form method="post" class="card inline"><?= csrf_field() ?><input type="hidden" name="a" value="category_add"><input name="name" placeholder="New category" required><button class="btn sm">Add</button></form>
