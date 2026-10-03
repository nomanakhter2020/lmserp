<?php
require_role('admin', 'teacher');
$me = user();
$p = $id ? one('SELECT * FROM posts WHERE id=?', [$id]) : ['title' => '', 'slug' => '', 'category' => '', 'excerpt' => '', 'content' => '', 'cover' => '', 'published' => role('admin') ? 1 : 0, 'author_id' => $me['id'], 'review' => '', 'review_note' => ''];
if (!$p || ($id && !role('admin') && (int)$p['author_id'] !== (int)$me['id'])) exit('Not allowed');
$title = $id ? 'Edit article' : 'New article'; $back = '?p=posts';
$cats = array_column(all('SELECT DISTINCT category FROM posts ORDER BY category'), 'category');
$cats = array_unique(array_merge($cats, ['Study Tips', 'Exam Preparation', 'IT & Programming', 'Languages', 'Careers & Freelancing', 'Science', 'Mathematics']));
?>
<?php
$st = $p['published'] ? ['ok', '✅ Live on website'] : ($p['review'] === 'pending' ? ['warn', '⏳ Waiting for admin approval'] : ($p['review'] === 'rejected' ? ['err', '↩️ Changes requested'] : ['', '📝 Draft']));
if ($id): ?><div class="alert <?= $st[0] ?: 'warn' ?>"><b><?= $st[1] ?></b><?php if ($p['review'] === 'rejected' && $p['review_note']): ?><br><small>Admin note: <?= e($p['review_note']) ?></small><?php endif ?><?php if (!role('admin') && $p['published']): ?><br><small>Saving changes will send it for review again.</small><?php endif ?></div><?php endif ?>
<?php if ($id && role('admin') && $p['review'] === 'pending'): ?>
<form method="post" class="card review-box"><?= csrf_field() ?><input type="hidden" name="a" value="post_review"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="back" value="?p=post_edit&id=<?= $id ?>">
  <h3>Review this article</h3><input name="note" placeholder="Note for the teacher (required if sending back)">
  <div class="two"><button class="btn" name="decision" value="approve">✓ Approve &amp; publish</button><button class="btn danger" name="decision" value="reject" onclick="if(!this.form.note.value.trim()){alert('Please write a note for the teacher');return false}">↩ Send back</button></div>
</form>
<?php endif ?>
<form method="post" class="card" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="a" value="post_save"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Title<input name="title" value="<?= e($p['title']) ?>" required maxlength="220"></label>
  <label>Cover image <small>(1200×630 recommended)</small>
    <div class="cover-pick" id="cprev" style="<?= $p['cover'] ? "background-image:url('" . e(photo_url($p['cover'])) . "')" : '' ?>"><span><?= $p['cover'] ? 'Tap to change' : '📷 Tap to add cover' ?></span>
    <input type="file" name="cover" accept="image/*" onchange="const f=this.files[0];if(f){const c=document.getElementById('cprev');c.style.backgroundImage='url('+URL.createObjectURL(f)+')';c.querySelector('span').textContent='Tap to change'}"></div></label>
  <div class="two"><label>Category<input name="category" list="catlist" value="<?= e($p['category']) ?>" placeholder="e.g. Exam Preparation" required><datalist id="catlist"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach ?></datalist></label>
    <label>URL slug <small>(auto)</small><input name="slug" value="<?= e($p['slug']) ?>" placeholder="auto-from-title" pattern="[a-z0-9-]*"></label></div>
  <label>Short summary <small>(shown in Google & blog list, ~150 chars)</small><textarea name="excerpt" rows="2" maxlength="400"><?= e($p['excerpt']) ?></textarea></label>
  <label>Article
    <textarea name="content" rows="18" required placeholder="Write your article…"><?= e($p['content']) ?></textarea></label>
  <details class="fmt"><summary>Formatting help</summary><p><code>## Heading</code> · <code>### Sub-heading</code> · <code>- bullet</code> · <code>1. numbered</code> · <code>**bold**</code> · <code>*italic*</code> · <code>&gt; quote</code> · <code>[link text](https://…)</code>. Leave an empty line between paragraphs.</p></details>
  <?php if (role('admin')): ?>
  <label class="check"><input type="checkbox" name="published" value="1" <?= $p['published'] ? 'checked' : '' ?>> Publish on website</label>
  <button class="btn block">Save article</button>
  <?php else: ?>
  <p class="muted" style="font-size:13px">Articles go live only after the admin reviews and approves them.</p>
  <button class="btn block" name="submit_review" value="1">📤 Submit for review</button>
  <button class="btn ghost block" name="save_draft" value="1">💾 Save as draft</button>
  <?php endif ?>
  <?php if ($id): ?><a class="btn ghost block" href="blog/<?= e($p['slug']) ?>" target="_blank">👁 <?= $p['published'] ? 'View on website' : 'Preview' ?></a><?php endif ?>
</form>
<?php if ($id): ?><form method="post" onsubmit="return confirm('Delete this article?')"><?= csrf_field() ?><input type="hidden" name="a" value="post_delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger block">Delete article</button></form><?php endif ?>
