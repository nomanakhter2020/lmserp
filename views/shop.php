<?php
$title = 'Shop'; $cat = trim((string)get('cat')); $s = trim((string)get('s'));
$w = 'active=1'; $pr = [];
if ($cat !== '') { $w .= ' AND category=?'; $pr[] = $cat; }
if ($s !== '') { $w .= ' AND title LIKE ?'; $pr[] = "%$s%"; }
$items = all("SELECT * FROM products WHERE $w ORDER BY (stock IS NOT NULL AND stock=0), id DESC", $pr);
$cats = all('SELECT category,COUNT(*) n FROM products WHERE active=1 GROUP BY category ORDER BY category');
$cc = cart_count();
?>
<div class="rowhead" style="margin-bottom:10px"><form class="search grow" style="margin:0"><input type="hidden" name="p" value="shop"><input name="s" value="<?= e($s) ?>" placeholder="Search books & materials…" type="search" style="margin:0"></form>
  <a class="btn sm cartbtn" href="?p=cart">🛒<?= $cc ? " $cc" : '' ?></a></div>
<?php if ($cats): ?><div class="chips"><a href="?p=shop" class="<?= $cat === '' ? 'on' : '' ?>">All</a><?php foreach ($cats as $c): ?><a href="?p=shop&cat=<?= e(rawurlencode($c['category'])) ?>" class="<?= $cat === $c['category'] ? 'on' : '' ?>"><?= e($c['category']) ?></a><?php endforeach ?></div><?php endif ?>
<div class="quick"><a href="?p=orders">📦 My orders</a><?php if (role('admin')): ?><a href="?p=products">🛠 Manage products</a><?php endif ?></div>
<div class="grid">
<?php foreach ($items as $p): $out = $p['stock'] !== null && (int)$p['stock'] === 0; ?>
  <a class="ccard pcard <?= $out ? 'locked' : '' ?>" href="?p=product&id=<?= $p['id'] ?>">
    <div class="pimg" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"><?php if (!$p['image']): ?><span><?= $p['type'] === 'digital' ? '📄' : '📚' ?></span><?php endif ?><?php if ($p['type'] === 'digital'): ?><i class="tag">PDF</i><?php endif ?><?php if ($out): ?><i class="tag out">Sold out</i><?php endif ?></div>
    <b><?= e($p['title']) ?></b><small><?= e($p['category']) ?></small>
    <div class="cfoot"><span class="price"><?= money($p['price']) ?></span><?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?><s class="muted" style="font-size:12.5px"><?= money($p['compare_price']) ?></s><?php endif ?></div>
  </a>
<?php endforeach ?>
</div>
<?php if (!$items): ?><p class="empty">No products yet.<?php if (role('admin')): ?><br><a class="btn" href="?p=product_edit">＋ Add first product</a><?php endif ?></p><?php endif ?>
