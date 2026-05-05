<?php
// core/auth.php — Shared authentication and config loader
session_start();

require_once __DIR__ . '/config.php';
$smtp_config = get_mailer_config();
$brand_name = $smtp_config['from_name'] ?? 'BMS Mailer';

// Set Global Timezone
date_default_timezone_set($smtp_config['timezone'] ?? 'UTC');

// Handle Login
$auth_error = false;
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ($smtp_config['_password'] ?? 'admin123')) {
        $_SESSION['bms_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $auth_error = "Incorrect password. Please try again.";
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
            <div class="login-logo">M</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-desc">Enter your password to access the dashboard.</p>

            <?php if ($auth_error) { ?>
                <div class="error-msg"><?= htmlspecialchars($auth_error) ?></div>
            <?php } ?>

            <form method="POST">
                <div class="form-group" style="text-align: left;">
                    <label>Password</label>
                    <input type="password" name="login_password" placeholder="••••••••" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 10px;">Sign
                    In</button>
            </form>
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
