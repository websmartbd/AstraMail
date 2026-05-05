<?php
/**
 * cron.php — Run this via cPanel Cron Job every hour:
 *   php /home/yourusername/public_html/email-marketing/cron.php
 *
 * This sends up to $HOURLY_LIMIT emails per run, then stops.
 * Safe for shared hosting with 30 email/hour limits.
 */

require_once __DIR__ . '/core/mailer.php';
$SETTINGS_FILE = __DIR__ . '/storage/settings.json';
$CONTACTS_FILE = __DIR__ . '/storage/contacts.json';
$STATE_FILE    = __DIR__ . '/storage/campaignState.json';
$email_list = file_exists($CONTACTS_FILE) ? json_decode(file_get_contents($CONTACTS_FILE), true) : [];

// Set Global Timezone
date_default_timezone_set($smtp_config['timezone'] ?? 'UTC');


// ─── CONFIG ───────────────────────────────────────────────────────────────────
$HOURLY_LIMIT  = (int)($smtp_config['hourly_limit'] ?? 25);
$DELAY_SECONDS = 10;
$LOG_FILE      = __DIR__ . '/cron.log';
// ──────────────────────────────────────────────────────────────────────────────

function clog($msg) {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
    echo $line;
}

// ── AUTO-CLEAN PREVIOUS CAMPAIGNS ───────────────────────────────────────────
if (file_exists($STATE_FILE)) {
    $current = json_decode(file_get_contents($STATE_FILE), true);
    $cur_status = $current['status'] ?? '';
    
    if (in_array($cur_status, ['done', 'cancelled'])) {
        clog("Clearing old campaign '{$current['campaign_name']}' to make room for schedule.");
        $cid = $current['campaign_id'] ?? uniqid('camp_');
        $archive_path = __DIR__ . "/storage/archive/campaign_{$cid}.json";
        file_put_contents($archive_path, json_encode($current, JSON_PRETTY_PRINT));
        unlink($STATE_FILE);
    }
}

// ── CHECK SCHEDULED FOLDER ───────────────────────────────────────────────────
if (!file_exists($STATE_FILE)) {
    $scheduled_dir = __DIR__ . '/storage/scheduled';
    $files = glob($scheduled_dir . '/campaign_*.json');
    $now = time();
    
    foreach ($files as $file) {
        $s = json_decode(file_get_contents($file), true);
        if ($s && ($s['scheduled_at'] ?? 0) <= $now) {
            clog("Promoting scheduled campaign '{$s['campaign_name']}' to active state.");
            $s['status'] = 'queued';
            file_put_contents($STATE_FILE, json_encode($s, JSON_PRETTY_PRINT));
            unlink($file); // Remove from scheduled folder
            break; 
        }
    }
}

// Still no active campaign?
if (!file_exists($STATE_FILE)) {
    clog('No active or ready-to-launch campaigns. Waiting...');
    exit;
}

$state = json_decode(file_get_contents($STATE_FILE), true);

if (!$state || !is_array($state)) {
    clog("Campaign state is invalid. Deleting file.");
    @unlink($STATE_FILE);
    exit;
}

$status = $state['status'] ?? 'idle';

if ($status === 'scheduled') {
    $now = time();
    $sch = $state['scheduled_at'] ?? 0;
    if ($now < $sch) {
        clog("Campaign scheduled for " . date('Y-m-d H:i:s', $sch) . ". Waiting...");
        exit;
    }
    // Time to start!
    $state['status'] = 'queued';
    file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
}

if (!in_array($state['status'] ?? '', ['queued', 'sending', 'paused', 'done_batch'])) {
    clog("Campaign status is '{$state['status']}' — nothing to do.");
    exit;
}

$subject  = $state['subject']  ?? '';
$body_raw = $state['body']     ?? '';
$target   = $state['target']   ?? 'all';
$offset   = $state['offset']   ?? 0;
$sent_log = $state['sent_log'] ?? [];

