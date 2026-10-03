<?php
$me = user(); $title = 'Fingerprint login'; $back = '?p=more';
$creds = all('SELECT * FROM webauthn_creds WHERE user_id=? ORDER BY id DESC', [$me['id']]);
?>
<div class="hero"><div class="big sm">👆 Fingerprint / Face ID</div><div class="muted-l">Log in with one touch on this phone — no password needed. Your fingerprint never leaves your phone.</div></div>
<div class="card" id="bioCard"><p id="bioMsg" class="muted" style="margin-top:0">Checking this device…</p><button class="btn block" id="bioEn" hidden onclick="bioEnable(this)">Enable on this phone</button></div>
<?php if ($creds): ?><h2>Registered devices</h2><div class="list"><?php foreach ($creds as $c): ?><div class="row"><span class="mi">📱</span><div class="grow"><b><?= e($c['name'] ?: 'Device') ?></b><small>Added <?= date('d M Y', strtotime($c['created_at'])) ?><?= $c['last_used'] ? ' · last used ' . date('d M, h:i A', strtotime($c['last_used'])) : '' ?></small></div><button class="btn sm ghost" onclick="if(confirm('Remove this device?'))Bio.remove(<?= $c['id'] ?>).then(()=>location.reload())">Remove</button></div><?php endforeach ?></div><?php endif ?>
<input type="hidden" name="_csrf" value="<?= csrf() ?>">
<script src="assets/bio.js?v=<?= APP_VERSION ?>"></script>
<script>const m=document.getElementById('bioMsg'),b=document.getElementById('bioEn');
Bio.supported().then(ok=>{if(!ok){m.textContent='This device or browser does not support fingerprint login. Use the installed app on a phone with fingerprint or Face ID (Chrome on Android, Safari on iPhone).';return}
m.textContent=Bio.on()?'✅ Fingerprint login is enabled on this phone. You can add it again if you reset your phone.':'Tap below and confirm with your fingerprint or face.';b.hidden=false;if(Bio.on())b.textContent='Re-enable on this phone';});
async function bioEnable(x){x.disabled=true;try{await Bio.enable();m.textContent='✅ Done! Next time tap "Login with fingerprint" on the login screen.';setTimeout(()=>location.reload(),1200)}catch(e){m.textContent=e.name==='NotAllowedError'?'Cancelled.':e.name==='InvalidStateError'?'Already enabled on this phone.':(e.message||'Could not enable')}x.disabled=false}</script>
