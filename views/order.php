<?php
$me = user();
$o = one('SELECT o.*,u.name uname,s.name sname FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN users s ON s.id=o.student_id WHERE o.id=?', [$id]);
if (!$o || (!role('admin') && (int)$o['user_id'] !== (int)$me['id'] && (int)$o['student_id'] !== (int)$me['id'])) { echo '<p class="empty">Order not found</p>'; return; }
$title = order_no($o); $back = '?p=orders';
$items = all('SELECT * FROM order_items WHERE order_id=?', [$id]);
$paid = in_array($o['status'], ['paid', 'processing', 'shipped', 'delivered'], true);
$steps = ['pending' => 'Placed', 'paid' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$order = array_keys(ORDER_ST); $cur = array_search($o['status'] === 'processing' ? 'paid' : $o['status'], $order);
?>
<?php if ($o['status'] !== 'cancelled'): ?><div class="track"><?php foreach ($steps as $k => $l): ?><div class="<?= array_search($k, $order) <= $cur ? 'on' : '' ?>"><i></i><span><?= $l ?></span></div><?php endforeach ?></div>
<?php else: ?><div class="alert err">This order was cancelled.</div><?php endif ?>
<?php if ($o['tracking']): ?><div class="alert">🚚 Tracking: <b><?= e($o['tracking']) ?></b></div><?php endif ?>
<?php if ($o['admin_note']): ?><div class="alert warn"><?= e($o['admin_note']) ?></div><?php endif ?>
<div class="list"><?php foreach ($items as $it): ?>
  <div class="row"><div class="grow"><b><?= e($it['title']) ?></b><small><?= $it['qty'] ?> × <?= money($it['price']) ?></small></div><b><?= money($it['qty'] * $it['price']) ?></b>
  <?php if ($it['type'] === 'digital' && $paid): ?><a class="btn sm" href="?p=dl&id=<?= $it['id'] ?>">⬇ PDF</a><?php elseif ($it['type'] === 'digital'): ?><span class="pill">PDF after payment</span><?php endif ?></div>
<?php endforeach ?></div>
<div class="card"><div class="kv"><span>Subtotal</span><b><?= money($o['subtotal']) ?></b></div><div class="kv"><span>Delivery</span><b><?= (float)$o['shipping'] ? money($o['shipping']) : 'Free' ?></b></div><hr><div class="kv total"><span>Total</span><b><?= money($o['total']) ?></b></div>
  <div class="kv"><span>Payment</span><b><?= e($o['pay_method']) ?><?= $o['txn_ref'] ? ' · ' . e($o['txn_ref']) : '' ?></b></div>
  <?php if ($o['address']): ?><div class="kv"><span>Deliver to</span><b style="text-align:right"><?= e($o['name']) ?><br><?= e($o['address']) ?>, <?= e($o['city']) ?><br><?= e($o['phone']) ?></b></div><?php endif ?>
  <?php if ($o['sname']): ?><div class="kv"><span>Student</span><b><?= e($o['sname']) ?></b></div><?php endif ?>
  <?php if ($o['proof']): ?><a class="btn sm ghost" href="?p=order_proof_file&id=<?= $id ?>" target="_blank">🧾 Payment proof</a><?php endif ?></div>

<?php if (!role('admin') && $o['status'] === 'pending' && $o['pay_method'] !== 'COD' && !$o['proof']): ?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="order_proof"><input type="hidden" name="id" value="<?= $id ?>"><h3>📤 Upload payment proof</h3>
  <select name="pay_method"><?php foreach (['JazzCash', 'EasyPaisa', 'Bank'] as $m): ?><option <?= $o['pay_method'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach ?></select>
  <input type="file" name="proof" accept="image/*,application/pdf" required><input name="txn_ref" placeholder="Transaction ID"><button class="btn block">Upload</button></form>
<?php endif ?>
<?php if (role('admin')): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="order_status"><input type="hidden" name="id" value="<?= $id ?>"><h3>Update order</h3>
  <small class="muted">Customer: <?= e($o['uname']) ?> · placed <?= date('d M Y, g:i a', strtotime($o['created_at'])) ?></small>
  <select name="status"><?php foreach (ORDER_ST as $k => [$l]): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select>
  <input name="tracking" value="<?= e($o['tracking']) ?>" placeholder="Courier & tracking no. (e.g. TCS 123456)">
  <input name="admin_note" value="<?= e($o['admin_note']) ?>" placeholder="Note to customer (optional)">
  <button class="btn block">Save & notify customer</button>
  <small class="muted">Marking Paid (or Delivered for COD) records the income in Fees & Reports.</small></form>
<?php if ($o['phone']): ?><a class="btn ghost block" href="https://wa.me/<?= wa_num($o['phone']) ?>?text=<?= rawurlencode('Assalam o Alaikum ' . $o['name'] . ', your order ' . order_no($o) . ' (' . money($o['total']) . ') from ' . setting('institute') . ' is ' . strtolower(ORDER_ST[$o['status']][0]) . ($o['tracking'] ? '. Tracking: ' . $o['tracking'] : '') . '. Thank you!') ?>" target="_blank" rel="noopener">💬 WhatsApp customer</a><?php endif ?>
<?php endif ?>
<?php if ($o['status'] === 'pending'): ?><form method="post" onsubmit="return confirm('Cancel this order?')"><?= csrf_field() ?><input type="hidden" name="a" value="order_cancel"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Cancel order</button></form><?php endif ?>
