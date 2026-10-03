<?php
// Public CV-style teacher profile
$t = one('SELECT id,name,email,phone,role FROM users WHERE id=? AND active=1 AND role IN ("teacher","admin")', [$id]);
$pr = $t ? teacher_profile((int)$t['id']) : null;
$me = user();
$own = $me && ($me['role'] === 'admin' || (int)$me['id'] === (int)($t['id'] ?? 0));
if (!$t || (!$pr['public'] && !$own)) { http_response_code(404); echo 'Profile not found. <a href="./">Home</a>'; exit; }
$inst = setting('institute', APP_NAME);
$courses = all('SELECT c.*,(SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) ls,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) st FROM courses c WHERE teacher_id=? AND published=1 ORDER BY id DESC', [$t['id']]);
$students = array_sum(array_column($courses, 'st'));
$skills = csv_list($pr['skills']); $langs = csv_list($pr['languages']);
$ach = array_values(array_filter(array_map('trim', explode("\n", (string)$pr['achievements']))));
$links = array_filter(['LinkedIn' => $pr['linkedin'], 'Website' => $pr['website'], 'YouTube' => $pr['youtube']]);
$cta = $me ? '?p=courses' : (setting('allow_register', '1') === '1' ? '?p=register' : '?p=login');
$photo = photo_url($pr['photo']);
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($t['name']) ?> — <?= e($pr['headline'] ?: 'Teacher') ?> · <?= e($inst) ?></title>
<meta name="description" content="<?= e(mb_substr((string)$pr['bio'] ?: $pr['headline'], 0, 155)) ?>">
<?php if ($photo): ?><meta property="og:image" content="<?= e($photo) ?>"><?php endif ?>
<meta name="theme-color" content="#4f46e5"><link rel="icon" href="assets/icon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=<?= APP_VERSION ?>"><link rel="stylesheet" href="assets/cv.css?v=<?= APP_VERSION ?>">
<?= ads_head() ?>
</head><body class="cvpage">
<header class="nav scrolled"><div class="container nav-in">
  <a class="brand" href="./"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a>
  <div class="cv-actions"><a class="btn-o sm" href="<?= $me ? '?p=teachers' : './#teachers' ?>">← All teachers</a><?php if ($own): ?><a class="btn sm" href="?p=tprofile&id=<?= $t['id'] ?>">✏️ Edit</a><?php endif ?></div>
</div></header>

