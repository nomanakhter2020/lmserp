<?php
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)get('slug')));
$in = $slug !== '' ? one('SELECT * FROM institutions WHERE slug=?', [$slug]) : null;
$site = setting('institute', 'EduMall.pk'); $me = user();
$canSee = $in && ($in['status'] === 'active' || ($me && (role('admin') || (int)($me['institution_id'] ?? 0) === (int)$in['id'])));
if (!$canSee) { http_response_code(404); $pageTitle = 'Institute not found'; $pageDesc = ''; require __DIR__ . '/_site_head.php'; echo '<main class="container sec-sm center"><h1 class="pg-h">Institute not found</h1><a class="btn" href="institutes">Browse institutes</a></main>'; require __DIR__ . '/_site_foot.php'; return; }
if ($in['status'] === 'active' && empty($_SESSION['iv'][$in['id']])) { q('UPDATE institutions SET views=views+1 WHERE id=?', [$in['id']]); $_SESSION['iv'][$in['id']] = 1; }
$ty = INST_TYPES[$in['type']] ?? ['🏫', 'Institute'];
$courses = all('SELECT c.*,u.name tname FROM courses c LEFT JOIN users u ON u.id=c.teacher_id WHERE c.institution_id=? AND c.published=1 ORDER BY c.id DESC LIMIT 12', [$in['id']]);
$teachers = inst_teachers((int)$in['id']);
$programs = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$in['programs']))));
$facilities = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$in['facilities']))));
$pageTitle = $in['name'] . ($in['city'] ? ', ' . $in['city'] : '') . ' — Fees, Admission & Programs · ' . $site;
$pageDesc = mb_substr(trim(preg_replace('/\s+/', ' ', (string)$in['about'])) ?: ($in['name'] . ' — ' . $ty[1] . ' in ' . $in['city'] . '. See fees, programs and apply online.'), 0, 160);
$canonical = abs_url(inst_url($in)); $ogImage = $in['cover'] ? abs_url(inst_img($in, 'cover')) : ($in['logo'] ? abs_url(inst_img($in)) : null);
$jsonld = ['@context' => 'https://schema.org', '@type' => in_array($in['type'], ['university', 'college'], true) ? 'CollegeOrUniversity' : ($in['type'] === 'school' ? 'School' : 'EducationalOrganization'), 'name' => $in['name'], 'url' => $canonical, 'description' => $pageDesc]
  + ($in['city'] ? ['address' => ['@type' => 'PostalAddress', 'addressLocality' => $in['city'], 'streetAddress' => $in['address'], 'addressCountry' => 'PK']] : []) + ($in['phone'] ? ['telephone' => $in['phone']] : []) + ($in['logo'] ? ['logo' => abs_url(inst_img($in))] : []);
require __DIR__ . '/_site_head.php';
$wa = wa_num($in['whatsapp'] ?: $in['phone']);
?>
<?php if ($in['status'] !== 'active'): ?><div class="cv-note" style="margin:76px auto 0;max-width:1100px">👁 Preview — this listing is <b><?= e($in['status']) ?></b> and not visible to the public yet.</div><?php endif ?>
<section class="ihero" style="<?= $in['cover'] ? "background-image:linear-gradient(180deg,rgba(15,17,38,.15),rgba(15,17,38,.75)),url('" . e(inst_img($in, 'cover')) . "')" : '' ?>"><div class="container ihero-in">
  <div class="ilogo lg"><?php if ($in['logo']): ?><img src="<?= e(inst_img($in)) ?>" alt="<?= e($in['name']) ?> logo"><?php else: ?><span><?= $ty[0] ?></span><?php endif ?></div>
  <div><span class="itype"><?= $ty[0] ?> <?= $ty[1] ?></span><?php if ($in['featured']): ?> <span class="itype gold">★ Featured</span><?php endif ?>
    <h1><?= e($in['name']) ?></h1><p>📍 <?= e(trim($in['address'] . ($in['address'] && $in['city'] ? ', ' : '') . $in['city'])) ?: 'Pakistan' ?><?= $in['established'] ? ' · Est. ' . (int)$in['established'] : '' ?></p>
    <div class="ihero-cta"><?php if ($in['admissions_open']): ?><a class="btn lg" href="<?= e(inst_url($in)) ?>#apply">Apply for admission</a><?php endif ?><?php if ($wa): ?><a class="btn-o lg light" target="_blank" rel="noopener" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Assalam o Alaikum, I found ' . $in['name'] . ' on ' . $site . ' and want to ask about admission.') ?>">💬 WhatsApp</a><?php endif ?><?php if ($in['phone']): ?><a class="btn-o lg light" href="tel:<?= e(preg_replace('/[^\d+]/', '', $in['phone'])) ?>">📞 Call</a><?php endif ?></div>
  </div>
