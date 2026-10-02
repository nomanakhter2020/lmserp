<?php
require_role('admin');
$title = 'Expenses'; $back = '?p=more';
$m = (string)get('m', date('Y-m'));
$rows = all('SELECT * FROM expenses WHERE DATE_FORMAT(spent_on,"%Y-%m")=? ORDER BY spent_on DESC,id DESC', [$m]);
?>
<form class="search"><input type="hidden" name="p" value="expenses"><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<div class="hero red"><div class="muted-l">Spent in <?= date('F Y', strtotime("$m-01")) ?></div><div class="big"><?= money(array_sum(array_column($rows, 'amount'))) ?></div></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="expense_add">
  <input name="title" placeholder="What for? (rent, salary, ads…)" required>
  <div class="two"><input name="amount" type="number" min="1" placeholder="Amount (PKR)" required><input name="spent_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <button class="btn block">Add expense</button></form>
<div class="list"><?php foreach ($rows as $r): ?>
  <div class="row"><div class="grow"><b><?= e($r['title']) ?></b><small><?= e($r['spent_on']) ?></small></div><b class="neg"><?= money($r['amount']) ?></b>
  <form method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="a" value="expense_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$rows): ?><p class="empty">No expenses this month</p><?php endif ?></div>
