<?php
$me = user();
$r = one('SELECT p.*,u.name,u.phone,u.email,c.title FROM payments p JOIN users u ON u.id=p.user_id LEFT JOIN courses c ON c.id=p.course_id WHERE p.id=?', [$id]);
if (!$r || (!role('admin') && (int)$r['user_id'] !== (int)$me['id'])) { echo '<p class="empty">Not found</p>'; return; }
$title = 'Receipt #' . $r['id']; $back = role('admin') ? "?p=user&id={$r['user_id']}" : '?p=fees';
?>
<div class="card receipt" id="rcpt">
  <div class="center"><img src="assets/icon.svg" width="48" alt=""><h1><?= e(setting('institute', APP_NAME)) ?></h1><small><?= e(setting('phone')) ?></small></div>
  <hr><div class="kv"><span>Receipt no.</span><b>#<?= str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT) ?></b></div>
  <div class="kv"><span>Date</span><b><?= date('d M Y', strtotime($r['paid_on'])) ?></b></div>
  <div class="kv"><span>Student</span><b><?= e($r['name']) ?></b></div>
  <div class="kv"><span>Course</span><b><?= e($r['title'] ?: 'General') ?></b></div>
  <div class="kv"><span>Method</span><b><?= e($r['method']) ?></b></div>
  <?php if ($r['note']): ?><div class="kv"><span>Note</span><b><?= e($r['note']) ?></b></div><?php endif ?>
  <hr><div class="kv total"><span>Amount paid</span><b><?= money($r['amount']) ?></b></div>
</div>
<div class="pager"><button class="btn ghost" onclick="window.print()">🖨 Print / PDF</button>
<?php $wa = preg_replace('/\D/', '', $r['phone']); if (str_starts_with($wa, '0')) $wa = '92' . substr($wa, 1);
 if ($wa && role('admin')): $msg = rawurlencode("Assalam o Alaikum {$r['name']}, we have received your payment of " . money($r['amount']) . ' on ' . date('d M Y', strtotime($r['paid_on'])) . ' for ' . ($r['title'] ?: 'fees') . '. Receipt #' . $r['id'] . '. Thank you! - ' . setting('institute')); ?>
<a class="btn" href="https://wa.me/<?= $wa ?>?text=<?= $msg ?>" target="_blank" rel="noopener">💬 Send on WhatsApp</a><?php endif ?></div>
