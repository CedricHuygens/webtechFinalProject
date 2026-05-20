<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use App\Entity\Team;
use App\Repository\ClubUserRepository;
use App\Repository\FieldRepository;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\TournamentField;
use App\Entity\Booking;
use App\Repository\ClubRepository;
use Symfony\Component\Routing\Attribute\Route;

class TournamentController extends AbstractController
{
    #[Route('/tournaments', name: 'app_tournaments')]
    public function index(
        TournamentRepository $tournamentRepository,
        ClubUserRepository $clubUserRepository,
        EntityManagerInterface $entityManager,
        ClubRepository $clubRepository,
        Request $request
    ): Response {
        $tournaments = $tournamentRepository->findAll();
        $user = $this->getUser();
        $selectedClub = $request->query->get('club');
        $selectedDate = $request->query->get('date');
        $clubs = $clubRepository->findAll();

        $enrolledTournamentIds = [];
        $enrolledTeams = [];
        $resultTournamentIds = [];

        $finishedMatches = $entityManager->getRepository(TournamentMatch::class)->findBy([
            'round' => 2,
        ]);

        foreach ($finishedMatches as $match) {
            if ($match->getWinner() !== null) {
                $resultTournamentIds[] = $match->getTournament()->getId();
            }
        }

        $resultTournamentIds = array_unique($resultTournamentIds);

        if ($user) {
            $teams = $entityManager->getRepository(\App\Entity\Team::class)
                ->createQueryBuilder('t')
                ->where('t.user1 = :user')
                ->orWhere('t.user2 = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

            foreach ($teams as $team) {
                $tournamentTeams = $entityManager->getRepository(TournamentTeam::class)->findBy([
                    'team' => $team,
                ]);

                foreach ($tournamentTeams as $tournamentTeam) {
                    $tournament = $tournamentTeam->getTournament();

                    $enrolledTournamentIds[] = $tournament->getId();
                    $enrolledTeams[$tournament->getId()] = $team;
                }
            }
        }

        if ($this->isGranted('ROLE_ADMIN')) {

            $club = $clubRepository->findOneBy([
                'user' => $user,
            ]);

            $tournaments = $club
                ? $tournamentRepository->findBy(['club' => $club])
                : [];
        }

        usort($tournaments, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        $myTournaments = [];
        $availableTournaments = [];
        $finishedTournaments = [];

        foreach ($tournaments as $tournament) {
            $isEnrolled = in_array($tournament->getId(), $enrolledTournamentIds);
            $hasResults = in_array($tournament->getId(), $resultTournamentIds);

            if ($this->isGranted('ROLE_ADMIN')) {
                if ($hasResults) {
                    $finishedTournaments[] = $tournament;
                } else {
                    $availableTournaments[] = $tournament;
                }
            } else {
                if ($hasResults && $isEnrolled) {
                    $finishedTournaments[] = $tournament;
                } elseif ($isEnrolled) {
                    $myTournaments[] = $tournament;
                } elseif (!$hasResults) {
                    $availableTournaments[] = $tournament;
                }
            }
        }

        if ($selectedClub) {
            $availableTournaments = array_filter($availableTournaments, function ($tournament) use ($selectedClub) {
                return $tournament->getClub()->getId() == $selectedClub;
            });
        }

        if ($selectedDate) {
            $availableTournaments = array_filter($availableTournaments, function ($tournament) use ($selectedDate) {
                return $tournament->getDate()->format('Y-m-d') === $selectedDate;
            });
        }

        return $this->render('tournaments/index.html.twig', [
            'myTournaments' => $myTournaments,
            'availableTournaments' => $availableTournaments,
            'finishedTournaments' => $finishedTournaments,
            'enrolledTournamentIds' => $enrolledTournamentIds,
            'enrolledTeams' => $enrolledTeams,
            'resultTournamentIds' => $resultTournamentIds,
            'clubs' => $clubs,
            'selectedClub' => $selectedClub,
            'selectedDate' => $selectedDate,
        ]);
    }

    #[Route('/tournaments/add', name: 'app_tournaments_add')]
    public function addTournaments(
        Request $request,
        EntityManagerInterface $entityManager,
        ClubRepository $clubRepository,
        FieldRepository $fieldRepository
    ): Response {
        $club = $clubRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            throw $this->createNotFoundException();
        }

        $fields = $fieldRepository->findBy([
            'club' => $club,
        ]);

        if ($request->isMethod('POST')) {
            $tournament = new Tournament();

            $tournament->setName($request->request->get('name'));
            $tournament->setDate(new \DateTime($request->request->get('date')));
            $tournament->setClub($club);

            $entityManager->persist($tournament);
            $entityManager->flush();

            $fieldId = $request->request->get('field_id');

            $field = $fieldRepository->findOneBy([
                'id' => $fieldId,
                'club' => $club,
            ]);

            if (!$field) {
                throw $this->createAccessDeniedException('Field does not belong to your club.');
            }

            $tournamentField = new TournamentField();
            $tournamentField->setTournament($tournament);
            $tournamentField->setField($field);

            $entityManager->persist($tournamentField);

            $tournamentDate = $tournament->getDate()->format('Y-m-d');

            $bookingDate = \DateTimeImmutable::createFromMutable($tournament->getDate());

            $bookings = $entityManager->getRepository(Booking::class)->findBy([
                'field' => $field,
                'date' => $bookingDate,
            ]);

            $deletedBookings = count($bookings);

            foreach ($bookings as $booking) {
                $entityManager->remove($booking);
            }

            if ($deletedBookings > 0) {
                $this->addFlash(
                    'error',
                    $deletedBookings . ' booking(s) were automatically cancelled because of this tournament.'
                );
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_tournaments');
        }

        return $this->render('tournaments/addTournament.html.twig', [
            'fields' => $fields,
        ]);
    }

    #[Route('/tournaments/{id}/enroll', name: 'app_tournament_enroll')]
    public function enroll(
        int $id,
        TournamentRepository $tournamentRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $tournament = $tournamentRepository->find($id);

        if (!$tournament) {
            throw $this->createNotFoundException();
        }

        $teamCount = $entityManager->getRepository(TournamentTeam::class)->count([
            'tournament' => $tournament,
        ]);

        if ($teamCount >= 4) {
            $this->addFlash('error', 'This tournament is already full.');
            return $this->redirectToRoute('app_tournaments');
        }

        $user = $this->getUser();
        $teams = [];

        $teamsOfUser = $entityManager->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->where('t.user1 = :user')
            ->orWhere('t.user2 = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        foreach ($teamsOfUser as $team) {
            $player1Id = $team->getUser1()->getId();
            $player2Id = $team->getUser2()->getId();

            $alreadyEnrolled = false;

            $tournamentTeams = $entityManager->getRepository(TournamentTeam::class)->findBy([
                'tournament' => $tournament,
            ]);

            foreach ($tournamentTeams as $tournamentTeam) {
                $enrolledTeam = $tournamentTeam->getTeam();

                if (
                    $enrolledTeam->getUser1()->getId() === $player1Id ||
                    $enrolledTeam->getUser2()->getId() === $player1Id ||
                    $enrolledTeam->getUser1()->getId() === $player2Id ||
                    $enrolledTeam->getUser2()->getId() === $player2Id
                ) {
                    $alreadyEnrolled = true;
                    break;
                }
            }

            if (!$alreadyEnrolled) {
                $teams[] = $team;
            }
        }

        if ($request->isMethod('POST')) {
            $teamId = $request->request->get('team_id');
            $selectedTeam = null;

            foreach ($teams as $team) {
                if ($team->getId() == $teamId) {
                    $selectedTeam = $team;
                    break;
                }
            }

            if (!$selectedTeam) {
                $this->addFlash('error', 'One (or both) of the persons in this team is already enrolled');
                return $this->redirectToRoute('app_tournaments');
            }

            $tournamentTeam = new TournamentTeam();
            $tournamentTeam->setTournament($tournament);
            $tournamentTeam->setTeam($selectedTeam);

            $entityManager->persist($tournamentTeam);
            $entityManager->flush();

            return $this->redirectToRoute('app_tournaments');
        }

        return $this->render('tournaments/enroll.html.twig', [
            'tournament' => $tournament,
            'teams' => $teams,
        ]);
    }

    #[Route('/tournaments/{id}/cancel', name: 'app_tournament_cancel')]
    public function cancelEnrollment(
        int $id,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        $tournament = $tournamentRepository->find($id);

        $teamsOfUser = $entityManager->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->where('t.user1 = :user')
            ->orWhere('t.user2 = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        foreach ($teamsOfUser as $team) {
            $tournamentTeam = $entityManager->getRepository(TournamentTeam::class)->findOneBy([
                'tournament' => $tournament,
                'team' => $team,
            ]);

            if ($tournamentTeam) {
                $entityManager->remove($tournamentTeam);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_tournaments');
    }

    #[Route('/tournaments/{id}/results', name: 'app_tournament_results')]
    public function results(
        int $id,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $tournament = $tournamentRepository->find($id);

        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found.');
        }

        $teams = $entityManager->getRepository(TournamentTeam::class)->findBy(
            ['tournament' => $tournament],
            ['id' => 'ASC']
        );

        $matches = $entityManager->getRepository(TournamentMatch::class)->findBy(
            ['tournament' => $tournament],
            ['round' => 'ASC', 'matchNumber' => 'ASC']
        );

        $semiMatches = [];
        $finalMatch = null;

        foreach ($matches as $match) {
            if ($match->getRound() === 1) {
                $semiMatches[] = $match;
            }

            if ($match->getRound() === 2) {
                $finalMatch = $match;
            }
        }

        $error = null;
        $semiFinal1 = null;
        $semiFinal2 = null;

        if (count($teams) < 4) {
            $error = 'Tournament needs 4 teams.';
        } elseif (count($teams) > 4) {
            $error = 'Too many teams.';
        } else {
            $semiFinal1 = [$teams[0], $teams[1]];
            $semiFinal2 = [$teams[2], $teams[3]];
        }

        if ($request->isMethod('POST') && !$error) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Only admins.');
            }

            $action = $request->request->get('action');

            if ($action === 'save_semis' && count($matches) === 0) {
                $s1t1 = (int) $request->request->get('semi1_score_team1');
                $s1t2 = (int) $request->request->get('semi1_score_team2');

                $s2t1 = (int) $request->request->get('semi2_score_team1');
                $s2t2 = (int) $request->request->get('semi2_score_team2');

                if ($s1t1 === $s1t2 || $s2t1 === $s2t2) {
                    $this->addFlash('error', 'Match cannot have an even score.');
                    return $this->redirectToRoute('app_tournament_results', ['id' => $tournament->getId()]);
                }

                $tt1 = $semiFinal1[0];
                $tt2 = $semiFinal1[1];
                $tt3 = $semiFinal2[0];
                $tt4 = $semiFinal2[1];

                $winner1 = $s1t1 > $s1t2 ? $tt1 : $tt2;
                $winner2 = $s2t1 > $s2t2 ? $tt3 : $tt4;

                $match1 = new TournamentMatch();
                $match1->setTournament($tournament);
                $match1->setTeam1($tt1);
                $match1->setTeam2($tt2);
                $match1->setTeam1Score($s1t1);
                $match1->setTeam2Score($s1t2);
                $match1->setWinner($winner1);
                $match1->setRound(1);
                $match1->setMatchNumber(1);

                $match2 = new TournamentMatch();
                $match2->setTournament($tournament);
                $match2->setTeam1($tt3);
                $match2->setTeam2($tt4);
                $match2->setTeam1Score($s2t1);
                $match2->setTeam2Score($s2t2);
                $match2->setWinner($winner2);
                $match2->setRound(1);
                $match2->setMatchNumber(2);

                $final = new TournamentMatch();
                $final->setTournament($tournament);
                $final->setTeam1($winner1);
                $final->setTeam2($winner2);
                $final->setRound(2);
                $final->setMatchNumber(1);

                $entityManager->persist($match1);
                $entityManager->persist($match2);
                $entityManager->persist($final);
                $entityManager->flush();

                return $this->redirectToRoute('app_tournament_results', ['id' => $tournament->getId()]);
            }

            if ($action === 'save_final' && $finalMatch) {
                $finalScoreTeam1 = (int) $request->request->get('final_score_team1');
                $finalScoreTeam2 = (int) $request->request->get('final_score_team2');

                if ($finalScoreTeam1 === $finalScoreTeam2) {
                    $this->addFlash('error', 'Finale cannot have an even score.');
                    return $this->redirectToRoute('app_tournament_results', ['id' => $tournament->getId()]);
                }

                $finalMatch->setTeam1Score($finalScoreTeam1);
                $finalMatch->setTeam2Score($finalScoreTeam2);
                $finalMatch->setWinner($finalScoreTeam1 > $finalScoreTeam2 ? $finalMatch->getTeam1() : $finalMatch->getTeam2());

                $entityManager->flush();

                return $this->redirectToRoute('app_tournament_results', ['id' => $tournament->getId()]);
            }
        }

        return $this->render('tournaments/results.html.twig', [
            'tournament' => $tournament,
            'teams' => $teams,
            'error' => $error,
            'semiFinal1' => $semiFinal1,
            'semiFinal2' => $semiFinal2,
            'matches' => $matches,
            'semiMatches' => $semiMatches,
            'finalMatch' => $finalMatch,
        ]);
    }

    #[Route('/tournaments/{id}/delete', name: 'app_tournament_delete', methods: ['POST'])]
    public function deleteTournament(
        Tournament $tournament,
        EntityManagerInterface $entityManager,
        ClubRepository $clubRepository
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club || $tournament->getClub()?->getId() !== $club->getId()) {
            throw $this->createAccessDeniedException();
        }

        $matches = $entityManager->getRepository(TournamentMatch::class)->findBy([
            'tournament' => $tournament,
        ]);

        foreach ($matches as $match) {
            $entityManager->remove($match);
        }

        $tournamentTeams = $entityManager->getRepository(TournamentTeam::class)->findBy([
            'tournament' => $tournament,
        ]);

        foreach ($tournamentTeams as $tournamentTeam) {
            $entityManager->remove($tournamentTeam);
        }

        $tournamentFields = $entityManager->getRepository(TournamentField::class)->findBy([
            'tournament' => $tournament,
        ]);

        foreach ($tournamentFields as $tournamentField) {
            $entityManager->remove($tournamentField);
        }

        $entityManager->remove($tournament);
        $entityManager->flush();

        $this->addFlash('success', 'Tournament deleted.');

        return $this->redirectToRoute('app_tournaments');
    }
}
