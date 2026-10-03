<?php
$slug = (string)get('slug');
if (!isset(LEGAL_PAGES[$slug])) { http_response_code(404); $slug = 'about'; }
$inst = setting('institute', APP_NAME);
$name = LEGAL_PAGES[$slug];
$pageTitle = "$name · $inst";
$pageDesc = ['about' => "Learn about $inst — our mission, teachers and courses.", 'privacy-policy' => "Privacy Policy of $inst: how we collect, use and protect your information, cookies and advertising.", 'terms' => "Terms and conditions for using $inst.", 'disclaimer' => "Disclaimer for $inst educational content and advertisements.", 'contact' => "Contact $inst — questions about courses, fees or admissions."][$slug];
$canonical = abs_url($slug);
$sent = flash();
require __DIR__ . '/_site_head.php';
$phone = setting('phone'); $email = setting('site_email'); $addr = setting('site_address');
$wa = preg_replace('/\D/', '', setting('site_whatsapp') ?: $phone); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1);
?>
<section class="page-hero sm"><div class="container"><nav class="crumbs"><a href="./">Home</a> › <?= e($name) ?></nav><h1><?= e($name) ?></h1></div></section>
<main class="container sec-sm narrow">
  <?php if ($slug === 'contact'): ?>
    <div class="contact-wrap">
      <div class="prose"><?= md(legal_content('contact')) ?>
        <div class="contact-list"><?php if ($phone): ?><a href="tel:<?= e($phone) ?>">📞 <?= e($phone) ?></a><?php endif ?><?php if ($wa): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">💬 WhatsApp us</a><?php endif ?><?php if ($email): ?><a href="mailto:<?= e($email) ?>">✉️ <?= e($email) ?></a><?php endif ?><?php if ($addr): ?><span>📍 <?= e($addr) ?></span><?php endif ?></div></div>
      <form method="post" action="contact" class="cform"><?= csrf_field() ?><input type="hidden" name="a" value="contact_send">
        <?php if ($sent): ?><div class="cv-note <?= $sent[1] === 'ok' ? 'ok' : '' ?>"><?= e($sent[0]) ?></div><?php endif ?>
        <div class="hp"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <label>Your name<input name="name" required maxlength="120"></label>
        <div class="two"><label>Email<input name="email" type="email" maxlength="160"></label><label>Phone / WhatsApp<input name="phone" maxlength="40"></label></div>
        <label>Subject<input name="subject" maxlength="200"></label>
        <label>Message<textarea name="message" rows="5" required maxlength="3000"></textarea></label>
        <button class="btn lg">Send message</button>
      </form>
    </div>
  <?php else: ?>
    <article class="prose legal"><?= md(legal_content($slug)) ?></article>
  <?php endif ?>
</main>
<?php require __DIR__ . '/_site_foot.php';
