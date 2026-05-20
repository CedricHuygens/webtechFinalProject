<?php

namespace App\Repository;

use App\Entity\Field;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Field>
 */
class FieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Field::class);
    }

    // geeft vrije velden op gevraagde moment
    public function findAvailableFields(string $date, string $time, ?int $clubId = null): array
    {
        $qb = $this->createQueryBuilder('f');

        $qb->andWhere(
            $qb->expr()->notIn(
                'f.id',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(b.field)')
                    ->from('App\Entity\Booking', 'b')
                    ->where('b.date = :date')
                    ->andWhere('b.startTime = :time')
                    ->getDQL()
            )
        )
            ->setParameter('date', $date)
            ->setParameter('time', $time);

        if ($clubId) {
            $qb->andWhere('f.club = :clubId')
                ->setParameter('clubId', $clubId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findFieldsByClub(?int $clubId = null): array
    {
        $qb = $this->createQueryBuilder('f');

        if ($clubId) {
            $qb->andWhere('f.club = :clubId')
                ->setParameter('clubId', $clubId);
        }

        return $qb->orderBy('f.fieldNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
