<?php
// Demo data: 3 teachers with full CVs, photos, a course each with lessons & a quiz.
// Idempotent: keyed by demo emails, safe to run twice.
function demo_fetch_photo(string $url): string {
    $data = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 25, CURLOPT_USERAGENT => 'Mozilla/5.0']);
        $data = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) $data = false;
        curl_close($ch);
    } else {
        $data = @file_get_contents($url);
    }
    if (!$data || !function_exists('imagecreatefromstring') || !($im = @imagecreatefromstring($data))) return '';
    $w = imagesx($im); $h = imagesy($im); $s = min($w, $h);
    $out = imagecreatetruecolor(600, 600);
    imagecopyresampled($out, $im, 0, 0, (int)(($w - $s) / 2), (int)(($h - $s) / 2), 600, 600, $s, $s);
    if (!is_dir(UPLOAD_DIR . '/covers')) mkdir(UPLOAD_DIR . '/covers', 0750, true);
    $name = 'cdemo-' . bin2hex(random_bytes(5)) . '.jpg';
    imagejpeg($out, UPLOAD_DIR . "/covers/$name", 85);
    return $name;
}

function demo_seed(): array {
    $cat = function (string $n) { return (int)(val('SELECT id FROM categories WHERE name=?', [$n]) ?: (q('INSERT INTO categories(name) VALUES(?)', [$n]) ? db()->lastInsertId() : 0)); };
    $T = [
      [
        'name' => 'Ahmed Raza', 'email' => 'ahmed.raza@demo.lms', 'phone' => '0300-1112233',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618569636/render.png?token=exp=1791331200~hmac=dfa2464a0ee8ad5a99f435f878589f08fee53a84f712292e779f26120ad23947',
        'p' => [
          'headline' => 'Mathematics Lecturer · O/A Level & Entry Test Specialist', 'city' => 'Karachi', 'years' => 12,
          'bio' => "I have spent 12 years helping students fall in love with mathematics. My approach is simple: understand the 'why' before the formula, then practise until it becomes second nature.\n\nI have prepared hundreds of students for O/A Levels, FSc and university entry tests (NUST, FAST, ECAT), with a strong record of A and A* grades.",
          'skills' => 'Algebra, Calculus, Trigonometry, Statistics, Entry Test Prep, Problem Solving', 'languages' => 'English, Urdu, Sindhi',
          'education' => [['degree' => 'MPhil Applied Mathematics', 'institute' => 'University of Karachi', 'year' => '2013', 'detail' => 'CGPA 3.7 / 4.0'], ['degree' => 'MSc Mathematics', 'institute' => 'University of Karachi', 'year' => '2011', 'detail' => 'First Division']],
          'experience' => [['role' => 'Senior Mathematics Lecturer', 'org' => 'Karachi Grammar Academy', 'period' => '2017 – Present', 'detail' => 'Teach O/A Level Pure Mathematics and Statistics; head of the maths department with 6 teachers.'], ['role' => 'Mathematics Teacher', 'org' => 'The City School', 'period' => '2013 – 2017', 'detail' => 'Taught grades 8–10; introduced weekly problem-solving clinics.']],
          'certifications' => [['name' => 'Cambridge Teaching Mathematics Certificate', 'issuer' => 'Cambridge Assessment', 'year' => '2018'], ['name' => 'Online Teaching Fundamentals', 'issuer' => 'Coursera', 'year' => '2020']],
          'achievements' => "Best Teacher Award 2021 — Karachi Grammar Academy\n40+ students achieved A* in A Level Mathematics\nAuthor of 'Entry Test Maths Made Easy' practice book",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Mathematics for Entry Tests', 'cat' => 'Mathematics', 'fee' => 6000, 'color' => '#2563eb',
          'desc' => 'Complete preparation for NUST, FAST and ECAT maths: algebra, functions, trigonometry and calculus with timed practice tests.',
          'lessons' => [['Algebra essentials', 'Linear and quadratic equations, factorisation, and shortcuts for MCQs.'], ['Functions & graphs', 'Domain, range, composite and inverse functions with graph sketching.'], ['Trigonometry made easy', 'Identities, equations and the unit circle — exam-style questions.']],
          'quiz' => ['Algebra Check', [['If 2x + 6 = 14, what is x?', '3', '4', '5', '8', 'b'], ['What is (a+b)²?', 'a² + b²', 'a² + 2ab + b²', '2a + 2b', 'a² − b²', 'b'], ['sin 90° equals', '0', '1', '½', '−1', 'b']]]],
      ],
      [
        'name' => 'Ayesha Siddiqui', 'email' => 'ayesha.siddiqui@demo.lms', 'phone' => '0321-4445566',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618570121/render.png?token=exp=1791331200~hmac=86bb2309ddb200588449cbef958c555f87b02fe9f0f10940363cd4270c118eba',
        'p' => [
          'headline' => 'English Language Teacher · IELTS & Spoken English Trainer', 'city' => 'Lahore', 'years' => 8,
          'bio' => "I help students speak English with confidence and score high in IELTS. My classes are interactive — lots of speaking practice, real-life situations and personal feedback.\n\nMore than 300 of my students have achieved IELTS band 7 or above for study and immigration abroad.",
          'skills' => 'IELTS Preparation, Spoken English, Grammar, Academic Writing, Public Speaking, Pronunciation', 'languages' => 'English, Urdu, Punjabi',
          'education' => [['degree' => 'MA English Language & Literature', 'institute' => 'Kinnaird College, Lahore', 'year' => '2016', 'detail' => 'Gold Medalist'], ['degree' => 'BA (Hons) English', 'institute' => 'Lahore College for Women University', 'year' => '2014', 'detail' => '']],
          'experience' => [['role' => 'Lead IELTS Trainer', 'org' => 'British Learning Centre, Lahore', 'period' => '2019 – Present', 'detail' => 'Run IELTS Academic and General batches; designed the mock-test programme.'], ['role' => 'English Lecturer', 'org' => 'Punjab Group of Colleges', 'period' => '2016 – 2019', 'detail' => 'Taught FA/FSc English and ran the college debating society.']],
          'certifications' => [['name' => 'CELTA', 'issuer' => 'Cambridge English', 'year' => '2018'], ['name' => 'IELTS Teacher Training', 'issuer' => 'British Council', 'year' => '2019'], ['name' => 'TESOL Certificate', 'issuer' => 'Arizona State University', 'year' => '2021']],
          'achievements' => "IELTS band 8.5 (personal score)\n300+ students achieved band 7+\nJudge at the All Pakistan Inter-College Debates 2023",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'IELTS Preparation Complete Course', 'cat' => 'Languages', 'fee' => 8000, 'color' => '#db2777',
          'desc' => 'All four IELTS modules — Listening, Reading, Writing and Speaking — with strategies, model answers and full mock tests.',
          'lessons' => [['IELTS overview & band scores', 'How the test works, how it is marked, and how to plan your preparation.'], ['Writing Task 2: essay structure', 'A simple four-paragraph structure that works for every essay question.'], ['Speaking Part 2: the cue card', 'How to speak fluently for two minutes using the PPF method.']],
          'quiz' => ['Grammar Warm-up', [['Choose the correct sentence:', 'She don\'t like tea', 'She doesn\'t likes tea', 'She doesn\'t like tea', 'She not like tea', 'c'], ['IELTS Speaking has how many parts?', '2', '3', '4', '5', 'b'], ['Synonym of "rapid":', 'slow', 'quick', 'late', 'heavy', 'b']]]],
      ],
      [
        'name' => 'Usman Tariq', 'email' => 'usman.tariq@demo.lms', 'phone' => '0333-7778899',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618570209/render.png?token=exp=1791331200~hmac=dbb2ca59bf8f830b8dc52432f675cf5fa336072a15119809e03d35f4775942f4',
        'p' => [
          'headline' => 'Full-Stack Web Developer · Programming Instructor', 'city' => 'Islamabad', 'years' => 6,
          'bio' => "I build web applications for a living and teach what I use every day. My courses are 100% practical: you write real code from lesson one and finish with projects you can show to clients and employers.\n\nI also mentor students starting freelancing careers on Upwork and Fiverr.",
          'skills' => 'HTML, CSS, JavaScript, PHP, Laravel, React, MySQL, Git, Freelancing', 'languages' => 'English, Urdu',
          'education' => [['degree' => 'BS Computer Science', 'institute' => 'FAST NUCES, Islamabad', 'year' => '2019', 'detail' => 'Final-year project: online exam system']],
          'experience' => [['role' => 'Senior Web Developer', 'org' => 'TechNest Solutions', 'period' => '2021 – Present', 'detail' => 'Lead developer on e-commerce and school-management platforms for local and international clients.'], ['role' => 'Programming Instructor (part-time)', 'org' => 'DigiSkills Training Centre', 'period' => '2020 – Present', 'detail' => 'Taught web development to 1,000+ students in evening batches.'], ['role' => 'Junior Developer', 'org' => 'CodeCraft Pvt Ltd', 'period' => '2019 – 2021', 'detail' => 'Built WordPress and Laravel websites.']],
          'certifications' => [['name' => 'Meta Front-End Developer', 'issuer' => 'Meta / Coursera', 'year' => '2022'], ['name' => 'Laravel Certified Developer', 'issuer' => 'Laravel', 'year' => '2023']],
          'achievements' => "Top Rated freelancer on Upwork with 100% job success\n1,000+ students trained in web development\nWinner — Islamabad Hackathon 2019",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Web Development from Zero', 'cat' => 'Programming', 'fee' => 10000, 'color' => '#059669',
          'desc' => 'Learn HTML, CSS and JavaScript step by step and build three real websites, then publish them online.',
          'lessons' => [['How websites work', 'Browsers, servers, domains and hosting explained simply.'], ['Your first HTML page', 'Headings, paragraphs, links, images and lists — build your first page.'], ['Styling with CSS', 'Colours, fonts, spacing and Flexbox to make your page look professional.']],
          'quiz' => ['Web Basics', [['HTML stands for:', 'HyperText Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink Text Making Language', 'a'], ['Which language styles a web page?', 'HTML', 'PHP', 'CSS', 'SQL', 'c'], ['Which tag makes a link?', '<p>', '<a>', '<img>', '<div>', 'b']]]],
      ],
    ];
    $log = [];
    foreach ($T as $t) {
        $uid = (int)val('SELECT id FROM users WHERE email=?', [$t['email']]);
        if (!$uid) {
            q('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,"teacher")', [$t['name'], $t['email'], $t['phone'], password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)]);
            $uid = (int)db()->lastInsertId();
        }
        $old = teacher_profile($uid);
        $photo = $old['photo'] ?: demo_fetch_photo($t['photo']);
        $p = $t['p'];
        q('REPLACE INTO teacher_profiles(user_id,photo,headline,bio,city,years,skills,languages,education,experience,certifications,achievements,linkedin,website,youtube,public) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)', [
            $uid, $photo, $p['headline'], $p['bio'], $p['city'], $p['years'], $p['skills'], $p['languages'],
            json_encode($p['education'], JSON_UNESCAPED_UNICODE), json_encode($p['experience'], JSON_UNESCAPED_UNICODE), json_encode($p['certifications'], JSON_UNESCAPED_UNICODE),
            $p['achievements'], $p['linkedin'], $p['website'], $p['youtube']]);
        $c = $t['course'];
        if (!val('SELECT id FROM courses WHERE teacher_id=? AND title=?', [$uid, $c['title']])) {
            q('INSERT INTO courses(title,description,category_id,teacher_id,fee,color,published) VALUES(?,?,?,?,?,?,1)', [$c['title'], $c['desc'], $cat($c['cat']), $uid, $c['fee'], $c['color']]);
            $cid = (int)db()->lastInsertId();
            foreach ($c['lessons'] as $i => [$lt, $lc]) q('INSERT INTO lessons(course_id,title,content,sort) VALUES(?,?,?,?)', [$cid, $lt, $lc, $i + 1]);
            q('INSERT INTO quizzes(course_id,title,pass_percent) VALUES(?,?,60)', [$cid, $c['quiz'][0]]);
            $qid = (int)db()->lastInsertId();
            foreach ($c['quiz'][1] as $qq) q('INSERT INTO questions(quiz_id,question,a,b,c,d,answer) VALUES(?,?,?,?,?,?,?)', [$qid, ...$qq]);
        }
        $log[] = $t['name'] . ($photo ? '' : ' (photo could not be downloaded)');
    }
    return $log;
}
