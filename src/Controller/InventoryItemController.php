<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\InventoryItem;
use App\Entity\ItemLike;
use App\Form\InventoryItemType;
use App\Repository\InventoryItemRepository;
use App\Repository\InventoryRepository; // <--- Added this
use App\Service\CustomIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <--- CHANGED THIS
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/{inventory_id<\d+>}/item')]
class InventoryItemController extends AbstractController
{
    #[Route('/', name: 'inventory_item_index', methods: ['GET'])]
    public function index(
        int $inventory_id, 
        InventoryItemRepository $repository,
        InventoryRepository $inventoryRepository // <--- Injected here
    ): Response
    {
        // Replaced getDoctrine() with injected repository
        $inventory = $inventoryRepository->find($inventory_id);
        
        if (!$inventory) {
            throw $this->createNotFoundException();
        }

        $items = $repository->findByInventoryWithValues($inventory);

        return $this->render('inventory_item/index.html.twig', [
            'inventory' => $inventory,
            'items' => $items,
        ]);
    }

    #[Route('/new', name: 'inventory_item_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        int $inventory_id,
        Request $request,
        EntityManagerInterface $em,
        CustomIdGenerator $idGenerator,
        InventoryRepository $inventoryRepository // <--- Injected here
    ): Response {
        
        // Replaced getDoctrine() with injected repository
        $inventory = $inventoryRepository->find($inventory_id);
        
        if (!$inventory) {
            throw $this->createNotFoundException();
        }

        $this->checkInventoryWriteAccess($inventory);

        $item = new InventoryItem();
        $item->setInventory($inventory);
        $item->setCreatedBy($this->getUser());

        // Generate custom ID if format exists
        if ($customFormat = $inventory->getCustomIdFormats()->first()) {
            $item->setCustomId($idGenerator->generateForItem($customFormat, $item));
        } else {
            // Use UUID-like default
            $item->setCustomId('ITEM-' . uniqid());
        }

        $form = $this->createForm(InventoryItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($item);
            $em->flush();

            $this->addFlash('success', 'Item created!');

            return $this->redirectToRoute('inventory_item_index', ['inventory_id' => $inventory->getId()]);
        }

        return $this->render('inventory_item/new.html.twig', [
            'form' => $form->createView(),
            'inventory' => $inventory,
        ]);
    }

    #[Route('/{id}', name: 'inventory_item_show', methods: ['GET'])]
    public function show(int $inventory_id, InventoryItem $item): Response
    {
        if ($item->getInventory()->getId() !== $inventory_id) {
            throw $this->createNotFoundException();
        }

        return $this->render('inventory_item/show.html.twig', [
            'item' => $item,
            'inventory' => $item->getInventory(),
        ]);
    }

    #[Route('/{id}/like', name: 'inventory_item_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleLike(int $inventory_id, InventoryItem $item, EntityManagerInterface $em): Response
    {
        if ($item->getInventory()->getId() !== $inventory_id) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $existingLike = null;

        foreach ($item->getLikes() as $like) {
            if ($like->getUser()->getId() === $user->getId()) {
                $existingLike = $like;
                break;
            }
        }

        if ($existingLike) {
            $em->remove($existingLike);
        } else {
            $like = new ItemLike();
            $like->setItem($item);
            $like->setUser($user);
            $em->persist($like);
        }

        $em->flush();

        return $this->json(['likes' => $item->countLikes()]);
    }

    private function checkInventoryWriteAccess(Inventory $inventory): void
    {
        $user = $this->getUser();

        if ($inventory->getCreator()->getId() === $user->getId()) {
            return;
        }

        foreach ($inventory->getAccessList() as $access) {
            if ($access->getUser()->getId() === $user->getId() && $access->isCanWrite()) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }
}