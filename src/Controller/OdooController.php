<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\InventoryField;
use App\Entity\InventoryItem;
use App\Repository\ApiTokenRepository;
use App\Service\ApiTokenService;
use App\Service\OdooService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    private EntityManagerInterface $entityManager;

    public function __construct(
        OdooService $odooService, 
        ApiTokenService $apiTokenService, 
        HttpClientInterface $httpClient, 
        EntityManagerInterface $entityManager
    ) {
        $this->odooService = $odooService;
        $this->apiTokenService = $apiTokenService;
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
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

    #[Route('/test-import', name: 'odoo_test_import', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function testImport(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $apiToken = $request->request->get('api_token');
            
            if (!$apiToken) {
                return new Response('API token is required');
            }

            return new Response('Token received: ' . substr($apiToken, 0, 8) . '...');
        }

        return new Response('
            <form method="post">
                <input type="text" name="api_token" placeholder="Enter API token">
                <button type="submit">Test</button>
            </form>
        ');
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

            // Validate API token directly without service
            $repository = $this->entityManager->getRepository(ApiToken::class);
            $token = $repository->findOneBy(['token' => $apiToken, 'isActive' => true]);
            
            if (!$token) {
                $this->addFlash('error', 'Invalid or expired API token');
                return $this->redirectToRoute('odoo_import_form');
            }

            // Get aggregated data
            $aggregatedData = $this->getAggregatedData($token);
            
            if (!$aggregatedData) {
                $this->addFlash('error', 'Failed to retrieve aggregated data');
                return $this->redirectToRoute('odoo_import_form');
            }

            // Import to Odoo - try direct service call
            try {
                $success = $this->odooService->importInventoryData($apiToken, $aggregatedData);

                if ($success) {
                    $this->addFlash('success', '✅ Inventory data successfully exported to Odoo!');
                    $this->addFlash('info', sprintf(
                        '📦 Inventory "%s" data has been stored in Odoo with %d items and %d fields.',
                        $aggregatedData['inventory']['title'],
                        $aggregatedData['inventory']['total_items'],
                        $aggregatedData['inventory']['total_fields']
                    ));
                    $this->addFlash('info', '🔗 Data is stored in your main contact record with existing Odoo inventory reference. Check the contact details for complete information.');
                } else {
                    $this->addFlash('error', '❌ Failed to export data to Odoo. Please check your Odoo configuration.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Odoo service error: ' . $e->getMessage());
            }

            return $this->redirectToRoute('odoo_import_form');
        }

        return $this->render('odoo/import.html.twig');
    }

    private function getAggregatedData(ApiToken $apiToken): ?array
    {
        try {
            // Direct call to API logic instead of HTTP request
            $inventory = $apiToken->getInventory();
            
            // Get repositories directly
            $itemRepository = $this->entityManager->getRepository(InventoryItem::class);
            $fieldRepository = $this->entityManager->getRepository(InventoryField::class);
            
            // Get data directly
            $items = $itemRepository->findBy(['inventory' => $inventory]);
            $fields = $fieldRepository->findBy(['inventory' => $inventory]);

            $aggregatedData = [
                'inventory' => [
                    'id' => $inventory->getId(),
                    'title' => $inventory->getTitle(),
                    'description' => $inventory->getDescription(),
                    'category' => $inventory->getCategory(),
                    'created_at' => $inventory->getCreatedAt()->format('Y-m-d H:i:s'),
                    'total_items' => count($items),
                    'total_fields' => count($fields),
                ],
                'fields' => array_map(function($field) {
                    return [
                        'id' => $field->getId(),
                        'title' => $field->getFieldName(),
                        'type' => $field->getFieldType(),
                        'required' => $field->isRequired(),
                        'order_index' => $field->getFieldOrder(),
                        'aggregation' => [
                            'total_values' => 0,
                            'empty_values' => 0,
                            'unique_values' => []
                        ]
                    ];
                }, $fields),
                'items' => array_map(function($item) {
                    return [
                        'id' => $item->getId(),
                        'custom_id' => $item->getCustomId(),
                        'created_at' => $item->getCreatedAt()->format('Y-m-d H:i:s')
                    ];
                }, $items),
                'aggregated_results' => []
            ];

            return $aggregatedData;
            
        } catch (\Exception $e) {
            error_log('Odoo API call error: ' . $e->getMessage());
            return null;
        }
    }
}
