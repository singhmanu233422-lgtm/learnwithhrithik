<?php
require_once __DIR__.'/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url): never { header('Location: '.$url); exit; }
function flash($type,$msg): void { $_SESSION['flash']=[$type,$msg]; }
function get_flash(): ?array { $x=$_SESSION['flash']??null; unset($_SESSION['flash']); return $x; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) {
        http_response_code(419); exit('Invalid request token. Please go back and try again.');
    }
}
function admin_logged(): bool { return !empty($_SESSION['admin_id']); }
function student_logged(): bool { return !empty($_SESSION['student_id']); }
function require_admin(): void { if(!admin_logged()) redirect('admin.php'); }
function require_student(): void { if(!student_logged()) redirect('student-login.php'); }

function slugify($text): string {
    $text=preg_replace('~[^\pL\d]+~u','-',trim($text));
    $text=iconv('utf-8','us-ascii//TRANSLIT',$text) ?: $text;
    $text=preg_replace('~[^-\w]+~','',$text);
    return trim(strtolower($text),'-') ?: 'item-'.time();
}
function unique_slug($table,$slug,$excludeId=0): string {
    $pdo=db(); $base=$slug; $n=1;
    while(true){
        $st=$pdo->prepare("SELECT id FROM `$table` WHERE slug=? AND id<>?");
        $st->execute([$slug,$excludeId]);
        if(!$st->fetch()) return $slug;
        $slug=$base.'-'.$n++;
    }
}
function upload_file($field,$folder,$allowedExts): ?array {
    if(empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE) return null;
    $f=$_FILES[$field];
    if($f['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Error code '.$f['error'].'.');
    if($f['size']>MAX_UPLOAD_BYTES) throw new RuntimeException('File is too large. Maximum is 100 MB.');
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,$allowedExts,true)) throw new RuntimeException('This file type is not allowed.');
    $dir=ADMIN_UPLOAD_DIR.'/'.$folder;
    if(!is_dir($dir)) mkdir($dir,0775,true);
    $safe=preg_replace('/[^A-Za-z0-9._-]/','_',basename($f['name']));
    $name=date('Ymd_His').'_'.bin2hex(random_bytes(4)).'_'.$safe;
    if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Could not save uploaded file.');
    return ['name'=>$safe,'path'=>'uploads/'.$folder.'/'.$name,'size'=>$f['size'],'ext'=>$ext];
}
function delete_upload($path): void {
    if(!$path) return;
    $full=__DIR__.'/'.$path;
    if(is_file($full)) @unlink($full);
}
function setting($key,$default=''): string {
    try { $st=db()->prepare("SELECT value FROM settings WHERE `key`=?"); $st->execute([$key]); $r=$st->fetch(); return $r?$r['value']:$default; }
    catch(Throwable $e){ return $default; }
}
