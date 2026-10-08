// Show/hide toggle (eye) for every password field
(function(){function add(i){if(i.dataset.eye)return;i.dataset.eye=1;const w=document.createElement('span');w.className='pwwrap';i.parentNode.insertBefore(w,i);w.appendChild(i);
const b=document.createElement('button');b.type='button';b.className='pweye';b.setAttribute('aria-label','Show password');
const on='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const off='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>';
b.innerHTML=on;b.onclick=()=>{const s=i.type==='password';i.type=s?'text':'password';b.innerHTML=s?off:on;b.setAttribute('aria-label',s?'Hide password':'Show password');i.focus()};w.appendChild(b)}
function run(){document.querySelectorAll('input[type=password]').forEach(add)}
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',run):run()})();
