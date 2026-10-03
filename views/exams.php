<?php
$me = user(); $title = 'Exams & results'; $back = '?p=more';
if (role('admin', 'teacher')) {
    $w = role('admin') ? '1=1' : 'c.teacher_id=' . (int)$me['id'];
    $rows = all("SELECT e.*,c.title ctitle,b.name bname,(SELECT COUNT(*) FROM exam_papers p WHERE p.exam_id=e.id) np FROM exams e JOIN courses c ON c.id=e.course_id LEFT JOIN batches b ON b.id=e.batch_id WHERE $w ORDER BY e.exam_date DESC, e.id DESC");
} else {
    $uid = child_id();
    $rows = all('SELECT e.*,c.title ctitle,b.name bname FROM exams e JOIN courses c ON c.id=e.course_id LEFT JOIN batches b ON b.id=e.batch_id JOIN enrollments en ON en.course_id=e.course_id AND en.user_id=? WHERE e.published=1 AND (e.batch_id IS NULL OR e.batch_id IN (SELECT batch_id FROM batch_students WHERE user_id=?)) ORDER BY e.exam_date DESC', [$uid, $uid]);
}
?>
<?php if (role('admin', 'teacher')): ?><a class="btn block" href="?p=exam_edit">＋ New exam</a><?php endif ?>
<div class="list"><?php foreach ($rows as $x): ?>
  <a class="row" href="?p=<?= role('admin', 'teacher') ? 'exam' : 'result' ?>&id=<?= $x['id'] ?><?= role('parent') ? '&child=' . $uid : '' ?>"><span class="mi">🧾</span><div class="grow"><b><?= e($x['title']) ?></b><small><?= e($x['ctitle']) ?><?= $x['bname'] ? ' · ' . e($x['bname']) : '' ?><?= $x['exam_date'] ? ' · ' . date('d M Y', strtotime($x['exam_date'])) : '' ?></small></div>
  <?php if (role('admin', 'teacher')): ?><span class="pill <?= $x['published'] ? 'ok' : '' ?>"><?= $x['published'] ? 'Published' : 'Draft' ?></span><?php else: $r = exam_results((int)$x['id'])[$uid] ?? null; ?><span class="pill <?= $r && $r['pass'] ? 'ok' : 'err' ?>"><?= $r ? $r['pct'] . '% · ' . $r['grade'] : '–' ?></span><?php endif ?></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty"><?= role('admin', 'teacher') ? 'No exams yet. Create term tests, monthly tests or final exams.' : 'No results published yet.' ?></p><?php endif ?>
