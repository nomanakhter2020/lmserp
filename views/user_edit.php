<?php
require_role('admin');
$u = $id ? one('SELECT * FROM users WHERE id=?', [$id]) : ['name' => '', 'email' => '', 'phone' => '', 'role' => get('role', 'student'), 'active' => 1, 'staff_role' => 'admin'];
if (!$u) exit('Not found');
$title = $id ? 'Edit person' : 'Add person'; $back = $id ? "?p=user&id=$id" : '?p=users';
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="user_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Full name<input name="name" value="<?= e($u['name']) ?>" required></label>
  <label>Email<input name="email" type="email" value="<?= e($u['email']) ?>" required></label>
  <label>Phone / WhatsApp<input name="phone" value="<?= e($u['phone']) ?>" inputmode="tel"></label>
  <?php $roles = is_super() ? ['student', 'teacher', 'parent', 'admin'] : (staff_role() === 'hr' ? ['teacher'] : ['student', 'teacher', 'parent']); ?>
  <label>Role<select name="role" onchange="document.getElementById('srole').hidden=this.value!=='admin'"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r === 'admin' ? 'Staff (admin panel)' : ucfirst($r) ?></option><?php endforeach ?></select></label>
  <?php if (is_super()): ?><div id="srole" <?= $u['role'] === 'admin' ? '' : 'hidden' ?>><label>Staff role</label><?php foreach (STAFF_ROLES as $k => [$ic, $l, $d]): ?><label class="modrow" style="padding:8px 0"><span class="mi"><?= $ic ?></span><span class="grow"><b><?= $l ?></b><small><?= $d ?></small></span><input type="radio" name="staff_role" value="<?= $k ?>" <?= (($u['staff_role'] ?? '') ?: 'admin') === $k ? 'checked' : '' ?> style="width:auto"></label><?php endforeach ?></div><?php endif ?>
  <label>Password <small><?= $id ? '(leave blank to keep)' : '' ?></small><input name="password" type="text" minlength="6" <?= $id ? '' : 'required' ?> autocomplete="new-password"></label>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>> Active</label>
  <button class="btn block">Save</button>
</form>
