<?php
// Public certificate view & verification
$code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string)get('c')));
$cert = $code ? one('SELECT ce.*,u.name,c.title,t.name tname FROM certificates ce JOIN users u ON u.id=ce.user_id JOIN courses c ON c.id=ce.course_id LEFT JOIN users t ON t.id=c.teacher_id WHERE ce.code=?', [$code]) : null;
$inst = setting('institute', APP_NAME);
$verify = abs_url('?p=cert&c=' . $code);
$signer = setting('cert_signer') ?: 'Director'; $stitle = setting('cert_signer_title') ?: $inst;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $cert ? e($cert['name'] . ' — Certificate · ' . $inst) : 'Verify certificate · ' . e($inst) ?></title>
<meta name="robots" content="noindex"><link rel="icon" href="assets/icon.svg">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/cert.css?v=<?= APP_VERSION ?>">
</head><body>
<?php if (!$cert): ?>
  <main class="verify"><img src="assets/icon.svg" width="56" alt=""><h1>Verify a certificate</h1><p><?= e($inst) ?></p>
    <?php if ($code): ?><div class="bad">❌ No certificate found with code <b><?= e($code) ?></b>.</div><?php endif ?>
    <form><input type="hidden" name="p" value="cert"><input name="c" placeholder="Certificate code, e.g. CAB12-3F4E" value="<?= e($code) ?>" required><button>Verify</button></form>
    <a href="./">← <?= e($inst) ?></a></main>
<?php else: ?>
  <div class="bar no-print">
    <?php if ($cert['revoked']): ?><span class="bad">❌ This certificate has been revoked</span><?php else: ?><span class="good">✅ Verified certificate · issued by <?= e($inst) ?></span><?php endif ?>
    <span><button onclick="window.print()">🖨 Print / Save PDF</button><a href="https://wa.me/?text=<?= rawurlencode('My certificate from ' . $inst . ': ' . $verify) ?>" target="_blank" rel="noopener">Share</a></span>
  </div>
  <div class="sheet-wrap"><div class="cert <?= $cert['revoked'] ? 'revoked' : '' ?>">
    <div class="frame">
      <div class="top"><img src="assets/icon.svg" alt=""><div class="inst"><?= e($inst) ?></div></div>
      <div class="kicker">Certificate of Completion</div>
      <p class="pre">This is to certify that</p>
      <div class="name"><?= e($cert['name']) ?></div>
      <p class="pre">has successfully completed the course</p>
      <div class="course"><?= e($cert['title']) ?></div>
      <?php if ($cert['grade']): ?><div class="grade">with <b><?= e($cert['grade']) ?></b></div><?php endif ?>
      <div class="foot">
        <div class="sig"><div class="line"><?= e(date('F j, Y', strtotime($cert['issued_at']))) ?></div><small>Date of issue</small></div>
        <div class="seal"><div id="qr"></div><small><?= e($cert['code']) ?></small></div>
        <div class="sig"><div class="line script"><?= e($cert['tname'] ?: $signer) ?></div><small><?= $cert['tname'] ? 'Course instructor' : e($stitle) ?></small></div>
      </div>
      <div class="verify-note">Verify at <?= e(abs_url('?p=cert')) ?> · Code <?= e($cert['code']) ?></div>
    </div>
  </div></div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>try{new QRCode(document.getElementById('qr'),{text:<?= json_encode($verify) ?>,width:88,height:88,colorDark:'#1e1b4b',colorLight:'#ffffff'})}catch(e){}</script>
<?php endif ?>
</body></html>
