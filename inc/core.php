<?php
// LMS ERP core: config, db, auth, helpers
declare(strict_types=1);
define('IS_HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1'); ini_set('session.gc_maxlifetime', '86400');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => IS_HTTPS, 'httponly' => true, 'samesite' => 'Lax']);
    // Security headers (also work where mod_headers is unavailable)
    header('X-Frame-Options: SAMEORIGIN'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'");
    if (IS_HTTPS) header('Strict-Transport-Security: max-age=31536000');
    header_remove('X-Powered-By');
}
session_start();
// Idle timeout: 24h without activity logs out
if (isset($_SESSION['uid'], $_SESSION['seen']) && time() - (int)$_SESSION['seen'] > 86400) { $_SESSION = []; session_regenerate_id(true); }
$_SESSION['seen'] = time();
date_default_timezone_set('Asia/Karachi');

const APP_NAME = 'LMS ERP';
const APP_VERSION = '3.5.0';
const DB_VERSION = 19;
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

function can_manage_course(array $c): bool {
    if (role('admin')) return true;
    if (role('teacher') && (int)($c['teacher_id'] ?? 0) === (int)user()['id']) return true;
    if (role('institute') && my_inst_id() && array_key_exists('institution_id', $c) && (int)$c['institution_id'] === my_inst_id()) return true; // institute manages its own courses
    return false;
}

/* ---------------- Staff roles inside admin: super / admin / accountant / hr ---------------- */
const STAFF_ROLES = ['super' => ['👑', 'Super Admin', 'Everything, including settings, modules, backups and staff'], 'admin' => ['🛠️', 'Admin', 'Day-to-day: courses, people, classes, exams, admissions, shop, website, fees'], 'accountant' => ['💰', 'Accountant', 'Fees, vouchers, payments, expenses, reports, payroll payouts'], 'hr' => ['👥', 'HR Manager', 'Teachers & staff, attendance reports, payroll']];
const STAFF_COMMON_VIEWS = ['home', 'more', 'notifications', 'profile', 'help', 'biometric', 'security', 'receipt'];
const STAFF_VIEWS = [
    'accountant' => ['fees', 'vouchers', 'voucher', 'voucher_gen', 'proofs', 'expenses', 'recurring', 'expense_cats', 'reports', 'payroll', 'slip', 'earnings', 'orders', 'order', 'users', 'user', 'enrollments'],
    'hr' => ['users', 'user', 'user_edit', 'teachers', 'teacher', 'payroll', 'slip', 'batches', 'batch', 'att_report', 'insts', 'inst_edit'],
];
const STAFF_ACTIONS = [
    'accountant' => ['payment_add', 'proof_review', 'payment_delete', 'expense_add', 'expense_delete', 'recurring_save', 'recurring_delete', 'expcat_add', 'expcat_delete', 'plan_save', 'plan_delete', 'voucher_generate', 'installments_create', 'voucher_pay', 'voucher_cancel', 'voucher_edit', 'enroll_discount', 'slip_pay', 'payout_save', 'payout_delete', 'order_status', 'order_bulk'],
    'hr' => ['user_save', 'salary_rule', 'slips_generate', 'slip_update', 'slip_delete', 'parent_add', 'parent_unlink'],
];
const STAFF_COMMON_ACTIONS = ['notif_read_all', 'notif_clear', 'profile_save', 'twofa_enable', 'twofa_disable', 'checklist_hide', 'tprofile_save'];
const SUPER_ONLY_VIEWS = ['settings', 'modules'];
const SUPER_ONLY_ACTIONS = ['settings_save', 'modules_save', 'mall_settings', 'mall_seed', 'mall_clear', 'backup_now', 'demo_seed', 'shop_seed', 'shop_clear', 'blog_seed', 'guide_seed'];
function staff_role(): string {
    $u = user(); if (!$u || $u['role'] !== 'admin') return '';
    static $healed = false; // safety: if no Super Admin exists, the first (owner) admin becomes Super Admin
    if (!$healed && ($u['staff_role'] ?? '') !== 'super') { $healed = true;
        if (!val('SELECT 1 FROM users WHERE role="admin" AND staff_role="super" AND active=1 LIMIT 1')) { $first = (int)val('SELECT MIN(id) FROM users WHERE role="admin" AND active=1'); q('UPDATE users SET staff_role="super" WHERE id=?', [$first]); if ($first === (int)$u['id']) return 'super'; } }
    return ($u['staff_role'] ?? '') ?: 'admin';
}
function is_super(): bool { return staff_role() === 'super'; }
function staff_can_view(string $v): bool {
    $s = staff_role(); if ($s === '' || $s === 'super') return true;
    if ($s === 'admin') return !in_array($v, SUPER_ONLY_VIEWS, true);
    return in_array($v, STAFF_COMMON_VIEWS, true) || in_array($v, STAFF_VIEWS[$s] ?? [], true);
}
function staff_can_act(string $a): bool {
    $s = staff_role(); if ($s === '' || $s === 'super') return true;
    if ($s === 'admin') return !in_array($a, SUPER_ONLY_ACTIONS, true);
    return in_array($a, STAFF_COMMON_ACTIONS, true) || in_array($a, STAFF_ACTIONS[$s] ?? [], true);
}

/* ---------------- EduMall: institutions ---------------- */
const INST_TYPES = ['school' => ['🏫', 'School'], 'college' => ['🎓', 'College'], 'university' => ['🏛️', 'University'], 'academy' => ['📘', 'Academy / Coaching'], 'tuition' => ['👩‍🏫', 'Tuition Centre'], 'institute' => ['💻', 'Skills Institute']];
const ADM_ST = ['new' => ['New', 'warn'], 'contacted' => ['Contacted', ''], 'admitted' => ['Admitted', 'ok'], 'rejected' => ['Rejected', 'err']];
function mall(): bool { return setting('mall_mode', '0') === '1'; }
function my_inst_id(): int { $u = user(); return $u && $u['role'] === 'institute' ? (int)($u['institution_id'] ?? 0) : 0; }
function my_inst(): ?array { $i = my_inst_id(); return $i ? one('SELECT * FROM institutions WHERE id=?', [$i]) : null; }
function inst_url(array $i): string { return 'i/' . $i['slug']; }
function inst_img(array $i, string $f = 'logo'): string { return $i[$f] ? photo_url($i[$f]) : ''; }
function unique_inst_slug(string $name, int $id = 0): string { $b = slugify($name) ?: 'institute'; $s = $b; $n = 2; while (val('SELECT id FROM institutions WHERE slug=? AND id<>?', [$s, $id])) $s = $b . '-' . $n++; return $s; }
function teacher_insts(int $tid, string $st = 'active'): array { return all('SELECT i.* FROM teacher_institutions ti JOIN institutions i ON i.id=ti.institution_id WHERE ti.teacher_id=? AND ti.status=? ORDER BY i.name', [$tid, $st]); }
function inst_teachers(int $iid, string $st = 'active'): array { return all('SELECT u.id,u.name,u.email,u.phone,ti.status,ti.requested_by,(SELECT photo FROM teacher_profiles tp WHERE tp.user_id=u.id) photo,(SELECT headline FROM teacher_profiles tp WHERE tp.user_id=u.id) headline FROM teacher_institutions ti JOIN users u ON u.id=ti.teacher_id WHERE ti.institution_id=? AND ti.status=? ORDER BY u.name', [$iid, $st]); }

