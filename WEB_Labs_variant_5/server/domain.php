<?php
declare(strict_types=1);
function cost(array $data): array {
 $labor=['dress'=>900,'shirt'=>650,'trousers'=>750,'jacket'=>1500];
 $complexity=['standard'=>1,'detailed'=>1.35,'designer'=>1.7];
 $unit=(int)round($labor[$data['garment']]*$complexity[$data['complexity']]*100)+(int)round($data['meters']*$data['price']*100)+(int)round($data['fittings']*100);
 $subtotal=$unit*(int)$data['quantity'];$rush=(int)round($subtotal*$data['urgency']/100);$discount=(int)round(($subtotal+$rush)*($data['quantity']>=10?.1:($data['quantity']>=5?.05:0)));
 return ['unit'=>$unit/100,'subtotal'=>$subtotal/100,'rush'=>$rush/100,'discount'=>$discount/100,'total'=>($subtotal+$rush-$discount)/100];
}
function valid_order(array $d): array {
 $errors=[];
 if(!is_string($d['customer']??null)||strlen(trim($d['customer']))<2||strlen($d['customer'])>240)$errors[]='Вкажіть ім’я клієнта.';
 if(!is_string($d['phone']??null)||!preg_match('/^[+() 0-9-]{7,25}$/D',$d['phone']))$errors[]='Некоректний телефон.';
 foreach(['meters'=>[.1,20],'price'=>[1,10000],'quantity'=>[1,100],'fittings'=>[0,10000]] as $key=>[$min,$max]){if(!is_numeric($d[$key]??null)||!is_finite((float)$d[$key])||$d[$key]<$min||$d[$key]>$max)$errors[]="Некоректне поле $key.";}
 if(isset($d['quantity'])&&filter_var($d['quantity'],FILTER_VALIDATE_INT)===false)$errors[]='Кількість має бути цілою.';
 foreach(['meters','price','fittings']as$key)if(isset($d[$key])&&is_numeric($d[$key])&&abs(round((float)$d[$key]*100)-(float)$d[$key]*100)>.00001)$errors[]='Не більше двох десяткових знаків.';
 if(!in_array($d['garment']??null,['dress','shirt','trousers','jacket'],true))$errors[]='Невідомий виріб.';
 if(!in_array($d['complexity']??null,['standard','detailed','designer'],true))$errors[]='Невідома складність.';
 if(!in_array((string)($d['urgency']??''),['0','20','40'],true))$errors[]='Невідома терміновість.';
 return $errors;
}

