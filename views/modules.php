<?php
require_role('admin'); $title = 'Modules'; $back = '?p=more';
?>
<div class="hero"><div class="big sm">🧩 Modules</div><div class="muted-l">Turn parts of the app on or off for this site. Turned-off modules disappear from menus, pages and the website — data is kept and comes back when you turn them on again.</div></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="modules_save">
<?php foreach (MODULES as $k => [$ic, $name, $desc]): ?>
  <label class="modrow"><span class="mi"><?= $ic ?></span><span class="grow"><b><?= e($name) ?></b><small><?= e($desc) ?></small></span><input type="checkbox" class="sw" name="mod_<?= $k ?>" value="1" <?= mod($k) ? 'checked' : '' ?>></label>
<?php endforeach ?>
  <p class="muted" style="font-size:13px">Always on: users & roles, fees & payments, notifications, settings, help.</p>
  <button class="btn block">Save modules</button>
</form>
