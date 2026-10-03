<?php
// Help & guides: role-wise step-by-step instructions
$me = user(); $title = 'Help & guides'; $back = '?p=more';
$G = [
 'admin' => ['🛠️ Admin guide', [
   ['Set up your institute', ["More → Settings: enter institute name, phone, email, address and WhatsApp.", "Add payment details (Bank, JazzCash, EasyPaisa) — students see these when paying.", "Set the certificate signer name, shop delivery charge and teacher discount."]],
   ['Add teachers and students', ["People → Teachers → ＋ Add teacher (name, email, password).", "Ask each teacher to fill their CV: More → My teacher profile.", "Students can sign up themselves, or add them in People → Students.", "Open a student → ＋ Add parent login to give parents their own account."]],
   ['Create courses', ["Courses → ＋ New course: title, cover photo, program (Course / Homeschooling / Train the Trainer), grade, fee and teacher.", "Open the course → ＋ Lesson to add videos (YouTube link), notes and PDF links.", "＋ Quiz to add multiple-choice questions."]],
   ['Classes, batches & attendance', ["More → Batches & attendance → ＋ New batch (days, time, room, online link).", "Open the batch → add enrolled students.", "Teachers mark attendance daily; absent students' parents get an alert."]],
   ['Fees', ["Fees → Monthly fee plans: vouchers are created every month automatically.", "Generate one-time vouchers (admission / exam fee) for a whole class.", "Open a student → create installments or give a scholarship %.", "Payment proofs (screenshots) arrive in More → Payment proofs — approve to mark paid."]],
   ['Exams, assignments & certificates', ["Teachers create assignments and grade them in More → Assignments.", "More → Exams: add subjects, enter marks, then Publish — result cards go to students and parents.", "Certificates are issued automatically when a course is completed (or issue manually)."]],
   ['Shop', ["More → Shop products → ＋ Add product (photo, price, stock, or PDF file).", "Link a product to a course so it shows under Required materials.", "New orders appear on Home; update status and courier tracking in Shop orders.", "Select several orders in Shop orders to mark them Shipped or Delivered in one go."]],
   ['Teacher products & payouts', ["Teachers add their own products; approve them in Shop products → Awaiting approval.", "Set the teacher share % in Settings (default 50%).", "More → Teacher sales & payouts: see each teacher's balance and pay in full or in parts."]],
   ['Money & reports', ["Expenses: add costs with categories; set recurring ones like rent and salaries.", "Teacher payroll: set salary rules, generate slips monthly, mark paid (adds to expenses).", "Reports show income vs expenses for the last 6 months."]],
   ['Website & blog', ["Your public website is at the home URL — edit headline and contact in Settings.", "Blog posts: write articles; teachers' articles need your approval.", "Website pages: About, Privacy, Terms, Disclaimer, Contact — required for AdSense."]],
 ]],
 'teacher' => ['👩‍🏫 Teacher guide', [
   ['Your profile', ["More → My teacher profile (CV): add photo, education, experience and skills.", "Your profile appears on the website under Our Teachers."]],
   ['Teach a course', ["Courses → I teach → open your course.", "＋ Lesson: add a YouTube link, notes and attachment link.", "＋ Quiz: add multiple-choice questions with the correct answer."]],
   ['Daily classes', ["Home shows Today's classes — tap to mark attendance.", "After saving, use the WhatsApp buttons to inform absent students."]],
   ['Assignments & exams', ["More → Assignments → ＋ New: set a deadline and total marks; students are notified.", "Open the assignment to see submissions, enter marks and feedback.", "More → Exams → ＋ New exam: add subjects, enter marks, and Publish."]],
   ['Sell your books & material', ["More → My products → ＋ Add product: photo, price, and PDF file or stock.", "Admin approves it, then it goes live in the shop with your name.", "You earn your share of every delivered sale; physical items ship from the institute warehouse.", "More → My sales & earnings shows sales, balance and payouts."]],
   ['Blog & salary', ["Write articles in More → Blog posts → Submit for review.", "See your salary slips in More → My salary."]],
 ]],
 'student' => ['🎒 Student guide', [
   ['Join a course', ["Browse → open a course → Enroll.", "For paid courses, pay using the account details shown and upload the screenshot.", "Your course unlocks once the payment is verified."]],
   ['Learn', ["My Courses → open a course → tap a lesson to watch the video and read notes.", "Tap Complete & next to move forward and track your progress.", "Take quizzes at the end — you can retry."]],
   ['Homework & results', ["More → Assignments: submit your answer or upload a photo of your notebook.", "More → Results: see your result cards when published."]],
   ['Fees, shop & certificate', ["More → My fees: pay vouchers and download receipts.", "Shop: buy task books and materials — pay cash on delivery or online.", "Finish all lessons and pass quizzes to get your certificate 🎓."]],
 ]],
 'parent' => ['👨‍👩‍👧 Parent guide', [
   ['Your dashboard', ["Home shows each child's attendance, latest result, fees due and pending homework.", "You'll get notifications for absences, results, new vouchers and payment approvals."]],
   ['Enroll your child', ["Courses → open a course → Enroll your child → choose the child.", "A fee voucher is created — pay it from Fees."]],
   ['Pay fees', ["Fees → open a voucher → pay by JazzCash, EasyPaisa or bank.", "Upload the payment screenshot — you'll be notified when it's approved.", "Download or print receipts any time."]],
   ['Track progress', ["Home → Results to see result cards.", "Home → Assignments to check homework.", "Home → Attendance for the monthly attendance record."]],
   ['Buy books & materials', ["Shop → add task books or kits to the cart.", "Choose the child, enter the delivery address and pay cash on delivery or online.", "Track your order (Placed → Shipped → Delivered) in My orders."]],
 ]],
];
$show = role('admin') ? array_keys($G) : [$me['role']];
$open = isset($G[get('r')]) && in_array(get('r'), $show, true) ? get('r') : $show[0];
?>
<?php if (count($show) > 1): ?><div class="chips"><?php foreach ($show as $r): ?><a href="?p=help&r=<?= $r ?>" class="<?= $open === $r ? 'on' : '' ?>"><?= $G[$r][0] ?></a><?php endforeach ?></div><?php endif ?>
<div class="hero"><div class="big sm"><?= $G[$open][0] ?></div><div class="muted-l">Step-by-step help for <?= e(setting('institute', APP_NAME)) ?>. Tap a topic to open it.</div></div>
<?php foreach ($G[$open][1] as $i => [$h, $steps]): ?>
<details class="card guide" <?= $i === 0 ? 'open' : '' ?>><summary><?= e($h) ?></summary><ol><?php foreach ($steps as $s): ?><li><?= e($s) ?></li><?php endforeach ?></ol></details>
<?php endforeach ?>
<h2>📚 Printable guides</h2>
<div class="list"><?php foreach (['homeschool-parent-guide.pdf' => ['Homeschooling Parent Guide', ['parent', 'admin', 'teacher']], 'weekly-homeschool-planner.pdf' => ['Weekly Homeschool Planner', ['parent', 'admin', 'teacher', 'student']], 'grade1-english-worksheets.pdf' => ['Grade 1 English Worksheets', ['parent', 'admin', 'teacher', 'student']], 'grade1-maths-worksheets.pdf' => ['Grade 1 Maths Worksheets', ['parent', 'admin', 'teacher', 'student']], 'trainer-handbook-sample.pdf' => ['Train the Trainer Handbook (sample)', ['admin', 'teacher']], 'lesson-plan-template.pdf' => ['Lesson Plan Template', ['admin', 'teacher']]] as $f => [$l, $who]): if (!in_array($me['role'], $who, true)) continue; ?>
  <a class="row" href="assets/guides/<?= $f ?>" target="_blank"><span class="mi">📄</span><b class="grow"><?= $l ?></b><span>PDF ›</span></a>
<?php endforeach ?></div>
<div class="card center"><p style="margin:0 0 10px">Still need help?</p><?php $wa = wa_num(setting('site_whatsapp') ?: setting('phone')); if ($wa): ?><a class="btn" href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">💬 WhatsApp support</a><?php endif ?></div>
