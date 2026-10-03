<?php
require_role('admin', 'teacher');
$u = one('SELECT * FROM users WHERE id=?', [$id]);
if (!$u) { echo '<p class="empty">Not found</p>'; return; }
$title = $u['name']; $back = '?p=users&role=' . $u['role'];
$me = user();
$ens = all('SELECT e.*,c.title,COALESCE(e.fee,c.fee) fee,c.teacher_id FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ' . (role('teacher') ? 'AND c.teacher_id=' . (int)$me['id'] : '') . ' ORDER BY e.id DESC', [$id]);
$pays = role('admin') ? all('SELECT p.*,c.title FROM payments p LEFT JOIN courses c ON c.id=p.course_id WHERE p.user_id=? ORDER BY p.paid_on DESC', [$id]) : [];
$paid = array_sum(array_column($pays, 'amount'));
$due = array_sum(array_map(fn($e) => (float)$e['fee'], $ens)) - $paid;
$atts = all('SELECT a.*,qz.title FROM attempts a JOIN quizzes qz ON qz.id=a.quiz_id WHERE a.user_id=? ORDER BY a.id DESC LIMIT 10', [$id]);
$courses = role('admin') ? all('SELECT id,title,fee FROM courses' . ($u['role'] === 'teacher' ? ' WHERE teacher_id IS NULL OR teacher_id<>' . (int)$u['id'] : '') . ' ORDER BY title') : [];
$wa = preg_replace('/\D/', '', $u['phone']); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1);
?>
<div class="card profile">
  <div class="avatar lg"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
  <div><b><?= e($u['name']) ?></b><small><?= ucfirst($u['role']) ?> · <?= e($u['email']) ?></small><small><?= e($u['phone']) ?></small></div>
</div>
<div class="quick">
  <?php if ($wa): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">💬 WhatsApp</a><a href="tel:<?= e($u['phone']) ?>">📞 Call</a><?php endif ?>
  <?php if (role('admin')): ?><a href="?p=user_edit&id=<?= $id ?>">✏️ Edit</a><?php endif ?>
  <?php if (role('admin') && $u['role'] !== 'student'): ?><a href="?p=tprofile&id=<?= $id ?>">🪪 CV profile</a><a href="?p=teacher&id=<?= $id ?>" target="_blank">🌐 View CV</a><?php endif ?>
</div>
<?php if (role('admin') && $u['role'] !== 'admin'): ?>
<div class="stats"><div class="stat"><b><?= money($paid) ?></b><span>Paid</span></div><div class="stat"><b class="<?= $due > 0 ? 'neg' : '' ?>"><?= money(max(0, $due)) ?></b><span>Balance due</span></div></div>
<?php endif ?>

<h2><?= $u['role'] === 'teacher' ? 'Enrolled as learner' : 'Courses' ?></h2>
<div class="list"><?php foreach ($ens as $e): $pc = course_progress($id, (int)$e['course_id']); ?>
  <div class="row col"><div class="rowhead"><a href="?p=course&id=<?= $e['course_id'] ?>"><b><?= e($e['title']) ?></b></a><span class="pill <?= $e['status'] === 'pending' ? 'warn' : ($e['status'] === 'completed' ? 'ok' : '') ?>"><?= ucfirst($e['status']) ?></span></div>
  <div class="bar"><i style="width:<?= $pc ?>%"></i></div><small><?= $pc ?>% · fee <?= money($e['fee']) ?></small>
  <?php if (role('admin') && $e['status'] === 'pending'): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="enroll_admin"><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="course_id" value="<?= $e['course_id'] ?>"><input type="hidden" name="status" value="active"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>"><button class="btn sm">Approve access</button></form>
  <?php endif ?></div>
<?php endforeach; if (!$ens): ?><p class="empty">Not enrolled</p><?php endif ?></div>

<?php if (role('admin') && $u['role'] !== 'admin'): ?>
<details class="card"><summary>＋ Enroll in a course</summary>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="enroll_admin"><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>">
  <select name="course_id"><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['title']) ?> (<?= money($c['fee']) ?>)</option><?php endforeach ?></select>
  <select name="status"><option value="active">Active</option><option value="pending">Pending</option></select>
  <button class="btn block">Enroll</button></form></details>

<h2>Fee payments</h2>
<details class="card" id="pay"><summary>＋ Record payment</summary>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="payment_add"><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>">
  <select name="course_id"><option value="">— General —</option><?php foreach ($ens as $e): ?><option value="<?= $e['course_id'] ?>"><?= e($e['title']) ?></option><?php endforeach ?></select>
  <div class="two"><input name="amount" type="number" min="1" placeholder="Amount (PKR)" required><input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <div class="two"><select name="method"><option>Cash</option><option>Bank</option><option>JazzCash</option><option>EasyPaisa</option></select><input name="note" placeholder="Note"></div>
  <label class="check"><input type="checkbox" name="activate" value="1" checked> Approve pending enrollment</label>
  <button class="btn block">Save payment</button></form></details>
<div class="list"><?php foreach ($pays as $p): ?>
  <div class="row"><div class="grow"><b><?= money($p['amount']) ?></b><small><?= e($p['paid_on']) ?> · <?= e($p['method']) ?> · <?= e($p['title'] ?: 'General') ?></small></div><a class="small" href="?p=receipt&id=<?= $p['id'] ?>">Receipt</a></div>
<?php endforeach ?></div>
<?php endif ?>

<?php $ub = all('SELECT b.id,b.name,c.title FROM batch_students s JOIN batches b ON b.id=s.batch_id JOIN courses c ON c.id=b.course_id WHERE s.user_id=?', [$id]); if ($ub): ?><h2>Batches & attendance</h2><div class="list"><?php foreach ($ub as $x): $ap = att_percent($id, (int)$x['id']); ?>
  <a class="row" href="?p=batch&id=<?= $x['id'] ?>"><div class="grow"><b><?= e($x['name']) ?></b><small><?= e($x['title']) ?></small></div><?php if ($ap !== null): ?><span class="pill <?= $ap >= 75 ? 'ok' : ($ap >= 50 ? 'warn' : 'err') ?>"><?= $ap ?>%</span><?php endif ?></a>
<?php endforeach ?></div><?php endif ?>
<?php if ($atts): ?><h2>Quiz attempts</h2><div class="list"><?php foreach ($atts as $a): ?>
  <div class="row"><div class="grow"><b><?= e($a['title']) ?></b><small><?= date('d M Y', strtotime($a['created_at'])) ?></small></div><span class="pill"><?= $a['score'] ?>/<?= $a['total'] ?></span></div>
<?php endforeach ?></div><?php endif ?>
