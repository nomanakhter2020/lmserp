<?php if (setting('allow_register', '1') !== '1') redirect('?p=login'); ?>
<form method="post" class="card authcard">
  <div class="logo"><img src="assets/icon.svg" alt=""></div>
  <h1>Create account</h1><p class="muted center">Join <?= e(setting('institute', APP_NAME)) ?></p>
  <?= csrf_field() ?><input type="hidden" name="a" value="register">
  <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
  <label>Full name<input name="name" required></label>
  <label>Email<input name="email" type="email" required></label>
  <label>Phone / WhatsApp<input name="phone" inputmode="tel"></label>
  <label>Password<input name="password" type="password" minlength="6" required></label>
  <button class="btn block">Sign up</button>
  <p class="center">Already have an account? <a href="?p=login">Sign in</a></p>
</form>
