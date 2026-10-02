<?php
require __DIR__ . '/inc/core.php';
if (!cfg()) redirect('install.php');
check_csrf();
migrate();

$p = preg_replace('/[^a-z_]/', '', (string)get('p', 'home'));
$id = (int)($_POST['id'] ?? get('id', 0));
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

/* ------------------------- ACTIONS (POST) ------------------------- */
if ($isPost) {
    $a = post('a');
    switch ($a) {
        case 'login':
            $u = one('SELECT * FROM users WHERE email=? AND active=1', [post('email')]);
            if ($u && password_verify((string)$_POST['password'], $u['password'])) { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; redirect('./'); }
            flash('Wrong email or password', 'err'); redirect('?p=login');
        case 'register':
            if (setting('allow_register', '1') !== '1') redirect('?p=login');
            if (!filter_var(post('email'), FILTER_VALIDATE_EMAIL) || strlen((string)$_POST['password']) < 6) { flash('Valid email and 6+ char password required', 'err'); redirect('?p=register'); }
            if (val('SELECT id FROM users WHERE email=?', [post('email')])) { flash('Email already registered', 'err'); redirect('?p=register'); }
            q('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,"student")', [post('name'), post('email'), post('phone'), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            $_SESSION['uid'] = db()->lastInsertId(); redirect('./');
    }
    require_login();
    $me = user();
    switch ($a) {
        case 'course_save':
            require_role('admin', 'teacher');
            $teacher = role('admin') ? ((int)post('teacher_id') ?: null) : $me['id'];
            $data = [post('title'), post('description'), (int)post('category_id') ?: null, $teacher, (float)post('fee'), post('color', '#4f46e5'), post('published') ? 1 : 0];
            try { $cover = save_cover('cover'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect('?p=course_edit' . ($id ? "&id=$id" : '')); }
            if ($id) {
                $c = one('SELECT * FROM courses WHERE id=?', [$id]); if (!$c || !can_manage_course($c)) exit('Not allowed');
                q('UPDATE courses SET title=?,description=?,category_id=?,teacher_id=?,fee=?,color=?,published=? WHERE id=?', [...$data, $id]);
            } else { q('INSERT INTO courses(title,description,category_id,teacher_id,fee,color,published) VALUES(?,?,?,?,?,?,?)', $data); $id = db()->lastInsertId(); }
            if ($cover || post('remove_cover')) {
                $old = val('SELECT cover FROM courses WHERE id=?', [$id]);
                if ($old) @unlink(UPLOAD_DIR . '/covers/' . basename($old));
                q('UPDATE courses SET cover=? WHERE id=?', [$cover, $id]);
            }
            flash('Course saved'); redirect("?p=course&id=$id");
        case 'course_delete':
            require_role('admin');
            foreach (['DELETE FROM lessons WHERE course_id=?', 'DELETE FROM enrollments WHERE course_id=?', 'DELETE FROM quizzes WHERE course_id=?', 'DELETE FROM courses WHERE id=?'] as $s) q($s, [$id]);
            flash('Course deleted'); redirect('?p=courses');
        case 'lesson_save':
            $cid = (int)post('course_id'); $c = one('SELECT * FROM courses WHERE id=?', [$cid]); if (!$c || !can_manage_course($c)) exit('Not allowed');
            $data = [post('title'), post('video_url'), post('content'), post('attachment_url'), (int)post('sort')];
            if ($id) q('UPDATE lessons SET title=?,video_url=?,content=?,attachment_url=?,sort=? WHERE id=? AND course_id=?', [...$data, $id, $cid]);
            else q('INSERT INTO lessons(title,video_url,content,attachment_url,sort,course_id) VALUES(?,?,?,?,?,?)', [...$data, $cid]);
            flash('Lesson saved'); redirect("?p=course&id=$cid");
        case 'lesson_delete':
            $l = one('SELECT l.*,c.teacher_id FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=?', [$id]);
            if (!$l || !can_manage_course($l)) exit('Not allowed');
            q('DELETE FROM lessons WHERE id=?', [$id]); flash('Lesson deleted'); redirect("?p=course&id={$l['course_id']}");
        case 'lesson_done':
            q('INSERT IGNORE INTO progress(user_id,lesson_id) VALUES(?,?)', [$me['id'], $id]);
            $l = one('SELECT * FROM lessons WHERE id=?', [$id]);
            $next = val('SELECT id FROM lessons WHERE course_id=? AND (sort>? OR (sort=? AND id>?)) ORDER BY sort,id LIMIT 1', [$l['course_id'], $l['sort'], $l['sort'], $id]);
            if (course_progress((int)$me['id'], (int)$l['course_id']) >= 100) q('UPDATE enrollments SET status="completed" WHERE user_id=? AND course_id=?', [$me['id'], $l['course_id']]);
            redirect($next ? "?p=lesson&id=$next" : "?p=course&id={$l['course_id']}");
        case 'enroll':
            $c = one('SELECT * FROM courses WHERE id=? AND published=1', [$id]); if (!$c) redirect('?p=courses');
            $status = ((float)$c['fee'] > 0 && setting('paid_needs_approval', '1') === '1') ? 'pending' : 'active';
            q('INSERT IGNORE INTO enrollments(user_id,course_id,status) VALUES(?,?,?)', [$me['id'], $id, $status]);
            flash($status === 'pending' ? 'Enrollment requested. Access opens after fee is confirmed.' : 'Enrolled!'); redirect("?p=course&id=$id");
        case 'enroll_admin':
            require_role('admin');
            q('INSERT INTO enrollments(user_id,course_id,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)', [(int)post('user_id'), (int)post('course_id'), post('status', 'active')]);
            flash('Enrollment updated'); redirect(post('back', '?p=enrollments'));
        case 'enroll_remove':
            require_role('admin'); q('DELETE FROM enrollments WHERE id=?', [$id]); flash('Enrollment removed'); redirect('?p=enrollments');
        case 'quiz_save':
            $cid = (int)post('course_id'); $c = one('SELECT * FROM courses WHERE id=?', [$cid]); if (!$c || !can_manage_course($c)) exit('Not allowed');
            if ($id) q('UPDATE quizzes SET title=?,pass_percent=? WHERE id=?', [post('title'), (int)post('pass_percent', 50), $id]);
            else { q('INSERT INTO quizzes(course_id,title,pass_percent) VALUES(?,?,?)', [$cid, post('title'), (int)post('pass_percent', 50)]); $id = db()->lastInsertId(); }
            flash('Quiz saved'); redirect("?p=quiz_edit&id=$id");
        case 'question_add':
            $qz = one('SELECT qz.*,c.teacher_id FROM quizzes qz JOIN courses c ON c.id=qz.course_id WHERE qz.id=?', [$id]); if (!$qz || !can_manage_course($qz)) exit('Not allowed');
            q('INSERT INTO questions(quiz_id,question,a,b,c,d,answer) VALUES(?,?,?,?,?,?,?)', [$id, post('question'), post('qa'), post('qb'), post('qc'), post('qd'), strtolower(post('answer', 'a'))]);
            flash('Question added'); redirect("?p=quiz_edit&id=$id");
        case 'question_delete':
            $qn = one('SELECT qn.quiz_id,c.teacher_id FROM questions qn JOIN quizzes qz ON qz.id=qn.quiz_id JOIN courses c ON c.id=qz.course_id WHERE qn.id=?', [$id]);
            if (!$qn || !can_manage_course($qn)) exit('Not allowed');
            q('DELETE FROM questions WHERE id=?', [$id]); redirect("?p=quiz_edit&id={$qn['quiz_id']}");
        case 'quiz_delete':
            $qz = one('SELECT qz.*,c.teacher_id FROM quizzes qz JOIN courses c ON c.id=qz.course_id WHERE qz.id=?', [$id]); if (!$qz || !can_manage_course($qz)) exit('Not allowed');
            q('DELETE FROM questions WHERE quiz_id=?', [$id]); q('DELETE FROM quizzes WHERE id=?', [$id]); redirect("?p=course&id={$qz['course_id']}");
        case 'quiz_submit':
            $qs = all('SELECT id,answer FROM questions WHERE quiz_id=?', [$id]); $score = 0;
            foreach ($qs as $qq) if (($_POST['q'][$qq['id']] ?? '') === $qq['answer']) $score++;
            q('INSERT INTO attempts(quiz_id,user_id,score,total) VALUES(?,?,?,?)', [$id, $me['id'], $score, count($qs)]);
            redirect("?p=quiz_result&id=" . db()->lastInsertId());
        case 'user_save':
            require_role('admin');
            $role = in_array(post('role'), ['admin', 'teacher', 'student'], true) ? post('role') : 'student';
            if ($id) {
                q('UPDATE users SET name=?,email=?,phone=?,role=?,active=? WHERE id=?', [post('name'), post('email'), post('phone'), $role, post('active') ? 1 : 0, $id]);
                if ((string)($_POST['password'] ?? '') !== '') q('UPDATE users SET password=? WHERE id=?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $id]);
            } else {
                if (val('SELECT id FROM users WHERE email=?', [post('email')])) { flash('Email exists', 'err'); redirect('?p=user_edit'); }
                q('INSERT INTO users(name,email,phone,role,password) VALUES(?,?,?,?,?)', [post('name'), post('email'), post('phone'), $role, password_hash($_POST['password'] ?: bin2hex(random_bytes(4)), PASSWORD_DEFAULT)]);
                $id = db()->lastInsertId();
            }
            flash('User saved'); redirect("?p=user&id=$id");
        case 'payment_add':
            require_role('admin');
            q('INSERT INTO payments(user_id,course_id,amount,method,note,paid_on,created_by) VALUES(?,?,?,?,?,?,?)', [(int)post('user_id'), (int)post('course_id') ?: null, (float)post('amount'), post('method', 'Cash'), post('note'), post('paid_on', date('Y-m-d')), $me['id']]);
            if (post('activate') && (int)post('course_id')) q('UPDATE enrollments SET status="active" WHERE user_id=? AND course_id=? AND status="pending"', [(int)post('user_id'), (int)post('course_id')]);
            flash('Payment recorded'); redirect(post('back', '?p=fees'));
        case 'proof_submit':
            $c = one('SELECT * FROM courses WHERE id=?', [$id]);
            $en = $c ? one('SELECT * FROM enrollments WHERE user_id=? AND course_id=?', [$me['id'], $id]) : null;
            if (!$en) redirect('?p=courses');
            $method = in_array(post('method'), ['Bank', 'JazzCash', 'EasyPaisa', 'Cash'], true) ? post('method') : 'Bank';
            try { $proof = save_upload('proof'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("?p=course&id=$id"); }
            if ($method !== 'Cash' && !$proof && post('txn_ref') === '') { flash('Upload a screenshot or enter the transaction ID', 'err'); redirect("?p=course&id=$id"); }
            q('INSERT INTO payment_requests(user_id,course_id,amount,method,txn_ref,proof,note) VALUES(?,?,?,?,?,?,?)', [$me['id'], $id, (float)post('amount', $c['fee']), $method, post('txn_ref'), $proof, post('note')]);
            flash($method === 'Cash' ? 'Noted. Pay cash at the office; admin will unlock the course.' : 'Payment proof sent. You will get access once the admin verifies it.');
            redirect("?p=course&id=$id");
        case 'proof_review':
            require_role('admin');
            $r = one('SELECT * FROM payment_requests WHERE id=? AND status="pending"', [$id]);
            if (!$r) redirect('?p=proofs');
            if (post('decision') === 'approve') {
                $amt = (float)post('amount', $r['amount']);
                q('INSERT INTO payments(user_id,course_id,amount,method,note,paid_on,created_by) VALUES(?,?,?,?,?,?,?)', [$r['user_id'], $r['course_id'], $amt, $r['method'], ($r['txn_ref'] ? 'Ref ' . $r['txn_ref'] : ($r['method'] === 'Cash' ? 'Cash at office' : 'Online proof')), date('Y-m-d'), $me['id']]);
                $pid = db()->lastInsertId();
                q('UPDATE payment_requests SET status="approved",amount=?,payment_id=?,admin_note=? WHERE id=?', [$amt, $pid, post('admin_note'), $id]);
                q('UPDATE enrollments SET status="active" WHERE user_id=? AND course_id=? AND status="pending"', [$r['user_id'], $r['course_id']]);
                flash('Approved — payment recorded and course unlocked');
            } else {
                q('UPDATE payment_requests SET status="rejected",admin_note=? WHERE id=?', [post('admin_note'), $id]);
                flash('Request rejected');
            }
            redirect('?p=proofs');
        case 'payment_delete':
            require_role('admin'); q('DELETE FROM payments WHERE id=?', [$id]); flash('Payment deleted'); redirect('?p=fees');
        case 'expense_add':
            require_role('admin'); q('INSERT INTO expenses(title,amount,spent_on) VALUES(?,?,?)', [post('title'), (float)post('amount'), post('spent_on', date('Y-m-d'))]);
            flash('Expense added'); redirect('?p=expenses');
        case 'expense_delete':
            require_role('admin'); q('DELETE FROM expenses WHERE id=?', [$id]); redirect('?p=expenses');
        case 'announce':
            require_role('admin', 'teacher');
            $cid = (int)post('course_id') ?: null;
            if ($cid) { $c = one('SELECT * FROM courses WHERE id=?', [$cid]); if (!$c || !can_manage_course($c)) exit('Not allowed'); } elseif (!role('admin')) exit('Not allowed');
            q('INSERT INTO announcements(course_id,title,body,created_by) VALUES(?,?,?,?)', [$cid, post('title'), post('body'), $me['id']]);
            flash('Announcement posted'); redirect($cid ? "?p=course&id=$cid" : '?p=announcements');
        case 'announce_delete':
            require_role('admin'); q('DELETE FROM announcements WHERE id=?', [$id]); redirect('?p=announcements');
        case 'demo_seed':
            require_role('admin');
            require __DIR__ . '/inc/demo.php';
            @set_time_limit(300);
            $t = demo_seed(); $it = demo_it_seed();
            $nt = (int)val('SELECT COUNT(*) FROM users WHERE email LIKE "%@demo.lms"');
            $nc = (int)val('SELECT COUNT(*) FROM courses c JOIN users u ON u.id=c.teacher_id WHERE u.email LIKE "%@demo.lms"');
            $np = (int)val('SELECT COUNT(*) FROM teacher_profiles tp JOIN users u ON u.id=tp.user_id WHERE u.email LIKE "%@demo.lms" AND tp.photo<>""');
            $errs = array_unique($GLOBALS['demo_err'] ?? []);
            flash("Demo loaded: $nt teachers, $nc courses, $np photos." . ($errs ? ' Image download problem: ' . implode('; ', $errs) : ''), $errs ? 'warn' : 'ok');
            redirect('?p=users&role=teacher');
        case 'category_add':
            require_role('admin'); q('INSERT INTO categories(name) VALUES(?)', [post('name')]); redirect('?p=settings');
        case 'category_delete':
            require_role('admin'); q('DELETE FROM categories WHERE id=?', [$id]); redirect('?p=settings');
        case 'settings_save':
            require_role('admin');
            foreach (['institute', 'phone', 'allow_register', 'paid_needs_approval', 'pay_bank', 'pay_jazzcash', 'pay_easypaisa', 'pay_cash', 'site_tagline', 'site_about', 'site_whatsapp', 'site_email', 'site_address'] as $k) q('REPLACE INTO settings(k,v) VALUES(?,?)', [$k, post($k, '0')]);
            flash('Settings saved'); redirect('?p=settings');
        case 'tprofile_save':
            $uid = role('admin') && (int)post('user_id') ? (int)post('user_id') : (int)$me['id'];
            if (!role('admin') && !role('teacher')) exit('Not allowed');
            $rows = function (string $k, array $fields) {
                $out = [];
                foreach ((array)($_POST[$k] ?? []) as $r) {
                    $r = array_map(fn($f) => trim((string)($r[$f] ?? '')), array_combine($fields, $fields));
                    if (implode('', $r) !== '') $out[] = $r;
                }
                return json_encode($out, JSON_UNESCAPED_UNICODE);
            };
            $old = teacher_profile($uid);
            try { $photo = save_cover('photo'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("?p=tprofile&id=$uid"); }
            if ($photo && $old['photo']) @unlink(UPLOAD_DIR . '/covers/' . basename($old['photo']));
            if (!$photo) $photo = post('remove_photo') ? '' : $old['photo'];
            q('REPLACE INTO teacher_profiles(user_id,photo,headline,bio,city,years,skills,languages,education,experience,certifications,achievements,linkedin,website,youtube,public) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $uid, $photo, post('headline'), post('bio'), post('city'), (int)post('years'), post('skills'), post('languages'),
                $rows('edu', ['degree', 'institute', 'year', 'detail']),
                $rows('exp', ['role', 'org', 'period', 'detail']),
                $rows('cert', ['name', 'issuer', 'year']),
                post('achievements'), post('linkedin'), post('website'), post('youtube'), post('public') ? 1 : 0]);
            flash('Profile saved'); redirect("?p=tprofile&id=$uid");
        case 'profile_save':
            q('UPDATE users SET name=?,phone=? WHERE id=?', [post('name'), post('phone'), $me['id']]);
            if ((string)($_POST['password'] ?? '') !== '') {
                if (!password_verify((string)$_POST['current'], $me['password'])) { flash('Current password wrong', 'err'); redirect('?p=profile'); }
                q('UPDATE users SET password=? WHERE id=?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $me['id']]);
            }
            flash('Profile updated'); redirect('?p=profile');
    }
    redirect('./');
}

if ($p === 'cover') { // public: course covers are shown on the website
    $f = basename((string)get('f'));
    $path = UPLOAD_DIR . '/covers/' . $f;
    if (!preg_match('/^c[\w-]+\.(jpg|png|webp)$/', $f) || !is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
    header('Cache-Control: public, max-age=2592000, immutable');
    readfile($path); exit;
}
if ($p === 'proof_file') {
    require_login();
    $r = one('SELECT * FROM payment_requests WHERE id=?', [$id]);
    if (!$r || !$r['proof'] || (!role('admin') && (int)$r['user_id'] !== (int)user()['id'])) { http_response_code(404); exit; }
    $path = UPLOAD_DIR . '/' . basename($r['proof']);
    if (!is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
    header('Content-Disposition: inline'); header('X-Content-Type-Options: nosniff');
    readfile($path); exit;
}
if ($p === 'teacher') { require __DIR__ . '/views/teacher.php'; exit; }
// Public website: guests landing on the root URL, or anyone via ?p=site
if (($p === 'home' && !isset($_GET['p']) && !user()) || $p === 'site') { require __DIR__ . '/views/landing.php'; exit; }
if ($p === 'logout') { session_destroy(); redirect('?p=login'); }
if (in_array($p, ['login', 'register'], true) && user()) redirect('./?p=home');
if (!in_array($p, ['login', 'register'], true)) require_login();

$view = __DIR__ . "/views/$p.php";
$page = $p;
if (!is_file($view)) { $p = $page = "home"; $view = __DIR__ . "/views/home.php"; }
require __DIR__ . '/views/_layout.php';
