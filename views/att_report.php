<?php
require_role('admin', 'teacher');
$b = one('SELECT b.*,c.title ctitle FROM batches b JOIN courses c ON c.id=b.course_id WHERE b.id=?', [$id]);
if (!$b || !can_manage_batch($b)) { echo '<p class="empty">Batch not found</p>'; return; }
$m = preg_match('/^\d{4}-\d{2}$/', (string)get('m')) ? get('m') : date('Y-m');
$title = 'Attendance report'; $back = "?p=batch&id=$id";
$dates = array_column(all('SELECT DISTINCT att_date FROM attendance WHERE batch_id=? AND DATE_FORMAT(att_date,"%Y-%m")=? ORDER BY att_date', [$id, $m]), 'att_date');
$sts = all('SELECT u.id,u.name FROM batch_students s JOIN users u ON u.id=s.user_id WHERE s.batch_id=? ORDER BY u.name', [$id]);
$map = [];
foreach (all('SELECT user_id,att_date,status FROM attendance WHERE batch_id=? AND DATE_FORMAT(att_date,"%Y-%m")=?', [$id, $m]) as $r) $map[$r['user_id']][$r['att_date']] = $r['status'];
?>
<div class="crumb"><?= e($b['name']) ?> · <?= e($b['ctitle']) ?></div>
<form class="search"><input type="hidden" name="p" value="att_report"><input type="hidden" name="id" value="<?= $id ?>"><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<?php if (!$dates): ?><p class="empty">No attendance marked in <?= date('F Y', strtotime("$m-01")) ?>.</p><?php return; endif ?>
<div class="card tablewrap"><table class="att">
  <tr><th>Student</th><?php foreach ($dates as $d): ?><th><?= date('d', strtotime($d)) ?></th><?php endforeach ?><th>%</th></tr>
  <?php foreach ($sts as $s): $p = 0; $t = 0; ?><tr><td><?= e($s['name']) ?></td>
    <?php foreach ($dates as $d): $v = $map[$s['id']][$d] ?? ''; if ($v) { $t++; if (in_array($v, ['P', 'L'], true)) $p++; } ?><td class="a-<?= $v ?>"><?= $v ?: '·' ?></td><?php endforeach ?>
    <td><b><?= $t ? round($p * 100 / $t) : '–' ?></b></td></tr><?php endforeach ?>
</table></div>
<p class="muted center" style="font-size:13px">P = Present · A = Absent · L = Late · E = Leave</p>
<button class="btn ghost block" onclick="window.print()">🖨 Print report</button>
