<?php
require_role('admin', 'teacher');
$me = user(); $cid = (int)get('course'); $st = (string)get('status');
$title = 'Enrollments'; $back = $cid ? "?p=course&id=$cid" : '?p=users';
$w = ['1=1']; $pr = [];
if ($cid) { $w[] = 'e.course_id=?'; $pr[] = $cid; }
if ($st) { $w[] = 'e.status=?'; $pr[] = $st; }
if (role('teacher')) { $w[] = 'c.teacher_id=?'; $pr[] = $me['id']; }
$rows = all('SELECT e.*,u.name,u.phone,c.title FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE ' . implode(' AND ', $w) . ' ORDER BY e.id DESC LIMIT 300', $pr);
$base = '?p=enrollments' . ($cid ? "&course=$cid" : '');
?>
<div class="chips"><?php foreach (['' => 'All', 'pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed'] as $k => $v): ?><a href="<?= $base ?>&status=<?= $k ?>" class="<?= $st === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?></div>
<div class="list"><?php foreach ($rows as $r): $pc = course_progress((int)$r['user_id'], (int)$r['course_id']); ?>
  <div class="row col">
    <div class="rowhead"><a href="?p=user&id=<?= $r['user_id'] ?>"><b><?= e($r['name']) ?></b></a><span class="pill <?= $r['status'] === 'pending' ? 'warn' : ($r['status'] === 'completed' ? 'ok' : '') ?>"><?= ucfirst($r['status']) ?></span></div>
    <small><?= e($r['title']) ?> · <?= $pc ?>% · <?= date('d M Y', strtotime($r['created_at'])) ?></small>
    <?php if (role('admin')): ?><div class="actions">
      <?php if ($r['status'] === 'pending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="enroll_admin"><input type="hidden" name="user_id" value="<?= $r['user_id'] ?>"><input type="hidden" name="course_id" value="<?= $r['course_id'] ?>"><input type="hidden" name="status" value="active"><input type="hidden" name="back" value="<?= e($base) ?>"><button class="btn sm">Approve</button></form><?php endif ?>
      <form method="post" onsubmit="return confirm('Remove enrollment?')"><?= csrf_field() ?><input type="hidden" name="a" value="enroll_remove"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm ghost">Remove</button></form>
    </div><?php endif ?>
  </div>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No enrollments</p><?php endif ?>
