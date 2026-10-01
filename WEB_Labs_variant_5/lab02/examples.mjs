export function runLanguageExamples() {
  const lines=[]; const log=(label,value)=>lines.push(label+': '+String(value));
  var name='John'; let age=30; const birthYear=2024;
  log('var / let / const', name+' / '+age+' / '+birthYear);
  let x=10,y=3.14,greeting='Hello, world!',isAdult=true,notDefined,emptyValue=null;
  let uniqueId=Symbol('id'),bigNumber=123456789012345678901234567890n;
  for(const [label,value] of Object.entries({x,y,greeting,isAdult,notDefined,emptyValue,uniqueId,bigNumber})) log(label,value);
  log('Арифметика + - * / % **',[10+5,10-5,10*5,10/5,10%3,2**3].join(', '));
  log('Math.pow',Math.pow(2,3)); log('== / ===',[5=='5',5==='5'].join(', '));
  log('!= !== < > <= >=',[5!=3,5!=='5',2<4,5>3,2<=2,4>=4].join(', '));
  log('&& || !',[(5>3)&&(2<4),false||true,!false].join(', '));
  let a=10;a+=5;log('+=',a);a-=5;a*=2;a/=4;log('-= *= /=',a);
  function greet(value){return 'Hello, '+value;} let sum=function(a,b){return a+b;};let multiply=(a,b)=>a*b;
  log('Функції',[greet('John'),sum(2,3),multiply(3,4)].join(', '));
  let car={make:'Toyota',model:'Camry',year:2024};car.color='blue';log('Об’єкт',car.make+' '+car['model']+' '+car.color);
  let person={name:'Alice',age:25,greet(){return 'Hello!';}};log('Метод',person.greet());
  let fruits=['apple','banana','cherry'];log('Індекс масиву',fruits[0]);fruits.push('orange');log('push',fruits);log('pop',fruits.pop());
  log('shift',fruits.shift());fruits.unshift('apple');log('unshift',fruits);
  fruits.forEach((fruit,index)=>log('forEach '+index,fruit));log('map',[1,2,3].map(n=>n*2));log('filter',[1,2,3,4,5].filter(n=>n>2));
  for(let i=0;i<fruits.length;i++)log('for fruits '+i,fruits[i]);
  let temperature=30;let weather;if(temperature>25)weather="It's hot outside.";else weather="It's not that hot outside.";log('if else',weather);
  let score=85;let grade;if(score>=90)grade='Excellent';else if(score>=75)grade='Good';else grade='Needs Improvement';log('else if',grade);
  let isLoggedIn=true;log('Тернарний',isLoggedIn?'Welcome back!':'Please log in.');
  let day=3,dayName;switch(day){case 1:dayName='Monday';break;case 2:dayName='Tuesday';break;case 3:dayName='Wednesday';break;default:dayName='Unknown';}log('switch',dayName);
  for(let i=0;i<5;i++)log('for','Iteration '+i);
  let i=0;while(i<5){log('while','Iteration '+i);i++;}
  i=0;do{log('do while','Iteration '+i);i++;}while(i<5);
  const odd=[];for(let i=0;i<10;i++){if(i===5)break;if(i%2===0)continue;odd.push(i);}log('break / continue',odd);
  return lines;
}

