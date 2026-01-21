<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Form\InventoryType;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory')]
class InventoryController extends AbstractController
{
    #[Route('/', name: 'inventory_index', methods: ['GET'])]
    public function index(InventoryRepository $inventoryRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $inventories = $inventoryRepository->findVisibleForUser($user);

        return $this->render('inventory/index.html.twig', [
            'inventories' => $inventories,
        ]);
    }

    #[Route('/new', name: 'inventory_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $inventory = new Inventory();
        $inventory->setCreator($this->getUser());

        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($inventory);
                $em->flush();
                $this->addFlash('success', 'Inventory created successfully!');

                return $this->redirectToRoute('inventory_show', ['id' => $inventory->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating inventory: ' . $e->getMessage());
                error_log('Inventory creation error: ' . $e->getMessage());
            }
        }

        return $this->render('inventory/new.html.twig', [
            'inventory' => $inventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}',
        name: 'inventory_show',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function show(Inventory $inventory): Response
    {
        $this->checkInventoryAccess($inventory, 'read');

        return $this->render('inventory/show.html.twig', [
            'inventory' => $inventory,
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'inventory_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+']
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Inventory $inventory, EntityManagerInterface $em): Response
    {
        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Inventory updated!');

            return $this->redirectToRoute('inventory_show', ['id' => $inventory->getId()]);
        }

        return $this->render('inventory/edit.html.twig', [
            'inventory' => $inventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}',
        name: 'inventory_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Inventory $inventory, EntityManagerInterface $em): Response
    {
        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $inventory->getId(), $request->request->get('_token'))) {
            $em->remove($inventory);
            $em->flush();
            $this->addFlash('success', 'Inventory deleted!');
        }

        return $this->redirectToRoute('inventory_index');
    }

    private function checkInventoryAccess(Inventory $inventory, string $type = 'read'): void
    {
        $user = $this->getUser();

        if ($type === 'read' && $inventory->isPublic()) {
            return;
        }

        if ($user && $inventory->getCreator()->getId() === $user->getId()) {
            return;
        }

        if ($user) {
            foreach ($inventory->getAccessList() as $access) {
                if ($access->getUser()->getId() === $user->getId()) {
                    if ($type === 'write' && !$access->isCanWrite()) {
                        throw $this->createAccessDeniedException();
                    }

                    return;
                }
            }
        }

        throw $this->createAccessDeniedException('You do not have access to this inventory.');
    }
}
