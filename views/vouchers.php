<?php
require_role('admin');
$title = 'Fee vouchers'; $back = '?p=fees';
$f = in_array(get('f'), ['unpaid', 'overdue', 'paid', 'cancelled', 'all'], true) ? get('f') : 'unpaid';
$s = trim((string)get('s'));
$w = ['1=1']; $pr = [];
if ($f === 'unpaid') $w[] = 'v.status="unpaid"';
elseif ($f === 'overdue') $w[] = 'v.status="unpaid" AND v.due_date<CURDATE()';
elseif ($f !== 'all') { $w[] = 'v.status=?'; $pr[] = $f; }
if ($s !== '') { $w[] = '(u.name LIKE ? OR v.title LIKE ?)'; $pr[] = "%$s%"; $pr[] = "%$s%"; }
$rows = all('SELECT v.*,u.name,u.phone FROM fee_vouchers v JOIN users u ON u.id=v.user_id WHERE ' . implode(' AND ', $w) . ' ORDER BY ' . ($f === 'paid' ? 'v.paid_on DESC' : 'v.due_date ASC') . ', v.id DESC LIMIT 300', $pr);
$tot = one('SELECT SUM(status="unpaid") un, SUM(CASE WHEN status="unpaid" THEN amount-discount ELSE 0 END) due, SUM(status="unpaid" AND due_date<CURDATE()) od, SUM(CASE WHEN status="paid" AND DATE_FORMAT(paid_on,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m") THEN paid_amount ELSE 0 END) col FROM fee_vouchers');
?>
<div class="stats"><div class="stat"><b><?= money($tot['due']) ?></b><span>Outstanding</span></div><div class="stat"><b class="<?= $tot['od'] ? 'neg' : '' ?>"><?= (int)$tot['od'] ?></b><span>Overdue</span></div><div class="stat"><b class="pos"><?= money($tot['col']) ?></b><span>Collected this month</span></div></div>
<div class="quick"><a href="?p=voucher_gen">＋ Generate vouchers</a><a href="?p=voucher_gen#plans">🔁 Monthly fee plans</a><a href="?p=proofs">🧾 Payment proofs</a></div>
<form class="search"><input type="hidden" name="p" value="vouchers"><input type="hidden" name="f" value="<?= e($f) ?>"><input name="s" value="<?= e($s) ?>" placeholder="Search student or voucher…" type="search"></form>
<div class="chips"><?php foreach (['unpaid' => 'Unpaid', 'overdue' => 'Overdue', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $v): ?><a href="?p=vouchers&f=<?= $k ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?></div>
<div class="list"><?php foreach ($rows as $v): $od = voucher_overdue($v); ?>
  <a class="row" href="?p=voucher&id=<?= $v['id'] ?>"><div class="grow"><b><?= e($v['name']) ?></b><small><?= voucher_no($v) ?> · <?= e($v['title']) ?></small><small><?= $v['status'] === 'paid' ? 'Paid ' . date('d M', strtotime($v['paid_on'])) : 'Due ' . date('d M Y', strtotime($v['due_date'])) ?></small></div>
    <div style="text-align:right"><b class="<?= $v['status'] === 'paid' ? 'pos' : ($od ? 'neg' : '') ?>"><?= money($v['status'] === 'paid' ? $v['paid_amount'] : voucher_total($v)) ?></b><br><span class="pill <?= $v['status'] === 'paid' ? 'ok' : ($od ? 'err' : ($v['status'] === 'cancelled' ? '' : 'warn')) ?>"><?= $v['status'] === 'unpaid' ? ($od ? 'Overdue' : 'Unpaid') : ucfirst($v['status']) ?></span></div></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No vouchers here.</p><?php endif ?>
