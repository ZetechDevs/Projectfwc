<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){ header("Location: /auth/login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
$name    = isset($_SESSION['name']) ? $_SESSION['name'] : '';

/* ── AJAX: thread ── */
if(isset($_GET['ajax']) && $_GET['ajax']==='thread' && isset($_GET['with'])){
    $with=(int)$_GET['with'];
    $q=mysqli_query($conn,"SELECT message,sender_id,created_at FROM messages WHERE (sender_id='$user_id' AND receiver_id='$with') OR (sender_id='$with' AND receiver_id='$user_id') ORDER BY created_at ASC");
    $rows=array(); if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=array('message'=>$r['message'],'sent'=>((int)$r['sender_id']===$user_id),'time'=>date('g:i A',strtotime($r['created_at']?$r['created_at']:'now')));
    header('Content-Type: application/json'); echo json_encode(array('messages'=>$rows)); exit();
}

/* ── AJAX: send reply ── */
if(isset($_POST['action']) && $_POST['action']==='send_reply'){
    $receiver_id=(int)$_POST['receiver_id'];
    $msg=mysqli_real_escape_string($conn,trim($_POST['message']));
    if($msg!=='' && $receiver_id>0) mysqli_query($conn,"INSERT INTO messages(sender_id,receiver_id,message,created_at) VALUES('$user_id','$receiver_id','$msg',NOW())");
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])){ header('Content-Type: application/json'); echo json_encode(array('ok'=>true)); exit(); }
    header("Location: ".$_SERVER['PHP_SELF']."?section=rooms"); exit();
}

/* ── ACCEPT / REJECT consultation ── */
if(isset($_POST['action']) && ($_POST['action']==='accept_consultation' || $_POST['action']==='reject_consultation')){
    $cons_id   = (int)$_POST['cons_id'];
    $new_status = ($_POST['action']==='accept_consultation') ? 'approved' : 'rejected';
    mysqli_query($conn,
        "UPDATE consultations SET status='$new_status'
         WHERE id='$cons_id' AND leader_id='$user_id'"
    );
    header("Location: ".$_SERVER['PHP_SELF']."?section=requests"); exit();
}

