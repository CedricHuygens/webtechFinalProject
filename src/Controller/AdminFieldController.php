<?php

namespace App\Controller;

use App\Entity\Field;
use App\Entity\ClubClosure;
use App\Entity\ClubOpeningHours;
use App\Repository\BookingRepository;
use App\Repository\TournamentRepository;
use App\Repository\ClubClosureRepository;
use App\Repository\ClubOpeningHoursRepository;
use App\Repository\ClubRepository;
use App\Repository\FieldRepository;
use App\Entity\Booking;
use App\Entity\TournamentField;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminFieldController extends AbstractController
{
    #[Route('/admin/opening-hours', name: 'admin_opening_hours', methods: ['GET', 'POST'])]
    public function openingHours(
        Request $request,
        ClubOpeningHoursRepository $openingRepo,
        EntityManagerInterface $em,
        ClubRepository $clubRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        $hours = $openingRepo->findBy([
            'club' => $club,
        ]);

        $order = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        usort($hours, function ($a, $b) use ($order) {
            return $order[$a->getDayOfWeek()] <=> $order[$b->getDayOfWeek()];
        });

        if ($request->isMethod('POST')) {
            foreach ($hours as $hour) {
                $id = $hour->getId();

                $newOpenTime = $request->request->get('open_time_' . $id);
                $newCloseTime = $request->request->get('close_time_' . $id);

                $hour->setOpenTime(new \DateTimeImmutable($newOpenTime));
                $hour->setCloseTime(new \DateTimeImmutable($newCloseTime));

                $bookings = $em->getRepository(Booking::class)->findAll();

                foreach ($bookings as $booking) {
                    if ($booking->getField()->getClub()->getId() !== $club->getId()) {
                        continue;
                    }

                    $bookingDay = strtolower($booking->getDate()->format('l'));

                    if ($bookingDay !== strtolower($hour->getDayOfWeek())) {
                        continue;
                    }

                    $bookingStartTime = $booking->getStartTime()->format('H:i');
                    $bookingEndTime = $booking->getEndTime()->format('H:i');

                    if ($bookingStartTime < $newOpenTime || $bookingEndTime > $newCloseTime) {
                        $em->remove($booking);
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('admin_opening_hours');
        }

        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $availableDays = $allDays;

        return $this->render('admin/opening_hours.html.twig', [
            'hours' => $hours,
            'availableDays' => $availableDays,
        ]);
    }

    #[Route('/admin/opening-hours/add', name: 'admin_opening_hours_add', methods: ['POST'])]
    public function addOpeningHours(
        Request $request,
        EntityManagerInterface $em,
        ClubOpeningHoursRepository $openingRepo,
        ClubRepository $clubRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        $dayOfWeek = $request->request->get('day_of_week');

        $newOpen = $request->request->get('open_time');
        $newClose = $request->request->get('close_time');

        $existingHours = $openingRepo->findBy([
            'club' => $club,
            'dayOfWeek' => $dayOfWeek,
        ]);

        foreach ($existingHours as $existingHour) {
            $existingOpen = $existingHour->getOpenTime()->format('H:i');
            $existingClose = $existingHour->getCloseTime()->format('H:i');

            $overlap = $newOpen < $existingClose && $newClose > $existingOpen;

            if ($overlap) {
                $this->addFlash('error', 'Opening hours overlap with existing hours.');
                return $this->redirectToRoute('admin_opening_hours');
            }
        }

        $openingHours = new ClubOpeningHours();
        $openingHours->setClub($club);
        $openingHours->setDayOfWeek($dayOfWeek);
        $openingHours->setOpenTime(new \DateTimeImmutable($newOpen));
        $openingHours->setCloseTime(new \DateTimeImmutable($newClose));

        $em->persist($openingHours);
        $em->flush();

        return $this->redirectToRoute('admin_opening_hours');
    }

    #[Route('/admin/opening-hours/delete/{id}', name: 'admin_opening_hours_delete')]
    public function deleteOpeningHours(
        ClubOpeningHours $openingHours,
        EntityManagerInterface $em,
        ClubRepository $clubRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club || $openingHours->getClub()?->getId() !== $club->getId()) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        $bookings = $em->getRepository(\App\Entity\Booking::class)->findAll();

        foreach ($bookings as $booking) {
            if ($booking->getField()->getClub()->getId() !== $club->getId()) {
                continue;
            }

            $bookingDay = strtolower($booking->getDate()->format('l'));

            if ($bookingDay === strtolower($openingHours->getDayOfWeek())) {
                $em->remove($booking);
            }
        }

        $em->remove($openingHours);
        $em->flush();

        return $this->redirectToRoute('admin_opening_hours');
    }

    #[Route('/admin/fields/new', name: 'admin_field_new')]
    public function new(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/new_field.html.twig');
    }

    #[Route('/admin/closures', name: 'admin_closures', methods: ['GET', 'POST'])]
    public function closures(
        Request $request,
        EntityManagerInterface $em,
        ClubClosureRepository $closureRepo,
        ClubRepository $clubRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        if ($request->isMethod('POST')) {
            $closureDate = new \DateTimeImmutable($request->request->get('date'));

            $closure = new ClubClosure();
            $closure->setClub($club);
            $closure->setDate($closureDate);
            $closure->setReason($request->request->get('reason'));

            $bookings = $em->getRepository(Booking::class)->findBy([
                'date' => $closureDate,
            ]);

            foreach ($bookings as $booking) {
                if ($booking->getField()->getClub()->getId() === $club->getId()) {
                    $em->remove($booking);
                }
            }

            $em->persist($closure);
            $em->flush();

            return $this->redirectToRoute('admin_closures');
        }

        $closures = $closureRepo->findBy([
            'club' => $club,
        ]);

        return $this->render('admin/closures.html.twig', [
            'closures' => $closures,
        ]);
    }

    #[Route('/admin/my-fields', name: 'admin_my_fields')]
    public function myFields(
        FieldRepository $fieldRepo,
        BookingRepository $bookingRepo,
        TournamentRepository $tournamentRepo,
        ClubRepository $clubRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        $fields = $fieldRepo->findBy([
            'club' => $club,
        ]);

        $bookings = $bookingRepo->findBy([], [
            'date' => 'ASC',
            'startTime' => 'ASC',
        ]);

        $tournaments = $tournamentRepo->findBy([
            'club' => $club,
        ], [
            'date' => 'ASC',
        ]);

        $tournamentFields = [];

        foreach ($tournaments as $tournament) {
            $fieldsForTournament = $em->getRepository(TournamentField::class)->findBy([
                'tournament' => $tournament,
            ]);

            foreach ($fieldsForTournament as $tournamentField) {
                $tournamentFields[] = $tournamentField;
            }
        }

        return $this->render('admin/my_fields.html.twig', [
            'fields' => $fields,
            'tournaments' => $tournaments,
            'tournamentFields' => $tournamentFields,
            'bookings' => $bookings,
        ]);
    }

    #[Route('/admin/fields/add', name: 'admin_field_add', methods: ['POST'])]
    public function addField(
        Request $request,
        EntityManagerInterface $em,
        ClubRepository $clubRepo,
        FieldRepository $fieldRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club) {
            $this->addFlash('error', 'Geen club gekoppeld aan deze gebruiker.');
            return $this->redirectToRoute('admin_my_fields');
        }

        $location = trim($request->request->get('location'));
        $type = strtolower(trim($request->request->get('type')));
        $fieldNumber = (int) $request->request->get('field_number');

        if (!$location) {
            $this->addFlash('error', 'Locatie is verplicht.');
            return $this->redirectToRoute('admin_my_fields');
        }

        if (!in_array($type, ['indoor', 'outdoor'], true)) {
            $this->addFlash('error', 'Kies een geldig veldtype (indoor/outdoor).');
            return $this->redirectToRoute('admin_my_fields');
        }

        if ($fieldNumber <= 0) {
            $this->addFlash('error', 'Court number moet groter zijn dan 0.');
            return $this->redirectToRoute('admin_my_fields');
        }

        $existingField = $fieldRepo->findOneBy([
            'club' => $club,
            'fieldNumber' => $fieldNumber,
        ]);

        if ($existingField) {
            $this->addFlash('error', 'Court number already exists for this club.');
            return $this->redirectToRoute('admin_my_fields');
        }

        $field = new Field();
        $field->setClub($club);
        $field->setLocation($location);
        $field->setType($type);
        $field->setFieldNumber($fieldNumber);
        $field->setName('Court ' . $fieldNumber);

        $em->persist($field);
        $em->flush();

        return $this->redirectToRoute('admin_my_fields');
    }

    #[Route('/admin/fields/delete/{id}', name: 'admin_field_delete', methods: ['POST'])]
    public function deleteField(
        Field $field,
        EntityManagerInterface $em,
        ClubRepository $clubRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $club = $clubRepo->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$club || $field->getClub()?->getId() !== $club->getId()) {
            $this->addFlash('error', 'No club associated with this user. Please contact us.');
            return $this->redirectToRoute('contactInfo');
        }

        $tournamentFields = $em->getRepository(TournamentField::class)->findBy([
            'field' => $field,
        ]);

        foreach ($tournamentFields as $tournamentField) {
            $tournament = $tournamentField->getTournament();

            $matches = $em->getRepository(TournamentMatch::class)->findBy([
                'tournament' => $tournament,
            ]);

            foreach ($matches as $match) {
                $em->remove($match);
            }

            $tournamentTeams = $em->getRepository(TournamentTeam::class)->findBy([
                'tournament' => $tournament,
            ]);

            foreach ($tournamentTeams as $tournamentTeam) {
                $em->remove($tournamentTeam);
            }

            $allTournamentFields = $em->getRepository(TournamentField::class)->findBy([
                'tournament' => $tournament,
            ]);

            foreach ($allTournamentFields as $fieldLink) {
                $em->remove($fieldLink);
            }

            $em->remove($tournament);
        }

        $bookings = $em->getRepository(Booking::class)->findBy([
            'field' => $field,
        ]);

        foreach ($bookings as $booking) {
            $em->remove($booking);
        }

        $em->remove($field);
        $em->flush();

        $this->addFlash('success', 'Field deleted.');

        return $this->redirectToRoute('admin_my_fields');
    }
}
