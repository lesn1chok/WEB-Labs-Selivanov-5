<?php
declare(strict_types=1);
require dirname(__DIR__).'/server/bootstrap.php';
$directory=data_dir().'/backups';if(!is_dir($directory))mkdir($directory,0700,true);
$path=$directory.'/backup_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(3)).'.sqlite';
db()->exec('VACUUM INTO '.db()->quote($path));chmod($path,0600);
$key=data_dir().'/key.bin';if(file_exists($key)){copy($key,$path.'.key');chmod($path.'.key',0600);}
$check=new PDO('sqlite:'.$path);if($check->query('PRAGMA integrity_check')->fetchColumn()!=='ok')throw new RuntimeException('Backup integrity error');
echo json_encode(['ok'=>true,'backup'=>basename($path),'integrity'=>'ok']).PHP_EOL;

