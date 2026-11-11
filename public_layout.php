<?php
/*
 * includes/public_layout.php
 * Shared header/footer for public pages (about, contact, privacy).
 * Uses your existing /assets/css/style1.css (teal glass theme).
 */
require_once __DIR__ . '/site.php';

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function pub_header($title, $description, $path) {
    $canonical = SITE_URL . $path;
    $full_title = $title . ' — ' . SITE_NAME;
    $organization = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => SITE_NAME,
        'url' => SITE_URL . '/',
        'logo' => SITE_URL . '/assets/images/logo.png'
    );
    if (CONTACT_EMAIL !== '') {
        $organization['email'] = CONTACT_EMAIL;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($full_title); ?></title>
    <meta name="description" content="<?php echo e($description); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo e($canonical); ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo e(SITE_NAME); ?>">
    <meta property="og:title" content="<?php echo e($full_title); ?>">
    <meta property="og:description" content="<?php echo e($description); ?>">
    <meta property="og:url" content="<?php echo e($canonical); ?>">
    <meta property="og:image" content="<?php echo e(SITE_URL); ?>/assets/images/logo.png">

    <script type="application/ld+json">
    <?php echo json_encode($organization, JSON_UNESCAPED_SLASHES); ?>
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style1.css">

    <style>
        .pg-header { display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .pg-nav { display:flex; gap:22px; align-items:center; font-size:14px; }
        .pg-nav a { color:var(--white70); padding:6px 2px; border-bottom:2px solid transparent; }
        .pg-nav a:hover, .pg-nav a[aria-current="page"] { color:var(--white); border-bottom-color:var(--white); }
        .pg-shell { position:relative; z-index:1; min-height:100vh; display:flex; flex-direction:column; }
        .pg-main { flex:1; width:100%; max-width:860px; margin:0 auto; padding:48px 24px 64px; }
        .pg-main .sec-title { font-size:44px; line-height:1.1; }
        .pg-prose h2 { font-family:var(--font-display); font-size:26px; font-weight:600; margin:36px 0 10px; }
        .pg-prose p, .pg-prose li { font-size:15px; line-height:1.75; color:var(--white70); }
        .pg-prose p { margin-bottom:12px; }
        .pg-prose ul, .pg-prose ol { margin:0 0 12px 22px; }
        .pg-prose li { margin-bottom:6px; }
        .pg-prose strong { color:var(--white); font-weight:500; }
        .pg-prose a { color:var(--white); text-decoration:underline; text-underline-offset:3px; }
        .pg-panel { background:var(--glass); border:1px solid var(--glass-border); border-radius:var(--rl);
                    padding:22px 24px; margin:0 0 16px; backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); }
        .pg-panel h3 { font-family:var(--font-display); font-size:22px; font-weight:600; margin-bottom:6px; }
        .pg-panel p { font-size:14px; line-height:1.65; color:var(--white70); margin:0; }
        .pg-panel a { text-decoration:underline; text-underline-offset:3px; color:var(--white); }
        .pg-steps { list-style:none; margin:0 0 12px; padding:0; counter-reset:step; }
        .pg-steps li { counter-increment:step; display:flex; gap:14px; align-items:flex-start; margin-bottom:14px; }
        .pg-steps li::before { content:counter(step); flex:0 0 32px; height:32px; border-radius:50%;
                    background:var(--glass); border:1px solid var(--glass-border); color:var(--white);
                    display:flex; align-items:center; justify-content:center; font-weight:500; }
        .pg-cta { margin-top:32px; display:flex; gap:10px; flex-wrap:wrap; }
        .pg-footer-links { display:flex; gap:18px; justify-content:center; flex-wrap:wrap; margin-bottom:8px; }
        .pg-footer-links a { color:var(--white70); text-decoration:underline; text-underline-offset:3px; }
        .pg-footer-links a:hover { color:var(--white); }
        a:focus-visible, .btn-glass:focus-visible, .btn-solid:focus-visible { outline:2px solid var(--white); outline-offset:3px; }
        @media (max-width:700px) {
            .pg-nav { display:none; }
            .pg-main { padding:32px 18px 48px; }
            .pg-main .sec-title { font-size:34px; }
        }
    </style>
</head>
<body>
<div class="pg-shell">
    <header class="top-header pg-header">
        <a class="header-logo" href="/" aria-label="<?php echo e(SITE_NAME); ?> home">
            <img src="/assets/images/logo.png" alt="">
            <span><?php echo e(SITE_NAME); ?></span>
        </a>
        <nav class="pg-nav" aria-label="Main">
            <a href="/about.php"   <?php if ($path === '/about.php')   echo 'aria-current="page"'; ?>>About</a>
            <a href="/contact.php" <?php if ($path === '/contact.php') echo 'aria-current="page"'; ?>>Contact</a>
            <a href="/privacy.php" <?php if ($path === '/privacy.php') echo 'aria-current="page"'; ?>>Privacy</a>
        </nav>
        <div class="auth-buttons">
            <a href="/auth/login.php"    class="btn-glass">Login</a>
            <a href="/auth/register.php" class="btn-solid">Register</a>
        </div>
    </header>
    <main class="pg-main">
<?php
}

function pub_footer() {
?>
    </main>
    <footer class="footer">
        <div class="pg-footer-links">
            <a href="/">Home</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
            <a href="/privacy.php">Privacy Policy</a>
        </div>
        <p>&copy; <?php echo date('Y'); ?> <?php echo e(SITE_NAME); ?>. All Rights Reserved.</p>
    </footer>
</div>
</body>
</html>
<?php
}