<main class="container cv">
  <?php if (!$pr['public']): ?><div class="cv-note">This profile is hidden from the website. Only you and admins can see it.</div><?php endif ?>
  <section class="cv-head">
    <div class="cv-photo"><?php if ($photo): ?><img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>"><?php else: ?><span><?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></span><?php endif ?></div>
    <div class="cv-id">
      <h1><?= e($t['name']) ?></h1>
      <?php if ($pr['headline']): ?><p class="cv-hl"><?= e($pr['headline']) ?></p><?php endif ?>
      <div class="cv-chips">
        <?php if ($pr['city']): ?><span>📍 <?= e($pr['city']) ?></span><?php endif ?>
        <?php if ($pr['years']): ?><span>⏳ <?= (int)$pr['years'] ?>+ years experience</span><?php endif ?>
        <?php if ($courses): ?><span>📚 <?= count($courses) ?> course<?= count($courses) > 1 ? 's' : '' ?></span><?php endif ?>
        <?php if ($students): ?><span>👥 <?= $students ?> students</span><?php endif ?>
      </div>
      <div class="cv-btns"><?php if ($courses): ?><a class="btn" href="#courses">View courses</a><?php endif ?><button class="btn-o" onclick="window.print()">⬇ Download CV</button></div>
    </div>
  </section>

  <div class="cv-grid">
    <aside class="cv-side">
      <?php if ($skills): ?><div class="cv-box"><h3>Skills</h3><div class="tags"><?php foreach ($skills as $s): ?><span><?= e($s) ?></span><?php endforeach ?></div></div><?php endif ?>
      <?php if ($langs): ?><div class="cv-box"><h3>Languages</h3><ul class="plain"><?php foreach ($langs as $l): ?><li><?= e($l) ?></li><?php endforeach ?></ul></div><?php endif ?>
      <?php if ($pr['certifications']): ?><div class="cv-box"><h3>Certifications</h3><?php foreach ($pr['certifications'] as $c): ?><div class="cert"><b><?= e($c['name']) ?></b><small><?= e(trim($c['issuer'] . ($c['year'] ? ' · ' . $c['year'] : ''), ' ·')) ?></small></div><?php endforeach ?></div><?php endif ?>
      <?php if ($links): ?><div class="cv-box"><h3>Links</h3><ul class="plain"><?php foreach ($links as $k => $u): ?><li><a href="<?= e(safe_url($u)) ?>" target="_blank" rel="noopener nofollow"><?= $k ?> ↗</a></li><?php endforeach ?></ul></div><?php endif ?>
    </aside>

    <div class="cv-main">
      <?php if ($pr['bio']): ?><section class="cv-sec"><h2>About</h2><p><?= nl2br(e($pr['bio'])) ?></p></section><?php endif ?>
      <?php if ($pr['experience']): ?><section class="cv-sec"><h2>Experience</h2><div class="tl">
        <?php foreach ($pr['experience'] as $x): ?><div class="tl-item"><div class="tl-top"><b><?= e($x['role']) ?></b><span><?= e($x['period']) ?></span></div><div class="tl-org"><?= e($x['org']) ?></div><?php if ($x['detail']): ?><p><?= nl2br(e($x['detail'])) ?></p><?php endif ?></div><?php endforeach ?>
      </div></section><?php endif ?>
      <?php if ($pr['education']): ?><section class="cv-sec"><h2>Education</h2><div class="tl">
        <?php foreach ($pr['education'] as $x): ?><div class="tl-item"><div class="tl-top"><b><?= e($x['degree']) ?></b><span><?= e($x['year']) ?></span></div><div class="tl-org"><?= e($x['institute']) ?></div><?php if ($x['detail']): ?><p><?= e($x['detail']) ?></p><?php endif ?></div><?php endforeach ?>
      </div></section><?php endif ?>
      <?php if ($ach): ?><section class="cv-sec"><h2>Achievements</h2><ul class="ach"><?php foreach ($ach as $a): ?><li><?= e($a) ?></li><?php endforeach ?></ul></section><?php endif ?>
      <?php $tprods = all('SELECT * FROM products WHERE teacher_id=? AND active=1 ORDER BY id DESC LIMIT 8', [$t['id']]); if ($tprods): ?><section class="cv-sec" id="products"><h2>Books &amp; material by <?= e(explode(' ', $t['name'])[0]) ?></h2><div class="sgrid tp"><?php foreach ($tprods as $p) require __DIR__ . '/_store_card.php'; ?></div></section><?php endif ?>
      <?php if ($courses): ?><section class="cv-sec" id="courses"><h2>Courses by <?= e(explode(' ', $t['name'])[0]) ?></h2><div class="cv-courses">
        <?php foreach ($courses as $c): ?><a class="cv-course" href="<?= $me ? "?p=course&id={$c['id']}" : $cta ?>" style="--c:<?= e($c['color']) ?>"><div class="cc-img" style="<?= cover_style($c) ?>"></div><div><b><?= e($c['title']) ?></b><small><?= $c['ls'] ?> lessons · <?= (float)$c['fee'] > 0 ? money($c['fee']) : 'Free' ?></small></div></a><?php endforeach ?>
      </div></section><?php endif ?>
      <?php if (!$pr['bio'] && !$pr['experience'] && !$pr['education']): ?><p class="muted">This teacher hasn't completed their profile yet.</p><?php endif ?>
    </div>
  </div>
</main>
<footer class="foot"><div class="container foot-in"><a class="brand" href="./"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a><small>© <?= date('Y') ?> <?= e($inst) ?></small></div></footer>
</body></html>
