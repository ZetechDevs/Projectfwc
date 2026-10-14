<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){ header("Location: /auth/login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];

/* ── Mark messages as seen (resets badge) ── */
if(isset($_GET['mark_seen'])){
    $_SESSION['msgs_last_seen'] = date('Y-m-d H:i:s');
    exit();
}

/* ── AJAX: thread loader ── */
if(isset($_GET['ajax']) && $_GET['ajax']==='thread' && isset($_GET['with'])){
    $with = (int)$_GET['with'];
    $q = mysqli_query($conn,"SELECT message,sender_id,created_at FROM messages
         WHERE (sender_id='$user_id' AND receiver_id='$with') OR (sender_id='$with' AND receiver_id='$user_id')
         ORDER BY created_at ASC");
    $rows = array();
    if($q) while($r=mysqli_fetch_assoc($q)){
        $rows[] = array('message'=>$r['message'],'sent'=>((int)$r['sender_id']===$user_id),'time'=>date('g:i A',strtotime($r['created_at']?$r['created_at']:'now')));
    }
    header('Content-Type: application/json'); echo json_encode(array('messages'=>$rows)); exit();
}

/* ── AJAX: send message ── */
if(isset($_POST['action']) && $_POST['action']==='send_message'){
    $receiver_id = (int)$_POST['receiver_id'];
    $msg = mysqli_real_escape_string($conn,trim($_POST['message']));
    if($msg!=='' && $receiver_id>0)
        mysqli_query($conn,"INSERT INTO messages(sender_id,receiver_id,message,created_at) VALUES('$user_id','$receiver_id','$msg',NOW())");
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])){ header('Content-Type: application/json'); echo json_encode(array('ok'=>true)); exit(); }
    header("Location: ".$_SERVER['PHP_SELF']); exit();
}

/* ── Submit review ── */
if(isset($_POST['action']) && $_POST['action']==='submit_review'){
    $leader_id = (int)$_POST['leader_id'];
    $rating    = min(5,max(1,(int)$_POST['rating']));
    $text      = mysqli_real_escape_string($conn,trim($_POST['review_text']));
    if($text!=='' && $leader_id>0){
        $ex = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM reviews WHERE user_id='$user_id' AND leader_id='$leader_id'"));
        if($ex) mysqli_query($conn,"UPDATE reviews SET review_text='$text',rating='$rating' WHERE user_id='$user_id' AND leader_id='$leader_id'");
        else    mysqli_query($conn,"INSERT INTO reviews(user_id,leader_id,review_text,rating,created_at) VALUES('$user_id','$leader_id','$text','$rating',NOW())");
    }
    header("Location: ".$_SERVER['PHP_SELF']."?section=home&reviewed=1"); exit();
}

/* ── Profile picture upload ── */
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0){
    $allowed = array('image/jpeg','image/png','image/gif','image/webp');
    if(in_array($_FILES['profile_image']['type'],$allowed) && $_FILES['profile_image']['size']<=2097152){
        $upload_dir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/avatars/';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
        $ext      = pathinfo($_FILES['profile_image']['name'],PATHINFO_EXTENSION);
        $filename = 'user_'.$user_id.'_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['profile_image']['tmp_name'],$upload_dir.$filename)){
            $db_path = mysqli_real_escape_string($conn,'assets/uploads/avatars/'.$filename);
            mysqli_query($conn,"UPDATE users SET profile_image='$db_path' WHERE user_id='$user_id'");
            $upload_ok = true;
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}

/* ── Update profile ── */
if(isset($_POST['action']) && $_POST['action']==='update_profile'){
    $age=$_POST['age']; $gender=mysqli_real_escape_string($conn,$_POST['gender']);
    $religion=mysqli_real_escape_string($conn,$_POST['religion']); $country=mysqli_real_escape_string($conn,$_POST['country']);
    $language=mysqli_real_escape_string($conn,$_POST['language']); $bio=mysqli_real_escape_string($conn,$_POST['bio']);
    $ex=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM user_profile WHERE user_id='$user_id'"));
    if($ex) mysqli_query($conn,"UPDATE user_profile SET age='$age',gender='$gender',religion='$religion',country='$country',language='$language',bio='$bio' WHERE user_id='$user_id'");
    else    mysqli_query($conn,"INSERT INTO user_profile(user_id,age,gender,religion,country,language,bio) VALUES('$user_id','$age','$gender','$religion','$country','$language','$bio')");
    header("Location: ".$_SERVER['PHP_SELF']."?section=profile&saved=1"); exit();
}

/* ── Submit consultation request ── */
if(isset($_POST['action']) && $_POST['action']==='submit_consultation'){
    $leader_id = (int)$_POST['leader_id'];
    $topic     = mysqli_real_escape_string($conn, trim($_POST['topic']));
    $msg       = mysqli_real_escape_string($conn, trim($_POST['consult_message']));
    if($topic!=='' && $leader_id>0){
        /* Only one active request per leader at a time */
        $ex = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM consultations WHERE user_id='$user_id' AND leader_id='$leader_id' AND status='pending'"
        ));
        if(!$ex){
            mysqli_query($conn,
                "INSERT INTO consultations(user_id,leader_id,topic,message,status,created_at)
                 VALUES('$user_id','$leader_id','$topic','$msg','pending',NOW())"
            );
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']."?section=consultations&sent=1"); exit();
}

/* ── LOAD DATA ── */
$u_res = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$user_id'");
$ur    = mysqli_fetch_assoc($u_res);
$name  = htmlspecialchars($ur['full_name']);
$email = htmlspecialchars($ur['email']);
$pfp   = !empty($ur['profile_image']) ? '/'.$ur['profile_image'] : '';

$pr_res = mysqli_query($conn,"SELECT * FROM user_profile WHERE user_id='$user_id'");
$pr     = ($pr_res && mysqli_num_rows($pr_res)>0) ? mysqli_fetch_assoc($pr_res) : array('age'=>'','gender'=>'','religion'=>'','country'=>'','language'=>'','bio'=>'');

/* Leaders — with avg rating from reviews table */
$leaders_q = mysqli_query($conn,
    "SELECT u.user_id, u.full_name, u.profile_image, u.verification_status, up.bio,
            ROUND(COALESCE(AVG(r.rating),0),1) AS avg_rating,
            COUNT(r.id) AS review_count
     FROM users u
     LEFT JOIN user_profile up ON up.user_id = u.user_id
     LEFT JOIN reviews r ON r.leader_id = u.user_id
     WHERE u.role='leader'
     GROUP BY u.user_id
     ORDER BY u.full_name ASC"
);
$leaders=array(); if($leaders_q) while($l=mysqli_fetch_assoc($leaders_q)) $leaders[]=$l;

/* Messages — all people this user has exchanged messages with (rooms style) */
$rooms_q = mysqli_query($conn,
    "SELECT DISTINCT u.user_id, u.full_name, u.profile_image,
            MAX(m.created_at) AS last_msg,
            (SELECT message FROM messages
             WHERE (sender_id='$user_id' AND receiver_id=u.user_id)
                OR (sender_id=u.user_id AND receiver_id='$user_id')
             ORDER BY created_at DESC LIMIT 1) AS last_message
     FROM messages m
     JOIN users u ON u.user_id = CASE WHEN m.sender_id='$user_id' THEN m.receiver_id ELSE m.sender_id END
     WHERE (m.sender_id='$user_id' OR m.receiver_id='$user_id')
     GROUP BY u.user_id
     ORDER BY last_msg DESC"
);
$msg_rooms=array(); if($rooms_q) while($r=mysqli_fetch_assoc($rooms_q)) $msg_rooms[]=$r;

/* Unread message count — messages received that are newer than last seen timestamp stored in session */
if(!isset($_SESSION['msgs_last_seen'])) $_SESSION['msgs_last_seen'] = '2000-01-01 00:00:00';
$unread_res = mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM messages
     WHERE receiver_id='$user_id' AND created_at > '".$_SESSION['msgs_last_seen']."'"
);
$unread_count = $unread_res ? (int)mysqli_fetch_assoc($unread_res)['cnt'] : 0;

