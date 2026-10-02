<?php
require_role('admin');
$title = 'Reports'; $back = '?p=more';
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("first day of -$i month"));
    $months[$m] = [
        (float)val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(paid_on,"%Y-%m")=?', [$m]),
        (float)val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(spent_on,"%Y-%m")=?', [$m]),
        (int)val('SELECT COUNT(*) FROM enrollments WHERE DATE_FORMAT(created_at,"%Y-%m")=?', [$m]),
    ];
}
$max = max(1, ...array_values(array_map(fn($x) => max($x[0], $x[1]), $months)));
$top = all('SELECT c.title,COUNT(e.id) n,(SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.course_id=c.id) rev FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id GROUP BY c.id ORDER BY n DESC LIMIT 10');
?>
<div class="card"><h3>Income vs expenses (6 months)</h3>
<div class="chart"><?php foreach ($months as $m => [$in, $ex]): ?>
  <div class="col"><div class="bars"><i class="in" style="height:<?= round($in / $max * 100) ?>%" title="<?= money($in) ?>"></i><i class="ex" style="height:<?= round($ex / $max * 100) ?>%" title="<?= money($ex) ?>"></i></div><small><?= date('M', strtotime("$m-01")) ?></small></div>
<?php endforeach ?></div>
<div class="legend"><span><i class="in"></i>Fees</span><span><i class="ex"></i>Expenses</span></div></div>
<div class="list"><?php foreach (array_reverse($months, true) as $m => [$in, $ex, $n]): ?>
  <div class="row"><div class="grow"><b><?= date('F Y', strtotime("$m-01")) ?></b><small><?= $n ?> new enrollments · in <?= money($in) ?> · out <?= money($ex) ?></small></div><b class="<?= $in - $ex >= 0 ? 'pos' : 'neg' ?>"><?= money($in - $ex) ?></b></div>
<?php endforeach ?></div>
<h2>Courses</h2>
<div class="list"><?php foreach ($top as $t): ?><div class="row"><div class="grow"><b><?= e($t['title']) ?></b><small><?= $t['n'] ?> students</small></div><b><?= money($t['rev']) ?></b></div><?php endforeach ?></div>
