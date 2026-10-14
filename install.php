<?php
// One-time installer: writes config outside public_html, creates tables + admin.
require __DIR__ . '/inc/core.php';
if (cfg()) { exit('Already installed. <a href="./">Open app</a>'); }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $c = ['db_host' => post('db_host', '127.0.0.1'), 'db_name' => post('db_name'), 'db_user' => post('db_user'), 'db_pass' => (string)($_POST['db_pass'] ?? '')];
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $from = preg_replace('/[^a-z0-9.\-]/', '', strtolower(post('copy_from')));
        if ($from !== '') {
            // Clone data (all tables + uploads) from another LMS ERP site on this hosting account
            $root = dirname(__DIR__, 2);
            $sf = dirname($root) . "/$from/lmserp-config.php";
            if (!is_file($sf) || realpath(dirname($sf)) === realpath($root)) throw new Exception('No LMS ERP install found for that domain.');
            $src = require $sf;
            $s = new PDO("mysql:host={$src['db_host']};dbname={$src['db_name']};charset=utf8mb4", $src['db_user'], $src['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) $pdo->exec("DROP TABLE `$t`");
            foreach ($s->query('SHOW FULL TABLES WHERE Table_type="BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$t]) {
                $pdo->exec($s->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1]);
                $pdo->beginTransaction();
                foreach ($s->query("SELECT * FROM `$t`", PDO::FETCH_ASSOC) as $r)
                    $pdo->prepare("INSERT INTO `$t` (`" . implode('`,`', array_keys($r)) . '`) VALUES (' . rtrim(str_repeat('?,', count($r)), ',') . ')')->execute(array_values($r));
                $pdo->commit();
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $up = dirname($sf) . '/lmserp-uploads';
            if (is_dir($up)) foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $f) {
                $q = UPLOAD_DIR . substr($f->getPathname(), strlen($up));
                $f->isDir() ? (is_dir($q) || mkdir($q, 0750, true)) : (is_dir(dirname($q)) || mkdir(dirname($q), 0750, true)) && copy($f->getPathname(), $q);
            }
            if (post('institute') !== '') $pdo->prepare('REPLACE INTO settings(k,v) VALUES("institute",?)')->execute([post('institute')]);
        } else {
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/inc/schema.sql')))) as $sql) $pdo->exec($sql);
        if (!filter_var(post('email'), FILTER_VALIDATE_EMAIL) || strlen((string)$_POST['password']) < 6) throw new Exception('Valid admin email and 6+ char password required.');
        $pdo->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,"admin")')->execute([post('name', 'Admin'), post('email'), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        $pdo->prepare('REPLACE INTO settings(k,v) VALUES("institute",?)')->execute([post('institute', 'My Institute')]);
        $pdo->prepare('REPLACE INTO settings(k,v) VALUES("mall_mode",?)')->execute([post('edition') === 'multi' ? '1' : '0']);
        foreach (['Programming', 'Design', 'Marketing', 'Languages'] as $cat) $pdo->prepare('INSERT INTO categories(name) VALUES(?)')->execute([$cat]);
        }
        if (!file_put_contents(CONFIG_FILE, "<?php\nreturn " . var_export($c, true) . ";\n")) throw new Exception('Cannot write config file.');
        @chmod(CONFIG_FILE, 0600);
        redirect('./?p=login');
    } catch (Throwable $ex) { $err = $ex->getMessage(); }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install LMS ERP</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="auth"><form method="post" class="card authcard">
<h1>Install LMS ERP</h1><?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif ?>
<label>Institute name<input name="institute"></label>
<h3>Database</h3>
<label>Host<input name="db_host" value="127.0.0.1"></label>
<label>Name<input name="db_name" required></label>
<label>User<input name="db_user" required></label>
<label>Password<input name="db_pass" type="password"></label>
<h3>Edition</h3>
<label class="check" style="display:flex;gap:10px;align-items:flex-start;border:1px solid #e4e5f1;border-radius:12px;padding:12px;margin-bottom:8px"><input type="radio" name="edition" value="single" checked style="width:auto;margin-top:4px"><span><b>🏫 Single Academy</b><br><small>One school / academy portal — courses, classes, fees, exams, shop, website</small></span></label>
<label class="check" style="display:flex;gap:10px;align-items:flex-start;border:1px solid #e4e5f1;border-radius:12px;padding:12px;margin-bottom:8px"><input type="radio" name="edition" value="multi" style="width:auto;margin-top:4px"><span><b>🏬 Multi-Vendor Academy</b><br><small>Marketplace of many schools, colleges &amp; academies — institute logins, online admissions, shared teacher pool</small></span></label>
<h3>Copy data (optional)</h3>
<label>Copy everything from domain<input name="copy_from" placeholder="e.g. lmserp.hostingersite.com"><small>Copies all data, users and uploads from another LMS ERP site on this hosting. Admin fields below are then ignored — log in with the source site's accounts.</small></label>
<h3>Admin account</h3>
<label>Name<input name="name"></label>
<label>Email<input name="email" type="email"></label>
<label>Password<input name="password" type="password" minlength="6"></label>
<button class="btn">Install</button></form><script src="assets/pw.js"></script></body></html>
