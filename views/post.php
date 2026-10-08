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
$ogImage = abs_url(post_cover_url($p));
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
      <img class="post-cover" src="<?= e(post_cover_url($p)) ?>" alt="<?= e($p['title']) ?>">
      <div class="prose"><?= md((string)$p['content']) ?></div>
      <div class="share"><span>Share:</span>
        <a class="sh s-wa" href="https://wa.me/?text=<?= rawurlencode($p['title'] . ' ' . $canonical) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-.9 1.2-.3.2-.6.1a8.2 8.2 0 0 1-4-3.5c-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.8.4 3.5 3.5 0 0 0-1.1 2.6 6 6 0 0 0 1.3 3.2 13.8 13.8 0 0 0 5.3 4.7c2 .8 2.7.9 3.7.7a3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.1-.3-.2-.6-.3zM12 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8zm8.4-18.2A11.8 11.8 0 0 0 1.8 17.9L.1 24l6.3-1.7a11.8 11.8 0 0 0 5.6 1.4A11.8 11.8 0 0 0 20.4 3.6z"/></svg>WhatsApp</a>
        <a class="sh s-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>Facebook</a>
        <a class="sh s-x" href="https://twitter.com/intent/tweet?url=<?= rawurlencode($canonical) ?>&text=<?= rawurlencode($p['title']) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M18.9 1.2h3.7l-8 9.2 9.4 12.4h-7.4l-5.8-7.6-6.6 7.6H.5l8.6-9.8L0 1.2h7.6l5.2 6.9 6.1-6.9zm-1.3 19.4h2L6.5 3.2H4.3l13.3 17.4z"/></svg>X</a>
        <a class="sh s-li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05a3.74 3.74 0 0 1 3.37-1.85c3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>LinkedIn</a>
        <button type="button" class="sh s-cp" onclick="navigator.clipboard&&navigator.clipboard.writeText(<?= e(json_encode($canonical, JSON_UNESCAPED_SLASHES)) ?>).then(()=>{this.textContent='✓ Copied'})">🔗 Copy link</button></div>
      <?php if ($p['author']): ?><div class="author-box"><div class="ab-img" style="<?= $tp && $tp['photo'] ? "background-image:url('" . e(photo_url($tp['photo'])) . "')" : '' ?>"><?= $tp && $tp['photo'] ? '' : e(mb_substr($p['author'], 0, 1)) ?></div>
        <div><small>Written by</small><b><?= e($p['author']) ?></b><?php if ($tp): ?><p><?= e($tp['headline']) ?></p><a href="?p=teacher&id=<?= $p['aid'] ?>">View profile →</a><?php endif ?></div></div><?php endif ?>
    </article>
    <aside class="post-side">
      <div class="side-box cta-side"><h3>Learn with <?= e($inst) ?></h3><p>Video lessons, notes and quizzes on your phone.</p><a class="btn" href="<?= $me ? '?p=courses' : (setting('allow_register', '1') === '1' ? '?p=register' : '?p=login') ?>">Browse courses</a></div>
      <?php if ($courses): ?><div class="side-box"><h3>Popular courses</h3><?php foreach ($courses as $c): ?><a class="side-course" href="./#courses"><span style="<?= cover_style($c) ?>"></span><b><?= e($c['title']) ?></b></a><?php endforeach ?></div><?php endif ?>
    </aside>
  </div>
  <?php if ($related): ?><h2 class="rel-h">Related articles</h2><div class="bgrid"><?php foreach ($related as $r): ?>
    <a class="bcard" href="<?= e(post_url($r)) ?>"><div class="bimg" style="background-image:url('<?= e(post_cover_url($r)) ?>')"></div><div class="bbody"><span class="bcat"><?= e($r['category']) ?></span><h2><?= e($r['title']) ?></h2><small><?= read_mins((string)$r['content']) ?> min read</small></div></a>
  <?php endforeach ?></div><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
