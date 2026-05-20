<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user2 = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, TournamentTeam>
     */
    #[ORM\OneToMany(targetEntity: TournamentTeam::class, mappedBy: 'teamId')]
    private Collection $tournamentTeams;


    public function __construct()
    {
        $this->tournamentTeams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser1(): ?User
    {
        return $this->user1;
    }

    public function setUser1(?User $user1): static
    {
        $this->user1 = $user1;

        return $this;
    }

    public function getUser2(): ?User
    {
        return $this->user2;
    }

    public function setUser2(?User $user2): static
    {
        $this->user2 = $user2;

        return $this;
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

    /**
     * @return Collection<int, TournamentTeam>
     */
    public function getTournamentTeams(): Collection
    {
        return $this->tournamentTeams;
    }

    public function addTournamentTeams(TournamentTeam $tournamentTeams): static
    {
        if (!$this->tournamentTeams->contains($tournamentTeams)) {
            $this->tournamentTeams->add($tournamentTeams);
            $tournamentTeams->setTeamId($this);
        }

        return $this;
    }

    public function removeTournamentTeams2(TournamentTeam $tournamentTeams1): static
    {
        if ($this->tournamentTeams->removeElement($tournamentTeams1)) {
            // set the owning side to null (unless already changed)
            if ($tournamentTeams1->getTeamId() === $this) {
                $tournamentTeams1->setTeamId(null);
            }
        }

        return $this;
    }

}
