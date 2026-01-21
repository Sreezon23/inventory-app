<?php

namespace App\Controller;

use App\Service\OdooService;
use App\Service\ApiTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/odoo')]
class OdooController extends AbstractController
{
    private OdooService $odooService;
    private ApiTokenService $apiTokenService;
    private HttpClientInterface $httpClient;

    public function __construct(OdooService $odooService, ApiTokenService $apiTokenService, HttpClientInterface $httpClient)
    {
        $this->odooService = $odooService;
        $this->apiTokenService = $apiTokenService;
        $this->httpClient = $httpClient;
    }

    #[Route('/import', name: 'odoo_import', methods: ['POST'])]
    public function importFromApiToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['api_token'])) {
            return new JsonResponse(['error' => 'API token is required'], 400);
        }

        // Validate API token
        $apiToken = $this->apiTokenService->validateToken($data['api_token']);
        if (!$apiToken) {
            return new JsonResponse(['error' => 'Invalid or expired API token'], 401);
        }

        // Get aggregated data from our own API
        $aggregatedData = $this->getAggregatedData($data['api_token']);
        
        if (!$aggregatedData) {
            return new JsonResponse(['error' => 'Failed to retrieve aggregated data'], 500);
        }

        // Import to Odoo
        $success = $this->odooService->importInventoryData($data['api_token'], $aggregatedData);

        if ($success) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Data successfully imported to Odoo',
                'inventory' => $aggregatedData['inventory']['title'],
                'fields_count' => count($aggregatedData['fields']),
            ]);
        } else {
            return new JsonResponse(['error' => 'Failed to import data to Odoo'], 500);
        }
    }

    #[Route('/import-form', name: 'odoo_import_form', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function importForm(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $apiToken = $request->request->get('api_token');
            
            if (!$apiToken) {
                $this->addFlash('error', 'API token is required');
                return $this->redirectToRoute('odoo_import_form');
            }

            // Validate API token
            $token = $this->apiTokenService->validateToken($apiToken);
            if (!$token) {
                $this->addFlash('error', 'Invalid or expired API token');
                return $this->redirectToRoute('odoo_import_form');
            }

            // Get aggregated data
            $aggregatedData = $this->getAggregatedData($apiToken);
            
            if (!$aggregatedData) {
                $this->addFlash('error', 'Failed to retrieve aggregated data');
                return $this->redirectToRoute('odoo_import_form');
            }

            // Import to Odoo
            $success = $this->odooService->importInventoryData($apiToken, $aggregatedData);

            if ($success) {
                $this->addFlash('success', 'Inventory data successfully imported to Odoo!');
                $this->addFlash('info', sprintf(
                    'Imported "%s" with %d fields to Odoo.',
                    $aggregatedData['inventory']['title'],
                    count($aggregatedData['fields'])
                ));
            } else {
                $this->addFlash('error', 'Failed to import data to Odoo. Please check your Odoo configuration.');
            }

            return $this->redirectToRoute('odoo_import_form');
        }

        return $this->render('odoo/import.html.twig');
    }

    private function getAggregatedData(string $apiToken): ?array
    {
        try {
            // Call our own API to get aggregated data
            $response = $this->httpClient->request('GET', $this->generateUrl('api_inventory_aggregated', ['token' => $apiToken], true));
            
            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return null;
    }
}
