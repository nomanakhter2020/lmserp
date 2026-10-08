<?php /* expects $in */ $ty = INST_TYPES[$in['type']] ?? ['🏫', 'Institute']; ?>
<a class="icard" href="<?= e(inst_url($in)) ?>">
  <div class="icover" style="<?= $in['cover'] ? "background-image:url('" . e(inst_img($in, 'cover')) . "')" : '' ?>"><?php if ($in['featured']): ?><i class="ibadge">★ Featured</i><?php endif ?><?php if ($in['admissions_open']): ?><i class="ibadge open">Admissions open</i><?php endif ?></div>
  <div class="ibody">
    <div class="ilogo"><?php if ($in['logo']): ?><img src="<?= e(inst_img($in)) ?>" alt="" loading="lazy"><?php else: ?><span><?= $ty[0] ?></span><?php endif ?></div>
    <small><?= $ty[1] ?><?= $in['city'] ? ' · ' . e($in['city']) : '' ?></small>
    <h3><?= e($in['name']) ?></h3>
    <?php if ($in['fee_min'] || $in['fee_max']): ?><p class="ifee">Fee: <?= $in['fee_min'] ? money($in['fee_min']) : '' ?><?= $in['fee_max'] && $in['fee_max'] != $in['fee_min'] ? ' – ' . money($in['fee_max']) : '' ?> <small>/ month</small></p><?php endif ?>
    <span class="imore">View details →</span>
  </div>
</a>
