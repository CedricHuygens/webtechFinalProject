<?php


namespace App\Tests;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ContactInfoTest extends WebTestCase
{
    public function testPageLoaded(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('alice123'.uniqid());
        $user->setEmail('john'.uniqid().'@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/contactInfo');
        $this->assertResponseIsSuccessful();
    }
}
