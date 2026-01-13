<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\InventoryItemRepository::class)]
#[ORM\Table(name: 'inventory_item')]
#[ORM\UniqueConstraint(name: 'UNIQ_INVENTORY_CUSTOM_ID', columns: ['inventory_id', 'custom_id'])]
#[ORM\Index(name: 'idx_item_inventory', columns: ['inventory_id'])]
#[ORM\Index(name: 'idx_item_created_by', columns: ['created_by_id'])]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Inventory $inventory;

    #[ORM\Column(type: 'string', length: 255)]
    private string $customId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemFieldValue::class, cascade: ['remove'])]
    private Collection $fieldValues;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemLike::class, cascade: ['remove'])]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->fieldValues = new ArrayCollection();
        $this->likes = new ArrayCollection();
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
        return $this;
    }

    public function getCustomId(): string
    {
        return $this->customId;
    }

    public function setCustomId(string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFieldValues(): Collection
    {
        return $this->fieldValues;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function countLikes(): int
    {
        return $this->likes->count();
    }

    public function hasLikeFromUser(User $user): bool
    {
        foreach ($this->likes as $like) {
            if ($like->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }
}
