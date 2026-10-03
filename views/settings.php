<?php
require_role('admin');
$title = 'Settings'; $back = '?p=more';
$cats = all('SELECT c.*,(SELECT COUNT(*) FROM courses WHERE category_id=c.id) n FROM categories c ORDER BY name');
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="settings_save">
  <label>Institute name<input name="institute" value="<?= e(setting('institute')) ?>" required></label>
  <label>Contact phone<input name="phone" value="<?= e(setting('phone')) ?>"></label>
  <h3>Public website</h3>
  <label>Headline<input name="site_tagline" value="<?= e(setting('site_tagline')) ?>" placeholder="Learn new skills online, at your own pace"></label>
  <label>About (hero text)<textarea name="site_about" rows="3" placeholder="Short intro about your institute"><?= e(setting('site_about')) ?></textarea></label>
  <div class="two"><label>WhatsApp<input name="site_whatsapp" value="<?= e(setting('site_whatsapp')) ?>" placeholder="0300-1234567"></label><label>Email<input name="site_email" type="email" value="<?= e(setting('site_email')) ?>"></label></div>
  <label>Address<input name="site_address" value="<?= e(setting('site_address')) ?>"></label>
  <label>Google AdSense publisher ID <small>(after approval, e.g. ca-pub-1234567890123456)</small><input name="adsense_client" value="<?= e(setting('adsense_client')) ?>" placeholder="ca-pub-…" pattern="ca-pub-\d{10,20}"></label>
  <p class="muted" style="font-size:13px">Ads appear only on public pages (website, blog, legal pages) — never inside the student app. ads.txt is generated automatically.</p>
  <p><a href="?p=site" target="_blank">🌐 View website ›</a> · <a href="?p=pages_edit">📄 Edit legal pages ›</a></p>
  <h3>Payment details shown to students</h3>
  <label>Bank account<textarea name="pay_bank" rows="2" placeholder="Meezan Bank · Title · IBAN"><?= e(setting('pay_bank')) ?></textarea></label>
  <label>JazzCash<input name="pay_jazzcash" value="<?= e(setting('pay_jazzcash')) ?>" placeholder="0300-1234567 · Account title"></label>
  <label>EasyPaisa<input name="pay_easypaisa" value="<?= e(setting('pay_easypaisa')) ?>" placeholder="0345-1234567 · Account title"></label>
  <label>Cash instructions<input name="pay_cash" value="<?= e(setting('pay_cash')) ?>" placeholder="Pay at office, Mon–Sat 10am–6pm"></label>
  <label>Teacher discount on courses (%) <small>(0 = full price, 100 = free)</small><input name="teacher_discount" type="number" min="0" max="100" value="<?= (int)setting('teacher_discount', '0') ?>"></label>
  <input type="hidden" name="allow_register" value="0"><label class="check"><input type="checkbox" name="allow_register" value="1" <?= setting('allow_register', '1') === '1' ? 'checked' : '' ?>> Students can sign up themselves</label>
  <input type="hidden" name="paid_needs_approval" value="0"><label class="check"><input type="checkbox" name="paid_needs_approval" value="1" <?= setting('paid_needs_approval', '1') === '1' ? 'checked' : '' ?>> Paid courses need fee approval before access</label>
  <button class="btn block">Save settings</button></form>
<h2>Categories</h2>
<div class="list"><?php foreach ($cats as $c): ?>
  <div class="row"><b class="grow"><?= e($c['name']) ?></b><small><?= $c['n'] ?> courses</small>
  <form method="post" onsubmit="return confirm('Delete category?')"><?= csrf_field() ?><input type="hidden" name="a" value="category_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="x">✕</button></form></div>
<?php endforeach ?></div>
<form method="post" class="card inline"><?= csrf_field() ?><input type="hidden" name="a" value="category_add"><input name="name" placeholder="New category" required><button class="btn sm">Add</button></form>
<h2 id="demo">Demo data</h2>
<form method="post" class="card" onsubmit="return confirm('Add starter blog articles?')"><?= csrf_field() ?><input type="hidden" name="a" value="blog_seed">
  <p class="muted">Adds original starter articles to the blog (study tips, exam prep, IT, careers). Edit them freely. Safe to run again.</p>
  <button class="btn block ghost">✍️ Load starter blog articles</button></form>
<form method="post" class="card" onsubmit="return confirm('Add demo teachers and IT courses?')"><?= csrf_field() ?><input type="hidden" name="a" value="demo_seed">
  <p class="muted">Adds 10 sample teachers (Maths, English/IELTS, IT, Physics, Chemistry, Biology, Urdu, Accounting, Quran, Kids) with full CV profiles and photos, plus 5 IT courses (Web Development, Python, MS Office, Graphic Design, Digital Marketing) with cover images, lectures, videos and quizzes. Safe to run again — nothing is duplicated.</p>
  <button class="btn block ghost">👩‍🏫 Load demo teachers & IT courses</button></form>
