import {runLanguageExamples} from './examples.mjs';
const output=document.getElementById('results');
const run=()=>{const lines=runLanguageExamples();output.textContent=lines.join('\n');lines.forEach(line=>console.log(line));};document.getElementById('run').addEventListener('click',run);run();
document.getElementById('dom').addEventListener('click',()=>{
 let element=document.getElementById('myElementId');const classes=document.getElementsByClassName('myClass'),paragraphs=document.getElementsByTagName('p'),firstDiv=document.querySelector('div'),allDivs=document.querySelectorAll('div');
 element.textContent='Новий текст';element.innerHTML='<strong>Новий текст з HTML</strong>';
 let img=document.querySelector('#demo-image');img.setAttribute('src','/lab01/music.svg');img.alt='New Image Description';element.style.color='red';element.style.fontSize='20px';element.classList.add('new-class');element.classList.remove('old-class');
 let newParagraph=document.createElement('p');newParagraph.textContent='This is a new paragraph.';document.querySelector('#dom-status').appendChild(newParagraph);let gone=document.getElementById('removeMe');if(gone)gone.parentNode.removeChild(gone);
 const firstChild=element.firstChild,parent=firstChild.parentNode;
 console.log({classes:classes.length,paragraphs:paragraphs.length,firstDiv:firstDiv.tagName,allDivs:allDivs.length,parent:parent.id,childNodes:parent.childNodes.length,lastChild:parent.lastChild.nodeName,previousSibling:element.previousSibling?.nodeName,nextSibling:element.nextSibling?.nodeName});
});
document.getElementById('myButton').addEventListener('click',()=>{document.getElementById('dom-status').textContent='Подію click оброблено.';alert('Button clicked!');});
document.getElementById('myButton').addEventListener('mouseover',event=>{event.currentTarget.classList.add('hover-example');console.log('mouseover');});
document.getElementById('myButton').addEventListener('mouseout',event=>{event.currentTarget.classList.remove('hover-example');console.log('mouseout');});
document.getElementById('demo-link').addEventListener('click',event=>{event.preventDefault();document.getElementById('dom-status').textContent='Перехід скасовано через preventDefault.';});
const form=document.getElementById('myForm');const usernameInput=form.elements['username'];form.addEventListener('submit',event=>{event.preventDefault();document.getElementById('form-status').textContent=usernameInput.value.trim()?'Форму перевірено: '+usernameInput.value:"Поле «Ім’я користувача» не може бути порожнім";});
usernameInput.addEventListener('input',()=>console.log('input',usernameInput.value));usernameInput.addEventListener('change',()=>console.log('change'));usernameInput.addEventListener('keydown',event=>console.log('keydown',event.key));usernameInput.addEventListener('keyup',event=>console.log('keyup',event.key));window.addEventListener('load',()=>console.log('load'));

