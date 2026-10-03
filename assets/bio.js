// Fingerprint / Face ID login (passkeys)
(function(){
const b2u=b=>btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
const u2b=s=>Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')+'==='.slice((s.length+3)%4)),c=>c.charCodeAt(0));
const tok=()=>document.querySelector('input[name=_csrf]')?.value||document.querySelector('meta[name=csrf]')?.content||'';
async function call(step,data={}){const f=new URLSearchParams({_csrf:tok(),step,...data});const r=await fetch('?p=webauthn',{method:'POST',body:f,credentials:'same-origin'});const j=await r.json().catch(()=>({error:'Server error'}));if(j.error)throw new Error(j.error);return j}
const ls={get:k=>{try{return localStorage.getItem(k)}catch(e){return null}},set:(k,v)=>{try{localStorage.setItem(k,v)}catch(e){}}};
window.Bio={
  async supported(){try{return !!(window.PublicKeyCredential&&await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable())}catch(e){return false}},
  on:()=>ls.get('bio_on')==='1',
  async enable(){
    const o=await call('reg_opts');
    o.challenge=u2b(o.challenge);o.user.id=u2b(o.user.id);o.excludeCredentials=(o.excludeCredentials||[]).map(c=>({...c,id:u2b(c.id)}));
    const c=await navigator.credentials.create({publicKey:o});const r=c.response;
    if(!r.getPublicKey)throw new Error('Please update your phone browser to use fingerprint login');
    await call('reg_save',{credId:c.id,clientData:b2u(r.clientDataJSON),authData:b2u(r.getAuthenticatorData()),publicKey:b2u(r.getPublicKey()),alg:r.getPublicKeyAlgorithm(),device:(navigator.userAgentData?.platform||(/iPhone|iPad/.test(navigator.userAgent)?'iPhone':/Android/.test(navigator.userAgent)?'Android':'Device'))});
    ls.set('bio_on','1');
  },
  async login(){
    const o=await call('login_opts');o.challenge=u2b(o.challenge);
    const c=await navigator.credentials.get({publicKey:o});const r=c.response;
    const j=await call('login',{credId:c.id,clientData:b2u(r.clientDataJSON),authData:b2u(r.authenticatorData),signature:b2u(r.signature)});
    ls.set('bio_on','1');location.href=j.go||'./';
  },
  remove:id=>call('remove',{cid:id})
};
})();
