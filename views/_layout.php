<?php
$title = '';
$back = '';
ob_start();
require $view;
$body = ob_get_clean();
$u = user();
$inst = setting('institute', APP_NAME);
$icons = [
    'home' => '<path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'courses' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM4 21V5"/>',
    'my' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
    'fees' => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/>',
    'users' => '<circle cx="9" cy="8" r="4"/><path d="M1 21a8 8 0 0 1 16 0M17 4a4 4 0 0 1 0 8M23 21a8 8 0 0 0-5-7"/>',
    'more' => '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
];
function ico($k) { global $icons; return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icons[$k] . '</svg>'; }
$nav = !$u ? [] : ($u['role'] === 'admin'
    ? ['home' => 'Home', 'courses' => 'Courses', 'users' => 'People', 'fees' => 'Fees', 'more' => 'More']
    : ($u['role'] === 'teacher'
        ? ['home' => 'Home', 'courses' => 'Courses', 'my' => 'My Learning', 'users' => 'Students', 'more' => 'More']
        : ['home' => 'Home', 'my' => 'My Courses', 'courses' => 'Browse', 'more' => 'More']));
$active = ['course' => 'courses', 'course_edit' => 'courses', 'lesson' => 'courses', 'lesson_edit' => 'courses', 'quiz' => 'courses', 'quiz_edit' => 'courses', 'user' => 'users', 'user_edit' => 'users', 'enrollments' => 'users', 'expenses' => 'more', 'recurring' => 'more', 'expense_cats' => 'more', 'proofs' => 'fees', 'vouchers' => 'fees', 'voucher_gen' => 'fees', 'voucher' => 'fees', 'batches' => 'more', 'batch' => 'more', 'batch_edit' => 'more', 'attendance' => 'more', 'att_report' => 'more', 'posts' => 'more', 'post_edit' => 'more', 'messages' => 'more', 'pages_edit' => 'more', 'teachers' => 'more', 'tprofile' => 'more', 'reports' => 'more', 'settings' => 'more', 'profile' => 'more', 'announcements' => 'more'][$page] ?? $page;
if ($u && in_array($u['role'], ['student', 'teacher'], true) && in_array($page, ['lesson', 'quiz', 'quiz_result'])) { $lc = $page === 'lesson' ? val('SELECT course_id FROM lessons WHERE id=?', [(int)get('id')]) : val('SELECT course_id FROM quizzes WHERE id=?', [(int)get('id')]); if ($u['role'] === 'student' || ($lc && !val('SELECT 1 FROM courses WHERE id=? AND teacher_id=?', [$lc, $u['id']]))) $active = 'my'; }
if ($u && $u['role'] === 'student' && $page === 'course') $active = 'my';
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#4f46e5"><meta name="apple-mobile-web-app-capable" content="yes">
<title><?= e(($title ? "$title · " : '') . $inst) ?></title>
<link rel="manifest" href="manifest.json"><link rel="icon" href="assets/icon.svg"><link rel="apple-touch-icon" href="assets/icon-192.png">
<link rel="stylesheet" href="assets/style.css?v=<?= APP_VERSION ?>">
</head><body class="<?= $u ? 'app' : 'auth' ?>">
<?php if ($u): ?>
<header class="top">
  <?php if ($back): ?><a class="backbtn" href="<?= e($back) ?>" aria-label="Back">‹</a><?php endif ?>
  <div class="ttl"><?= e($title ?: $inst) ?></div>
  <a class="avatar" href="?p=profile"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></a>
</header>
<?php endif ?>
<main class="<?= $u ? 'wrap' : '' ?>">
<?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
<?= $body ?>
</main>
<?php if ($u): ?>
<nav class="bottom">
<?php foreach ($nav as $k => $label): ?>
  <a href="?p=<?= $k ?>" class="<?= $active === $k ? 'on' : '' ?>"><?= ico($k) ?><span><?= $label ?></span></a>
<?php endforeach ?>
</nav>
<?php endif ?>
<script src="assets/app.js?v=<?= APP_VERSION ?>"></script>
</body></html>
