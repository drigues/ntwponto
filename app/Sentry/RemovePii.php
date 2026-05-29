<?php

namespace App\Sentry;

use Sentry\Event;
use Sentry\EventHint;

class RemovePii
{
    public static function handle(Event $event, ?EventHint $hint = null): ?Event
    {
        if ($user = $event->getUser()) {
            $user->setEmail(null);
            $user->setIpAddress(null);
        }

        return $event;
    }
}
