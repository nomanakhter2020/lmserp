<?php
$me = user(); $title = role('admin') ? 'Shop orders' : 'My orders'; $back = role('admin') ? '?p=more' : '?p=shop';
$f = isset(ORDER_ST[get('f')]) ? get('f') : (role('admin') && get('f') !== 'all' ? 'open' : '');
if (role('admin')) {
    $w = $f === 'open' ? 'o.status IN ("pending","paid","processing","shipped")' : ($f ? 'o.status="' . $f . '"' : '1=1');
    $rows = all("SELECT o.*,COALESCE(u.name,o.name) name,(o.user_id IS NULL) guest,(SELECT COUNT(*) FROM order_items i WHERE i.order_id=o.id) n FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE $w ORDER BY o.id DESC LIMIT 200");
    $st = one('SELECT SUM(status="pending") p, SUM(status IN ("paid","processing")) t, COALESCE(SUM(CASE WHEN status<>"cancelled" AND DATE_FORMAT(created_at,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m") THEN total END),0) m FROM orders');
} else $rows = all('SELECT o.*,(SELECT COUNT(*) FROM order_items i WHERE i.order_id=o.id) n FROM orders o WHERE o.user_id=? ORDER BY o.id DESC', [$me['id']]);
?>
<?php if (role('admin')): ?>
<div class="stats"><div class="stat"><b class="<?= $st['p'] ? 'neg' : '' ?>"><?= (int)$st['p'] ?></b><span>Pending</span></div><div class="stat"><b><?= (int)$st['t'] ?></b><span>To ship</span></div><div class="stat"><b><?= money($st['m']) ?></b><span>Sales this month</span></div></div>
<div class="quick"><a href="?p=products">🛠 Products</a><a href="?p=shop">🛒 View shop</a></div>
<div class="chips"><?php foreach (['open' => 'Open', 'pending' => 'Pending', 'paid' => 'Paid', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $v): ?><a href="?p=orders&f=<?= $k ?>" class="<?= ($f ?: 'all') === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?></div>
<?php endif ?>
<?php $bulk = role('admin') && $rows; if ($bulk): ?><form method="post" id="bulk"><?= csrf_field() ?><input type="hidden" name="a" value="order_bulk"><input type="hidden" name="f" value="<?= e(get('f', 'open')) ?>">
<div class="card bulkbar"><label class="check" style="margin:0"><input type="checkbox" onchange="document.querySelectorAll('.oc').forEach(c=>c.checked=this.checked)"> Select all</label>
<select name="status"><?php foreach (['paid' => 'Mark Paid', 'processing' => 'Mark Processing', 'shipped' => 'Mark Shipped', 'delivered' => 'Mark Delivered', 'cancelled' => 'Cancel'] as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach ?></select><button class="btn sm" onclick="return document.querySelector('.oc:checked')?confirm('Update selected orders?'):(alert('Select orders first'),false)">Apply</button></div><?php endif ?>
<div class="list"><?php foreach ($rows as $o): ?>
  <div class="orow"><?php if ($bulk): ?><input type="checkbox" class="oc" name="ids[]" value="<?= $o['id'] ?>"><?php endif ?><a class="row" href="?p=order&id=<?= $o['id'] ?>"><span class="mi">📦</span><div class="grow"><b><?= order_no($o) ?><?= role('admin') ? ' · ' . e($o['name']) : '' ?></b><small><?= $o['n'] ?> item<?= $o['n'] > 1 ? 's' : '' ?> · <?= e($o['pay_method']) ?> · <?= date('d M Y', strtotime($o['created_at'])) ?></small></div>
  <div style="text-align:right"><b><?= money($o['total']) ?></b><br><span class="pill <?= ORDER_ST[$o['status']][1] ?>"><?= ORDER_ST[$o['status']][0] ?></span></div></a></div>
<?php endforeach ?></div><?php if ($bulk): ?></form><?php endif ?>
<?php if (!$rows): ?><p class="empty">No orders yet.<?php if (!role('admin')): ?><br><a class="btn" href="?p=shop">Visit shop</a><?php endif ?></p><?php endif ?>
