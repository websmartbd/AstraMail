<?php
/**
 * config.php — Central configuration loader
 * This ensures credentials are only defined in one place.
 */

function get_mailer_config() {
    $settings_file = __DIR__ . '/../storage/settings.json';
    $defaults = [
        'host'       => 'mail.example.com',
        'username'   => 'your-email',
        'password'   => 'your-password',
        'port'       => 465,
        'encryption' => 'ssl',
        'from_email' => 'your-email',
        'from_name'  => 'AstraMail',
        '_secret'    => 'bms_mailer_2026', // API Token
        '_password'  => '1234567'         // Dashboard Password
    ];

    $stored = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
    $config = array_merge($defaults, is_array($stored) ? $stored : []);

    // 🤖 AUTO-SENSE & PERSIST: If URL is missing, detect and save it (only in browser)
    if (empty($config['app_url']) && isset($_SERVER['HTTP_HOST'])) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Get the directory path of the current script
        $base_dir = dirname($script_name);
        // Normalize to ensure it ends with / and doesn't have double slashes
        $base_dir = rtrim(str_replace('\\', '/', $base_dir), '/') . '/';
        
        $config['app_url'] = "$protocol://$host" . $base_dir;
        
        // Save it back to settings so Cron/CLI can see it
        $to_save = array_merge($stored ?: [], ['app_url' => $config['app_url']]);
        file_put_contents($settings_file, json_encode($to_save, JSON_PRETTY_PRINT));
    }

    return $config;
}
