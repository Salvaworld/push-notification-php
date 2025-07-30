<?php

namespace SalvaWorld\PushNotification\Channels;

use SalvaWorld\PushNotification\Messages\PushMessage;

class ApnChannel extends PushChannel {
    /**
     * {@inheritdoc}
     */
    protected function pushServiceName() {
        return 'apn';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildData(PushMessage $message) {
        $data = [
            'aps' => [
                'alert' => [
                    'title' => $message->title,
                    'body' => $message->body,
                ],
                'category' => $message->category,
                'sound' => $message->sound,
            ],
        ];

        if (!empty($message->extra)) {
            $data['extraPayLoad'] = $message->extra;
        }

        if (is_numeric($message->badge)) {
            $data['aps']['badge'] = $message->badge;
        }

        // Add headers for auth key method if needed
        $headers = [];
        
        if (!empty($message->topic)) {
            $headers['apns-topic'] = $message->topic;
        }
        
        // Add priority header if specified
        if (property_exists($message, 'priority') && !empty($message->priority)) {
            $headers['apns-priority'] = $message->priority;
        }
        
        // Add expiration header if specified
        if (property_exists($message, 'expiration') && !empty($message->expiration)) {
            $headers['apns-expiration'] = $message->expiration;
        }
        
        if (!empty($headers)) {
            $data['headers'] = $headers;
        }

        return $data;
    }
}