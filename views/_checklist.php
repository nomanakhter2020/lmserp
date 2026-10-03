<?php
if (setting('checklist_hidden') === '1') return;
$steps = [
  ['Institute details & payment accounts', setting('site_email') !== '' && (setting('pay_jazzcash') . setting('pay_bank') . setting('pay_easypaisa')) !== '', '?p=settings'],
  ['Add a teacher', (bool)val('SELECT 1 FROM users WHERE role="teacher" AND email NOT LIKE "%@demo.lms" LIMIT 1'), '?p=user_edit&role=teacher'],
  ['Create a course with lessons', (bool)val('SELECT 1 FROM lessons l JOIN courses c ON c.id=l.course_id JOIN users u ON u.id=c.teacher_id WHERE u.email NOT LIKE "%@demo.lms" LIMIT 1') || (bool)val('SELECT 1 FROM lessons l JOIN courses c ON c.id=l.course_id WHERE c.teacher_id IS NULL LIMIT 1'), '?p=course_edit'],
  ['Create a batch / class', (bool)val('SELECT 1 FROM batches LIMIT 1'), '?p=batch_edit'],
  ['Set up monthly fees', (bool)val('SELECT 1 FROM fee_plans LIMIT 1'), '?p=voucher_gen#plans'],
  ['Add a shop product', (bool)val('SELECT 1 FROM products WHERE active=1 LIMIT 1'), '?p=product_edit'],
  ['Give a parent their login', (bool)val('SELECT 1 FROM parent_links LIMIT 1'), '?p=users&role=student'],
];
$done = count(array_filter($steps, fn($s) => $s[1]));
if ($done === count($steps)) return;
?>
<div class="card checklist-card">
  <div class="rowhead"><b>🚀 Getting started · <?= $done ?>/<?= count($steps) ?></b><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="checklist_hide"><button class="x" title="Hide">✕</button></form></div>
  <div class="bar"><i style="width:<?= round($done * 100 / count($steps)) ?>%"></i></div>
  <?php foreach ($steps as [$l, $ok, $u]): ?><a class="ck <?= $ok ? 'done' : '' ?>" href="<?= $u ?>"><span><?= $ok ? '✓' : '' ?></span><?= e($l) ?><?= $ok ? '' : ' ›' ?></a><?php endforeach ?>
  <a class="small" href="?p=help">❓ Read the admin guide</a>
</div>
