<?php

namespace App\Controller;

use App\Service\OdooService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/odoo')]
class OdooMonitorController extends AbstractController
{
    private OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    #[Route('/status', name: 'odoo_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function checkOdooStatus(): JsonResponse
    {
        try {
            $userId = $this->odooService->authenticate();
            
            if ($userId) {
                // Test basic read operation
                $testRead = $this->odooService->executeKw($userId, 'res.partner', 'search', [[['id', '=', 1]]]);
                
                return new JsonResponse([
                    'status' => 'connected',
                    'user_id' => $userId,
                    'read_permissions' => !empty($testRead),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'odoo_url' => $_ENV['ODOO_URL'] ?? 'Not configured'
                ]);
            } else {
                return new JsonResponse([
                    'status' => 'authentication_failed',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'error' => 'Could not authenticate with Odoo'
                ], 401);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/recent-activity', name: 'odoo_recent_activity', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getRecentActivity(): JsonResponse
    {
        try {
            $userId = $this->odooService->authenticate();
            
            if (!$userId) {
                return new JsonResponse(['error' => 'Not authenticated with Odoo'], 401);
            }

            // Get recent contacts
            $recentContacts = $this->odooService->executeKw($userId, 'res.partner', 'search_read', [
                [['create_date', '>=', date('Y-m-d', strtotime('-7 days'))]],
                ['name', 'create_date', 'email'],
                ['limit' => 10, 'order' => 'create_date desc']
            ]);

            // Get recent products
            $recentProducts = $this->odooService->executeKw($userId, 'product.product', 'search_read', [
                [['create_date', '>=', date('Y-m-d', strtotime('-7 days'))]],
                ['name', 'create_date', 'default_code'],
                ['limit' => 10, 'order' => 'create_date desc']
            ]);

            return new JsonResponse([
                'status' => 'success',
                'timestamp' => date('Y-m-d H:i:s'),
                'recent_contacts' => $recentContacts,
                'recent_products' => $recentProducts,
                'total_contacts' => count($recentContacts),
                'total_products' => count($recentProducts)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
