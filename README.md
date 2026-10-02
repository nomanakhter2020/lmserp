# LMS ERP

Mobile-first Learning Management System (PWA) by NAFsols — PHP 8 + MySQL.

## Features
- Roles: **Admin**, **Teacher**, **Student**
- Courses with categories, fee (PKR), colour, draft/published
- Lessons: YouTube / MP4 video, notes, attachment link, ordering, progress tracking
- MCQ quizzes with pass mark, auto-grading, results
- Enrollments (self sign-up or admin), pending → approve on fee payment
- Fee payments, printable receipts, WhatsApp receipt share, outstanding dues
- Expenses and 6-month income/expense reports
- Announcements (global or per course)
- Installable PWA with offline page

## Server setup
- Deploy repo into `public_html` (Hostinger Git auto-deploy).
- Open `/install.php` once; it writes DB config to `../../lmserp-config.php` (outside the web root and repo) and creates tables + admin.
- Config file format:
```php
<?php return ['db_host' => '127.0.0.1', 'db_name' => '...', 'db_user' => '...', 'db_pass' => '...'];
```
- `.htaccess` blocks `inc/`, `views/` and `.sql` from the web and forces HTTPS.
