<?php

namespace App\Entity;

use App\Repository\ClubOpeningHoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubOpeningHoursRepository::class)]
class ClubOpeningHours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'openingHours')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    #[ORM\Column(length: 20)]
    private ?string $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $openTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $closeTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;

        return $this;
    }

    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getOpenTime(): ?\DateTimeImmutable
    {
        return $this->openTime;
    }

    public function setOpenTime(\DateTimeImmutable $openTime): static
    {
        $this->openTime = $openTime;

        return $this;
    }

    public function getCloseTime(): ?\DateTimeImmutable
    {
        return $this->closeTime;
    }

    public function setCloseTime(\DateTimeImmutable $closeTime): static
    {
        $this->closeTime = $closeTime;

        return $this;
    }
}
