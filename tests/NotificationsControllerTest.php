<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationsControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private User $currentUser;
    private User $otherUser;

    private Notification $currentUserUnreadNotification;
    private Notification $currentUserReadNotification;
    private Notification $otherUserNotification;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->clearDatabase();

        $this->currentUser = $this->createUser(
            'current-notifications@example.com',
            'currentnotifications',
            'Current',
            'User'
        );

        $this->otherUser = $this->createUser(
            'other-notifications@example.com',
            'othernotifications',
            'Other',
            'User'
        );

        $this->currentUserUnreadNotification = $this->createNotification(
            $this->currentUser,
            'like',
            'Someone liked your match.',
            false,
            new \DateTimeImmutable('2026-05-10 12:00:00')
        );

        $this->currentUserReadNotification = $this->createNotification(
            $this->currentUser,
            'comment',
            'Someone commented on your match.',
            true,
            new \DateTimeImmutable('2026-05-09 12:00:00')
        );

        $this->otherUserNotification = $this->createNotification(
            $this->otherUser,
            'like',
            'This notification belongs to another user.',
            false,
            new \DateTimeImmutable('2026-05-08 12:00:00')
        );

        $this->entityManager->flush();
    }

    public function testNotificationsPageShowsOnlyNotificationsOfCurrentUser(): void
    {
        $this->loginCurrentUser();

        $this->client->request('GET', '/notifications');

        self::assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Current User', $content);
        self::assertStringContainsString('Someone liked your match.', $content);
        self::assertStringContainsString('Someone commented on your match.', $content);

        self::assertStringNotContainsString('This notification belongs to another user.', $content);
    }

    public function testMarkNotificationAsRead(): void
    {
        $this->loginCurrentUser();

        $notificationId = $this->currentUserUnreadNotification->getId();

        $this->client->request('POST', '/notifications/' . $notificationId . '/read');

        self::assertResponseRedirects('/notifications');

        $this->entityManager->clear();

        $notification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($notificationId);

        self::assertNotNull($notification);
        self::assertTrue($notification->isRead());
    }

    public function testCurrentUserCannotMarkOtherUsersNotificationAsRead(): void
    {
        $this->loginCurrentUser();

        $notificationId = $this->otherUserNotification->getId();

        $this->client->request('POST', '/notifications/' . $notificationId . '/read');

        self::assertResponseRedirects('/notifications');

        $this->entityManager->clear();

        $notification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($notificationId);

        self::assertNotNull($notification);
        self::assertFalse($notification->isRead());
    }

    public function testMarkAllAsReadOnlyMarksCurrentUsersNotifications(): void
    {
        $this->loginCurrentUser();

        $currentUserUnreadNotificationId = $this->currentUserUnreadNotification->getId();
        $currentUserReadNotificationId = $this->currentUserReadNotification->getId();
        $otherUserNotificationId = $this->otherUserNotification->getId();

        $this->client->request('POST', '/notifications/read-all');

        self::assertResponseRedirects('/notifications');

        $this->entityManager->clear();

        $currentUserUnreadNotification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($currentUserUnreadNotificationId);

        $currentUserReadNotification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($currentUserReadNotificationId);

        $otherUserNotification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($otherUserNotificationId);

        self::assertNotNull($currentUserUnreadNotification);
        self::assertNotNull($currentUserReadNotification);
        self::assertNotNull($otherUserNotification);

        self::assertTrue($currentUserUnreadNotification->isRead());
        self::assertTrue($currentUserReadNotification->isRead());

        self::assertFalse($otherUserNotification->isRead());
    }

    public function testDeleteNotification(): void
    {
        $this->loginCurrentUser();

        $notificationId = $this->currentUserUnreadNotification->getId();

        $this->client->request('POST', '/notifications/' . $notificationId . '/delete');

        self::assertResponseRedirects('/notifications');

        $this->entityManager->clear();

        $deletedNotification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($notificationId);

        self::assertNull($deletedNotification);
    }

    public function testCurrentUserCannotDeleteOtherUsersNotification(): void
    {
        $this->loginCurrentUser();

        $notificationId = $this->otherUserNotification->getId();

        $this->client->request('POST', '/notifications/' . $notificationId . '/delete');

        self::assertResponseRedirects('/notifications');

        $this->entityManager->clear();

        $notification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($notificationId);

        self::assertNotNull($notification);
    }

    private function loginCurrentUser(): void
    {
        $this->client->loginUser($this->currentUser, 'main');
    }

    private function createUser(
        string $email,
        string $username,
        string $firstName,
        string $lastName
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword('test-password');
        $user->setBirthDate(new \DateTime('2000-01-01'));

        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }

        $this->entityManager->persist($user);

        return $user;
    }

    private function createNotification(
        User $user,
        string $type,
        string $message,
        bool $isRead,
        \DateTimeImmutable $createdAt
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setIsRead($isRead);
        $notification->setCreatedAt($createdAt);

        $this->entityManager->persist($notification);

        return $notification;
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        $isSqlite = $connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            $connection->executeStatement('PRAGMA foreign_keys = OFF');
        } else {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        }

        $tables = [
            'padel_match_comment_like',
            'padel_match_like',
            'padel_match_comment',
            'padel_match_player',
            'notification',
            'padel_match',
            'team',
            'friend',
            'field',
            'club',
            'user',
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement(
                    'DELETE FROM ' . $connection->quoteSingleIdentifier($table)
                );
            } catch (\Throwable) {
                // Ignore tables that do not exist.
            }
        }

        if ($isSqlite) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        } else {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
