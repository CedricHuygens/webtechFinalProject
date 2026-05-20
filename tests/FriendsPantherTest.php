<?php

use App\Entity\Friend;
use App\Entity\User;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FriendsPantherTest extends PantherTestCase
{
    public function testDeleteFriend(): void
    {
        $client = static::createPantherClient();

        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // delete the used users with that email so that we can run test multiple times
        foreach (['panther@test.com', 'friend@test.com'] as $email) {
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


        $friend = new User();
        $friend->setFirstName('Friend');
        $friend->setLastName('User');
        $friend->setUsername('frienduser');
        $friend->setEmail('friend@test.com');
        $friend->setBirthDate(new \DateTime('2000-01-01'));
        $friend->setRoles(['ROLE_USER']);
        $friend->setIsActive(true);
        $friend->setPassword($passwordHasher->hashPassword($friend, 'test123'));
        $em->persist($friend);

        $em->flush();

        $friendship = new Friend();
        $friendship->setUser1($user);
        $friendship->setUser2($friend);
        $em->persist($friendship);
        $em->flush();

        $client->request('GET', '/login');
        $client->waitFor('input[name="_username"]');
        $client->findElement(WebDriverBy::name('_username'))->sendKeys('panther@test.com');
        $client->findElement(WebDriverBy::name('_password'))->sendKeys('test123');
        $client->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        $client->waitFor('body');
        self::assertStringNotContainsString('/login', $client->getCurrentURL());

        $client->request('GET', '/friends');
        $this->assertSelectorExists('#friendList');

        $this->assertSelectorIsVisible('.deleteFriendButton'); // to check the friend is visible

        $client->executeScript("window.confirm = () => true;"); // setting for what happens when the pop up comes when you press on delete button
        $client->findElement(WebDriverBy::cssSelector('.deleteFriendButton'))->click(); // here we press the delete button

        $client->waitForInvisibility('.deleteFriendButton'); // friend will be removed
        $this->assertSelectorNotExists('.deleteFriendButton');
        $client->quit();
    }
}
