<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\ClubClosureRepository;
use App\Repository\ClubOpeningHoursRepository;
use App\Repository\ClubRepository;
use App\Repository\FieldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\TournamentField;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FieldsController extends AbstractController
{
    #[Route('/fields', name: 'app_fields')]
    public function index(
        FieldRepository $fieldRepo,
        ClubRepository $clubRepo,
        BookingRepository $bookingRepo,
        ClubOpeningHoursRepository $openingRepo,
        ClubClosureRepository $closureRepo,
        Request $request,
        HttpClientInterface $httpClient
    ): Response {
        $timezone = new \DateTimeZone('Europe/Brussels');
        $now = new \DateTimeImmutable('now', $timezone);
        $today = $now->format('Y-m-d');

        $searchDate = $request->query->get('search_date') ?: $today;
        $requestedTime = $request->query->get('time');
        $time = $requestedTime;
        $selectedClub = $request->query->get('club');
        $duration = (int) ($request->query->get('duration') ?: 1);
        $hasSearched = $request->query->has('search');

        $clubs = $clubRepo->findAll();

        $futureBookings = [];

        if (!$hasSearched && $this->getUser()) {
            $futureBookings = $bookingRepo->createQueryBuilder('b')
                ->where('b.user = :user')
                ->andWhere('b.date >= :today')
                ->setParameter('user', $this->getUser())
                ->setParameter('today', new \DateTimeImmutable($today))
                ->orderBy('b.date', 'ASC')
                ->addOrderBy('b.startTime', 'ASC')
                ->getQuery()
                ->getResult();
        }
        // Implementing wheater external API
        $weatherByBooking = [];
        $apiKey = $_ENV['OPENWEATHER_API_KEY'];

        foreach($futureBookings as $booking) {
            // Sicne the openweathermap API only provides data for the upcoming 5 days we need to check how many days it is until the booking
            $daysUntil = (int) (new \DateTimeImmutable('today'))->diff($booking->getDate())->days;

            if($daysUntil <= 5) {
                $location = $booking->getField()->getLocation();
                // Using Symfony's HTTPClient $httpClient->request('GET', url, options). In this case as specified by OpenWeatherMap the options should/could be the location, APIKEY (stored in .env file) and the unit of the result
                $response = $httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/forecast', [
                    'query' => ['q' => $location, 'appid' => $apiKey, 'units' => 'metric']
                ]);
                $data = $response->toArray();
                $targetDate = $booking->getDate()->format('Y-m-d');
                foreach ($data['list'] as $forecast) {
                    if (str_starts_with($forecast['dt_txt'], $targetDate)) { // OpenWeatherMap returns 40 forecasts (each three hours for 5 days). So we select the first forecast that aligns with the bookingDate
                        $weatherByBooking[$booking->getId()] = [ // We select our wanted parameters from forecast data which later will be used in the front-end
                            'temp' => round($forecast['main']['temp']),
                            'icon' => $forecast['weather'][0]['icon'],
                            'desc' => $forecast['weather'][0]['description'],
                            'rain_chance' => round($forecast['pop'] * 100),
                            'wind'      => round($forecast['wind']['speed']),
                        ];
                        break;
                    }
                }
            }

        }

        $errorMessage = null;
        $allowedTimes = [];
        $fieldCards = [];
        $openingHoursList = [];

        $isToday = ($searchDate === $today);
        $dayOfWeek = strtolower((new \DateTimeImmutable($searchDate))->format('l'));

        if ($selectedClub) {
            $isClosed = $closureRepo->findOneBy([
                'club' => (int) $selectedClub,
                'date' => new \DateTimeImmutable($searchDate),
            ]);

            if ($isClosed) {
                $errorMessage = 'This club is closed on this date.';
            } else {
                $openingHoursList = $openingRepo->findBy([
                    'club' => (int) $selectedClub,
                    'dayOfWeek' => $dayOfWeek,
                ]);
            }
        } else {
            $openingHoursList = $openingRepo->findBy([
                'dayOfWeek' => $dayOfWeek,
            ]);
        }

        if (!$errorMessage && !empty($openingHoursList)) {
            $startHour = min(array_map(
                fn($openingHours) => (int) $openingHours->getOpenTime()->format('H'),
                $openingHoursList
            ));

            $endHour = max(array_map(
                fn($openingHours) => (int) $openingHours->getCloseTime()->format('H'),
                $openingHoursList
            ));

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeValue = sprintf('%02d:00', $hour);

                if ($isToday) {
                    $slotDateTime = new \DateTimeImmutable($searchDate . ' ' . $timeValue, $timezone);

                    if ($slotDateTime <= $now) {
                        continue;
                    }
                }

                $allowedTimes[] = $timeValue;
            }
        }

        if ($hasSearched && !$errorMessage && empty($allowedTimes)) {
            $errorMessage = 'No opening hours found for this day.';
        }

        if (!$time || !in_array($time, $allowedTimes, true)) {
            $time = $allowedTimes[0] ?? null;
        }

        if ($hasSearched && $requestedTime && !in_array($requestedTime, $allowedTimes, true)) {
            $errorMessage = 'This time is not available.';
        }

        if ($hasSearched && $searchDate && $time && !$errorMessage) {
            $fields = $fieldRepo->findFieldsByClub($selectedClub ? (int) $selectedClub : null);

            $currentUser = $this->getUser();

            if (!$currentUser) {
                return $this->redirectToRoute('app_login');
            }

            $requestStart = new \DateTimeImmutable($searchDate . ' ' . $time, $timezone);
            $requestEnd = $requestStart->modify('+' . $duration . ' hour');

            $bookingsOnThatDate = $bookingRepo->findBy([
                'date' => new \DateTimeImmutable($searchDate),
            ]);

            foreach ($fields as $field) {
                $overlappingBooking = null;

                $fieldClub = $field->getClub();

                $fieldOpeningHours = $openingRepo->findOneBy([
                    'club' => $fieldClub,
                    'dayOfWeek' => $dayOfWeek,
                ]);

                $fieldIsClosed = $closureRepo->findOneBy([
                    'club' => $fieldClub,
                    'date' => new \DateTimeImmutable($searchDate),
                ]);

                if ($fieldIsClosed || !$fieldOpeningHours) {
                    continue;
                }

                $fieldOpen = new \DateTimeImmutable(
                    $searchDate . ' ' . $fieldOpeningHours->getOpenTime()->format('H:i:s'),
                    $timezone
                );

                $fieldClose = new \DateTimeImmutable(
                    $searchDate . ' ' . $fieldOpeningHours->getCloseTime()->format('H:i:s'),
                    $timezone
                );

                if ($requestStart < $fieldOpen || $requestEnd > $fieldClose) {
                    continue;
                }

                foreach ($bookingsOnThatDate as $booking) {
                    if ($booking->getField()->getId() !== $field->getId()) {
                        continue;
                    }

                    $bookingStart = new \DateTimeImmutable(
                        $searchDate . ' ' . $booking->getStartTime()->format('H:i:s'),
                        $timezone
                    );

                    $bookingEnd = new \DateTimeImmutable(
                        $searchDate . ' ' . $booking->getEndTime()->format('H:i:s'),
                        $timezone
                    );

                    $hasOverlap = $bookingStart < $requestEnd && $bookingEnd > $requestStart;

                    if ($hasOverlap) {
                        $overlappingBooking = $booking;
                        break;
                    }
                }

                if (!$overlappingBooking) {
                    $fieldCards[] = [
                        'field' => $field,
                        'status' => 'available',
                        'booking' => null,
                    ];
                } elseif ($overlappingBooking->getUser()?->getId() === $currentUser->getId()) {
                    $fieldCards[] = [
                        'field' => $field,
                        'status' => 'mine',
                        'booking' => $overlappingBooking,
                    ];
                } else {
                    $fieldCards[] = [
                        'field' => $field,
                        'status' => 'unavailable',
                        'booking' => $overlappingBooking,
                    ];
                }
            }

            usort($fieldCards, function (array $a, array $b) {
                $priority = [
                    'mine' => 0,
                    'available' => 1,
                    'unavailable' => 2,
                ];

                $statusCompare = $priority[$a['status']] <=> $priority[$b['status']];

                if ($statusCompare !== 0) {
                    return $statusCompare;
                }

                return $a['field']->getFieldNumber() <=> $b['field']->getFieldNumber();
            });
        }

        return $this->render('fields.html.twig', [
            'fieldCards' => $fieldCards,
            'searchDate' => $searchDate,
            'selectedTime' => $time,
            'errorMessage' => $errorMessage,
            'allowedTimes' => $allowedTimes,
            'clubs' => $clubs,
            'selectedClub' => $selectedClub,
            'hasSearched' => $hasSearched,
            'futureBookings' => $futureBookings,
            'selectedDuration' => $duration,
            'weatherByBooking' => $weatherByBooking,
        ]);
    }

    #[Route('/booking/confirm/{fieldId}', name: 'app_booking_confirm')]
    public function confirm(int $fieldId, FieldRepository $fieldRepo, Request $request): Response
    {
        $field = $fieldRepo->find($fieldId);

        if (!$field) {
            throw $this->createNotFoundException('Field not found');
        }

        $date = $request->query->get('date');
        $time = $request->query->get('time');
        $duration = (int) ($request->query->get('duration') ?: 1);

        $timezone = new \DateTimeZone('Europe/Brussels');
        $selectedDateTime = new \DateTimeImmutable($date . ' ' . $time, $timezone);
        $now = new \DateTimeImmutable('now', $timezone);

        if ($selectedDateTime <= $now) {
            $this->addFlash('error', 'You cannot book a field in the past.');
            return $this->redirectToRoute('app_fields');
        }

        return $this->render('confirmbooking.html.twig', [
            'field' => $field,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
        ]);
    }

    #[Route('/booking/final', name: 'app_booking_final', methods: ['POST'])]
    public function finalBooking(Request $request, EntityManagerInterface $em, FieldRepository $fieldRepo): Response
    {
        $fieldId = $request->request->get('field_id');
        $dateString = $request->request->get('date');
        $timeString = $request->request->get('time');
        $duration = (int) ($request->request->get('duration') ?: 1);

        $timezone = new \DateTimeZone('Europe/Brussels');
        $selectedDateTime = new \DateTimeImmutable($dateString . ' ' . $timeString, $timezone);
        $now = new \DateTimeImmutable('now', $timezone);

        if ($selectedDateTime <= $now) {
            $this->addFlash('error', 'You cannot book a field in the past.');
            return $this->redirectToRoute('app_fields');
        }

        $field = $fieldRepo->find($fieldId);

        if (!$field) {
            throw $this->createNotFoundException('Field not found');
        }

        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $startTime = new \DateTimeImmutable($dateString . ' ' . $timeString);
        $endTime = $startTime->modify('+' . $duration . ' hour');

        $existingBookings = $em->getRepository(Booking::class)->findBy([
            'field' => $field,
            'date' => new \DateTimeImmutable($dateString),
        ]);

        $hasExistingBooking = false;

        foreach ($existingBookings as $existingBooking) {
            if (
                $existingBooking->getStartTime() < $endTime &&
                $existingBooking->getEndTime() > $startTime
            ) {
                $hasExistingBooking = true;
                break;
            }
        }

        if ($hasExistingBooking) {
            $this->addFlash('error', 'This field is already booked at this time.');

            return $this->redirectToRoute('app_fields', [
                'search' => 1,
                'search_date' => $dateString,
                'time' => $timeString,
                'duration' => $duration,
                'club' => $field->getClub()->getId(),
            ]);
        }

        $booking = new Booking();
        $booking->setField($field);
        $booking->setDate(new \DateTimeImmutable($dateString));
        $booking->setStartTime($startTime);
        $booking->setEndTime($endTime);
        $booking->setUser($currentUser);

        $tournamentFields = $em->getRepository(TournamentField::class)->findBy([
            'field' => $field,
        ]);

        $tournamentConflict = false;

        foreach ($tournamentFields as $tournamentField) {
            if ($tournamentField->getTournament()->getDate()->format('Y-m-d') === $dateString) {
                $tournamentConflict = true;
                break;
            }
        }

        if ($tournamentConflict) {
            $this->addFlash(
                'error',
                'This field cannot be booked because there is a tournament on this field that day.'
            );

            return $this->redirectToRoute('app_fields', [
                'search' => 1,
                'search_date' => $dateString,
                'time' => $timeString,
                'duration' => $duration,
                'club' => $field->getClub()->getId(),
            ]);
        }

        $em->persist($booking);
        $em->flush();

        return $this->redirectToRoute('app_booking_status', ['id' => $booking->getId()]);
    }

    #[Route('/booking/status/{id}', name: 'app_booking_status')]
    public function status(Booking $booking): Response
    {
        return $this->render('status.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/booking/cancel/{id}', name: 'app_booking_delete', methods: ['POST'])]
    public function deleteBooking(Booking $booking, EntityManagerInterface $em): Response
    {
        $em->remove($booking);
        $em->flush();

        $this->addFlash('success', 'Booking cancelled');

        return $this->redirectToRoute('app_fields');
    }
}
