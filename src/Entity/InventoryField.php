<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_field')]
class InventoryField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private Inventory $inventory;

    #[ORM\Column(type: 'string', length: 100)]
    private string $fieldName;

    #[ORM\Column(type: 'string', length: 50)]
    private string $fieldType;

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

    public const MAX_TEXT_FIELDS = 3;
    public const MAX_TEXTAREA_FIELDS = 3;
    public const MAX_NUMBER_FIELDS = 3;
    public const MAX_BOOLEAN_FIELDS = 3;
    public const MAX_DOCUMENT_FIELDS = 3;

    public function getId(): int
    {
        return $this->id;
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;
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