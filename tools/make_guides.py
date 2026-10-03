"""Generate demo study-guide PDFs for LMS ERP (Elite Home Scholars)."""
import os
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib import colors
from reportlab.lib.units import mm
from reportlab.pdfgen import canvas
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import Paragraph, Table, TableStyle

OUT = os.path.join(os.path.dirname(__file__), '..', 'assets', 'guides')
os.makedirs(OUT, exist_ok=True)
BRAND = 'Elite Home Scholars'
P = colors.HexColor('#4f46e5'); P2 = colors.HexColor('#7c3aed'); INK = colors.HexColor('#1e1b4b'); MU = colors.HexColor('#6b7090'); LN = colors.HexColor('#dfe2f2'); SOFT = colors.HexColor('#f3f4fd')
body = ParagraphStyle('b', fontName='Helvetica', fontSize=11, leading=16, textColor=INK)
small = ParagraphStyle('s', parent=body, fontSize=9.5, leading=13, textColor=MU)


class Doc:
    def __init__(self, name, title, subtitle, size=A4):
        self.c = canvas.Canvas(os.path.join(OUT, name), pagesize=size)
        self.c.setTitle(title); self.c.setAuthor(BRAND)
        self.W, self.H = size; self.title = title; self.sub = subtitle; self.page = 0
        self.new_page()

    def new_page(self):
        if self.page: self.footer(); self.c.showPage()
        self.page += 1; c = self.c
        c.setFillColor(P); c.rect(0, self.H - 30 * mm, self.W, 30 * mm, fill=1, stroke=0)
        c.setFillColor(P2); c.circle(self.W - 20 * mm, self.H - 5 * mm, 22 * mm, fill=1, stroke=0)
        c.setFillColor(colors.white); c.setFont('Helvetica-Bold', 9); c.drawString(18 * mm, self.H - 10 * mm, BRAND.upper())
        c.setFont('Helvetica-Bold', 19); c.drawString(18 * mm, self.H - 19 * mm, self.title)
        c.setFont('Helvetica', 10); c.drawString(18 * mm, self.H - 25 * mm, self.sub)
        self.y = self.H - 42 * mm

    def footer(self):
        c = self.c; c.setStrokeColor(LN); c.line(18 * mm, 14 * mm, self.W - 18 * mm, 14 * mm)
        c.setFont('Helvetica', 8.5); c.setFillColor(MU)
        c.drawString(18 * mm, 9 * mm, f'{BRAND} · {self.title}'); c.drawRightString(self.W - 18 * mm, 9 * mm, f'Page {self.page}')

    def need(self, h):
        if self.y - h < 22 * mm: self.new_page()

    def h(self, text, size=14):
        self.need(14 * mm); c = self.c
        c.setFillColor(P); c.rect(18 * mm, self.y - 1.5 * mm, 1.6 * mm, 6 * mm, fill=1, stroke=0)
        c.setFillColor(INK); c.setFont('Helvetica-Bold', size); c.drawString(22 * mm, self.y, text); self.y -= 9 * mm

    def p(self, text, style=body, indent=0):
        para = Paragraph(text, style); w = self.W - 36 * mm - indent
        _, ph = para.wrap(w, 1000); self.need(ph + 2 * mm)
        para.drawOn(self.c, 18 * mm + indent, self.y - ph + 3 * mm); self.y -= ph + 3 * mm

    def bullets(self, items, num=False):
        for i, t in enumerate(items, 1):
            para = Paragraph(t, body); w = self.W - 44 * mm; _, ph = para.wrap(w, 1000); self.need(ph + 2 * mm)
            self.c.setFillColor(P); self.c.setFont('Helvetica-Bold', 11)
            self.c.drawString(20 * mm, self.y - 1 * mm, f'{i}.' if num else '•')
            para.drawOn(self.c, 26 * mm, self.y - ph + 3 * mm); self.y -= ph + 2.5 * mm
        self.y -= 2 * mm

    def box(self, title, text):
        para = Paragraph(text, body); w = self.W - 46 * mm; _, ph = para.wrap(w, 1000); hh = ph + 14 * mm; self.need(hh + 3 * mm)
        c = self.c; c.setFillColor(SOFT); c.setStrokeColor(LN); c.roundRect(18 * mm, self.y - hh + 4 * mm, self.W - 36 * mm, hh, 4 * mm, fill=1, stroke=1)
        c.setFillColor(P); c.setFont('Helvetica-Bold', 11); c.drawString(23 * mm, self.y - 2 * mm, title)
        para.drawOn(c, 23 * mm, self.y - 6 * mm - ph); self.y -= hh + 4 * mm

    def table(self, data, widths, header=True, row_h=None, font=10):
        t = Table(data, colWidths=widths, rowHeights=row_h)
        st = [('FONT', (0, 0), (-1, -1), 'Helvetica', font), ('GRID', (0, 0), (-1, -1), .6, LN), ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('TEXTCOLOR', (0, 0), (-1, -1), INK), ('LEFTPADDING', (0, 0), (-1, -1), 6)]
        if header: st += [('BACKGROUND', (0, 0), (-1, 0), P), ('TEXTCOLOR', (0, 0), (-1, 0), colors.white), ('FONT', (0, 0), (-1, 0), 'Helvetica-Bold', font)]
        t.setStyle(TableStyle(st)); _, th = t.wrap(0, 0); self.need(th + 4 * mm)
        t.drawOn(self.c, 18 * mm, self.y - th + 3 * mm); self.y -= th + 6 * mm

    def name_line(self):
        c = self.c; c.setFont('Helvetica', 10); c.setFillColor(MU)
        c.drawString(18 * mm, self.y, 'Name: ________________________________'); c.drawString(self.W - 80 * mm, self.y, 'Date: __________________'); self.y -= 10 * mm

    def save(self):
        self.footer(); self.c.save()