/* Consultations this user has submitted */
$cons_q = mysqli_query($conn,
    "SELECT c.*, u.full_name AS leader_name, u.profile_image AS leader_pfp, u.verification_status AS leader_ver
     FROM consultations c
     JOIN users u ON u.user_id = c.leader_id
     WHERE c.user_id='$user_id'
     ORDER BY c.created_at DESC"
);
$consultations=array(); if($cons_q) while($c=mysqli_fetch_assoc($cons_q)) $consultations[]=$c;

/* Notifications from DB + upcoming meetings */
$notif_q = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC"
);
$db_notifs=array(); if($notif_q) while($n=mysqli_fetch_assoc($notif_q)) $db_notifs[]=$n;

$meet_notif_q = mysqli_query($conn,
    "SELECT mt.meeting_date, mt.meeting_time, u.full_name AS leader_name
     FROM meetings mt
     JOIN users u ON u.user_id = mt.leader_id
     WHERE mt.user_id='$user_id' AND mt.meeting_date >= CURDATE()
     ORDER BY mt.meeting_date ASC"
);
$notifs=array(); if($meet_notif_q) while($n=mysqli_fetch_assoc($meet_notif_q)) $notifs[]=$n;
$total_notif_badge = count($db_notifs) + count($notifs);

/* Meetings */
$meet_q = mysqli_query($conn,
    "SELECT mt.*, u.full_name AS leader_name, u.profile_image AS leader_pfp
     FROM meetings mt
     JOIN users u ON u.user_id = mt.leader_id
     WHERE mt.user_id='$user_id'
     ORDER BY mt.meeting_date ASC"
);
$meetings=array(); if($meet_q) while($m=mysqli_fetch_assoc($meet_q)) $meetings[]=$m;

$sec   = isset($_GET['section']) ? $_GET['section'] : 'home';
$saved = isset($_GET['saved']);

/* ── Avatar helper ── */
function avatar_html($pfp_path, $full_name, $size=44, $extra_style=''){
    if(!empty($pfp_path)){
        return '<img src="'.htmlspecialchars($pfp_path).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;flex-shrink:0;'.$extra_style.'" alt="">';
    }
    $parts=explode(' ',trim($full_name)); $ini='';
    foreach($parts as $p){ if(strlen($ini)>=2) break; if(!empty($p)) $ini.=strtoupper($p[0]); }
    $colors=array('#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a');
    $bg=$colors[abs(crc32($full_name))%count($colors)];
    $fs=floor($size*0.36);
    return '<div style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:'.$bg.';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'.$fs.'px;color:#fff;flex-shrink:0;'.$extra_style.'">'.htmlspecialchars($ini).'</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — <?php echo $name; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ── DARK MODE (default) ── */
[data-theme="dark"]{
  --bg1:#0da1e6; --bg2:#0dccda;
  --glass:rgba(255,255,255,0.10); --glass2:rgba(255,255,255,0.16);
  --gb:rgba(255,255,255,0.22);
  --txt:#fff; --txt2:rgba(255,255,255,0.72); --txt3:rgba(255,255,255,0.45);
  --sb-bg:rgba(0,0,0,0.28); --sb-link:rgba(255,255,255,0.68);
  --card:rgba(255,255,255,0.10); --card-hover:rgba(255,255,255,0.18);
  --input-bg:rgba(255,255,255,0.10); --input-focus:rgba(255,255,255,0.20);
  --topbar:rgba(255,255,255,0.08);
  --shadow:0 8px 32px rgba(0,0,0,0.22);
  --badge-v-bg:rgba(46,204,113,0.2); --badge-v-col:#7fffb2;
  --badge-p-bg:rgba(255,213,79,0.2); --badge-p-col:#ffe566;
  --toggle-icon:☀️;
}
/* ── LIGHT MODE ── */
[data-theme="light"]{
  --bg1:#b2ebf2; --bg2:#e0f7fa;
  --glass:rgba(255,255,255,0.55); --glass2:rgba(255,255,255,0.80);
  --gb:rgba(13,161,230,0.30);
  --txt:#0a2535; --txt2:rgba(10,37,53,0.70); --txt3:rgba(10,37,53,0.40);
  --sb-bg:rgba(255,255,255,0.75); --sb-link:#0a2535;
  --card:rgba(255,255,255,0.65); --card-hover:rgba(255,255,255,0.90);
  --input-bg:rgba(255,255,255,0.70); --input-focus:rgba(255,255,255,0.95);
  --topbar:rgba(255,255,255,0.75);
  --shadow:0 8px 32px rgba(13,161,230,0.12);
  --badge-v-bg:rgba(46,204,113,0.15); --badge-v-col:#1a7a45;
  --badge-p-bg:rgba(243,156,18,0.15); --badge-p-col:#9a6200;
  --toggle-icon:🌙;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--txt);overflow-x:hidden}
body{background:linear-gradient(135deg,var(--bg1),var(--bg2));background-attachment:fixed;transition:background 0.4s}
body::before{content:'';position:fixed;inset:0;background:url('/assets/images/landing.png') center/cover no-repeat;opacity:0.10;z-index:0;pointer-events:none}
a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
button{cursor:pointer;font-family:'DM Sans',sans-serif}
input,textarea,select{font-family:'DM Sans',sans-serif}

