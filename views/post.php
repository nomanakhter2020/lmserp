<?php
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)get('slug')));
$p = one('SELECT p.*,u.name author,u.id aid FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE slug=?', [$slug]);
$me = user();
if (!$p || (!$p['published'] && !($me && ($me['role'] === 'admin' || (int)$me['id'] === (int)$p['aid'])))) { http_response_code(404); $pageTitle = 'Article not found'; $pageDesc = ''; require __DIR__ . '/_site_head.php'; echo '<main class="container sec-sm center"><h1>Article not found</h1><p><a class="btn" href="blog">Back to blog</a></p></main>'; require __DIR__ . '/_site_foot.php'; return; }
if ($p['published']) q('UPDATE posts SET views=views+1 WHERE id=?', [$p['id']]);
$inst = setting('institute', APP_NAME);
$pageTitle = $p['title'] . " · $inst";
$pageDesc = $p['excerpt'] ?: mb_substr(strip_tags(md((string)$p['content'])), 0, 160);
$canonical = abs_url(post_url($p));
$ogImage = $p['cover'] ? abs_url(photo_url($p['cover'])) : null;
$tp = $p['aid'] ? one('SELECT photo,headline FROM teacher_profiles WHERE user_id=? AND public=1', [$p['aid']]) : null;
$jsonld = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $p['title'], 'description' => $pageDesc, 'datePublished' => date('c', strtotime($p['created_at'])), 'dateModified' => date('c', strtotime($p['updated_at'] ?: $p['created_at'])),
  'author' => ['@type' => 'Person', 'name' => $p['author'] ?: $inst], 'publisher' => ['@type' => 'Organization', 'name' => $inst, 'logo' => ['@type' => 'ImageObject', 'url' => abs_url('assets/icon-512.png')]], 'mainEntityOfPage' => $canonical] + ($ogImage ? ['image' => $ogImage] : []);
$related = all('SELECT * FROM posts WHERE published=1 AND id<>? ORDER BY (category=?) DESC, created_at DESC LIMIT 3', [$p['id'], $p['category']]);
$courses = all('SELECT id,title,fee,color,cover FROM courses WHERE published=1 ORDER BY id DESC LIMIT 3');
require __DIR__ . '/_site_head.php';
?>
<main class="container sec-sm">
  <nav class="crumbs"><a href="./">Home</a> › <a href="blog">Blog</a> › <a href="blog?cat=<?= e(rawurlencode($p['category'])) ?>"><?= e($p['category']) ?></a></nav>
  <div class="post-grid">
    <article class="post">
      <?php if (!$p['published']): ?><div class="cv-note">Draft — not visible to the public.</div><?php endif ?>
      <span class="bcat"><?= e($p['category']) ?></span>
      <h1><?= e($p['title']) ?></h1>
      <div class="post-meta"><?php if ($p['author']): ?><?php if ($tp): ?><a href="?p=teacher&id=<?= $p['aid'] ?>"><?= e($p['author']) ?></a><?php else: ?><?= e($p['author']) ?><?php endif ?> · <?php endif ?><?= date('F j, Y', strtotime($p['created_at'])) ?> · <?= read_mins((string)$p['content']) ?> min read</div>
      <?php if ($p['cover']): ?><img class="post-cover" src="<?= e(photo_url($p['cover'])) ?>" alt="<?= e($p['title']) ?>"><?php endif ?>
      <div class="prose"><?= md((string)$p['content']) ?></div>
      <div class="share"><span>Share:</span>
        <a href="https://wa.me/?text=<?= rawurlencode($p['title'] . ' ' . $canonical) ?>" target="_blank" rel="noopener">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener">Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($canonical) ?>&text=<?= rawurlencode($p['title']) ?>" target="_blank" rel="noopener">X</a></div>
      <?php if ($p['author']): ?><div class="author-box"><div class="ab-img" style="<?= $tp && $tp['photo'] ? "background-image:url('" . e(photo_url($tp['photo'])) . "')" : '' ?>"><?= $tp && $tp['photo'] ? '' : e(mb_substr($p['author'], 0, 1)) ?></div>
        <div><small>Written by</small><b><?= e($p['author']) ?></b><?php if ($tp): ?><p><?= e($tp['headline']) ?></p><a href="?p=teacher&id=<?= $p['aid'] ?>">View profile →</a><?php endif ?></div></div><?php endif ?>
    </article>
    <aside class="post-side">
      <div class="side-box cta-side"><h3>Learn with <?= e($inst) ?></h3><p>Video lessons, notes and quizzes on your phone.</p><a class="btn" href="<?= $me ? '?p=courses' : (setting('allow_register', '1') === '1' ? '?p=register' : '?p=login') ?>">Browse courses</a></div>
      <?php if ($courses): ?><div class="side-box"><h3>Popular courses</h3><?php foreach ($courses as $c): ?><a class="side-course" href="./#courses"><span style="<?= $c['cover'] ? "background-image:url('" . e(photo_url($c['cover'])) . "')" : 'background:' . e($c['color']) ?>"></span><b><?= e($c['title']) ?></b></a><?php endforeach ?></div><?php endif ?>
    </aside>
  </div>
  <?php if ($related): ?><h2 class="rel-h">Related articles</h2><div class="bgrid"><?php foreach ($related as $r): ?>
    <a class="bcard" href="<?= e(post_url($r)) ?>"><div class="bimg" style="<?= $r['cover'] ? "background-image:url('" . e(photo_url($r['cover'])) . "')" : '' ?>"><?php if (!$r['cover']): ?><span><?= e(mb_substr($r['title'], 0, 1)) ?></span><?php endif ?></div><div class="bbody"><span class="bcat"><?= e($r['category']) ?></span><h2><?= e($r['title']) ?></h2><small><?= read_mins((string)$r['content']) ?> min read</small></div></a>
  <?php endforeach ?></div><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
