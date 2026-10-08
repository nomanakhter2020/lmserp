<?php
$cat = trim((string)get('cat')); $s = trim((string)get('s')); $pg = max(1, (int)get('page', 1)); $per = 12;
$w = 'published=1'; $pr = [];
if ($cat !== '') { $w .= ' AND category=?'; $pr[] = $cat; }
if ($s !== '') { $w .= ' AND (title LIKE ? OR excerpt LIKE ?)'; $pr[] = "%$s%"; $pr[] = "%$s%"; }
$total = (int)val("SELECT COUNT(*) FROM posts WHERE $w", $pr);
$posts = all("SELECT p.*,u.name author FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE $w ORDER BY p.created_at DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $pr);
$cats = all('SELECT category,COUNT(*) n FROM posts WHERE published=1 GROUP BY category ORDER BY category');
$inst = setting('institute', APP_NAME);
$pageTitle = ($cat ? "$cat articles" : 'Blog — free study notes, guides & tips') . " · $inst";
$pageDesc = "Free study guides, exam tips, notes and career advice from the teachers at $inst." . ($cat ? " Articles about $cat." : '');
$canonical = abs_url('blog' . ($cat ? '?cat=' . rawurlencode($cat) : '') . ($pg > 1 ? ($cat ? '&' : '?') . "page=$pg" : ''));
require __DIR__ . '/_site_head.php';
?>
<section class="page-hero"><div class="container">
  <span class="kicker">Blog</span><h1><?= $cat ? e($cat) : 'Free study notes, guides &amp; tips' ?></h1>
  <p>Practical articles written by our teachers to help you study smarter.</p>
  <form class="blog-search" action="blog"><?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif ?><input name="s" value="<?= e($s) ?>" placeholder="Search articles…" type="search"><button class="btn">Search</button></form>
</div></section>
<main class="container sec-sm">
  <div class="chips pubchips"><a href="blog" class="<?= $cat === '' ? 'on' : '' ?>">All</a><?php foreach ($cats as $c): ?><a href="blog?cat=<?= e(rawurlencode($c['category'])) ?>" class="<?= $cat === $c['category'] ? 'on' : '' ?>"><?= e($c['category']) ?> <small><?= $c['n'] ?></small></a><?php endforeach ?></div>
  <div class="bgrid">
  <?php foreach ($posts as $p): ?>
    <a class="bcard" href="<?= e(post_url($p)) ?>">
      <div class="bimg" style="background-image:url('<?= e(post_cover_url($p)) ?>')"></div>
      <div class="bbody"><span class="bcat"><?= e($p['category']) ?></span><h2><?= e($p['title']) ?></h2><p><?= e($p['excerpt']) ?></p>
        <small><?= date('M j, Y', strtotime($p['created_at'])) ?> · <?= read_mins((string)$p['content']) ?> min read</small></div>
    </a>
  <?php endforeach ?>
  </div>
  <?php if (!$posts): ?><p class="center muted">No articles found.</p><?php endif ?>
  <?php if ($total > $per): $pages = (int)ceil($total / $per); ?><nav class="pagin"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $pg ? 'on' : '' ?>" href="blog?<?= http_build_query(array_filter(['cat' => $cat, 's' => $s, 'page' => $i > 1 ? $i : null])) ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
