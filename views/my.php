<?php
$me = user(); $title = role('teacher') ? 'My learning' : 'My courses';
$cs = all('SELECT c.*,e.status FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.id DESC', [$me['id']]);
?>
<div class="list">
<?php foreach ($cs as $c): $pc = course_progress((int)$me['id'], (int)$c['id']); ?>
  <a class="row col" href="?p=course&id=<?= $c['id'] ?>" style="--c:<?= e($c['color']) ?>">
    <div class="rowhead"><b><?= e($c['title']) ?></b>
      <span class="pill <?= $c['status'] === 'pending' ? 'warn' : ($c['status'] === 'completed' ? 'ok' : '') ?>"><?= ucfirst($c['status']) ?></span></div>
    <div class="bar"><i style="width:<?= $pc ?>%"></i></div><small><?= $pc ?>% complete</small>
  </a>
<?php endforeach ?>
</div>
<?php if (!$cs): ?><p class="empty">You haven't joined a course yet.<br><a class="btn" href="?p=courses">Browse courses</a><?php if (role('teacher') && ($d = (int)setting('teacher_discount', '0'))): ?><br><small>Teachers get <?= $d ?>% off every course.</small><?php endif ?></p><?php endif ?>
