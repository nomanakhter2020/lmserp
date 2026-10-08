<?php
$inst = setting('institute', 'EduMall.pk'); $q = trim((string)get('q')); $city = trim((string)get('city'));
$w = 'u.role="teacher" AND u.active=1 AND tp.public=1'; $pr = [];
if ($q !== '') { $w .= ' AND (u.name LIKE ? OR tp.headline LIKE ? OR tp.skills LIKE ? OR tp.bio LIKE ?)'; array_push($pr, "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($city !== '') { $w .= ' AND tp.city=?'; $pr[] = $city; }
$rows = all("SELECT u.id,u.name,tp.photo,tp.headline,tp.city,tp.years,(SELECT COUNT(*) FROM teacher_institutions ti WHERE ti.teacher_id=u.id AND ti.status='active') ni,(SELECT COUNT(*) FROM courses c WHERE c.teacher_id=u.id AND c.published=1) nc FROM users u JOIN teacher_profiles tp ON tp.user_id=u.id WHERE $w ORDER BY nc DESC, u.id DESC LIMIT 60", $pr);
$pageTitle = 'Teachers & tutors' . ($city ? " in $city" : '') . ' · ' . $inst; $pageDesc = "Find experienced teachers and tutors on $inst — see their CV, subjects and courses."; $canonical = abs_url('teachers');
require __DIR__ . '/_site_head.php';
?>
<section class="shop-hero"><div class="container"><span class="kicker">Teacher pool</span><h1>Teachers &amp; tutors</h1><p>Teachers who teach at institutes on <?= e($inst) ?> and offer their own courses.</p></div></section>
<main class="container sec-sm">
  <form class="shop-tools" action="teachers"><input name="q" value="<?= e($q) ?>" placeholder="Subject, skill or name…" type="search"><input name="city" value="<?= e($city) ?>" placeholder="City" style="max-width:180px"><button class="btn">Search</button></form>
  <div class="tgrid big"><?php foreach ($rows as $t): ?><a class="tcard" href="?p=teacher&id=<?= $t['id'] ?>"><div class="tph" style="<?= $t['photo'] ? "background-image:url('" . e(photo_url($t['photo'])) . "')" : '' ?>"><?= $t['photo'] ? '' : '👩‍🏫' ?></div><b><?= e($t['name']) ?></b><small><?= e($t['headline'] ?: 'Teacher') ?></small><small class="muted"><?= $t['city'] ? '📍 ' . e($t['city']) . ' · ' : '' ?><?= (int)$t['years'] ? (int)$t['years'] . ' yrs · ' : '' ?><?= (int)$t['nc'] ?> courses</small></a><?php endforeach ?></div>
  <?php if (!$rows): ?><p class="center muted">No teachers found.</p><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
