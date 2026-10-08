<?php
// Demo data: 3 teachers with full CVs, photos, a course each with lessons & a quiz.
// Idempotent: keyed by demo emails, safe to run twice.
$GLOBALS['demo_err'] = [];
function demo_get(string $url) {
    $data = false; $err = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/png,image/*,*/*;q=0.8', 'Referer: https://www.magnific.com/']]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($data === false) $err = curl_error($ch); elseif ($code !== 200) { $err = "HTTP $code"; $data = false; }
        curl_close($ch);
    }
    if ($data === false && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 40, 'header' => "User-Agent: Mozilla/5.0\r\nReferer: https://www.magnific.com/\r\n"]]);
        $d = @file_get_contents($url, false, $ctx);
        if ($d !== false) { $data = $d; $err = ''; }
    }
    if ($data === false) $GLOBALS['demo_err'][] = $err ?: 'download blocked';
    elseif (!function_exists('imagecreatefromstring')) { $GLOBALS['demo_err'][] = 'GD extension missing'; return false; }
    return $data;
}

// Magnific links expire; fall back to the copy already stored on the main LMS site
const DEMO_MIRROR = 'https://lmserp.hostingersite.com/';
function demo_get_any(string $url, string $key) {
    $before = count($GLOBALS['demo_err']);
    $d = demo_get($url);
    if (!$d && rtrim(DEMO_MIRROR, '/') !== rtrim(abs_url(''), '/')) { $d = demo_get(DEMO_MIRROR . '?p=demo_img&k=' . rawurlencode($key)); if ($d) array_splice($GLOBALS['demo_err'], $before); }
    return $d;
}
function demo_fetch_photo(string $url, string $key = ''): string {
    $data = demo_get_any($url, $key);
    if (!$data || !($im = @imagecreatefromstring($data))) return '';
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
      [
        'name' => 'Prof. Khalid Mehmood', 'email' => 'khalid.mehmood@demo.lms', 'phone' => '0301-2223344',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618683592/render.png?token=exp=1791331200~hmac=ffcbc067e879faeaa085053d6ba50058b9c277b10495d73d347f94ac3778bbdf',
        'p' => [
          'headline' => 'Physics Professor · FSc, A Level & ECAT Physics', 'city' => 'Rawalpindi', 'years' => 22,
          'bio' => "For over two decades I have taught physics the way it should be learned — through everyday examples, simple experiments and lots of numericals.\n\nMy students consistently top FSc board exams and secure seats in engineering universities through ECAT and NUST.",
          'skills' => 'Mechanics, Electricity & Magnetism, Waves & Optics, Modern Physics, Numericals, ECAT Prep', 'languages' => 'English, Urdu, Punjabi',
          'education' => [['degree' => 'MPhil Physics', 'institute' => 'Quaid-i-Azam University, Islamabad', 'year' => '2002', 'detail' => 'Thesis in solid-state physics'], ['degree' => 'MSc Physics', 'institute' => 'University of the Punjab', 'year' => '2000', 'detail' => 'First Division']],
          'experience' => [['role' => 'Associate Professor of Physics', 'org' => 'Government Gordon College, Rawalpindi', 'period' => '2008 – Present', 'detail' => 'Teach FSc and BS Physics; supervise the physics laboratory.'], ['role' => 'Physics Lecturer', 'org' => 'Fazaia Degree College', 'period' => '2003 – 2008', 'detail' => 'Taught FSc Part I & II and prepared students for board exams.']],
          'certifications' => [['name' => 'Professional Development for College Teachers', 'issuer' => 'Higher Education Commission', 'year' => '2012']],
          'achievements' => "Students achieved top-10 positions in Rawalpindi Board 6 times\nAuthor of 'FSc Physics Numericals Solved'\n22 years of college teaching",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'FSc Physics Part 1 — Complete', 'cat' => 'Science', 'fee' => 7000, 'color' => '#7c3aed',
          'desc' => 'All chapters of FSc Part 1 Physics with concepts, solved numericals and past-paper practice for board exams.',
          'lessons' => [['Measurements & vectors', 'SI units, significant figures, errors, vector addition and the dot and cross products.'], ['Motion & force', "Equations of motion, Newton's laws, momentum and projectile motion with solved numericals."], ['Work, energy & power', 'Work–energy theorem, conservation of energy, power and efficiency.']],
          'quiz' => ['Physics Basics', [['SI unit of force:', 'Joule', 'Newton', 'Watt', 'Pascal', 'b'], ['Rate of change of velocity is:', 'Speed', 'Acceleration', 'Momentum', 'Force', 'b'], ['Unit of power:', 'Watt', 'Volt', 'Ampere', 'Ohm', 'a']]]],
      ],
      [
        'name' => 'Dr. Sana Fatima', 'email' => 'sana.fatima@demo.lms', 'phone' => '0322-3334455',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618683599/render.png?token=exp=1791331200~hmac=3e73f0c7e9f7eaa3f91acd5059da2020b8aebb07df4f72ba7815d99183f92f13',
        'p' => [
          'headline' => 'Chemistry Teacher · PhD · FSc, O/A Level & MDCAT Chemistry', 'city' => 'Lahore', 'years' => 10,
          'bio' => "I make chemistry logical instead of a subject of memorisation. Reactions, mechanisms and calculations are taught with clear patterns and colourful diagrams.\n\nI have helped hundreds of pre-medical and pre-engineering students improve their chemistry scores in boards and MDCAT.",
          'skills' => 'Organic Chemistry, Physical Chemistry, Inorganic Chemistry, Stoichiometry, MDCAT Prep, Lab Safety', 'languages' => 'English, Urdu',
          'education' => [['degree' => 'PhD Chemistry', 'institute' => 'University of the Punjab', 'year' => '2019', 'detail' => 'Research in green synthesis'], ['degree' => 'MSc Chemistry', 'institute' => 'GC University, Lahore', 'year' => '2013', 'detail' => 'Gold Medalist']],
          'experience' => [['role' => 'Assistant Professor of Chemistry', 'org' => 'Lahore College for Women University', 'period' => '2019 – Present', 'detail' => 'Teach organic chemistry and supervise undergraduate research.'], ['role' => 'Chemistry Teacher', 'org' => 'Beaconhouse School System', 'period' => '2014 – 2019', 'detail' => 'Taught O/A Level Chemistry with consistent A grades.']],
          'certifications' => [['name' => 'Laboratory Safety Certification', 'issuer' => 'American Chemical Society', 'year' => '2017'], ['name' => 'Cambridge Chemistry Examiner Training', 'issuer' => 'Cambridge Assessment', 'year' => '2016']],
          'achievements' => "8 research papers in international journals\nBest Young Researcher Award 2020\n200+ students scored 85%+ in MDCAT chemistry",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Organic Chemistry Made Easy', 'cat' => 'Science', 'fee' => 6500, 'color' => '#0d9488',
          'desc' => 'Hydrocarbons, functional groups, reaction mechanisms and naming — simplified with patterns and practice MCQs.',
          'lessons' => [['Introduction & IUPAC naming', 'How to name alkanes, alkenes and alkynes step by step.'], ['Hydrocarbons', 'Properties and reactions of alkanes, alkenes and benzene.'], ['Functional groups', 'Alcohols, aldehydes, ketones and carboxylic acids — identify and compare.']],
          'quiz' => ['Organic Basics', [['General formula of alkanes:', 'CnH2n', 'CnH2n+2', 'CnH2n-2', 'CnHn', 'b'], ['Functional group of alcohols:', '-COOH', '-OH', '-CHO', '-NH2', 'b'], ['Benzene has how many carbon atoms?', '4', '5', '6', '8', 'c']]]],
      ],
      [
        'name' => 'Dr. Hira Naveed', 'email' => 'hira.naveed@demo.lms', 'phone' => '0345-5556677',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618684299/render.png?token=exp=1791331200~hmac=cfb783d0c8bb2e84088e44d534a20b602185421e8c8184ceacbc41506f56045e',
        'p' => [
          'headline' => 'Medical Doctor · Biology & MDCAT Tutor', 'city' => 'Faisalabad', 'years' => 5,
          'bio' => "As a practising doctor who cleared MDCAT with a top score, I know exactly what the test demands. I teach biology with diagrams, mnemonics and high-yield MCQ practice.\n\nMy goal is to help every future doctor get into the medical college of their dreams.",
          'skills' => 'Human Physiology, Cell Biology, Genetics, MDCAT Biology, Mnemonics, MCQ Strategy', 'languages' => 'English, Urdu, Punjabi',
          'education' => [['degree' => 'MBBS', 'institute' => 'Punjab Medical College, Faisalabad', 'year' => '2020', 'detail' => 'Distinction in Physiology'], ['degree' => 'FSc Pre-Medical', 'institute' => 'Punjab College', 'year' => '2014', 'detail' => '1045 / 1100']],
          'experience' => [['role' => 'Medical Officer', 'org' => 'Allied Hospital, Faisalabad', 'period' => '2021 – Present', 'detail' => 'Medical officer in internal medicine.'], ['role' => 'MDCAT Biology Instructor', 'org' => 'KIPS Academy (part-time)', 'period' => '2020 – Present', 'detail' => 'Teach biology to MDCAT batches of 100+ students.']],
          'certifications' => [['name' => 'Basic Life Support (BLS)', 'issuer' => 'American Heart Association', 'year' => '2022']],
          'achievements' => "MDCAT top-50 scorer in Punjab\n150+ students admitted to medical colleges\nCreator of the 'Biology in 100 Diagrams' notes",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'MDCAT Biology Crash Course', 'cat' => 'Science', 'fee' => 9000, 'color' => '#16a34a',
          'desc' => 'High-yield MDCAT biology: cell biology, human physiology and genetics with diagrams, mnemonics and timed MCQ sessions.',
          'lessons' => [['Cell structure & function', 'Organelles, cell membrane transport and the cell cycle.'], ['Human digestive & circulatory systems', 'Organs, enzymes, the heart and blood vessels with labelled diagrams.'], ['Genetics essentials', "Mendel's laws, dominant and recessive traits, Punnett squares."]],
          'quiz' => ['Biology MCQs', [['Powerhouse of the cell:', 'Nucleus', 'Mitochondria', 'Ribosome', 'Golgi body', 'b'], ['Number of chambers in the human heart:', '2', '3', '4', '5', 'c'], ['DNA is found mainly in the:', 'Cytoplasm', 'Nucleus', 'Cell wall', 'Vacuole', 'b']]]],
      ],
      [
        'name' => 'Professor Tariq Jameel', 'email' => 'tariq.jameel@demo.lms', 'phone' => '0300-6667788',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618684745/render.png?token=exp=1791331200~hmac=447383dd5fad5f86f8860d5a1a708c65ab116dc0e06ec440273c0b295d1d9897',
        'p' => [
          'headline' => 'Urdu Language & Literature Teacher', 'city' => 'Hyderabad', 'years' => 28,
          'bio' => "Urdu is not just a subject, it is our identity. For 28 years I have taught Urdu grammar, essay writing and poetry so students can both score well and enjoy the beauty of the language.\n\nI specialise in board exam preparation, CSS/PMS Urdu and creative writing.",
          'skills' => 'Urdu Grammar, Essay Writing, Ghazal & Nazm, Letter Writing, CSS Urdu, Translation', 'languages' => 'Urdu, English, Sindhi',
          'education' => [['degree' => 'MA Urdu', 'institute' => 'University of Sindh, Jamshoro', 'year' => '1996', 'detail' => 'First position'], ['degree' => 'B.Ed', 'institute' => 'Allama Iqbal Open University', 'year' => '1998', 'detail' => '']],
          'experience' => [['role' => 'Head of Urdu Department', 'org' => 'Government College Hyderabad', 'period' => '2010 – Present', 'detail' => 'Lead a department of 8 teachers; organise annual mushaira and essay competitions.'], ['role' => 'Urdu Lecturer', 'org' => 'Government College Hyderabad', 'period' => '1998 – 2010', 'detail' => 'Taught intermediate and degree classes.']],
          'certifications' => [['name' => 'Teacher Training Certificate', 'issuer' => 'Sindh Education Department', 'year' => '1999']],
          'achievements' => "Published poetry collection 'Harf-e-Shauq'\nSindh Best Teacher Award 2015\nJudge at national Urdu debates and mushairas",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Urdu Grammar & Essay Writing', 'cat' => 'Languages', 'fee' => 3500, 'color' => '#b45309',
          'desc' => 'Urdu grammar, essay and letter writing, and poetry explanation for matric, intermediate and competitive exams.',
          'lessons' => [['Urdu grammar basics (Qawaid)', 'Ism, fail, harf, tazkeer-o-taneez and common mistakes.'], ['Essay writing (Mazmoon Nawesi)', 'Structure, introduction, body with quotes and couplets, and conclusion.'], ['Poetry explanation (Tashreeh)', 'How to explain a couplet with context, meaning and literary devices.']],
          'quiz' => ['Urdu Basics', [['Who wrote "Bang-e-Dra"?', 'Mirza Ghalib', 'Allama Iqbal', 'Faiz Ahmed Faiz', 'Mir Taqi Mir', 'b'], ['A ghazal is made of:', 'Paragraphs', 'Couplets (ashaar)', 'Chapters', 'Dialogues', 'b'], ['Urdu is written from:', 'Left to right', 'Right to left', 'Top to bottom', 'Either way', 'b']]]],
      ],
      [
        'name' => 'Faisal Qureshi, ACA', 'email' => 'faisal.qureshi@demo.lms', 'phone' => '0333-8889900',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618685627/render.png?token=exp=1791331200~hmac=ecb8ff6b9106292bc7d61305c3e091b3e9a7fded3c8ab51c716172ccbdf36ec3',
        'p' => [
          'headline' => 'Chartered Accountant · Accounting, Finance & Tax Trainer', 'city' => 'Karachi', 'years' => 15,
          'bio' => "I am a chartered accountant with 15 years of audit and industry experience. I teach accounting the practical way — the same skills used in real companies.\n\nMy students prepare for ICOM, B.Com, CA Foundation and real jobs in accounts departments.",
          'skills' => 'Financial Accounting, Cost Accounting, Taxation, QuickBooks, Excel for Finance, Auditing', 'languages' => 'English, Urdu',
          'education' => [['degree' => 'Chartered Accountant (ACA)', 'institute' => 'ICAP — Institute of Chartered Accountants of Pakistan', 'year' => '2012', 'detail' => ''], ['degree' => 'B.Com (Hons)', 'institute' => 'University of Karachi', 'year' => '2007', 'detail' => 'Distinction']],
          'experience' => [['role' => 'Finance Manager', 'org' => 'Al-Noor Textiles Ltd', 'period' => '2016 – Present', 'detail' => 'Manage financial reporting, budgeting and tax compliance.'], ['role' => 'Audit Senior', 'org' => 'A. F. Ferguson & Co. (PwC)', 'period' => '2009 – 2016', 'detail' => 'Led statutory audits of listed companies.']],
          'certifications' => [['name' => 'QuickBooks Certified User', 'issuer' => 'Intuit', 'year' => '2018'], ['name' => 'Certified Tax Practitioner', 'issuer' => 'FBR Training', 'year' => '2017']],
          'achievements' => "Qualified CA in first attempt\nTrained 500+ accounting students\nSpeaker at ICAP student conventions",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Practical Accounting for Beginners', 'cat' => 'Business', 'fee' => 7500, 'color' => '#1e3a8a',
          'desc' => 'Double-entry bookkeeping, journals, ledgers, trial balance and final accounts — with Excel and QuickBooks practice.',
          'lessons' => [['Accounting equation & double entry', 'Assets = Liabilities + Equity; debit and credit rules with examples.'], ['Journal, ledger & trial balance', 'Recording transactions, posting to ledgers and balancing the trial balance.'], ['Final accounts', 'Income statement and balance sheet with adjustments.']],
          'quiz' => ['Accounting Basics', [['Accounting equation:', 'Assets = Liabilities + Equity', 'Assets = Income − Expenses', 'Equity = Assets + Liabilities', 'Liabilities = Assets + Equity', 'a'], ['Cash received is recorded as a:', 'Debit to cash', 'Credit to cash', 'Debit to capital', 'No entry', 'a'], ['Trial balance checks that:', 'Profit is correct', 'Debits equal credits', 'Tax is paid', 'Cash is positive', 'b']]]],
      ],
      [
        'name' => 'Maulana Abdul Rehman', 'email' => 'abdul.rehman@demo.lms', 'phone' => '0312-1212121',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618685678/render.png?token=exp=1791331200~hmac=e5b9b644746621c98f44eaed82959caba7202b3104a2a7c2c872249ec582c565',
        'p' => [
          'headline' => 'Quran Teacher · Tajweed, Nazra & Islamic Studies', 'city' => 'Multan', 'years' => 18,
          'bio' => "I teach the Holy Quran with correct Tajweed to children and adults, step by step from Noorani Qaida to fluent recitation.\n\nI also teach Islamic Studies for school and college exams with patience and care for every student.",
          'skills' => 'Tajweed, Noorani Qaida, Nazra Quran, Hifz Revision, Islamic Studies, Arabic Basics', 'languages' => 'Urdu, Arabic, English, Saraiki',
          'education' => [['degree' => 'Shahadat-ul-Alamiyah (Dars-e-Nizami)', 'institute' => 'Jamia Khair-ul-Madaris, Multan', 'year' => '2006', 'detail' => 'Equivalent to MA Islamic Studies & Arabic'], ['degree' => 'Hifz-ul-Quran', 'institute' => 'Jamia Khair-ul-Madaris, Multan', 'year' => '1998', 'detail' => '']],
          'experience' => [['role' => 'Senior Quran Teacher', 'org' => 'Dar-ul-Quran Academy (online & on-site)', 'period' => '2012 – Present', 'detail' => 'Teach Tajweed and Nazra to students in Pakistan, UK and the Gulf.'], ['role' => 'Islamiat Teacher', 'org' => 'Multan Public School', 'period' => '2006 – 2012', 'detail' => 'Taught Islamiat to grades 6–10.']],
          'certifications' => [['name' => 'Ijazah in Qira\'at (Hafs)', 'issuer' => 'Jamia Khair-ul-Madaris', 'year' => '2008']],
          'achievements' => "Taught 1,000+ students to read the Quran with Tajweed\nJudge at district Qirat competitions\n18 years of teaching experience",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Quran with Tajweed — Beginners', 'cat' => 'Islamic Studies', 'fee' => 3000, 'color' => '#047857',
          'desc' => 'Learn to read the Holy Quran correctly — Arabic letters, Noorani Qaida and essential Tajweed rules, for children and adults.',
          'lessons' => [['Arabic letters & makharij', 'Correct pronunciation points (makharij) of all Arabic letters.'], ['Harakat & joining letters', 'Zabar, zer, pesh, tanween, sukoon and shadd with practice words.'], ['Basic Tajweed rules', 'Noon sakin and tanween rules, madd, and qalqalah with examples.']],
          'quiz' => ['Tajweed Basics', [['How many letters are in the Arabic alphabet?', '26', '28', '30', '32', 'b'], ['Qalqalah letters are:', 'ق ط ب ج د', 'ا و ي', 'م ن', 'ل ر', 'a'], ['Tanween means:', 'Double vowel sound ending in "n"', 'A long vowel', 'A silent letter', 'A pause sign', 'a']]]],
      ],
      [
        'name' => 'Mrs. Rabia Khan', 'email' => 'rabia.khan@demo.lms', 'phone' => '0321-9090909',
        'photo' => 'https://pikaso.cdnpk.net/private/production/5618686241/render.png?token=exp=1791331200~hmac=10c5c8cc3fe642974261ac0a7020eca18a29dd0f47e1d8a3b89a5ca8b97cc54b',
        'p' => [
          'headline' => 'Early Years & Primary Teacher · Phonics and Kids Learning', 'city' => 'Islamabad', 'years' => 9,
          'bio' => "Little learners learn best through play, songs and stories. I teach phonics, reading, basic maths and good habits to children aged 3–8 in a fun, caring way.\n\nParents receive regular progress updates and simple activities to do at home.",
          'skills' => 'Phonics (Jolly Phonics), Early Reading, Montessori Methods, Basic Maths, Storytelling, Classroom Management', 'languages' => 'English, Urdu',
          'education' => [['degree' => 'M.Ed (Early Childhood Education)', 'institute' => 'Allama Iqbal Open University', 'year' => '2016', 'detail' => ''], ['degree' => 'Montessori Diploma', 'institute' => 'Pakistan Montessori Council', 'year' => '2014', 'detail' => '']],
          'experience' => [['role' => 'Primary Section Coordinator', 'org' => 'Roots Millennium School, Islamabad', 'period' => '2019 – Present', 'detail' => 'Coordinate KG–Grade 2 teachers and curriculum.'], ['role' => 'Montessori Teacher', 'org' => 'Little Stars Montessori', 'period' => '2015 – 2019', 'detail' => 'Taught playgroup and KG classes.']],
          'certifications' => [['name' => 'Jolly Phonics Trainer', 'issuer' => 'Jolly Learning', 'year' => '2018'], ['name' => 'Child Safeguarding', 'issuer' => 'UNICEF Online', 'year' => '2021']],
          'achievements' => "Best Primary Teacher Award 2022\nDesigned a phonics programme used by 5 schools\nConducted 30+ parent workshops",
          'linkedin' => '', 'website' => '', 'youtube' => ''],
        'course' => ['title' => 'Phonics & Early Reading for Kids', 'cat' => 'Kids', 'fee' => 2500, 'color' => '#f59e0b',
          'desc' => 'A fun phonics course for ages 4–7: letter sounds, blending, sight words and first stories — with activities for parents.',
          'lessons' => [['Letter sounds A–M', 'Learn the sounds of letters A to M with actions and songs.'], ['Letter sounds N–Z', 'Learn the sounds of letters N to Z and practise with picture cards.'], ['Blending & first words', 'Blend sounds into simple words like c-a-t and s-u-n.']],
          'quiz' => ['Phonics Fun', [['What sound does "b" make?', '/b/ as in ball', '/d/ as in dog', '/p/ as in pen', '/m/ as in moon', 'a'], ['c + a + t makes:', 'cot', 'cat', 'cut', 'kit', 'b'], ['Which word starts with "s"?', 'Moon', 'Sun', 'Dog', 'Ball', 'b']]]],
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
        $photo = $old['photo'] ?: demo_fetch_photo($t['photo'], 't:' . $t['email']);
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

function demo_fetch_cover(string $url, string $key = ''): string {
    $data = demo_get_any($url, $key);
    if (!$data || !($im = @imagecreatefromstring($data))) return '';
    $w = imagesx($im); $h = imagesy($im);
    if ($w > 1280) { $nh = (int)round($h * 1280 / $w); $r = imagecreatetruecolor(1280, $nh); imagecopyresampled($r, $im, 0, 0, 0, 0, 1280, $nh, $w, $h); $im = $r; }
    if (!is_dir(UPLOAD_DIR . '/covers')) mkdir(UPLOAD_DIR . '/covers', 0750, true);
    $name = 'cdemo-' . bin2hex(random_bytes(5)) . '.jpg';
    imagejpeg($im, UPLOAD_DIR . "/covers/$name", 84);
    return $name;
}

// IT department demo: courses by Usman Tariq with lectures, videos, quizzes and cover images
function demo_it_seed(): array {
    $uid = (int)val('SELECT id FROM users WHERE email="usman.tariq@demo.lms"');
    if (!$uid) return ['IT teacher missing'];
    $cid = (int)(val('SELECT id FROM categories WHERE name="Programming"') ?: 0);
    if (!$cid) { q('INSERT INTO categories(name) VALUES("Programming")'); $cid = (int)db()->lastInsertId(); }
    $itc = (int)(val('SELECT id FROM categories WHERE name="IT Skills"') ?: 0);
    if (!$itc) { q('INSERT INTO categories(name) VALUES("IT Skills")'); $itc = (int)db()->lastInsertId(); }
    $C = 'https://pikaso.cdnpk.net/private/production/';
    $courses = [
      ['Web Development from Zero', $cid, 10000, '#059669', $C . '5618662330/render.png?token=exp=1791331200~hmac=3257a613194de5d5501850b586297aa3ab308605a28e37b663372a1159cad9a2',
        'Learn HTML, CSS and JavaScript step by step and build three real websites, then publish them online.',
        [
          ['HTML & CSS full course (video)', 'https://www.youtube.com/watch?v=mU6anWqZJcc', "Watch this complete HTML & CSS lecture. Pause and type every example yourself in VS Code.\n\nWhat you will learn:\n• Page structure: <html>, <head>, <body>\n• Text, links, images and lists\n• CSS selectors, colours, box model and Flexbox\n\nHomework: build a one-page profile about yourself with a photo, short bio and contact links."],
          ['JavaScript basics (video)', 'https://www.youtube.com/watch?v=PkZNo7MFNFg', "JavaScript makes websites interactive.\n\nKey topics:\n• Variables (let, const) and data types\n• If/else conditions and loops\n• Functions and events (click, input)\n• Changing the page with document.querySelector()\n\nHomework: add a dark-mode toggle button to your profile page."],
          ['Project: landing page', '', "Build a landing page for a local business (bakery, salon, tuition centre).\n\nRequired sections:\n1. Header with logo and menu\n2. Hero with headline and button\n3. Services (3 cards)\n4. Testimonials\n5. Contact form and footer\n\nMake it mobile-friendly using Flexbox and media queries."],
          ['Publishing your website', '', "Put your site online for free:\n\n1. Create a GitHub account and a new repository\n2. Upload your index.html, style.css and images\n3. Settings → Pages → choose main branch\n4. Your site goes live at username.github.io/repo\n\nShare the link in the class group for feedback."],
        ],
        ['Web Basics', [['HTML stands for:', 'HyperText Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink Text Making Language', 'a'], ['Which language styles a web page?', 'HTML', 'PHP', 'CSS', 'SQL', 'c'], ['Which tag makes a link?', '<p>', '<a>', '<img>', '<div>', 'b'], ['Which keyword declares a constant in JavaScript?', 'var', 'let', 'const', 'fixed', 'c']]]],
      ['Python Programming for Beginners', $cid, 9000, '#2563eb', $C . '5618661596/render.png?token=exp=1791331200~hmac=90a1875c46067eafceb1b164ae3b2e50f018ef8c47db20ae3a9dff296963b8e9',
        'Start coding with Python — the most popular beginner language. Variables, loops, functions and small real projects. No experience needed.',
        [
          ['Python full course for beginners (video)', 'https://www.youtube.com/watch?v=rfscVS0vtbw', "This lecture covers Python from zero. Install Python from python.org and VS Code before starting.\n\nFocus on the first hour this week:\n• print() and comments\n• Variables and data types\n• Strings and numbers\n• Getting input from the user"],
          ['Conditions & loops', '', "Making decisions and repeating work:\n\nif marks >= 50:\n    print('Pass')\nelse:\n    print('Fail')\n\nfor i in range(1, 11):\n    print(i)\n\nPractice: print the multiplication table of any number the user enters."],
          ['Lists & functions', '', "Lists store many values: fruits = ['apple', 'mango']\n\nFunctions reuse code:\n\ndef area(l, w):\n    return l * w\n\nPractice: write a function that returns the average of a list of marks."],
          ['Mini project: student result calculator', '', "Build a program that:\n1. Asks for a student's name and 5 subject marks\n2. Calculates total, percentage and grade (A/B/C/F)\n3. Prints a neat result card\n\nBonus: save results to a text file."],
        ],
        ['Python Basics', [['Which function prints output in Python?', 'echo()', 'print()', 'write()', 'say()', 'b'], ['What is the result of 10 // 3?', '3.33', '3', '1', '30', 'b'], ['Which symbol starts a comment?', '//', '#', '<!--', '--', 'b'], ['Which keyword defines a function?', 'func', 'function', 'def', 'fn', 'c']]]],
      ['Computer Basics & MS Office', $itc, 4000, '#ea580c', $C . '5618662540/render.png?token=exp=1791331200~hmac=4510980c88e8424bbaee7400a6e7cbb86f485485ca2c7db81f9f972d938072da',
        'Essential computer skills for students and office jobs: Windows, typing, internet, email, Word, Excel and PowerPoint.',
        [
          ['Getting to know your computer', '', "Parts of a computer: CPU, RAM, storage, monitor, keyboard, mouse.\n\nWindows basics:\n• Desktop, Start menu and taskbar\n• Creating, renaming and moving folders\n• Copy (Ctrl+C), Paste (Ctrl+V), Undo (Ctrl+Z)\n\nPractice: create a folder for each subject and organise your files."],
          ['MS Word: professional documents', '', "Learn to make a CV and an application letter:\n• Fonts, headings and paragraph spacing\n• Bullets and numbering\n• Inserting tables and pictures\n• Page margins and printing / Save as PDF"],
          ['MS Excel: marks sheet & formulas', '', "Create a class marks sheet:\n• =SUM(B2:F2) for total\n• =AVERAGE(B2:F2) for average\n• =IF(G2>=50,\"Pass\",\"Fail\") for result\n• Sorting, filters and a simple chart"],
          ['PowerPoint: presenting with confidence', '', "Design a 5-slide presentation:\n• Choose a clean theme\n• One idea per slide, big text, few words\n• Add images and icons\n• Use simple transitions — avoid heavy animations"],
        ],
        ['Computer Basics', [['Shortcut to copy:', 'Ctrl+V', 'Ctrl+C', 'Ctrl+X', 'Ctrl+Z', 'b'], ['Which program is best for calculations?', 'Word', 'PowerPoint', 'Excel', 'Paint', 'c'], ['RAM is:', 'Permanent storage', 'Temporary working memory', 'A printer', 'An input device', 'b']]]],
      ['Graphic Design with Canva & Photoshop', $itc, 7000, '#c026d3', $C . '5618662376/render.png?token=exp=1791331200~hmac=edbe9781b30fd2e4ba2831fd33616968a74575729a2f39da1d4aa00807589f90',
        'Design social media posts, logos, flyers and thumbnails. Learn design principles first, then practical tools.',
        [
          ['Design principles that matter', '', "Good design follows simple rules:\n• Contrast — make important things stand out\n• Alignment — line things up\n• Repetition — consistent colours and fonts\n• White space — don't crowd the design\n\nAssignment: find 3 ads you like and note which principles they use."],
          ['Colours & typography', '', "• Use 2–3 colours maximum; pick from a palette (coolors.co)\n• Pair one heading font with one body font\n• Keep text readable: dark on light or light on dark\n\nPractice: make an Instagram quote post in Canva."],
          ['Logo & brand kit', '', "Create a simple logo for a fictional brand:\n1. Brainstorm keywords for the brand\n2. Sketch 5 ideas on paper\n3. Build the best one in Canva or Photoshop\n4. Export PNG with transparent background"],
          ['Photoshop essentials', '', "Key tools: layers, selection, crop, healing brush, text and adjustment layers.\n\nProject: remove the background from a product photo and place it on a clean social media banner."],
        ],
        ['Design Basics', [['Using too many fonts makes a design:', 'Professional', 'Cluttered', 'Brighter', 'Smaller', 'b'], ['Which file type supports transparent background?', 'JPG', 'PNG', 'BMP', 'DOCX', 'b'], ['Empty space in a design is called:', 'Gap fill', 'White space', 'Margin error', 'Blank layer', 'b']]]],
      ['Digital Marketing & Freelancing', $itc, 8000, '#0891b2', $C . '5618663537/render.png?token=exp=1791331200~hmac=79a8b425a52da825307646591b0e55246ab92a9e89c3fc9284187ac57134e097',
        'Grow businesses online with social media, ads and SEO — then learn to sell your skills on Upwork and Fiverr.',
        [
          ['How digital marketing works', '', "The customer journey: Awareness → Interest → Decision → Action.\n\nChannels: social media, search (SEO), paid ads, email and WhatsApp marketing.\n\nAssignment: pick a local business and list where its customers spend time online."],
          ['Facebook & Instagram marketing', '', "• Set up a business page with a clear profile photo and bio\n• Post consistently: 3–5 times a week\n• Mix content: educational, behind-the-scenes, offers\n• Basics of boosting a post and targeting by city, age and interests"],
          ['SEO basics', '', "Help Google find a website:\n• Choose keywords people actually search\n• Use the keyword in the page title, heading and first paragraph\n• Make the site fast and mobile-friendly\n• Get a Google Business Profile for local customers"],
          ['Start freelancing', '', "1. Pick one skill to sell (design, web, social media)\n2. Build a portfolio with 3 sample projects\n3. Create a Fiverr gig / Upwork profile with a clear title\n4. Send short, personalised proposals\n5. Deliver on time and ask for a review\n\nGet paid in Pakistan via Payoneer or bank transfer."],
        ],
        ['Marketing Basics', [['SEO stands for:', 'Search Engine Optimization', 'Social Engagement Online', 'Sales Email Order', 'Site Edit Option', 'a'], ['First stage of the customer journey:', 'Action', 'Decision', 'Awareness', 'Payment', 'c'], ['A freelancing portfolio shows:', 'Your bank balance', 'Samples of your work', 'Your exam marks', 'Your followers', 'b']]]],
    ];
    $log = [];
    foreach ($courses as [$title, $cat, $fee, $color, $img, $desc, $lessons, $quiz]) {
        $id = (int)val('SELECT id FROM courses WHERE teacher_id=? AND title=?', [$uid, $title]);
        if (!$id) {
            q('INSERT INTO courses(title,description,category_id,teacher_id,fee,color,published) VALUES(?,?,?,?,?,?,1)', [$title, $desc, $cat, $uid, $fee, $color]);
            $id = (int)db()->lastInsertId();
        } else {
            q('UPDATE courses SET description=?,category_id=? WHERE id=?', [$desc, $cat, $id]);
        }
        if (!val('SELECT cover FROM courses WHERE id=?', [$id]) && ($cv = demo_fetch_cover($img, 'c:' . $title))) q('UPDATE courses SET cover=? WHERE id=?', [$cv, $id]);
        foreach ($lessons as $i => [$lt, $vid, $txt]) {
            if (!val('SELECT id FROM lessons WHERE course_id=? AND title=?', [$id, $lt])) q('INSERT INTO lessons(course_id,title,video_url,content,sort) VALUES(?,?,?,?,?)', [$id, $lt, $vid, $txt, 10 + $i]);
        }
        if (!val('SELECT id FROM quizzes WHERE course_id=? AND title=?', [$id, $quiz[0]])) {
            q('INSERT INTO quizzes(course_id,title,pass_percent) VALUES(?,?,60)', [$id, $quiz[0]]);
            $qid = (int)db()->lastInsertId();
            foreach ($quiz[1] as $qq) q('INSERT INTO questions(quiz_id,question,a,b,c,d,answer) VALUES(?,?,?,?,?,?,?)', [$qid, ...$qq]);
        }
        $log[] = $title . (val('SELECT cover FROM courses WHERE id=?', [$id]) ? '' : ' (no cover)');
    }
    return $log;
}

// Attach cover images to demo courses that don't have one yet (also retries failed downloads)
function demo_covers_seed(): int {
    $B = 'https://pikaso.cdnpk.net/private/production/';
    $map = [
        'Mathematics for Entry Tests' => '5618756157/render.png?token=exp=1791331200~hmac=242b533c2bb9c01a15438251971e2a7f78c52fdfcc3f7341fd063175cb9b0826',
        'IELTS Preparation Complete Course' => '5618756173/render.jpg?token=exp=1791331200~hmac=94a9d6ae5c41d082509fc036e0453d2e68f1fbbc37433174ab140f2afbb3c131',
        'FSc Physics Part 1 — Complete' => '5618756666/render.png?token=exp=1791331200~hmac=ad81f4630b630a3e72d72d0ab397601dbe47640c5d85c88c2a940995e9caf4d7',
        'Organic Chemistry Made Easy' => '5618757363/render.png?token=exp=1791331200~hmac=6d479f66e5c00fc594b64b95ec9f66f9b19e954f7eb3da8a1f05c39cfabbeb21',
        'MDCAT Biology Crash Course' => '5618757596/render.png?token=exp=1791331200~hmac=ac0eb0d57add05aacc8e03da04e9adeaa5d3cd789b300dd4c524adbc4ee1e4ad',
        'Urdu Grammar & Essay Writing' => '5618758439/render.png?token=exp=1791331200~hmac=2eeb7686f1d161bdcec92a2c42344376c98d02156921586e2d471ed2dc82549e',
        'Practical Accounting for Beginners' => '5618758407/render.png?token=exp=1791331200~hmac=6dc98c81ef47d0ea561671feb66b0614f84835f5533992d59fea1600f25358d1',
        'Quran with Tajweed — Beginners' => '5618758597/render.png?token=exp=1791331200~hmac=dd01ae9fe65bf82895ef33c917d926cb8c65345ed5ca056907a61416c5c6d1fe',
        'Phonics & Early Reading for Kids' => '5618760031/render.png?token=exp=1791331200~hmac=35811c5920cfacc1b7e92e6da488e1c31f80f02c1ab2c0aa5b34a36e13714d2e',
        'Web Development from Zero' => '5618662330/render.png?token=exp=1791331200~hmac=3257a613194de5d5501850b586297aa3ab308605a28e37b663372a1159cad9a2',
        'Python Programming for Beginners' => '5618661596/render.png?token=exp=1791331200~hmac=90a1875c46067eafceb1b164ae3b2e50f018ef8c47db20ae3a9dff296963b8e9',
        'Computer Basics & MS Office' => '5618662540/render.png?token=exp=1791331200~hmac=4510980c88e8424bbaee7400a6e7cbb86f485485ca2c7db81f9f972d938072da',
        'Graphic Design with Canva & Photoshop' => '5618662376/render.png?token=exp=1791331200~hmac=edbe9781b30fd2e4ba2831fd33616968a74575729a2f39da1d4aa00807589f90',
        'Digital Marketing & Freelancing' => '5618663537/render.png?token=exp=1791331200~hmac=79a8b425a52da825307646591b0e55246ab92a9e89c3fc9284187ac57134e097',
    ];
    $n = 0;
    foreach ($map as $title => $path) {
        foreach (all('SELECT id FROM courses WHERE title=? AND (cover IS NULL OR cover="")', [$title]) as $c) {
            if ($cv = demo_fetch_cover($B . $path, 'c:' . $title)) { q('UPDATE courses SET cover=? WHERE id=?', [$cv, $c['id']]); $n++; }
        }
    }
    return $n;
}
