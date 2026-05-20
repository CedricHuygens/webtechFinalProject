<?php

namespace App\Entity;

use App\Repository\TournamentMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
class TournamentMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentTeam $team1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentTeam $team2 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TournamentTeam $winner = null;

    #[ORM\Column]
    private ?int $round = null;

    #[ORM\Column]
    private ?int $matchNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $team1Score = null;

    #[ORM\Column(nullable: true)]
    private ?int $team2Score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function getTeam1(): ?TournamentTeam
    {
        return $this->team1;
    }

    public function setTeam1(?TournamentTeam $team1): static
    {
        $this->team1 = $team1;
        return $this;
    }

    public function getTeam2(): ?TournamentTeam
    {
        return $this->team2;
    }

    public function setTeam2(?TournamentTeam $team2): static
    {
        $this->team2 = $team2;
        return $this;
    }

    public function getWinner(): ?TournamentTeam
    {
        return $this->winner;
    }

    public function setWinner(?TournamentTeam $winner): static
    {
        $this->winner = $winner;
        return $this;
    }

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(int $round): static
    {
        $this->round = $round;
        return $this;
    }

    public function getMatchNumber(): ?int
    {
        return $this->matchNumber;
    }

    public function setMatchNumber(int $matchNumber): static
    {
        $this->matchNumber = $matchNumber;
        return $this;
    }

    public function getTeam1Score(): ?int
    {
        return $this->team1Score;
    }

    public function setTeam1Score(?int $team1Score): static
    {
        $this->team1Score = $team1Score;
        return $this;
    }

    public function getTeam2Score(): ?int
    {
        return $this->team2Score;
    }

    public function setTeam2Score(?int $team2Score): static
    {
        $this->team2Score = $team2Score;
        return $this;
    }
}
