<?php

namespace App\Tests;

use App\Entity\Club;
use App\Entity\Field;
use App\Entity\User;
use App\Entity\Booking;
use App\Entity\Tournament;
use App\Entity\Team;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\ClubOpeningHours;

class AdminFieldControllerIntegrationTest extends WebTestCase
{
    private function createUser(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('admin_field_user_' . uniqid());
        $user->setEmail('admin_field_user_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    // helper admin
    private function createAdmin(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername('admin_field_admin_' . uniqid());
        $user->setEmail('admin_field_admin_' . uniqid() . '@example.com');
        $user->setPassword('test123');
        $user->setBirthDate(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    // helper club
    private function createClub(User $admin): Club
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $club = new Club();
        $club->setName('Admin Test Club');
        $club->setUser($admin);

        $entityManager->persist($club);
        $entityManager->flush();

        return $club;
    }

    // helper veld
    private function createField(Club $club): Field
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $field = new Field();
        $field->setName('Court 1');
        $field->setType('indoor');
        $field->setFieldNumber(1);
        $field->setLocation('Antwerp');
        $field->setClub($club);

        $entityManager->persist($field);
        $entityManager->flush();

        return $field;
    }

    // helper openinghours
    private function createClubOpeningHours(Club $club): ClubOpeningHours
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $openingHours = new ClubOpeningHours();
        $openingHours->setClub($club);
        $openingHours->setDayOfWeek('monday');
        $openingHours->setOpenTime(new \DateTimeImmutable('10:00'));
        $openingHours->setCloseTime(new \DateTimeImmutable('18:00'));

        $entityManager->persist($openingHours);
        $entityManager->flush();

        return $openingHours;
    }

    public function testAdminCanAccessNewFieldPage(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $client->loginUser($admin);

        $client->request('GET', '/admin/fields/new');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanViewMyFields(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $this->createField($club);

        $client->loginUser($admin);

        $client->request('GET', '/admin/my-fields');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanAddField(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $client->loginUser($admin);

        // admin voegt veld toe met locatie, type en veld_nr
        $client->request('POST', '/admin/fields/add', [
            'location' => 'Brussels',
            'type' => 'indoor',
            'field_number' => 2,
        ]);

        $this->assertResponseRedirects('/admin/my-fields'); // na toevoegen blijf op my fields

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // kijk of veld in test database staat
        $field = $entityManager
            ->getRepository(Field::class)
            ->findOneBy([
                'club' => $club,
                'fieldNumber' => 2,
            ]);

        $this->assertNotNull($field);
    }

    public function testAdminCanDeleteField(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $field = $this->createField($club);
        $fieldId = $field->getId();

        $client->loginUser($admin);

        $client->request('POST', '/admin/fields/delete/' . $fieldId);

        $this->assertResponseRedirects('/admin/my-fields');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $deletedField = $entityManager
            ->getRepository(Field::class)
            ->find($fieldId);

        $this->assertNull($deletedField); // moet nul teruggeven want staat niemeer in testdatabase
    }

    public function testAdminDeleteFieldAlsoDeletesBookingsAndTournaments(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $field = $this->createField($club);

        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $booking = new Booking();
        $booking->setField($field);
        $booking->setUser($user1);
        $booking->setDate(new \DateTimeImmutable('2026-06-01'));
        $booking->setStartTime(new \DateTimeImmutable('2026-06-01 10:00'));
        $booking->setEndTime(new \DateTimeImmutable('2026-06-01 11:00'));
        $entityManager->persist($booking);

        $tournament = new Tournament();
        $tournament->setName('Delete Field Tournament');
        $tournament->setDate(new \DateTime('2026-06-02'));
        $tournament->setClub($club);
        $entityManager->persist($tournament);
        $entityManager->flush();

        $team = new Team();
        $team->setName('Delete Field Team');
        $team->setUser1($user1);
        $team->setUser2($user2);
        $entityManager->persist($team);
        $entityManager->flush();

        $entityManager->getConnection()->insert('tournament_field', [
            'tournament_id' => $tournament->getId(),
            'field_id' => $field->getId(),
        ]);

        $entityManager->getConnection()->insert('tournament_team', [
            'tournament_id' => $tournament->getId(),
            'team_id' => $team->getId(),
        ]);

        $bookingId = $booking->getId();
        $fieldId = $field->getId();
        $tournamentId = $tournament->getId();

        $client->loginUser($admin);

        $client->request('POST', '/admin/fields/delete/' . $fieldId);

        $this->assertResponseRedirects('/admin/my-fields');

        $this->assertSame(0, (int) $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM booking WHERE id = ?',
            [$bookingId]
        ));

        $this->assertSame(0, (int) $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM tournament_field WHERE field_id = ?',
            [$fieldId]
        ));

        $this->assertSame(0, (int) $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM tournament_team WHERE tournament_id = ?',
            [$tournamentId]
        ));

        $this->assertSame(0, (int) $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM tournament WHERE id = ?',
            [$tournamentId]
        ));

