<?php
require_role('admin', 'teacher');
$qz = $id ? one('SELECT * FROM quizzes WHERE id=?', [$id]) : ['course_id' => (int)get('course'), 'title' => '', 'pass_percent' => 50];
$c = one('SELECT * FROM courses WHERE id=?', [$qz['course_id']]);
if (!$c || !can_manage_course($c)) exit('Not allowed');
$title = $id ? 'Quiz' : 'New quiz'; $back = "?p=course&id={$c['id']}";
$qs = $id ? all('SELECT * FROM questions WHERE quiz_id=? ORDER BY id', [$id]) : [];
$res = $id ? all('SELECT a.*,u.name FROM attempts a JOIN users u ON u.id=a.user_id WHERE quiz_id=? ORDER BY a.id DESC LIMIT 30', [$id]) : [];
?>
<div class="crumb"><?= e($c['title']) ?></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="quiz_save"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="course_id" value="<?= $c['id'] ?>">
  <div class="two"><label>Quiz title<input name="title" value="<?= e($qz['title']) ?>" required></label>
  <label>Pass %<input name="pass_percent" type="number" min="0" max="100" value="<?= (int)$qz['pass_percent'] ?>"></label></div>
  <button class="btn block"><?= $id ? 'Update' : 'Create quiz' ?></button>
</form>
<?php if ($id): ?>
<h2>Questions (<?= count($qs) ?>)</h2>
<div class="list"><?php foreach ($qs as $i => $q): ?>
  <div class="row col"><div class="rowhead"><b><?= $i + 1 ?>. <?= e($q['question']) ?></b>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="question_delete"><input type="hidden" name="id" value="<?= $q['id'] ?>"><button class="x" aria-label="Delete">✕</button></form></div>
    <small><?php foreach (['a', 'b', 'c', 'd'] as $o) if ($q[$o] !== '') echo '<span class="' . ($q['answer'] === $o ? 'pos' : '') . '">' . strtoupper($o) . ') ' . e($q[$o]) . '</span> &nbsp;'; ?></small></div>
<?php endforeach ?></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="question_add"><input type="hidden" name="id" value="<?= $id ?>">
  <h3>Add question</h3>
  <textarea name="question" rows="2" placeholder="Question" required></textarea>
  <div class="two"><input name="qa" placeholder="Option A" required><input name="qb" placeholder="Option B" required></div>
  <div class="two"><input name="qc" placeholder="Option C"><input name="qd" placeholder="Option D"></div>
  <label>Correct answer<select name="answer"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select></label>
  <button class="btn block">Add question</button>
</form>
<?php if ($res): ?><h2>Results</h2><div class="list"><?php foreach ($res as $r): $pc = $r['total'] ? round($r['score'] * 100 / $r['total']) : 0; ?>
  <div class="row"><div class="grow"><b><?= e($r['name']) ?></b><small><?= date('d M, h:i a', strtotime($r['created_at'])) ?></small></div><span class="pill <?= $pc >= $qz['pass_percent'] ? 'ok' : 'warn' ?>"><?= $r['score'] ?>/<?= $r['total'] ?></span></div>
<?php endforeach ?></div><?php endif ?>
<form method="post" onsubmit="return confirm('Delete quiz?')"><?= csrf_field() ?><input type="hidden" name="a" value="quiz_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete quiz</button></form>
<?php endif ?>
