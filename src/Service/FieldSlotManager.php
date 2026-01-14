<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\InventoryField;
use App\Repository\InventoryFieldRepository;

class FieldSlotManager
{
    public function __construct(
        private InventoryFieldRepository $fieldRepository
    ) {
    }


    public function findNextAvailableSlot(Inventory $inventory, string $fieldType): string
    {
        $usedSlots = $this->fieldRepository->findUsedSlots($inventory);

        $prefix = match($fieldType) {
            InventoryField::TYPE_TEXT => 'text',
            InventoryField::TYPE_TEXTAREA => 'textarea',
            InventoryField::TYPE_NUMBER => 'number',
            InventoryField::TYPE_BOOLEAN => 'bool',
            InventoryField::TYPE_DOCUMENT_LINK => 'link',
            default => throw new \InvalidArgumentException("Unsupported field type: $fieldType"),
        };


        for ($i = 1; $i <= InventoryField::MAX_PER_TYPE; $i++) {
            $candidateSlot = $prefix . $i;

            if (!in_array($candidateSlot, $usedSlots, true)) {
                return $candidateSlot;
            }
        }

        throw new \OverflowException("Maximum number of {$fieldType} fields reached for this inventory.");
    }
}