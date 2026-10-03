<?php
require_role('admin', 'teacher');
$me = user();
$x = $id ? one('SELECT e.*,c.teacher_id FROM exams e JOIN courses c ON c.id=e.course_id WHERE e.id=?', [$id]) : ['course_id' => (int)get('course'), 'batch_id' => null, 'title' => '', 'exam_date' => date('Y-m-d')];
if ($id && (!$x || !can_manage_course($x))) exit('Not allowed');
$title = $id ? 'Edit exam' : 'New exam'; $back = $id ? "?p=exam&id=$id" : '?p=exams';
$courses = role('admin') ? all('SELECT id,title FROM courses ORDER BY title') : all('SELECT id,title FROM courses WHERE teacher_id=? ORDER BY title', [$me['id']]);
$batches = all('SELECT id,name,course_id FROM batches WHERE active=1 ORDER BY name');
$papers = $id ? all('SELECT * FROM exam_papers WHERE exam_id=? ORDER BY sort,id', [$id]) : [];
if (!$papers) $papers = [['id' => 0, 'subject' => '', 'max_marks' => 100, 'pass_marks' => 33]];
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="exam_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Course<select name="course_id" id="acourse" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $x['course_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label>
  <label>Batch <small>(optional)</small><select name="batch_id" id="abatch"><option value="">All students</option><?php foreach ($batches as $b): ?><option value="<?= $b['id'] ?>" data-c="<?= $b['course_id'] ?>" <?= $x['batch_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach ?></select></label>
  <div class="two"><label>Exam title<input name="title" value="<?= e($x['title']) ?>" placeholder="e.g. Mid-Term Exam" required></label><label>Date<input name="exam_date" type="date" value="<?= e($x['exam_date']) ?>"></label></div>
  <h3>Subjects / papers</h3>
  <div id="papers"><?php foreach ($papers as $i => $p): ?>
    <div class="paper-row"><input type="hidden" name="pid[]" value="<?= (int)$p['id'] ?>"><input name="subj[]" value="<?= e($p['subject']) ?>" placeholder="Subject (e.g. Theory)"><input name="max[]" type="number" min="1" value="<?= (int)$p['max_marks'] ?>" title="Total marks"><input name="pass[]" type="number" min="0" value="<?= (int)$p['pass_marks'] ?>" title="Pass marks"><button type="button" class="x" onclick="if(document.querySelectorAll('.paper-row').length>1)this.parentNode.remove()">✕</button></div>
  <?php endforeach ?></div>
  <small class="muted">Columns: subject · total marks · pass marks</small>
  <button type="button" class="btn sm ghost" style="margin:8px 0 14px" onclick="const r=document.querySelector('.paper-row').cloneNode(true);r.querySelectorAll('input').forEach(i=>i.value=i.name=='max[]'?100:(i.name=='pass[]'?33:''));document.getElementById('papers').appendChild(r)">＋ Add subject</button>
  <button class="btn block">Save exam</button>
</form>
<?php if ($id): ?><form method="post" onsubmit="return confirm('Delete exam and all marks?')"><?= csrf_field() ?><input type="hidden" name="a" value="exam_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete exam</button></form><?php endif ?>
<script>const ac=document.getElementById('acourse'),ab=document.getElementById('abatch');function fb(){[...ab.options].forEach(o=>{if(o.dataset.c)o.hidden=o.dataset.c!==ac.value});if(ab.selectedOptions[0]&&ab.selectedOptions[0].hidden)ab.value=''}ac.onchange=fb;fb();</script>
