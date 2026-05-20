<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FriendRequest;
use App\Repository\FriendRequestRepository;
use App\Repository\FriendRepository;
use App\Entity\Friend;


class FriendsController extends AbstractController
{

    // Friendrequests:

    #[Route('/friendrequests', name: 'friendrequests')]
    public function indexFriendrequests(FriendRequestRepository $friendRequestRepository): Response {
        $user = $this->getUser();
        $friendrequestsSent = $friendRequestRepository->getFriendrequestSent($user);
        $friendrequestsReceived = $friendRequestRepository->getFriendrequestReceived($user);

        return $this->render('friendrequests.html.twig',[
            'friendrequestsSent' => $friendrequestsSent,
            'friendrequestsReceived'=> $friendrequestsReceived
        ]);
    }

    #[Route('/searchFriends', name: "searchFriends")]
    public function searchFriends(Request $request, UserRepository $userRepository): JsonResponse{
        $currentUser = $this->getUser();
        $query = $request->query->get('q'); // what the user currently typed
        $users = $userRepository->findPotentialFriendsByUsername($query, $currentUser);

        return $this->json([
                'html' => $this->renderView('partials/_user_results.html.twig', [ // renderView returnes only html, not full page -> partials, _user... the _ for convention
                'users' => $users
            ])
        ]);
    }

    #[Route('/sendFriendRequest', name: "sendFriendRequest", methods: ['POST'])]
    public function sendFriendRequest(Request $request, EntityManagerInterface $em, UserRepository $userRepository, FriendRequestRepository $friendRequestRepository): JsonResponse{
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        // check if data available
        if (!isset($data['receiverUserId'])) {
            return $this->json(['error' => 'Missing data'], 400);
        }
        $receiverUserId = $data['receiverUserId'];
        $receiverUser = $userRepository->find($receiverUserId);

        if (!$receiverUser || $receiverUser === $currentUser) {
            return $this->json(['error' => 'Invalid user'], 400);
        }

        $existing = $friendRequestRepository->findOneBy([
            'senderUser' => $currentUser,
            'receiverUser' => $receiverUser
        ]);

        $reverse = $friendRequestRepository->findOneBy([
            'senderUser' => $receiverUser,
            'receiverUser' => $currentUser
        ]);

        if ($existing || $reverse) {
            return $this->json(['error' => 'Request already exists'], 400);
        }


        $friendRequest = new FriendRequest();
        $friendRequest->setSenderUser($currentUser);
        $friendRequest->setReceiverUser($receiverUser);
        $friendRequest->setCreatedAt(new \DateTimeImmutable());

        $em->persist($friendRequest);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/cancelFriendRequest', name: "cancelFriendRequest", methods: ['POST'])]
    public function cancelFriendRequest(Request $request, EntityManagerInterface $em, FriendRequestRepository $friendRequestRepository): JsonResponse{
        $data = json_decode($request->getContent(), true);

        if (!isset($data['requestId'])) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $requestId = $data['requestId'];

        $friendRequest = $friendRequestRepository->find($requestId);

        if (!$friendRequest) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $currentUser = $this->getUser();
        if (
            $friendRequest->getSenderUser() !== $currentUser &&
            $friendRequest->getReceiverUser() !== $currentUser
        ) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $em->remove($friendRequest);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/acceptFriendRequest', name: "acceptFriendRequest", methods: ['POST'])]
    public function acceptFriendRequest(Request $request, EntityManagerInterface $em, FriendRequestRepository $friendRequestRepository): JsonResponse{
        $data = json_decode($request->getContent(), true);

        if (!isset($data['requestId'])) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $requestId = $data['requestId'];

        $friendRequest = $friendRequestRepository->find($requestId);

        if (!$friendRequest) {
            return $this->json(['error' => 'Not found'], 404);
        }

        // security check
        if ($friendRequest->getReceiverUser() !== $this->getUser()) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $user1 = $friendRequest->getSenderUser();
        $user2 = $friendRequest->getReceiverUser();

        $friend = new Friend();
        $friend->setUser1($user1);
        $friend->setUser2($user2);


        $em->remove($friendRequest);
        $em->persist($friend);
        $em->flush();

        return $this->json(['success' => true]);
    }

    // friends:
    #[Route('/friends', name: 'friends')]
    public function indexFriends(FriendRepository $friendRepository): Response {
        $user = $this->getUser();

        $friends = $friendRepository->getFriendsFromUser($user);

        return $this->render('friends.html.twig',[
            'friends' => $friends
        ]);
    }

    #[Route('/deleteFriend', name: 'deleteFriend', methods: ['POST'])]
    public function deleteFriend(Request $request, FriendRepository $friendRepository, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse{
        $data = json_decode($request->getContent(), true);
        $user1 = $this->getUser();
        $user2 = $userRepository->find($data['userId']);
        $friend = $friendRepository->findFriend($user1, $user2);

        if (!$friend) {
            return $this->json(['error' => 'Friendship not found'], 404);
        }

        $em->remove($friend);
        $em->flush();
        return $this->json(['success' => true]);
    }


}
