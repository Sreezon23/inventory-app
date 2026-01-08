<?php

namespace App\Entity;

use App\Repository\InventoryFieldConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryFieldConfigRepository::class)]
class InventoryFieldConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inventory::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Inventory $inventory = null;

    #[ORM\Column(length: 50)]
    private ?string $fieldKey = null; // e.g., "text_field1", "number_field2"

    #[ORM\Column(length: 100)]
    private ?string $fieldTitle = null; // e.g., "SKU", "Quantity", "Price"

    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $fieldType = null; // "text", "number", "date"

    #[ORM\Column]
    private bool $isVisible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): static
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function getFieldKey(): ?string
    {
        return $this->fieldKey;
    }

    public function setFieldKey(string $fieldKey): static
    {
        $this->fieldKey = $fieldKey;
        return $this;
    }

    public function getFieldTitle(): ?string
    {
        return $this->fieldTitle;
    }

    public function setFieldTitle(string $fieldTitle): static
    {
        $this->fieldTitle = $fieldTitle;
        return $this;
    }

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): static
    {
        $this->fieldType = $fieldType;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        return $this;
    }
}
