<?php
$me = user(); $title = 'Notifications'; $back = '?p=home';
$rows = all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100', [$me['id']]);
q('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0', [$me['id']]);
function ago(string $t): string { $d = time() - strtotime($t); return $d < 60 ? 'just now' : ($d < 3600 ? floor($d / 60) . 'm ago' : ($d < 86400 ? floor($d / 3600) . 'h ago' : date('d M', strtotime($t)))); }
?>
<?php if ($rows): ?><form method="post" style="text-align:right;margin:0 0 8px"><?= csrf_field() ?><input type="hidden" name="a" value="notif_clear"><button class="btn sm ghost">Clear read</button></form><?php endif ?>
<div class="list"><?php foreach ($rows as $n): ?>
  <a class="row notif <?= $n['is_read'] ? '' : 'new' ?>" href="<?= e($n['link'] ?: '?p=notifications') ?>"><span class="mi"><?= $n['icon'] ?></span><div class="grow"><b><?= e($n['title']) ?></b><?php if ($n['body']): ?><small><?= e($n['body']) ?></small><?php endif ?></div><small><?= ago($n['created_at']) ?></small></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">🔔 You're all caught up.</p><?php endif ?>
