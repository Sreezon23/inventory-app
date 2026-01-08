<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Legacy field – keep for now (UI already uses it).
     * Will be deprecated later in favor of voters.
     */
    #[ORM\Column(length: 50)]
    private ?string $accessLevel = null;

    /**
     * Optimistic locking version.
     */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;


    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'inventories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * Canonical relation to Item (InventoryItem removed).
     */
    #[ORM\OneToMany(
        mappedBy: 'inventory',
        targetEntity: Item::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $items;

    /**
     * Public inventory flag (Phase 1.2).
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = false;

    /**
     * Users with write access (Phase 1.2).
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'inventory_writers')]
    private Collection $writers;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->writers = new ArrayCollection();
    }

    /* =======================
       Getters / Setters
       ======================= */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAccessLevel(): ?string
    {
        return $this->accessLevel;
    }

    public function setAccessLevel(string $accessLevel): static
    {
        $this->accessLevel = $accessLevel;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInventory($this);
        }

        return $this;
    }

    public function removeItem(Item $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInventory() === $this) {
                // inventory is non-nullable, so do NOT set null
            }
        }

        return $this;
    }

    public function getItemCount(): int
    {
        return $this->items->count();
    }

    /* ===== Access control fields ===== */

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getWriters(): Collection
    {
        return $this->writers;
    }

    public function addWriter(User $user): static
    {
        if (!$this->writers->contains($user)) {
            $this->writers->add($user);
        }
        return $this;
    }

    public function removeWriter(User $user): static
    {
        $this->writers->removeElement($user);
        return $this;
    }
}
