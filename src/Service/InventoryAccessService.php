<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\InventoryAccess;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class InventoryAccessService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function grantAccess(Inventory $inventory, User $user, bool $canWrite = false): void
    {
        foreach ($inventory->getAccessList() as $access) {
            if ($access->getUser()->getId() === $user->getId()) {
                $access->setCanWrite($canWrite);
                $this->em->flush();
                return;
            }
        }

        $access = new InventoryAccess();
        $access->setInventory($inventory);
        $access->setUser($user);
        $access->setCanWrite($canWrite);

        $this->em->persist($access);
        $this->em->flush();
    }

    public function revokeAccess(Inventory $inventory, User $user): void
    {
        foreach ($inventory->getAccessList() as $access) {
            if ($access->getUser()->getId() === $user->getId()) {
                $this->em->remove($access);
                $this->em->flush();
                return;
            }
        }
    }

    public function userCanWrite(Inventory $inventory, User $user): bool
    {
        if ($inventory->getCreator()->getId() === $user->getId()) {
            return true;
        }

        foreach ($inventory->getAccessList() as $access) {
            if ($access->getUser()->getId() === $user->getId()) {
                return $access->isCanWrite();
            }
        }

        return false;
    }
}