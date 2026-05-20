<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginRegisterIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $userRepository = $em->getRepository(User::class);

        // Remove any existing users from the test database
        foreach ($userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();

        // Create a User fixture
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())
            ->setEmail('email@example.com')
            ->setFirstName('Test')
            ->setLastName('User')
            ->setUsername('testuser')
            ->setBirthDate(new \DateTime('2000-01-01'));

        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $em->persist($user);
        $em->flush();
    }
    public function testLoginToRegiserPage(): void {
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        $this->client->clickLink('Create one.');
        $this->assertRouteSame('register');
    }

    public function testLoginFail(): void
    {
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        // Testing if login would fail with invalid credentials

        $this->client->submitForm('Login', [
            '_username' => 'doesNotExist@example.com',
            '_password' => 'password',
        ]);
        // Checken of we inderdaad terug gaan naar de /login pagina
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.status-message.error', 'Invalid email or password. Please try again.');
    }
    public function testLoginSuccess(): void
    {
        // nu testen of login inderdaad wel werkt
        $this->client->request('GET', '/login');
        $this->client->submitForm('Login', [
            '_username' => 'email@example.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorNotExists('.status-message.error');
    }
    public function testRegisterFormWithWeakPassword(): void {
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Register', [
            'form[firstName]'     => 'TestFirstName',
            'form[lastName]'      => 'TestLastName',
            'form[username]'      => 'testusername',
            'form[email]'         => 'testFirstname.testLastName@gmail.com',
            'form[password]'      => '123456',
            'form[passwordCheck]' => '123456',
            'form[birthDate]'     => '2000-01-01',
        ]);
        self::assertSelectorExists('.status-message.error');
        $this->assertSelectorTextContains(
            '.status-message.error',
            'Your password is too weak'
        );
    }
    public function testRegisterFormWithStrongPassword(): void
    {
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Register', [
            'form[firstName]'     => 'TestFirstName',
            'form[lastName]'      => 'TestLastName',
            'form[username]'      => 'testusername',
            'form[email]'         => 'testFirstname.testLastName@gmail.com',
            'form[password]'      => 'éé&àà&ç12ç&é',
            'form[passwordCheck]' => 'éé&àà&ç12ç&é',
            'form[birthDate]'     => '2000-01-01',
        ]);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
    }
}
