<?php
$me = user();
if (!role('admin')) { // teacher: own slips
    $title = 'My salary'; $back = '?p=more';
    $rows = all('SELECT * FROM salary_slips WHERE user_id=? ORDER BY period DESC', [$me['id']]); $rule = one('SELECT * FROM salary_rules WHERE user_id=?', [$me['id']]);
    if ($rule): ?><div class="card"><small>Your salary basis</small><b style="display:block"><?= ['fixed' => 'Fixed ' . money($rule['amount']) . ' / month', 'per_student' => money($rule['amount']) . ' per active student', 'percent' => (float)$rule['amount'] . '% of your course fees collected'][$rule['type']] ?></b></div><?php endif ?>
    <div class="list"><?php foreach ($rows as $r): ?><a class="row" href="?p=slip&id=<?= $r['id'] ?>"><div class="grow"><b><?= date('F Y', strtotime($r['period'] . '-01')) ?></b><small><?= e($r['basis']) ?></small></div><b><?= money($r['net']) ?></b><span class="pill <?= $r['status'] === 'paid' ? 'ok' : 'warn' ?>"><?= ucfirst($r['status']) ?></span></a><?php endforeach ?></div>
    <?php if (!$rows): ?><p class="empty">No salary slips yet.</p><?php endif;
    return;
}
require_role('admin');
$title = 'Teacher payroll'; $back = '?p=more';
$m = preg_match('/^\d{4}-\d{2}$/', (string)get('m')) ? get('m') : date('Y-m');
$slips = all('SELECT s.*,u.name FROM salary_slips s JOIN users u ON u.id=s.user_id WHERE s.period=? ORDER BY u.name', [$m]);
$teachers = all('SELECT u.id,u.name,r.type,r.amount FROM users u LEFT JOIN salary_rules r ON r.user_id=u.id WHERE u.role IN ("teacher") AND u.active=1 AND u.email NOT LIKE "%@demo.lms" ORDER BY u.name');
$tot = array_sum(array_column($slips, 'net')); $paid = array_sum(array_map(fn($s) => $s['status'] === 'paid' ? $s['net'] : 0, $slips));
$tl = ['fixed' => 'Fixed / month', 'per_student' => 'Per active student', 'percent' => '% of course fees'];
?>
<form class="search"><input type="hidden" name="p" value="payroll"><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<div class="hero red"><div class="muted-l">Payroll · <?= date('F Y', strtotime("$m-01")) ?></div><div class="big"><?= money($tot) ?></div><div class="split"><div><b><?= money($paid) ?></b><span>Paid</span></div><div><b><?= money($tot - $paid) ?></b><span>Pending</span></div></div></div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="slips_generate"><input type="hidden" name="period" value="<?= e($m) ?>"><button class="btn block">⚙️ Generate slips for <?= date('F', strtotime("$m-01")) ?></button></form>
<div class="list"><?php foreach ($slips as $s): ?>
  <a class="row" href="?p=slip&id=<?= $s['id'] ?>"><div class="grow"><b><?= e($s['name']) ?></b><small><?= e($s['basis']) ?></small></div><b><?= money($s['net']) ?></b><span class="pill <?= $s['status'] === 'paid' ? 'ok' : 'warn' ?>"><?= ucfirst($s['status']) ?></span></a>
<?php endforeach; if (!$slips): ?><p class="empty">No slips for this month. Set salary rules below, then generate.</p><?php endif ?></div>
<h2>Salary rules</h2>
<?php foreach ($teachers as $t): ?>
<form method="post" class="card rule"><?= csrf_field() ?><input type="hidden" name="a" value="salary_rule"><input type="hidden" name="user_id" value="<?= $t['id'] ?>"><input type="hidden" name="back" value="?p=payroll&m=<?= e($m) ?>">
  <b><?= e($t['name']) ?></b>
  <div class="rule-row"><select name="type"><?php foreach ($tl as $k => $v): ?><option value="<?= $k ?>" <?= $t['type'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select><input name="amount" type="number" min="0" step="0.01" value="<?= $t['amount'] !== null ? (float)$t['amount'] : '' ?>" placeholder="Amount / %"><button class="btn sm">Save</button></div>
</form>
<?php endforeach; if (!$teachers): ?><p class="empty">No teachers yet.</p><?php endif ?>
