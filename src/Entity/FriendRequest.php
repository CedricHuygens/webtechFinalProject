<?php

namespace App\Entity;

use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendRequestRepository::class)]
class FriendRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'friendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $senderUser = null;

    #[ORM\ManyToOne(inversedBy: 'friendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiverUser = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderUser(): ?User
    {
        return $this->senderUser;
    }

    public function setSenderUser(?User $senderUser): static
    {
        $this->senderUser = $senderUser;

        return $this;
    }

    public function getReceiverUser(): ?User
    {
        return $this->receiverUser;
    }

    public function setReceiverUser(?User $receiverUser): static
    {
        $this->receiverUser = $receiverUser;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
