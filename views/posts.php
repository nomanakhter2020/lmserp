<?php
require_role('admin', 'teacher');
$title = 'Blog posts'; $back = '?p=more';
$me = user();
$rows = role('admin') ? all('SELECT p.*,u.name author FROM posts p LEFT JOIN users u ON u.id=p.author_id ORDER BY p.created_at DESC') : all('SELECT p.*,u.name author FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE author_id=? ORDER BY p.created_at DESC', [$me['id']]);
$pub = count(array_filter($rows, fn($r) => $r['published']));
?>
<div class="stats"><div class="stat"><b><?= $pub ?></b><span>Published</span></div><div class="stat"><b><?= count($rows) - $pub ?></b><span>Drafts</span></div><div class="stat"><b><?= array_sum(array_column($rows, 'views')) ?></b><span>Views</span></div></div>
<a class="btn block" href="?p=post_edit">✍️ Write new article</a>
<div class="quick"><a href="blog" target="_blank">🌐 View blog</a><?php if (role('admin')): ?><a href="?p=pages_edit">📄 Website pages</a><?php endif ?></div>
<div class="list"><?php foreach ($rows as $r): ?>
  <a class="row" href="?p=post_edit&id=<?= $r['id'] ?>"><div class="grow"><b><?= e($r['title']) ?></b><small><?= e($r['category']) ?> · <?= date('d M Y', strtotime($r['created_at'])) ?> · <?= (int)$r['views'] ?> views<?= role('admin') && $r['author'] ? ' · ' . e($r['author']) : '' ?></small></div>
  <span class="pill <?= $r['published'] ? 'ok' : '' ?>"><?= $r['published'] ? 'Live' : 'Draft' ?></span></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No articles yet. Regular, original articles help Google rank your site and are required for AdSense approval.<?php if (role('admin')): ?><br><br><a class="btn ghost" href="?p=settings#demo">Load starter articles</a><?php endif ?></p><?php endif ?>
