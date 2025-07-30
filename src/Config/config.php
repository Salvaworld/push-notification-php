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
        'certificate' => __DIR__ . '/iosCertificates/apns-dev-cert.pem',
        'passPhrase' => 'secret', //Optional
        'passFile' => __DIR__ . '/iosCertificates/yourKey.pem', //Optional
        
        // Auth Key-based authentication (recommended)
        'auth_key' => __DIR__ . '/iosAuthKeys/apns-dev-key.p8', // Path to .p8 file
        'key_id' => '2222222222', // Key ID from Apple Developer Account
        'team_id' => '3333333333', // Team ID from Apple Developer Account
        'bundle_id' => 'com.salvaworld.app', // Your app's bundle identifier
        
        'dry_run' => true,
        'use_auth_key' => true, // Set to true to use auth key, false for certificate
    ],
];