// Runs schema.sql (all CREATE IF NOT EXISTS) once per DB_VERSION bump
function migrate() {
    try { $v = (int)setting('db_version', '1'); } catch (Throwable $e) { $v = 1; }
    if ($v >= DB_VERSION) return;
    foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/schema.sql')))) as $sql) db()->exec($sql);
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='cover'"))
        db()->exec("ALTER TABLE courses ADD cover VARCHAR(120) DEFAULT ''");
    foreach (['category_id' => 'INT NULL', 'recurring_id' => 'INT NULL', 'note' => "VARCHAR(255) DEFAULT ''"] as $col => $def)
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='expenses' AND COLUMN_NAME=?", [$col]))
            db()->exec("ALTER TABLE expenses ADD $col $def");
    foreach (['review' => "VARCHAR(10) NOT NULL DEFAULT ''", 'review_note' => "VARCHAR(255) DEFAULT ''"] as $col => $def)
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME=?", [$col]))
            db()->exec("ALTER TABLE posts ADD $col $def");
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='enrollments' AND COLUMN_NAME='fee'"))
        db()->exec("ALTER TABLE enrollments ADD fee DECIMAL(12,2) NULL");
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='enrollments' AND COLUMN_NAME='discount'"))
        db()->exec("ALTER TABLE enrollments ADD discount TINYINT NOT NULL DEFAULT 0");
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_requests' AND COLUMN_NAME='voucher_id'"))
        db()->exec("ALTER TABLE payment_requests ADD voucher_id INT NULL");
    if (!str_contains((string)val("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role'"), 'parent'))
        db()->exec("ALTER TABLE users MODIFY role ENUM('admin','teacher','student','parent') NOT NULL DEFAULT 'student'");
    foreach (['program' => "VARCHAR(20) NOT NULL DEFAULT 'course'", 'level' => "VARCHAR(60) DEFAULT ''"] as $col => $def)
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME=?", [$col]))
            db()->exec("ALTER TABLE courses ADD $col $def");
    if (val("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='user_id'") === 'NO')
        db()->exec("ALTER TABLE orders MODIFY user_id INT NULL");
    if (val("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='user_id'") === 'NO')
        db()->exec("ALTER TABLE payments MODIFY user_id INT NULL");
    if (!str_contains((string)val("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role'"), 'institute'))
        db()->exec("ALTER TABLE users MODIFY role ENUM('admin','teacher','student','parent','institute') NOT NULL DEFAULT 'student'");
    foreach ([['users', 'institution_id', 'INT NULL'], ['courses', 'institution_id', 'INT NULL']] as [$t, $col, $def])
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?", [$t, $col]))
            db()->exec("ALTER TABLE $t ADD $col $def");
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='staff_role'")) {
        db()->exec("ALTER TABLE users ADD staff_role VARCHAR(12) NOT NULL DEFAULT ''");
        db()->exec("UPDATE users SET staff_role='super' WHERE role='admin'"); // existing admins keep full access
    }
    if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='totp_secret'"))
        db()->exec("ALTER TABLE users ADD totp_secret VARCHAR(64) DEFAULT ''");
    foreach ([['products', 'teacher_id', 'INT NULL'], ['products', 'review', "VARCHAR(10) DEFAULT ''"], ['order_items', 'teacher_id', 'INT NULL'], ['order_items', 'teacher_share', 'DECIMAL(10,2) DEFAULT 0']] as [$t, $col, $def])
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?", [$t, $col]))
            db()->exec("ALTER TABLE $t ADD $col $def");
    foreach (['email' => "VARCHAR(160) DEFAULT ''", 'token' => "VARCHAR(32) DEFAULT ''"] as $col => $def)
        if (!val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME=?", [$col]))
            db()->exec("ALTER TABLE orders ADD $col $def");
    if (!val('SELECT COUNT(*) FROM expense_categories'))
        foreach ([['Rent', '🏢'], ['Salaries', '👥'], ['Utilities', '💡'], ['Internet & Phone', '📶'], ['Marketing & Ads', '📣'], ['Stationery', '📚'], ['Maintenance', '🛠️'], ['Software', '💻'], ['Transport', '🚗'], ['Other', '💸']] as [$n, $i])
            q('INSERT INTO expense_categories(name,icon) VALUES(?,?)', [$n, $i]);
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
function cover_url(array $c): string { return !empty($c['cover']) ? '?p=cover&f=' . rawurlencode($c['cover']) : (!empty($c['id']) ? '?p=gen_cover&c=' . (int)$c['id'] : ''); }
function post_cover_url(array $p): string { return !empty($p['cover']) ? photo_url($p['cover']) : '?p=gen_cover&b=' . (int)$p['id']; }
// Designed cover image (gradient + title) for courses/posts without a photo. Cached outside public_html.
function gen_cover(string $title, string $label, int $seed): string {
    $dir = UPLOAD_DIR . '/gen'; if (!is_dir($dir)) mkdir($dir, 0750, true);
    $file = $dir . '/' . md5("v2|$title|$label|$seed") . '.jpg'; if (is_file($file)) return $file;
    $W = 1200; $H = 630; $im = imagecreatetruecolor($W, $H);
    $pal = [[79, 70, 229, 124, 58, 237], [14, 165, 233, 79, 70, 229], [16, 185, 129, 14, 116, 144], [234, 88, 12, 219, 39, 119], [147, 51, 234, 219, 39, 119], [30, 27, 75, 67, 56, 202], [5, 150, 105, 22, 163, 74], [217, 119, 6, 234, 88, 12]];
    [$r1, $g1, $b1, $r2, $g2, $b2] = $pal[$seed % count($pal)];
    for ($y = 0; $y < $H; $y++) { $t = $y / $H; imageline($im, 0, $y, $W, $y, imagecolorallocate($im, (int)($r1 + ($r2 - $r1) * $t), (int)($g1 + ($g2 - $g1) * $t), (int)($b1 + ($b2 - $b1) * $t))); }
    imagealphablending($im, true);
    $wc = imagecolorallocatealpha($im, 255, 255, 255, 112);
    imagefilledellipse($im, $W - 120, 90, 520, 520, $wc); imagefilledellipse($im, 140, $H + 40, 420, 420, $wc); imagefilledellipse($im, $W - 340, $H - 40, 180, 180, $wc);
    $white = imagecolorallocate($im, 255, 255, 255); $soft = imagecolorallocatealpha($im, 255, 255, 255, 40);
    $fb = dirname(__DIR__) . '/assets/fonts/DejaVuSans-Bold.ttf'; $fr = dirname(__DIR__) . '/assets/fonts/DejaVuSans.ttf';
    if (function_exists('imagettftext') && is_file($fb)) {
        $size = mb_strlen($title) > 60 ? 44 : (mb_strlen($title) > 34 ? 54 : 64); $lines = []; $line = '';
        foreach (preg_split('/\s+/', trim($title)) as $w) { $try = trim("$line $w"); $bb = imagettfbbox($size, 0, $fb, $try); if ($bb[2] - $bb[0] > $W - 160 && $line !== '') { $lines[] = $line; $line = $w; } else $line = $try; }
        $lines[] = $line; $lines = array_slice($lines, 0, 4); $lh = (int)($size * 1.3);
        $y0 = (int)(($H - count($lines) * $lh) / 2) + $size + 10;
        imagettftext($im, 22, 0, 80, $y0 - $size - 40, $soft, $fr, mb_strtoupper($label));
        foreach ($lines as $i => $l) imagettftext($im, $size, 0, 80, $y0 + $i * $lh, $white, $fb, $l);
        imagettftext($im, 20, 0, 80, $H - 50, $soft, $fb, setting('institute', APP_NAME));
    } else imagestring($im, 5, 60, (int)($H / 2), $title, $white);
    imagejpeg($im, $file, 86); imagedestroy($im); return $file;
}
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

// Post every due recurring expense (catches up missed periods). Called on admin page loads.
function run_recurring(): int {
    $n = 0; $today = date('Y-m-d');
    foreach (all('SELECT * FROM recurring_expenses WHERE active=1 AND next_date<=?', [$today]) as $r) {
        $d = $r['next_date']; $guard = 0;
        while ($d <= $today && $guard++ < 60) {
            if (!val('SELECT id FROM expenses WHERE recurring_id=? AND spent_on=?', [$r['id'], $d])) {
                q('INSERT INTO expenses(title,amount,spent_on,category_id,recurring_id,note) VALUES(?,?,?,?,?,?)', [$r['title'], $r['amount'], $d, $r['category_id'], $r['id'], 'Auto (recurring)']);
                $n++;
            }
            $d = next_due($d, $r['frequency']);
        }
        q('UPDATE recurring_expenses SET next_date=? WHERE id=?', [$d, $r['id']]);
    }
    return $n;
}
function next_due(string $d, string $f): string {
    $t = new DateTime($d);
    if ($f === 'weekly') $t->modify('+1 week');
    elseif ($f === 'yearly') $t->modify('+1 year');
    else { $day = (int)$t->format('d'); $t->modify('first day of next month'); $t->setDate((int)$t->format('Y'), (int)$t->format('m'), min($day, (int)$t->format('t'))); }
    return $t->format('Y-m-d');
}

/* ---------------- Public website helpers (blog, legal pages, SEO, AdSense) ---------------- */
define('BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
function abs_url(string $path = ''): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE . ltrim($path, '/');
}
function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return $s !== '' ? substr($s, 0, 180) : 'post-' . time();
}
function post_url(array $p): string { return 'blog/' . $p['slug']; }
function read_mins(string $t): int { return max(1, (int)ceil(str_word_count(strip_tags($t)) / 200)); }