.app{display:flex;height:100vh;overflow:hidden;position:relative;z-index:1}

/* ── SIDEBAR ── */
.sidebar{width:250px;background:var(--sb-bg);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-right:1px solid var(--gb);display:flex;flex-direction:column;height:100vh;flex-shrink:0;overflow:hidden;transition:background 0.4s}
.sb-brand{padding:20px 18px 14px;border-bottom:1px solid var(--gb)}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--txt)}
.brand-tag{font-size:11px;color:var(--txt3);text-transform:uppercase;letter-spacing:.1em;margin-top:2px}
.sb-user{padding:14px 18px;display:flex;align-items:center;gap:11px;border-bottom:1px solid var(--gb)}
.sb-user-info .uname{font-weight:500;color:var(--txt);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-user-info .urole{font-size:11px;color:var(--txt3);margin-top:1px}
.sb-nav{flex:1;padding:10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.nav-btn{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--sb-link);font-size:13.5px;cursor:pointer;transition:0.2s;border:none;background:none;width:100%;text-align:left;position:relative}
.nav-btn:hover{background:var(--glass2);color:var(--txt)}
.nav-btn.active{background:var(--glass2);color:var(--txt);font-weight:600;border:1px solid var(--gb)}
.nav-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0}
.nav-badge{margin-left:auto;background:rgba(13,161,230,0.8);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
.sb-footer{padding:10px;border-top:1px solid var(--gb)}
.logout-btn{display:flex;align-items:center;gap:9px;width:100%;padding:9px 12px;border-radius:10px;color:var(--txt3);font-size:13.5px;cursor:pointer;transition:0.2s;background:none;border:none}
.logout-btn:hover{color:#ff6b6b;background:rgba(231,76,60,0.12)}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;min-width:0}
.topbar{background:var(--topbar);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid var(--gb);padding:13px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;transition:background 0.4s}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600}
.topbar-sub{font-size:12px;color:var(--txt2);margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:10px}
.theme-toggle{background:var(--glass2);border:1px solid var(--gb);border-radius:99px;padding:6px 12px;font-size:13px;cursor:pointer;color:var(--txt);transition:0.2s}
.theme-toggle:hover{background:var(--card-hover)}
.content{flex:1;overflow-y:auto;padding:22px 24px}

/* ── PANELS ── */
.section{display:none;animation:fu 0.22s ease}
.section.active{display:block}
@keyframes fu{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.sh{margin-bottom:16px;display:flex;align-items:center;gap:10px}
.sh h2{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;white-space:nowrap;color:var(--txt)}
.sh-line{flex:1;height:1px;background:var(--gb)}

.empty{text-align:center;padding:44px 24px;color:var(--txt2)}
.empty .icon{font-size:40px;margin-bottom:8px}
.empty p{font-size:14px}

/* ── LEADERS GRID ── */
.leaders-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.leader-card{background:var(--card);backdrop-filter:blur(12px);border:1px solid var(--gb);border-radius:16px;padding:20px 16px;text-align:center;transition:0.25s;position:relative;overflow:hidden}
.leader-card:hover{background:var(--card-hover);transform:translateY(-3px);box-shadow:var(--shadow)}
.leader-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--bg1),var(--bg2));transform:scaleX(0);transition:0.25s;transform-origin:left}
.leader-card:hover::before{transform:scaleX(1)}
.lcard-avatar{margin:0 auto 12px;cursor:pointer}
.lcard-avatar img,.lcard-avatar div{border:3px solid var(--gb)!important}
.lcard-name{font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;margin-bottom:4px;color:var(--txt)}
.lcard-bio{font-size:12px;color:var(--txt2);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.badge-v{display:inline-flex;align-items:center;gap:4px;background:var(--badge-v-bg);color:var(--badge-v-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600;margin-bottom:12px}
.badge-p{display:inline-flex;align-items:center;gap:4px;background:var(--badge-p-bg);color:var(--badge-p-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600;margin-bottom:12px}
.btn-msg{display:block;width:100%;padding:8px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;transition:0.2s}
.btn-msg:hover{opacity:0.88}

/* ── MESSAGE MODAL ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.50);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--glass2);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid var(--gb);border-radius:20px;padding:26px;width:460px;max-width:94vw;box-shadow:var(--shadow);animation:mi 0.28s cubic-bezier(.34,1.56,.64,1)}
@keyframes mi{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
.modal-hdr{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.modal-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--txt)}
.modal-sub{font-size:12px;color:var(--txt2)}
.modal-close{margin-left:auto;background:none;border:none;font-size:18px;color:var(--txt2);cursor:pointer}
.chat-thread{max-height:240px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding:4px 0;margin-bottom:14px}
.bubble{max-width:75%;padding:8px 12px;border-radius:12px;font-size:13.5px;line-height:1.5}
.bubble.sent{background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.bubble.recv{background:var(--glass2);color:var(--txt);align-self:flex-start;border-bottom-left-radius:4px;border:1px solid var(--gb)}
.btime{font-size:10px;margin-top:3px;opacity:.6}
.chat-input-row{display:flex;gap:8px}
.chat-input{flex:1;padding:9px 12px;background:var(--input-bg);border:1px solid var(--gb);border-radius:9px;font-size:14px;color:var(--txt);outline:none;transition:0.2s}
.chat-input:focus{background:var(--input-focus);border-color:rgba(255,255,255,0.55)}
.chat-input::placeholder{color:var(--txt3)}
.btn-send{padding:9px 18px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-weight:600;font-size:13.5px;transition:0.2s}
.btn-send:hover{opacity:.88}

/* ── CARD LIST (messages, consultations etc) ── */
.clist{display:flex;flex-direction:column;gap:11px}
.icard{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:12px;padding:14px 16px;display:flex;align-items:flex-start;gap:12px;transition:0.2s}
.icard:hover{background:var(--card-hover);border-color:rgba(255,255,255,0.4)}
.ibody{flex:1;min-width:0}
.ititle{font-weight:600;font-size:14.5px;color:var(--txt);margin-bottom:2px}
.isub{font-size:13px;color:var(--txt2)}
.imeta{font-size:11.5px;color:var(--txt3);margin-top:3px}

/* ── MEETINGS ── */
.meeting-card{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:16px;margin-bottom:12px;transition:0.2s}
.meeting-card:hover{background:var(--card-hover)}
.date-blk{background:var(--glass2);border:1px solid var(--gb);border-radius:10px;padding:8px 12px;text-align:center;min-width:56px;flex-shrink:0}
.date-m{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--bg1);font-weight:700}
.date-d{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600;line-height:1;color:var(--txt)}
.meeting-info{flex:1}
.meeting-leader{font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;color:var(--txt);margin-bottom:3px}
.meeting-meta{font-size:12.5px;color:var(--txt2);margin-bottom:8px}
.meeting-meta span{margin-right:12px}
.btn-join{display:inline-flex;align-items:center;gap:5px;padding:7px 16px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:0.2s}
.btn-join:hover{opacity:.88}

/* ── NOTIFICATIONS ── */
.notif-item{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-left:3px solid var(--bg1);border-radius:9px;padding:12px 16px;display:flex;align-items:center;gap:11px;margin-bottom:10px;transition:0.2s}
.notif-txt{font-size:14px;font-weight:500;color:var(--txt)}
.notif-sub{font-size:12px;color:var(--txt2);margin-top:1px}

/* ── PROFILE ── */
.profile-hero{background:var(--glass2);backdrop-filter:blur(20px);border:1px solid var(--gb);border-radius:16px 16px 0 0;padding:26px;display:flex;align-items:center;gap:18px;max-width:600px;position:relative;overflow:hidden}
.profile-hero::before{content:'';position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:radial-gradient(circle,rgba(13,204,218,0.25),transparent 70%);pointer-events:none}
.profile-fullname{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--txt);position:relative}
.profile-email{font-size:13px;color:var(--txt2);margin-top:3px;position:relative}
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
.btn-edit{padding:9px 18px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;transition:0.2s}
.btn-edit:hover{opacity:.88}
.btn-cancel{padding:9px 18px;background:var(--glass2);color:var(--txt);border:1px solid var(--gb);border-radius:9px;font-size:14px;font-weight:500;transition:0.2s}
.btn-cancel:hover{background:var(--card-hover)}
.upload-area{border:2px dashed var(--gb);border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;transition:0.2s}
.upload-area:hover{border-color:rgba(255,255,255,0.5);background:var(--glass2)}
.upload-area label{cursor:pointer;display:block}
.upload-area p{font-size:13px;color:var(--txt2);margin-top:6px}
.upload-area input[type="file"]{display:none}

/* ── Rooms (messages panel) ── */
.rooms-wrap{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);border-radius:14px;overflow:hidden;display:grid;grid-template-columns:260px 1fr;height:520px}
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

