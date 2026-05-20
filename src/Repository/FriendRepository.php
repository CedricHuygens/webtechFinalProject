<?php

namespace App\Repository;

use App\Entity\Friend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Friend>
 */
class FriendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friend::class);
    }

    public function getFriendsFromUser(User $user){
        $friends = $this->createQueryBuilder('f')
                ->join('f.user1', 'u1')
                ->join('f.user2', 'u2')
                ->where('(f.user1 = :user AND u2.isActive = true) OR (f.user2 = :user AND u1.isActive = true)')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

        $result = [];

        foreach ($friends as $friend){
            if ($friend->getUser1() === $user) {
                $result[] = $friend->getUser2();
            } else {
                $result[] = $friend->getUser1();
            }
        }

        return $result;
    }

    public function findFriend(User $user1, User $user2){
        return $this->createQueryBuilder('f')
            ->where('(f.user1 = :user1 and f.user2 = :user2) or (f.user1 = :user2 and f.user2 = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult(); // returns no JSON array, just the element
    }





    //    /**
    //     * @return Friend[] Returns an array of Friend objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Friend
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
