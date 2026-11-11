<?php
require_once __DIR__ . '/includes/public_layout.php';

pub_header(
    'Contact Us',
    'Contact Faith Connect for account help, faith leader verification, reporting a problem, or privacy requests.',
    '/contact.php'
);
?>
        <h1 class="sec-title">Contact us</h1>
        <p class="sec-sub">
            <?php if (CONTACT_EMAIL !== ''): ?>
                Email us at <a href="mailto:<?php echo $mail; ?>" style="text-decoration:underline"><?php echo $mail; ?></a>.
            <?php else: ?>
                Our support email address has not been configured yet. Please check back later.
            <?php endif; ?>
            Tell us which of the topics below your message is about so we can route it quickly.
        </p>

        <div class="pg-panel">
            <h3>Account help</h3>
            <p>Can't sign in, need to change your details, or want your account closed? Email us from the
            address you registered with.</p>
        </div>
        <div class="pg-panel">
            <h3>Faith leaders</h3>
            <p>Register as a leader first, then email us with your name, tradition, and community or
            organisation so our team can complete verification.</p>
        </div>
        <div class="pg-panel">
            <h3>Report a problem or concern</h3>
            <p>If you see abusive behaviour, an impersonator, or content that should not be here, send the
            profile name and a short description. We review every report.</p>
        </div>
        <div class="pg-panel">
            <h3>Privacy requests</h3>
            <p>To access, correct, or delete your personal data, email us with the subject
            "Privacy request". See the <a href="/privacy.php">Privacy Policy</a> for what we hold and why.</p>
        </div>

        <div class="pg-prose">
            <p>We aim to reply within 3 business days. We will never ask for your password by email or message.</p>
        </div>

        <div class="pg-cta">
            <?php if (CONTACT_EMAIL !== ''): ?>
                <a href="mailto:<?php echo $mail; ?>" class="btn-solid">Send an email</a>
            <?php endif; ?>
            <a href="/about.php" class="btn-glass">About Faith Connect</a>
        </div>
<?php pub_footer(); ?>
