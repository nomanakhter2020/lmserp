<?php
// LMS ERP core: config, db, auth, helpers
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Karachi');

const APP_NAME = 'LMS ERP';
const APP_VERSION = '1.4.1';
const DB_VERSION = 4;
define('CONFIG_FILE', dirname(__DIR__, 2) . '/lmserp-config.php'); // outside public_html
define('UPLOAD_DIR', dirname(__DIR__, 2) . '/lmserp-uploads'); // outside public_html, survives git deploys

function cfg(): ?array {
    static $c = null;
    if ($c === null) $c = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : [];
    return $c ?: null;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = cfg();
    if (!$c) { header('Location: install.php'); exit; }
    $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function one(string $sql, array $p = []) { return q($sql, $p)->fetch(); }
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); }

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return 'Rs ' . number_format((float)$n); }
function post(string $k, $d = '') { return isset($_POST[$k]) ? (is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k]) : $d; }
function get(string $k, $d = '') { return $_GET[$k] ?? $d; }
function redirect(string $url) { header("Location: $url"); exit; }
function flash(?string $m = null, string $t = 'ok') {
    if ($m !== null) { $_SESSION['flash'] = [$m, $t]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf() . '">'; }
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(400); exit('Invalid session token. Go back and retry.');
    }
}

function user(): ?array {
    static $u = false;
    if ($u === false) $u = !empty($_SESSION['uid']) ? one('SELECT * FROM users WHERE id=? AND active=1', [$_SESSION['uid']]) : null;
    return $u ?: null;
}
function require_login() { if (!user()) redirect('?p=login'); }
function role(...$r): bool { return user() && in_array(user()['role'], $r, true); }
function require_role(...$r) { require_login(); if (!role(...$r)) { http_response_code(403); exit('Not allowed'); } }

function setting(string $k, $d = '') { $v = val('SELECT v FROM settings WHERE k=?', [$k]); return $v === false ? $d : $v; }

function youtube_id(string $url): ?string {
    return preg_match('~(?:youtu\.be/|v=|embed/|shorts/|live/)([\w-]{11})~', $url, $m) ? $m[1] : null;
}

function course_progress(int $uid, int $cid): int {
    $total = (int)val('SELECT COUNT(*) FROM lessons WHERE course_id=?', [$cid]);
    if (!$total) return 0;
    $done = (int)val('SELECT COUNT(*) FROM progress pr JOIN lessons l ON l.id=pr.lesson_id WHERE pr.user_id=? AND l.course_id=?', [$uid, $cid]);
    return (int)round($done * 100 / $total);
}

function can_manage_course(array $c): bool { return role('admin') || (role('teacher') && (int)$c['teacher_id'] === (int)user()['id']); }

// Runs schema.sql (all CREATE IF NOT EXISTS) once per DB_VERSION bump
function migrate() {
    try { $v = (int)setting('db_version', '1'); } catch (Throwable $e) { $v = 1; }
    if ($v >= DB_VERSION) return;
    foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/schema.sql')))) as $sql) db()->exec($sql);
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='cover'"))
        db()->exec("ALTER TABLE courses ADD cover VARCHAR(120) DEFAULT ''");
    q('REPLACE INTO settings(k,v) VALUES("db_version",?)', [DB_VERSION]);
}

// Saves an uploaded image/PDF proof; returns stored filename or '' / throws on bad file
function save_upload(string $field): string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed, try a smaller file.');
    if ($f['size'] > 5 * 1024 * 1024) throw new RuntimeException('File too large (max 5 MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WEBP or PDF allowed.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0750, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Could not save file.');
    return $name;
}

// Saves an uploaded cover image, shrunk to max 1280px wide JPEG when GD is available
function save_cover(string $field): string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed, try a smaller image.');
    if ($f['size'] > 8 * 1024 * 1024) throw new RuntimeException('Image too large (max 8 MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Cover must be JPG, PNG or WEBP.');
    if (!is_dir(UPLOAD_DIR . '/covers')) mkdir(UPLOAD_DIR . '/covers', 0750, true);
    $name = 'c' . date('Ymd') . '-' . bin2hex(random_bytes(6));
    if (function_exists('imagecreatefromstring') && ($im = @imagecreatefromstring(file_get_contents($f['tmp_name'])))) {
        $w = imagesx($im); $h = imagesy($im);
        if ($w > 1280) { $nh = (int)round($h * 1280 / $w); $r = imagecreatetruecolor(1280, $nh); imagecopyresampled($r, $im, 0, 0, 0, 0, 1280, $nh, $w, $h); $im = $r; }
        imagejpeg($im, UPLOAD_DIR . "/covers/$name.jpg", 82);
        return "$name.jpg";
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . "/covers/$name.$ext")) throw new RuntimeException('Could not save image.');
    return "$name.$ext";
}
function cover_url(array $c): string { return !empty($c['cover']) ? '?p=cover&f=' . rawurlencode($c['cover']) : ''; }
function cover_style(array $c): string { $u = cover_url($c); return $u ? "background-image:url('" . e($u) . "')" : ''; }

function teacher_profile(int $uid): array {
    $p = one('SELECT * FROM teacher_profiles WHERE user_id=?', [$uid]) ?: [];
    $p += ['photo' => '', 'headline' => '', 'bio' => '', 'city' => '', 'years' => 0, 'skills' => '', 'languages' => '', 'achievements' => '', 'linkedin' => '', 'website' => '', 'youtube' => '', 'public' => 1];
    foreach (['education', 'experience', 'certifications'] as $k) $p[$k] = json_decode((string)($p[$k] ?? ''), true) ?: [];
    return $p;
}
function photo_url(string $f): string { return $f ? '?p=cover&f=' . rawurlencode($f) : ''; }
function csv_list(?string $s): array { return array_values(array_filter(array_map('trim', explode(',', (string)$s)))); }
function safe_url(string $u): string { return preg_match('~^https?://~i', $u) ? $u : ($u ? 'https://' . ltrim($u, '/') : ''); }
