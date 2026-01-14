<?php

namespace App\Service;

use App\Entity\InventoryItem;
use App\Repository\InventoryItemRepository;

class CustomIdGenerator
{
    public function __construct(
        private InventoryItemRepository $repository
    ) {}

    public function generateForItem(string $format, InventoryItem $item): string
    {
        $inventory = $item->getInventory();
        
        $replaced = str_replace(
            ['{Y}', '{m}', '{d}'],
            [date('Y'), date('m'), date('d')],
            $format
        );

        if (str_contains($replaced, '{000}')) {
            $count = $this->repository->count(['inventory' => $inventory]);
            $sequence = str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
            $replaced = str_replace('{000}', $sequence, $replaced);
        }

        if (str_contains($replaced, '{0000}')) {
            $count = $this->repository->count(['inventory' => $inventory]);
            $sequence = str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
            $replaced = str_replace('{0000}', $sequence, $replaced);
        }

        return $replaced;
    }
}