<?php
require_role('admin', 'teacher');
$me = user();
$b = $id ? one('SELECT * FROM batches WHERE id=?', [$id]) : ['course_id' => (int)get('course'), 'name' => '', 'teacher_id' => null, 'days' => 'Mon,Wed,Fri', 'start_time' => '', 'end_time' => '', 'room' => '', 'meet_link' => '', 'start_date' => date('Y-m-d'), 'active' => 1];
if ($id && (!$b || !can_manage_batch($b))) exit('Not allowed');
$title = $id ? 'Edit batch' : 'New batch'; $back = $id ? "?p=batch&id=$id" : '?p=batches';
$courses = role('admin') ? all('SELECT id,title FROM courses ORDER BY title') : all('SELECT id,title FROM courses WHERE teacher_id=? ORDER BY title', [$me['id']]);
$teachers = all('SELECT id,name FROM users WHERE role IN ("teacher","admin") AND active=1 ORDER BY name');
$sel = explode(',', (string)$b['days']);
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="batch_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Course<select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $b['course_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label>
  <label>Batch name<input name="name" value="<?= e($b['name']) ?>" placeholder="e.g. Evening Batch A" required></label>
  <?php if (role('admin')): ?><label>Teacher<select name="teacher_id"><option value="">Course teacher</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= $b['teacher_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach ?></select></label><?php endif ?>
  <label>Class days</label>
  <div class="daypick"><?php foreach (WEEKDAYS as $d): ?><label><input type="checkbox" name="days[]" value="<?= $d ?>" <?= in_array($d, $sel, true) ? 'checked' : '' ?>><span><?= $d ?></span></label><?php endforeach ?></div>
  <div class="two"><label>Start time<input type="time" name="start_time" value="<?= e(substr((string)$b['start_time'], 0, 5)) ?>"></label><label>End time<input type="time" name="end_time" value="<?= e(substr((string)$b['end_time'], 0, 5)) ?>"></label></div>
  <div class="two"><label>Room<input name="room" value="<?= e($b['room']) ?>" placeholder="Room 2"></label><label>Start date<input type="date" name="start_date" value="<?= e($b['start_date']) ?>"></label></div>
  <label>Online class link <small>(Zoom / Meet, optional)</small><input name="meet_link" type="url" value="<?= e(safe_link($b['meet_link'])) ?>" placeholder="https://meet.google.com/…"></label>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $b['active'] ? 'checked' : '' ?>> Active</label>
  <button class="btn block">Save batch</button>
</form>
<?php if ($id && role('admin')): ?><form method="post" onsubmit="return confirm('Delete batch and its attendance records?')"><?= csrf_field() ?><input type="hidden" name="a" value="batch_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete batch</button></form><?php endif ?>
