<?php

use App\Entity\FriendRequest;
use App\Entity\User;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FriendRequestPantherTest extends PantherTestCase
{
    public function testAcceptFriendRequest(): void
    {
        $client = static::createPantherClient();

        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // if a user already exist with that email, remove so we can run multiple times
        foreach (['panther@test.com', 'sender@test.com'] as $email) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $em->remove($existing);
                $em->flush();
            }
        }

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('pantheruser');
        $user->setEmail('panther@test.com');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setPassword($passwordHasher->hashPassword($user, 'test123'));
        $em->persist($user);

        $sender = new User();
        $sender->setFirstName('Sender');
        $sender->setLastName('User');
        $sender->setUsername('senderuser');
        $sender->setEmail('sender@test.com');
        $sender->setBirthDate(new \DateTime('2000-01-01'));
        $sender->setRoles(['ROLE_USER']);
        $sender->setIsActive(true);
        $sender->setPassword($passwordHasher->hashPassword($sender, 'test123'));
        $em->persist($sender);

        $em->flush();

        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($sender);
        $friendRequest->setReceiverUser($user);
        $friendRequest->setCreatedAt(new \DateTimeImmutable());
        $em->persist($friendRequest);
        $em->flush();

        $client->request('GET', '/login');
        $client->waitFor('input[name="_username"]');
        $client->findElement(WebDriverBy::name('_username'))->sendKeys('panther@test.com');
        $client->findElement(WebDriverBy::name('_password'))->sendKeys('test123');
        $client->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        $client->waitFor('body');
        self::assertStringNotContainsString('/login', $client->getCurrentURL());

        $client->request('GET', '/friendrequests');
        $this->assertSelectorExists('#receivedRequestsList');

        $this->assertSelectorIsVisible('.acceptFriendRequestReceivedButton'); // check that the friendrequest is visible

        $client->findElement(WebDriverBy::cssSelector('.acceptFriendRequestReceivedButton'))->click(); // accept it

        $client->waitForInvisibility('.acceptFriendRequestReceivedButton'); // accepted, so removed from page
        $this->assertSelectorNotExists('.acceptFriendRequestReceivedButton');
        $client->quit();
    }
}
