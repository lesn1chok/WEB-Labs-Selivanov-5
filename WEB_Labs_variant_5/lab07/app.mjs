import{api,refreshSession}from'/shared/api.mjs';
const message=document.querySelector('#message');async function state(){const s=await refreshSession();document.querySelector('#session-status').textContent=(s.authenticated?'Користувач увійшов.':'Користувач не увійшов.')+' З’єднання: '+(s.https?'HTTPS':'HTTP (навчальний локальний запуск)');}
document.querySelector('#auth').addEventListener('submit',async e=>{e.preventDefault();const d=Object.fromEntries(new FormData(e.target));d.action=e.submitter.value;try{const r=await api('auth','POST',d);message.textContent=r.message;message.classList.remove('error');await state();}catch(error){message.textContent=error.message;message.classList.add('error');}});
document.querySelector('#logout').addEventListener('click',async()=>{try{message.textContent=(await api('auth','POST',{action:'logout'})).message;await state();}catch(e){message.textContent=e.message;}});
document.querySelector('#csrf-test').addEventListener('click',async()=>{const res=await fetch('/api.php?resource=orders',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});document.querySelector('#csrf-result').textContent='HTTP '+res.status+': '+(await res.json()).message;});
await state();

