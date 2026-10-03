<?php
require_role('admin');
$title = 'Expenses'; $back = '?p=more';
$m = preg_match('/^\d{4}-\d{2}$/', (string)get('m')) ? get('m') : date('Y-m');
$cat = (int)get('cat');
$cats = all('SELECT * FROM expense_categories ORDER BY name');
$w = 'DATE_FORMAT(e.spent_on,"%Y-%m")=?'; $pr = [$m];
if ($cat) { $w .= ' AND e.category_id=?'; $pr[] = $cat; }
$rows = all("SELECT e.*,c.name cname,c.icon FROM expenses e LEFT JOIN expense_categories c ON c.id=e.category_id WHERE $w ORDER BY e.spent_on DESC,e.id DESC", $pr);
$total = array_sum(array_column($rows, 'amount'));
$bycat = all('SELECT COALESCE(c.name,"Uncategorised") name,COALESCE(c.icon,"💸") icon,c.id,SUM(e.amount) t FROM expenses e LEFT JOIN expense_categories c ON c.id=e.category_id WHERE DATE_FORMAT(e.spent_on,"%Y-%m")=? GROUP BY c.id ORDER BY t DESC', [$m]);
$mt = array_sum(array_column($bycat, 't')) ?: 1;
$rec = (float)val('SELECT COALESCE(SUM(CASE frequency WHEN "weekly" THEN amount*52/12 WHEN "yearly" THEN amount/12 ELSE amount END),0) FROM recurring_expenses WHERE active=1');
$nrec = (int)val('SELECT COUNT(*) FROM recurring_expenses WHERE active=1');
?>
<form class="search"><input type="hidden" name="p" value="expenses"><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<div class="hero red"><div class="muted-l">Spent in <?= date('F Y', strtotime("$m-01")) ?><?= $cat ? ' · filtered' : '' ?></div><div class="big"><?= money($total) ?></div>
  <div class="split"><div><b><?= count($rows) ?></b><span>Entries</span></div><div><b><?= money($rec) ?></b><span>Recurring / month</span></div></div></div>
<div class="quick"><a href="?p=recurring">🔁 Recurring (<?= $nrec ?>)</a><a href="?p=expense_cats">🏷️ Categories</a><a href="#add">＋ Add expense</a></div>

<?php if ($bycat): ?>
<h2>By category</h2>
<div class="card catbars"><?php foreach ($bycat as $b): ?>
  <a href="?p=expenses&m=<?= e($m) ?>&cat=<?= (int)$b['id'] ?>" class="cb <?= $cat && $cat == $b['id'] ? 'on' : '' ?>">
    <div class="cb-top"><span><?= $b['icon'] ?> <?= e($b['name']) ?></span><b><?= money($b['t']) ?></b></div>
    <div class="bar"><i style="width:<?= round($b['t'] / $mt * 100) ?>%;background:var(--bad)"></i></div></a>
<?php endforeach ?><?php if ($cat): ?><a class="small" href="?p=expenses&m=<?= e($m) ?>">✕ Clear filter</a><?php endif ?></div>
<?php endif ?>

<form method="post" class="card" id="add"><?= csrf_field() ?><input type="hidden" name="a" value="expense_add">
  <h3>＋ Add expense</h3>
  <input name="title" placeholder="What for? (rent, salary, ads…)" required>
  <div class="two"><input name="amount" type="number" min="1" placeholder="Amount (PKR)" required><input name="spent_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <select name="category_id"><option value="">Select category</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= $c['icon'] ?> <?= e($c['name']) ?></option><?php endforeach ?></select>
  <input name="note" placeholder="Note (optional)">
  <label class="check"><input type="checkbox" name="make_recurring" value="1" onchange="document.getElementById('freq').hidden=!this.checked"> 🔁 Repeat this expense</label>
  <div id="freq" hidden><select name="frequency"><option value="monthly">Every month</option><option value="weekly">Every week</option><option value="yearly">Every year</option></select></div>
  <button class="btn block">Save expense</button></form>

<div class="list"><?php foreach ($rows as $r): ?>
  <div class="row"><span class="mi"><?= $r['icon'] ?: '💸' ?></span><div class="grow"><b><?= e($r['title']) ?><?= $r['recurring_id'] ? ' <span class="pill">🔁</span>' : '' ?></b><small><?= e($r['spent_on']) ?> · <?= e($r['cname'] ?: 'Uncategorised') ?><?= $r['note'] && !$r['recurring_id'] ? ' · ' . e($r['note']) : '' ?></small></div><b class="neg"><?= money($r['amount']) ?></b>
  <form method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="a" value="expense_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$rows): ?><p class="empty">No expenses this month</p><?php endif ?></div>
