<?php
require_role('admin');
$title = 'Products'; $back = '?p=orders';
$rows = all('SELECT p.*,(SELECT COALESCE(SUM(qty),0) FROM order_items i JOIN orders o ON o.id=i.order_id WHERE i.product_id=p.id AND o.status<>"cancelled") sold FROM products p ORDER BY p.active DESC, p.id DESC');
?>
<a class="btn block" href="?p=product_edit">＋ Add product</a>
<div class="list"><?php foreach ($rows as $p): ?>
  <a class="row <?= $p['active'] ? '' : 'locked' ?>" href="?p=product_edit&id=<?= $p['id'] ?>"><div class="cthumb" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"></div><div class="grow"><b><?= e($p['title']) ?></b><small><?= e($p['category']) ?> · <?= $p['type'] === 'digital' ? 'PDF' : ($p['stock'] === null ? 'Unlimited' : (int)$p['stock'] . ' in stock') ?> · <?= (int)$p['sold'] ?> sold</small></div>
  <b><?= money($p['price']) ?></b><?php if ($p['stock'] !== null && (int)$p['stock'] <= 5 && $p['type'] === 'physical'): ?><span class="pill err">Low</span><?php endif ?></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No products yet. Add task books, workbooks, kits or PDF worksheets.</p><?php endif ?>
