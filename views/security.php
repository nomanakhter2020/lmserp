<?php
$me = user(); $title = role('admin') ? 'Security & backups' : 'Two-step verification'; $back = '?p=more';
$on = !empty($me['totp_secret']);
if (!$on && empty($_SESSION['totp_new'])) $_SESSION['totp_new'] = b32_secret();
$sec = $_SESSION['totp_new'] ?? '';
$uri = 'otpauth://totp/' . rawurlencode(setting('institute', APP_NAME) . ':' . $me['email']) . '?secret=' . $sec . '&issuer=' . rawurlencode(setting('institute', APP_NAME));
?>
<div class="card">
  <h3 style="margin-top:0">🔐 Two-step verification <?= $on ? '<span class="pill ok">ON</span>' : '<span class="pill warn">OFF</span>' ?></h3>
  <?php if ($on): ?>
    <p class="muted">When you log in with your password, the app also asks for the 6-digit code from your authenticator app. Fingerprint login stays one-touch.</p>
    <?php if (!is_super()): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="twofa_disable"><label>Your password (to turn off)<input name="password" type="password" required></label><button class="btn ghost block">Turn off</button></form><?php else: ?><p class="muted" style="font-size:13px">Required for your account. Password sign-in asks for the code; fingerprint / Face ID sign-in goes straight in.</p><?php endif ?>
  <?php else: ?>
    <p class="muted">Protects your account even if someone learns your password<?= role('admin') ? ' — strongly recommended for admins' : '' ?>.</p>
    <ol style="padding-left:18px;line-height:1.7"><li>Install <b>Google Authenticator</b> or <b>Microsoft Authenticator</b> on your phone.</li><li>Tap ＋ → <b>Scan a QR code</b> and scan this code (or enter the key manually).</li><li>Type the 6-digit code shown in the app below.</li></ol>
    <div id="qr" style="display:flex;justify-content:center;margin:10px 0"></div>
    <p class="center" style="font-family:monospace;font-size:15px;letter-spacing:2px;word-break:break-all"><?= e(trim(chunk_split($sec, 4, ' '))) ?></p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="twofa_enable"><label>6-digit code<input name="code" inputmode="numeric" maxlength="7" required style="font-size:22px;letter-spacing:5px;text-align:center"></label><button class="btn block">Turn on</button></form>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>try{new QRCode(document.getElementById('qr'),{text:<?= json_encode($uri, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>,width:180,height:180})}catch(e){}</script>
  <?php endif ?>
</div>
<?php if (role('admin')): $files = is_dir(BACKUP_DIR) ? (glob(BACKUP_DIR . '/backup-*.sql.gz') ?: []) : []; rsort($files); ?>
<div class="card">
  <h3 style="margin-top:0">💾 Database backups</h3>
  <p class="muted">A backup is made automatically every day (last 7 kept), stored outside the website folder. Download one regularly and keep it on your computer or Google Drive.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="backup_now"><button class="btn block">Back up now</button></form>
  <div class="list" style="margin-top:12px"><?php foreach ($files as $f): $b = basename($f); ?><a class="row" href="?p=backup_dl&f=<?= e($b) ?>"><span class="mi">🗄️</span><div class="grow"><b><?= e($b) ?></b><small><?= round(filesize($f) / 1024) ?> KB</small></div><span>⬇</span></a><?php endforeach ?></div>
  <?php if (!$files): ?><p class="muted">No backups yet.</p><?php endif ?>
</div>
<div class="card"><h3 style="margin-top:0">🛡️ Protection active</h3><ul style="padding-left:18px;line-height:1.8;margin:0">
  <li>Login locked for 15 min after 5 wrong passwords</li><li>Secure session cookies (HTTPS-only, hidden from scripts) and 24-hour idle logout</li>
  <li>Security headers (clickjacking, sniffing, HSTS)</li><li>Encrypted passwords, protected forms, private uploads and config outside the website folder</li></ul></div>
<?php endif ?>
