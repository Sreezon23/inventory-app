<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(InventoryRepository $inventoryRepository): Response
    {
        $inventories = $inventoryRepository->findVisibleForUser($this->getUser());
        
        return $this->render('inventory/index.html.twig', [
            'inventories' => $inventories,
        ]);
    }
}
