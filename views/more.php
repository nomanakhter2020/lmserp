<?php
$title = 'More';
$items = [
  ['announcements', '📣', 'Announcements', true],
  ['proofs', '🧾', 'Payment proofs' . (role('admin') && ($n = (int)val('SELECT COUNT(*) FROM payment_requests WHERE status="pending"')) ? " ($n)" : ''), role('admin')],
  ['fees', '💳', 'My fees', role('student')],
  ['enrollments', '📝', 'Enrollments', role('admin', 'teacher')],
  ['expenses', '📉', 'Expenses', role('admin')],
  ['reports', '📊', 'Reports', role('admin')],
  ['settings', '⚙️', 'Settings', role('admin')],
  ['site', '🌐', 'Website', true],
  ['profile', '👤', 'My profile', true],
];
?>
<div class="list menu">
<?php foreach ($items as [$k, $i, $l, $show]): if (!$show) continue; ?>
  <a class="row" href="?p=<?= $k ?>"><span class="mi"><?= $i ?></span><b class="grow"><?= $l ?></b><span>›</span></a>
<?php endforeach ?>
  <a class="row" href="?p=logout"><span class="mi">🚪</span><b class="grow neg">Log out</b></a>
</div>
<p class="install-hint" hidden><button class="btn block ghost" id="installBtn">📲 Install app on this phone</button></p>
<p class="center muted"><?= APP_NAME ?> v<?= APP_VERSION ?> · by NAFsols</p>