/* Consultation status badges */
.badge-v{display:inline-flex;align-items:center;gap:4px;background:var(--badge-v-bg);color:var(--badge-v-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}
.badge-p{display:inline-flex;align-items:center;gap:4px;background:var(--badge-p-bg);color:var(--badge-p-col);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}
.badge-r{display:inline-flex;align-items:center;gap:4px;background:rgba(231,76,60,0.15);color:#ff8a80;border:1px solid rgba(231,76,60,0.3);border-radius:99px;padding:3px 9px;font-size:11px;font-weight:600}

/* Consultation request modal */
.consult-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.50);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center}
.consult-modal-overlay.open{display:flex}
.consult-modal{background:var(--glass2);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid var(--gb);border-radius:20px;padding:26px;width:440px;max-width:94vw;box-shadow:var(--shadow);animation:mi 0.28s cubic-bezier(.34,1.56,.64,1)}
.cmod-hdr{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.cmod-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--txt)}
.cmod-sub{font-size:12px;color:var(--txt2)}
.cmod-close{margin-left:auto;background:none;border:none;font-size:18px;color:var(--txt2);cursor:pointer}
.cmod-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.cmod-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--txt3)}
.cmod-input{padding:9px 12px;border:1px solid var(--gb);border-radius:9px;font-size:14px;color:var(--txt);background:var(--input-bg);outline:none;transition:0.2s;width:100%}
.cmod-input:focus{background:var(--input-focus);border-color:rgba(255,255,255,0.55)}
.cmod-input::placeholder{color:var(--txt3)}
textarea.cmod-input{resize:vertical;min-height:80px}
.btn-submit-req{width:100%;padding:11px;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;margin-top:4px;cursor:pointer;transition:0.2s}
.btn-submit-req:hover{opacity:.88}
.review-form h3{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--txt);margin-bottom:12px}
.star-row{display:flex;gap:6px;margin-bottom:12px}
.star-radio{display:none}
.star-label{font-size:26px;cursor:pointer;opacity:0.4;transition:0.15s}
.star-radio:checked ~ .star-label,
.star-label:hover, .star-label:hover ~ .star-label{opacity:1}

/* ── ALERT ── */
.alert-success{background:rgba(46,204,113,0.15);border:1px solid rgba(46,204,113,0.35);color:var(--badge-v-col);border-radius:9px;padding:10px 14px;font-size:13.5px;margin-bottom:14px}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:var(--gb);border-radius:99px}

/* ── TOAST ── */
#toast{position:fixed;bottom:20px;right:20px;background:var(--glass2);backdrop-filter:blur(16px);border:1px solid var(--gb);color:var(--txt);padding:10px 16px;border-radius:9px;font-size:14px;font-weight:500;box-shadow:var(--shadow);transform:translateY(60px);opacity:0;transition:0.25s;z-index:9999}
#toast.show{transform:translateY(0);opacity:1}

@media(max-width:700px){.leaders-grid{grid-template-columns:repeat(2,1fr)}.pgrid{grid-template-columns:1fr}.meeting-card{flex-wrap:wrap}}
</style>
</head>
<body>

<div id="toast"></div>

<!-- MESSAGE MODAL -->
<div class="modal-overlay" id="msgModal">
  <div class="modal">
    <div class="modal-hdr">
      <div id="modalAv"></div>
      <div><div class="modal-title" id="modalName"></div><div class="modal-sub" id="modalSub"></div></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="chat-thread" id="chatThread"><div style="text-align:center;color:var(--txt2);font-size:13px;padding:16px">Start the conversation ✨</div></div>
    <div class="chat-input-row">
      <input type="text" class="chat-input" id="chatInput" placeholder="Type your message…" autocomplete="off">
      <button class="btn-send" onclick="sendMsg()">Send</button>
    </div>
  </div>
</div>

