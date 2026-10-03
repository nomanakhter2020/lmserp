<?php
$p = one('SELECT pr.*,c.title ctitle FROM products pr LEFT JOIN courses c ON c.id=pr.course_id WHERE pr.id=? AND (pr.active=1 OR ?)', [$id, role('admin') ? 1 : 0]);
if (!$p) { echo '<p class="empty">Product not found</p>'; return; }
$title = $p['title']; $back = '?p=shop'; $out = $p['stock'] !== null && (int)$p['stock'] === 0;
?>
<div class="pimg big" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"><?php if (!$p['image']): ?><span><?= $p['type'] === 'digital' ? '📄' : '📚' ?></span><?php endif ?></div>
<div class="card">
  <small><?= e($p['category']) ?><?= $p['type'] === 'digital' ? ' · Digital download (PDF)' : '' ?><?= $p['ctitle'] ? ' · for ' . e($p['ctitle']) : '' ?></small>
  <h1 style="margin:4px 0 6px"><?= e($p['title']) ?></h1>
  <div><span class="price" style="font-size:24px;font-weight:800;color:var(--p)"><?= money($p['price']) ?></span><?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?> <s class="muted"><?= money($p['compare_price']) ?></s> <span class="pill ok"><?= round(100 - $p['price'] * 100 / $p['compare_price']) ?>% off</span><?php endif ?></div>
  <?php if ($p['stock'] !== null && $p['type'] === 'physical'): ?><small class="<?= $out ? 'neg' : ((int)$p['stock'] <= 5 ? 'neg' : 'pos') ?>"><?= $out ? 'Out of stock' : ((int)$p['stock'] <= 5 ? 'Only ' . (int)$p['stock'] . ' left' : 'In stock') ?></small><?php endif ?>
  <?php if ($p['description']): ?><div class="content" style="margin-top:12px;font-size:15px"><?= nl2br(e($p['description'])) ?></div><?php endif ?>
</div>
<?php if (!$out): ?>
<form method="post" class="buybar"><?= csrf_field() ?><input type="hidden" name="a" value="cart_add"><input type="hidden" name="id" value="<?= $id ?>">
  <?php if ($p['type'] === 'physical'): ?><input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] !== null ? (int)$p['stock'] : 99 ?>"><?php endif ?>
  <button class="btn grow">🛒 Add to cart</button></form>
<?php endif ?>
<?php if (role('admin')): ?><a class="btn ghost block" href="?p=product_edit&id=<?= $id ?>">✏️ Edit product</a><?php endif ?>
