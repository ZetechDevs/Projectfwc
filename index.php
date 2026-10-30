<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faith Connect — Spiritual Guidance Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Font Awesome (social icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Site stylesheet -->
    <link rel="stylesheet" href="/projectfwc/assets/css/style1.css">
</head>
<body>

<?php require_once "config/db.php"; ?>

<!-- Mobile sidebar overlay -->
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="page-wrap">

    <!-- ════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <div>
            <!-- Logo -->
            <div class="sidebar-logo">
                <img src="/projectfwc/assets/images/logo2.png" alt="Faith Connect">
                <span>Faith Connect</span>
            </div>

            <!-- Navigation -->
            <nav class="side-nav">
                <button class="nav-link active" onclick="showSection('home', this)">
                    <span class="ni">🏠</span> Home
                </button>
                <button class="nav-link" onclick="showSection('about', this)">
                    <span class="ni">ℹ️</span> About Us
                </button>
                <button class="nav-link" onclick="showSection('services', this)">
                    <span class="ni">⚙️</span> Services
                </button>
                <button class="nav-link" onclick="showSection('music', this)">
                    <span class="ni">🎵</span> Music
                </button>
                <button class="nav-link" onclick="showSection('podcast', this)">
                    <span class="ni">🎙️</span> Podcast
                </button>
            </nav>
        </div>

        <!-- Bottom: auth buttons + social icons -->
        <div class="sidebar-bottom">
            <a href="/auth/login.php"    class="btn-glass" style="display:block;text-align:center;margin-bottom:10px;padding:10px">Sign In</a>
            <a href="/auth/register.php" class="btn-solid"  style="display:block;text-align:center;padding:10px">Get Started</a>

            <div class="socials">
                <a href="#" class="social-icon fb" title="Facebook">  <i class="fab fa-facebook-f"></i>  </a>
                <a href="#" class="social-icon ig" title="Instagram"> <i class="fab fa-instagram"></i>   </a>
                <a href="#" class="social-icon yt" title="YouTube">   <i class="fab fa-youtube"></i>     </a>
                <a href="#" class="social-icon tw" title="X/Twitter"> <i class="fab fa-x-twitter"></i>  </a>
                <a href="#" class="social-icon tk" title="TikTok">    <i class="fab fa-tiktok"></i>      </a>
                <a href="#" class="social-icon wa" title="WhatsApp">  <i class="fab fa-whatsapp"></i>    </a>
            </div>
        </div>

    </aside>
    <!-- /SIDEBAR -->


    <!-- ════════════════════════════════════
         MAIN
    ════════════════════════════════════ -->
    <div class="main">

        <!-- TOP HEADER -->
        <header class="top-header">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <div class="header-logo">
                <img src="/assets/images/logo.png" alt="Faith Connect">
                <span>Faith Connect</span>
            </div>
            <div class="auth-buttons">
                <a href="/projectfwc/auth/login.php"    class="btn-glass">Login</a>
                <a href="/projectfwc/auth/register.php" class="btn-solid">Register</a>
            </div>
        </header>
        <!-- /TOP HEADER -->

        <div class="content-area">

            <!-- ════════════════════════════
                 HOME
            ════════════════════════════ -->
            <div id="home" class="section active">
                <div class="hero-wrap">
                    <div class="hero-inner">

                        <div class="hero-tag">Connecting Faith Across Communities</div>

                        <h1 class="hero-h1">
                            Find Your<br>
                            <em>Spiritual Guide</em><br>
                            Today
                        </h1>

                        <p class="hero-p">
                            Faith Connect bridges the gap between people seeking spiritual guidance
                            and verified faith leaders across all religions. Book consultations,
                            attend virtual meetings, and grow in your faith journey.
                        </p>

                        <div class="hero-btns">
                            <a href="/auth/register.php" class="btn-hero-primary">
                                Get Started — It's Free
                            </a>
                            <button class="btn-hero-outline"
                                    onclick="showSection('services', document.querySelectorAll('.nav-link')[2])">
                                Explore Services
                            </button>
                        </div>

                        <!-- Live stats from DB -->
                        <?php
                        $uc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'"));
                        $lc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='leader'"));
                        $cc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM consultations"));
                        $user_count    = (int)$uc['c'];
                        $leader_count  = (int)$lc['c'];
                        $consult_count = (int)$cc['c'];
                        ?>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <div class="num"><?php echo ($user_count > 0) ? $user_count . '+' : '100+'; ?></div>
                                <div class="lbl">Active Members</div>
                            </div>
                            <div class="stat-item">
                                <div class="num"><?php echo ($leader_count > 0) ? $leader_count . '+' : '20+'; ?></div>
                                <div class="lbl">Faith Leaders</div>
                            </div>
                            <div class="stat-item">
                                <div class="num"><?php echo ($consult_count > 0) ? $consult_count . '+' : '50+'; ?></div>
                                <div class="lbl">Consultations</div>
                            </div>
                            <div class="stat-item">
                                <div class="num">5+</div>
                                <div class="lbl">Religions Covered</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /HOME -->


            <!-- ════════════════════════════
                 ABOUT US
            ════════════════════════════ -->
            <div id="about" class="section">
                <div class="sec-wrap">

                    <div class="sec-title">About Faith Connect</div>
                    <p class="sec-sub">
                        We are a multi-faith platform built on the belief that everyone deserves
                        access to spiritual guidance — regardless of religion, location, or background.
                    </p>

                    <!-- Info cards -->
                    <div class="about-grid">
                        <div class="about-card">
                            <div class="about-icon">🌍</div>
                            <div class="about-title">Our Mission</div>
                            <div class="about-text">
                                To create a safe, inclusive space where individuals can connect with
                                verified faith leaders for spiritual guidance, consultation, and community
                                support across all religious traditions.
                            </div>
                        </div>
                        <div class="about-card">
                            <div class="about-icon">✅</div>
                            <div class="about-title">Verified Leaders</div>
                            <div class="about-text">
                                Every faith leader on our platform goes through a thorough verification
                                process. Our admin team manually reviews and approves each leader before
                                they can offer consultations.
                            </div>
                        </div>
                        <div class="about-card">
                            <div class="about-icon">🔒</div>
                            <div class="about-title">Safe &amp; Private</div>
                            <div class="about-text">
                                Your spiritual journey is personal. All consultations and messages are
                                private between you and your chosen leader. We never share your data.
                            </div>
                        </div>
                        <div class="about-card">
                            <div class="about-icon">🤝</div>
                            <div class="about-title">Multi-Faith</div>
                            <div class="about-text">
                                From Christianity to Islam, Hinduism, Buddhism and beyond — Faith Connect
                                welcomes leaders and seekers from all traditions under one platform.
                            </div>
                        </div>
                    </div>

                    <!-- Reviews from DB -->
                    <div class="sec-title" style="font-size:28px;margin-top:44px;margin-bottom:8px">
                        What Our Users Say
                    </div>
                    <p class="sec-sub">Real feedback from our community</p>

                    <?php
                    /*
                     * reviews: id, user_id, leader_id, review_text, rating, created_at
                     * Fetch: leader name + pfp, reviewer name + pfp, rating, text
                     */
                    $rev_res = mysqli_query($conn,
                        "SELECT r.review_text, r.rating,
                                u.full_name  AS reviewer,
                                u.profile_image AS reviewer_pfp,
                                l.full_name  AS leader_name,
                                l.profile_image AS leader_pfp,
                                l.verification_status AS leader_ver
                         FROM reviews r
                         JOIN users u ON u.user_id = r.user_id
                         JOIN users l ON l.user_id = r.leader_id
                         ORDER BY r.created_at DESC LIMIT 9"
                    );
                    $reviews = array();
                    if ($rev_res) {
                        while ($rv = mysqli_fetch_assoc($rev_res)) {
                            $reviews[] = $rv;
                        }
                    }

                    /* Helper: initials avatar HTML */
                    function review_avatar($pfp, $name, $size, $css_class) {
                        if (!empty($pfp)) {
                            return '<img src="/' . htmlspecialchars($pfp) . '" class="' . $css_class . '" alt="">';
                        }
                        $parts = explode(' ', trim($name));
                        $ini = '';
                        foreach ($parts as $p) {
                            if (strlen($ini) >= 2) break;
                            if (!empty($p)) $ini .= strtoupper($p[0]);
                        }
                        $colors = array('#0da1e6','#0dccda','#40916c','#9a7a42','#7209b7','#e05a4a');
                        $bg = $colors[abs(crc32($name)) % count($colors)];
                        $ini_class = ($css_class === 'review-leader-avatar') ? 'review-leader-initials' : 'review-author-initials';
                        return '<div class="' . $ini_class . '" style="background:' . $bg . ';width:' . $size . 'px;height:' . $size . 'px">'
                             . htmlspecialchars($ini) . '</div>';
                    }
                    ?>

                    <div class="reviews-grid">

                    <?php if (empty($reviews)):
                        /* Placeholder cards — shown until DB has real reviews */
                        $placeholders = array(
                            array(
                                'leader'     => 'Pastor John M.',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 5,
                                'text'       => '"Faith Connect changed my life. Pastor John helped me through the most difficult period with grace and wisdom."',
                                'reviewer'   => 'Sarah M.',
                                'rev_pfp'    => '',
                            ),
                            array(
                                'leader'     => 'Imam Hassan A.',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 5,
                                'text'       => '"As a Muslim in a new country, finding a verified Imam on Faith Connect was a blessing I am truly grateful for."',
                                'reviewer'   => 'Aisha K.',
                                'rev_pfp'    => '',
                            ),
                            array(
                                'leader'     => 'Swami Arjun Das',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 5,
                                'text'       => '"My sessions with Swami Arjun have deeply enriched my spiritual practice. I feel more grounded than ever before."',
                                'reviewer'   => 'Ravi P.',
                                'rev_pfp'    => '',
                            ),
                            array(
                                'leader'     => 'Rabbi David L.',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 4,
                                'text'       => '"Verified leaders give me confidence. Rabbi David is knowledgeable, patient, and deeply compassionate."',
                                'reviewer'   => 'David N.',
                                'rev_pfp'    => '',
                            ),
                            array(
                                'leader'     => 'Sister Grace O.',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 5,
                                'text'       => '"The virtual meetings feature is excellent. Sister Grace and I connect weekly and it has strengthened my faith enormously."',
                                'reviewer'   => 'Grace L.',
                                'rev_pfp'    => '',
                            ),
                            array(
                                'leader'     => 'Monk Thich Binh',
                                'leader_pfp' => '',
                                'leader_ver' => 'verified',
                                'stars'      => 5,
                                'text'       => '"A beautiful platform. The multi-faith approach makes everyone feel seen and respected — truly inclusive."',
                                'reviewer'   => 'James O.',
                                'rev_pfp'    => '',
                            ),
                        );
                        foreach ($placeholders as $p):
                            $stars = str_repeat('★', $p['stars']) . str_repeat('☆', 5 - $p['stars']);
                    ?>
                        <div class="review-card">
                            <!-- Leader info at top -->
                            <div class="review-leader">
                                <?php echo review_avatar($p['leader_pfp'], $p['leader'], 46, 'review-leader-avatar'); ?>
                                <div>
                                    <div class="review-leader-name"><?php echo htmlspecialchars($p['leader']); ?></div>
                                    <div class="review-leader-badge">✓ Verified Leader</div>
                                </div>
                            </div>
                            <!-- Star rating -->
                            <div class="review-stars"><?php echo $stars; ?></div>
                            <!-- Review text -->
                            <div class="review-text"><?php echo $p['text']; ?></div>
                            <!-- Reviewer at bottom -->
                            <div class="review-author">
                                <?php echo review_avatar($p['rev_pfp'], $p['reviewer'], 30, 'review-author-avatar'); ?>
                                <span class="review-author-name"><?php echo htmlspecialchars($p['reviewer']); ?></span>
                                <span class="review-author-label">Member</span>
                            </div>
                        </div>
                    <?php endforeach;
                    else:
                        foreach ($reviews as $rv):
                            $s     = (int)$rv['rating'];
                            $stars = str_repeat('★', $s) . str_repeat('☆', 5 - $s);
                            $lver  = ($rv['leader_ver'] === 'verified');
                    ?>
                        <div class="review-card">
                            <!-- Leader info at top -->
                            <div class="review-leader">
                                <?php echo review_avatar($rv['leader_pfp'], $rv['leader_name'], 46, 'review-leader-avatar'); ?>
                                <div>
                                    <div class="review-leader-name"><?php echo htmlspecialchars($rv['leader_name']); ?></div>
                                    <?php if ($lver): ?>
                                    <div class="review-leader-badge">✓ Verified Leader</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Star rating -->
                            <div class="review-stars"><?php echo $stars; ?></div>
                            <!-- Review text -->
                            <div class="review-text">"<?php echo htmlspecialchars($rv['review_text']); ?>"</div>
                            <!-- Reviewer at bottom -->
                            <div class="review-author">
                                <?php echo review_avatar($rv['reviewer_pfp'], $rv['reviewer'], 30, 'review-author-avatar'); ?>
                                <span class="review-author-name"><?php echo htmlspecialchars($rv['reviewer']); ?></span>
                                <span class="review-author-label">Member</span>
                            </div>
                        </div>
                    <?php endforeach;
                    endif; ?>

                    </div><!-- /reviews-grid -->

                </div>
            </div>
            <!-- /ABOUT US -->


            <!-- ════════════════════════════
                 SERVICES
            ════════════════════════════ -->
            <div id="services" class="section">
                <div class="sec-wrap">

                    <div class="sec-title">Our Services</div>
                    <p class="sec-sub">Everything you need for your spiritual journey — all in one place.</p>

                    <div class="services-grid">

                        <div class="service-card">
                            <div class="svc-icon">🙏</div>
                            <div class="svc-title">Faith Leader Consultations</div>
                            <div class="svc-desc">Book one-on-one consultations with verified spiritual leaders from your religion. Get guidance on life, family, faith, and purpose.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">🎥</div>
                            <div class="svc-title">Virtual Meetings</div>
                            <div class="svc-desc">Join online meetings via Zoom or Google Meet scheduled by your faith leader. Attend from the comfort of your home.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">🎵</div>
                            <div class="svc-title">Spiritual Music</div>
                            <div class="svc-desc">Explore curated spiritual music — from meditation soundscapes to worship songs, Islamic nasheeds, and Hindu bhajans.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">🎙️</div>
                            <div class="svc-title">Faith Podcasts</div>
                            <div class="svc-desc">Watch religion-focused podcasts curated from trusted YouTube channels covering Christianity, Islam, Hinduism, Buddhism and more.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">💬</div>
                            <div class="svc-title">Direct Messaging</div>
                            <div class="svc-desc">Send messages directly to faith leaders and receive personal replies. Build a lasting relationship with your spiritual guide.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">⭐</div>
                            <div class="svc-title">Leader Ratings</div>
                            <div class="svc-desc">Read community reviews and ratings to find the right leader for you. Verified ratings from real users ensure transparency.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">🔔</div>
                            <div class="svc-title">Meeting Notifications</div>
                            <div class="svc-desc">Never miss a session. Get instant notifications when a faith leader schedules a meeting — with date, time, and link.</div>
                        </div>
                        <div class="service-card">
                            <div class="svc-icon">🌍</div>
                            <div class="svc-title">Multi-Faith Support</div>
                            <div class="svc-desc">Christian, Muslim, Hindu, Buddhist or any other faith — our platform is built to serve every spiritual background with equal respect.</div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /SERVICES -->


            <!-- ════════════════════════════
                 MUSIC
            ════════════════════════════ -->
            <div id="music" class="section">
                <div class="sec-wrap">

                    <div class="sec-title">Spiritual Music</div>
                    <p class="sec-sub">Explore music that nurtures the soul — browse by category below.</p>

                    <!-- Category tabs -->
                    <div class="music-tabs">
                        <button class="mtab active"  onclick="showMusic('meditation', this)">🧘 Meditation</button>
                        <button class="mtab"          onclick="showMusic('worship',    this)">✝️ Worship</button>
                        <button class="mtab"          onclick="showMusic('nasheed',    this)">☪️ Nasheed</button>
                        <button class="mtab"          onclick="showMusic('bhajan',     this)">🕉️ Bhajan</button>
                        <button class="mtab"          onclick="showMusic('gospel',     this)">🎺 Gospel</button>
                        <button class="mtab"          onclick="showMusic('chants',     this)">📿 Sacred Chants</button>
                    </div>

                    <?php
                    /* Music data per category */
                    $music_data = array(

                        'meditation' => array(
                            array('e'=>'🎵','n'=>'Deep Meditation',  'd'=>'Calm ambient sounds for deep spiritual focus',   'l'=>'https://www.youtube.com/results?search_query=deep+meditation+music'),
                            array('e'=>'🌊','n'=>'Nature Sounds',     'd'=>'Rain, rivers and forest for mindful reflection', 'l'=>'https://www.youtube.com/results?search_query=nature+sounds+meditation'),
                            array('e'=>'🪔','n'=>'Tibetan Bowls',     'd'=>'Healing frequencies and singing bowls',          'l'=>'https://www.youtube.com/results?search_query=tibetan+singing+bowls'),
                            array('e'=>'🌙','n'=>'Sleep Meditation',  'd'=>'Peaceful music for rest and renewal',            'l'=>'https://www.youtube.com/results?search_query=sleep+meditation+music'),
                        ),
                        'worship' => array(
                            array('e'=>'✝️','n'=>'Contemporary Worship', 'd'=>'Modern Christian praise and worship',          'l'=>'https://www.youtube.com/results?search_query=contemporary+christian+worship'),
                            array('e'=>'🎶','n'=>'Hymns & Classics',      'd'=>'Traditional hymns that stand the test of time','l'=>'https://www.youtube.com/results?search_query=christian+hymns+classics'),
                            array('e'=>'🎸','n'=>'Hillsong Worship',      'd'=>'Popular worship anthems from Hillsong',        'l'=>'https://www.youtube.com/results?search_query=hillsong+worship'),
                            array('e'=>'🎹','n'=>'Instrumental Worship',  'd'=>'Piano and orchestral praise music',            'l'=>'https://www.youtube.com/results?search_query=instrumental+worship+music'),
                        ),
                        'nasheed' => array(
                            array('e'=>'☪️','n'=>'Islamic Nasheeds',   'd'=>'Beautiful Arabic and English nasheeds',          'l'=>'https://www.youtube.com/results?search_query=islamic+nasheed'),
                            array('e'=>'📖','n'=>'Quran Recitation',   'd'=>'Peaceful recitations by renowned Qaris',         'l'=>'https://www.youtube.com/results?search_query=quran+recitation+beautiful'),
                            array('e'=>'🕌','n'=>'Adhan Collections',  'd'=>'Beautiful calls to prayer worldwide',            'l'=>'https://www.youtube.com/results?search_query=beautiful+adhan+collection'),
                            array('e'=>'🌙','n'=>'Ramadan Music',      'd'=>'Spiritual songs for the holy month',             'l'=>'https://www.youtube.com/results?search_query=ramadan+nasheed'),
                        ),
                        'bhajan' => array(
                            array('e'=>'🕉️','n'=>'Hindu Bhajans',  'd'=>'Devotional songs for Hindu deities',         'l'=>'https://www.youtube.com/results?search_query=hindu+bhajans'),
                            array('e'=>'🪘','n'=>'Kirtan',          'd'=>'Devotional call-and-response chanting',      'l'=>'https://www.youtube.com/results?search_query=kirtan+music'),
                            array('e'=>'🎵','n'=>'Mantras',         'd'=>'Sacred Sanskrit mantras for meditation',     'l'=>'https://www.youtube.com/results?search_query=sanskrit+mantras+meditation'),
                            array('e'=>'🌸','n'=>'Aarti Songs',     'd'=>'Traditional aarti hymns for daily worship',  'l'=>'https://www.youtube.com/results?search_query=aarti+songs'),
                        ),
                        'gospel' => array(
                            array('e'=>'🎺','n'=>'Gospel Classics',  'd'=>'Timeless African-American gospel music', 'l'=>'https://www.youtube.com/results?search_query=gospel+music+classics'),
                            array('e'=>'🎤','n'=>'African Gospel',   'd'=>'Uplifting gospel music from Africa',     'l'=>'https://www.youtube.com/results?search_query=african+gospel+music'),
                            array('e'=>'🎼','n'=>'Southern Gospel',  'd'=>'Traditional southern gospel choir',      'l'=>'https://www.youtube.com/results?search_query=southern+gospel+choir'),
                            array('e'=>'🙌','n'=>'Gospel Praise',    'd'=>'High-energy praise gospel songs',        'l'=>'https://www.youtube.com/results?search_query=gospel+praise+songs'),
                        ),
                        'chants' => array(
                            array('e'=>'📿','n'=>'Gregorian Chants', 'd'=>'Ancient Catholic monastic chanting',  'l'=>'https://www.youtube.com/results?search_query=gregorian+chants'),
                            array('e'=>'☸️','n'=>'Buddhist Chants',  'd'=>'Peaceful Buddhist mantras and chants','l'=>'https://www.youtube.com/results?search_query=buddhist+chants'),
                            array('e'=>'✡️','n'=>'Jewish Prayers',   'd'=>'Traditional Hebrew prayers and songs', 'l'=>'https://www.youtube.com/results?search_query=jewish+prayer+music'),
                            array('e'=>'🌿','n'=>'Sikh Shabads',     'd'=>'Gurbani kirtan — Sikh tradition',      'l'=>'https://www.youtube.com/results?search_query=sikh+shabad+kirtan'),
                        ),
                    );

                    foreach ($music_data as $cat => $tracks):
                        $is_active = ($cat === 'meditation') ? 'active' : '';
                    ?>
                    <div class="music-panel <?php echo $is_active; ?>" id="music-<?php echo $cat; ?>">
                        <?php foreach ($tracks as $t): ?>
                        <div class="music-card">
                            <div class="music-emoji"><?php echo $t['e']; ?></div>
                            <div class="music-name"><?php echo $t['n']; ?></div>
                            <div class="music-desc"><?php echo $t['d']; ?></div>
                            <a href="<?php echo $t['l']; ?>" target="_blank" class="music-btn">▶ Listen</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
            <!-- /MUSIC -->


            <!-- ════════════════════════════
                 PODCAST
            ════════════════════════════ -->
            <div id="podcast" class="section">
                <div class="sec-wrap">

                    <div class="sec-title">Faith Podcasts</div>
                    <p class="sec-sub">
                        Curated religious and spiritual podcasts from trusted YouTube channels
                        — watch directly here.
                    </p>

                    <?php
                    /*
                     * YouTube Video IDs — all from major channels with millions of views.
                     * To swap a video: go to the YouTube video, copy the ID from the URL
                     * (the part after ?v= or /watch?v=) and paste it as the 'id' value below.
                     */
                    $podcasts = array(
                        array(
                            /* The Bible Project — "What is the Bible?" — 7M+ views, very stable */
                            'id'    => 'ak06MSETeo4',
                            'title' => 'What is the Bible? — The Bible Project',
                            'ch'    => 'The Bible Project',
                            'tag'   => '✝️ Christianity',
                        ),
                        array(
                            /* Nouman Ali Khan — "Who is Allah?" — millions of views, Islamic Guidance */
                            'id'    => 'e7YWPJ_fSoc',
                            'title' => 'Who is Allah? — Understanding Islam',
                            'ch'    => 'Nouman Ali Khan',
                            'tag'   => '☪️ Islam',
                        ),
                        array(
                            /* Sadhguru — "How to Meditate for Beginners" — Official Sadhguru channel */
                            'id'    => 'Ilg3Bg51tKw',
                            'title' => 'How to Meditate — Sadhguru',
                            'ch'    => 'Sadhguru',
                            'tag'   => '🕉️ Hinduism',
                        ),
                        array(
                            /* Alan Watts — "The Art of Meditation" — classic, millions of views */
                            'id'    => 'PJggfBMRNRs',
                            'title' => 'The Art of Meditation — Alan Watts',
                            'ch'    => 'Alan Watts',
                            'tag'   => '☸️ Buddhism',
                        ),
                        array(
                            /* Confirmed working by site owner — Spiritual Motivation */
                            'id'    => 'MIr3RsUWrdo',
                            'title' => 'Daily Spiritual Motivation',
                            'ch'    => 'Motivation2Study',
                            'tag'   => '✨ Spirituality',
                        ),
                        array(
                            /* Rabbi Lord Jonathan Sacks — "The Meaning of Life" — very popular */
                            'id'    => 'R6ISJ_x5yQE',
                            'title' => 'The Meaning of Life — Rabbi Jonathan Sacks',
                            'ch'    => 'Rabbi Sacks',
                            'tag'   => '✡️ Judaism',
                        ),
                    );
                    ?>

                    <div class="podcast-grid">
                        <?php foreach ($podcasts as $p): ?>
                        <div class="podcast-card">
                            <iframe
                                src="https://www.youtube.com/embed/<?php echo $p['id']; ?>"
                                title="<?php echo htmlspecialchars($p['title']); ?>"
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                            <div class="podcast-info">
                                <div class="podcast-title"><?php echo htmlspecialchars($p['title']); ?></div>
                                <div class="podcast-ch">📺 <?php echo htmlspecialchars($p['ch']); ?></div>
                                <span class="podcast-tag"><?php echo $p['tag']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
            <!-- /PODCAST -->


        </div><!-- /content-area -->

        <!-- FOOTER -->
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Faith Connect. All Rights Reserved. Built with ❤️ for all faiths.</p>
        </footer>

    </div><!-- /main -->

</div><!-- /page-wrap -->

<!-- Site JavaScript -->
<script src="/assets/js/script.js"></script>

</body>
</html>