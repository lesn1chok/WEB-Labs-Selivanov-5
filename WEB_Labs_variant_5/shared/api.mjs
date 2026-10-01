export let session;
export async function refreshSession(){const res=await fetch('/api.php?resource=session',{cache:'no-store'});session=await res.json();return session;}
export async function api(resource,method='GET',body,id){if(!session)await refreshSession();const response=await fetch('/api.php?resource='+resource+(id?'&id='+id:''),{method,headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf},body:body?JSON.stringify(body):undefined});const result=await response.json();if(!response.ok)throw Error(result.message||'Помилка запиту');return result;}
export function textCell(row,text){const cell=document.createElement('td');cell.textContent=String(text);row.append(cell);return cell;}
export const money=value=>Number(value).toLocaleString('uk-UA',{minimumFractionDigits:2,maximumFractionDigits:2})+' грн';

