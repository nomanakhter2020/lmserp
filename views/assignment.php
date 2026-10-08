<?php
$me = user();
$a = one('SELECT a.*,c.title ctitle,c.teacher_id,b.name bname FROM assignments a JOIN courses c ON c.id=a.course_id LEFT JOIN batches b ON b.id=a.batch_id WHERE a.id=?', [$id]);
if (!$a) { echo '<p class="empty">Not found</p>'; return; }
$manage = can_manage_course($a);
$viewUid = child_id();
if (!$manage && !(role('parent') ? is_parent_of($viewUid) && val('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?', [$viewUid, $a['course_id']]) : val('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? AND status<>"pending"', [$me['id'], $a['course_id']]))) { echo '<p class="empty">Not available</p>'; return; }
$title = 'Assignment'; $back = '?p=assignments';
$late = $a['due_at'] && strtotime($a['due_at']) < time();
?>
<div class="card"><span class="pill"><?= e($a['ctitle']) ?><?= $a['bname'] ? ' · ' . e($a['bname']) : '' ?></span>
  <h1 style="margin-top:8px"><?= e($a['title']) ?></h1>
  <small><?= $a['due_at'] ? '⏰ Due ' . date('l, d M Y g:i a', strtotime($a['due_at'])) . ($late ? ' (closed)' : '') : 'No deadline' ?> · <?= $a['max_marks'] ?> marks</small>
  <?php if ($a['instructions']): ?><div class="content" style="margin-top:12px"><?= nl2br(e($a['instructions'])) ?></div><?php endif ?>
  <?php if ($a['attachment_url']): ?><p><a class="btn sm ghost" href="<?= e(safe_link($a['attachment_url'])) ?>" target="_blank" rel="noopener">📎 Open attachment</a></p><?php endif ?>
  <?php if ($manage): ?><div class="quick" style="margin:10px 0 0"><a href="?p=assign_edit&id=<?= $id ?>">✏️ Edit</a></div><?php endif ?>
</div>

<?php if ($manage):
  $sts = all('SELECT u.id,u.name,s.id sid,s.answer,s.file,s.submitted_at,s.marks,s.feedback FROM users u LEFT JOIN submissions s ON s.user_id=u.id AND s.assignment_id=? WHERE u.id IN (' . (implode(',', course_student_ids((int)$a['course_id'], $a['batch_id'] ? (int)$a['batch_id'] : null)) ?: '0') . ') ORDER BY (s.answer<>"" OR s.file<>"") DESC, u.name', [$id]);
  $done = count(array_filter($sts, fn($s) => $s['answer'] || $s['file'])); ?>
<h2>Submissions (<?= $done ?> / <?= count($sts) ?>)</h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="grade_save"><input type="hidden" name="id" value="<?= $id ?>">
<div class="list"><?php foreach ($sts as $s): $has = $s['answer'] || $s['file']; ?>
  <div class="row col">
    <div class="rowhead"><b><?= e($s['name']) ?></b><span class="pill <?= $s['marks'] !== null ? 'ok' : ($has ? 'warn' : '') ?>"><?= $s['marks'] !== null ? (float)$s['marks'] . '/' . $a['max_marks'] : ($has ? 'To grade' : 'Not submitted') ?></span></div>
    <?php if ($has): ?><small>Submitted <?= date('d M, g:i a', strtotime($s['submitted_at'])) ?><?= $a['due_at'] && $s['submitted_at'] > $a['due_at'] ? ' · <span class="neg">late</span>' : '' ?></small>
      <?php if ($s['answer']): ?><div class="ans"><?= nl2br(e($s['answer'])) ?></div><?php endif ?>
      <?php if ($s['file']): ?><a class="btn sm ghost" href="?p=sub_file&id=<?= $s['sid'] ?>" target="_blank">📎 Open file</a><?php endif ?><?php endif ?>
    <div class="two" style="margin-top:6px"><input name="marks[<?= $s['id'] ?>]" type="number" step="0.5" min="0" max="<?= $a['max_marks'] ?>" value="<?= $s['marks'] !== null ? (float)$s['marks'] : '' ?>" placeholder="Marks / <?= $a['max_marks'] ?>"><input name="feedback[<?= $s['id'] ?>]" value="<?= e($s['feedback']) ?>" placeholder="Feedback"></div>
  </div>
<?php endforeach ?></div>
<?php if ($sts): ?><button class="btn block">Save grades & notify</button><?php else: ?><p class="empty">No students yet.</p><?php endif ?>
</form>

<?php else: $s = one('SELECT * FROM submissions WHERE assignment_id=? AND user_id=?', [$id, $viewUid]); $has = $s && ($s['answer'] || $s['file']); ?>
  <?php if ($s && $s['marks'] !== null): ?>
    <div class="card result pass" style="padding:20px"><div class="ring" style="--p:<?= round($s['marks'] * 100 / $a['max_marks']) ?>"><b><?= (float)$s['marks'] ?>/<?= $a['max_marks'] ?></b></div><?php if ($s['feedback']): ?><p><b>Teacher's feedback:</b> <?= e($s['feedback']) ?></p><?php endif ?></div>
  <?php endif ?>
  <?php if ($has): ?><div class="card"><h3>Your submission</h3><small><?= date('d M Y, g:i a', strtotime($s['submitted_at'])) ?></small><?php if ($s['answer']): ?><div class="ans"><?= nl2br(e($s['answer'])) ?></div><?php endif ?><?php if ($s['file']): ?><a class="btn sm ghost" href="?p=sub_file&id=<?= $s['id'] ?>" target="_blank">📎 Your file</a><?php endif ?></div><?php endif ?>
  <?php if (!role('parent') && (!$s || $s['marks'] === null)): ?>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="submit_work"><input type="hidden" name="id" value="<?= $id ?>">
    <h3><?= $has ? 'Update submission' : 'Submit your work' ?></h3>
    <?php if ($late): ?><div class="alert warn">The deadline has passed — late submissions are marked late.</div><?php endif ?>
    <textarea name="answer" rows="6" placeholder="Write your answer…"><?= e($s['answer'] ?? '') ?></textarea>
    <label>Attach file <small>(photo of notebook, PDF — max 5 MB)</small><input type="file" name="file" accept="image/*,application/pdf"></label>
    <button class="btn block"><?= $has ? 'Update' : 'Submit' ?></button></form>
  <?php endif ?>
<?php endif ?>
