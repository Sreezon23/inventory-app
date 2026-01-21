<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ORM\Table(name: 'inventory')]
#[ORM\Index(name: 'idx_inventory_creator', columns: ['creator_id'])]
#[ORM\HasLifecycleCallbacks]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category = 'Other';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = false;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'inventories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryField::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $fields;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryAccess::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accessList;

    #[ORM\OneToOne(mappedBy: 'inventory', targetEntity: CustomIdFormat::class, cascade: ['persist', 'remove'])]
    private ?CustomIdFormat $customIdFormat = null;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryDiscussionPost::class, cascade: ['remove'])]
    private Collection $discussionPosts;

    #[ORM\ManyToMany(targetEntity: InventoryTag::class)]
    #[ORM\JoinTable(name: 'inventory_has_tag')]
    private Collection $tags;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->accessList = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getName(): string { return $this->title; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getVersion(): int { return $this->version; }

    public function getCreator(): ?User { return $this->creator; }
    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getFields(): Collection { return $this->fields; }

    public function addField(InventoryField $field): self
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setInventory($this);
        }
        return $this;
    }

    public function removeField(InventoryField $field): self
    {
        if ($this->fields->removeElement($field)) {
            if ($field->getInventory() === $this) {
                $field->setInventory(null);
            }
        }
        return $this;
    }

    public function getItems(): Collection { return $this->items; }

    public function getAccessList(): Collection { return $this->accessList; }

    public function getCustomIdFormat(): ?CustomIdFormat { return $this->customIdFormat; }
    public function setCustomIdFormat(?CustomIdFormat $customIdFormat): self
    {
        if ($customIdFormat === null && $this->customIdFormat !== null) {
            $this->customIdFormat->setInventory(null);
        }

        if ($customIdFormat !== null && $customIdFormat->getInventory() !== $this) {
            $customIdFormat->setInventory($this);
        }

        $this->customIdFormat = $customIdFormat;
        return $this;
    }

    public function getDiscussionPosts(): Collection { return $this->discussionPosts; }

    public function getTags(): Collection { return $this->tags; }

    public function addTag(InventoryTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(InventoryTag $tag): self
    {
        $this->tags->removeElement($tag);
        return $this;
    }
}