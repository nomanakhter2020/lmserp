<?php
$me = user();
$s = one('SELECT s.*,u.name,u.email,u.phone FROM salary_slips s JOIN users u ON u.id=s.user_id WHERE s.id=?', [$id]);
if (!$s || (!role('admin') && (int)$s['user_id'] !== (int)$me['id'])) { echo '<p class="empty">Not found</p>'; return; }
$title = 'Salary slip'; $back = '?p=payroll' . (role('admin') ? '&m=' . $s['period'] : ''); $inst = setting('institute', APP_NAME);
?>
<div class="card receipt">
  <div class="center"><img src="assets/icon.svg" width="44" alt=""><h1><?= e($inst) ?></h1><div class="vtag">SALARY SLIP</div><p style="margin:6px 0 0;font-weight:700"><?= date('F Y', strtotime($s['period'] . '-01')) ?></p></div>
  <hr><div class="kv"><span>Employee</span><b><?= e($s['name']) ?></b></div><div class="kv"><span>Designation</span><b>Teacher</b></div><div class="kv"><span>Basis</span><b style="text-align:right"><?= e($s['basis']) ?></b></div>
  <hr><div class="kv"><span>Basic / earned</span><b><?= money($s['basic']) ?></b></div>
  <?php if ((float)$s['bonus']): ?><div class="kv"><span>Bonus</span><b class="pos">+ <?= money($s['bonus']) ?></b></div><?php endif ?>
  <?php if ((float)$s['deduction']): ?><div class="kv"><span>Deductions</span><b class="neg">− <?= money($s['deduction']) ?></b></div><?php endif ?>
  <?php if ($s['note']): ?><div class="kv"><span>Note</span><b><?= e($s['note']) ?></b></div><?php endif ?>
  <hr><div class="kv total"><span>Net salary</span><b><?= money($s['net']) ?></b></div>
  <div class="center vstatus <?= $s['status'] === 'paid' ? 'paid' : '' ?>"><?= $s['status'] === 'paid' ? 'PAID · ' . date('d M Y', strtotime($s['paid_on'])) . ' · ' . e($s['method']) : 'UNPAID' ?></div>
</div>
<div class="pager"><button class="btn ghost" onclick="window.print()">🖨 Print / PDF</button></div>
<?php if (role('admin') && $s['status'] === 'unpaid'): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="slip_update"><input type="hidden" name="id" value="<?= $id ?>"><h3>Adjust</h3>
  <div class="two"><label>Basic<input name="basic" type="number" step="0.01" value="<?= (float)$s['basic'] ?>"></label><label>Bonus<input name="bonus" type="number" step="0.01" value="<?= (float)$s['bonus'] ?>"></label></div>
  <div class="two"><label>Deduction<input name="deduction" type="number" step="0.01" value="<?= (float)$s['deduction'] ?>"></label><label>Note<input name="note" value="<?= e($s['note']) ?>"></label></div>
  <button class="btn ghost block">Update</button></form>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="slip_pay"><input type="hidden" name="id" value="<?= $id ?>"><h3>Pay salary</h3>
  <div class="two"><select name="method"><option>Bank</option><option>Cash</option><option>JazzCash</option><option>EasyPaisa</option></select><input name="paid_on" type="date" value="<?= date('Y-m-d') ?>"></div>
  <button class="btn block">✓ Mark paid (adds to expenses)</button></form>
<form method="post" onsubmit="return confirm('Delete slip?')"><?= csrf_field() ?><input type="hidden" name="a" value="slip_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete slip</button></form>
<?php endif ?>
