<?php

namespace App\Entity;

use App\Repository\InventoryItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: 'inventory_item')]
#[ORM\Index(name: 'idx_item_inventory', columns: ['inventory_id'])]
#[ORM\UniqueConstraint(name: 'uniq_item_custom_id', columns: ['inventory_id', 'custom_id'])]
#[ORM\HasLifecycleCallbacks]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Inventory $inventory = null;

    #[ORM\Column(length: 64)]
    private ?string $customId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $text1 = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $text2 = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $text3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textarea1 = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textarea2 = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textarea3 = null;

    #[ORM\Column(nullable: true)]
    private ?float $number1 = null;
    #[ORM\Column(nullable: true)]
    private ?float $number2 = null;
    #[ORM\Column(nullable: true)]
    private ?float $number3 = null;

    #[ORM\Column(nullable: true)]
    private ?bool $bool1 = null;
    #[ORM\Column(nullable: true)]
    private ?bool $bool2 = null;
    #[ORM\Column(nullable: true)]
    private ?bool $bool3 = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $link1 = null;
    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $link2 = null;
    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $link3 = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemLike::class, cascade: ['remove'])]
    private Collection $likes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
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

    public function getId(): ?int { return $this->id; }

    public function getInventory(): ?Inventory { return $this->inventory; }
    public function setInventory(?Inventory $inventory): self { $this->inventory = $inventory; return $this; }

    public function getCustomId(): ?string { return $this->customId; }
    public function setCustomId(string $customId): self { $this->customId = $customId; return $this; }

    public function getValueForSlot(string $slot): mixed
    {
        return match($slot) {
            'text1' => $this->text1, 'text2' => $this->text2, 'text3' => $this->text3,
            'textarea1' => $this->textarea1, 'textarea2' => $this->textarea2, 'textarea3' => $this->textarea3,
            'number1' => $this->number1, 'number2' => $this->number2, 'number3' => $this->number3,
            'bool1' => $this->bool1, 'bool2' => $this->bool2, 'bool3' => $this->bool3,
            'link1' => $this->link1, 'link2' => $this->link2, 'link3' => $this->link3,
            default => null,
        };
    }


    public function setValueForSlot(string $slot, mixed $value): self
    {
        match($slot) {
            'text1' => $this->setText1($value),
            'text2' => $this->setText2($value),
            'text3' => $this->setText3($value),
            'textarea1' => $this->setTextarea1($value),
            'textarea2' => $this->setTextarea2($value),
            'textarea3' => $this->setTextarea3($value),
            'number1' => $this->setNumber1($value === null ? null : (float)$value),
            'number2' => $this->setNumber2($value === null ? null : (float)$value),
            'number3' => $this->setNumber3($value === null ? null : (float)$value),
            'bool1' => $this->setBool1($value === null ? null : (bool)$value),
            'bool2' => $this->setBool2($value === null ? null : (bool)$value),
            'bool3' => $this->setBool3($value === null ? null : (bool)$value),
            'link1' => $this->setLink1($value),
            'link2' => $this->setLink2($value),
            'link3' => $this->setLink3($value),
            default => null,
        };

        return $this;
    }


    public function getValueByField(InventoryField $field): mixed
    {

        return $this->getValueForSlot($field->getStorageSlot());
    }


    public function setValueByField(InventoryField $field, mixed $value): self
    {
        return $this->setValueForSlot($field->getStorageSlot(), $value);
    }


    public function setText1(?string $v): self { $this->text1 = $v; return $this; }
    public function setText2(?string $v): self { $this->text2 = $v; return $this; }
    public function setText3(?string $v): self { $this->text3 = $v; return $this; }

    public function setTextarea1(?string $v): self { $this->textarea1 = $v; return $this; }
    public function setTextarea2(?string $v): self { $this->textarea2 = $v; return $this; }
    public function setTextarea3(?string $v): self { $this->textarea3 = $v; return $this; }

    public function setNumber1(?float $v): self { $this->number1 = $v; return $this; }
    public function setNumber2(?float $v): self { $this->number2 = $v; return $this; }
    public function setNumber3(?float $v): self { $this->number3 = $v; return $this; }

    public function setBool1(?bool $v): self { $this->bool1 = $v; return $this; }
    public function setBool2(?bool $v): self { $this->bool2 = $v; return $this; }
    public function setBool3(?bool $v): self { $this->bool3 = $v; return $this; }

    public function setLink1(?string $v): self { $this->link1 = $v; return $this; }
    public function setLink2(?string $v): self { $this->link2 = $v; return $this; }
    public function setLink3(?string $v): self { $this->link3 = $v; return $this; }


    public function getText1(): ?string { return $this->text1; }
    public function getText2(): ?string { return $this->text2; }
    public function getText3(): ?string { return $this->text3; }
    
    public function getTextarea1(): ?string { return $this->textarea1; }
    public function getTextarea2(): ?string { return $this->textarea2; }
    public function getTextarea3(): ?string { return $this->textarea3; }

    public function getNumber1(): ?float { return $this->number1; }
    public function getNumber2(): ?float { return $this->number2; }
    public function getNumber3(): ?float { return $this->number3; }

    public function isBool1(): ?bool { return $this->bool1; }
    public function isBool2(): ?bool { return $this->bool2; }
    public function isBool3(): ?bool { return $this->bool3; }

    public function getLink1(): ?string { return $this->link1; }
    public function getLink2(): ?string { return $this->link2; }
    public function getLink3(): ?string { return $this->link3; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getLikes(): Collection { return $this->likes; }

    public function addLike(ItemLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setItem($this);
        }
        return $this;
    }

    public function removeLike(ItemLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getItem() === $this) {
                $like->setItem(null);
            }
        }
        return $this;
    }

    public function countLikes(): int { return $this->likes->count(); }
}