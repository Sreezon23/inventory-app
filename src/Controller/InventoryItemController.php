<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\InventoryItem;
use App\Entity\ItemLike;
use App\Form\InventoryItemType;
use App\Repository\InventoryItemRepository;
use App\Repository\InventoryRepository;
use App\Service\CustomIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/inventory/{inventory_id<\d+>}/item')]
class InventoryItemController extends AbstractController
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }
    #[Route('/', name: 'inventory_item_index', methods: ['GET'])]
    public function index(
        int $inventory_id,
        InventoryItemRepository $repository,
        InventoryRepository $inventoryRepository
    ): Response
    {
        $inventory = $inventoryRepository->find($inventory_id);

        if (!$inventory) {
            throw $this->createNotFoundException();
        }

        $items = $repository->findBy(['inventory' => $inventory], ['createdAt' => 'DESC']);

        return $this->render('inventory_item/index.html.twig', [
            'inventory' => $inventory,
            'items' => $items,
            'fields' => $inventory->getFields(), 
        ]);
    }

    #[Route('/new', name: 'inventory_item_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        int $inventory_id,
        Request $request,
        EntityManagerInterface $em,
        CustomIdGenerator $idGenerator,
        InventoryRepository $inventoryRepository
    ): Response {

        $inventory = $inventoryRepository->find($inventory_id);

        if (!$inventory) {
            throw $this->createNotFoundException();
        }

        $this->checkInventoryWriteAccess($inventory);

        $item = new InventoryItem();
        $item->setInventory($inventory);
        $item->setCreatedBy($this->getUser());

        $customFormat = $inventory->getCustomIdFormat();
        if ($customFormat) {
            $item->setCustomId($idGenerator->generateForItem($customFormat, $item));
        } else {
            $item->setCustomId('ITEM-' . uniqid());
        }

        $fields = $inventory->getFields()->toArray();


        usort($fields, fn($a, $b) => $a->getFieldOrder() <=> $b->getFieldOrder());

        $form = $this->createForm(InventoryItemType::class, $item, [
            'custom_fields' => $fields,
        ]);
        
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

    #[Route('/{id}/edit', name: 'inventory_item_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        int $inventory_id, 
        InventoryItem $item, 
        Request $request, 
        EntityManagerInterface $em
    ): Response
    {
        if ($item->getInventory()->getId() !== $inventory_id) {
            throw $this->createNotFoundException();
        }

        $this->checkInventoryWriteAccess($item->getInventory());


        $fields = $item->getInventory()->getFields()->toArray();
        usort($fields, fn($a, $b) => $a->getFieldOrder() <=> $b->getFieldOrder());

        $form = $this->createForm(InventoryItemType::class, $item, [
            'custom_fields' => $fields,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Item updated!');
            return $this->redirectToRoute('inventory_item_index', ['inventory_id' => $inventory_id]);
        }

        return $this->render('inventory_item/edit.html.twig', [
            'form' => $form->createView(),
            'inventory' => $item->getInventory(),
            'item' => $item
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
            'fields' => $item->getInventory()->getFields(),
        ]);
    }

    #[Route('/like/{id}', name: 'inventory_item_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleLike(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $item = $em->find(InventoryItem::class, $id);
        if (!$item) {
            throw $this->createNotFoundException();
        }

        $submittedToken = $request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid('inventory-item-like', $submittedToken)) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
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
            $action = 'unliked';
        } else {
            $like = new ItemLike();
            $like->setItem($item);
            $like->setUser($user);
            $em->persist($like);
            $action = 'liked';
        }

        $em->flush();
        
        return $this->json([
            'success' => true,
            'status' => $action, 
            'likes' => $item->getLikes()->count()
        ]);
    }

    #[Route('/{id}/debug-csrf', name: 'inventory_item_debug_csrf', methods: ['GET'])]
    public function debugCsrf(int $inventory_id, InventoryItem $item): Response
    {
        if ($item->getInventory()->getId() !== $inventory_id) {
            throw $this->createNotFoundException();
        }

        $token = $this->csrfTokenManager->getToken('inventory-item-like');
        
        return $this->json([
            'token' => $token,
            'token_id' => 'inventory-item-like',
            'item_id' => $item->getId(),
        ]);
    }

    private function checkInventoryWriteAccess(Inventory $inventory): void
    {
        $user = $this->getUser();
        if (!$user) throw $this->createAccessDeniedException();

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