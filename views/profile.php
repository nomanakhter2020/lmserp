<?php
$me = user(); $title = 'My profile'; $back = '?p=more';
?>
<div class="card profile"><div class="avatar lg"><?= e(mb_strtoupper(mb_substr($me['name'], 0, 1))) ?></div>
  <div><b><?= e($me['name']) ?></b><small><?= ucfirst($me['role']) ?> · <?= e($me['email']) ?></small></div></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="profile_save">
  <label>Full name<input name="name" value="<?= e($me['name']) ?>" required></label>
  <label>Phone / WhatsApp<input name="phone" value="<?= e($me['phone']) ?>"></label>
  <h3>Change password</h3>
  <label>Current password<input name="current" type="password" autocomplete="current-password"></label>
  <label>New password<input name="password" type="password" minlength="6" autocomplete="new-password"></label>
  <button class="btn block">Save</button></form>
<a class="btn danger block" href="?p=logout&t=<?= csrf() ?>">Log out</a>
