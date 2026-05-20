<?php

namespace App\Entity;

use App\Repository\PadelMatchCommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchCommentRepository::class)]
class PadelMatchComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'padelMatchComments')]
    private ?PadelMatch $padelMatch = null;

    #[ORM\ManyToOne(inversedBy: 'padelMatchComments')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    /**
     * @var Collection<int, PadelMatchCommentLike>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchCommentLike::class, mappedBy: 'comment', orphanRemoval: true)]
    private Collection $padelMatchCommentLikes;

    /**
     * @var Collection<int, PadelMatchCommentComment>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchCommentComment::class, mappedBy: 'mainComment')]
    private Collection $padelMatchCommentComments;

    public function __construct()
    {
        $this->padelMatchCommentLikes = new ArrayCollection();
        $this->padelMatchCommentComments = new ArrayCollection();
    }

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, PadelMatchCommentLike>
     */
    public function getPadelMatchCommentLikes(): Collection
    {
        return $this->padelMatchCommentLikes;
    }

    public function addPadelMatchCommentLike(PadelMatchCommentLike $padelMatchCommentLike): static
    {
        if (!$this->padelMatchCommentLikes->contains($padelMatchCommentLike)) {
            $this->padelMatchCommentLikes->add($padelMatchCommentLike);
            $padelMatchCommentLike->setComment($this);
        }

        return $this;
    }

    public function removePadelMatchCommentLike(PadelMatchCommentLike $padelMatchCommentLike): static
    {
        if ($this->padelMatchCommentLikes->removeElement($padelMatchCommentLike)) {
            if ($padelMatchCommentLike->getComment() === $this) {
                $padelMatchCommentLike->setComment(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection<int, PadelMatchCommentComment>
     */
    public function getPadelMatchCommentComments(): Collection
    {
        return $this->padelMatchCommentComments;
    }

    public function addPadelMatchCommentComment(PadelMatchCommentComment $padelMatchCommentComment): static
    {
        if (!$this->padelMatchCommentComments->contains($padelMatchCommentComment)) {
            $this->padelMatchCommentComments->add($padelMatchCommentComment);
            $padelMatchCommentComment->setMainComment($this);
        }

        return $this;
    }

    public function removePadelMatchCommentComment(PadelMatchCommentComment $padelMatchCommentComment): static
    {
        if ($this->padelMatchCommentComments->removeElement($padelMatchCommentComment)) {
            if ($padelMatchCommentComment->getMainComment() === $this) {
                $padelMatchCommentComment->setMainComment(null);
            }
        }

        return $this;
    }
}
