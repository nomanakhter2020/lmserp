<?php
$title = 'Our teachers'; $back = '?p=more';
$ts = all('SELECT u.id,u.name,tp.photo,tp.headline,tp.years,tp.city,(SELECT COUNT(*) FROM courses c WHERE c.teacher_id=u.id AND c.published=1) cc FROM users u JOIN teacher_profiles tp ON tp.user_id=u.id WHERE u.active=1 AND u.role IN ("teacher","admin") AND tp.public=1 ORDER BY cc DESC, u.name');
?>
<div class="list">
<?php foreach ($ts as $t): ?>
  <a class="row" href="?p=teacher&id=<?= $t['id'] ?>">
    <div class="avatar lg" style="<?= $t['photo'] ? "background:center/cover url('" . e(photo_url($t['photo'])) . "')" : '' ?>"><?= $t['photo'] ? '' : e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></div>
    <div class="grow"><b><?= e($t['name']) ?></b><small><?= e($t['headline']) ?></small><small><?= $t['years'] ? (int)$t['years'] . '+ yrs · ' : '' ?><?= $t['cc'] ?> course<?= $t['cc'] == 1 ? '' : 's' ?><?= $t['city'] ? ' · ' . e($t['city']) : '' ?></small></div><span>›</span>
  </a>
<?php endforeach ?>
</div>
<?php if (!$ts): ?><p class="empty">No teacher profiles yet.<?php if (role('admin')): ?><br><a class="btn" href="?p=settings">Load demo teachers</a><?php endif ?></p><?php endif ?>
