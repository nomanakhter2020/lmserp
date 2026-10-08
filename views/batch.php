<?php
require_role('admin', 'teacher');
$b = one('SELECT b.*,c.title ctitle,u.name tname FROM batches b JOIN courses c ON c.id=b.course_id LEFT JOIN users u ON u.id=b.teacher_id WHERE b.id=?', [$id]);
if (!$b || !can_manage_batch($b)) { echo '<p class="empty">Batch not found</p>'; return; }
$title = $b['name']; $back = '?p=batches';
$sts = all('SELECT u.id,u.name,u.phone,(SELECT COUNT(*) FROM attendance a WHERE a.batch_id=? AND a.user_id=u.id) t,(SELECT SUM(status IN ("P","L")) FROM attendance a WHERE a.batch_id=? AND a.user_id=u.id) p,(SELECT SUM(status="A") FROM attendance a WHERE a.batch_id=? AND a.user_id=u.id) ab FROM batch_students s JOIN users u ON u.id=s.user_id WHERE s.batch_id=? ORDER BY u.name', [$id, $id, $id, $id]);
$avail = all('SELECT u.id,u.name FROM enrollments e JOIN users u ON u.id=e.user_id WHERE e.course_id=? AND e.status<>"pending" AND u.id NOT IN (SELECT user_id FROM batch_students WHERE batch_id=?) ORDER BY u.name', [$b['course_id'], $id]);
$days = (int)val('SELECT COUNT(DISTINCT att_date) FROM attendance WHERE batch_id=?', [$id]);
$recent = all('SELECT att_date,SUM(status IN ("P","L")) p,COUNT(*) t FROM attendance WHERE batch_id=? GROUP BY att_date ORDER BY att_date DESC LIMIT 10', [$id]);
?>
<div class="hero"><div class="muted-l"><?= e($b['ctitle']) ?><?= $b['tname'] ? ' · ' . e($b['tname']) : '' ?></div><div class="big sm"><?= e($b['name']) ?></div>
  <div class="muted-l">📅 <?= e(str_replace(',', ' · ', $b['days'])) ?><?= batch_time($b) ? ' &nbsp; 🕒 ' . batch_time($b) : '' ?><?= $b['room'] ? ' &nbsp; 📍 ' . e($b['room']) : '' ?></div>
  <div class="split"><div><b><?= count($sts) ?></b><span>Students</span></div><div><b><?= $days ?></b><span>Classes held</span></div></div></div>
<div class="quick"><a href="?p=attendance&id=<?= $id ?>">✅ Mark attendance</a><a href="?p=batch_edit&id=<?= $id ?>">✏️ Edit</a><?php if ($b['meet_link']): ?><a href="<?= e(safe_link($b['meet_link'])) ?>" target="_blank" rel="noopener">🎥 Online class</a><?php endif ?><a href="?p=att_report&id=<?= $id ?>">📊 Monthly report</a></div>

<h2>Students</h2>
<div class="list"><?php foreach ($sts as $s): $pc = $s['t'] ? round($s['p'] * 100 / $s['t']) : null; ?>
  <div class="row"><div class="grow"><a href="?p=user&id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><small><?= $pc === null ? 'No attendance yet' : "$pc% attendance · " . (int)$s['ab'] . ' absent' ?></small></div>
  <?php if ($pc !== null): ?><span class="pill <?= $pc >= 75 ? 'ok' : ($pc >= 50 ? 'warn' : 'err') ?>"><?= $pc ?>%</span><?php endif ?>
  <form method="post" onsubmit="return confirm('Remove from batch?')"><?= csrf_field() ?><input type="hidden" name="a" value="batch_students"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="remove" value="<?= $s['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$sts): ?><p class="empty">No students in this batch yet.</p><?php endif ?></div>

<?php if ($avail): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="batch_students"><input type="hidden" name="id" value="<?= $id ?>">
  <h3>Add enrolled students</h3>
  <div class="checklist"><?php foreach ($avail as $a): ?><label class="check"><input type="checkbox" name="add[]" value="<?= $a['id'] ?>"> <?= e($a['name']) ?></label><?php endforeach ?></div>
  <div class="two"><button class="btn">Add selected</button><button class="btn ghost" name="add_all" value="1">Add all not in a batch</button></div>
</form>
<?php endif ?>

<?php if ($recent): ?><h2>Recent classes</h2><div class="list"><?php foreach ($recent as $r): ?>
  <a class="row" href="?p=attendance&id=<?= $id ?>&date=<?= $r['att_date'] ?>"><div class="grow"><b><?= date('D, d M Y', strtotime($r['att_date'])) ?></b><small><?= (int)$r['p'] ?> / <?= (int)$r['t'] ?> present</small></div><span>›</span></a>
<?php endforeach ?></div><?php endif ?>
