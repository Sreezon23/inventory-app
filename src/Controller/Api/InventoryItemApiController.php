<?php

namespace App\Controller\Api;

use App\Entity\Inventory;
use App\Entity\InventoryItem;
use App\Entity\ItemLike;
use App\Repository\InventoryItemRepository;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/inventory/{inventory_id}/item', name: 'api_item_')]
class InventoryItemApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $inventory_id, InventoryRepository $invRepo, InventoryItemRepository $itemRepo): JsonResponse
    {
        $inventory = $invRepo->find($inventory_id);
        if (!$inventory) {
            return $this->json(['success' => false, 'error' => 'Inventory not found'], Response::HTTP_NOT_FOUND);
        }

        $items = $itemRepo->findByInventoryWithValues($inventory);

        return $this->json([
            'success' => true,
            'count' => count($items),
            'data' => array_map(fn (InventoryItem $item) => [
                'id' => $item->getId(),
                'customId' => $item->getCustomId(),
                'createdBy' => $item->getCreatedBy()->getName(),
                'likes' => $item->countLikes(),
                'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $items),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $inventory_id, int $id, InventoryRepository $invRepo, InventoryItemRepository $itemRepo): JsonResponse
    {
        $inventory = $invRepo->find($inventory_id);
        if (!$inventory) {
            return $this->json(['success' => false, 'error' => 'Inventory not found'], Response::HTTP_NOT_FOUND);
        }

        $item = $itemRepo->find($id);
        if (!$item || $item->getInventory()->getId() !== $inventory_id) {
            return $this->json(['success' => false, 'error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $item->getId(),
                'customId' => $item->getCustomId(),
                'createdBy' => [
                    'id' => $item->getCreatedBy()->getId(),
                    'name' => $item->getCreatedBy()->getName(),
                ],
                'version' => $item->getVersion(),
                'likes' => $item->countLikes(),
                'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $item->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('/{id}/like', name: 'like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleLike(int $inventory_id, int $id, InventoryRepository $invRepo, InventoryItemRepository $itemRepo, EntityManagerInterface $em): JsonResponse
    {
        $inventory = $invRepo->find($inventory_id);
        if (!$inventory) {
            return $this->json(['success' => false, 'error' => 'Inventory not found'], Response::HTTP_NOT_FOUND);
        }

        $item = $itemRepo->find($id);
        if (!$item || $item->getInventory()->getId() !== $inventory_id) {
            return $this->json(['success' => false, 'error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $existingLike = null;

        foreach ($item->getLikes() as $like) {
            if ($like->getUser()->getId() === $user->getId()) {
                $existingLike = $like;
                break;
            }
        }

        $liked = false;
        if ($existingLike) {
            $em->remove($existingLike);
        } else {
            $like = new ItemLike();
            $like->setItem($item);
            $like->setUser($user);
            $em->persist($like);
            $liked = true;
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'liked' => $liked,
            'likes' => $item->countLikes(),
        ]);
    }
}