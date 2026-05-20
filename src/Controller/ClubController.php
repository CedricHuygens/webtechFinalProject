<?php

namespace App\Controller;

use App\Repository\ClubUserRepository;
use App\Repository\ClubRepository;
use App\Repository\ClubRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Club;
use App\Entity\ClubUser;
use Doctrine\ORM\EntityManagerInterface;

class ClubController extends AbstractController
{
    #[Route('/club', name: 'club_page')]
    public function club(
        ClubUserRepository $clubUserRepo,
        ClubRepository $clubRepo
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            $clubs = $clubRepo->findBy([
                'user' => $this->getUser(),
            ]);

            if (!$clubs) {
                return $this->render('club/index.html.twig', [
                    'club' => null,
                    'clubUsers' => [],
                    'clubs' => [],
                    'adminNoClub' => true,
                ]);
            }

            $clubUsersByClub = [];

            foreach ($clubs as $club) {
                $clubUsersByClub[$club->getId()] = $clubUserRepo->findBy([
                    'club' => $club,
                ]);
            }

            return $this->render('club/index.html.twig', [
                'club' => null,
                'clubUsers' => [],
                'clubs' => $clubs,
                'clubUsersByClub' => $clubUsersByClub,
                'adminNoClub' => false,
            ]);
        }

        $myClubUser = $clubUserRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if ($myClubUser) {
            $club = $myClubUser->getClub();

            $clubUsers = $clubUserRepo->findBy([
                'club' => $club,
            ]);

            return $this->render('club/index.html.twig', [
                'club' => $club,
                'clubUsers' => $clubUsers,
                'clubs' => [],
                'adminNoClub' => false,
            ]);
        }

        return $this->render('club/index.html.twig', [
            'club' => null,
            'clubUsers' => [],
            'clubs' => $clubRepo->findAll(),
            'adminNoClub' => false,
        ]);
    }

    #[Route('/admin/club/users', name: 'admin_club_users')]
    public function adminUsers(
        ClubRepository $clubRepo,
        ClubUserRepository $clubUserRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'Geen club gekoppeld aan deze admin.');
            return $this->redirectToRoute('admin_my_fields');
        }

        $clubUsers = $clubUserRepo->findBy([
            'club' => $club,
        ]);

        return $this->render('admin/club_users.html.twig', [
            'club' => $club,
            'clubUsers' => $clubUsers,
        ]);
    }

    #[Route('/club/enroll/{id}', name: 'club_enroll', methods: ['POST'])]
    public function enroll(
        Club $club,
        ClubUserRepository $clubUserRepo,
        EntityManagerInterface $em
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('club_error', 'Admins kunnen zich niet enrollen voor een club.');
            return $this->redirectToRoute('club_page');
        }

        $existing = $clubUserRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if ($existing) {
            return $this->redirectToRoute('club_page');
        }

        $clubUser = new ClubUser();
        $clubUser->setUser($this->getUser());
        $clubUser->setClub($club);
        $clubUser->setCreatedAt(new \DateTimeImmutable());

        $em->persist($clubUser);
        $em->flush();

        return $this->redirectToRoute('club_page');
    }

    #[Route('/club/leave', name: 'club_leave', methods: ['POST'])]
    public function leaveClub(
        ClubUserRepository $clubUserRepo,
        EntityManagerInterface $em
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('club_error', 'Admins kunnen hun club niet verlaten.');
            return $this->redirectToRoute('club_page');
        }

        $clubUser = $clubUserRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$clubUser) {
            return $this->redirectToRoute('club_page');
        }

        $em->remove($clubUser);
        $em->flush();

        $this->addFlash('club_success', 'Left the club.');

        return $this->redirectToRoute('club_page');
    }

    #[Route('/admin/club/remove-user/{id}', name: 'admin_club_remove_user', methods: ['POST'])]
    public function removeUserFromClub(
        ClubUser $clubUser,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($clubUser);
        $em->flush();

        $this->addFlash('club_success', 'User removed from club.');

        return $this->redirectToRoute('club_page');
    }
}
