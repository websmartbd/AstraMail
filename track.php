<?php
/**
 * track.php — AstraMail Tracking Engine
 * Handles Email Opens and Link Clicks
 */

require_once __DIR__ . '/core/config.php';
$STATE_DIR    = __DIR__ . '/storage/archive';
$ACTIVE_FILE  = __DIR__ . '/storage/campaignState.json';

$type = $_GET['type'] ?? ''; // 'open' or 'click'
$cid  = $_GET['cid']  ?? ''; // Campaign ID
$url  = $_GET['url']  ?? ''; // Target URL (for clicks)
$email = $_GET['e']   ?? ''; // Recipient Email (optional, for granular tracking)

if (!$cid) exit;

// 1. Find ALL matching campaign files (check active and archive)
$target_files = [];
if (file_exists($ACTIVE_FILE)) {
    $temp_data = json_decode(file_get_contents($ACTIVE_FILE), true);
    if (($temp_data['campaign_id'] ?? '') === $cid) {
        $target_files[] = $ACTIVE_FILE;
    }
}

// Always check archive to ensure past records are synced
$files = glob($STATE_DIR . "/campaign_*.json");
foreach ($files as $f) {
    $temp_data = json_decode(file_get_contents($f), true);
    if (($temp_data['campaign_id'] ?? '') === $cid) {
        if (!in_array($f, $target_files)) $target_files[] = $f;
    }
}

foreach ($target_files as $file) {
    // LOCK file for safe writing
    $fp = fopen($file, 'r+');
    if (flock($fp, LOCK_EX)) {
        $content = stream_get_contents($fp);
        $data = json_decode($content, true);
        
        if ($data) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            // 🛡️ BOT FILTER: Ignore known non-human scanners (keeping Gmail proxy allowed)
            $is_bot = false;
            $bots = ['bingbot', 'slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'Edge/12.246'];
            foreach ($bots as $bot) {
                if (stripos($ua, $bot) !== false) { $is_bot = true; break; }
            }

            // 👁️ OPEN LOGIC
            if ($type === 'open' && !$is_bot) {
                if (!isset($data['open_log'])) $data['open_log'] = [];
                if (!in_array($email, $data['open_log'])) {
                    $data['opens'] = ($data['opens'] ?? 0) + 1;
                    $data['open_log'][] = $email;
                }
            } 
            // 🖱️ CLICK LOGIC (with Open Fail-Safe)
            elseif ($type === 'click') {
                // FAIL-SAFE: If they click, they MUST have opened it.
                if (!isset($data['open_log'])) $data['open_log'] = [];
                if (!in_array($email, $data['open_log'])) {
                    $data['opens'] = ($data['opens'] ?? 0) + 1;
                    $data['open_log'][] = $email;
                }

                if (!isset($data['click_log'])) $data['click_log'] = [];
                $has_clicked = false;
                foreach ($data['click_log'] as $cl) { if (($cl['email'] ?? '') === $email) { $has_clicked = true; break; } }
                
                if (!$has_clicked) {
                    $data['clicks'] = ($data['clicks'] ?? 0) + 1;
                    $data['click_log'][] = ['email' => $email, 'url' => base64_decode($url), 'time' => date('c'), 'ua' => $ua];
                }
            }
            
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// 2. Respond
if ($type === 'open') {
    // Return a 1x1 transparent GIF
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
} elseif ($type === 'click' && $url) {
    // Redirect to the actual destination
    header('Location: ' . base64_decode($url));
}
exit;
