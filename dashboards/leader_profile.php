<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){ header("Location: /auth/login.php"); exit(); }
$viewer_id   = (int)$_SESSION['user_id'];
$viewer_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$leader_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* If no ID given, redirect to correct dashboard */
if($leader_id === 0){
    if($viewer_role === 'leader') header("Location: /dashboards/leader.php");
    elseif($viewer_role === 'admin') header("Location: /dashboards/admin.php");
    else header("Location: /dashboards/user.php");
    exit();
}

/* ── Submit review ── */
if(isset($_POST['action']) && $_POST['action']==='submit_review'){
    $rating = min(5,max(1,(int)$_POST['rating']));
    $text   = mysqli_real_escape_string($conn, trim($_POST['review_text']));
    if($text !== ''){
        $ex = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM reviews WHERE user_id='$viewer_id' AND leader_id='$leader_id'"
        ));
        if($ex) mysqli_query($conn,
            "UPDATE reviews SET review_text='$text',rating='$rating',created_at=NOW()
             WHERE user_id='$viewer_id' AND leader_id='$leader_id'"
        );
        else    mysqli_query($conn,
            "INSERT INTO reviews(user_id,leader_id,review_text,rating,created_at)
             VALUES('$viewer_id','$leader_id','$text','$rating',NOW())"
        );
    }
    header("Location: ".$_SERVER['PHP_SELF']."?id=$leader_id&reviewed=1"); exit();
}

/* ── Load leader (works whether viewer is user, leader, or admin) ── */
$l_res = mysqli_query($conn,
    "SELECT u.*, up.age, up.gender, up.religion, up.country, up.language, up.bio
     FROM users u
     LEFT JOIN user_profile up ON up.user_id = u.user_id
     WHERE u.user_id='$leader_id' AND u.role='leader'"
);
if(!$l_res || mysqli_num_rows($l_res) === 0){
    /* Leader not found — go back */
    if($viewer_role === 'leader') header("Location: /dashboards/leader.php");
    elseif($viewer_role === 'admin') header("Location: /dashboards/admin.php");
    else header("Location: /dashboards/user.php");
    exit();
}
$l    = mysqli_fetch_assoc($l_res);
$lpfp = !empty($l['profile_image']) ? '/'.$l['profile_image'] : '';
$lname= htmlspecialchars($l['full_name']);
$lver = ($l['verification_status']==='verified');

/* Reviews */
$rev_q = mysqli_query($conn,
    "SELECT r.*, u.full_name AS reviewer, u.profile_image AS reviewer_pfp
     FROM reviews r
     JOIN users u ON u.user_id = r.user_id
     WHERE r.leader_id='$leader_id'
     ORDER BY r.created_at DESC"
);
$reviews = array();
if($rev_q) while($r = mysqli_fetch_assoc($rev_q)) $reviews[] = $r;
$avg = 0;
if(!empty($reviews)){
    $sum=0; foreach($reviews as $rv) $sum+=(int)$rv['rating'];
    $avg = round($sum/count($reviews),1);
}

/* Viewer's existing review */
$my_rev = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM reviews WHERE user_id='$viewer_id' AND leader_id='$leader_id'"
));

/* Consultation count */
$cc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM consultations WHERE leader_id='$leader_id'"
));

/* Back URL depends on viewer role */
if($viewer_role === 'leader')     $back_url = '/dashboards/leader.php';
elseif($viewer_role === 'admin')  $back_url = '/dashboards/admin.php';
else                               $back_url = '/dashboards/user.php';

/* Avatar helper */
function av($pfp, $name, $size, $extra=''){
    if(!empty($pfp))
        return '<img src="'.htmlspecialchars($pfp).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;flex-shrink:0;'.$extra.'" alt="">';
    $parts = explode(' ', trim($name)); $ini='';
    foreach($parts as $p){ if(strlen($ini)>=2) break; if(!empty($p)) $ini.=strtoupper($p[0]); }
    $cols = array('#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a');
    $bg = $cols[abs(crc32($name))%count($cols)]; $fs = floor($size*0.36);
    return '<div style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:'.$bg.';display:flex;align-items:center;justify-content:center;font-weight:700;font-size:'.$fs.'px;color:#fff;flex-shrink:0;'.$extra.'">'.htmlspecialchars($ini).'</div>';
}

