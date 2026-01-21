<?php

namespace App\Controller;

use App\Form\SupportTicketType;
use App\Service\CloudStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support')]
class SupportTicketController extends AbstractController
{
    private CloudStorageService $cloudStorageService;

    public function __construct(CloudStorageService $cloudStorageService)
    {
        $this->cloudStorageService = $cloudStorageService;
    }

    #[Route('/ticket/new', name: 'support_ticket_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get inventory and page info from request
        $inventoryTitle = $request->query->get('inventory');
        $pageUrl = $request->headers->get('referer') ?: $request->query->get('page_url', '');

        $form = $this->createForm(SupportTicketType::class, [
            'inventory_title' => $inventoryTitle,
            'page_url' => $pageUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Create ticket data
            $ticketData = [
                'reported_by' => [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'id' => $user->getId(),
                ],
                'inventory' => $data['inventory_title'] ?: 'N/A',
                'link' => $data['page_url'] ?: 'N/A',
                'priority' => $data['priority'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'ticket_id' => uniqid('TICKET-', true),
                'admin_emails' => $this->getAdminEmails(),
            ];

            // Upload to cloud storage
            $result = $this->cloudStorageService->uploadTicket($ticketData);

            if ($result['success']) {
                $this->addFlash('success', 'Support ticket created successfully! Your ticket ID is: ' . $ticketData['ticket_id']);
                $this->addFlash('info', 'The support team has been notified and will respond within 24 hours.');
            } else {
                $this->addFlash('error', 'Failed to create support ticket. Please try again or contact support directly.');
            }

            return $this->redirectToRoute('support_ticket_new');
        }

        return $this->render('support/ticket_new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'inventory_title' => $inventoryTitle,
            'page_url' => $pageUrl,
        ]);
    }

    private function getAdminEmails(): array
    {
        // In a real application, you would fetch admin emails from the database
        // For now, return default admin emails
        return [
            'admin@inventoryapp.com',
            'support@inventoryapp.com',
        ];
    }
}
