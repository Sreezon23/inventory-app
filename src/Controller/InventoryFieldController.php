<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\InventoryField;
use App\Form\InventoryFieldType; // We will need to create this Form next if it doesn't exist
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/inventory/{inventory_id}/field')]
class InventoryFieldController extends AbstractController
{
    #[Route('/new', name: 'inventory_field_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $inventory_id, InventoryRepository $inventoryRepository, EntityManagerInterface $entityManager): Response
    {
        // 1. Find the Inventory (Parent)
        $inventory = $inventoryRepository->find($inventory_id);

        if (!$inventory) {
            throw $this->createNotFoundException('Inventory not found');
        }

        // 2. Create the new Field
        $field = new InventoryField();
        $field->setInventory($inventory);

        // 3. Create Form (You might need to generate this form class if you haven't)
        // If you don't have InventoryFieldType yet, run: php bin/console make:form InventoryFieldType
        $form = $this->createForm(\App\Form\InventoryFieldType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($field);
            $entityManager->flush();

            return $this->redirectToRoute('inventory_show', ['id' => $inventory->getId()]);
        }

        return $this->render('inventory_field/new.html.twig', [
            'inventory' => $inventory,
            'form' => $form->createView(),
        ]);
    }
}