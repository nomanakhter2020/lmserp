<?php
$title = 'More';
$items = [
  ['orders', '🛒', role('admin') ? 'Shop orders' . (($po = (int)val('SELECT COUNT(*) FROM orders WHERE status="pending"')) ? " ($po)" : '') : 'My orders', true],
  ['products', '📚', role('admin') ? 'Shop products' . (($pp = (int)val('SELECT COUNT(*) FROM products WHERE review="pending"')) ? " ($pp pending)" : '') : 'My products', role('admin', 'teacher')],
  ['earnings', '🤝', role('admin') ? 'Teacher sales & payouts' : 'My sales & earnings', role('admin', 'teacher')],
  ['batches', '🗓️', 'Batches & attendance', role('admin', 'teacher')],
  ['assignments', '📝', 'Assignments', true],
  ['exams', '🧾', role('admin', 'teacher') ? 'Exams & results' : 'Results', true],
  ['attendance_me', '🗓️', 'Attendance', role('student', 'parent')],
  ['certificates', '🎓', role('admin') ? 'Certificates' : 'My certificates', !role('parent')],
  ['notifications', '🔔', 'Notifications', true],
  ['teachers', '👩‍🏫', 'Our teachers', true],
  ['announcements', '📣', 'Announcements', true],
  ['posts', '✍️', 'Blog posts' . (role('admin') && ($pr = (int)val('SELECT COUNT(*) FROM posts WHERE review="pending"')) ? " ($pr pending)" : ''), role('admin', 'teacher')],
  ['messages', '📬', 'Contact messages' . (role('admin') && ($m = (int)val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0')) ? " ($m)" : ''), role('admin')],
  ['pages_edit', '📄', 'Website pages', role('admin')],
  ['proofs', '🧾', 'Payment proofs' . (role('admin') && ($n = (int)val('SELECT COUNT(*) FROM payment_requests WHERE status="pending"')) ? " ($n)" : ''), role('admin')],
  ['fees', '💳', role('parent') ? 'Fees' : 'My fees', role('student', 'teacher', 'parent')],
  ['enrollments', '📝', 'Enrollments', role('admin', 'teacher')],
  ['expenses', '📉', 'Expenses', role('admin')],
  ['payroll', '💰', role('admin') ? 'Teacher payroll' : 'My salary', role('admin', 'teacher')],
  ['reports', '📊', 'Reports', role('admin')],
  ['settings', '⚙️', 'Settings', role('admin')],
  ['modules', '🧩', 'Modules (turn features on/off)', role('admin')],
  ['tprofile', '🪪', 'My teacher profile (CV)', role('admin', 'teacher')],
  ['biometric', '👆', 'Fingerprint login', true],
  ['help', '❓', 'Help & guides', true],
  ['site', '🌐', 'Website', true],
  ['profile', '👤', 'My profile', true],
];
?>
<div class="list menu">
<?php foreach ($items as [$k, $i, $l, $show]): if (!$show || !view_on($k)) continue; ?>
  <a class="row" href="?p=<?= $k ?>"><span class="mi"><?= $i ?></span><b class="grow"><?= $l ?></b><span>›</span></a>
<?php endforeach ?>
  <a class="row" href="?p=logout"><span class="mi">🚪</span><b class="grow neg">Log out</b></a>
</div>
<p class="install-hint" hidden><button class="btn block ghost" id="installBtn">📲 Install app</button></p>
<div class="sheet" id="installSheet" hidden><div class="sheet-card">
  <h3>📲 Install the app</h3>
  <div data-os="ios"><p>On iPhone / iPad (in <b>Safari</b>):</p><ol><li>Tap the <b>Share</b> button <span class="k">⬆︎</span> at the bottom</li><li>Scroll and tap <b>Add to Home Screen</b></li><li>Tap <b>Add</b></li></ol></div>
  <div data-os="inapp"><p>You opened this inside another app (WhatsApp, Facebook…). Tap <b>⋮</b> or <b>…</b> and choose <b>Open in Chrome</b> / <b>Open in browser</b>, then come back here and tap Install.</p></div>
  <div data-os="android"><p>On Android (<b>Chrome</b>):</p><ol><li>Tap the <b>⋮</b> menu at the top right</li><li>Tap <b>Install app</b> or <b>Add to Home screen</b></li><li>Tap <b>Install</b></li></ol></div>
  <div data-os="desktop"><p>On computer (<b>Chrome / Edge</b>): click the <b>install icon</b> <span class="k">⊕</span> at the right side of the address bar, or open the browser menu → <b>Install app</b>.</p></div>
  <button class="btn block" id="sheetClose">Got it</button>
</div></div>
<p class="center muted"><?= APP_NAME ?> v<?= APP_VERSION ?> · by NAFsols</p>