// Tiny safe markdown: ## / ### headings, - and 1. lists, > quotes, **bold**, *italic*, [text](https://link), paragraphs
function md(string $src): string {
    $inline = function (string $t): string {
        $t = e($t);
        $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
        $t = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $t);
        $t = preg_replace('~\[([^\]]+)\]\((https?://[^\s)]+)\)~', '<a href="$2" target="_blank" rel="noopener nofollow">$1</a>', $t);
        return $t;
    };
    $out = '';
    foreach (preg_split("/\R{2,}/", trim(str_replace("\r", '', $src))) as $b) {
        $lines = explode("\n", trim($b));
        if (preg_match('/^(#{2,3})\s+(.+)$/', $lines[0], $m) && count($lines) === 1) { $h = strlen($m[1]); $out .= "<h$h>" . $inline($m[2]) . "</h$h>"; continue; }
        if (preg_match('/^[-*]\s+/', $lines[0])) { $out .= '<ul>' . implode('', array_map(fn($l) => '<li>' . $inline(preg_replace('/^[-*]\s+/', '', $l)) . '</li>', $lines)) . '</ul>'; continue; }
        if (preg_match('/^\d+[.)]\s+/', $lines[0])) { $out .= '<ol>' . implode('', array_map(fn($l) => '<li>' . $inline(preg_replace('/^\d+[.)]\s+/', '', $l)) . '</li>', $lines)) . '</ol>'; continue; }
        if (str_starts_with($lines[0], '>')) { $isCode = (bool)array_filter($lines, fn($l) => preg_match('/[=():]|print|^>\s{2,}/', $l));
            $rows = array_map(fn($l) => preg_replace_callback('/^ +/', fn($m) => str_repeat('&nbsp;', strlen($m[0])), $inline(preg_replace('/^>\s?/', '', $l))), $lines);
            $out .= '<blockquote' . ($isCode && count($lines) > 0 && preg_match('/[=(]/', implode('', $lines)) ? ' class="code"' : '') . '>' . implode('<br>', $rows) . '</blockquote>'; continue; }
        $out .= '<p>' . implode('<br>', array_map($inline, $lines)) . '</p>';
    }
    return $out;
}

function adsense_client(): string { $c = trim(setting('adsense_client')); return preg_match('/^ca-pub-\d{10,20}$/', $c) ? $c : ''; }
function ads_head(): string {
    $c = adsense_client();
    return $c ? '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . $c . '" crossorigin="anonymous"></script>' . "\n<meta name=\"google-adsense-account\" content=\"$c\">" : '';
}

const LEGAL_PAGES = ['about' => 'About Us', 'privacy-policy' => 'Privacy Policy', 'terms' => 'Terms & Conditions', 'disclaimer' => 'Disclaimer', 'contact' => 'Contact Us'];

