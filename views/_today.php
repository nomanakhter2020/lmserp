<?php
// Today's classes for the logged-in user (teacher: batches they teach; student: batches they are in)
$__me = user(); $__d = WEEKDAYS[(int)date('N') - 1];
$__rows = $__me['role'] === 'student'
  ? all('SELECT b.*,c.title ctitle FROM batch_students s JOIN batches b ON b.id=s.batch_id JOIN courses c ON c.id=b.course_id WHERE s.user_id=? AND b.active=1 ORDER BY b.start_time', [$__me['id']])
  : all('SELECT b.*,c.title ctitle FROM batches b JOIN courses c ON c.id=b.course_id WHERE b.active=1 AND (b.teacher_id=? OR (b.teacher_id IS NULL AND c.teacher_id=?)) ORDER BY b.start_time', [$__me['id'], $__me['id']]);
$__rows = array_filter($__rows, fn($b) => in_array($__d, explode(',', $b['days']), true));
if ($__rows): ?>
<h2>Today's classes</h2>
<div class="list"><?php foreach ($__rows as $b): ?>
  <?php if ($__me['role'] === 'student'): $st = val('SELECT status FROM attendance WHERE batch_id=? AND user_id=? AND att_date=CURDATE()', [$b['id'], $__me['id']]); ?>
  <div class="row"><div class="num">🕒</div><div class="grow"><b><?= e($b['ctitle']) ?></b><small><?= e($b['name']) ?><?= batch_time($b) ? ' · ' . batch_time($b) : '' ?><?= $b['room'] ? ' · ' . e($b['room']) : '' ?></small></div>
    <?php if ($st): ?><span class="pill <?= ATT[$st][1] ?>"><?= ATT[$st][0] ?></span><?php elseif ($b['meet_link']): ?><a class="btn sm" href="<?= e(safe_link($b['meet_link'])) ?>" target="_blank" rel="noopener">Join</a><?php endif ?></div>
  <?php else: $done = (int)val('SELECT COUNT(*) FROM attendance WHERE batch_id=? AND att_date=CURDATE()', [$b['id']]); ?>
  <a class="row" href="?p=attendance&id=<?= $b['id'] ?>"><div class="num">🕒</div><div class="grow"><b><?= e($b['name']) ?></b><small><?= e($b['ctitle']) ?><?= batch_time($b) ? ' · ' . batch_time($b) : '' ?><?= $b['room'] ? ' · ' . e($b['room']) : '' ?></small></div><span class="pill <?= $done ? 'ok' : 'warn' ?>"><?= $done ? '✓ Marked' : 'Mark attendance' ?></span></a>
  <?php endif ?>
<?php endforeach ?></div>
<?php endif;
