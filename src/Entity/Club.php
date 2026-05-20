<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Field;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Field::class)]
    private Collection $fields;

    /**
     * @var Collection<int, ClubOpeningHours>
     */
    #[ORM\OneToMany(targetEntity: ClubOpeningHours::class, mappedBy: 'club')]
    private Collection $openingHours;

    /**
     * @var Collection<int, ClubClosure>
     */
    #[ORM\OneToMany(targetEntity: ClubClosure::class, mappedBy: 'club')]
    private Collection $closures;

    /**
     * @var Collection<int, Tournament>
     */
    #[ORM\OneToMany(targetEntity: Tournament::class, mappedBy: 'clubId')]
    private Collection $tournaments;

    #[ORM\ManyToOne(inversedBy: 'clubs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    /**
     * @var Collection<int, ClubUser>
     */
    #[ORM\OneToMany(targetEntity: ClubUser::class, mappedBy: 'clubId')]
    private Collection $clubUsers;

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

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
        $this->closures = new ArrayCollection();
        $this->tournaments = new ArrayCollection();
        $this->clubUsers = new ArrayCollection();
    }

    /**
     * @return Collection<int, ClubOpeningHours>
     */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(ClubOpeningHours $openingHour): static
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setClub($this);
        }

        return $this;
    }

    public function removeOpeningHour(ClubOpeningHours $openingHour): static
    {
        if ($this->openingHours->removeElement($openingHour)) {
            // set the owning side to null (unless already changed)
            if ($openingHour->getClub() === $this) {
                $openingHour->setClub(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubClosure>
     */
    public function getClosures(): Collection
    {
        return $this->closures;
    }

    public function addClosure(ClubClosure $closure): static
    {
        if (!$this->closures->contains($closure)) {
            $this->closures->add($closure);
            $closure->setClub($this);
        }

        return $this;
    }

    public function removeClosure(ClubClosure $closure): static
    {
        if ($this->closures->removeElement($closure)) {
            // set the owning side to null (unless already changed)
            if ($closure->getClub() === $this) {
                $closure->setClub(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tournament>
     */
    public function getTournaments(): Collection
    {
        return $this->tournaments;
    }

    public function addTournament(Tournament $tournament): static
    {
        if (!$this->tournaments->contains($tournament)) {
            $this->tournaments->add($tournament);
            $tournament->setClubId($this);
        }

        return $this;
    }

    public function removeTournament(Tournament $tournament): static
    {
        if ($this->tournaments->removeElement($tournament)) {
            // set the owning side to null (unless already changed)
            if ($tournament->getClubId() === $this) {
                $tournament->setClubId(null);
            }
        }

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

    /**
     * @return Collection<int, ClubUser>
     */
    public function getClubUsers(): Collection
    {
        return $this->clubUsers;
    }

    public function addClubUser(ClubUser $clubUser): static
    {
        if (!$this->clubUsers->contains($clubUser)) {
            $this->clubUsers->add($clubUser);
            $clubUser->setClubId($this);
        }

        return $this;
    }

    public function removeClubUser(ClubUser $clubUser): static
    {
        if ($this->clubUsers->removeElement($clubUser)) {
            // set the owning side to null (unless already changed)
            if ($clubUser->getClubId() === $this) {
                $clubUser->setClubId(null);
            }
        }

        return $this;
    }
}
