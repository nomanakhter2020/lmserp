<?php
$p = one('SELECT pr.*,c.title ctitle,t.name tname FROM products pr LEFT JOIN courses c ON c.id=pr.course_id LEFT JOIN users t ON t.id=pr.teacher_id WHERE pr.id=? AND pr.active=1', [$id]);
$inst = setting('institute', APP_NAME);
if (!$p) { http_response_code(404); $pageTitle = 'Product not found'; $pageDesc = ''; require __DIR__ . '/_site_head.php'; echo '<main class="container sec-sm center"><h1>Product not found</h1><a class="btn" href="shop">Back to shop</a></main>'; require __DIR__ . '/_site_foot.php'; return; }
$out = $p['stock'] !== null && (int)$p['stock'] === 0; $sale = $p['compare_price'] && $p['compare_price'] > $p['price'];
$pageTitle = $p['title'] . ' · ' . $inst; $pageDesc = mb_substr(trim(preg_replace('/\s+/', ' ', (string)$p['description'])) ?: $p['title'], 0, 160);
$canonical = abs_url(product_url($p)); $ogImage = $p['image'] ? abs_url(product_img($p)) : null;
$jsonld = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['title'], 'description' => $pageDesc, 'category' => $p['category'], 'brand' => ['@type' => 'Brand', 'name' => $inst], 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'PKR', 'price' => (float)$p['price'], 'availability' => $out ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock', 'url' => $canonical]] + ($ogImage ? ['image' => $ogImage] : []);
$related = all('SELECT * FROM products WHERE active=1 AND id<>? ORDER BY (category=?) DESC, id DESC LIMIT 4', [$id, $p['category']]);
require __DIR__ . '/_site_head.php';
$free = (float)setting('shop_free_over', '0');
?>
<main class="container sec-sm">
  <nav class="crumbs"><a href="./">Home</a> › <a href="shop">Shop</a> › <a href="shop?cat=<?= e(rawurlencode($p['category'])) ?>"><?= e($p['category']) ?></a></nav>
  <?php if ($f = flash()): ?><div class="cv-note <?= $f[1] === 'ok' ? 'ok' : '' ?>"><?= e($f[0]) ?></div><?php endif ?>
  <div class="pgrid">
    <div class="pphoto" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"><?php if (!$p['image']): ?><span><?= $p['type'] === 'digital' ? '📄' : '📚' ?></span><?php endif ?><?php if ($sale && !$out): ?><i class="badge-s">-<?= round(100 - $p['price'] * 100 / $p['compare_price']) ?>%</i><?php endif ?></div>
    <div class="pinfo">
      <span class="bcat"><?= e($p['category']) ?></span>
      <h1><?= e($p['title']) ?></h1>
      <?php if ($p['tname']): ?><p class="muted" style="margin:-4px 0 10px">By <a href="?p=teacher&id=<?= (int)$p['teacher_id'] ?>"><b><?= e($p['tname']) ?></b></a></p><?php endif ?>
      <div class="pprice"><b><?= money($p['price']) ?></b><?php if ($sale): ?><s><?= money($p['compare_price']) ?></s><span class="save">Save <?= money($p['compare_price'] - $p['price']) ?></span><?php endif ?></div>
      <?php if ($p['type'] === 'digital'): ?><p class="stock ok">📄 Digital PDF — download instantly after payment</p>
      <?php elseif ($out): ?><p class="stock bad">Out of stock</p>
      <?php elseif ($p['stock'] !== null && (int)$p['stock'] <= 5): ?><p class="stock warn">🔥 Only <?= (int)$p['stock'] ?> left in stock</p>
      <?php else: ?><p class="stock ok">✓ In stock — ready to ship</p><?php endif ?>
      <?php if (!$out): ?>
      <form method="post" class="pbuy"><?= csrf_field() ?><input type="hidden" name="a" value="store_add"><input type="hidden" name="id" value="<?= $id ?>">
        <?php if ($p['type'] === 'physical'): ?><div class="stepper"><button type="button" onclick="const i=this.nextElementSibling;i.stepDown();">−</button><input name="qty" type="number" value="1" min="1" max="<?= $p['stock'] !== null ? (int)$p['stock'] : 99 ?>"><button type="button" onclick="const i=this.previousElementSibling;i.stepUp();">+</button></div><?php endif ?>
        <button class="btn-o lg">Add to cart</button><button class="btn lg" name="buy_now" value="1">Buy now</button>
      </form>
      <?php endif ?>
      <ul class="ptrust"><?php if ($p['type'] === 'physical'): ?><li>🚚 Delivery in 3–5 working days<?= $free > 0 ? ' · free over ' . money($free) : '' ?></li><?php if (setting('shop_cod', '1') === '1'): ?><li>💵 Cash on delivery available</li><?php endif ?><?php endif ?><li>💳 JazzCash · EasyPaisa · Bank transfer</li><li>💬 Questions? WhatsApp us anytime</li></ul>
      <?php if ($p['ctitle']): ?><p class="muted" style="font-size:14px">📘 Used in our course: <b><?= e($p['ctitle']) ?></b></p><?php endif ?>
      <?php if ($p['description']): ?><div class="prose pdesc"><?= md((string)$p['description']) ?></div><?php endif ?>
    </div>
  </div>
  <?php if ($related): ?><h2 class="rel-h">You may also like</h2><div class="sgrid"><?php foreach ($related as $p) require __DIR__ . '/_store_card.php'; ?></div><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
