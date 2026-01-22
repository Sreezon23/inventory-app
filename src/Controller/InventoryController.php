<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\ApiToken;
use App\Form\InventoryType;
use App\Repository\InventoryRepository;
use App\Service\ApiTokenService;
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

    #[Route(
        '/{id}/api-tokens',
        name: 'inventory_api_tokens',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    #[IsGranted('ROLE_USER')]
    public function apiTokens(Inventory $inventory, EntityManagerInterface $em): Response
    {
        // Check if user is the creator or if inventory has no creator (for demo purposes)
        if ($inventory->getCreator() && $inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Use repository directly to avoid service dependency issues
        $repository = $em->getRepository(ApiToken::class);
        $tokens = $repository->findBy(['inventory' => $inventory, 'isActive' => true], ['createdAt' => 'DESC']);

        return $this->render('inventory/api_tokens.html.twig', [
            'inventory' => $inventory,
            'tokens' => $tokens,
        ]);
    }

    #[Route(
        '/{id}/api-tokens/create',
        name: 'inventory_api_token_create',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    #[IsGranted('ROLE_USER')]
    public function createApiToken(Inventory $inventory, Request $request, EntityManagerInterface $em): Response
    {
        // Check if user is the creator or if inventory has no creator (for demo purposes)
        if ($inventory->getCreator() && $inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('create_token' . $inventory->getId(), $request->request->get('_token'))) {
            $description = $request->request->get('description');
            
            // Create token directly without service
            $token = new ApiToken();
            $token->setInventory($inventory);
            $token->setDescription($description);
            
            $em->persist($token);
            $em->flush();
            
            $this->addFlash('success', 'API token created successfully: ' . $token->getToken());
        }

        return $this->redirectToRoute('inventory_api_tokens', ['id' => $inventory->getId()]);
    }

    #[Route(
        '/{id}/api-tokens/{tokenId}/revoke',
        name: 'inventory_api_token_revoke',
        methods: ['POST'],
        requirements: ['id' => '\d+', 'tokenId' => '\d+']
    )]
    #[IsGranted('ROLE_USER')]
    public function revokeApiToken(Inventory $inventory, int $tokenId, Request $request, EntityManagerInterface $em): Response
    {
        // Check if user is the creator or if inventory has no creator (for demo purposes)
        if ($inventory->getCreator() && $inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('revoke_token' . $tokenId, $request->request->get('_token'))) {
            $token = $em->find(ApiToken::class, $tokenId);
            if ($token && $token->getInventory()->getId() === $inventory->getId()) {
                // Revoke token directly without service
                $token->setIsActive(false);
                $em->flush();
                $this->addFlash('success', 'API token revoked successfully');
            }
        }

        return $this->redirectToRoute('inventory_api_tokens', ['id' => $inventory->getId()]);
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
