<?php
declare(strict_types=1);
require __DIR__.'/server/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$resource=$_GET['resource']??'';$method=$_SERVER['REQUEST_METHOD'];
try {
 if($resource==='health'){if($method!=='GET')respond(['message'=>'Method not allowed'],405);db()->query('SELECT 1')->fetch();respond(['ok'=>true,'database'=>'SQLite','time'=>gmdate('c')]);}
 if($resource==='sensors'){if($method!=='GET')respond(['message'=>'Method not allowed'],405);respond(external_json((getenv('SENSOR_URL')?:'http://127.0.0.1:8091').'/sensors'));}
 setup_session();
 if($resource==='session'){if($method!=='GET')respond(['message'=>'Method not allowed'],405);respond(['csrf'=>$_SESSION['csrf'],'authenticated'=>!empty($_SESSION['uid']),'https'=>getenv('LAB_HTTPS')==='1','passwords'=>'password_hash / password_verify','encryption'=>'AES-256-GCM']);}
 if(!in_array($method,['GET','POST','PUT','DELETE'],true))respond(['message'=>'Method not allowed'],405);
 if($method!=='GET')csrf();
 $pdo=db();
 if($resource==='auth') {
  if($method!=='POST')respond(['message'=>'Method not allowed'],405);
  $d=input_data();$action=$d['action']??'';
  if($action==='logout'){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));respond(['message'=>'Ви вийшли.']);}
  $email=$d['email']??'';$password=$d['password']??'';
  if(!is_string($email)||strlen($email)>150||!filter_var($email,FILTER_VALIDATE_EMAIL)||!is_string($password)||strlen($password)<12||strlen($password)>72)respond(['message'=>'Потрібен email та пароль 12–72 байти.'],422);
  if($action==='register'){$hash=password_hash($password,PASSWORD_DEFAULT);try{$s=$pdo->prepare('INSERT INTO users(email,password_hash)VALUES(?,?)');$s->execute([$email,$hash]);}catch(PDOException){respond(['message'=>'Обліковий запис уже існує.'],409);}respond(['message'=>'Обліковий запис створено. Тепер увійдіть.'],201);}
  if($action!=='login')respond(['message'=>'Невідома дія.'],422);
  $ip=$_SERVER['REMOTE_ADDR'];$s=$pdo->prepare('SELECT * FROM login_attempts WHERE ip=?');$s->execute([$ip]);$attempt=$s->fetch();if($attempt&&$attempt['attempts']>=5&&time()-$attempt['last_time']<300)respond(['message'=>'Забагато спроб. Спробуйте через 5 хвилин.'],429);
  $s=$pdo->prepare('SELECT id,password_hash FROM users WHERE email=?');$s->execute([$email]);$u=$s->fetch();if(!$u||!password_verify($password,$u['password_hash'])){$s=$pdo->prepare('INSERT INTO login_attempts(ip,attempts,last_time)VALUES(?,1,?) ON CONFLICT(ip)DO UPDATE SET attempts=CASE WHEN ?-last_time>=300 THEN 1 ELSE attempts+1 END,last_time=?');$s->execute([$ip,time(),time(),time()]);respond(['message'=>'Некоректні облікові дані.'],401);}
  $s=$pdo->prepare('DELETE FROM login_attempts WHERE ip=?');$s->execute([$ip]);session_regenerate_id(true);$_SESSION['uid']=(int)$u['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));respond(['message'=>'Вхід успішний.']);
 }
 $uid=user_id();
 if($resource==='orders') {
  $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if(in_array($method,['PUT','DELETE'],true)&&!$id)respond(['message'=>'Некоректний id.'],400);
  if($method==='GET'){$s=$pdo->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');$s->execute([$uid]);$items=$s->fetchAll();foreach($items as &$row){$row['phone']=decrypt_phone($row['phone_encrypted']);unset($row['phone_encrypted']);}respond(['items'=>$items]);}
  if($method==='DELETE'){$s=$pdo->prepare('DELETE FROM orders WHERE id=? AND user_id=?');$s->execute([$id,$uid]);if(!$s->rowCount())respond(['message'=>'Запис не знайдено.'],404);respond(['deleted'=>$id]);}
  $d=input_data();$errors=valid_order($d);if($errors)respond(['message'=>implode(' ',$errors)],422);$calculated=cost($d);$values=[trim($d['customer']),encrypt_phone($d['phone']),$d['garment'],$d['meters'],$d['price'],$d['quantity'],$d['complexity'],$d['urgency'],$d['fittings'],$calculated['total']];
  if($method==='POST'){$s=$pdo->prepare('INSERT INTO orders(customer,phone_encrypted,garment,meters,price,quantity,complexity,urgency,fittings,total,user_id)VALUES(?,?,?,?,?,?,?,?,?,?,?)');$s->execute([...$values,$uid]);respond(['id'=>(int)$pdo->lastInsertId(),'cost'=>$calculated],201);}
  $s=$pdo->prepare('SELECT id FROM orders WHERE id=? AND user_id=?');$s->execute([$id,$uid]);if(!$s->fetch())respond(['message'=>'Запис не знайдено.'],404);
  $s=$pdo->prepare('UPDATE orders SET customer=?,phone_encrypted=?,garment=?,meters=?,price=?,quantity=?,complexity=?,urgency=?,fittings=?,total=? WHERE id=? AND user_id=?');$s->execute([...$values,$id,$uid]);respond(['id'=>$id,'cost'=>$calculated]);
 }
 if($resource==='materials') {
  if($method==='GET')respond(['items'=>$pdo->query('SELECT * FROM materials ORDER BY name')->fetchAll()]);
  if($method!=='POST')respond(['message'=>'Method not allowed'],405);
  $data=external_json((getenv('SUPPLIER_URL')?:'http://127.0.0.1:8092').'/materials');$items=$data['items']??null;
  if(!is_array($items)||count($items)>100)respond(['message'=>'Некоректне API постачальника.'],502);
  foreach($items as $item){if(!is_array($item)||!is_string($item['sku']??null)||!preg_match('/^[A-Z0-9_-]{1,32}$/D',$item['sku'])||!is_string($item['name']??null)||strlen($item['name'])>120||!is_numeric($item['price']??null)||$item['price']<=0||$item['price']>10000||!is_numeric($item['stock']??null)||$item['stock']<0||$item['stock']>100000)respond(['message'=>'Некоректний запис постачальника.'],502);}
  $pdo->beginTransaction();$consumed=$pdo->prepare('SELECT COALESCE(SUM(meters*quantity),0) FROM production WHERE sku=?');$s=$pdo->prepare('INSERT INTO materials(sku,name,price,stock)VALUES(?,?,?,?) ON CONFLICT(sku)DO UPDATE SET name=excluded.name,price=excluded.price,stock=excluded.stock');foreach($items as $item){$consumed->execute([$item['sku']]);$available=max(0,$item['stock']-(float)$consumed->fetchColumn());$s->execute([$item['sku'],$item['name'],$item['price'],$available]);}$pdo->commit();respond(['message'=>'Залишки оновлено з локального API-симулятора.','source'=>$data['source']??'']);
 }
 if($resource==='notifications'){if($method!=='GET')respond(['message'=>'Method not allowed'],405);$s=$pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100');$s->execute([$uid]);respond(['items'=>$s->fetchAll()]);}
 if($resource==='production') {
  if($method==='GET'){$s=$pdo->prepare('SELECT p.*,m.name AS material FROM production p JOIN materials m ON p.sku=m.sku WHERE p.user_id=? ORDER BY p.id DESC');$s->execute([$uid]);respond(['items'=>$s->fetchAll()]);}
  $d=input_data();
  if($method==='PUT'){$id=filter_var($d['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id||!in_array($d['status']??null,['new','working','done'],true))respond(['message'=>'Некоректний статус або id.'],422);$pdo->beginTransaction();$s=$pdo->prepare('SELECT status FROM production WHERE id=? AND user_id=?');$s->execute([$id,$uid]);$old=$s->fetch();if(!$old){$pdo->rollBack();respond(['message'=>'Запис не знайдено.'],404);}$s=$pdo->prepare('UPDATE production SET status=? WHERE id=? AND user_id=?');$s->execute([$d['status'],$id,$uid]);if($old['status']!==$d['status']){$s=$pdo->prepare('INSERT INTO notifications(user_id,order_id,message)VALUES(?,?,?)');$s->execute([$uid,$id,'Замовлення №'.$id.': статус змінено на '.$d['status']]);}$pdo->commit();respond(['message'=>'Статус оновлено.']);}
  if($method!=='POST')respond(['message'=>'Method not allowed'],405);
  if(!is_string($d['customer']??null)||strlen(trim($d['customer']))<2||strlen($d['customer'])>240||!is_string($d['sku']??null)||!is_numeric($d['meters']??null)||$d['meters']<=0||$d['meters']>20||filter_var($d['quantity']??null,FILTER_VALIDATE_INT)===false||$d['quantity']<1||$d['quantity']>100||!is_numeric($d['labor']??null)||$d['labor']<0||$d['labor']>10000)respond(['message'=>'Перевірте параметри замовлення.'],422);
  foreach(['meters','labor']as$key)if(!is_finite((float)$d[$key])||abs(round((float)$d[$key]*100)-(float)$d[$key]*100)>.00001)respond(['message'=>'Не більше двох десяткових знаків.'],422);
  $need=round($d['meters']*$d['quantity'],2);$pdo->beginTransaction();$s=$pdo->prepare('SELECT * FROM materials WHERE sku=?');$s->execute([$d['sku']]);$material=$s->fetch();if(!$material||$material['stock']<$need){$pdo->rollBack();respond(['message'=>'Недостатньо матеріалу на складі.'],422);}
  $total=round(($d['meters']*$material['price']+$d['labor'])*$d['quantity'],2);
  $s=$pdo->prepare('UPDATE materials SET stock=stock-? WHERE sku=? AND stock>=?');$s->execute([$need,$d['sku'],$need]);if(!$s->rowCount())throw new RuntimeException('Stock changed');
  $s=$pdo->prepare("INSERT INTO production(user_id,customer,sku,meters,quantity,labor,total,status)VALUES(?,?,?,?,?,?,?,'new')");$s->execute([$uid,trim($d['customer']),$d['sku'],$d['meters'],$d['quantity'],$d['labor'],$total]);$id=(int)$pdo->lastInsertId();$s=$pdo->prepare('INSERT INTO notifications(user_id,order_id,message)VALUES(?,?,?)');$s->execute([$uid,$id,'Замовлення №'.$id.' прийнято. Матеріали зарезервовано.']);$pdo->commit();respond(['id'=>$id,'total'=>$total],201);
 }
 respond(['message'=>'Ресурс не знайдено.'],404);
} catch(Throwable $error){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log('LAB ERROR: '.$error->getMessage());respond(['message'=>'Помилка сервера або зовнішнього API.'],503);}