function legal_default(string $slug): string {
    $i = setting('institute', APP_NAME); $email = setting('site_email') ?: 'our contact email'; $site = abs_url(); $d = date('F j, Y');
    switch ($slug) {
    case 'about': return "## Who we are\n\n$i is an online learning platform that helps students in Pakistan and abroad learn practical skills and prepare for exams from their phones.\n\nWe offer structured courses with video lessons, written notes, quizzes and progress tracking, taught by qualified and experienced teachers.\n\n## Our mission\n\nTo make quality education affordable and accessible to every student, whether they live in a big city or a small town.\n\n## What we offer\n\n- Exam preparation: entry tests, board exams, IELTS and more\n- Skills courses: IT, programming, design, digital marketing and accounting\n- Language and Quran courses\n- Free study notes and guides on our blog\n\n## Our teachers\n\nEvery teacher on $i has a public profile showing their qualifications and experience, so students and parents can choose with confidence.\n\n## Contact\n\nHave a question? Visit our Contact page or email us at $email.";
    case 'privacy-policy': return "*Last updated: $d*\n\nThis Privacy Policy explains how $i (\"we\", \"us\") collects, uses and protects information when you visit $site (the \"Website\") or use our learning app.\n\n## Information we collect\n\n- **Account information:** name, email address, phone number and password (stored encrypted) when you register.\n- **Learning data:** courses you enrol in, lessons completed and quiz results.\n- **Payment information:** payment method, amount, transaction reference and any payment screenshot you upload. We do not store card numbers.\n- **Contact form messages:** name, email, phone and message you send us.\n- **Technical data:** IP address, browser type, device information and pages visited, collected through server logs and cookies.\n\n## How we use information\n\n- To create and manage your account and give access to your courses\n- To verify payments and issue receipts\n- To respond to your messages and provide support\n- To improve our courses and Website\n- To show advertising on public pages (see below)\n\n## Cookies\n\nWe use cookies to keep you signed in and to remember your preferences. You can disable cookies in your browser settings, but some features may not work.\n\n## Advertising and Google AdSense\n\nWe may show advertisements served by Google AdSense on public pages of this Website.\n\n- Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this and other websites.\n- Google's use of advertising cookies enables it and its partners to serve ads to you based on your visits to this and/or other sites on the Internet.\n- You may opt out of personalised advertising by visiting [Google Ads Settings](https://www.google.com/settings/ads). You can also opt out of some third-party vendors' cookies at [www.aboutads.info](https://www.aboutads.info/choices/).\n- For more information, see [How Google uses information from sites that use its services](https://policies.google.com/technologies/partner-sites).\n\n## Sharing of information\n\nWe do not sell your personal information. We share it only with service providers who help us run the Website (such as hosting), when required by law, or to protect our rights.\n\n## Data security\n\nWe use reasonable technical measures such as HTTPS and encrypted passwords to protect your data. No method of transmission over the Internet is 100% secure.\n\n## Children's information\n\nOur Website is intended for students of all ages. Children under 13 should use the Website with the permission and supervision of a parent or guardian. We do not knowingly collect personal information from children under 13 without parental consent; if you believe we have, please contact us and we will delete it.\n\n## Your rights\n\nYou may ask us to access, correct or delete your personal information by contacting us at $email.\n\n## Changes to this policy\n\nWe may update this Privacy Policy from time to time. Changes are posted on this page with a new \"Last updated\" date.\n\n## Contact us\n\nIf you have questions about this Privacy Policy, contact us at $email or through our Contact page.";
    case 'terms': return "*Last updated: $d*\n\nBy accessing $site or using the $i app, you agree to these Terms & Conditions. If you do not agree, please do not use the Website.\n\n## Accounts\n\n- You must provide accurate information when creating an account.\n- You are responsible for keeping your password secure and for all activity under your account.\n- One account is for one student only; sharing accounts is not allowed.\n\n## Courses and access\n\n- Free courses can be accessed after registration.\n- Paid courses are unlocked after your fee payment is verified.\n- Course content (videos, notes, quizzes) is for your personal learning only. Copying, recording, reselling or sharing it is prohibited.\n\n## Fees and refunds\n\n- Fees are shown in Pakistani Rupees (PKR) on each course page.\n- Refund requests are reviewed case by case within 7 days of payment if you have not accessed most of the course. Contact us to request a refund.\n\n## Acceptable use\n\nYou agree not to misuse the Website, attempt unauthorised access, upload harmful content, or harass teachers or other students.\n\n## Intellectual property\n\nAll content on this Website, including text, graphics, logos and course material, belongs to $i or its teachers and is protected by copyright.\n\n## Third-party links and ads\n\nThe Website may contain links to third-party websites and advertisements. We are not responsible for the content or practices of those sites.\n\n## Limitation of liability\n\nThe Website and courses are provided \"as is\". We do not guarantee specific exam results or job outcomes. We are not liable for any indirect loss arising from use of the Website.\n\n## Changes\n\nWe may change these terms at any time. Continued use of the Website after changes means you accept the updated terms.\n\n## Contact\n\nQuestions about these terms? Email us at $email.";
    case 'disclaimer': return "*Last updated: $d*\n\nThe information on $site is published in good faith for general educational purposes only.\n\n## Educational content\n\nOur blog posts, notes and courses are designed to help students learn. While we try to keep information accurate and up to date, we make no warranties about its completeness or accuracy. Always check official sources (boards, universities, testing bodies) for exam dates, syllabus and rules.\n\n## No guarantee of results\n\nResults depend on each student's effort. We do not guarantee admission, exam scores, employment or income from any course.\n\n## External links\n\nWe may link to other websites. We have no control over their content and are not responsible for it.\n\n## Advertisements\n\nThis Website may display advertisements from Google AdSense and other partners. We do not endorse the products or services advertised.\n\n## Consent\n\nBy using our Website, you consent to this disclaimer.\n\n## Contact\n\nFor any questions, email us at $email.";
    case 'contact': return "We'd love to hear from you. Whether you have a question about a course, fees, admissions or anything else, our team is ready to help.\n\nFill in the form and we will get back to you as soon as possible, usually within 24 hours.";
    }
    return '';
}
function legal_content(string $slug): string { $c = trim(setting('page_' . $slug)); return $c !== '' ? $c : legal_default($slug); }

// Fee a given user pays for a course (teachers get the configured discount)
function course_fee_for(array $c, ?array $u = null): float {
    $u = $u ?? user(); $fee = (float)$c['fee'];
    if ($u && $u['role'] === 'teacher') $fee = round($fee * (100 - max(0, min(100, (int)setting('teacher_discount', '0')))) / 100);
    return $fee;
}
function can_enroll(array $c): bool { return role('student') || (role('teacher') && (int)$c['teacher_id'] !== (int)user()['id']); }

/* ---------------- Batches & attendance ---------------- */
const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const ATT = ['P' => ['Present', 'ok'], 'A' => ['Absent', 'err'], 'L' => ['Late', 'warn'], 'E' => ['Leave', '']];
function can_manage_batch(array $b): bool { return role('admin') || (role('teacher') && ((int)$b['teacher_id'] === (int)user()['id'] || (int)val('SELECT teacher_id FROM courses WHERE id=?', [$b['course_id']]) === (int)user()['id'])); }
function batch_time(array $b): string { return $b['start_time'] ? date('g:i a', strtotime($b['start_time'])) . ($b['end_time'] ? ' – ' . date('g:i a', strtotime($b['end_time'])) : '') : ''; }
function att_percent(int $uid, ?int $bid = null): ?int {
    $r = one('SELECT COUNT(*) t, SUM(status IN ("P","L")) p FROM attendance WHERE user_id=?' . ($bid ? ' AND batch_id=' . (int)$bid : ''), [$uid]);
    return $r['t'] ? (int)round($r['p'] * 100 / $r['t']) : null;
}
function wa_num(string $phone): string { $w = preg_replace('/\D/', '', $phone); return str_starts_with($w, '0') ? '92' . substr($w, 1) : $w; }

/* ---------------- Fee vouchers ---------------- */
function voucher_overdue(array $v): bool { return $v['status'] === 'unpaid' && $v['due_date'] < date('Y-m-d'); }
function voucher_total(array $v): float { return max(0, (float)$v['amount'] - (float)$v['discount'] + (voucher_overdue($v) ? (float)$v['late_fee'] : 0)); }
function voucher_no(array $v): string { return 'FV-' . str_pad((string)$v['id'], 6, '0', STR_PAD_LEFT); }
function create_voucher(int $uid, ?int $cid, string $title, float $amount, string $due, float $late = 0, ?int $plan = null, string $period = '', ?float $disc = null): int {
    if ($disc === null) { $pct = $cid ? (int)val('SELECT discount FROM enrollments WHERE user_id=? AND course_id=?', [$uid, $cid]) : 0; $disc = round($amount * $pct / 100); }
    q('INSERT INTO fee_vouchers(user_id,course_id,title,amount,discount,late_fee,due_date,plan_id,period) VALUES(?,?,?,?,?,?,?,?,?)', [$uid, $cid, $title, $amount, $disc, $late, $due, $plan, $period]);
    $vid = (int)db()->lastInsertId();
    notify($uid, 'New fee voucher: ' . $title, money(max(0, $amount - $disc)) . ' due ' . date('d M Y', strtotime($due)), "?p=voucher&id=$vid", '📄', true);
    return $vid;
}
// Monthly fee plans: create this month's vouchers for every active student of the course
function run_fee_plans(): int {
    $n = 0; $period = date('Y-m'); $day = (int)date('j');
    foreach (all('SELECT p.*,c.title FROM fee_plans p JOIN courses c ON c.id=p.course_id WHERE p.active=1 AND p.last_period<>? AND p.generate_day<=?', [$period, $day]) as $p) {
        $due = $period . '-' . str_pad((string)min((int)$p['due_day'], (int)date('t')), 2, '0', STR_PAD_LEFT);
        foreach (all('SELECT user_id FROM enrollments WHERE course_id=? AND status IN ("active","completed")', [$p['course_id']]) as $e) {
            if (val('SELECT id FROM fee_vouchers WHERE user_id=? AND plan_id=? AND period=?', [$e['user_id'], $p['id'], $period])) continue;
            create_voucher((int)$e['user_id'], (int)$p['course_id'], $p['title'] . ' — ' . date('F Y'), (float)$p['amount'], $due, (float)$p['late_fee'], (int)$p['id'], $period); $n++;
        }
        q('UPDATE fee_plans SET last_period=? WHERE id=?', [$period, $p['id']]);
    }
    return $n;
}
function pay_voucher(int $vid, float $amount, string $method, string $date, string $note = ''): int {
    $v = one('SELECT * FROM fee_vouchers WHERE id=?', [$vid]);
    if (!$v || $v['status'] !== 'unpaid') return 0;
    q('INSERT INTO payments(user_id,course_id,amount,method,note,paid_on,created_by) VALUES(?,?,?,?,?,?,?)', [$v['user_id'], $v['course_id'], $amount, $method, trim(voucher_no($v) . ' ' . $v['title'] . ($note ? " · $note" : '')), $date, user()['id'] ?? null]);
    $pid = (int)db()->lastInsertId();
    q('UPDATE fee_vouchers SET status="paid",paid_amount=?,paid_on=?,payment_id=? WHERE id=?', [$amount, $date, $pid, $vid]);
    if ($v['course_id']) q('UPDATE enrollments SET status="active" WHERE user_id=? AND course_id=? AND status="pending"', [$v['user_id'], $v['course_id']]);
    return $pid;
}