$reviewed = isset($_GET['reviewed']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $lname; ?> — Faith Connect</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
[data-theme="dark"]{
  --bg1:#0da1e6;--bg2:#0dccda;
  --glass:rgba(255,255,255,0.10);--glass2:rgba(255,255,255,0.16);--gb:rgba(255,255,255,0.22);
  --txt:#fff;--txt2:rgba(255,255,255,0.72);--txt3:rgba(255,255,255,0.45);
  --card:rgba(255,255,255,0.10);--card-h:rgba(255,255,255,0.18);
  --input:rgba(255,255,255,0.10);--topbar:rgba(255,255,255,0.08);
  --shadow:0 8px 32px rgba(0,0,0,0.22);
  --bvbg:rgba(46,204,113,0.2);--bvc:#7fffb2;
  --bpbg:rgba(255,213,79,0.2);--bpc:#ffe566;
}
[data-theme="light"]{
  --bg1:#b2ebf2;--bg2:#e0f7fa;
  --glass:rgba(255,255,255,0.55);--glass2:rgba(255,255,255,0.80);--gb:rgba(13,161,230,0.30);
  --txt:#0a2535;--txt2:rgba(10,37,53,0.70);--txt3:rgba(10,37,53,0.40);
  --card:rgba(255,255,255,0.65);--card-h:rgba(255,255,255,0.90);
  --input:rgba(255,255,255,0.70);--topbar:rgba(255,255,255,0.75);
  --shadow:0 8px 32px rgba(13,161,230,0.12);
  --bvbg:rgba(46,204,113,0.15);--bvc:#1a7a45;
  --bpbg:rgba(243,156,18,0.15);--bpc:#9a6200;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100%;font-family:'DM Sans',sans-serif;color:var(--txt)}
body{background:linear-gradient(135deg,var(--bg1),var(--bg2));background-attachment:fixed;transition:background 0.4s}
body::before{content:'';position:fixed;inset:0;background:url('/assets/images/landing.png') center/cover no-repeat;opacity:0.10;z-index:0;pointer-events:none}
a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
button{cursor:pointer;font-family:'DM Sans',sans-serif}
input,textarea{font-family:'DM Sans',sans-serif}

.wrap{position:relative;z-index:1;max-width:740px;margin:0 auto;padding:0 20px 60px}

/* Topbar */
.topbar{background:var(--topbar);backdrop-filter:blur(16px);border-bottom:1px solid var(--gb);
  padding:13px 20px;display:flex;align-items:center;justify-content:space-between;
  margin:0 -20px 32px;position:sticky;top:0;z-index:100}
.back-btn{display:flex;align-items:center;gap:8px;color:var(--txt2);font-size:14px;
  font-weight:500;background:none;border:none;cursor:pointer;transition:0.2s}
.back-btn:hover{color:var(--txt)}
.theme-btn{background:var(--glass2);border:1px solid var(--gb);border-radius:99px;
  padding:6px 12px;font-size:13px;cursor:pointer;color:var(--txt)}

/* Hero */
.hero{background:var(--glass2);backdrop-filter:blur(20px);border:1px solid var(--gb);
  border-radius:20px;padding:28px;display:flex;gap:22px;align-items:flex-start;
  margin-bottom:20px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;
  background:radial-gradient(circle,rgba(13,204,218,0.2),transparent 70%);pointer-events:none}
.hero-info{flex:1;position:relative}
.hero-name{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:600;margin-bottom:6px;color:var(--txt)}
.badge-v{display:inline-flex;align-items:center;gap:4px;background:var(--bvbg);color:var(--bvc);
  border-radius:99px;padding:4px 11px;font-size:12px;font-weight:600;margin-bottom:12px}
.badge-p{display:inline-flex;align-items:center;gap:4px;background:var(--bpbg);color:var(--bpc);
  border-radius:99px;padding:4px 11px;font-size:12px;font-weight:600;margin-bottom:12px}
.hero-stats{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px}
.hstat .num{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--txt)}
.hstat .lbl{font-size:12px;color:var(--txt3)}
.hero-bio{font-size:14px;color:var(--txt2);line-height:1.65;margin-bottom:10px}
.hero-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.meta-chip{background:var(--card);border:1px solid var(--gb);border-radius:99px;padding:4px 12px;font-size:12.5px;color:var(--txt2)}
.btn-msg{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;
  background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#fff;
  border:none;border-radius:10px;font-size:14px;font-weight:600;margin-top:14px;
  cursor:pointer;transition:0.2s;text-decoration:none}
.btn-msg:hover{opacity:.88}

/* Section title */
.sec-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;
  color:var(--txt);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--gb)}

