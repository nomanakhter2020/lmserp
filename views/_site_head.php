<?php
// Shared header for public pages. Expects: $pageTitle, $pageDesc, optional $canonical, $ogImage, $jsonld
$inst = setting('institute', APP_NAME);
$me = user();
$canReg = setting('allow_register', '1') === '1';
$cta = $me ? '?p=home' : ($canReg ? '?p=register' : '?p=login');
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<base href="<?= e(BASE) ?>">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(mb_substr($pageDesc, 0, 160)) ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><meta property="og:url" content="<?= e($canonical) ?>"><?php endif ?>
<meta property="og:type" content="<?= !empty($jsonld) ? 'article' : 'website' ?>"><meta property="og:title" content="<?= e($pageTitle) ?>"><meta property="og:description" content="<?= e(mb_substr($pageDesc, 0, 200)) ?>"><meta property="og:site_name" content="<?= e($inst) ?>">
<meta property="og:image" content="<?= e($ogImage ?? abs_url('assets/icon-512.png')) ?>"><meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#4f46e5"><link rel="icon" href="assets/icon.svg"><link rel="manifest" href="manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=<?= APP_VERSION ?>">
<?php if (!empty($jsonld)): ?><script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script><?php endif ?>
<?= ads_head() ?>
</head><body class="pub">
<header class="nav scrolled" id="top">
  <div class="container nav-in">
    <a class="brand" href="./"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a>
    <nav class="links" id="menu">
      <a href="./#courses">Courses</a><a href="./#teachers">Teachers</a><a href="blog">Blog</a><a href="?p=shop">Shop</a><a href="about">About</a><a href="contact">Contact</a>
      <?php if ($me): ?><a class="m-only btn" href="?p=home">My dashboard →</a><?php else: ?><a class="m-only btn-o" href="?p=login">Log in</a><a class="m-only btn" href="<?= $cta ?>">Enroll now</a><?php endif ?>
    </nav>
    <div class="nav-cta"><?php if ($me): ?><a class="btn" href="?p=home">My dashboard →</a><?php else: ?><a class="login" href="?p=login">Log in</a><?php if ($canReg): ?><a class="btn" href="?p=register">Enroll now</a><?php endif ?><?php endif ?></div>
    <button class="burger" aria-label="Menu" onclick="document.body.classList.toggle('open')"><span></span><span></span><span></span></button>
  </div>
</header>
