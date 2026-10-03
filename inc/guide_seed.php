<?php
// Demo study material: homeschool + trainer courses with guide lessons and printable PDFs. Idempotent.
function guide_seed(): array {
    $g = fn($f) => abs_url('assets/guides/' . $f);
    $uid = fn($email) => (int)val('SELECT id FROM users WHERE email=?', [$email]) ?: null;
    $cat = function (string $n) { return (int)(val('SELECT id FROM categories WHERE name=?', [$n]) ?: (q('INSERT INTO categories(name) VALUES(?)', [$n]) ? db()->lastInsertId() : 0)); };
    $C = [
      ['Homeschool Grade 1 — English & Maths', 'homeschool', 'Grade 1 · Age 6–7', 3000, '#f59e0b', $uid('rabia.khan@demo.lms'), 'Kids',
       'A complete, parent-friendly Grade 1 programme for homeschooling: phonics, reading, numbers, addition and subtraction — with printable worksheets and a daily routine guide.',
       [
        ['Start here: how to homeschool with us', "Welcome! This short guide shows you how to set up a learning corner, a simple daily routine and how to use this app.\n\nDownload the **Parent Guide** and the **Weekly Planner** below and print them. Stick the planner on the wall.\n\nEach lesson has:\n• a short explanation for you\n• an activity to do together\n• a worksheet to print", 'homeschool-parent-guide.pdf'],
        ['Weekly planner (printable)', "Plan the week in 10 minutes every Sunday. Write which lesson and which task-book pages you will do each day. Tick off each box when done — children love seeing their progress.", 'weekly-homeschool-planner.pdf'],
        ['Phonics: letter sounds A to L', "Today we learn the SOUNDS of letters, not their names. 'a' says /a/ as in apple.\n\nActivity:\n1. Say each sound and do an action (a = tap your arm like an ant).\n2. Find 3 things at home that start with that sound.\n3. Trace the letters on page 1 of the English worksheets.\n\nTip: practise 10 minutes a day rather than one long session.", 'grade1-english-worksheets.pdf'],
        ['Reading: sight words & short sentences', "Sight words are common words children learn to recognise instantly: the, and, is, you, we, see.\n\nActivity:\n1. Write each word on a card.\n2. Play 'flash and say' — show the card for 3 seconds.\n3. Read the short sentences on page 2 of the worksheets and draw pictures.", 'grade1-english-worksheets.pdf'],
        ['Numbers & counting to 20', "Counting real objects builds number sense.\n\nActivity:\n1. Count spoons, buttons or blocks up to 20.\n2. Group them in tens.\n3. Complete 'Count the dots' and 'Missing numbers' in the Maths worksheets.", 'grade1-maths-worksheets.pdf'],
        ['Addition & subtraction within 10', "Use objects first, then pictures, then numbers.\n\nAddition = putting together. Subtraction = taking away.\n\nActivity:\n1. 3 apples + 2 apples — how many?\n2. 7 sweets, eat 3 — how many left?\n3. Solve the Add and Subtract sections, then the word problems.\n\nThe answer key for parents is at the end of the worksheet.", 'grade1-maths-worksheets.pdf'],
       ],
       ['Grade 1 Check-up', [['Which word starts with the /b/ sound?', 'cat', 'ball', 'sun', 'map', 'b'], ['What is 4 + 3?', '6', '7', '8', '5', 'b'], ['Which is a sight word?', 'the', 'zebra', 'elephant', 'rocket', 'a'], ['What is 9 − 4?', '5', '4', '6', '3', 'a'], ['How many sides does a triangle have?', '2', '3', '4', '5', 'b']]]],
      ['Train the Trainer — Level 1', 'trainer', 'For new & aspiring teachers', 15000, '#0ea5e9', $uid('ayesha.siddiqui@demo.lms'), 'Teacher Training',
       'Become a confident, certified trainer: the trainer\'s role, lesson planning with the 5E model, classroom management, assessment & feedback, and working with parents. Includes the handbook and a lesson plan template.',
       [
        ['Programme overview & handbook', "Welcome to Train the Trainer. Download the handbook — we follow it chapter by chapter.\n\nTo earn the **Certified Trainer** certificate you must:\n1. Complete every lesson\n2. Pass the final quiz (70%)\n3. Submit a lesson plan and deliver one observed micro-teaching session", 'trainer-handbook-sample.pdf'],
        ['The role of a trainer', "A trainer is a facilitator, coach and role model.\n\nReflect: think of the best teacher you ever had. What did they DO that helped you learn? Write 3 behaviours and how you will use them.", 'trainer-handbook-sample.pdf'],
        ['Planning lessons with the 5E model', "Engage → Explore → Explain → Elaborate → Evaluate.\n\nTask: use the Lesson Plan Template to plan a 30-minute lesson on a topic you know well. Keep teacher talk under 40% of the time.", 'lesson-plan-template.pdf'],
        ['Classroom management', "Set 3–4 clear rules on day one, plan your transitions, use learners' names and praise good behaviour publicly.\n\nScenario: two learners keep talking during your explanation. What do you do first, second and third?", ''],
        ['Assessment & giving feedback', "Check understanding often: quick quizzes, thumbs up/down, exit tickets.\n\nUse the feedback sandwich: what went well → one improvement → encouragement.", 'trainer-handbook-sample.pdf'],
        ['Micro-teaching task', "Record or deliver a 10-minute lesson using your 5E plan. Your trainer will observe and give feedback using the checklist in the handbook. Submit your lesson plan in Assignments.", 'lesson-plan-template.pdf'],
       ],
       ['Trainer Final Quiz', [['In the 5E model, which stage comes first?', 'Explain', 'Engage', 'Evaluate', 'Explore', 'b'], ['Good feedback should be…', 'General and long', 'Specific and actionable', 'Only negative', 'Given once a year', 'b'], ['A trainer is mainly a…', 'Lecturer', 'Facilitator', 'Examiner', 'Manager', 'b'], ['Best way to set class rules?', 'Many rules, no explanation', 'Agree 3–4 clear rules on day one', 'No rules', 'Change them daily', 'b']]]],
    ];
    $made = 0; $ids = [];
    foreach ($C as [$title, $prog, $level, $fee, $color, $tid, $catn, $desc, $lessons, $quiz]) {
        $cid = (int)val('SELECT id FROM courses WHERE title=?', [$title]);
        if (!$cid) { q('INSERT INTO courses(title,description,category_id,teacher_id,fee,color,published,program,level) VALUES(?,?,?,?,?,?,1,?,?)', [$title, $desc, $cat($catn), $tid, $fee, $color, $prog, $level]); $cid = (int)db()->lastInsertId(); $made++; }
        foreach ($lessons as $i => [$lt, $txt, $pdf]) if (!val('SELECT id FROM lessons WHERE course_id=? AND title=?', [$cid, $lt])) q('INSERT INTO lessons(course_id,title,content,attachment_url,sort) VALUES(?,?,?,?,?)', [$cid, $lt, $txt, $pdf ? $g($pdf) : '', $i + 1]);
        if (!val('SELECT id FROM quizzes WHERE course_id=? AND title=?', [$cid, $quiz[0]])) { q('INSERT INTO quizzes(course_id,title,pass_percent) VALUES(?,?,70)', [$cid, $quiz[0]]); $qid = (int)db()->lastInsertId(); foreach ($quiz[1] as $qq) q('INSERT INTO questions(quiz_id,question,a,b,c,d,answer) VALUES(?,?,?,?,?,?,?)', [$qid, ...$qq]); }
        $ids[$prog] = $cid;
    }
    // Study guides in existing demo courses
    $extra = [['IELTS Preparation Complete Course', '📘 Study guide: Writing Task 2', "Download the Writing Task 2 guide: structure, useful phrases, a checklist and a practice question. Print it and keep it next to you while you practise.", 'ielts-writing-task2-guide.pdf'],
              ['Python Programming for Beginners', '📘 Python cheat sheet', "A one-page reference for print, variables, input, if/else, loops, lists, functions and common errors. Print it and keep it beside your computer.", 'python-cheatsheet.pdf'],
              ['Phonics & Early Reading for Kids', '📘 Printable phonics worksheets', "Tracing, missing letters, sight words and read-and-draw activities. Print one page a day.", 'grade1-english-worksheets.pdf']];
    $added = 0;
    foreach ($extra as [$ct, $lt, $txt, $pdf]) if (($cid = (int)val('SELECT id FROM courses WHERE title=?', [$ct])) && !val('SELECT id FROM lessons WHERE course_id=? AND title=?', [$cid, $lt])) { q('INSERT INTO lessons(course_id,title,content,attachment_url,sort) VALUES(?,?,?,?,0)', [$cid, $lt, $txt, $g($pdf)]); $added++; }
    // Link demo shop products & give digital products real PDFs
    foreach (['Grade 1 English Task Book', 'Grade 2 Maths Task Book', 'Phonics Flash Cards (52 cards)', 'Printable Worksheets Pack — Grades 1–3 (PDF)', 'Weekly Homeschool Planner (PDF)'] as $t) q('UPDATE products SET course_id=? WHERE title=? AND course_id IS NULL', [$ids['homeschool'], $t]);
    q('UPDATE products SET course_id=? WHERE title="Train the Trainer Handbook"', [$ids['trainer']]);
    foreach (['Printable Worksheets Pack — Grades 1–3 (PDF)' => 'grade1-english-worksheets.pdf', 'Weekly Homeschool Planner (PDF)' => 'weekly-homeschool-planner.pdf'] as $t => $f) {
        if (!($pid = (int)val('SELECT id FROM products WHERE title=?', [$t]))) continue;
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0750, true);
        $fn = 'guide-' . $pid . '-' . $f; @copy(__DIR__ . '/../assets/guides/' . $f, UPLOAD_DIR . '/' . $fn); q('UPDATE products SET file=? WHERE id=?', [$fn, $pid]);
    }
    return [$made, $added];
}
