<?php
$uid = child_id(); if (!$uid) { echo '<p class="empty">Not available</p>'; return; }
$title = 'Attendance'; $back = '?p=home';
$m = preg_match('/^\d{4}-\d{2}$/', (string)get('m')) ? get('m') : date('Y-m');
$rows = all('SELECT a.*,b.name bname,c.title FROM attendance a JOIN batches b ON b.id=a.batch_id JOIN courses c ON c.id=b.course_id WHERE a.user_id=? AND DATE_FORMAT(a.att_date,"%Y-%m")=? ORDER BY a.att_date DESC', [$uid, $m]);
$cnt = array_count_values(array_column($rows, 'status')) + ['P' => 0, 'A' => 0, 'L' => 0, 'E' => 0];
?>
<form class="search"><input type="hidden" name="p" value="attendance_me"><?php if (role('parent')): ?><input type="hidden" name="child" value="<?= $uid ?>"><?php endif ?><input type="month" name="m" value="<?= e($m) ?>" onchange="this.form.submit()"></form>
<div class="stats"><?php foreach (ATT as $k => [$l, $c]): ?><div class="stat"><b class="<?= $k === 'A' && $cnt['A'] ? 'neg' : '' ?>"><?= $cnt[$k] ?></b><span><?= $l ?></span></div><?php endforeach ?></div>
<div class="list"><?php foreach ($rows as $r): ?><div class="row"><div class="grow"><b><?= date('D, d M', strtotime($r['att_date'])) ?></b><small><?= e($r['title']) ?> · <?= e($r['bname']) ?></small></div><span class="pill <?= ATT[$r['status']][1] ?>"><?= ATT[$r['status']][0] ?></span></div><?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No attendance this month.</p><?php endif ?>
