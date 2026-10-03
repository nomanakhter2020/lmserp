<?php
$me = user();
$x = one('SELECT e.*,c.title ctitle,c.teacher_id,b.name bname,t.name tname FROM exams e JOIN courses c ON c.id=e.course_id LEFT JOIN batches b ON b.id=e.batch_id LEFT JOIN users t ON t.id=c.teacher_id WHERE e.id=?', [$id]);
if (!$x) { echo '<p class="empty">Not found</p>'; return; }
$manage = can_manage_course($x);
$res = exam_results($id);
if ($manage && get('all')) $uids = array_keys(array_filter($res, fn($r) => $r['any']));
elseif ($manage && (int)get('u')) $uids = [(int)get('u')];
elseif (role('parent')) $uids = child_id() ? [child_id()] : [];
else $uids = [(int)$me['id']];
if (!$manage && !$x['published']) $uids = [];
$uids = array_values(array_filter($uids, fn($u) => isset($res[$u])));
if (!$uids) { echo '<p class="empty">Result not available yet.</p>'; return; }
$title = 'Result card'; $back = $manage ? "?p=exam&id=$id" : '?p=exams' . (role('parent') ? '&child=' . (int)get('child') : '');
$papers = all('SELECT * FROM exam_papers WHERE exam_id=? ORDER BY sort,id', [$id]);
$inst = setting('institute', APP_NAME); $n = count(array_filter($res, fn($r) => $r['any']));
foreach ($uids as $uid): $r = $res[$uid]; $st = one('SELECT name,phone FROM users WHERE id=?', [$uid]); $att = $x['batch_id'] ? att_percent($uid, (int)$x['batch_id']) : att_percent($uid); ?>
<div class="card receipt rcard">
  <div class="center"><img src="assets/icon.svg" width="44" alt=""><h1><?= e($inst) ?></h1><div class="vtag">RESULT CARD</div><p style="margin:6px 0 0;font-weight:700"><?= e($x['title']) ?><?= $x['exam_date'] ? ' · ' . date('M Y', strtotime($x['exam_date'])) : '' ?></p></div>
  <hr><div class="kv"><span>Student</span><b><?= e($st['name']) ?></b></div><div class="kv"><span>Course</span><b><?= e($x['ctitle']) ?><?= $x['bname'] ? ' · ' . e($x['bname']) : '' ?></b></div>
  <table class="rtable"><tr><th>Subject</th><th>Total</th><th>Obtained</th><th>Status</th></tr>
  <?php foreach ($papers as $p): $m = $r['marks'][$p['id']] ?? null; $ok = $m && !$m['absent'] && $m['marks'] !== null && $m['marks'] >= $p['pass_marks']; ?>
    <tr><td><?= e($p['subject']) ?></td><td><?= $p['max_marks'] ?></td><td><b><?= $m ? ($m['absent'] ? 'Absent' : (float)$m['marks']) : '–' ?></b></td><td class="<?= $ok ? 'a-P' : 'a-A' ?>"><?= $ok ? 'Pass' : 'Fail' ?></td></tr>
  <?php endforeach ?>
  <tr class="tot"><td>Total</td><td><?= $r['max'] ?></td><td><?= (float)$r['total'] ?></td><td></td></tr></table>
  <div class="rsum"><div><b><?= $r['pct'] ?>%</b><span>Percentage</span></div><div><b class="<?= $r['pass'] ? 'pos' : 'neg' ?>"><?= $r['grade'] ?></b><span>Grade</span></div><div><b><?= $r['rank'] ? $r['rank'] . '<small>/' . $n . '</small>' : '–' ?></b><span>Position</span></div><?php if ($att !== null): ?><div><b><?= $att ?>%</b><span>Attendance</span></div><?php endif ?></div>
  <div class="center vstatus <?= $r['pass'] ? 'paid' : 'od' ?>"><?= $r['pass'] ? 'PASSED' : 'NOT PASSED' ?></div>
  <div class="rsign"><div><span></span><small><?= e($x['tname'] ?: 'Class teacher') ?></small></div><div><span></span><small><?= e(setting('cert_signer') ?: 'Principal') ?></small></div></div>
</div>
<?php endforeach ?>
<div class="pager"><button class="btn ghost" onclick="window.print()">🖨 Print / PDF</button></div>
