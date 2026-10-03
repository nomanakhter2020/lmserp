<?php
require_role('admin');
$title = 'Expense categories'; $back = '?p=expenses';
$cats = all('SELECT c.*,(SELECT COUNT(*) FROM expenses e WHERE e.category_id=c.id) n,(SELECT COALESCE(SUM(amount),0) FROM expenses e WHERE e.category_id=c.id AND YEAR(e.spent_on)=YEAR(CURDATE())) yt FROM expense_categories c ORDER BY name');
?>
<form method="post" class="card inline"><?= csrf_field() ?><input type="hidden" name="a" value="expcat_add"><input name="icon" placeholder="🏷️" maxlength="4" style="width:64px;text-align:center"><input name="name" placeholder="New category" required><button class="btn sm">Add</button></form>
<div class="list"><?php foreach ($cats as $c): ?>
  <div class="row"><span class="mi"><?= $c['icon'] ?></span><div class="grow"><b><?= e($c['name']) ?></b><small><?= $c['n'] ?> entries · <?= money($c['yt']) ?> this year</small></div>
  <form method="post" onsubmit="return confirm('Delete category? Its expenses become Uncategorised.')"><?= csrf_field() ?><input type="hidden" name="a" value="expcat_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach ?></div>
