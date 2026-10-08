<?php
// Demo institutes for EduMall (fictional names). Run from Modules → Load demo institutes.
function mall_seed(): array {
    $list = [
        ['Crescent Valley School', 'school', 'Lahore', 'DHA Phase 5', 6500, 12000, 2004, "Playgroup to Grade 5\nGrade 6 to 8\nMatric Science\nMatric Computer Science", 'Science labs, Computer lab, Library, Transport, CCTV, Sports ground'],
        ['Iqra Model High School', 'school', 'Karachi', 'Gulshan-e-Iqbal Block 13', 3500, 6000, 1998, "Montessori\nPrimary (Grade 1-5)\nMiddle (Grade 6-8)\nMatric", 'Library, Computer lab, Transport, Air-conditioned classes'],
        ['Margalla Grammar School', 'school', 'Islamabad', 'G-11/2', 9000, 18000, 2010, "Early Years\nPrimary\nO Level\nA Level", 'Science labs, Swimming pool, Library, Robotics club, Cafeteria'],
        ['Punjab College of Sciences', 'college', 'Faisalabad', 'Susan Road', 5000, 9000, 2001, "FSc Pre-Medical\nFSc Pre-Engineering\nICS\nI.Com\nFA", 'Labs, Library, Hostel, Transport, Scholarships'],
        ['Indus Commerce College', 'college', 'Hyderabad', 'Qasimabad', 3000, 5500, 2007, "I.Com\nB.Com\nICS\nADP Accounting", 'Computer lab, Library, Career counselling'],
        ['Frontier Institute of Technology', 'university', 'Peshawar', 'University Town', 35000, 60000, 1995, "BS Computer Science\nBS Software Engineering\nBBA\nMBA\nBS Electrical Engineering", 'Hostel, Labs, Sports complex, Library, Wi-Fi campus, Transport'],
        ['Sindh University of Management Sciences', 'university', 'Karachi', 'Clifton Block 5', 40000, 75000, 2003, "BBA\nBS Accounting & Finance\nBS Computer Science\nMS Management", 'Library, Labs, Incubation centre, Cafeteria, Scholarships'],
        ['Star Achievers Academy', 'academy', 'Rawalpindi', 'Saddar', 2500, 7000, 2015, "MDCAT preparation\nECAT preparation\nMatric & FSc tuition\nO/A Level Physics & Maths", 'Test series, Air-conditioned rooms, Online classes'],
        ['Skill Up Digital Institute', 'institute', 'Lahore', 'Johar Town', 4000, 12000, 2019, "Web Development\nGraphic Design\nDigital Marketing\nFreelancing (Fiverr & Upwork)\nAmazon VA", 'Computer lab, Internship support, Certificates, Weekend batches'],
        ['Bright Minds Tuition Centre', 'tuition', 'Multan', 'Gulgasht Colony', 2000, 5000, 2016, "Grade 1-8 all subjects\nMatric Maths & Science\nEnglish speaking", 'Small groups, Female teachers, Weekly tests'],
    ];
    $about = "%s is a trusted %s in %s offering quality education with experienced teachers, modern facilities and a caring environment.\n\nWe focus on concept-based learning, character building and regular parent-teacher communication. Admissions are open — apply online and our team will contact you.";
    $n = 0; $ids = [];
    foreach ($list as [$name, $type, $city, $addr, $fmin, $fmax, $est, $prog, $fac]) {
        if ($id = val('SELECT id FROM institutions WHERE name=?', [$name])) { $ids[] = (int)$id; continue; }
        q('INSERT INTO institutions(name,slug,type,city,address,about,programs,facilities,phone,whatsapp,email,fee_min,fee_max,established,admissions_open,status,featured) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,"active",?)',
          [$name, unique_inst_slug($name), $type, $city, $addr, sprintf($about, $name, strtolower(INST_TYPES[$type][1]), $city), $prog, $fac, '0300-' . rand(1000000, 9999999), '0300-' . rand(1000000, 9999999), 'info@' . slugify($name) . '.demo', $fmin, $fmax, $est, $n < 4 ? 1 : 0]);
        $ids[] = (int)db()->lastInsertId(); $n++;
    }
    // institute logins for the first two (demo123456)
    foreach (array_slice($ids, 0, 2) as $k => $iid) {
        $em = 'institute' . ($k + 1) . '@demo.lms';
        if (!val('SELECT id FROM users WHERE email=?', [$em])) { q('INSERT INTO users(name,email,phone,password,role,institution_id) VALUES(?,?,?,?,"institute",?)', [val('SELECT name FROM institutions WHERE id=?', [$iid]) . ' Admin', $em, '0300-0000000', password_hash('demo123456', PASSWORD_DEFAULT), $iid]); q('UPDATE institutions SET owner_id=? WHERE id=?', [(int)db()->lastInsertId(), $iid]); }
    }
    // link demo teachers to institutes (each teacher to 1-2 institutes)
    $teachers = array_map('intval', array_column(all('SELECT id FROM users WHERE role="teacher" AND active=1 ORDER BY id LIMIT 12'), 'id'));
    foreach ($teachers as $i => $tid) foreach ([$ids[$i % count($ids)], $ids[($i + 3) % count($ids)]] as $iid) q('INSERT IGNORE INTO teacher_institutions(teacher_id,institution_id,status,requested_by) VALUES(?,?,"active","institute")', [$tid, $iid]);
    // attach existing courses of linked teachers to their first institute
    foreach ($teachers as $i => $tid) q('UPDATE courses SET institution_id=? WHERE teacher_id=? AND institution_id IS NULL', [$ids[$i % count($ids)], $tid]);
    // demo admission enquiries
    $names = ['Ali Hassan', 'Ayesha Noor', 'Hamza Khan', 'Fatima Zahra', 'Usman Tariq', 'Zainab Ali', 'Bilal Ahmed', 'Hira Shah'];
    if (!val('SELECT COUNT(*) FROM admissions')) foreach ($ids as $k => $iid) for ($j = 0; $j < 2; $j++) {
        $pr = array_values(array_filter(explode("\n", (string)val('SELECT programs FROM institutions WHERE id=?', [$iid]))));
        q('INSERT INTO admissions(institution_id,student_name,guardian_name,phone,class_program,city,message,status,created_at) VALUES(?,?,?,?,?,?,?,?,NOW() - INTERVAL ? HOUR)', [$iid, $names[($k + $j) % 8], 'Parent of ' . explode(' ', $names[($k + $j) % 8])[0], '0301-' . rand(1000000, 9999999), $pr[$j % max(1, count($pr))] ?? '', '', 'Please share the fee structure and admission test date.', $j ? 'contacted' : 'new', rand(2, 120)]);
    }
    return [$n, count($ids)];
}
function mall_demo_clear(): int {
    $ids = array_map('intval', array_column(all('SELECT id FROM institutions WHERE email LIKE "%.demo"'), 'id')); if (!$ids) return 0;
    $in = implode(',', $ids);
    q("DELETE FROM admissions WHERE institution_id IN ($in)"); q("DELETE FROM teacher_institutions WHERE institution_id IN ($in)"); q("UPDATE courses SET institution_id=NULL WHERE institution_id IN ($in)");
    q("DELETE FROM users WHERE role='institute' AND institution_id IN ($in)"); q("DELETE FROM institutions WHERE id IN ($in)");
    return count($ids);
}