/* ---------------- Certificates ---------------- */
// Learner has real access to a course (active/completed enrollment)
function is_enrolled(int $uid, int $cid): bool { return (bool)val('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? AND status IN ("active","completed")', [$uid, $cid]); }
// Only http(s) links are allowed for user-supplied URLs (blocks javascript: etc.)
function safe_link(?string $u): string { $u = trim((string)$u); return preg_match('~^https?://~i', $u) ? $u : ''; }
function cert_eligibility(int $uid, int $cid): array {
    if (!is_enrolled($uid, $cid)) return [false, 'Enroll in the course first'];
    if (course_progress($uid, $cid) < 100) return [false, 'Complete all lessons first'];
    foreach (all('SELECT id,title,pass_percent FROM quizzes WHERE course_id=?', [$cid]) as $q) {
        $best = val('SELECT MAX(ROUND(score*100/NULLIF(total,0))) FROM attempts WHERE quiz_id=? AND user_id=?', [$q['id'], $uid]);
        if ($best === null || $best === false || (int)$best < (int)$q['pass_percent']) return [false, 'Pass the quiz: ' . $q['title']];
    }
    return [true, ''];
}
function cert_grade(int $uid, int $cid): string {
    $avg = val('SELECT AVG(b) FROM (SELECT MAX(score*100/NULLIF(total,0)) b FROM attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE q.course_id=? AND a.user_id=? GROUP BY a.quiz_id) x', [$cid, $uid]);
    if ($avg === null || $avg === false) return '';
    return $avg >= 85 ? 'Distinction' : ($avg >= 70 ? 'Merit' : 'Pass');
}
function issue_certificate(int $uid, int $cid, ?string $grade = null): string {
    if ($code = val('SELECT code FROM certificates WHERE user_id=? AND course_id=?', [$uid, $cid])) { if (role('admin')) q('UPDATE certificates SET revoked=0 WHERE user_id=? AND course_id=?', [$uid, $cid]); return $code; }
    do { $code = 'C' . strtoupper(substr(str_replace(['0', 'O', '1', 'I'], '', bin2hex(random_bytes(8))), 0, 4) . '-' . substr(strtoupper(bin2hex(random_bytes(3))), 0, 4)); } while (val('SELECT id FROM certificates WHERE code=?', [$code]));
    q('INSERT INTO certificates(user_id,course_id,code,grade,issued_by) VALUES(?,?,?,?,?)', [$uid, $cid, $code, $grade ?? cert_grade($uid, $cid), user()['id'] ?? null]);
    q('UPDATE enrollments SET status="completed" WHERE user_id=? AND course_id=?', [$uid, $cid]);
    return $code;
}

/* ---------------- Notifications ---------------- */
function notify($uids, string $title, string $body = '', string $link = '', string $icon = '🔔', bool $parents = false): void {
    $uids = array_unique(array_filter(array_map('intval', (array)$uids)));
    if ($parents && $uids) $uids = array_unique(array_merge($uids, array_map('intval', array_column(all('SELECT parent_id FROM parent_links WHERE student_id IN (' . implode(',', $uids) . ')'), 'parent_id'))));
    foreach ($uids as $u) q('INSERT INTO notifications(user_id,title,body,link,icon) VALUES(?,?,?,?,?)', [$u, mb_substr($title, 0, 200), mb_substr($body, 0, 500), $link, $icon]);
}
function course_student_ids(int $cid, ?int $bid = null): array {
    return array_map('intval', array_column($bid ? all('SELECT user_id FROM batch_students WHERE batch_id=?', [$bid]) : all('SELECT user_id FROM enrollments WHERE course_id=? AND status<>"pending"', [$cid]), 'user_id'));
}
function unread_count(): int { return user() ? (int)val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [user()['id']]) : 0; }
function my_children(): array { return user() ? all('SELECT u.*,pl.relation FROM parent_links pl JOIN users u ON u.id=pl.student_id WHERE pl.parent_id=? ORDER BY u.name', [user()['id']]) : []; }
// Parent adds a child: creates a student account (login optional) and links it
function add_child(int $pid, string $name, string $email = '', string $pw = ''): int|string {
    $name = trim($name); if ($name === '') return "Enter the child's name";
    $email = strtolower(trim($email));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Child email is not valid';
    if ($email !== '' && val('SELECT id FROM users WHERE email=?', [$email])) return 'That child email is already registered';
    if ($email !== '' && strlen($pw) < 6) return "Child password must be 6+ characters";
    if ($email === '') { $email = 'child' . bin2hex(random_bytes(5)) . '@student.local'; $pw = bin2hex(random_bytes(8)); }
    q('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,"student")', [mb_substr($name, 0, 120), $email, val('SELECT phone FROM users WHERE id=?', [$pid]) ?: '', password_hash($pw, PASSWORD_DEFAULT)]);
    $sid = (int)db()->lastInsertId();
    q('INSERT IGNORE INTO parent_links(parent_id,student_id,relation) VALUES(?,?,?)', [$pid, $sid, 'Parent']);
    return $sid;
}
function is_parent_of(int $sid): bool { return (bool)val('SELECT 1 FROM parent_links WHERE parent_id=? AND student_id=?', [user()['id'] ?? 0, $sid]); }

/* ---------------- Exams & results ---------------- */
function exam_grade(float $pct): string { return $pct >= 80 ? 'A+' : ($pct >= 70 ? 'A' : ($pct >= 60 ? 'B' : ($pct >= 50 ? 'C' : ($pct >= 40 ? 'D' : 'F')))); }
// Returns [user_id => ['total','max','pct','grade','pass','rank','marks'=>[paper_id=>row]]]
function exam_results(int $eid): array {
    $papers = all('SELECT * FROM exam_papers WHERE exam_id=? ORDER BY sort,id', [$eid]);
    $ex = one('SELECT * FROM exams WHERE id=?', [$eid]);
    $uids = course_student_ids((int)$ex['course_id'], $ex['batch_id'] ? (int)$ex['batch_id'] : null);
    $marks = [];
    if ($papers) foreach (all('SELECT * FROM exam_marks WHERE paper_id IN (' . implode(',', array_column($papers, 'id')) . ')') as $m) $marks[$m['user_id']][$m['paper_id']] = $m;
    $out = []; $max = array_sum(array_column($papers, 'max_marks'));
    foreach ($uids as $u) {
        $t = 0; $pass = true; $any = false;
        foreach ($papers as $p) { $m = $marks[$u][$p['id']] ?? null; if ($m && !$m['absent'] && $m['marks'] !== null) { $t += (float)$m['marks']; $any = true; if ((float)$m['marks'] < $p['pass_marks']) $pass = false; } else $pass = false; }
        $pct = $max ? $t * 100 / $max : 0;
        $out[$u] = ['total' => $t, 'max' => $max, 'pct' => round($pct, 1), 'grade' => $any ? ($pass ? exam_grade($pct) : 'F') : '–', 'pass' => $pass && $any, 'any' => $any, 'marks' => $marks[$u] ?? []];
    }
    $sorted = $out; uasort($sorted, fn($a, $b) => $b['total'] <=> $a['total']);
    $r = 0; $prev = null; $i = 0;
    foreach ($sorted as $u => $x) { $i++; if ($x['total'] !== $prev) { $r = $i; $prev = $x['total']; } $out[$u]['rank'] = $x['any'] ? $r : null; }
    return $out;
}

