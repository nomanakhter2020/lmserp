<?php
$inst = setting('institute', 'EduMall.pk'); $pageTitle = 'List your institute free · ' . $inst; $pageDesc = "List your school, college, university or academy on $inst and receive admission enquiries online."; $canonical = abs_url('list-your-institute');
require __DIR__ . '/_site_head.php';
?>
<main class="container sec-sm"><div class="joingrid">
  <div class="joininfo"><span class="kicker">For institutes</span><h1>Get more admissions with <?= e($inst) ?></h1>
    <ul class="ptrust" style="font-size:15.5px"><li>🏫 Your own profile page — fees, programs, facilities, photos</li><li>📝 Admission enquiries straight to your dashboard &amp; WhatsApp</li><li>👩‍🏫 Connect teachers from the <?= e($inst) ?> teacher pool</li><li>📚 Sell online courses and manage students in one app</li><li>📱 Works on mobile like an app</li></ul>
    <p class="muted">Listings are reviewed by our team before they go live — usually within 24 hours.</p></div>
  <form method="post" class="cform co"><?= csrf_field() ?><input type="hidden" name="a" value="inst_register">
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
    <?php if ($f = flash()): ?><div class="cv-note"><?= e($f[0]) ?></div><?php endif ?>
    <h3>Institute details</h3>
    <label>Institute name<input name="inst_name" required placeholder="e.g. Bright Future School"></label>
    <div class="two"><label>Type<select name="type"><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><option value="<?= $k ?>"><?= $ic ?> <?= $l ?></option><?php endforeach ?></select></label><label>City<input name="city" required list="jcities"><datalist id="jcities"><?php foreach (['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Hyderabad', 'Peshawar', 'Quetta'] as $ct): ?><option value="<?= $ct ?>"><?php endforeach ?></datalist></label></div>
    <h3>Your login</h3>
    <div class="two"><label>Your name<input name="name" required autocomplete="name"></label><label>Phone / WhatsApp<input name="phone" required inputmode="tel" autocomplete="tel"></label></div>
    <label>Email<input name="email" type="email" required autocomplete="email"></label>
    <label>Password <small>(8+ characters)</small><input name="password" type="password" minlength="8" required autocomplete="new-password"></label>
    <button class="btn lg block-b">Create free listing</button>
    <p class="muted center" style="font-size:13px">Already listed? <a href="?p=login">Log in</a> · By continuing you agree to our <a href="terms">terms</a>.</p>
  </form>
</div></main>
<?php require __DIR__ . '/_site_foot.php';
