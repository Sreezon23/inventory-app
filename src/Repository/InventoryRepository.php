<?php

namespace App\Repository;

use App\Entity\Inventory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventory::class);
    }

    public function findVisibleForUser(?User $user): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.accessList', 'a')
            ->addSelect('a')
            ->leftJoin('i.creator', 'c')
            ->addSelect('c')
            ->orderBy('i.createdAt', 'DESC');

        if ($user === null) {
            $qb->andWhere('i.isPublic = true');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    'i.isPublic = true',
                    'i.creator = :user',
                    'a.user = :user'
                )
            )->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.creator = :creator')
            ->setParameter('creator', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPublic(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.isPublic = true')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}