<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Repository\PadelMatchRepository;
use App\Repository\PadelMatchCommentRepository;
use App\Repository\PadelMatchCommentCommentRepository;
use App\Repository\PadelMatchCommentLikeRepository;
use App\Repository\PadelMatchLikeRepository;
use App\Repository\PadelMatchPlayerRepository;
use App\Repository\TournamentTeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamsController extends AbstractController
{
    #[Route('/teams', name: 'teams')]
    public function team(Request $request, TeamRepository $teamRepository, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        $teamForm = $this->createFormBuilder()
            ->add('teamMate', TextType::class, ['attr' => ['required' => 'true']])
            ->add('teamName', TextType::class, ['attr' => ['required' => 'true']])
            ->getForm();

        $teamForm->handleRequest($request);

        if ($teamForm->isSubmitted() && $teamForm->isValid()) { // isSubmitted() checkt of het gestuurd is via een POST request. isValid() checkt of alle validatieregels zijn doorstaan ('required' fields ingevuld)
            $friendUsername = $teamForm->get('teamMate')->getData();
            $friend = $userRepository->findOneBy(['username' => $friendUsername]);
            if (!$friend) { // If, in case, the JS get's compromised this will check also if the user exists so the DB doesn't get compromised also
                $this->addFlash('error', 'User not found.'); // in case it isn't a user it will display an error message above the form
                return $this->redirectToRoute('teams');
            }
            $availableFriends = $teamRepository->findAvailableFriends($user); // If, in case, the JS get's compromised this will check also if the user is a friend of yours so the DB doesn't get compromised also
            if (!in_array($friend, $availableFriends)) {
                $this->addFlash('error', 'User is not a friend.'); // in case it isn't a user it will display an error message above the form
                return $this->redirectToRoute('teams');
            }
            $team = new Team();
            $team->setUser1($user);
            $team->setUser2($friend);
            $team->setName($teamForm->get('teamName')->getData());

            $em->persist($team);
            $em->flush();

            return $this->redirectToRoute('teams');
        }

        return $this->render('teams.html.twig', [
            'teams' => $teamRepository->findTeamsForUser($user),
            'availableFriends' => $teamRepository->findAvailableFriends($user),
            'teamForm' => $teamForm->createView()
        ]);
    }

    #[Route('/teams/leave/{id}', name: 'teams_leave')]
    public function leaveTeam(int $id, TeamRepository $teamRepository, EntityManagerInterface $em, PadelMatchRepository $padelMatchRepository, PadelMatchLikeRepository $padelMatchLikeRepository, PadelMatchPlayerRepository $padelMatchPlayerRepository, PadelMatchCommentRepository $padelMatchCommentRepository, PadelMatchCommentLikeRepository $padelMatchCommentLikeRepository, PadelMatchCommentCommentRepository $padelMatchCommentCommentRepository, TournamentTeamRepository $tournamentTeamRepository): Response
    {
        $user = $this->getUser();

        // Check if team exists
        $team = $teamRepository->find($id);
        if (!$team) {
            $this->addFlash('error', 'Team not found.');
            return $this->redirectToRoute('teams');
        }
        // Check if user that is logged in is in the team
        if ($team->getUser1() !== $user && $team->getUser2() !== $user) {
            $this->addFlash('error', 'You are not allowed to leave a team where you are not in.');
            return $this->redirectToRoute('teams');
        }
        // Search if team is still in matches
        $matches = array_merge(
            $padelMatchRepository->findBy(['team1' => $team]),
            $padelMatchRepository->findBy(['team2' => $team])
        );
        // Search if team is still connected to a tournament
        $tournamentTeams = $tournamentTeamRepository->findBy(['team' => $team]);
        foreach ($tournamentTeams as $tt) {
            $em->remove($tt);
        }

        // For each match it should delete all comments, all likes and all comments on comments
        foreach ($matches as $match) {
            // Collect and remove all likes
            $likes = $padelMatchLikeRepository->findBy([
                'padelMatch' => $match
            ]);
            foreach ($likes as $like) {
                $em->remove($like);
            }
            // Collect and remove all players
            $players = $padelMatchPlayerRepository->findBy([
                'padelMatch' => $match
            ]);
            foreach ($players as $player) {
                $em->remove($player);
            }
            // Collect all comments
            $comments = $padelMatchCommentRepository->findBy([
                'padelMatch' => $match
            ]);
            foreach ($comments as $comment) {
                // Collect and remove all likes on comments
                $commentLikes = $padelMatchCommentLikeRepository->findBy([
                    'comment' => $comment
                ]);
                foreach ($commentLikes as $like) {
                    $em->remove($like);
                }
                // Collect and remove all comments on comments
                $replies = $padelMatchCommentCommentRepository->findBy([
                    'mainComment' => $comment
                ]);
                foreach ($replies as $reply) {
                    $em->remove($reply);
                }
                // Remove all comments
                $em->remove($comment);
            }
            // Remove all matches
            $em->remove($match);
        }
        // Remove the team
        $em->remove($team);
        $em->flush();
        return $this->redirectToRoute('teams');
    }
}
