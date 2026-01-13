<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\InventoryRepository::class)]
#[ORM\Table(name: 'inventory')]
#[ORM\Index(name: 'idx_inventory_creator', columns: ['creator_id'])]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

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
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'inventories')]
    #[ORM\JoinColumn(nullable: false)]
    private User $creator;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryField::class, cascade: ['remove'])]
    private Collection $fields;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryItem::class, cascade: ['remove'])]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: InventoryAccess::class, cascade: ['remove'])]
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
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->fields = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->accessList = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
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

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;
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

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getAccessList(): Collection
    {
        return $this->accessList;
    }

    public function getCustomIdFormat(): ?CustomIdFormat
    {
        return $this->customIdFormat;
    }

    public function setCustomIdFormat(?CustomIdFormat $format): self
    {
        $this->customIdFormat = $format;

        if ($format !== null && $format->getInventory() !== $this) {
            $format->setInventory($this);
        }

        return $this;
    }

    public function getDiscussionPosts(): Collection
    {
        return $this->discussionPosts;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

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
