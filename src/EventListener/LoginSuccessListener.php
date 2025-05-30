<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class LoginSuccessListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            $user->setLastLogin(new \DateTime());
            $this->em->flush();

            // Добавление данных пользователя в ответ
            $event->setData(array_merge($event->getData(), [
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'isBlocked' => $user->isBlocked(),
                    'name' => $user->getName(),
                    'last_login' => $user -> getLastLogin(),
                ]
            ]));
        }
    }
}
