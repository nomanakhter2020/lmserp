<?php
$me = user();
$c = one('SELECT c.*,u.name tname,cat.name cname FROM courses c LEFT JOIN users u ON u.id=c.teacher_id LEFT JOIN categories cat ON cat.id=c.category_id WHERE c.id=?', [$id]);
if (!$c || (!$c['published'] && !can_manage_course($c) && !role('admin'))) { echo '<p class="empty">Course not found</p>'; return; }
$title = $c['title']; $back = role('student') ? '?p=my' : '?p=courses';
$manage = can_manage_course($c);
$en = one('SELECT * FROM enrollments WHERE user_id=? AND course_id=?', [$me['id'], $id]);
$access = $manage || ($en && $en['status'] !== 'pending');
$lessons = all('SELECT l.*,(SELECT 1 FROM progress p WHERE p.lesson_id=l.id AND p.user_id=?) done FROM lessons l WHERE course_id=? ORDER BY sort,id', [$me['id'], $id]);
$quizzes = all('SELECT qz.*,(SELECT COUNT(*) FROM questions q WHERE q.quiz_id=qz.id) qn,(SELECT MAX(ROUND(score*100/NULLIF(total,0))) FROM attempts a WHERE a.quiz_id=qz.id AND a.user_id=?) best FROM quizzes qz WHERE course_id=?', [$me['id'], $id]);
$ann = all('SELECT * FROM announcements WHERE course_id=? ORDER BY id DESC LIMIT 5', [$id]);
$pc = $en ? course_progress((int)$me['id'], $id) : 0;
?>
<div class="hero" style="--c:<?= e($c['color']) ?>">
  <div class="muted-l"><?= e($c['cname'] ?: 'General') ?> · <?= e($c['tname'] ?: 'No teacher') ?></div>
  <div class="big sm"><?= e($c['title']) ?></div>
  <?php if ($en && $access): ?><div class="bar light"><i style="width:<?= $pc ?>%"></i></div><div class="muted-l"><?= $pc ?>% complete</div><?php endif ?>
  <div class="split"><div><b><?= count($lessons) ?></b><span>Lessons</span></div><div><b><?= count($quizzes) ?></b><span>Quizzes</span></div><div><b><?= (float)$c['fee'] > 0 ? money($c['fee']) : 'Free' ?></b><span>Fee</span></div></div>
</div>
<?php if ($c['description']): ?><p class="desc"><?= nl2br(e($c['description'])) ?></p><?php endif ?>

<?php if (role('student')): ?>
  <?php if (!$en): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="enroll"><input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn block" formaction="?id=<?= $id ?>"><?= (float)$c['fee'] > 0 ? 'Enroll · ' . money($c['fee']) : 'Enroll for free' ?></button></form>
  <?php elseif ($en['status'] === 'pending'):
    $reqs = all('SELECT * FROM payment_requests WHERE user_id=? AND course_id=? ORDER BY id DESC', [$me['id'], $id]);
    $waiting = $reqs && $reqs[0]['status'] === 'pending';
    $accts = array_filter(['Bank' => setting('pay_bank'), 'JazzCash' => setting('pay_jazzcash'), 'EasyPaisa' => setting('pay_easypaisa')]);
  ?>
    <?php if ($waiting): ?>
      <div class="alert warn">⏳ Your <?= e($reqs[0]['method']) ?> payment of <?= money($reqs[0]['amount']) ?> is being verified. The course unlocks as soon as the admin approves it.</div>
    <?php else: ?>
      <?php if ($reqs && $reqs[0]['status'] === 'rejected'): ?><div class="alert err">Your last payment proof was not accepted<?= $reqs[0]['admin_note'] ? ': ' . e($reqs[0]['admin_note']) : '' ?>. Please submit again.</div><?php endif ?>
      <div class="card paybox">
        <h3>💳 Pay <?= money($c['fee']) ?> to unlock</h3>
        <?php foreach ($accts as $m => $txt): ?><div class="acct"><b><?= $m ?></b><span><?= nl2br(e($txt)) ?></span></div><?php endforeach ?>
        <?php if (setting('pay_cash')): ?><div class="acct"><b>Cash</b><span><?= nl2br(e(setting('pay_cash'))) ?></span></div><?php endif ?>
        <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="a" value="proof_submit"><input type="hidden" name="id" value="<?= $id ?>">
          <label>How did you pay?<select name="method" id="pm" onchange="document.getElementById('online').hidden=this.value==='Cash'">
            <?php foreach (array_keys($accts) ?: ['Bank', 'JazzCash', 'EasyPaisa'] as $m): ?><option><?= $m ?></option><?php endforeach ?><option value="Cash">Cash at office</option></select></label>
          <label>Amount (PKR)<input name="amount" type="number" min="1" value="<?= (float)$c['fee'] ?>" required></label>
          <div id="online">
            <label>Payment screenshot<input name="proof" type="file" accept="image/*,application/pdf"></label>
            <label>Transaction ID <small>(optional if screenshot attached)</small><input name="txn_ref" placeholder="e.g. 0123456789"></label>
          </div>
          <label>Note <small>(optional)</small><input name="note"></label>
          <button class="btn block">Submit payment</button>
        </form>
      </div>
    <?php endif ?>
  <?php endif ?>
