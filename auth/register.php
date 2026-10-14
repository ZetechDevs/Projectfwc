<?php
require_once "../config/db.php";

$errors          = array();
$success_message = '';
$full_name = $email = $role = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name        = trim($_POST['full_name']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role             = $_POST['role'];

    /* Validation */
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    $allowed_roles = array('user', 'leader');
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Please select a valid role.";
    }

    /* Insert if no errors */
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");

        if ($stmt === false) {
            $errors[] = "Database error: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("ssss", $full_name, $email, $password_hash, $role);

            if ($stmt->execute()) {
                $success_message = "Account created successfully! You can now sign in.";
                $full_name = $email = $role = '';
            } else {
                if ($conn->errno === 1062) {
                    $errors[] = "That email address is already registered.";
                } else {
                    $errors[] = "Database error: " . htmlspecialchars($stmt->error);
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Faith Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
    :root {
        --teal1: #0da1e6;
        --teal2: #0dccda;
        --glass:        rgba(255,255,255,0.12);
        --glass-hover:  rgba(255,255,255,0.20);
        --glass-border: rgba(255,255,255,0.28);
        --white:   #ffffff;
        --white70: rgba(255,255,255,0.70);
        --white50: rgba(255,255,255,0.50);
        --font-display: 'Cormorant Garamond', Georgia, serif;
        --font-body:    'DM Sans', Arial, sans-serif;
        --r:  14px;
        --tr: 0.25s cubic-bezier(0.4,0,0.2,1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: var(--font-body);
        min-height: 100vh;
        background: linear-gradient(135deg, var(--teal1), var(--teal2));
        background-attachment: fixed;
        display: flex;
        flex-direction: column;
        color: var(--white);
    }

    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background: url('/assets/images/landing.png') center / cover no-repeat;
        opacity: 0.15;
        z-index: 0;
        pointer-events: none;
    }

    /* Top bar */
    .top-bar {
        position: relative;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 32px;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--glass-border);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--white);
    }

    .brand img  { height: 38px; width: auto; }

    .brand span {
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 600;
    }

    .top-bar-link { font-size: 14px; color: var(--white70); }

    .top-bar-link a {
        color: var(--white);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    /* Page centre */
    .page-center {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        z-index: 1;
    }

    /* Card — wider for register */
    .card {
        width: 100%;
        max-width: 480px;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid var(--glass-border);
        border-radius: 22px;
        padding: 40px 36px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.18);
    }

    .card-icon {
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.18);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin: 0 auto 20px;
    }

    .card-title {
        font-family: var(--font-display);
        font-size: 32px;
        font-weight: 600;
        text-align: center;
        margin-bottom: 6px;
    }

    .card-sub {
        font-size: 14px;
        color: var(--white70);
        text-align: center;
        margin-bottom: 28px;
    }

    /* Alerts */
    .error-box {
        background: rgba(231,76,60,0.18);
        border: 1px solid rgba(231,76,60,0.45);
        border-radius: var(--r);
        padding: 12px 14px;
        font-size: 13.5px;
        color: #ffcdd2;
        margin-bottom: 20px;
    }

    .error-box p { margin-bottom: 4px; }
    .error-box p:last-child { margin-bottom: 0; }
    .error-box p::before { content: '⚠ '; }

    .success-box {
        background: rgba(46,204,113,0.18);
        border: 1px solid rgba(46,204,113,0.45);
        border-radius: var(--r);
        padding: 12px 14px;
        font-size: 13.5px;
        color: #c8f5da;
        margin-bottom: 20px;
    }

    /* Two-column grid for name + email */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    @media (max-width: 480px) {
        .form-row { grid-template-columns: 1fr; }
        .card { padding: 28px 20px; }
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--white70);
    }

    .form-input {
        width: 100%;
        padding: 12px 14px;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: var(--r);
        color: var(--white);
        font-size: 15px;
        font-family: var(--font-body);
        outline: none;
        transition: var(--tr);
    }

    .form-input::placeholder { color: var(--white50); }

    .form-input:focus {
        background: rgba(255,255,255,0.20);
        border-color: rgba(255,255,255,0.55);
        box-shadow: 0 0 0 3px rgba(255,255,255,0.10);
    }

    /* Role selector cards */
    .role-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 18px;
    }

    .role-option { position: relative; }

    .role-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .role-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 16px 12px;
        background: rgba(255,255,255,0.10);
        border: 1px solid rgba(255,255,255,0.22);
        border-radius: var(--r);
        cursor: pointer;
        transition: var(--tr);
        text-align: center;
    }

    .role-label:hover {
        background: rgba(255,255,255,0.18);
        border-color: rgba(255,255,255,0.45);
    }

    .role-option input:checked + .role-label {
        background: rgba(255,255,255,0.22);
        border-color: rgba(255,255,255,0.65);
        box-shadow: 0 0 0 2px rgba(255,255,255,0.20);
    }

    .role-icon  { font-size: 26px; }
    .role-name  { font-size: 14px; font-weight: 600; }
    .role-desc  { font-size: 11.5px; color: var(--white70); line-height: 1.4; }

    /* Submit */
    .btn-submit {
        width: 100%;
        padding: 13px;
        background: rgba(255,255,255,0.92);
        color: var(--teal1);
        border: none;
        border-radius: var(--r);
        font-size: 15px;
        font-weight: 700;
        font-family: var(--font-body);
        cursor: pointer;
        transition: var(--tr);
        margin-top: 4px;
    }

    .btn-submit:hover {
        background: #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,0.20);
        transform: translateY(-1px);
    }

    /* Footer link */
    .card-footer {
        text-align: center;
        font-size: 14px;
        color: var(--white70);
        margin-top: 22px;
    }

    .card-footer a {
        color: var(--white);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    /* Page footer */
    .page-footer {
        position: relative;
        z-index: 1;
        text-align: center;
        padding: 14px;
        font-size: 13px;
        color: var(--white50);
        background: rgba(0,0,0,0.15);
        border-top: 1px solid rgba(255,255,255,0.10);
    }
    </style>
</head>
<body>

    <!-- Top bar -->
    <div class="top-bar">
        <a href="/index.php" class="brand">
            <img src="/assets/images/logo.png" alt="Faith Connect">
            <span>Faith Connect</span>
        </a>
        <div class="top-bar-link">
            Already have an account? <a href="/auth/login.php">Sign in</a>
        </div>
    </div>

    <!-- Centred card -->
    <div class="page-center">
        <div class="card">

            <div class="card-icon">✨</div>
            <div class="card-title">Create Account</div>
            <div class="card-sub">Join thousands on their spiritual journey</div>

            <!-- Error messages -->
            <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $err): ?>
                <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Success message -->
            <?php if ($success_message): ?>
            <div class="success-box">
                ✅ <?php echo htmlspecialchars($success_message); ?>
                <br><a href="/auth/login.php" style="color:#fff;font-weight:600;text-decoration:underline">Sign in now →</a>
            </div>
            <?php endif; ?>

            <form method="POST">

                <!-- Name + Email side by side -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input
                            class="form-input"
                            type="text"
                            name="full_name"
                            placeholder="Your name"
                            value="<?php echo htmlspecialchars($full_name); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input
                            class="form-input"
                            type="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required>
                    </div>
                </div>

                <!-- Password + Confirm side by side -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input
                            class="form-input"
                            type="password"
                            name="password"
                            placeholder="Min. 6 characters"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input
                            class="form-input"
                            type="password"
                            name="confirm_password"
                            placeholder="Repeat password"
                            required>
                    </div>
                </div>

                <!-- Role selection as visual cards -->
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">I am joining as</label>
                </div>
                <div class="role-grid">
                    <div class="role-option">
                        <input
                            type="radio"
                            name="role"
                            id="role-user"
                            value="user"
                            <?php echo ($role === 'user' || $role === '') ? 'checked' : ''; ?>
                            required>
                        <label class="role-label" for="role-user">
                            <span class="role-icon">🙍</span>
                            <span class="role-name">Community Member</span>
                            <span class="role-desc">Seek guidance from faith leaders</span>
                        </label>
                    </div>
                    <div class="role-option">
                        <input
                            type="radio"
                            name="role"
                            id="role-leader"
                            value="leader"
                            <?php echo ($role === 'leader') ? 'checked' : ''; ?>>
                        <label class="role-label" for="role-leader">
                            <span class="role-icon">🙏</span>
                            <span class="role-name">Faith Leader</span>
                            <span class="role-desc">Offer spiritual guidance to others</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="margin-top:16px">Create Account →</button>

            </form>

            <div class="card-footer">
                Already a member? <a href="/auth/login.php">Sign in here</a>
            </div>

        </div>
    </div>

    <div class="page-footer">
        &copy; <?php echo date("Y"); ?> Faith Connect. All Rights Reserved.
    </div>

</body>
</html>