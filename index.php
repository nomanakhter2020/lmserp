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
    if ($a === 'contact_send') {
        if (post('website') !== '' || (time() - ($_SESSION['last_contact'] ?? 0)) < 30) { flash('Please wait a moment before sending another message.', 'err'); redirect('contact'); }
        if (post('name') === '' || post('message') === '' || (post('email') === '' && post('phone') === '')) { flash('Please enter your name, message and an email or phone.', 'err'); redirect('contact'); }
        q('INSERT INTO contact_messages(name,email,phone,subject,message) VALUES(?,?,?,?,?)', [mb_substr(post('name'), 0, 120), mb_substr(post('email'), 0, 160), mb_substr(post('phone'), 0, 40), mb_substr(post('subject'), 0, 200), mb_substr(post('message'), 0, 3000)]);
        $_SESSION['last_contact'] = time();
        flash('Thank you! Your message has been sent. We will reply soon.'); redirect('contact');
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
            require_role('admin');
            q('INSERT INTO expenses(title,amount,spent_on,category_id,note) VALUES(?,?,?,?,?)', [post('title'), (float)post('amount'), post('spent_on', date('Y-m-d')), (int)post('category_id') ?: null, post('note')]);
            $eid = (int)db()->lastInsertId();
            if (post('make_recurring')) {
                $f = in_array(post('frequency'), ['monthly', 'weekly', 'yearly'], true) ? post('frequency') : 'monthly';
                q('INSERT INTO recurring_expenses(title,amount,category_id,frequency,next_date) VALUES(?,?,?,?,?)', [post('title'), (float)post('amount'), (int)post('category_id') ?: null, $f, next_due(post('spent_on', date('Y-m-d')), $f)]);
                q('UPDATE expenses SET recurring_id=? WHERE id=?', [(int)db()->lastInsertId(), $eid]);
            }
            flash('Expense added'); redirect('?p=expenses&m=' . substr(post('spent_on', date('Y-m-d')), 0, 7));
        case 'recurring_save':
            require_role('admin');
            $f = in_array(post('frequency'), ['monthly', 'weekly', 'yearly'], true) ? post('frequency') : 'monthly';
            $data = [post('title'), (float)post('amount'), (int)post('category_id') ?: null, $f, post('next_date', date('Y-m-d')), post('active') ? 1 : 0, post('note')];
            if ($id) q('UPDATE recurring_expenses SET title=?,amount=?,category_id=?,frequency=?,next_date=?,active=?,note=? WHERE id=?', [...$data, $id]);
            else q('INSERT INTO recurring_expenses(title,amount,category_id,frequency,next_date,active,note) VALUES(?,?,?,?,?,?,?)', $data);
            $posted = run_recurring();
            flash('Recurring expense saved' . ($posted ? " · $posted entr" . ($posted > 1 ? 'ies' : 'y') . ' posted' : '')); redirect('?p=recurring');
        case 'recurring_delete':
            require_role('admin'); q('DELETE FROM recurring_expenses WHERE id=?', [$id]); flash('Recurring expense removed (past entries kept)'); redirect('?p=recurring');
        case 'expcat_add':
            require_role('admin'); q('INSERT INTO expense_categories(name,icon) VALUES(?,?)', [post('name'), post('icon') ?: '💸']); redirect('?p=expense_cats');
        case 'expcat_delete':
            require_role('admin'); q('UPDATE expenses SET category_id=NULL WHERE category_id=?', [$id]); q('UPDATE recurring_expenses SET category_id=NULL WHERE category_id=?', [$id]); q('DELETE FROM expense_categories WHERE id=?', [$id]); redirect('?p=expense_cats');
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
            $t = demo_seed(); $it = demo_it_seed(); demo_covers_seed();
            $nt = (int)val('SELECT COUNT(*) FROM users WHERE email LIKE "%@demo.lms"');
            $nc = (int)val('SELECT COUNT(*) FROM courses c JOIN users u ON u.id=c.teacher_id WHERE u.email LIKE "%@demo.lms"');
            $np = (int)val('SELECT COUNT(*) FROM teacher_profiles tp JOIN users u ON u.id=tp.user_id WHERE u.email LIKE "%@demo.lms" AND tp.photo<>""');
            $nv = (int)val('SELECT COUNT(*) FROM courses c JOIN users u ON u.id=c.teacher_id WHERE u.email LIKE "%@demo.lms" AND c.cover<>""');
            $errs = array_unique($GLOBALS['demo_err'] ?? []);
            flash("Demo loaded: $nt teachers, $nc courses, $np photos, $nv course covers." . ($errs ? ' Image download problem: ' . implode('; ', $errs) : ''), $errs ? 'warn' : 'ok');
            redirect('?p=users&role=teacher');
        case 'post_save':
            require_role('admin', 'teacher');
            $old = $id ? one('SELECT * FROM posts WHERE id=?', [$id]) : null;
            if ($id && (!$old || (!role('admin') && (int)$old['author_id'] !== (int)$me['id']))) exit('Not allowed');
            $slug = slugify(post('slug') ?: post('title')); $base = $slug; $n = 2;
            while (val('SELECT id FROM posts WHERE slug=? AND id<>?', [$slug, $id])) $slug = $base . '-' . $n++;
            try { $cover = save_cover('cover'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect('?p=post_edit' . ($id ? "&id=$id" : '')); }
            $excerpt = post('excerpt') ?: mb_strimwidth(trim(preg_replace('/\s+/', ' ', preg_replace('/[#*>\[\]()-]+/', ' ', post('content')))), 0, 155, '…');
            // Admin publishes directly; teachers can only save a draft or submit for review.
            if (role('admin')) { $pub = post('published') ? 1 : 0; $review = $pub ? '' : ($old['review'] ?? ''); }
            else { $pub = 0; $review = post('submit_review') ? 'pending' : ''; }
            $data = [post('title'), $slug, post('category') ?: 'General', $excerpt, post('content'), $pub, $review];
            if ($id) q('UPDATE posts SET title=?,slug=?,category=?,excerpt=?,content=?,published=?,review=? WHERE id=?', [...$data, $id]);
            else { q('INSERT INTO posts(title,slug,category,excerpt,content,published,review,author_id) VALUES(?,?,?,?,?,?,?,?)', [...$data, $me['id']]); $id = (int)db()->lastInsertId(); }
            if ($cover) { if ($old && $old['cover']) @unlink(UPLOAD_DIR . '/covers/' . basename($old['cover'])); q('UPDATE posts SET cover=? WHERE id=?', [$cover, $id]); }
            flash(role('admin') ? 'Article saved' : ($review === 'pending' ? 'Submitted for review. It will go live after admin approval.' : 'Draft saved')); redirect("?p=post_edit&id=$id");
        case 'post_review':
            require_role('admin');
            if (post('decision') === 'approve') { q('UPDATE posts SET published=1,review="",review_note="",created_at=IF(published=0 AND views=0,NOW(),created_at) WHERE id=?', [$id]); flash('Article approved and published'); }
            else { q('UPDATE posts SET published=0,review="rejected",review_note=? WHERE id=?', [post('note'), $id]); flash('Article sent back to the teacher'); }
            redirect(post('back', '?p=posts&f=pending'));
        case 'post_delete':
            $old = one('SELECT * FROM posts WHERE id=?', [$id]);
            if (!$old || (!role('admin') && (int)$old['author_id'] !== (int)$me['id'])) exit('Not allowed');
            q('DELETE FROM posts WHERE id=?', [$id]); flash('Article deleted'); redirect('?p=posts');
        case 'page_save':
            require_role('admin');
            if (isset(LEGAL_PAGES[post('slug')])) q('REPLACE INTO settings(k,v) VALUES(?,?)', ['page_' . post('slug'), (string)($_POST['content'] ?? '')]);
            flash('Page saved'); redirect('?p=pages_edit' . (post('content') !== '' ? '&slug=' . post('slug') : ''));
        case 'msg_delete':
            require_role('admin'); q('DELETE FROM contact_messages WHERE id=?', [$id]); redirect('?p=messages');
        case 'blog_seed':
            require_role('admin');
            require __DIR__ . '/inc/blog_seed.php';
            $n = blog_seed();
            flash("$n starter articles added to the blog"); redirect('?p=posts');
        case 'category_add':
            require_role('admin'); q('INSERT INTO categories(name) VALUES(?)', [post('name')]); redirect('?p=settings');
        case 'category_delete':
            require_role('admin'); q('DELETE FROM categories WHERE id=?', [$id]); redirect('?p=settings');
        case 'settings_save':
            require_role('admin');
            foreach (['institute', 'phone', 'allow_register', 'paid_needs_approval', 'pay_bank', 'pay_jazzcash', 'pay_easypaisa', 'pay_cash', 'site_tagline', 'site_about', 'site_whatsapp', 'site_email', 'site_address', 'adsense_client'] as $k) q('REPLACE INTO settings(k,v) VALUES(?,?)', [$k, post($k, '0')]);
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
if (in_array($p, ['blog', 'post', 'page'], true)) { require __DIR__ . "/views/$p.php"; exit; }
if ($p === 'robots') { header('Content-Type: text/plain'); echo "User-agent: *\nAllow: /\nDisallow: /install.php\nDisallow: /*?p=login\nDisallow: /*?p=register\n\nSitemap: " . abs_url('sitemap.xml') . "\n"; exit; }
if ($p === 'adstxt') { header('Content-Type: text/plain'); $c = adsense_client(); echo $c ? 'google.com, ' . str_replace('ca-', '', $c) . ", DIRECT, f08c47fec0942fa0\n" : "# AdSense publisher ID not set yet\n"; exit; }
if ($p === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    $u = [[abs_url(), date('Y-m-d'), '1.0'], [abs_url('blog'), date('Y-m-d'), '0.9']];
    foreach (all('SELECT slug,updated_at,created_at FROM posts WHERE published=1 ORDER BY created_at DESC') as $r) $u[] = [abs_url('blog/' . $r['slug']), substr($r['updated_at'] ?: $r['created_at'], 0, 10), '0.8'];
    foreach (array_keys(LEGAL_PAGES) as $k) $u[] = [abs_url($k), date('Y-m-d'), '0.4'];
    foreach (all('SELECT u.id FROM users u JOIN teacher_profiles tp ON tp.user_id=u.id WHERE u.active=1 AND tp.public=1') as $r) $u[] = [abs_url('?p=teacher&id=' . $r['id']), date('Y-m-d'), '0.6'];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($u as [$l, $d, $pr]) echo '<url><loc>' . htmlspecialchars($l, ENT_XML1) . "</loc><lastmod>$d</lastmod><priority>$pr</priority></url>\n";
    echo '</urlset>'; exit;
}
// Public website: guests landing on the root URL, or anyone via ?p=site
if (($p === 'home' && !isset($_GET['p']) && !user()) || $p === 'site') { require __DIR__ . '/views/landing.php'; exit; }
if ($p === 'logout') { session_destroy(); redirect('?p=login'); }
if (in_array($p, ['login', 'register'], true) && user()) redirect('./?p=home');
if (!in_array($p, ['login', 'register'], true)) require_login();

if (role('admin')) run_recurring();
$view = __DIR__ . "/views/$p.php";
$page = $p;
if (!is_file($view)) { $p = $page = "home"; $view = __DIR__ . "/views/home.php"; }
require __DIR__ . '/views/_layout.php';