<?php endif ?>

<?php if ($manage): ?>
<div class="quick"><a href="?p=course_edit&id=<?= $id ?>">✏️ Edit</a><a href="?p=lesson_edit&course=<?= $id ?>">＋ Lesson</a><a href="?p=quiz_edit&course=<?= $id ?>">＋ Quiz</a><a href="?p=enrollments&course=<?= $id ?>">👥 Students</a></div>
<?php endif ?>

<h2>Lessons</h2>
<div class="list">
<?php foreach ($lessons as $i => $l): ?>
  <?php if ($access): ?><a class="row" href="?p=lesson&id=<?= $l['id'] ?>"><?php else: ?><div class="row locked"><?php endif ?>
    <div class="num <?= $l['done'] ? 'done' : '' ?>"><?= $l['done'] ? '✓' : $i + 1 ?></div>
    <div class="grow"><b><?= e($l['title']) ?></b><small><?= $l['video_url'] ? '▶ Video' : '📄 Reading' ?></small></div>
    <span><?= $access ? '›' : '🔒' ?></span>
  <?= $access ? '</a>' : '</div>' ?>
<?php endforeach; if (!$lessons): ?><p class="empty">No lessons yet</p><?php endif ?>
</div>

<?php if ($quizzes): ?>
<h2>Quizzes</h2>
<div class="list"><?php foreach ($quizzes as $qz): ?>
  <a class="row" href="<?= $access ? ($manage ? "?p=quiz_edit&id={$qz['id']}" : "?p=quiz&id={$qz['id']}") : '#' ?>">
    <div class="num">?</div><div class="grow"><b><?= e($qz['title']) ?></b><small><?= $qz['qn'] ?> questions · pass <?= $qz['pass_percent'] ?>%</small></div>
    <?php if ($qz['best'] !== null): ?><span class="pill <?= $qz['best'] >= $qz['pass_percent'] ? 'ok' : 'warn' ?>"><?= $qz['best'] ?>%</span><?php else: ?><span>›</span><?php endif ?>
  </a>
<?php endforeach ?></div>
<?php endif ?>

<?php if ($ann || $manage): ?>
<h2>Announcements</h2>
<?php if ($manage): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="announce"><input type="hidden" name="course_id" value="<?= $id ?>">
  <input name="title" placeholder="Title" required><textarea name="body" placeholder="Message to enrolled students" rows="2"></textarea><button class="btn sm">Post</button></form>
<?php endif ?>
<div class="list"><?php foreach ($ann as $a): ?><div class="row col"><b><?= e($a['title']) ?></b><small><?= date('d M Y', strtotime($a['created_at'])) ?></small><p><?= nl2br(e($a['body'])) ?></p></div><?php endforeach ?></div>
<?php endif ?>
