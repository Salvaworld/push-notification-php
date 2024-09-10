<?php

namespace SalvaWorld\PushNotification\Channels;

use SalvaWorld\PushNotification\Messages\PushMessage;

class FcmChannel extends PushChannel {
    /**
     * {@inheritdoc}
     */
    protected function pushServiceName() {
        return 'fcm';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildData(PushMessage $message) {
        $data = [];
        if ($message->title != null || $message->body != null || $message->click_action != null) {
            $data = [
                'message' => [
                    'topic' => $message->topic ?? '',
                    'notification' => [
                        'title' => $message->title,
                        'body' => $message->body,
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => $message->sound ?? 'default', // Default sound if not set
                            'color' => $message->color ?? '#ffffff', // Default white color if not set
                            'click_action' => $message->click_action,
                            'icon' => $message->icon ?? 'default', // Default icon if not set
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $message->title,
                                    'body' => $message->body,
                                ],
                                'sound' => $message->sound ?? 'default', // Default sound
                                'badge' => $message->badge ?? 1, // Badge number if not set
                            ],
                        ],
                    ],
                    'webpush' => [
                        'notification' => [
                            'title' => $message->title,
                            'body' => $message->body,
                            'icon' => $message->icon ?? 'default',
                            'click_action' => $message->click_action,
                        ],
                    ],
                ],
            ];
        }

        if (!empty($message->extra)) {
            $data = [];
            if (!empty($message->extra)) {
                foreach($message->extra as $key => $value) {
                    $data[$key] = (string)$value;
                }
            }
            $data['message']['data'] = $data;
        }

        return $data;
    }
}
