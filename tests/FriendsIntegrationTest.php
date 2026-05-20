<?php

use App\Entity\User;
use App\Entity\Friend;
use App\Repository\FriendRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FriendsIntegrationTest extends WebTestCase
{
    public function testPageLoad(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('user_'.uniqid());
        $user->setEmail('user_'.uniqid().'@test.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/friends');

        $this->assertResponseIsSuccessful();


        $link = $crawler->selectLink('Manage Friendrequests')->link();
        $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('friendrequests');

    }

    public function testDeleteFriend(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('user_'.uniqid());
        $user->setEmail('user_'.uniqid().'@test.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);

        $user1 = new User();
        $user1->setFirstName('Test');
        $user1->setLastName('User');
        $user1->setUsername('user_'.uniqid());
        $user1->setEmail('user_'.uniqid().'@test.com');
        $user1->setPassword('test123');
        $user1->setBirthDate(new \DateTime('2000-01-01'));
        $user1->setRoles(['ROLE_USER']);

        $entityManager->persist($user1);

        $friend = new Friend();
        $friend->setUser1($user);
        $friend->setUser2($user1);

        $entityManager->persist($friend);

        $entityManager->flush();

        $client->loginUser($user);


        $client->jsonRequest('POST', '/deleteFriend', [
            'userId' => $user1->getId(),
        ]);


        $friendRepository = $entityManager->getRepository(Friend::class);

        $this->assertResponseIsSuccessful();
        $this->assertNull($friendRepository->findFriend($user,$user1));
    }
}
