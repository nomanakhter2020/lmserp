<?php
$me = user();
$title = role('student') || get('mine') ? 'My certificates' : 'Certificates'; $back = '?p=more';
if (role('admin') && !get('mine')) {
    $s = trim((string)get('s'));
    $rows = all('SELECT ce.*,u.name,c.title FROM certificates ce JOIN users u ON u.id=ce.user_id JOIN courses c ON c.id=ce.course_id WHERE u.name LIKE ? OR c.title LIKE ? OR ce.code LIKE ? ORDER BY ce.issued_at DESC LIMIT 300', ["%$s%", "%$s%", "%$s%"]);
    $ready = all('SELECT e.user_id,e.course_id,u.name,c.title FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id LEFT JOIN certificates ce ON ce.user_id=e.user_id AND ce.course_id=e.course_id WHERE e.status="completed" AND ce.id IS NULL ORDER BY e.id DESC LIMIT 50');
} else {
    $rows = all('SELECT ce.*,c.title FROM certificates ce JOIN courses c ON c.id=ce.course_id WHERE ce.user_id=? AND ce.revoked=0 ORDER BY ce.issued_at DESC', [$me['id']]); $ready = [];
}
?>
<?php if (role('admin') && !get('mine')): ?>
<form class="search"><input type="hidden" name="p" value="certificates"><input name="s" value="<?= e($s) ?>" placeholder="Search name, course or code…" type="search"></form>
<div class="quick"><a href="?p=cert" target="_blank">🔎 Public verify page</a><a href="?p=settings#cert">⚙️ Certificate settings</a></div>
<?php if ($ready): ?><h2>Completed — certificate not issued</h2><div class="list"><?php foreach ($ready as $r): ?>
  <div class="row"><div class="grow"><b><?= e($r['name']) ?></b><small><?= e($r['title']) ?></small></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="cert_issue"><input type="hidden" name="user_id" value="<?= $r['user_id'] ?>"><input type="hidden" name="course_id" value="<?= $r['course_id'] ?>"><input type="hidden" name="grade" value=""><button class="btn sm">Issue</button></form></div>
<?php endforeach ?></div><?php endif ?>
<h2>Issued</h2>
<?php endif ?>
<div class="list"><?php foreach ($rows as $r): ?>
  <a class="row <?= !empty($r['revoked']) ? 'locked' : '' ?>" href="?p=cert&c=<?= e($r['code']) ?>" target="_blank"><span class="mi">🎓</span><div class="grow"><b><?= e($r['name'] ?? $r['title']) ?></b><small><?= isset($r['name']) ? e($r['title']) . ' · ' : '' ?><?= e($r['code']) ?> · <?= date('d M Y', strtotime($r['issued_at'])) ?><?= $r['grade'] ? ' · ' . e($r['grade']) : '' ?></small></div><?= !empty($r['revoked']) ? '<span class="pill err">Revoked</span>' : '<span>›</span>' ?></a>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty"><?= role('admin') && !get('mine') ? 'No certificates issued yet.' : 'Complete a course to earn your certificate 🎓' ?></p><?php endif ?>
