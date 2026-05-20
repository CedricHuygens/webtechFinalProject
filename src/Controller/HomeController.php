<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Field;
use App\Entity\Friend;
use App\Entity\Notification;
use App\Entity\PadelMatch;
use App\Entity\PadelMatchComment;
use App\Entity\PadelMatchCommentComment;
use App\Entity\PadelMatchCommentLike;
use App\Entity\PadelMatchLike;
use App\Entity\PadelMatchPlayer;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_feed');
    }
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('app_feed');
    }

    #[Route('/feed', name: 'app_feed', methods: ['GET'])]
    public function feed(EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $friendIds = $this->getFriendIds($entityManager, $currentUser);

        if (empty($friendIds)) {
            return $this->render('feed.html.twig', [
                'matches' => [],
                'currentUserId' => $currentUser->getId(),
                'friendIds' => [],
            ]);
        }

        $matches = $entityManager
            ->getRepository(PadelMatch::class)
            ->findBy([], ['startTime' => 'DESC']);

        $feedMatches = [];

        foreach ($matches as $match) {
            $team1Players = [];
            $team2Players = [];
            $matchPlayerUserIds = [];

            $team1 = $match->getTeam1();
            $team2 = $match->getTeam2();

            if ($team1) {
                foreach ([$team1->getUser1(), $team1->getUser2()] as $user) {
                    if (!$user) {
                        continue;
                    }

                    $matchPlayerUserIds[] = $user->getId();

                    $team1Players[] = [
                        'id' => $user->getId(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'profilePicture' => $user->getProfilePicture() ?: 'uploads/profile_pictures/default.png',
                    ];
                }
            }

            if ($team2) {
                foreach ([$team2->getUser1(), $team2->getUser2()] as $user) {
                    if (!$user) {
                        continue;
                    }

                    $matchPlayerUserIds[] = $user->getId();

                    $team2Players[] = [
                        'id' => $user->getId(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'profilePicture' => $user->getProfilePicture() ?: 'uploads/profile_pictures/default.png',
                    ];
                }
            }

            if (count($team1Players) === 0 && count($team2Players) === 0) {
                foreach ($match->getMatchPlayers() as $matchPlayer) {
                    $user = $matchPlayer->getUser();

                    if (!$user) {
                        continue;
                    }

                    $matchPlayerUserIds[] = $user->getId();

                    $playerData = [
                        'id' => $user->getId(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'profilePicture' => $user->getProfilePicture() ?: 'uploads/profile_pictures/default.png',
                    ];

                    if ($matchPlayer->getTeamNumber() === 1) {
                        $team1Players[] = $playerData;
                    } else {
                        $team2Players[] = $playerData;
                    }
                }
            }

            $matchPlayerUserIds = array_values(array_unique(array_map('intval', $matchPlayerUserIds)));

            $hasFriendInMatch = count(array_intersect($matchPlayerUserIds, $friendIds)) > 0;

            if (!$hasFriendInMatch) {
                continue;
            }

            $likes = $entityManager
                ->getRepository(PadelMatchLike::class)
                ->findBy(['padelMatch' => $match]);

            $existingLike = $entityManager
                ->getRepository(PadelMatchLike::class)
                ->findOneBy([
                    'padelMatch' => $match,
                    'user' => $currentUser,
                ]);

            $comments = $entityManager
                ->getRepository(PadelMatchComment::class)
                ->findBy(
                    ['padelMatch' => $match],
                    ['createdAt' => 'DESC']
                );

            $commentData = [];

            foreach ($comments as $comment) {
                $isReply = $entityManager
                    ->getRepository(PadelMatchCommentComment::class)
                    ->findOneBy([
                        'ReplyComment' => $comment,
                    ]);

                if ($isReply) {
                    continue;
                }

                $commentArray = $this->buildCommentData(
                    $entityManager,
                    $comment,
                    $currentUser,
                    true
                );

                if ($commentArray !== null) {
                    $commentData[] = $commentArray;
                }
            }

            $field = $match->getField();

            $feedMatches[] = [
                'id' => $match->getId(),
                'team1Name' => $team1?->getName(),
                'team2Name' => $team2?->getName(),
                'team1Players' => $team1Players,
                'team2Players' => $team2Players,
                'team1Score' => $match->getTeam1Score(),
                'team2Score' => $match->getTeam2Score(),
                'startTime' => $match->getStartTime(),
                'endTime' => $match->getEndTime(),
                'fieldNumber' => $field?->getFieldNumber(),
                'fieldLocation' => $field?->getLocation(),
                'likesCount' => count($likes),
                'likedByCurrentUser' => $existingLike !== null,
                'comments' => $commentData,
            ];
        }

        return $this->render('feed.html.twig', [
            'matches' => $feedMatches,
            'currentUserId' => $currentUser->getId(),
            'friendIds' => $friendIds,
        ]);
    }

    #[Route('/team-mate-request/add', name: 'app_add_team_mate_request', methods: ['GET', 'POST'])]
    public function addTeamMateRequest(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $friends = $this->getFriendsForUser($entityManager, $currentUser);

        if ($request->isMethod('POST')) {
            $teamName = trim((string) $request->request->get('team_name'));
            $friendId = (int) $request->request->get('friend_id');

            if ($teamName === '') {
                $this->addFlash('error', 'Team name is required.');
                return $this->redirectToRoute('app_add_team_mate_request');
            }

            $selectedFriend = $entityManager
                ->getRepository(User::class)
                ->find($friendId);

            if (!$selectedFriend) {
                $this->addFlash('error', 'Selected friend was not found.');
                return $this->redirectToRoute('app_add_team_mate_request');
            }

            $isRealFriend = false;

            foreach ($friends as $friend) {
                if ($friend->getId() === $selectedFriend->getId()) {
                    $isRealFriend = true;
                    break;
                }
            }

            if (!$isRealFriend) {
                $this->addFlash('error', 'You can only create a team with one of your friends.');
                return $this->redirectToRoute('app_add_team_mate_request');
            }

            $existingTeam = $entityManager
                ->getRepository(Team::class)
                ->createQueryBuilder('t')
                ->where('(t.user1 = :currentUser AND t.user2 = :friend)')
                ->orWhere('(t.user1 = :friend AND t.user2 = :currentUser)')
                ->setParameter('currentUser', $currentUser)
                ->setParameter('friend', $selectedFriend)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingTeam) {
                $this->addFlash('error', 'You already have a team with this friend.');
                return $this->redirectToRoute('app_add_team_mate_request');
            }

            $team = new Team();
            $team->setName($teamName);
            $team->setUser1($currentUser);
            $team->setUser2($selectedFriend);

            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Team created successfully.');

            return $this->redirectToRoute('app_feed');
        }

        return $this->render('team_mate_request.html.twig', [
            'friends' => $friends,
        ]);
    }

    #[Route('/match/{id}/like', name: 'app_match_like', methods: ['POST'])]
    public function likeMatch(int $id, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $match = $entityManager
            ->getRepository(PadelMatch::class)
            ->find($id);

        if (!$match) {
            return $this->redirectToRoute('app_feed');
        }

        $existingLike = $entityManager
            ->getRepository(PadelMatchLike::class)
            ->findOneBy([
                'padelMatch' => $match,
                'user' => $currentUser,
            ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
        } else {
            $like = new PadelMatchLike();
            $like->setPadelMatch($match);
            $like->setUser($currentUser);

            $entityManager->persist($like);

            foreach ($this->getUsersInMatch($match) as $playerToNotify) {
                if ($playerToNotify->getId() === $currentUser->getId()) {
                    continue;
                }

                $this->createNotification(
                    $entityManager,
                    $playerToNotify,
                    'like',
                    $this->getUserDisplayName($currentUser) . ' liked your match.'
                );
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_feed');
    }

    #[Route('/match/{id}/comment', name: 'app_match_comment', methods: ['POST'])]
    public function addComment(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $match = $entityManager
            ->getRepository(PadelMatch::class)
            ->find($id);

        if (!$match) {
            return $this->redirectToRoute('app_feed');
        }

        $content = trim((string) $request->request->get('content'));

        if ($content !== '') {
            $comment = new PadelMatchComment();
            $comment->setPadelMatch($match);
            $comment->setUser($currentUser);
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTime());

            $entityManager->persist($comment);

            foreach ($this->getUsersInMatch($match) as $playerToNotify) {
                if ($playerToNotify->getId() === $currentUser->getId()) {
                    continue;
                }

                $this->createNotification(
                    $entityManager,
                    $playerToNotify,
                    'comment',
                    $this->getUserDisplayName($currentUser) . ' commented on your match: "' . $content . '"'
                );
            }

            $entityManager->flush();
        }

        return $this->redirectToRoute('app_feed');
    }

    #[Route('/comment/{id}/like', name: 'app_comment_like', methods: ['POST'])]
    public function likeComment(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $comment = $entityManager
            ->getRepository(PadelMatchComment::class)
            ->find($id);

        if (!$comment) {
            return $this->redirectToRoute('app_feed');
        }

        $existingLike = $entityManager
            ->getRepository(PadelMatchCommentLike::class)
            ->findOneBy([
                'comment' => $comment,
                'user' => $currentUser,
            ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
        } else {
            $commentLike = new PadelMatchCommentLike();
            $commentLike->setComment($comment);
            $commentLike->setUser($currentUser);

            $entityManager->persist($commentLike);

            $commentOwner = $comment->getUser();

            if ($commentOwner && $commentOwner->getId() !== $currentUser->getId()) {
                $this->createNotification(
                    $entityManager,
                    $commentOwner,
                    'comment_like',
                    $this->getUserDisplayName($currentUser) . ' liked your comment.'
                );
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_feed');
    }

    #[Route('/comment/{id}/reply', name: 'app_comment_reply', methods: ['POST'])]
    public function replyComment(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $mainComment = $entityManager
            ->getRepository(PadelMatchComment::class)
            ->find($id);

        if (!$mainComment) {
            return $this->redirectToRoute('app_feed');
        }

        $match = $mainComment->getPadelMatch();

        if (!$match) {
            return $this->redirectToRoute('app_feed');
        }

        $content = trim((string) $request->request->get('content'));

        if ($content === '') {
            return $this->redirectToRoute('app_feed');
        }

        $replyComment = new PadelMatchComment();
        $replyComment->setPadelMatch($match);
        $replyComment->setUser($currentUser);
        $replyComment->setContent($content);
        $replyComment->setCreatedAt(new \DateTime());

        $entityManager->persist($replyComment);

        $commentRelation = new PadelMatchCommentComment();
        $commentRelation->setMainComment($mainComment);
        $commentRelation->setReplyComment($replyComment);

        $entityManager->persist($commentRelation);

        $mainCommentOwner = $mainComment->getUser();

        if ($mainCommentOwner && $mainCommentOwner->getId() !== $currentUser->getId()) {
            $this->createNotification(
                $entityManager,
                $mainCommentOwner,
                'comment_reply',
                $this->getUserDisplayName($currentUser) . ' replied to your comment: "' . $content . '"'
            );
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_feed');
    }

    #[Route('/match/add', name: 'app_add_match', methods: ['GET', 'POST'])]
    public function addMatch(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $teams = $entityManager
            ->getRepository(Team::class)
            ->findAll();

        $fields = $entityManager
            ->getRepository(Field::class)
            ->findAll();

        if ($request->isMethod('POST')) {
            $startTime = (string) $request->request->get('start_time');
            $endTime = (string) $request->request->get('end_time');
            $team1Score = (int) $request->request->get('team1_score');
            $team2Score = (int) $request->request->get('team2_score');
            $fieldId = (int) $request->request->get('field_id');
            $team1Id = (int) $request->request->get('team1_id');
            $team2Id = (int) $request->request->get('team2_id');

            $field = $entityManager
                ->getRepository(Field::class)
                ->find($fieldId);

            $team1 = $entityManager
                ->getRepository(Team::class)
                ->find($team1Id);

            $team2 = $entityManager
                ->getRepository(Team::class)
                ->find($team2Id);

            if (
                $field &&
                $team1 &&
                $team2 &&
                $team1->getId() !== $team2->getId() &&
                $team1->getUser1() &&
                $team1->getUser2() &&
                $team2->getUser1() &&
                $team2->getUser2() &&
                $startTime !== '' &&
                $endTime !== ''
            ) {
                $match = new PadelMatch();
                $match->setStartTime(new \DateTime($startTime));
                $match->setEndTime(new \DateTime($endTime));
                $match->setTeam1Score($team1Score);
                $match->setTeam2Score($team2Score);
                $match->setField($field);
                $match->setTeam1($team1);
                $match->setTeam2($team2);

                $entityManager->persist($match);

                $players = [
                    [$team1->getUser1(), 1],
                    [$team1->getUser2(), 1],
                    [$team2->getUser1(), 2],
                    [$team2->getUser2(), 2],
                ];

                foreach ($players as [$user, $teamNumber]) {
                    $matchPlayer = new PadelMatchPlayer();
                    $matchPlayer->setPadelMatch($match);
                    $matchPlayer->setUser($user);
                    $matchPlayer->setTeamNumber($teamNumber);

                    $entityManager->persist($matchPlayer);
                }

                $entityManager->flush();

                return $this->redirectToRoute('app_feed');
            }
        }

        return $this->render('add_match.html.twig', [
            'teams' => $teams,
            'fields' => $fields,
        ]);
    }

    #[Route('/match-request/add', name: 'app_add_match_request', methods: ['GET', 'POST'])]
    public function addMatchRequest(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $users = $entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $fields = $entityManager
            ->getRepository(Field::class)
            ->findAll();

        if ($request->isMethod('POST')) {
            $partnerId = (int) $request->request->get('partner');
            $opponent1Id = (int) $request->request->get('opponent1');
            $opponent2Id = (int) $request->request->get('opponent2');
            $fieldId = (int) $request->request->get('field_id');
            $dateValue = trim((string) $request->request->get('date'));

            $selectedUserIds = [
                $partnerId,
                $opponent1Id,
                $opponent2Id,
            ];

            if (
                $partnerId === 0 ||
                $opponent1Id === 0 ||
                $opponent2Id === 0 ||
                $fieldId === 0 ||
                $dateValue === ''
            ) {
                $this->addFlash('error', 'Please fill in all fields.');
                return $this->redirectToRoute('app_add_match_request');
            }

            if (in_array($currentUser->getId(), $selectedUserIds, true)) {
                $this->addFlash('error', 'You cannot select yourself for a match request.');
                return $this->redirectToRoute('app_add_match_request');
            }

            if (count(array_unique($selectedUserIds)) !== 3) {
                $this->addFlash('error', 'Partner, opponent 1 and opponent 2 must be different users.');
                return $this->redirectToRoute('app_add_match_request');
            }

            $partner = $entityManager
                ->getRepository(User::class)
                ->find($partnerId);

            $opponent1 = $entityManager
                ->getRepository(User::class)
                ->find($opponent1Id);

            $opponent2 = $entityManager
                ->getRepository(User::class)
                ->find($opponent2Id);

            $field = $entityManager
                ->getRepository(Field::class)
                ->find($fieldId);

            if (
                !$partner instanceof User ||
                !$opponent1 instanceof User ||
                !$opponent2 instanceof User ||
                !$field instanceof Field
            ) {
                $this->addFlash('error', 'One or more selected values are invalid.');
                return $this->redirectToRoute('app_add_match_request');
            }

            try {
                $matchDate = new \DateTimeImmutable($dateValue);
            } catch (\Exception) {
                $this->addFlash('error', 'Invalid date selected.');
                return $this->redirectToRoute('app_add_match_request');
            }

            $senderName = $this->getUserDisplayName($currentUser);
            $dateText = $matchDate->format('d/m/Y');
            $fieldText = 'Court ' . $field->getFieldNumber() . ' - ' . $field->getLocation();

            $receivers = [
                $partner,
                $opponent1,
                $opponent2,
            ];

            foreach ($receivers as $receiver) {
                $this->createNotification(
                    $entityManager,
                    $receiver,
                    'match_request',
                    $senderName . ' sent you a match request for ' . $dateText . ' on ' . $fieldText . '.'
                );
            }

            $receiverNames = [];

            foreach ($receivers as $receiver) {
                $receiverNames[] = $this->getUserDisplayName($receiver);
            }

            $this->createNotification(
                $entityManager,
                $currentUser,
                'match_request',
                'You sent a match request to ' . implode(', ', $receiverNames) . ' for ' . $dateText . ' on ' . $fieldText . '.'
            );

            $entityManager->flush();

            $this->addFlash('success', 'Match request sent successfully.');

            return $this->redirectToRoute('app_notifications');
        }

        return $this->render('add.html.twig', [
            'users' => $users,
            'fields' => $fields,
        ]);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    private function getFriendsForUser(EntityManagerInterface $entityManager, User $currentUser): array
    {
        $friendLinks = $entityManager
            ->getRepository(Friend::class)
            ->createQueryBuilder('f')
            ->where('f.user1 = :currentUser')
            ->orWhere('f.user2 = :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->getQuery()
            ->getResult();

        $friends = [];

        foreach ($friendLinks as $friendLink) {
            if ($friendLink->getUser1()?->getId() === $currentUser->getId()) {
                $friend = $friendLink->getUser2();
            } else {
                $friend = $friendLink->getUser1();
            }

            if ($friend) {
                $friends[$friend->getId()] = $friend;
            }
        }

        return array_values($friends);
    }

    private function getFriendIds(EntityManagerInterface $entityManager, User $currentUser): array
    {
        $connection = $entityManager->getConnection();

        $rows = $connection->fetchAllAssociative(
            '
            SELECT user1_id, user2_id
            FROM friend
            WHERE user1_id = :currentUserId
               OR user2_id = :currentUserId
            ',
            [
                'currentUserId' => $currentUser->getId(),
            ]
        );

        $friendIds = [];

        foreach ($rows as $row) {
            $user1Id = (int) $row['user1_id'];
            $user2Id = (int) $row['user2_id'];

            if ($user1Id === $currentUser->getId()) {
                $friendIds[] = $user2Id;
            }

            if ($user2Id === $currentUser->getId()) {
                $friendIds[] = $user1Id;
            }
        }

        return array_values(array_unique($friendIds));
    }

    private function getUsersInMatch(PadelMatch $match): array
    {
        $users = [];

        $team1 = $match->getTeam1();
        $team2 = $match->getTeam2();

        if ($team1) {
            foreach ([$team1->getUser1(), $team1->getUser2()] as $user) {
                if ($user) {
                    $users[$user->getId()] = $user;
                }
            }
        }

        if ($team2) {
            foreach ([$team2->getUser1(), $team2->getUser2()] as $user) {
                if ($user) {
                    $users[$user->getId()] = $user;
                }
            }
        }

        foreach ($match->getMatchPlayers() as $matchPlayer) {
            $user = $matchPlayer->getUser();

            if ($user) {
                $users[$user->getId()] = $user;
            }
        }

        return array_values($users);
    }

    private function getCommentLikeData(
        EntityManagerInterface $entityManager,
        PadelMatchComment $comment,
        ?User $currentUser
    ): array {
        $repository = $entityManager->getRepository(PadelMatchCommentLike::class);

        $likes = $repository->findBy([
            'comment' => $comment,
        ]);

        $likedByCurrentUser = false;

        if ($currentUser) {
            $existingLike = $repository->findOneBy([
                'comment' => $comment,
                'user' => $currentUser,
            ]);

            $likedByCurrentUser = $existingLike !== null;
        }

        return [count($likes), $likedByCurrentUser];
    }

    private function createNotification(
        EntityManagerInterface $entityManager,
        User $user,
        string $type,
        string $message
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($notification);
    }
    private function buildCommentData(
        EntityManagerInterface $entityManager,
        PadelMatchComment $comment,
        User $currentUser,
        bool $includeReplies
    ): ?array {
        $commentUser = $comment->getUser();

        if (!$commentUser) {
            return null;
        }

        [$commentLikesCount, $commentLikedByCurrentUser] = $this->getCommentLikeData(
            $entityManager,
            $comment,
            $currentUser
        );

        $replies = [];

        if ($includeReplies) {
            $replyRelations = $entityManager
                ->getRepository(PadelMatchCommentComment::class)
                ->findBy([
                    'mainComment' => $comment,
                ]);

            usort($replyRelations, function (
                PadelMatchCommentComment $a,
                PadelMatchCommentComment $b
            ): int {
                return $a->getReplyComment()?->getCreatedAt() <=> $b->getReplyComment()?->getCreatedAt();
            });

            foreach ($replyRelations as $replyRelation) {
                $replyComment = $replyRelation->getReplyComment();

                if (!$replyComment) {
                    continue;
                }

                $replyData = $this->buildCommentData(
                    $entityManager,
                    $replyComment,
                    $currentUser,
                    false
                );

                if ($replyData !== null) {
                    $replies[] = $replyData;
                }
            }
        }

        return [
            'id' => $comment->getId(),
            'userId' => $commentUser->getId(),
            'firstName' => $commentUser->getFirstName(),
            'lastName' => $commentUser->getLastName(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt(),
            'likedByCurrentUser' => $commentLikedByCurrentUser,
            'likesCount' => $commentLikesCount,
            'replies' => $replies,
        ];
    }

    private function getUserDisplayName(User $user): string
    {
        $name = trim(
            ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')
        );

        if ($name !== '') {
            return $name;
        }

        if (method_exists($user, 'getUsername') && $user->getUsername()) {
            return $user->getUsername();
        }

        return 'User ' . $user->getId();
    }
}
