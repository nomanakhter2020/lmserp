<?php
$me = user(); $title = 'Hi, ' . explode(' ', $me['name'])[0];
$ann = all('SELECT a.*,c.title ct FROM announcements a LEFT JOIN courses c ON c.id=a.course_id
  WHERE a.course_id IS NULL OR a.course_id IN (SELECT course_id FROM enrollments WHERE user_id=?) OR a.course_id IN (SELECT id FROM courses WHERE teacher_id=?) OR ?
  ORDER BY a.id DESC LIMIT 3', [$me['id'], $me['id'], role('admin') ? 1 : 0]);

if (role('admin')):
  $m = date('Y-m');
  $s = [
    'Students' => val('SELECT COUNT(*) FROM users WHERE role="student" AND active=1'),
    'Teachers' => val('SELECT COUNT(*) FROM users WHERE role="teacher" AND active=1'),
    'Courses' => val('SELECT COUNT(*) FROM courses'),
    'Pending' => val('SELECT COUNT(*) FROM enrollments WHERE status="pending"'),
  ];
  $inc = (float)val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(paid_on,"%Y-%m")=?', [$m]);
  $exp = (float)val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(spent_on,"%Y-%m")=?', [$m]);
  $recent = all('SELECT p.*,u.name FROM payments p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 5');
  $pending = all('SELECT e.*,u.name,c.title,c.fee FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE e.status="pending" ORDER BY e.id DESC LIMIT 5');
?>
<?php require __DIR__ . '/_checklist.php'; ?>
<div class="hero">
  <div class="muted-l"><?= date('F Y') ?></div>
  <div class="big"><?= money($inc - $exp) ?></div><div class="muted-l">Net this month</div>
  <div class="split"><div><b><?= money($inc) ?></b><span>Fees in</span></div><div><b><?= money($exp) ?></b><span>Expenses</span></div></div>
</div>
<div class="stats"><?php foreach ($s as $k => $v): ?><div class="stat"><b><?= (int)$v ?></b><span><?= $k ?></span></div><?php endforeach ?></div>
<div class="quick">
  <a href="?p=batches">🗓️ Batches</a><a href="?p=course_edit">＋ Course</a><a href="?p=user_edit">＋ Person</a><a href="?p=fees#add">＋ Fee</a><a href="?p=expenses">＋ Expense</a>
</div>
<?php if ($np = (int)val('SELECT COUNT(*) FROM payment_requests WHERE status="pending"')): ?><a class="alert warn" href="?p=proofs" style="display:block">🧾 <?= $np ?> payment proof<?= $np > 1 ? 's' : '' ?> waiting for verification ›</a><?php endif ?>
<?php if ($odv = one('SELECT COUNT(*) n, COALESCE(SUM(amount-discount+late_fee),0) t FROM fee_vouchers WHERE status="unpaid" AND due_date<CURDATE()')) if ($odv['n']): ?><a class="alert err" href="?p=vouchers&f=overdue" style="display:block">📄 <?= $odv['n'] ?> overdue fee voucher<?= $odv['n'] > 1 ? 's' : '' ?> · <?= money($odv['t']) ?> ›</a><?php endif ?>
<?php if ($npo = (int)val('SELECT COUNT(*) FROM orders WHERE status="pending"')): ?><a class="alert warn" href="?p=orders&f=pending" style="display:block">🛒 <?= $npo ?> new shop order<?= $npo > 1 ? 's' : '' ?> to confirm ›</a><?php endif ?>
<?php if ($nr = (int)val('SELECT COUNT(*) FROM posts WHERE review="pending"')): ?><a class="alert warn" href="?p=posts&f=pending" style="display:block">✍️ <?= $nr ?> blog article<?= $nr > 1 ? 's' : '' ?> waiting for your approval ›</a><?php endif ?>
<?php if ($pending): ?>
<h2>Pending enrollments</h2>
<div class="list"><?php foreach ($pending as $r): ?>
  <a class="row" href="?p=user&id=<?= $r['user_id'] ?>"><div><b><?= e($r['name']) ?></b><small><?= e($r['title']) ?> · <?= money($r['fee']) ?></small></div><span class="pill warn">Pending</span></a>
<?php endforeach ?></div>
<?php endif ?>
<h2>Recent fees</h2>
<div class="list"><?php foreach ($recent as $r): ?>
  <div class="row"><div><b><?= e($r['name']) ?></b><small><?= e($r['paid_on']) ?> · <?= e($r['method']) ?></small></div><b class="pos"><?= money($r['amount']) ?></b></div>
<?php endforeach; if (!$recent): ?><p class="empty">No payments yet</p><?php endif ?></div>

<?php elseif (role('parent')): require __DIR__ . '/_parent_home.php'; ?>
<?php elseif (role('teacher')):
  $cs = all('SELECT c.*,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) st,(SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) ls FROM courses c WHERE teacher_id=? ORDER BY c.id DESC', [$me['id']]);
  $tot = array_sum(array_column($cs, 'st'));
?>
<div class="stats"><div class="stat"><b><?= count($cs) ?></b><span>My courses</span></div><div class="stat"><b><?= $tot ?></b><span>Students</span></div></div>
<?php $myp = one('SELECT SUM(published) live, SUM(review="pending") pend, SUM(review="rejected") rej, MAX(created_at) last FROM posts WHERE author_id=?', [$me['id']]); ?>
<div class="quick"><a href="?p=course_edit">＋ New course</a><a href="?p=post_edit">✍️ Write article</a><a href="?p=announcements">📣 Announce</a></div>
<?php if ((int)$myp['rej']): ?><a class="alert err" href="?p=posts" style="display:block">↩️ <?= (int)$myp['rej'] ?> article<?= $myp['rej'] > 1 ? 's' : '' ?> need changes — see admin notes ›</a>
<?php elseif (!$myp['last'] || strtotime($myp['last']) < strtotime('-7 days')): ?><a class="alert" href="?p=post_edit" style="display:block">✍️ Share your knowledge — write a blog article this week. It helps students find your courses on Google. ›</a><?php endif ?>
<?php require __DIR__ . '/_today.php'; ?>
<?php $learn = all('SELECT c.*,e.status FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.id DESC LIMIT 4', [$me['id']]); if ($learn): ?>
<div class="rowhead sec-t"><h2>My learning</h2><a href="?p=my">See all ›</a></div>
<div class="list"><?php foreach ($learn as $l): $pc = course_progress((int)$me['id'], (int)$l['id']); ?>
  <a class="row col" href="?p=course&id=<?= $l['id'] ?>"><div class="rowhead"><b><?= e($l['title']) ?></b><?php if ($l['status'] === 'pending'): ?><span class="pill warn">Pending</span><?php endif ?></div><div class="bar"><i style="width:<?= $pc ?>%"></i></div><small><?= $pc ?>% complete</small></a>
<?php endforeach ?></div>
<?php endif ?>
<h2>My courses (teaching)</h2>
<div class="grid"><?php foreach ($cs as $c): ?>
  <a class="ccard" href="?p=course&id=<?= $c['id'] ?>" style="--c:<?= e($c['color']) ?>"><div class="band<?= $c['cover'] ? ' img' : '' ?>" style="<?= cover_style($c) ?>"></div><b><?= e($c['title']) ?></b><small><?= $c['ls'] ?> lessons · <?= $c['st'] ?> students</small></a>
<?php endforeach; if (!$cs): ?><p class="empty">No courses yet. Create your first one.</p><?php endif ?></div>

<?php else:
  $cs = all('SELECT c.*,e.status FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.id DESC', [$me['id']]);
  $done = (int)val('SELECT COUNT(*) FROM progress WHERE user_id=?', [$me['id']]);
  $avg = val('SELECT ROUND(AVG(score*100/NULLIF(total,0))) FROM attempts WHERE user_id=?', [$me['id']]);
  $paid = (float)val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE user_id=?', [$me['id']]);
  $cont = null;
  foreach ($cs as $c) { if ($c['status'] === 'active' && course_progress((int)$me['id'], (int)$c['id']) < 100) { $cont = $c; break; } }
?>
<?php $attp = att_percent((int)$me['id']); ?>
<div class="stats"><div class="stat"><b><?= count($cs) ?></b><span>Courses</span></div><div class="stat"><b><?= $done ?></b><span>Lessons done</span></div><div class="stat"><b><?= $avg !== null ? $avg . '%' : '–' ?></b><span>Quiz avg</span></div><?php if ($attp !== null): ?><div class="stat"><b class="<?= $attp < 75 ? 'neg' : 'pos' ?>"><?= $attp ?>%</b><span>Attendance</span></div><?php endif ?></div>
<?php if ($uvn = one('SELECT COUNT(*) n, SUM(due_date<CURDATE()) od FROM fee_vouchers WHERE user_id=? AND status="unpaid"', [$me['id']])) if ($uvn['n']): ?><a class="alert <?= $uvn['od'] ? 'err' : 'warn' ?>" href="?p=fees" style="display:block">📄 You have <?= $uvn['n'] ?> unpaid fee voucher<?= $uvn['n'] > 1 ? 's' : '' ?><?= $uvn['od'] ? ' (' . $uvn['od'] . ' overdue)' : '' ?> ›</a><?php endif ?>
<?php require __DIR__ . '/_today.php'; ?>
<?php if ($cont): $pc = course_progress((int)$me['id'], (int)$cont['id']);
  $nx = val('SELECT l.id FROM lessons l LEFT JOIN progress p ON p.lesson_id=l.id AND p.user_id=? WHERE l.course_id=? AND p.lesson_id IS NULL ORDER BY l.sort,l.id LIMIT 1', [$me['id'], $cont['id']]); ?>
<a class="hero" href="<?= $nx ? "?p=lesson&id=$nx" : "?p=course&id={$cont['id']}" ?>" style="--c:<?= e($cont['color']) ?>">
  <div class="muted-l">Continue learning</div><div class="big sm"><?= e($cont['title']) ?></div>
  <div class="bar light"><i style="width:<?= $pc ?>%"></i></div><div class="muted-l"><?= $pc ?>% complete · Resume ›</div>
</a>
<?php endif ?>
<div class="quick"><a href="?p=shop">🛒 Shop books</a><a href="?p=courses">🔎 Browse courses</a><a href="?p=fees">💳 My fees (<?= money($paid) ?>)</a></div>
<?php endif ?>

<?php require __DIR__ . '/_teachers_strip.php'; ?>
<?php if ($ann): ?>
<h2>Announcements</h2>
<div class="list"><?php foreach ($ann as $a): ?>
  <div class="row col"><b><?= e($a['title']) ?></b><small><?= e($a['ct'] ?: 'Everyone') ?> · <?= date('d M', strtotime($a['created_at'])) ?></small><p><?= nl2br(e($a['body'])) ?></p></div>
<?php endforeach ?></div>
<?php endif ?>
