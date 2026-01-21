<?php

namespace App\Repository;

use App\Entity\Inventory;
use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function findByInventory(Inventory $inventory): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.inventory = :inventory')
            ->setParameter('inventory', $inventory)
            ->leftJoin('i.fieldValues', 'fv')
            ->addSelect('fv')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByInventoryWithValues(Inventory $inventory): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.inventory = :inventory')
            ->setParameter('inventory', $inventory)
            ->leftJoin('i.fieldValues', 'fv')
            ->addSelect('fv')
            ->leftJoin('fv.field', 'f')
            ->addSelect('f')
            ->leftJoin('i.likes', 'l')
            ->addSelect('l')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCustomId(Inventory $inventory, string $customId): ?InventoryItem
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.inventory = :inventory')
            ->andWhere('i.customId = :customId')
            ->setParameter('inventory', $inventory)
            ->setParameter('customId', $customId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}