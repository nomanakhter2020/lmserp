<?php
require_role('admin', 'teacher');
$x = one('SELECT e.*,c.title ctitle,c.teacher_id,b.name bname FROM exams e JOIN courses c ON c.id=e.course_id LEFT JOIN batches b ON b.id=e.batch_id WHERE e.id=?', [$id]);
if (!$x || !can_manage_course($x)) { echo '<p class="empty">Not found</p>'; return; }
$title = $x['title']; $back = '?p=exams';
$papers = all('SELECT * FROM exam_papers WHERE exam_id=? ORDER BY sort,id', [$id]);
$res = exam_results($id);
$names = $res ? array_column(all('SELECT id,name FROM users WHERE id IN (' . implode(',', array_keys($res)) . ') ORDER BY name'), 'name', 'id') : [];
$passed = count(array_filter($res, fn($r) => $r['pass'])); $marked = count(array_filter($res, fn($r) => $r['any']));
?>
<div class="hero"><div class="muted-l"><?= e($x['ctitle']) ?><?= $x['bname'] ? ' · ' . e($x['bname']) : '' ?><?= $x['exam_date'] ? ' · ' . date('d M Y', strtotime($x['exam_date'])) : '' ?></div><div class="big sm"><?= e($x['title']) ?></div>
  <div class="split"><div><b><?= count($res) ?></b><span>Students</span></div><div><b><?= $marked ? round($passed * 100 / $marked) . '%' : '–' ?></b><span>Pass rate</span></div><div><b><?= $x['published'] ? '✅' : '📝' ?></b><span><?= $x['published'] ? 'Published' : 'Draft' ?></span></div></div></div>
<div class="quick"><a href="?p=exam_edit&id=<?= $id ?>">✏️ Edit subjects</a><?php if ($x['published']): ?><a href="?p=result&id=<?= $id ?>&all=1" target="_blank">🖨 Print all result cards</a><?php endif ?></div>
<?php if (!$papers): ?><p class="empty">Add subjects first.</p><?php return; endif ?>
<?php if (!$res): ?><p class="empty">No students in this course/batch yet.</p><?php return; endif ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="marks_save"><input type="hidden" name="id" value="<?= $id ?>">
<p class="muted" style="font-size:13px;margin:0 4px 8px">Enter marks. Type <b>A</b> for absent. Leave empty if not taken.</p>
<div class="card tablewrap"><table class="att marks">
  <tr><th>Student</th><?php foreach ($papers as $p): ?><th><?= e($p['subject']) ?><br><small><?= $p['max_marks'] ?></small></th><?php endforeach ?><th>Total</th><th>%</th><th>Grade</th><th>Pos</th></tr>
  <?php foreach ($names as $uid => $nm): $r = $res[$uid]; ?><tr><td><?= e($nm) ?></td>
    <?php foreach ($papers as $p): $m = $r['marks'][$p['id']] ?? null; ?><td><input name="m[<?= $uid ?>][<?= $p['id'] ?>]" value="<?= $m ? ($m['absent'] ? 'A' : (float)$m['marks']) : '' ?>" inputmode="decimal" class="<?= $m && !$m['absent'] && $m['marks'] < $p['pass_marks'] ? 'low' : '' ?>"></td><?php endforeach ?>
    <td><b><?= $r['any'] ? (float)$r['total'] : '–' ?></b></td><td><?= $r['any'] ? $r['pct'] : '–' ?></td><td class="<?= $r['pass'] ? 'a-P' : 'a-A' ?>"><?= $r['grade'] ?></td><td><?= $r['rank'] ?? '–' ?></td></tr><?php endforeach ?>
</table></div>
<div class="two"><button class="btn ghost">💾 Save marks</button><button class="btn" name="publish" value="1" onclick="return confirm('Publish results to students and parents?')"><?= $x['published'] ? '💾 Save & re-publish' : '📢 Save & publish' ?></button></div>
</form>
<?php if ($x['published']): ?><form method="post" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="a" value="exam_unpublish"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn ghost block">Hide result from students</button></form><?php endif ?>
