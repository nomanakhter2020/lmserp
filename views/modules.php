<?php
require_role('admin'); $title = 'Modules'; $back = '?p=more';
?>
<div class="hero"><div class="big sm">🧩 Modules</div><div class="muted-l">Turn parts of the app on or off for this site. Turned-off modules disappear from menus, pages and the website — data is kept and comes back when you turn them on again.</div></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="mall_settings">
  <h3 style="margin-top:0">Edition</h3>
  <label class="modrow"><span class="mi">🏫</span><span class="grow"><b>Single Academy</b><small>One school / academy portal — courses, classes, fees, exams, shop and website</small></span><input type="radio" name="mall_mode" value="0" <?= mall() ? '' : 'checked' ?> style="width:auto"></label>
  <label class="modrow"><span class="mi">🏬</span><span class="grow"><b>Multi-Vendor Academy</b><small>Marketplace of many schools, colleges, universities &amp; academies — institute logins, online admissions, shared teacher pool</small></span><input type="radio" name="mall_mode" value="1" <?= mall() ? 'checked' : '' ?> style="width:auto"></label>
  <button class="btn block">Save edition</button></form>
<div class="card"><h3 style="margin-top:0">🧪 Multi-Vendor demo institutes</h3><p class="muted" style="margin-top:-6px;font-size:13px">10 fictional schools, colleges, universities and academies with teachers and admission enquiries — to see how the mall looks.</p>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="a" value="mall_seed"><button class="btn sm">Load demo institutes</button></form> <form method="post" style="display:inline" onsubmit="return confirm('Remove all demo institutes?')"><?= csrf_field() ?><input type="hidden" name="a" value="mall_clear"><button class="btn sm ghost">Remove demo institutes</button></form><?php if (!mall()): ?><p class="muted" style="font-size:12.5px;margin-bottom:0">Loading demo institutes switches to the Multi-Vendor Academy edition.</p><?php endif ?></div>

<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="modules_save">
<?php foreach (MODULES as $k => [$ic, $name, $desc]): ?>
  <label class="modrow"><span class="mi"><?= $ic ?></span><span class="grow"><b><?= e($name) ?></b><small><?= e($desc) ?></small></span><input type="checkbox" class="sw" name="mod_<?= $k ?>" value="1" <?= mod($k) ? 'checked' : '' ?>></label>
<?php endforeach ?>
  <p class="muted" style="font-size:13px">Always on: users & roles, fees & payments, notifications, settings, help.</p>
  <button class="btn block">Save modules</button>
</form>
