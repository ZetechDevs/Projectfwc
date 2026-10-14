<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){ header("Location: /auth/login.php"); exit(); }
$admin_id = (int)$_SESSION['user_id'];
$name     = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

$role_res = mysqli_query($conn,"SELECT role FROM users WHERE user_id='$admin_id'");
$role_row = $role_res ? mysqli_fetch_assoc($role_res) : null;
if(!$role_row || $role_row['role']!=='admin'){ header("Location: /auth/login.php"); exit(); }

/* ── HANDLERS ── */
if(isset($_POST['change_verification'])){
    $tid=(int)$_POST['target_id']; $ns=($_POST['new_status']==='verified')?'verified':'pending';
    mysqli_query($conn,"UPDATE users SET verification_status='$ns' WHERE user_id='$tid'");
    header("Location: ".$_SERVER['PHP_SELF']."?section=verification&done=1"); exit();
}
if(isset($_POST['send_message'])){
    $rid=(int)$_POST['receiver_id']; $msg=mysqli_real_escape_string($conn,trim($_POST['message']));
    if($msg!==''&&$rid>0) mysqli_query($conn,"INSERT INTO messages(sender_id,receiver_id,message,created_at) VALUES('$admin_id','$rid','$msg',NOW())");
    header("Location: ".$_SERVER['PHP_SELF']."?section=messages&sent=1"); exit();
}
if(isset($_POST['delete_user'])){
    $tid=(int)$_POST['target_id'];
    if($tid!==$admin_id) mysqli_query($conn,"DELETE FROM users WHERE user_id='$tid'");
    header("Location: ".$_SERVER['PHP_SELF']."?section=users&deleted=1"); exit();
}
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0){
    $allowed=array('image/jpeg','image/png','image/gif','image/webp');
    if(in_array($_FILES['profile_image']['type'],$allowed) && $_FILES['profile_image']['size']<=2097152){
        $dir=$_SERVER['DOCUMENT_ROOT'].'/assets/uploads/avatars/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $ext=pathinfo($_FILES['profile_image']['name'],PATHINFO_EXTENSION);
        $fn='user_'.$admin_id.'_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['profile_image']['tmp_name'],$dir.$fn)){
            $dp=mysqli_real_escape_string($conn,'assets/uploads/avatars/'.$fn);
            mysqli_query($conn,"UPDATE users SET profile_image='$dp' WHERE user_id='$admin_id'");
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}
if(isset($_POST['update_profile'])){
    $age=(int)$_POST['age']; $gender=mysqli_real_escape_string($conn,$_POST['gender']);
    $religion=mysqli_real_escape_string($conn,$_POST['religion']); $country=mysqli_real_escape_string($conn,$_POST['country']);
    $language=mysqli_real_escape_string($conn,$_POST['language']); $bio=mysqli_real_escape_string($conn,$_POST['bio']);
    $ex=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM user_profile WHERE user_id='$admin_id'"));
    if($ex) mysqli_query($conn,"UPDATE user_profile SET age='$age',gender='$gender',religion='$religion',country='$country',language='$language',bio='$bio' WHERE user_id='$admin_id'");
    else    mysqli_query($conn,"INSERT INTO user_profile(user_id,age,gender,religion,country,language,bio) VALUES('$admin_id','$age','$gender','$religion','$country','$language','$bio')");
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}

/* ── LOAD DATA ── */
$a_res  = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$admin_id'");
$a_data = mysqli_fetch_assoc($a_res);
$email  = htmlspecialchars($a_data['email']);
$avatar = !empty($a_data['profile_image']) ? '/'.$a_data['profile_image'] : '';

$pr_res = mysqli_query($conn,"SELECT * FROM user_profile WHERE user_id='$admin_id'");
$pr     = ($pr_res && mysqli_num_rows($pr_res)>0) ? mysqli_fetch_assoc($pr_res) : array('age'=>'','gender'=>'','religion'=>'','country'=>'','language'=>'','bio'=>'');

$users_res   = mysqli_query($conn,"SELECT user_id,full_name,email,role,created_at,verification_status FROM users WHERE role='user' ORDER BY created_at DESC");
$users=array(); if($users_res) while($r=mysqli_fetch_assoc($users_res)) $users[]=$r;

$leaders_res = mysqli_query($conn,"SELECT user_id,full_name,email,role,created_at,verification_status FROM users WHERE role='leader' ORDER BY created_at DESC");
$leaders=array(); if($leaders_res) while($r=mysqli_fetch_assoc($leaders_res)) $leaders[]=$r;

$unverified_res = mysqli_query($conn,"SELECT user_id,full_name,email,verification_status,created_at FROM users WHERE role='leader' AND verification_status!='verified' ORDER BY created_at DESC");
$unverified=array(); if($unverified_res) while($r=mysqli_fetch_assoc($unverified_res)) $unverified[]=$r;

$verified_res = mysqli_query($conn,"SELECT user_id,full_name,email,verification_status,created_at FROM users WHERE role='leader' AND verification_status='verified' ORDER BY created_at DESC");
$verified=array(); if($verified_res) while($r=mysqli_fetch_assoc($verified_res)) $verified[]=$r;

$au_res = mysqli_query($conn,"SELECT user_id,full_name,email,role FROM users WHERE user_id!='$admin_id' ORDER BY full_name ASC");
$all_users=array(); if($au_res) while($r=mysqli_fetch_assoc($au_res)) $all_users[]=$r;

$inbox_res = mysqli_query($conn,"SELECT * FROM messages WHERE receiver_id='$admin_id'");
$inbox=array();
if($inbox_res){
    while($m=mysqli_fetch_assoc($inbox_res)){
        $sn=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name,profile_image FROM users WHERE user_id='".(int)$m['sender_id']."'"));
        $m['sender_name']=$sn?$sn['full_name']:'Unknown';
        $m['sender_pfp']=$sn&&!empty($sn['profile_image'])?'/'.$sn['profile_image']:'';
        $inbox[]=$m;
    }
}

$cons_res  = mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM consultations");
$total_cons= (int)mysqli_fetch_assoc($cons_res)['cnt'];
$sec=isset($_GET['section'])?$_GET['section']:'overview';
$saved=isset($_GET['saved']);

function av($pfp,$name,$size,$extra=''){
    if(!empty($pfp)) return '<img src="'.htmlspecialchars($pfp).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;flex-shrink:0;'.$extra.'" alt="">';
    $p=explode(' ',trim($name)); $i='';
    foreach($p as $x){ if(strlen($i)>=2) break; if(!empty($x)) $i.=strtoupper($x[0]); }
    $c=array('#3a86ff','#7209b7','#f39c12','#2ecc71','#e74c3c');
    $bg=$c[abs(crc32($name))%count($c)]; $fs=floor($size*0.36);
    return '<div style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:'.$bg.';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'.$fs.'px;color:#fff;flex-shrink:0;'.$extra.'">'.htmlspecialchars($i).'</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel — FaithConnect</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
[data-theme="dark"]{
  --bg1:#0d1b2a;--bg2:#1a2e45;
  --glass:rgba(255,255,255,0.06);--glass2:rgba(255,255,255,0.10);--gb:rgba(58,134,255,0.18);
  --txt:#fff;--txt2:rgba(200,214,229,0.75);--txt3:rgba(200,214,229,0.40);
  --sb:rgba(13,27,42,0.95);--card:rgba(255,255,255,0.05);--card-h:rgba(255,255,255,0.10);
  --input:rgba(255,255,255,0.07);--topbar:rgba(13,27,42,0.90);
  --surface:#0d1b2a;--surface2:#1a2e45;--border:rgba(58,134,255,0.15);
  --shadow:0 8px 32px rgba(0,0,0,0.35);
  --blue:#3a86ff;--blight:#74b3ff;--bdark:#1a5fd4;
  --success:#2ecc71;--danger:#e74c3c;--warning:#f39c12;
}
[data-theme="light"]{
  --bg1:#e8f4fd;--bg2:#f0f9ff;
  --glass:rgba(255,255,255,0.65);--glass2:rgba(255,255,255,0.85);--gb:rgba(58,134,255,0.20);
  --txt:#0d1b2a;--txt2:rgba(13,27,42,0.68);--txt3:rgba(13,27,42,0.38);
  --sb:rgba(255,255,255,0.92);--card:rgba(255,255,255,0.75);--card-h:rgba(255,255,255,0.95);
  --input:rgba(255,255,255,0.80);--topbar:rgba(255,255,255,0.88);
  --surface:#fff;--surface2:#f0f5ff;--border:rgba(58,134,255,0.18);
  --shadow:0 8px 32px rgba(58,134,255,0.12);
  --blue:#3a86ff;--blight:#1a5fd4;--bdark:#1a5fd4;
  --success:#27ae60;--danger:#c0392b;--warning:#d4880a;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--txt)}
body{background:linear-gradient(160deg,var(--bg1),var(--bg2));background-attachment:fixed;transition:background 0.4s;overflow-x:hidden}
a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
button{cursor:pointer;font-family:'DM Sans',sans-serif} input,textarea,select{font-family:'DM Sans',sans-serif}

.app{display:flex;height:100vh;overflow:hidden;position:relative;z-index:1}

/* Sidebar */
.sidebar{width:260px;background:var(--sb);backdrop-filter:blur(20px);border-right:1px solid var(--border);display:flex;flex-direction:column;height:100vh;flex-shrink:0;overflow:hidden;transition:background 0.4s}
.sb-header{padding:18px 18px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:11px}
.sb-logo{width:34px;height:34px;background:linear-gradient(135deg,var(--bdark),var(--blue));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.brand-name{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--txt)}
.brand-tag{font-size:10px;color:var(--txt3);text-transform:uppercase;letter-spacing:.1em;margin-top:1px}
.sb-profile{padding:13px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border)}
.pname{font-weight:600;color:var(--txt);font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prole{font-size:11px;color:var(--blue);text-transform:uppercase;letter-spacing:.06em}
.sb-nav{flex:1;padding:10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.nlabel{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--txt3);padding:8px 10px 3px}
.nav-btn{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--txt2);font-size:13.5px;cursor:pointer;transition:0.2s;border:none;background:none;width:100%;text-align:left;position:relative}
.nav-btn:hover{background:var(--glass2);color:var(--txt)}
.nav-btn.active{background:var(--glass2);color:var(--txt);font-weight:600;border:1px solid var(--gb)}
.nav-btn.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--blue);border-radius:0 3px 3px 0}
.nav-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--blue);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
.nav-badge.warn{background:var(--warning)}
.sb-footer{padding:10px;border-top:1px solid var(--border)}
.logout-btn{display:flex;align-items:center;gap:9px;width:100%;padding:9px 12px;border-radius:9px;color:var(--txt3);font-size:13.5px;cursor:pointer;transition:0.2s;background:none;border:none}
.logout-btn:hover{color:#ff6b6b;background:rgba(231,76,60,0.12)}

/* Main */
.main{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;min-width:0}
.topbar{background:var(--topbar);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:13px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;transition:background 0.4s}
.topbar-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700}
.topbar-sub{font-size:12px;color:var(--txt2);margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:10px}
.chip{background:linear-gradient(135deg,var(--bdark),var(--blue));color:#fff;font-size:11px;font-weight:700;padding:4px 10px;border-radius:99px;letter-spacing:.05em}
.theme-btn{background:var(--glass2);border:1px solid var(--gb);border-radius:99px;padding:6px 12px;font-size:13px;cursor:pointer;color:var(--txt)}
.content{flex:1;overflow-y:auto;padding:22px 24px}

/* Panels */
.section{display:none;animation:fu 0.22s ease}
.section.active{display:block}
@keyframes fu{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.sh{margin-bottom:16px;display:flex;align-items:center;gap:10px}
.sh h2{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;white-space:nowrap;color:var(--txt)}
.sh-line{flex:1;height:1px;background:var(--border)}
.empty{text-align:center;padding:40px 24px;color:var(--txt2)}
.empty .icon{font-size:38px;margin-bottom:8px}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:13px;margin-bottom:24px}
.stat-card{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--border);border-radius:14px;padding:16px;position:relative;overflow:hidden;transition:0.2s}
.stat-card:hover{background:var(--card-h);box-shadow:var(--shadow)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.c1::before{background:linear-gradient(90deg,var(--bdark),var(--blue))}
.c2::before{background:linear-gradient(90deg,#7209b7,#b5179e)}
.c3::before{background:linear-gradient(90deg,var(--warning),#f9c74f)}
.c4::before{background:linear-gradient(90deg,var(--success),#52b788)}
.c5::before{background:linear-gradient(90deg,var(--danger),#ff6b6b)}
.stat-icon{font-size:22px;margin-bottom:7px}
.stat-value{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;line-height:1;margin-bottom:2px;color:var(--txt)}
.stat-label{font-size:12px;color:var(--txt2)}

/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:16px;background:var(--glass);padding:4px;border-radius:9px;width:fit-content;border:1px solid var(--border)}
.tab{padding:6px 16px;border-radius:7px;font-size:13.5px;font-weight:500;cursor:pointer;border:none;background:none;color:var(--txt2);transition:0.2s}
.tab.active{background:var(--blue);color:#fff;font-weight:600}

/* Table */
.tbl-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.tbl-head{display:grid;padding:11px 16px;background:var(--glass2);border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--txt3)}
.tbl-row{display:grid;padding:12px 16px;border-bottom:1px solid var(--border);align-items:center;transition:0.2s}
.tbl-row:last-child{border-bottom:none}
.tbl-row:hover{background:var(--card-h)}
.tbl-users .tbl-head,.tbl-users .tbl-row{grid-template-columns:1.8fr 2fr 0.8fr 1.2fr 0.7fr}
.tbl-leaders .tbl-head,.tbl-leaders .tbl-row{grid-template-columns:1.8fr 2fr 1fr 1.2fr 0.9fr}
.tcell{font-size:13.5px;color:var(--txt);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tcell.muted{color:var(--txt2);font-size:12.5px}

/* Badges */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600}
.badge.user{background:rgba(58,134,255,0.15);color:var(--blight);border:1px solid rgba(58,134,255,0.25)}
.badge.leader{background:rgba(114,9,183,0.15);color:#b57bff;border:1px solid rgba(114,9,183,0.25)}
.badge.verified{background:rgba(46,204,113,0.15);color:var(--success);border:1px solid rgba(46,204,113,0.3)}
.badge.pending{background:rgba(243,156,18,0.15);color:var(--warning);border:1px solid rgba(243,156,18,0.3)}

/* Buttons */
.btn{padding:6px 13px;border-radius:7px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:0.2s}
.btn-blue{background:var(--blue);color:#fff}
.btn-blue:hover{background:var(--bdark)}
.btn-green{background:var(--success);color:#fff}
.btn-green:hover{opacity:.9}
.btn-red{background:var(--danger);color:#fff}
.btn-red:hover{opacity:.9}
.btn-outline{background:transparent;color:var(--txt2);border:1px solid var(--gb)}
.btn-outline:hover{background:var(--glass2)}
.btn-primary{padding:10px 20px;background:linear-gradient(135deg,var(--bdark),var(--blue));color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;transition:0.2s}
.btn-primary:hover{opacity:.88;box-shadow:0 4px 12px rgba(58,134,255,0.4)}

/* V-cards */
.vgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:13px}
.vcard{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;transition:0.2s;display:flex;flex-direction:column;gap:6px}
.vcard:hover{background:var(--card-h);box-shadow:var(--shadow)}
.vcard-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.vcard-email{font-size:12.5px;color:var(--txt2)}

/* Messages */
.compose{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:18px}
.compose-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:12px}
.compose-row{display:flex;gap:9px;align-items:flex-end;flex-wrap:wrap}
.compose-row select,.compose-row input{flex:1;min-width:140px;padding:9px 12px;border:1px solid var(--border);border-radius:9px;font-size:14px;background:var(--input);color:var(--txt);outline:none}
.inbox-item{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:11px;transition:0.2s}
.inbox-item:hover{background:var(--card-h)}
.inbox-from{font-weight:600;font-size:14px;color:var(--txt);margin-bottom:6px;display:flex;align-items:center;gap:8px}
.inbox-msg{font-size:13.5px;color:var(--txt2);background:var(--glass2);padding:8px 12px;border-radius:8px;border-left:3px solid var(--blue)}
.reply-row{display:flex;gap:8px;margin-top:10px}
.reply-row input{flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:9px;font-size:13.5px;background:var(--input);color:var(--txt);outline:none}

/* Profile */
.phero{background:linear-gradient(135deg,var(--bg1),var(--bg2));border:1px solid var(--border);border-radius:16px 16px 0 0;padding:26px;display:flex;align-items:center;gap:18px;max-width:620px;position:relative;overflow:hidden}
.phero::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(58,134,255,0.15),transparent 70%);pointer-events:none}
.phero-name{font-family:'Syne',sans-serif;font-size:21px;font-weight:700;color:var(--txt);position:relative}
.phero-email{font-size:13px;color:var(--txt2);margin-top:3px;position:relative}
.pbody{background:var(--card);border:1px solid var(--border);border-top:none;border-radius:0 0 16px 16px;padding:22px 26px;max-width:620px}
.pgrid{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:16px}
.fwrap{display:flex;flex-direction:column;gap:4px}
.fwrap.full{grid-column:1/-1}
.flabel{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--txt3)}
.fval{font-size:14px;color:var(--txt);padding:8px 0;border-bottom:1px solid var(--border)}
.finput{padding:8px 11px;border:1px solid var(--border);border-radius:9px;font-size:14px;color:var(--txt);background:var(--input);outline:none;transition:0.2s;width:100%}
.finput:focus{background:var(--glass2);border-color:var(--blue)}
.finput::placeholder{color:var(--txt3)}
textarea.finput{resize:vertical;min-height:80px}
.pactions{display:flex;gap:9px;margin-top:14px;flex-wrap:wrap}
.upload-area{border:2px dashed var(--border);border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;transition:0.2s}
.upload-area:hover{border-color:var(--blue);background:var(--glass2)}
.upload-area label{cursor:pointer;display:block}
.upload-area p{font-size:13px;color:var(--txt2);margin-top:6px}
.upload-area input[type="file"]{display:none}

.alert{border-radius:9px;padding:10px 14px;font-size:13.5px;margin-bottom:14px}
.alert.success{background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.3);color:var(--success)}
.alert.info{background:rgba(58,134,255,0.12);border:1px solid rgba(58,134,255,0.25);color:var(--blight)}

::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px}
#toast{position:fixed;bottom:20px;right:20px;background:var(--bg2);backdrop-filter:blur(16px);border:1px solid var(--border);color:var(--txt);padding:10px 16px;border-radius:9px;font-size:14px;font-weight:500;box-shadow:var(--shadow);transform:translateY(60px);opacity:0;transition:0.25s;z-index:9999}
#toast.show{transform:translateY(0);opacity:1}
@media(max-width:900px){.tbl-users .tbl-head,.tbl-users .tbl-row{grid-template-columns:1fr 1fr 0.7fr 0.6fr}.pgrid{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div id="toast"></div>
<div class="app">

<aside class="sidebar">
  <div class="sb-header">
    <div class="sb-logo">⚙️</div>
    <div><div class="brand-name">FaithConnect</div><div class="brand-tag">Admin Panel</div></div>
  </div>
  <div class="sb-profile">
    <?php echo av($avatar,$name,38,'border:2px solid var(--border)'); ?>
    <div style="min-width:0"><div class="pname"><?php echo htmlspecialchars($name); ?></div><div class="prole">Administrator</div></div>
  </div>
  <nav class="sb-nav">
    <div class="nlabel">Control</div>
    <button class="nav-btn <?php echo $sec==='overview'?'active':''; ?>" onclick="show('overview')"><span class="nav-icon">📊</span> Overview</button>
    <button class="nav-btn <?php echo $sec==='users'?'active':''; ?>" onclick="show('users')"><span class="nav-icon">👥</span> Users <span class="nav-badge"><?php echo count($users)+count($leaders); ?></span></button>
    <button class="nav-btn <?php echo $sec==='verification'?'active':''; ?>" onclick="show('verification')"><span class="nav-icon">✅</span> Verification <?php if(count($unverified)>0): ?><span class="nav-badge warn"><?php echo count($unverified); ?></span><?php endif; ?></button>
    <button class="nav-btn <?php echo $sec==='messages'?'active':''; ?>" onclick="show('messages')"><span class="nav-icon">💬</span> Messages <?php if(count($inbox)>0): ?><span class="nav-badge"><?php echo count($inbox); ?></span><?php endif; ?></button>
    <div class="nlabel">Account</div>
    <button class="nav-btn <?php echo $sec==='profile'?'active':''; ?>" onclick="show('profile')"><span class="nav-icon">👤</span> Profile</button>
  </nav>
  <div class="sb-footer"><a href="/index.php" class="logout-btn"><span>🚪</span> Sign Out</a></div>
</aside>

<div class="main">
<header class="topbar">
  <div><div class="topbar-title" id="ttl">Overview</div><div class="topbar-sub" id="tsub">Platform stats and quick actions</div></div>
  <div class="topbar-right">
    <button class="theme-btn" onclick="toggleTheme()" id="themeBtn">☀️ Light</button>
    <span class="chip">Admin</span>
    <?php echo av($avatar,$name,34,'border:2px solid var(--border)'); ?>
  </div>
</header>

<div class="content">

<!-- OVERVIEW -->
<div id="sec-overview" class="section <?php echo $sec==='overview'?'active':''; ?>">
  <div class="stats-grid">
    <div class="stat-card c1"><div class="stat-icon">👤</div><div class="stat-value"><?php echo count($users); ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card c2"><div class="stat-icon">🙏</div><div class="stat-value"><?php echo count($leaders); ?></div><div class="stat-label">Faith Leaders</div></div>
    <div class="stat-card c3"><div class="stat-icon">⏳</div><div class="stat-value"><?php echo count($unverified); ?></div><div class="stat-label">Needs Verification</div></div>
    <div class="stat-card c4"><div class="stat-icon">📋</div><div class="stat-value"><?php echo $total_cons; ?></div><div class="stat-label">Consultations</div></div>
    <div class="stat-card c5"><div class="stat-icon">💬</div><div class="stat-value"><?php echo count($inbox); ?></div><div class="stat-label">Messages</div></div>
  </div>
  <div class="sh"><h2>Leaders Awaiting Verification</h2><div class="sh-line"></div></div>
  <?php if(empty($unverified)): ?><div class="empty"><div class="icon">✅</div><p>All leaders are verified!</p></div>
  <?php else: ?><div class="vgrid"><?php foreach(array_slice($unverified,0,4) as $u): ?>
  <div class="vcard"><div class="vcard-name"><?php echo htmlspecialchars($u['full_name']); ?></div><div class="vcard-email">✉ <?php echo htmlspecialchars($u['email']); ?></div><span class="badge pending">Pending</span>
    <form method="POST" style="margin-top:6px"><input type="hidden" name="target_id" value="<?php echo (int)$u['user_id']; ?>"><input type="hidden" name="new_status" value="verified"><button type="submit" name="change_verification" class="btn btn-green">✓ Verify Now</button></form>
  </div><?php endforeach; ?></div><?php endif; ?>
</div>

<!-- USERS -->
<div id="sec-users" class="section <?php echo $sec==='users'?'active':''; ?>">
  <div class="sh"><h2>User Management</h2><div class="sh-line"></div></div>
  <?php if(isset($_GET['deleted'])): ?><div class="alert info">🗑 User deleted.</div><?php endif; ?>
  <div class="tabs">
    <button class="tab active" id="tab-u" onclick="switchTab('tbl-u','tab-u')">👤 Users (<?php echo count($users); ?>)</button>
    <button class="tab" id="tab-l" onclick="switchTab('tbl-l','tab-l')">🙏 Leaders (<?php echo count($leaders); ?>)</button>
  </div>
  <div id="tbl-u">
    <?php if(empty($users)): ?><div class="empty"><div class="icon">👤</div><p>No users yet.</p></div><?php else: ?>
    <div class="tbl-wrap tbl-users">
      <div class="tbl-head"><div>Name</div><div>Email</div><div>Role</div><div>Joined</div><div>Action</div></div>
      <?php foreach($users as $u): ?>
      <div class="tbl-row"><div class="tcell"><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></div><div class="tcell muted"><?php echo htmlspecialchars($u['email']); ?></div><div class="tcell"><span class="badge user">User</span></div><div class="tcell muted"><?php echo !empty($u['created_at'])?date('M j, Y',strtotime($u['created_at'])):'—'; ?></div>
        <div class="tcell"><form method="POST" onsubmit="return confirm('Delete this user?')"><input type="hidden" name="target_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" name="delete_user" class="btn btn-red" style="font-size:11px;padding:4px 9px">🗑</button></form></div>
      </div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
  <div id="tbl-l" style="display:none">
    <?php if(empty($leaders)): ?><div class="empty"><div class="icon">🙏</div><p>No leaders yet.</p></div><?php else: ?>
    <div class="tbl-wrap tbl-leaders">
      <div class="tbl-head"><div>Name</div><div>Email</div><div>Status</div><div>Joined</div><div>Action</div></div>
      <?php foreach($leaders as $l): ?>
      <div class="tbl-row"><div class="tcell"><strong><?php echo htmlspecialchars($l['full_name']); ?></strong></div><div class="tcell muted"><?php echo htmlspecialchars($l['email']); ?></div><div class="tcell"><span class="badge <?php echo ($l['verification_status']==='verified')?'verified':'pending'; ?>"><?php echo ($l['verification_status']==='verified')?'✓ Verified':'⏳ Pending'; ?></span></div><div class="tcell muted"><?php echo !empty($l['created_at'])?date('M j, Y',strtotime($l['created_at'])):'—'; ?></div>
        <div class="tcell"><form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="target_id" value="<?php echo (int)$l['user_id']; ?>"><button type="submit" name="delete_user" class="btn btn-red" style="font-size:11px;padding:4px 9px">🗑</button></form></div>
      </div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>

<!-- VERIFICATION -->
<div id="sec-verification" class="section <?php echo $sec==='verification'?'active':''; ?>">
  <div class="sh"><h2>Verification</h2><div class="sh-line"></div></div>
  <?php if(isset($_GET['done'])): ?><div class="alert success">✅ Status updated.</div><?php endif; ?>
  <div class="sh" style="margin-top:6px"><h2 style="font-size:15px;color:var(--warning)">⏳ Needs Verification (<?php echo count($unverified); ?>)</h2><div class="sh-line"></div></div>
  <?php if(empty($unverified)): ?><div class="empty" style="padding:20px"><div class="icon">✅</div><p>All verified!</p></div>
  <?php else: ?><div class="vgrid" style="margin-bottom:26px"><?php foreach($unverified as $u): ?>
  <div class="vcard"><div class="vcard-name"><?php echo htmlspecialchars($u['full_name']); ?></div><div class="vcard-email">✉ <?php echo htmlspecialchars($u['email']); ?></div><?php if(!empty($u['created_at'])): ?><div class="vcard-email">Joined: <?php echo date('M j, Y',strtotime($u['created_at'])); ?></div><?php endif; ?><span class="badge pending">Pending</span>
  <form method="POST" style="margin-top:8px"><input type="hidden" name="target_id" value="<?php echo (int)$u['user_id']; ?>"><input type="hidden" name="new_status" value="verified"><button type="submit" name="change_verification" class="btn btn-green">✓ Verify</button></form></div><?php endforeach; ?></div><?php endif; ?>
  <div class="sh"><h2 style="font-size:15px;color:var(--success)">✅ Verified (<?php echo count($verified); ?>)</h2><div class="sh-line"></div></div>
  <?php if(empty($verified)): ?><div class="empty" style="padding:20px"><div class="icon">🙏</div><p>None yet.</p></div>
  <?php else: ?><div class="vgrid"><?php foreach($verified as $v): ?>
  <div class="vcard" style="border-color:rgba(46,204,113,0.3)"><div class="vcard-name"><?php echo htmlspecialchars($v['full_name']); ?></div><div class="vcard-email">✉ <?php echo htmlspecialchars($v['email']); ?></div><span class="badge verified">✓ Verified</span>
  <form method="POST" style="margin-top:8px"><input type="hidden" name="target_id" value="<?php echo (int)$v['user_id']; ?>"><input type="hidden" name="new_status" value="pending"><button type="submit" name="change_verification" class="btn btn-outline" style="font-size:11.5px">↩ Revoke</button></form></div><?php endforeach; ?></div><?php endif; ?>
</div>

<!-- MESSAGES -->
<div id="sec-messages" class="section <?php echo $sec==='messages'?'active':''; ?>">
  <div class="sh"><h2>Messages</h2><div class="sh-line"></div></div>
  <?php if(isset($_GET['sent'])): ?><div class="alert success">✅ Message sent.</div><?php endif; ?>
  <div class="compose">
    <div class="compose-title">✉ Send a Message</div>
    <form method="POST">
      <div class="compose-row">
        <select name="receiver_id" required><option value="">— Select recipient —</option><?php foreach($all_users as $u): ?><option value="<?php echo (int)$u['user_id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role']; ?>)</option><?php endforeach; ?></select>
        <input type="text" name="message" placeholder="Type your message…" required>
        <button type="submit" name="send_message" class="btn btn-blue" style="padding:9px 16px;white-space:nowrap">Send ↑</button>
      </div>
    </form>
  </div>
  <div class="sh"><h2>Inbox (<?php echo count($inbox); ?>)</h2><div class="sh-line"></div></div>
  <?php if(empty($inbox)): ?><div class="empty"><div class="icon">💬</div><p>No messages received.</p></div>
  <?php else: ?><?php foreach($inbox as $m): ?>
  <div class="inbox-item">
    <div class="inbox-from"><?php echo av($m['sender_pfp'],$m['sender_name'],28); ?> <?php echo htmlspecialchars($m['sender_name']); ?><?php if(!empty($m['created_at'])): ?><span style="font-size:12px;color:var(--txt3);font-weight:400;margin-left:auto"><?php echo $m['created_at']; ?></span><?php endif; ?></div>
    <div class="inbox-msg"><?php echo htmlspecialchars($m['message']); ?></div>
    <form method="POST"><div class="reply-row"><input type="hidden" name="receiver_id" value="<?php echo (int)$m['sender_id']; ?>"><input type="text" name="message" placeholder="Reply…" required><button type="submit" name="send_message" class="btn btn-blue">Reply ↑</button></div></form>
  </div>
  <?php endforeach; ?><?php endif; ?>
</div>

<!-- PROFILE -->
<div id="sec-profile" class="section <?php echo $sec==='profile'?'active':''; ?>">
  <?php if($saved): ?><div class="alert success">✅ Profile updated!</div><?php endif; ?>
  <div class="phero">
    <?php echo av($avatar,$name,76,'border:3px solid var(--border)!important;flex-shrink:0'); ?>
    <div style="position:relative"><div class="phero-name"><?php echo htmlspecialchars($name); ?></div><div class="phero-email">✉ <?php echo $email; ?></div><span class="badge user" style="margin-top:8px;display:inline-flex">Admin</span></div>
  </div>
  <div class="pbody">
    <form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'].'?section=profile'; ?>">
      <div class="upload-area">
        <label for="pfp-up"><?php echo av($avatar,$name,52,'margin:0 auto'); ?><p>Click to upload profile picture (JPG/PNG/WEBP · max 2MB)</p></label>
        <input type="file" id="pfp-up" name="profile_image" accept="image/*" onchange="this.form.submit()">
      </div>
    </form>
    <div id="pView">
      <div class="pgrid">
        <div class="fwrap"><div class="flabel">Age</div><div class="fval"><?php echo ($pr['age']!=='')?htmlspecialchars($pr['age']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Gender</div><div class="fval"><?php echo ($pr['gender']!=='')?htmlspecialchars($pr['gender']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Religion</div><div class="fval"><?php echo ($pr['religion']!=='')?htmlspecialchars($pr['religion']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Country</div><div class="fval"><?php echo ($pr['country']!=='')?htmlspecialchars($pr['country']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Language</div><div class="fval"><?php echo ($pr['language']!=='')?htmlspecialchars($pr['language']):'—'; ?></div></div>
        <div class="fwrap full"><div class="flabel">Bio</div><div class="fval" style="white-space:pre-wrap"><?php echo ($pr['bio']!=='')?htmlspecialchars($pr['bio']):'—'; ?></div></div>
      </div>
      <div class="pactions"><button class="btn-primary" onclick="editOn()">✏️ Edit Profile</button></div>
    </div>
    <div id="pEdit" style="display:none">
      <form method="POST"><input type="hidden" name="update_profile" value="1">
        <div class="pgrid">
          <div class="fwrap"><label class="flabel">Age</label><input class="finput" type="number" name="age" min="1" max="120" value="<?php echo htmlspecialchars($pr['age']); ?>"></div>
          <div class="fwrap"><label class="flabel">Gender</label><select class="finput" name="gender"><option value="">— Select —</option><?php foreach(array('Male','Female','Non-binary','Prefer not to say') as $g): ?><option value="<?php echo $g; ?>" <?php echo ($pr['gender']===$g)?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
          <div class="fwrap"><label class="flabel">Religion</label><input class="finput" type="text" name="religion" value="<?php echo htmlspecialchars($pr['religion']); ?>"></div>
          <div class="fwrap"><label class="flabel">Country</label><input class="finput" type="text" name="country" value="<?php echo htmlspecialchars($pr['country']); ?>"></div>
          <div class="fwrap"><label class="flabel">Language</label><input class="finput" type="text" name="language" value="<?php echo htmlspecialchars($pr['language']); ?>"></div>
          <div class="fwrap full"><label class="flabel">Bio</label><textarea class="finput" name="bio"><?php echo htmlspecialchars($pr['bio']); ?></textarea></div>
        </div>
        <div class="pactions"><button type="submit" class="btn-primary">💾 Save</button><button type="button" class="btn-outline" style="padding:9px 18px;border-radius:9px" onclick="editOff()">Cancel</button></div>
      </form>
    </div>
  </div>
</div>

</div></div></div>

<script>
var meta={overview:{t:'Overview',s:'Platform statistics'},users:{t:'Users',s:'Manage all users and leaders'},verification:{t:'Verification',s:'Approve or revoke leader verification'},messages:{t:'Messages',s:'Send and receive messages'},profile:{t:'My Profile',s:'View and edit your admin profile'}};
function show(id){
  document.querySelectorAll('.section').forEach(function(s){s.classList.remove('active');});
  document.querySelectorAll('.nav-btn').forEach(function(n){n.classList.remove('active');});
  document.getElementById('sec-'+id).classList.add('active');
  document.querySelectorAll('.nav-btn').forEach(function(n){if(n.getAttribute('onclick')==="show('"+id+"')") n.classList.add('active');});
  var m=meta[id]||{}; document.getElementById('ttl').textContent=m.t||''; document.getElementById('tsub').textContent=m.s||'';
  if(history.replaceState) history.replaceState(null,'','?section='+id);
}
function switchTab(showId,activeId){
  document.getElementById('tbl-u').style.display='none';
  document.getElementById('tbl-l').style.display='none';
  document.getElementById(showId).style.display='block';
  document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active');});
  document.getElementById(activeId).classList.add('active');
}
function toggleTheme(){
  var html=document.documentElement, isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  document.getElementById('themeBtn').textContent=isDark?'🌙 Dark':'☀️ Light';
  localStorage.setItem('fc_theme',isDark?'light':'dark');
}
(function(){ var s=localStorage.getItem('fc_theme')||'dark'; document.documentElement.setAttribute('data-theme',s); document.getElementById('themeBtn').textContent=s==='dark'?'☀️ Light':'🌙 Dark'; })();
function editOn(){document.getElementById('pView').style.display='none';document.getElementById('pEdit').style.display='block';}
function editOff(){document.getElementById('pEdit').style.display='none';document.getElementById('pView').style.display='block';}
function toast(msg){var t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(function(){t.classList.remove('show');},2600);}
<?php if($saved): ?>toast('Profile saved ✅');<?php endif; ?>
</script>
</body>
</html>