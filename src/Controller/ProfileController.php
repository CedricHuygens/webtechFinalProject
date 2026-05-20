<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\PadelMatchPlayerRepository;
use App\Repository\FriendRepository;

class ProfileController extends Abstractcontroller
{
    #[Route('/profile', name: 'profile')]
    public function index(PadelMatchPlayerRepository $padelMatchPlayerRepository, FriendRepository $friendRepository): Response
    {
        $user = $this->getUser();
        $stats = $this->getStats($padelMatchPlayerRepository);
        $friends = $friendRepository->getFriendsFromUser($user);
        $friendsCount = count($friends);
        return $this->render('profile.html.twig', [
            'profilePicture' => $user->getProfilePicture() ?: 'uploads/profile_pictures/default.png',
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'totalMatches' => $stats['total'],
            'countMatchedWon' => $stats['won'],
            'countMatchedLost' => $stats['lost'],
            'winrate' => $stats['winrate'],
            'friendsCount' => $friendsCount
        ]);
    }

    #[Route('/goToProfileSettings', name: 'goToProfileSettings')]
    public function goToProfileSettings()
    {
        return $this->render('profileSettings.html.twig');
    }

    public function getStats(PadelMatchPlayerRepository $padelMatchPlayerRepository)
    {
        $totalMatches = 0;
        $matchesWon = 0;
        $matchesLost = 0;
        $winrate = 0;
        $user = $this->getUser();
        $matchesOfUser = $padelMatchPlayerRepository->getMatchesOfUser($user);
        foreach ($matchesOfUser as $p){
            $match = $p->getPadelMatch();
            $team = $p->getTeamNumber();
            $totalMatches ++;
            $team1Score = $match->getTeam1Score();
            $team2Score = $match->getTeam2Score();
            if (($team === 1 && $team1Score > $team2Score) || ($team === 2 && $team2Score > $team1Score)
            ) {
                $matchesWon++;
            }
        }

        $matchesLost = $totalMatches - $matchesWon;

        if($totalMatches > 0){
            $winrate = ($matchesWon/$totalMatches)*100;
        }

        return ['total' => $totalMatches, 'won' => $matchesWon, 'lost' => $matchesLost, 'winrate' => $winrate];
    }

}
