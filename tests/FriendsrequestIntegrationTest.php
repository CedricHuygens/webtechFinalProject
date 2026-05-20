<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Friend;
use App\Entity\FriendRequest;
use App\Repository\UserRepository;
use App\Repository\FriendRepository;
use App\Repository\FriendRequestRepository;

class FriendsrequestIntegrationTest extends WebTestCase
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

        $crawler = $client->request('GET', '/friendrequests');

        $this->assertResponseIsSuccessful();
    }

    public function testReceivedRequests(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $receiver = new User();
        $receiver->setFirstName('Receiver');
        $receiver->setLastName('User');
        $receiver->setUsername('receiver_' . uniqid());
        $receiver->setEmail('receiver_' . uniqid() . '@test.com');
        $receiver->setPassword('test123');
        $receiver->setBirthDate(new \DateTime('2000-01-01'));
        $receiver->setRoles(['ROLE_USER']);
        $receiver->setIsActive(true);

        $sender = new User();
        $sender->setFirstName('Sender');
        $sender->setLastName('User');
        $sender->setUsername('sender_' . uniqid());
        $sender->setEmail('sender_' . uniqid() . '@test.com');
        $sender->setPassword('test123');
        $sender->setBirthDate(new \DateTime('2000-01-01'));
        $sender->setRoles(['ROLE_USER']);
        $sender->setIsActive(true);

        $entityManager->persist($receiver);
        $entityManager->persist($sender);

        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($sender);
        $friendRequest->setReceiverUser($receiver);
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($friendRequest);
        $entityManager->flush();

        $client->loginUser($receiver);

        $crawler = $client->request('GET', '/friendrequests');
        $this->assertResponseIsSuccessful();

        // verify that the sender username is shown on page, selector is the part we are looking at (here part with id=receivedRequests)
        $this->assertSelectorTextContains('#receivedRequests', $sender->getUsername());
    }

    public function testSentRequests(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $receiver = new User();
        $receiver->setFirstName('Receiver');
        $receiver->setLastName('User');
        $receiver->setUsername('receiver_' . uniqid());
        $receiver->setEmail('receiver_' . uniqid() . '@test.com');
        $receiver->setPassword('test123');
        $receiver->setBirthDate(new \DateTime('2000-01-01'));
        $receiver->setRoles(['ROLE_USER']);
        $receiver->setIsActive(true);

        $sender = new User();
        $sender->setFirstName('Sender');
        $sender->setLastName('User');
        $sender->setUsername('sender_' . uniqid());
        $sender->setEmail('sender_' . uniqid() . '@test.com');
        $sender->setPassword('test123');
        $sender->setBirthDate(new \DateTime('2000-01-01'));
        $sender->setRoles(['ROLE_USER']);
        $sender->setIsActive(true);

        $entityManager->persist($receiver);
        $entityManager->persist($sender);

        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($sender);
        $friendRequest->setReceiverUser($receiver);
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($friendRequest);
        $entityManager->flush();

        $client->loginUser($sender);

        $crawler = $client->request('GET', '/friendrequests');
        $this->assertResponseIsSuccessful();

        // verify that the receiver username is shown on page
        $this->assertSelectorTextContains('#sentRequests', $receiver->getUsername());
    }

    public function testFindNewFriends(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $receiver = new User();
        $receiver->setFirstName('Receiver');
        $receiver->setLastName('User');
        $receiver->setUsername('receiver_' .uniqid());
        $receiver->setEmail('receiver_'.uniqid().'@test.com');
        $receiver->setPassword('test123');
        $receiver->setBirthDate(new \DateTime('2000-01-01'));
        $receiver->setRoles(['ROLE_USER']);
        $receiver->setIsActive(true);

        $sender = new User();
        $sender->setFirstName('Sender');
        $sender->setLastName('User');
        $sender->setUsername('sender_'.uniqid());
        $sender->setEmail('sender_'.uniqid() . '@test.com');
        $sender->setPassword('test123');
        $sender->setBirthDate(new \DateTime('2000-01-01'));
        $sender->setRoles(['ROLE_USER']);
        $sender->setIsActive(true);

        $entityManager->persist($receiver);
        $entityManager->persist($sender);

        $entityManager->flush();

        $client->loginUser($sender);

        $crawler = $client->request('GET', '/friendrequests');
        $this->assertResponseIsSuccessful();

        $results = $userRepository->findPotentialFriendsByUsername($receiver->getUsername(), $receiver);
        $this->assertCount(0, $results); // O the current logged in user is not shown

        $results = $userRepository->findPotentialFriendsByUsername($receiver->getUsername(), $sender);
        $this->assertCount(1, $results); // valid case


        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($sender);
        $friendRequest->setReceiverUser($receiver);
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($friendRequest);
        $entityManager->flush();

        $results = $userRepository->findPotentialFriendsByUsername($sender->getUsername(), $receiver);
        $this->assertCount(0, $results); // 0 because the sender already sent a friend request and then you can not add them
    }


    public function testSearchFriends(): void
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

        $entityManager->flush();

        $client->loginUser($user);

        $client->request('GET', '/searchFriends?q='.$user1->getUsername());

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true); // get body of response (/searchFriend returns JSON)
        $this->assertStringContainsString($user1->getUsername(), $data['html']); // $data['html'] returns the template and it must contain the username
    }

    public function testSentFriendRequests(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $friendRequestRepository = static::getContainer()->get(FriendRequestRepository::class);

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

        $entityManager->flush();

        $client->loginUser($user);


        $client->jsonRequest('POST', '/sendFriendRequest', [
            'receiverUserId' => $user1->getId()
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertNotNull($friendRequestRepository->findOneBy(['senderUser' => $user, 'receiverUser' => $user1]));

    }

    public function testCancelFriendRequests(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $friendRequestRepository = static::getContainer()->get(FriendRequestRepository::class);

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

        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($user1);
        $friendRequest->setReceiverUser($user); // the logged in user is the receiver and needs to cancel the request
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($friendRequest);

        $entityManager->flush();

        $client->loginUser($user);


        $client->jsonRequest('POST', '/cancelFriendRequest', [
            'requestId' => $friendRequest->getId()
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertNull($friendRequestRepository->findOneBy(['senderUser' => $user, 'receiverUser' => $user1]));
    }

    public function testAcceptFriendRequests(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $friendRequestRepository = static::getContainer()->get(FriendRequestRepository::class);
        $friendRepository = static::getContainer()->get(FriendRepository::class);

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

        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($user1);
        $friendRequest->setReceiverUser($user); // the logged in user is the receiver and needs to accept the request
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($friendRequest);

        $entityManager->flush();

        $client->loginUser($user);


        $client->jsonRequest('POST', '/acceptFriendRequest', [
            'requestId' => $friendRequest->getId()
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertNull($friendRequestRepository->findOneBy(['senderUser' => $user, 'receiverUser' => $user1])); // request is removed because accepted
        $this->assertnotNull($friendRepository->findFriend($user, $user1)); // now in friend table
    }


}
