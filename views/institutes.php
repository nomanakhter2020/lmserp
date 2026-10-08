<?php
$inst = setting('institute', 'EduMall.pk');
$q = trim((string)get('q')); $type = isset(INST_TYPES[get('type')]) ? get('type') : ''; $city = trim((string)get('city'));
$fee = (int)get('fee'); $sort = get('sort', 'top'); $pg = max(1, (int)get('pg', 1)); $per = 24;
$w = 'status="active"'; $pr = [];
if ($q !== '') { $w .= ' AND (name LIKE ? OR programs LIKE ? OR about LIKE ? OR city LIKE ?)'; array_push($pr, "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($type) { $w .= ' AND type=?'; $pr[] = $type; }
if ($city !== '') { $w .= ' AND city=?'; $pr[] = $city; }
if ($fee) { $w .= ' AND (fee_min=0 OR fee_min<=?)'; $pr[] = $fee; }
if (get('open')) $w .= ' AND admissions_open=1';
$order = ['top' => 'featured DESC, views DESC', 'new' => 'id DESC', 'fee' => 'fee_min=0, fee_min ASC', 'name' => 'name ASC'][$sort] ?? 'featured DESC, views DESC';
$total = (int)val("SELECT COUNT(*) FROM institutions WHERE $w", $pr);
$rows = all("SELECT * FROM institutions WHERE $w ORDER BY $order, id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $pr);
$cities = array_column(all('SELECT DISTINCT city FROM institutions WHERE status="active" AND city<>"" ORDER BY city'), 'city');
$label = $type ? INST_TYPES[$type][1] . 's' : 'Institutes'; if ($type === 'academy') $label = 'Academies & Coaching'; if ($type === 'university') $label = 'Universities';
$pageTitle = $label . ($city ? " in $city" : ' in Pakistan') . ' · ' . $inst;
$pageDesc = "Compare $label" . ($city ? " in $city" : '') . " — fees, programs, facilities and teachers. Apply for admission online on $inst.";
$canonical = abs_url('institutes' . ($type || $city ? '?' . http_build_query(array_filter(['type' => $type, 'city' => $city])) : ''));
require __DIR__ . '/_site_head.php';
$qs = fn($ch) => 'institutes?' . http_build_query(array_filter(array_merge(['q' => $q, 'type' => $type, 'city' => $city, 'fee' => $fee ?: '', 'sort' => $sort !== 'top' ? $sort : '', 'open' => get('open')], $ch), fn($v) => $v !== '' && $v !== null));
?>
<section class="shop-hero"><div class="container"><span class="kicker">Directory</span><h1><?= e($label) ?><?= $city ? ' in ' . e($city) : '' ?></h1><p><?= number_format($total) ?> result<?= $total === 1 ? '' : 's' ?> · fees, programs and online admission</p></div></section>
<main class="container sec-sm">
  <form class="ifilters" action="institutes">
    <input name="q" value="<?= e($q) ?>" placeholder="Search name, program, subject…" type="search">
    <select name="type"><option value="">All types</option><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select>
    <select name="city"><option value="">All cities</option><?php foreach ($cities as $c): ?><option <?= $city === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?></select>
    <select name="fee"><option value="">Any fee</option><?php foreach ([3000, 5000, 10000, 20000, 50000] as $f): ?><option value="<?= $f ?>" <?= $fee === $f ? 'selected' : '' ?>>Up to <?= money($f) ?>/mo</option><?php endforeach ?></select>
    <select name="sort"><option value="top">Top rated</option><option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option><option value="fee" <?= $sort === 'fee' ? 'selected' : '' ?>>Lowest fee</option><option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>A–Z</option></select>
    <label class="chk"><input type="checkbox" name="open" value="1" <?= get('open') ? 'checked' : '' ?>> Admissions open</label>
    <button class="btn">Filter</button>
  </form>
  <div class="chips pubchips"><a href="<?= e($qs(['type' => null])) ?>" class="<?= !$type ? 'on' : '' ?>">All</a><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><a href="<?= e($qs(['type' => $k, 'pg' => null])) ?>" class="<?= $type === $k ? 'on' : '' ?>"><?= $ic ?> <?= $l ?></a><?php endforeach ?></div>
  <div class="igrid"><?php foreach ($rows as $in) require __DIR__ . '/_inst_card.php'; ?></div>
  <?php if (!$rows): ?><div class="empty-cart"><span>🔍</span><p>No institutes match your search.</p><a class="btn" href="institutes">Clear filters</a></div><?php endif ?>
  <?php if ($total > $per): ?><div class="center" style="margin-top:22px"><?php if ($pg > 1): ?><a class="btn-o" href="<?= e($qs(['pg' => $pg - 1])) ?>">← Previous</a><?php endif ?> <span class="muted">Page <?= $pg ?> of <?= ceil($total / $per) ?></span> <?php if ($pg * $per < $total): ?><a class="btn-o" href="<?= e($qs(['pg' => $pg + 1])) ?>">Next →</a><?php endif ?></div><?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
