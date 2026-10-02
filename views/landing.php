<?php
// Public marketing homepage (guests). Standalone page, not the app shell.
$inst = setting('institute', APP_NAME);
$tag = setting('site_tagline', 'Learn new skills online, at your own pace');
$about = setting('site_about', "We offer practical, job-ready courses taught by experienced teachers. Watch video lessons on your phone, test yourself with quizzes, and track your progress — all in one app.");
$phone = setting('phone'); $email = setting('site_email'); $addr = setting('site_address');
$wa = preg_replace('/\D/', '', setting('site_whatsapp') ?: $phone); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1);
$courses = all('SELECT c.*,u.name tname,cat.name cname,(SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) ls FROM courses c LEFT JOIN users u ON u.id=c.teacher_id LEFT JOIN categories cat ON cat.id=c.category_id WHERE c.published=1 ORDER BY c.id DESC LIMIT 9');
$stats = [
  [(int)val('SELECT COUNT(*) FROM users WHERE role="student"'), 'Students'],
  [(int)val('SELECT COUNT(*) FROM courses WHERE published=1'), 'Courses'],
  [(int)val('SELECT COUNT(*) FROM lessons'), 'Video lessons'],
  [(int)val('SELECT COUNT(*) FROM users WHERE role="teacher"') ?: 1, 'Teachers'],
];
$me = user();
$canReg = setting('allow_register', '1') === '1';
$cta = $me ? '?p=courses' : ($canReg ? '?p=register' : '?p=login');
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($inst) ?> — <?= e($tag) ?></title>
<meta name="description" content="<?= e(mb_substr($about, 0, 155)) ?>">
<meta property="og:title" content="<?= e($inst) ?>"><meta property="og:description" content="<?= e($tag) ?>"><meta property="og:image" content="assets/icon-512.png">
<meta name="theme-color" content="#4f46e5"><link rel="manifest" href="manifest.json"><link rel="icon" href="assets/icon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=<?= APP_VERSION ?>">
</head><body>

<header class="nav" id="top">
  <div class="container nav-in">
    <a class="brand" href="./"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a>
    <nav class="links" id="menu">
      <a href="#courses">Courses</a><a href="#why">Why us</a><a href="#how">How it works</a><a href="#faq">FAQ</a><a href="#contact">Contact</a>
      <?php if ($me): ?><a class="m-only btn" href="?p=home">My dashboard →</a>
      <?php else: ?><a class="m-only btn-o" href="?p=login">Log in</a><a class="m-only btn" href="<?= $cta ?>"><?= $canReg ? 'Enroll now' : 'Log in' ?></a><?php endif ?>
    </nav>
    <div class="nav-cta"><?php if ($me): ?><a class="btn" href="?p=home">My dashboard →</a><?php else: ?><a class="login" href="?p=login">Log in</a><?php if ($canReg): ?><a class="btn" href="?p=register">Enroll now</a><?php endif ?><?php endif ?></div>
    <button class="burger" aria-label="Menu" onclick="document.body.classList.toggle('open')"><span></span><span></span><span></span></button>
  </div>
</header>

<section class="hero">
  <div class="container hero-in">
    <div class="hero-txt">
      <span class="badge">🎓 Admissions open</span>
      <h1><?= e($tag) ?></h1>
      <p><?= e($about) ?></p>
      <div class="hero-cta"><a class="btn lg" href="<?= $cta ?>">Start learning →</a><a class="btn-o lg" href="#courses">Browse courses</a></div>
      <div class="trust"><span>✓ Learn on mobile</span><span>✓ Quizzes &amp; progress</span><span>✓ Easy JazzCash / EasyPaisa fee</span></div>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="phone">
        <div class="ph-top"></div>
        <div class="ph-card grad"><small>Continue learning</small><b><?= e($courses[0]['title'] ?? 'Web Development') ?></b><div class="ph-bar"><i style="width:64%"></i></div><small>64% complete</small></div>
        <div class="ph-row"><span class="dot ok">✓</span><div><b>Lesson 1</b><small>Video · 12 min</small></div></div>
        <div class="ph-row"><span class="dot ok">✓</span><div><b>Lesson 2</b><small>Video · 18 min</small></div></div>
        <div class="ph-row"><span class="dot">3</span><div><b>Lesson 3</b><small>Reading</small></div></div>
        <div class="ph-row"><span class="dot q">?</span><div><b>Quiz 1</b><small>10 questions</small></div></div>
      </div>
      <div class="float f1">🏆 Quiz passed · 90%</div>
      <div class="float f2">▶ New lesson added</div>
    </div>
  </div>
