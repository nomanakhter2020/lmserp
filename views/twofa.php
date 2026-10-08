<?php $inst = setting('institute', APP_NAME); ?>
<form method="post" class="card authcard">
  <div class="logo"><img src="assets/icon.svg" alt=""></div>
  <h1>Two-step verification</h1><p class="muted center">Open Google Authenticator (or any authenticator app) and enter the 6-digit code for <?= e($inst) ?>.</p>
  <?= csrf_field() ?><input type="hidden" name="a" value="twofa_verify">
  <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
  <label>Code<input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus style="font-size:24px;letter-spacing:6px;text-align:center"></label>
  <button class="btn block">Verify</button>
  <p class="center"><a href="?p=login">← Back to login</a></p>
</form>
