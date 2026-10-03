<?php
require_role('admin');
$title = 'Recurring expenses'; $back = '?p=expenses';
$cats = all('SELECT * FROM expense_categories ORDER BY name');
$rows = all('SELECT r.*,c.name cname,c.icon FROM recurring_expenses r LEFT JOIN expense_categories c ON c.id=r.category_id ORDER BY r.active DESC, r.next_date');
$edit = $id ? one('SELECT * FROM recurring_expenses WHERE id=?', [$id]) : null;
$f = $edit ?: ['title' => '', 'amount' => '', 'category_id' => null, 'frequency' => 'monthly', 'next_date' => date('Y-m-d'), 'active' => 1, 'note' => ''];
$fl = ['monthly' => 'Monthly', 'weekly' => 'Weekly', 'yearly' => 'Yearly'];
$monthly = 0; foreach ($rows as $r) if ($r['active']) $monthly += $r['frequency'] === 'weekly' ? $r['amount'] * 52 / 12 : ($r['frequency'] === 'yearly' ? $r['amount'] / 12 : $r['amount']);
?>
<div class="hero red"><div class="muted-l">Fixed costs per month (approx.)</div><div class="big"><?= money($monthly) ?></div><div class="muted-l">Due entries are added to Expenses automatically on their date.</div></div>

<form method="post" class="card" id="form"><?= csrf_field() ?><input type="hidden" name="a" value="recurring_save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? '✏️ Edit recurring expense' : '＋ New recurring expense' ?></h3>
  <input name="title" value="<?= e($f['title']) ?>" placeholder="e.g. Office rent, Teacher salary, Internet" required>
  <div class="two"><input name="amount" type="number" min="1" value="<?= e($f['amount']) ?>" placeholder="Amount (PKR)" required>
    <select name="frequency"><?php foreach ($fl as $k => $v): ?><option value="<?= $k ?>" <?= $f['frequency'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select></div>
  <select name="category_id"><option value="">Select category</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $f['category_id'] == $c['id'] ? 'selected' : '' ?>><?= $c['icon'] ?> <?= e($c['name']) ?></option><?php endforeach ?></select>
  <label><?= $edit ? 'Next due date' : 'First due date' ?><input name="next_date" type="date" value="<?= e($f['next_date']) ?>" required></label>
  <input name="note" value="<?= e($f['note']) ?>" placeholder="Note (optional)">
  <label class="check"><input type="checkbox" name="active" value="1" <?= $f['active'] ? 'checked' : '' ?>> Active</label>
  <button class="btn block">Save</button><?php if ($edit): ?><a class="btn ghost block" href="?p=recurring">Cancel</a><?php endif ?>
</form>

<div class="list"><?php foreach ($rows as $r): ?>
  <div class="row <?= $r['active'] ? '' : 'locked' ?>"><span class="mi"><?= $r['icon'] ?: '🔁' ?></span>
    <div class="grow"><b><?= e($r['title']) ?></b><small><?= $fl[$r['frequency']] ?> · <?= e($r['cname'] ?: 'Uncategorised') ?> · <?= $r['active'] ? 'next ' . date('d M Y', strtotime($r['next_date'])) : 'paused' ?></small></div>
    <b class="neg"><?= money($r['amount']) ?></b>
    <a class="x" href="?p=recurring&id=<?= $r['id'] ?>#form" aria-label="Edit">✏️</a>
    <form method="post" onsubmit="return confirm('Remove this recurring expense? Past entries stay.')"><?= csrf_field() ?><input type="hidden" name="a" value="recurring_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$rows): ?><p class="empty">No recurring expenses yet. Add rent, salaries, internet etc. once and they'll post every month automatically.</p><?php endif ?></div>
