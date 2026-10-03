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
  <?php if (role('admin') && $u['role'] === 'student' && val('SELECT 1 FROM certificates ce JOIN courses c ON c.id=ce.course_id WHERE ce.user_id=? AND c.program="trainer" AND ce.revoked=0', [$id])): ?><form method="post" style="display:inline" onsubmit="return confirm('Make this certified trainer a teacher?')"><?= csrf_field() ?><input type="hidden" name="a" value="promote_trainer"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn sm">🎤 Promote to teacher</button></form><?php endif ?>
  <?php if (role('admin') && $u['role'] !== 'student'): ?><a href="?p=tprofile&id=<?= $id ?>">🪪 CV profile</a><a href="?p=teacher&id=<?= $id ?>" target="_blank">🌐 View CV</a><?php endif ?>
</div>
<?php if (role('admin') && $u['role'] !== 'admin'): ?>
<div class="stats"><div class="stat"><b><?= money($paid) ?></b><span>Paid</span></div><div class="stat"><b class="<?= $due > 0 ? 'neg' : '' ?>"><?= money(max(0, $due)) ?></b><span>Balance due</span></div></div>
<?php endif ?>

<h2><?= $u['role'] === 'teacher' ? 'Enrolled as learner' : 'Courses' ?></h2>
<div class="list"><?php foreach ($ens as $e): $pc = course_progress($id, (int)$e['course_id']); ?>
  <div class="row col"><div class="rowhead"><a href="?p=course&id=<?= $e['course_id'] ?>"><b><?= e($e['title']) ?></b></a><span class="pill <?= $e['status'] === 'pending' ? 'warn' : ($e['status'] === 'completed' ? 'ok' : '') ?>"><?= ucfirst($e['status']) ?></span></div>
  <div class="bar"><i style="width:<?= $pc ?>%"></i></div><small><?= $pc ?>% · fee <?= money($e['fee']) ?></small>
  <?php $ce = one('SELECT * FROM certificates WHERE user_id=? AND course_id=?', [$id, $e['course_id']]); if ($ce && !$ce['revoked']): ?>
    <div class="actions"><a class="btn sm ghost" href="?p=cert&c=<?= e($ce['code']) ?>" target="_blank">🎓 <?= e($ce['code']) ?></a><?php if (role('admin')): ?><form method="post" onsubmit="return confirm('Revoke certificate?')"><?= csrf_field() ?><input type="hidden" name="a" value="cert_revoke"><input type="hidden" name="id" value="<?= $ce['id'] ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>"><button class="btn sm danger">Revoke</button></form><?php endif ?></div>
  <?php elseif (can_manage_course(['teacher_id' => $e['teacher_id']]) && $e['status'] !== 'pending'): ?>
    <form method="post" class="inline" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="a" value="cert_issue"><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="course_id" value="<?= $e['course_id'] ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>"><select name="grade" style="margin:0"><option value="">Auto grade</option><option>Distinction</option><option>Merit</option><option>Pass</option></select><button class="btn sm">🎓 Issue certificate</button></form>
  <?php endif ?>
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

<?php $uv = all('SELECT * FROM fee_vouchers WHERE user_id=? AND status<>"cancelled" ORDER BY due_date DESC LIMIT 24', [$id]); ?>
<h2 id="vouchers">Fee vouchers</h2>
<div class="list"><?php foreach ($uv as $v): $od = voucher_overdue($v); ?>
  <a class="row" href="?p=voucher&id=<?= $v['id'] ?>"><div class="grow"><b><?= e($v['title']) ?></b><small><?= voucher_no($v) ?> · due <?= date('d M Y', strtotime($v['due_date'])) ?></small></div><b class="<?= $v['status'] === 'paid' ? 'pos' : ($od ? 'neg' : '') ?>"><?= money($v['status'] === 'paid' ? $v['paid_amount'] : voucher_total($v)) ?></b><span class="pill <?= $v['status'] === 'paid' ? 'ok' : ($od ? 'err' : 'warn') ?>"><?= $v['status'] === 'paid' ? 'Paid' : ($od ? 'Overdue' : 'Unpaid') ?></span></a>
<?php endforeach; if (!$uv): ?><p class="empty">No vouchers</p><?php endif ?></div>
<details class="card"><summary>＋ Create installment plan</summary>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="installments_create"><input type="hidden" name="user_id" value="<?= $id ?>">
  <select name="course_id"><option value="">— General —</option><?php foreach ($ens as $e): ?><option value="<?= $e['course_id'] ?>"><?= e($e['title']) ?> (<?= money($e['fee']) ?>)</option><?php endforeach ?></select>
  <div class="two"><input name="total" type="number" min="1" placeholder="Total amount" required><input name="count" type="number" min="2" max="24" value="3" placeholder="Installments"></div>
  <div class="two"><label>First due date<input name="first_due" type="date" value="<?= date('Y-m-d') ?>"></label><label>Late fine<input name="late_fee" type="number" min="0" value="0"></label></div>
  <button class="btn block">Create installments</button></form></details>
