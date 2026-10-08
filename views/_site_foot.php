<?php
$inst = setting('institute', APP_NAME);
$phone = setting('phone'); $email = setting('site_email');
$wa = preg_replace('/\D/', '', setting('site_whatsapp') ?: $phone); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1);
$cats = all('SELECT category,COUNT(*) n FROM posts WHERE published=1 GROUP BY category ORDER BY n DESC LIMIT 5');
?>
<footer class="foot big">
  <div class="container fgrid4">
    <div><a class="brand" href="./"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a>
      <p><?= e(mb_strimwidth(setting('site_tagline', 'Learn new skills online, at your own pace'), 0, 120, '…')) ?></p>
      <?php if ($phone): ?><p>📞 <?= e($phone) ?></p><?php endif ?><?php if ($email): ?><p>✉️ <?= e($email) ?></p><?php endif ?></div>
    <div><h4>Explore</h4><?php if (mod('lms')): ?><a href="./#courses">Courses</a><?php endif ?><a href="./#teachers">Teachers</a><a href="blog">Blog</a><a href="?p=login">Student login</a></div>
    <div><h4>Blog topics</h4><?php foreach ($cats as $c): ?><a href="blog?cat=<?= e(rawurlencode($c['category'])) ?>"><?= e($c['category']) ?></a><?php endforeach; if (!$cats): ?><a href="blog">All articles</a><?php endif ?></div>
    <div><h4>Company</h4><a href="about">About us</a><a href="contact">Contact us</a><?php if (mod('shop')): ?><a href="shop">Shop</a><?php endif ?><?php if (mod('shop')): ?><a href="track">Track your order</a><?php endif ?><a href="privacy-policy">Privacy policy</a><a href="terms">Terms &amp; conditions</a><a href="disclaimer">Disclaimer</a></div>
  </div>
  <div class="container foot-bottom"><small>© <?= date('Y') ?> <?= e($inst) ?>. All rights reserved.</small><small><a href="privacy-policy">Privacy</a> · <a href="terms">Terms</a> · <a href="sitemap.xml">Sitemap</a></small></div>
</footer>
<?php if ($wa): ?><a class="wa-float" href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 32 32" width="30" height="30" fill="#fff"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7c-2 0-4-.6-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.3 1.4 3.5c.2.2 2.4 3.6 5.7 5 2.1.9 3 1 4 .8.7-.1 1.9-.8 2.2-1.5.3-.7.3-1.3.2-1.5l-.7-.3z"/></svg></a><?php endif ?>
<div class="cookie" id="cookie" hidden><p>We use cookies to keep you signed in and to show relevant ads. See our <a href="privacy-policy">Privacy Policy</a>.</p><button class="btn sm" onclick="try{localStorage.setItem('ck','1')}catch(e){}document.getElementById('cookie').hidden=true">OK</button></div>
<script>
try{if(!localStorage.getItem('ck'))document.getElementById('cookie').hidden=false}catch(e){document.getElementById('cookie').hidden=false}
document.querySelectorAll('#menu a').forEach(a=>a.addEventListener('click',()=>document.body.classList.remove('open')));
</script>
<script src="assets/pw.js?v=<?= APP_VERSION ?>"></script>
</body></html>
