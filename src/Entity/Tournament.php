<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'tournaments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    /**
     * @var Collection<int, TournamentField>
     */
    #[ORM\OneToMany(targetEntity: TournamentField::class, mappedBy: 'tournament')]
    private Collection $tournamentFields;

    /**
     * @var Collection<int, TournamentTeam>
     */
    #[ORM\OneToMany(targetEntity: TournamentTeam::class, mappedBy: 'tournament')]
    private Collection $tournamentTeams;

    /**
     * @var Collection<int, TournamentMatch>
     */
    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'tournament')]
    private Collection $team1;

    public function __construct()
    {
        $this->tournamentFields = new ArrayCollection();
        $this->tournamentTeams = new ArrayCollection();
        $this->team1 = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
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

    /**
     * @return Collection<int, TournamentField>
     */
    public function getTournamentFields(): Collection
    {
        return $this->tournamentFields;
    }

    public function addTournamentField(TournamentField $tournamentField): static
    {
        if (!$this->tournamentFields->contains($tournamentField)) {
            $this->tournamentFields->add($tournamentField);
            $tournamentField->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentField(TournamentField $tournamentField): static
    {
        if ($this->tournamentFields->removeElement($tournamentField)) {
            if ($tournamentField->getTournament() === $this) {
                $tournamentField->setTournament(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TournamentTeam>
     */
    public function getTournamentTeams(): Collection
    {
        return $this->tournamentTeams;
    }

    public function addTournamentTeam(TournamentTeam $tournamentTeam): static
    {
        if (!$this->tournamentTeams->contains($tournamentTeam)) {
            $this->tournamentTeams->add($tournamentTeam);
            $tournamentTeam->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentTeam(TournamentTeam $tournamentTeam): static
    {
        if ($this->tournamentTeams->removeElement($tournamentTeam)) {
            // set the owning side to null (unless already changed)
            if ($tournamentTeam->getTournament() === $this) {
                $tournamentTeam->setTournament(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TournamentMatch>
     */
    public function getTeam1(): Collection
    {
        return $this->team1;
    }

    public function addTeam1(TournamentMatch $team1): static
    {
        if (!$this->team1->contains($team1)) {
            $this->team1->add($team1);
            $team1->setTournament($this);
        }

        return $this;
    }

    public function removeTeam1(TournamentMatch $team1): static
    {
        if ($this->team1->removeElement($team1)) {
            // set the owning side to null (unless already changed)
            if ($team1->getTournament() === $this) {
                $team1->setTournament(null);
            }
        }

        return $this;
    }
}