<?php if ($ens): ?><details class="card"><summary>🎓 Scholarship / discount</summary>
<?php foreach ($ens as $e): ?><form method="post" class="inline" style="margin-bottom:8px"><?= csrf_field() ?><input type="hidden" name="a" value="enroll_discount"><input type="hidden" name="id" value="<?= $e['id'] ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>#vouchers">
  <span class="grow" style="font-size:14px"><?= e($e['title']) ?></span><input name="discount" type="number" min="0" max="100" value="<?= (int)($e['discount'] ?? 0) ?>" style="width:80px"><span>%</span><button class="btn sm">Save</button></form><?php endforeach ?>
<p class="muted" style="font-size:12.5px">Applied automatically to new monthly vouchers for that course.</p></details><?php endif ?>
<h2>Fee payments</h2>
<details class="card" id="pay"><summary>＋ Record payment</summary>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="payment_add"><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="back" value="?p=user&id=<?= $id ?>">
  <select name="course_id"><option value="">— General —</option><?php foreach ($ens as $e): ?><option value="<?= $e['course_id'] ?>"><?= e($e['title']) ?></option><?php endforeach ?></select>
  <div class="two"><input name="amount" type="number" min="1" placeholder="Amount (PKR)" required><input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <div class="two"><select name="method"><option>Cash</option><option>Bank</option><option>JazzCash</option><option>EasyPaisa</option></select><input name="note" placeholder="Note"></div>
  <label class="check"><input type="checkbox" name="activate" value="1" checked> Approve pending enrollment</label>
  <button class="btn block">Save payment</button></form></details>
<div class="list"><?php foreach ($pays as $p): ?>
  <div class="row"><div class="grow"><b><?= money($p['amount']) ?></b><small><?= e($p['paid_on']) ?> · <?= e($p['method']) ?> · <?= e($p['title'] ?: ($p['note'] ?: 'General')) ?></small></div><a class="small" href="?p=receipt&id=<?= $p['id'] ?>">Receipt</a></div>
<?php endforeach ?></div>
<?php endif ?>

<?php $ub = all('SELECT b.id,b.name,c.title FROM batch_students s JOIN batches b ON b.id=s.batch_id JOIN courses c ON c.id=b.course_id WHERE s.user_id=?', [$id]); if ($ub): ?><h2>Batches & attendance</h2><div class="list"><?php foreach ($ub as $x): $ap = att_percent($id, (int)$x['id']); ?>
  <a class="row" href="?p=batch&id=<?= $x['id'] ?>"><div class="grow"><b><?= e($x['name']) ?></b><small><?= e($x['title']) ?></small></div><?php if ($ap !== null): ?><span class="pill <?= $ap >= 75 ? 'ok' : ($ap >= 50 ? 'warn' : 'err') ?>"><?= $ap ?>%</span><?php endif ?></a>
<?php endforeach ?></div><?php endif ?>
<?php if (role('admin') && $u['role'] === 'student'): $ps = all('SELECT u.*,pl.relation FROM parent_links pl JOIN users u ON u.id=pl.parent_id WHERE pl.student_id=?', [$id]); ?>
<h2>Parents / guardians</h2>
<div class="list"><?php foreach ($ps as $pp): ?><div class="row"><div class="grow"><b><?= e($pp['name']) ?></b><small><?= e($pp['relation']) ?> · <?= e($pp['email']) ?> · <?= e($pp['phone']) ?></small></div>
  <?php if ($pp['phone']): ?><a class="x" href="https://wa.me/<?= wa_num($pp['phone']) ?>" target="_blank" rel="noopener">💬</a><?php endif ?>
  <form method="post" onsubmit="return confirm('Unlink parent?')"><?= csrf_field() ?><input type="hidden" name="a" value="parent_unlink"><input type="hidden" name="parent_id" value="<?= $pp['id'] ?>"><input type="hidden" name="student_id" value="<?= $id ?>"><button class="x">✕</button></form></div><?php endforeach; if (!$ps): ?><p class="empty">No parent linked</p><?php endif ?></div>
<details class="card"><summary>＋ Add parent login</summary><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="parent_add"><input type="hidden" name="student_id" value="<?= $id ?>">
  <div class="two"><input name="name" placeholder="Parent name" required><select name="relation"><option>Father</option><option>Mother</option><option>Guardian</option></select></div>
  <div class="two"><input name="email" type="email" placeholder="Email (login)" required><input name="phone" placeholder="Phone / WhatsApp"></div>
  <input name="password" placeholder="Password (leave blank to auto-generate)">
  <p class="muted" style="font-size:12.5px">If this email already has a parent account (e.g. siblings), the student is simply linked to it.</p>
  <button class="btn block">Create / link parent</button></form></details>
<?php endif ?>
<?php if ($atts): ?><h2>Quiz attempts</h2><div class="list"><?php foreach ($atts as $a): ?>
  <div class="row"><div class="grow"><b><?= e($a['title']) ?></b><small><?= date('d M Y', strtotime($a['created_at'])) ?></small></div><span class="pill"><?= $a['score'] ?>/<?= $a['total'] ?></span></div>
<?php endforeach ?></div><?php endif ?>
