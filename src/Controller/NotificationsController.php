<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationsController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $notifications = $entityManager
            ->getRepository(Notification::class)
            ->findBy(
                ['user' => $currentUser],
                ['createdAt' => 'DESC']
            );

        $friendrequestsReceived = $entityManager
            ->getRepository(FriendRequest::class)
            ->findBy(
                ['receiverUser' => $currentUser],
                ['id' => 'DESC']
            );

        $currentUserName = trim(
            ($currentUser->getFirstName() ?? '') . ' ' . ($currentUser->getLastName() ?? '')
        );

        if ($currentUserName === '') {
            $currentUserName = $currentUser->getUsername() ?? 'User ' . $currentUser->getId();
        }

        return $this->render('notifications.html.twig', [
            'notifications' => $notifications,
            'friendrequestsReceived' => $friendrequestsReceived,
            'currentUserName' => $currentUserName,
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $notification = $entityManager
            ->getRepository(Notification::class)
            ->find($id);

        if (
            !$notification ||
            !$notification->getUser() ||
            $notification->getUser()->getId() !== $currentUser->getId()
        ) {
            return $this->redirectToRoute('app_notifications');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/notifications/read-all', name: 'app_notifications_read_all', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $notifications = $entityManager
            ->getRepository(Notification::class)
            ->findBy([
                'user' => $currentUser,
                'isRead' => false,
            ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/notifications/{id}/delete', name: 'app_notification_delete', methods: ['POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $notification = $entityManager
            ->getRepository(Notification::class)
            ->find($id);

        if (
            !$notification ||
            !$notification->getUser() ||
            $notification->getUser()->getId() !== $currentUser->getId()
        ) {
            return $this->redirectToRoute('app_notifications');
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        return $this->redirectToRoute('app_notifications');
    }
}
