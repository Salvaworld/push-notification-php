<?php
/**
 * @see https://github.com/SalvaWorld/PushNotification
 */

return [
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'My_ApiKey',
        'guzzle' => [],
    ],
    'apn' => [
        'auth_key' => __DIR__ . '/iosAuthKeys/apns-dev-key.p8', // Path to .p8 file
        'key_id' => '2222222222', // Key ID from Apple Developer Account
        'team_id' => '3333333333', // Team ID from Apple Developer Account
        'bundle_id' => 'com.salvaworld.app', // Your app's bundle identifier
        'dry_run' => true,
    ],
];
