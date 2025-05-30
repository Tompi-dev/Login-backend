<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Event\BlockingUserCheckEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/users', name: 'user_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findBy([], ['lastLogin' => 'DESC']);

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                'lastLogin' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
                'status' => $user->isBlocked() ? 'blocked' : 'active',
            ];
        }, $users);

        return new JsonResponse($data);
    }

    #[Route('/users/block', name: 'user_block', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]

public function block(Request $request, UserRepository $userRepository, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $targetIds = $data['target_user_ids'] ?? [];

    /** @var User $authUser */
    $authUser = $this->getUser();
    $userId = $authUser->getId();

 

    // if (in_array($userId, $targetIds)) {
    //     return new JsonResponse(['error' => 'You cannot block yourself.'], 400);
    // }
    if ($authUser->isBlocked()) {
    return new JsonResponse(['error' => 'Blocked users cannot perform this action'], 403);
}
    $dispatcher->dispatch(new BlockingUserCheckEvent($userId));

    $blockedIds = [];

    foreach ($targetIds as $id) {
       

        $user = $userRepository->find($id);
        if ($user) {
            $user->setIsBlocked(true);
            $blockedIds[] = $id;
        }
    }

    $em->flush();

    return new JsonResponse([
        'status' => 'Users blocked',
        'blocked_by' => $userId,
        'blocked_users' => $blockedIds,
    ]);
}


    #[Route('/users/unblock', name: 'user_unblock', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
public function unblock(
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $em,
    EventDispatcherInterface $dispatcher
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $targetIds = $data['target_user_ids'] ?? [];

   
    $authUser = $this->getUser();

    if (!$authUser) {
        return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    $userId = $authUser->getId();

   
    $dispatcher->dispatch(new BlockingUserCheckEvent($userId));

    $unblockedIds = [];

    foreach ($targetIds as $id) {
        $user = $userRepository->find($id);
        if ($user) {
            $user->setIsBlocked(false);
            $unblockedIds[] = $id;
        }
    }

    $em->flush();

    return new JsonResponse([
        'status' => 'Users Unblocked',
        'unblocked_by' => $userId,
        'unblocked_users' => $unblockedIds,
    ]);
}



    #[Route('/users/delete', name: 'user_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]

public function delete(Request $request, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $ids = $data['ids'] ?? [];

    if (!is_array($ids)) {
        return new JsonResponse(['error' => 'Invalid request: ids must be array'], 400);
    }

    /** @var User $authUser */
    $authUser = $this->getUser();
    $userId = $authUser->getId();

    // if (in_array($userId, $ids)) {
    //     return new JsonResponse(['error' => 'You cannot delete yourself.'], 400);
    // }

    foreach ($ids as $id) {
        $user = $userRepository->find($id);
        if ($user) {
            $em->remove($user);
        }
    }

    $em->flush();
    return new JsonResponse(['status' => 'Users deleted', 'deleted' => $ids]);
}}
