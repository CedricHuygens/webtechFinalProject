<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\PadelMatch;
use App\Entity\PadelMatchPlayer;

class ProfileIntegrationTest extends WebTestCase
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

        $match = new PadelMatch();
        $match->setStartTime(new \DateTime('now'));
        $match->setEndTime(new \DateTime('now +1 hour'));
        $match->setTeam1Score(6);
        $match->setTeam2Score(3);

        $player = new PadelMatchPlayer();
        $player->setUser($user);
        $player->setPadelMatch($match);
        $player->setTeamNumber(1);

        $entityManager->persist($match);
        $entityManager->persist($player);
        $entityManager->flush();

        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();

        $this->assertGreaterThan(0, $crawler->filter('.profileStats')->count());

        $crawler = $client->request('GET', '/profileSettings');

        $client->request('GET', '/goToProfileSettings');

        $this->assertResponseIsSuccessful();
    }

}
