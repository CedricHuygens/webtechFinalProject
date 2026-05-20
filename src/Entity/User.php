<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface // voor hashing en te kunnen inloggen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column]
    private array $roles = [];


    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PadelMatchPlayer::class)]
    private Collection $matchPlayers;

    /**
     * @var Collection<int, PadelMatchLike>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchLike::class, mappedBy: 'user')]
    private Collection $padelMatchLikes;

    /**
     * @var Collection<int, PadelMatchComment>
     */
    #[ORM\OneToMany(targetEntity: PadelMatchComment::class, mappedBy: 'user')]
    private Collection $padelMatchComments;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'user1')]
    private Collection $teams;

    /**
     * @var Collection<int, Friend>
     */
    #[ORM\OneToMany(targetEntity: Friend::class, mappedBy: 'user1Id')]
    private Collection $friends;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'senderUserId')]
    private Collection $friendRequests;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'userId')]
    private Collection $notifications;

    /**
     * @var Collection<int, Club>
     */
    #[ORM\OneToMany(targetEntity: Club::class, mappedBy: 'userId')]
    private Collection $clubs;


    /**
     * @var Collection<int, ClubUser>
     */
    #[ORM\OneToMany(targetEntity: ClubUser::class, mappedBy: 'userId')]
    private Collection $clubUsers;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function __construct()
    {
        $this->profilePicture = 'uploads/profile_pictures/default.png';
        $this->roles = ['ROLE_USER'];
        $this->matchPlayers = new ArrayCollection();
        $this->padelMatchLikes = new ArrayCollection();
        $this->padelMatchComments = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->friendRequests = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->clubs = new ArrayCollection();
        $this->clubUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }

    public function addMatchPlayer(PadelMatchPlayer $matchPlayer): static
    {
        if (!$this->matchPlayers->contains($matchPlayer)) {
            $this->matchPlayers->add($matchPlayer);
            $matchPlayer->setUser($this);
        }

        return $this;
    }

    public function removeMatchPlayer(PadelMatchPlayer $matchPlayer): static
    {
        $this->matchPlayers->removeElement($matchPlayer);
        return $this;
    }

    /**
     * @return Collection<int, PadelMatchLike>
     */
    public function getPadelMatchLikes(): Collection
    {
        return $this->padelMatchLikes;
    }

    public function addPadelMatchLike(PadelMatchLike $padelMatchLike): static
    {
        if (!$this->padelMatchLikes->contains($padelMatchLike)) {
            $this->padelMatchLikes->add($padelMatchLike);
            $padelMatchLike->setUser($this);
        }

        return $this;
    }

    public function removePadelMatchLike(PadelMatchLike $padelMatchLike): static
    {
        if ($this->padelMatchLikes->removeElement($padelMatchLike)) {
            // set the owning side to null (unless already changed)
            if ($padelMatchLike->getUser() === $this) {
                $padelMatchLike->setUser(null);
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
            $padelMatchComment->setUser($this);
        }

        return $this;
    }

    public function removePadelMatchComment(PadelMatchComment $padelMatchComment): static
    {
        if ($this->padelMatchComments->removeElement($padelMatchComment)) {
            // set the owning side to null (unless already changed)
            if ($padelMatchComment->getUser() === $this) {
                $padelMatchComment->setUser(null);
            }
        }

        return $this;
    }
    // Veplichte functies voor UserInterface:
    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email; // Of username
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setUser1($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            // set the owning side to null (unless already changed)
            if ($team->getUser1() === $this) {
                $team->setUser1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Friend>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(Friend $friend): static
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->setUser1Id($this);
        }

        return $this;
    }

    public function removeFriend(Friend $friend): static
    {
        if ($this->friends->removeElement($friend)) {
            // set the owning side to null (unless already changed)
            if ($friend->getUser1Id() === $this) {
                $friend->setUser1Id(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getFriendRequests(): Collection
    {
        return $this->friendRequests;
    }

    public function addFriendRequest(FriendRequest $friendRequest): static
    {
        if (!$this->friendRequests->contains($friendRequest)) {
            $this->friendRequests->add($friendRequest);
            $friendRequest->setSenderUserId($this);
        }

        return $this;
    }

    public function removeFriendRequest(FriendRequest $friendRequest): static
    {
        if ($this->friendRequests->removeElement($friendRequest)) {
            // set the owning side to null (unless already changed)
            if ($friendRequest->getSenderUserId() === $this) {
                $friendRequest->setSenderUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUserId($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUserId() === $this) {
                $notification->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Club>
     */
    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function addClub(Club $club): static
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs->add($club);
            $club->setUserId($this);
        }

        return $this;
    }

    public function removeClub(Club $club): static
    {
        if ($this->clubs->removeElement($club)) {
            // set the owning side to null (unless already changed)
            if ($club->getUserId() === $this) {
                $club->setUserId(null);
            }
        }

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
            $clubUser->setUserId($this);
        }

        return $this;
    }

    public function removeClubUser(ClubUser $clubUser): static
    {
        if ($this->clubUsers->removeElement($clubUser)) {
            // set the owning side to null (unless already changed)
            if ($clubUser->getUserId() === $this) {
                $clubUser->setUserId(null);
            }
        }

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}
