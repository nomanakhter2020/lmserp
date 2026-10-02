if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');
let deferred;
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault(); deferred = e;
  const h = document.querySelector('.install-hint'); if (h) h.hidden = false;
});
document.addEventListener('click', e => {
  if (e.target.id === 'installBtn' && deferred) { deferred.prompt(); deferred = null; }
});
// prevent double submits
document.addEventListener('submit', e => {
  const b = e.target.querySelector('button:not(.x)');
  if (b && !e.defaultPrevented) setTimeout(() => { b.disabled = true; b.style.opacity = .6; }, 0);
});
