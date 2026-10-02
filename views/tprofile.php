<?php
require_role('admin', 'teacher');
$uid = role('admin') && $id ? $id : (int)user()['id'];
$t = one('SELECT * FROM users WHERE id=?', [$uid]);
if (!$t) exit('Not found');
$pr = teacher_profile($uid);
$title = 'Teacher profile'; $back = role('admin') && $uid !== (int)user()['id'] ? "?p=user&id=$uid" : '?p=more';
$rowsUI = function (string $k, array $items, array $fields) {
    $items = $items ?: [array_fill_keys(array_keys($fields), '')];
    foreach ($items as $i => $r) {
        echo '<div class="rep-row">';
        foreach ($fields as $f => [$ph, $type]) {
            $v = e($r[$f] ?? '');
            echo $type === 'ta' ? "<textarea name=\"{$k}[$i][$f]\" rows=\"2\" placeholder=\"$ph\">$v</textarea>" : "<input name=\"{$k}[$i][$f]\" value=\"$v\" placeholder=\"$ph\">";
        }
        echo '<button type="button" class="x rep-del" aria-label="Remove">✕ Remove</button></div>';
    }
};
$edu = ['degree' => ['Degree / qualification (e.g. MSc Computer Science)', 'in'], 'institute' => ['University / board', 'in'], 'year' => ['Year (e.g. 2018)', 'in'], 'detail' => ['Grade / notes (optional)', 'in']];
$exp = ['role' => ['Job title (e.g. Senior Lecturer)', 'in'], 'org' => ['Organisation', 'in'], 'period' => ['Period (e.g. 2019 – Present)', 'in'], 'detail' => ['What you did there', 'ta']];
$cert = ['name' => ['Certification name', 'in'], 'issuer' => ['Issued by', 'in'], 'year' => ['Year', 'in']];
?>
<div class="card profile"><div class="avatar lg" style="<?= $pr['photo'] ? "background:center/cover url('" . e(photo_url($pr['photo'])) . "')" : '' ?>"><?= $pr['photo'] ? '' : e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></div>
  <div class="grow"><b><?= e($t['name']) ?></b><small><?= e($pr['headline'] ?: 'Add a headline below') ?></small></div>
  <a class="btn sm ghost" href="?p=teacher&id=<?= $uid ?>" target="_blank">View CV ›</a></div>

<form method="post" enctype="multipart/form-data" class="cvform"><?= csrf_field() ?><input type="hidden" name="a" value="tprofile_save"><input type="hidden" name="user_id" value="<?= $uid ?>">
<div class="card"><h3>👤 Basic info</h3>
  <label>Profile photo <small>(square, clear face)</small><input type="file" name="photo" accept="image/*"></label>
  <?php if ($pr['photo']): ?><label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label><?php endif ?>
  <label>Headline<input name="headline" value="<?= e($pr['headline']) ?>" placeholder="e.g. English Language Teacher · IELTS Trainer"></label>
  <div class="two"><label>City<input name="city" value="<?= e($pr['city']) ?>"></label><label>Years of experience<input name="years" type="number" min="0" value="<?= (int)$pr['years'] ?>"></label></div>
  <label>About me<textarea name="bio" rows="5" placeholder="Introduce yourself: teaching style, subjects, what students will gain"><?= e($pr['bio']) ?></textarea></label>
</div>

<div class="card"><h3>🎓 Education</h3><div class="rep" data-k="edu"><?php $rowsUI('edu', $pr['education'], $edu) ?></div><button type="button" class="btn sm ghost rep-add">＋ Add education</button></div>
<div class="card"><h3>💼 Work experience</h3><div class="rep" data-k="exp"><?php $rowsUI('exp', $pr['experience'], $exp) ?></div><button type="button" class="btn sm ghost rep-add">＋ Add experience</button></div>
<div class="card"><h3>📜 Certifications</h3><div class="rep" data-k="cert"><?php $rowsUI('cert', $pr['certifications'], $cert) ?></div><button type="button" class="btn sm ghost rep-add">＋ Add certification</button></div>

<div class="card"><h3>⭐ Skills & more</h3>
  <label>Skills <small>(comma separated)</small><input name="skills" value="<?= e($pr['skills']) ?>" placeholder="Grammar, Spoken English, IELTS, Public speaking"></label>
  <label>Languages <small>(comma separated)</small><input name="languages" value="<?= e($pr['languages']) ?>" placeholder="English, Urdu, Punjabi"></label>
  <label>Achievements / awards <small>(one per line)</small><textarea name="achievements" rows="3"><?= e($pr['achievements']) ?></textarea></label>
</div>

<div class="card"><h3>🔗 Links</h3>
  <label>LinkedIn<input name="linkedin" value="<?= e($pr['linkedin']) ?>" placeholder="linkedin.com/in/…"></label>
  <label>Website<input name="website" value="<?= e($pr['website']) ?>"></label>
  <label>YouTube<input name="youtube" value="<?= e($pr['youtube']) ?>"></label>
  <label class="check"><input type="checkbox" name="public" value="1" <?= $pr['public'] ? 'checked' : '' ?>> Show my profile on the website</label>
</div>
<button class="btn block">Save profile</button>
</form>
<script>
document.querySelectorAll('.rep-add').forEach(b => b.addEventListener('click', () => {
  const rep = b.previousElementSibling, rows = rep.querySelectorAll('.rep-row'), tpl = rows[rows.length - 1].cloneNode(true);
  const n = Date.now();
  tpl.querySelectorAll('input,textarea').forEach(el => { el.value = ''; el.name = el.name.replace(/\[\d+\]/, '[' + n + ']'); });
  rep.appendChild(tpl); tpl.querySelector('input,textarea').focus();
}));
document.addEventListener('click', e => {
  if (!e.target.classList.contains('rep-del')) return;
  const row = e.target.closest('.rep-row'), rep = row.parentNode;
  if (rep.querySelectorAll('.rep-row').length > 1) row.remove(); else row.querySelectorAll('input,textarea').forEach(el => el.value = '');
});
</script>
