<?php if (setting('allow_register', '1') !== '1') redirect('?p=login'); $asP = get('as') === 'parent'; $asT = get('as') === 'teacher' && mall(); ?>
<form method="post" class="card authcard">
  <div class="logo"><img src="assets/icon.svg" alt=""></div>
  <h1>Create account</h1><p class="muted center">Join <?= e(setting('institute', APP_NAME)) ?></p>
  <?= csrf_field() ?><input type="hidden" name="a" value="register">
  <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
  <div class="seg"><a href="?p=register" class="<?= $asP || $asT ? '' : 'on' ?>">🎒 Student</a><a href="?p=register&as=parent" class="<?= $asP ? 'on' : '' ?>">👨‍👩‍👧 Parent</a><?php if (mall()): ?><a href="?p=register&as=teacher" class="<?= $asT ? 'on' : '' ?>">👩‍🏫 Teacher</a><?php endif ?></div>
  <?php if ($asP): ?><input type="hidden" name="as" value="parent"><?php elseif ($asT): ?><input type="hidden" name="as" value="teacher"><?php endif ?>
  <label><?= $asP ? 'Your name (parent)' : 'Full name' ?><input name="name" required></label>
  <label>Email<input name="email" type="email" required></label>
  <label>Phone / WhatsApp<input name="phone" inputmode="tel"></label>
  <label>Password<input name="password" type="password" minlength="6" required></label>
  <?php if ($asP): ?><label>Child's name <small>(more than one? separate with commas)</small><input name="child_name" required placeholder="e.g. Ayesha, Ahmed"></label><p class="muted" style="font-size:13px;margin-top:-4px">You can add more children later and give them their own login.</p><?php endif ?>
  <button class="btn block">Sign up</button>
  <p class="center">Already have an account? <a href="?p=login">Sign in</a></p>
  <p class="center"><a href="./">← Back to website</a></p>
</form>
