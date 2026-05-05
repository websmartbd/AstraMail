<?php
// SMTP Configuration
require_once __DIR__ . '/config.php';
$smtp_config = get_mailer_config();


function send_email($to, $subject, $message, $headers = '') {
    global $smtp_config;
    $max_attempts = 3;
    $attempt = 1;
    $last_error = '';
    while ($attempt <= $max_attempts) {
        try {
            $res = _send_smtp_core($to, $subject, $message, $headers);
            if ($res === 'success') return 'success';
            $last_error = $res;
        } catch (Exception $e) {
            $last_error = $e->getMessage();
        }
        error_log("SMTP Attempt $attempt failed for $to: $last_error. Retrying...");
        sleep(3);
        $attempt++;
    }
    $local_headers = "From: {$smtp_config['from_name']} <{$smtp_config['from_email']}>\r\n" .
                     "Reply-To: {$smtp_config['from_email']}\r\n" .
                     "MIME-Version: 1.0\r\n" .
                     "Content-Type: text/html; charset=UTF-8\r\n";
    if (@mail($to, $subject, $message, $local_headers)) {
        return 'success';
    }
    return "Failed after $max_attempts attempts. Last error: $last_error";
}

function _send_smtp_core($to, $subject, $message, $headers = '') {
    global $smtp_config;
    $fromName  = $smtp_config['from_name'];
    $fromEmail = $smtp_config['from_email'];
    if (empty($headers)) {
        $headers  = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: <$fromEmail>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    }
    $timeout = 25;
    $smtp = @stream_socket_client(
        "ssl://{$smtp_config['host']}:{$smtp_config['port']}",
        $errno, $errstr, $timeout,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]])
    );
    if (!$smtp) return "Connection Failed: $errstr ($errno)";
    stream_set_timeout($smtp, $timeout);
    $check = function($s, $expected) {
        $r = '';
        while ($line = fgets($s, 515)) {
            $r .= $line;
            if (isset($line[3]) && $line[3] == ' ') break;
        }
        $code = substr($r, 0, 3);
        return (in_array($code, (array)$expected)) ? true : $r;
    };
    if (($r = $check($smtp, '220')) !== true) return "Banner: $r";
    fputs($smtp, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
    if (($r = $check($smtp, '250')) !== true) return "EHLO: $r";
    fputs($smtp, "AUTH LOGIN\r\n");
    if (($r = $check($smtp, '334')) !== true) return "AUTH: $r";
    fputs($smtp, base64_encode($smtp_config['username']) . "\r\n");
    if (($r = $check($smtp, '334')) !== true) return "USER: $r";
    fputs($smtp, base64_encode($smtp_config['password']) . "\r\n");
    if (($r = $check($smtp, '235')) !== true) return "PASS: $r";
    fputs($smtp, "MAIL FROM: <$fromEmail>\r\n");
    if (($r = $check($smtp, '250')) !== true) return "MAIL: $r";
    fputs($smtp, "RCPT TO: <" . trim($to) . ">\r\n");
    if (($r = $check($smtp, '250')) !== true) return "RCPT: $r";
    fputs($smtp, "DATA\r\n");
    if (($r = $check($smtp, '354')) !== true) return "DATA: $r";
    $emailData  = "Date: " . date('r') . "\r\n";
    $emailData .= "To: <" . trim($to) . ">\r\n";
    $emailData .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $emailData .= $headers;
    $emailData .= "Message-ID: <" . md5(uniqid()) . "@" . ($smtp_config['host'] ?? 'localhost') . ">\r\n";
    $emailData .= "\r\n";
    $emailData .= $message;
    $emailData .= "\r\n.\r\n";
    fputs($smtp, $emailData);
    if (($r = $check($smtp, '250')) !== true) return "Final: $r";
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    return 'success';
}

function build_html_email($from_name, $subject, $body_html) {
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$subject}</title>
<style>
    body { margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
    .wrapper { width:100%; table-layout:fixed; background-color:#f1f5f9; padding:40px 0; }
    .card { width:100%; max-width:600px; background-color:#ffffff; margin:0 auto; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); }
    .accent { height:4px; background-color:#2563eb; }
    .content { padding:40px; color:#1e293b; line-height:1.7; font-size:16px; text-align:left; }
    .footer { padding:24px; text-align:center; color:#64748b; font-size:12px; border-top:1px solid #f1f5f9; background-color:#fafafa; }
</style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="accent"></div>
            <div class="content">
                {$body_html}
            </div>
            <div class="footer">
                © {$year} {$from_name}. All rights reserved.<br>
                <span style="color:#cbd5e1; font-size:10px; margin-top:8px; display:block;">Sent via BMS Mailer Premium</span>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}
