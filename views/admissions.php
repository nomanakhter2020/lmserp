<?php
require_role('admin', 'institute'); $iid = my_inst_id();
$title = 'Admissions'; $back = '?p=home';
$f = isset(ADM_ST[get('f')]) ? get('f') : '';
$w = ($iid ? 'a.institution_id=' . $iid : '1=1') . ($f ? ' AND a.status="' . $f . '"' : '');
$rows = all("SELECT a.*,i.name iname FROM admissions a JOIN institutions i ON i.id=a.institution_id WHERE $w ORDER BY a.id DESC LIMIT 300");
$cnt = array_column(all('SELECT status,COUNT(*) n FROM admissions a WHERE ' . ($iid ? 'a.institution_id=' . $iid : '1=1') . ' GROUP BY status'), 'n', 'status');
?>
<div class="chips"><a href="?p=admissions" class="<?= !$f ? 'on' : '' ?>">All</a><?php foreach (ADM_ST as $k => [$l]): ?><a href="?p=admissions&f=<?= $k ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $l ?> <?= (int)($cnt[$k] ?? 0) ?></a><?php endforeach ?></div>
<?php foreach ($rows as $r): $wa = wa_num($r['phone']); ?>
<div class="card adm">
  <div class="rowhead"><div><b><?= e($r['student_name']) ?></b><?= $r['guardian_name'] ? ' <small class="muted">· ' . e($r['guardian_name']) . '</small>' : '' ?><br><small class="muted"><?= e($r['class_program'] ?: '—') ?> · <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?><?= !$iid ? ' · 🏫 ' . e($r['iname']) : '' ?></small></div><span class="pill <?= ADM_ST[$r['status']][1] ?>"><?= ADM_ST[$r['status']][0] ?></span></div>
  <?php if ($r['message']): ?><p style="margin:8px 0;white-space:pre-line"><?= e($r['message']) ?></p><?php endif ?>
  <div class="quick" style="margin:8px 0"><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['phone'])) ?>">📞 <?= e($r['phone']) ?></a><?php if ($wa): ?><a target="_blank" rel="noopener" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Assalam o Alaikum ' . ($r['guardian_name'] ?: $r['student_name']) . ', thank you for your admission enquiry for ' . ($r['class_program'] ?: 'admission') . ' at ' . $r['iname'] . '.') ?>">💬 WhatsApp</a><?php endif ?><?php if ($r['email']): ?><a href="mailto:<?= e($r['email']) ?>">✉️ Email</a><?php endif ?></div>
  <form method="post" action="?p=admissions<?= $f ? '&f=' . $f : '' ?>" class="admf"><?= csrf_field() ?><input type="hidden" name="a" value="admission_status"><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <select name="status"><?php foreach (ADM_ST as $k => [$l]): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select><input name="note" value="<?= e($r['note']) ?>" placeholder="Note (e.g. test on Monday)"><button class="btn sm">Save</button></form>
</div>
<?php endforeach ?>
<?php if (!$rows): ?><p class="empty">No admission enquiries<?= $f ? ' here' : ' yet' ?>.</p><?php endif ?>
