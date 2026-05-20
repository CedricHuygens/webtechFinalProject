<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Friend;
use App\Entity\PadelMatch;
use App\Entity\PadelMatchComment;
use App\Entity\PadelMatchCommentLike;
use App\Entity\PadelMatchLike;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private User $currentUser;
    private User $friendUser;
    private User $secondFriendUser;
    private User $opponentOne;
    private User $opponentTwo;
    private User $hiddenUserOne;
    private User $hiddenUserTwo;

    private Team $teamWithFriend;
    private Team $opponentTeam;
    private Team $hiddenTeamOne;
    private Team $hiddenTeamTwo;

    private PadelMatch $visibleMatch;
    private PadelMatch $hiddenMatch;

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

        $this->friendUser = $this->createUser(
            'friend@example.com',
            'frienduser',
            'Friend',
            'User'
        );

        $this->secondFriendUser = $this->createUser(
            'secondfriend@example.com',
            'secondfrienduser',
            'Second',
            'Friend'
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

        $this->hiddenUserOne = $this->createUser(
            'hidden1@example.com',
            'hiddenone',
            'Hidden',
            'One'
        );

        $this->hiddenUserTwo = $this->createUser(
            'hidden2@example.com',
            'hiddentwo',
            'Hidden',
            'Two'
        );

        $this->createFriendLink($this->currentUser, $this->friendUser);
        $this->createFriendLink($this->currentUser, $this->secondFriendUser);

        $this->teamWithFriend = $this->createTeam(
            'Current Friend Team',
            $this->currentUser,
            $this->friendUser
        );

        $this->opponentTeam = $this->createTeam(
            'Opponent Team',
            $this->opponentOne,
            $this->opponentTwo
        );

        $this->hiddenTeamOne = $this->createTeam(
            'Hidden Team One',
            $this->hiddenUserOne,
            $this->opponentOne
        );

        $this->hiddenTeamTwo = $this->createTeam(
            'Hidden Team Two',
            $this->hiddenUserTwo,
            $this->opponentTwo
        );

        $this->visibleMatch = $this->createMatch(
            $this->teamWithFriend,
            $this->opponentTeam,
            6,
            4
        );

        $this->hiddenMatch = $this->createMatch(
            $this->hiddenTeamOne,
            $this->hiddenTeamTwo,
            2,
            6
        );

        $this->entityManager->flush();
    }

    public function testFeedRedirectsToLoginWhenUserIsNotLoggedIn(): void
    {
        $this->client->request('GET', '/feed');

        self::assertResponseRedirects('/login');
    }

    public function testFeedShowsOnlyMatchesWhereAFriendParticipates(): void
    {
        $this->loginCurrentUser();

        $this->client->request('GET', '/feed');

        self::assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Matches', $content);
        self::assertStringContainsString('Current Friend Team', $content);
        self::assertStringContainsString('Opponent Team', $content);

        self::assertStringNotContainsString('Hidden Team One', $content);
        self::assertStringNotContainsString('Hidden Team Two', $content);
    }

    public function testAddPlayedMatchPageIsDisplayed(): void
    {
        $this->loginCurrentUser();

        $this->client->request('GET', '/match/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Add Played Match');

        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Start time', $content);
        self::assertStringContainsString('End time', $content);
        self::assertStringContainsString('Team Red', $content);
        self::assertStringContainsString('Team Blue', $content);
        self::assertStringContainsString('Save Match', $content);
        self::assertStringContainsString('Back', $content);
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

    public function testTeamMateRequestPageIsDisplayed(): void
    {
        $this->loginCurrentUser();

        $this->client->request('GET', '/team-mate-request/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Team Mate Request');

        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Team name', $content);
        self::assertStringContainsString('Friend', $content);
        self::assertStringContainsString('Send Request', $content);
        self::assertStringContainsString('Back', $content);
    }

    public function testTeamMateRequestCreatesNewTeam(): void
    {
        $this->loginCurrentUser();

        $this->client->request('POST', '/team-mate-request/add', [
            'team_name' => 'New Test Team',
            'friend_id' => $this->secondFriendUser->getId(),
        ]);

        self::assertResponseRedirects('/feed');

        $createdTeam = $this->entityManager
            ->getRepository(Team::class)
            ->findOneBy([
                'name' => 'New Test Team',
            ]);

        self::assertNotNull($createdTeam);
        self::assertSame($this->currentUser->getId(), $createdTeam->getUser1()?->getId());
        self::assertSame($this->secondFriendUser->getId(), $createdTeam->getUser2()?->getId());
    }

    public function testLikeMatchAddsAndRemovesLike(): void
    {
        $this->loginCurrentUser();

        $this->client->request('POST', '/match/' . $this->visibleMatch->getId() . '/like');

        self::assertResponseRedirects('/feed');

        $createdLike = $this->entityManager
            ->getRepository(PadelMatchLike::class)
            ->findOneBy([
                'padelMatch' => $this->visibleMatch,
                'user' => $this->currentUser,
            ]);

        self::assertNotNull($createdLike);

        $this->client->request('POST', '/match/' . $this->visibleMatch->getId() . '/like');

        self::assertResponseRedirects('/feed');

        $removedLike = $this->entityManager
            ->getRepository(PadelMatchLike::class)
            ->findOneBy([
                'padelMatch' => $this->visibleMatch,
                'user' => $this->currentUser,
            ]);

        self::assertNull($removedLike);
    }

    public function testAddCommentAndLikeComment(): void
    {
        $this->loginCurrentUser();

        $this->client->request('POST', '/match/' . $this->visibleMatch->getId() . '/comment', [
            'content' => 'Great match!',
        ]);

        self::assertResponseRedirects('/feed');

        $comment = $this->entityManager
            ->getRepository(PadelMatchComment::class)
            ->findOneBy([
                'padelMatch' => $this->visibleMatch,
                'user' => $this->currentUser,
                'content' => 'Great match!',
            ]);

        self::assertNotNull($comment);

        $this->client->request('POST', '/comment/' . $comment->getId() . '/like');

        self::assertResponseRedirects('/feed');

        $commentLike = $this->entityManager
            ->getRepository(PadelMatchCommentLike::class)
            ->findOneBy([
                'comment' => $comment,
                'user' => $this->currentUser,
            ]);

        self::assertNotNull($commentLike);

        $this->client->request('POST', '/comment/' . $comment->getId() . '/like');

        self::assertResponseRedirects('/feed');

        $removedCommentLike = $this->entityManager
            ->getRepository(PadelMatchCommentLike::class)
            ->findOneBy([
                'comment' => $comment,
                'user' => $this->currentUser,
            ]);

        self::assertNull($removedCommentLike);
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

    private function createFriendLink(User $user1, User $user2): Friend
    {
        $friend = new Friend();
        $friend->setUser1($user1);
        $friend->setUser2($user2);

        $this->entityManager->persist($friend);

        return $friend;
    }

    private function createTeam(string $name, User $user1, User $user2): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setUser1($user1);
        $team->setUser2($user2);

        $this->entityManager->persist($team);

        return $team;
    }

    private function createMatch(
        Team $team1,
        Team $team2,
        int $team1Score,
        int $team2Score
    ): PadelMatch {
        $match = new PadelMatch();
        $match->setStartTime(new \DateTime('2026-05-07 10:00:00'));
        $match->setEndTime(new \DateTime('2026-05-07 11:00:00'));
        $match->setTeam1($team1);
        $match->setTeam2($team2);
        $match->setTeam1Score($team1Score);
        $match->setTeam2Score($team2Score);

        $this->entityManager->persist($match);

        return $match;
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
