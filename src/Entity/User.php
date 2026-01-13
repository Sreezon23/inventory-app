<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 5)]
    private string $language = 'en';

    #[ORM\Column(type: 'string', length: 10)]
    private string $theme = 'light';

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isBlocked = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isAdmin = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $socialProvider = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $socialId = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Inventory::class)]
    private Collection $inventories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InventoryAccess::class, cascade: ['remove'])]
    private Collection $inventoryAccess;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: InventoryItem::class)]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ItemLike::class, cascade: ['remove'])]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InventoryDiscussionPost::class)]
    private Collection $discussionPosts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->inventories = new ArrayCollection();
        $this->inventoryAccess = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    public function eraseCredentials(): void
    {

    }

    public function getInventories(): Collection
    {
        return $this->inventories;
    }

    public function getInventoryAccess(): Collection
    {
        return $this->inventoryAccess;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function getDiscussionPosts(): Collection
    {
        return $this->discussionPosts;
    }
}
