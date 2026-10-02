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

// ---- custom YouTube player (no links out to youtube.com) ----
(function () {
  const box = document.querySelector('.yt[data-yt]');
  if (!box) return;
  const id = box.dataset.yt, $ = s => box.querySelector(s);
  box.style.backgroundImage = `url(https://i.ytimg.com/vi/${id}/hqdefault.jpg)`;
  box.classList.add('paused');
  const fmt = s => { s = Math.floor(s || 0); const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = String(s % 60).padStart(2, '0'); return h ? `${h}:${String(m).padStart(2, '0')}:${x}` : `${m}:${x}`; };
  let player, ready = false, timer, idle;
  window.onYouTubeIframeAPIReady = () => {
    player = new YT.Player('ytp', {
      videoId: id, host: 'https://www.youtube-nocookie.com',
      playerVars: { controls: 0, rel: 0, modestbranding: 1, playsinline: 1, disablekb: 1, fs: 0, iv_load_policy: 3, cc_load_policy: 0 },
      events: {
        onReady: () => { ready = true; $('.yt-d').textContent = fmt(player.getDuration()); },
        onStateChange: e => {
          const playing = e.data === YT.PlayerState.PLAYING;
          box.classList.toggle('paused', !playing);
          $('.yt-pp').textContent = playing ? '❚❚' : '▶';
          clearInterval(timer);
          if (playing) { timer = setInterval(tick, 500); poke(); }
          if (e.data === YT.PlayerState.ENDED) { player.seekTo(0); player.pauseVideo(); }
        }
      }
    });
  };
  const s = document.createElement('script'); s.src = 'https://www.youtube.com/iframe_api'; document.head.appendChild(s);
  function tick() { const d = player.getDuration(), t = player.getCurrentTime(); $('.yt-t').textContent = fmt(t); $('.yt-d').textContent = fmt(d); if (d) $('.yt-seek').value = t / d * 1000; }
  function toggle() { if (!ready) return; player.getPlayerState() === YT.PlayerState.PLAYING ? player.pauseVideo() : player.playVideo(); }
  function poke() { box.classList.remove('idle'); clearTimeout(idle); idle = setTimeout(() => box.classList.add('idle'), 2500); }
  $('.yt-shield').addEventListener('click', () => { box.classList.contains('idle') ? poke() : toggle(); });
  $('.yt-big').addEventListener('click', toggle);
  $('.yt-pp').addEventListener('click', toggle);
  $('.yt-seek').addEventListener('input', e => { if (ready) { player.seekTo(player.getDuration() * e.target.value / 1000, true); poke(); } });
  const speeds = [1, 1.25, 1.5, 2, 0.75]; let si = 0;
  $('.yt-sp').addEventListener('click', () => { if (!ready) return; si = (si + 1) % speeds.length; player.setPlaybackRate(speeds[si]); $('.yt-sp').textContent = speeds[si] + 'x'; });
  $('.yt-fs').addEventListener('click', () => {
    if (document.fullscreenElement) return document.exitFullscreen();
    if (box.requestFullscreen) box.requestFullscreen().then(() => screen.orientation && screen.orientation.lock && screen.orientation.lock('landscape').catch(() => {})).catch(() => box.classList.toggle('fs'));
    else box.classList.toggle('fs');
  });
  box.addEventListener('contextmenu', e => e.preventDefault());
})();
