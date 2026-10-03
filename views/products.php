<?php
require_role('admin', 'teacher'); $me = user(); $isT = role('teacher');
$title = $isT ? 'My products' : 'Products'; $back = $isT ? '?p=more' : '?p=orders';
$f = get('f');
$w = $isT ? 'p.teacher_id=' . (int)$me['id'] : ($f === 'pending' ? 'p.review="pending"' : ($f === 'teachers' ? 'p.teacher_id IS NOT NULL' : '1=1'));
$rows = all("SELECT p.*,u.name tname,(SELECT COALESCE(SUM(qty),0) FROM order_items i JOIN orders o ON o.id=i.order_id WHERE i.product_id=p.id AND o.status<>'cancelled') sold FROM products p LEFT JOIN users u ON u.id=p.teacher_id WHERE $w ORDER BY p.review='pending' DESC, p.active DESC, p.id DESC");
$pend = $isT ? 0 : (int)val('SELECT COUNT(*) FROM products WHERE review="pending"');
?>
<?php if ($isT): ?><div class="alert">Add your books, notes or PDFs. Admin approves them before they go live. You earn <b><?= teacher_pct() + 0 ?>%</b> of every delivered sale — the institute handles delivery from its warehouse. <a href="?p=earnings"><b>My earnings →</b></a></div><?php endif ?>
<a class="btn block" href="?p=product_edit">＋ Add product</a>
<?php if (!$isT): ?><div class="chips"><a href="?p=products" class="<?= !$f ? 'on' : '' ?>">All</a><a href="?p=products&f=pending" class="<?= $f === 'pending' ? 'on' : '' ?>">Awaiting approval<?= $pend ? " ($pend)" : '' ?></a><a href="?p=products&f=teachers" class="<?= $f === 'teachers' ? 'on' : '' ?>">Teachers' products</a></div><?php endif ?>
<div class="list"><?php foreach ($rows as $p): ?>
  <div class="card prow">
    <a class="row <?= $p['active'] ? '' : 'locked' ?>" href="?p=product_edit&id=<?= $p['id'] ?>"><div class="cthumb" style="<?= $p['image'] ? "background-image:url('" . e(product_img($p)) . "')" : '' ?>"></div><div class="grow"><b><?= e($p['title']) ?></b><small><?= e($p['category']) ?> · <?= $p['type'] === 'digital' ? 'PDF' : ($p['stock'] === null ? '∞' : (int)$p['stock'] . ' in stock') ?> · <?= (int)$p['sold'] ?> sold<?= !$isT && $p['tname'] ? ' · 👩‍🏫 ' . e($p['tname']) : '' ?></small>
      <?php if ($p['review'] === 'pending'): ?><span class="pill warn">Awaiting approval</span><?php elseif ($p['review'] === 'rejected'): ?><span class="pill err">Not approved</span><?php elseif (!$p['active']): ?><span class="pill">Hidden</span><?php else: ?><span class="pill ok">Live</span><?php endif ?></div>
      <b><?= money($p['price']) ?></b></a>
    <?php if (!$isT && $p['review'] === 'pending'): ?><form method="post" class="revbtns"><?= csrf_field() ?><input type="hidden" name="a" value="product_review"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm" name="decision" value="approve">✓ Approve</button><input name="reason" placeholder="Reason (if rejecting)"><button class="btn sm ghost" name="decision" value="reject">✕ Reject</button></form><?php endif ?>
  </div>
<?php endforeach ?></div>
<?php if (!$rows): ?><p class="empty"><?= $isT ? 'You have no products yet.' : 'No products here.' ?></p><?php endif ?>
