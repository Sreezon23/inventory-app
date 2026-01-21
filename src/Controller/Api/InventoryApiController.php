<?php

namespace App\Controller\Api;

use App\Entity\Inventory;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/inventory', name: 'api_inventory_')]
class InventoryApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(InventoryRepository $inventoryRepository): JsonResponse
    {
        $inventories = $inventoryRepository->findVisibleForUser($this->getUser());

        return $this->json([
            'success' => true,
            'count' => count($inventories),
            'data' => array_map(fn (Inventory $inv) => [
                'id' => $inv->getId(),
                'title' => $inv->getTitle(),
                'description' => $inv->getDescription(),
                'category' => $inv->getCategory(),
                'isPublic' => $inv->isPublic(),
                'itemCount' => $inv->getItems()->count(),
                'createdAt' => $inv->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $inventories),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Inventory $inventory): JsonResponse
    {
        $this->checkInventoryAccess($inventory, 'read');

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $inventory->getId(),
                'title' => $inventory->getTitle(),
                'description' => $inventory->getDescription(),
                'category' => $inventory->getCategory(),
                'imageUrl' => $inventory->getImageUrl(),
                'isPublic' => $inventory->isPublic(),
                'version' => $inventory->getVersion(),
                'creator' => [
                    'id' => $inventory->getCreator()?->getId(),
                    'name' => $inventory->getCreator()?->getName(),
                    'email' => $inventory->getCreator()?->getEmail(),
                ],
                'itemCount' => $inventory->getItems()->count(),
                'createdAt' => $inventory->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $inventory->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return $this->json(['success' => false, 'error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $inventory = new Inventory();
        $inventory->setTitle($data['title']);
        $inventory->setDescription($data['description'] ?? null);
        $inventory->setCategory($data['category'] ?? 'Other');
        $inventory->setImageUrl($data['imageUrl'] ?? null);
        $inventory->setIsPublic($data['isPublic'] ?? false);
        $inventory->setCreator($this->getUser());

        $em->persist($inventory);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Inventory created successfully',
            'data' => ['id' => $inventory->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request, EntityManagerInterface $em, InventoryRepository $repo): JsonResponse
    {
        $inventory = $repo->find($id);
        if (!$inventory) {
            return $this->json(['success' => false, 'error' => 'Inventory not found'], Response::HTTP_NOT_FOUND);
        }

        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $inventory->setTitle($data['title']);
        if (isset($data['description'])) $inventory->setDescription($data['description']);
        if (isset($data['category'])) $inventory->setCategory($data['category']);
        if (isset($data['imageUrl'])) $inventory->setImageUrl($data['imageUrl']);
        if (isset($data['isPublic'])) $inventory->setIsPublic($data['isPublic']);

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'data' => ['id' => $inventory->getId()],
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, EntityManagerInterface $em, InventoryRepository $repo): JsonResponse
    {
        $inventory = $repo->find($id);
        if (!$inventory) {
            return $this->json(['success' => false, 'error' => 'Inventory not found'], Response::HTTP_NOT_FOUND);
        }

        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($inventory);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Inventory deleted successfully',
        ]);
    }

    private function checkInventoryAccess(Inventory $inventory, string $type = 'read'): void
    {
        $user = $this->getUser();

        if ($inventory->isPublic() || $inventory->getCreator()->getId() === $user?->getId()) {
            return;
        }

        throw $this->createAccessDeniedException();
    }
}