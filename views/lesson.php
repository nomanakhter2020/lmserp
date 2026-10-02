<?php
$me = user();
$l = one('SELECT l.*,c.title ctitle,c.teacher_id,c.color FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=?', [$id]);
if (!$l) { echo '<p class="empty">Lesson not found</p>'; return; }
$manage = can_manage_course($l);
$en = one('SELECT status FROM enrollments WHERE user_id=? AND course_id=?', [$me['id'], $l['course_id']]);
if (!$manage && (!$en || $en['status'] === 'pending')) { echo '<p class="empty">Enroll in this course to view lessons.</p>'; return; }
$title = $l['title']; $back = "?p=course&id={$l['course_id']}";
$all = all('SELECT id FROM lessons WHERE course_id=? ORDER BY sort,id', [$l['course_id']]);
$ids = array_column($all, 'id'); $pos = array_search($l['id'], $ids);
$prev = $ids[$pos - 1] ?? null; $next = $ids[$pos + 1] ?? null;
$done = val('SELECT 1 FROM progress WHERE user_id=? AND lesson_id=?', [$me['id'], $id]);
$yt = $l['video_url'] ? youtube_id($l['video_url']) : null;
?>
<div class="crumb"><?= e($l['ctitle']) ?> · Lesson <?= $pos + 1 ?> of <?= count($ids) ?></div>
<?php if ($yt): ?>
  <div class="yt" data-yt="<?= e($yt) ?>">
    <div class="yt-frame"><div id="ytp"></div></div>
    <div class="yt-shield"></div>
    <button class="yt-big" aria-label="Play">▶</button>
    <div class="yt-bar">
      <button class="yt-pp" aria-label="Play/Pause">▶</button>
      <span class="yt-t">0:00</span>
      <input class="yt-seek" type="range" min="0" max="1000" value="0" aria-label="Seek">
      <span class="yt-d">0:00</span>
      <button class="yt-sp" aria-label="Speed">1x</button>
      <button class="yt-fs" aria-label="Fullscreen">⛶</button>
    </div>
  </div>
<?php elseif ($l['video_url']): ?>
  <div class="video"><video src="<?= e($l['video_url']) ?>" controls playsinline></video></div>
<?php endif ?>
<article class="content card"><h1><?= e($l['title']) ?></h1><?= nl2br(e($l['content'])) ?>
<?php if ($l['attachment_url']): ?><p><a class="btn ghost" href="<?= e($l['attachment_url']) ?>" target="_blank" rel="noopener">📎 Open attachment</a></p><?php endif ?>
</article>
<?php if ($manage): ?><div class="quick"><a href="?p=lesson_edit&id=<?= $id ?>">✏️ Edit lesson</a></div><?php endif ?>
<div class="pager">
  <?= $prev ? "<a class='btn ghost' href='?p=lesson&id=$prev'>‹ Prev</a>" : '<span></span>' ?>
  <?php if (!$manage): ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="lesson_done"><input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn"><?= $done ? ($next ? 'Next lesson ›' : 'Back to course') : ($next ? '✓ Complete & next' : '✓ Mark complete') ?></button></form>
  <?php else: ?><?= $next ? "<a class='btn' href='?p=lesson&id=$next'>Next ›</a>" : '' ?><?php endif ?>
</div>