<!-- CONSULTATION REQUEST MODAL -->
<div class="consult-modal-overlay" id="consultModal">
  <div class="consult-modal">
    <div class="cmod-hdr">
      <div id="cmodAv"></div>
      <div>
        <div class="cmod-title">Request Consultation</div>
        <div class="cmod-sub" id="cmodLeaderName"></div>
      </div>
      <button class="cmod-close" onclick="closeConsultModal()">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="submit_consultation">
      <input type="hidden" name="leader_id" id="cmodLeaderId" value="">
      <div class="cmod-group">
        <label class="cmod-label">Topic / Subject</label>
        <input type="text" class="cmod-input" name="topic" placeholder="e.g. Marriage guidance, faith questions…" required>
      </div>
      <div class="cmod-group">
        <label class="cmod-label">Your Message to the Leader</label>
        <textarea class="cmod-input" name="consult_message" placeholder="Briefly describe what you need guidance on…" required></textarea>
      </div>
      <button type="submit" class="btn-submit-req">🙏 Send Request</button>
    </form>
  </div>
</div>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="brand-name">FaithConnect</div>
    <div class="brand-tag">Member Portal</div>
  </div>
  <div class="sb-user">
    <?php echo avatar_html($pfp,$ur['full_name'],42); ?>
    <div class="sb-user-info" style="min-width:0">
      <div class="uname"><?php echo $name; ?></div>
      <div class="urole">Member</div>
    </div>
  </div>
  <nav class="sb-nav">
    <button class="nav-btn <?php echo $sec==='home'?'active':''; ?>" onclick="show('home')"><span class="nav-icon">🏠</span> Browse Leaders</button>
    <button class="nav-btn <?php echo $sec==='messages'?'active':''; ?>" onclick="showMessages()" id="msgNavBtn"><span class="nav-icon">💬</span> Messages <span class="nav-badge" id="msgBadge" <?php echo $unread_count===0?'style="display:none"':''; ?>><?php echo $unread_count; ?></span></button>
    <button class="nav-btn <?php echo $sec==='consultations'?'active':''; ?>" onclick="show('consultations')"><span class="nav-icon">🤝</span> Consultations</button>
    <button class="nav-btn <?php echo $sec==='notifications'?'active':''; ?>" onclick="show('notifications')"><span class="nav-icon">🔔</span> Notifications <?php if($total_notif_badge>0): ?><span class="nav-badge"><?php echo $total_notif_badge; ?></span><?php endif; ?></button>
    <button class="nav-btn <?php echo $sec==='meetings'?'active':''; ?>" onclick="show('meetings')"><span class="nav-icon">📅</span> Meetings</button>
    <button class="nav-btn <?php echo $sec==='profile'?'active':''; ?>" onclick="show('profile')"><span class="nav-icon">👤</span> Profile</button>
  </nav>
  <div class="sb-footer">
    <a href="/index.php" class="logout-btn"><span>🚪</span> Sign Out</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
<header class="topbar">
  <div><div class="topbar-title" id="ttl">Browse Leaders</div><div class="topbar-sub" id="tsub">Connect with verified faith leaders</div></div>
  <div class="topbar-right">
    <button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">☀️ Light</button>
    <?php echo avatar_html($pfp,$ur['full_name'],34,'border:2px solid var(--gb)'); ?>
  </div>
</header>

<div class="content">

<!-- HOME: BROWSE LEADERS -->
<div id="sec-home" class="section <?php echo $sec==='home'?'active':''; ?>">
  <?php if($saved): ?><div class="alert-success">✅ Profile updated!</div><?php endif; ?>
  <div class="sh"><h2>Faith Leaders</h2><div class="sh-line"></div></div>
  <?php if(empty($leaders)): ?>
  <div class="empty"><div class="icon">🙏</div><p>No faith leaders registered yet.</p></div>
  <?php else: ?>
  <div class="leaders-grid">
    <?php foreach($leaders as $l):
      $lid     = (int)$l['user_id'];
      $lname   = htmlspecialchars($l['full_name']);
      $lpfp    = !empty($l['profile_image']) ? '/'.$l['profile_image'] : '';
      $lbio    = htmlspecialchars(isset($l['bio']) ? $l['bio'] : '');
      $lver    = ($l['verification_status']==='verified');
      $lrating = (float)$l['avg_rating'];
      $lrevs   = (int)$l['review_count'];
      $filled  = round($lrating);
      $star_html = str_repeat('★',$filled).str_repeat('☆',5-$filled);
    ?>
    <div class="leader-card">
      <!-- Avatar — click to view public profile -->
      <div class="lcard-avatar" onclick="window.location='/dashboards/leader_profile.php?id=<?php echo $lid; ?>'">
        <?php echo avatar_html($lpfp,$l['full_name'],70,'margin:0 auto'); ?>
      </div>
      <div class="lcard-name"><?php echo $lname; ?></div>
      <?php if($lbio): ?><div class="lcard-bio"><?php echo $lbio; ?></div><?php endif; ?>
      <?php if($lver): ?><div class="badge-v">✓ Verified</div><?php else: ?><div class="badge-p">⏳ Pending</div><?php endif; ?>
      <!-- Rating -->
      <div style="margin:6px 0 10px;font-size:13px">
        <span style="color:#ffd700;letter-spacing:1px"><?php echo $star_html; ?></span>
        <?php if($lrating>0): ?>
        <span style="color:var(--txt2);font-size:12px;margin-left:4px"><?php echo $lrating; ?> (<?php echo $lrevs; ?>)</span>
        <?php else: ?>
        <span style="color:var(--txt3);font-size:12px;margin-left:4px">No reviews yet</span>
        <?php endif; ?>
      </div>
      <!-- Action buttons -->
      <div style="display:flex;flex-direction:column;gap:7px">
        <button class="btn-msg" onclick="openModal(<?php echo $lid; ?>,'<?php echo addslashes($lname); ?>','<?php echo addslashes($lpfp); ?>',<?php echo $lver?'true':'false'; ?>)">✉ Message</button>
        <button class="btn-msg" style="background:var(--glass2);border:1px solid var(--gb);color:var(--txt)" onclick="openConsultModal(<?php echo $lid; ?>,'<?php echo addslashes($lname); ?>','<?php echo addslashes($lpfp); ?>')">🙏 Request Consultation</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- MESSAGES — rooms style split panel -->
