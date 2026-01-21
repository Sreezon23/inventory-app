<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Repository\UserRepository;
use App\Service\InventoryAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/{id}/access')]
#[IsGranted('ROLE_USER')]
class InventoryAccessController extends AbstractController
{
    #[Route('/', name: 'inventory_access_index', methods: ['GET', 'POST'])]
    public function index(
        Inventory $inventory, 
        Request $request, 
        UserRepository $userRepo,
        InventoryAccessService $accessService
    ): Response
    {
        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $canWrite = $request->request->has('can_write');
            
            $userToAdd = $userRepo->findOneBy(['email' => $email]);

            if ($userToAdd) {
                if ($userToAdd->getId() === $this->getUser()->getId()) {
                    $this->addFlash('error', 'You cannot add yourself.');
                } else {
                    $accessService->grantAccess($inventory, $userToAdd, $canWrite);
                    $this->addFlash('success', 'User access updated.');
                }
            } else {
                $this->addFlash('error', 'User not found with that email.');
            }

            return $this->redirectToRoute('inventory_access_index', ['id' => $inventory->getId()]);
        }

        return $this->render('inventory_access/index.html.twig', [
            'inventory' => $inventory,
            'access_list' => $inventory->getAccessList(),
        ]);
    }

    #[Route('/revoke/{user_id}', name: 'inventory_access_revoke', methods: ['POST'])]
    public function revoke(
        int $user_id,
        Inventory $inventory, 
        UserRepository $userRepo,
        InventoryAccessService $accessService,
        Request $request
    ): Response
    {
        if ($inventory->getCreator()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('revoke_access', $request->request->get('_token'))) {
            return $this->redirectToRoute('inventory_access_index', ['id' => $inventory->getId()]);
        }

        $userToRemove = $userRepo->find($user_id);

        if ($userToRemove) {
            $accessService->revokeAccess($inventory, $userToRemove);
            $this->addFlash('success', 'Access revoked.');
        }

        return $this->redirectToRoute('inventory_access_index', ['id' => $inventory->getId()]);
    }
}