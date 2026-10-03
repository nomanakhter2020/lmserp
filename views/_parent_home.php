<?php
$kids = my_children();
if (!$kids): ?><p class="empty">No student is linked to your account yet. Please contact the institute.</p><?php return; endif;
foreach ($kids as $k): $kid = (int)$k['id'];
  $att = att_percent($kid);
  $cs = all('SELECT c.id,c.title,e.status FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=?', [$kid]);
  $vu = one('SELECT COUNT(*) n, COALESCE(SUM(amount-discount),0) t, SUM(due_date<CURDATE()) od FROM fee_vouchers WHERE user_id=? AND status="unpaid"', [$kid]);
  $pend = (int)val('SELECT COUNT(*) FROM assignments a JOIN enrollments e ON e.course_id=a.course_id AND e.user_id=? LEFT JOIN submissions s ON s.assignment_id=a.id AND s.user_id=? WHERE (s.id IS NULL OR (s.answer="" AND s.file="")) AND (a.due_at IS NULL OR a.due_at>NOW())', [$kid, $kid]);
  $lastEx = one('SELECT e.id,e.title FROM exams e JOIN enrollments en ON en.course_id=e.course_id AND en.user_id=? WHERE e.published=1 ORDER BY e.exam_date DESC, e.id DESC LIMIT 1', [$kid]);
  $lr = $lastEx ? (exam_results((int)$lastEx['id'])[$kid] ?? null) : null;
  $absToday = val('SELECT COUNT(*) FROM attendance WHERE user_id=? AND att_date=CURDATE() AND status="A"', [$kid]);
?>
<div class="card kid">
  <div class="profile"><div class="avatar lg"><?= e(mb_strtoupper(mb_substr($k['name'], 0, 1))) ?></div><div><b><?= e($k['name']) ?></b><small><?= e($k['relation']) ?> view · <?= count($cs) ?> course<?= count($cs) == 1 ? '' : 's' ?></small></div></div>
  <?php if ($absToday): ?><div class="alert err">⚠️ Marked absent today</div><?php endif ?>
  <div class="stats" style="margin:12px 0 0">
    <div class="stat"><b class="<?= $att !== null && $att < 75 ? 'neg' : '' ?>"><?= $att !== null ? $att . '%' : '–' ?></b><span>Attendance</span></div>
    <div class="stat"><b><?= $lr ? $lr['grade'] : '–' ?></b><span>Last result</span></div>
    <div class="stat"><b class="<?= $vu['od'] ? 'neg' : '' ?>"><?= money($vu['t']) ?></b><span>Fee due</span></div>
    <div class="stat"><b><?= $pend ?></b><span>Pending work</span></div>
  </div>
  <?php foreach ($cs as $c): $pc = course_progress($kid, (int)$c['id']); ?><div class="kidc"><small><?= e($c['title']) ?> · <?= $pc ?>%</small><div class="bar"><i style="width:<?= $pc ?>%"></i></div></div><?php endforeach ?>
  <div class="quick" style="margin:12px 0 0"><a href="?p=fees&child=<?= $kid ?>">💳 Fees</a><a href="?p=exams&child=<?= $kid ?>">🧾 Results</a><a href="?p=assignments&child=<?= $kid ?>">📝 Assignments</a><a href="?p=attendance_me&child=<?= $kid ?>">🗓️ Attendance</a></div>
</div>
<?php endforeach;