<div id="sec-messages" class="section <?php echo $sec==='messages'?'active':''; ?>">
  <div class="sh"><h2>Messages</h2><div class="sh-line"></div></div>
  <?php if(empty($msg_rooms)): ?>
    <div class="empty"><div class="icon">💬</div><p>No messages yet. Message a faith leader to start a conversation.</p></div>
  <?php else: ?>
  <div class="rooms-wrap">
    <!-- Left: conversation list -->
    <div class="rlist">
      <div class="rlist-hd">Conversations</div>
      <?php foreach($msg_rooms as $r):
        $rpfp    = !empty($r['profile_image']) ? '/'.$r['profile_image'] : '';
        $preview = mb_substr(isset($r['last_message'])?$r['last_message']:'', 0, 38);
      ?>
      <div class="ritem" id="msgroom-<?php echo (int)$r['user_id']; ?>"
           onclick="openRoom(<?php echo (int)$r['user_id']; ?>,'<?php echo addslashes(htmlspecialchars($r['full_name'])); ?>','<?php echo addslashes($rpfp); ?>')">
        <?php echo avatar_html($rpfp, $r['full_name'], 36); ?>
        <div style="min-width:0">
          <div class="rname"><?php echo htmlspecialchars($r['full_name']); ?></div>
          <div class="rprev"><?php echo htmlspecialchars($preview); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Right: chat window -->
    <div class="cwin" id="roomWin">
      <div class="no-room" id="noRoomSel">
        <div class="icon">💬</div>
        <p>Select a conversation</p>
      </div>
      <div class="active-chat" id="activeMsgChat">
        <div class="chdr">
          <div id="roomChatAv"></div>
          <div><div class="chdr-name" id="roomChatName"></div></div>
        </div>
        <div class="cmsgs" id="roomChatMsgs">
          <div class="cempty">Loading…</div>
        </div>
        <div class="cinput-row">
          <input type="text" class="cinput" id="roomChatInput" placeholder="Type your message…" autocomplete="off">
          <button class="btn-send" onclick="sendRoomMsg()">Send ↑</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- CONSULTATIONS — user's own requests to leaders -->
<div id="sec-consultations" class="section <?php echo $sec==='consultations'?'active':''; ?>">
  <div class="sh"><h2>My Consultation Requests</h2><div class="sh-line"></div></div>
  <?php if(isset($_GET['sent'])): ?><div class="alert-success">✅ Consultation request sent!</div><?php endif; ?>
  <?php if(empty($consultations)): ?>
    <div class="empty"><div class="icon">🤝</div><p>No requests yet. Click "Request Consultation" on a leader's card to begin.</p></div>
  <?php else: ?>
  <div class="clist">
    <?php foreach($consultations as $c):
      $lpfp = !empty($c['leader_pfp']) ? '/'.$c['leader_pfp'] : '';
      $lver = ($c['leader_ver']==='verified');
      $date = !empty($c['created_at']) ? date('M j, Y', strtotime($c['created_at'])) : '';
      if($c['status']==='approved')      { $badge='<span class="badge-v">✅ Approved</span>'; }
      elseif($c['status']==='rejected')  { $badge='<span class="badge-r">❌ Rejected</span>'; }
      else                               { $badge='<span class="badge-p">⏳ Pending</span>'; }
    ?>
    <div class="icard">
      <div onclick="window.location='/dashboards/leader_profile.php?id=<?php echo (int)$c['leader_id']; ?>'" style="cursor:pointer;flex-shrink:0">
        <?php echo avatar_html($lpfp, $c['leader_name'], 50, 'border:2px solid var(--gb)'); ?>
      </div>
      <div class="ibody">
        <div class="ititle"><?php echo htmlspecialchars($c['leader_name']); ?>
          <?php if($lver): ?><span style="font-size:11px;color:var(--badge-v-col);margin-left:5px">✓</span><?php endif; ?>
        </div>
        <div style="font-weight:600;font-size:13.5px;color:var(--txt);margin:3px 0"><?php echo htmlspecialchars($c['topic']); ?></div>
        <div class="isub"><?php echo htmlspecialchars(mb_substr($c['message'],0,80)); ?><?php echo strlen($c['message'])>80?'…':''; ?></div>
        <?php if($date): ?><div class="imeta">Submitted: <?php echo $date; ?></div><?php endif; ?>
      </div>
      <div style="flex-shrink:0"><?php echo $badge; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- NOTIFICATIONS -->
<div id="sec-notifications" class="section <?php echo $sec==='notifications'?'active':''; ?>">
  <div class="sh"><h2>Notifications</h2><div class="sh-line"></div></div>
  <?php if(empty($db_notifs) && empty($notifs)): ?>
    <div class="empty"><div class="icon">🔔</div><p>No notifications yet.</p></div>
  <?php else: ?>
    <?php if(!empty($db_notifs)): ?>
    <div class="sh" style="margin-bottom:10px"><h2 style="font-size:14px;color:var(--txt3)">System Messages</h2><div class="sh-line"></div></div>
    <?php foreach($db_notifs as $n): ?>
    <div class="notif-item" style="margin-bottom:10px">
      <div style="font-size:22px;flex-shrink:0">📢</div>
      <div style="flex:1">
        <div class="notif-txt"><?php echo htmlspecialchars($n['massage']); ?></div>
        <?php if(!empty($n['created_at'])): ?><div class="notif-sub"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if(!empty($notifs)): ?>
    <div class="sh" style="margin-top:16px;margin-bottom:10px"><h2 style="font-size:14px;color:var(--txt3)">Upcoming Meetings</h2><div class="sh-line"></div></div>
    <?php foreach($notifs as $n): ?>
    <div class="notif-item" style="margin-bottom:10px">
      <div style="font-size:22px;flex-shrink:0">📅</div>
      <div style="flex:1">
        <div class="notif-txt">Upcoming meeting with <strong><?php echo htmlspecialchars($n['leader_name']); ?></strong></div>
        <div class="notif-sub"><?php echo date('l, F j Y',strtotime($n['meeting_date'])); ?> at <?php echo date('g:i A',strtotime($n['meeting_time'])); ?></div>
      </div>
      <button onclick="show('meetings')" style="background:var(--glass2);border:1px solid var(--gb);color:var(--txt);padding:6px 12px;border-radius:7px;font-size:12px;cursor:pointer;flex-shrink:0">View →</button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- MEETINGS -->
