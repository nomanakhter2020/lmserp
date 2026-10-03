<?php
require_role('admin', 'teacher');
$b = one('SELECT b.*,c.title ctitle FROM batches b JOIN courses c ON c.id=b.course_id WHERE b.id=?', [$id]);
if (!$b || !can_manage_batch($b)) { echo '<p class="empty">Batch not found</p>'; return; }
$d = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)get('date')) ? get('date') : date('Y-m-d');
$title = 'Attendance'; $back = "?p=batch&id=$id";
$sts = all('SELECT u.id,u.name,u.phone,a.status FROM batch_students s JOIN users u ON u.id=s.user_id LEFT JOIN attendance a ON a.batch_id=s.batch_id AND a.user_id=u.id AND a.att_date=? WHERE s.batch_id=? ORDER BY u.name', [$d, $id]);
$marked = count(array_filter($sts, fn($s) => $s['status']));
$inst = setting('institute', APP_NAME);
?>
<div class="crumb"><?= e($b['name']) ?> · <?= e($b['ctitle']) ?></div>
<form class="search" style="display:flex;gap:8px"><input type="hidden" name="p" value="attendance"><input type="hidden" name="id" value="<?= $id ?>"><input type="date" name="date" value="<?= e($d) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()"></form>
<?php if (!$sts): ?><p class="empty">Add students to this batch first.<br><a class="btn" href="?p=batch&id=<?= $id ?>">Manage students</a></p><?php return; endif ?>
<form method="post" id="attf"><?= csrf_field() ?><input type="hidden" name="a" value="attendance_save"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="date" value="<?= e($d) ?>">
<div class="rowhead" style="margin:0 4px 10px"><small><?= date('l, d M Y', strtotime($d)) ?> · <?= $marked ? "$marked marked" : 'not marked yet' ?></small><button type="button" class="btn sm ghost" onclick="document.querySelectorAll('.attopt input[value=P]').forEach(i=>i.checked=true)">All present</button></div>
<div class="list"><?php foreach ($sts as $s): $cur = $s['status'] ?: ($marked ? '' : 'P'); ?>
  <div class="row col"><b><?= e($s['name']) ?></b>
    <div class="attopt"><?php foreach (ATT as $k => [$lbl, $cls]): ?><label class="<?= $cls ?>"><input type="radio" name="st[<?= $s['id'] ?>]" value="<?= $k ?>" <?= $cur === $k ? 'checked' : '' ?>><span><?= $lbl ?></span></label><?php endforeach ?></div>
  </div>
<?php endforeach ?></div>
<button class="btn block">Save attendance</button>
</form>
<?php $abs = array_filter($sts, fn($s) => $s['status'] === 'A' && $s['phone']); if ($abs && get('saved')): ?>
<h2>Notify absent students</h2>
<div class="list"><?php foreach ($abs as $s): $msg = rawurlencode("Assalam o Alaikum. {$s['name']} was absent from {$b['name']} ({$b['ctitle']}) on " . date('d M Y', strtotime($d)) . ". Please make sure to attend the next class. - $inst"); ?>
  <a class="row" href="https://wa.me/<?= wa_num($s['phone']) ?>?text=<?= $msg ?>" target="_blank" rel="noopener"><div class="grow"><b><?= e($s['name']) ?></b><small><?= e($s['phone']) ?></small></div><span class="pill">💬 WhatsApp</span></a>
<?php endforeach ?></div>
<?php endif ?>
