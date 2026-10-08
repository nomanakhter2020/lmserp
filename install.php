<?php
// One-time installer: writes config outside public_html, creates tables + admin.
require __DIR__ . '/inc/core.php';
if (cfg()) { exit('Already installed. <a href="./">Open app</a>'); }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $c = ['db_host' => post('db_host', '127.0.0.1'), 'db_name' => post('db_name'), 'db_user' => post('db_user'), 'db_pass' => (string)($_POST['db_pass'] ?? '')];
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/inc/schema.sql')))) as $sql) $pdo->exec($sql);
        if (!filter_var(post('email'), FILTER_VALIDATE_EMAIL) || strlen((string)$_POST['password']) < 6) throw new Exception('Valid admin email and 6+ char password required.');
        $pdo->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,"admin")')->execute([post('name', 'Admin'), post('email'), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        $pdo->prepare('REPLACE INTO settings(k,v) VALUES("institute",?)')->execute([post('institute', 'My Institute')]);
        foreach (['Programming', 'Design', 'Marketing', 'Languages'] as $cat) $pdo->prepare('INSERT INTO categories(name) VALUES(?)')->execute([$cat]);
        if (!file_put_contents(CONFIG_FILE, "<?php\nreturn " . var_export($c, true) . ";\n")) throw new Exception('Cannot write config file.');
        @chmod(CONFIG_FILE, 0600);
        redirect('./?p=login');
    } catch (Throwable $ex) { $err = $ex->getMessage(); }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install LMS ERP</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="auth"><form method="post" class="card authcard">
<h1>Install LMS ERP</h1><?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif ?>
<label>Institute name<input name="institute" required></label>
<h3>Database</h3>
<label>Host<input name="db_host" value="127.0.0.1"></label>
<label>Name<input name="db_name" required></label>
<label>User<input name="db_user" required></label>
<label>Password<input name="db_pass" type="password"></label>
<h3>Admin account</h3>
<label>Name<input name="name" required></label>
<label>Email<input name="email" type="email" required></label>
<label>Password<input name="password" type="password" minlength="6" required></label>
<button class="btn">Install</button></form><script src="assets/pw.js"></script></body></html>
