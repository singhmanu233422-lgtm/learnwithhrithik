<?php
require_once __DIR__.'/config.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$msg=''; $ok=false;
try {
    $pdo=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");
    $sql=[
"CREATE TABLE IF NOT EXISTS settings (`key` VARCHAR(100) PRIMARY KEY, `value` TEXT NOT NULL)",
"CREATE TABLE IF NOT EXISTS admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS students (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS courses (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(190) UNIQUE NOT NULL, title VARCHAR(190) NOT NULL, description TEXT, level VARCHAR(60) DEFAULT 'Beginner', duration VARCHAR(60) DEFAULT '', lessons INT DEFAULT 0, language VARCHAR(100) DEFAULT 'English', price VARCHAR(50) DEFAULT 'Free', icon VARCHAR(20) DEFAULT '📚', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS tutorials (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, description TEXT, icon VARCHAR(20) DEFAULT '📘', url VARCHAR(255) DEFAULT '#', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS resources (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, type VARCHAR(80) DEFAULT 'PDF Notes', category VARCHAR(80) DEFAULT 'Notes', description TEXT, file_path VARCHAR(500), original_name VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS applications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, version VARCHAR(60) DEFAULT '', description TEXT, icon VARCHAR(20) DEFAULT '📦', file_path VARCHAR(500) NOT NULL, original_name VARCHAR(255) NOT NULL, size_bytes BIGINT UNSIGNED DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS updates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, message TEXT NOT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS enrollments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT UNSIGNED NOT NULL, course_id INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY student_course(student_id,course_id))"
    ];
    foreach($sql as $q)$pdo->exec($q);
    $st=$pdo->prepare("SELECT COUNT(*) FROM admins");$st->execute();
    if((int)$st->fetchColumn()===0){
        $st=$pdo->prepare("INSERT INTO admins(name,email,password_hash) VALUES(?,?,?)");
        $st->execute(['Hrithik','admin@learnwithhrithik.local',password_hash('admin123',PASSWORD_DEFAULT)]);
    }
    $defaults=[
['computer-fundamentals','Computer Fundamentals','Learn computers from zero: hardware, software, memory, operating systems and basic concepts.','Beginner','8h 20m',32,'English','Free','💻'],
['ms-office','MS Office Master Course','Learn Word, Excel and PowerPoint with practical assignments and real-world examples.','Beginner','12h 10m',48,'English + Hindi','₹299','📘'],
['html-css','HTML & CSS Website Development','Build responsive websites from scratch using modern HTML and CSS.','Beginner','10h 30m',44,'English','₹399','🌐'],
['javascript','JavaScript for Beginners','Understand JavaScript fundamentals, DOM, events and interactive web pages.','Beginner','14h 15m',55,'English','₹499','⚡'],
['php-mysql','PHP & MySQL Web Development','Create dynamic PHP websites with forms, sessions, databases and CRUD operations.','Intermediate','18h 40m',72,'English + Hindi','₹699','🐘'],
['pgdca-computer','PGDCA Computer Applications','A structured learning path for computer fundamentals, Office, programming and databases.','Beginner','30h',120,'English + Hindi','₹999','🎓']];
    $count=(int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    if($count===0){
        $st=$pdo->prepare("INSERT INTO courses(slug,title,description,level,duration,lessons,language,price,icon) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($defaults as $d)$st->execute($d);
    }
    $tuts=[['HTML Tutorial','Learn HTML structure and semantic elements.','🌐'],['CSS Tutorial','Style responsive websites from the ground up.','🎨'],['JavaScript Tutorial','Add logic and interactivity to web pages.','⚡'],['Python Tutorial','Start programming with beginner-friendly Python.','🐍'],['C Tutorial','Build a strong programming foundation with C.','C'],['React Tutorial','Understand components and modern frontend basics.','⚛️'],['Java Tutorial','Learn Java programming concepts step by step.','☕'],['C++ Tutorial','Explore object-oriented programming with C++.','C++'],['PHP Tutorial','Build dynamic websites using PHP.','🐘']];
    if((int)$pdo->query("SELECT COUNT(*) FROM tutorials")->fetchColumn()===0){
        $st=$pdo->prepare("INSERT INTO tutorials(title,description,icon) VALUES(?,?,?)"); foreach($tuts as $t)$st->execute($t);
    }
    if((int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn()===0){
        $st=$pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?)");$st->execute(['site_name','Learn With Hrithik']);
    }
    $ok=true;$msg='Installation complete. You can now use the Admin Panel.';
} catch(Throwable $e){$msg=$e->getMessage();}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install | Learn With Hrithik</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="section"><div class="container"><div class="panel form-box"><h1>Learn With Hrithik Setup</h1><?php if($ok):?><div class="notice"><?=e($msg)?></div><p><b>Admin:</b> admin@learnwithhrithik.local</p><p><b>Password:</b> admin123</p><div class="btns"><a class="primary" href="admin.php">Open Admin</a><a class="secondary" href="index.php">Open Website</a></div><p class="muted">Change the demo password after first login.</p><?php else:?><p class="notice"><?=e($msg)?></p><p>Start Apache + MySQL in XAMPP and open this page again.</p><?php endif;?></div></div></main></body></html>