<div id="sec-meetings" class="section <?php echo $sec==='meetings'?'active':''; ?>">
  <div class="sh"><h2>My Meetings</h2><div class="sh-line"></div></div>
  <?php if(empty($meetings)): ?>
  <div class="empty"><div class="icon">📅</div><p>No meetings scheduled yet.</p></div>
  <?php else: ?>
  <?php foreach($meetings as $m):
    $md   = isset($m['meeting_date']) ? $m['meeting_date'] : '';
    $isup = $md && strtotime($md)>=strtotime('today');
  ?>
  <div class="meeting-card">
    <div class="date-blk">
      <div class="date-m"><?php echo $md?date('M',strtotime($md)):'---'; ?></div>
      <div class="date-d"><?php echo $md?date('j',strtotime($md)):'--'; ?></div>
    </div>
    <div class="meeting-info">
      <div class="meeting-leader">With <?php echo htmlspecialchars($m['leader_name']); ?></div>
      <div class="meeting-meta">
        <span>🗓 <?php echo $md?date('l, F j Y',strtotime($md)):'TBD'; ?></span>
        <span>🕐 <?php echo !empty($m['meeting_time'])?date('g:i A',strtotime($m['meeting_time'])):'TBD'; ?></span>
        <?php if($isup): ?><span style="color:var(--badge-v-col);font-weight:600">● Upcoming</span><?php else: ?><span style="color:var(--txt3)">Completed</span><?php endif; ?>
      </div>
      <?php if(!empty($m['meeting_link']) && $isup): ?>
      <a class="btn-join" href="<?php echo htmlspecialchars($m['meeting_link']); ?>" target="_blank" rel="noopener">🎥 Join Meeting</a>
      <?php elseif(!empty($m['meeting_link'])): ?>
      <span style="font-size:12px;color:var(--txt3)">Meeting has ended</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- PROFILE -->
