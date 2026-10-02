<?php $inst = setting('institute', APP_NAME); ?>
<form method="post" class="card authcard">
  <div class="logo"><img src="assets/icon.svg" alt=""></div>
  <h1><?= e($inst) ?></h1><p class="muted center">Sign in to continue learning</p>
  <?= csrf_field() ?><input type="hidden" name="a" value="login">
  <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
  <label>Email<input name="email" type="email" autocomplete="username" required></label>
  <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
  <button class="btn block">Sign in</button>
  <?php if (setting('allow_register', '1') === '1'): ?><p class="center">New student? <a href="?p=register">Create account</a></p><?php endif ?>
  <p class="center"><a href="./">← Back to website</a></p>
</form>
