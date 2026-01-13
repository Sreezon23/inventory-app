<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'custom_id_format')]
class CustomIdFormat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'customIdFormat', targetEntity: Inventory::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Inventory $inventory;

    #[ORM\Column(type: 'json')]
    private array $elements = [];

    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

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

        if ($inventory->getCustomIdFormat() !== $this) {
            $inventory->setCustomIdFormat($this);
        }

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function setElements(array $elements): self
    {
        $this->elements = $elements;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function incrementVersion(): self
    {
        $this->version++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSequencePadding(): int
    {
        foreach ($this->elements as $element) {
            if (($element['type'] ?? null) === 'sequence') {
                return $element['padding'] ?? 0;
            }
        }

        return 0;
    }
}
