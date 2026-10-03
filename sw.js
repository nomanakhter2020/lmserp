const C = 'lmserp-v10';
const SHELL = ['assets/style.css?v=1.7.0', 'assets/app.js?v=1.7.0', 'assets/icon.svg', 'offline.html'];
self.addEventListener('install', e => { e.waitUntil(caches.open(C).then(c => c.addAll(SHELL))); self.skipWaiting(); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x => x !== C).map(x => caches.delete(x))))); self.clients.claim(); });
self.addEventListener('fetch', e => {
  const r = e.request;
  if (r.method !== 'GET') return;
  if (r.mode === 'navigate') { e.respondWith(fetch(r).catch(() => caches.match('offline.html'))); return; }
  if (new URL(r.url).pathname.includes('/assets/')) e.respondWith(caches.match(r).then(m => m || fetch(r)));
});
