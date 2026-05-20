<?php

namespace App\Tests;

use App\Entity\Club;
use App\Entity\ClubClosure;
use App\Entity\ClubOpeningHours;
use App\Entity\ClubRequest;
use App\Entity\ClubUser;
use App\Entity\Tournament;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ClubUnitTest extends TestCase
{
    public function testClubGettersSettersAndDefaultCollections(): void
    {
        $user = new User();

        $club = new Club();
        $club->setName('Test Club');
        $club->setUser($user);

        $this->assertNull($club->getId());
        $this->assertSame('Test Club', $club->getName());
        $this->assertSame($user, $club->getUser());
        $this->assertCount(0, $club->getFields());
        $this->assertCount(0, $club->getOpeningHours());
        $this->assertCount(0, $club->getClosures());
        $this->assertCount(0, $club->getTournaments());
        $this->assertCount(0, $club->getClubUsers());
    }

    public function testOpeningHoursCollection(): void
    {
        $club = new Club();
        $openingHour = new ClubOpeningHours();

        $club->addOpeningHour($openingHour);
        $club->addOpeningHour($openingHour);

        $this->assertCount(1, $club->getOpeningHours());
        $this->assertSame($club, $openingHour->getClub());

        $club->removeOpeningHour($openingHour);

        $this->assertFalse($club->getOpeningHours()->contains($openingHour));
        $this->assertNull($openingHour->getClub());
    }

    public function testClosureCollection(): void
    {
        $club = new Club();
        $closure = new ClubClosure();

        $club->addClosure($closure);
        $club->addClosure($closure);

        $this->assertCount(1, $club->getClosures());
        $this->assertSame($club, $closure->getClub());

        $club->removeClosure($closure);

        $this->assertFalse($club->getClosures()->contains($closure));
        $this->assertNull($closure->getClub());
    }

    public function testBrokenTournamentCollectionMethods(): void
    {
        $club = new Club();
        $tournament = new Tournament();

        $this->expectException(\Error::class);

        $club->addTournament($tournament);
    }

    public function testRemoveTournamentThrowsError(): void
    {
        $club = new Club();
        $tournament = new Tournament();

        $club->getTournaments()->add($tournament);

        $this->expectException(\Error::class);

        $club->removeTournament($tournament);
    }

    public function testBrokenClubUserCollectionMethods(): void
    {
        $club = new Club();
        $clubUser = new ClubUser();

        $this->expectException(\Error::class);

        $club->addClubUser($clubUser);
    }

    public function testRemoveClubUserThrowsError(): void
    {
        $club = new Club();
        $clubUser = new ClubUser();

        $club->getClubUsers()->add($clubUser);

        $this->expectException(\Error::class);

        $club->removeClubUser($clubUser);
    }

    public function testClubOpeningHoursGettersAndSetters(): void
    {
        $club = new Club();
        $openTime = new \DateTimeImmutable('09:00');
        $closeTime = new \DateTimeImmutable('18:00');

        $openingHours = new ClubOpeningHours();
        $openingHours->setClub($club);
        $openingHours->setDayOfWeek('wednesday');
        $openingHours->setOpenTime($openTime);
        $openingHours->setCloseTime($closeTime);

        $this->assertNull($openingHours->getId());
        $this->assertSame($club, $openingHours->getClub());
        $this->assertSame('wednesday', $openingHours->getDayOfWeek());
        $this->assertSame($openTime, $openingHours->getOpenTime());
        $this->assertSame($closeTime, $openingHours->getCloseTime());
    }

    public function testClubClosureGettersAndSetters(): void
    {
        $club = new Club();
        $date = new \DateTimeImmutable('2026-05-20');

        $closure = new ClubClosure();
        $closure->setClub($club);
        $closure->setDate($date);
        $closure->setReason('Holiday');

        $this->assertNull($closure->getId());
        $this->assertSame($club, $closure->getClub());
        $this->assertSame($date, $closure->getDate());
        $this->assertSame('Holiday', $closure->getReason());
    }



    public function testClubUserGettersAndSetters(): void
    {
        $user = new User();
        $club = new Club();
        $createdAt = new \DateTimeImmutable();

        $clubUser = new ClubUser();
        $clubUser->setUser($user);
        $clubUser->setClub($club);
        $clubUser->setCreatedAt($createdAt);

        $this->assertNull($clubUser->getId());
        $this->assertSame($user, $clubUser->getUser());
        $this->assertSame($club, $clubUser->getClub());
        $this->assertSame($createdAt, $clubUser->getCreatedAt());
    }
}