<div id="sec-profile" class="section <?php echo $sec==='profile'?'active':''; ?>">
  <?php if($saved): ?><div class="alert-success">✅ Profile updated successfully!</div><?php endif; ?>
  <div class="profile-hero">
    <?php echo avatar_html($pfp,$ur['full_name'],80,'border:3px solid var(--gb)!important;flex-shrink:0'); ?>
    <div style="position:relative">
      <div class="profile-fullname"><?php echo $name; ?></div>
      <div class="profile-email">✉ <?php echo $email; ?></div>
    </div>
  </div>

  <!-- Upload picture -->
  <div class="pbody">
    <form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'].'?section=profile'; ?>">
      <div class="upload-area">
        <label for="pfp-upload">
          <?php echo avatar_html($pfp,$ur['full_name'],52,'margin:0 auto'); ?>
          <p>Click to upload a profile picture (JPG/PNG/WEBP · max 2MB)</p>
        </label>
        <input type="file" id="pfp-upload" name="profile_image" accept="image/*" onchange="this.form.submit()">
      </div>
    </form>

    <!-- Edit profile details -->
    <div id="pView">
      <div class="pgrid">
        <div class="fwrap"><div class="flabel">Age</div><div class="fval"><?php echo ($pr['age']!=='')?htmlspecialchars($pr['age']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Gender</div><div class="fval"><?php echo ($pr['gender']!=='')?htmlspecialchars($pr['gender']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Religion</div><div class="fval"><?php echo ($pr['religion']!=='')?htmlspecialchars($pr['religion']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Country</div><div class="fval"><?php echo ($pr['country']!=='')?htmlspecialchars($pr['country']):'—'; ?></div></div>
        <div class="fwrap"><div class="flabel">Language</div><div class="fval"><?php echo ($pr['language']!=='')?htmlspecialchars($pr['language']):'—'; ?></div></div>
        <div class="fwrap full"><div class="flabel">Bio</div><div class="fval" style="white-space:pre-wrap"><?php echo ($pr['bio']!=='')?htmlspecialchars($pr['bio']):'—'; ?></div></div>
      </div>
      <div class="pactions"><button class="btn-edit" onclick="editOn()">✏️ Edit Profile</button></div>
    </div>

    <div id="pEdit" style="display:none">
      <form method="POST">
        <input type="hidden" name="action" value="update_profile">
        <div class="pgrid">
          <div class="fwrap"><label class="flabel">Age</label><input class="finput" type="number" name="age" min="1" max="120" value="<?php echo htmlspecialchars($pr['age']); ?>"></div>
          <div class="fwrap"><label class="flabel">Gender</label>
            <select class="finput" name="gender">
              <option value="">— Select —</option>
              <?php foreach(array('Male','Female','Non-binary','Prefer not to say') as $g): ?>
              <option value="<?php echo $g; ?>" <?php echo ($pr['gender']===$g)?'selected':''; ?>><?php echo $g; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fwrap"><label class="flabel">Religion</label><input class="finput" type="text" name="religion" value="<?php echo htmlspecialchars($pr['religion']); ?>"></div>
          <div class="fwrap"><label class="flabel">Country</label><input class="finput" type="text" name="country" value="<?php echo htmlspecialchars($pr['country']); ?>"></div>
          <div class="fwrap"><label class="flabel">Language</label><input class="finput" type="text" name="language" value="<?php echo htmlspecialchars($pr['language']); ?>"></div>
          <div class="fwrap full"><label class="flabel">Bio</label><textarea class="finput" name="bio"><?php echo htmlspecialchars($pr['bio']); ?></textarea></div>
        </div>
        <div class="pactions">
          <button type="submit" class="btn-edit">💾 Save</button>
          <button type="button" class="btn-cancel" onclick="editOff()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

</div><!-- /content -->
</div><!-- /main -->
</div><!-- /app -->

<script>
var panelMeta={
  home:{t:'Browse Leaders',s:'Connect with verified faith leaders'},
  messages:{t:'Messages',s:'Your conversations with faith leaders'},
  consultations:{t:'Consultations',s:'Requests you have sent to faith leaders'},
  notifications:{t:'Notifications',s:'System alerts and upcoming meetings'},
  meetings:{t:'My Meetings',s:'All your scheduled sessions'},
  profile:{t:'My Profile',s:'View and edit your details'}
};

function show(id){
  document.querySelectorAll('.section').forEach(function(s){s.classList.remove('active');});
  document.querySelectorAll('.nav-btn').forEach(function(n){n.classList.remove('active');});
  document.getElementById('sec-'+id).classList.add('active');
  document.querySelectorAll('.nav-btn').forEach(function(n){if(n.getAttribute('onclick')==="show('"+id+"')") n.classList.add('active');});
  var m=panelMeta[id]||{};
  document.getElementById('ttl').textContent=m.t||'';
  document.getElementById('tsub').textContent=m.s||'';
  if(history.replaceState) history.replaceState(null,'','?section='+id);
}

/* Messages nav — clears unread badge and marks as seen on server */
function showMessages(){
  /* Hide badge immediately */
  var badge = document.getElementById('msgBadge');
  if(badge){ badge.style.display='none'; badge.textContent='0'; }
  /* Tell server to update last-seen timestamp */
  fetch(window.location.pathname+'?mark_seen=1');
  show('messages');
}
/* Mark-as-seen handler (no output needed, just updates session) */

/* Dark / Light toggle */
function toggleTheme(){
  var html=document.documentElement;
  var isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  document.getElementById('themeBtn').textContent=isDark?'🌙 Dark':'☀️ Light';
  localStorage.setItem('fc_theme',isDark?'light':'dark');
}
(function(){
  var s=localStorage.getItem('fc_theme')||'dark';
  document.documentElement.setAttribute('data-theme',s);
  document.getElementById('themeBtn').textContent=s==='dark'?'☀️ Light':'🌙 Dark';
})();

/* ── MESSAGE MODAL (from leader cards) ── */
var aId=null, aName='';
function openModal(id,name,pfp,verified){
  aId=id; aName=name;
  var av=document.getElementById('modalAv');
  if(pfp) av.innerHTML='<img src="'+pfp+'" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--gb)">';
  else av.innerHTML=mkini(name,40);
  document.getElementById('modalName').textContent=name;
  document.getElementById('modalSub').textContent=verified?'✓ Verified Leader':'⏳ Pending Verification';
  document.getElementById('msgModal').classList.add('open');
  document.getElementById('chatInput').focus();
  loadModalThread(id);
}
function closeModal(){
  document.getElementById('msgModal').classList.remove('open');
  document.getElementById('chatThread').innerHTML='<div style="text-align:center;color:var(--txt2);font-size:13px;padding:16px">Start the conversation ✨</div>';
  aId=null;
}
document.getElementById('msgModal').addEventListener('click',function(e){if(e.target===this) closeModal();});
document.getElementById('chatInput').addEventListener('keydown',function(e){if(e.key==='Enter') sendMsg();});

function loadModalThread(id){
  document.getElementById('chatThread').innerHTML='<div style="text-align:center;color:var(--txt2);font-size:13px;padding:16px">Loading…</div>';
  fetch('?ajax=thread&with='+id).then(function(r){return r.json();}).then(function(d){
    var b=document.getElementById('chatThread');
    if(!d.messages||!d.messages.length){b.innerHTML='<div style="text-align:center;color:var(--txt2);font-size:13px;padding:16px">Start the conversation ✨</div>';return;}
    b.innerHTML=d.messages.map(function(m){return '<div><div class="bubble '+(m.sent?'sent':'recv')+'">'+esc(m.message)+'<div class="btime">'+m.time+'</div></div></div>';}).join('');
    b.scrollTop=b.scrollHeight;
  }).catch(function(){});
}
function sendMsg(){
  var input=document.getElementById('chatInput'), msg=input.value.trim();
  if(!msg||!aId) return; input.value='';
  fetch(window.location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:'action=send_message&receiver_id='+aId+'&message='+encodeURIComponent(msg)})
  .then(function(r){return r.json();}).then(function(d){if(d.ok){loadModalThread(aId);toast('Sent to '+aName);}}).catch(function(){});
}

/* ── ROOMS (messages panel) ── */
var rUid=null, rUname='';
function openRoom(uid,uname,upfp){
  rUid=uid; rUname=uname;
  document.querySelectorAll('.ritem').forEach(function(r){r.classList.remove('active');});
  var el=document.getElementById('msgroom-'+uid); if(el) el.classList.add('active');
  document.getElementById('noRoomSel').style.display='none';
  var ac=document.getElementById('activeMsgChat'); ac.style.display='flex';
  var av=document.getElementById('roomChatAv');
  if(upfp) av.innerHTML='<img src="'+upfp+'" style="width:32px;height:32px;border-radius:50%;object-fit:cover">';
  else av.innerHTML=mkini(uname,32);
  document.getElementById('roomChatName').textContent=uname;
  loadRoomThread(uid);
  document.getElementById('roomChatInput').focus();
}
function loadRoomThread(uid){
  document.getElementById('roomChatMsgs').innerHTML='<div class="cempty">Loading…</div>';
  fetch('?ajax=thread&with='+uid).then(function(r){return r.json();}).then(function(d){
    var b=document.getElementById('roomChatMsgs');
    if(!d.messages||!d.messages.length){b.innerHTML='<div class="cempty">No messages yet — send the first one!</div>';return;}
    b.innerHTML=d.messages.map(function(m){return '<div><div class="bubble '+(m.sent?'sent':'recv')+'">'+esc(m.message)+'<div class="btime">'+m.time+'</div></div></div>';}).join('');
    b.scrollTop=b.scrollHeight;
  }).catch(function(){b.innerHTML='<div class="cempty">Could not load.</div>';});
}
function sendRoomMsg(){
  var input=document.getElementById('roomChatInput'), msg=input.value.trim();
  if(!msg||!rUid) return; input.value='';
  fetch(window.location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:'action=send_message&receiver_id='+rUid+'&message='+encodeURIComponent(msg)})
  .then(function(r){return r.json();}).then(function(d){if(d.ok){loadRoomThread(rUid);}}).catch(function(){});
}
document.getElementById('roomChatInput').addEventListener('keydown',function(e){if(e.key==='Enter') sendRoomMsg();});

/* ── CONSULTATION MODAL ── */
var cLeaderId=null;
function openConsultModal(id,name,pfp){
  cLeaderId=id;
  document.getElementById('cmodLeaderId').value=id;
  document.getElementById('cmodLeaderName').textContent='with '+name;
  var av=document.getElementById('cmodAv');
  if(pfp) av.innerHTML='<img src="'+pfp+'" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--gb)">';
  else av.innerHTML=mkini(name,40);
  document.getElementById('consultModal').classList.add('open');
}
function closeConsultModal(){
  document.getElementById('consultModal').classList.remove('open');
  cLeaderId=null;
}
document.getElementById('consultModal').addEventListener('click',function(e){if(e.target===this) closeConsultModal();});

function editOn(){document.getElementById('pView').style.display='none';document.getElementById('pEdit').style.display='block';}
function editOff(){document.getElementById('pEdit').style.display='none';document.getElementById('pView').style.display='block';}

function toast(msg){
  var t=document.getElementById('toast'); t.textContent=msg; t.classList.add('show');
  setTimeout(function(){t.classList.remove('show');},2600);
}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function mkini(name,size){
  var p=name.split(' '),ini='';
  for(var i=0;i<p.length;i++){if(ini.length>=2) break; if(p[i]) ini+=p[i][0].toUpperCase();}
  var cols=['#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a'],h=0;
  for(var i=0;i<name.length;i++) h+=name.charCodeAt(i);
  return '<div style="width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+cols[h%cols.length]+';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'+Math.floor(size*0.36)+'px;color:#fff;flex-shrink:0">'+esc(ini)+'</div>';
}
<?php if($saved): ?>toast('Profile saved ✅');<?php endif; ?>
<?php if(isset($_GET['sent'])): ?>toast('Consultation request sent! ✅');<?php endif; ?>
</script>
</body>
</html>