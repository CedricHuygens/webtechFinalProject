<?php

namespace App\Tests;

use App\Entity\Club;
use App\Entity\ClubUser;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClubControllerIntegrationTest extends WebTestCase
{
    private function createUser(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('club_user_' . uniqid());
        $user->setEmail('club_user_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createAdmin(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername('club_admin_' . uniqid());
        $user->setEmail('club_admin_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createClub(User $admin): Club
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $club = new Club();
        $club->setName('Test Club');
        $club->setUser($admin);

        $entityManager->persist($club);
        $entityManager->flush();

        return $club;
    }

    private function createClubUser(User $user, Club $club): ClubUser
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $clubUser = new ClubUser();
        $clubUser->setUser($user);
        $clubUser->setClub($club);
        $clubUser->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($clubUser);
        $entityManager->flush();

        return $clubUser;
    }

    public function testUserCanViewClubPage(): void
    {
        $client = static::createClient();

        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('GET', '/club');

        $this->assertResponseIsSuccessful();
    }

    public function testUserCanEnrollInClub(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('POST', '/club/enroll/' . $club->getId()); // user will enrollen

        $this->assertResponseRedirects('/club'); // check of je op club pagina blijft

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $clubUser = $entityManager
            ->getRepository(ClubUser::class)
            ->findOneBy([
                'user' => $user,
                'club' => $club,
            ]);

        $this->assertNotNull($clubUser); // mag niet null zijn want user behoort nu tot die club
    }

    public function testUserCanLeaveClub(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $user = $this->createUser();

        // user zit al in club
        $clubUser = $this->createClubUser($user, $club);

        $clubUserId = $clubUser->getId();

        $client->loginUser($user);

        $client->request('POST', '/club/leave');

        $this->assertResponseRedirects('/club');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $deletedClubUser = $entityManager
            ->getRepository(ClubUser::class)
            ->find($clubUserId);

        $this->assertNull($deletedClubUser); // user niet terugvinden in clubusertabel
    }

    public function testAdminCanRemoveUserFromClub(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $user = $this->createUser();
        $clubUser = $this->createClubUser($user, $club);
        $clubUserId = $clubUser->getId();

        $client->loginUser($admin);

        $client->request('POST', '/admin/club/remove-user/' . $clubUserId);

        $this->assertResponseRedirects('/club');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $deletedClubUser = $entityManager
            ->getRepository(ClubUser::class)
            ->find($clubUserId);

        $this->assertNull($deletedClubUser);
    }

    public function testAdminCanViewClubPageWithOwnClub(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('GET', '/club');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $club->getName());
    }

    public function testAdminWithoutClubCanViewClubPage(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();

        $client->loginUser($admin);

        $client->request('GET', '/club');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanViewClubUsersPage(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('GET', '/admin/club/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.main-title', 'Users - Test Club');
    }
}
