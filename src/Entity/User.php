<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 5)]
    private string $language = 'en';

    #[ORM\Column(length: 10)]
    private string $theme = 'light';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isBlocked = false;

    #[ORM\Column]
    private bool $isAdmin = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $socialProvider = null;

    #[ORM\Column(nullable: true)]
    private ?string $socialId = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Inventory::class, orphanRemoval: true)]
    private Collection $inventories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InventoryAccess::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $inventoryAccess;


    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ItemLike::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InventoryDiscussionPost::class, cascade: ['remove'])]
    private Collection $discussionPosts;

    public function __construct()
    {
        $this->inventories = new ArrayCollection();
        $this->inventoryAccess = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
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
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if ($this->isAdmin) $roles[] = 'ROLE_ADMIN';
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): self { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}

    public function getLanguage(): string { return $this->language; }
    public function setLanguage(string $language): self { $this->language = $language; return $this; }

    public function getTheme(): string { return $this->theme; }
    public function setTheme(string $theme): self { $this->theme = $theme; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function isBlocked(): bool { return $this->isBlocked; }
    public function setIsBlocked(bool $isBlocked): self { $this->isBlocked = $isBlocked; return $this; }

    public function isAdmin(): bool { return $this->isAdmin; }
    public function setIsAdmin(bool $isAdmin): self { $this->isAdmin = $isAdmin; return $this; }

    public function getInventories(): Collection { return $this->inventories; }
    public function getInventoryAccess(): Collection { return $this->inventoryAccess; }
    public function getLikes(): Collection { return $this->likes; }
    public function getDiscussionPosts(): Collection { return $this->discussionPosts; }
    public function getSocialProvider(): ?string { return $this->socialProvider; }
    public function setSocialProvider(?string $p): self { $this->socialProvider = $p; return $this; }
    public function getSocialId(): ?string { return $this->socialId; }
    public function setSocialId(?string $i): self { $this->socialId = $i; return $this; }
}