/* ── Profile picture upload ── */
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0){
    $allowed=array('image/jpeg','image/png','image/gif','image/webp');
    if(in_array($_FILES['profile_image']['type'],$allowed) && $_FILES['profile_image']['size']<=2097152){
        $upload_dir=$_SERVER['DOCUMENT_ROOT'].'/assets/uploads/avatars/';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
        $ext=pathinfo($_FILES['profile_image']['name'],PATHINFO_EXTENSION);
        $filename='user_'.$user_id.'_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['profile_image']['tmp_name'],$upload_dir.$filename)){
            $db_path=mysqli_real_escape_string($conn,'assets/uploads/avatars/'.$filename);
            mysqli_query($conn,"UPDATE users SET profile_image='$db_path' WHERE user_id='$user_id'");
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}

/* ── Update profile ── */
if(isset($_POST['action']) && $_POST['action']==='update_profile'){
    $age=(int)$_POST['age']; $gender=mysqli_real_escape_string($conn,$_POST['gender']);
    $religion=mysqli_real_escape_string($conn,$_POST['religion']); $country=mysqli_real_escape_string($conn,$_POST['country']);
    $language=mysqli_real_escape_string($conn,$_POST['language']); $bio=mysqli_real_escape_string($conn,$_POST['bio']);
    $ex=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM user_profile WHERE user_id='$user_id'"));
    if($ex) mysqli_query($conn,"UPDATE user_profile SET age='$age',gender='$gender',religion='$religion',country='$country',language='$language',bio='$bio' WHERE user_id='$user_id'");
    else    mysqli_query($conn,"INSERT INTO user_profile(user_id,age,gender,religion,country,language,bio) VALUES('$user_id','$age','$gender','$religion','$country','$language','$bio')");
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}

/* ── Create meeting ── */
if(isset($_POST['create_room'])){
    $participant=mysqli_real_escape_string($conn,trim($_POST['participant']));
    $mdate=mysqli_real_escape_string($conn,$_POST['mdate']); $mtime=mysqli_real_escape_string($conn,$_POST['mtime']);
    $link=mysqli_real_escape_string($conn,trim($_POST['link']));
    $pu=mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM users WHERE full_name='$participant' LIMIT 1"));
    $room_msg=$pu ? 'Room created!' : 'User not found. Check the name.';
    if($pu) mysqli_query($conn,"INSERT INTO meetings(user_id,leader_id,meeting_link,meeting_date,meeting_time,participant_name) VALUES('".(int)$pu['user_id']."','$user_id','$link','$mdate','$mtime','$participant')");
}

/* ── LOAD DATA ── */
$u_res=mysqli_query($conn,"SELECT * FROM users WHERE user_id='$user_id'");
$ur=mysqli_fetch_assoc($u_res); if(!$ur){ session_destroy(); header("Location: /auth/login.php"); exit(); }
$name  = htmlspecialchars($ur['full_name']);
$email = htmlspecialchars($ur['email']);
$pfp   = !empty($ur['profile_image']) ? '/'.$ur['profile_image'] : '';
$is_verified = ($ur['verification_status']==='verified');

$pr_res=mysqli_query($conn,"SELECT * FROM user_profile WHERE user_id='$user_id'");
$pr=($pr_res && mysqli_num_rows($pr_res)>0) ? mysqli_fetch_assoc($pr_res) : array('age'=>'','gender'=>'','religion'=>'','country'=>'','language'=>'','bio'=>'');

/* ALL consultations — JOIN users to get name + pfp */
$req_res=mysqli_query($conn,
    "SELECT c.*, u.full_name AS user_name, u.profile_image AS user_pfp
     FROM consultations c
     JOIN users u ON u.user_id = c.user_id
     WHERE c.leader_id='$user_id' AND c.status='pending'
     ORDER BY c.created_at DESC"
);
$requests=array(); if($req_res) while($r=mysqli_fetch_assoc($req_res)) $requests[]=$r;

$app_res=mysqli_query($conn,
    "SELECT c.*, u.full_name AS user_name, u.profile_image AS user_pfp
     FROM consultations c
     JOIN users u ON u.user_id = c.user_id
     WHERE c.leader_id='$user_id' AND c.status='approved'
     ORDER BY c.created_at DESC"
);
$appointments=array(); if($app_res) while($r=mysqli_fetch_assoc($app_res)) $appointments[]=$r;

$rej_res=mysqli_query($conn,
    "SELECT c.*, u.full_name AS user_name, u.profile_image AS user_pfp
     FROM consultations c
     JOIN users u ON u.user_id = c.user_id
     WHERE c.leader_id='$user_id' AND c.status='rejected'
     ORDER BY c.created_at DESC"
);
$rejected=array(); if($rej_res) while($r=mysqli_fetch_assoc($rej_res)) $rejected[]=$r;

$pending=count($requests); $approved=count($appointments); $tot_rejected=count($rejected);
$pc=array(); foreach($requests as $c){ $pc[]=$c; } /* pending for notifications */
$mc_res=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM messages WHERE receiver_id='$user_id'");
$msg_count=(int)mysqli_fetch_assoc($mc_res)['cnt'];

/* Rooms */
$rq=mysqli_query($conn,"SELECT DISTINCT u.user_id,u.full_name,u.profile_image,MAX(m.created_at) AS last_msg,(SELECT message FROM messages WHERE (sender_id='$user_id' AND receiver_id=u.user_id) OR (sender_id=u.user_id AND receiver_id='$user_id') ORDER BY created_at DESC LIMIT 1) AS last_message FROM messages m JOIN users u ON u.user_id=CASE WHEN m.sender_id='$user_id' THEN m.receiver_id ELSE m.sender_id END WHERE (m.sender_id='$user_id' OR m.receiver_id='$user_id') GROUP BY u.user_id ORDER BY last_msg DESC");
$rooms=array(); if($rq) while($r=mysqli_fetch_assoc($rq)) $rooms[]=$r;

$mq=mysqli_query($conn,"SELECT mt.*,u.full_name AS user_name FROM meetings mt JOIN users u ON u.user_id=mt.user_id WHERE mt.leader_id='$user_id' ORDER BY mt.meeting_date DESC");
$meetings=array(); if($mq) while($r=mysqli_fetch_assoc($mq)) $meetings[]=$r;

$uq=mysqli_query($conn,"SELECT mt.meeting_date,mt.meeting_time,mt.participant_name,u.full_name AS user_name FROM meetings mt JOIN users u ON u.user_id=mt.user_id WHERE mt.leader_id='$user_id' AND mt.meeting_date>=CURDATE() ORDER BY mt.meeting_date ASC");
$upcoming=array(); if($uq) while($r=mysqli_fetch_assoc($uq)) $upcoming[]=$r;
/* pc already set above — pending consultations for notifications */

/* Reviews of this leader */
$rev_q=mysqli_query($conn,"SELECT r.*,u.full_name AS reviewer,u.profile_image AS reviewer_pfp FROM reviews r JOIN users u ON u.user_id=r.user_id WHERE r.leader_id='$user_id' ORDER BY r.created_at DESC");
$reviews=array(); if($rev_q) while($r=mysqli_fetch_assoc($rev_q)) $reviews[]=$r;
$avg_rating=0; if(!empty($reviews)){ $sum=0; foreach($reviews as $rv) $sum+=(int)$rv['rating']; $avg_rating=round($sum/count($reviews),1); }

$rat_res=mysqli_query($conn,"SELECT AVG(rating) AS avg FROM ratings WHERE leader_id='$user_id'");
$rat_row=mysqli_fetch_assoc($rat_res); $rating=$rat_row['avg']?round((float)$rat_row['avg'],1):0;

$sec=isset($_GET['section'])?$_GET['section']:'dashboard';
$saved=isset($_GET['saved']);

function avatar_html($pfp_path,$full_name,$size=44,$extra=''){
    if(!empty($pfp_path)) return '<img src="'.htmlspecialchars($pfp_path).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;flex-shrink:0;'.$extra.'" alt="">';
    $parts=explode(' ',trim($full_name)); $ini='';
    foreach($parts as $p){ if(strlen($ini)>=2) break; if(!empty($p)) $ini.=strtoupper($p[0]); }
    $colors=array('#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a');
    $bg=$colors[abs(crc32($full_name))%count($colors)]; $fs=floor($size*0.36);
    return '<div style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:'.$bg.';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'.$fs.'px;color:#fff;flex-shrink:0;'.$extra.'">'.htmlspecialchars($ini).'</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leader Dashboard — <?php echo $name; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
[data-theme="dark"]{
  --bg1:#0da1e6;--bg2:#0dccda;
  --glass:rgba(255,255,255,0.10);--glass2:rgba(255,255,255,0.16);--gb:rgba(255,255,255,0.22);
  --txt:#fff;--txt2:rgba(255,255,255,0.72);--txt3:rgba(255,255,255,0.45);
  --sb-bg:rgba(0,0,0,0.28);--sb-link:rgba(255,255,255,0.68);
  --card:rgba(255,255,255,0.10);--card-hover:rgba(255,255,255,0.18);
  --input-bg:rgba(255,255,255,0.10);--input-focus:rgba(255,255,255,0.20);
  --topbar:rgba(255,255,255,0.08);--shadow:0 8px 32px rgba(0,0,0,0.22);
  --badge-v-bg:rgba(46,204,113,0.2);--badge-v-col:#7fffb2;
  --badge-p-bg:rgba(255,213,79,0.2);--badge-p-col:#ffe566;
}
[data-theme="light"]{
  --bg1:#b2ebf2;--bg2:#e0f7fa;
  --glass:rgba(255,255,255,0.55);--glass2:rgba(255,255,255,0.80);--gb:rgba(13,161,230,0.30);
  --txt:#0a2535;--txt2:rgba(10,37,53,0.70);--txt3:rgba(10,37,53,0.40);
  --sb-bg:rgba(255,255,255,0.75);--sb-link:#0a2535;
  --card:rgba(255,255,255,0.65);--card-hover:rgba(255,255,255,0.90);
  --input-bg:rgba(255,255,255,0.70);--input-focus:rgba(255,255,255,0.95);
  --topbar:rgba(255,255,255,0.75);--shadow:0 8px 32px rgba(13,161,230,0.12);
  --badge-v-bg:rgba(46,204,113,0.15);--badge-v-col:#1a7a45;
  --badge-p-bg:rgba(243,156,18,0.15);--badge-p-col:#9a6200;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--txt)}
body{background:linear-gradient(135deg,var(--bg1),var(--bg2));background-attachment:fixed;transition:background 0.4s;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:url('/assets/images/landing.png') center/cover no-repeat;opacity:0.10;z-index:0;pointer-events:none}
a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
button{cursor:pointer;font-family:'DM Sans',sans-serif} input,textarea,select{font-family:'DM Sans',sans-serif}

.app{display:flex;height:100vh;overflow:hidden;position:relative;z-index:1}

.sidebar{width:250px;background:var(--sb-bg);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-right:1px solid var(--gb);display:flex;flex-direction:column;height:100vh;flex-shrink:0;overflow:hidden;transition:background 0.4s}
.sb-brand{padding:20px 18px 14px;border-bottom:1px solid var(--gb)}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--txt)}
.brand-tag{font-size:11px;color:var(--txt3);text-transform:uppercase;letter-spacing:.1em;margin-top:2px}
.sb-user{padding:14px 18px;display:flex;align-items:center;gap:11px;border-bottom:1px solid var(--gb)}
.uname{font-weight:500;color:var(--txt);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.urole{font-size:11px;color:var(--txt3);margin-top:1px}
.sb-nav{flex:1;padding:10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.nav-btn{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--sb-link);font-size:13.5px;cursor:pointer;transition:0.2s;border:none;background:none;width:100%;text-align:left;position:relative}
.nav-btn:hover{background:var(--glass2);color:var(--txt)}
.nav-btn.active{background:var(--glass2);color:var(--txt);font-weight:600;border:1px solid var(--gb)}
.nav-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0}
.nav-badge{margin-left:auto;background:rgba(13,161,230,0.8);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
.nav-badge.warn{background:rgba(255,165,0,0.8)}
.sb-footer{padding:10px;border-top:1px solid var(--gb)}
.logout-btn{display:flex;align-items:center;gap:9px;width:100%;padding:9px 12px;border-radius:10px;color:var(--txt3);font-size:13.5px;cursor:pointer;transition:0.2s;background:none;border:none}
.logout-btn:hover{color:#ff6b6b;background:rgba(231,76,60,0.12)}

.main{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;min-width:0}
.topbar{background:var(--topbar);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid var(--gb);padding:13px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;transition:background 0.4s}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600}
.topbar-sub{font-size:12px;color:var(--txt2);margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:10px}
.theme-toggle{background:var(--glass2);border:1px solid var(--gb);border-radius:99px;padding:6px 12px;font-size:13px;cursor:pointer;color:var(--txt);transition:0.2s}
.content{flex:1;overflow-y:auto;padding:22px 24px}

.section{display:none;animation:fu 0.22s ease}
.section.active{display:block}
@keyframes fu{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.sh{margin-bottom:16px;display:flex;align-items:center;gap:10px}
.sh h2{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;white-space:nowrap;color:var(--txt)}
.sh-line{flex:1;height:1px;background:var(--gb)}
.empty{text-align:center;padding:44px 24px;color:var(--txt2)}
.empty .icon{font-size:40px;margin-bottom:8px}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:13px;margin-bottom:24px}
.stat-card{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:14px;padding:16px;position:relative;overflow:hidden;transition:0.2s}
.stat-card:hover{background:var(--card-hover);box-shadow:var(--shadow)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.c1::before{background:linear-gradient(90deg,var(--bg1),var(--bg2))}
.c2::before{background:linear-gradient(90deg,#ffd700,#ffa500)}
.c3::before{background:linear-gradient(90deg,#40916c,#74c69d)}
.c4::before{background:linear-gradient(90deg,#7209b7,#b5179e)}
.stat-icon{font-size:22px;margin-bottom:7px}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:600;line-height:1;margin-bottom:2px;color:var(--txt)}
.stat-label{font-size:12px;color:var(--txt2);font-weight:500}

/* Cards */
.clist{display:flex;flex-direction:column;gap:11px}
.icard{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:12px;padding:14px 16px;display:flex;align-items:flex-start;gap:12px;transition:0.2s}
.icard:hover{background:var(--card-hover);border-color:rgba(255,255,255,0.4)}
.ibody{flex:1;min-width:0}
.ititle{font-weight:600;font-size:14.5px;color:var(--txt);margin-bottom:2px}
.isub{font-size:13px;color:var(--txt2)}
.imeta{font-size:11.5px;color:var(--txt3);margin-top:3px}
.badge-v{display:inline-flex;align-items:center;gap:4px;background:var(--badge-v-bg);color:var(--badge-v-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}
.badge-p{display:inline-flex;align-items:center;gap:4px;background:var(--badge-p-bg);color:var(--badge-p-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}
.badge-r{display:inline-flex;align-items:center;gap:4px;background:rgba(231,76,60,0.15);color:#ff8a80;border:1px solid rgba(231,76,60,0.3);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}
.rtab{padding:8px 16px;border-radius:99px;background:var(--glass);border:1px solid var(--gb);color:var(--txt2);font-size:13.5px;font-weight:500;cursor:pointer;transition:0.2s}
.rtab:hover{background:var(--glass2);color:var(--txt)}
.rtab.active{background:var(--glass2);color:var(--txt);font-weight:600;border-color:rgba(255,255,255,0.4)}
.btn-join{display:inline-flex;align-items:center;gap:5px;padding:7px 15px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:0.2s}
.btn-join:hover{opacity:.88}
.badge-p{display:inline-flex;align-items:center;gap:4px;background:var(--badge-p-bg);color:var(--badge-p-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}

/* Rooms */
.rooms-wrap{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:14px;overflow:hidden;display:grid;grid-template-columns:260px 1fr;height:500px}
.rlist{border-right:1px solid var(--gb);overflow-y:auto;background:var(--glass)}
.rlist-hd{padding:12px 15px;font-size:11px;font-weight:700;color:var(--txt3);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid var(--gb)}
.ritem{display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;transition:0.2s;border-bottom:1px solid var(--gb)}
.ritem:hover{background:var(--glass2)}
.ritem.active{background:var(--glass2);border-left:3px solid var(--bg1)}
.rname{font-weight:600;font-size:13.5px;color:var(--txt)}
.rprev{font-size:12px;color:var(--txt3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px}
.cwin{display:flex;flex-direction:column;height:100%}
.no-room{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--txt2);font-size:14px;gap:9px}
.no-room .icon{font-size:36px}
.active-chat{display:none;flex-direction:column;height:100%}
.chdr{padding:12px 16px;border-bottom:1px solid var(--gb);display:flex;align-items:center;gap:11px;flex-shrink:0}
.chdr-name{font-weight:600;font-size:14px;color:var(--txt)}
.cmsgs{flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:9px;background:var(--glass)}
.cempty{margin:auto;text-align:center;color:var(--txt2);font-size:13.5px;padding:20px}
.bubble{max-width:70%;padding:9px 13px;border-radius:12px;font-size:13.5px;line-height:1.5;word-break:break-word}
.bubble.sent{background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.bubble.recv{background:var(--glass2);color:var(--txt);align-self:flex-start;border-bottom-left-radius:4px;border:1px solid var(--gb)}
.btime{font-size:10px;margin-top:3px;opacity:.6}
.cinput-row{padding:12px;border-top:1px solid var(--gb);display:flex;gap:8px;flex-shrink:0}
.cinput{flex:1;padding:9px 12px;border:1px solid var(--gb);border-radius:9px;font-size:14px;background:var(--input-bg);color:var(--txt);outline:none;transition:0.2s}
.cinput:focus{background:var(--input-focus);border-color:rgba(255,255,255,0.55)}
.cinput::placeholder{color:var(--txt3)}
.btn-send{padding:9px 16px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-weight:600;font-size:13.5px;transition:0.2s}

/* Meetings */
.mcard{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:15px;margin-bottom:12px;transition:0.2s}
.mcard:hover{background:var(--card-hover)}
.date-blk{background:var(--glass2);border:1px solid var(--gb);border-radius:10px;padding:8px 12px;text-align:center;min-width:56px;flex-shrink:0}
.date-m{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--bg1);font-weight:700}
.date-d{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600;line-height:1;color:var(--txt)}
.minfo{flex:1}
.muser{font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;color:var(--txt);margin-bottom:3px}
.mmeta{font-size:12.5px;color:var(--txt2);margin-bottom:8px}
.mmeta span{margin-right:12px}
.btn-join{display:inline-flex;align-items:center;gap:5px;padding:7px 15px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:0.2s}
.btn-join:hover{opacity:.88}

/* Notif */
.nitem{background:var(--card);border:1px solid var(--gb);border-left:3px solid var(--bg1);border-radius:9px;padding:12px 16px;display:flex;align-items:center;gap:11px;margin-bottom:10px}
.ntxt{font-size:14px;font-weight:500;color:var(--txt)}
.nsub{font-size:12px;color:var(--txt2);margin-top:1px}

/* Profile */
.phero{background:var(--glass2);backdrop-filter:blur(20px);border:1px solid var(--gb);border-radius:16px 16px 0 0;padding:26px;display:flex;align-items:center;gap:18px;max-width:600px;position:relative;overflow:hidden}
.phero::before{content:'';position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:radial-gradient(circle,rgba(13,204,218,0.25),transparent 70%);pointer-events:none}
.phero-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--txt);position:relative}
.phero-email{font-size:13px;color:var(--txt2);margin-top:3px;position:relative}
.pbody{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-top:none;border-radius:0 0 16px 16px;padding:22px 26px;max-width:600px}
.pgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.fwrap{display:flex;flex-direction:column;gap:4px}
.fwrap.full{grid-column:1/-1}
.flabel{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--txt3)}
.fval{font-size:14px;color:var(--txt);padding:8px 0;border-bottom:1px solid var(--gb)}
.finput{padding:8px 11px;border:1px solid var(--gb);border-radius:9px;font-size:14px;color:var(--txt);background:var(--input-bg);outline:none;transition:0.2s;width:100%}
.finput:focus{background:var(--input-focus);border-color:rgba(255,255,255,0.55)}
.finput::placeholder{color:var(--txt3)}
textarea.finput{resize:vertical;min-height:80px}
.pactions{display:flex;gap:9px;margin-top:14px;flex-wrap:wrap}
.btn-primary{padding:9px 18px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;transition:0.2s}
.btn-primary:hover{opacity:.88}
.btn-secondary{padding:9px 18px;background:var(--glass2);color:var(--txt);border:1px solid var(--gb);border-radius:9px;font-size:14px;font-weight:500;transition:0.2s}
.upload-area{border:2px dashed var(--gb);border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;transition:0.2s}
.upload-area:hover{border-color:rgba(255,255,255,0.5);background:var(--glass2)}
.upload-area label{cursor:pointer;display:block}
.upload-area p{font-size:13px;color:var(--txt2);margin-top:6px}
.upload-area input[type="file"]{display:none}

/* Form */
.fgroup{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.flabel2{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--txt3)}

/* Reviews */
.review-card{background:var(--card);border:1px solid var(--gb);border-radius:12px;padding:16px;margin-bottom:11px;transition:0.2s}
.review-card:hover{background:var(--card-hover)}
.review-stars{color:#ffd700;font-size:16px;margin-bottom:8px}
.review-text{font-size:14px;color:var(--txt);font-style:italic;line-height:1.6;margin-bottom:10px}
.review-author{font-size:13px;color:var(--txt2);display:flex;align-items:center;gap:8px}

.alert-success{background:rgba(46,204,113,0.15);border:1px solid rgba(46,204,113,0.35);color:var(--badge-v-col);border-radius:9px;padding:10px 14px;font-size:13.5px;margin-bottom:14px}
.alert-error{background:rgba(231,76,60,0.15);border:1px solid rgba(231,76,60,0.35);color:#ffb3ad;border-radius:9px;padding:10px 14px;font-size:13.5px;margin-bottom:14px}

::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:var(--gb);border-radius:99px}
#toast{position:fixed;bottom:20px;right:20px;background:var(--glass2);backdrop-filter:blur(16px);border:1px solid var(--gb);color:var(--txt);padding:10px 16px;border-radius:9px;font-size:14px;font-weight:500;box-shadow:var(--shadow);transform:translateY(60px);opacity:0;transition:0.25s;z-index:9999}
#toast.show{transform:translateY(0);opacity:1}
@media(max-width:700px){.rooms-wrap{grid-template-columns:1fr}.rlist{display:none}.pgrid{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}.mcard{flex-wrap:wrap}}
</style>
</head>
<body>
<div id="toast"></div>
<div class="app">

<aside class="sidebar">
  <div class="sb-brand"><div class="brand-name">FaithConnect</div><div class="brand-tag">Leader Portal</div></div>
  <div class="sb-user">
    <?php echo avatar_html($pfp,$ur['full_name'],42); ?>
    <div style="min-width:0"><div class="uname"><?php echo $name; ?></div><div class="urole"><?php echo $is_verified?'✓ Verified':'⏳ Pending'; ?></div></div>
  </div>
  <nav class="sb-nav">
    <button class="nav-btn <?php echo $sec==='dashboard'?'active':''; ?>" onclick="show('dashboard')"><span class="nav-icon">🏠</span> Dashboard</button>
    <button class="nav-btn <?php echo $sec==='requests'?'active':''; ?>" onclick="show('requests')"><span class="nav-icon">📥</span> Requests <?php if($pending>0): ?><span class="nav-badge warn"><?php echo $pending; ?></span><?php endif; ?></button>
    <button class="nav-btn <?php echo $sec==='appointments'?'active':''; ?>" onclick="show('appointments')"><span class="nav-icon">📅</span> Appointments</button>
    <button class="nav-btn <?php echo $sec==='rooms'?'active':''; ?>" onclick="show('rooms')"><span class="nav-icon">💬</span> Rooms <?php if($msg_count>0): ?><span class="nav-badge"><?php echo $msg_count; ?></span><?php endif; ?></button>
    <button class="nav-btn <?php echo $sec==='meetings'?'active':''; ?>" onclick="show('meetings')"><span class="nav-icon">🎥</span> Meetings</button>
    <button class="nav-btn <?php echo $sec==='notifications'?'active':''; ?>" onclick="show('notifications')"><span class="nav-icon">🔔</span> Notifications <?php if(count($upcoming)>0): ?><span class="nav-badge"><?php echo count($upcoming); ?></span><?php endif; ?></button>
    <button class="nav-btn <?php echo $sec==='reviews'?'active':''; ?>" onclick="show('reviews')"><span class="nav-icon">⭐</span> Reviews</button>
    <button class="nav-btn <?php echo $sec==='room'?'active':''; ?>" onclick="show('room')"><span class="nav-icon">🛠</span> Create Room</button>
    <button class="nav-btn <?php echo $sec==='profile'?'active':''; ?>" onclick="show('profile')"><span class="nav-icon">👤</span> Profile</button>
  </nav>
  <div class="sb-footer"><a href="/index.php" class="logout-btn"><span>🚪</span> Sign Out</a></div>
</aside>

<div class="main">
<header class="topbar">
  <div><div class="topbar-title" id="ttl">Dashboard</div><div class="topbar-sub" id="tsub">Welcome back, <?php echo $name; ?></div></div>
  <div class="topbar-right">
    <button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">☀️ Light</button>
    <?php echo avatar_html($pfp,$ur['full_name'],34,'border:2px solid var(--gb)'); ?>
  </div>
</header>

<div class="content">

<!-- DASHBOARD -->
<div id="sec-dashboard" class="section <?php echo $sec==='dashboard'?'active':''; ?>">
  <div class="stats-grid">
    <div class="stat-card c1"><div class="stat-icon">📥</div><div class="stat-value"><?php echo $pending; ?></div><div class="stat-label">Pending Requests</div></div>
    <div class="stat-card c2"><div class="stat-icon">📅</div><div class="stat-value"><?php echo $approved; ?></div><div class="stat-label">Appointments</div></div>
    <div class="stat-card c3"><div class="stat-icon">💬</div><div class="stat-value"><?php echo $msg_count; ?></div><div class="stat-label">Messages</div></div>
    <div class="stat-card c4"><div class="stat-icon">⭐</div><div class="stat-value"><?php echo $avg_rating>0?$avg_rating:($rating>0?$rating:0); ?></div><div class="stat-label">Avg Rating</div></div>
  </div>
  <div class="sh"><h2>Recent Requests</h2><div class="sh-line"></div></div>
  <?php $recent=array_slice($requests,0,3); if(empty($recent)): ?><div class="empty"><div class="icon">📋</div><p>No pending requests.</p></div>
  <?php else: ?><div class="clist"><?php foreach($recent as $c): ?>
  <div class="icard"><div style="font-size:22px;flex-shrink:0">🙏</div><div class="ibody"><div class="ititle"><?php echo htmlspecialchars($c['topic']); ?></div><div class="isub"><?php echo htmlspecialchars($c['message']); ?></div></div><span class="badge-p">Pending</span></div>
  <?php endforeach; ?></div><?php endif; ?>
</div>

<!-- REQUESTS -->
<!-- REQUESTS — tabbed: Pending / Approved / Rejected -->
<div id="sec-requests" class="section <?php echo $sec==='requests'?'active':''; ?>">
  <div class="sh"><h2>Consultation Requests</h2><div class="sh-line"></div></div>

  <!-- Tab buttons -->
  <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
    <button class="rtab active" id="rtab-pending" onclick="switchReqTab('pending')">
      ⏳ Pending <span style="background:rgba(255,165,0,0.3);padding:1px 7px;border-radius:99px;font-size:11px;margin-left:4px"><?php echo count($requests); ?></span>
    </button>
    <button class="rtab" id="rtab-approved" onclick="switchReqTab('approved')">
      ✅ Approved <span style="background:rgba(46,204,113,0.25);padding:1px 7px;border-radius:99px;font-size:11px;margin-left:4px"><?php echo count($appointments); ?></span>
    </button>
    <button class="rtab" id="rtab-rejected" onclick="switchReqTab('rejected')">
      ❌ Rejected <span style="background:rgba(231,76,60,0.2);padding:1px 7px;border-radius:99px;font-size:11px;margin-left:4px"><?php echo $tot_rejected; ?></span>
    </button>
  </div>

  <!-- PENDING -->
  <div id="rlist-pending">
    <?php if(empty($requests)): ?>
      <div class="empty"><div class="icon">📥</div><p>No pending requests.</p></div>
    <?php else: ?>
    <div class="clist">
      <?php foreach($requests as $r):
        $upfp = !empty($r['user_pfp']) ? '/'.$r['user_pfp'] : '';
      ?>
      <div class="icard" style="flex-direction:column;gap:12px">
        <!-- Top: user info + topic -->
        <div style="display:flex;align-items:center;gap:12px">
          <!-- Clicking user avatar goes to their profile -->
          <div style="flex-shrink:0"><?php echo avatar_html($upfp,$r['user_name'],48,'border:2px solid var(--gb)'); ?></div>
          <div style="flex:1;min-width:0">
            <div class="ititle"><?php echo htmlspecialchars($r['user_name']); ?></div>
            <div style="font-size:13px;color:var(--txt2);font-weight:600;margin-top:2px">📋 <?php echo htmlspecialchars($r['topic']); ?></div>
          </div>
          <span class="badge-p" style="flex-shrink:0">Pending</span>
        </div>
        <!-- Message body -->
        <div style="background:var(--glass);border:1px solid var(--gb);border-radius:9px;padding:10px 14px;font-size:13.5px;color:var(--txt2);line-height:1.6">
          <?php echo htmlspecialchars($r['message']); ?>
        </div>
        <!-- Date + Accept/Reject -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <?php if(!empty($r['created_at'])): ?>
          <div class="imeta">📅 <?php echo date('M j, Y g:i A', strtotime($r['created_at'])); ?></div>
          <?php endif; ?>
          <div style="display:flex;gap:8px">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="accept_consultation">
              <input type="hidden" name="cons_id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" style="padding:7px 16px;background:rgba(46,204,113,0.2);border:1px solid rgba(46,204,113,0.45);color:var(--badge-v-col);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:0.2s" onmouseover="this.style.background='rgba(46,204,113,0.35)'" onmouseout="this.style.background='rgba(46,204,113,0.2)'">✅ Accept</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="reject_consultation">
              <input type="hidden" name="cons_id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" style="padding:7px 16px;background:rgba(231,76,60,0.15);border:1px solid rgba(231,76,60,0.35);color:#ff8a80;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:0.2s" onmouseover="this.style.background='rgba(231,76,60,0.3)'" onmouseout="this.style.background='rgba(231,76,60,0.15)'">❌ Reject</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- APPROVED -->
  <div id="rlist-approved" style="display:none">
    <?php if(empty($appointments)): ?>
      <div class="empty"><div class="icon">✅</div><p>No approved consultations yet.</p></div>
    <?php else: ?>
    <div class="clist">
      <?php foreach($appointments as $r):
        $upfp = !empty($r['user_pfp']) ? '/'.$r['user_pfp'] : '';
      ?>
      <div class="icard">
        <div style="flex-shrink:0"><?php echo avatar_html($upfp,$r['user_name'],48,'border:2px solid var(--gb)'); ?></div>
        <div class="ibody">
          <div class="ititle"><?php echo htmlspecialchars($r['user_name']); ?></div>
          <div style="font-size:13px;color:var(--txt2);font-weight:600;margin:2px 0">📋 <?php echo htmlspecialchars($r['topic']); ?></div>
          <div class="isub"><?php echo htmlspecialchars(mb_substr($r['message'],0,80)); ?></div>
          <?php if(!empty($r['created_at'])): ?><div class="imeta"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></div><?php endif; ?>
        </div>
        <span class="badge-v" style="flex-shrink:0">✅ Approved</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- REJECTED -->
  <div id="rlist-rejected" style="display:none">
    <?php if(empty($rejected)): ?>
      <div class="empty"><div class="icon">❌</div><p>No rejected requests.</p></div>
    <?php else: ?>
    <div class="clist">
      <?php foreach($rejected as $r):
        $upfp = !empty($r['user_pfp']) ? '/'.$r['user_pfp'] : '';
      ?>
      <div class="icard" style="opacity:0.8">
        <div style="flex-shrink:0"><?php echo avatar_html($upfp,$r['user_name'],48,'border:2px solid var(--gb)'); ?></div>
        <div class="ibody">
          <div class="ititle"><?php echo htmlspecialchars($r['user_name']); ?></div>
          <div style="font-size:13px;color:var(--txt2);font-weight:600;margin:2px 0">📋 <?php echo htmlspecialchars($r['topic']); ?></div>
          <div class="isub"><?php echo htmlspecialchars(mb_substr($r['message'],0,80)); ?></div>
          <?php if(!empty($r['created_at'])): ?><div class="imeta"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></div><?php endif; ?>
        </div>
        <span class="badge-r" style="flex-shrink:0">❌ Rejected</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- APPOINTMENTS — from meetings table -->
<div id="sec-appointments" class="section <?php echo $sec==='appointments'?'active':''; ?>"><?php
  /* Upcoming meetings for appointments panel */
  $apt_q = mysqli_query($conn,
      "SELECT mt.*, u.full_name AS user_name, u.profile_image AS user_pfp
       FROM meetings mt
       JOIN users u ON u.user_id = mt.user_id
       WHERE mt.leader_id='$user_id' AND mt.meeting_date >= CURDATE()
       ORDER BY mt.meeting_date ASC, mt.meeting_time ASC"
  );
  $apts=array(); if($apt_q) while($r=mysqli_fetch_assoc($apt_q)) $apts[]=$r;
?>
  <div class="sh"><h2>Upcoming Appointments</h2><div class="sh-line"></div></div>
  <?php if(empty($apts)): ?>
    <div class="empty"><div class="icon">📅</div><p>No upcoming appointments. Use Create Room to schedule one.</p></div>
  <?php else: ?>
  <div class="clist">
    <?php foreach($apts as $m):
      $upfp  = !empty($m['user_pfp']) ? '/'.$m['user_pfp'] : '';
      $md    = isset($m['meeting_date']) ? $m['meeting_date'] : '';
      $mtime = !empty($m['meeting_time']) ? date('g:i A', strtotime($m['meeting_time'])) : 'TBD';
      $fdate = $md ? date('l, F j Y', strtotime($md)) : 'TBD';
      $days  = $md ? (int)floor((strtotime($md)-strtotime('today'))/86400) : -1;
      if($days===0)      $cd='🔴 Today!';
      elseif($days===1)  $cd='🟡 Tomorrow';
      elseif($days>1)    $cd='🟢 In '.$days.' days';
      else               $cd='';
    ?>
    <div class="icard" style="align-items:center;gap:14px">
      <div style="flex-shrink:0;text-align:center">
        <?php echo avatar_html($upfp,$m['user_name'],54,'border:2px solid var(--gb)'); ?>
        <div style="font-size:10px;color:var(--txt3);margin-top:4px">Participant</div>
      </div>
      <div class="ibody">
        <div class="ititle"><?php echo htmlspecialchars($m['user_name']); ?></div>
        <div class="imeta" style="font-size:13px;margin-top:4px">🗓 <?php echo $fdate; ?> &nbsp; 🕐 <?php echo $mtime; ?></div>
        <?php if($cd): ?><div style="margin-top:6px;font-size:13px;font-weight:600"><?php echo $cd; ?></div><?php endif; ?>
      </div>
      <?php if(!empty($m['meeting_link'])): ?>
      <a class="btn-join" href="<?php echo htmlspecialchars($m['meeting_link']); ?>" target="_blank" rel="noopener" style="flex-shrink:0">🎥 Join</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ROOMS -->
<div id="sec-rooms" class="section <?php echo $sec==='rooms'?'active':''; ?>">
  <div class="sh"><h2>Message Rooms</h2><div class="sh-line"></div></div>
  <?php if(empty($rooms)): ?><div class="empty"><div class="icon">💬</div><p>No conversations yet.</p></div>
  <?php else: ?>
  <div class="rooms-wrap">
    <div class="rlist">
      <div class="rlist-hd">Conversations</div>
      <?php foreach($rooms as $r):
        $rpfp = !empty($r['profile_image']) ? '/'.$r['profile_image'] : '';
        $preview = mb_substr(isset($r['last_message'])?$r['last_message']:'',0,38);
      ?>
      <div class="ritem" id="room-<?php echo (int)$r['user_id']; ?>" onclick="openRoom(<?php echo (int)$r['user_id']; ?>,'<?php echo addslashes(htmlspecialchars($r['full_name'])); ?>','<?php echo addslashes($rpfp); ?>')">
        <?php echo avatar_html($rpfp,$r['full_name'],36); ?>
        <div style="min-width:0"><div class="rname"><?php echo htmlspecialchars($r['full_name']); ?></div><div class="rprev"><?php echo htmlspecialchars($preview); ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cwin" id="chatWin">
      <div class="no-room" id="noRoom"><div class="icon">💬</div><p>Select a conversation</p></div>
      <div class="active-chat" id="activeChat">
        <div class="chdr" id="chatHdr"><div id="chatAv"></div><div><div class="chdr-name" id="chatName"></div></div></div>
        <div class="cmsgs" id="chatMsgs"><div class="cempty">Loading…</div></div>
        <div class="cinput-row">
          <input type="text" class="cinput" id="chatInput" placeholder="Type a reply…" autocomplete="off">
          <button class="btn-send" onclick="sendReply()">Send ↑</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- MEETINGS -->
<div id="sec-meetings" class="section <?php echo $sec==='meetings'?'active':''; ?>">
  <div class="sh"><h2>Meetings</h2><div class="sh-line"></div></div>
  <?php if(empty($meetings)): ?><div class="empty"><div class="icon">📅</div><p>No meetings yet.</p></div>
  <?php else: ?><?php foreach($meetings as $m):
    $md=isset($m['meeting_date'])?$m['meeting_date']:''; $isup=$md&&strtotime($md)>=strtotime('today');
  ?>
  <div class="mcard">
    <div class="date-blk"><div class="date-m"><?php echo $md?date('M',strtotime($md)):'---'; ?></div><div class="date-d"><?php echo $md?date('j',strtotime($md)):'--'; ?></div></div>
    <div class="minfo">
      <div class="muser">With <?php echo htmlspecialchars($m['user_name']); ?><?php if(!empty($m['participant_name'])): ?> <span style="font-size:12px;color:var(--txt3)">(<?php echo htmlspecialchars($m['participant_name']); ?>)</span><?php endif; ?></div>
      <div class="mmeta"><span>🗓 <?php echo $md?date('l, F j Y',strtotime($md)):'TBD'; ?></span><span>🕐 <?php echo !empty($m['meeting_time'])?date('g:i A',strtotime($m['meeting_time'])):'TBD'; ?></span><?php if($isup): ?><span style="color:var(--badge-v-col);font-weight:600">● Upcoming</span><?php else: ?><span style="color:var(--txt3)">Completed</span><?php endif; ?></div>
      <?php if(!empty($m['meeting_link'])&&$isup): ?><a class="btn-join" href="<?php echo htmlspecialchars($m['meeting_link']); ?>" target="_blank" rel="noopener">🎥 Join</a><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?><?php endif; ?>
</div>

<!-- NOTIFICATIONS -->
<div id="sec-notifications" class="section <?php echo $sec==='notifications'?'active':''; ?>">
  <div class="sh"><h2>Notifications</h2><div class="sh-line"></div></div>
  <?php if(empty($pc)&&empty($upcoming)): ?><div class="empty"><div class="icon">🔔</div><p>Nothing new.</p></div>
  <?php else: ?>
  <?php foreach($pc as $c): ?><div class="nitem"><div style="font-size:20px;flex-shrink:0">📋</div><div style="flex:1"><div class="ntxt">New consultation from <strong><?php echo htmlspecialchars($c['topic']); ?></strong></div></div><span class="badge-p">Pending</span></div><?php endforeach; ?>
  <?php foreach($upcoming as $u): ?><div class="nitem"><div style="font-size:20px;flex-shrink:0">📅</div><div style="flex:1"><div class="ntxt">Meeting with <strong><?php echo htmlspecialchars($u['user_name']); ?></strong></div><div class="nsub"><?php echo date('l, F j Y',strtotime($u['meeting_date'])); ?> at <?php echo date('g:i A',strtotime($u['meeting_time'])); ?></div></div></div><?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- REVIEWS -->
<div id="sec-reviews" class="section <?php echo $sec==='reviews'?'active':''; ?>">
  <div class="sh"><h2>My Reviews</h2><div class="sh-line"></div></div>
  <?php if(!empty($avg_rating)): ?><div style="background:var(--glass2);border:1px solid var(--gb);border-radius:12px;padding:16px;text-align:center;margin-bottom:18px"><div style="font-size:36px">⭐</div><div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:var(--txt)"><?php echo $avg_rating; ?> / 5</div><div style="font-size:13px;color:var(--txt2)"><?php echo count($reviews); ?> review<?php echo count($reviews)!==1?'s':''; ?></div></div><?php endif; ?>
  <?php if(empty($reviews)): ?><div class="empty"><div class="icon">⭐</div><p>No reviews yet.</p></div>
  <?php else: ?>
  <?php foreach($reviews as $rv):
    $rp = !empty($rv['reviewer_pfp']) ? '/'.$rv['reviewer_pfp'] : '';
    $s  = (int)$rv['rating'];
    $stars = str_repeat('★',$s).str_repeat('☆',5-$s);
  ?>
  <div class="review-card">
    <div class="review-stars"><?php echo $stars; ?></div>
    <div class="review-text">"<?php echo htmlspecialchars($rv['review_text']); ?>"</div>
    <div class="review-author"><?php echo avatar_html($rp,$rv['reviewer'],28); ?><span><?php echo htmlspecialchars($rv['reviewer']); ?></span></div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- CREATE ROOM -->
<div id="sec-room" class="section <?php echo $sec==='room'?'active':''; ?>">
  <div class="sh"><h2>Create Meeting Room</h2><div class="sh-line"></div></div>
  <?php if(isset($room_msg)): ?><div class="<?php echo strpos($room_msg,'created')!==false?'alert-success':'alert-error'; ?>"><?php echo htmlspecialchars($room_msg); ?></div><?php endif; ?>
  <div style="max-width:460px">
    <form method="POST">
      <div class="fgroup"><label class="flabel2">Participant Full Name</label><input class="finput" type="text" name="participant" placeholder="Exact name as registered" required></div>
      <div class="fgroup"><label class="flabel2">Date</label><input class="finput" type="date" name="mdate" required></div>
      <div class="fgroup"><label class="flabel2">Time</label><input class="finput" type="time" name="mtime" required></div>
      <div class="fgroup"><label class="flabel2">Meeting Link</label><input class="finput" type="text" name="link" placeholder="https://zoom.us/j/…" required></div>
      <button type="submit" name="create_room" class="btn-primary">🛠 Create Room</button>
    </form>
  </div>
</div>

<!-- PROFILE -->
<div id="sec-profile" class="section <?php echo $sec==='profile'?'active':''; ?>">
  <?php if($saved): ?><div class="alert-success">✅ Profile updated!</div><?php endif; ?>
  <div class="phero">
    <?php echo avatar_html($pfp,$ur['full_name'],80,'border:3px solid var(--gb)!important;flex-shrink:0'); ?>
    <div style="position:relative"><div class="phero-name"><?php echo $name; ?></div><div class="phero-email">✉ <?php echo $email; ?></div><div style="margin-top:6px"><?php if($is_verified): ?><span class="badge-v">✓ Verified</span><?php else: ?><span class="badge-p">⏳ Pending</span><?php endif; ?></div></div>
  </div>
  <div class="pbody">
    <form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'].'?section=profile'; ?>">
      <div class="upload-area">
        <label for="pfp-upload"><?php echo avatar_html($pfp,$ur['full_name'],52,'margin:0 auto'); ?><p>Click to upload profile picture (JPG/PNG/WEBP · max 2MB)</p></label>
        <input type="file" id="pfp-upload" name="profile_image" accept="image/*" onchange="this.form.submit()">
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
      <form method="POST"><input type="hidden" name="action" value="update_profile">
        <div class="pgrid">
          <div class="fwrap"><label class="flabel">Age</label><input class="finput" type="number" name="age" min="1" max="120" value="<?php echo htmlspecialchars($pr['age']); ?>"></div>
          <div class="fwrap"><label class="flabel">Gender</label><select class="finput" name="gender"><option value="">— Select —</option><?php foreach(array('Male','Female','Non-binary','Prefer not to say') as $g): ?><option value="<?php echo $g; ?>" <?php echo ($pr['gender']===$g)?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
          <div class="fwrap"><label class="flabel">Religion</label><input class="finput" type="text" name="religion" value="<?php echo htmlspecialchars($pr['religion']); ?>"></div>
          <div class="fwrap"><label class="flabel">Country</label><input class="finput" type="text" name="country" value="<?php echo htmlspecialchars($pr['country']); ?>"></div>
          <div class="fwrap"><label class="flabel">Language</label><input class="finput" type="text" name="language" value="<?php echo htmlspecialchars($pr['language']); ?>"></div>
          <div class="fwrap full"><label class="flabel">Bio</label><textarea class="finput" name="bio"><?php echo htmlspecialchars($pr['bio']); ?></textarea></div>
        </div>
        <div class="pactions"><button type="submit" class="btn-primary">💾 Save</button><button type="button" class="btn-secondary" onclick="editOff()">Cancel</button></div>
      </form>
    </div>
  </div>
</div>

</div></div></div>

<script>
var meta={dashboard:{t:'Dashboard',s:'Your activity overview'},requests:{t:'Requests',s:'Pending consultation requests'},appointments:{t:'Appointments',s:'Approved consultations'},rooms:{t:'Rooms',s:'Message conversations'},meetings:{t:'Meetings',s:'All scheduled sessions'},notifications:{t:'Notifications',s:'Alerts and reminders'},reviews:{t:'My Reviews',s:'What users say about you'},room:{t:'Create Room',s:'Schedule a meeting'},profile:{t:'My Profile',s:'View and edit your details'}};
function switchReqTab(status){
  var lists=['pending','approved','rejected'];
  for(var i=0;i<lists.length;i++){
    document.getElementById('rlist-'+lists[i]).style.display=(lists[i]===status)?'block':'none';
    document.getElementById('rtab-'+lists[i]).classList.remove('active');
  }
  document.getElementById('rtab-'+status).classList.add('active');
}

function show(id){
  document.querySelectorAll('.section').forEach(function(s){s.classList.remove('active');});
  document.querySelectorAll('.nav-btn').forEach(function(n){n.classList.remove('active');});
  document.getElementById('sec-'+id).classList.add('active');
  document.querySelectorAll('.nav-btn').forEach(function(n){if(n.getAttribute('onclick')==="show('"+id+"')") n.classList.add('active');});
  var m=meta[id]||{}; document.getElementById('ttl').textContent=m.t||''; document.getElementById('tsub').textContent=m.s||'';
  if(history.replaceState) history.replaceState(null,'','?section='+id);
}
function toggleTheme(){
  var html=document.documentElement, isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  document.getElementById('themeBtn').textContent=isDark?'🌙 Dark':'☀️ Light';
  localStorage.setItem('fc_theme',isDark?'light':'dark');
}
(function(){ var s=localStorage.getItem('fc_theme')||'dark'; document.documentElement.setAttribute('data-theme',s); document.getElementById('themeBtn').textContent=s==='dark'?'☀️ Light':'🌙 Dark'; })();

var aUid=null,aUname='';
function openRoom(uid,uname,upfp){
  aUid=uid; aUname=uname;
  document.querySelectorAll('.ritem').forEach(function(r){r.classList.remove('active');});
  var el=document.getElementById('room-'+uid); if(el) el.classList.add('active');
  document.getElementById('noRoom').style.display='none';
  var ac=document.getElementById('activeChat'); ac.style.display='flex';
  var av=document.getElementById('chatAv');
  if(upfp) av.innerHTML='<img src="'+upfp+'" style="width:32px;height:32px;border-radius:50%;object-fit:cover">';
  else av.innerHTML=ini(uname,32);
  document.getElementById('chatName').textContent=uname;
  loadThread(uid); document.getElementById('chatInput').focus();
}
function loadThread(uid){
  document.getElementById('chatMsgs').innerHTML='<div class="cempty">Loading…</div>';
  fetch('?ajax=thread&with='+uid).then(function(r){return r.json();}).then(function(d){
    var b=document.getElementById('chatMsgs');
    if(!d.messages||!d.messages.length){b.innerHTML='<div class="cempty">No messages yet.</div>';return;}
    b.innerHTML=d.messages.map(function(m){return '<div><div class="bubble '+(m.sent?'sent':'recv')+'">'+esc(m.message)+'<div class="btime">'+m.time+'</div></div></div>';}).join('');
    b.scrollTop=b.scrollHeight;
  }).catch(function(){});
}
function sendReply(){
  var input=document.getElementById('chatInput'),msg=input.value.trim();
  if(!msg||!aUid) return; input.value='';
  fetch(window.location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:'action=send_reply&receiver_id='+aUid+'&message='+encodeURIComponent(msg)})
  .then(function(r){return r.json();}).then(function(d){if(d.ok){loadThread(aUid);toast('Replied to '+aUname);}}).catch(function(){});
}
document.getElementById('chatInput').addEventListener('keydown',function(e){if(e.key==='Enter') sendReply();});
function editOn(){document.getElementById('pView').style.display='none';document.getElementById('pEdit').style.display='block';}
function editOff(){document.getElementById('pEdit').style.display='none';document.getElementById('pView').style.display='block';}
function toast(msg){var t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(function(){t.classList.remove('show');},2600);}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function ini(name,size){var p=name.split(' '),i='';for(var x=0;x<p.length;x++){if(i.length>=2)break;if(p[x])i+=p[x][0].toUpperCase();}var c=['#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a'],h=0;for(var x=0;x<name.length;x++)h+=name.charCodeAt(x);return '<div style="width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+c[h%c.length]+';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'+Math.floor(size*0.36)+'px;color:#fff">'+esc(i)+'</div>';}
<?php if($saved): ?>toast('Profile saved ✅');<?php endif; ?>
</script>
</body>
</html>