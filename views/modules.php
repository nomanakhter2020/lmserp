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
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="mall_settings">
  <label class="modrow"><span class="mi">🏬</span><span class="grow"><b>EduMall mode (marketplace)</b><small>Website becomes a directory of many schools, colleges &amp; universities with online admissions, institute logins and a shared teacher pool</small></span><input type="checkbox" class="sw" name="mall_mode" value="1" <?= mall() ? 'checked' : '' ?>></label>
  <button class="btn block">Save</button></form>
<?php if (mall()): ?><div class="card"><h3 style="margin-top:0">🧪 Demo data</h3><p class="muted" style="margin-top:-6px;font-size:13px">10 fictional schools, colleges, universities and academies with teachers and admission enquiries — to see how the mall looks.</p>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="a" value="mall_seed"><button class="btn sm">Load demo institutes</button></form> <form method="post" style="display:inline" onsubmit="return confirm('Remove all demo institutes?')"><?= csrf_field() ?><input type="hidden" name="a" value="mall_clear"><button class="btn sm ghost">Remove demo institutes</button></form></div><?php endif ?>
