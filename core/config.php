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

    if (!file_exists($settings_file)) {
        return $defaults;
    }

    $stored = json_decode(file_get_contents($settings_file), true);
    return is_array($stored) ? array_merge($defaults, $stored) : $defaults;
}
