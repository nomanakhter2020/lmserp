<?php
require_role('admin', 'teacher');
$c = $id ? one('SELECT * FROM courses WHERE id=?', [$id]) : ['title' => '', 'description' => '', 'category_id' => null, 'teacher_id' => user()['id'], 'fee' => 0, 'color' => '#4f46e5', 'published' => 1, 'cover' => '', 'program' => get('program', 'course'), 'level' => ''];
if ($id && (!$c || !can_manage_course($c))) exit('Not allowed');
$title = $id ? 'Edit course' : 'New course'; $back = $id ? "?p=course&id=$id" : '?p=courses';
$cats = all('SELECT * FROM categories ORDER BY name');
$teachers = all('SELECT id,name FROM users WHERE role IN ("teacher","admin") AND active=1 ORDER BY name');
?>
<form method="post" class="card" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="a" value="course_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Title<input name="title" value="<?= e($c['title']) ?>" required></label>
  <label>Cover photo <small>(landscape, e.g. 1280×720)</small>
    <div class="cover-pick" id="cprev" style="<?= cover_style($c) ?>"><span><?= !empty($c['cover']) ? 'Tap to change' : '📷 Tap to add cover photo' ?></span>
    <input type="file" name="cover" accept="image/*" onchange="const f=this.files[0];if(f){const p=document.getElementById('cprev');p.style.backgroundImage='url('+URL.createObjectURL(f)+')';p.querySelector('span').textContent='Tap to change'}"></div></label>
  <?php if (!empty($c['cover'])): ?><label class="check"><input type="checkbox" name="remove_cover" value="1"> Remove cover photo</label><?php endif ?>
  <div class="two"><label>Program<select name="program"><?php foreach (PROGRAMS as $k => [$l, $i]): ?><option value="<?= $k ?>" <?= ($c['program'] ?? 'course') === $k ? 'selected' : '' ?>><?= $i ?> <?= $l ?></option><?php endforeach ?></select></label>
    <label>Level / grade / age <small>(optional)</small><input name="level" value="<?= e($c['level'] ?? '') ?>" placeholder="e.g. Grade 3 · Age 7–8"></label></div>
  <label>Description<textarea name="description" rows="4"><?= e($c['description']) ?></textarea></label>
  <div class="two">
    <label>Category<select name="category_id"><option value="">General</option><?php foreach ($cats as $x): ?><option value="<?= $x['id'] ?>" <?= $c['category_id'] == $x['id'] ? 'selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach ?></select></label>
    <label>Fee (PKR)<input name="fee" type="number" min="0" step="1" value="<?= (float)$c['fee'] ?>"></label>
  </div>
  <?php if (role('admin')): ?>
  <label>Teacher<select name="teacher_id"><option value="">— None —</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= $c['teacher_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach ?></select></label>
  <?php endif ?>
  <div class="two">
    <label>Colour<input name="color" type="color" value="<?= e($c['color']) ?>"></label>
    <label class="check"><input type="checkbox" name="published" value="1" <?= $c['published'] ? 'checked' : '' ?>> Published</label>
  </div>
  <button class="btn block">Save course</button>
</form>
<?php if ($id && role('admin')): ?>
<form method="post" onsubmit="return confirm('Delete this course with all lessons, quizzes and enrollments?')"><?= csrf_field() ?><input type="hidden" name="a" value="course_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete course</button></form>
<?php endif ?>
