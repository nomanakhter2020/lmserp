<?php
$me = user(); $title = role('student') ? 'Browse courses' : 'Courses';
$cat = (int)get('cat'); $s = trim((string)get('s')); $prog = isset(PROGRAMS[get('prog')]) ? get('prog') : '';
$w = ['1=1']; $pr = [];
if (role('student', 'parent')) $w[] = 'c.published=1';
if (role('institute')) { $w[] = 'c.institution_id=?'; $pr[] = my_inst_id(); }
$mine = get('mine') === '1';
if (role('teacher')) { if ($mine) { $w[] = 'c.teacher_id=?'; $pr[] = $me['id']; } else { $w[] = '(c.published=1 OR c.teacher_id=?)'; $pr[] = $me['id']; } }
if ($cat) { $w[] = 'c.category_id=?'; $pr[] = $cat; }
if ($prog) { $w[] = 'c.program=?'; $pr[] = $prog; }
if ($s !== '') { $w[] = 'c.title LIKE ?'; $pr[] = "%$s%"; }
$cs = all('SELECT c.*,u.name tname,cat.name cname,(SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) ls,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) st
  FROM courses c LEFT JOIN users u ON u.id=c.teacher_id LEFT JOIN categories cat ON cat.id=c.category_id WHERE ' . implode(' AND ', $w) . ' ORDER BY c.id DESC', $pr);
$cats = all('SELECT * FROM categories ORDER BY name');
?>
<form class="search"><input type="hidden" name="p" value="courses"><input name="s" value="<?= e($s) ?>" placeholder="Search courses…" type="search"></form>
<?php if (role('teacher')): ?><div class="chips"><a href="?p=courses" class="<?= !$mine ? 'on' : '' ?>">All courses</a><a href="?p=courses&mine=1" class="<?= $mine ? 'on' : '' ?>">I teach</a></div><?php endif ?>
<?php if ((int)val('SELECT COUNT(DISTINCT program) FROM courses WHERE published=1') > 1 || $prog): ?><div class="chips"><a href="?p=courses" class="<?= !$prog ? 'on' : '' ?>">All programs</a><?php foreach (PROGRAMS as $k => [$l, $i]): ?><a href="?p=courses&prog=<?= $k ?>" class="<?= $prog === $k ? 'on' : '' ?>"><?= $i ?> <?= $l ?></a><?php endforeach ?></div><?php endif ?>
<div class="chips"><a href="?p=courses" class="<?= !$cat ? 'on' : '' ?>">All</a><?php foreach ($cats as $c): ?><a href="?p=courses&cat=<?= $c['id'] ?>" class="<?= $cat == $c['id'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach ?></div>
<?php if (role('admin', 'teacher')): ?><a class="btn block" href="?p=course_edit">＋ New course</a><?php endif ?>
<div class="grid">
<?php foreach ($cs as $c): ?>
  <a class="ccard" href="?p=course&id=<?= $c['id'] ?>" style="--c:<?= e($c['color']) ?>">
    <div class="band img" style="<?= cover_style($c) ?>"><span><?= e($c['cname'] ?: 'General') ?></span><?php if (!$c['published']): ?><span class="pill">Draft</span><?php endif ?></div>
    <b><?= e($c['title']) ?></b>
    <small><?= $c['program'] !== 'course' ? (PROGRAMS[$c['program']][1] ?? '') . ' ' : '' ?><?= $c['level'] ? e($c['level']) . ' · ' : '' ?><?= $c['ls'] ?> lessons · <?= e($c['tname'] ?: 'No teacher') ?></small>
    <div class="cfoot"><span class="price"><?= (float)$c['fee'] > 0 ? money($c['fee']) : 'Free' ?></span><?php if (role('admin') || (role('teacher') && (int)$c['teacher_id'] === (int)$me['id'])): ?><small><?= $c['st'] ?> students</small><?php elseif (role('teacher') && ($tf = course_fee_for($c)) < (float)$c['fee']): ?><small class="pos">You: <?= money($tf) ?></small><?php endif ?></div>
  </a>
<?php endforeach ?>
</div>
<?php if (!$cs): ?><p class="empty">No courses found</p><?php endif ?>
<?php if (!$s && !$cat) require __DIR__ . '/_teachers_strip.php'; ?>
