<?php
$me = user(); $title = 'Cart'; $back = '?p=shop';
$items = cart_items();
if (!$items): ?><p class="empty">Your cart is empty.<br><a class="btn" href="?p=shop">Browse shop</a></p><?php return; endif;
$phys = (bool)array_filter($items, fn($i) => $i['type'] === 'physical');
$sub = array_sum(array_column($items, 'line')); $ship = shipping_for($sub, $phys); $free = (float)setting('shop_free_over', '0');
$kids = role('parent') ? my_children() : [];
$accts = array_filter(['Bank' => setting('pay_bank'), 'JazzCash' => setting('pay_jazzcash'), 'EasyPaisa' => setting('pay_easypaisa')]);
$cod = $phys && setting('shop_cod', '1') === '1';
?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="cart_update">
<div class="list"><?php foreach ($items as $it): ?>
  <div class="row"><div class="cthumb" style="<?= $it['image'] ? "background-image:url('" . e(product_img($it)) . "')" : '' ?>"></div><div class="grow"><b><?= e($it['title']) ?></b><small><?= money($it['price']) ?><?= $it['type'] === 'digital' ? ' · PDF' : '' ?></small></div>
  <?php if ($it['type'] === 'physical'): ?><input class="qty" type="number" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>" min="0" max="99" onchange="this.form.submit()"><?php else: ?><input type="hidden" name="qty[<?= $it['id'] ?>]" value="1"><?php endif ?>
  <button class="x" name="qty[<?= $it['id'] ?>]" value="0" aria-label="Remove">✕</button></div>
<?php endforeach ?></div></form>
<div class="card"><div class="kv"><span>Subtotal</span><b><?= money($sub) ?></b></div><?php if ($phys): ?><div class="kv"><span>Delivery</span><b><?= $ship ? money($ship) : 'Free' ?></b></div><?php if ($free > 0 && $ship): ?><small class="muted">Free delivery on orders above <?= money($free) ?></small><?php endif ?><?php endif ?>
  <hr><div class="kv total"><span>Total</span><b><?= money($sub + $ship) ?></b></div></div>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="checkout">
  <h3><?= $phys ? '🚚 Delivery details' : 'Checkout' ?></h3>
  <?php if ($kids): ?><label>For student<select name="student_id"><?php foreach ($kids as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['name']) ?></option><?php endforeach ?></select></label><?php endif ?>
  <div class="two"><input name="name" value="<?= e($me['name']) ?>" placeholder="Full name" required><input name="phone" value="<?= e($me['phone']) ?>" placeholder="Phone" <?= $phys ? 'required' : '' ?>></div>
  <?php if ($phys): ?><input name="address" placeholder="House, street, area" required><input name="city" placeholder="City" required><?php endif ?>
  <h3>💳 Payment</h3>
  <div class="paypick"><?php if ($cod): ?><label><input type="radio" name="pay_method" value="COD" checked onchange="pv()"><span>💵 Cash on delivery</span></label><?php endif ?>
    <?php foreach (['JazzCash', 'EasyPaisa', 'Bank'] as $i => $m): ?><label><input type="radio" name="pay_method" value="<?= $m ?>" <?= !$cod && !$i ? 'checked' : '' ?> onchange="pv()"><span><?= $m ?></span></label><?php endforeach ?></div>
  <div id="prepaid" <?= $cod ? 'hidden' : '' ?>>
    <?php foreach ($accts as $k => $t): ?><div class="acct"><b><?= $k ?></b><span><?= nl2br(e($t)) ?></span></div><?php endforeach ?>
    <label>Payment screenshot <small>(or upload later on the order page)</small><input type="file" name="proof" accept="image/*,application/pdf"></label>
    <input name="txn_ref" placeholder="Transaction ID (optional)">
  </div>
  <input name="note" placeholder="Order note (optional)">
  <button class="btn block">Place order · <?= money($sub + $ship) ?></button>
</form>
<script>function pv(){const c=document.querySelector('input[name=pay_method]:checked');document.getElementById('prepaid').hidden=c&&c.value==='COD'}</script>
