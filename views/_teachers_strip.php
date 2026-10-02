<?php
$__ts = all('SELECT u.id,u.name,tp.photo,tp.headline FROM users u JOIN teacher_profiles tp ON tp.user_id=u.id WHERE u.active=1 AND u.role IN ("teacher","admin") AND tp.public=1 ORDER BY (SELECT COUNT(*) FROM courses c WHERE c.teacher_id=u.id AND c.published=1) DESC LIMIT 12');
if ($__ts): ?>
<div class="rowhead sec-t"><h2>Our teachers</h2><a href="?p=teachers">See all ›</a></div>
<div class="tstrip">
<?php foreach ($__ts as $t): ?>
  <a href="?p=teacher&id=<?= $t['id'] ?>" class="tchip">
    <div class="avatar lg" style="<?= $t['photo'] ? "background:center/cover url('" . e(photo_url($t['photo'])) . "')" : '' ?>"><?= $t['photo'] ? '' : e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></div>
    <b><?= e($t['name']) ?></b><small><?= e(mb_strimwidth((string)$t['headline'], 0, 34, '…')) ?></small>
  </a>
<?php endforeach ?>
</div>
<?php endif;
