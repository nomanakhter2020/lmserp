<?php
$inst = setting('institute', APP_NAME); $pageTitle = 'Your cart · ' . $inst; $pageDesc = 'Shopping cart'; $canonical = abs_url('cart');
$items = cart_items(); require __DIR__ . '/_site_head.php';
$phys = (bool)array_filter($items, fn($i) => $i['type'] === 'physical');
$sub = array_sum(array_column($items, 'line')); $ship = shipping_for($sub, $phys); $free = (float)setting('shop_free_over', '0');
?>
<main class="container sec-sm">
  <h1 class="pg-h">Your cart</h1>
  <?php if ($f = flash()): ?><div class="cv-note <?= $f[1] === 'ok' ? 'ok' : '' ?>"><?= e($f[0]) ?></div><?php endif ?>
  <?php if (!$items): ?><div class="empty-cart"><span>🛒</span><p>Your cart is empty.</p><a class="btn lg" href="shop">Start shopping</a></div>
  <?php else: ?>
  <div class="cgrid2">
    <form method="post" class="clist"><?= csrf_field() ?><input type="hidden" name="a" value="store_update">
      <?php foreach ($items as $it): ?>
      <div class="citem"><a class="cimg" href="<?= e(product_url($it)) ?>" style="<?= $it['image'] ? "background-image:url('" . e(product_img($it)) . "')" : '' ?>"><?= $it['image'] ? '' : '📚' ?></a>
        <div class="cmeta"><a href="<?= e(product_url($it)) ?>"><b><?= e($it['title']) ?></b></a><small><?= money($it['price']) ?><?= $it['type'] === 'digital' ? ' · PDF download' : '' ?></small>
          <div class="crow"><?php if ($it['type'] === 'physical'): ?><div class="stepper sm"><button type="button" onclick="const i=this.nextElementSibling;i.stepDown();i.form.submit()">−</button><input name="qty[<?= $it['id'] ?>]" type="number" value="<?= $it['qty'] ?>" min="0" max="99" onchange="this.form.submit()"><button type="button" onclick="const i=this.previousElementSibling;i.stepUp();i.form.submit()">+</button></div><?php else: ?><input type="hidden" name="qty[<?= $it['id'] ?>]" value="1"><?php endif ?>
            <button class="linkbtn" name="qty[<?= $it['id'] ?>]" value="0">Remove</button></div></div>
        <b class="cline"><?= money($it['line']) ?></b></div>
      <?php endforeach ?>
      <a class="muted" href="shop">← Continue shopping</a>
    </form>
    <aside class="csum">
      <h3>Order summary</h3>
      <?php if ($phys && $free > 0): $left = $free - $sub; ?><div class="freebar"><small><?= $left > 0 ? 'Add ' . money($left) . ' more for <b>free delivery</b>' : '🎉 You get <b>free delivery</b>' ?></small><div class="fb"><i style="width:<?= min(100, round($sub * 100 / $free)) ?>%"></i></div></div><?php endif ?>
      <div class="kvs"><span>Subtotal</span><b><?= money($sub) ?></b></div>
      <?php if ($phys): ?><div class="kvs"><span>Delivery</span><b><?= $ship ? money($ship) : 'Free' ?></b></div><?php endif ?>
      <div class="kvs tot"><span>Total</span><b><?= money($sub + $ship) ?></b></div>
      <a class="btn lg block-b" href="checkout">Checkout →</a>
      <p class="muted center" style="font-size:13px"><?= setting('shop_cod', '1') === '1' && $phys ? '💵 Cash on delivery available' : '💳 JazzCash · EasyPaisa · Bank' ?></p>
    </aside>
  </div>
  <?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
