<?php
// Demo shop: products (with photos), demo families and orders in different statuses. Idempotent.
require_once __DIR__ . '/demo.php';
function shop_seed(): array {
    $B = 'https://pikaso.cdnpk.net/private/production/';
    $hs = (int)(val('SELECT id FROM courses WHERE program="homeschool" ORDER BY id LIMIT 1') ?: 0) ?: null;
    $tr = (int)(val('SELECT id FROM courses WHERE program="trainer" ORDER BY id LIMIT 1') ?: 0) ?: null;
    $P = [
      ['Grade 1 English Task Book', 'Task Books', 950, 1200, 'physical', 40, $hs, '5624280759/render.png?token=exp=1791331200~hmac=4d249ad5b1bb783875ad65669d8fd0f7b71f987fb42dd3469642ecff08f7da66', "120 colourful pages of phonics, reading and writing activities for Grade 1.\n\n- Letter sounds and tracing\n- Sight words and short sentences\n- Weekly review pages and stickers"],
      ['Grade 2 Maths Task Book', 'Task Books', 950, 1200, 'physical', 35, $hs, '5624280798/render.png?token=exp=1791331200~hmac=e833199e153ab0c059ae034a419af160cd889cec84b6a8c471a1f7d794512475', "Step-by-step maths practice aligned with our Grade 2 homeschool course.\n\n- Addition & subtraction up to 1000\n- Shapes, time and money\n- Answer key for parents"],
      ['Grade 3 Science Activity Book', 'Task Books', 1100, null, 'physical', 25, $hs, '5624280908/render.png?token=exp=1791331200~hmac=96d8be84f191c7b4fd3bd9266543ff3e272f9703cf941780f6aad559263786b8', "Hands-on science experiments you can do at home with everyday items: plants, magnets, weather, the human body and more."],
      ['Urdu Qaida & Writing Practice', 'Workbooks', 650, 800, 'physical', 50, null, '5624280942/render.jpg?token=exp=1791331200~hmac=7bb3b31b973f2c9a721d1db230803cbb657650bb0e3b8f2b88ffd70134e27665', "Urdu huroof, joining letters and handwriting practice with tracing lines — ideal for ages 4–7."],
      ['Phonics Flash Cards (52 cards)', 'Learning Kits', 1200, 1500, 'physical', 4, $hs, '5624281010/render.png?token=exp=1791331200~hmac=042e2ebea0312202f4ba706bc301b5b726da8dee8597725ce634c61f907e30fc', "Durable picture flash cards for letter sounds, blends and first words. Comes in a sturdy box."],
      ['Homeschool Starter Stationery Kit', 'Stationery', 2500, 3000, 'physical', 20, null, '5624281094/render.png?token=exp=1791331200~hmac=e1acc7002b17c4dd568be1b52a1246c75e5d2066ecf315b8040a2b77e2f0cc15', "Everything your child needs to start: pencils, crayons, eraser, sharpener, glue, safety scissors, 2 notebooks and a pencil case."],
      ['Train the Trainer Handbook', 'Trainer Material', 1800, null, 'physical', 15, $tr, '5624281276/render.png?token=exp=1791331200~hmac=3ff1f7d5fa3269ea1278dc2c3a5c823e98f6929a0cb0704ce5fa1d9f21e8f0f0', "The official handbook for our Train the Trainer program: lesson planning, classroom management, assessment and communicating with parents."],
      ['Montessori Maths Kit (wooden)', 'Learning Kits', 3200, 3800, 'physical', 8, null, '5624281254/render.png?token=exp=1791331200~hmac=429285653e5a3ef3a7b08dff60776839e69711caeadda208e9cdb8f5d62a5a08', "Wooden number rods, counting beads and number tiles to make early maths concrete and fun."],
      ['Printable Worksheets Pack — Grades 1–3 (PDF)', 'Worksheets (PDF)', 499, 799, 'digital', null, $hs, '', "200+ printable worksheets for English, Maths and Urdu. Download instantly after payment and print as many times as you need."],
      ['Weekly Homeschool Planner (PDF)', 'Worksheets (PDF)', 299, null, 'digital', null, null, '', "A simple weekly planner to organise lessons, reading time and activities. Printable A4 PDF."],
    ];
    $ids = json_decode(setting('demo_products', '[]'), true) ?: [];
    $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj 4 0 obj<</Length 60>>stream\nBT /F1 24 Tf 72 760 Td (Sample worksheet - replace with real file) Tj ET\nendstream endobj 5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
    $np = 0; $map = [];
    foreach ($P as [$t, $cat, $price, $cmp, $type, $stock, $cid, $img, $desc]) {
        $pid = (int)val('SELECT id FROM products WHERE title=?', [$t]);
        if (!$pid) {
            q('INSERT INTO products(title,description,category,price,compare_price,type,stock,course_id,active) VALUES(?,?,?,?,?,?,?,?,1)', [$t, $desc, $cat, $price, $cmp, $type, $stock, $cid]);
            $pid = (int)db()->lastInsertId(); $np++; $ids[] = $pid;
            if ($type === 'digital') { if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0750, true); $fn = 'demo-' . bin2hex(random_bytes(5)) . '.pdf'; file_put_contents(UPLOAD_DIR . "/$fn", $pdf); q('UPDATE products SET file=? WHERE id=?', [$fn, $pid]); }
        }
        if ($img && !val('SELECT image FROM products WHERE id=?', [$pid]) && ($f = demo_fetch_cover($B . $img))) q('UPDATE products SET image=? WHERE id=?', [$f, $pid]);
        $map[$t] = one('SELECT * FROM products WHERE id=?', [$pid]);
    }
    q('REPLACE INTO settings(k,v) VALUES("demo_products",?)', [json_encode(array_values(array_unique($ids)))]);

    // demo families: parent + child
    $F = [['Imran Shah', 'Hamza Shah', '0300-4561230', 'Lahore', 'House 12, Street 4, DHA Phase 5'], ['Sadia Noor', 'Zara Noor', '0321-7788990', 'Karachi', 'Flat 7B, Clifton Block 2'], ['Kashif Mehmood', 'Ayaan Mehmood', '0333-2244668', 'Islamabad', 'House 45, F-10/2'], ['Rubina Akhtar', 'Fatima Akhtar', '0345-1357913', 'Faisalabad', 'Street 9, Peoples Colony'], ['Tahir Iqbal', 'Ibrahim Iqbal', '0312-8642097', 'Multan', 'House 3, Gulgasht Colony']];
    $fam = [];
    foreach ($F as $i => [$pn, $cn, $ph, $city, $addr]) {
        $pe = 'parent' . ($i + 1) . '@demo.lms'; $ce = 'student' . ($i + 1) . '@demo.lms';
        foreach ([[$pn, $pe, 'parent'], [$cn, $ce, 'student']] as [$n, $em, $rl]) if (!val('SELECT id FROM users WHERE email=?', [$em])) q('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,?)', [$n, $em, $ph, password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT), $rl]);
        $pid = (int)val('SELECT id FROM users WHERE email=?', [$pe]); $cid = (int)val('SELECT id FROM users WHERE email=?', [$ce]);
        q('INSERT IGNORE INTO parent_links(parent_id,student_id,relation) VALUES(?,?,?)', [$pid, $cid, $i % 2 ? 'Mother' : 'Father']);
        if ($hs) q('INSERT IGNORE INTO enrollments(user_id,course_id,status,fee) VALUES(?,?,"active",?)', [$cid, $hs, (float)val('SELECT fee FROM courses WHERE id=?', [$hs])]);
        $fam[] = [$pid, $cid, $pn, $ph, $city, $addr];
    }

    // orders: [family, [[product, qty]...], status, pay, days ago, tracking]
    $O = [
      [0, [['Grade 1 English Task Book', 1], ['Homeschool Starter Stationery Kit', 1]], 'delivered', 'COD', 24, 'TCS 77812345'],
      [1, [['Phonics Flash Cards (52 cards)', 1], ['Printable Worksheets Pack — Grades 1–3 (PDF)', 1]], 'delivered', 'JazzCash', 20, 'Leopards LP556677'],
      [2, [['Montessori Maths Kit (wooden)', 1]], 'shipped', 'Bank', 6, 'TCS 77890011'],
      [3, [['Urdu Qaida & Writing Practice', 2], ['Grade 2 Maths Task Book', 1]], 'paid', 'EasyPaisa', 3, ''],
      [4, [['Grade 3 Science Activity Book', 1], ['Weekly Homeschool Planner (PDF)', 1]], 'processing', 'JazzCash', 2, ''],
      [0, [['Grade 2 Maths Task Book', 1]], 'pending', 'COD', 1, ''],
      [1, [['Homeschool Starter Stationery Kit', 1], ['Urdu Qaida & Writing Practice', 1]], 'pending', 'JazzCash', 0, ''],
      [2, [['Train the Trainer Handbook', 1]], 'cancelled', 'COD', 12, ''],
      [3, [['Printable Worksheets Pack — Grades 1–3 (PDF)', 1]], 'delivered', 'EasyPaisa', 15, ''],
      [4, [['Phonics Flash Cards (52 cards)', 1], ['Grade 1 English Task Book', 1]], 'shipped', 'COD', 4, 'M&P 9988776'],
    ];
    $no = 0;
    if (!val('SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id WHERE u.email LIKE "%@demo.lms"')) {
        $ship = (float)setting('shop_shipping', '250');
        foreach ($O as [$fi, $lines, $st, $pm, $ago, $trk]) {
            [$pid, $cid, $pn, $ph, $city, $addr] = $fam[$fi];
            $sub = 0; $phys = false;
            foreach ($lines as [$t, $qty]) { $sub += $map[$t]['price'] * $qty; if ($map[$t]['type'] === 'physical') $phys = true; }
            $sh = $phys ? $ship : 0; $dt = date('Y-m-d H:i:s', strtotime("-$ago days -" . rand(1, 9) . ' hours'));
            q('INSERT INTO orders(user_id,student_id,status,subtotal,shipping,total,name,phone,address,city,pay_method,txn_ref,tracking,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$pid, $cid, $st, $sub, $sh, $sub + $sh, $pn, $ph, $phys ? $addr : '', $phys ? $city : '', $pm, $pm !== 'COD' ? 'TX' . rand(10000000, 99999999) : '', $trk, $dt]);
            $oid = (int)db()->lastInsertId();
            foreach ($lines as [$t, $qty]) q('INSERT INTO order_items(order_id,product_id,title,price,qty,type) VALUES(?,?,?,?,?,?)', [$oid, $map[$t]['id'], $t, $map[$t]['price'], $qty, $map[$t]['type']]);
            $no++;
        }
    }
    $imgs = (int)val('SELECT COUNT(*) FROM products WHERE image<>"" AND id IN (' . (implode(',', array_map('intval', $ids)) ?: '0') . ')');
    return [$np, $no, $imgs];
}
function shop_demo_clear(): array {
    $ids = array_map('intval', json_decode(setting('demo_products', '[]'), true) ?: []);
    $du = array_map('intval', array_column(all('SELECT id FROM users WHERE email LIKE "%@demo.lms" AND role IN ("student","parent")'), 'id'));
    $o = 0;
    if ($du) {
        $oids = array_map('intval', array_column(all('SELECT id FROM orders WHERE user_id IN (' . implode(',', $du) . ')'), 'id'));
        if ($oids) { q('DELETE FROM order_items WHERE order_id IN (' . implode(',', $oids) . ')'); q('DELETE FROM orders WHERE id IN (' . implode(',', $oids) . ')'); $o = count($oids); }
        $in = implode(',', $du);
        foreach (["DELETE FROM parent_links WHERE parent_id IN ($in) OR student_id IN ($in)", "DELETE FROM enrollments WHERE user_id IN ($in)", "DELETE FROM notifications WHERE user_id IN ($in)", "DELETE FROM fee_vouchers WHERE user_id IN ($in)", "DELETE FROM users WHERE id IN ($in)"] as $sql) q($sql);
    }
    $p = 0;
    if ($ids) { $in = implode(',', $ids); $p = (int)val("SELECT COUNT(*) FROM products WHERE id IN ($in)"); q("UPDATE products SET active=0 WHERE id IN ($in) AND id IN (SELECT product_id FROM order_items)"); q("DELETE FROM products WHERE id IN ($in) AND id NOT IN (SELECT product_id FROM order_items)"); }
    q('REPLACE INTO settings(k,v) VALUES("demo_products","[]")');
    return [$p, $o, count($du)];
}