/* Review form */
.rform-card{background:var(--glass2);backdrop-filter:blur(12px);border:1px solid var(--gb);
  border-radius:16px;padding:22px;margin-bottom:22px}
.rform-title{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;
  color:var(--txt);margin-bottom:14px}

/* Star rating — reverse order trick */
.star-row{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px;margin-bottom:14px}
.star-radio{display:none}
.star-lbl{font-size:28px;cursor:pointer;color:rgba(255,215,0,0.3);transition:0.15s}
.star-radio:checked ~ .star-lbl,
.star-lbl:hover,
.star-lbl:hover ~ .star-lbl{color:#ffd700}

.finput{width:100%;padding:10px 13px;border:1px solid var(--gb);border-radius:10px;
  font-size:14px;color:var(--txt);background:var(--input);outline:none;
  transition:0.2s;line-height:1.5}
.finput:focus{background:rgba(255,255,255,0.22);border-color:rgba(255,255,255,0.55)}
.finput::placeholder{color:var(--txt3)}
textarea.finput{resize:vertical;min-height:80px;margin-bottom:12px}
.btn-submit{padding:10px 22px;background:linear-gradient(135deg,var(--bg1),var(--bg2));
  color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;
  cursor:pointer;transition:0.2s}
.btn-submit:hover{opacity:.88}

/* Review cards */
.review-card{background:var(--card);backdrop-filter:blur(10px);border:1px solid var(--gb);
  border-radius:12px;padding:16px;margin-bottom:12px;transition:0.2s;
  display:flex;flex-direction:column;gap:8px}
.review-card:hover{background:var(--card-h)}
.rev-stars{color:#ffd700;font-size:16px;letter-spacing:2px}
.rev-text{font-size:14px;color:var(--txt);font-style:italic;line-height:1.6}
.rev-author{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--txt2);
  padding-top:8px;border-top:1px solid rgba(255,255,255,0.1)}

.alert{border-radius:10px;padding:11px 15px;font-size:13.5px;margin-bottom:18px}
.alert.success{background:rgba(46,204,113,0.15);border:1px solid rgba(46,204,113,0.35);color:var(--bvc)}

::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-thumb{background:var(--gb);border-radius:99px}
</style>
</head>
<body>
<div class="wrap">

  <!-- Topbar -->
  <div class="topbar">
    <a href="<?php echo $back_url; ?>" class="back-btn">← Back to Dashboard</a>
    <button class="theme-btn" onclick="toggleTheme()" id="themeBtn">☀️ Light</button>
  </div>

  <?php if($reviewed): ?><div class="alert success">✅ Review submitted — thank you!</div><?php endif; ?>

  <!-- HERO -->
  <div class="hero">
    <?php echo av($lpfp, $l['full_name'], 96, 'border:3px solid var(--gb)!important;flex-shrink:0'); ?>
    <div class="hero-info">
      <div class="hero-name"><?php echo $lname; ?></div>
      <?php if($lver): ?>
        <div class="badge-v">✓ Verified Leader</div>
      <?php else: ?>
        <div class="badge-p">⏳ Pending Verification</div>
      <?php endif; ?>
      <div class="hero-stats">
        <div class="hstat">
          <div class="num"><?php echo (int)$cc['c']; ?></div>
          <div class="lbl">Consultations</div>
        </div>
        <div class="hstat">
          <div class="num"><?php echo count($reviews); ?></div>
          <div class="lbl">Reviews</div>
        </div>
        <?php if($avg > 0): ?>
        <div class="hstat">
          <div class="num" style="color:#ffd700">⭐ <?php echo $avg; ?></div>
          <div class="lbl">Avg Rating</div>
        </div>
        <?php endif; ?>
      </div>
      <?php if(!empty($l['bio'])): ?>
        <div class="hero-bio"><?php echo htmlspecialchars($l['bio']); ?></div>
      <?php endif; ?>
      <div class="hero-meta">
        <?php if(!empty($l['religion'])): ?><span class="meta-chip">🙏 <?php echo htmlspecialchars($l['religion']); ?></span><?php endif; ?>
        <?php if(!empty($l['country'])): ?><span class="meta-chip">🌍 <?php echo htmlspecialchars($l['country']); ?></span><?php endif; ?>
        <?php if(!empty($l['language'])): ?><span class="meta-chip">💬 <?php echo htmlspecialchars($l['language']); ?></span><?php endif; ?>
      </div>
      <!-- Only show message button for users (not the leader themselves) -->
      <?php if($viewer_id !== $leader_id): ?>
      <a href="<?php echo $back_url; ?>?section=messages" class="btn-msg">✉ Send Message</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- REVIEW FORM — show for everyone except the leader themselves -->
  <?php if($viewer_id !== $leader_id): ?>
  <div class="rform-card">
    <div class="rform-title"><?php echo $my_rev ? '✏️ Update Your Review' : '⭐ Leave a Review'; ?></div>
    <form method="POST">
      <input type="hidden" name="action" value="submit_review">
      <input type="hidden" name="leader_id" value="<?php echo $leader_id; ?>">
      <!-- Star selector -->
      <div class="star-row">
        <?php for($i=5;$i>=1;$i--): ?>
        <input type="radio" class="star-radio" name="rating" id="s<?php echo $i; ?>"
               value="<?php echo $i; ?>"
               <?php echo ($my_rev && (int)$my_rev['rating']===$i) ? 'checked' : ''; ?> required>
        <label class="star-lbl" for="s<?php echo $i; ?>">★</label>
        <?php endfor; ?>
      </div>
      <textarea class="finput" name="review_text"
                placeholder="Share your experience with this faith leader…"
                required><?php echo $my_rev ? htmlspecialchars($my_rev['review_text']) : ''; ?></textarea>
      <button type="submit" class="btn-submit">Submit Review</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- EXISTING REVIEWS -->
  <?php if(!empty($reviews)): ?>
  <div class="sec-title">Community Reviews (<?php echo count($reviews); ?>)</div>
  <?php foreach($reviews as $rv):
    $rp    = !empty($rv['reviewer_pfp']) ? '/'.$rv['reviewer_pfp'] : '';
    $s     = (int)$rv['rating'];
    $sts   = str_repeat('★',$s).str_repeat('☆',5-$s);
    $is_mine = ((int)$rv['user_id'] === $viewer_id);
  ?>
  <div class="review-card" <?php if($is_mine) echo 'style="border-color:rgba(13,204,218,0.5)"'; ?>>
    <div class="rev-stars"><?php echo $sts; ?>
      <?php if($is_mine): ?><span style="font-size:11px;color:var(--txt3);margin-left:6px;font-style:normal">Your review</span><?php endif; ?>
    </div>
    <div class="rev-text">"<?php echo htmlspecialchars($rv['review_text']); ?>"</div>
    <div class="rev-author">
      <?php echo av($rp, $rv['reviewer'], 28, 'border:1px solid var(--gb)'); ?>
      <span><?php echo htmlspecialchars($rv['reviewer']); ?></span>
      <?php if(!empty($rv['created_at'])): ?>
      <span style="color:var(--txt3);font-size:12px;margin-left:auto"><?php echo date('M j, Y', strtotime($rv['created_at'])); ?></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="text-align:center;padding:32px 24px;color:var(--txt2)">
    <div style="font-size:36px;margin-bottom:8px">⭐</div>
    <p style="font-size:14px">No reviews yet. Be the first!</p>
  </div>
  <?php endif; ?>

</div><!-- /wrap -->

<script>
function toggleTheme(){
  var html=document.documentElement, isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  document.getElementById('themeBtn').textContent=isDark?'🌙 Dark':'☀️ Light';
  localStorage.setItem('fc_theme',isDark?'light':'dark');
}
(function(){
  var s=localStorage.getItem('fc_theme')||'dark';
  document.documentElement.setAttribute('data-theme',s);
  document.getElementById('themeBtn').textContent=s==='dark'?'☀️ Light':'🌙 Dark';
})();
</script>
</body>
</html>