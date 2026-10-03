<?php
// LMS ERP core: config, db, auth, helpers
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Karachi');

const APP_NAME = 'LMS ERP';
const APP_VERSION = '2.0.0';
const DB_VERSION = 9;
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
