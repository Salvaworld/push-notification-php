<?php

namespace SalvaWorld\PushNotification\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SalvaWorld\PushNotification\PushNotification;

class NotificationPushed {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \SalvaWorld\PushNotification\PushNotification
     */
    public $push;

    /**
     * Create a new event instance.
     *
     * @param  \SalvaWorld\PushNotification\PushNotification $push
     */
    public function __construct(PushNotification $push) {
        $this->push = $push;
    }
}
