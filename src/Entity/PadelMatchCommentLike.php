<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PadelMatchCommentLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchCommentLikeRepository::class)]
#[ORM\Table(name: 'padel_match_comment_like')]
#[ORM\UniqueConstraint(name: 'unique_comment_like', columns: ['comment_id', 'user_id'])]
class PadelMatchCommentLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PadelMatchComment::class)]
    #[ORM\JoinColumn(name: 'comment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PadelMatchComment $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?PadelMatchComment
    {
        return $this->comment;
    }

    public function setComment(?PadelMatchComment $comment): static
    {
        $this->comment = $comment;

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
