<?php

namespace App\Controller;

use App\Form\SalesforceIntegrationType;
use App\Service\SalesforceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/salesforce')]
class SalesforceController extends AbstractController
{
    private SalesforceService $salesforceService;

    public function __construct(SalesforceService $salesforceService)
    {
        $this->salesforceService = $salesforceService;
    }

    #[Route('/integration', name: 'salesforce_integration', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function integration(Request $request): Response
    {
        $user = $this->getUser();
        
        $form = $this->createForm(SalesforceIntegrationType::class, [
            'first_name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            
            // Add user's existing data
            $userData['name'] = $user->getName();
            $userData['email'] = $user->getEmail();

            $result = $this->salesforceService->createAccountAndContact($userData);

            if ($result && $result['success']) {
                $this->addFlash('success', 'Successfully created Account and Contact in Salesforce!');
                $this->addFlash('info', 'Account ID: ' . $result['account_id'] . ', Contact ID: ' . $result['contact_id']);
            } else {
                $this->addFlash('error', 'Failed to create Salesforce records. Please check your credentials and try again.');
            }

            return $this->redirectToRoute('salesforce_integration');
        }

        return $this->render('salesforce/integration.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
