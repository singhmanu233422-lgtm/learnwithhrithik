<?php
require_once __DIR__.'/functions.php';
if(isset($_GET['logout'])){session_destroy();redirect('admin.php');}
if(!admin_logged()){
    if($_SERVER['REQUEST_METHOD']==='POST'){
        verify_csrf(); try{$st=db()->prepare("SELECT * FROM admins WHERE email=?");$st->execute([trim($_POST['email']??'')]);$a=$st->fetch();
            if($a && password_verify($_POST['password']??'',$a['password_hash'])){$_SESSION['admin_id']=$a['id'];$_SESSION['admin_name']=$a['name'];redirect('admin.php');}
            $error='Invalid email or password.';
        }catch(Throwable $e){$error='Database not ready. Run install.php first.';}
    }
    $pageTitle='Admin Login';include 'includes/header.php';?>
    <section class="section"><form class="form-box panel" method="post"><h1>Admin Login</h1><p class="muted">Manage courses, notes, applications, updates and students.</p><?php if(!empty($error)):?><div class="notice"><?=e($error)?></div><?php endif;?><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Email</label><input type="email" name="email" value="admin@learnwithhrithik.local" required><label>Password</label><input type="password" name="password" required><button class="primary" style="width:100%">Login</button><p class="muted" style="font-size:13px">First install: admin@learnwithhrithik.local / admin123</p></form></section><?php include 'includes/footer.php';exit;
}
$pdo=db(); $tab=$_GET['tab']??'dashboard'; $error=''; $ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); $action=$_POST['action']??'';
    try{
        if($action==='add_resource'){
            $up=upload_file('file','notes',['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip']);
            if(!$up) throw new RuntimeException('Choose a file.');
            $st=$pdo->prepare("INSERT INTO resources(title,type,category,description,file_path,original_name) VALUES(?,?,?,?,?,?)");
            $st->execute([trim($_POST['title']),trim($_POST['type']),trim($_POST['category']),trim($_POST['description']),$up['path'],$up['name']]);$ok='Note/resource uploaded.';
        }elseif($action==='add_application'){
            $up=upload_file('file','applications',['apk','exe','msi','zip','rar','dmg','deb','rpm','appimage','jar']);
            if(!$up) throw new RuntimeException('Choose an application file.');
            $st=$pdo->prepare("INSERT INTO applications(title,version,description,icon,file_path,original_name,size_bytes) VALUES(?,?,?,?,?,?,?)");
            $st->execute([trim($_POST['title']),trim($_POST['version']),trim($_POST['description']),trim($_POST['icon']?:'📦'),$up['path'],$up['name'],$up['size']]);$ok='Application uploaded.';
        }elseif($action==='add_update'){
            $st=$pdo->prepare("INSERT INTO updates(title,description) VALUES(?,?)");$st->execute([trim($_POST['title']),trim($_POST['description'])]);$ok='Update published.';
        }elseif($action==='add_course'){
            $slug=unique_slug('courses',slugify($_POST['title']));
            $st=$pdo->prepare("INSERT INTO courses(slug,title,description,level,duration,lessons,language,price,icon) VALUES(?,?,?,?,?,?,?,?,?)");
            $st->execute([$slug,trim($_POST['title']),trim($_POST['description']),trim($_POST['level']),trim($_POST['duration']),(int)$_POST['lessons'],trim($_POST['language']),trim($_POST['price']),trim($_POST['icon']?:'📚')]);$ok='Course added.';
        }elseif($action==='delete'){
            $table=$_POST['table'];$id=(int)$_POST['id'];
            $allowed=['resources','applications','updates','courses','tutorials','messages','students'];
            if(!in_array($table,$allowed,true))throw new RuntimeException('Invalid action.');
            if(in_array($table,['resources','applications'],true)){
                $st=$pdo->prepare("SELECT file_path FROM `$table` WHERE id=?");$st->execute([$id]);$r=$st->fetch(); if($r)delete_upload($r['file_path']);
            }
            $pdo->prepare("DELETE FROM `$table` WHERE id=?")->execute([$id]);$ok='Item deleted.';
        }elseif($action==='add_tutorial'){
            $st=$pdo->prepare("INSERT INTO tutorials(title,description,icon,url) VALUES(?,?,?,?)");$st->execute([trim($_POST['title']),trim($_POST['description']),trim($_POST['icon']?:'📘'),trim($_POST['url']?:'#')]);$ok='Tutorial added.';
        }elseif($action==='change_password'){
            $old=$_POST['old_password']??'';$new=$_POST['new_password']??'';
            $st=$pdo->prepare("SELECT password_hash FROM admins WHERE id=?");$st->execute([$_SESSION['admin_id']]);$a=$st->fetch();
            if(!$a||!password_verify($old,$a['password_hash']))throw new RuntimeException('Old password is incorrect.');
            if(strlen($new)<8)throw new RuntimeException('New password must be at least 8 characters.');
            $pdo->prepare("UPDATE admins SET password_hash=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['admin_id']]);$ok='Password changed.';
        }
    }catch(Throwable $e){$error=$e->getMessage();}
}
$counts=[];
foreach(['courses','tutorials','resources','applications','students','messages','updates'] as $t){$counts[$t]=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();}
$flash=get_flash();
$pageTitle='Admin Dashboard';include 'includes/header.php';
?>
<section class="page-head"><div class="container"><h1>Admin Dashboard</h1><p class="muted">Welcome, <?=e($_SESSION['admin_name'])?>. Manage your whole website from here.</p><div class="btns"><a class="secondary" href="index.php">View Website</a><a class="secondary" href="admin.php?logout=1">Logout</a></div></div></section>
<section class="section" style="padding-top:10px"><div class="container">
<?php if($ok||$flash):?><div class="notice"><?=e($ok ?: $flash[1])?></div><?php endif;?><?php if($error):?><div class="notice"><?=e($error)?></div><?php endif;?>
<div class="stats admin-stats"><?php foreach(['courses'=>'Courses','tutorials'=>'Tutorials','resources'=>'Notes/Files','applications'=>'Applications','students'=>'Students','messages'=>'Messages','updates'=>'Updates'] as $k=>$v):?><div class="stat"><strong><?=$counts[$k]?></strong><span><?=$v?></span></div><?php endforeach;?></div>
<div class="admin-tabs">
<?php foreach(['dashboard'=>'Dashboard','resources'=>'Notes/PDF','applications'=>'Applications','courses'=>'Courses','tutorials'=>'Tutorials','updates'=>'Updates','students'=>'Students','messages'=>'Messages','settings'=>'Settings'] as $k=>$v):?><a class="<?=($tab===$k?'active':'')?>" href="admin.php?tab=<?=$k?>"><?=e($v)?></a><?php endforeach;?>
</div>
<?php if($tab==='dashboard'):?>
<div class="feature-grid"><div class="panel"><h2>📄 Notes/PDF</h2><p class="muted">Upload PDFs, DOC/DOCX, PPT, Excel and ZIP study files.</p><a class="primary" href="admin.php?tab=resources">Manage Notes</a></div><div class="panel"><h2>📦 Applications</h2><p class="muted">Upload APK, EXE, MSI, ZIP and other application packages.</p><a class="primary" href="admin.php?tab=applications">Manage Applications</a></div><div class="panel"><h2>🔔 Updates</h2><p class="muted">Publish announcements shown on the website.</p><a class="primary" href="admin.php?tab=updates">Manage Updates</a></div></div>
<div class="panel" style="margin-top:20px"><h2>How to upload WhatsApp or another application</h2><ol><li>Open <b>Applications</b>.</li><li>Enter name, version and description.</li><li>Select the APK/EXE/ZIP file you legally have permission to distribute.</li><li>Click Upload Application.</li><li>It will automatically appear on Applications and Downloads.</li></ol></div>
<?php elseif($tab==='resources'):?>
<div class="panel"><h2>Upload Notes / PDF</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_resource"><label>Title</label><input name="title" required placeholder="Computer Fundamentals Notes"><label>Type</label><select name="type"><option>PDF Notes</option><option>Study Material</option><option>Assignment</option><option>Document</option></select><label>Category</label><input name="category" value="Notes"><label>Description</label><textarea name="description" rows="3"></textarea><label>File</label><input type="file" name="file" required><button class="primary">Upload Resource</button></form></div>
<div class="list" style="margin-top:20px"><?php foreach($pdo->query("SELECT * FROM resources ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['title'])?></h3><p class="muted"><?=e($r['original_name'])?> · <?=e($r['category'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="resources"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary" onclick="return confirm('Delete this file?')">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='applications'):?>
<div class="panel"><h2>Upload Application</h2><p class="muted">For example, if you have a WhatsApp APK that you are legally allowed to distribute, upload it here. The website does not fetch apps automatically.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_application"><label>Application Name</label><input name="title" required placeholder="WhatsApp"><label>Version</label><input name="version" placeholder="Example: 2.x.x"><label>Icon/Emoji</label><input name="icon" value="📱"><label>Description</label><textarea name="description" rows="3"></textarea><label>Application file</label><input type="file" name="file" required><button class="primary">Upload Application</button></form></div>
<div class="list" style="margin-top:20px"><?php foreach($pdo->query("SELECT * FROM applications ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['icon'])?> <?=e($r['title'])?></h3><p class="muted">Version <?=e($r['version'])?> · <?=e($r['original_name'])?> · <?=number_format($r['size_bytes']/1048576,2)?> MB</p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="applications"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary" onclick="return confirm('Delete this application?')">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='updates'):?>
<div class="panel"><h2>Publish Update</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_update"><label>Title</label><input name="title" required><label>Message</label><textarea name="description" rows="4" required></textarea><button class="primary">Publish</button></form></div>
<div class="list" style="margin-top:20px"><?php foreach($pdo->query("SELECT * FROM updates ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['title'])?></h3><p class="muted"><?=e($r['description'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="updates"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='courses'):?>
<div class="panel"><h2>Add Course</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_course"><label>Title</label><input name="title" required><label>Description</label><textarea name="description"></textarea><div class="form-two"><div><label>Level</label><input name="level" value="Beginner"></div><div><label>Duration</label><input name="duration" placeholder="8h 30m"></div></div><div class="form-two"><div><label>Lessons</label><input type="number" name="lessons" value="10"></div><div><label>Language</label><input name="language" value="English"></div></div><div class="form-two"><div><label>Price</label><input name="price" value="Free"></div><div><label>Icon</label><input name="icon" value="📚"></div></div><button class="primary">Add Course</button></form></div>
<div class="list" style="margin-top:20px"><?php foreach($pdo->query("SELECT * FROM courses ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['icon'])?> <?=e($r['title'])?></h3><p class="muted"><?=e($r['level'])?> · <?=e($r['lessons'])?> lessons · <?=e($r['price'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="courses"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary" onclick="return confirm('Delete course?')">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='tutorials'):?>
<div class="panel"><h2>Add Tutorial</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_tutorial"><label>Title</label><input name="title" required><label>Description</label><textarea name="description"></textarea><div class="form-two"><div><label>Icon</label><input name="icon" value="📘"></div><div><label>Learning URL</label><input name="url" value="#"></div></div><button class="primary">Add Tutorial</button></form></div>
<div class="list" style="margin-top:20px"><?php foreach($pdo->query("SELECT * FROM tutorials ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['icon'])?> <?=e($r['title'])?></h3><p class="muted"><?=e($r['description'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="tutorials"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='students'):?>
<div class="list"><?php foreach($pdo->query("SELECT id,name,email,created_at FROM students ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['name'])?></h3><p class="muted"><?=e($r['email'])?> · Joined <?=e($r['created_at'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="students"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='messages'):?>
<div class="list"><?php foreach($pdo->query("SELECT * FROM messages ORDER BY id DESC") as $r):?><div class="resource"><div><h3><?=e($r['name'])?> · <?=e($r['email'])?></h3><p><?=nl2br(e($r['message']))?></p><p class="muted"><?=e($r['created_at'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="messages"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="secondary">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($tab==='settings'):?>
<div class="panel"><h2>Change Admin Password</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="change_password"><label>Current password</label><input type="password" name="old_password" required><label>New password</label><input type="password" name="new_password" minlength="8" required><button class="primary">Change Password</button></form></div>
<?php endif;?>
</div></section>
<?php include 'includes/footer.php';?>
