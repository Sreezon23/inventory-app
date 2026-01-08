<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Form\InventoryFormType;
use App\Repository\InventoryRepository;
use App\Security\Voter\InventoryVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InventoryController extends AbstractController
{
    #[Route('/inventory', name: 'inventory_index')]
    public function index(InventoryRepository $inventoryRepository): Response
    {
        // Logged-in users see inventories they own
        if ($this->getUser()) {
            $inventories = $inventoryRepository->findBy(
                ['owner' => $this->getUser()],
                ['createdAt' => 'DESC']
            );
        } else {
            // Anonymous users see all inventories (read-only)
            $inventories = $inventoryRepository->findBy(
                [],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('inventory/index.html.twig', [
            'inventories' => $inventories,
        ]);
    }

    #[Route('/inventory/new', name: 'inventory_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $inventory = new Inventory();
        $form = $this->createForm(InventoryFormType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inventory->setOwner($this->getUser());
            $inventory->setCreatedAt(new \DateTimeImmutable());
            $inventory->setUpdatedAt(new \DateTimeImmutable());

            $em->persist($inventory);
            $em->flush();

            $this->addFlash('success', 'Inventory created successfully.');

            return $this->redirectToRoute('inventory_index');
        }

        return $this->render('inventory/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/inventory/{id}', name: 'inventory_show', methods: ['GET'])]
    public function show(Inventory $inventory): Response
    {
        // Everyone can view
        $this->denyAccessUnlessGranted(
            InventoryVoter::VIEW,
            $inventory
        );

        return $this->render('inventory/show.html.twig', [
            'inventory' => $inventory,
        ]);
    }

    #[Route('/inventory/{id}/edit', name: 'inventory_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Inventory $inventory,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(
            InventoryVoter::EDIT,
            $inventory
        );

        $form = $this->createForm(InventoryFormType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inventory->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'Inventory updated successfully.');

            return $this->redirectToRoute('inventory_show', [
                'id' => $inventory->getId()
            ]);
        }

        return $this->render('inventory/edit.html.twig', [
            'inventory' => $inventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/inventory/{id}/delete', name: 'inventory_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Inventory $inventory,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(
            InventoryVoter::EDIT,
            $inventory
        );

        $submittedToken = $request->request->get('token');

        if ($this->isCsrfTokenValid(
            'delete-inventory-' . $inventory->getId(),
            $submittedToken
        )) {
            $em->remove($inventory);
            $em->flush();

            $this->addFlash('success', 'Inventory deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid delete request.');
        }

        return $this->redirectToRoute('inventory_index');
    }
}
