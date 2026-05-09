<?php
header('Content-Type: application/json');

// Disable error display to prevent warnings from breaking JSON responses
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any accidental output
ob_start();

require_once __DIR__ . '/core/mailer.php';
$SETTINGS_FILE = __DIR__ . '/storage/settings.json';
$CONTACTS_FILE = __DIR__ . '/storage/contacts.json';
$STATE_FILE    = __DIR__ . '/storage/campaignState.json';

$email_list = file_exists($CONTACTS_FILE) ? json_decode(file_get_contents($CONTACTS_FILE), true) : [];

// _secret removed as we now use session_id() for API tokens

// Set Global Timezone
date_default_timezone_set($smtp_config['timezone'] ?? 'UTC');

session_start();
$is_authenticated = isset($_SESSION['bms_auth']) && $_SESSION['bms_auth'] === true;

// ── AUTH: Session-bound token check (fixes hardcoded token vulnerability) ────────────────
// Token is now the session ID, not a static string
if (!$is_authenticated && ($_POST['_token'] ?? '') !== session_id()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access. Please login.']);
    exit;
}

$action = trim($_POST['action'] ?? 'queue');

// ── STATUS ────────────────────────────────────────────────────────────────────
if ($action === 'status') {
    echo file_exists($STATE_FILE)
        ? file_get_contents($STATE_FILE)
        : json_encode(['status' => 'idle']);
    exit;
}

// ── CANCEL ────────────────────────────────────────────────────────────────────
if ($action === 'cancel') {
    if (file_exists($STATE_FILE)) {
        $s = json_decode(file_get_contents($STATE_FILE), true);
        $s['status'] = 'cancelled';
        $s['updated_at'] = date('c');
        file_put_contents($STATE_FILE, json_encode($s, JSON_PRETTY_PRINT));
    }
    echo json_encode(['status' => 'cancelled']);
    exit;
}

// ── RESET (Archive current and clear) ────────────────────────────────────────
if ($action === 'reset') {
    if (file_exists($STATE_FILE)) {
        $s = json_decode(file_get_contents($STATE_FILE), true);
        $cid = $s['campaign_id'] ?? uniqid('camp_');
        $archive_path = __DIR__ . "/storage/archive/campaign_{$cid}.json";
        
        // Move to archive
        rename($STATE_FILE, $archive_path);
    }
    echo json_encode(['status' => 'idle']);
    exit;
}

// ── SAVE SETTINGS ─────────────────────────────────────────────────────────────
if ($action === 'save_settings') {
    $new_settings = [
        'host'       => trim($_POST['host'] ?? ''),
        'username'   => trim($_POST['username'] ?? ''),
        'password'   => trim($_POST['password'] ?? ''),
        'port'       => (int)($_POST['port'] ?? 465),
        'encryption' => trim($_POST['encryption'] ?? 'ssl'),
        'from_email' => str_replace(["\r", "\n"], '', trim($_POST['from_email'] ?? '')),
        'from_name'  => str_replace(["\r", "\n"], '', trim($_POST['from_name'] ?? '')),
        'hourly_limit' => (int)($_POST['hourly_limit'] ?? 25),
        'timezone'   => trim($_POST['timezone'] ?? 'UTC'),
        'app_url'    => rtrim(trim($_POST['app_url'] ?? ''), '/') . '/',
        'imap_host'     => trim($_POST['imap_host'] ?? ''),
        'imap_port'     => (int)($_POST['imap_port'] ?? 993),
        'imap_username' => trim($_POST['imap_username'] ?? ''),
        'imap_password' => trim($_POST['imap_password'] ?? ''),
        // Bug 1 FIX: Preserve _password — never erase it on settings save
        '_password'     => $smtp_config['_password'] ?? '',
    ];

    // Low-priority fix: Validate timezone
    if (!in_array($new_settings['timezone'], timezone_identifiers_list())) {
        $new_settings['timezone'] = 'UTC';
    }

    // Low-priority fix: Validate encryption
    if (!in_array($new_settings['encryption'], ['ssl', 'tls', 'starttls'])) {
        $new_settings['encryption'] = 'ssl';
    }

    file_put_contents($SETTINGS_FILE, json_encode($new_settings, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => 'Settings updated.']);
    exit;
}

// ── ADD CONTACT ───────────────────────────────────────────────────────────────
if ($action === 'add_contact') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name || !$email) {
        echo json_encode(['status' => 'error', 'message' => 'Name and email required.']);
        exit;
    }
    // Low-priority fix: Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
        exit;
    }
    // Prevent duplicate emails
    foreach ($email_list as $ex) {
        if (strtolower($ex['email'] ?? '') === strtolower($email)) {
            echo json_encode(['status' => 'error', 'message' => 'This email already exists in your list.']);
            exit;
        }
    }
    $email_list[] = ['name' => $name, 'email' => $email];
    file_put_contents($CONTACTS_FILE, json_encode($email_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['status' => 'success', 'message' => 'Contact added.']);
    exit;
}

