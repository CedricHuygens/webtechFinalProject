<?php

namespace App\Entity;

use App\Repository\PadelMatchCommentCommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchCommentCommentRepository::class)]
class PadelMatchCommentComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'padelMatchCommentComments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PadelMatchComment $mainComment = null;

    #[ORM\ManyToOne(inversedBy: 'padelMatchCommentComments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PadelMatchComment $ReplyComment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMainComment(): ?PadelMatchComment
    {
        return $this->mainComment;
    }

    public function setMainComment(?PadelMatchComment $mainComment): static
    {
        $this->mainComment = $mainComment;

        return $this;
    }

    public function getReplyComment(): ?PadelMatchComment
    {
        return $this->ReplyComment;
    }

    public function setReplyComment(?PadelMatchComment $ReplyComment): static
    {
        $this->ReplyComment = $ReplyComment;

        return $this;
    }
}
