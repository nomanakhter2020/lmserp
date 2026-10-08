<?php
require_role('admin', 'institute');
$iid = role('admin') ? $id : my_inst_id(); $in = $iid ? one('SELECT * FROM institutions WHERE id=?', [$iid]) : null;
if (!$in) { echo '<p class="empty">Not found</p>'; return; }
$title = role('admin') ? $in['name'] : 'Institute profile'; $back = role('admin') ? '?p=insts' : '?p=home';
$pic = fn($name, $label, $cur, $ratio) => '<label>' . $label . '<div class="cover-pick" id="pv_' . $name . '" style="aspect-ratio:' . $ratio . ';' . ($cur ? "background-image:url('" . e(photo_url($cur)) . "')" : '') . '"><span>' . ($cur ? 'Tap to change' : '📷 Tap to add') . '</span><input type="file" name="' . $name . '" accept="image/*" onchange="const f=this.files[0];if(f){const c=document.getElementById(\'pv_' . $name . '\');c.style.backgroundImage=\'url(\'+URL.createObjectURL(f)+\')\';c.querySelector(\'span\').textContent=\'Tap to change\'}"></div></label>';
?>
<?php if (role('admin')): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="inst_status"><input type="hidden" name="id" value="<?= $iid ?>">
  <div class="rowhead"><b>Status: <span class="pill <?= ['active' => 'ok', 'pending' => 'warn', 'suspended' => 'err'][$in['status']] ?? '' ?>"><?= e(ucfirst($in['status'])) ?></span></b><a href="<?= e(inst_url($in)) ?>" target="_blank">View page ↗</a></div>
  <div class="quick" style="margin-top:10px"><?php if ($in['status'] !== 'active'): ?><button class="btn sm" name="status" value="active">✓ Approve &amp; publish</button><?php endif ?><?php if ($in['status'] !== 'suspended'): ?><button class="btn sm ghost" name="status" value="suspended">Suspend</button><?php endif ?><button class="btn sm ghost" name="featured" value="<?= $in['featured'] ? '0' : '1' ?>"><?= $in['featured'] ? '☆ Remove featured' : '★ Make featured' ?></button></div>
</form>
<?php endif ?>
<form method="post" class="card" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="a" value="inst_save"><input type="hidden" name="id" value="<?= $iid ?>">
  <label>Institute name<input name="name" value="<?= e($in['name']) ?>" required></label>
  <div class="two"><label>Type<select name="type"><?php foreach (INST_TYPES as $k => [$ic, $l]): ?><option value="<?= $k ?>" <?= $in['type'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label><label>Established (year)<input name="established" type="number" min="1800" max="<?= date('Y') ?>" value="<?= e((string)$in['established']) ?>"></label></div>
  <?= $pic('logo', 'Logo <small>(square)</small>', $in['logo'], '1/1;max-width:140px') ?>
  <?= $pic('cover', 'Cover photo <small>(campus / classroom, landscape)</small>', $in['cover'], '16/6') ?>
  <label>About <small>(history, vision, why choose you)</small><textarea name="about" rows="6"><?= e((string)$in['about']) ?></textarea></label>
  <label>Programs &amp; classes <small>(one per line — e.g. Playgroup to Grade 5, Matric Science, FSc Pre-Medical)</small><textarea name="programs" rows="4"><?= e((string)$in['programs']) ?></textarea></label>
  <label>Facilities <small>(comma separated — e.g. Science labs, Transport, Library, CCTV)</small><input name="facilities" value="<?= e((string)$in['facilities']) ?>"></label>
  <div class="two"><label>Monthly fee from (PKR)<input name="fee_min" type="number" min="0" value="<?= (int)$in['fee_min'] ?: '' ?>"></label><label>Monthly fee up to (PKR)<input name="fee_max" type="number" min="0" value="<?= (int)$in['fee_max'] ?: '' ?>"></label></div>
  <div class="two"><label>City<input name="city" value="<?= e($in['city']) ?>"></label><label>Address<input name="address" value="<?= e($in['address']) ?>"></label></div>
  <div class="two"><label>Phone<input name="phone" value="<?= e($in['phone']) ?>" inputmode="tel"></label><label>WhatsApp<input name="whatsapp" value="<?= e($in['whatsapp']) ?>" inputmode="tel"></label></div>
  <div class="two"><label>Email<input name="email" type="email" value="<?= e($in['email']) ?>"></label><label>Website<input name="website" type="url" value="<?= e($in['website']) ?>" placeholder="https://"></label></div>
  <label class="check"><input type="checkbox" name="admissions_open" value="1" <?= $in['admissions_open'] ? 'checked' : '' ?>> Admissions open (show "Apply" form)</label>
  <button class="btn block">Save profile</button>
</form>
