<?php
require_once __DIR__ . '/includes/public_layout.php';

pub_header(
    'Privacy Policy',
    'How Faith Connect collects, uses, and protects your personal information, including account details, consultations, and cookies.',
    '/privacy.php'
);
?>
        <h1 class="sec-title">Privacy Policy</h1>
        <p class="sec-sub">Last updated: <?php echo e(LAST_UPDATED); ?></p>

        <div class="pg-prose">
            <p><?php echo e(SITE_NAME); ?> ("we", "us") runs this website and platform. This policy explains what
            personal information we collect, why, and the choices you have.</p>

            <h2>Information we collect</h2>
            <ul>
                <li><strong>Account details:</strong> your full name, email address, role (member or faith leader), and password.</li>
                <li><strong>Profile information:</strong> an optional profile photo and, for faith leaders, details used for verification.</li>
                <li><strong>Activity on the platform:</strong> consultation requests, messages with your chosen leader, meeting details, notifications, and reviews you write.</li>
                <li><strong>Technical data:</strong> a session cookie that keeps you signed in, and standard server logs such as IP address and browser type.</li>
            </ul>

            <h2>How we use it</h2>
            <ul>
                <li>To create and secure your account and sign you in.</li>
                <li>To connect members with verified faith leaders and deliver meeting notifications.</li>
                <li>To show reviews, which display the reviewer's name and photo.</li>
                <li>To verify leaders, prevent abuse, and keep the platform safe.</li>
                <li>To reply to messages you send us.</li>
            </ul>
            <p>We do not sell your personal information and we do not use it for advertising.</p>

            <h2>Passwords and security</h2>
            <p>Passwords are stored only as one-way hashes, so we cannot read them. Access to administrator
            tools is restricted. No system is perfectly secure, so please use a strong, unique password.
            We will never ask for your password by email or message.</p>

            <h2>Sensitive information</h2>
            <p>Because this is a faith platform, your use of it may reveal your religion. We treat this as
            sensitive. Your consultations and messages are visible only to you and the leader you choose, and to
            administrators when needed to investigate a report or keep the platform safe.</p>

            <h2>Who we share data with</h2>
            <ul>
                <li><strong>Faith leaders</strong> you choose to contact see your name and the details you send them.</li>
                <li><strong>Service providers</strong> that host and run the site (our web hosting and database provider).</li>
                <li><strong>Third-party content and tools</strong> loaded in your browser: Google Fonts, Font Awesome, and embedded YouTube videos. These services may receive your IP address when a page loads, under their own privacy policies.</li>
                <li><strong>Meeting platforms</strong> such as Zoom or Google Meet, when you follow a meeting link. Their own terms apply.</li>
                <li><strong>Authorities</strong>, only where the law requires it.</li>
            </ul>

            <h2>Cookies</h2>
            <p>We use a session cookie that is needed to keep you signed in. We do not use advertising or
            tracking cookies. Embedded YouTube videos may set their own cookies.</p>

            <h2>How long we keep data</h2>
            <p>We keep your information while your account is active. If you ask us to delete your account, we
            remove your personal data, except what we must keep to meet legal obligations or resolve disputes.</p>

            <h2>Your rights</h2>
            <p>You can ask to access, correct, or delete your personal data, to object to how we use it, or to
            withdraw your consent. Use the <a href="/contact.php">Contact page</a> with the subject
            "Privacy request". We may need to confirm it is really you before acting.</p>

            <h2>Children</h2>
            <p><?php echo e(SITE_NAME); ?> is intended for adults. If you believe a child has created an account,
            contact us and we will remove it.</p>

            <h2>Changes to this policy</h2>
            <p>If we change this policy, we will update the date at the top of this page. For significant changes
            we will also notify registered users.</p>

            <h2>Contact</h2>
            <p>Questions about this policy? Visit our <a href="/contact.php">Contact page</a>.</p>
        </div>
<?php pub_footer(); ?>
