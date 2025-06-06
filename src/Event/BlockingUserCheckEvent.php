<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BlockingUserCheckEvent extends Event
{
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId; 

    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
