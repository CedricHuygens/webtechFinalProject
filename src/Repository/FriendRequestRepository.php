<?php

namespace App\Repository;

use App\Entity\FriendRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<FriendRequest>
 */
class FriendRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendRequest::class);
    }

    public function getFriendrequestSent(User $user)
    {
        return $this->createQueryBuilder('r')
            ->join('r.receiverUser', 'receiver')
            ->where('r.senderUser = :user')
            ->andWhere('receiver.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function getFriendrequestReceived(User $user)
    {
        return $this->createQueryBuilder('r')
            ->join('r.senderUser', 'sender')
            ->where('r.receiverUser = :user')
            ->andWhere('sender.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return FriendRequest[] Returns an array of FriendRequest objects
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

    //    public function findOneBySomeField($value): ?FriendRequest
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
