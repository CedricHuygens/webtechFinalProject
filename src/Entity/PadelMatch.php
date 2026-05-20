<?php

namespace App\Entity;

use App\Repository\PadelMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PadelMatchRepository::class)]
class PadelMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column]
    private ?int $team1Score = null;

    #[ORM\Column]
    private ?int $team2Score = null;

    #[ORM\ManyToOne(targetEntity: Field::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Field $field = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'team1_id', referencedColumnName: 'id', nullable: true)]
    private ?Team $team1 = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'team2_id', referencedColumnName: 'id', nullable: true)]
    private ?Team $team2 = null;

    /**
     * @var Collection<int, PadelMatchPlayer>
     */
    #[ORM\OneToMany(mappedBy: 'padelMatch', targetEntity: PadelMatchPlayer::class, orphanRemoval: true)]
    private Collection $matchPlayers;

    /**
     * @var Collection<int, PadelMatchLike>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchLike::class, mappedBy: 'padelMatch')]
    private Collection $likes;

    /**
     * @var Collection<int, PadelMatchComment>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchComment::class, mappedBy: 'padelMatch')]
    private Collection $padelMatchComments;

    public function __construct()
    {
        $this->matchPlayers = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->padelMatchComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getTeam1Score(): ?int
    {
        return $this->team1Score;
    }

    public function setTeam1Score(int $team1Score): static
    {
        $this->team1Score = $team1Score;

        return $this;
    }

    public function getTeam2Score(): ?int
    {
        return $this->team2Score;
    }

    public function setTeam2Score(int $team2Score): static
    {
        $this->team2Score = $team2Score;

        return $this;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }

    public function setField(?Field $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getTeam1(): ?Team
    {
        return $this->team1;
    }

    public function setTeam1(?Team $team1): static
    {
        $this->team1 = $team1;

        return $this;
    }

    public function getTeam2(): ?Team
    {
        return $this->team2;
    }

    public function setTeam2(?Team $team2): static
    {
        $this->team2 = $team2;

        return $this;
    }

    /**
     * @return Collection<int, PadelMatchPlayer>
     */
    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }

    public function addMatchPlayer(PadelMatchPlayer $matchPlayer): static
    {
        if (!$this->matchPlayers->contains($matchPlayer)) {
            $this->matchPlayers->add($matchPlayer);
            $matchPlayer->setPadelMatch($this);
        }

        return $this;
    }

    public function removeMatchPlayer(PadelMatchPlayer $matchPlayer): static
    {
        if ($this->matchPlayers->removeElement($matchPlayer)) {
            if ($matchPlayer->getPadelMatch() === $this) {
                $matchPlayer->setPadelMatch(null);
            }
        }

        return $this;
    }

    public function getTeam1Players(): array
    {
        $players = [];

        foreach ($this->matchPlayers as $matchPlayer) {
            if ($matchPlayer->getTeamNumber() === 1 && $matchPlayer->getUser() !== null) {
                $players[] = $matchPlayer->getUser();
            }
        }

        return $players;
    }

    public function getTeam2Players(): array
    {
        $players = [];

        foreach ($this->matchPlayers as $matchPlayer) {
            if ($matchPlayer->getTeamNumber() === 2 && $matchPlayer->getUser() !== null) {
                $players[] = $matchPlayer->getUser();
            }
        }

        return $players;
    }

    /**
     * @return Collection<int, PadelMatchLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(PadelMatchLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setPadelMatch($this);
        }

        return $this;
    }

    public function removeLike(PadelMatchLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getPadelMatch() === $this) {
                $like->setPadelMatch(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PadelMatchComment>
     */
    public function getPadelMatchComments(): Collection
    {
        return $this->padelMatchComments;
    }

    public function addPadelMatchComment(PadelMatchComment $padelMatchComment): static
    {
        if (!$this->padelMatchComments->contains($padelMatchComment)) {
            $this->padelMatchComments->add($padelMatchComment);
            $padelMatchComment->setPadelMatch($this);
        }

        return $this;
    }

    public function removePadelMatchComment(PadelMatchComment $padelMatchComment): static
    {
        if ($this->padelMatchComments->removeElement($padelMatchComment)) {
            if ($padelMatchComment->getPadelMatch() === $this) {
                $padelMatchComment->setPadelMatch(null);
            }
        }

        return $this;
    }
}
