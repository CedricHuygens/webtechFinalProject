<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class LoginPantherTest extends PantherTestCase
{
    protected function setUp(): void
    {
        $kernelClient = static::createClient();
        $container = $kernelClient->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $userRepository = $em->getRepository(User::class);
        foreach ($userRepository->findAll() as $user) {
            $em->remove($user);
        }
        $em->flush();
        // creating user
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $existingUser = (new User())
            ->setEmail('test@test.com')
            ->setFirstName('TestFirstName')
            ->setLastName('TestLastName')
            ->setUsername('testUser')
            ->setBirthDate(new \DateTime('2000-01-01'));
        $existingUser->setPassword($passwordHasher->hashPassword($existingUser, 'password1234@'));
        $existingUser->setRoles(['ROLE_USER']);
        $em->persist($existingUser);
        $em->flush();
    }
    public function testLoginValidPassword(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/login');
        $this->assertSelectorExists("#form_email");
        $this->assertSelectorExists("#form_password");
        $this->assertSelectorExists("#loginButton");

        $crawler->filter('#form_email')->sendKeys('test@test.com');
        $crawler->filter('#form_password')->sendKeys('password1234@');
        $crawler->filter('#loginButton')->click();

        $client->waitFor('body');
        $this->assertSelectorNotExists('.status-message.error');
        $this->assertStringContainsString('/', $client->getCurrentURL());
        $client->quit(); // to make sure the fields are empty throughout the different fields
    }
    public function testLoginInvalidPassword(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/login');
        $this->assertSelectorExists("#form_email");
        $this->assertSelectorExists("#form_password");
        $this->assertSelectorExists("#loginButton");

        $crawler->filter('#form_email')->sendKeys('test@test.com');
        $crawler->filter('#form_password')->sendKeys('invalidPassword');
        $crawler->filter('#loginButton')->click();

        $client->waitFor('body');
        $client->waitFor('.status-message.error');
        $this->assertStringContainsString('/login', $client->getCurrentURL());
        $client->quit();
    }
    public function testLoginInvalidEmail(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/login');
        $this->assertSelectorExists("#form_email");
        $this->assertSelectorExists("#form_password");
        $this->assertSelectorExists("#loginButton");

        $crawler->filter('#form_email')->sendKeys('invalidEmail@gmail.com');
        $crawler->filter('#form_password')->sendKeys('password1234@');
        $crawler->filter('#loginButton')->click();
        $client->waitFor('body');
        $this->assertSelectorExists('.status-message.error');
        $this->assertStringContainsString('/login', $client->getCurrentURL());
        $client->quit();
    }
}
