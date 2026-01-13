<?php

namespace App\Service;

use App\Entity\CustomIdFormat;
use App\Entity\InventoryItem;
use Doctrine\ORM\EntityManagerInterface;

class CustomIdGenerator
{
    private int $sequenceCounter = 0;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function generateForItem(CustomIdFormat $format, InventoryItem $item): string
    {
        $parts = [];

        foreach ($format->getElements() as $element) {
            switch ($element['type'] ?? null) {
                case 'prefix':
                    $parts[] = $element['value'] ?? '';
                    break;
                case 'date':
                    $dateFormat = $element['format'] ?? 'Ymd';
                    $parts[] = (new \DateTimeImmutable())->format($dateFormat);
                    break;
                case 'sequence':
                    $padding = $element['padding'] ?? 4;
                    $sequence = $this->getNextSequence($format);
                    $parts[] = str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
                    break;
                case 'random':
                    $length = $element['length'] ?? 6;
                    $parts[] = strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
                    break;
            }
        }

        return implode($element['separator'] ?? '', $parts);
    }

    private function getNextSequence(CustomIdFormat $format): int
    {
        // Simple implementation: count existing items in inventory
        $inventory = $format->getInventory();
        return $inventory->getItems()->count() + 1;
    }
}