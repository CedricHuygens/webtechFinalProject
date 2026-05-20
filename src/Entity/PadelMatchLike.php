<?php

namespace App\Entity;

use App\Repository\PadelMatchLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchLikeRepository::class)]
class PadelMatchLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'user')]
    private ?PadelMatch $padelMatch = null;

    #[ORM\ManyToOne(inversedBy: 'padelMatchLikes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
