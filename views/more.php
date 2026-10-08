<?php
$title = 'More';
$groups = menu_groups(); unset($groups['Main'][0]);
?>
<?php foreach ($groups as $g => $its): if (!$its) continue; ?><h3 class="mgh"><?= e($g) ?></h3><div class="list menu"><?php foreach ($its as [$k, $i, $l]): ?>
  <a class="row" href="?p=<?= $k ?>"><span class="mi"><?= $i ?></span><b class="grow"><?= $l ?></b><span>›</span></a>
<?php endforeach ?></div><?php endforeach ?>
<div class="list menu">
  <a class="row" href="?p=logout&t=<?= csrf() ?>"><span class="mi">🚪</span><b class="grow neg">Log out</b></a>
</div>
<p class="install-hint" hidden><button class="btn block ghost" id="installBtn">📲 Install app</button></p>
<div class="sheet" id="installSheet" hidden><div class="sheet-card">
  <h3>📲 Install the app</h3>
  <div data-os="ios"><p>On iPhone / iPad (in <b>Safari</b>):</p><ol><li>Tap the <b>Share</b> button <span class="k">⬆︎</span> at the bottom</li><li>Scroll and tap <b>Add to Home Screen</b></li><li>Tap <b>Add</b></li></ol></div>
  <div data-os="inapp"><p>You opened this inside another app (WhatsApp, Facebook…). Tap <b>⋮</b> or <b>…</b> and choose <b>Open in Chrome</b> / <b>Open in browser</b>, then come back here and tap Install.</p></div>
  <div data-os="android"><p>On Android (<b>Chrome</b>):</p><ol><li>Tap the <b>⋮</b> menu at the top right</li><li>Tap <b>Install app</b> or <b>Add to Home screen</b></li><li>Tap <b>Install</b></li></ol></div>
  <div data-os="desktop"><p>On computer (<b>Chrome / Edge</b>): click the <b>install icon</b> <span class="k">⊕</span> at the right side of the address bar, or open the browser menu → <b>Install app</b>.</p></div>
  <button class="btn block" id="sheetClose">Got it</button>
</div></div>
<p class="center muted"><?= APP_NAME ?> v<?= APP_VERSION ?> · by NAFsols</p>
