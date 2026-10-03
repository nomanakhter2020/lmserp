<?php $inst = setting('institute', APP_NAME); ?>
<form method="post" class="card authcard">
  <div class="logo"><img src="assets/icon.svg" alt=""></div>
  <h1><?= e($inst) ?></h1><p class="muted center">Sign in to continue learning</p>
  <?= csrf_field() ?><input type="hidden" name="a" value="login">
  <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
  <label>Email<input name="email" type="email" autocomplete="username" required></label>
  <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
  <button class="btn block">Sign in</button>
  <button type="button" id="bioBtn" class="btn ghost block" hidden onclick="bioLogin(this)">👆 Login with fingerprint / Face ID</button>
  <div id="bioErr" class="alert err" hidden></div>
  <?php if (setting('allow_register', '1') === '1'): ?><p class="center">New here? <a href="?p=register">Student sign up</a> · <a href="?p=register&as=parent">Parent sign up</a></p><?php endif ?>
  <p class="center"><a href="./">← Back to website</a></p>
</form>
<script src="assets/bio.js?v=<?= APP_VERSION ?>"></script>
<script>Bio.supported().then(ok=>{if(!ok)return;const b=document.getElementById('bioBtn');b.hidden=false;if(Bio.on()){b.classList.remove('ghost');}});
async function bioLogin(b){const e=document.getElementById('bioErr');e.hidden=true;b.disabled=true;try{await Bio.login()}catch(x){if(x.name!=='NotAllowedError'){e.textContent=x.message||'Could not verify';e.hidden=false}else{e.textContent='Fingerprint not set up yet? Log in with password, then enable it from More → Fingerprint login.';e.hidden=false}}b.disabled=false}</script>
