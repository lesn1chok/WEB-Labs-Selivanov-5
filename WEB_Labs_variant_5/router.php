<?php
declare(strict_types=1);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
if($path==='/api.php'){require __DIR__.'/api.php';return;}
if(preg_match('~^/lab0[1-9]$~',$path)){header('Location: '.$path.'/',true,308);return;}
if($path==='/')$path='/index.html';
if(preg_match('~^/lab0[1-9]/?$~',$path))$path=rtrim($path,'/').'/index.html';
$allowed=preg_match('~^/(lab0[1-9]|shared|vendor)/[a-zA-Z0-9_./-]+\.(html|css|js|mjs|svg|png)$~',$path)||$path==='/index.html';
if(str_starts_with($path,'/assets/')||str_starts_with($path,'/lab05/')){$base=realpath(__DIR__.'/dist');$allowed=(bool)preg_match('~^/(assets/[^/]+\.(js|css)|lab05/index.html)$~',$path);}
else $base=realpath(__DIR__);
$file=$base?realpath($base.$path):false;
if(!$allowed||!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);echo 'Not found. React requires npm run build.';return;}
$mime=['html'=>'text/html; charset=utf-8','css'=>'text/css; charset=utf-8','js'=>'text/javascript; charset=utf-8','mjs'=>'text/javascript; charset=utf-8','svg'=>'image/svg+xml','png'=>'image/png'];
header('Content-Type: '.$mime[pathinfo($file,PATHINFO_EXTENSION)]);header('Cache-Control: '.(str_starts_with($path,'/assets/')?'public, max-age=31536000, immutable':'no-cache'));readfile($file);

