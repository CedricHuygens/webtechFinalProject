<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Club;
use App\Entity\Field;
use App\Entity\Booking;
use App\Entity\ClubOpeningHours;

class FieldsControllerIntegrationTest extends WebTestCase
{
    // test user in iedere test nodig dus makkelijk ophaalbaar maken
    private function createUser(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('field_user_' . uniqid());
        $user->setEmail('field_user_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    // ook voor admins
    private function createAdmin(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername('admin_' . uniqid());
        $user->setEmail('admin_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    // zelfde voor club
    private function createClub(User $user): Club
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $club = new Club();
        $club->setName('Test Club');
        $club->setUser($user);

        $entityManager->persist($club);
        $entityManager->flush();

        return $club;
    }

    //zelfde voor club openinghours
    private function createClubOpeningHours(Club $club): ClubOpeningHours
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $openingHours = new ClubOpeningHours();
        $openingHours->setClub($club);
        $openingHours->setDayOfWeek('wednesday');
        $openingHours->setOpenTime(new \DateTimeImmutable('09:00'));
        $openingHours->setCloseTime(new \DateTimeImmutable('18:00'));

        $entityManager->persist($openingHours);
        $entityManager->flush();

        return $openingHours;
    }

    // zelfde voor field
    private function createField(Club $club): Field
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $field = new Field();
        $field->setName('Center Court');
        $field->setType('Padel');
        $field->setFieldNumber(1);
        $field->setLocation('Antwerp');
        $field->setClub($club);

        $entityManager->persist($field);
        $entityManager->flush();

        return $field;
    }

    // zelfde voor booking
    private function createBooking(User $user, Field $field): Booking
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setField($field);
        $booking->setDate(new \DateTimeImmutable('2026-05-20'));
        $booking->setStartTime(new \DateTimeImmutable('10:00'));
        $booking->setEndTime(new \DateTimeImmutable('11:00'));

        $entityManager->persist($booking);
        $entityManager->flush();

        return $booking;
    }


    // test dat pagina redirect naar login wanneer gebruiker niet is ingelogd
    public function testFieldsPageRedirectsToLoginWhenNotLoggedIn(): void
    {
        $client = static::createClient(); // testclient
        $client->request('GET', '/fields?search=1'); // client surft naar fields url
        $this->assertResponseRedirects('/login'); // verwacht dat je op login terecht komt
    }

    // naar field gaan na inloggen
    public function testFieldsPageLoadsWhenLoggedIn(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $client->loginUser($user); // inloggen met de testuser

        $client->request('GET', '/fields'); // fields pagina bezoeken

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#fields-page h1.main-title', 'Fields'); // kijk of je wel echt op fields zit door hoofdtitel te vergelijken
    }

    public function testFieldsSearchPageLoads(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $client->loginUser($user);

        // hier wordt de zoekknop ingedrukt en de ingevulde velden zijn 20/5/2025 om 10h en 1u tijd
        $client->request(
            'GET',
            '/fields?search=1&search_date=2025-05-20&time=10:00&duration=1'
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.no-results', 'This time is not available.'); // geen velden
    }

    public function testStatusBooking(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin); // club aanmaken, een club hoort bij user
        $user = $this->createUser();
        $field = $this->createField($club); // veld aanmaken dat bij de club hoort
        $booking = $this->createBooking($user, $field); // user maakt reservatie op dat veld

        $client->loginUser($user);

        $client->request('GET', '/booking/status/' . $booking->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteBooking(): void
    {
        $client = static::createClient();
        // booking zetten om later te deleten
        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $field = $this->createField($club);
        $user = $this->createUser();
        $booking = $this->createBooking($user, $field);

        $bookingId = $booking->getId(); // bookingId opslaan om later te checken
        $client->loginUser($user);
        $client->request('POST', '/booking/cancel/' . $bookingId); // user gaat die booking annuleren
        $this->assertResponseRedirects('/fields'); // redirect naar field na cancel

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $deletedBooking = $entityManager
            ->getRepository(Booking::class)
            ->find($bookingId);
        $this->assertNull($deletedBooking); // zou niks mogen teruggeven
    }

    public function testFieldAvailable(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $this->createClubOpeningHours($club);
        $field = $this->createField($club);

        $user = $this->createUser();
        $client->loginUser($user);

        // URL om te checken of 20/05/2026 vrij is van 10 tot 11
        $client->request(
            'GET',
            '/fields?search=1&search_date=2026-05-20&time=10:00&duration=1'
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.field-name', $club->getName()); // bevat field name de naam vd club juist
        $this->assertSelectorTextContains('.field-name', $field->getLocation()); // bevat field name de locatie vd field juist
    }

    public function testConfirmBookingPageLoads(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $field = $this->createField($club);

        $user = $this->createUser();
        $client->loginUser($user);

        $client->request(
            'GET',
            '/booking/confirm/' . $field->getId() . '?date=2026-05-20&time=10:00&duration=1'
        );

        $this->assertResponseIsSuccessful();
    }

    public function testFinalBookingCreatesBooking(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $field = $this->createField($club);

        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('POST', '/booking/final', [
            'field_id' => $field->getId(),
            'date' => '2026-05-20',
            'time' => '10:00',
            'duration' => 1,
        ]);

        $this->assertResponseRedirects();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $booking = $entityManager
            ->getRepository(Booking::class)
            ->findOneBy([
                'field' => $field,
                'user' => $user,
                'date' => new \DateTimeImmutable('2026-05-20'),
            ]);

        $this->assertNotNull($booking);
        $this->assertSame('10:00', $booking->getStartTime()->format('H:i'));
        $this->assertSame('11:00', $booking->getEndTime()->format('H:i'));
    }
}
