<?php
require_role('admin', 'teacher');
$title = 'Blog posts'; $back = '?p=more';
$me = user();
$all = role('admin') ? all('SELECT p.*,u.name author FROM posts p LEFT JOIN users u ON u.id=p.author_id ORDER BY (p.review="pending") DESC, p.created_at DESC') : all('SELECT p.*,u.name author FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE author_id=? ORDER BY p.created_at DESC', [$me['id']]);
$f = in_array(get('f'), ['pending', 'live', 'draft'], true) ? get('f') : '';
$stOf = fn($r) => $r['published'] ? 'live' : ($r['review'] === 'pending' ? 'pending' : 'draft');
$rows = $f ? array_values(array_filter($all, fn($r) => $stOf($r) === $f)) : $all;
$cnt = array_count_values(array_map($stOf, $all)) + ['live' => 0, 'pending' => 0, 'draft' => 0];
$pub = $cnt['live'];
?>
<div class="stats"><div class="stat"><b><?= $cnt['live'] ?></b><span>Live</span></div><div class="stat"><b class="<?= $cnt['pending'] ? 'neg' : '' ?>"><?= $cnt['pending'] ?></b><span>Pending review</span></div><div class="stat"><b><?= array_sum(array_column($all, 'views')) ?></b><span>Views</span></div></div>
<div class="chips"><?php foreach (['' => 'All', 'pending' => 'Pending (' . $cnt['pending'] . ')', 'live' => 'Live', 'draft' => 'Drafts'] as $k => $v): ?><a href="?p=posts<?= $k ? "&f=$k" : '' ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?></div>
<a class="btn block" href="?p=post_edit">✍️ Write new article</a>
<div class="quick"><a href="blog" target="_blank">🌐 View blog</a><?php if (role('admin')): ?><a href="?p=pages_edit">📄 Website pages</a><?php endif ?></div>
<div class="list"><?php foreach ($rows as $r): ?>
  <a class="row" href="?p=post_edit&id=<?= $r['id'] ?>"><div class="grow"><b><?= e($r['title']) ?></b><small><?= e($r['category']) ?> · <?= date('d M Y', strtotime($r['created_at'])) ?> · <?= (int)$r['views'] ?> views<?= role('admin') && $r['author'] ? ' · ' . e($r['author']) : '' ?></small></div>
  <?php $sx = $stOf($r); ?><span class="pill <?= $sx === 'live' ? 'ok' : ($sx === 'pending' ? 'warn' : '') ?>"><?= $sx === 'live' ? 'Live' : ($sx === 'pending' ? 'Pending' : ($r['review'] === 'rejected' ? 'Sent back' : 'Draft')) ?></span></a>
<?php endforeach ?></div>
<?php if (!$rows && $f): ?><p class="empty">Nothing here.</p><?php endif ?>
<?php if (!$all): ?><p class="empty">No articles yet. Regular, original articles help Google rank your site and are required for AdSense approval.<?php if (role('admin')): ?><br><br><a class="btn ghost" href="?p=settings#demo">Load starter articles</a><?php endif ?></p><?php endif ?>
