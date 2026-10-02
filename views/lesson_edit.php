<?php
require_role('admin', 'teacher');
$l = $id ? one('SELECT * FROM lessons WHERE id=?', [$id]) : ['course_id' => (int)get('course'), 'title' => '', 'video_url' => '', 'content' => '', 'attachment_url' => '', 'sort' => 0];
$c = one('SELECT * FROM courses WHERE id=?', [$l['course_id']]);
if (!$c || !can_manage_course($c)) exit('Not allowed');
if (!$id) $l['sort'] = (int)val('SELECT COALESCE(MAX(sort),0)+1 FROM lessons WHERE course_id=?', [$c['id']]);
$title = $id ? 'Edit lesson' : 'New lesson'; $back = "?p=course&id={$c['id']}";
?>
<div class="crumb"><?= e($c['title']) ?></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="lesson_save"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="course_id" value="<?= $c['id'] ?>">
  <label>Lesson title<input name="title" value="<?= e($l['title']) ?>" required></label>
  <label>Video URL <small>(YouTube link or .mp4)</small><input name="video_url" type="url" value="<?= e($l['video_url']) ?>" placeholder="https://youtu.be/…"></label>
  <label>Lesson notes<textarea name="content" rows="8"><?= e($l['content']) ?></textarea></label>
  <label>Attachment URL <small>(PDF, Drive link…)</small><input name="attachment_url" type="url" value="<?= e($l['attachment_url']) ?>"></label>
  <label>Order<input name="sort" type="number" value="<?= (int)$l['sort'] ?>"></label>
  <button class="btn block">Save lesson</button>
</form>
<?php if ($id): ?>
<form method="post" onsubmit="return confirm('Delete lesson?')"><?= csrf_field() ?><input type="hidden" name="a" value="lesson_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete lesson</button></form>
<?php endif ?>
