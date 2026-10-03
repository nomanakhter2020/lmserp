<?php
$me = user(); $title = 'Assignments'; $back = '?p=more';
$cid = (int)get('course');
if (role('admin', 'teacher')) {
    $w = role('admin') ? '1=1' : 'c.teacher_id=' . (int)$me['id'];
    if ($cid) $w .= ' AND a.course_id=' . $cid;
    $rows = all("SELECT a.*,c.title ctitle,(SELECT COUNT(*) FROM submissions s WHERE s.assignment_id=a.id AND s.submitted_at IS NOT NULL AND (s.answer<>'' OR s.file<>'')) subs,(SELECT COUNT(*) FROM submissions s WHERE s.assignment_id=a.id AND s.marks IS NULL AND (s.answer<>'' OR s.file<>'')) ungraded FROM assignments a JOIN courses c ON c.id=a.course_id WHERE $w ORDER BY a.created_at DESC LIMIT 200");
} else {
    $uid = child_id();
    $rows = all('SELECT a.*,c.title ctitle,s.id sid,s.marks,s.submitted_at,(s.answer<>"" OR s.file<>"") done FROM assignments a JOIN courses c ON c.id=a.course_id JOIN enrollments e ON e.course_id=a.course_id AND e.user_id=? AND e.status<>"pending" LEFT JOIN submissions s ON s.assignment_id=a.id AND s.user_id=? WHERE a.batch_id IS NULL OR a.batch_id IN (SELECT batch_id FROM batch_students WHERE user_id=?) ORDER BY (s.id IS NULL) DESC, a.due_at IS NULL, a.due_at ASC', [$uid, $uid, $uid]);
}
?>
<?php if (role('admin', 'teacher')): ?><a class="btn block" href="?p=assign_edit<?= $cid ? "&course=$cid" : '' ?>">＋ New assignment</a><?php endif ?>
<div class="list"><?php foreach ($rows as $a): $late = $a['due_at'] && strtotime($a['due_at']) < time(); ?>
  <a class="row" href="?p=assignment&id=<?= $a['id'] ?><?= role('parent') ? '&child=' . $uid : '' ?>"><span class="mi">📝</span><div class="grow"><b><?= e($a['title']) ?></b><small><?= e($a['ctitle']) ?><?= $a['due_at'] ? ' · due ' . date('d M, g:i a', strtotime($a['due_at'])) : '' ?></small></div>
  <?php if (role('admin', 'teacher')): ?><span class="pill <?= $a['ungraded'] ? 'warn' : '' ?>"><?= (int)$a['subs'] ?> submitted<?= $a['ungraded'] ? ' · ' . $a['ungraded'] . ' to grade' : '' ?></span>
  <?php elseif ($a['marks'] !== null): ?><span class="pill ok"><?= (float)$a['marks'] ?>/<?= $a['max_marks'] ?></span>
  <?php elseif ($a['done']): ?><span class="pill">Submitted</span>
  <?php else: ?><span class="pill <?= $late ? 'err' : 'warn' ?>"><?= $late ? 'Late' : 'Pending' ?></span><?php endif ?></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No assignments yet.</p><?php endif ?>
