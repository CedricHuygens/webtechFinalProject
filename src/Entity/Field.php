<?php

namespace App\Entity;

use App\Repository\FieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Club;

#[ORM\Entity(repositoryClass: FieldRepository::class)]
class Field
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $fieldNumber = null; // Dit was field_number in de DB

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'field')]
    private Collection $bookings; // Ik heb 'date' hernoemd naar 'bookings' (logischer)

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    /**
     * @var Collection<int, TournamentField>
     */
    #[ORM\OneToMany(targetEntity: TournamentField::class, mappedBy: 'fieldId')]
    private Collection $tournamentFields;

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->tournamentFields = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getFieldNumber(): ?int { return $this->fieldNumber; }
    public function setFieldNumber(int $fieldNumber): self { $this->fieldNumber = $fieldNumber; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(string $location): self { $this->location = $location; return $this; }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection { return $this->bookings; }

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
            $tournamentField->setFieldId($this);
        }

        return $this;
    }

    public function removeTournamentField(TournamentField $tournamentField): static
    {
        if ($this->tournamentFields->removeElement($tournamentField)) {
            // set the owning side to null (unless already changed)
            if ($tournamentField->getFieldId() === $this) {
                $tournamentField->setFieldId(null);
            }
        }

        return $this;
    }
}
