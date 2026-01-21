<?php

namespace App\Entity;

use App\Repository\InventoryFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryFieldRepository::class)]
#[ORM\Table(name: 'inventory_field')]
#[ORM\UniqueConstraint(name: 'unique_field_name_inv', columns: ['inventory_id', 'field_name'])]
#[ORM\UniqueConstraint(name: 'unique_storage_slot_inv', columns: ['inventory_id', 'storage_slot'])]
class InventoryField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private Inventory $inventory;

    #[ORM\Column(type: 'string', length: 100)]
    private string $fieldName;

    #[ORM\Column(type: 'string', length: 50)]
    private string $fieldType;

    #[ORM\Column(type: 'string', length: 20)]
    private string $storageSlot;

    #[ORM\Column(type: 'integer')]
    private int $fieldOrder = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $showInTable = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRequired = false;

    #[ORM\Column(type: 'json')]
    private array $fieldData = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DOCUMENT_LINK = 'document_link';

    public const MAX_PER_TYPE = 3;

    public const ALLOWED_SLOTS = [
        'text1', 'text2', 'text3',
        'textarea1', 'textarea2', 'textarea3',
        'number1', 'number2', 'number3',
        'bool1', 'bool2', 'bool3',
        'link1', 'link2', 'link3',
    ];

    public static function getTypeFromSlot(string $slot): string 
    {
        if (str_starts_with($slot, 'textarea')) return self::TYPE_TEXTAREA;
        if (str_starts_with($slot, 'text')) return self::TYPE_TEXT;
        if (str_starts_with($slot, 'number')) return self::TYPE_NUMBER;
        if (str_starts_with($slot, 'bool')) return self::TYPE_BOOLEAN;
        if (str_starts_with($slot, 'link')) return self::TYPE_DOCUMENT_LINK;
        
        return 'unknown';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): self
    {
        if ($inventory !== null) {
            $this->inventory = $inventory;
        }
        return $this;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): self
    {
        $this->fieldType = $fieldType;
        return $this;
    }

    public function getStorageSlot(): string
    {
        return $this->storageSlot;
    }

    public function setStorageSlot(string $storageSlot): self
    {
        if (!in_array($storageSlot, self::ALLOWED_SLOTS)) {
            throw new \InvalidArgumentException("Invalid storage slot: $storageSlot");
        }
        $this->storageSlot = $storageSlot;
        return $this;
    }

    public function getFieldOrder(): int
    {
        return $this->fieldOrder;
    }

    public function setFieldOrder(int $fieldOrder): self
    {
        $this->fieldOrder = $fieldOrder;
        return $this;
    }

    public function isShowInTable(): bool
    {
        return $this->showInTable;
    }

    public function setShowInTable(bool $showInTable): self
    {
        $this->showInTable = $showInTable;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    public function getFieldData(): array
    {
        return $this->fieldData;
    }

    public function setFieldData(array $fieldData): self
    {
        $this->fieldData = $fieldData;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}