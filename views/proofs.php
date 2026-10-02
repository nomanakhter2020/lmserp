<?php
require_role('admin');
$title = 'Payment proofs'; $back = '?p=fees';
$st = in_array(get('status'), ['pending', 'approved', 'rejected'], true) ? get('status') : 'pending';
$rows = all('SELECT r.*,u.name,u.phone,c.title,c.fee FROM payment_requests r JOIN users u ON u.id=r.user_id JOIN courses c ON c.id=r.course_id WHERE r.status=? ORDER BY r.id DESC LIMIT 200', [$st]);
?>
<div class="chips"><?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $v): ?><a href="?p=proofs&status=<?= $k ?>" class="<?= $st === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach ?></div>
<?php foreach ($rows as $r): $isImg = $r['proof'] && !str_ends_with($r['proof'], '.pdf'); ?>
<div class="card proof">
  <div class="rowhead"><a href="?p=user&id=<?= $r['user_id'] ?>"><b><?= e($r['name']) ?></b></a><b class="pos"><?= money($r['amount']) ?></b></div>
  <small><?= e($r['title']) ?> (fee <?= money($r['fee']) ?>) · <?= e($r['method']) ?><?= $r['txn_ref'] ? ' · Ref ' . e($r['txn_ref']) : '' ?> · <?= date('d M, h:i a', strtotime($r['created_at'])) ?></small>
  <?php if ($r['note']): ?><p><?= e($r['note']) ?></p><?php endif ?>
  <?php if ($isImg): ?><a href="?p=proof_file&id=<?= $r['id'] ?>" target="_blank"><img class="shot" src="?p=proof_file&id=<?= $r['id'] ?>" alt="Payment screenshot" loading="lazy"></a>
  <?php elseif ($r['proof']): ?><a class="btn ghost sm" href="?p=proof_file&id=<?= $r['id'] ?>" target="_blank">📄 Open PDF proof</a>
  <?php elseif ($r['method'] === 'Cash'): ?><p class="muted">💵 Will pay cash at office — approve after receiving it.</p><?php endif ?>
  <?php if ($r['status'] === 'pending'): ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="proof_review"><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="two"><input name="amount" type="number" min="1" value="<?= (float)$r['amount'] ?>" aria-label="Amount received"><input name="admin_note" placeholder="Note (reason if rejecting)"></div>
    <div class="two"><button class="btn" name="decision" value="approve">✓ Approve & unlock</button><button class="btn danger" name="decision" value="reject" onclick="return confirm('Reject this payment?')">Reject</button></div>
  </form>
  <?php else: ?><span class="pill <?= $r['status'] === 'approved' ? 'ok' : 'warn' ?>"><?= ucfirst($r['status']) ?></span><?php if ($r['admin_note']): ?> <small><?= e($r['admin_note']) ?></small><?php endif ?>
    <?php if ($r['payment_id']): ?><a class="small" href="?p=receipt&id=<?= $r['payment_id'] ?>"> · Receipt</a><?php endif ?><?php endif ?>
</div>
<?php endforeach ?>
<?php if (!$rows): ?><p class="empty">No <?= $st ?> payment proofs</p><?php endif ?>
