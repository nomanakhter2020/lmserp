<?php
$inst = setting('institute', APP_NAME);
$o = (int)get('o') ? one('SELECT * FROM orders WHERE id=?', [(int)get('o')]) : null;
if (!$o && get('no') !== '' && get('phone') !== '') { // lookup by order number + phone
    $no = (int)preg_replace('/\D/', '', (string)get('no')); $cand = $no ? one('SELECT * FROM orders WHERE id=?', [$no]) : null;
    $ph = substr(preg_replace('/\D/', '', (string)get('phone')), -10);
    if ($cand && $ph !== '' && substr(preg_replace('/\D/', '', $cand['phone']), -10) === $ph) { $_SESSION['my_orders'][$cand['id']] = $cand['token']; header('Location: ' . order_track_url($cand)); exit; }
    $notFound = true;
}
if ($o && !can_view_order($o)) $o = null;
$pageTitle = ($o ? order_no($o) : 'Track your order') . ' · ' . $inst; $pageDesc = 'Track your order'; $canonical = abs_url('track');
require __DIR__ . '/_site_head.php';
?>
<main class="container sec-sm narrow">
<?php if (!$o): ?>
  <h1 class="pg-h">Track your order</h1>
  <?php if (!empty($notFound)): ?><div class="cv-note">We couldn't find an order with that number and phone. Please check and try again.</div><?php endif ?>
  <form class="cform" action="track"><label>Order number<input name="no" placeholder="e.g. ORD-00012" required value="<?= e(get('no')) ?>"></label><label>Phone used for the order<input name="phone" required inputmode="tel" value="<?= e(get('phone')) ?>"></label><button class="btn lg">Track order</button></form>
  <?php $mine = !empty($_SESSION['my_orders']) ? all('SELECT * FROM orders WHERE id IN (' . implode(',', array_map('intval', array_keys($_SESSION['my_orders']))) . ') ORDER BY id DESC') : []; if ($mine): ?>
  <h3 style="margin-top:28px">Your recent orders</h3><div class="cform"><?php foreach ($mine as $m): ?><a class="sumi" href="<?= e(order_track_url($m)) ?>"><span class="grow"><b><?= order_no($m) ?></b> <small class="muted"><?= date('d M Y', strtotime($m['created_at'])) ?></small></span><span><?= e(ORDER_ST[$m['status']][0] ?? $m['status']) ?></span><b><?= money($m['total']) ?></b></a><?php endforeach ?></div><?php endif ?>
<?php else:
  $items = all('SELECT * FROM order_items WHERE order_id=?', [$o['id']]);
  $paid = in_array($o['status'], ['paid', 'processing', 'shipped', 'delivered'], true);
  $steps = ['pending' => 'Order placed', 'paid' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered']; $order = array_keys(ORDER_ST); $cur = array_search($o['status'] === 'processing' ? 'paid' : $o['status'], $order);
  $wa = wa_num(setting('site_whatsapp') ?: setting('phone')); $accts = array_filter(['JazzCash' => setting('pay_jazzcash'), 'EasyPaisa' => setting('pay_easypaisa'), 'Bank' => setting('pay_bank')]);
?>
  <?php if (get('new')): ?><div class="thanks"><span>✅</span><h1>Thank you, <?= e(explode(' ', $o['name'])[0]) ?>!</h1><p>Your order <b><?= order_no($o) ?></b> has been placed. <?= $o['pay_method'] === 'COD' ? 'Please keep ' . money($o['total']) . ' ready — you pay when it arrives.' : ($o['proof'] ? 'We will confirm your payment shortly.' : 'Please complete your payment below.') ?></p><p class="muted" style="font-size:13px">Save this page or note your order number to track it later.</p></div>
  <?php else: ?><h1 class="pg-h"><?= order_no($o) ?></h1><?php endif ?>
  <?php if ($f = flash()): ?><div class="cv-note <?= $f[1] === 'ok' ? 'ok' : '' ?>"><?= e($f[0]) ?></div><?php endif ?>
  <?php if ($o['status'] === 'cancelled'): ?><div class="cv-note">This order was cancelled.</div><?php else: ?>
  <div class="otrack"><?php foreach ($steps as $k => $l): ?><div class="<?= array_search($k, $order) <= $cur ? 'on' : '' ?>"><i></i><span><?= $l ?></span></div><?php endforeach ?></div><?php endif ?>
  <?php if ($o['tracking']): ?><div class="cv-note ok">🚚 Courier tracking: <b><?= e($o['tracking']) ?></b></div><?php endif ?>
  <?php if ($o['admin_note']): ?><div class="cv-note"><?= e($o['admin_note']) ?></div><?php endif ?>
  <div class="cform"><?php foreach ($items as $it): ?><div class="sumi"><span class="grow"><?= e($it['title']) ?> <small class="muted">× <?= $it['qty'] ?></small></span><b><?= money($it['qty'] * $it['price']) ?></b><?php if ($it['type'] === 'digital'): ?><?= $paid ? '<a class="btn sm" href="?p=dl&id=' . $it['id'] . '&t=' . e($o['token']) . '">⬇ Download</a>' : '<small class="muted">Download after payment</small>' ?><?php endif ?></div><?php endforeach ?>
    <div class="kvs"><span>Subtotal</span><b><?= money($o['subtotal']) ?></b></div><div class="kvs"><span>Delivery</span><b><?= (float)$o['shipping'] ? money($o['shipping']) : 'Free' ?></b></div><div class="kvs tot"><span>Total</span><b><?= money($o['total']) ?></b></div>
    <div class="kvs"><span>Payment</span><b><?= e($o['pay_method'] === 'COD' ? 'Cash on delivery' : $o['pay_method']) ?></b></div>
    <?php if ($o['address']): ?><div class="kvs"><span>Deliver to</span><b style="text-align:right"><?= e($o['name']) ?>, <?= e($o['address']) ?>, <?= e($o['city']) ?> · <?= e($o['phone']) ?></b></div><?php endif ?></div>
  <?php if ($o['status'] === 'pending' && $o['pay_method'] !== 'COD' && !$o['proof']): ?>
  <form method="post" action="track" enctype="multipart/form-data" class="cform" style="margin-top:16px"><?= csrf_field() ?><input type="hidden" name="a" value="track_proof"><input type="hidden" name="id" value="<?= $o['id'] ?>">
    <h3>Complete your payment</h3><?php foreach ($accts as $k => $t): ?><div class="acct-pub"><b><?= $k ?></b><span><?= nl2br(e($t)) ?></span></div><?php endforeach ?>
    <label>Method<select name="pay_method"><?php foreach (['JazzCash', 'EasyPaisa', 'Bank'] as $m): ?><option <?= $o['pay_method'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach ?></select></label>
    <label>Payment screenshot<input type="file" name="proof" accept="image/*,application/pdf" required></label><label>Transaction ID<input name="txn_ref"></label><button class="btn lg">Submit payment proof</button></form>
  <?php endif ?>
  <div class="center" style="margin-top:20px"><?php if ($wa): ?><a class="btn-o lg" target="_blank" rel="noopener" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Assalam o Alaikum, I placed order ' . order_no($o) . ' (' . money($o['total']) . ') on your website. Name: ' . $o['name']) ?>">💬 Message us on WhatsApp</a><?php endif ?> <a class="btn lg" href="shop">Continue shopping</a></div>
<?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
