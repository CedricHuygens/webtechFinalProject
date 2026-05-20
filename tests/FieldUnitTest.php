<?php

namespace App\Tests;

use App\Entity\Club;
use App\Entity\Field;
use App\Entity\TournamentField;
use PHPUnit\Framework\TestCase;

class FieldUnitTest extends TestCase
{
    public function testFieldGettersSettersAndDefaultCollections(): void
    {
        $club = new Club();

        $field = new Field();
        $field->setName('Center Court');
        $field->setType('Padel');
        $field->setFieldNumber(1);
        $field->setLocation('Antwerp');
        $field->setClub($club);

        $this->assertNull($field->getId());
        $this->assertSame('Center Court', $field->getName());
        $this->assertSame('Padel', $field->getType());
        $this->assertSame(1, $field->getFieldNumber());
        $this->assertSame('Antwerp', $field->getLocation());
        $this->assertSame($club, $field->getClub());
        $this->assertCount(0, $field->getBookings());
        $this->assertCount(0, $field->getTournamentFields());
    }

    public function testAddTournamentFieldThrowsError(): void
    {
        $field = new Field();
        $tournamentField = new TournamentField();

        $this->expectException(\Error::class);

        $field->addTournamentField($tournamentField);
    }

    public function testRemoveTournamentFieldThrowsError(): void
    {
        $field = new Field();
        $tournamentField = new TournamentField();

        $field->getTournamentFields()->add($tournamentField);

        $this->expectException(\Error::class);

        $field->removeTournamentField($tournamentField);
    }

    public function testAddDuplicateTournamentFieldDoesNotThrowError(): void
    {
        $field = new Field();
        $tournamentField = new TournamentField();

        $field->getTournamentFields()->add($tournamentField);

        $field->addTournamentField($tournamentField);

        $this->assertCount(1, $field->getTournamentFields());
    }
}