</section>

<section class="stats-band"><div class="container stats">
  <?php foreach ($stats as [$n, $l]): ?><div><b data-count="<?= $n ?>"><?= $n ?></b><span><?= $l ?></span></div><?php endforeach ?>
</div></section>

<section id="courses" class="sec">
  <div class="container">
    <div class="sec-head"><span class="kicker">Our courses</span><h2>Pick a course and start today</h2><p>Every course includes video lessons, notes and quizzes you can take on your phone.</p></div>
    <div class="cgrid">
    <?php foreach ($courses as $c): ?>
      <article class="course" style="--c:<?= e($c['color']) ?>">
        <div class="c-top"><span><?= e($c['cname'] ?: 'General') ?></span></div>
        <div class="c-body">
          <h3><?= e($c['title']) ?></h3>
          <p><?= e(mb_strimwidth((string)$c['description'], 0, 120, '…')) ?></p>
          <div class="c-meta"><span>📚 <?= $c['ls'] ?> lessons</span><?php if ($c['tname']): ?><span>👤 <?= e($c['tname']) ?></span><?php endif ?></div>
        </div>
        <div class="c-foot"><b><?= (float)$c['fee'] > 0 ? money($c['fee']) : 'Free' ?></b><a class="btn sm" href="<?= $cta ?>">Enroll</a></div>
      </article>
    <?php endforeach ?>
    </div>
    <?php if (!$courses): ?><p class="center muted">New courses are coming soon. Contact us to register your interest.</p><?php endif ?>
  </div>
</section>

<section id="why" class="sec alt">
  <div class="container">
    <div class="sec-head"><span class="kicker">Why choose us</span><h2>Learning that fits your life</h2></div>
    <div class="fgrid">
      <div class="feat"><i>📱</i><h3>Learn on your phone</h3><p>Install our app and watch lessons anytime, anywhere — no laptop needed.</p></div>
      <div class="feat"><i>🎥</i><h3>Video lessons</h3><p>Clear, step-by-step videos with notes and downloadable material.</p></div>
      <div class="feat"><i>📝</i><h3>Quizzes &amp; results</h3><p>Test yourself after every topic and see your score instantly.</p></div>
      <div class="feat"><i>📈</i><h3>Track your progress</h3><p>See exactly how much you've completed and pick up where you left off.</p></div>
      <div class="feat"><i>👨‍🏫</i><h3>Experienced teachers</h3><p>Learn from teachers who know the subject and answer your questions.</p></div>
      <div class="feat"><i>💳</i><h3>Easy fee payment</h3><p>Pay by bank, JazzCash, EasyPaisa or cash and get an instant digital receipt.</p></div>
    </div>
  </div>
</section>

<section id="how" class="sec">
  <div class="container">
    <div class="sec-head"><span class="kicker">How it works</span><h2>Start in 3 simple steps</h2></div>
    <div class="steps">
      <div class="step"><b>1</b><h3>Create your account</h3><p>Sign up free in under a minute with your name and phone.</p></div>
      <div class="step"><b>2</b><h3>Choose a course &amp; pay</h3><p>Enroll, pay the fee and upload your payment screenshot.</p></div>
      <div class="step"><b>3</b><h3>Start learning</h3><p>Once verified, your course unlocks — watch, practise, pass.</p></div>
    </div>
    <div class="center"><a class="btn lg" href="<?= $cta ?>"><?= $me ? 'Browse courses →' : 'Create free account →' ?></a></div>
  </div>
</section>

