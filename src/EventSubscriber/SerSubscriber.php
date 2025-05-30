<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SerSubscriber implements EventSubscriberInterface
{
    public function onBlockedUserSubscriber($event): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'BlockedUserSubscriber' => 'onBlockedUserSubscriber',
        ];
    }
}
