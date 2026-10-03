<?php
$inst = setting('institute', APP_NAME);
$cat = trim((string)get('cat')); $s = trim((string)get('q')); $sort = get('sort', 'new');
$w = 'active=1'; $pr = [];
if ($cat !== '') { $w .= ' AND category=?'; $pr[] = $cat; }
if ($s !== '') { $w .= ' AND (title LIKE ? OR description LIKE ?)'; $pr[] = "%$s%"; $pr[] = "%$s%"; }
$order = ['low' => 'price ASC', 'high' => 'price DESC', 'new' => 'id DESC'][$sort] ?? 'id DESC';
$items = all("SELECT * FROM products WHERE $w ORDER BY (stock IS NOT NULL AND stock=0), $order", $pr);
$cats = all('SELECT category,COUNT(*) n FROM products WHERE active=1 GROUP BY category ORDER BY category');
$pageTitle = ($cat ?: 'Shop') . ' · ' . $inst; $pageDesc = "Buy task books, workbooks, learning kits and printable worksheets from $inst. Cash on delivery across Pakistan.";
$canonical = abs_url('shop' . ($cat ? '?cat=' . rawurlencode($cat) : ''));
require __DIR__ . '/_site_head.php';
$free = (float)setting('shop_free_over', '0');
?>
<section class="shop-hero"><div class="container">
  <div><span class="kicker">Shop</span><h1><?= $cat ? e($cat) : 'Books, kits &amp; learning material' ?></h1><p>Task books and resources made for our courses — delivered to your door.</p></div>
  <div class="trust-row"><span>🚚 Delivery all over Pakistan</span><?php if (setting('shop_cod', '1') === '1'): ?><span>💵 Cash on delivery</span><?php endif ?><?php if ($free > 0): ?><span>🎁 Free delivery over <?= money($free) ?></span><?php endif ?><span>📄 Instant PDF downloads</span></div>
</div></section>
<main class="container sec-sm">
  <form class="shop-tools" action="shop"><input name="q" value="<?= e($s) ?>" placeholder="Search products…" type="search"><?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif ?>
    <select name="sort" onchange="this.form.submit()"><option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option><option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Price: low to high</option><option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option></select></form>
  <?php if ($cats): ?><div class="chips pubchips"><a href="shop" class="<?= $cat === '' ? 'on' : '' ?>">All</a><?php foreach ($cats as $c): ?><a href="shop?cat=<?= e(rawurlencode($c['category'])) ?>" class="<?= $cat === $c['category'] ? 'on' : '' ?>"><?= e($c['category']) ?> <small><?= $c['n'] ?></small></a><?php endforeach ?></div><?php endif ?>
  <?php if ($f = flash()): ?><div class="cv-note <?= $f[1] === 'ok' ? 'ok' : '' ?>"><?= e($f[0]) ?> <?php if ($f[1] === 'ok'): ?><a href="cart"><b>View cart →</b></a><?php endif ?></div><?php endif ?>
  <div class="sgrid"><?php foreach ($items as $p) require __DIR__ . '/_store_card.php'; ?></div>
  <?php if (!$items): ?><p class="center muted">No products found.</p><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
