<?php
$me = user();
$qz = one('SELECT qz.*,c.title ctitle,c.teacher_id FROM quizzes qz JOIN courses c ON c.id=qz.course_id WHERE qz.id=?', [$id]);
if (!$qz) { echo '<p class="empty">Quiz not found</p>'; return; }
$en = val('SELECT status FROM enrollments WHERE user_id=? AND course_id=?', [$me['id'], $qz['course_id']]);
if (!can_manage_course($qz) && (!$en || $en === 'pending')) { echo '<p class="empty">Enroll to take this quiz.</p>'; return; }
$title = $qz['title']; $back = "?p=course&id={$qz['course_id']}";
$qs = all('SELECT id,question,a,b,c,d FROM questions WHERE quiz_id=? ORDER BY RAND()', [$id]);
?>
<div class="crumb"><?= e($qz['ctitle']) ?> · <?= count($qs) ?> questions · pass <?= $qz['pass_percent'] ?>%</div>
<?php if (!$qs): ?><p class="empty">No questions yet</p><?php return; endif ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="quiz_submit"><input type="hidden" name="id" value="<?= $id ?>">
<?php foreach ($qs as $i => $q): ?>
  <div class="card qcard"><b><?= $i + 1 ?>. <?= e($q['question']) ?></b>
  <?php foreach (['a', 'b', 'c', 'd'] as $o): if ($q[$o] === '') continue; ?>
    <label class="opt"><input type="radio" name="q[<?= $q['id'] ?>]" value="<?= $o ?>" required><span><?= e($q[$o]) ?></span></label>
  <?php endforeach ?></div>
<?php endforeach ?>
<button class="btn block">Submit answers</button></form>
