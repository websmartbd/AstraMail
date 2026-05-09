<?php
/**
 * unsubscribe.php — Handles one-click unsubscriptions
 */

require_once __DIR__ . '/core/config.php';
$CONTACTS_FILE = __DIR__ . '/storage/contacts.json';

$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? ''); // Simple security token

if (!$email) {
    exit('Invalid request.');
}

// Simple validation: base64 of email as a token (for now)
if ($token !== base64_encode($email)) {
    exit('Invalid security token.');
}

if (file_exists($CONTACTS_FILE)) {
    $contacts = json_decode(file_get_contents($CONTACTS_FILE), true);
    $found = false;

    foreach ($contacts as &$c) {
        if (strtolower($c['email']) === strtolower($email)) {
            $c['status'] = 'unsubscribed';
            $c['unsubscribed_at'] = date('c');
            $found = true;
            break;
        }
    }

    if ($found) {
        file_put_contents($CONTACTS_FILE, json_encode($contacts, JSON_PRETTY_PRINT));
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; color: #0f172a; }
        .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); text-align: center; max-width: 400px; }
        h1 { font-size: 24px; margin-bottom: 16px; }
        p { color: #64748b; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>You're Unsubscribed</h1>
        <p>You have been successfully removed from our mailing list. You will no longer receive marketing emails from us.</p>
    </div>
</body>
</html>
