<?php
// core/auth.php — Shared authentication and config loader
session_start();

require_once __DIR__ . '/config.php';
$smtp_config = get_mailer_config();
$brand_name = $smtp_config['from_name'] ?? 'BMS Mailer';

// Set Global Timezone
date_default_timezone_set($smtp_config['timezone'] ?? 'UTC');

// Initialize auth state variables
$auth_error    = false;
$is_locked_out = false;

// ── BRUTE-FORCE LOCKOUT ──────────────────────────────────────────────────────
$fail_file = __DIR__ . '/../storage/.login_fails';
$fail_data = file_exists($fail_file) ? json_decode(file_get_contents($fail_file), true) : ['count' => 0, 'last' => 0];
$lockout_seconds = 900; // 15 minutes
if (($fail_data['count'] ?? 0) >= 5 && (time() - ($fail_data['last'] ?? 0)) < $lockout_seconds) {
    $wait = ceil(($lockout_seconds - (time() - $fail_data['last'])) / 60);
    $auth_error = "Too many failed attempts. Please wait {$wait} minute(s) before trying again.";
    $is_locked_out = true;
} else {
    if ((time() - ($fail_data['last'] ?? 0)) >= $lockout_seconds) {
        $fail_data = ['count' => 0, 'last' => 0]; // Reset after cooldown
    }
    $is_locked_out = false;
}

// ── FIRST-RUN SETUP ──────────────────────────────────────────────────────────
$needs_setup = empty($smtp_config['_password']);

// Handle First-Run Password Setup
if ($needs_setup && isset($_POST['setup_password'])) {
    $new_pass = trim($_POST['setup_password']);
    $confirm  = trim($_POST['confirm_password']);
    if (strlen($new_pass) < 8) {
        $auth_error = 'Password must be at least 8 characters.';
    } elseif ($new_pass !== $confirm) {
        $auth_error = 'Passwords do not match.';
    } else {
        $settings_file = __DIR__ . '/../storage/settings.json';
        $stored = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
        $stored['_password'] = $new_pass;
        file_put_contents($settings_file, json_encode($stored, JSON_PRETTY_PRINT));
        session_regenerate_id(true);
        $_SESSION['bms_auth'] = true;
        header('Location: index.php'); exit;
    }
}

// Handle Login
$auth_error = $auth_error ?: false;
if (!$is_locked_out && !$needs_setup && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ($smtp_config['_password'] ?? '')) {
        // Success — reset lockout, regenerate session
        file_put_contents($fail_file, json_encode(['count' => 0, 'last' => 0]));
        session_regenerate_id(true);
        $_SESSION['bms_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $fail_data['count'] = ($fail_data['count'] ?? 0) + 1;
        $fail_data['last']  = time();
        file_put_contents($fail_file, json_encode($fail_data));
        $remaining = max(0, 5 - $fail_data['count']);
        $auth_error = "Incorrect password. {$remaining} attempt(s) remaining before lockout.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$is_authenticated = isset($_SESSION['bms_auth']) && $_SESSION['bms_auth'] === true;

if (!$is_authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login — <?= htmlspecialchars($brand_name) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="assets/style.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                background: #f8fafc;
                margin: 0;
            }

            .login-card {
                background: #fff;
                padding: 40px;
                border-radius: 24px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
                width: 100%;
                max-width: 360px;
                text-align: center;
                border: 1px solid #e2e8f0;
            }

            .login-logo {
                width: 48px;
                height: 48px;
                background: var(--accent);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-weight: 800;
                margin: 0 auto 24px;
            }

            .login-title {
                font-size: 24px;
                font-weight: 800;
                margin-bottom: 8px;
                letter-spacing: -0.5px;
            }

            .login-desc {
                color: #64748b;
                font-size: 14px;
                margin-bottom: 32px;
            }

            .error-msg {
                background: #fef2f2;
                color: #ef4444;
                padding: 12px;
                border-radius: 12px;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 20px;
            }
        </style>
    </head>

    <body>
        <div class="login-card">
            <div class="login-logo">A</div>
            <?php if ($needs_setup): ?>
                <h1 class="login-title">Welcome to AstraMail</h1>
                <p class="login-desc">Create a strong password to secure your dashboard.</p>
                <?php if ($auth_error): ?>
                    <div class="error-msg"><?= htmlspecialchars($auth_error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group" style="text-align:left; margin-bottom:12px;">
                        <label>New Password (min. 8 chars)</label>
                        <input type="password" name="setup_password" placeholder="••••••••" required autofocus minlength="8">
                    </div>
                    <div class="form-group" style="text-align:left;">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; height:48px; margin-top:10px;">Set Password & Enter</button>
                </form>
            <?php else: ?>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-desc">Enter your password to access the dashboard.</p>
                <?php if ($auth_error): ?>
                    <div class="error-msg"><?= htmlspecialchars($auth_error) ?></div>
                <?php endif; ?>
                <?php if (!$is_locked_out): ?>
                <form method="POST">
                    <div class="form-group" style="text-align: left;">
                        <label>Password</label>
                        <input type="password" name="login_password" placeholder="••••••••" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 10px;">Sign In</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </body>

    </html>
    <?php
    exit;
}

// Helper to get active class
function nav_active($page)
{
    return strpos($_SERVER['PHP_SELF'], $page) !== false ? 'active' : '';
}