// Build full recipient list
if ($target === 'all') {
    $recipients = $email_list;
} else {
    $targets = explode(',', $target);
    $targets = array_map('trim', $targets);
    $recipients = array_values(array_filter($email_list, fn($c) => in_array($c['email'], $targets)));
}

$total = count($recipients);

if ($offset >= $total) {
    $state['status']       = 'done';
    $state['completed_at'] = date('c');
    file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
    clog("Campaign already complete ($offset / $total).");
    exit;
}

clog("=== Cron run started. Offset: $offset / $total. Sending up to $HOURLY_LIMIT emails. ===");

$state['status']     = 'sending';
$state['updated_at'] = date('c');
file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));

$batch       = array_slice($recipients, $offset, $HOURLY_LIMIT);
$sent_this_run = 0;
$fail_this_run = 0;

foreach ($batch as $i => $contact) {
    // Re-check for cancel signal
    $live = json_decode(file_get_contents($STATE_FILE), true);
    if (($live['status'] ?? '') === 'cancelled') {
        clog("Cancelled signal detected. Stopping.");
        break;
    }

    $ps   = str_replace('{{name}}', $contact['name'], $subject);
    $pb   = str_replace('{{name}}', htmlspecialchars($contact['name']), $body_raw);
    $html = build_html_email($smtp_config['from_name'], $ps, $pb);

    // Single attempt only — no retry (avoid double-counting toward hourly limit)
    $res = _send_smtp_core($contact['email'], $ps, $html);

    if ($res === 'success') {
        $sent_this_run++;
        $row = ['email' => $contact['email'], 'name' => $contact['name'], 'status' => 'sent'];
        clog("✓ Sent to {$contact['email']} ({$contact['name']})");
    } else {
        $fail_this_run++;
        $row = ['email' => $contact['email'], 'name' => $contact['name'], 'status' => 'failed', 'error' => $res];

        // Permanent failure? Log and skip. Temporary? Also skip (cron will retry next hour via re-queue logic below)
        $permanent = stripos($res, '550') !== false || stripos($res, '551') !== false || stripos($res, 'invalid') !== false;
        clog(($permanent ? '✗ Permanent fail' : '~ Temp fail') . " for {$contact['email']}: $res");
    }

    $sent_log[] = $row;

    // Update state after each email
    $state['offset']     = $offset + $sent_this_run + $fail_this_run;
    $state['sent']       = count(array_filter($sent_log, fn($r) => $r['status'] === 'sent'));
    $state['failed']     = count(array_filter($sent_log, fn($r) => $r['status'] === 'failed'));
    $state['sent_log']   = $sent_log;
    $state['updated_at'] = date('c');
    file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));

    // Delay between emails — not after the last one
    if ($i < count($batch) - 1) sleep($DELAY_SECONDS);
}

// ── Finalize this run ─────────────────────────────────────────────────────────
$new_offset = $state['offset'];
$is_done    = ($new_offset >= $total);

$live = json_decode(file_get_contents($STATE_FILE), true);
if (($live['status'] ?? '') === 'cancelled') {
    $state['status'] = 'cancelled';
} elseif ($is_done) {
    $state['status']       = 'done';
    $state['completed_at'] = date('c');
    $state['updated_at']   = date('c');
    
    // Automatic Archiving
    $cid = $state['campaign_id'] ?? uniqid('camp_');
    $archive_path = __DIR__ . "/storage/archive/campaign_{$cid}.json";
    
    // Save to archive first
    file_put_contents($archive_path, json_encode($state, JSON_PRETTY_PRINT));
    
    // Now also update the main state file (so the dashboard knows it's done)
    file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
} else {
    $state['status'] = 'queued'; // ready for next cron run
}

$state['updated_at'] = date('c');
file_put_contents($STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));

clog("=== Run complete. Sent: $sent_this_run, Failed: $fail_this_run, Progress: {$new_offset}/{$total}, Status: {$state['status']} ===");