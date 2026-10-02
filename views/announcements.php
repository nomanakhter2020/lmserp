<?php
$me = user(); $title = 'Announcements'; $back = '?p=more';
$rows = all('SELECT a.*,c.title ct,u.name un FROM announcements a LEFT JOIN courses c ON c.id=a.course_id LEFT JOIN users u ON u.id=a.created_by
  WHERE ? OR a.course_id IS NULL OR a.course_id IN (SELECT course_id FROM enrollments WHERE user_id=?) OR c.teacher_id=? ORDER BY a.id DESC LIMIT 100', [role('admin') ? 1 : 0, $me['id'], $me['id']]);
$courses = role('admin') ? all('SELECT id,title FROM courses ORDER BY title') : (role('teacher') ? all('SELECT id,title FROM courses WHERE teacher_id=? ORDER BY title', [$me['id']]) : []);
?>
<?php if (role('admin', 'teacher')): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="announce">
  <select name="course_id"><?php if (role('admin')): ?><option value="">Everyone</option><?php endif ?><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach ?></select>
  <input name="title" placeholder="Title" required><textarea name="body" rows="3" placeholder="Message"></textarea><button class="btn block">Post announcement</button></form>
<?php endif ?>
<div class="list"><?php foreach ($rows as $a): ?>
  <div class="row col"><div class="rowhead"><b><?= e($a['title']) ?></b><?php if (role('admin')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="announce_delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="x">✕</button></form><?php endif ?></div>
  <small><?= e($a['ct'] ?: 'Everyone') ?> · <?= e($a['un']) ?> · <?= date('d M Y', strtotime($a['created_at'])) ?></small><p><?= nl2br(e($a['body'])) ?></p></div>
<?php endforeach; if (!$rows): ?><p class="empty">No announcements</p><?php endif ?></div>
