<?php

namespace App\Security\Voter;

use App\Entity\Item;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class ItemVoter extends Voter
{
    public const VIEW = 'ITEM_VIEW';
    public const EDIT = 'ITEM_EDIT';
    public const DELETE = 'ITEM_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ], true) && $subject instanceof Item;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $user = $token->getUser();

        // Everyone can view items
        if ($attribute === self::VIEW) {
            return true;
        }

        // Anonymous users cannot edit/delete
        if (!$user instanceof User) {
            return false;
        }

        // Admin override
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Item $item */
        $item = $subject;
        $inventory = $item->getInventory();

        // Owner can edit/delete
        if ($inventory->getOwner() === $user) {
            return true;
        }

        // Writers can edit/delete items
        return $inventory->getWriters()->contains($user);
    }
}
