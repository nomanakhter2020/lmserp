<?php
require_role('admin'); $title = 'Institutes'; $back = '?p=more';
$f = in_array(get('f'), ['pending', 'active', 'suspended'], true) ? get('f') : ''; $s = trim((string)get('s'));
$w = '1=1'; $pr = []; if ($f) { $w .= ' AND status=?'; $pr[] = $f; } if ($s !== '') { $w .= ' AND (name LIKE ? OR city LIKE ?)'; $pr[] = "%$s%"; $pr[] = "%$s%"; }
$rows = all("SELECT i.*,(SELECT COUNT(*) FROM admissions a WHERE a.institution_id=i.id) na,(SELECT COUNT(*) FROM teacher_institutions t WHERE t.institution_id=i.id AND t.status='active') nt FROM institutions i WHERE $w ORDER BY i.status='pending' DESC, i.id DESC LIMIT 300", $pr);
$cnt = array_column(all('SELECT status,COUNT(*) n FROM institutions GROUP BY status'), 'n', 'status');
?>
<div class="stats"><a class="stat" href="?p=insts&f=pending"><b class="<?= !empty($cnt['pending']) ? 'neg' : '' ?>"><?= (int)($cnt['pending'] ?? 0) ?></b><span>Pending</span></a><a class="stat" href="?p=insts&f=active"><b><?= (int)($cnt['active'] ?? 0) ?></b><span>Live</span></a><a class="stat" href="?p=admissions"><b><?= (int)val('SELECT COUNT(*) FROM admissions') ?></b><span>Enquiries</span></a></div>
<form class="search"><input type="hidden" name="p" value="insts"><input name="s" value="<?= e($s) ?>" placeholder="Search name or city"></form>
<div class="chips"><a href="?p=insts" class="<?= !$f ? 'on' : '' ?>">All</a><a href="?p=insts&f=pending" class="<?= $f === 'pending' ? 'on' : '' ?>">Pending</a><a href="?p=insts&f=active" class="<?= $f === 'active' ? 'on' : '' ?>">Live</a><a href="?p=insts&f=suspended" class="<?= $f === 'suspended' ? 'on' : '' ?>">Suspended</a></div>
<div class="list"><?php foreach ($rows as $i): ?>
  <div class="card prow"><a class="row" href="?p=inst_edit&id=<?= $i['id'] ?>"><span class="mi"><?= INST_TYPES[$i['type']][0] ?? '🏫' ?></span><div class="grow"><b><?= e($i['name']) ?><?= $i['featured'] ? ' ★' : '' ?></b><small><?= e(INST_TYPES[$i['type']][1] ?? '') ?> · <?= e($i['city']) ?> · <?= (int)$i['na'] ?> enquiries · <?= (int)$i['nt'] ?> teachers · <?= (int)$i['views'] ?> views</small></div><span class="pill <?= ['active' => 'ok', 'pending' => 'warn', 'suspended' => 'err'][$i['status']] ?? '' ?>"><?= e(ucfirst($i['status'])) ?></span></a>
  <?php if ($i['status'] === 'pending'): ?><form method="post" class="revbtns"><?= csrf_field() ?><input type="hidden" name="a" value="inst_status"><input type="hidden" name="id" value="<?= $i['id'] ?>"><input type="hidden" name="back" value="list"><button class="btn sm" name="status" value="active">✓ Approve</button><button class="btn sm ghost" name="status" value="suspended">✕ Reject</button></form><?php endif ?></div>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty">No institutes yet.</p><?php endif ?>
