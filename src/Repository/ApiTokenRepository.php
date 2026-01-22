<?php

namespace App\Repository;

use App\Entity\ApiToken;
use App\Entity\Inventory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function save(ApiToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByToken(string $token): ?ApiToken
    {
        return $this->createQueryBuilder('a')
            ->where('a.token = :token')
            ->andWhere('a.isActive = :active')
            ->andWhere('a.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByInventory(Inventory $inventory): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.inventory = :inventory')
            ->andWhere('a.isActive = :active')
            ->setParameter('inventory', $inventory)
            ->setParameter('active', true)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
