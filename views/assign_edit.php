<?php
require_role('admin', 'teacher');
$me = user();
$a = $id ? one('SELECT a.*,c.teacher_id FROM assignments a JOIN courses c ON c.id=a.course_id WHERE a.id=?', [$id]) : ['course_id' => (int)get('course'), 'batch_id' => null, 'title' => '', 'instructions' => '', 'attachment_url' => '', 'due_at' => date('Y-m-d 23:59', strtotime('+7 days')), 'max_marks' => 10];
if ($id && (!$a || !can_manage_course($a))) exit('Not allowed');
$title = $id ? 'Edit assignment' : 'New assignment'; $back = $id ? "?p=assignment&id=$id" : '?p=assignments';
$courses = role('admin') ? all('SELECT id,title FROM courses ORDER BY title') : all('SELECT id,title FROM courses WHERE teacher_id=? ORDER BY title', [$me['id']]);
$batches = all('SELECT b.id,b.name,b.course_id FROM batches b WHERE b.active=1 ORDER BY b.name');
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="assign_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Course<select name="course_id" required id="acourse"><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $a['course_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label>
  <label>For batch <small>(optional)</small><select name="batch_id" id="abatch"><option value="">All students of the course</option><?php foreach ($batches as $b): ?><option value="<?= $b['id'] ?>" data-c="<?= $b['course_id'] ?>" <?= $a['batch_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach ?></select></label>
  <label>Title<input name="title" value="<?= e($a['title']) ?>" required></label>
  <label>Instructions<textarea name="instructions" rows="6"><?= e($a['instructions']) ?></textarea></label>
  <label>Attachment / reference link <small>(optional)</small><input name="attachment_url" type="url" value="<?= e(safe_link($a['attachment_url'])) ?>"></label>
  <div class="two"><label>Due<input name="due_at" type="datetime-local" value="<?= e($a['due_at'] ? date('Y-m-d\TH:i', strtotime($a['due_at'])) : '') ?>"></label><label>Total marks<input name="max_marks" type="number" min="1" value="<?= (int)$a['max_marks'] ?>"></label></div>
  <button class="btn block"><?= $id ? 'Save' : 'Create & notify students' ?></button>
</form>
<?php if ($id): ?><form method="post" onsubmit="return confirm('Delete assignment and all submissions?')"><?= csrf_field() ?><input type="hidden" name="a" value="assign_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete</button></form><?php endif ?>
<script>const ac=document.getElementById('acourse'),ab=document.getElementById('abatch');function fb(){[...ab.options].forEach(o=>{if(o.dataset.c)o.hidden=o.dataset.c!==ac.value});if(ab.selectedOptions[0]&&ab.selectedOptions[0].hidden)ab.value=''}ac.onchange=fb;fb();</script>
