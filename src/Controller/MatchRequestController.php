<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Field;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MatchRequestController extends AbstractController
{
    #[Route('/match-request/add', name: 'app_match_request_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $users = $entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $fields = $entityManager
            ->getRepository(Field::class)
            ->findBy([], ['fieldNumber' => 'ASC']);

        if ($request->isMethod('POST')) {
            $partnerId = (int) $request->request->get('partner');
            $opponentOneId = (int) $request->request->get('opponent1');
            $opponentTwoId = (int) $request->request->get('opponent2');
            $fieldId = (int) $request->request->get('field_id');
            $date = (string) $request->request->get('date');

            $partner = $entityManager->getRepository(User::class)->find($partnerId);
            $opponentOne = $entityManager->getRepository(User::class)->find($opponentOneId);
            $opponentTwo = $entityManager->getRepository(User::class)->find($opponentTwoId);
            $field = $entityManager->getRepository(Field::class)->find($fieldId);

            if (
                !$partner instanceof User ||
                !$opponentOne instanceof User ||
                !$opponentTwo instanceof User ||
                !$field instanceof Field ||
                $date === ''
            ) {
                $this->addFlash('error', 'Please fill in all fields correctly.');

                return $this->redirectToRoute('app_match_request_add');
            }

            $senderName = $this->getDisplayName($currentUser);
            $dateText = (new \DateTime($date))->format('d/m/Y');

            $fieldText = 'Court ' . $field->getFieldNumber() . ' - ' . $field->getLocation();

            $receivers = [
                $partner,
                $opponentOne,
                $opponentTwo,
            ];

            $uniqueReceivers = [];

            foreach ($receivers as $receiver) {
                if (!$receiver instanceof User) {
                    continue;
                }

                if ($receiver->getId() === $currentUser->getId()) {
                    continue;
                }

                $uniqueReceivers[$receiver->getId()] = $receiver;
            }

            foreach ($uniqueReceivers as $receiver) {
                $notification = new Notification();
                $notification->setUser($receiver);
                $notification->setType('match_request');
                $notification->setMessage(
                    $senderName . ' sent you a match request for ' . $dateText . ' on ' . $fieldText . '.'
                );
                $notification->setIsRead(false);
                $notification->setCreatedAt(new \DateTime());

                $entityManager->persist($notification);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_notifications');
        }

        return $this->render('add.html.twig', [
            'users' => $users,
            'fields' => $fields,
        ]);
    }

    private function getDisplayName(User $user): string
    {
        $name = trim(
            ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')
        );

        if ($name !== '') {
            return $name;
        }

        return $user->getUsername() ?? 'User ' . $user->getId();
    }
}