# 1. Parent homeschool guide
d = Doc('homeschool-parent-guide.pdf', 'Homeschooling Parent Guide', 'How to set up a calm, effective learning routine at home')
d.h('Welcome to homeschooling')
d.p('Homeschooling works best with a simple routine, a dedicated learning corner and lots of encouragement. This guide gives you a practical plan you can start this week. You do not need to be a teacher — our lessons, videos and task books guide you step by step.')
d.h('Set up a learning corner')
d.bullets(['A table and chair at the right height, with good light.', 'A small shelf for task books, stationery and a water bottle.', 'Keep phones and TV away during lesson time.', 'A wall chart for the weekly timetable and a star chart for rewards.'])
d.h('A sample daily routine (ages 5–9)')
d.table([['Time', 'Activity', 'Tip'], ['9:00', 'Morning circle — date, weather, prayer/du\'a', 'Keep it short and fun'], ['9:15', 'English — phonics or reading (video + task book)', '20–30 minutes'], ['9:50', 'Movement break / snack', 'Go outside if possible'], ['10:10', 'Maths — lesson video, then 1–2 task book pages', 'Use real objects to count'], ['10:50', 'Urdu / Islamiat', 'Read aloud together'], ['11:20', 'Science, art or a hands-on project', 'Follow the activity book'], ['12:00', 'Free reading & lunch', 'Let your child choose a book']], [22 * mm, 95 * mm, 57 * mm])
d.h('Tips that make a big difference')
d.bullets(['<b>Short sessions:</b> young children focus for about their age + 5 minutes. Take breaks.', '<b>Praise effort,</b> not just correct answers: "You tried really hard on that!"', '<b>Same time every day:</b> routine reduces arguments and builds habits.', '<b>Read every day</b> — even 15 minutes of reading together builds vocabulary.', '<b>Use the app:</b> mark lessons complete, check quiz scores and teacher feedback.'])
d.box('How we support you', 'Your child\'s teacher reviews assignments and gives feedback in the app. Attendance, results, fee vouchers and announcements all appear on your parent dashboard. Message us on WhatsApp any time you need help.')
d.h('Weekly checklist')
d.table([['Task', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']] + [[t, '', '', '', '', '', ''] for t in ['English lesson', 'Maths lesson', 'Urdu / Islamiat', 'Science / project', 'Reading 15 min', 'Assignment submitted', 'Quiz attempted']], [60 * mm] + [19 * mm] * 6, row_h=[8 * mm] * 8)
d.save()

# 2. Grade 1 English worksheets
d = Doc('grade1-english-worksheets.pdf', 'Grade 1 English Worksheets', 'Phonics · tracing · sight words')
d.name_line(); d.h('1. Trace the letters')
c = d.c
for row, letters in enumerate(['A a  B b  C c  D d', 'E e  F f  G g  H h', 'I i  J j  K k  L l']):
    y = d.y - row * 22 * mm
    c.setStrokeColor(LN)
    for k, off in enumerate([0, 6, 12]): c.setDash(2, 2) if k == 1 else c.setDash(); c.line(18 * mm, y - off * mm, d.W - 18 * mm, y - off * mm)
    c.setDash(); c.setFont('Helvetica', 34); c.setFillColor(colors.HexColor('#c7cbe6')); c.drawString(22 * mm, y - 11 * mm, letters + '   ' + letters.split('  ')[0])
d.y -= 72 * mm
d.h('2. Fill in the missing letter')
d.table([['c _ t', 'd _ g', 's _ n', 'h _ t'], ['b _ g', 'p _ n', 'm _ p', 'r _ d']], [43.5 * mm] * 4, header=False, row_h=[14 * mm] * 2, font=18)
d.h('3. Read the sight words, then write each one twice')
d.table([['Word', 'Write it', 'Write it again']] + [[w, '', ''] for w in ['the', 'and', 'is', 'you', 'we', 'see']], [40 * mm, 67 * mm, 67 * mm], row_h=[8 * mm] + [11 * mm] * 6, font=14)
d.new_page(); d.name_line()
d.h('4. Circle the word that matches the sound')
d.table([['Starts with /b/', 'ball', 'cat', 'bag', 'sun'], ['Starts with /s/', 'sock', 'map', 'sit', 'dog'], ['Ends with /t/', 'hat', 'pen', 'net', 'cup'], ['Ends with /n/', 'pin', 'bus', 'fan', 'top']], [44 * mm, 32.5 * mm, 32.5 * mm, 32.5 * mm, 32.5 * mm], header=False, row_h=[13 * mm] * 4, font=15)
d.h('5. Read and draw')
d.p('Read each sentence. Draw a picture in the box.')
d.table([['A red ball.', 'A big cat.'], ['', ''], ['The sun is hot.', 'I see a dog.'], ['', '']], [87 * mm, 87 * mm], header=False, row_h=[9 * mm, 45 * mm, 9 * mm, 45 * mm], font=13)
d.box('Note for parents', 'Say the letter sound (not the letter name) while your child traces. Praise neat work and effort. Answers: c-a-t, d-o-g, s-u-n, h-a-t, b-a-g, p-i-n/p-e-n, m-a-p, r-e-d.')
d.save()

# 3. Grade 1 maths worksheets
d = Doc('grade1-maths-worksheets.pdf', 'Grade 1 Maths Worksheets', 'Counting · addition · subtraction · shapes')
d.name_line(); d.h('1. Count the dots and write the number')
c = d.c
for i, n in enumerate([3, 5, 7, 4, 8, 6]):
    col, row = i % 3, i // 3; x = 18 * mm + col * 58 * mm; y = d.y - row * 32 * mm
    c.setStrokeColor(LN); c.roundRect(x, y - 26 * mm, 54 * mm, 26 * mm, 3 * mm)
    for k in range(n): c.setFillColor(P if k % 2 == 0 else P2); c.circle(x + 7 * mm + (k % 4) * 8 * mm, y - 7 * mm - (k // 4) * 8 * mm, 2.6 * mm, fill=1, stroke=0)
    c.setFillColor(INK); c.rect(x + 40 * mm, y - 22 * mm, 10 * mm, 10 * mm)
d.y -= 70 * mm
d.h('2. Add')
d.table([['2 + 3 = ___', '4 + 1 = ___', '5 + 2 = ___', '3 + 3 = ___'], ['6 + 2 = ___', '1 + 7 = ___', '4 + 4 = ___', '5 + 5 = ___']], [43.5 * mm] * 4, header=False, row_h=[14 * mm] * 2, font=15)
d.h('3. Subtract')
d.table([['5 − 2 = ___', '7 − 3 = ___', '9 − 4 = ___', '6 − 6 = ___'], ['8 − 1 = ___', '10 − 5 = ___', '4 − 2 = ___', '9 − 7 = ___']], [43.5 * mm] * 4, header=False, row_h=[14 * mm] * 2, font=15)
d.new_page(); d.name_line()
d.h('4. Name the shapes')
c = d.c; y = d.y - 30 * mm; c.setLineWidth(2); c.setStrokeColor(P)
c.circle(40 * mm, y, 14 * mm); c.rect(75 * mm, y - 14 * mm, 28 * mm, 28 * mm)
pth = c.beginPath(); pth.moveTo(130 * mm, y - 14 * mm); pth.lineTo(158 * mm, y - 14 * mm); pth.lineTo(144 * mm, y + 14 * mm); pth.close(); c.drawPath(pth)
c.rect(168 * mm, y - 10 * mm, 26 * mm, 20 * mm); c.setLineWidth(1)
c.setFillColor(MU); c.setFont('Helvetica', 11)
for x in [26, 75, 130, 166]: c.drawString(x * mm, y - 24 * mm, '____________')
d.y -= 64 * mm
d.h('5. Word problems')
d.bullets(['Ali has 4 apples. His mother gives him 3 more. How many apples does Ali have now? ______', 'There are 9 birds on a tree. 5 fly away. How many birds are left? ______', 'Sara has 2 red pencils and 6 blue pencils. How many pencils in all? ______'], num=True)
d.h('6. Fill in the missing numbers')
d.table([['1', '2', '', '4', '', '6', '', '8', '', '10'], ['11', '', '13', '', '15', '', '', '18', '', '20']], [17.4 * mm] * 10, header=False, row_h=[13 * mm] * 2, font=15)
d.box('Answer key for parents', 'Dots: 3, 5, 7, 4, 8, 6 · Add: 5, 5, 7, 6, 8, 8, 8, 10 · Subtract: 3, 4, 5, 0, 7, 5, 2, 2 · Shapes: circle, square, triangle, rectangle · Problems: 7, 4, 8.')
d.save()

# 4. Train the trainer handbook sample
d = Doc('trainer-handbook-sample.pdf', 'Train the Trainer Handbook', 'Sample chapters · Level 1')
d.h('Chapter 1 · The role of a trainer')
d.p('A great trainer does more than deliver content. You create a safe space where learners participate, practise and receive feedback. Your job is to make learning happen — not just to talk.')
d.bullets(['<b>Facilitator:</b> guide discussion and activities instead of lecturing for long periods.', '<b>Coach:</b> observe, give specific feedback and encourage improvement.', '<b>Role model:</b> be punctual, prepared, patient and respectful.'])
d.h('Chapter 2 · Planning a lesson (the 5E model)')
d.table([['Stage', 'What happens', 'Time'], ['Engage', 'Hook: a question, story or short video', '5 min'], ['Explore', 'Learners try an activity in pairs or groups', '10 min'], ['Explain', 'Trainer clarifies key ideas with examples', '10 min'], ['Elaborate', 'Practice task applying the idea', '15 min'], ['Evaluate', 'Quick quiz, exit ticket or demonstration', '5 min']], [30 * mm, 115 * mm, 29 * mm])
d.box('Activity', 'Write a 30-minute lesson plan for a topic you know well using the 5E stages. Use the Lesson Plan Template provided with this course.')
d.h('Chapter 3 · Classroom management')
d.bullets(['Agree on 3–4 clear class rules on day one and display them.', 'Use names, eye contact and movement around the room.', 'Plan transitions: tell learners what comes next and how long they have.', 'Address disruption calmly and privately; praise positive behaviour publicly.'])
d.h('Chapter 4 · Assessment & feedback')
d.p('Use short, frequent checks to see what learners understand — quizzes, thumbs-up/down, one-minute papers. Feedback should be <b>specific, timely and actionable</b>.')
d.box('The feedback sandwich', '1) What went well (be specific). 2) One thing to improve and how. 3) Encouragement for next time.')
d.h('Chapter 5 · Working with parents')
d.bullets(['Share progress regularly — the app sends results, attendance and assignment feedback automatically.', 'Start conversations with a positive observation.', 'Agree on one small goal the parent can support at home.'])
d.h('Certification')
d.p('To become a Certified Trainer, complete all lessons, pass the final quiz and deliver one observed micro-teaching session. Your certificate can be verified online with its QR code.')
d.save()

# 5. Lesson plan template
d = Doc('lesson-plan-template.pdf', 'Lesson Plan Template', 'Fill in before every class')
d.table([['Teacher', '', 'Date', ''], ['Class / Batch', '', 'Duration', ''], ['Subject / Topic', '', '', '']], [32 * mm, 55 * mm, 30 * mm, 57 * mm], header=False, row_h=[10 * mm] * 3)
d.table([['Learning objectives — by the end of the lesson learners will be able to:'], ['1.'], ['2.'], ['3.']], [174 * mm], row_h=[9 * mm, 10 * mm, 10 * mm, 10 * mm])
d.table([['Stage', 'Teacher activity', 'Learner activity', 'Time']] + [[s, '', '', ''] for s in ['Engage', 'Explore', 'Explain', 'Elaborate', 'Evaluate']], [26 * mm, 62 * mm, 62 * mm, 24 * mm], row_h=[9 * mm] + [20 * mm] * 5)
d.table([['Materials / resources'], [''], ['Homework / follow-up'], [''], ['Reflection — what worked, what to change next time'], ['']], [174 * mm], header=False, row_h=[8 * mm, 14 * mm, 8 * mm, 14 * mm, 8 * mm, 16 * mm])
d.save()

# 6. Weekly planner (landscape)
d = Doc('weekly-homeschool-planner.pdf', 'Weekly Homeschool Planner', 'Week of: ____________________', size=landscape(A4))
days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
d.table([['Subject'] + days] + [[s] + [''] * 6 for s in ['English', 'Maths', 'Urdu', 'Islamiat', 'Science', 'Reading', 'Art / Activity']], [34 * mm] + [38.5 * mm] * 6, row_h=[9 * mm] + [15 * mm] * 7)
d.table([['This week\'s goals', 'Books / materials needed', 'Notes for teacher']] + [['', '', '']], [87 * mm] * 3, row_h=[8 * mm, 22 * mm])
d.save()

# 7. IELTS writing guide
d = Doc('ielts-writing-task2-guide.pdf', 'IELTS Writing Task 2 Guide', 'Structure · useful phrases · checklist')
d.h('The four-paragraph structure')
d.table([['Paragraph', 'What to write', 'Sentences'], ['Introduction', 'Paraphrase the question + your clear position', '2'], ['Body 1', 'Main idea 1 → explanation → example', '4–5'], ['Body 2', 'Main idea 2 (or other view) → explanation → example', '4–5'], ['Conclusion', 'Restate your position in new words', '1–2']], [32 * mm, 112 * mm, 30 * mm])
d.h('Useful phrases')
d.table([['Purpose', 'Phrases'], ['Giving opinion', 'I strongly believe that… / In my view…'], ['Adding ideas', 'Furthermore, … / In addition, … / Moreover, …'], ['Contrasting', 'However, … / On the other hand, … / Although…'], ['Examples', 'For instance, … / A clear example of this is…'], ['Results', 'As a result, … / Consequently, … / This leads to…'], ['Concluding', 'In conclusion, … / To sum up, …']], [40 * mm, 134 * mm])
d.h('Before you submit — checklist')
d.bullets(['Did I answer <b>every part</b> of the question?', 'Is my opinion clear in the introduction and conclusion?', 'Does each body paragraph have one main idea and an example?', 'Did I write at least <b>250 words</b>?', 'Did I check articles (a/the), subject-verb agreement and spelling?'])
d.box('Practice question', 'Some people think children should start school at a very young age, while others believe they should begin at 7 or older. Discuss both views and give your own opinion. (40 minutes, 250+ words)')
d.save()

# 8. Python cheat sheet
d = Doc('python-cheatsheet.pdf', 'Python Beginner Cheat Sheet', 'Keep this next to you while you code')
code = ParagraphStyle('c', fontName='Courier', fontSize=10, leading=13.5, textColor=INK)
rows = [['Topic', 'Example'], ['Print', 'print("Hello")'], ['Variable', 'name = "Ali"   age = 12'], ['Input', 'x = int(input("Number: "))'], ['If / else', 'if marks >= 50:<br/>&nbsp;&nbsp;&nbsp;&nbsp;print("Pass")<br/>else:<br/>&nbsp;&nbsp;&nbsp;&nbsp;print("Fail")'], ['For loop', 'for i in range(1, 6):<br/>&nbsp;&nbsp;&nbsp;&nbsp;print(i)'], ['While loop', 'while n &gt; 0:<br/>&nbsp;&nbsp;&nbsp;&nbsp;n = n - 1'], ['List', 'fruits = ["apple", "mango"]<br/>fruits.append("kiwi")'], ['Function', 'def area(l, w):<br/>&nbsp;&nbsp;&nbsp;&nbsp;return l * w'], ['Dictionary', 'student = {"name": "Sara", "marks": 90}'], ['Comment', '# this line is ignored']]
d.table([[r[0], Paragraph(r[1], code) if i else r[1]] for i, r in enumerate(rows)], [36 * mm, 138 * mm])
d.box('Common errors', '<b>IndentationError</b> — check spaces at the start of lines. <b>NameError</b> — variable spelled differently or not created yet. <b>TypeError</b> — mixing text and numbers: use int() or str().')
d.save()
print('done', sorted(os.listdir(OUT)))
