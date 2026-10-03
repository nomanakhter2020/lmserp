<?php
require_role('admin');
$title = 'Website pages'; $back = '?p=more';
$slug = isset(LEGAL_PAGES[get('slug')]) ? get('slug') : '';
?>
<?php if (!$slug): ?>
<p class="muted">These pages are required for Google AdSense. Ready-made text is filled in automatically with your institute name and email — edit if you like.</p>
<div class="list"><?php foreach (LEGAL_PAGES as $k => $v): ?>
  <a class="row" href="?p=pages_edit&slug=<?= $k ?>"><div class="grow"><b><?= $v ?></b><small>/<?= $k ?> · <?= trim(setting('page_' . $k)) !== '' ? 'Customised' : 'Default text' ?></small></div><span>›</span></a>
<?php endforeach ?></div>
<?php else: ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="a" value="page_save"><input type="hidden" name="slug" value="<?= e($slug) ?>">
  <h3><?= LEGAL_PAGES[$slug] ?></h3>
  <textarea name="content" rows="22"><?= e(legal_content($slug)) ?></textarea>
  <details class="fmt"><summary>Formatting help</summary><p><code>## Heading</code> · <code>- bullet</code> · <code>**bold**</code> · <code>[link](https://…)</code></p></details>
  <button class="btn block">Save page</button>
  <a class="btn ghost block" href="<?= e($slug) ?>" target="_blank">👁 View page</a>
</form>
<form method="post" onsubmit="return confirm('Reset to the default text?')"><?= csrf_field() ?><input type="hidden" name="a" value="page_save"><input type="hidden" name="slug" value="<?= e($slug) ?>"><input type="hidden" name="content" value=""><button class="btn danger block">Reset to default</button></form>
<?php endif ?>
