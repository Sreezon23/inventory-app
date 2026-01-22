<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\Inventory;
use App\Service\ApiTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/debug')]
class DebugController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/test-simple/{id}', name: 'debug_simple', methods: ['GET'])]
    public function testSimple(Inventory $inventory): Response
    {
        $debug = [
            'inventory_id' => $inventory->getId(),
            'inventory_title' => $inventory->getTitle(),
            'creator_id' => $inventory->getCreator() ? $inventory->getCreator()->getId() : null,
            'creator_name' => $inventory->getCreator() ? $inventory->getCreator()->getName() : null,
        ];
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
    
    #[Route('/test-repository/{id}', name: 'debug_repository', methods: ['GET'])]
    public function testRepository(Inventory $inventory): Response
    {
        $debug = [
            'inventory_id' => $inventory->getId(),
            'inventory_title' => $inventory->getTitle(),
        ];
        
        try {
            $repository = $this->entityManager->getRepository(ApiToken::class);
            $debug['repository_class'] = get_class($repository);
            $debug['status'] = 'success';
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['status'] = 'error';
        }
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
    
    #[Route('/test-repository-simple/{id}', name: 'debug_repository_simple', methods: ['GET'])]
    public function testRepositorySimple(Inventory $inventory): Response
    {
        $debug = [
            'inventory_id' => $inventory->getId(),
            'inventory_title' => $inventory->getTitle(),
        ];
        
        try {
            $repository = $this->entityManager->getRepository(ApiToken::class);
            $tokens = $repository->findAll(); // Simple test
            $debug['all_tokens_count'] = count($tokens);
            $debug['status'] = 'success';
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
            $debug['status'] = 'error';
        }
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
    
    #[Route('/test-repository-full/{id}', name: 'debug_repository_full', methods: ['GET'])]
    public function testRepositoryFull(Inventory $inventory): Response
    {
        $debug = [
            'inventory_id' => $inventory->getId(),
            'inventory_title' => $inventory->getTitle(),
        ];
        
        try {
            $repository = $this->entityManager->getRepository(ApiToken::class);
            
            // Test step by step
            $qb = $repository->createQueryBuilder('a');
            $debug['step1_qb_created'] = 'success';
            
            $qb->where('a.inventory = :inventory');
            $debug['step2_where_added'] = 'success';
            
            $qb->andWhere('a.isActive = :active');
            $debug['step3_andwhere_added'] = 'success';
            
            $qb->setParameter('inventory', $inventory);
            $debug['step4_param1_set'] = 'success';
            
            $qb->setParameter('active', true);
            $debug['step5_param2_set'] = 'success';
            
            $qb->orderBy('a.createdAt', 'DESC');
            $debug['step6_order_added'] = 'success';
            
            $query = $qb->getQuery();
            $debug['step7_query_created'] = 'success';
            
            $tokens = $query->getResult();
            $debug['tokens_count'] = count($tokens);
            $debug['status'] = 'success';
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
            $debug['status'] = 'error';
        }
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
    
    #[Route('/test-api-simple/{token}', name: 'debug_api_simple', methods: ['GET'])]
    public function testApiSimple(string $token): Response
    {
        $debug = [
            'token' => substr($token, 0, 8) . '...',
            'status' => 'testing',
        ];
        
        try {
            $repository = $this->entityManager->getRepository(ApiToken::class);
            $apiToken = $repository->findOneBy(['token' => $token, 'isActive' => true]);
            
            if (!$apiToken) {
                $debug['error'] = 'Token not found or inactive';
                $debug['status'] = 'error';
            } else {
                $debug['token_found'] = true;
                $debug['inventory_id'] = $apiToken->getInventory()->getId();
                $debug['inventory_title'] = $apiToken->getInventory()->getTitle();
                $debug['status'] = 'success';
            }
            
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['status'] = 'error';
        }
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
}
