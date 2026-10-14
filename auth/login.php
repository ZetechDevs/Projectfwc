<?php
session_start();
require_once "../config/db.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $result   = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['full_name'];

            if ($user['role'] == 'admin') {
                header("Location: ../dashboards/admin.php");
            } elseif ($user['role'] == 'leader') {
                header("Location: ../dashboards/leader.php");
            } else {
                header("Location: ../dashboards/user.php");
            }
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Faith Connect</title>
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

    /* Card */
    .card {
        width: 100%;
        max-width: 420px;
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

    /* Error */
    .error-box {
        background: rgba(231,76,60,0.18);
        border: 1px solid rgba(231,76,60,0.45);
        border-radius: var(--r);
        padding: 11px 14px;
        font-size: 13.5px;
        color: #ffcdd2;
        margin-bottom: 20px;
    }

    /* Form */
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 18px;
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
        margin-top: 6px;
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
            No account yet? <a href="/auth/register.php">Register free</a>
        </div>
    </div>

    <!-- Centred card -->
    <div class="page-center">
        <div class="card">

            <div class="card-icon">🙏</div>
            <div class="card-title">Welcome Back</div>
            <div class="card-sub">Sign in to continue your spiritual journey</div>

            <?php if ($error): ?>
            <div class="error-box">⚠ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input
                        class="form-input"
                        type="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input
                        class="form-input"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required>
                </div>

                <button type="submit" class="btn-submit">Sign In →</button>

            </form>

            <div class="card-footer">
                New to Faith Connect? <a href="/auth/register.php">Create an account</a>
            </div>

        </div>
    </div>

    <div class="page-footer">
        &copy; <?php echo date("Y"); ?> Faith Connect. All Rights Reserved.
    </div>

</body>
</html>