        $this->assertSame(0, (int) $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM field WHERE id = ?',
            [$fieldId]
        ));
    }

    public function testAdminCannotAddFieldWithInvalidFieldNumber(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('POST', '/admin/fields/add', [
            'location' => 'Brussels',
            'type' => 'indoor',
            'field_number' => 0,
        ]);

        $this->assertResponseRedirects('/admin/my-fields');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // nr 0 is geen veldnr, start vanaf 1
        $field = $entityManager
            ->getRepository(Field::class)
            ->findOneBy([
                'location' => 'Brussels',
                'fieldNumber' => 0,
            ]);

        $this->assertNull($field);
    }

    public function testAdminCannotAddDuplicateFieldNumber(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);
        $this->createField($club); // maakt field met veldnr 1

        $client->loginUser($admin);

        // admin probeert nog een veld toe te voegen met veldnr 1
        $client->request('POST', '/admin/fields/add', [
            'location' => 'Brussels',
            'type' => 'indoor',
            'field_number' => 1,
        ]);

        $this->assertResponseRedirects('/admin/my-fields');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $fields = $entityManager
            ->getRepository(Field::class)
            ->findBy([
                'club' => $club,
                'fieldNumber' => 1,
            ]);

        $this->assertCount(1, $fields);
    }

    public function testAdminCanViewClosures(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('GET', '/admin/closures');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanAddClosure(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('POST', '/admin/closures', [
            'date' => '2026-05-20',
            'reason' => 'Maintenance',
        ]);

        $this->assertResponseRedirects('/admin/closures');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $closure = $entityManager
            ->getRepository(\App\Entity\ClubClosure::class)
            ->findOneBy([
                'club' => $club,
                'date' => new \DateTimeImmutable('2026-05-20'),
            ]);

        $this->assertNotNull($closure);
    }

    public function testAdminCanViewOpeningHours(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('GET', '/admin/opening-hours');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanAddOpeningHours(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $client->loginUser($admin);

        $client->request('POST', '/admin/opening-hours/add', [
            'day_of_week' => 'monday',
            'open_time' => '09:00',
            'close_time' => '22:00',
        ]);

        $this->assertResponseRedirects('/admin/opening-hours');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $openingHours = $entityManager
            ->getRepository(\App\Entity\ClubOpeningHours::class)
            ->findOneBy([
                'club' => $club,
                'dayOfWeek' => 'monday',
            ]);

        $this->assertNotNull($openingHours);
    }

    public function testAdminCannotAddOverlappingOpeningHours(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $this->createClubOpeningHours($club);

        $client->loginUser($admin);

        // probeert 14-22 toe te voegen maar overlapt met 10-18 van de helper
        $client->request('POST', '/admin/opening-hours/add', [
            'day_of_week' => 'monday',
            'open_time' => '14:00',
            'close_time' => '22:00',
        ]);

        $this->assertResponseRedirects('/admin/opening-hours');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $hours = $entityManager
            ->getRepository(\App\Entity\ClubOpeningHours::class)
            ->findBy([
                'club' => $club,
                'dayOfWeek' => 'monday',
            ]);

        $this->assertCount(1, $hours);
    }

    public function testAdminCanDeleteOpeningHours(): void
    {
        $client = static::createClient();

        $admin = $this->createAdmin();
        $club = $this->createClub($admin);

        $openingHours = $this->createClubOpeningHours($club);

        $openingHoursId = $openingHours->getId();

        $client->loginUser($admin);

        $client->request(
            'GET',
            '/admin/opening-hours/delete/' . $openingHoursId
        );

        $this->assertResponseRedirects('/admin/opening-hours');

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $deletedOpeningHours = $entityManager
            ->getRepository(ClubOpeningHours::class)
            ->find($openingHoursId);

        $this->assertNull($deletedOpeningHours);
    }
}
