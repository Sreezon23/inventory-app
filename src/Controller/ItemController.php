<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inventory/{id}/items')]
final class ItemController extends AbstractController
{
    #[Route('', name: 'item_list', methods: ['GET'])]
    public function list(
        Inventory $inventory,
        ItemRepository $itemRepository
    ): Response {
        $items = $itemRepository->findBy(
            ['inventory' => $inventory],
            ['createdAt' => 'ASC']
        );

        return $this->render('item/list.html.twig', [
            'inventory' => $inventory,
            'items' => $items,
        ]);
    }
}