/* ---------------- Payroll ---------------- */
function salary_calc(int $uid, string $period): array {
    $r = one('SELECT * FROM salary_rules WHERE user_id=?', [$uid]);
    if (!$r) return [0, 'No salary rule'];
    if ($r['type'] === 'per_student') {
        $n = (int)val('SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE c.teacher_id=? AND e.status IN ("active","completed")', [$uid]);
        return [round($n * (float)$r['amount']), "$n students × " . money($r['amount'])];
    }
    if ($r['type'] === 'percent') {
        $f = (float)val('SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN courses c ON c.id=p.course_id WHERE c.teacher_id=? AND DATE_FORMAT(p.paid_on,"%Y-%m")=?', [$uid, $period]);
        return [round($f * (float)$r['amount'] / 100), (float)$r['amount'] . '% of ' . money($f) . ' fees collected'];
    }
    return [(float)$r['amount'], 'Fixed monthly salary'];
}
function child_id(): int { // parent: selected child (or first); others: self
    $u = user(); if (!$u) return 0;
    if ($u['role'] !== 'parent') return (int)$u['id'];
    $c = (int)get('child'); if ($c && is_parent_of($c)) return $c;
    return (int)(val('SELECT student_id FROM parent_links WHERE parent_id=? ORDER BY student_id LIMIT 1', [$u['id']]) ?: 0);
}

/* ---------------- Programs ---------------- */
const PROGRAMS = ['course' => ['Course', '📘'], 'homeschool' => ['Homeschooling', '🏠'], 'trainer' => ['Train the Trainer', '🎤']];
function program_label(?string $p): string { return (PROGRAMS[$p ?: 'course'] ?? PROGRAMS['course'])[1] . ' ' . (PROGRAMS[$p ?: 'course'] ?? PROGRAMS['course'])[0]; }
// Parent enrolls a child: enrollment (pending if paid) + automatic fee voucher
function enroll_child(int $childId, array $c): string {
    $fee = (float)$c['fee'];
    $status = ($fee > 0 && setting('paid_needs_approval', '1') === '1') ? 'pending' : 'active';
    if (val('SELECT id FROM enrollments WHERE user_id=? AND course_id=?', [$childId, $c['id']])) return 'already';
    q('INSERT INTO enrollments(user_id,course_id,status,fee) VALUES(?,?,?,?)', [$childId, $c['id'], $status, $fee]);
    if ($fee > 0) create_voucher($childId, (int)$c['id'], 'Enrollment fee — ' . $c['title'], $fee, date('Y-m-d', strtotime('+3 days')), 0, null, '', 0);
    return $status;
}

/* ---------------- Shop ---------------- */
const ORDER_ST = ['pending' => ['Pending', 'warn'], 'paid' => ['Paid', 'ok'], 'processing' => ['Processing', ''], 'shipped' => ['Shipped', ''], 'delivered' => ['Delivered', 'ok'], 'cancelled' => ['Cancelled', 'err']];
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_count(): int { return array_sum(cart()); }
function cart_items(): array {
    $c = cart(); if (!$c) return [];
    $rows = all('SELECT * FROM products WHERE active=1 AND id IN (' . implode(',', array_map('intval', array_keys($c))) . ')');
    foreach ($rows as &$r) { $r['qty'] = $r['type'] === 'digital' ? 1 : (int)$c[$r['id']]; $r['line'] = $r['qty'] * (float)$r['price']; }
    return $rows;
}
function shipping_for(float $sub, bool $physical): float {
    if (!$physical) return 0;
    $free = (float)setting('shop_free_over', '0');
    return ($free > 0 && $sub >= $free) ? 0 : (float)setting('shop_shipping', '250');
}
function order_no(array $o): string { return 'ORD-' . str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT); }
function product_img(array $p): string { return $p['image'] ? photo_url($p['image']) : ''; }