<section id="faq" class="sec alt">
  <div class="container narrow">
    <div class="sec-head"><span class="kicker">FAQ</span><h2>Frequently asked questions</h2></div>
    <details><summary>Do I need a laptop?</summary><p>No. Everything works on your mobile phone. You can even install it like an app from your browser.</p></details>
    <details><summary>How do I pay the fee?</summary><p>After enrolling, you'll see our bank, JazzCash and EasyPaisa details. Pay, upload a screenshot, and your course unlocks once we verify it. Cash at the office also works.</p></details>
    <details><summary>Can I watch lessons again?</summary><p>Yes, you can rewatch any lesson in your enrolled courses as many times as you like.</p></details>
    <details><summary>Will I get a receipt?</summary><p>Yes, every payment gets a digital receipt you can download or print.</p></details>
    <details><summary>How do I contact a teacher?</summary><p>Reach us on WhatsApp or phone using the contact details below and we'll connect you.</p></details>
  </div>
</section>

<section id="contact" class="sec">
  <div class="container">
    <div class="cta-box">
      <div><h2>Ready to start learning?</h2><p>Join <?= e($inst) ?> today. Have a question? We're one message away.</p></div>
      <div class="cta-btns"><a class="btn lg light" href="<?= $cta ?>">Enroll now</a><?php if ($wa): ?><a class="btn-o lg light" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Assalam o Alaikum, I want to know about your courses.') ?>" target="_blank" rel="noopener">💬 WhatsApp us</a><?php endif ?></div>
    </div>
    <div class="contact-grid">
      <?php if ($phone): ?><a class="ci" href="tel:<?= e($phone) ?>"><i>📞</i><div><small>Call us</small><b><?= e($phone) ?></b></div></a><?php endif ?>
      <?php if ($wa): ?><a class="ci" href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener"><i>💬</i><div><small>WhatsApp</small><b>Chat with us</b></div></a><?php endif ?>
      <?php if ($email): ?><a class="ci" href="mailto:<?= e($email) ?>"><i>✉️</i><div><small>Email</small><b><?= e($email) ?></b></div></a><?php endif ?>
      <?php if ($addr): ?><div class="ci"><i>📍</i><div><small>Visit us</small><b><?= e($addr) ?></b></div></div><?php endif ?>
    </div>
  </div>
</section>

<footer class="foot"><div class="container foot-in">
  <a class="brand" href="#top"><img src="assets/icon.svg" alt=""><span><?= e($inst) ?></span></a>
  <nav><a href="#courses">Courses</a><a href="#faq">FAQ</a><a href="#contact">Contact</a><a href="?p=<?= $me ? 'home' : 'login' ?>"><?= $me ? 'My dashboard' : 'Student login' ?></a></nav>
  <small>© <?= date('Y') ?> <?= e($inst) ?>. All rights reserved.</small>
</div></footer>

<?php if ($wa): ?><a class="wa-float" href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 32 32" width="30" height="30" fill="#fff"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7c-2 0-4-.6-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.3 1.4 3.5c.2.2 2.4 3.6 5.7 5 2.1.9 3 1 4 .8.7-.1 1.9-.8 2.2-1.5.3-.7.3-1.3.2-1.5l-.7-.3z"/></svg></a><?php endif ?>

<script>
document.querySelectorAll('#menu a').forEach(a=>a.addEventListener('click',()=>document.body.classList.remove('open')));
addEventListener('scroll',()=>document.querySelector('.nav').classList.toggle('scrolled',scrollY>10),{passive:true});
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);
  const n=e.target.querySelector('[data-count]');}}),{threshold:.15});
document.querySelectorAll('.sec-head,.course,.feat,.step,details,.cta-box,.stats>div').forEach(el=>{el.classList.add('rv');io.observe(el)});
document.querySelectorAll('[data-count]').forEach(el=>{const t=+el.dataset.count;if(t<2)return;let s=null;el.textContent='0';
  new IntersectionObserver((es,o)=>{if(!es[0].isIntersecting)return;o.disconnect();const step=ts=>{s??=ts;const p=Math.min((ts-s)/1200,1);el.textContent=Math.round(t*p*(2-p))+(p===1?'+':'');if(p<1)requestAnimationFrame(step)};requestAnimationFrame(step)}).observe(el)});
if('serviceWorker' in navigator)navigator.serviceWorker.register('sw.js');
</script>
</body></html>
