<?php
/**
 * bounceHandler.php — Scans the IMAP inbox for bounce messages
 * and updates contacts.json status to 'bounced'.
 */

require_once __DIR__ . '/config.php';
$smtp_config = get_mailer_config();

$CONTACTS_FILE = __DIR__ . '/../storage/contacts.json';
$LOG_FILE = __DIR__ . '/../cron.log';

function blog($msg)
{
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] [BOUNCE] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
    echo $line;
}

if (!function_exists('imap_open')) {
    blog("ERROR: PHP IMAP extension is NOT installed on this server. Bounce handling disabled.");
    exit;
}

if (empty($smtp_config['imap_host'])) {
    blog("IMAP host not configured. Skipping bounce check.");
    exit;
}

$host = $smtp_config['imap_host'];
$port = $smtp_config['imap_port'] ?? 993;
$user = $smtp_config['imap_username'];
$pass = $smtp_config['imap_password'];
$mailbox = "{" . $host . ":" . $port . "/imap/ssl}INBOX";

blog("Connecting to $mailbox...");

$mbox = @imap_open($mailbox, $user, $pass);

if (!$mbox) {
    blog("IMAP Connection Failed: " . imap_last_error());
    exit;
}

// Search for ALL unread messages
$emails_to_block = [];
$messages = imap_search($mbox, 'UNSEEN');

if ($messages) {
    blog("Found " . count($messages) . " unread messages to scan.");

    foreach ($messages as $msg_id) {
        $header = imap_headerinfo($mbox, $msg_id);
        $subject = isset($header->subject) ? $header->subject : '';
        $body    = imap_fetchbody($mbox, $msg_id, 1);
        $full_text = $body . " " . $subject;

        // Check if this is actually a bounce message
        $bounce_keywords = ['undelivered', 'failure', 'returned mail', 'delivery status', 'postmaster', 'mailer-daemon'];
        $is_bounce = false;
        foreach ($bounce_keywords as $kw) {
            if (stripos($full_text, $kw) !== false) { $is_bounce = true; break; }
        }

        // --- NEW SAFETY CHECK ---
        // If the error is about YOUR server being blocked (Rate Limit), 
        // DO NOT mark the recipient as bounced. It's not their fault!
        $server_errors = ['exceeded the max defers', 'Message discarded', 'too many messages', 'rate limit'];
        foreach ($server_errors as $se) {
            if (stripos($full_text, $se) !== false) {
                blog("Skipping: This is a SERVER LIMIT error, not a recipient bounce.");
                $is_bounce = false; 
                break;
            }
        }

        if (!$is_bounce) {
            continue; // Skip normal emails or server errors
        }

        // Look for email addresses in the bounce message
        // Common DSN pattern: "Final-Recipient: rfc822; user@domain.com"
        // Also fallback to any email-like string near failure keywords
        // 1. Standard DSN (Delivery Status Notification) pattern
        if (preg_match('/Final-Recipient:\s*rfc822\s*;\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        } 
        // 2. Diagnostic Code pattern
        elseif (preg_match('/Diagnostic-Code:.*?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }
        // 3. Failed address block pattern (like the user's screenshot)
        elseif (preg_match('/failed:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }
        // 4. Fallback: Any email followed by a 5xx error code
        elseif (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}).*?5\d{2}/is', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }

        // Mark as seen so we don't process it again next time
        imap_setflag_full($mbox, $msg_id, "\\Seen");
    }
} else {
    blog("No new bounce messages found.");
}

imap_close($mbox);

$emails_to_block = array_unique(array_filter($emails_to_block));

if (!empty($emails_to_block)) {
    blog("Discovered " . count($emails_to_block) . " unique bounced addresses.");

    if (file_exists($CONTACTS_FILE)) {
        $contacts = json_decode(file_get_contents($CONTACTS_FILE), true);
        $updated = 0;

        foreach ($contacts as &$c) {
            if (in_array($c['email'], $emails_to_block)) {
                if (($c['status'] ?? 'active') !== 'bounced') {
                    $c['status'] = 'bounced';
                    $c['bounced_at'] = date('c');
                    $updated++;
                    blog("!!! Marked as BOUNCED: {$c['email']}");
                }
            }
        }

        if ($updated > 0) {
            file_put_contents($CONTACTS_FILE, json_encode($contacts, JSON_PRETTY_PRINT));
            blog("Updated contacts.json with $updated new bounces.");
        }
    }
}

blog("Bounce handling complete.");
