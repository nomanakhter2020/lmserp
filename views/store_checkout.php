<?php
$inst = setting('institute', APP_NAME); $pageTitle = 'Checkout · ' . $inst; $pageDesc = 'Checkout'; $canonical = abs_url('checkout');
$items = cart_items(); if (!$items) { header('Location: cart'); exit; }
require __DIR__ . '/_site_head.php';
$phys = (bool)array_filter($items, fn($i) => $i['type'] === 'physical');
$sub = array_sum(array_column($items, 'line')); $ship = shipping_for($sub, $phys);
$u = user(); $co = ($_SESSION['co'] ?? []) + ['name' => $u['name'] ?? '', 'phone' => $u['phone'] ?? '', 'email' => $u['email'] ?? '', 'address' => '', 'city' => '', 'note' => '', 'pay_method' => ''];
$cod = $phys && setting('shop_cod', '1') === '1'; $pm = $co['pay_method'] ?: ($cod ? 'COD' : 'JazzCash');
$accts = array_filter(['JazzCash' => setting('pay_jazzcash'), 'EasyPaisa' => setting('pay_easypaisa'), 'Bank' => setting('pay_bank')]);
$kids = $u && $u['role'] === 'parent' ? my_children() : [];
?>
<main class="container sec-sm">
  <h1 class="pg-h">Checkout</h1>
  <?php if ($f = flash()): ?><div class="cv-note"><?= e($f[0]) ?></div><?php endif ?>
  <form method="post" action="checkout" enctype="multipart/form-data" class="cgrid2"><?= csrf_field() ?><input type="hidden" name="a" value="store_checkout">
    <div class="cform co">
      <?php if (!$u): ?><p class="muted" style="margin-top:0">Have an account? <a href="?p=login"><b>Log in</b></a> — or continue as guest.</p><?php endif ?>
      <h3>1. Contact</h3>
      <div class="two"><label>Full name<input name="name" value="<?= e($co['name']) ?>" required autocomplete="name"></label><label>Phone / WhatsApp<input name="phone" value="<?= e($co['phone']) ?>" required inputmode="tel" autocomplete="tel" placeholder="03XX-XXXXXXX"></label></div>
      <label>Email <small><?= $phys ? '(optional)' : '(for your download link)' ?></small><input name="email" type="email" value="<?= e($co['email']) ?>" <?= $phys ? '' : 'required' ?> autocomplete="email"></label>
      <?php if ($kids): ?><label>For student<select name="student_id"><?php foreach ($kids as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['name']) ?></option><?php endforeach ?></select></label><?php endif ?>
      <?php if ($phys): ?><h3>2. Delivery address</h3>
      <label>Address<input name="address" value="<?= e($co['address']) ?>" required autocomplete="street-address" placeholder="House no, street, area"></label>
      <label>City<input name="city" value="<?= e($co['city']) ?>" required autocomplete="address-level2" list="cities"><datalist id="cities"><?php foreach (['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Hyderabad', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala'] as $ct): ?><option value="<?= $ct ?>"><?php endforeach ?></datalist></label><?php endif ?>
      <h3><?= $phys ? '3' : '2' ?>. Payment</h3>
      <div class="paylist">
        <?php if ($cod): ?><label class="payopt"><input type="radio" name="pay_method" value="COD" <?= $pm === 'COD' ? 'checked' : '' ?> onchange="pv()"><span><b>💵 Cash on delivery</b><small>Pay when your order arrives</small></span></label><?php endif ?>
        <?php foreach (['JazzCash', 'EasyPaisa', 'Bank'] as $m): ?><label class="payopt"><input type="radio" name="pay_method" value="<?= $m ?>" <?= $pm === $m ? 'checked' : '' ?> onchange="pv()"><span><b><?= $m === 'Bank' ? '🏦 Bank transfer' : '📱 ' . $m ?></b><small><?= e($accts[$m] ?? 'Details shared after order') ?></small></span></label><?php endforeach ?>
      </div>
      <div id="prepaid" <?= $pm === 'COD' ? 'hidden' : '' ?> class="prepaid">
        <p>Send <b><?= money($sub + $ship) ?></b> to the account above, then upload the screenshot (you can also do this later from the order page).</p>
        <label>Payment screenshot<input type="file" name="proof" accept="image/*,application/pdf"></label>
        <label>Transaction ID <small>(optional)</small><input name="txn_ref"></label>
      </div>
      <label>Order note <small>(optional)</small><input name="note" value="<?= e($co['note']) ?>"></label>
    </div>
    <aside class="csum">
      <h3>Your order</h3>
      <?php foreach ($items as $it): ?><div class="sumi"><span class="si" style="<?= $it['image'] ? "background-image:url('" . e(product_img($it)) . "')" : '' ?>"><i><?= $it['qty'] ?></i></span><span class="grow"><?= e($it['title']) ?></span><b><?= money($it['line']) ?></b></div><?php endforeach ?>
      <div class="kvs"><span>Subtotal</span><b><?= money($sub) ?></b></div><?php if ($phys): ?><div class="kvs"><span>Delivery</span><b><?= $ship ? money($ship) : 'Free' ?></b></div><?php endif ?>
      <div class="kvs tot"><span>Total</span><b><?= money($sub + $ship) ?></b></div>
      <button class="btn lg block-b">Place order</button>
      <p class="muted center" style="font-size:12.5px">By placing your order you agree to our <a href="terms">terms</a>.</p>
    </aside>
  </form>
</main>
<script>function pv(){const c=document.querySelector('input[name=pay_method]:checked');document.getElementById('prepaid').hidden=!c||c.value==='COD'}</script>
<?php require __DIR__ . '/_site_foot.php';
