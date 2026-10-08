<?php
$in = my_inst(); if (!$in) { echo '<p class="empty">No institute linked to this account.</p>'; return; }
$iid = (int)$in['id'];
$ad = one('SELECT COUNT(*) t, SUM(status="new") n, SUM(status="admitted") a, SUM(created_at > NOW() - INTERVAL 7 DAY) w FROM admissions WHERE institution_id=?', [$iid]);
$nt = count(inst_teachers($iid)); $np = count(inst_teachers($iid, 'pending'));
$nc = (int)val('SELECT COUNT(*) FROM courses WHERE institution_id=?', [$iid]);
$recent = all('SELECT * FROM admissions WHERE institution_id=? ORDER BY id DESC LIMIT 5', [$iid]);
$missing = array_keys(array_filter(['logo' => !$in['logo'], 'cover photo' => !$in['cover'], 'about' => !trim((string)$in['about']), 'programs' => !trim((string)$in['programs']), 'fees' => !$in['fee_min'] && !$in['fee_max']]));
?>
<?php if ($in['status'] === 'pending'): ?><div class="alert">⏳ Your listing is <b>under review</b>. Complete your profile — it goes live once approved.</div><?php elseif ($in['status'] === 'suspended'): ?><div class="alert err">Your listing is suspended. Please contact support.</div><?php endif ?>
<div class="hero"><div class="muted-l"><?= e(INST_TYPES[$in['type']][1] ?? 'Institute') ?> · <?= e($in['city']) ?></div><div class="big sm"><?= e($in['name']) ?></div><div class="muted-l"><?= (int)$in['views'] ?> profile views · <a style="color:inherit;text-decoration:underline" href="<?= e(inst_url($in)) ?>" target="_blank">View public page ↗</a></div></div>
<?php if ($missing): ?><a class="card" href="?p=inst_edit" style="display:block">📝 <b>Complete your profile</b><br><small class="muted">Missing: <?= e(implode(', ', $missing)) ?> — complete profiles get more admissions.</small></a><?php endif ?>
<div class="stats"><a class="stat" href="?p=admissions&f=new"><b class="<?= $ad['n'] ? 'neg' : '' ?>"><?= (int)$ad['n'] ?></b><span>New enquiries</span></a><div class="stat"><b><?= (int)$ad['w'] ?></b><span>This week</span></div><div class="stat"><b><?= (int)$ad['a'] ?></b><span>Admitted</span></div></div>
<div class="quick"><a href="?p=admissions">📝 Admissions</a><a href="?p=inst_teachers">👩‍🏫 Teachers <?= $nt ?><?= $np ? " · $np pending" : '' ?></a><a href="?p=courses">📚 Courses <?= $nc ?></a><a href="?p=inst_edit">✏️ Edit profile</a></div>
<h2>Latest enquiries</h2>
<div class="list"><?php foreach ($recent as $r): ?><a class="row" href="?p=admissions"><span class="mi">🧑‍🎓</span><div class="grow"><b><?= e($r['student_name']) ?></b><small><?= e($r['class_program'] ?: '—') ?> · <?= date('d M, h:i A', strtotime($r['created_at'])) ?></small></div><span class="pill <?= ADM_ST[$r['status']][1] ?>"><?= ADM_ST[$r['status']][0] ?></span></a><?php endforeach ?></div>
<?php if (!$recent): ?><p class="empty">No enquiries yet. Share your page link on WhatsApp and Facebook to get started.</p><?php endif ?>
