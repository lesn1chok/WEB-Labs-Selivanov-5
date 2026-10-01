<?php
declare(strict_types=1);
require dirname(__DIR__).'/server/bootstrap.php';
$count=0;
function check(bool $value,string $label):void{global$count;if(!$value)throw new RuntimeException($label);$count++;}
$d=['customer'=>'Навчальний клієнт','phone'=>'+380000000000','garment'=>'dress','meters'=>2.5,'price'=>280,'quantity'=>1,'fittings'=>120,'complexity'=>'standard','urgency'=>0];
check(valid_order($d)===[],'Valid order');check(cost($d)['total']===1720,'Base cost');
$d['quantity']=5;check(cost($d)['total']===8170,'Five-item discount');
$d['quantity']=10;$d['urgency']=20;check(cost($d)['total']===18576,'Rush then ten-item discount');
foreach(['customer'=>'','phone'=>'wrong','meters'=>0,'price'=>0,'quantity'=>1.5,'fittings'=>-1,'garment'=>'bad','complexity'=>'bad','urgency'=>13]as$key=>$value){$invalid=$d;$invalid[$key]=$value;check(count(valid_order($invalid))>0,'Reject '.$key);}
$invalid=$d;$invalid['meters']=2.555;check(count(valid_order($invalid))>0,'Two decimals');
$encrypted=encrypt_phone('+380000000000');check($encrypted!=='+380000000000','Not plaintext');check(decrypt_phone($encrypted)==='+380000000000','Round trip');check(encrypt_phone('+380000000000')!==$encrypted,'Random IV');
$raw=base64_decode($encrypted);$raw[30]=chr(ord($raw[30])^1);try{decrypt_phone(base64_encode($raw));throw new LogicException('Tampering accepted');}catch(RuntimeException){check(true,'Authentication tag');}
$hash=password_hash('TemporaryTestOnly123!',PASSWORD_DEFAULT);check(password_verify('TemporaryTestOnly123!',$hash),'Password verification');check(!password_verify('wrong',$hash),'Wrong password');
echo "PHP assertions: $count passed\n";
