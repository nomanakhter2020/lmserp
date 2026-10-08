<?php $inst = setting('institute', APP_NAME); $url = abs_url('install'); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<base href="<?= e(BASE) ?>"><title>Install <?= e($inst) ?> app</title>
<meta name="description" content="Install the <?= e($inst) ?> app on Android, iPhone, Windows, Mac or Linux — free, no app store needed.">
<meta property="og:title" content="Install the <?= e($inst) ?> app"><meta property="og:description" content="One tap install on Android, iPhone, Windows, Mac and Linux."><meta property="og:image" content="<?= e(abs_url('assets/icon-512.png')) ?>"><meta property="og:url" content="<?= e($url) ?>">
<meta name="theme-color" content="#4f46e5"><link rel="manifest" href="manifest.json"><link rel="icon" href="assets/icon.svg"><link rel="apple-touch-icon" href="assets/icon-192.png">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Inter,sans-serif;color:#151629;background:radial-gradient(900px 500px at 80% -10%,#a78bfa55,transparent),linear-gradient(160deg,#eef0ff,#f8f7ff 60%,#fff);display:flex;align-items:center;justify-content:center;padding:24px 16px}
.box{width:100%;max-width:520px;text-align:center}.logo{width:96px;height:96px;border-radius:24px;box-shadow:0 18px 40px -16px #4f46e5aa}
h1{font-size:28px;margin:16px 0 4px;letter-spacing:-.02em}.sub{color:#6b7085;margin:0 0 26px}
.btns{display:grid;gap:12px}.b{display:flex;align-items:center;gap:14px;width:100%;padding:16px 18px;border-radius:18px;border:1.5px solid #e4e5f1;background:#fff;font:inherit;cursor:pointer;text-align:left;transition:.15s;color:inherit}
.b:hover{border-color:#4f46e5;transform:translateY(-1px)}.b.me{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-color:transparent;box-shadow:0 14px 30px -14px #4f46e5}
.b svg{width:30px;height:30px;flex:none}.b b{display:block;font-size:17px}.b small{opacity:.75;font-size:13px}.b .go{margin-left:auto;font-weight:800;font-size:14px;white-space:nowrap}
.how{display:none;text-align:left;background:#fff;border:1px solid #e4e5f1;border-radius:16px;padding:6px 18px 12px;margin-top:-4px}.how.on{display:block}.how ol{padding-left:20px;margin:10px 0}.how li{margin:6px 0}.k{display:inline-block;border:1px solid #d7d9e8;border-radius:7px;padding:0 7px;font-weight:700;background:#f6f6fc}
.done{display:none;background:#ecfdf5;color:#065f46;border-radius:14px;padding:14px;font-weight:700;margin-bottom:14px}.foot{margin-top:24px;font-size:13px;color:#8a8fa8}.foot a{color:#4f46e5;font-weight:700;text-decoration:none}
</style></head><body><div class="box">
<img class="logo" src="assets/icon-192.png" alt="">
<h1><?= e($inst) ?></h1><p class="sub">Install the free app — opens full screen like a normal app, no app store needed.</p>
<div class="done" id="done">✅ App installed! Open it from your home screen or apps list.</div>
<div class="btns">
  <button class="b" data-os="android"><svg viewBox="0 0 24 24" fill="#3DDC84"><path d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.86 3.22a11.4 11.4 0 0 0-9.84 0L5.22 5.92a.38.38 0 1 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52zM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/></svg><span><b>Android</b><small>Chrome · Samsung Internet</small></span><span class="go">Install</span></button>
  <div class="how" id="how-android"><ol><li>Open this page in <b>Chrome</b> (not inside WhatsApp/Facebook — tap <span class="k">⋮</span> → <b>Open in Chrome</b>)</li><li>Tap <span class="k">⋮</span> menu at the top right</li><li>Tap <b>Install app</b> or <b>Add to Home screen</b> → <b>Install</b></li></ol></div>
  <button class="b" data-os="ios"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.37 1.43c0 1.14-.42 2.2-1.25 3.06-.99 1.02-2.18 1.6-3.47 1.5a3.5 3.5 0 0 1-.03-.43c0-1.1.48-2.27 1.33-3.15.42-.44.96-.8 1.6-1.09.65-.28 1.26-.44 1.83-.47.01.2-.01.4-.01.58zM20.9 17.1c-.36.83-.79 1.6-1.29 2.31-.68.97-1.24 1.64-1.67 2.01-.67.61-1.39.93-2.16.94-.55 0-1.22-.16-1.99-.47-.78-.32-1.49-.47-2.15-.47-.69 0-1.42.15-2.21.47-.79.32-1.42.48-1.91.5-.74.03-1.48-.29-2.21-.97-.47-.41-1.06-1.11-1.76-2.1A14.6 14.6 0 0 1 1.3 15.1c-.47-1.36-.71-2.67-.71-3.94 0-1.46.31-2.71.94-3.77a5.5 5.5 0 0 1 1.98-2c.83-.5 1.72-.75 2.68-.77.53 0 1.22.16 2.08.48.86.32 1.41.48 1.65.48.18 0 .79-.19 1.83-.57.98-.35 1.81-.5 2.49-.44 1.84.15 3.22.87 4.14 2.18-1.65 1-2.46 2.4-2.44 4.19.01 1.4.52 2.56 1.52 3.48.45.43.96.76 1.52.99-.12.36-.25.7-.38 1.04z"/></svg><span><b>iPhone / iPad</b><small>Safari</small></span><span class="go">How to</span></button>
  <div class="how" id="how-ios"><ol><li>Open this page in <b>Safari</b></li><li>Tap the <b>Share</b> button <span class="k">⬆︎</span> at the bottom</li><li>Scroll down, tap <b>Add to Home Screen</b> → <b>Add</b></li></ol></div>
  <button class="b" data-os="desktop"><svg viewBox="0 0 24 24" fill="#0078D4"><path d="M3 5.5 10 4.5v7H3zM11 4.35 21 3v8.5H11zM3 12.5h7v7l-7-1zM11 12.5h10V21l-10-1.4z"/></svg><span><b>Windows / Mac</b><small>Chrome · Edge</small></span><span class="go">Install</span></button>
  <div class="how" id="how-desktop"><ol><li>Open this page in <b>Chrome</b> or <b>Microsoft Edge</b></li><li>Click the <b>install icon</b> <span class="k">⊕</span> at the right side of the address bar</li><li>Click <b>Install</b> — the app appears in your Start menu / Applications</li></ol><p style="font-size:13px;color:#6b7085">Mac Safari: <b>File → Add to Dock</b>.</p></div>
  <button class="b" data-os="linux"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 2c-1.9 0-3.1 1.6-3.1 4 0 .8.1 1.5.1 2.1 0 .9-1.2 2.3-2 3.6-.9 1.4-1.6 3-1.6 4.4 0 .3 0 .6.1.9-.8.5-2.2.9-2.2 1.9 0 1.1 2.1 1.1 3.3 1.7 1 .5 1.8 1.4 3 1.4 1 0 1.6-.6 2.1-1.1h1.1c.5.5 1.1 1.1 2.1 1.1 1.2 0 2-.9 3-1.4 1.2-.6 3.3-.6 3.3-1.7 0-1-1.4-1.4-2.2-1.9l.1-.9c0-1.4-.7-3-1.6-4.4-.8-1.3-2-2.7-2-3.6 0-.6.1-1.3.1-2.1 0-2.4-1.2-4-3.1-4zm-1.3 3.6c.4 0 .6.5.6 1s-.3.9-.6.9-.6-.4-.6-.9.2-1 .6-1zm2.7 0c.4 0 .6.5.6 1s-.3.9-.6.9-.6-.4-.6-.9.2-1 .6-1zm-1.4 2.6c.8 0 1.9.6 1.9 1s-1.1 1.1-1.9 1.1-1.9-.6-1.9-1.1 1.1-1 1.9-1z"/></svg><span><b>Linux</b><small>Chrome · Chromium · Edge</small></span><span class="go">Install</span></button>
  <div class="how" id="how-linux"><ol><li>Open this page in <b>Chrome</b>, <b>Chromium</b> or <b>Edge</b></li><li>Click the install icon <span class="k">⊕</span> in the address bar (or menu <span class="k">⋮</span> → <b>Install</b>)</li><li>Click <b>Install</b> — it appears in your applications menu</li></ol></div>
</div>
<p class="foot">Already installed? <a href="./?p=login">Open <?= e($inst) ?> →</a></p>
</div>
<script>
if('serviceWorker' in navigator)navigator.serviceWorker.register('sw.js');
let dp=null;window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();dp=e});
window.addEventListener('appinstalled',()=>{document.getElementById('done').style.display='block'});
const ua=navigator.userAgent,os=/iPhone|iPad|iPod/.test(ua)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1)?'ios':/Android/.test(ua)?'android':/Linux/.test(ua)&&!/Android/.test(ua)?'linux':'desktop';
const box=document.querySelector('.btns'),mine=box.querySelector('[data-os="'+os+'"]');mine.classList.add('me');box.prepend(document.getElementById('how-'+os));box.prepend(mine);
if(matchMedia('(display-mode: standalone)').matches)document.getElementById('done').style.display='block';
document.querySelectorAll('.b').forEach(b=>b.onclick=async()=>{const o=b.dataset.os;
  if(dp&&o===os&&o!=='ios'){dp.prompt();const r=await dp.userChoice;dp=null;if(r.outcome==='accepted')return}
  document.querySelectorAll('.how').forEach(h=>h.classList.toggle('on',h.id==='how-'+o&&!h.classList.contains('on')));});
</script></body></html>
