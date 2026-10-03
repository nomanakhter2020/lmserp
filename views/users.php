<?php
require_role('admin', 'teacher');
$me = user(); $r = (string)get('role', 'student'); $s = trim((string)get('s'));
$title = role('admin') ? 'People' : 'My students';
if (role('teacher')) {
    $us = all('SELECT DISTINCT u.* FROM users u JOIN enrollments e ON e.user_id=u.id JOIN courses c ON c.id=e.course_id WHERE c.teacher_id=? AND u.name LIKE ? ORDER BY u.name', [$me['id'], "%$s%"]);
} else {
    $us = all('SELECT u.*,(SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id) ec,(SELECT COUNT(*) FROM courses c WHERE c.teacher_id=u.id) tc,(SELECT photo FROM teacher_profiles tp WHERE tp.user_id=u.id) ph FROM users u WHERE role=? AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY u.id DESC', [$r, "%$s%", "%$s%", "%$s%"]);
}
?>
<form class="search"><input type="hidden" name="p" value="users"><input type="hidden" name="role" value="<?= e($r) ?>"><input name="s" value="<?= e($s) ?>" placeholder="Search name, email, phone…" type="search"></form>
<?php if (role('admin')): ?>
<div class="chips"><?php foreach (['student' => 'Students', 'teacher' => 'Teachers', 'parent' => 'Parents', 'admin' => 'Admins'] as $k => $v): ?><a href="?p=users&role=<?= $k ?>" class="<?= $r === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?><a href="?p=enrollments">Enrollments</a></div>
<a class="btn block" href="?p=user_edit&role=<?= e($r) ?>">＋ Add <?= e($r) ?></a>
<?php endif ?>
<div class="list"><?php foreach ($us as $u): ?>
  <a class="row" href="?p=user&id=<?= $u['id'] ?>"><div class="avatar sm" style="<?= !empty($u['ph']) ? "background:center/cover url('" . e(photo_url($u['ph'])) . "')" : '' ?>"><?= !empty($u['ph']) ? '' : e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
    <div class="grow"><b><?= e($u['name']) ?></b><small><?= e($u['phone'] ?: $u['email']) ?></small></div>
    <?= !$u['active'] ? '<span class="pill">Inactive</span>' : (isset($u['ec']) ? '<small>' . ($u['role'] === 'student' ? "{$u['ec']} enrolled" : "{$u['tc']} course" . ($u['tc'] == 1 ? '' : 's')) . '</small>' : '') ?></a>
<?php endforeach ?></div>
<?php if (!$us): ?><p class="empty">Nobody here yet</p><?php endif ?>
