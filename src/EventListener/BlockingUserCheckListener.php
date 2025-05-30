<?php

namespace App\EventListener;

use App\Event\BlockingUserCheckEvent;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class BlockingUserCheckListener
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[AsEventListener(event: BlockingUserCheckEvent::class)]
    public function onBlockingUserCheck(BlockingUserCheckEvent $event): void

    {

      if ($event->getUserId() === null) {
            throw new AccessDeniedHttpException('Blocking user not found.');
        }
        
        $user = $this->userRepository->find($event->getUserId());

        if (!$user) {
            throw new AccessDeniedHttpException('Blocking user not found.');
        }

        if ($user->isBlocked()) {
            throw new AccessDeniedHttpException('Blocked users cannot perform this action.');
        }
    }
}
