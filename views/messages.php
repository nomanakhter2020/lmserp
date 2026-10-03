<?php
require_role('admin');
$title = 'Contact messages'; $back = '?p=more';
$rows = all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200');
q('UPDATE contact_messages SET is_read=1 WHERE is_read=0');
?>
<?php foreach ($rows as $r): $wa = preg_replace('/\D/', '', $r['phone']); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1); ?>
<div class="card msg <?= $r['is_read'] ? '' : 'unread' ?>">
  <div class="rowhead"><b><?= e($r['name']) ?></b><small><?= date('d M, h:i a', strtotime($r['created_at'])) ?></small></div>
  <?php if ($r['subject']): ?><b class="subj"><?= e($r['subject']) ?></b><?php endif ?>
  <p><?= nl2br(e($r['message'])) ?></p>
  <div class="quick"><?php if ($r['email']): ?><a href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode('Re: ' . ($r['subject'] ?: 'Your message')) ?>">✉️ <?= e($r['email']) ?></a><?php endif ?><?php if ($wa): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">💬 <?= e($r['phone']) ?></a><?php endif ?>
  <form method="post" onsubmit="return confirm('Delete message?')"><?= csrf_field() ?><input type="hidden" name="a" value="msg_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="x">🗑</button></form></div>
</div>
<?php endforeach; if (!$rows): ?><p class="empty">No messages yet. Messages from the website Contact page appear here.</p><?php endif ?>
