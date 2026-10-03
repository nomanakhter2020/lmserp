<?php
$me = user(); $title = role('admin') ? 'Fees' : 'My fees';
if (!role('admin')) {
    $kid = child_id(); if (role('parent')) { $kn = val('SELECT name FROM users WHERE id=?', [$kid]); $title = 'Fees · ' . $kn; $kids = my_children(); if (count($kids) > 1): ?><div class="chips"><?php foreach ($kids as $k): ?><a href="?p=fees&child=<?= $k['id'] ?>" class="<?= $k['id'] == $kid ? 'on' : '' ?>"><?= e($k['name']) ?></a><?php endforeach ?></div><?php endif; }
    $me = ['id' => $kid] + $me;
    $pays = all('SELECT p.*,c.title FROM payments p LEFT JOIN courses c ON c.id=p.course_id WHERE p.user_id=? ORDER BY p.paid_on DESC', [$me['id']]);
    $fee = (float)val('SELECT COALESCE(SUM(COALESCE(e.fee,c.fee)),0) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=?', [$me['id']]);
    $paid = array_sum(array_column($pays, 'amount')); ?>
    <div class="stats"><div class="stat"><b><?= money($fee) ?></b><span>Total fee</span></div><div class="stat"><b><?= money($paid) ?></b><span>Paid</span></div><div class="stat"><b class="<?= $fee - $paid > 0 ? 'neg' : '' ?>"><?= money(max(0, $fee - $paid)) ?></b><span>Due</span></div></div>
    <?php $vs = all('SELECT * FROM fee_vouchers WHERE user_id=? AND status="unpaid" ORDER BY due_date', [$me['id']]); if ($vs): ?>
    <h2>Fee vouchers to pay</h2><div class="list"><?php foreach ($vs as $v): $od = voucher_overdue($v); ?>
      <a class="row" href="?p=voucher&id=<?= $v['id'] ?>"><div class="grow"><b><?= e($v['title']) ?></b><small><?= voucher_no($v) ?> · due <?= date('d M Y', strtotime($v['due_date'])) ?></small></div><div style="text-align:right"><b class="<?= $od ? 'neg' : '' ?>"><?= money(voucher_total($v)) ?></b><br><span class="pill <?= $od ? 'err' : 'warn' ?>"><?= $od ? 'Overdue' : 'Unpaid' ?></span></div></a>
    <?php endforeach ?></div><h2>Payment history</h2><?php endif ?>
    <div class="list"><?php foreach ($pays as $p): ?><a class="row" href="?p=receipt&id=<?= $p['id'] ?>"><div class="grow"><b><?= money($p['amount']) ?></b><small><?= e($p['paid_on']) ?> · <?= e($p['title'] ?: 'General') ?></small></div><span>Receipt ›</span></a><?php endforeach ?></div>
    <?php if (!$pays): ?><p class="empty">No payments yet</p><?php endif;
    return;
}
$m = (string)get('m', date('Y-m'));
$pays = all('SELECT p.*,u.name,c.title FROM payments p JOIN users u ON u.id=p.user_id LEFT JOIN courses c ON c.id=p.course_id WHERE DATE_FORMAT(p.paid_on,"%Y-%m")=? ORDER BY p.paid_on DESC,p.id DESC', [$m]);
$students = all('SELECT id,name,role FROM users WHERE (role="student" OR (role="teacher" AND id IN (SELECT user_id FROM enrollments))) AND active=1 ORDER BY name');
$courses = all('SELECT id,title FROM courses ORDER BY title');
$dues = all('SELECT * FROM (SELECT u.id,u.name,u.phone,SUM(COALESCE(e.fee,c.fee)) tfee,(SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.user_id=u.id) paid FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id GROUP BY u.id,u.name,u.phone) x WHERE tfee>paid ORDER BY tfee-paid DESC LIMIT 50');
?>
<form class="search"><input type="hidden" name="p" value="fees"><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<div class="quick"><a href="?p=vouchers">📄 Fee vouchers</a><a href="?p=voucher_gen">＋ Generate vouchers</a><a href="?p=voucher_gen#plans">🔁 Monthly plans</a><a href="?p=proofs">🧾 Proofs</a></div>
<div class="hero"><div class="muted-l">Collected in <?= date('F Y', strtotime("$m-01")) ?></div><div class="big"><?= money(array_sum(array_column($pays, 'amount'))) ?></div><div class="muted-l"><?= count($pays) ?> payments</div></div>
<details class="card" id="add"><summary>＋ Record payment</summary>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="payment_add"><input type="hidden" name="back" value="?p=fees">
  <select name="user_id" required><option value="">Select student</option><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?><?= $s['role'] === 'teacher' ? ' (teacher)' : '' ?></option><?php endforeach ?></select>
  <select name="course_id"><option value="">— General —</option><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach ?></select>
  <div class="two"><input name="amount" type="number" min="1" placeholder="Amount (PKR)" required><input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <div class="two"><select name="method"><option>Cash</option><option>Bank</option><option>JazzCash</option><option>EasyPaisa</option></select><input name="note" placeholder="Note"></div>
  <label class="check"><input type="checkbox" name="activate" value="1" checked> Approve pending enrollment</label>
  <button class="btn block">Save payment</button></form></details>
<div class="list"><?php foreach ($pays as $p): ?>
  <div class="row"><div class="grow"><a href="?p=user&id=<?= $p['user_id'] ?>"><b><?= e($p['name']) ?></b></a><small><?= e($p['paid_on']) ?> · <?= e($p['method']) ?> · <?= e($p['title'] ?: 'General') ?></small></div>
    <b class="pos"><?= money($p['amount']) ?></b>
    <a class="x" href="?p=receipt&id=<?= $p['id'] ?>" aria-label="Receipt">🧾</a>
    <form method="post" onsubmit="return confirm('Delete payment?')"><?= csrf_field() ?><input type="hidden" name="a" value="payment_delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach; if (!$pays): ?><p class="empty">No payments this month</p><?php endif ?></div>
<?php if ($dues): ?><h2>Outstanding dues</h2><div class="list"><?php foreach ($dues as $d): ?>
  <a class="row" href="?p=user&id=<?= $d['id'] ?>#pay"><div class="grow"><b><?= e($d['name']) ?></b><small><?= e($d['phone']) ?></small></div><b class="neg"><?= money($d['tfee'] - $d['paid']) ?></b></a>
<?php endforeach ?></div><?php endif ?>
