<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MatchRequestControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private User $currentUser;
    private User $partner;
    private User $opponentOne;
    private User $opponentTwo;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->clearDatabase();

        $this->currentUser = $this->createUser(
            'current@example.com',
            'currentuser',
            'Current',
            'User'
        );

        $this->partner = $this->createUser(
            'partner@example.com',
            'partneruser',
            'Partner',
            'User'
        );

        $this->opponentOne = $this->createUser(
            'opponent1@example.com',
            'opponentone',
            'Opponent',
            'One'
        );

        $this->opponentTwo = $this->createUser(
            'opponent2@example.com',
            'opponenttwo',
            'Opponent',
            'Two'
        );

        $this->entityManager->flush();
    }

    public function testMatchRequestRedirectsToLoginWhenUserIsNotLoggedIn(): void
    {
        $this->client->request('GET', '/match-request/add');

        self::assertResponseRedirects('/login');
    }

    public function testMatchRequestPageIsDisplayed(): void
    {
        $this->loginCurrentUser();

        $this->client->request('GET', '/match-request/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Match Request');

        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Partner', $content);
        self::assertStringContainsString('Opponent 1', $content);
        self::assertStringContainsString('Opponent 2', $content);
        self::assertStringContainsString('Date', $content);
        self::assertStringContainsString('Send Request', $content);
        self::assertStringContainsString('Back', $content);
    }

    public function testMatchRequestWithInvalidDataRedirectsBackAndCreatesNoNotification(): void
    {
        $this->loginCurrentUser();

        $this->client->request('POST', '/match-request/add', [
            'partner' => $this->partner->getId(),
            'opponent1' => $this->opponentOne->getId(),
            'opponent2' => $this->opponentTwo->getId(),
            'field_id' => '',
            'date' => '',
        ]);

        self::assertResponseRedirects('/match-request/add');

        $notifications = $this->entityManager
            ->getRepository(Notification::class)
            ->findBy([
                'type' => 'match_request',
            ]);

        self::assertCount(0, $notifications);
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
                    'DELETE FROM ' . $connection->quoteIdentifier($table)
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
