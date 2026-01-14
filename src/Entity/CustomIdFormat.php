<?php

namespace App\Entity;

use App\Repository\CustomIdFormatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomIdFormatRepository::class)]
#[ORM\Table(name: 'custom_id_format')]
#[ORM\HasLifecycleCallbacks] 
class CustomIdFormat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'customIdFormat', targetEntity: Inventory::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Inventory $inventory = null;


    #[ORM\Column(type: 'json')]
    private array $elements = [];


    #[ORM\Column(type: 'integer')]
    #[ORM\Version] 
    private int $version = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {

    }

    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function setElements(array $elements): self
    {
        $this->elements = $elements;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSequencePadding(): int
    {
        foreach ($this->elements as $element) {
            if (isset($element['type']) && $element['type'] === 'sequence') {
                return (int)($element['padding'] ?? 0);
            }
        }
        return 0;
    }
}