</div></section>
<main class="container sec-sm">
  <?php if ($f = flash()): ?><div class="cv-note <?= $f[1] === 'ok' ? 'ok' : '' ?>"><?= e($f[0]) ?></div><?php endif ?>
  <div class="igrid2">
    <div>
      <div class="ifacts"><div><small>Type</small><b><?= $ty[1] ?></b></div><div><small>Monthly fee</small><b><?= $in['fee_min'] || $in['fee_max'] ? ($in['fee_min'] ? money($in['fee_min']) : '') . ($in['fee_max'] && $in['fee_max'] != $in['fee_min'] ? ' – ' . money($in['fee_max']) : '') : 'Ask' ?></b></div><div><small>Admissions</small><b class="<?= $in['admissions_open'] ? 'pos' : '' ?>"><?= $in['admissions_open'] ? 'Open' : 'Closed' ?></b></div><div><small>Teachers</small><b><?= count($teachers) ?: '—' ?></b></div></div>
      <?php if ($in['about']): ?><section class="isec"><h2>About</h2><div class="prose"><?= md((string)$in['about']) ?></div></section><?php endif ?>
      <?php if ($programs): ?><section class="isec"><h2>Programs &amp; classes</h2><ul class="iprog"><?php foreach ($programs as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></section><?php endif ?>
      <?php if ($facilities): ?><section class="isec"><h2>Facilities</h2><div class="ifac"><?php foreach ($facilities as $x): ?><span>✓ <?= e($x) ?></span><?php endforeach ?></div></section><?php endif ?>
      <?php if ($courses): ?><section class="isec"><h2>Online courses</h2><div class="icourses"><?php foreach ($courses as $c): ?><a href="?p=course&id=<?= $c['id'] ?>"><span class="icc" style="<?= cover_style($c) ?>"></span><span class="grow"><b><?= e($c['title']) ?></b><small><?= $c['tname'] ? e($c['tname']) . ' · ' : '' ?><?= (float)$c['fee'] > 0 ? money($c['fee']) : 'Free' ?></small></span><span class="go">›</span></a><?php endforeach ?></div></section><?php endif ?>
      <?php if ($teachers): ?><section class="isec"><h2>Teachers</h2><div class="tgrid"><?php foreach ($teachers as $t): ?><a class="tcard" href="?p=teacher&id=<?= $t['id'] ?>"><div class="tph" style="<?= $t['photo'] ? "background-image:url('" . e(photo_url($t['photo'])) . "')" : '' ?>"><?= $t['photo'] ? '' : '👩‍🏫' ?></div><b><?= e($t['name']) ?></b><small><?= e($t['headline'] ?: 'Teacher') ?></small></a><?php endforeach ?></div></section><?php endif ?>
    </div>
    <aside>
      <div class="csum" id="apply">
        <?php if ($in['admissions_open']): ?>
        <h3>Apply for admission</h3><p class="muted" style="font-size:14px;margin-top:-6px">Free · the institute will call you back</p>
        <form method="post" class="cform co" style="padding:0;border:0;box-shadow:none"><?= csrf_field() ?><input type="hidden" name="a" value="admission_apply"><input type="hidden" name="id" value="<?= $in['id'] ?>">
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
          <label>Student name<input name="student_name" required value="<?= e($me['name'] ?? '') ?>"></label>
          <label>Parent / guardian <small>(optional)</small><input name="guardian_name"></label>
          <label>Phone / WhatsApp<input name="phone" required inputmode="tel" placeholder="03XX-XXXXXXX" value="<?= e($me['phone'] ?? '') ?>"></label>
          <label>Class / program<?php if ($programs): ?><select name="class_program"><option value="">Select…</option><?php foreach ($programs as $x): ?><option><?= e($x) ?></option><?php endforeach ?><option>Other</option></select><?php else: ?><input name="class_program" placeholder="e.g. Grade 5, FSc Pre-Medical"><?php endif ?></label>
          <label>Message <small>(optional)</small><textarea name="message" rows="2"></textarea></label>
          <button class="btn lg block-b">Send application</button>
        </form>
        <?php else: ?><h3>Admissions closed</h3><p class="muted">Contact the institute for the next intake.</p><?php endif ?>
        <div class="icontact"><?php if ($in['phone']): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $in['phone'])) ?>">📞 <?= e($in['phone']) ?></a><?php endif ?><?php if ($in['email']): ?><a href="mailto:<?= e($in['email']) ?>">✉️ <?= e($in['email']) ?></a><?php endif ?><?php if ($in['website']): ?><a href="<?= e(safe_link($in['website'])) ?>" target="_blank" rel="noopener nofollow">🌐 Website</a><?php endif ?></div>
      </div>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/_site_foot.php';
