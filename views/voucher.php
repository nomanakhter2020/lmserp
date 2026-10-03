<?php
$me = user();
$v = one('SELECT v.*,u.name,u.phone,u.email,c.title ctitle FROM fee_vouchers v JOIN users u ON u.id=v.user_id LEFT JOIN courses c ON c.id=v.course_id WHERE v.id=?', [$id]);
if (!$v || (!role('admin') && (int)$v['user_id'] !== (int)$me['id'])) { echo '<p class="empty">Voucher not found</p>'; return; }
$title = voucher_no($v); $back = role('admin') ? '?p=vouchers' : '?p=fees';
$inst = setting('institute', APP_NAME); $od = voucher_overdue($v); $total = voucher_total($v);
$pend = val('SELECT id FROM payment_requests WHERE voucher_id=? AND status="pending"', [$id]);
$accts = array_filter(['Bank' => setting('pay_bank'), 'JazzCash' => setting('pay_jazzcash'), 'EasyPaisa' => setting('pay_easypaisa')]);
?>
<div class="card receipt voucher" id="rcpt">
  <div class="center"><img src="assets/icon.svg" width="44" alt=""><h1><?= e($inst) ?></h1><small><?= e(setting('phone')) ?> <?= e(setting('site_address')) ?></small><div class="vtag">FEE VOUCHER</div></div>
  <hr><div class="kv"><span>Voucher no.</span><b><?= voucher_no($v) ?></b></div>
  <div class="kv"><span>Student</span><b><?= e($v['name']) ?></b></div>
  <?php if ($v['ctitle']): ?><div class="kv"><span>Course</span><b><?= e($v['ctitle']) ?></b></div><?php endif ?>
  <div class="kv"><span>Description</span><b><?= e($v['title']) ?></b></div>
  <div class="kv"><span>Issue date</span><b><?= date('d M Y', strtotime($v['created_at'])) ?></b></div>
  <div class="kv"><span>Due date</span><b class="<?= $od ? 'neg' : '' ?>"><?= date('d M Y', strtotime($v['due_date'])) ?></b></div>
  <hr><div class="kv"><span>Fee</span><b><?= money($v['amount']) ?></b></div>
  <?php if ((float)$v['discount']): ?><div class="kv"><span>Scholarship / discount</span><b class="pos">− <?= money($v['discount']) ?></b></div><?php endif ?>
  <?php if ((float)$v['late_fee']): ?><div class="kv"><span>Late fine <?= $od ? '(applied)' : '(after due date)' ?></span><b class="<?= $od ? 'neg' : 'muted' ?>"><?= $od ? '+ ' : '' ?><?= money($v['late_fee']) ?></b></div><?php endif ?>
  <hr><div class="kv total"><span><?= $v['status'] === 'paid' ? 'Amount paid' : 'Payable' ?></span><b><?= money($v['status'] === 'paid' ? $v['paid_amount'] : $total) ?></b></div>
  <div class="center vstatus <?= $v['status'] === 'paid' ? 'paid' : ($od ? 'od' : '') ?>"><?= $v['status'] === 'paid' ? 'PAID · ' . date('d M Y', strtotime($v['paid_on'])) : ($v['status'] === 'cancelled' ? 'CANCELLED' : ($od ? 'OVERDUE' : 'UNPAID')) ?></div>
  <?php if ($accts && $v['status'] === 'unpaid'): ?><hr><small class="muted">Pay to:</small><?php foreach ($accts as $k => $t): ?><div class="kv"><span><?= $k ?></span><b style="text-align:right"><?= nl2br(e($t)) ?></b></div><?php endforeach ?><?php endif ?>
</div>
<div class="pager"><button class="btn ghost" onclick="window.print()">🖨 Print / PDF</button>
<?php if (role('admin') && $v['phone'] && $v['status'] === 'unpaid'): $msg = rawurlencode("Assalam o Alaikum {$v['name']}. Fee reminder from $inst: " . voucher_no($v) . " ({$v['title']}) — " . money($total) . ' due on ' . date('d M Y', strtotime($v['due_date'])) . ($od ? ' (overdue)' : '') . '. Please pay at your earliest. Thank you.'); ?>
<a class="btn" href="https://wa.me/<?= wa_num($v['phone']) ?>?text=<?= $msg ?>" target="_blank" rel="noopener">💬 Send reminder</a><?php endif ?>
<?php if ($v['payment_id']): ?><a class="btn" href="?p=receipt&id=<?= $v['payment_id'] ?>">🧾 Receipt</a><?php endif ?></div>

<?php if ($v['status'] === 'unpaid'): ?>
  <?php if (role('admin')): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="voucher_pay"><input type="hidden" name="id" value="<?= $id ?>">
    <h3>✓ Record payment</h3>
    <div class="two"><input name="amount" type="number" min="1" value="<?= $total ?>"><input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></div>
    <div class="two"><select name="method"><option>Cash</option><option>Bank</option><option>JazzCash</option><option>EasyPaisa</option></select><input name="note" placeholder="Note / ref"></div>
    <button class="btn block">Mark as paid</button></form>
  <details class="card"><summary>✏️ Edit voucher</summary><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="voucher_edit"><input type="hidden" name="id" value="<?= $id ?>">
    <div class="two"><label>Fee<input name="amount" type="number" value="<?= (float)$v['amount'] ?>"></label><label>Discount<input name="discount" type="number" value="<?= (float)$v['discount'] ?>"></label></div>
    <div class="two"><label>Late fine<input name="late_fee" type="number" value="<?= (float)$v['late_fee'] ?>"></label><label>Due date<input name="due_date" type="date" value="<?= e($v['due_date']) ?>"></label></div>
    <button class="btn block">Save</button></form></details>
  <form method="post" onsubmit="return confirm('Cancel this voucher?')"><?= csrf_field() ?><input type="hidden" name="a" value="voucher_cancel"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Cancel voucher</button></form>
  <?php elseif ($pend): ?>
  <div class="alert warn">⏳ Your payment proof for this voucher is being verified.</div>
  <?php else: ?>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="proof_submit"><input type="hidden" name="voucher_id" value="<?= $id ?>">
    <h3>📤 Paid? Upload proof</h3>
    <div class="two"><select name="method"><option>JazzCash</option><option>EasyPaisa</option><option>Bank</option><option value="Cash">Cash at office</option></select><input name="amount" type="number" min="1" value="<?= $total ?>"></div>
    <label>Screenshot<input name="proof" type="file" accept="image/*,application/pdf"></label>
    <input name="txn_ref" placeholder="Transaction ID (optional if screenshot attached)">
    <button class="btn block">Submit proof</button></form>
  <?php endif ?>
<?php endif ?>
