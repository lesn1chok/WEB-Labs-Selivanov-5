const host=document.querySelector('.dropdown'),toggle=document.getElementById('menu-toggle'),menu=document.getElementById('submenu');let timer;
function setOpen(open){toggle.setAttribute('aria-expanded',String(open));menu.hidden=!open;}
host.addEventListener('mouseenter',()=>{clearTimeout(timer);setOpen(true);});host.addEventListener('mouseleave',()=>{timer=setTimeout(()=>{if(!host.contains(document.activeElement))setOpen(false);},100);});
toggle.addEventListener('click',()=>setOpen(menu.hidden));host.addEventListener('focusout',event=>{if(!host.contains(event.relatedTarget))setOpen(false);});
host.addEventListener('keydown',event=>{if(event.key==='ArrowDown'){event.preventDefault();setOpen(true);menu.querySelector('a').focus();}});
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!menu.hidden){setOpen(false);toggle.focus();}});
document.addEventListener('click',event=>{if(!host.contains(event.target))setOpen(false);});menu.addEventListener('click',event=>{if(event.target.matches('a')){document.getElementById('selection').textContent='Обрано: '+event.target.textContent;setOpen(false);}});

