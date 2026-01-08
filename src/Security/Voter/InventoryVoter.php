<?php

namespace App\Security\Voter;

use App\Entity\Inventory;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class InventoryVoter extends Voter
{
    public const VIEW = 'INVENTORY_VIEW';
    public const EDIT = 'INVENTORY_EDIT';
    public const ADD_ITEM = 'INVENTORY_ADD_ITEM';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::ADD_ITEM,
        ], true) && $subject instanceof Inventory;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $user = $token->getUser();

        // Everyone (including anonymous) can view inventories
        if ($attribute === self::VIEW) {
            return true;
        }

        // Anonymous users cannot edit or add items
        if (!$user instanceof User) {
            return false;
        }

        // Admin override
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Inventory $inventory */
        $inventory = $subject;

        // Owner can do everything
        if ($inventory->getOwner() === $user) {
            return true;
        }

        return match ($attribute) {
            self::EDIT => false,
            self::ADD_ITEM => $inventory->isPublic()
                || $inventory->getWriters()->contains($user),
            default => false,
        };
    }
}
