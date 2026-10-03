<?php
require_role('admin');
$title = 'Generate vouchers'; $back = '?p=vouchers';
$courses = all('SELECT c.id,c.title,c.fee,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id AND e.status<>"pending") n FROM courses c ORDER BY c.title');
$batches = all('SELECT b.id,b.name,c.title FROM batches b JOIN courses c ON c.id=b.course_id WHERE b.active=1 ORDER BY c.title,b.name');
$plans = all('SELECT p.*,c.title FROM fee_plans p JOIN courses c ON c.id=p.course_id ORDER BY p.active DESC,c.title');
$ep = (int)get('plan') ? one('SELECT * FROM fee_plans WHERE id=?', [(int)get('plan')]) : null;
$pf = $ep ?: ['id' => 0, 'course_id' => '', 'amount' => '', 'due_day' => 10, 'late_fee' => 0, 'generate_day' => 1, 'active' => 1];
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="voucher_generate">
  <h3>📄 One-time vouchers for a class</h3>
  <p class="muted" style="font-size:13px">Creates one voucher for every enrolled student (e.g. admission fee, exam fee, a specific month).</p>
  <label>Course<select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['title']) ?> (<?= $c['n'] ?> students)</option><?php endforeach ?></select></label>
  <?php if ($batches): ?><label>Only this batch <small>(optional)</small><select name="batch_id"><option value="">All enrolled students</option><?php foreach ($batches as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['title'] . ' — ' . $b['name']) ?></option><?php endforeach ?></select></label><?php endif ?>
  <label>Voucher title<input name="title" value="Tuition fee — <?= date('F Y') ?>" required></label>
  <div class="two"><label>Amount (PKR)<input name="amount" type="number" min="1" required></label><label>Due date<input name="due_date" type="date" value="<?= date('Y-m-10') ?>" required></label></div>
  <label>Late fine after due date (PKR)<input name="late_fee" type="number" min="0" value="0"></label>
  <button class="btn block">Generate vouchers</button>
</form>

<h2 id="plans">🔁 Monthly fee plans</h2>
<p class="muted" style="margin:0 4px 10px;font-size:13px">Vouchers are created automatically every month for all active students of the course. Student scholarships (%) are applied automatically.</p>
<div class="list"><?php foreach ($plans as $p): ?>
  <div class="row <?= $p['active'] ? '' : 'locked' ?>"><div class="grow"><b><?= e($p['title']) ?></b><small><?= money($p['amount']) ?>/month · due on <?= (int)$p['due_day'] ?> · fine <?= money($p['late_fee']) ?><?= $p['last_period'] ? ' · last: ' . date('M Y', strtotime($p['last_period'] . '-01')) : '' ?></small></div>
  <a class="x" href="?p=voucher_gen&plan=<?= $p['id'] ?>#planf">✏️</a>
  <form method="post" onsubmit="return confirm('Remove this plan?')"><?= csrf_field() ?><input type="hidden" name="a" value="plan_delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$plans): ?><p class="empty">No monthly plans yet.</p><?php endif ?></div>
<form method="post" class="card" id="planf"><?= csrf_field() ?><input type="hidden" name="a" value="plan_save"><input type="hidden" name="id" value="<?= (int)$pf['id'] ?>">
  <h3><?= $ep ? 'Edit plan' : '＋ New monthly plan' ?></h3>
  <label>Course<select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $pf['course_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label>
  <div class="two"><label>Monthly fee (PKR)<input name="amount" type="number" min="1" value="<?= e($pf['amount']) ?>" required></label><label>Late fine (PKR)<input name="late_fee" type="number" min="0" value="<?= e($pf['late_fee']) ?>"></label></div>
  <div class="two"><label>Create vouchers on day<input name="generate_day" type="number" min="1" max="28" value="<?= (int)$pf['generate_day'] ?>"></label><label>Due on day<input name="due_day" type="number" min="1" max="28" value="<?= (int)$pf['due_day'] ?>"></label></div>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $pf['active'] ? 'checked' : '' ?>> Active</label>
  <button class="btn block">Save plan</button>
</form>