// ── EDIT CONTACT ───────────────────────────────────────────────────────────────────────
if ($action === 'edit_contact') {
    $index      = (int)($_POST['index'] ?? -1);
    $old_email  = trim($_POST['old_email'] ?? ''); // Verify identity before mutating
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    // Fix #4: Require old_email to match to prevent index tampering
    if ($index >= 0 && isset($email_list[$index]) && $email_list[$index]['email'] === $old_email) {
        $email_list[$index]['name']  = $name;
        $email_list[$index]['email'] = $email;
        file_put_contents($CONTACTS_FILE, json_encode($email_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['status' => 'success']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Contact not found or identity mismatch.']); exit;
}

// ── DELETE CONTACT ────────────────────────────────────────────────────────────
if ($action === 'reactivate_contact') {
    $index     = (int)($_POST['index'] ?? -1);
    $old_email = trim($_POST['old_email'] ?? '');
    // Fix #4: Verify email matches before reactivating
    if ($index >= 0 && isset($email_list[$index]) && $email_list[$index]['email'] === $old_email) {
        $email_list[$index]['status'] = 'active';
        unset($email_list[$index]['bounced_at']);
        file_put_contents($CONTACTS_FILE, json_encode($email_list, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Contact not found or identity mismatch.']);
    }
    exit;
}

if ($action === 'delete_contact') {
    $index     = (int)($_POST['index'] ?? -1);
    $old_email = trim($_POST['old_email'] ?? '');
    // Fix #4: Verify email matches before deleting
    if ($index >= 0 && isset($email_list[$index]) && $email_list[$index]['email'] === $old_email) {
        array_splice($email_list, $index, 1);
        file_put_contents($CONTACTS_FILE, json_encode($email_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['status' => 'success']);
        exit;
    }
    echo json_encode(['status' => 'error']); exit;
}

// ── SYSTEM LOG ──────────────────────────────────────────────────────────────
if ($action === 'get_system_log') {
    $log_file = __DIR__ . '/cron.log';
    if (!file_exists($log_file)) {
        echo json_encode(['status' => 'success', 'log' => 'No activity recorded yet.']);
        exit;
    }
    // Read last 100 lines
    $lines = file($log_file);
    $last_lines = array_slice($lines, -100);
    echo json_encode(['status' => 'success', 'log' => implode('', $last_lines)]);
    exit;
}

if ($action === 'clear_system_log') {
    $log_file = __DIR__ . '/cron.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Log cleared by administrator.\n");
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'sync_bounces') {
    // Increase time limit for IMAP operations
    @set_time_limit(300); 
    
    // Define a constant to prevent early exit in bounceHandler.php
    define('BOUNCE_LIB', true);
    $result = require_once __DIR__ . '/core/bounceHandler.php';
    
    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Sync complete.']);
    exit;
}

// ── QUEUE (save campaign for cron to pick up) ─────────────────────────────────
$subject  = trim($_POST['subject'] ?? '');
$body_raw = trim($_POST['body']    ?? '');
$target   = trim($_POST['target']  ?? 'all');
$schedule = trim($_POST['scheduled_at'] ?? ''); // Empty or ISO date

if (!$subject || !$body_raw) {
    echo json_encode(['status' => 'error', 'message' => 'Subject and body are required.']);
    exit;
}

// Filter out bounced contacts from the very start
$active_list = array_values(array_filter($email_list, function($c) {
    return ($c['status'] ?? 'active') !== 'bounced';
}));

// Count recipients
$total = 0;
if ($target === 'all') {
    $total = count($active_list);
} else {
    $targets = explode(',', $target);
    foreach ($targets as $t) {
        $t = trim($t);
        if (array_filter($active_list, fn($c) => $c['email'] === $t)) $total++;
    }
}

if ($total === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No active recipients found.']);
    exit;
}

$state = [
    'campaign_id'   => uniqid('camp_'),
    'status'        => $schedule ? 'scheduled' : 'queued',
    'campaign_name' => trim($_POST['name'] ?? $subject),
    'subject'       => $subject,
    'body'          => $body_raw,
    'target'        => $target,
    'total'         => (int)$total,
    'offset'        => 0,
    'sent'          => 0,
    'failed'        => 0,
    'sent_log'      => [],
    'scheduled_at'  => $schedule ? strtotime($schedule) : null,
    'queued_at'     => date('c'),
    'updated_at'    => date('c'),
];

if ($schedule) {
    $scheduled_dir = __DIR__ . '/storage/scheduled';
    if (!is_dir($scheduled_dir)) mkdir($scheduled_dir, 0755, true);
    $path = $scheduled_dir . '/campaign_' . $state['campaign_id'] . '.json';
    file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'status'  => 'scheduled',
        'message' => 'Campaign scheduled for ' . date('Y-m-d H:i', $state['scheduled_at']),
        'total'   => $total,
    ]);
} else {
    // Check if busy
    if (file_exists($STATE_FILE)) {
        $ex = json_decode(file_get_contents($STATE_FILE), true);
        if (in_array($ex['status'] ?? '', ['queued', 'sending'])) {
            echo json_encode(['status' => 'error', 'message' => 'An active campaign is already in progress.']);
            exit;
        }
    }
    file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
    echo json_encode([
        'status'  => 'queued',
        'message' => 'Campaign queued for immediate sending.',
        'total'   => $total,
    ]);
}