function product_url(array $p): string { return 'shop/' . $p['id'] . '-' . slugify($p['title']); }
function order_track_url(array $o): string { return 'track?o=' . $o['id'] . '&t=' . $o['token']; }
// Create an order from the session cart (guest or logged-in). Returns order id or error string.
function place_order(array $f): int|string {
    $items = cart_items(); if (!$items) return 'Your cart is empty';
    foreach ($items as $it) if ($it['stock'] !== null && $it['qty'] > (int)$it['stock']) return $it['title'] . ': only ' . (int)$it['stock'] . ' left';
    $phys = (bool)array_filter($items, fn($i) => $i['type'] === 'physical');
    if (trim($f['name'] ?? '') === '' || trim($f['phone'] ?? '') === '') return 'Please enter your name and phone number';
    if ($phys && (trim($f['address'] ?? '') === '' || trim($f['city'] ?? '') === '')) return 'Please enter your delivery address and city';
    if (!$phys && trim($f['email'] ?? '') === '' && !user()) return 'Please enter your email for the download link';
    $pm = in_array($f['pay_method'] ?? '', ['COD', 'JazzCash', 'EasyPaisa', 'Bank'], true) ? $f['pay_method'] : 'COD';
    if ($pm === 'COD' && (!$phys || setting('shop_cod', '1') !== '1')) $pm = 'JazzCash';
    $sub = array_sum(array_column($items, 'line')); $ship = shipping_for($sub, $phys);
    $u = user(); $tok = bin2hex(random_bytes(12));
    $sid = $u && $u['role'] === 'student' ? (int)$u['id'] : ($u && $u['role'] === 'parent' && !empty($f['student_id']) && is_parent_of((int)$f['student_id']) ? (int)$f['student_id'] : null);
    q('INSERT INTO orders(user_id,student_id,subtotal,shipping,total,name,phone,email,address,city,pay_method,proof,txn_ref,note,token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$u['id'] ?? null, $sid, $sub, $ship, $sub + $ship, mb_substr(trim($f['name']), 0, 120), mb_substr(trim($f['phone']), 0, 40), mb_substr(trim($f['email'] ?? ''), 0, 160), mb_substr(trim($f['address'] ?? ''), 0, 300), mb_substr(trim($f['city'] ?? ''), 0, 80), $pm, $f['proof'] ?? '', mb_substr($f['txn_ref'] ?? '', 0, 100), mb_substr($f['note'] ?? '', 0, 300), $tok]);
    $oid = (int)db()->lastInsertId();
    foreach ($items as $it) {
        $tid = $it['teacher_id'] ?? null; q('INSERT INTO order_items(order_id,product_id,title,price,qty,type,teacher_id,teacher_share) VALUES(?,?,?,?,?,?,?,?)', [$oid, $it['id'], $it['title'], $it['price'], $it['qty'], $it['type'], $tid, $tid ? round($it['price'] * $it['qty'] * teacher_pct() / 100, 2) : 0]);
        if ($it['stock'] !== null) q('UPDATE products SET stock=GREATEST(0,stock-?) WHERE id=?', [$it['qty'], $it['id']]);
    }
    unset($_SESSION['cart']);
    $_SESSION['my_orders'][$oid] = $tok;
    notify(array_map('intval', array_column(all('SELECT id FROM users WHERE role="admin" AND active=1'), 'id')), 'New shop order ' . order_no(['id' => $oid]), trim($f['name']) . ' · ' . money($sub + $ship) . ' · ' . $pm . ($u ? '' : ' · guest'), "?p=order&id=$oid", '🛒');
    return $oid;
}
// Change order status: records payment, restocks on cancel, auto-delivers digital-only orders, notifies
function set_order_status(array $o, string $st, ?string $tracking, ?string $note): void {
    $id = (int)$o['id']; $me = user();
    $digital = !val('SELECT COUNT(*) FROM order_items WHERE order_id=? AND type="physical"', [$id]);
    if ($digital && in_array($st, ['paid', 'processing'], true)) $st = 'delivered';
    $paidNow = in_array($st, ['paid', 'processing', 'shipped', 'delivered'], true) && !$o['payment_id'] && ($o['pay_method'] !== 'COD' || $st === 'delivered' || $st === 'paid');
    if ($paidNow) { q('INSERT INTO payments(user_id,course_id,amount,method,note,paid_on,created_by) VALUES(?,?,?,?,?,?,?)', [$o['student_id'] ?: $o['user_id'], null, $o['total'], $o['pay_method'] === 'COD' ? 'Cash' : $o['pay_method'], 'Shop ' . order_no($o) . ($o['user_id'] ? '' : ' · ' . $o['name']), date('Y-m-d'), $me['id'] ?? null]); q('UPDATE orders SET payment_id=? WHERE id=?', [(int)db()->lastInsertId(), $id]); }
    if ($st === 'cancelled' && $o['status'] !== 'cancelled') foreach (all('SELECT product_id,qty FROM order_items WHERE order_id=?', [$id]) as $it) q('UPDATE products SET stock=stock+? WHERE id=? AND stock IS NOT NULL', [$it['qty'], $it['product_id']]);
    q('UPDATE orders SET status=?,tracking=?,admin_note=? WHERE id=?', [$st, $tracking, $note, $id]);
    if ($st !== $o['status'] && $o['user_id']) notify((int)$o['user_id'], order_no($o) . ': ' . ORDER_ST[$st][0], $tracking ? 'Tracking: ' . $tracking : ($note ?: ''), "?p=order&id=$id", '📦');
}
// Teacher marketplace: teacher's % of each sale; earnings count once an order is delivered
/* ---------------- Modules (turn parts of the app on/off per site) ---------------- */
const MODULES = [
    'lms' => ['📚', 'LMS — online courses', 'Courses, video lessons, quizzes, certificates, enrollments'],
    'school' => ['🏫', 'School management', 'Batches, attendance, assignments, exams & result cards, fee vouchers, payroll'],
    'shop' => ['🛒', 'E-commerce shop', 'Public store, guest checkout, orders, order tracking, teacher marketplace'],
    'website' => ['🌐', 'Website & blog', 'Public website, blog, legal pages, contact form'],
    'accounts' => ['💰', 'Accounts', 'Expenses, recurring expenses, income & expense reports'],
];
const MODULE_VIEWS = [
    'lms' => ['courses', 'course', 'course_edit', 'lesson', 'quiz', 'certificates', 'cert', 'enrollments', 'my'],
    'school' => ['batches', 'batch', 'batch_edit', 'attendance', 'att_report', 'attendance_me', 'assignments', 'assignment', 'assign_edit', 'exams', 'exam', 'exam_edit', 'result', 'payroll', 'slip', 'vouchers', 'voucher', 'voucher_gen'],
    'shop' => ['shop', 'product', 'cart', 'orders', 'order', 'products', 'product_edit', 'earnings', 'store', 'store_product', 'store_cart', 'store_checkout', 'track'],
    'website' => ['blog', 'post', 'page', 'posts', 'post_edit', 'pages_edit', 'messages', 'site'],
    'accounts' => ['expenses', 'recurring', 'expense_cats', 'reports'],
];
function mod(string $m): bool { return setting('mod_' . $m, '1') === '1'; }
function view_module(string $view): ?string { foreach (MODULE_VIEWS as $m => $vs) if (in_array($view, $vs, true)) return $m; return null; }
function view_on(string $view): bool { $m = view_module($view); return $m === null || mod($m); }

/* ---------------- Login protection ---------------- */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }
function login_blocked(string $email): bool {
    q('DELETE FROM login_attempts WHERE at < NOW() - INTERVAL 1 DAY');
    return (int)val('SELECT COUNT(*) FROM login_attempts WHERE email=? AND at > NOW() - INTERVAL 15 MINUTE', [strtolower($email)]) >= 5
        || (int)val('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND at > NOW() - INTERVAL 15 MINUTE', [client_ip()]) >= 20;
}
function login_failed(string $email): void { q('INSERT INTO login_attempts(email,ip) VALUES(?,?)', [strtolower(mb_substr($email, 0, 160)), client_ip()]); }
function login_ok(array $u, string $via = 'password'): void { q('DELETE FROM login_attempts WHERE email=?', [strtolower($u['email'])]); session_regenerate_id(true); unset($_SESSION['csrf'], $_SESSION['2fa_uid']); $_SESSION['uid'] = (int)$u['id']; $_SESSION['via'] = $via; }
// Super Admin who signed in with a password must have two-step verification set up before using the portal
function must_setup_2fa(): bool { $u = user(); return $u && is_super() && empty($_SESSION['imp_from']) && ($_SESSION['via'] ?? 'password') === 'password' && empty($u['totp_secret']); }

/* ---------------- TOTP two-step verification (Google Authenticator) ---------------- */
function b32_decode(string $b): string { $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = ''; foreach (str_split(strtoupper(rtrim($b, '='))) as $c) { $v = strpos($a, $c); if ($v === false) continue; $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT); } $o = ''; foreach (str_split($bits, 8) as $by) if (strlen($by) === 8) $o .= chr(bindec($by)); return $o; }
function b32_secret(): string { $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $s = ''; foreach (str_split(random_bytes(20)) as $c) $s .= $a[ord($c) & 31]; return $s; }
function totp_code(string $secret, int $t): string { $h = hash_hmac('sha1', pack('N*', 0, $t), b32_decode($secret), true); $o = ord($h[19]) & 15; $n = (unpack('N', substr($h, $o, 4))[1] & 0x7fffffff) % 1000000; return str_pad((string)$n, 6, '0', STR_PAD_LEFT); }
function totp_verify(string $secret, string $code): bool { $code = preg_replace('/\D/', '', $code); if (strlen($code) !== 6 || $secret === '') return false; $t = intdiv(time(), 30); for ($i = -1; $i <= 1; $i++) if (hash_equals(totp_code($secret, $t + $i), $code)) return true; return false; }

/* ---------------- Daily database backups (kept 7 days, outside public_html) ---------------- */
define('BACKUP_DIR', dirname(__DIR__, 2) . '/lmserp-backups');
function backup_now(): string {
    if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0700, true);
    $file = BACKUP_DIR . '/backup-' . date('Y-m-d-His') . '.sql.gz'; $gz = gzopen($file, 'w6');
    gzwrite($gz, "-- LMS ERP backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach (db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $c = db()->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "\nDROP TABLE IF EXISTS `$t`;\n$c;\n");
        $st = db()->query("SELECT * FROM `$t`");
        while ($r = $st->fetch(PDO::FETCH_NUM)) gzwrite($gz, "INSERT INTO `$t` VALUES(" . implode(',', array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), $r)) . ");\n");
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n"); gzclose($gz); @chmod($file, 0600);
    $all = glob(BACKUP_DIR . '/backup-*.sql.gz') ?: []; rsort($all); foreach (array_slice($all, 7) as $old) @unlink($old);
    q('REPLACE INTO settings(k,v) VALUES("last_backup",?)', [date('Y-m-d')]);
    return basename($file);
}
function run_daily_backup(): void { if (setting('last_backup') !== date('Y-m-d')) { try { backup_now(); } catch (Throwable $e) { error_log('backup failed: ' . $e->getMessage()); } } }

