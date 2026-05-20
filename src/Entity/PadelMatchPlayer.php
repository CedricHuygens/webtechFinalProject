<?php

namespace App\Entity;

use App\Repository\PadelMatchPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchPlayerRepository::class)]
class PadelMatchPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $teamNumber = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: PadelMatch::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'padel_match', referencedColumnName: 'id', nullable: false)]
    private ?PadelMatch $padelMatch = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeamNumber(): ?int
    {
        return $this->teamNumber;
    }

    public function setTeamNumber(int $teamNumber): static
    {
        $this->teamNumber = $teamNumber;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPadelMatch(): ?PadelMatch
    {
        return $this->padelMatch;
    }

    public function setPadelMatch(?PadelMatch $padelMatch): static
    {
        $this->padelMatch = $padelMatch;
        return $this;
    }
}
