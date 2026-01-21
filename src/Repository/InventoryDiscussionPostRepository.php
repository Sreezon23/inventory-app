<?php

namespace App\Repository;

use App\Entity\InventoryDiscussionPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryDiscussionPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryDiscussionPost::class);
    }
}
