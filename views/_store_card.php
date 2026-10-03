<?php /* expects $p */ $out = $p['stock'] !== null && (int)$p['stock'] === 0; $sale = $p['compare_price'] && $p['compare_price'] > $p['price']; ?>
<div class="scard <?= $out ? 'out' : '' ?>">
  <a class="simg" href="<?= e(product_url($p)) ?>" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"><?php if (!$p['image']): ?><span><?= $p['type'] === 'digital' ? '📄' : '📚' ?></span><?php endif ?>
    <?php if ($out): ?><i class="badge-s out">Sold out</i><?php elseif ($sale): ?><i class="badge-s">-<?= round(100 - $p['price'] * 100 / $p['compare_price']) ?>%</i><?php endif ?><?php if ($p['type'] === 'digital'): ?><i class="badge-s dg">PDF</i><?php endif ?></a>
  <div class="sbody"><small><?= e($p['category']) ?></small><a href="<?= e(product_url($p)) ?>"><h3><?= e($p['title']) ?></h3></a>
    <div class="sprice"><b><?= money($p['price']) ?></b><?php if ($sale): ?><s><?= money($p['compare_price']) ?></s><?php endif ?></div>
    <?php if (!$out): ?><form method="post" action="shop"><?= csrf_field() ?><input type="hidden" name="a" value="store_add"><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? 'shop') ?>"><button class="btn sm sadd">Add to cart</button></form><?php endif ?></div>
</div>
