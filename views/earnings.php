<?php
require_role('admin', 'teacher'); $me = user();
$tid = role('teacher') ? (int)$me['id'] : $id;
if (!$tid) { // admin overview
    $title = 'Teacher sales & payouts'; $back = '?p=more';
    $ts = all('SELECT DISTINCT u.id,u.name,u.phone FROM users u WHERE u.id IN (SELECT teacher_id FROM products WHERE teacher_id IS NOT NULL UNION SELECT teacher_id FROM order_items WHERE teacher_id IS NOT NULL) ORDER BY u.name');
    $tot = ['earned' => 0, 'balance' => 0, 'pending' => 0];
    foreach ($ts as &$t) { $t['b'] = teacher_balance((int)$t['id']); foreach ($tot as $k => $_) $tot[$k] += $t['b'][$k]; } unset($t);
    ?>
    <div class="stats"><div class="stat"><b><?= money($tot['earned']) ?></b><span>Teachers earned</span></div><div class="stat"><b class="<?= $tot['balance'] > 0 ? 'neg' : '' ?>"><?= money($tot['balance']) ?></b><span>To pay</span></div><div class="stat"><b><?= money($tot['pending']) ?></b><span>In open orders</span></div></div>
    <p class="muted" style="font-size:13px">Teachers get <?= teacher_pct() + 0 ?>% of each sale (change in Settings). Earnings count once the order is <b>Delivered</b>.</p>
    <div class="list"><?php foreach ($ts as $t): ?><a class="row" href="?p=earnings&id=<?= $t['id'] ?>"><span class="mi">👩‍🏫</span><div class="grow"><b><?= e($t['name']) ?></b><small><?= (int)$t['b']['sold'] ?> sold · earned <?= money($t['b']['earned']) ?> · paid <?= money($t['b']['paid']) ?></small></div><b class="<?= $t['b']['balance'] > 0 ? 'neg' : '' ?>"><?= money($t['b']['balance']) ?></b></a><?php endforeach ?></div>
    <?php if (!$ts): ?><p class="empty">No teacher products yet. Teachers add them from More → My products.</p><?php endif ?>
    <?php return;
}
$t = one('SELECT id,name,phone FROM users WHERE id=?', [$tid]); if (!$t) { echo '<p class="empty">Not found</p>'; return; }
$b = teacher_balance($tid);
$title = role('admin') ? $t['name'] . ' — sales' : 'My sales & earnings'; $back = role('admin') ? '?p=earnings' : '?p=more';
$sales = all('SELECT i.*,o.status,o.created_at FROM order_items i JOIN orders o ON o.id=i.order_id WHERE i.teacher_id=? ORDER BY o.id DESC LIMIT 100', [$tid]);
$pays = all('SELECT * FROM teacher_payouts WHERE teacher_id=? ORDER BY paid_on DESC, id DESC', [$tid]);
?>
<div class="hero"><div class="muted-l">Balance to be paid</div><div class="big"><?= money($b['balance']) ?></div><div class="muted-l">Earned <?= money($b['earned']) ?> · Paid <?= money($b['paid']) ?><?= $b['pending'] > 0 ? ' · ' . money($b['pending']) . ' in orders not yet delivered' : '' ?></div></div>
<?php if (role('admin')): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="payout_save"><input type="hidden" name="id" value="<?= $tid ?>">
  <h3 style="margin-top:0">💸 Pay <?= e($t['name']) ?></h3>
  <p class="muted" style="margin-top:0;font-size:13px">Pay the full balance or any part of it — the rest stays as balance. Also added to Expenses.</p>
  <div class="two"><label>Amount<input name="amount" type="number" min="1" step="0.01" value="<?= $b['balance'] > 0 ? $b['balance'] : '' ?>" required></label><label>Date<input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></label></div>
  <div class="two"><label>Method<select name="method"><?php foreach (['Cash', 'JazzCash', 'EasyPaisa', 'Bank'] as $m): ?><option><?= $m ?></option><?php endforeach ?></select></label><label>Note<input name="note" placeholder="e.g. 1st installment"></label></div>
  <button class="btn block" onclick="const a=+this.form.amount.value;return a<=<?= max(0, $b['balance']) ?>||confirm('This is more than the balance (advance payment). Continue?')">Record payout</button>
</form>
<?php else: ?><div class="alert">You earn <b><?= teacher_pct() + 0 ?>%</b> of every sale of your products. It is added to your balance when the order is delivered, and the admin pays you in one go or in parts.</div><?php endif ?>
<h2>Payouts</h2>
<div class="list"><?php foreach ($pays as $p): ?><div class="row"><span class="mi">💸</span><div class="grow"><b><?= money($p['amount']) ?></b><small><?= date('d M Y', strtotime($p['paid_on'])) ?> · <?= e($p['method']) ?><?= $p['note'] ? ' · ' . e($p['note']) : '' ?></small></div>
  <?php if (role('admin')): ?><form method="post" onsubmit="return confirm('Remove this payout?')"><?= csrf_field() ?><input type="hidden" name="a" value="payout_delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="linkbtn" style="border:0;background:none;color:#b91c1c">✕</button></form><?php endif ?></div><?php endforeach ?></div>
<?php if (!$pays): ?><p class="empty">No payouts yet.</p><?php endif ?>
<h2>Sales</h2>
<div class="list"><?php foreach ($sales as $s): ?><div class="row"><span class="mi">📦</span><div class="grow"><b><?= e($s['title']) ?> × <?= $s['qty'] ?></b><small><?= order_no(['id' => $s['order_id']]) ?> · <?= date('d M Y', strtotime($s['created_at'])) ?> · <span class="pill <?= ORDER_ST[$s['status']][1] ?>"><?= ORDER_ST[$s['status']][0] ?></span></small></div><div style="text-align:right"><b><?= $s['status'] === 'cancelled' ? '—' : money($s['teacher_share']) ?></b><br><small class="muted">of <?= money($s['qty'] * $s['price']) ?></small></div></div><?php endforeach ?></div>
<?php if (!$sales): ?><p class="empty">No sales yet.</p><?php endif ?>
