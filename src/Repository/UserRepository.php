<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.isActive = true')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySocialProvider(string $provider, string $socialId): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.socialProvider = :provider')
            ->andWhere('u.socialId = :socialId')
            ->setParameter('provider', $provider)
            ->setParameter('socialId', $socialId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = true')
            ->andWhere('u.isBlocked = false')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}