/* ---------------- App menu (sidebar on desktop, More on mobile) ---------------- */
function menu_groups(): array {
  if (!user()) return [];
  $items = [
  ['home', '🏠', 'Dashboard', true],
  ['courses', '📚', role('student') ? 'Browse courses' : 'Courses', true],
  ['my', '🎒', role('teacher') ? 'My learning' : 'My courses', role('student', 'teacher')],
  ['users', '👥', role('admin') ? 'People' : 'My students', role('admin', 'teacher')],
  ['vouchers', '🧾', 'Fee vouchers', role('admin')],
  ['shop', '🛍️', 'Shop', role('student', 'parent')],
  ['orders', '🛒', role('admin') ? 'Shop orders' . (($po = (int)val('SELECT COUNT(*) FROM orders WHERE status="pending"')) ? " ($po)" : '') : 'My orders', true],
  ['products', '📚', role('admin') ? 'Shop products' . (($pp = (int)val('SELECT COUNT(*) FROM products WHERE review="pending"')) ? " ($pp pending)" : '') : 'My products', role('admin', 'teacher')],
  ['earnings', '🤝', role('admin') ? 'Teacher sales & payouts' : 'My sales & earnings', role('admin', 'teacher')],
  ['batches', '🗓️', 'Batches & attendance', role('admin', 'teacher')],
  ['assignments', '📝', 'Assignments', true],
  ['exams', '🧾', role('admin', 'teacher') ? 'Exams & results' : 'Results', true],
  ['attendance_me', '🗓️', 'Attendance', role('student', 'parent')],
  ['certificates', '🎓', role('admin') ? 'Certificates' : 'My certificates', !role('parent')],
  ['notifications', '🔔', 'Notifications', true],
  ['teachers', '👩‍🏫', 'Our teachers', true],
  ['announcements', '📣', 'Announcements', true],
  ['posts', '✍️', 'Blog posts' . (role('admin') && ($pr = (int)val('SELECT COUNT(*) FROM posts WHERE review="pending"')) ? " ($pr pending)" : ''), role('admin', 'teacher')],
  ['messages', '📬', 'Contact messages' . (role('admin') && ($m = (int)val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0')) ? " ($m)" : ''), role('admin')],
  ['pages_edit', '📄', 'Website pages', role('admin')],
  ['proofs', '🧾', 'Payment proofs' . (role('admin') && ($n = (int)val('SELECT COUNT(*) FROM payment_requests WHERE status="pending"')) ? " ($n)" : ''), role('admin')],
  ['fees', '💳', role('admin') ? 'Fees & payments' : (role('parent') ? 'Fees' : 'My fees'), role('admin', 'student', 'teacher', 'parent')],
  ['enrollments', '📝', 'Enrollments', role('admin', 'teacher')],
  ['expenses', '📉', 'Expenses', role('admin')],
  ['payroll', '💰', role('admin') ? 'Teacher payroll' : 'My salary', role('admin', 'teacher')],
  ['reports', '📊', 'Reports', role('admin')],
  ['settings', '⚙️', 'Settings', role('admin')],
  ['modules', '🧩', 'Modules (turn features on/off)', role('admin')],
  ['insts', '🏫', 'Institutes' . (role('admin') && mall() && ($pi = (int)val('SELECT COUNT(*) FROM institutions WHERE status="pending"')) ? " ($pi pending)" : ''), role('admin')],
  ['admissions', '📝', 'Admission enquiries', role('admin', 'institute') && mall()],
  ['inst_edit', '✏️', 'Institute profile', role('institute')],
  ['inst_teachers', '👩‍🏫', 'Our teachers', role('institute')],
  ['my_insts', '🏫', 'My institutions', role('teacher') && mall()],
  ['tprofile', '🪪', 'My teacher profile (CV)', role('admin', 'teacher')],
  ['biometric', '👆', 'Fingerprint login', true],
  ['security', '🔐', role('admin') ? 'Security & backups' : 'Two-step verification', true],
  ['help', '❓', 'Help & guides', true],
  ['site', '🌐', 'Website', true],
  ['profile', '👤', 'My profile', true],
];
  $grp = ['home' => 'Main', 'courses' => 'Main', 'my' => 'Main', 'users' => 'Main', 'insts' => 'Main', 'admissions' => 'Main', 'inst_edit' => 'Main', 'inst_teachers' => 'Main', 'my_insts' => 'Main',
    'batches' => 'Academics', 'assignments' => 'Academics', 'exams' => 'Academics', 'attendance_me' => 'Academics', 'certificates' => 'Academics', 'enrollments' => 'Academics', 'teachers' => 'Academics', 'announcements' => 'Academics', 'tprofile' => 'Academics',
    'fees' => 'Finance', 'vouchers' => 'Finance', 'proofs' => 'Finance', 'expenses' => 'Finance', 'payroll' => 'Finance', 'reports' => 'Finance', 'earnings' => 'Finance',
    'shop' => 'Shop', 'orders' => 'Shop', 'products' => 'Shop',
    'posts' => 'Website', 'messages' => 'Website', 'pages_edit' => 'Website', 'site' => 'Website',
    'settings' => 'Settings', 'modules' => 'Settings', 'security' => 'Settings', 'biometric' => 'Settings', 'notifications' => 'Settings', 'profile' => 'Settings', 'help' => 'Settings'];
  $instOk = ['home', 'courses', 'insts', 'admissions', 'inst_edit', 'inst_teachers', 'notifications', 'biometric', 'security', 'help', 'profile'];
  $out = [];
  foreach ($items as [$k, $i, $l, $show]) {
    if (!$show || !view_on($k) || !staff_can_view($k) || (role('institute') && !in_array($k, $instOk, true))) continue;
    $out[$grp[$k] ?? 'Main'][] = [$k, $i, $l];
  }
  $ord = array_flip(['Main', 'Academics', 'Finance', 'Shop', 'Website', 'Settings']); uksort($out, fn($a, $b) => ($ord[$a] ?? 9) <=> ($ord[$b] ?? 9));
  return $out;
}

function teacher_pct(): float { return max(0, min(100, (float)setting('teacher_share', '50'))); }
function teacher_balance(int $tid): array {
    $r = one('SELECT COALESCE(SUM(CASE WHEN o.status="delivered" THEN i.teacher_share END),0) earned, COALESCE(SUM(CASE WHEN o.status IN ("pending","paid","processing","shipped") THEN i.teacher_share END),0) pending, COALESCE(SUM(CASE WHEN o.status<>"cancelled" THEN i.qty END),0) sold FROM order_items i JOIN orders o ON o.id=i.order_id WHERE i.teacher_id=?', [$tid]);
    $r['paid'] = (float)val('SELECT COALESCE(SUM(amount),0) FROM teacher_payouts WHERE teacher_id=?', [$tid]);
    $r['balance'] = round($r['earned'] - $r['paid'], 2); return $r;
}
function can_view_order(array $o): bool {
    $u = user();
    if ($u && ($u['role'] === 'admin' || (int)$o['user_id'] === (int)$u['id'] || (int)$o['student_id'] === (int)$u['id'])) return true;
    $t = (string)(get('t') ?: ($_SESSION['my_orders'][$o['id']] ?? ''));
    return $o['token'] !== '' && hash_equals((string)$o['token'], $t);
}
