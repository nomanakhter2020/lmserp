<?php
$inst = setting('institute', 'EduMall.pk');
$pageTitle = $inst . ' — Find schools, colleges, universities & teachers in Pakistan';
$pageDesc = "Search and compare schools, colleges, universities, academies and tutors across Pakistan. See fees, programs and teachers, and apply for admission online on $inst.";
$canonical = abs_url('');
$counts = array_column(all('SELECT type,COUNT(*) n FROM institutions WHERE status="active" GROUP BY type'), 'n', 'type');
$featured = all('SELECT * FROM institutions WHERE status="active" ORDER BY featured DESC, views DESC, id DESC LIMIT 8');
$cities = all('SELECT city,COUNT(*) n FROM institutions WHERE status="active" AND city<>"" GROUP BY city ORDER BY n DESC LIMIT 12');
$tutors = all('SELECT u.id,u.name,tp.photo,tp.headline,tp.city FROM users u JOIN teacher_profiles tp ON tp.user_id=u.id WHERE u.role="teacher" AND u.active=1 AND tp.public=1 ORDER BY u.id DESC LIMIT 6');
$nInst = array_sum($counts); $nTeach = (int)val('SELECT COUNT(*) FROM users WHERE role="teacher" AND active=1');
$jsonld = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $inst, 'url' => abs_url(''), 'potentialAction' => ['@type' => 'SearchAction', 'target' => abs_url('institutes') . '?q={q}', 'query-input' => 'required name=q']];
require __DIR__ . '/_site_head.php';
?>
<section class="mhero"><div class="container">
  <span class="kicker light">Pakistan's education marketplace</span>
  <h1>Find the right school, college, university &amp; teacher</h1>
  <p>Compare fees and programs, meet the teachers, and apply for admission online — all in one place.</p>
  <form class="msearch" action="institutes">
    <input name="q" placeholder="Name, program or subject…" aria-label="Search">
    <select name="type" aria-label="Type"><option value="">All types</option><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach ?></select>
    <input name="city" placeholder="City" list="mcities" aria-label="City"><datalist id="mcities"><?php foreach (['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Hyderabad', 'Peshawar', 'Quetta'] as $ct): ?><option value="<?= $ct ?>"><?php endforeach ?></datalist>
    <button class="btn lg">Search</button>
  </form>
  <div class="mstats"><span><b><?= number_format($nInst) ?></b> institutes</span><span><b><?= number_format($nTeach) ?></b> teachers</span><span><b>Free</b> for students &amp; parents</span></div>
</div></section>

<section class="sec-sm"><div class="container">
  <div class="mcats"><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><a href="institutes?type=<?= $k ?>"><span><?= $ic ?></span><b><?= $l ?></b><small><?= (int)($counts[$k] ?? 0) ?> listed</small></a><?php endforeach ?></div>
</div></section>

<?php if ($featured): ?>
<section class="sec alt"><div class="container">
  <div class="sec-head"><span class="kicker">Featured</span><h2>Top institutes</h2><p>Hand-picked schools, colleges and academies with admissions open.</p></div>
  <div class="igrid"><?php foreach ($featured as $in) require __DIR__ . '/_inst_card.php'; ?></div>
  <div class="center" style="margin-top:26px"><a class="btn lg" href="institutes">Browse all institutes →</a></div>
</div></section>
<?php endif ?>

<?php if ($cities): ?><section class="sec-sm"><div class="container"><h2 class="rel-h" style="margin-top:0">Browse by city</h2><div class="chips pubchips"><?php foreach ($cities as $c): ?><a href="institutes?city=<?= e(rawurlencode($c['city'])) ?>"><?= e($c['city']) ?> <small><?= $c['n'] ?></small></a><?php endforeach ?></div></div></section><?php endif ?>

<section class="sec"><div class="container">
  <div class="sec-head"><span class="kicker">How it works</span><h2>Admission in 3 simple steps</h2></div>
  <div class="msteps"><div><span>1</span><h3>Search &amp; compare</h3><p>Filter by city, type and fee. See programs, facilities and teachers.</p></div><div><span>2</span><h3>Apply online</h3><p>Send your admission enquiry in one minute — no forms to print.</p></div><div><span>3</span><h3>Get a call back</h3><p>The institute contacts you directly on phone or WhatsApp.</p></div></div>
</div></section>

<?php if ($tutors): ?>
<section class="sec alt"><div class="container">
  <div class="sec-head"><span class="kicker">Teachers</span><h2>Learn from experienced teachers</h2><p>Teachers on <?= e($inst) ?> teach at multiple institutes and offer their own courses.</p></div>
  <div class="tgrid"><?php foreach ($tutors as $t): ?><a class="tcard" href="?p=teacher&id=<?= $t['id'] ?>"><div class="tph" style="<?= $t['photo'] ? "background-image:url('" . e(photo_url($t['photo'])) . "')" : '' ?>"><?= $t['photo'] ? '' : '👩‍🏫' ?></div><b><?= e($t['name']) ?></b><small><?= e($t['headline'] ?: 'Teacher') ?><?= $t['city'] ? ' · ' . e($t['city']) : '' ?></small></a><?php endforeach ?></div>
  <div class="center" style="margin-top:22px"><a class="btn-o lg" href="teachers">All teachers →</a></div>
</div></section>
<?php endif ?>

<section class="sec"><div class="container"><div class="mcta">
  <div><h2>Own a school, college or academy?</h2><p>List your institute on <?= e($inst) ?>, receive admission enquiries online, and manage courses, teachers and students from one app.</p></div>
  <a class="btn lg" href="list-your-institute">List your institute — free</a>
</div></div></section>
<?php require __DIR__ . '/_site_foot.php';
