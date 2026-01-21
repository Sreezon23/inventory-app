<?php

namespace App\Service;

use App\Entity\ApiToken;
use App\Entity\Inventory;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiTokenService
{
    private ApiTokenRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ApiTokenRepository $repository,
        EntityManagerInterface $entityManager
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    public function createToken(Inventory $inventory, ?string $description = null): ApiToken
    {
        $token = new ApiToken();
        $token->setInventory($inventory);
        $token->setDescription($description);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function revokeToken(ApiToken $token): void
    {
        $token->setIsActive(false);
        $this->entityManager->flush();
    }

    public function getTokensForInventory(Inventory $inventory): array
    {
        return $this->repository->findByInventory($inventory);
    }

    public function validateToken(string $token): ?ApiToken
    {
        return $this->repository->findByToken($token);
    }

    public function regenerateToken(ApiToken $token): ApiToken
    {
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setCreatedAt(new \DateTimeImmutable());
        $token->setExpiresAt(new \DateTimeImmutable('+1 year'));
        $token->setIsActive(true);

        $this->entityManager->flush();

        return $token;
    }
}
