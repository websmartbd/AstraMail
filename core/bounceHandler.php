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
    // Echo to terminal if running in CLI, but stay silent for web requests to prevent JSON corruption
    if (php_sapi_name() === 'cli') echo $line;
}

if (!function_exists('imap_open')) {
    blog("ERROR: PHP IMAP extension is NOT installed on this server. Bounce handling disabled.");
    if (defined('BOUNCE_LIB')) return false;
    exit;
}

if (empty($smtp_config['imap_host'])) {
    blog("IMAP host not configured. Skipping bounce check.");
    if (defined('BOUNCE_LIB')) return false;
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
    if (defined('BOUNCE_LIB')) return false;
    exit;
}

// Search for ALL unread messages
$emails_to_block = [];
// Search for emails from the last 3 days
$date = date("d-M-Y", strToTime("-3 days"));
$messages = imap_search($mbox, "SINCE \"$date\"");

if ($messages) {
    blog("Found " . count($messages) . " potential messages to scan from the last 3 days.");

    // Fetch overviews first (very fast) to filter down to only likely bounces
    $overviews = imap_fetch_overview($mbox, implode(',', $messages), 0);
    $likely_bounces = [];
    $bounce_keywords = ['undelivered', 'failure', 'returned mail', 'delivery status', 'postmaster', 'mailer-daemon', 'rejection', 'not exist'];

    foreach ($overviews as $ov) {
        $full_text_meta = ($ov->subject ?? '') . ' ' . ($ov->from ?? '');
        $is_likely = false;
        foreach ($bounce_keywords as $kw) {
            if (stripos($full_text_meta, $kw) !== false) { $is_likely = true; break; }
        }
        if ($is_likely) $likely_bounces[] = $ov->msgno;
    }

    blog("Filtered down to " . count($likely_bounces) . " likely bounce messages.");

    foreach ($likely_bounces as $msg_id) {
        // Fetch headers as they often contain the failed address in X-Failed-Recipients
        $headers_raw = imap_fetchheader($mbox, $msg_id);
        
        // Fetch part 1 (usually text) 
        $body = imap_fetchbody($mbox, $msg_id, 1);
        
        // If part 1 is empty or doesn't seem to contain much, try to fetch the full raw body
        if (strlen($body) < 100) {
            $body = imap_body($mbox, $msg_id);
        }
        
        $full_text = $headers_raw . "\n\n" . $body;

        // Check if this is actually a bounce message
        $bounce_keywords = ['undelivered', 'failure', 'returned mail', 'delivery status', 'postmaster', 'mailer-daemon', 'rejection', 'not exist'];
        $is_bounce = false;
        foreach ($bounce_keywords as $kw) {
            if (stripos($full_text, $kw) !== false) { $is_bounce = true; break; }
        }

        // --- NEW SAFETY CHECK ---
        // If the error is about YOUR server being blocked (Rate Limit), 
        // DO NOT mark the recipient as bounced. It's not their fault!
        $server_errors = ['exceeded the max defers', 'Message discarded', 'too many messages', 'rate limit', 'over quota'];
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
        // 1. Standard DSN (Delivery Status Notification) pattern
        if (preg_match('/Final-Recipient:\s*rfc822\s*;\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        } 
        // 2. Failed Recipient pattern
        elseif (preg_match('/(?:Failed|Original)-Recipient:\s*(?:rfc822;\s*)?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }
        // 3. Diagnostic Code pattern
        elseif (preg_match('/Diagnostic-Code:.*?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }
        // 4. "Failed address" text block pattern
        elseif (preg_match('/(?:failed|undeliverable|reject(?:ed)?):\s*<?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i', $full_text, $matches)) {
            $emails_to_block[] = trim($matches[1]);
        }
        // 5. Fallback: Any email followed by a 5xx error code in close proximity
        elseif (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\s*.*?(?:5\d{2}|does not exist)/is', $full_text, $matches)) {
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
