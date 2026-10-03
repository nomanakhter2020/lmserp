<?php
require_role('admin', 'teacher');
$me = user(); $title = 'Batches & classes'; $back = '?p=more';
$w = role('admin') ? '1=1' : '(b.teacher_id=' . (int)$me['id'] . ' OR c.teacher_id=' . (int)$me['id'] . ')';
$rows = all("SELECT b.*,c.title ctitle,u.name tname,(SELECT COUNT(*) FROM batch_students s WHERE s.batch_id=b.id) n FROM batches b JOIN courses c ON c.id=b.course_id LEFT JOIN users u ON u.id=b.teacher_id WHERE $w ORDER BY b.active DESC, b.start_time");
$today = WEEKDAYS[(int)date('N') - 1];
?>
<a class="btn block" href="?p=batch_edit">＋ New batch</a>
<?php $tod = array_filter($rows, fn($b) => $b['active'] && in_array($today, explode(',', $b['days']), true)); if ($tod): ?>
<h2>Today (<?= date('l') ?>)</h2>
<div class="list"><?php foreach ($tod as $b): $done = (int)val('SELECT COUNT(*) FROM attendance WHERE batch_id=? AND att_date=CURDATE()', [$b['id']]); ?>
  <a class="row" href="?p=attendance&id=<?= $b['id'] ?>"><div class="num">🕒</div><div class="grow"><b><?= e($b['name']) ?></b><small><?= batch_time($b) ?><?= $b['room'] ? ' · ' . e($b['room']) : '' ?> · <?= e($b['ctitle']) ?></small></div>
  <span class="pill <?= $done ? 'ok' : 'warn' ?>"><?= $done ? '✓ Marked' : 'Mark' ?></span></a>
<?php endforeach ?></div>
<?php endif ?>
<h2>All batches</h2>
<div class="list"><?php foreach ($rows as $b): ?>
  <a class="row <?= $b['active'] ? '' : 'locked' ?>" href="?p=batch&id=<?= $b['id'] ?>"><div class="grow"><b><?= e($b['name']) ?></b><small><?= e($b['ctitle']) ?> · <?= e(str_replace(',', ' ', $b['days'])) ?><?= batch_time($b) ? ' · ' . batch_time($b) : '' ?></small><small><?= $b['n'] ?> students<?= $b['tname'] ? ' · ' . e($b['tname']) : '' ?></small></div><span>›</span></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No batches yet. Create a batch for each class group (e.g. "IELTS Evening A, Mon-Wed-Fri 6–8 pm").</p><?php endif ?>
