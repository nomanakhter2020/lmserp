<?php
require_role('admin');
$u = $id ? one('SELECT * FROM users WHERE id=?', [$id]) : ['name' => '', 'email' => '', 'phone' => '', 'role' => get('role', 'student'), 'active' => 1];
if (!$u) exit('Not found');
$title = $id ? 'Edit person' : 'Add person'; $back = $id ? "?p=user&id=$id" : '?p=users';
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="user_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Full name<input name="name" value="<?= e($u['name']) ?>" required></label>
  <label>Email<input name="email" type="email" value="<?= e($u['email']) ?>" required></label>
  <label>Phone / WhatsApp<input name="phone" value="<?= e($u['phone']) ?>" inputmode="tel"></label>
  <label>Role<select name="role"><?php foreach (['student', 'teacher', 'parent', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach ?></select></label>
  <label>Password <small><?= $id ? '(leave blank to keep)' : '' ?></small><input name="password" type="text" minlength="6" <?= $id ? '' : 'required' ?> autocomplete="new-password"></label>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>> Active</label>
  <button class="btn block">Save</button>
</form>
