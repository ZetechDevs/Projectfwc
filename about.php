<?php
require_once __DIR__ . '/includes/public_layout.php';

pub_header(
    'About Us',
    'Faith Connect is a multi-faith platform where people book consultations and virtual meetings with verified faith leaders, and explore spiritual music and podcasts.',
    '/about.php'
);
?>
        <h1 class="sec-title">About Faith Connect</h1>
        <p class="sec-sub">
            We are a multi-faith platform built on the belief that everyone deserves access to
            spiritual guidance, regardless of religion, location, or background.
        </p>

        <div class="pg-panel">
            <h3>Our mission</h3>
            <p>To create a safe, inclusive space where people can connect with verified faith leaders for
            guidance, consultation, and community support across all religious traditions.</p>
        </div>
        <div class="pg-panel">
            <h3>Verified leaders</h3>
            <p>Every faith leader is reviewed and approved by our admin team before they can offer
            consultations. Verification status is shown on their profile.</p>
        </div>
        <div class="pg-panel">
            <h3>Private by design</h3>
            <p>Consultations and messages are private between you and your chosen leader. We never sell your
            data. Read the details in our <a href="/privacy.php">Privacy Policy</a>.</p>
        </div>
        <div class="pg-panel">
            <h3>Open to every tradition</h3>
            <p>Christianity, Islam, Hinduism, Buddhism and more. Leaders and seekers from all traditions
            are welcome.</p>
        </div>

        <div class="pg-prose">
            <h2>What you can do here</h2>
            <ul>
                <li><strong>Consult a faith leader.</strong> Book one-on-one guidance on life, family, faith, and purpose.</li>
                <li><strong>Join virtual meetings.</strong> Attend sessions your leader schedules on Zoom or Google Meet.</li>
                <li><strong>Listen and watch.</strong> Browse spiritual music and faith podcasts curated for each tradition.</li>
                <li><strong>Get notified.</strong> Receive the date, time, and link as soon as a leader schedules a meeting.</li>
            </ul>

            <h2>How it works</h2>
            <ol class="pg-steps">
                <li><span>Create a free account as a member, or as a faith leader applying for verification.</span></li>
                <li><span>Browse approved leaders and choose one from your tradition.</span></li>
                <li><span>Request a consultation or join the meetings your leader posts.</span></li>
            </ol>

            <h2>Get in touch</h2>
            <p>Questions, feedback, or a leader who would like to join? Visit our
            <a href="/contact.php">Contact page</a>.</p>
        </div>

        <div class="pg-cta">
            <a href="/auth/register.php" class="btn-solid">Create an account</a>
            <a href="/contact.php" class="btn-glass">Contact us</a>
        </div>
<?php pub_footer(); ?>
