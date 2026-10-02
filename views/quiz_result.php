<?php
$me = user();
$r = one('SELECT a.*,qz.title,qz.pass_percent,qz.course_id FROM attempts a JOIN quizzes qz ON qz.id=a.quiz_id WHERE a.id=? AND a.user_id=?', [$id, $me['id']]);
if (!$r) { echo '<p class="empty">Result not found</p>'; return; }
$title = 'Result'; $back = "?p=course&id={$r['course_id']}";
$pc = $r['total'] ? (int)round($r['score'] * 100 / $r['total']) : 0; $pass = $pc >= $r['pass_percent'];
?>
<div class="card result <?= $pass ? 'pass' : 'fail' ?>">
  <div class="ring" style="--p:<?= $pc ?>"><b><?= $pc ?>%</b></div>
  <h1><?= $pass ? '🎉 Passed!' : 'Keep practicing' ?></h1>
  <p><?= e($r['title']) ?> — <?= $r['score'] ?> of <?= $r['total'] ?> correct (pass mark <?= $r['pass_percent'] ?>%)</p>
  <div class="pager"><a class="btn ghost" href="?p=quiz&id=<?= $r['quiz_id'] ?>">Retry</a><a class="btn" href="?p=course&id=<?= $r['course_id'] ?>">Back to course</a></div>
</div>
