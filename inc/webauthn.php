<?php
// Fingerprint / Face ID login (WebAuthn passkeys). Browser sends SPKI public key; we verify signatures with OpenSSL.
header('Content-Type: application/json');
function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function ub64(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4)); }
function wa_out(array $d, int $code = 200): never { http_response_code($code); echo json_encode($d); exit; }
$host = parse_url(abs_url(''), PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
$rpId = preg_replace('/:\d+$/', '', $host);
$origin = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http') : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? $rpId);
$step = (string)post('step');
function check_client(string $cdj, string $type, string $origin): void {
    $c = json_decode($cdj, true);
    if (!$c || ($c['type'] ?? '') !== $type) wa_out(['error' => 'Bad request'], 400);
    if (!hash_equals((string)($_SESSION['wa_chal'] ?? ''), (string)($c['challenge'] ?? ''))) wa_out(['error' => 'Session expired, try again'], 400);
    if (($c['origin'] ?? '') !== $origin) wa_out(['error' => 'Origin mismatch'], 400);
    unset($_SESSION['wa_chal']);
}
function check_auth(string $ad, string $rpId): array {
    if (strlen($ad) < 37 || !hash_equals(hash('sha256', $rpId, true), substr($ad, 0, 32))) wa_out(['error' => 'Wrong site'], 400);
    $flags = ord($ad[32]); if (!($flags & 1)) wa_out(['error' => 'User not present'], 400);
    return [$flags, unpack('N', substr($ad, 33, 4))[1]];
}
$chal = b64u(random_bytes(32));
if ($step === 'reg_opts') {
    $u = user(); if (!$u) wa_out(['error' => 'Login first'], 401);
    $_SESSION['wa_chal'] = $chal;
    $ex = array_map(fn($c) => ['type' => 'public-key', 'id' => $c], array_column(all('SELECT cred_id FROM webauthn_creds WHERE user_id=?', [$u['id']]), 'cred_id'));
    wa_out(['challenge' => $chal, 'rp' => ['id' => $rpId, 'name' => setting('institute', APP_NAME)], 'user' => ['id' => b64u('u' . $u['id']), 'name' => $u['email'], 'displayName' => $u['name']],
        'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7], ['type' => 'public-key', 'alg' => -257]], 'timeout' => 60000, 'attestation' => 'none',
        'authenticatorSelection' => ['authenticatorAttachment' => 'platform', 'residentKey' => 'required', 'userVerification' => 'required'], 'excludeCredentials' => $ex]);
}
if ($step === 'reg_save') {
    $u = user(); if (!$u) wa_out(['error' => 'Login first'], 401);
    check_client(ub64((string)post('clientData')), 'webauthn.create', $origin);
    check_auth(ub64((string)post('authData')), $rpId);
    $spki = ub64((string)post('publicKey')); $alg = (int)post('alg');
    if ($spki === '' || !in_array($alg, [-7, -257], true)) wa_out(['error' => 'This phone does not support fingerprint login'], 400);
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    if (!openssl_pkey_get_public($pem)) wa_out(['error' => 'Invalid key'], 400);
    $cid = substr((string)post('credId'), 0, 255);
    q('INSERT INTO webauthn_creds(user_id,cred_id,pubkey,alg,name) VALUES(?,?,?,?,?)', [$u['id'], $cid, $pem, $alg, mb_substr((string)post('device') ?: 'Phone', 0, 80)]);
    wa_out(['ok' => true]);
}
if ($step === 'login_opts') {
    $_SESSION['wa_chal'] = $chal;
    wa_out(['challenge' => $chal, 'rpId' => $rpId, 'timeout' => 60000, 'userVerification' => 'required', 'allowCredentials' => []]);
}
if ($step === 'login') {
    $cdjRaw = ub64((string)post('clientData')); $ad = ub64((string)post('authData')); $sig = ub64((string)post('signature'));
    check_client($cdjRaw, 'webauthn.get', $origin);
    [, $count] = check_auth($ad, $rpId);
    $cr = one('SELECT c.*,u.active FROM webauthn_creds c JOIN users u ON u.id=c.user_id WHERE c.cred_id=?', [substr((string)post('credId'), 0, 255)]);
    if (!$cr || !$cr['active']) wa_out(['error' => 'Fingerprint not registered on this account. Log in with password and enable it again.'], 400);
    $ok = openssl_verify($ad . hash('sha256', $cdjRaw, true), $sig, $cr['pubkey'], OPENSSL_ALGO_SHA256) === 1;
    if (!$ok) wa_out(['error' => 'Verification failed'], 400);
    if ($count > 0 && $count <= (int)$cr['sign_count']) wa_out(['error' => 'Security check failed'], 400);
    q('UPDATE webauthn_creds SET sign_count=?,last_used=NOW() WHERE id=?', [$count, $cr['id']]);
    session_regenerate_id(true); unset($_SESSION['csrf']); $_SESSION['uid'] = (int)$cr['user_id']; $_SESSION['via'] = 'passkey';
    wa_out(['ok' => true, 'go' => './']);
}
if ($step === 'remove') {
    $u = user(); if (!$u) wa_out(['error' => 'Login first'], 401);
    q('DELETE FROM webauthn_creds WHERE id=? AND user_id=?', [(int)post('cid'), $u['id']]); wa_out(['ok' => true]);
}
wa_out(['error' => 'Unknown step'], 400);
